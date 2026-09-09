<?php
require_once dirname(__DIR__, 3) . '/calendar/permissions_shared.php';

use dbObject\ArrayDocument;
use dbObject\ArrayEvent;
use dbObject\Document;
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
    $eventIds = array();
    foreach ($events as $event) {
        if (
            $event instanceof \dbObject\Event
            && (int)$event->getId() > 0
            && $event->isDraftVisibleToViewer($currentUserId)
        ) {
            $eventIds[] = (int)$event->getId();
        }
    }
    $associatedDocumentsByEventId = array();
    if ($eventIds !== array()) {
        $associatedDocuments = new ArrayDocument();
        $associatedDocuments->load(array(
            'where' => array(
                array('field' => 'IDorganization', 'value' => $currentOrganizationId),
                array('field' => 'IDevent', 'op' => 'in', 'value' => array_values(array_unique($eventIds))),
            ),
            'orderBy' => array(
                array('field' => 'id', 'dir' => 'ASC'),
            ),
            'hydrate' => true,
        ));
        foreach ($associatedDocuments as $document) {
            if (!($document instanceof Document) || (int)$document->getId() <= 0) {
                continue;
            }

            $documentEventId = (int)$document->get('IDevent');
            if ($documentEventId > 0) {
                $associatedDocumentsByEventId[$documentEventId][] = $document;
            }
        }
    }
    $holonNameCache = array();
    $organizationContextLabel = t('personal_space.calendar.context.organization', [], $lang, $sourceLang);
    $calendarScopeHolonIdMap = $dashboardModuleScope === 'contextual'
        ? ($dashboardModuleContextHolonId > 0 ? [$dashboardModuleContextHolonId => true] : [])
        : $dashboardModuleScopeHolonIdMap;

    foreach ($events as $event) {
        if (
            !($event instanceof \dbObject\Event)
            || (int)$event->getId() <= 0
            || !$event->isDraftVisibleToViewer($currentUserId)
        ) {
            continue;
        }

        $eventHolonId = (int)$event->get('IDholon');
        $eventStatus = \dbObject\Event::normalizeStatus($event->get('status'));
        if ($eventHolonId > 0 && !isset($calendarScopeHolonIdMap[$eventHolonId])) {
            continue;
        }

        $isMine = $currentUserId > 0
            && $event->isPersonallyRelevantToViewer($currentUserId, $currentOrganizationId);
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
        $associatedDocumentOpenData = omoCalendarBuildAssociatedDocumentOpenData(
            $event,
            $associatedDocumentsByEventId[(int)$event->getId()] ?? array(),
            $currentUserId,
            $currentOrganizationId,
            $eventHolonId > 0 ? $eventHolonId : $dashboardModuleContextHolonId
        );

        $calendarEvents[] = array(
            'id' => (int)$event->getId(),
            'holonId' => $eventHolonId,
            'status' => $eventStatus,
            'title' => trim((string)$event->get('title')) !== ''
                ? trim((string)$event->get('title'))
                : 'Evenement #' . (int)$event->getId(),
            'description' => trim((string)$event->get('description')),
            'contextLabel' => $contextLabel,
            'documentUrl' => $associatedDocumentOpenData['url'],
            'documentTitle' => $associatedDocumentOpenData['title'],
            'documentPvEditorUrl' => $associatedDocumentOpenData['pvEditorUrl'],
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
