<?php
declare(strict_types=1);
$_SERVER['HTTP_HOST'] = 'localtest.me';
$_SERVER['HTTPS'] = 'on';
require_once __DIR__ . '/mcp_test_helpers.php';
require_once dirname(__DIR__) . '/common/caldav.php';
use dbObject\{ArrayApplication, ArrayDbObject, DbObject, Event, EventInvitation, OrganizationApplication, PerformanceRequest, UserOrganization};

function caldavPatchQueryCount(int $userId): int
{
    $rows = new class extends ArrayDbObject { public static function objectName() { return PerformanceRequest::class; } };
    $rows->loadHydrated(['where' => [['field' => 'IDuser', 'value' => $userId], ['field' => 'method', 'value' => 'PROPPATC']],
        'orderBy' => [['field' => 'id', 'dir' => 'DESC']], 'limit' => 1]);
    mcpCheck(count($rows) === 1, 'Performance sample must exist.');
    return (int)$rows[0]->get('query_count');
}

function caldavPatchHttp(array $items, string $path, string $method, string $body = ''): array
{
    $headers = [];
    $curl = curl_init('https://localtest.me/omo/api/caldav' . $path);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_USERAGENT => 'OMO-CalDAV-regression-test',
        CURLOPT_USERPWD => $items['user']->get('email') . ':' . $items['password'], CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_RESOLVE => ['localtest.me:443:127.0.0.1'], CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_TIMEOUT => 20, CURLOPT_HTTPHEADER => ['Content-Type: application/xml; charset=UTF-8', 'Depth: 0'],
        CURLOPT_POSTFIELDS => $body, CURLOPT_HEADERFUNCTION => static function ($curl, $line) use (&$headers) {
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) $headers[strtolower(trim($parts[0]))] = trim($parts[1]);
            return strlen($line);
        }]);
    $response = curl_exec($curl);
    mcpCheck(is_string($response), 'Local HTTP request failed: ' . curl_error($curl));
    $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $xml = new DOMDocument();
    if ($response !== '' && $response[0] === '<') mcpCheck($xml->loadXML($response, LIBXML_NONET), 'Response XML must be valid.');
    return ['status' => $status, 'body' => $response, 'headers' => $headers, 'xml' => $xml];
}
function caldavPatchBody(string $instructions): string
{
    return '<d:propertyupdate xmlns:d="DAV:" xmlns:i="http://apple.com/ns/ical/">' . $instructions . '</d:propertyupdate>';
}
function caldavPatchRead(array $items, string $path): array
{
    return caldavPatchHttp($items, $path, 'PROPFIND', '<d:propfind xmlns:d="DAV:" xmlns:i="http://apple.com/ns/ical/">'
        . '<d:prop><d:displayname/><i:calendar-color/><i:calendar-order/><d:sync-token/><d:current-user-privilege-set/></d:prop></d:propfind>');
}
function caldavPatchValue(array $response, string $namespace, string $name): ?string
{
    $nodes = $response['xml']->getElementsByTagNameNS($namespace, $name);
    return $nodes->length ? $nodes->item(0)->textContent : null;
}

if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
$items = mcpFixtures();
try {
    $apps = new ArrayApplication();
    $apps->load(['where' => [['field' => 'hash', 'value' => 'calendar']], 'limit' => 1]);
    mcpCheck(count($apps) === 1, 'Calendar application must exist.');
    $items['calendar_app'] = mcpFixture(OrganizationApplication::class,
        ['IDorganization' => $items['org']->getId(), 'IDapplication' => $apps[0]->getId(), 'active' => 1]);
    $items['membership']->set('parameters', ['keep' => 'existing preference']);
    mcpCheck(!empty($items['membership']->save()['status']), 'Save unrelated personal settings.');
    $path = '/calendars/' . $items['user']->getId() . '/organization-' . $items['org']->getId() . '/';
    $scopedBase = '/scoped/' . $items['org']->getId() . '/' . $items['root']->getId() . '/contextual/2563eb/';
    $patchPaths = [$path, $scopedBase . 'editable/', $scopedBase . 'readonly/'];
    $probe = caldavPatchBody('<d:set><d:prop><i:calendar-color>#123456</i:calendar-color></d:prop></d:set>');
    $baseline = [];
    foreach ($patchPaths as $patchPath) {
        caldavPatchHttp($items, $patchPath, 'PROPPATCH', $probe);
        caldavPatchHttp($items, $patchPath, 'PROPPATCH', $probe);
        $baseline[$patchPath] = caldavPatchQueryCount((int)$items['user']->getId());
    }
    for ($i = 0; $i < 12; $i++) {
        $items['event_' . $i] = mcpFixture(Event::class, ['IDuser' => $items['user']->getId(), 'IDorganization' => $items['org']->getId(),
            'IDholon' => $items['root']->getId(), 'title' => 'Metadata performance event ' . $i, 'active' => 1, 'status' => Event::STATUS_CONFIRMED,
            'start_at' => new DateTimeImmutable('tomorrow 09:00'), 'end_at' => new DateTimeImmutable('tomorrow 10:00')]);
        $items['invitation_' . $i] = mcpFixture(EventInvitation::class, ['IDevent' => $items['event_' . $i]->getId(),
            'invitation_type' => 'user', 'IDuser' => $items['user']->getId()]);
    }
    foreach ($patchPaths as $patchPath) {
        $result = caldavPatchHttp($items, $patchPath, 'PROPPATCH', $probe);
        $queries = caldavPatchQueryCount((int)$items['user']->getId());
        mcpCheck($result['status'] === 207 && str_contains($result['body'], '200 OK'), 'Metadata updates still succeed with events.');
        mcpCheck($queries === $baseline[$patchPath], 'PROPPATCH queries must not scale with event count.');
        echo 'PROPPATCH ' . (str_contains($patchPath, 'scoped') ? basename($patchPath) : 'organization') . ': ' . $queries . " SQL for 0 or 12 events\n";
    }
    $options = caldavPatchHttp($items, $path, 'OPTIONS');
    mcpCheck($options['status'] === 204 && str_contains($options['headers']['allow'] ?? '', 'PROPPATCH'), 'OPTIONS must advertise PROPPATCH.');
    $set = caldavPatchHttp($items, $path, 'PROPPATCH', caldavPatchBody('<d:set><d:prop><d:displayname>My &amp; calendar</d:displayname>'
        . '<i:calendar-color>#1234abff</i:calendar-color><i:calendar-order>7</i:calendar-order></d:prop></d:set>'));
    mcpCheck($set['status'] === 207 && str_contains($set['body'], '200 OK'), 'Valid properties must return 207/200.');
    $read = caldavPatchRead($items, $path);
    mcpCheck($read['status'] === 207 && caldavPatchValue($read, 'DAV:', 'displayname') === 'My & calendar'
        && caldavPatchValue($read, 'http://apple.com/ns/ical/', 'calendar-color') === '#1234ABFF'
        && caldavPatchValue($read, 'http://apple.com/ns/ical/', 'calendar-order') === '7', 'A new request must read persisted properties.');

    $mixed = caldavPatchHttp($items, $path, 'PROPPATCH', caldavPatchBody('<d:set><d:prop><d:displayname>Must not be saved</d:displayname>'
        . '<d:sync-token>fake</d:sync-token></d:prop></d:set>'));
    mcpCheck($mixed['status'] === 207 && str_contains($mixed['body'], '403 Forbidden') && str_contains($mixed['body'], '424 Failed Dependency')
        && str_contains($mixed['body'], 'cannot-modify-protected-property'), 'Protected properties must reject the entire request.');
    $invalid = caldavPatchHttp($items, $path, 'PROPPATCH', caldavPatchBody('<d:set><d:prop><i:calendar-color>invalid</i:calendar-color>'
        . '<i:calendar-order>8</i:calendar-order></d:prop></d:set>'));
    mcpCheck(str_contains($invalid['body'], '409 Conflict') && str_contains($invalid['body'], '424 Failed Dependency'), 'Invalid values must not partially save.');
    $unchanged = caldavPatchRead($items, $path);
    mcpCheck(caldavPatchValue($unchanged, 'DAV:', 'displayname') === 'My & calendar'
        && caldavPatchValue($unchanged, 'http://apple.com/ns/ical/', 'calendar-order') === '7', 'Failed requests leave the preferences intact.');
    $ordered = caldavPatchHttp($items, $path, 'PROPPATCH', caldavPatchBody('<d:set><d:prop><i:calendar-order>9</i:calendar-order></d:prop></d:set>'
        . '<d:remove><d:prop><i:calendar-order/></d:prop></d:remove>'));
    mcpCheck($ordered['status'] === 207 && str_contains($ordered['body'], '200 OK'), 'Set/remove instructions execute in document order.');
    $removed = caldavPatchRead($items, $path);
    mcpCheck(str_contains($removed['body'], '404 Not Found'), 'Removed optional properties are absent.');

    foreach (['<bad/>', caldavPatchBody('<d:set><d:prop/></d:set>'),
        '<!DOCTYPE x [<!ENTITY secret SYSTEM "file:///etc/passwd">]>' . caldavPatchBody('<d:set><d:prop><d:displayname>&secret;</d:displayname></d:prop></d:set>')] as $body) {
        mcpCheck(caldavPatchHttp($items, $path, 'PROPPATCH', $body)['status'] === 400, 'Malformed or DTD-bearing requests must be rejected.');
    }
    $unknown = caldavPatchHttp($items, $path, 'PROPPATCH', caldavPatchBody('<d:set><d:prop><x:unknown xmlns:x="urn:test">x</x:unknown></d:prop></d:set>'));
    mcpCheck($unknown['status'] === 207 && str_contains($unknown['body'], '403 Forbidden'), 'Unsupported property names must be reported.');
    mcpCheck(caldavPatchHttp($items, '/principals/' . $items['user']->getId() . '/', 'PROPPATCH', caldavPatchBody(''))['status'] === 405,
        'PROPPATCH is only enabled for calendars.');
    mcpCheck(caldavPatchHttp($items, '/calendars/999999999/organization-' . $items['org']->getId() . '/', 'PROPPATCH', caldavPatchBody(''))['status'] === 404,
        'Another user calendar cannot be addressed.');

    $scoped = '/scoped/' . $items['org']->getId() . '/' . $items['root']->getId() . '/contextual/2563eb/readonly/';
    $before = caldavPatchRead($items, $scoped);
    $cached = caldavPatchRead($items, $scoped);
    mcpCheck(($cached['headers']['x-omo-caldav-cache'] ?? '') === 'HIT', 'Scoped PROPFIND must exercise the response cache.');
    $personal = caldavPatchHttp($items, $scoped, 'PROPPATCH', caldavPatchBody('<d:set><d:prop><i:calendar-color>#aabbcc</i:calendar-color></d:prop></d:set>'));
    mcpCheck($personal['status'] === 207 && str_contains($personal['body'], '200 OK'), 'Read-only event calendars still accept personal metadata.');
    $after = caldavPatchRead($items, $scoped);
    mcpCheck(caldavPatchValue($after, 'http://apple.com/ns/ical/', 'calendar-color') === '#AABBCC'
        && ($after['headers']['x-omo-caldav-cache'] ?? '') !== 'HIT', 'Successful writes invalidate cached discovery.');
    mcpCheck(caldavPatchValue($after, 'DAV:', 'sync-token') === caldavPatchValue($before, 'DAV:', 'sync-token'), 'Personal metadata must not change event sync tokens.');
    caldavPatchHttp($items, $scoped, 'PROPPATCH', caldavPatchBody('<d:set><d:prop><i:calendar-color>#aabbcc</i:calendar-color></d:prop></d:set>'));
    $repeated = caldavPatchRead($items, $scoped);
    mcpCheck(($repeated['headers']['x-omo-caldav-cache'] ?? '') === 'HIT', 'Identical preferences must preserve the response cache.');
    mcpCheck($after['xml']->getElementsByTagNameNS('DAV:', 'write-properties')->length === 1
        && $after['xml']->getElementsByTagNameNS('DAV:', 'write')->length === 0
        && $after['xml']->getElementsByTagNameNS('DAV:', 'bind')->length === 0, 'Read-only calendars advertise only personal metadata writes.');
    mcpCheck(caldavPatchValue(caldavPatchRead($items, $path), 'http://apple.com/ns/ical/', 'calendar-color') === '#1234ABFF',
        'Different calendars keep independent personal preferences.');
    $items['second_user'] = mcpFixture(\dbObject\User::class, ['email' => 'caldav-' . bin2hex(random_bytes(8)) . '@example.invalid', 'active' => 1]);
    $items['second_membership'] = mcpFixture(UserOrganization::class, ['IDuser' => $items['second_user']->getId(),
        'IDorganization' => $items['org']->getId(), 'active' => 1]);
    mcpCheck(UserOrganization::loadCalDavCalendarProperties((int)$items['second_user']->getId(), (int)$items['org']->getId(),
        'organization-' . $items['org']->getId()) === [], 'Personal preferences never leak to another member.');
    $items['membership']->load($items['membership']->getId(), true);
    mcpCheck($items['membership']->getParameter('keep') === 'existing preference', 'Unrelated personal settings remain intact.');
    mcpCheck(UserOrganization::loadCalDavCalendarProperties((int)$items['user']->getId(), (int)$items['other_org']->getId(),
        'organization-' . $items['org']->getId()) === [], 'Preferences stay isolated by organization.');
    $items['membership']->set('active', 0);
    mcpCheck(!empty($items['membership']->save()['status']), 'Revoke fixture membership.');
    foreach ($patchPaths as $patchPath) {
        mcpCheck(caldavPatchHttp($items, $patchPath, 'PROPPATCH', $probe)['status'] === 404, 'Metadata writes must check current membership.');
    }
    echo "caldav_proppatch_test: OK (HTTP, constant queries, persistence, atomic failures, removal, read-only metadata, cache and revocation)\n";
} finally {
    DbObject::enableReadOnlyMemoization(false);
    mcpCleanup($items);
}
