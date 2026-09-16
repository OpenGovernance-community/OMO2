<?php

function omoHolonTemplateDiscoveryModeAccess(\dbObject\Organization $organization, $holonId = 0)
{
    if (!$organization->isDiscoveryMode()) {
        return ['status' => true];
    }

    $rootHolon = $organization->getStructuralRootHolon();
    if (
        $rootHolon !== null
        && (int)$holonId > 0
        && (int)$rootHolon->getId() === (int)$holonId
    ) {
        return ['status' => true];
    }

    return [
        'status' => false,
        'message' => omoHolonTemplateT('parameters.holon_templates.error.discovery_mode'),
    ];
}

function omoHolonTemplateAdminModeAccess($organizationId)
{
    $organizationId = (int)$organizationId;
    if (commonCurrentUserIsSiteAdminModeEnabled()) {
        return ['status' => true];
    }

    if (!commonCurrentUserCanUseAdminMode($organizationId)) {
        return [
            'status' => false,
            'message' => omoHolonTemplateT('parameters.holon_templates.error.admin_required'),
        ];
    }

    if (!commonCurrentUserIsAdminModeEnabled($organizationId)) {
        return [
            'status' => false,
            'message' => omoHolonTemplateT('parameters.holon_templates.error.admin_mode_required'),
        ];
    }

    return ['status' => true];
}
