<?php

function commonUserAvailabilityFormatDate(DateTimeInterface $date, bool $withWeekday = false): string
{
    if (class_exists('IntlDateFormatter')) {
        $formatter = new IntlDateFormatter('fr_CH', $withWeekday ? IntlDateFormatter::FULL : IntlDateFormatter::LONG, IntlDateFormatter::NONE);
        $formatted = $formatter->format($date);
        if (is_string($formatted) && $formatted !== '') {
            return $formatted;
        }
    }

    return $date->format($withWeekday ? 'l j F Y' : 'F Y');
}

/**
 * Personal availability is intentionally limited to intervals. Event titles,
 * descriptions and external calendar names must not leave the owner's agenda.
 */
function commonUserAvailabilityLoadBusyIntervals(int $userId, DateTimeInterface $start, DateTimeInterface $end, ?bool &$incomplete = null, int $excludeEventId = 0): array
{
    $incomplete = false;
    if ($userId <= 0 || $end <= $start) {
        return [];
    }

    $storageZone = new DateTimeZone(date_default_timezone_get());
    $storageStart = DateTimeImmutable::createFromInterface($start)->setTimezone($storageZone);
    $storageEnd = DateTimeImmutable::createFromInterface($end)->setTimezone($storageZone);
    $intervals = [];

    $events = new \dbObject\ArrayEvent();
    $events->loadBusyForUserDateRange($userId, $storageStart, $storageEnd);
    foreach ($events as $event) {
        if ($excludeEventId > 0 && (int)$event->getId() === $excludeEventId) {
            continue;
        }
        $interval = $event->getBusyInterval();
        if ($interval !== null) {
            $intervals[] = $interval;
        }
    }

    $external = \dbObject\ArrayExternalCalendarEvent::busyIntervalsForUser($userId, $storageStart, $storageEnd);
    $incomplete = !empty($external['incomplete']);
    foreach ($external['intervals'] as $interval) {
        $intervals[] = $interval;
    }

    return $intervals;
}

function commonUserAvailabilityOverlaps(DateTimeInterface $start, DateTimeInterface $end, array $busy): bool
{
    foreach ($busy as $interval) {
        if (!is_array($interval) || count($interval) < 2) {
            continue;
        }
        [$busyStart, $busyEnd] = $interval;
        if ($busyStart instanceof DateTimeInterface && $busyEnd instanceof DateTimeInterface && $start < $busyEnd && $end > $busyStart) {
            return true;
        }
    }

    return false;
}

/** Build a half-hour, title-free availability view from a user's configured working hours. */
function commonUserAvailabilityBuildDay(DateTimeImmutable $day, array $hours, array $busy): array
{
    $row = $hours[(int)$day->format('N')] ?? [];
    $startLabel = (string)($row['start'] ?? '09:00');
    $endLabel = (string)($row['end'] ?? '17:00');
    if (empty($row['open']) || $startLabel >= $endLabel) {
        return ['state' => 'closed', 'slots' => []];
    }

    $start = $day->setTime((int)substr($startLabel, 0, 2), (int)substr($startLabel, 3, 2));
    $end = $day->setTime((int)substr($endLabel, 0, 2), (int)substr($endLabel, 3, 2));
    $pauseStart = $day->setTime((int)substr((string)($row['pause_start'] ?? '12:00'), 0, 2), (int)substr((string)($row['pause_start'] ?? '12:00'), 3, 2));
    $pauseEnd = $day->setTime((int)substr((string)($row['pause_end'] ?? '13:00'), 0, 2), (int)substr((string)($row['pause_end'] ?? '13:00'), 3, 2));
    $slots = [];
    $workingCount = 0;
    $busyCount = 0;

    for ($slotStart = $start; $slotStart < $end; $slotStart = $slotStart->modify('+30 minutes')) {
        $slotEnd = $slotStart->modify('+30 minutes');
        $isPause = !empty($row['pause']) && $slotStart < $pauseEnd && $slotEnd > $pauseStart;
        $isBusy = !$isPause && commonUserAvailabilityOverlaps($slotStart, $slotEnd, $busy);
        if (!$isPause) {
            $workingCount += 1;
            $busyCount += $isBusy ? 1 : 0;
        }
        $slots[] = ['start' => $slotStart, 'end' => $slotEnd, 'busy' => $isBusy, 'pause' => $isPause];
    }

    $state = $workingCount === 0 ? 'closed' : ($busyCount === 0 ? 'free' : ($busyCount === $workingCount ? 'full' : 'partial'));
    return ['state' => $state, 'slots' => $slots, 'workingCount' => $workingCount, 'busySlotCount' => $busyCount];
}

/** Show only times shared by every participant; a pause or busy slot blocks the group. */
function commonUserAvailabilityBuildCombinedDay(DateTimeImmutable $day, array $participants): array
{
    if (!$participants) {
        return ['state' => 'closed', 'slots' => []];
    }

    $byPerson = [];
    foreach ($participants as $participant) {
        $dayData = commonUserAvailabilityBuildDay($day, $participant['hours'], $participant['busy']);
        if (!$dayData['slots']) {
            return ['state' => 'closed', 'slots' => []];
        }
        $slots = [];
        foreach ($dayData['slots'] as $slot) {
            $slots[$slot['start']->format('H:i')] = $slot;
        }
        $byPerson[] = $slots;
    }

    $slots = [];
    $workingCount = 0;
    $busyCount = 0;
    foreach ($byPerson[0] as $time => $firstSlot) {
        $matching = [];
        foreach ($byPerson as $personSlots) {
            if (!isset($personSlots[$time])) {
                continue 2;
            }
            $matching[] = $personSlots[$time];
        }
        $isPause = false;
        $occupiedPeople = 0;
        foreach ($matching as $slot) {
            $isPause = $isPause || $slot['pause'];
            $occupiedPeople += $slot['busy'] ? 1 : 0;
        }
        $isBusy = $occupiedPeople > 0;
        if (!$isPause) {
            $workingCount++;
            $busyCount += $isBusy ? 1 : 0;
        }
        $slots[] = ['start' => $firstSlot['start'], 'end' => $firstSlot['end'], 'busy' => $isBusy, 'pause' => $isPause,
            'busyCount' => $occupiedPeople, 'participantCount' => count($participants)];
    }

    $state = $workingCount === 0 ? 'closed' : ($busyCount === 0 ? 'free' : ($busyCount === $workingCount ? 'full' : 'partial'));
    return ['state' => $state, 'slots' => $slots, 'workingCount' => $workingCount, 'busySlotCount' => $busyCount];
}

/** Compact, title-free daily data for local filtering: 0 closed, 1 free, 2 busy, 3 pause. */
function commonUserAvailabilityEncodeDay(array $day): string
{
    $codes = str_repeat('0', 48);
    foreach ($day['slots'] as $slot) {
        $index = (int)$slot['start']->format('G') * 2 + ((int)$slot['start']->format('i') >= 30 ? 1 : 0);
        $codes[$index] = $slot['pause'] ? '3' : ($slot['busy'] ? '2' : '1');
    }
    return $codes;
}
