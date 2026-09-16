<?php
namespace dbObject;

class ArrayExternalCalendarEvent extends ArrayDbObject
{
    /** Read the local cache; an optional refresh is orchestrated by the caller before this read. */
    public static function busyIntervalsForUser(int $userId, \DateTimeInterface $start, \DateTimeInterface $end): array
    {
        $result = ['intervals' => [], 'hasCalendars' => false, 'incomplete' => false];
        if (!ExternalCalendar::isStorageAvailable()) { return $result; }
        $calendars = ExternalCalendar::fetchAll('SELECT last_sync_at, last_sync_error FROM external_calendar WHERE IDuser = :uid AND active = 1', ['uid' => $userId]);
        if (!is_array($calendars)) { throw new \RuntimeException('storage'); }
        $result['hasCalendars'] = count($calendars) > 0;
        foreach ($calendars as $calendar) {
            $lastSync = !empty($calendar['last_sync_at']) ? new \DateTimeImmutable($calendar['last_sync_at']) : null;
            if ($lastSync === null || $lastSync < new \DateTimeImmutable('-2 hours') || !empty($calendar['last_sync_error'])
                || $start < new \DateTimeImmutable('-30 days') || $end > new \DateTimeImmutable('+400 days')) {
                $result['incomplete'] = true;
            }
        }
        $rows = ExternalCalendarEvent::fetchAll('SELECT e.start_at, e.end_at, e.is_all_day FROM external_calendar_event e
            JOIN external_calendar c ON c.id = e.IDexternalcalendar
            WHERE c.IDuser = :uid AND c.active = 1 AND e.active = 1 AND e.is_busy = 1
            AND e.start_at < :end AND e.end_at >= :start', ['uid' => $userId, 'start' => $start, 'end' => $end]);
        if (!is_array($rows)) { throw new \RuntimeException('storage'); }
        foreach ($rows as $row) {
            $busyStart = new \DateTimeImmutable($row['start_at']);
            $busyEnd = new \DateTimeImmutable($row['end_at']);
            if ($row['is_all_day']) { $busyEnd = $busyEnd->modify('+1 second'); }
            if ($busyStart < $end && $busyEnd > $start) { $result['intervals'][] = [$busyStart, $busyEnd]; }
        }
        return $result;
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
            'SELECT e.`id`
             FROM `external_calendar_event` e
             INNER JOIN `external_calendar` c ON c.`id` = e.`IDexternalcalendar`
             WHERE c.`IDuser` = :user_id
               AND c.`active` = 1
               AND e.`active` = 1
               AND e.`start_at` <= :range_end
               AND e.`end_at` >= :range_start
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
            if ($event->load((int)($row['id'] ?? 0))) {
                $this[] = $event;
            }
        }
    }
}
