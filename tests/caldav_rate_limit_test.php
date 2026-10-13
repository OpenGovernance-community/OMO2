<?php
declare(strict_types=1);
$_SERVER['HTTP_HOST'] = 'localtest.me';
$_SERVER['HTTPS'] = 'on';
require_once __DIR__ . '/mcp_test_helpers.php';
require_once dirname(__DIR__) . '/common/caldav.php';
require_once __DIR__ . '/caldav_http_helpers.php';
use dbObject\{ArrayApplication, ArrayDbObject, CalDavCache, CalDavRateLimit, OrganizationApplication, PerformanceRequest};

function rateTicket(array $items, string $path, string $method, string $body = '', string $depth = '0'): ?array
{
    $_SERVER['PHP_AUTH_USER'] = $items['user']->get('email');
    $_SERVER['PHP_AUTH_PW'] = $items['password'];
    $_SERVER['REQUEST_METHOD'] = $method;
    $_SERVER['REQUEST_URI'] = $path;
    $_SERVER['HTTP_DEPTH'] = $depth;
    $GLOBALS['commonCardDavRawInput'] = $body;
    return commonCalDavReadLimitTicket();
}
function rateFile(array $ticket): string { return CalDavRateLimit::directory() . '/' . $ticket['key'] . '.json'; }
function rateEndBurst(array $ticket): array
{
    $path = rateFile($ticket);
    mcpCheck(is_file($path), 'Only successful HTTP requests create a verified limit record.');
    $record = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    $record['burst_until'] = time() - 1;
    $record['expires_at'] = time() + 800;
    file_put_contents($path, json_encode($record, JSON_THROW_ON_ERROR), LOCK_EX);
    return $record;
}
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
$items = mcpFixtures();
$tickets = [];
try {
    $apps = new ArrayApplication();
    $apps->load(['where' => [['field' => 'hash', 'value' => 'calendar']], 'limit' => 1]);
    $items['calendar_app'] = mcpFixture(OrganizationApplication::class,
        ['IDorganization' => $items['org']->getId(), 'IDapplication' => $apps[0]->getId(), 'active' => 1]);
    $principal = '/omo/api/caldav/principals/' . $items['user']->getId() . '/';
    $xml = '<d:propfind xmlns:d="DAV:"><d:prop><d:displayname/></d:prop></d:propfind>';
    $ticket = $tickets[] = rateTicket($items, $principal, 'PROPFIND', $xml);
    mcpCheck($ticket !== null && $ticket['window'] === 900 && $ticket['burst'] === 30, 'Default policy is a 30-second burst inside a 15-minute window.');
    mcpCheck(CalDavRateLimit::inspect($ticket['key']) === null && !is_file(rateFile($ticket)), 'Unverified requests create no records.');
    $first = calendarCacheHttp($items, $principal, 'PROPFIND', $xml);
    $burst = calendarCacheHttp($items, $principal, 'PROPFIND', $xml);
    mcpCheck($first['status'] === 207 && $burst['status'] === 207, 'Initial synchronization can repeat reads within its burst.');
    $stored = rateEndBurst($ticket);
    $blocked = calendarCacheHttp($items, $principal, 'PROPFIND', $xml);
    mcpCheck($blocked['status'] === 429 && (int)$blocked['headers']['retry-after'] > 0
        && (int)$blocked['headers']['retry-after'] <= 800 && str_contains($blocked['headers']['cache-control'], 'no-store'), 'Repeated discovery is rejected with Retry-After and no-store.');
    mcpCheck(!str_contains($blocked['body'], (string)$items['user']->get('email')), '429 contains no private calendar data.');
    $samples = new class extends ArrayDbObject { public static function objectName() { return PerformanceRequest::class; } };
    $samples->loadHydrated(['where' => [['field' => 'IDuser', 'value' => $items['user']->getId()], ['field' => 'status_code', 'value' => 429]],
        'orderBy' => [['field' => 'id', 'dir' => 'DESC']], 'limit' => 1]);
    mcpCheck(count($samples) === 1 && (int)$samples[0]->get('query_count') === 0, 'Pre-auth rejection is attributed to the verified account without SQL reads.');
    calendarCacheHttp($items, $principal, 'PROPFIND', $xml);
    mcpCheck(json_decode(file_get_contents(rateFile($ticket)), true)['expires_at'] === $stored['expires_at'], 'Rejected attempts never extend the window.');
    $wrong = ['user' => $items['user'], 'password' => $items['password'] . '-invalid'];
    mcpCheck(calendarCacheHttp($wrong, $principal, 'PROPFIND', $xml)['status'] === 401, 'An unverified password does not match another request limit.');
    $wrongTicket = $tickets[] = rateTicket($wrong, $principal, 'PROPFIND', $xml);
    mcpCheck(!is_file(rateFile($wrongTicket)), 'Failed authentication never seeds limits.');
    $missingPath = '/omo/api/caldav/calendars/999999999/';
    $missingTicket = $tickets[] = rateTicket($items, $missingPath, 'PROPFIND', $xml);
    mcpCheck(calendarCacheHttp($items, $missingPath, 'PROPFIND', $xml)['status'] === 404
        && !is_file(rateFile($missingTicket)), 'Denied resources never seed limits even with valid credentials.');
    mcpCheck(rateTicket($items, $principal, 'PROPFIND', $xml, '1')['key'] !== $ticket['key'], 'Depth is part of request identity.');
    mcpCheck(rateTicket($items, $principal, 'PROPFIND', $xml . ' ')['key'] !== $ticket['key'], 'Different bodies are not suppressed.');
    foreach (['REPORT', 'GET', 'HEAD', 'PUT', 'DELETE', 'PROPPATCH'] as $method) {
        mcpCheck(rateTicket($items, $principal, $method, $xml) === null, 'Event transfers and writes bypass discovery limiting.');
    }
    $_ENV['CALDAV_READ_LIMIT_ENABLED'] = 'false';
    mcpCheck(rateTicket($items, $principal, 'PROPFIND', $xml) === null, 'Configuration can disable limiting.');
    unset($_ENV['CALDAV_READ_LIMIT_ENABLED']);
    unset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
    mcpCheck(commonCalDavReadLimitTicket() === null, 'Session-only or anonymous requests do not use credential records.');
    $optionTicket = $tickets[] = rateTicket($items, $principal, 'OPTIONS');
    mcpCheck(calendarCacheHttp($items, $principal, 'OPTIONS')['status'] === 204, 'OPTIONS is independent from PROPFIND.');
    rateEndBurst($optionTicket);
    mcpCheck(calendarCacheHttp($items, $principal, 'OPTIONS')['status'] === 429, 'Repeated OPTIONS also avoid authentication.');

    $calendar = '/omo/api/caldav/calendars/' . $items['user']->getId() . '/organization-' . $items['org']->getId() . '/';
    $calendarTicket = $tickets[] = rateTicket($items, $calendar, 'PROPFIND', $xml);
    mcpCheck(calendarCacheHttp($items, $calendar, 'PROPFIND', $xml)['status'] === 207, 'Calendar discovery initially succeeds.');
    rateEndBurst($calendarTicket);
    mcpCheck(calendarCacheHttp($items, $calendar, 'PROPFIND', $xml)['status'] === 429, 'Calendar discovery is throttled after its burst.');
    $patch = '<d:propertyupdate xmlns:d="DAV:"><d:set><d:prop><d:displayname>New name</d:displayname></d:prop></d:set></d:propertyupdate>';
    mcpCheck(calendarCacheHttp($items, $calendar, 'PROPPATCH', $patch)['status'] === 207, 'Actual metadata writes are never throttled.');
    $refreshed = calendarCacheHttp($items, $calendar, 'PROPFIND', $xml);
    mcpCheck($refreshed['status'] === 207 && str_contains($refreshed['body'], 'New name'), 'Revision changes reopen discovery immediately.');

    $homePath = '/omo/api/caldav/calendars/' . $items['user']->getId() . '/';
    $homeTicket = $tickets[] = rateTicket($items, $homePath, 'PROPFIND', $xml, '1');
    mcpCheck(calendarCacheHttp($items, $homePath, 'PROPFIND', $xml, ['Depth: 1'])['status'] === 207, 'Home discovery succeeds.');
    rateEndBurst($homeTicket);
    mcpCheck(calendarCacheHttp($items, $homePath, 'PROPFIND', $xml, ['Depth: 1'])['status'] === 429, 'Home discovery is throttled.');
    CalDavCache::invalidateOrganization((int)$items['org']->getId());
    mcpCheck(calendarCacheHttp($items, $homePath, 'PROPFIND', $xml, ['Depth: 1'])['status'] === 207, 'Home discovery tracks organization revisions.');

    $record = json_decode(file_get_contents(rateFile($ticket)), true);
    $record['expires_at'] = time() - 1;
    file_put_contents(rateFile($ticket), json_encode($record), LOCK_EX);
    mcpCheck(calendarCacheHttp($items, $principal, 'PROPFIND', $xml)['status'] === 207, 'Expiry permits authenticated refresh again.');
    $newRecord = json_decode(file_get_contents(rateFile($ticket)), true);
    mcpCheck($newRecord['expires_at'] > time() && !str_contains(file_get_contents(rateFile($ticket)), $items['password']), 'New window is stored without credentials.');

    $unitKey = hash('sha256', 'rate-unit-' . bin2hex(random_bytes(8)));
    $unitTicket = $tickets[] = ['key' => $unitKey];
    $now = time();
    mcpCheck(CalDavRateLimit::remember($unitKey, 1, 0, [], 900, 30, $now), 'Store unit window.');
    CalDavRateLimit::remember($unitKey, 1, 0, [], 900, 30, $now + 10);
    mcpCheck(CalDavRateLimit::inspect($unitKey, $now + 29) === null
        && CalDavRateLimit::inspect($unitKey, $now + 30)['retry_after'] === 870
        && CalDavRateLimit::inspect($unitKey, $now + 900) === null, 'Burst boundary and non-sliding expiry.');
    $lock = fopen(rateFile($unitTicket), 'r+');
    flock($lock, LOCK_EX);
    mcpCheck(CalDavRateLimit::inspect($unitKey, $now + 31) === null, 'Contended storage falls back to authentication instead of waiting.');
    fclose($lock);
    file_put_contents(rateFile($unitTicket), 'invalid');
    mcpCheck(CalDavRateLimit::inspect($unitKey) === null, 'Corrupt storage fails open to normal authentication.');
    touch(rateFile($unitTicket), time() - 90000);
    CalDavRateLimit::cleanup();
    mcpCheck(!is_file(rateFile($unitTicket)), 'Cron cleanup removes stale invalid records.');
    $unavailablePath = tempnam(sys_get_temp_dir(), 'caldav-limit-test-');
    $previousCachePath = $_ENV['CALDAV_CACHE_PATH'] ?? null;
    try {
        $_ENV['CALDAV_CACHE_PATH'] = $unavailablePath;
        mcpCheck(CalDavRateLimit::inspect($unitKey) === null
            && !CalDavRateLimit::remember($unitKey, 1, 0, [], 900, 30), 'Unavailable storage preserves normal authentication.');
    } finally {
        if ($previousCachePath === null) unset($_ENV['CALDAV_CACHE_PATH']);
        else $_ENV['CALDAV_CACHE_PATH'] = $previousCachePath;
        unlink($unavailablePath);
    }
    echo 'caldav_rate_limit_test: OK (HTTP burst/429, auth isolation, no SQL, revisions, writes, expiry and cleanup; first='
        . round($first['seconds'] * 1000) . ' ms, authenticated repeat=' . round($burst['seconds'] * 1000)
        . ' ms, blocked=' . round($blocked['seconds'] * 1000) . " ms)\n";
} finally {
    foreach ($tickets as $cleanupTicket) { $path = rateFile($cleanupTicket); if (is_file($path)) unlink($path); }
    mcpCleanup($items);
}
