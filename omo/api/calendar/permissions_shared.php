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
        if ($organizationId <= 0 || $userId <= 0 || (int)$event->get('IDorganization') !== $organizationId) {
            return false;
        }

        $permissionHolon = omoCalendarResolveEventPermissionHolon($event, $rootHolon);
        return $permissionHolon instanceof \dbObject\Holon
            && omoCalendarCanUseEditEventPermission($permissionHolon, $organizationId, $userId, $useSessionCache);
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
