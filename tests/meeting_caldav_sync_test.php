<?php
declare(strict_types=1);
// Run as www-data: the real local HTTP endpoint shares its rate-limit files.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$_SERVER['HTTP_HOST'] = 'localtest.me'; $_SERVER['HTTPS'] = 'on';
require_once __DIR__ . '/mcp_test_helpers.php';
require_once __DIR__ . '/caldav_http_helpers.php';
require_once dirname(__DIR__) . '/common/caldav.php';
require_once dirname(__DIR__) . '/meeting/service.php';

use dbObject\{ArrayApplication, ArrayExternalCalendarEvent, CalDavRateLimit, Event, EventInvitation, ExternalCalendar,
    MeetingProfile, OrganizationApplication};

$oldSession = $_SESSION;
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
$oldKey = getenv('EXTERNAL_CALENDAR_ENCRYPTION_KEY');
putenv('EXTERNAL_CALENDAR_ENCRYPTION_KEY=meeting-sync-fixture-key');
$oldPrivateHosts = getenv('OMO_EXTERNAL_CALENDAR_ALLOW_PRIVATE_HOSTS');
putenv('OMO_EXTERNAL_CALENDAR_ALLOW_PRIVATE_HOSTS=1');
$items = mcpFixtures(); $limitFile = null;
try {
    $uid = (int)$items['user']->getId(); $oid = (int)$items['org']->getId();
    $apps = new ArrayApplication();
    $apps->load(['where' => [['field' => 'hash', 'value' => 'calendar']], 'limit' => 1]);
    $items['calendar_app'] = mcpFixture(OrganizationApplication::class,
        ['IDorganization' => $oid, 'IDapplication' => $apps[0]->getId(), 'active' => 1]);
    $start = (new DateTimeImmutable('+2 days'))->setTime(13, 0);
    $items['event'] = mcpFixture(Event::class, ['IDorganization' => $oid, 'IDuser' => $uid,
        'title' => 'Private CalDAV fixture title', 'start_at' => $start, 'end_at' => $start->modify('+1 hour'),
        'status' => Event::STATUS_CONFIRMED, 'active' => 1]);
    $items['invitation'] = mcpFixture(EventInvitation::class, ['IDevent' => $items['event']->getId(),
        'invitation_type' => 'user', 'IDuser' => $uid, 'active' => 1]);
    $path = '/omo/api/caldav/calendars/' . $uid . '/organization-' . $oid . '/';
    $items['external'] = mcpFixture(ExternalCalendar::class, ['IDuser' => $uid, 'provider' => 'caldav',
        'title' => 'Local HTTP fixture', 'calendar_url' => 'https://localtest.me' . $path,
        'username' => $items['user']->get('email'), 'password_encrypted' => commonExternalCalendarEncryptPassword($items['password']),
        'active' => 1]);
    $profile = $items['profile'] = MeetingProfile::forUser($uid);
    $slug = 'sync-' . bin2hex(random_bytes(6));
    foreach (['slug' => $slug, 'enabled' => 1, 'IDexternalcalendar' => $items['external']->getId(),
        'weekly_hours' => json_encode(MeetingProfile::defaultHours())] as $field => $value) $profile->set($field, $value);
    meetingSave($profile);

    $xml = '<d:propfind xmlns:d="DAV:"><d:prop><d:displayname/></d:prop></d:propfind>';
    $_SERVER['PHP_AUTH_USER'] = $items['user']->get('email'); $_SERVER['PHP_AUTH_PW'] = $items['password'];
    $_SERVER['REQUEST_METHOD'] = 'PROPFIND'; $_SERVER['REQUEST_URI'] = $path; $_SERVER['HTTP_DEPTH'] = '0';
    $GLOBALS['commonCardDavRawInput'] = $xml;
    $ticket = commonCalDavReadLimitTicket();
    mcpCheck($ticket !== null && $ticket['window'] === 600, 'The active default policy is ten minutes.');
    $limitFile = CalDavRateLimit::directory() . '/' . $ticket['key'] . '.json';
    $blockDiscovery = static function () use ($items, $path, $xml, $limitFile): void {
        mcpCheck(calendarCacheHttp($items, $path, 'PROPFIND', $xml)['status'] === 207, 'Discovery succeeds before its burst ends.');
        $record = json_decode(file_get_contents($limitFile), true, 512, JSON_THROW_ON_ERROR);
        $record['burst_until'] = time() - 1; $record['expires_at'] = time() + 599;
        file_put_contents($limitFile, json_encode($record), LOCK_EX);
        mcpCheck(calendarCacheHttp($items, $path, 'PROPFIND', $xml)['status'] === 429, 'Discovery is currently limited.');
    };
    $calls = 0;
    $request = static function ($url, $username, $password, $body, $method) use ($items, $path, &$calls): array {
        mcpCheck($method === 'REPORT', 'Event synchronization uses REPORT, not rate-limited discovery.');
        mcpCheck($username === $items['user']->get('email') && $password === $items['password'], 'Stored credentials are decoded correctly.');
        $response = calendarCacheHttp($items, $path, $method, $body, ['Depth: 1']);
        $calls++;
        mcpCheck($response['status'] === 207, 'An event REPORT succeeds while discovery is limited.');
        return ['status' => true, 'code' => 207, 'body' => $response['body'], 'url' => $url];
    };
    $blockDiscovery();
    $synced = commonExternalCalendarSynchronize($items['external'], request: $request);
    mcpCheck(!empty($synced['status']), 'Initial CalDAV event synchronization succeeds.');
    $imported = new ArrayExternalCalendarEvent();
    $loadImported = static function () use ($imported, $items): void {
        $imported->exchangeArray([]);
        $imported->loadHydrated(['where' => [['field' => 'IDexternalcalendar', 'value' => $items['external']->getId()],
            ['field' => 'active', 'value' => 1]]]);
    };
    $loadImported();
    mcpCheck(count($imported) === 1 && $imported[0]->get('start_at') == $start, 'Imported dates match the source event.');
    $page = calendarCacheHttp($items, '/meeting/' . $slug, 'GET');
    mcpCheck($page['status'] === 200 && str_contains($page['body'], 'meeting-calendar__day'), 'Public booking renders after a real HTTP calendar import.');
    mcpCheck(!str_contains($page['body'], 'Private CalDAV fixture title'), 'Public booking does not expose event titles.');

    $moved = $start->modify('+2 hours');
    $items['event']->set('start_at', $moved); $items['event']->set('end_at', $moved->modify('+1 hour')); meetingSave($items['event']);
    // A source edit invalidates discovery's old revision; then start another limited window.
    $blockDiscovery();
    $synced = commonExternalCalendarSynchronize($items['external'], request: $request);
    mcpCheck(!empty($synced['status']) && $calls === 2, 'A second import fetches changed dates despite discovery throttling.');
    $loadImported();
    mcpCheck(count($imported) === 1 && $imported[0]->get('start_at') == $moved, 'The imported row is updated without duplication.');
    $busy = meetingBusy($profile, $start->setTime(0, 0), $start->setTime(0, 0)->modify('+1 day'));
    mcpCheck(!meetingOverlap($start, $start->modify('+1 hour'), $busy)
        && meetingOverlap($moved, $moved->modify('+1 hour'), $busy), 'Availability frees the old slot and blocks the moved slot.');
    echo "meeting_caldav_sync_test: OK (HTTP REPORT during 429 discovery, moved dates, public booking and availability)\n";
} finally {
    unset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'], $GLOBALS['commonCardDavRawInput']);
    if ($limitFile !== null && is_file($limitFile)) unlink($limitFile);
    mcpCleanup($items); $_SESSION = $oldSession;
    $oldKey === false ? putenv('EXTERNAL_CALENDAR_ENCRYPTION_KEY') : putenv('EXTERNAL_CALENDAR_ENCRYPTION_KEY=' . $oldKey);
    $oldPrivateHosts === false ? putenv('OMO_EXTERNAL_CALENDAR_ALLOW_PRIVATE_HOSTS') : putenv('OMO_EXTERNAL_CALENDAR_ALLOW_PRIVATE_HOSTS=' . $oldPrivateHosts);
}
