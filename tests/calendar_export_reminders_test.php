<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/caldav.php';
require_once dirname(__DIR__) . '/meeting/service.php';

function exportReminderExpect(bool $ok, string $message): void
{
    if (!$ok) { throw new RuntimeException($message); }
}
function exportReminderTriggers(string $ics): array
{
    preg_match_all('/BEGIN:VALARM\r\n(.*?)END:VALARM/s', $ics, $alarms);
    $triggers = [];
    foreach ($alarms[1] as $alarm) {
        exportReminderExpect(str_contains($alarm, "ACTION:DISPLAY\r\n") && str_contains($alarm, 'DESCRIPTION:'), 'Display alarm includes required fields.');
        preg_match('/TRIGGER;RELATED=START:-PT(\d+)M\r\n/', $alarm, $trigger);
        exportReminderExpect(isset($trigger[1]), 'Alarm is relative to the real appointment start.');
        $triggers[] = (int)$trigger[1];
    }
    return $triggers;
}
$zone = new DateTimeZone('Europe/Zurich');
// Cross a daylight-saving boundary to test both local and UTC exports.
$start = new DateTimeImmutable('2026-10-25 09:00', $zone);
$end = $start->modify('+1 hour');
$org = new \dbObject\Organization(); $org->set('name', 'Export fixture');
$event = new \dbObject\Event();
foreach (['title' => "Rendez-vous ; virgule, barre\\\r\nATTENDEE:injected@example.invalid", 'description' => 'Original event description', 'start_at' => $start, 'end_at' => $end,
    'timezone' => $zone->getName(), 'status' => \dbObject\Event::STATUS_CONFIRMED, 'active' => 1] as $key => $value) {
    $event->set($key, $value);
}
foreach ([0 => [5], 3 => [5], 5 => [5], 15 => [15, 5], 120 => [120, 5], 1440 => [1440, 5]] as $preparation => $expected) {
    $event->setTimeBuffers(42, $preparation, 45);
    $exports = [
        'CalDAV' => commonCalDavBuildEventCalendarData($org, $event, 42),
        'booking' => meetingIcs('fixture', $start, $end, (string)$event->get('title'), 'Description', '', $preparation, 45),
    ];
    foreach ($exports as $format => $ics) {
        exportReminderExpect(exportReminderTriggers($ics) === $expected, $format . ': personal and minimum reminders, without duplicates.');
        $events = commonExternalCalendarParseEvents($ics, '', true);
        exportReminderExpect(count($events) === 1 && $events[0]['start_at'] == $start && $events[0]['end_at'] == $end,
            $format . ': exactly one appointment at its original times.');
        exportReminderExpect(!in_array('ATTENDEE:injected@example.invalid', commonExternalCalendarUnfoldLines($ics), true), 'Reminder description cannot inject properties.');
        if ($format === 'CalDAV') {
            $update = commonCalDavParseEventUpdate($ics, $event);
            exportReminderExpect($update['status'] && $update['values']['description'] === 'Original event description'
                && $update['values']['start_at'] == $start && $update['values']['end_at'] == $end,
                'An editing client can return the alarms without overwriting the event description or times.');
        }
        foreach (explode("\r\n", trim($ics)) as $line) { exportReminderExpect(strlen($line) <= 75, 'Folded ICS content lines.'); }
    }
}
$event->set('is_all_day', 1);
$allDayIcs = commonCalDavBuildEventCalendarData($org, $event, 42);
exportReminderExpect(str_contains($allDayIcs, 'DTSTART;VALUE=DATE:20261025') && str_contains($allDayIcs, 'DTEND;VALUE=DATE:20261026'),
    'All-day date boundaries remain unchanged.');
exportReminderExpect(exportReminderTriggers($allDayIcs) === [1440, 5], 'All-day reminders remain relative to the base date.');
$event->set('status', \dbObject\Event::STATUS_CANCELLED);
exportReminderExpect(exportReminderTriggers(commonCalDavBuildEventCalendarData($org, $event, 42)) === [], 'Cancelled appointments do not raise reminders.');

$booking = new \dbObject\MeetingBooking();
$ownerIcs = meetingIcs('existing-booking', $start, $end, 'Old booking', 'Original description', 'Original location', 120, 45);
// A stored booking created before reminders were introduced.
$legacyIcs = preg_replace('/BEGIN:VALARM\r\n.*?END:VALARM\r\n/s', '', $ownerIcs);
foreach (['token' => 'existing-booking', 'start_at' => $start, 'end_at' => $end, 'calendar_data' => $legacyIcs] as $key => $value) {
    $booking->set($key, $value);
}
$guestIcs = meetingGuestIcs($booking);
exportReminderExpect(exportReminderTriggers($guestIcs) === [5], 'Existing visitor downloads and email copies receive a five-minute reminder.');
$guest = commonExternalCalendarParseEvents($guestIcs, '', true)[0];
exportReminderExpect($guest['start_at'] == $start && $guest['end_at'] == $end && $guest['title'] === 'Old booking'
    && $guest['location'] === 'Original location' && $guest['description'] === 'Original description', 'Visitor copy preserves appointment data.');
exportReminderExpect($guest['preparation_minutes'] === 0 && $guest['closing_minutes'] === 0
    && str_contains($guestIcs, 'UID:existing-booking@omo2.org'), 'Visitor retains the stable UID without inheriting the organizer personal times.');
exportReminderExpect($booking->get('calendar_data') === $legacyIcs, 'Visitor export does not rewrite the organizer snapshot.');
$booking->set('token', '');
exportReminderExpect(str_contains(meetingGuestIcs($booking), 'UID:existing-booking@omo2.org'), 'Unsaved mail fixtures retain the snapshot UID as well.');
echo "calendar_export_reminders_test: OK\n";
