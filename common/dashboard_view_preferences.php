<?php

use dbObject\Holon;
use dbObject\Organization;
use dbObject\UserHolon;

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

        $canSaveHolon = false;
        $canSaveOrganizationTemplate = false;
        $canSaveApplicationType = false;
        $canSavePersonal = false;
        if ($interfaceLevel === Organization::INTERFACE_LEVEL_DISCOVERY) {
            $canSaveOrganizationTemplate = ($isOrganizationAdmin || $isSiteAdmin) && $templateKey !== '';
            $canSaveApplicationType = $isSiteAdmin && $baseTypeKey !== '';
        } elseif ($interfaceLevel === Organization::INTERFACE_LEVEL_AUTONOMOUS) {
            $canSaveHolon = $isHolonAdmin || $isOrganizationAdmin || $isSiteAdmin;
            $canSaveOrganizationTemplate = ($isOrganizationAdmin || $isSiteAdmin) && $templateKey !== '';
            $canSaveApplicationType = $isSiteAdmin && $baseTypeKey !== '';
        } else {
            $canSavePersonal = $isMember || $isSiteAdmin;
            $canSaveHolon = $isHolonAdmin || $isOrganizationAdmin || $isSiteAdmin;
            $canSaveOrganizationTemplate = ($isOrganizationAdmin || $isSiteAdmin) && $templateKey !== '';
            $canSaveApplicationType = $isSiteAdmin && $baseTypeKey !== '';
        }

        return array(
            'interfaceLevel' => $interfaceLevel,
            'isMember' => $isMember,
            'isOrganizationAdmin' => $isOrganizationAdmin,
            'isHolonAdmin' => $isHolonAdmin,
            'isSiteAdmin' => $isSiteAdmin,
            'canSavePersonal' => $canSavePersonal,
            'canSaveHolon' => $canSaveHolon,
            'canSaveOrganizationTemplate' => $canSaveOrganizationTemplate,
            'canSaveApplicationType' => $canSaveApplicationType,
            'canEdit' => $canSavePersonal || $canSaveHolon || $canSaveOrganizationTemplate || $canSaveApplicationType,
        );
    }
}
