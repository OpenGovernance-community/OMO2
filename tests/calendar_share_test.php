<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/calendar/share-feed.php';
require_once dirname(__DIR__) . '/common/external_calendar.php';

use dbObject\CalendarShare;
use dbObject\DbObject;
use dbObject\Event;
use dbObject\ExternalCalendar;
use dbObject\ExternalCalendarEvent;
use dbObject\Organization;
use dbObject\User;
use dbObject\UserOrganization;

function shareExpect(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}
function shareFixture(string $class, array $values): DbObject
{
    $object = new $class();
    foreach ($values as $field => $value) { $object->set($field, $value); }
    $result = $object->save();
    shareExpect(is_array($result) && !empty($result['status']), 'Fixture save failed: ' . $class . ' ' . json_encode($result));
    return $object;
}
function shareReject(callable $callback, string $message): void
{
    try { $callback(); } catch (Throwable $exception) {
        shareExpect($exception->getMessage() === $message, 'Unexpected rejection: ' . $exception->getMessage());
        return;
    }
    throw new RuntimeException('Expected rejection: ' . $message);
}

// Isolated fixture transaction; no mail, remote calendars or permanent subscriptions.
$pdo = DbObject::getPdo();
$pdo->beginTransaction();
register_shutdown_function(static function () use ($pdo): void { if ($pdo->inTransaction()) { $pdo->rollBack(); } });
try {
    $nonce = bin2hex(random_bytes(6));
    $owner = shareFixture(User::class, ['firstname' => 'Owner', 'email' => 'share-' . $nonce . '@example.invalid', 'active' => 1]);
    $other = shareFixture(User::class, ['firstname' => 'Other', 'email' => 'other-share-' . $nonce . '@example.invalid', 'active' => 1]);
    $uid = (int)$owner->getId();
    $today = new DateTimeImmutable('today');
    $day = $today->modify('+1 day');
    $orgs = [];
    foreach (['One', 'Two'] as $name) {
        $org = shareFixture(Organization::class, ['name' => 'SECRET Org ' . $name, 'shortname' => 'share-' . $name . '-' . $nonce]);
        shareFixture(UserOrganization::class, ['IDuser' => $uid, 'IDorganization' => $org->getId(), 'active' => 1]);
        $orgs[] = $org;
        shareFixture(Event::class, ['IDuser' => $uid, 'IDorganization' => $org->getId(), 'title' => 'SECRET ' . $name,
            'description' => "SECRET description\r\nATTENDEE:injected@example.invalid", 'start_at' => $day->setTime(9, 0),
            'end_at' => $day->setTime(11, 0), 'active' => 1, 'status' => Event::STATUS_CONFIRMED]);
    }
    // An invitation-only meeting for someone else in an organization the owner belongs to.
    $unrelated = shareFixture(Event::class, ['IDuser' => $other->getId(), 'IDorganization' => $orgs[0]->getId(), 'title' => 'UNRELATED',
        'start_at' => $day->setTime(10, 0), 'end_at' => $day->setTime(12, 0), 'active' => 1, 'status' => Event::STATUS_CONFIRMED]);
    shareFixture(\dbObject\EventInvitation::class, ['IDevent' => $unrelated->getId(), 'invitation_type' => 'user', 'IDuser' => $other->getId()]);
    foreach (['past' => $today->modify('-3 days'), 'future' => $today->modify('+4 months')] as $name => $date) {
        shareFixture(Event::class, ['IDuser' => $uid, 'IDorganization' => $orgs[0]->getId(), 'title' => 'OUTSIDE ' . $name,
            'start_at' => $date->setTime(9, 0), 'end_at' => $date->setTime(10, 0), 'active' => 1, 'status' => Event::STATUS_CONFIRMED]);
    }
    shareFixture(Event::class, ['IDuser' => $uid, 'IDorganization' => $orgs[0]->getId(), 'title' => 'CANCELLED',
        'start_at' => $day->setTime(9, 0), 'end_at' => $day->setTime(10, 0), 'active' => 1, 'status' => Event::STATUS_CANCELLED]);
    $calendar = shareFixture(ExternalCalendar::class, ['IDuser' => $uid, 'provider' => 'caldav', 'title' => 'SECRET Calendar',
        'calendar_url' => 'https://example.invalid/calendar', 'username' => 'SECRET account', 'password_encrypted' => 'unused', 'active' => 1]);
    $external = shareFixture(ExternalCalendarEvent::class, ['IDexternalcalendar' => $calendar->getId(), 'source_key' => 'SECRET uid',
        'title' => str_repeat('SECRET external ', 12), 'description' => 'SECRET notes', 'location' => 'SECRET place',
        'start_at' => $day->setTime(8, 0), 'end_at' => $day->setTime(12, 0), 'active' => 1, 'is_busy' => 1]);
    shareFixture(ExternalCalendarEvent::class, ['IDexternalcalendar' => $calendar->getId(), 'source_key' => 'all-day',
        'title' => 'SECRET all day', 'start_at' => $day, 'end_at' => $day->setTime(23, 59, 59), 'is_all_day' => 1, 'active' => 1, 'is_busy' => 1]);
    shareFixture(ExternalCalendarEvent::class, ['IDexternalcalendar' => $calendar->getId(), 'source_key' => 'transparent',
        'title' => 'SECRET transparent', 'start_at' => $day->setTime(8, 0), 'end_at' => $day->setTime(12, 0), 'active' => 1, 'is_busy' => 0]);
    $busy = CalendarShare::createForUser($uid, 'PRIVATE recipient', 3, false, '');
    $clear = CalendarShare::createForUser($uid, 'PRIVATE recipient 2', 3, true, '');
    shareExpect(count(CalendarShare::forUser($uid)) === 2 && CalendarShare::forUser((int)$other->getId()) === [], 'Shares are owner scoped');
    shareExpect($busy->get('token') !== $clear->get('token') && strlen($busy->get('token')) === 64, 'Independent strong tokens');
    shareExpect(CalendarShare::resolveToken($busy->get('token')) !== null, 'Live link resolves');
    shareExpect(CalendarShare::resolveToken('invalid') === null && CalendarShare::resolveToken(str_repeat('0', 64)) === null, 'Invalid links rejected');
    foreach ([0, 13] as $months) { shareReject(fn() => CalendarShare::createForUser($uid, 'Test', $months, false, ''), 'invalid'); }
    shareReject(fn() => CalendarShare::createForUser($uid, '', 1, false, ''), 'invalid');
    shareReject(fn() => CalendarShare::createForUser($uid, 'Test', 1, false, '2030-02-31'), 'expiration_invalid');
    shareReject(fn() => CalendarShare::createForUser($uid, 'Test', 1, false, $today->modify('-1 day')->format('Y-m-d')), 'expiration_invalid');
    $expires = CalendarShare::createForUser($uid, 'Today', 1, false, $today->format('Y-m-d'));
    shareExpect($expires->isUsable($today->setTime(23, 59, 59)) && !$expires->isUsable($today->modify('+1 day')), 'Expiry includes selected day only');
    shareExpect($expires->visibilityRange(new DateTimeImmutable('2027-01-31'))[1]->format('Y-m-d') === '2027-02-28', 'Months clamp at month end');

    $_SESSION['currentUser'] = (int)$other->getId();
    $busyIcs = calendarShareBuildFeed($busy, $today);
    shareExpect(!str_contains($busyIcs, 'SECRET') && !str_contains($busyIcs, 'PRIVATE') && !str_contains($busyIcs, 'DESCRIPTION:')
        && !str_contains($busyIcs, 'LOCATION:') && !str_contains($busyIcs, 'ATTENDEE:'), 'Busy mode discloses no private metadata');
    $busyEvents = commonExternalCalendarParseEvents($busyIcs, '', true);
    shareExpect(count($busyEvents) === 4, 'Both OMO organizations and busy external instances aggregated without unrelated events');
    $clearIcs = calendarShareBuildFeed($clear, $today);
    $clearEvents = commonExternalCalendarParseEvents($clearIcs, '', true);
    shareExpect(count($clearEvents) === 5, 'Detailed feed includes transparent external events');
    shareExpect(str_contains($clearIcs, 'SECRET One') && str_contains($clearIcs, 'SECRET Two') && str_contains($clearIcs, 'LOCATION:SECRET place'), 'Detailed content exported');
    shareExpect(!str_contains($clearIcs, 'UNRELATED') && !str_contains($clearIcs, 'OUTSIDE') && !str_contains($clearIcs, 'CANCELLED'), 'Scope, horizon and cancellation respected');
    shareExpect(!str_contains($clearIcs, 'SECRET account') && !str_contains($clearIcs, 'PRIVATE') && !str_contains($clearIcs, 'SECRET uid'), 'No credentials, private share names or source keys exported');
    shareExpect(!in_array('ATTENDEE:injected@example.invalid', commonExternalCalendarUnfoldLines($clearIcs), true), 'ICS content injection escaped');
    foreach (explode("\r\n", trim($clearIcs)) as $line) { shareExpect(strlen($line) <= 75, 'ICS octet folding'); }
    $fullExternal = array_values(array_filter($clearEvents, static fn($event) => str_starts_with($event['title'], 'SECRET external')))[0];
    shareExpect($fullExternal['start_at'] == $day->setTime(8, 0) && $fullExternal['end_at'] == $day->setTime(12, 0), 'UTC round trip preserves full hours');
    $allDay = array_values(array_filter($clearEvents, static fn($event) => $event['title'] === 'SECRET all day'))[0];
    shareExpect($allDay['is_all_day'] && $allDay['end_at'] == $day->modify('+1 day'), 'All-day exclusive ICS end round trips');
    shareExpect($clearIcs === calendarShareBuildFeed($clear, $today), 'Stable feed enables conditional requests');
    $external->set('title', 'UPDATED external'); $external->save();
    shareExpect(str_contains(calendarShareBuildFeed($clear, $today), 'UPDATED external'), 'Feed follows local changes without regenerating link');
    shareReject(fn() => CalendarShare::revokeForUser((int)$busy->getId(), (int)$other->getId()), 'missing');

    $mode = $argv[1] ?? '';
    if ($mode === '--settings' || str_starts_with($mode, '--api-')) {
        $_SESSION['currentUser'] = $uid;
        $_SESSION['currentOrganization'] = (int)$orgs[0]->getId();
        $_SESSION['calendar_share_csrf'] = bin2hex(random_bytes(32));
        $_SERVER['HTTP_HOST'] = 'localtest.me';
        $_SERVER['REQUEST_URI'] = '/omo/api/calendar/share.php';
        $_SERVER['REQUEST_METHOD'] = $mode === '--settings' ? 'GET' : 'POST';
        $_GET = $_REQUEST = ['oid' => $orgs[0]->getId()];
        $_POST = ['csrf' => $_SESSION['calendar_share_csrf'], 'action' => 'create', 'label' => 'API share', 'months' => '6', 'mode' => 'busy', 'expiration' => ''];
        if ($mode === '--api-csrf') { $_POST['csrf'] = 'bad'; }
        if ($mode === '--api-revoke-other') {
            $foreign = CalendarShare::createForUser((int)$other->getId(), 'Other private link', 1, false, '');
            $_POST['action'] = 'revoke'; $_POST['id'] = $foreign->getId();
        }
        if ($mode === '--api-revoke') { $_POST['action'] = 'revoke'; $_POST['id'] = $clear->getId(); }
        ob_start();
        register_shutdown_function(static function () use ($mode): void {
            $body = ob_get_clean();
            if ($mode === '--settings') {
                $dom = new DOMDocument(); @$dom->loadHTML($body);
                $xpath = new DOMXPath($dom);
                shareExpect($xpath->query('//*[@data-calendar-share]')->length === 1, 'Share settings panel rendered');
                shareExpect($xpath->query('//select[@name="months"]/option')->length === 12, 'All month choices rendered');
                shareExpect($xpath->query('//*[@data-calendar-share-link]')->length === 3, 'Each own usable link is listed');
                shareExpect($xpath->query('//*[@data-calendar-share-revoke]')->length === 3, 'Links can be revoked individually');
                shareExpect(!str_contains($body, 'SECRET'), 'Settings never embed event data');
            } else {
                $result = json_decode($body, true);
                shareExpect(is_array($result) && $result['status'] === in_array($mode, ['--api-create', '--api-revoke'], true), 'API result: ' . $body);
                if ($mode === '--api-csrf') { shareExpect(http_response_code() === 403, 'CSRF request rejected'); }
            }
            echo 'calendar_share_test ' . $mode . ": OK\n";
        });
        require dirname(__DIR__) . '/omo/api/calendar/share.php';
        exit;
    }
    if (str_starts_with($mode, '--feed')) {
        $token = (string)$busy->get('token');
        if ($mode === '--feed-revoked') { CalendarShare::revokeForUser((int)$busy->getId(), $uid); }
        if ($mode === '--feed-expired') { $busy->set('expires_at', $today); $busy->save(); }
        if ($mode === '--feed-invalid') { $token = str_repeat('0', 64); }
        if ($mode === '--feed-disabled-user') { $owner->set('active', 0); $owner->save(); }
        $_GET = ['token' => $token];
        $_SERVER['REQUEST_METHOD'] = $mode === '--feed-head' ? 'HEAD' : 'GET';
        if ($mode === '--feed-etag') { $_SERVER['HTTP_IF_NONE_MATCH'] = '"' . hash('sha256', calendarShareBuildFeed($busy)) . '"'; }
        http_response_code(200);
        ob_start();
        register_shutdown_function(static function () use ($mode): void {
            $body = ob_get_clean();
            $expected = in_array($mode, ['--feed-revoked', '--feed-expired', '--feed-invalid', '--feed-disabled-user'], true) ? 404 : ($mode === '--feed-etag' ? 304 : 200);
            shareExpect(http_response_code() === $expected, 'Feed response status: ' . http_response_code());
            shareExpect($mode === '--feed' ? str_starts_with($body, 'BEGIN:VCALENDAR') && !str_contains($body, 'SECRET') : $body === '', 'Feed response body');
            echo 'calendar_share_test ' . $mode . ": OK\n";
        });
        require dirname(__DIR__) . '/common/calendar/feed.php';
        exit;
    }
    CalendarShare::revokeForUser((int)$busy->getId(), $uid);
    shareExpect(CalendarShare::resolveToken($busy->get('token')) === null && CalendarShare::resolveToken($clear->get('token')) !== null, 'Revocation affects only the selected link');
    echo "calendar_share_test: OK\n";
} finally {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
}
