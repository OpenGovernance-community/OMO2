<?php
use dbObject\ArrayEvent;
use dbObject\Holon;

$calendarEvents = array();
$dashboardEventCounts = array('all' => 0, 'mine' => 0);
if (!empty($enabledAppHashes['calendar'])) {
    $events = new ArrayEvent();
    $dashboardEventWindowStart = new DateTimeImmutable('now');
    $dashboardEventWindowEnd = $dashboardEventWindowStart->modify('+30 days');
    $events->loadForOrganizationDateRange(
        $currentOrganizationId,
        $dashboardEventWindowStart,
        $dashboardEventWindowEnd,
        false
    );
    $holonNameCache = array();
    $organizationContextLabel = t('personal_space.calendar.context.organization', [], $lang, $sourceLang);
    $calendarScopeHolonIdMap = $dashboardModuleScope === 'contextual'
        ? ($dashboardModuleContextHolonId > 0 ? [$dashboardModuleContextHolonId => true] : [])
        : $dashboardModuleScopeHolonIdMap;

    foreach ($events as $event) {
        if (!($event instanceof \dbObject\Event) || (int)$event->getId() <= 0) {
            continue;
        }

        $eventHolonId = (int)$event->get('IDholon');
        if ($eventHolonId > 0 && !isset($calendarScopeHolonIdMap[$eventHolonId])) {
            continue;
        }

        $isMine = $currentUserId > 0
            && (
                (int)$event->get('IDuser') === $currentUserId
                || $event->isVisibleToInvitationViewer($currentUserId, $currentOrganizationId)
            );
        $dashboardEventCounts['all']++;
        if ($isMine) {
            $dashboardEventCounts['mine']++;
        }

        $contextLabel = $organizationContextLabel;

        if ($eventHolonId > 0) {
            if (!array_key_exists($eventHolonId, $holonNameCache)) {
                $holon = new Holon();
                $holonNameCache[$eventHolonId] = $holon->load($eventHolonId)
                    ? trim((string)$holon->get('name'))
                    : '';
            }

            if (trim((string)$holonNameCache[$eventHolonId]) !== '') {
                $contextLabel = (string)$holonNameCache[$eventHolonId];
            }
        }

        $locationData = $event->getLocationDisplayData();
        $locationParts = array_values(array_filter(array(
            trim((string)($locationData['modeLabel'] ?? '')),
            trim((string)($locationData['address'] ?? '')),
            trim((string)($locationData['videoUrl'] ?? '')),
        )));

        $calendarEvents[] = array(
            'id' => (int)$event->getId(),
            'holonId' => $eventHolonId,
            'title' => trim((string)$event->get('title')) !== ''
                ? trim((string)$event->get('title'))
                : 'Evenement #' . (int)$event->getId(),
            'description' => trim((string)$event->get('description')),
            'contextLabel' => $contextLabel,
            'locationLabel' => implode(' · ', $locationParts),
            'filters' => $isMine ? array('all', 'mine') : array('all'),
            'rangeLabel' => $formatCalendarRange(
                $event->get('start_at'),
                $event->get('end_at'),
                (bool)$event->get('is_all_day')
            ),
        );
    }
}
