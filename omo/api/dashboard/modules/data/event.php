<?php
use dbObject\ArrayEvent;
use dbObject\Holon;

$calendarEvents = array();
if (!empty($enabledAppHashes['calendar']) && $currentUserId > 0) {
    $events = new ArrayEvent();
    $events->loadUpcomingForPersonalSpace($currentOrganizationId, $currentUserId, 5);
    $holonNameCache = array();
    $organizationContextLabel = t('personal_space.calendar.context.organization', [], $lang, $sourceLang);
    $limitCalendarToScope = $personalSpaceScope !== 'contextual'
        ? count($personalSpaceScopeHolonIdMap) > 0
        : $currentHolonId > 0;
    $calendarScopeHolonIdMap = $personalSpaceScope === 'contextual'
        ? ($currentHolonId > 0 ? [$currentHolonId => true] : [])
        : $personalSpaceScopeHolonIdMap;

    foreach ($events as $event) {
        if (!($event instanceof \dbObject\Event) || (int)$event->getId() <= 0) {
            continue;
        }

        $eventHolonId = (int)$event->get('IDholon');
        $eventHasExplicitInvitations = $event->hasExplicitInvitations();
        if (
            $limitCalendarToScope
            && ($eventHolonId <= 0 || !isset($calendarScopeHolonIdMap[$eventHolonId]))
            && !$eventHasExplicitInvitations
        ) {
            continue;
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

        $calendarEvents[] = array(
            'id' => (int)$event->getId(),
            'holonId' => $eventHolonId,
            'title' => trim((string)$event->get('title')) !== ''
                ? trim((string)$event->get('title'))
                : 'Evenement #' . (int)$event->getId(),
            'description' => trim((string)$event->get('description')),
            'contextLabel' => $contextLabel,
            'rangeLabel' => $formatCalendarRange(
                $event->get('start_at'),
                $event->get('end_at'),
                (bool)$event->get('is_all_day')
            ),
        );
    }
}
