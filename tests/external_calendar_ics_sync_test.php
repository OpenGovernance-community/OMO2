<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/external_calendar.php';

use dbObject\ExternalCalendar;
use dbObject\ExternalCalendarEvent;
use dbObject\User;

function expectIcsSync(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}

$previousKey = getenv('EXTERNAL_CALENDAR_ENCRYPTION_KEY');
putenv('EXTERNAL_CALENDAR_ENCRYPTION_KEY=ics-test-only-key');
$user = new User();
try {
    $user->set('email', 'ics-sync-' . bin2hex(random_bytes(8)) . '@example.invalid');
    $user->set('firstname', 'ICS');
    expectIcsSync(!empty($user->save()['status']), 'User fixture failed.');
    $url = 'https://example.com/private-calendar.ics';
    $encrypted = commonExternalCalendarEncryptPassword($url);
    expectIcsSync($encrypted !== null && !str_contains($encrypted, $url), 'ICS URL must be encrypted.');
    $calendar = new ExternalCalendar();
    foreach (['IDuser' => $user->getId(), 'provider' => 'ics', 'title' => 'ICS fixture',
        'calendar_url' => 'ics:' . hash('sha256', $url), 'username' => 'ics',
        'password_encrypted' => $encrypted, 'active' => 1] as $field => $value) {
        $calendar->set($field, $value);
    }
    expectIcsSync(!empty($calendar->save()['status']), 'ICS calendar fixture failed.');
    $calendar->load((int)$calendar->getId(), true);
    $makeFeed = static fn(bool $cancel): string => implode("\r\n", [
        'BEGIN:VCALENDAR', 'VERSION:2.0', 'BEGIN:VEVENT', 'UID:series', 'SUMMARY:Meeting',
        'DTSTART:20261001T100000Z', 'DTEND:20261001T110000Z', 'RRULE:FREQ=DAILY;COUNT=2',
        'END:VEVENT', ...($cancel ? ['BEGIN:VEVENT', 'UID:series', 'RECURRENCE-ID:20261002T100000Z',
            'STATUS:CANCELLED', 'END:VEVENT'] : []), 'END:VCALENDAR', '',
    ]);
    $feed = $makeFeed(false);
    $request = static function ($requestedUrl, $username, $password, $body, $method) use (&$feed, $url): array {
        expectIcsSync($requestedUrl === $url && $username === '' && $password === '' && $method === 'GET', 'ICS must use unauthenticated GET with encrypted URL.');
        return ['status' => true, 'body' => $feed];
    };
    $start = new DateTimeImmutable('2026-10-01');
    $end = new DateTimeImmutable('2026-10-05');
    $first = commonExternalCalendarSynchronize($calendar, $start, $end, true, null, $request);
    expectIcsSync($first['status'] && $first['count'] === 2, 'Initial ICS import failed.');
    $cancelled = ExternalCalendarEvent::findForCalendarSourceKey((int)$calendar->getId(), 'series|20261002T100000Z');
    expectIcsSync($cancelled instanceof ExternalCalendarEvent && (int)$cancelled->get('active') === 1, 'Second instance was not saved.');
    $feed = $makeFeed(true);
    $second = commonExternalCalendarSynchronize($calendar, $start, $end, true, null, $request);
    $cancelled->load((int)$cancelled->getId(), true);
    expectIcsSync($second['status'] && $second['count'] === 1 && (int)$cancelled->get('active') === 0, 'Cancelled occurrence must be deactivated on resync.');
    $anchor = new DateTimeImmutable('today', new DateTimeZone('UTC'));
    $distant = new ExternalCalendarEvent();
    foreach (['IDexternalcalendar' => $calendar->getId(), 'source_key' => 'old-wide-window', 'title' => 'Future fixture',
        'start_at' => $anchor->modify('+4 months'), 'end_at' => $anchor->modify('+4 months +1 hour'), 'active' => 1] as $field => $value) {
        $distant->set($field, $value);
    }
    expectIcsSync(!empty($distant->save()['status']), 'Old wider-cache fixture failed.');
    $feed = implode("\r\n", ['BEGIN:VCALENDAR', 'VERSION:2.0', 'BEGIN:VEVENT', 'UID:rolling',
        'DTSTART:' . $anchor->format('Ymd\THis\Z'), 'DTEND:' . $anchor->modify('+1 hour')->format('Ymd\THis\Z'),
        'RRULE:FREQ=WEEKLY', 'END:VEVENT', 'END:VCALENDAR', '']);
    $rolling = commonExternalCalendarSynchronize($calendar, null, null, false, null, $request);
    expectIcsSync($rolling['status'] && $rolling['count'] >= 12 && $rolling['count'] <= 14, 'Default sync must expand about thirteen weeks, not thirteen months.');
    $distant->load((int)$distant->getId(), true);
    expectIcsSync((int)$distant->get('active') === 0, 'A full refresh must retire occurrences beyond the new window.');
    $active = new \dbObject\ArrayExternalCalendarEvent();
    $active->loadActiveForUserDateRange((int)$user->getId(), $anchor, $anchor->modify('+1 year'));
    [, $horizon] = ExternalCalendar::synchronizationRange();
    foreach ($active as $event) {
        expectIcsSync($event->get('start_at') <= $horizon, 'No active occurrence may exceed the three-month horizon.');
    }
    [, $firstHorizon] = ExternalCalendar::synchronizationRange(new DateTimeImmutable('2026-09-15 12:00'));
    [, $nextHorizon] = ExternalCalendar::synchronizationRange(new DateTimeImmutable('2026-10-15 12:00'));
    expectIcsSync($firstHorizon->format('Y-m-d') === '2026-12-15' && $nextHorizon->format('Y-m-d') === '2027-01-15', 'The three-month horizon must advance with synchronization time.');
    echo "external_calendar_ics_sync_test: OK\n";
} finally {
    if ((int)$user->getId() > 0) { $user->delete(); }
    if ($previousKey === false) { putenv('EXTERNAL_CALENDAR_ENCRYPTION_KEY'); }
    else { putenv('EXTERNAL_CALENDAR_ENCRYPTION_KEY=' . $previousKey); }
}
