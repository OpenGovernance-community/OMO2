<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/user_availability.php';
require_once dirname(__DIR__) . '/common/calendar/share-feed.php';
require_once dirname(__DIR__) . '/meeting/service.php';
require_once dirname(__DIR__) . '/meeting/translations.php';

use dbObject\ArrayExternalCalendarEvent;
use dbObject\CalendarShare;
use dbObject\DbObject;
use dbObject\Event;
use dbObject\ExternalCalendar;
use dbObject\ExternalCalendarEvent;
use dbObject\MeetingProfile;
use dbObject\Organization;
use dbObject\User;

function openingExpect(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}
function openingFixture(string $class, array $values): DbObject
{
    $object = new $class();
    foreach ($values as $field => $value) { $object->set($field, $value); }
    meetingSave($object);
    return $object;
}
function openingReject(callable $action, string $reason): void
{
    try { $action(); } catch (RuntimeException $exception) {
        openingExpect($exception->getMessage() === $reason, 'Unexpected rejection: ' . $exception->getMessage());
        return;
    }
    throw new RuntimeException('Expected rejection: ' . $reason);
}

// Isolated transactional fixtures. Remote calendar writes and mail are replaced by callbacks.
putenv('EXTERNAL_CALENDAR_ENCRYPTION_KEY=opening-calendar-test-key');
$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $nonce = bin2hex(random_bytes(6));
    $zone = new DateTimeZone('Europe/Zurich');
    $day = new DateTimeImmutable('tomorrow', $zone);
    $now = new DateTimeImmutable('now', $zone);
    $user = openingFixture(User::class, ['firstname' => 'Opening', 'email' => 'opening-' . $nonce . '@example.invalid']);
    $uid = (int)$user->getId();
    $organization = openingFixture(Organization::class, ['name' => 'Opening fixture', 'shortname' => 'opening-' . $nonce]);
    $base = ['IDuser' => $uid, 'provider' => 'caldav', 'title' => 'Private fixture', 'username' => 'fixture',
        'password_encrypted' => commonExternalCalendarEncryptPassword('fixture-secret'), 'active' => 1, 'last_sync_at' => $now];
    $destination = openingFixture(ExternalCalendar::class, $base + ['calendar_url' => 'https://calendar.example/destination/']);
    $opening = openingFixture(ExternalCalendar::class, $base + ['calendar_url' => 'https://calendar.example/opening/', 'availability_only' => 1]);
    $second = openingFixture(ExternalCalendar::class, array_replace($base, ['provider' => 'ics',
        'calendar_url' => 'ics:' . $nonce, 'availability_only' => 1]));
    $window = openingFixture(ExternalCalendarEvent::class, ['IDexternalcalendar' => $opening->getId(), 'source_key' => 'opening',
        'title' => 'PRIVATE opening', 'start_at' => $day->setTime(8, 0), 'end_at' => $day->setTime(12, 0), 'active' => 1, 'is_busy' => 0]);
    $secondWindow = openingFixture(ExternalCalendarEvent::class, ['IDexternalcalendar' => $second->getId(), 'source_key' => 'second',
        'title' => 'PRIVATE second', 'start_at' => $day->setTime(11, 0), 'end_at' => $day->setTime(14, 0), 'active' => 1, 'is_busy' => 1]);
    openingFixture(ExternalCalendarEvent::class, ['IDexternalcalendar' => $destination->getId(), 'source_key' => 'busy',
        'title' => 'PRIVATE busy', 'start_at' => $day->setTime(10, 0), 'end_at' => $day->setTime(11, 0), 'active' => 1, 'is_busy' => 1]);
    $profile = MeetingProfile::forUser($uid);
    $hours = MeetingProfile::defaultHours();
    foreach ($hours as &$row) { $row['open'] = true; $row['pause'] = true; } unset($row);
    foreach (['enabled' => 1, 'slug' => 'opening-' . $nonce, 'weekly_hours' => json_encode($hours),
        'IDexternalcalendar' => $destination->getId()] as $field => $value) { $profile->set($field, $value); }
    meetingSave($profile);

    $external = ArrayExternalCalendarEvent::busyIntervalsForUser($uid, $day, $day->modify('+1 day'));
    openingExpect($external['hasAvailabilityCalendars'] && !$external['incomplete'], 'Opening calendars are recognized in a fresh cache.');
    openingExpect(count($external['intervals']) === 1, 'Opening events, including opaque ones, are never interpreted as busy.');
    openingExpect(count($external['unavailable']) === 2 && $external['unavailable'][0][1] == $day->setTime(8, 0)
        && $external['unavailable'][1][0] == $day->setTime(14, 0), 'Overlapping openings from different calendars are united.');
    $busy = commonUserAvailabilityLoadBusyIntervals($uid, $day, $day->modify('+1 day'));
    $personal = commonUserAvailabilityBuildDay($day, $profile->availabilityHours(), $busy);
    $codes = commonUserAvailabilityEncodeDay($personal);
    openingExpect($codes[18] === '1' && $codes[20] === '2' && $codes[22] === '1' && $codes[24] === '3' && $codes[26] === '1'
        && $codes[28] === '2' && $codes[16] === '0', 'Profile data applies weekly hours, lunch, openings and busy meetings together.');
    $combined = commonUserAvailabilityBuildCombinedDay($day, [
        ['hours' => $profile->availabilityHours(), 'busy' => $busy],
        ['hours' => $profile->availabilityHours(), 'busy' => []],
    ]);
    $last = end($combined['slots']);
    openingExpect($last['busyCount'] === 1 && $last['participantCount'] === 2, 'Opening restrictions count once per guest in the team gradient.');
    $profile->set('enabled', 0);
    $unrestricted = commonUserAvailabilityEncodeDay(commonUserAvailabilityBuildDay($day, $profile->availabilityHours(), $busy));
    openingExpect($unrestricted[16] === '1' && $unrestricted[24] === '1' && $unrestricted[28] === '2', 'Disabled appointment booking disables weekly hours and lunch, but preserves external opening restrictions.');
    $profile->set('enabled', 1);

    $proposed = new Event();
    foreach (['IDuser' => $uid, 'IDorganization' => $organization->getId(), 'start_at' => $day->setTime(14, 0), 'end_at' => $day->setTime(15, 0)] as $field => $value) { $proposed->set($field, $value); }
    $warning = $proposed->checkInvitationAvailability([]);
    openingExpect(count($warning['conflicts']) === 1 && $warning['conflicts'][0]['source'] === 'availability', 'Creation reports outside opening windows as a separate availability conflict.');
    openingExpect(!str_contains(json_encode($warning), 'PRIVATE'), 'Conflict checks preserve event privacy.');
    $proposed->set('start_at', $day->setTime(9, 0)); $proposed->set('end_at', $day->setTime(10, 0));
    openingExpect($proposed->checkInvitationAvailability([])['conflicts'] === [], 'Opening events do not produce false meeting conflicts.');

    $busyShare = CalendarShare::createForUser($uid, 'Fixture busy', 1, false, '');
    $detailShare = CalendarShare::createForUser($uid, 'Fixture detailed', 1, true, '');
    $sharedBusy = commonExternalCalendarParseEvents(calendarShareBuildFeed($busyShare, $now), '', true);
    $sharedDetailed = commonExternalCalendarParseEvents(calendarShareBuildFeed($detailShare, $now), '', true);
    openingExpect(count($sharedBusy) === 1 && count($sharedDetailed) === 3, 'Busy feeds omit opening windows; detailed feeds retain them.');
    openingExpect(count(array_filter($sharedDetailed, static fn($event) => !$event['is_busy'])) === 2, 'Detailed ICS exports opening windows as transparent.');

    $puts = 0;
    $failReport = false;
    $allDay = false;
    $normalIcs = meetingIcs(bin2hex(random_bytes(32)), $day->setTime(10, 0), $day->setTime(11, 0), 'Private busy', '');
    $openingIcs = str_replace('TRANSP:OPAQUE', 'TRANSP:TRANSPARENT', meetingIcs(bin2hex(random_bytes(32)), $day->setTime(8, 0), $day->setTime(12, 0), 'Private opening', ''));
    $request = static function ($url, $username, $password, $body, $method) use (&$puts, &$failReport, &$allDay, $normalIcs, $openingIcs, $day): array {
        if ($method === 'PUT') { $puts++; return ['status' => true, 'code' => 201]; }
        $xml = '<d:multistatus xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:caldav">';
        if ($method === 'PROPFIND') {
            $xml .= '<d:response><d:href>/destination/</d:href><d:propstat><d:prop><d:resourcetype><c:calendar/></d:resourcetype><d:current-user-privilege-set><d:privilege><d:bind/></d:privilege></d:current-user-privilege-set></d:prop><d:status>HTTP/1.1 200 OK</d:status></d:propstat></d:response>';
        } else {
            if ($failReport) { return ['status' => false, 'code' => 503]; }
            $ics = str_contains($url, '/opening/') ? $openingIcs : $normalIcs;
            if ($allDay && str_contains($url, '/opening/')) {
                $ics = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nUID:all-day\r\nDTSTART;VALUE=DATE:" . $day->format('Ymd')
                    . "\r\nDTEND;VALUE=DATE:" . $day->modify('+1 day')->format('Ymd') . "\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
            }
            $xml .= '<d:response><d:propstat><d:prop><c:calendar-data>' . htmlspecialchars($ics, ENT_XML1)
                . '</c:calendar-data></d:prop><d:status>HTTP/1.1 200 OK</d:status></d:propstat></d:response>';
        }
        return ['status' => true, 'url' => $url, 'body' => $xml . '</d:multistatus>'];
    };
    $liveBusy = meetingBusy($profile, $day, $day->modify('+1 day'), true, '', $request);
    $live = meetingDay($day, $profile->availabilityHours(), $liveBusy, $now, 30);
    $liveFree = array_column($live['slots'], 'free', 'time');
    openingExpect($liveFree['09:00'] && !$liveFree['10:00'] && $liveFree['11:00'] && !$liveFree['12:00'] && $liveFree['13:00'] && !$liveFree['14:00'], 'Live CalDAV and cached ICS use the same opening union and weekly restrictions.');
    $draft = ['token' => bin2hex(random_bytes(32)), 'date' => $day->format('Y-m-d'), 'time' => '13:30', 'duration' => 60,
        'name' => 'Fixture visitor', 'email' => 'visitor@example.invalid', 'reason' => 'Fixture'];
    openingReject(fn() => meetingBook($uid, $draft, $request, static fn() => false), 'slot_taken');
    openingExpect($puts === 0, 'No event is written when its duration exceeds an opening window.');
    $draft['duration'] = 30;
    $confirmed = meetingBook($uid, $draft, $request, static fn() => false);
    openingExpect($puts === 1 && $confirmed->get('status') === 'confirmed', 'A fitting duration can be reserved in the normal destination calendar.');
    $failReport = true;
    openingReject(fn() => meetingBusy($profile, $day, $day->modify('+1 day'), true, '', $request), 'unavailable');
    $failReport = false;
    $allDay = true;
    $acrossMidnight = meetingBusy($profile, $day, $day->modify('+1 day +1 second'), true, '', $request);
    openingExpect(meetingOverlap($day->modify('+1 day'), $day->modify('+1 day +1 second'), $acrossMidnight), 'A live all-day opening ends exactly at midnight.');
    $profile->set('IDexternalcalendar', $opening->getId());
    openingReject(fn() => meetingDestination($profile), 'calendar_invalid');

    $window->set('active', 0); meetingSave($window);
    $secondWindow->set('active', 0); meetingSave($secondWindow);
    $empty = ArrayExternalCalendarEvent::busyIntervalsForUser($uid, $day, $day->modify('+1 day'));
    openingExpect(count($empty['unavailable']) === 1 && $empty['unavailable'][0][0] == $day && $empty['unavailable'][0][1] == $day->modify('+1 day'), 'An empty opening calendar does not imply unrestricted availability.');
    $opening->set('availability_only', 0); meetingSave($opening);
    $second->set('active', 0); meetingSave($second);
    $normal = ArrayExternalCalendarEvent::busyIntervalsForUser($uid, $day, $day->modify('+1 day'));
    openingExpect(!$normal['hasAvailabilityCalendars'] && $normal['unavailable'] === [], 'Turning off opening mode restores the normal calendar calculation.');
    echo "external_calendar_availability_test: OK\n";
} finally {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
}
