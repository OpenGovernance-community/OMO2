<?php
declare(strict_types=1);
$_SERVER['HTTP_HOST'] = 'localtest.me';
$_SERVER['HTTPS'] = 'on';
require_once __DIR__ . '/mcp_test_helpers.php';
use dbObject\{ArrayApplication, ArrayDbObject, CalendarShare, Event, EventInvitation, OrganizationApplication, PerformanceRequest, UserOrganization};

require_once __DIR__ . '/caldav_http_helpers.php';
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
    $homePath = '/omo/api/caldav/calendars/' . $items['user']->getId() . '/';
    $homeXml = '<d:propfind xmlns:d="DAV:" xmlns:cs="http://calendarserver.org/ns/">'
        . '<d:prop><d:displayname/><d:sync-token/><cs:getctag/><d:owner/><d:current-user-privilege-set/></d:prop></d:propfind>';
    $homeCold = calendarCacheHttp($items, $homePath, 'PROPFIND', $homeXml, ['Depth: 1']);
    $homeColdQueries = calendarCacheLastQueryCount((int)$items['user']->getId(), 0);
    $homeWarm = calendarCacheHttp($items, $homePath, 'PROPFIND', $homeXml, ['Depth: 1']);
    $homeWarmQueries = calendarCacheLastQueryCount((int)$items['user']->getId(), 0);
    mcpCheck(($homeCold['headers']['x-omo-caldav-cache'] ?? '') === 'MISS'
        && ($homeWarm['headers']['x-omo-caldav-cache'] ?? '') === 'HIT' && $homeCold['body'] === $homeWarm['body']
        && str_contains($homeWarm['body'], $path), 'Calendar-home discovery reuses identical authenticated content.');
    mcpCheck($homeWarmQueries < $homeColdQueries / 2, 'Calendar-home cache avoids most event queries.');
    $shallow = calendarCacheHttp($items, $homePath, 'PROPFIND', $homeXml, ['Depth: 0']);
    mcpCheck(!str_contains($shallow['body'], $path), 'Depth must remain part of the discovery cache key.');
    $items['other_membership'] = mcpFixture(UserOrganization::class, ['IDuser' => $items['user']->getId(),
        'IDorganization' => $items['other_org']->getId(), 'active' => 1]);
    $items['other_calendar_app'] = mcpFixture(OrganizationApplication::class,
        ['IDorganization' => $items['other_org']->getId(), 'IDapplication' => $apps[0]->getId(), 'active' => 1]);
    $items['other_event'] = mcpFixture(Event::class, ['IDuser' => $items['user']->getId(), 'IDorganization' => $items['other_org']->getId(),
        'IDholon' => $items['other_root']->getId(), 'title' => 'Other calendar appointment', 'active' => 1, 'status' => Event::STATUS_CONFIRMED,
        'start_at' => new DateTimeImmutable('tomorrow 11:00'), 'end_at' => new DateTimeImmutable('tomorrow 12:00')]);
    $items['other_invitation'] = mcpFixture(EventInvitation::class, ['IDevent' => $items['other_event']->getId(),
        'invitation_type' => 'user', 'IDuser' => $items['user']->getId()]);
    $otherPath = $homePath . 'organization-' . $items['other_org']->getId() . '/';
    $expanded = calendarCacheHttp($items, $homePath, 'PROPFIND', $homeXml, ['Depth: 1']);
    mcpCheck(str_contains($expanded['body'], $otherPath) && str_contains($expanded['body'], $path), 'New memberships appear immediately.');
    $expandedWarm = calendarCacheHttp($items, $homePath, 'PROPFIND', $homeXml, ['Depth: 1']);
    mcpCheck(($expandedWarm['headers']['x-omo-caldav-cache'] ?? '') === 'HIT', 'Multi-organization discovery is cached.');
    $items['second_user'] = mcpFixture(\dbObject\User::class, ['email' => 'caldav-home-' . bin2hex(random_bytes(8)) . '@example.invalid',
        'password' => password_hash($items['password'], PASSWORD_DEFAULT), 'allow_password_login' => 1, 'active' => 1]);
    $items['second_membership'] = mcpFixture(UserOrganization::class, ['IDuser' => $items['second_user']->getId(),
        'IDorganization' => $items['org']->getId(), 'active' => 1]);
    $secondViewer = ['user' => $items['second_user'], 'password' => $items['password']];
    mcpCheck(calendarCacheHttp($secondViewer, $homePath, 'PROPFIND', $homeXml, ['Depth: 1'])['status'] === 404,
        'Another user cannot access a cached calendar home.');
    $secondHomePath = '/omo/api/caldav/calendars/' . $items['second_user']->getId() . '/';
    $secondHome = calendarCacheHttp($secondViewer, $secondHomePath, 'PROPFIND', $homeXml, ['Depth: 1']);
    mcpCheck(str_contains($secondHome['body'], $secondHomePath . 'organization-' . $items['org']->getId() . '/')
        && !str_contains($secondHome['body'], 'organization-' . $items['other_org']->getId() . '/'), 'Discovery remains isolated by user.');
    $wrongPassword = ['user' => $items['user'], 'password' => 'incorrect-password'];
    mcpCheck(calendarCacheHttp($wrongPassword, $homePath, 'PROPFIND', $homeXml, ['Depth: 1'])['status'] === 401,
        'Authentication must run before any discovery cache hit.');
    $items['other_event']->set('title', 'Changed other calendar appointment');
    mcpCheck(!empty($items['other_event']->save()['status']), 'Modify the second organization appointment.');
    $otherChanged = calendarCacheHttp($items, $homePath, 'PROPFIND', $homeXml, ['Depth: 1']);
    mcpCheck(($otherChanged['headers']['x-omo-caldav-cache'] ?? '') === 'MISS' && $otherChanged['body'] !== $expandedWarm['body'],
        'A change outside the cache storage organization invalidates discovery and updates the sync token.');
    $items['other_calendar_app']->set('active', 0);
    mcpCheck(!empty($items['other_calendar_app']->save()['status']), 'Disable the other calendar application.');
    $disabledApp = calendarCacheHttp($items, $homePath, 'PROPFIND', $homeXml, ['Depth: 1']);
    mcpCheck(!str_contains($disabledApp['body'], $otherPath) && str_contains($disabledApp['body'], $path), 'Disabled apps never leak through home cache.');
    $items['other_calendar_app']->set('active', 1);
    mcpCheck(!empty($items['other_calendar_app']->save()['status']), 'Enable the other calendar application.');
    mcpCheck(str_contains(calendarCacheHttp($items, $homePath, 'PROPFIND', $homeXml, ['Depth: 1'])['body'], $otherPath), 'Reenabled apps reappear.');
    $items['other_membership']->set('active', 0);
    mcpCheck(!empty($items['other_membership']->save()['status']), 'Revoke other membership.');
    mcpCheck(!str_contains(calendarCacheHttp($items, $homePath, 'PROPFIND', $homeXml, ['Depth: 1'])['body'], $otherPath), 'Revoked memberships disappear from cached discovery.');
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
    $homeChanged = calendarCacheHttp($items, $homePath, 'PROPFIND', $homeXml, ['Depth: 1']);
    mcpCheck(($homeChanged['headers']['x-omo-caldav-cache'] ?? '') === 'MISS' && $homeChanged['body'] !== $homeWarm['body'],
        'First-organization changes also invalidate discovery.');
    mcpCheck($icsChanged['status'] === 200 && str_contains($icsChanged['body'], 'Changed appointment')
        && ($icsChanged['headers']['x-omo-ics-cache'] ?? '') === 'MISS', 'ICS changes invalidate both cached content and ETag.');
    CalendarShare::revokeForUser((int)$items['share']->getId(), (int)$items['user']->getId());
    mcpCheck(calendarCacheHttp($items, $feed, 'GET')['status'] === 404, 'Revoked shares never serve cached content.');
    $items['membership']->set('active', 0);
    $items['membership']->save();
    mcpCheck(calendarCacheHttp($items, $path, 'PROPFIND', $xml)['status'] === 404, 'Membership revocation is checked before any cache hit.');
    $emptyHome = calendarCacheHttp($items, $homePath, 'PROPFIND', $homeXml, ['Depth: 1']);
    mcpCheck($emptyHome['status'] === 207 && !str_contains($emptyHome['body'], $path) && !str_contains($emptyHome['body'], $otherPath),
        'A user with no remaining calendars receives an empty discovery response.');
    echo "calendar_cache_http_test: OK (CalDAV cold=$coldQueries SQL, hit=$warmQueries SQL; home cold=$homeColdQueries SQL, hit=$homeWarmQueries SQL; ICS, multi-organization invalidation and revocation)\n";
} finally { mcpCleanup($items); }
