<?php

if (!function_exists('omoCurrentUserCanManageOrganizationApplications')) {
    function omoCurrentUserCanManageOrganizationApplications($organizationId, $userId = null, $forcePermissionRefresh = false)
    {
        $organizationId = (int)$organizationId;
        $userId = $userId === null
            ? (int)commonGetCurrentUserId()
            : (int)$userId;

        if ($organizationId <= 0 || $userId <= 0) {
            return false;
        }

        if (!commonUserHasOrganizationAccess($userId, $organizationId)) {
            return false;
        }

        if (commonUserHasAdminOverride($userId, $organizationId)) {
            return true;
        }

        if ($userId !== (int)commonGetCurrentUserId()) {
            return false;
        }

        $permissionSet = commonGetCurrentUserOrganizationPermissionSet($organizationId, (bool)$forcePermissionRefresh);
        $scope = $permissionSet['permissions']['CAN_ADD_APP'] ?? null;

        return is_array($scope) && !empty($scope['organization']);
    }
}
