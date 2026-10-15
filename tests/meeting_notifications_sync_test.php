<?php
declare(strict_types=1);

// No real calendar writes, emails, push messages or Telegram messages.
$deliveries = [];
function notificationCenterSendEmail($user, $organization, $title, $body, $url) { $GLOBALS['deliveries'][] = ['email', (int)$user->getId(), $organization, $body]; return true; }
function notificationCenterSendPush($userId, array $payload) { $GLOBALS['deliveries'][] = ['push', $userId, $payload]; return true; }
function notificationCenterSendTelegram($user, $title, $body, $url) { $GLOBALS['deliveries'][] = ['telegram', (int)$user->getId(), $body]; return true; }
require_once __DIR__ . '/mcp_test_helpers.php';
require_once dirname(__DIR__) . '/meeting/service.php';
require_once dirname(__DIR__) . '/meeting/translations.php';

use dbObject\{ExternalCalendar, ExternalCalendarEvent, MeetingProfile, MeetingBooking, Notification, NotificationPreference};

putenv('OMO_EXTERNAL_CALENDAR_ALLOW_PRIVATE_HOSTS=1');
$items = mcpFixtures();
$before = $_SESSION;
try {
    $uid = (int)$items['user']->getId();
    $oid = (int)$items['org']->getId();
    $otherOid = (int)$items['other_org']->getId();
    $zone = new DateTimeZone('Europe/Zurich');
    $day = new DateTimeImmutable('+10 days midnight', $zone);
    $calendar = mcpFixture(ExternalCalendar::class, ['IDuser' => $uid, 'provider' => 'caldav', 'title' => 'Booking fixture',
        'calendar_url' => 'https://calendar.example/personal/', 'username' => 'fixture',
        'password_encrypted' => commonExternalCalendarEncryptPassword('fixture'), 'active' => 1,
        'last_sync_at' => new DateTimeImmutable('-2 hours')]);
    $profile = MeetingProfile::forUser($uid);
    $hours = MeetingProfile::defaultHours();
    foreach ($hours as &$row) { $row['open'] = true; } unset($row);
    foreach (['slug' => 'sync-' . bin2hex(random_bytes(8)), 'enabled' => 1, 'IDexternalcalendar' => $calendar->getId(),
        'weekly_hours' => json_encode($hours)] as $key => $value) { $profile->set($key, $value); }
    meetingSave($profile);
    $feed = static fn(string $token, DateTimeImmutable $at) => meetingIcs($token, $at, $at->modify('+1 hour'), 'Private event', 'Private description');
    $import = static function (string $ics) use ($calendar): ExternalCalendarEvent {
        $event = new ExternalCalendarEvent(); $event->set('IDexternalcalendar', $calendar->getId());
        $event->applyImportedValues(commonExternalCalendarParseEvents($ics)[0]);
        $event->set('active', 1); meetingSave($event); return $event;
    };
    $deleted = $import($feed('deleted', $day->setTime(9, 0)));
    $moved = $import($feed('moved', $day->setTime(11, 0)));
    mcpCheck($moved->saveLocalTimeBuffers($uid, 15, 20), 'Local annotations saved.');
    $otherDay = $day->modify('+3 days');
    $outside = $import($feed('outside-window', $day->modify('+6 months')->setTime(9, 0)));
    $remote = [$feed('new-conflict', $day->setTime(10, 0)), $feed('moved', $day->setTime(14, 0)), $feed('other-day', $otherDay->setTime(9, 0))];
    $allDay = $day->modify('+2 days');
    $remote[] = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nUID:all-day\r\nDTSTART;VALUE=DATE:" . $allDay->format('Ymd')
        . "\r\nDTEND;VALUE=DATE:" . $allDay->modify('+1 day')->format('Ymd') . "\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
    $reports = 0; $puts = 0; $invalid = false; $reportBody = '';
    $request = static function ($url, $username, $password, $body, $method) use (&$remote, &$reports, &$puts, &$invalid, &$reportBody): array {
        if ($method === 'PUT') { $puts++; return ['status' => true, 'code' => 201]; }
        $xml = '<d:multistatus xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:caldav">';
        if ($method === 'PROPFIND') {
            $xml .= '<d:response><d:href>/personal/</d:href><d:propstat><d:prop><d:resourcetype><c:calendar/></d:resourcetype>'
                . '<d:current-user-privilege-set><d:privilege><d:bind/></d:privilege></d:current-user-privilege-set>'
                . '</d:prop><d:status>HTTP/1.1 200 OK</d:status></d:propstat></d:response>';
        } else {
            $reports++; $reportBody = $body;
            if ($invalid) { return ['status' => true, 'body' => '<invalid/>']; }
            foreach ($remote as $ics) { $xml .= '<d:response><d:propstat><d:prop><c:calendar-data>' . htmlspecialchars($ics, ENT_XML1)
                . '</c:calendar-data></d:prop><d:status>HTTP/1.1 200 OK</d:status></d:propstat></d:response>'; }
        }
        return ['status' => true, 'url' => $url, 'body' => $xml . '</d:multistatus>'];
    };
    $draft = ['token' => bin2hex(random_bytes(32)), 'date' => $day->format('Y-m-d'), 'time' => '10:00', 'duration' => 30,
        'name' => 'Visitor', 'email' => 'visitor@example.invalid', 'reason' => 'Test'];
    try { meetingBook($uid, $draft, $request, static fn() => true); throw new LogicException('Expected conflict'); }
    catch (RuntimeException $error) { mcpCheck($error->getMessage() === 'slot_taken', 'Reject live conflict.'); }
    mcpCheck($puts === 0 && !$deliveries, 'Rejected booking neither writes remotely nor notifies.');
    $calendar->load((int)$calendar->getId(), true);
    mcpCheck($calendar->get('last_sync_at')->getTimestamp() >= time() - 5 && !$calendar->get('last_sync_error'), 'Full successful sync resets cache age despite conflict.');
    $deleted->load((int)$deleted->getId(), true); $moved->load((int)$moved->getId(), true); $outside->load((int)$outside->getId(), true);
    mcpCheck(!$deleted->get('active') && $outside->get('active'), 'Remove deleted source events only inside refreshed coverage.');
    mcpCheck($moved->get('start_at') == $day->setTime(14, 0) && $moved->get('time_buffers_local') && (int)$moved->get('preparation_minutes') === 15,
        'Moved event retains identity and local annotations.');
    $cached = meetingBusy($profile, $day, $day->modify('+1 day'));
    mcpCheck(meetingOverlap($day->setTime(10, 0), $day->setTime(10, 30), $cached), 'Reload now displays the rejected slot as occupied.');
    mcpCheck(!meetingOverlap($day->setTime(9, 0), $day->setTime(10, 0), $cached), 'Deleted source event becomes free.');
    mcpCheck(meetingOverlap($otherDay->setTime(9, 0), $otherDay->setTime(10, 0), meetingBusy($profile, $otherDay, $otherDay->modify('+1 day'))), 'Other days are refreshed too.');
    $allBusy = meetingBusy($profile, $allDay, $allDay->modify('+2 days'));
    mcpCheck(meetingOverlap($allDay->setTime(23, 59), $allDay->modify('+1 day'), $allBusy)
        && !meetingOverlap($allDay->modify('+1 day'), $allDay->modify('+1 day +1 minute'), $allBusy), 'Stored all-day event ends exactly at midnight.');
    $rangeXml = new DOMDocument(); $rangeXml->loadXML($reportBody); $xpath = new DOMXPath($rangeXml); $xpath->registerNamespace('c', 'urn:ietf:params:xml:ns:caldav');
    $start = new DateTimeImmutable($xpath->evaluate('string(//c:time-range/@start)'));
    $end = new DateTimeImmutable($xpath->evaluate('string(//c:time-range/@end)'));
    mcpCheck($start < new DateTimeImmutable('-29 days') && $end > new DateTimeImmutable('+89 days'), 'REPORT covers the usual full cache window.');
    $invalid = true;
    try { meetingBusy($profile, $day, $day->modify('+1 day'), true, '', $request); throw new LogicException('Expected invalid report rejection'); }
    catch (RuntimeException $error) { mcpCheck($error->getMessage() === 'unavailable', 'Invalid report fails closed.'); }
    $moved->load((int)$moved->getId(), true);
    mcpCheck((bool)$moved->get('active'), 'Invalid report does not erase cached events.');

    $key = 'meeting_booking_created';
    mcpCheck(NotificationPreference::getChannelsFor($uid, $oid, $key)['email'], 'Email is enabled by default.');
    $booking = new MeetingBooking(); $booking->setId(900000 + $uid);
    foreach (['IDuser' => $uid, 'status' => 'confirmed', 'guest_name' => 'Guest <test>', 'guest_email' => 'guest@example.invalid',
        'reason' => 'Private reason', 'start_at' => $day->setTime(15, 0), 'end_at' => $day->setTime(16, 0)] as $field => $value) { $booking->set($field, $value); }
    $_SESSION = []; // Public booking has no logged-in owner or organization.
    notificationCenterDispatchMeetingBooking($booking);
    notificationCenterDispatchMeetingBooking($booking);
    mcpCheck(count($deliveries) === 1 && $deliveries[0][0] === 'email' && $deliveries[0][1] === $uid && $deliveries[0][2] === null, 'Owner gets one personal email, independently of visitor and session.');
    $inbox = Notification::getInboxForUser($uid, $oid);
    mcpCheck(count($inbox) === 1 && !$inbox[0]->get('IDorganization') && str_contains($inbox[0]->get('body'), 'Private reason'), 'Private inbox includes personal booking details.');
    mcpCheck(count(Notification::getInboxForUser($uid, $otherOid)) === 1 && Notification::getInboxForUser($uid + 1000000, $oid) === [], 'Visible across own organizations, never to another user.');
    mcpCheck(NotificationPreference::saveChannels($uid, $oid, $key, []), 'Disable all channels.');
    mcpCheck(!NotificationPreference::getChannelsFor($uid, $otherOid, $key)['email'], 'Preference is shared across organizations.');
    $booking->setId(1000000 + $uid); notificationCenterDispatchMeetingBooking($booking);
    mcpCheck(count($deliveries) === 1 && count(Notification::getInboxForUser($uid, $oid)) === 1, 'Disabled channels create no notification.');
    mcpCheck(NotificationPreference::saveChannels($uid, $otherOid, $key, ['push' => true, 'telegram' => true]), 'Select alternative channels.');
    $booking->setId(1100000 + $uid); notificationCenterDispatchMeetingBooking($booking);
    mcpCheck(array_column($deliveries, 0) === ['email', 'push', 'telegram'], 'Selected channels replace email.');
    mcpCheck(Notification::countUnreadForUser($uid, $oid) === 1, 'External-only notification is not unread in OMO.');
    Notification::markReadForUser($uid, $otherOid, (int)$inbox[0]->getId());
    mcpCheck(Notification::countUnreadForUser($uid, $oid) === 0, 'Reading a personal notification updates every own organization.');
    $foreign = Notification::createForUser($uid, $otherOid, 'calendar_event_invited', 'foreign-' . $uid, 'Other organization');
    Notification::markAllReadForUser($uid, $oid);
    mcpCheck(Notification::countUnreadForUser($uid, $otherOid) === 1, 'Organization notifications remain isolated.');
    NotificationPreference::saveChannels($uid, $oid, $key, ['in_app' => true, 'email' => true]);
    if (in_array('--browser', $argv, true)) {
        echo json_encode(['origin' => 'https://localtest.me', 'email' => $items['user']->get('email'), 'password' => $items['password'], 'oid' => $oid]) . "\n";
        fgets(STDIN);
    } else { echo "meeting_notifications_sync_test: OK\n"; }
} finally { mcpCleanup($items); $_SESSION = $before; }
