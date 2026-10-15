<?php
require_once dirname(__DIR__) . '/caldav.php';

use dbObject\ArrayEvent;
use dbObject\CalendarShare;
use dbObject\Event;
use dbObject\Holon;
use dbObject\Organization;
use dbObject\UserOrganization;

/** Reuse the DAV revision cache for organization-scoped subscriptions. */
function calendarShareReadFeed(CalendarShare $share): string
{
    $scope = $share->getScopedCalendarConfig();
    $viewer = new \dbObject\User();
    if (!is_array($scope) || !$viewer->load((int)$share->get('IDuser'))
        || !commonCalDavCanUseScopedResponseCache($viewer, $scope)) {
        header('X-OMO-ICS-Cache: BYPASS');
        return calendarShareBuildFeed($share);
    }
    $organizationId = (int)$scope['organizationId'];
    $GLOBALS['commonPerformanceOrganizationId'] = $organizationId;
    $revision = \dbObject\CalDavCache::getOrganizationRevision($organizationId);
    $key = \dbObject\CalDavCache::buildResponseKey(['schema' => 'ics-1', 'share' => (int)$share->getId(),
        'token' => hash('sha256', (string)$share->get('token')), 'user' => (int)$viewer->getId(),
        'scope' => $scope, 'months' => (int)$share->get('months'), 'details' => (bool)$share->get('details'),
        'day' => (new DateTimeImmutable('today'))->format('Y-m-d')]);
    $cached = \dbObject\CalDavCache::readResponse($organizationId, $revision, $key);
    if (is_array($cached)) { header('X-OMO-ICS-Cache: HIT'); return $cached['body']; }
    $lock = \dbObject\CalDavCache::acquireBuildLock($organizationId, $revision, $key);
    try {
        $cached = \dbObject\CalDavCache::readResponse($organizationId, $revision, $key);
        if (is_array($cached)) { header('X-OMO-ICS-Cache: HIT'); return $cached['body']; }
        $content = calendarShareBuildFeed($share);
        $saved = is_resource($lock) && \dbObject\CalDavCache::writeResponse($organizationId, $revision, $key,
            ['status' => 200, 'headers' => [], 'body' => $content]);
        header('X-OMO-ICS-Cache: ' . ($saved ? 'MISS' : 'BYPASS'));
        return $content;
    } finally { \dbObject\CalDavCache::releaseBuildLock($lock); }
}

function calendarShareLoadScopedOmoEvents(CalendarShare $share, array $scope): array
{
    $userId = (int)$share->get('IDuser');
    $organizationId = (int)($scope['organizationId'] ?? 0);
    $holonId = (int)($scope['holonId'] ?? 0);
    $range = (string)($scope['range'] ?? '');
    if (
        $userId <= 0
        || $organizationId <= 0
        || $holonId <= 0
        || !UserOrganization::hasActiveMembership($userId, $organizationId)
    ) {
        return [];
    }

    $organization = new Organization();
    if (!$organization->load($organizationId) || !$organization->isApplicationEnabled('calendar', $userId)) {
        return [];
    }

    $rootHolon = $organization->getEnabledStructuralRootHolon();
    $holon = new Holon();
    if (
        !($rootHolon instanceof Holon)
        || !$holon->load($holonId)
        || !$holon->isDescendantOf((int)$rootHolon->getId(), true)
    ) {
        return [];
    }

    $visibleHolonIds = [(int)$holon->getId() => true];
    if ($range === 'children') {
        $visibleHolonIds += omoApiGetDirectChildHolonIdMap($holon);
    } elseif ($range === 'descendants') {
        $visibleHolonIds += omoApiGetDescendantHolonIdMap($holon);
    }

    $events = new ArrayEvent();
    $events->loadForOrganization($organizationId, false, true);
    $visibleEvents = [];
    foreach ($events as $event) {
        if (!($event instanceof Event) || !$event->isInvitedToEvent($userId, $organizationId)) {
            continue;
        }

        $eventHolonId = (int)$event->get('IDholon');
        if ($eventHolonId > 0 && !isset($visibleHolonIds[$eventHolonId])) {
            continue;
        }

        $visibleEvents[] = $event;
    }

    return $visibleEvents;
}

/** Export only OMO appointments; connected calendars must never be re-exported. */
function calendarShareBuildFeed(CalendarShare $share, ?DateTimeImmutable $now = null): string
{
    [$start, $end] = $share->visibilityRange($now);
    $userId = (int)$share->get('IDuser');
    $scope = $share->getScopedCalendarConfig();
    if (is_array($scope)) {
        $events = calendarShareLoadScopedOmoEvents($share, $scope);
    } else {
        $events = new ArrayEvent();
        $events->loadBusyForUserDateRange($userId, $start, $end);
    }
    $details = (bool)$share->get('details');
    $lines = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//OpenMyOrganization//Calendar Share//EN',
        'CALSCALE:GREGORIAN', 'X-WR-CALNAME:Agenda partage', 'REFRESH-INTERVAL;VALUE=DURATION:PT1H', 'X-PUBLISHED-TTL:PT1H'];
    foreach ($events as $event) {
        $allDay = (bool)$event->get('is_all_day');
        [$preparation] = $event->getTimeBuffers($userId);
        // Personal occupied bounds remain internal to OMO.
        $interval = $event->getBusyInterval();
        if ($interval === null) { continue; }
        [$eventStart, $eventEnd] = $interval;
        if ($eventStart >= $end || $eventEnd <= $start || $eventEnd <= $eventStart) { continue; }
        // Stable per link, with no internal IDs or remote UIDs disclosed.
        $uid = hash_hmac('sha256', 'omo:' . $event->getId(), (string)$share->get('token'));
        $stamp = $event->get('updated_at') ?: $event->get('created_at') ?: $share->get('created_at');
        $lines[] = 'BEGIN:VEVENT';
        $lines[] = 'UID:' . $uid . '@omo-calendar-share';
        $lines[] = 'DTSTAMP:' . commonCardDavFormatTimestamp($stamp);
        $lines[] = 'LAST-MODIFIED:' . commonCardDavFormatTimestamp($stamp);
        $lines[] = 'SUMMARY:' . commonCalDavEscapeText($details ? (string)$event->get('title') : 'Occupe');
        $lines[] = 'TRANSP:OPAQUE';
        if ($details) {
            $description = html_entity_decode(strip_tags((string)$event->get('description')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $location = (string)($event->getLocationDisplayData()['address'] ?? '');
            if ($description !== '') { $lines[] = 'DESCRIPTION:' . commonCalDavEscapeText($description); }
            if ($location !== '') { $lines[] = 'LOCATION:' . commonCalDavEscapeText($location); }
        }
        $lines[] = $allDay ? commonCalDavBuildAllDayDateLine('DTSTART', $eventStart)
            : commonCalDavBuildTimedDateLine('DTSTART', $eventStart, 'UTC');
        $lines[] = $allDay ? commonCalDavBuildAllDayDateLine('DTEND', $eventEnd)
            : commonCalDavBuildTimedDateLine('DTEND', $eventEnd, 'UTC');
        array_push($lines, ...commonCalendarIcsReminderLines($preparation, $details ? (string)$event->get('title') : 'Occupe'));
        $lines[] = 'END:VEVENT';
    }
    $lines[] = 'END:VCALENDAR';
    return implode("\r\n", array_map('commonCalDavFoldLine', $lines)) . "\r\n";
}
