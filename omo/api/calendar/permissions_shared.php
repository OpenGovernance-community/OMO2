<?php

if (!function_exists('omoCalendarResolveEventPermissionHolon')) {
    function omoCalendarResolveEventPermissionHolon(\dbObject\Event $event, ?\dbObject\Holon $rootHolon = null): ?\dbObject\Holon
    {
        $eventHolonId = (int)$event->get('IDholon');
        if ($eventHolonId <= 0) {
            return $rootHolon;
        }

        $eventHolon = new \dbObject\Holon();
        if (!$eventHolon->load($eventHolonId)) {
            return null;
        }

        if (
            !($rootHolon instanceof \dbObject\Holon)
            || !$eventHolon->isDescendantOf((int)$rootHolon->getId(), true)
        ) {
            return null;
        }

        return $eventHolon;
    }
}

if (!function_exists('omoCalendarCanUseEditEventPermission')) {
    function omoCalendarCanUseEditEventPermission(\dbObject\Holon $permissionHolon, int $organizationId, int $userId, bool $useSessionCache = true): bool
    {
        $organizationId = (int)$organizationId;
        $userId = (int)$userId;
        if ($organizationId <= 0 || $userId <= 0) {
            return false;
        }

        return $permissionHolon->isAllowed('CAN_EDIT_EVENT', $useSessionCache, $userId);
    }
}

if (!function_exists('omoCalendarCanEditEvent')) {
    function omoCalendarCanEditEvent(\dbObject\Event $event, int $organizationId, int $userId, ?\dbObject\Holon $rootHolon = null, bool $useSessionCache = true): bool
    {
        $organizationId = (int)$organizationId;
        $userId = (int)$userId;
        if ($organizationId <= 0 || $userId <= 0 || (int)$event->get('IDorganization') !== $organizationId || $event->isPastRecurringMeeting()) {
            return false;
        }

        $permissionHolon = omoCalendarResolveEventPermissionHolon($event, $rootHolon);
        return $permissionHolon instanceof \dbObject\Holon
            && omoCalendarCanUseEditEventPermission($permissionHolon, $organizationId, $userId, $useSessionCache);
    }
}

if (!function_exists('omoCalendarCanUseDeleteEventPermission')) {
    function omoCalendarCanUseDeleteEventPermission(\dbObject\Holon $permissionHolon, int $organizationId, int $userId, bool $useSessionCache = true): bool
    {
        $organizationId = (int)$organizationId;
        $userId = (int)$userId;
        if ($organizationId <= 0 || $userId <= 0) {
            return false;
        }

        return $permissionHolon->isAllowed('CAN_DELETE_EVENT', $useSessionCache, $userId);
    }
}

if (!function_exists('omoCalendarCanDeleteEvent')) {
    function omoCalendarCanDeleteEvent(\dbObject\Event $event, int $organizationId, int $userId, ?\dbObject\Holon $rootHolon = null, bool $useSessionCache = true): bool
    {
        $organizationId = (int)$organizationId;
        $userId = (int)$userId;
        if ($organizationId <= 0 || $userId <= 0 || (int)$event->get('IDorganization') !== $organizationId || $event->isPastRecurringMeeting()) {
            return false;
        }

        $permissionHolon = omoCalendarResolveEventPermissionHolon($event, $rootHolon);
        return $permissionHolon instanceof \dbObject\Holon
            && omoCalendarCanUseDeleteEventPermission($permissionHolon, $organizationId, $userId, $useSessionCache);
    }
}

if (!function_exists('omoCalendarBuildAssociatedDocumentOpenData')) {
    function omoCalendarBuildAssociatedDocumentOpenData(
        \dbObject\Event $event,
        $document,
        int $userId,
        int $organizationId,
        int $fallbackHolonId = 0
    ): array {
        $emptyData = [
            'url' => '',
            'title' => '',
            'pvEditorUrl' => '',
        ];

        $documents = is_array($document) ? $document : [$document];
        foreach ($documents as $documentItem) {
            if (
                !($documentItem instanceof \dbObject\Document)
                || (int)$documentItem->getId() <= 0
                || (int)$documentItem->get('IDorganization') !== $organizationId
            ) {
                continue;
            }

            $eventHolonId = (int)$event->get('IDholon');
            $canOpenDocument = $documentItem->isPvDocument()
                && !$documentItem->isPvValidated()
                ? (
                    $documentItem->canUserAccessPvBeforeValidation($userId, $organizationId)
                    || (
                        $documentItem->getPvStage() === \dbObject\Document::PV_STAGE_REVIEW
                        && $documentItem->canUserViewPvReadOnly(
                            $userId,
                            $organizationId,
                            $eventHolonId > 0 ? $eventHolonId : null
                        )
                    )
                )
                : $documentItem->canViewDirectlyInOrganization($organizationId);

            if (!$canOpenDocument) {
                continue;
            }

            $documentHolonId = (int)$documentItem->get('IDholon');
            if ($documentHolonId <= 0) {
                $documentHolonId = max(0, $fallbackHolonId);
            }

            $url = '/omo/api/documents/detail.php?id=' . rawurlencode((string)(int)$documentItem->getId());
            $url .= '&oid=' . rawurlencode((string)$organizationId);
            if ($documentHolonId > 0) {
                $url .= '&cid=' . rawurlencode((string)$documentHolonId);
            }

            $documentTitle = trim((string)$documentItem->get('title'));
            if ($documentTitle === '') {
                $documentTitle = 'Document #' . (int)$documentItem->getId();
            }

            return [
                'url' => $url,
                'title' => $documentTitle,
                'pvEditorUrl' => $documentItem->canUserOpenPvEditor($userId, $organizationId)
                    ? $documentItem->buildPvEditorUrl($organizationId)
                    : '',
            ];
        }

        return $emptyData;
    }
}

/** The caller owns the transaction and series lock. All targets are validated before deletion. */
function omoCalendarDeleteEvents(array $events, bool $deleteDocuments, int $organizationId, int $currentUserId, $rootHolon): array
{
    $deletedEvents = [];
    $documentsByEvent = [];
    // Validate every target before deleting any document or stopping generation.
    foreach ($events as $target) {
        if (!$target->isDraftVisibleToViewer($currentUserId)
            || !omoCalendarCanDeleteEvent($target, $organizationId, $currentUserId, $rootHolon, false)) {
            throw new \RuntimeException('event_delete_forbidden');
        }
        // Shared documents belong to every occurrence and survive meeting deletion.
        $documentsByEvent[$target->getId()] = $deleteDocuments ? array_values(array_filter($target->getAssociatedDocuments(),
            static fn($document) => (int)$document->get('IDevent') === (int)$target->getId())) : [];
        foreach ($documentsByEvent[$target->getId()] as $document) {
            if (!$document->canDeleteInOrganizationContext($organizationId, $currentUserId) || !$document->canDeleteDocument(true)) {
                throw new \RuntimeException('document_delete_forbidden');
            }
        }
    }
    foreach ($events as $target) {
        foreach ($documentsByEvent[$target->getId()] as $document) {
            if (!$document->delete()) { throw new \RuntimeException('document_delete_failed'); }
        }
        $deletedEvents[] = ['id' => (int)$target->getId(), 'project' => (int)$target->get('IDproject'), 'title' => (string)$target->get('title')];
        if (!$target->delete()) { throw new \RuntimeException('event_delete_failed'); }
    }
    return $deletedEvents;
}
