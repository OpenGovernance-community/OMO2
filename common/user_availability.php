<?php

/**
 * Personal availability is intentionally limited to intervals. Event titles,
 * descriptions and external calendar names must not leave the owner's agenda.
 */
function commonUserAvailabilityLoadBusyIntervals(int $userId, DateTimeInterface $start, DateTimeInterface $end): array
{
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
        $interval = $event->getBusyInterval();
        if ($interval !== null) {
            $intervals[] = $interval;
        }
    }

    $external = \dbObject\ArrayExternalCalendarEvent::busyIntervalsForUser($userId, $storageStart, $storageEnd);
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
    return ['state' => $state, 'slots' => $slots];
}
