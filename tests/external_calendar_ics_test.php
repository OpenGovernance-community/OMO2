<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/common/external_calendar.php';

function expectIcs(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}

$feed = implode("\r\n", [
    'BEGIN:VCALENDAR', 'VERSION:2.0',
    'BEGIN:VEVENT', 'UID:weekly', 'SUMMARY:Weekly meeting',
    'DTSTART;TZID=Europe/Zurich:20261005T090000', 'DTEND;TZID=Europe/Zurich:20261005T100000',
    'RRULE:FREQ=WEEKLY;COUNT=4;BYDAY=MO',
    'EXDATE;TZID=Europe/Zurich:20261012T090000', 'END:VEVENT',
    'BEGIN:VEVENT', 'UID:weekly', 'RECURRENCE-ID;TZID=Europe/Zurich:20261019T090000',
    'STATUS:CANCELLED', 'END:VEVENT',
    'BEGIN:VEVENT', 'UID:weekly', 'RECURRENCE-ID;TZID=Europe/Zurich:20261026T090000',
    'SUMMARY:Moved meeting', 'DTSTART;TZID=Europe/Zurich:20261027T110000',
    'DTEND;TZID=Europe/Zurich:20261027T120000', 'END:VEVENT',
    'BEGIN:VEVENT', 'UID:monthly', 'SUMMARY:Month end',
    'DTSTART;TZID=Europe/Zurich:20261030T150000', 'DTEND;TZID=Europe/Zurich:20261030T160000',
    'RRULE:FREQ=MONTHLY;COUNT=3;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=-1', 'END:VEVENT',
    'BEGIN:VEVENT', 'UID:holiday', 'SUMMARY:Holiday', 'DTSTART;VALUE=DATE:20261224',
    'DTEND;VALUE=DATE:20261225', 'RDATE;VALUE=DATE:20261231',
    'EXDATE;VALUE=DATE:20261224', 'END:VEVENT',
    'END:VCALENDAR', '',
]);
$result = commonExternalCalendarParseIcsFeed($feed, new DateTimeImmutable('2026-10-01'), new DateTimeImmutable('2027-01-01'));
expectIcs($result['status'], 'ICS recurrence parsing failed.');
$events = $result['events'];
$weekly = array_values(array_filter($events, static fn($event) => str_starts_with($event['source_key'], 'weekly|')));
expectIcs(count($weekly) === 2, 'EXDATE and cancelled RECURRENCE-ID must remove instances.');
expectIcs($weekly[0]['source_key'] === 'weekly|20261005T070000Z', 'Occurrence key must remain stable in UTC.');
expectIcs($weekly[1]['title'] === 'Moved meeting' && $weekly[1]['start_at']->setTimezone(new DateTimeZone('Europe/Zurich'))->format('Y-m-d H:i') === '2026-10-27 11:00', 'Moved override must replace original instance.');
$monthly = array_values(array_filter($events, static fn($event) => str_starts_with($event['source_key'], 'monthly|')));
expectIcs(count($monthly) === 3, 'BYSETPOS monthly occurrences must be expanded.');
expectIcs($monthly[1]['start_at']->setTimezone(new DateTimeZone('Europe/Zurich'))->format('Y-m-d') === '2026-11-30', 'Last weekday recurrence is wrong.');
$holiday = array_values(array_filter($events, static fn($event) => str_starts_with($event['source_key'], 'holiday|')));
expectIcs(count($holiday) === 1 && $holiday[0]['is_all_day'] === 1 && $holiday[0]['source_key'] === 'holiday|20261231', 'All-day RDATE and EXDATE must be applied.');
$crossing = implode("\r\n", ['BEGIN:VCALENDAR', 'VERSION:2.0', 'BEGIN:VEVENT', 'UID:crossing',
    'DTSTART:20260930T233000Z', 'DTEND:20261001T003000Z', 'RRULE:FREQ=DAILY;COUNT=2',
    'END:VEVENT', 'END:VCALENDAR', '']);
$crossingResult = commonExternalCalendarParseIcsFeed($crossing, new DateTimeImmutable('2026-10-01T00:00:00Z'), new DateTimeImmutable('2026-10-02T00:00:00Z'));
expectIcs($crossingResult['status'] && count($crossingResult['events']) === 2, 'Recurring occurrences overlapping the range start must be retained.');
$floating = implode("\r\n", ['BEGIN:VCALENDAR', 'X-WR-TIMEZONE:Europe/Zurich', 'BEGIN:VEVENT',
    'UID:floating', 'DTSTART:20261005T090000', 'DTEND:20261005T100000',
    'END:VEVENT', 'END:VCALENDAR', '']);
$floatingResult = commonExternalCalendarParseIcsFeed($floating, new DateTimeImmutable('2026-10-01'), new DateTimeImmutable('2026-10-10'));
expectIcs($floatingResult['status'] && $floatingResult['events'][0]['source_key'] === 'floating|20261005T070000Z', 'Floating event must use calendar timezone.');
$dateUntil = implode("\r\n", ['BEGIN:VCALENDAR', 'X-WR-TIMEZONE:America/Los_Angeles', 'BEGIN:VEVENT',
    'UID:date-until', 'DTSTART;VALUE=DATE:20261030', 'DTEND;VALUE=DATE:20261031',
    'RRULE:FREQ=DAILY;UNTIL=20261102', 'END:VEVENT', 'END:VCALENDAR', '']);
$untilResult = commonExternalCalendarParseIcsFeed($dateUntil, new DateTimeImmutable('2026-10-01T00:00:00Z'), new DateTimeImmutable('2026-12-01T00:00:00Z'));
expectIcs($untilResult['status'], 'A date-only UNTIL must be accepted for an all-day recurrence.');
expectIcs(array_column($untilResult['events'], 'source_key') === ['date-until|20261030', 'date-until|20261031', 'date-until|20261101', 'date-until|20261102'], 'All-day UNTIL must include its last date across daylight saving changes.');
$utcUntilResult = commonExternalCalendarParseIcsFeed(str_replace('UNTIL=20261102', 'UNTIL=20261103T075959Z', $dateUntil), new DateTimeImmutable('2026-10-01T00:00:00Z'), new DateTimeImmutable('2026-12-01T00:00:00Z'));
expectIcs($utcUntilResult['status'] && array_column($utcUntilResult['events'], 'source_key') === array_column($untilResult['events'], 'source_key'), 'All-day series with an explicit UTC cutoff must remain supported.');
foreach ($untilResult['events'] as $event) {
    $zone = new DateTimeZone('America/Los_Angeles');
    expectIcs($event['start_at']->setTimezone($zone)->format('H:i:s') === '00:00:00'
        && $event['end_at']->setTimezone($zone)->format('H:i:s') === '23:59:59', 'All-day recurrence must retain the calendar timezone.');
}
expectIcs(!commonExternalCalendarParseIcsFeed('<html>error</html>', new DateTimeImmutable(), new DateTimeImmutable('+1 day'))['status'], 'Invalid feed must not clear the cache.');

echo "external_calendar_ics_test: OK\n";
