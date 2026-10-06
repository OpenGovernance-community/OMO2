<?php
namespace dbObject;

class ArrayExternalCalendarEvent extends ArrayDbObject
{
    /** Read the local cache; an optional refresh is orchestrated by the caller before this read. */
    public static function busyIntervalsForUser(int $userId, \DateTimeInterface $start, \DateTimeInterface $end): array
    {
        $result = ['intervals' => [], 'unavailable' => [], 'hasAvailabilityCalendars' => false, 'hasCalendars' => false, 'incomplete' => false];
        if (!ExternalCalendar::isStorageAvailable()) { return $result; }
        $calendars = ExternalCalendar::fetchAll('SELECT availability_only, last_sync_at, last_sync_error FROM external_calendar WHERE IDuser = :uid AND active = 1', ['uid' => $userId]);
        if (!is_array($calendars)) { throw new \RuntimeException('storage'); }
        $result['hasCalendars'] = count($calendars) > 0;
        foreach ($calendars as $calendar) {
            $result['hasAvailabilityCalendars'] = $result['hasAvailabilityCalendars'] || (bool)$calendar['availability_only'];
            $lastSync = !empty($calendar['last_sync_at']) ? new \DateTimeImmutable($calendar['last_sync_at']) : null;
            [$coveredStart, $coveredEnd] = ExternalCalendar::synchronizationRange($lastSync);
            if ($lastSync === null || $lastSync < new \DateTimeImmutable('-1 hour') || !empty($calendar['last_sync_error'])
                || $start < $coveredStart || $end > $coveredEnd) {
                $result['incomplete'] = true;
            }
        }
        $rows = ExternalCalendarEvent::fetchAll('SELECT e.start_at, e.end_at, e.is_all_day, e.preparation_minutes, e.closing_minutes, c.availability_only FROM external_calendar_event e
            JOIN external_calendar c ON c.id = e.IDexternalcalendar
            WHERE c.IDuser = :uid AND c.active = 1 AND e.active = 1 AND (e.is_busy = 1 OR c.availability_only = 1)
            AND DATE_SUB(e.start_at, INTERVAL e.preparation_minutes MINUTE) < :end AND DATE_ADD(e.end_at, INTERVAL e.closing_minutes MINUTE) >= :start', ['uid' => $userId, 'start' => $start, 'end' => $end]);
        if (!is_array($rows)) { throw new \RuntimeException('storage'); }
        $available = [];
        foreach ($rows as $row) {
            $busyStart = new \DateTimeImmutable($row['start_at']);
            $busyEnd = new \DateTimeImmutable($row['end_at']);
            if ($row['is_all_day']) { $busyEnd = $busyEnd->modify('+1 second'); }
            if (!$row['availability_only']) {
                $busyStart = $busyStart->modify('-' . (int)$row['preparation_minutes'] . ' minutes');
                $busyEnd = $busyEnd->modify('+' . (int)$row['closing_minutes'] . ' minutes');
            }
            if ($busyStart < $end && $busyEnd > $start) {
                if ($row['availability_only']) { $available[] = [$busyStart, $busyEnd]; }
                else { $result['intervals'][] = [$busyStart, $busyEnd]; }
            }
        }
        if ($result['hasAvailabilityCalendars']) { $result['unavailable'] = self::outsideAvailability($available, $start, $end); }
        return $result;
    }

    /** Complement of the union of opening windows; touching windows are continuous. */
    public static function outsideAvailability(array $available, \DateTimeInterface $start, \DateTimeInterface $end): array
    {
        usort($available, static fn($a, $b) => $a[0] <=> $b[0]);
        $cursor = \DateTimeImmutable::createFromInterface($start);
        $limit = \DateTimeImmutable::createFromInterface($end);
        $closed = [];
        foreach ($available as [$from, $to]) {
            if ($to <= $cursor || $from >= $limit || $to <= $from) { continue; }
            if ($from > $cursor) { $closed[] = [$cursor, \DateTimeImmutable::createFromInterface($from)]; }
            $cursor = \DateTimeImmutable::createFromInterface($to < $limit ? $to : $limit);
        }
        if ($cursor < $limit) { $closed[] = [$cursor, $limit]; }
        return $closed;
    }

    public static function objectName()
    {
        return '\\dbObject\\ExternalCalendarEvent';
    }

    public function loadActiveForUserDateRange($userId, \DateTimeInterface $rangeStart, \DateTimeInterface $rangeEnd)
    {
        $userId = (int)$userId;
        $this->exchangeArray([]);
        if ($userId <= 0 || !ExternalCalendar::isStorageAvailable()) {
            return;
        }

        $rows = ExternalCalendarEvent::fetchAll(
            'SELECT e.*
             FROM `external_calendar_event` e
             INNER JOIN `external_calendar` c ON c.`id` = e.`IDexternalcalendar`
             WHERE c.`IDuser` = :user_id
               AND c.`active` = 1
               AND e.`active` = 1
               AND DATE_SUB(e.`start_at`, INTERVAL e.preparation_minutes MINUTE) <= :range_end
               AND DATE_ADD(e.`end_at`, INTERVAL e.closing_minutes MINUTE) >= :range_start
             ORDER BY e.`start_at` ASC, e.`end_at` ASC, e.`id` ASC',
            [
                'user_id' => $userId,
                'range_start' => $rangeStart,
                'range_end' => $rangeEnd,
            ]
        );
        if (!is_array($rows)) {
            return;
        }

        foreach ($rows as $row) {
            $event = new ExternalCalendarEvent();
            if ($event->hydrateFromDatabaseRow($row, true)) {
                $this[] = $event;
            }
        }
    }
}
