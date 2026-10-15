<?php
declare(strict_types=1);
// All remote calendar traffic and notification delivery are mocked.
$ownerEmails = 0;
function notificationCenterSendEmail($user, $organization, $title, $body, $url) { $GLOBALS['ownerEmails']++; return true; }
function notificationCenterSendPush($userId, array $payload) { return true; }
function notificationCenterSendTelegram($user, $title, $body, $url) { return true; }
require_once __DIR__ . '/mcp_test_helpers.php';
require_once dirname(__DIR__) . '/meeting/service.php';
require_once dirname(__DIR__) . '/meeting/translations.php';

use dbObject\{User, ExternalCalendar, ExternalCalendarEvent, ArrayExternalCalendar, MeetingProfile};

putenv('OMO_EXTERNAL_CALENDAR_ALLOW_PRIVATE_HOSTS=1');
$items = mcpFixtures();
$before = $_SESSION;
try {
    $hostId = (int)$items['user']->getId();
    $items['visitor'] = mcpFixture(User::class, ['firstname' => 'Visitor', 'lastname' => 'Fixture',
        'email' => 'visitor-' . bin2hex(random_bytes(6)) . '@example.invalid', 'active' => 1,
        'password' => password_hash($items['password'], PASSWORD_DEFAULT), 'allow_password_login' => 1]);
    $visitorId = (int)$items['visitor']->getId();
    $items['visitor_membership'] = mcpFixture(\dbObject\UserOrganization::class, ['IDuser' => $visitorId,
        'IDorganization' => $items['org']->getId(), 'active' => 1]);
    $login = static function (User $user): void {
        $_SESSION['currentUser'] = (int)$user->getId();
        $_SESSION['auth_security_version'] = (int)$user->get('security_version');
    };
    $login($items['visitor']);
    $identity = meetingVisitorIdentity();
    mcpCheck($identity['id'] === $visitorId && $identity['name'] === 'Visitor Fixture' && $identity['email'] === $items['visitor']->get('email'), 'Global account identity.');
    $hours = MeetingProfile::defaultHours();
    foreach ($hours as &$row) { $row['open'] = true; } unset($row);
    $profiles = []; $calendars = [];
    foreach (['host' => $hostId, 'visitor' => $visitorId] as $name => $id) {
        $calendars[$name] = mcpFixture(ExternalCalendar::class, ['IDuser' => $id, 'provider' => 'caldav', 'title' => $name,
            'calendar_url' => 'https://calendar.example/' . $name . '/', 'username' => 'fixture',
            'password_encrypted' => commonExternalCalendarEncryptPassword('fixture'), 'active' => 1]);
        $profiles[$name] = mcpFixture(MeetingProfile::class, ['IDuser' => $id, 'slug' => 'visitor-' . bin2hex(random_bytes(8)),
            'enabled' => $name === 'host' ? 1 : 0, 'IDexternalcalendar' => $calendars[$name]->getId(),
            'weekly_hours' => json_encode($hours), 'preparation_minutes' => 15, 'closing_minutes' => 15]);
    }
    $alternate = mcpFixture(ExternalCalendar::class, ['IDuser' => $visitorId, 'provider' => 'caldav', 'title' => 'Other visitor calendar',
        'calendar_url' => 'https://calendar.example/visitor/alternate/', 'username' => 'fixture',
        'password_encrypted' => commonExternalCalendarEncryptPassword('fixture'), 'active' => 1]);
    $icsCalendar = mcpFixture(ExternalCalendar::class, ['IDuser' => $visitorId, 'provider' => 'ics', 'title' => 'ICS feed',
        'calendar_url' => 'https://calendar.example/feed.ics', 'username' => 'fixture', 'password_encrypted' => 'unused', 'active' => 1]);
    $choices = new ArrayExternalCalendar(); $choices->loadMeetingDestinationsForUser($visitorId);
    mcpCheck(count($choices) === 2, 'Selector includes only own active CalDAV destinations, never ICS feeds or host calendars.');
    $remote = []; $puts = ['host' => 0, 'visitor' => 0]; $visitorTimeout = false; $visitorReadonly = false;
    $request = static function ($url, $username, $password, $body, $method, $depth, $redirects = 0, $headers = []) use (&$remote, &$puts, &$visitorTimeout, &$visitorReadonly): array {
        $name = str_contains($url, '/visitor/') ? 'visitor' : 'host';
        if ($method === 'PUT') {
            mcpCheck(in_array('If-None-Match: *', $headers, true), 'Remote writes cannot overwrite events.');
            $puts[$name]++; $remote[$url] = $body;
            return ['status' => !($name === 'visitor' && $visitorTimeout), 'code' => $name === 'visitor' && $visitorTimeout ? 0 : 201];
        }
        if ($method === 'GET') { return isset($remote[$url]) ? ['status' => true, 'body' => $remote[$url], 'code' => 200] : ['status' => false, 'code' => 404]; }
        $xml = '<d:multistatus xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:caldav">';
        if ($method === 'PROPFIND') {
            $privilege = $name === 'visitor' && $visitorReadonly ? 'read' : 'bind';
            $xml .= '<d:response><d:href>' . htmlspecialchars(parse_url($url, PHP_URL_PATH), ENT_XML1) . '</d:href><d:propstat><d:prop><d:resourcetype><c:calendar/></d:resourcetype>'
                . '<d:current-user-privilege-set><d:privilege><d:' . $privilege . '/></d:privilege></d:current-user-privilege-set>'
                . '</d:prop><d:status>HTTP/1.1 200 OK</d:status></d:propstat></d:response>';
        } else {
            foreach ($remote as $resource => $ics) {
                if (str_starts_with($resource, $url)) {
                    $xml .= '<d:response><d:href>' . htmlspecialchars($resource, ENT_XML1) . '</d:href><d:propstat><d:prop><c:calendar-data>'
                        . htmlspecialchars($ics, ENT_XML1) . '</c:calendar-data></d:prop><d:status>HTTP/1.1 200 OK</d:status></d:propstat></d:response>';
                }
            }
        }
        return ['status' => true, 'url' => $url, 'body' => $xml . '</d:multistatus>'];
    };
    $day = new DateTimeImmutable('+10 days midnight', new DateTimeZone('Europe/Zurich'));
    $draftFor = static fn(int $offset) => ['token' => bin2hex(random_bytes(32)), 'date' => $day->modify('+' . $offset . ' days')->format('Y-m-d'),
        'time' => '10:00', 'duration' => 30, 'name' => $identity['name'], 'email' => $identity['email'], 'reason' => 'Fixture',
        'guest_user_id' => $visitorId, 'guest_calendar_id' => (int)$calendars['visitor']->getId()];
    $draft = $draftFor(0);
    $booking = meetingBook($hostId, $draft, $request, static fn() => true);
    mcpCheck($booking->get('status') === 'confirmed' && $booking->get('guest_calendar_status') === 'added' && $puts === ['host' => 1, 'visitor' => 1], 'Confirmed in both calendars, even with visitor public booking disabled.');
    $guestIcs = $remote[$booking->get('guest_resource_url')];
    $values = commonExternalCalendarParseEvents($guestIcs)[0];
    mcpCheck((int)$values['preparation_minutes'] === 0 && (int)$values['closing_minutes'] === 0, 'Guest event has no host buffers.');
    mcpCheck(ExternalCalendarEvent::findForCalendarSourceKey((int)$calendars['visitor']->getId(), $values['source_key']) !== null, 'Visitor cache immediately populated.');
    meetingBook($hostId, $draft, $request, static fn() => true);
    mcpCheck($puts === ['host' => 1, 'visitor' => 1] && $ownerEmails === 1, 'Retry duplicates neither event nor notification.');
    $login($items['user']);
    try { meetingBook($hostId, $draft, $request, static fn() => true); throw new LogicException('Expected identity rejection'); }
    catch (RuntimeException $error) { mcpCheck($error->getMessage() === 'identity_changed', 'Another account cannot confirm the identified draft.'); }
    $login($items['visitor']);

    $manual = $draftFor(1); $manual['guest_user_id'] = null; $manual['guest_calendar_id'] = 0;
    $manualBooking = meetingBook($hostId, $manual, $request, static fn() => true);
    mcpCheck(!$manualBooking->get('IDuser_guest') && !$manualBooking->get('guest_calendar_status') && $puts['visitor'] === 1, 'Manual form never links an account, even for the same email.');

    $visitorTimeout = true; $uncertain = $draftFor(2);
    $pending = meetingBook($hostId, $uncertain, $request, static fn() => true);
    mcpCheck($pending->get('status') === 'confirmed' && $pending->get('guest_calendar_status') === 'pending', 'Visitor timeout preserves host reservation.');
    $visitorTimeout = false; $beforePuts = $puts;
    $pending = meetingBook($hostId, $uncertain, $request, static fn() => true);
    mcpCheck($pending->get('guest_calendar_status') === 'added' && $puts === $beforePuts, 'Recover an uncertain visitor write by GET, without duplicate PUT.');

    $visitorReadonly = true; $denied = $draftFor(3);
    $pending = meetingBook($hostId, $denied, $request, static fn() => true);
    mcpCheck($pending->get('status') === 'confirmed' && $pending->get('guest_calendar_status') === 'pending' && $puts['visitor'] === $beforePuts['visitor'], 'Read-only visitor calendar cannot cancel host booking.');
    $calendars['visitor']->set('calendar_url', 'https://calendar.example/replacement/'); meetingSave($calendars['visitor']);
    $beforePuts = $puts;
    $pending = meetingBook($hostId, $denied, $request, static fn() => true);
    mcpCheck($pending->get('guest_calendar_status') === 'pending' && $puts === $beforePuts, 'Never silently switch target calendars on retry.');
    $calendars['visitor']->set('calendar_url', 'https://calendar.example/visitor/'); meetingSave($calendars['visitor']);
    $visitorReadonly = false;
    $pending = meetingBook($hostId, $denied, $request, static fn() => true);
    mcpCheck($pending->get('guest_calendar_status') === 'added', 'Retry after restoring calendar permissions.');

    $profiles['visitor']->set('IDexternalcalendar', null); meetingSave($profiles['visitor']);
    $withoutCopy = $draftFor(4); $withoutCopy['guest_calendar_id'] = 0;
    $noCalendar = meetingBook($hostId, $withoutCopy, $request, static fn() => true);
    mcpCheck((int)$noCalendar->get('IDuser_guest') === $visitorId && !$noCalendar->get('guest_calendar_status'), 'Identified visitor with no destination can still book.');
    $profiles['visitor']->set('IDexternalcalendar', $calendars['visitor']->getId()); meetingSave($profiles['visitor']);

    $login($items['user']); $self = $draftFor(5); $self['guest_user_id'] = $hostId; $self['guest_calendar_id'] = (int)$calendars['host']->getId();
    $beforePuts = $puts;
    $selfBooking = meetingBook($hostId, $self, $request, static fn() => true);
    mcpCheck($selfBooking->get('guest_calendar_status') === 'added' && $puts['host'] === $beforePuts['host'] + 1 && $puts['visitor'] === $beforePuts['visitor'], 'Same owner and visitor calendar creates only one event.');
    $login($items['visitor']);
    $chosen = $draftFor(7); $chosen['guest_calendar_id'] = (int)$alternate->getId();
    $chosenBooking = meetingBook($hostId, $chosen, $request, static fn() => true);
    mcpCheck((int)$chosenBooking->get('IDexternalcalendar_guest') === (int)$alternate->getId()
        && $chosenBooking->get('guest_calendar_status') === 'added', 'Explicit choice overrides configured destination.');
    $beforePuts = $puts;
    foreach ([$calendars['host'], $icsCalendar] as $invalidCalendar) {
        $invalid = $draftFor(8); $invalid['guest_calendar_id'] = (int)$invalidCalendar->getId();
        try { meetingBook($hostId, $invalid, $request, static fn() => true); throw new LogicException('Expected calendar rejection'); }
        catch (RuntimeException $error) { mcpCheck($error->getMessage() === 'guest_calendar_invalid', 'Foreign or non-CalDAV destination denied.'); }
    }
    $alternate->set('active', 0); meetingSave($alternate);
    $invalid = $draftFor(8); $invalid['guest_calendar_id'] = (int)$alternate->getId();
    try { meetingBook($hostId, $invalid, $request, static fn() => true); throw new LogicException('Expected inactive rejection'); }
    catch (RuntimeException $error) { mcpCheck($error->getMessage() === 'guest_calendar_invalid', 'Inactive destination denied.'); }
    mcpCheck($puts === $beforePuts, 'Invalid visitor destination rejected before host write.');
    $alternate->set('active', 1); meetingSave($alternate);
    if (($argv[1] ?? '') === '--browser') {
        echo json_encode(['origin' => 'https://localtest.me', 'path' => '/meeting/' . $profiles['host']->get('slug'),
            'date' => $day->modify('+6 days')->format('Y-m-d'), 'email' => $identity['email'], 'password' => $items['password'],
            'visitorId' => $visitorId, 'hostId' => $hostId, 'hostEmail' => $items['user']->get('email'), 'oid' => $items['org']->getId(),
            'calendarId' => $calendars['visitor']->getId(), 'alternateId' => $alternate->getId(), 'hostCalendarId' => $calendars['host']->getId(),
            'icsCalendarId' => $icsCalendar->getId()]) . "\n";
        fgets(STDIN);
    }
    echo "Meeting visitor tests passed.\n";
} finally {
    $_SESSION = $before;
    mcpCleanup($items);
}
