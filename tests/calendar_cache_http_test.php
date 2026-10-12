<?php
declare(strict_types=1);
$_SERVER['HTTP_HOST'] = 'localtest.me';
$_SERVER['HTTPS'] = 'on';
require_once __DIR__ . '/mcp_test_helpers.php';
use dbObject\{ArrayApplication, ArrayDbObject, CalendarShare, Event, EventInvitation, OrganizationApplication, PerformanceRequest};

function calendarCacheHttp(array $items, string $path, string $method, string $body = '', array $extraHeaders = []): array
{
    $headers = [];
    $curl = curl_init('https://localtest.me' . $path);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_USERPWD => $items['user']->get('email') . ':' . $items['password'], CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_RESOLVE => ['localtest.me:443:127.0.0.1'], CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_TIMEOUT => 20, CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/xml', 'Depth: 0'], $extraHeaders),
        CURLOPT_POSTFIELDS => $body, CURLOPT_HEADERFUNCTION => static function ($curl, $line) use (&$headers) {
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) $headers[strtolower(trim($parts[0]))] = trim($parts[1]);
            return strlen($line);
        }]);
    $response = curl_exec($curl);
    mcpCheck(is_string($response), 'Local HTTP request failed: ' . curl_error($curl));
    return ['status' => curl_getinfo($curl, CURLINFO_RESPONSE_CODE), 'body' => $response, 'headers' => $headers];
}
function calendarCacheLastQueryCount(int $userId, int $organizationId): int
{
    $rows = new class extends ArrayDbObject { public static function objectName() { return PerformanceRequest::class; } };
    $rows->loadHydrated(['where' => [['field' => 'IDuser', 'value' => $userId], ['field' => 'method', 'value' => 'PROPFIND']],
        'orderBy' => [['field' => 'id', 'dir' => 'DESC']], 'limit' => 1]);
    mcpCheck(count($rows) === 1, 'Performance sample must exist.');
    mcpCheck((int)$rows[0]->get('IDorganization') === $organizationId, 'DAV requests must be attributed to their validated organization.');
    return (int)$rows[0]->get('query_count');
}
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
$items = mcpFixtures();
try {
    $apps = new ArrayApplication();
    $apps->load(['where' => [['field' => 'hash', 'value' => 'calendar']], 'limit' => 1]);
    $items['calendar_app'] = mcpFixture(OrganizationApplication::class,
        ['IDorganization' => $items['org']->getId(), 'IDapplication' => $apps[0]->getId(), 'active' => 1]);
    for ($i = 0; $i < 12; $i++) {
        $items['event_' . $i] = mcpFixture(Event::class, ['IDuser' => $items['user']->getId(), 'IDorganization' => $items['org']->getId(),
            'IDholon' => $items['root']->getId(), 'title' => 'Cache appointment ' . $i, 'active' => 1, 'status' => Event::STATUS_CONFIRMED,
            'start_at' => new DateTimeImmutable('tomorrow 09:00'), 'end_at' => new DateTimeImmutable('tomorrow 10:00')]);
        $items['invitation_' . $i] = mcpFixture(EventInvitation::class, ['IDevent' => $items['event_' . $i]->getId(),
            'invitation_type' => 'user', 'IDuser' => $items['user']->getId()]);
    }
    $path = '/omo/api/caldav/calendars/' . $items['user']->getId() . '/organization-' . $items['org']->getId() . '/';
    $xml = '<d:propfind xmlns:d="DAV:"><d:prop><d:displayname/><d:sync-token/></d:prop></d:propfind>';
    $cold = calendarCacheHttp($items, $path, 'PROPFIND', $xml);
    $coldQueries = calendarCacheLastQueryCount((int)$items['user']->getId(), (int)$items['org']->getId());
    $warm = calendarCacheHttp($items, $path, 'PROPFIND', $xml);
    $warmQueries = calendarCacheLastQueryCount((int)$items['user']->getId(), (int)$items['org']->getId());
    mcpCheck($cold['status'] === 207 && ($cold['headers']['x-omo-caldav-cache'] ?? '') === 'MISS', 'Standard CalDAV route fills the cache.');
    mcpCheck(($warm['headers']['x-omo-caldav-cache'] ?? '') === 'HIT' && $cold['body'] === $warm['body'], 'Repeated discovery reuses identical content.');
    mcpCheck($warmQueries < $coldQueries / 2, 'A cache hit must avoid most event queries.');
    $items['share'] = CalendarShare::findOrCreateScopedCalendarForUser((int)$items['user']->getId(), (int)$items['org']->getId(),
        (int)$items['root']->getId(), 'contextual', 'Cache subscription');
    $feed = '/common/calendar/feed.php?token=' . $items['share']->get('token');
    $icsCold = calendarCacheHttp($items, $feed, 'GET');
    $icsWarm = calendarCacheHttp($items, $feed, 'GET', '', ['If-None-Match: ' . $icsCold['headers']['etag']]);
    mcpCheck($icsCold['status'] === 200 && str_contains($icsCold['body'], 'Cache appointment 0'), 'The subscription exports the invited events.');
    mcpCheck(($icsCold['headers']['x-omo-ics-cache'] ?? '') === 'MISS'
        && ($icsWarm['headers']['x-omo-ics-cache'] ?? '') === 'HIT' && $icsWarm['status'] === 304 && $icsWarm['body'] === '',
        'An unchanged ICS subscription returns 304 using cached content.');
    $items['event_0']->set('title', 'Changed appointment');
    mcpCheck(!empty($items['event_0']->save()['status']), 'Modify the fixture appointment.');
    $changed = calendarCacheHttp($items, $path, 'PROPFIND', $xml);
    $icsChanged = calendarCacheHttp($items, $feed, 'GET', '', ['If-None-Match: ' . $icsCold['headers']['etag']]);
    mcpCheck(($changed['headers']['x-omo-caldav-cache'] ?? '') === 'MISS', 'Event changes invalidate standard CalDAV responses.');
    mcpCheck($icsChanged['status'] === 200 && str_contains($icsChanged['body'], 'Changed appointment')
        && ($icsChanged['headers']['x-omo-ics-cache'] ?? '') === 'MISS', 'ICS changes invalidate both cached content and ETag.');
    CalendarShare::revokeForUser((int)$items['share']->getId(), (int)$items['user']->getId());
    mcpCheck(calendarCacheHttp($items, $feed, 'GET')['status'] === 404, 'Revoked shares never serve cached content.');
    $items['membership']->set('active', 0);
    $items['membership']->save();
    mcpCheck(calendarCacheHttp($items, $path, 'PROPFIND', $xml)['status'] === 404, 'Membership revocation is checked before any cache hit.');
    echo "calendar_cache_http_test: OK (CalDAV cold=$coldQueries SQL, hit=$warmQueries SQL; ICS 304, invalidation and revocation)\n";
} finally { mcpCleanup($items); }
