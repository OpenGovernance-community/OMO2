<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/meeting/service.php';
require_once dirname(__DIR__) . '/meeting/translations.php';

$meetingSettingsSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/calendar/meeting_settings.php');
$calendarSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/calendar/index.php');
$calendarSource .= (string)file_get_contents(dirname(__DIR__) . '/omo/api/calendar/calendar.js');

use dbObject\MeetingProfile;
use dbObject\MeetingBooking;
use dbObject\ExternalCalendar;
use dbObject\User;

function meetingExpect(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}
function meetingReject(callable $fn, string $expected): void
{
    try { $fn(); } catch (RuntimeException $e) { meetingExpect($e->getMessage() === $expected, 'Expected ' . $expected . ', got ' . $e->getMessage()); return; }
    throw new RuntimeException('Expected rejection: ' . $expected);
}

meetingExpect(
    str_contains($meetingSettingsSource, 'data-meeting-enabled-content')
        && str_contains($calendarSource, 'syncMeetingEnabledContent'),
    'Disabled meeting settings must hide their configuration until activation.'
);

if (str_starts_with($argv[1] ?? '', '--lock-check=')) {
    $id = (int)substr($argv[1], strlen('--lock-check='));
    $locked = MeetingProfile::lock($id);
    if ($locked) { MeetingProfile::unlock($id); }
    echo $locked ? 'acquired' : 'blocked';
    exit;
}

meetingExpect(meetingSlug(' David ') === 'david', 'Case insensitive slug');
foreach (['18', 'ab', 'test/', 'a--', 'api', 'foo.bar'] as $slug) { meetingReject(fn() => meetingSlug($slug), 'slug_invalid'); }
$hours = MeetingProfile::defaultHours();
$hours[1]['pause'] = true;
$hours = meetingValidateHours($hours);
$invalid = $hours; $invalid[1]['pause_end'] = '18:00';
meetingReject(fn() => meetingValidateHours($invalid), 'hours_invalid');
$invalid = $hours; $invalid[1]['start'] = '09:15';
meetingReject(fn() => meetingValidateHours($invalid), 'hours_invalid');
$zone = new DateTimeZone('Europe/Zurich');
$day = meetingDate('2030-01-07', $zone); // Monday
$before = $day->modify('-1 day');
$free = meetingDay($day, $hours, [], $before);
meetingExpect($free['state'] === 'free', 'Lunch alone should not make the day yellow');
$map = array_column($free['slots'], 'free', 'time');
meetingExpect($map['11:00'] && !$map['11:30'] && !$map['12:00'] && !$map['12:30'] && $map['13:00'], 'No lunch overlap');
 $pauseMap = array_column($free['slots'], 'pause', 'time');
meetingExpect(!$pauseMap['11:00'] && $pauseMap['11:30'] && $pauseMap['12:00'] && $pauseMap['12:30'] && !$pauseMap['13:00'], 'Lunch slots are identified for the public divider');
meetingExpect($map['16:00'] && !isset($map['16:30']), 'Only full appointments that fit before closing are displayed');
$busy = [[$day->setTime(10, 15), $day->setTime(11, 15)]];
$partial = meetingDay($day, $hours, $busy, $before);
meetingExpect($partial['state'] === 'partial', 'Partial day state');
$map = array_column($partial['slots'], 'free', 'time');
meetingExpect(!$map['09:30'] && !$map['10:00'] && !$map['10:30'] && !$map['11:00'], 'Sub-half-hour event conflicts');
meetingExpect(meetingDay($day, $hours, [[$day, $day->modify('+1 day')]], $before)['state'] === 'full', 'All day occupation');
meetingExpect(meetingDay($day->modify('+5 days'), $hours, [], $before)['state'] === 'closed', 'Closed weekend');
meetingExpect(meetingDay($day, $hours, [], $day->modify('+1 day'))['state'] === 'closed', 'Past day');
meetingExpect(!meetingOverlap($day->setTime(9, 0), $day->setTime(10, 0), [[$day->setTime(10, 0), $day->setTime(11, 0)]]), 'Adjacent events do not overlap');
meetingReject(fn() => meetingDate('2030-02-31', $zone), 'date_invalid');
foreach (['2030-03-31', '2030-10-27'] as $dstDate) {
    $dst = meetingDate($dstDate, $zone); $dstHours = $hours;
    $dstHours[7] = ['open' => true, 'start' => '01:00', 'end' => '05:00', 'pause' => false, 'pause_start' => '12:00', 'pause_end' => '13:00'];
    foreach (meetingDay($dst, $dstHours, [], $dst->modify('-1 day'))['slots'] as $slot) {
        if ($slot['free']) { meetingExpect($slot['end']->getTimestamp() - $slot['start']->getTimestamp() === 3600, 'DST duration remains one hour'); }
    }
}
$ics = meetingIcs(str_repeat('a', 64), $day->setTime(9, 0), $day->setTime(10, 0), str_repeat('Long title ', 30), "line one\r\nATTENDEE:evil@example.org;comma,");
foreach (explode("\r\n", trim($ics)) as $line) { meetingExpect(strlen($line) <= 75, 'ICS byte folding'); }
meetingExpect(!in_array('ATTENDEE:evil@example.org;comma,', commonExternalCalendarUnfoldLines($ics), true), 'ICS injection escaped');
$parsed = commonExternalCalendarParseEvents($ics, '', true);
meetingExpect(count($parsed) === 1 && $parsed[0]['start_at'] == $day->setTime(9, 0), 'UTC ICS round trip');
$transparent = str_replace('TRANSP:OPAQUE', 'TRANSP:TRANSPARENT', $ics);
meetingExpect(commonExternalCalendarParseEvents($transparent)[0]['is_busy'] === 0, 'Transparent events are not busy');
meetingExpect(commonExternalCalendarParseEvents(str_replace('STATUS:CONFIRMED', 'STATUS:CANCELLED', $ics)) === [], 'Cancelled event excluded');
meetingExpect(!commonExternalCalendarParseReport('<html/>', true)['status'], 'Unexpected report rejected');
meetingReject(fn() => commonExternalCalendarParseEvents("BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nEND:VCALENDAR", '', true), 'Incomplete event data');
$allDayIcs = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nUID:day\r\nDTSTART;VALUE=DATE:20300107\r\nDTEND;VALUE=DATE:20300108\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
meetingExpect(commonExternalCalendarParseEvents($allDayIcs)[0]['end_at']->format('Y-m-d H:i:s') === '2030-01-07 23:59:59', 'Existing all-day display remains inclusive');
meetingExpect(commonExternalCalendarParseEvents($allDayIcs, '', true)[0]['end_at']->format('Y-m-d H:i:s') === '2030-01-08 00:00:00', 'Live availability uses exclusive all-day ends');

// Integration fixture: dedicated disposable user, no live CalDAV calls or email delivery.
if (!MeetingProfile::isStorageAvailable()) { throw new RuntimeException('Run meeting migration first'); }
$user = new User();
$nonce = bin2hex(random_bytes(6));
$user->set('email', 'meeting-test-' . $nonce . '@example.invalid');
$user->set('firstname', 'Meeting');
$user->set('lastname', 'Fixture');
meetingSave($user);
$uid = (int)$user->getId();
try {
    $calendar = new ExternalCalendar();
    foreach (['IDuser' => $uid, 'provider' => 'caldav', 'title' => 'Fixture calendar', 'calendar_url' => 'https://calendar.example/dav/personal/',
        'username' => 'fixture', 'password_encrypted' => commonExternalCalendarEncryptPassword('fixture-password'),
        'active' => 1, 'color' => '#0f766e', 'last_sync_at' => new DateTimeImmutable(), 'created_at' => new DateTimeImmutable(), 'updated_at' => new DateTimeImmutable()] as $key => $value) { $calendar->set($key, $value); }
    meetingSave($calendar);
    $profile = MeetingProfile::forUser($uid);
    $hours = MeetingProfile::defaultHours();
    foreach ($hours as &$row) { $row['open'] = true; $row['pause'] = true; } unset($row);
    foreach (['slug' => 'fixture-' . $nonce, 'enabled' => 1, 'IDexternalcalendar' => $calendar->getId(), 'weekly_hours' => json_encode($hours)] as $key => $value) { $profile->set($key, $value); }
    meetingSave($profile);
    meetingExpect(MeetingProfile::lock($uid), 'Owner lock acquired');
    $output = []; $exit = 0;
    exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' --lock-check=' . $uid, $output, $exit);
    MeetingProfile::unlock($uid);
    meetingExpect($exit === 0 && implode('', $output) === 'blocked', 'Other workers cannot reserve concurrently');
    meetingExpect(!MeetingProfile::slugAvailable('FIXTURE-' . strtoupper($nonce), $uid + 1), 'Unique slug case insensitive');
    $profile->set('IDuser', $uid + 1);
    meetingReject(fn() => meetingDestination($profile), 'calendar_invalid');
    $profile->set('IDuser', $uid);
    $remote = [];
    $puts = 0;
    $timeout = false;
    $readonly = false;
    $reportError = false;
    $request = static function ($url, $username, $password, $body, $method, $depth, $redirects = 0, $headers = []) use (&$remote, &$puts, &$timeout, &$readonly, &$reportError) {
        if ($method === 'PUT') {
            $puts++;
            meetingExpect(in_array('If-None-Match: *', $headers, true), 'Never overwrite a remote resource');
            $remote[$url] = $body;
            return $timeout ? ['status' => false] : ['status' => true, 'code' => 201, 'body' => ''];
        }
        if ($method === 'GET') { return isset($remote[$url]) ? ['status' => true, 'code' => 200, 'body' => $remote[$url]] : ['status' => false, 'code' => 404]; }
        $xml = '<d:multistatus xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:caldav">';
        if ($method === 'PROPFIND') {
            $xml .= '<d:response><d:href>/dav/personal/</d:href><d:propstat><d:prop><d:resourcetype><c:calendar/></d:resourcetype>'
                . '<d:current-user-privilege-set><d:privilege><d:' . ($readonly ? 'read' : 'bind') . '/></d:privilege></d:current-user-privilege-set>'
                . '</d:prop><d:status>HTTP/1.1 200 OK</d:status></d:propstat></d:response>';
        } else {
            if ($reportError) { return ['status' => false, 'code' => 503]; }
            foreach ($remote as $ics) { $xml .= '<d:response><d:propstat><d:prop><c:calendar-data>' . htmlspecialchars($ics, ENT_XML1)
                . '</c:calendar-data></d:prop><d:status>HTTP/1.1 200 OK</d:status></d:propstat></d:response>'; }
        }
        return ['status' => true, 'url' => $url, 'body' => $xml . '</d:multistatus>'];
    };
    $draft = ['token' => bin2hex(random_bytes(32)), 'date' => (new DateTimeImmutable('tomorrow', $zone))->format('Y-m-d'),
        'time' => '10:00', 'name' => 'Test visitor', 'email' => 'visitor@example.invalid', 'reason' => 'A test'];
    $readonly = true;
    meetingReject(fn() => meetingBook($uid, $draft, $request, fn() => false), 'calendar_invalid');
    $readonly = false; $reportError = true;
    meetingReject(fn() => meetingBook($uid, $draft, $request, fn() => false), 'unavailable');
    $reportError = false;
    $booking = meetingBook($uid, $draft, $request, fn() => false);
    meetingExpect($booking->get('status') === 'confirmed' && !$booking->get('email_sent_at') && $puts === 1, 'Failed email does not cancel reservation');
    meetingExpect(str_contains((string)$booking->get('calendar_data'), 'SUMMARY:Rendez-vous : Meeting Fixture / Test visitor'), 'Calendar event uses the owner full name instead of the public slug');
    $mailCount = 0;
    $mailer = static function () use (&$mailCount) { $mailCount++; return true; };
    $booking = meetingBook($uid, $draft, $request, $mailer);
    meetingBook($uid, $draft, $request, $mailer);
    meetingExpect($puts === 1 && $mailCount === 1 && $booking->get('email_sent_at') instanceof DateTimeInterface, 'Retry is idempotent for creation and email');
    $other = $draft; $other['token'] = bin2hex(random_bytes(32));
    meetingReject(fn() => meetingBook($uid, $other, $request, $mailer), 'slot_taken');
    $later = $other; $later['time'] = '14:00';
    $timeout = true;
    meetingReject(fn() => meetingBook($uid, $later, $request, $mailer), 'pending');
    $third = $later; $third['token'] = bin2hex(random_bytes(32));
    meetingReject(fn() => meetingBook($uid, $third, $request, $mailer), 'slot_taken');
    $timeout = false;
    $recovered = meetingBook($uid, $later, $request, $mailer);
    meetingExpect($recovered->get('status') === 'confirmed' && $puts === 2, 'Ambiguous timeout reconciled without a second PUT');

    // Real local HTTP route, visitor session, privacy, review and CSRF (no real booking or email).
    $curl = curl_init();
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEFILE => '', CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => 0]); // Local Docker certificate only.
    $http = static function (string $path, ?array $post = null) use ($curl) {
        curl_setopt($curl, CURLOPT_URL, 'https://localhost' . $path);
        if ($post === null) { curl_setopt($curl, CURLOPT_HTTPGET, true); }
        else { curl_setopt($curl, CURLOPT_POST, true); curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post)); }
        $body = curl_exec($curl);
        return [(int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE), (string)$body];
    };
    $path = '/meeting/' . $profile->get('slug');
    [$code, $page] = $http($path);
    meetingExpect($code === 200 && str_contains($page, 'meeting-calendar__day'), 'Public named route renders month');
    meetingExpect(str_contains($page, 'meeting-avatar') && !str_contains($page, ' data-meeting-avatar '), 'Missing host photo displays initials');
    meetingExpect(str_contains($page, 'meeting-legend') && str_contains($page, 'meeting-steps'), 'Availability legend and booking steps render');
    $dom = new DOMDocument();
    @$dom->loadHTML($page);
    $xpath = new DOMXPath($dom);
    $expectedShareTitle = meetingT('share_title', ['name' => 'Meeting Fixture']);
    meetingExpect(trim((string)$xpath->evaluate('string(//title)')) === $expectedShareTitle . ' - OMO', 'Browser title uses the owner full name');
    meetingExpect(trim((string)$xpath->evaluate('string(//h1)')) === meetingT('book_with', ['name' => 'Meeting Fixture']), 'Booking heading uses the owner full name');
    meetingExpect($xpath->evaluate('string(//meta[@property="og:title"]/@content)') === $expectedShareTitle, 'Open Graph title describes the booking link');
    meetingExpect($xpath->evaluate('string(//meta[@property="og:description"]/@content)') === meetingT('share_description', ['name' => 'Meeting Fixture']), 'Open Graph description explains the booking link');
    meetingExpect($xpath->evaluate('string(//meta[@property="og:url"]/@content)') === 'https://localhost' . $path, 'Open Graph URL is absolute and canonical');
    meetingExpect($xpath->query('//meta[@property="og:image"]')->length === 0, 'Open Graph image is omitted when the owner has no photo');
    foreach ($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " meeting-calendar__day ")]') as $cell) {
        meetingExpect(ctype_digit(trim($cell->textContent)) && $cell->getAttribute('aria-label') !== '', 'Day cells only show numbers and keep accessible availability');
    }
    meetingExpect(!str_contains($page, 'A test') && !str_contains($page, 'visitor@example.invalid'), 'Visitor cannot read existing booking details');
    [$code, $page] = $http($path . '?date=' . $draft['date']);
    meetingExpect($code === 200 && str_contains($page, 'meeting-slots__pause') && !str_contains($page, '11:30 - 12:30'), 'Lunch slots are replaced by one divider');
    $user->set('image', '/common/assets/icon-topbar-help.png'); meetingSave($user);
    [$code, $page] = $http($path);
    meetingExpect(str_contains($page, 'src="/common/assets/icon-topbar-help.png"') && str_contains($page, ' data-meeting-avatar '), 'Global OMO photo renders publicly');
    $photoDom = new DOMDocument();
    @$photoDom->loadHTML($page);
    $photoXpath = new DOMXPath($photoDom);
    meetingExpect($photoXpath->evaluate('string(//meta[@property="og:image"]/@content)') === 'https://localhost/common/assets/icon-topbar-help.png', 'Relative profile photo becomes an absolute Open Graph image');
    meetingExpect($photoXpath->evaluate('string(//meta[@name="twitter:image"]/@content)') === 'https://localhost/common/assets/icon-topbar-help.png', 'Relative profile photo becomes an absolute card image');
    $user->set('image', 'javascript:alert(1)'); meetingSave($user);
    [$code, $page] = $http($path);
    meetingExpect(!str_contains($page, 'javascript:') && !str_contains($page, ' data-meeting-avatar ') && !str_contains($page, 'property="og:image"'), 'Unsafe photo URL falls back to initials and is not shared');
    $user->set('image', ''); meetingSave($user);
    [$code, $page] = $http($path . '?receipt=' . $draft['token']);
    meetingExpect(!str_contains($page, 'visitor@example.invalid'), 'Receipt is bound to visitor session');
    [$code, $page] = $http($path . '?date=' . $draft['date'] . '&time=16:00');
    meetingExpect($code === 200 && str_contains($page, 'name="reason"'), 'One-hour selection shows guest form');
    meetingExpect(!str_contains($page, 'Seules les disponibilites sont publiques') && str_contains($page, 'Powered by')
        && str_contains($page, 'href="/">OMO2</a>') && str_contains($page, 'href="/">OpenMyOrganization</a>'), 'Booking form uses the current instance footer without redundant privacy copy');
    preg_match('/name="csrf" value="([a-f0-9]+)"/', $page, $matches);
    $csrf = $matches[1] ?? '';
    [$code, $page] = $http($path, ['csrf' => $csrf, 'date' => $draft['date'], 'time' => '16:00',
        'name' => 'HTTP fixture', 'email' => 'not-an-email', 'reason' => 'Preserve my input']);
    meetingExpect(str_contains($page, 'name="reason"') && str_contains($page, 'Preserve my input'), 'Validation error preserves the form');
    [$code, $page] = $http($path, ['csrf' => $csrf, 'date' => $draft['date'], 'time' => '16:00',
        'name' => 'HTTP fixture', 'email' => 'http@example.invalid', 'reason' => 'HTTP preview only']);
    meetingExpect($code === 200 && str_contains($page, 'name="token"') && str_contains($page, 'HTTP preview only'), 'Review step keeps visitor details');
    meetingExpect(str_contains($page, 'meeting-summary__details') && str_contains($page, '16:00 - 17:00'), 'Review has the redesigned complete time range');

    // Render the final screen with an already mocked booking: no real CalDAV write or email.
    $confirmation = (static function () use ($booking, $profile, $zone, $path): string {
        $receipt = $booking; $draft = null; $selectedSlot = null; $csrf = 'fixture'; $error = '';
        $ownerName = 'fixture'; $ownerPhoto = ''; $now = new DateTimeImmutable('now', $zone);
        $monthKeys = [1 => 'january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december'];
        ob_start();
        require dirname(__DIR__) . '/meeting/view.php';
        return (string)ob_get_clean();
    })();
    meetingExpect(str_contains($confirmation, 'meeting-success-mark') && str_contains($confirmation, 'download=1')
        && str_contains($confirmation, 'Test visitor'), 'Confirmation shows success, attendee and ICS action');
    [$code] = $http($path, ['csrf' => 'incorrect']);
    meetingExpect($code === 403, 'Public POST rejects bad CSRF');
    [$code] = $http('/omo/api/calendar/meeting_settings.php');
    meetingExpect(in_array($code, [401, 403], true), 'Settings are not public');
    $profile->set('enabled', 0); meetingSave($profile);
    [$code] = $http($path);
    meetingExpect($code === 404, 'Disabled named route is not public');
    $other['time'] = '16:00'; $other['token'] = bin2hex(random_bytes(32));
    meetingReject(fn() => meetingBook($uid, $other, $request, $mailer), 'disabled');
} finally {
    // Only remove the user created above; cascading foreign keys remove this fixture's records.
    $user->delete();
}
echo "meeting_test: OK\n";
