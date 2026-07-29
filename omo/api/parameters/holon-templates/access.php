<?php

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
