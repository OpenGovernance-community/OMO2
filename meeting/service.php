<?php
require_once dirname(__DIR__) . '/common/external_calendar.php';

use dbObject\MeetingProfile;
use dbObject\MeetingBooking;
use dbObject\ExternalCalendar;
use dbObject\ExternalCalendarEvent;
use dbObject\ArrayExternalCalendar;
use dbObject\ArrayExternalCalendarEvent;
use dbObject\ArrayEvent;

function meetingSlug(string $value): string
{
    $value = strtolower(trim($value));
    if (!preg_match('/^[a-z][a-z0-9-]{1,46}[a-z0-9]$/D', $value)
        || in_array($value, ['admin', 'api', 'settings', 'index', 'assets', 'service', 'calendar', 'new', 'edit'], true)) {
        throw new RuntimeException('slug_invalid');
    }
    return $value;
}

function meetingValidateHours(array $input): array
{
    $hours = [];
    for ($day = 1; $day <= 7; $day++) {
        $row = $input[$day] ?? [];
        $row = is_array($row) ? $row : [];
        $normalized = MeetingProfile::defaultHours()[$day];
        $normalized['open'] = !empty($row['open']);
        $normalized['pause'] = !empty($row['pause']);
        foreach (['start', 'end', 'pause_start', 'pause_end'] as $field) {
            $value = (string)($row[$field] ?? $normalized[$field]);
            if (!preg_match('/^(?:[01][0-9]|2[0-3]):(?:00|30)$/D', $value)) { throw new RuntimeException('hours_invalid'); }
            $normalized[$field] = $value;
        }
        if ($normalized['open'] && ($normalized['start'] >= $normalized['end'] || ($normalized['pause']
            && !($normalized['start'] < $normalized['pause_start'] && $normalized['pause_start'] < $normalized['pause_end']
                && $normalized['pause_end'] < $normalized['end'])))) { throw new RuntimeException('hours_invalid'); }
        $hours[$day] = $normalized;
    }
    return $hours;
}

function meetingDate(string $value, DateTimeZone $zone): DateTimeImmutable
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, $zone);
    if (!$date || $date->format('Y-m-d') !== $value) { throw new RuntimeException('date_invalid'); }
    return $date;
}

function meetingOverlap(DateTimeInterface $start, DateTimeInterface $end, array $busy): bool
{
    foreach ($busy as [$a, $b]) { if ($start < $b && $end > $a) { return true; } }
    return false;
}

function meetingDay(DateTimeImmutable $day, array $hours, array $busy, DateTimeImmutable $now): array
{
    $row = $hours[(int)$day->format('N')];
    if (!$row['open'] || $day->modify('+1 day') <= $now) { return ['state' => 'closed', 'slots' => []]; }
    $zone = $day->getTimezone();
    $begin = meetingDate($day->format('Y-m-d'), $zone)->modify($row['start']);
    $finish = $day->modify($row['end']);
    $pauseStart = $day->modify($row['pause_start']);
    $pauseEnd = $day->modify($row['pause_end']);
    $slots = [];
    $available = false;
    $occupied = false;
    // Iterate wall-clock half-hours, skipping non-existent or DST-crossing hours.
    [$hour, $minute] = array_map('intval', explode(':', $row['start']));
    for ($minutes = $hour * 60 + $minute; $minutes < 24 * 60; $minutes += 30) {
        $label = sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
        if ($label >= $row['end']) { break; }
        $start = $day->setTime(intdiv($minutes, 60), $minutes % 60);
        $end = $start->modify('+1 hour');
        // Do not render a half-hour departure if its one-hour appointment ends after closing.
        if ($end > $finish) { break; }
        $pause = $row['pause'] && $start < $pauseEnd && $end > $pauseStart;
        $conflict = meetingOverlap($start, $end, $busy);
        $free = !$pause && !$conflict && $start > $now
            && $start->format('H:i') === $label && $end->getTimestamp() - $start->getTimestamp() === 3600;
        $slots[] = ['time' => $label, 'start' => $start, 'end' => $end, 'free' => $free, 'pause' => $pause];
        $available = $available || $free;
    }
    $working = $row['pause'] ? [[$begin, $pauseStart], [$pauseEnd, $finish]] : [[$begin, $finish]];
    foreach ($working as [$start, $end]) { $occupied = $occupied || meetingOverlap($start, $end, $busy); }
    return ['state' => !$available ? 'full' : ($occupied ? 'partial' : 'free'), 'slots' => $slots];
}

function meetingSave($object): void
{
    $result = $object->save();
    if (!is_array($result) || empty($result['status'])) { throw new RuntimeException('storage'); }
}

function meetingDestination(MeetingProfile $profile): ExternalCalendar
{
    $calendar = new ExternalCalendar();
    if (!$calendar->load((int)$profile->get('IDexternalcalendar'), true)
        || (int)$calendar->get('IDuser') !== (int)$profile->get('IDuser') || !(int)$calendar->get('active')) {
        throw new RuntimeException('calendar_invalid');
    }
    return $calendar;
}

function meetingBusy(MeetingProfile $profile, DateTimeImmutable $start, DateTimeImmutable $end,
    bool $live = false, string $except = '', ?callable $request = null): array
{
    $uid = (int)$profile->get('IDuser');
    $storageZone = new DateTimeZone(date_default_timezone_get());
    $storageStart = $start->setTimezone($storageZone);
    $storageEnd = $end->setTimezone($storageZone);
    $busy = MeetingBooking::pendingIntervals($uid, $storageStart, $storageEnd, $except);
    $events = new ArrayEvent();
    $events->loadBusyForUserDateRange($uid, $storageStart, $storageEnd);
    foreach ($events as $event) {
        $a = $event->get('start_at');
        $b = $event->get('end_at');
        if ($a instanceof DateTimeInterface) {
            $b = $b instanceof DateTimeInterface && $b >= $a ? DateTimeImmutable::createFromInterface($b) : DateTimeImmutable::createFromInterface($a)->modify('+1 hour');
            if ($event->get('is_all_day')) {
                $a = DateTimeImmutable::createFromInterface($a)->setTime(0, 0);
                $b = $b->setTime(0, 0)->modify('+1 day');
            }
            $busy[] = [$a, $b];
        }
    }
    $calendars = new ArrayExternalCalendar();
    $calendars->loadForUser($uid, true);
    $destinationPresent = false;
    foreach ($calendars as $calendar) { $destinationPresent = $destinationPresent || (int)$calendar->getId() === (int)$profile->get('IDexternalcalendar'); }
    if (!$destinationPresent) { throw new RuntimeException('unavailable'); }
    $request ??= 'commonExternalCalendarHttpRequest';
    foreach ($calendars as $calendar) {
        if ($live) {
            $password = commonExternalCalendarDecryptPassword($calendar->get('password_encrypted'));
            if ($password === null) { throw new RuntimeException('unavailable'); }
            $result = $request($calendar->get('calendar_url'), $calendar->get('username'), $password,
                commonExternalCalendarBuildReport($start, $end), 'REPORT', 1);
            if (empty($result['status'])) { throw new RuntimeException('unavailable'); }
            $parsed = commonExternalCalendarParseReport($result['body'], true);
            if (empty($parsed['status'])) { throw new RuntimeException('unavailable'); }
            foreach ($parsed['events'] as $event) { if ($event['is_busy']) { $busy[] = [$event['start_at'], $event['end_at']]; } }
        } else {
            $last = $calendar->get('last_sync_at');
            if (!$last instanceof DateTimeInterface || $last->getTimestamp() < time() - 7200 || $calendar->get('last_sync_error')) {
                // A bounded, owner-wide refresh: public visitors never trigger concurrent full imports.
                if (!MeetingProfile::lock($uid)) { throw new RuntimeException('unavailable'); }
                try {
                    $calendar->load((int)$calendar->getId(), true);
                    $last = $calendar->get('last_sync_at');
                    if (!$last instanceof DateTimeInterface || $last->getTimestamp() < time() - 7200) {
                        $result = commonExternalCalendarSynchronize($calendar);
                        if (empty($result['status'])) { throw new RuntimeException('unavailable'); }
                    }
                    if ($calendar->get('last_sync_error')) { throw new RuntimeException('unavailable'); }
                } finally { MeetingProfile::unlock($uid); }
            }
        }
    }
    if (!$live) {
        $external = new ArrayExternalCalendarEvent();
        $external->loadActiveForUserDateRange($uid, $storageStart, $storageEnd);
        foreach ($external as $event) {
            if ($event->get('is_busy')) {
                $endAt = DateTimeImmutable::createFromInterface($event->get('end_at'));
                $busy[] = [$event->get('start_at'), $event->get('is_all_day') ? $endAt->modify('+1 second') : $endAt];
            }
        }
    }
    return $busy;
}

function meetingIcs(string $token, DateTimeImmutable $start, DateTimeImmutable $end, string $title, string $description): string
{
    $escape = static fn($text) => str_replace(["\\", "\r\n", "\r", "\n", ';', ','], ['\\\\', '\\n', '\\n', '\\n', '\\;', '\\,'], $text);
    $utc = new DateTimeZone('UTC');
    $lines = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//OMO//Meeting//FR', 'CALSCALE:GREGORIAN', 'BEGIN:VEVENT',
        'UID:' . $token . '@omo2.org', 'DTSTAMP:' . gmdate('Ymd\THis\Z'),
        'DTSTART:' . $start->setTimezone($utc)->format('Ymd\THis\Z'), 'DTEND:' . $end->setTimezone($utc)->format('Ymd\THis\Z'),
        'SUMMARY:' . $escape($title), 'DESCRIPTION:' . $escape($description), 'STATUS:CONFIRMED', 'TRANSP:OPAQUE', 'END:VEVENT', 'END:VCALENDAR'];
    $folded = [];
    foreach ($lines as $line) {
        while (strlen($line) > 75) {
            $chunk = mb_strcut($line, 0, 75, 'UTF-8');
            $folded[] = $chunk;
            $line = ' ' . substr($line, strlen($chunk));
        }
        $folded[] = $line;
    }
    return implode("\r\n", $folded) . "\r\n";
}

function meetingMail(MeetingBooking $booking): bool
{
    require_once dirname(__DIR__) . '/common/email_layout.php';
    $from = trim((string)($GLOBALS['mailUser'] ?? ''));
    // An unauthenticated SMTP relay (such as Mailpit) has no MAIL_USER.
    if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
        $host = function_exists('commonGetRootHost') ? commonGetRootHost() : (string)($_SERVER['HTTP_HOST'] ?? '');
        $from = 'noreply@' . preg_replace('/:\d+$/', '', (string)$host);
        if (!filter_var($from, FILTER_VALIDATE_EMAIL)) { $from = 'noreply@localhost.invalid'; }
    }
    $ics = (string)$booking->get('calendar_data');
    $events = commonExternalCalendarParseEvents($ics);
    $title = $events[0]['title'] ?? meetingT('title');
    $start = DateTimeImmutable::createFromInterface($booking->get('start_at'))->setTimezone(new DateTimeZone('Europe/Zurich'));
    $body = commonRenderMailLayout([
        'brand_name' => 'OMO',
        'heading' => meetingT('confirmed'),
        'intro_html' => commonMailTextToHtml(meetingT('mail_body', ['date' => $start->format('d.m.Y H:i')])),
        'body_html' => commonMailTextToHtml($title),
        'details_html' => commonMailTextToHtml(
            meetingT('name') . ' : ' . (string)$booking->get('guest_name') . "\n"
            . meetingT('email') . ' : ' . (string)$booking->get('guest_email')
        ) . commonMailTextToHtml(meetingT('reason') . " :\n" . (string)$booking->get('reason')),
    ]);
    try { return myHTMLMail([$from, 'OMO'], $booking->get('guest_email'), $title, $body, null, null,
        [['name' => 'rendez-vous.ics', 'type' => 'text/calendar; charset=UTF-8', 'content' => $ics]]); }
    catch (Throwable $exception) { error_log('Meeting confirmation email failed'); return false; }
}

function meetingBook(int $userId, array $draft, ?callable $request = null, ?callable $mailer = null): MeetingBooking
{
    $request ??= 'commonExternalCalendarHttpRequest';
    $mailer ??= 'meetingMail';
    if (!MeetingProfile::lock($userId)) { throw new RuntimeException('busy'); }
    try {
        $booking = new MeetingBooking();
        $existing = $booking->load(['token', $draft['token']], true);
        if ($existing && (int)$booking->get('IDuser') !== $userId) { throw new RuntimeException('expired'); }
        if (!$existing || $booking->get('status') !== 'confirmed') {
            $profile = MeetingProfile::forUser($userId);
            if (!(int)$profile->get('enabled')) { throw new RuntimeException('disabled'); }
            $calendar = meetingDestination($profile);
            if ($existing && (int)$booking->get('IDexternalcalendar') !== (int)$calendar->getId()) { throw new RuntimeException('pending'); }
            if (!commonExternalCalendarCanCreate($calendar, $request)) { throw new RuntimeException('calendar_invalid'); }
            $password = commonExternalCalendarDecryptPassword($calendar->get('password_encrypted'));
            $resource = commonExternalCalendarResolveHref(rtrim($calendar->get('calendar_url'), '/') . '/', 'omo-meeting-' . $draft['token'] . '.ics');
            if (!$resource) { throw new RuntimeException('calendar_invalid'); }
            $found = false;
            if ($existing && $booking->get('status') === 'pending') {
                $recovery = $request($booking->get('resource_url'), $calendar->get('username'), $password, '', 'GET', 0);
                if (!empty($recovery['status'])) {
                    $lines = commonExternalCalendarUnfoldLines($recovery['body']);
                    $recovered = commonExternalCalendarParseEvents($recovery['body']);
                    $found = in_array('UID:' . $draft['token'] . '@omo2.org', $lines, true)
                        && count($recovered) === 1 && $recovered[0]['start_at'] == $booking->get('start_at')
                        && $recovered[0]['end_at'] == $booking->get('end_at');
                    if (!$found) { throw new RuntimeException('pending'); }
                } elseif (($recovery['code'] ?? 0) !== 404) { throw new RuntimeException('pending'); }
            }
            if (!$found) {
                $day = meetingDate($draft['date'], new DateTimeZone($profile->get('timezone')));
                $now = new DateTimeImmutable('now', $day->getTimezone());
                if ($day < $now->setTime(0, 0) || $day > $now->modify('+365 days')) { throw new RuntimeException('date_invalid'); }
                $busy = meetingBusy($profile, $day, $day->modify('+1 day'), true, $draft['token'], $request);
                $slot = null;
                foreach (meetingDay($day, $profile->hours(), $busy, $now)['slots'] as $candidate) {
                    if ($candidate['time'] === $draft['time'] && $candidate['free']) { $slot = $candidate; }
                }
                if (!$slot) { throw new RuntimeException('slot_taken'); }
                if (!$existing) {
                    $ownerName = (string)$profile->get('slug');
                    $ics = meetingIcs($draft['token'], $slot['start'], $slot['end'], meetingT('event_title', ['owner' => $ownerName, 'guest' => $draft['name']]),
                        $draft['name'] . "\n" . $draft['email'] . "\n\n" . $draft['reason']);
                    foreach (['IDuser' => $userId, 'IDexternalcalendar' => $calendar->getId(), 'token' => $draft['token'],
                        'guest_name' => $draft['name'], 'guest_email' => $draft['email'], 'reason' => $draft['reason'],
                        'start_at' => $slot['start']->setTimezone(new DateTimeZone(date_default_timezone_get())),
                        'end_at' => $slot['end']->setTimezone(new DateTimeZone(date_default_timezone_get())), 'resource_url' => $resource,
                        'calendar_data' => $ics, 'created_at' => new DateTimeImmutable()] as $key => $value) { $booking->set($key, $value); }
                }
                $booking->set('status', 'pending');
                meetingSave($booking); // Persist the identity BEFORE the network write, including ambiguous timeouts.
                $result = $request($booking->get('resource_url'), $calendar->get('username'), $password,
                    $booking->get('calendar_data'), 'PUT', 0, 0, ['If-None-Match: *']);
                if (empty($result['status'])) {
                    if (in_array($result['code'] ?? 0, [400, 401, 403, 404, 405, 409, 415, 422], true)) {
                        $booking->set('status', 'failed'); meetingSave($booking);
                        throw new RuntimeException('write_failed');
                    }
                    throw new RuntimeException('pending');
                }
            }
            // Publish into the private OMO cache immediately; subsequent sync stays authoritative.
            foreach (commonExternalCalendarParseEvents($booking->get('calendar_data')) as $values) {
                $event = ExternalCalendarEvent::findForCalendarSourceKey((int)$calendar->getId(), $values['source_key']) ?? new ExternalCalendarEvent();
                $event->set('IDexternalcalendar', $calendar->getId());
                foreach ($values as $key => $value) { $event->set($key, $value); }
                $event->set('active', 1); $event->set('created_at', new DateTimeImmutable()); $event->set('updated_at', new DateTimeImmutable());
                meetingSave($event);
            }
            $booking->set('status', 'confirmed');
            meetingSave($booking);
        }
        if (!$booking->get('email_sent_at') && $mailer($booking)) {
            $booking->set('email_sent_at', new DateTimeImmutable()); meetingSave($booking);
        }
        return $booking;
    } finally { MeetingProfile::unlock($userId); }
}
