<?php
require_once dirname(__DIR__) . '/caldav.php';

use dbObject\ArrayEvent;
use dbObject\ArrayExternalCalendarEvent;
use dbObject\CalendarShare;
use dbObject\Event;

/** Export normalized local instances only: no remote requests, alarms or attendee addresses. */
function calendarShareBuildFeed(CalendarShare $share, ?DateTimeImmutable $now = null): string
{
    [$start, $end] = $share->visibilityRange($now);
    $userId = (int)$share->get('IDuser');
    $omo = new ArrayEvent();
    $omo->loadBusyForUserDateRange($userId, $start, $end);
    $external = new ArrayExternalCalendarEvent();
    $external->loadActiveForUserDateRange($userId, $start, $end);
    $details = (bool)$share->get('details');
    $lines = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//OpenMyOrganization//Calendar Share//EN',
        'CALSCALE:GREGORIAN', 'X-WR-CALNAME:Agenda partage', 'REFRESH-INTERVAL;VALUE=DURATION:PT1H', 'X-PUBLISHED-TTL:PT1H'];
    foreach ([$omo, $external] as $events) {
        foreach ($events as $event) {
            $isOmo = $event instanceof Event;
            $busy = $isOmo || (bool)$event->get('is_busy');
            if (!$details && !$busy) { continue; }
            $allDay = (bool)$event->get('is_all_day');
            if ($isOmo) {
                $interval = $event->getBusyInterval();
                if ($interval === null) { continue; }
                [$eventStart, $eventEnd] = $interval;
            } else {
                $eventStart = DateTimeImmutable::createFromInterface($event->get('start_at'));
                $eventEnd = DateTimeImmutable::createFromInterface($event->get('end_at'));
                if ($allDay) {
                    $eventStart = $eventStart->setTime(0, 0);
                    $eventEnd = $eventEnd->setTime(0, 0)->modify('+1 day');
                }
            }
            if ($eventStart >= $end || $eventEnd <= $start || $eventEnd <= $eventStart) { continue; }
            // Stable per link, with no internal IDs or remote UIDs disclosed.
            $uid = hash_hmac('sha256', ($isOmo ? 'omo:' : 'external:') . $event->getId(), (string)$share->get('token'));
            $stamp = $event->get('updated_at') ?: $event->get('created_at') ?: $share->get('created_at');
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:' . $uid . '@omo-calendar-share';
            $lines[] = 'DTSTAMP:' . commonCardDavFormatTimestamp($stamp);
            $lines[] = 'LAST-MODIFIED:' . commonCardDavFormatTimestamp($stamp);
            $lines[] = 'SUMMARY:' . commonCalDavEscapeText($details ? (string)$event->get('title') : 'Occupe');
            $lines[] = 'TRANSP:' . ($busy ? 'OPAQUE' : 'TRANSPARENT');
            if ($details) {
                $description = html_entity_decode(strip_tags((string)$event->get('description')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $location = $isOmo ? (string)($event->getLocationDisplayData()['address'] ?? '') : (string)$event->get('location');
                if ($description !== '') { $lines[] = 'DESCRIPTION:' . commonCalDavEscapeText($description); }
                if ($location !== '') { $lines[] = 'LOCATION:' . commonCalDavEscapeText($location); }
            }
            $lines[] = $allDay ? commonCalDavBuildAllDayDateLine('DTSTART', $eventStart)
                : commonCalDavBuildTimedDateLine('DTSTART', $eventStart, 'UTC');
            $lines[] = $allDay ? commonCalDavBuildAllDayDateLine('DTEND', $eventEnd)
                : commonCalDavBuildTimedDateLine('DTEND', $eventEnd, 'UTC');
            $lines[] = 'END:VEVENT';
        }
    }
    $lines[] = 'END:VCALENDAR';
    return implode("\r\n", array_map('commonCalDavFoldLine', $lines)) . "\r\n";
}
