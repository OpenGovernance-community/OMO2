<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/meeting/service.php';
require_once dirname(__DIR__) . '/omo/translations.php';
require_once dirname(__DIR__) . '/common/calendar/time-buffers.php';

use dbObject\ArrayEvent;
use dbObject\ArrayExternalCalendarEvent;
use dbObject\DbObject;
use dbObject\Event;
use dbObject\ExternalCalendar;
use dbObject\ExternalCalendarEvent;
use dbObject\MeetingBooking;
use dbObject\MeetingProfile;
use dbObject\Organization;
use dbObject\User;

function bufferExpect(bool $ok, string $message): void
{
    if (!$ok) { throw new RuntimeException($message); }
}
function bufferFixture(string $class, array $values): DbObject
{
    $object = new $class();
    foreach ($values as $key => $value) { $object->set($key, $value); }
    meetingSave($object);
    return $object;
}

$zone = new DateTimeZone('Europe/Zurich');
$day = new DateTimeImmutable('2030-01-07', $zone);
$hours = MeetingProfile::defaultHours();
$hours[1]['pause'] = true;
$busy = [[$day->setTime(10, 0), $day->setTime(11, 0)]];
$result = meetingDay($day, $hours, $busy, $day->modify('-1 day'), 30, 15, 20);
$slots = array_column($result['slots'], null, 'time');
bufferExpect(!$slots['09:00']['bookable'], 'Preparation must fit after opening.');
bufferExpect(!$slots['09:30']['bookable'], 'Closing may conflict although the appointment ends at the busy start.');
bufferExpect(!$slots['11:00']['bookable'] && $slots['11:30']['free'] && !$slots['11:30']['bookable'], 'Preparation and closing must avoid existing meetings and lunch.');
bufferExpect(!$slots['13:00']['bookable'] && $slots['13:30']['bookable'], 'Preparation must fit after the pause.');
bufferExpect($slots['16:00']['bookable'] && !$slots['16:30']['bookable'], 'Closing must fit before the working day ends.');
bufferExpect(!$slots['09:30']['after_free'] && !$slots['11:00']['before_free'], 'Browser receives precise buffer availability independently of duration.');
$nowResult = meetingDay($day, $hours, [], $day->setTime(9, 20), 30, 15, 0);
bufferExpect(!array_column($nowResult['slots'], 'bookable', 'time')['09:30'], 'Preparation cannot begin in the past.');
foreach ([-1, '1.5', 1441, [], true] as $invalid) {
    try { Event::validateBufferMinutes($invalid); throw new LogicException('Accepted invalid buffer.'); }
    catch (RuntimeException $e) { bufferExpect($e->getMessage() === 'buffer_invalid', 'Correct validation error.'); }
}
ob_start();
commonCalendarRenderTimeBufferSelect('closing_minutes', 20);
$selectHtml = (string)ob_get_clean();
bufferExpect(str_contains($selectHtml, 'value="20" selected') && str_contains($selectHtml, 'value="0"'),
    'The dropdown preserves existing custom durations and allows one-sided buffers.');
foreach ([15, 30, 45, 60, 90, 120, 150, 180, 240] as $preset) {
    bufferExpect(str_contains($selectHtml, 'value="' . $preset . '"'), 'Requested duration preset exists.');
}

$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $nonce = bin2hex(random_bytes(6));
    $user = bufferFixture(User::class, ['email' => 'buffer-' . $nonce . '@example.invalid', 'firstname' => 'Buffer']);
    $org = bufferFixture(Organization::class, ['name' => 'Buffer ' . $nonce, 'shortname' => 'buf-' . $nonce]);
    $event = bufferFixture(Event::class, ['IDuser' => $user->getId(), 'IDorganization' => $org->getId(),
        'title' => 'Buffered event', 'status' => Event::STATUS_CONFIRMED, 'active' => 1,
        'start_at' => $day->setTime(10, 0), 'end_at' => $day->setTime(11, 0),
        'preparation_minutes' => 15, 'closing_minutes' => 20]);
    [$from, $to] = $event->getBusyInterval();
    bufferExpect($from->format('H:i') === '09:45' && $to->format('H:i') === '11:20', 'Attached durations expand occupied time.');
    bufferExpect($event->get('start_at')->format('H:i') === '10:00', 'Actual appointment time is unchanged.');
    foreach ([['09:45', '10:00'], ['11:00', '11:20']] as [$start, $end]) {
        $events = new ArrayEvent();
        $events->loadBusyForUserDateRange((int)$user->getId(), $day->modify($start), $day->modify($end));
        bufferExpect(count($events) === 1, 'Query must load events when only their buffer overlaps.');
    }
    $proposed = new Event();
    foreach (['IDuser' => $user->getId(), 'IDorganization' => $org->getId(),
        'start_at' => $day->setTime(11, 20), 'end_at' => $day->setTime(11, 50)] as $field => $value) { $proposed->set($field, $value); }
    bufferExpect(!$proposed->checkInvitationAvailability([])['conflicts'], 'Touching occupied bounds is allowed.');
    $proposed->set('preparation_minutes', 1);
    bufferExpect(count($proposed->checkInvitationAvailability([])['conflicts']) === 1, 'Proposed preparation triggers a conflict.');
    $event->set('is_all_day', 1);
    meetingSave($event);
    [$from, $to] = $event->getBusyInterval();
    bufferExpect($from == $day->modify('-15 minutes') && $to == $day->modify('+1 day +20 minutes'), 'All-day buffers surround the full inclusive day.');
    $events = new ArrayEvent();
    $events->loadBusyForUserDateRange((int)$user->getId(), $day->modify('-15 minutes'), $day);
    bufferExpect(count($events) === 1, 'All-day preparation loads even when the stored appointment has a non-midnight time.');

    $calendar = bufferFixture(ExternalCalendar::class, ['IDuser' => $user->getId(), 'provider' => 'caldav',
        'title' => 'Buffer calendar', 'calendar_url' => 'https://example.invalid/calendar',
        'username' => 'fixture', 'password_encrypted' => 'unused', 'active' => 1, 'last_sync_at' => new DateTimeImmutable()]);
    $ics = meetingIcs($nonce, $day->setTime(10, 0), $day->setTime(11, 0), 'Buffered booking', '', '', 15, 20);
    $values = commonExternalCalendarParseEvents($ics, '', true)[0];
    bufferExpect($values['preparation_minutes'] === 15 && $values['closing_minutes'] === 20, 'ICS round trip keeps both durations.');
    bufferFixture(ExternalCalendarEvent::class, $values + ['IDexternalcalendar' => $calendar->getId(), 'active' => 1]);
    $external = ArrayExternalCalendarEvent::busyIntervalsForUser((int)$user->getId(), $day->setTime(9, 45), $day->setTime(10, 0));
    bufferExpect(count($external['intervals']) === 1 && $external['intervals'][0][1]->format('H:i') === '11:20', 'External cached buffers block availability.');
    bufferFixture(MeetingBooking::class, ['IDuser' => $user->getId(), 'token' => $nonce, 'guest_name' => 'Guest',
        'guest_email' => 'guest@example.invalid', 'status' => 'pending', 'start_at' => $day->setTime(10, 0),
        'end_at' => $day->setTime(11, 0), 'preparation_minutes' => 15, 'closing_minutes' => 20,
        'reason' => 'Fixture', 'resource_url' => 'https://example.invalid/fixture.ics', 'calendar_data' => $ics]);
    bufferExpect(count(MeetingBooking::pendingIntervals((int)$user->getId(), $day->setTime(11, 0), $day->setTime(11, 20))) === 1,
        'Pending reservations protect closing time, including ambiguous network writes.');
} finally {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
}
echo "calendar_time_buffers_test: OK\n";
