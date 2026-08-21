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
        static $configuredPermissionCache = [];

        $organizationId = (int)$organizationId;
        $userId = (int)$userId;
        if ($organizationId <= 0 || $userId <= 0) {
            return false;
        }

        $cacheKey = $organizationId . ':' . $userId;
        if (!array_key_exists($cacheKey, $configuredPermissionCache)) {
            $permissionSet = \dbObject\HolonPermission::buildUserPermissionSetForOrganization(
                $userId,
                $organizationId,
                ['CAN_EDIT_EVENT']
            );
            $configuredPermissionCache[$cacheKey] = !empty($permissionSet['definedPermissionKeys']['CAN_EDIT_EVENT']);
        }

        return $configuredPermissionCache[$cacheKey]
            && $permissionHolon->isAllowed('CAN_EDIT_EVENT', $useSessionCache, $userId);
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
