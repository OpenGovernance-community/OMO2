<?php
require_once dirname(__DIR__) . '/common/external_calendar.php';

use dbObject\MeetingProfile;
use dbObject\MeetingBooking;
use dbObject\ExternalCalendar;
use dbObject\ExternalCalendarEvent;
use dbObject\ArrayExternalCalendar;
use dbObject\ArrayExternalCalendarEvent;
use dbObject\ArrayEvent;
use dbObject\User;

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

function meetingValidateDuration($value, int $maximum): int
{
    if ((!is_int($value) && !is_string($value)) || !preg_match('/^[0-9]{1,4}$/D', (string)$value)) { throw new RuntimeException('duration_invalid'); }
    $minutes = (int)$value;
    if ($minutes < 30 || $minutes % 30 !== 0 || $minutes > min($maximum, MeetingProfile::MAX_DURATION_MINUTES)) { throw new RuntimeException('duration_invalid'); }
    return $minutes;
}

function meetingValidateMethods($input, array $existing): array
{
    if (!is_array($input) || count($input) > MeetingProfile::MAX_METHODS) { throw new RuntimeException('methods_invalid'); }
    $knownIds = array_column($existing, 'id');
    $methods = [];
    $seen = [];
    foreach ($input as $row) {
        if (!is_array($row) || !is_string($row['type'] ?? null) || !is_string($row['value'] ?? null) || !is_string($row['id'] ?? '')) { throw new RuntimeException('methods_invalid'); }
        $type = $row['type'];
        $value = trim($row['value']);
        $id = $row['id'] ?? '';
        if (!in_array($type, MeetingProfile::METHOD_TYPES, true) || $value === '' || mb_strlen($value) > 1000 || preg_match('/[\x00-\x1f\x7f]/', $value)) { throw new RuntimeException('methods_invalid'); }
        if ($type === 'video' && (!filter_var($value, FILTER_VALIDATE_URL) || !in_array(strtolower((string)parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true))) { throw new RuntimeException('methods_invalid'); }
        if ($type === 'phone' && (mb_strlen($value) > 100 || !preg_match('/[0-9]/', $value))) { throw new RuntimeException('methods_invalid'); }
        if ($id !== '' && (!in_array($id, $knownIds, true) || isset($seen[$id]))) { throw new RuntimeException('methods_invalid'); }
        $id = $id !== '' ? $id : bin2hex(random_bytes(8));
        $seen[$id] = true;
        $methods[] = ['id' => $id, 'type' => $type, 'value' => $value];
    }
    return $methods;
}

function meetingResolveMethod(MeetingProfile $profile, $id, bool $useDefault = false): ?array
{
    if (!is_string($id)) { throw new RuntimeException('method_invalid'); }
    $methods = $profile->methods();
    if (!$methods && $id === '') { return null; }
    if ($id === '' && $useDefault) { return $methods[0] ?? null; }
    foreach ($methods as $method) { if ($method['id'] === $id) { return $method; } }
    throw new RuntimeException('method_invalid');
}

function meetingDay(DateTimeImmutable $day, array $hours, array $busy, DateTimeImmutable $now, int $durationMinutes = 30, int $preparationMinutes = 0, int $closingMinutes = 0): array
{
    $durationMinutes = meetingValidateDuration($durationMinutes, MeetingProfile::MAX_DURATION_MINUTES);
    $preparationMinutes = MeetingProfile::validateBufferMinutes($preparationMinutes);
    $closingMinutes = MeetingProfile::validateBufferMinutes($closingMinutes);
    $row = $hours[(int)$day->format('N')];
    if (!$row['open'] || $day->modify('+1 day') <= $now) { return ['state' => 'closed', 'slots' => []]; }
    $workingStart = $day->modify($row['start']);
    $finish = $day->modify($row['end']);
    $pauseStart = $day->modify($row['pause_start']);
    $pauseEnd = $day->modify($row['pause_end']);
    $slots = [];
    $workingCount = 0;
    $busySlotCount = 0;
    // Iterate wall-clock half-hours, skipping non-existent or DST-crossing hours.
    [$hour, $minute] = array_map('intval', explode(':', $row['start']));
    for ($minutes = $hour * 60 + $minute; $minutes < 24 * 60; $minutes += 30) {
        $label = sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
        if ($label >= $row['end']) { break; }
        $start = $day->setTime(intdiv($minutes, 60), $minutes % 60);
        $end = $start->modify('+30 minutes');
        if ($end > $finish) { break; }
        $pause = $row['pause'] && $start < $pauseEnd && $end > $pauseStart;
        $conflict = meetingOverlap($start, $end, $busy);
        $free = !$pause && !$conflict && $start > $now
            && $start->format('H:i') === $label && $end->getTimestamp() - $start->getTimestamp() === 1800;
        $bufferStart = $start->modify('-' . $preparationMinutes . ' minutes');
        $bufferEnd = $end->modify('+' . $closingMinutes . ' minutes');
        $beforeFree = $bufferStart >= $workingStart && $bufferStart > $now
            && !meetingOverlap($bufferStart, $start, $busy)
            && !($row['pause'] && $bufferStart < $pauseEnd && $start > $pauseStart);
        $afterFree = $bufferEnd <= $finish && !meetingOverlap($end, $bufferEnd, $busy)
            && !($row['pause'] && $end < $pauseEnd && $bufferEnd > $pauseStart);
        $slots[] = ['time' => $label, 'start' => $start, 'end' => $end, 'free' => $free, 'pause' => $pause,
            'before_free' => $beforeFree, 'after_free' => $afterFree];
        if (!$pause) {
            $workingCount++;
            $busySlotCount += $free ? 0 : 1;
        }
    }
    $requiredSlots = intdiv($durationMinutes, 30);
    foreach ($slots as $index => &$slot) {
        $occupiedStart = $slot['start']->modify('-' . $preparationMinutes . ' minutes');
        $occupiedEnd = $slot['start']->modify('+' . ($durationMinutes + $closingMinutes) . ' minutes');
        $slot['bookable'] = $occupiedStart >= $workingStart && $occupiedEnd <= $finish && $occupiedStart > $now
            && !meetingOverlap($occupiedStart, $occupiedEnd, $busy)
            && !($row['pause'] && $occupiedStart < $pauseEnd && $occupiedEnd > $pauseStart);
        $slot['booking_end'] = $slot['start']->modify('+' . $durationMinutes . ' minutes');
        for ($offset = 0; $offset < $requiredSlots; $offset++) {
            $next = $slots[$index + $offset] ?? null;
            if (!$next || !$next['free'] || $next['start']->getTimestamp() !== $slot['start']->getTimestamp() + $offset * 1800) {
                $slot['bookable'] = false;
                break;
            }
        }
    }
    unset($slot);
    // A clicked half-hour may belong to a valid appointment starting earlier.
    $busySlotCount = 0;
    foreach ($slots as $index => &$slot) {
        $slot['selection_time'] = null;
        for ($first = $index; $first >= max(0, $index - $requiredSlots + 1); $first--) {
            if ($slots[$first]['bookable']) { $slot['selection_time'] = $slots[$first]['time']; break; }
        }
        if (!$slot['pause'] && $slot['selection_time'] === null) { $busySlotCount++; }
    }
    unset($slot);
    return ['state' => !$workingCount ? 'closed' : ($busySlotCount === $workingCount ? 'full' : ($busySlotCount ? 'partial' : 'free')),
        'slots' => $slots, 'workingCount' => $workingCount, 'busySlotCount' => $busySlotCount];
}

function meetingSave($object): void
{
    $result = $object->save();
    if (!is_array($result) || empty($result['status'])) { throw new RuntimeException('storage'); }
}

function meetingDestination(MeetingProfile $profile): ExternalCalendar
{
    $calendar = $profile->getDestinationCalendar();
    if (!$calendar) {
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
        $interval = $event->getBusyInterval();
        if ($interval !== null) { $busy[] = $interval; }
    }
    $calendars = new ArrayExternalCalendar();
    if (!$live) { commonExternalCalendarRefreshForDisplay($uid); }
    $calendars->loadForUser($uid, true);
    $destinationPresent = false;
    foreach ($calendars as $calendar) { $destinationPresent = $destinationPresent || (int)$calendar->getId() === (int)$profile->get('IDexternalcalendar'); }
    if (!$destinationPresent) { throw new RuntimeException('unavailable'); }
    $request ??= 'commonExternalCalendarHttpRequest';
    $icsCalendarIds = [];
    $availabilityCalendarIds = [];
    $available = [];
    foreach ($calendars as $calendar) {
        if ($calendar->get('availability_only')) { $availabilityCalendarIds[(int)$calendar->getId()] = true; }
        $isIcs = (string)$calendar->get('provider') === 'ics';
        if ($isIcs) { $icsCalendarIds[(int)$calendar->getId()] = true; }
        if ($live && !$isIcs) {
            $password = commonExternalCalendarDecryptPassword($calendar->get('password_encrypted'));
            if ($password === null) { throw new RuntimeException('unavailable'); }
            $result = $request($calendar->get('calendar_url'), $calendar->get('username'), $password,
                commonExternalCalendarBuildReport($start->modify('-1 day'), $end->modify('+1 day')), 'REPORT', 1);
            if (empty($result['status'])) { throw new RuntimeException('unavailable'); }
            $parsed = commonExternalCalendarParseReport($result['body'], true);
            if (empty($parsed['status'])) { throw new RuntimeException('unavailable'); }
            foreach (ExternalCalendarEvent::withLocalTimeBuffers((int)$calendar->getId(), $parsed['events']) as $event) {
                // Strict REPORT parsing already returns exclusive all-day ends.
                $eventEnd = $event['end_at'];
                if ($calendar->get('availability_only')) { $available[] = [$event['start_at'], $eventEnd]; }
                elseif ($event['is_busy']) {
                    $busy[] = [$event['start_at']->modify('-' . (int)$event['preparation_minutes'] . ' minutes'),
                        $eventEnd->modify('+' . (int)$event['closing_minutes'] . ' minutes')];
                }
            }
        } elseif ($live && $isIcs) {
            $last = $calendar->get('last_sync_at');
            if (!$last instanceof DateTimeInterface || $last->getTimestamp() < time() - 3600 || $calendar->get('last_sync_error')) {
                $result = commonExternalCalendarSynchronize($calendar, null, null, false, microtime(true) + 8);
                if (empty($result['status'])) { throw new RuntimeException('unavailable'); }
            }
        } else {
            $last = $calendar->get('last_sync_at');
            if (!$last instanceof DateTimeInterface || $last->getTimestamp() < time() - 5 * 3600 || $calendar->get('last_sync_error')) { throw new RuntimeException('unavailable'); }
        }
        if (!$live || $isIcs) {
            [$coveredStart, $coveredEnd] = ExternalCalendar::synchronizationRange($calendar->get('last_sync_at'));
            if ($storageStart < $coveredStart || $storageEnd > $coveredEnd) { throw new RuntimeException('unavailable'); }
        }
    }
    if (!$live || $icsCalendarIds !== []) {
        $external = new ArrayExternalCalendarEvent();
        $external->loadActiveForUserDateRange($uid, $storageStart, $storageEnd);
        foreach ($external as $event) {
            if ($live && !isset($icsCalendarIds[(int)$event->get('IDexternalcalendar')])) { continue; }
            $isAvailability = isset($availabilityCalendarIds[(int)$event->get('IDexternalcalendar')]);
            if ($isAvailability || $event->get('is_busy')) {
                $endAt = DateTimeImmutable::createFromInterface($event->get('end_at'));
                $interval = [$event->get('start_at'), $event->get('is_all_day') ? $endAt->modify('+1 second') : $endAt];
                if ($isAvailability) { $available[] = $interval; }
                else { $busy[] = $event->getBusyInterval(); }
            }
        }
    }
    return array_merge($busy, $availabilityCalendarIds ? ArrayExternalCalendarEvent::outsideAvailability($available, $start, $end) : []);
}

function meetingIcs(string $token, DateTimeImmutable $start, DateTimeImmutable $end, string $title, string $description, string $location = '', int $preparationMinutes = 0, int $closingMinutes = 0): string
{
    $escape = static fn($text) => str_replace(["\\", "\r\n", "\r", "\n", ';', ','], ['\\\\', '\\n', '\\n', '\\n', '\\;', '\\,'], $text);
    $utc = new DateTimeZone('UTC');
    $lines = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//OMO//Meeting//FR', 'CALSCALE:GREGORIAN', 'BEGIN:VEVENT',
        'UID:' . $token . '@omo2.org', 'DTSTAMP:' . gmdate('Ymd\THis\Z'),
        'DTSTART:' . $start->setTimezone($utc)->format('Ymd\THis\Z'), 'DTEND:' . $end->setTimezone($utc)->format('Ymd\THis\Z'),
        'SUMMARY:' . $escape($title), 'DESCRIPTION:' . $escape($description)];
    if ($location !== '') { $lines[] = 'LOCATION:' . $escape($location); }
    $lines[] = 'X-OMO-PREPARATION-MINUTES:' . MeetingProfile::validateBufferMinutes($preparationMinutes);
    $lines[] = 'X-OMO-CLOSING-MINUTES:' . MeetingProfile::validateBufferMinutes($closingMinutes);
    array_push($lines, 'STATUS:CONFIRMED', 'TRANSP:OPAQUE', 'END:VEVENT', 'END:VCALENDAR');
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
        'intro_html' => commonMailTextToHtml(meetingT('mail_body', ['date' => $start->format('d.m.Y H:i'),
            'duration' => (int)(($booking->get('end_at')->getTimestamp() - $booking->get('start_at')->getTimestamp()) / 60)])),
        'body_html' => commonMailTextToHtml($title),
        'details_html' => commonMailTextToHtml(
            meetingT('name') . ' : ' . (string)$booking->get('guest_name') . "\n"
            . meetingT('email') . ' : ' . (string)$booking->get('guest_email')
        ) . commonMailTextToHtml(meetingT('reason') . " :\n" . (string)$booking->get('reason'))
            . ($booking->meetingMethod() ? commonMailTextToHtml(meetingT('method') . ' : ' . meetingMethodLabel($booking->meetingMethod())) : ''),
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
                $method = meetingResolveMethod($profile, $draft['method']['id'] ?? '');
                if ($method !== ($draft['method'] ?? null)) { throw new RuntimeException('method_invalid'); }
                // Legacy drafts created before variable durations represented one hour.
                $durationMinutes = meetingValidateDuration($draft['duration'] ?? 60, $profile->maxDurationMinutes());
                $day = meetingDate($draft['date'], new DateTimeZone($profile->get('timezone')));
                $now = new DateTimeImmutable('now', $day->getTimezone());
                if ($day < $now->setTime(0, 0) || $day > $now->modify('+365 days')) { throw new RuntimeException('date_invalid'); }
                $preparationMinutes = (int)($existing ? $booking->get('preparation_minutes') : $profile->get('preparation_minutes'));
                $closingMinutes = (int)($existing ? $booking->get('closing_minutes') : $profile->get('closing_minutes'));
                $busy = meetingBusy($profile, $day, $day->modify('+1 day'), true, $draft['token'], $request);
                $slot = null;
                foreach (meetingDay($day, $profile->availabilityHours(), $busy, $now, $durationMinutes, $preparationMinutes, $closingMinutes)['slots'] as $candidate) {
                    if ($candidate['time'] === $draft['time'] && $candidate['bookable']) { $slot = $candidate; }
                }
                if (!$slot) { throw new RuntimeException('slot_taken'); }
                if (!$existing) {
                    $ownerName = (string)$profile->get('slug');
                    $owner = new User();
                    if ($owner->load($userId)) {
                        $displayName = trim((string)$owner->getScopedDisplayName());
                        if ($displayName !== '') { $ownerName = $displayName; }
                    }
                    $ics = meetingIcs($draft['token'], $slot['start'], $slot['booking_end'], meetingT('event_title', ['owner' => $ownerName, 'guest' => $draft['name']]),
                        $draft['name'] . "\n" . $draft['email'] . "\n\n" . $draft['reason']
                            . ($method ? "\n\n" . meetingT('method') . ' : ' . meetingMethodLabel($method) : ''),
                        $method ? meetingMethodLabel($method) : '', $preparationMinutes, $closingMinutes);
                    foreach (['IDuser' => $userId, 'IDexternalcalendar' => $calendar->getId(), 'token' => $draft['token'],
                        'preparation_minutes' => $preparationMinutes, 'closing_minutes' => $closingMinutes,
                        'guest_name' => $draft['name'], 'guest_email' => $draft['email'], 'reason' => $draft['reason'],
                        'meeting_method' => $method ? json_encode($method, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE) : null,
                        'start_at' => $slot['start']->setTimezone(new DateTimeZone(date_default_timezone_get())),
                        'end_at' => $slot['booking_end']->setTimezone(new DateTimeZone(date_default_timezone_get())), 'resource_url' => $resource,
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
                $event->applyImportedValues($values);
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
