<?php

use dbObject\Holon;
use dbObject\Organization;
use dbObject\UserHolon;

if (!function_exists('omoDashboardViewPreferencesResolveCapabilities')) {
    function omoDashboardViewPreferencesResolveCapabilities($interfaceLevel, $holonId, $templateKey, $baseTypeKey, array $roles): array
    {
        $interfaceLevel = (int)$interfaceLevel;
        $holonId = (int)$holonId;
        $templateKey = trim((string)$templateKey);
        $baseTypeKey = trim((string)$baseTypeKey);
        $isMember = !empty($roles['isMember']);
        $isHolonAdmin = !empty($roles['isHolonAdmin']);
        $isOrganizationAdmin = !empty($roles['isOrganizationAdmin']);
        $isSiteAdmin = !empty($roles['isSiteAdmin']);

        $capabilities = array(
            'canSavePersonal' => false,
            'canSaveTemporary' => false,
            'canSaveHolon' => false,
            'canSaveOrganizationTemplate' => false,
            'canSaveApplicationType' => false,
            'canSaveGlobal' => $isSiteAdmin,
        );

        if ($interfaceLevel === Organization::INTERFACE_LEVEL_DISCOVERY) {
            $capabilities['canSaveTemporary'] = $isMember || $isHolonAdmin || $isOrganizationAdmin || $isSiteAdmin;
            $capabilities['canSaveOrganizationTemplate'] = ($isOrganizationAdmin || $isSiteAdmin) && $templateKey !== '';
            $capabilities['canSaveApplicationType'] = $isSiteAdmin && $baseTypeKey !== '';
        } else {
            $capabilities['canSavePersonal'] = $holonId > 0 && $isMember;
            $capabilities['canSaveHolon'] = $holonId > 0 && ($isHolonAdmin || $isOrganizationAdmin || $isSiteAdmin);
            $capabilities['canSaveOrganizationTemplate'] = ($isOrganizationAdmin || $isSiteAdmin) && $templateKey !== '';
            $capabilities['canSaveApplicationType'] = $isSiteAdmin && $baseTypeKey !== '';
        }

        $capabilities['canEdit'] = in_array(true, $capabilities, true);
        return $capabilities;
    }
}

if (!function_exists('omoDashboardViewPreferencesGetAccess')) {
    function omoDashboardViewPreferencesGetAccess($userId, Organization $organization, ?Holon $holon): array
    {
        $userId = (int)$userId;
        $organizationId = (int)$organization->getId();
        $holonId = $holon instanceof Holon ? (int)$holon->getId() : 0;
        $interfaceLevel = $organization->getInterfaceLevel();
        $membership = $userId > 0 ? $organization->getMembership($userId, true) : null;
        $isMember = $membership !== null;
        $isSiteAdmin = $userId > 0
            && function_exists('commonUserHasSiteAdminOverride')
            && commonUserHasSiteAdminOverride($userId);
        $isOrganizationAdmin = $isMember
            && $membership->isOrganizationAdmin()
            && function_exists('commonCurrentUserIsAdminModeEnabled')
            && commonCurrentUserIsAdminModeEnabled($organizationId);
        $isHolonAdmin = $userId > 0
            && $holonId > 0
            && UserHolon::canUserManageDashboardHolonDefault($userId, $organizationId, $holonId);
        $templateKey = $holon instanceof Holon ? $holon->getDashboardDirectTemplateLayoutKey() : '';
        $baseTypeKey = $holon instanceof Holon ? $holon->getDashboardBaseTypeLayoutKey() : '';

        return array_merge(array(
            'interfaceLevel' => $interfaceLevel,
            'isMember' => $isMember,
            'isOrganizationAdmin' => $isOrganizationAdmin,
            'isHolonAdmin' => $isHolonAdmin,
            'isSiteAdmin' => $isSiteAdmin,
        ), omoDashboardViewPreferencesResolveCapabilities(
            $interfaceLevel,
            $holonId,
            $templateKey,
            $baseTypeKey,
            array(
                'isMember' => $isMember,
                'isOrganizationAdmin' => $isOrganizationAdmin,
                'isHolonAdmin' => $isHolonAdmin,
                'isSiteAdmin' => $isSiteAdmin,
            )
        ));
    }
}

if (!function_exists('omoDashboardViewPreferencesResolveLayout')) {
    function omoDashboardViewPreferencesResolveLayout(array $layouts): array
    {
        foreach (array('temporary', 'personal', 'holon', 'organizationTemplate', 'applicationType', 'global') as $scope) {
            if (isset($layouts[$scope]) && is_array($layouts[$scope])) {
                return $layouts[$scope];
            }
        }

        return UserHolon::getDefaultDashboardLayout();
    }
}

if (!function_exists('omoDashboardViewPreferencesGetOrderedSaveScopes')) {
    function omoDashboardViewPreferencesGetOrderedSaveScopes(array $access): array
    {
        $scopes = array();
        foreach (array(
            'temporary' => 'canSaveTemporary',
            'personal' => 'canSavePersonal',
            'holon' => 'canSaveHolon',
            'organization_template' => 'canSaveOrganizationTemplate',
            'application_type' => 'canSaveApplicationType',
            'global' => 'canSaveGlobal',
        ) as $scope => $permission) {
            if (!empty($access[$permission])) {
                $scopes[] = $scope;
            }
        }

        return $scopes;
    }
}

if (!function_exists('omoDashboardViewPreferencesGetTemporaryLayout')) {
    function omoDashboardViewPreferencesGetTemporaryLayout($userId, $organizationId, $holonId): ?array
    {
        $key = (int)$userId . ':' . (int)$organizationId . ':' . max(0, (int)$holonId);
        $layouts = $_SESSION['omo_dashboard_temporary_layouts'] ?? array();
        if (!is_array($layouts) || !array_key_exists($key, $layouts)) {
            return null;
        }

        return UserHolon::normalizeDashboardLayout($layouts[$key]);
    }
}

if (!function_exists('omoDashboardViewPreferencesSaveTemporaryLayout')) {
    function omoDashboardViewPreferencesSaveTemporaryLayout($userId, $organizationId, $holonId, array $layout): array
    {
        $key = (int)$userId . ':' . (int)$organizationId . ':' . max(0, (int)$holonId);
        if (!isset($_SESSION['omo_dashboard_temporary_layouts']) || !is_array($_SESSION['omo_dashboard_temporary_layouts'])) {
            $_SESSION['omo_dashboard_temporary_layouts'] = array();
        }
        $_SESSION['omo_dashboard_temporary_layouts'][$key] = UserHolon::normalizeDashboardLayout($layout);
        return array('status' => true);
    }
}

if (!function_exists('omoDashboardViewPreferencesClearTemporaryLayout')) {
    function omoDashboardViewPreferencesClearTemporaryLayout($userId, $organizationId, $holonId): array
    {
        $key = (int)$userId . ':' . (int)$organizationId . ':' . max(0, (int)$holonId);
        if (isset($_SESSION['omo_dashboard_temporary_layouts']) && is_array($_SESSION['omo_dashboard_temporary_layouts'])) {
            unset($_SESSION['omo_dashboard_temporary_layouts'][$key]);
            if ($_SESSION['omo_dashboard_temporary_layouts'] === array()) {
                unset($_SESSION['omo_dashboard_temporary_layouts']);
            }
        }
        return array('status' => true);
    }
}
