<?php

use dbObject\ApplicationSetting;
use dbObject\DocumentApplicationTab;
use dbObject\Holon;
use dbObject\Organization;
use dbObject\UserHolon;

if (!function_exists('omoApplicationViewPreferencesSourceLang')) {
    function omoApplicationViewPreferencesSourceLang(): array
    {
        return array(
            'app_view.save_options' => array('text' => 'Autres options d’enregistrement', 'context' => 'Accessible label for the application view save options menu.'),
			'app_view.save_holon' => array('text' => 'Enregistrer par défaut pour cet espace', 'context' => 'Save the current application view as the default for one space.'),
			'app_view.save_organization_template' => array('text' => 'Enregistrer pour tous les espaces du modèle {templateName}', 'context' => 'Save the current application view for spaces directly inheriting one organization template.'),
            'app_view.save_organization' => array('text' => 'Enregistrer par défaut pour cette organisation', 'context' => 'Save the current application view as the organization-wide default.'),
			'app_view.save_application_type' => array('text' => 'Enregistrer pour tous les espaces de type {typeName}', 'context' => 'Save the current application view for one base space type in all organizations.'),
            'app_view.save_global' => array('text' => 'Enregistrer par défaut global', 'context' => 'Save the current application view as the global default.'),
            'app_view.clear_temporary' => array('text' => 'Restaurer la vue par défaut', 'context' => 'Remove the temporary discovery application view.'),
            'app_view.clear_personal' => array('text' => 'Effacer ma préférence personnelle', 'context' => 'Remove the personal application view and restore the configured default.'),
			'app_view.clear_holon' => array('text' => 'Effacer le défaut de cet espace', 'context' => 'Remove the space application view default.'),
            'app_view.clear_organization_template' => array('text' => 'Effacer le défaut du modèle {templateName}', 'context' => 'Remove the organization template application view default.'),
            'app_view.clear_organization' => array('text' => 'Effacer le défaut de cette organisation', 'context' => 'Remove the organization-wide application view default.'),
			'app_view.clear_application_type' => array('text' => 'Effacer le défaut du type {typeName}', 'context' => 'Remove the base space type application view default.'),
            'app_view.clear_global' => array('text' => 'Effacer le défaut global', 'context' => 'Remove the global application view default.'),
            'app_view.save_error' => array('text' => 'Impossible d’enregistrer cette vue par défaut.', 'context' => 'Error shown when a default application view cannot be saved.'),
        );
    }
}

if (!function_exists('omoApplicationViewPreferencesT')) {
    function omoApplicationViewPreferencesT($key, array $replace = array()): string
    {
        $sourceLang = omoApplicationViewPreferencesSourceLang();
        if (function_exists('omoLoadTranslationBundle') && function_exists('t')) {
            static $bundle = null;
            if ($bundle === null) {
                $bundle = omoLoadTranslationBundle('omo_application_view_preferences', $sourceLang);
            }
            return t($key, $replace, $bundle, $sourceLang);
        }

        $entry = $sourceLang[$key]['text'] ?? $key;
        foreach ($replace as $name => $value) {
            $entry = str_replace('{' . $name . '}', (string)$value, $entry);
        }
        return $entry;
    }
}

if (!function_exists('omoApplicationViewPreferencesResolveCapabilities')) {
    function omoApplicationViewPreferencesResolveCapabilities($interfaceLevel, $holonId, $templateKey, $baseTypeKey, array $roles): array
    {
        $isDiscoveryMode = (int)$interfaceLevel === Organization::INTERFACE_LEVEL_DISCOVERY;
        $holonId = (int)$holonId;
        $templateKey = trim((string)$templateKey);
        $baseTypeKey = trim((string)$baseTypeKey);
        $isMember = !empty($roles['isMember']);
        $isHolonAdmin = !empty($roles['isHolonAdmin']);
        $isOrganizationAdmin = !empty($roles['isOrganizationAdmin']);
        $isSiteAdmin = !empty($roles['isSiteAdmin']);

        $capabilities = array(
            'canSaveTemporary' => false,
            'canSavePersonal' => false,
            'canSaveHolon' => false,
            'canSaveOrganizationTemplate' => ($isOrganizationAdmin || $isSiteAdmin) && $templateKey !== '',
            'canSaveOrganization' => $isOrganizationAdmin || $isSiteAdmin,
            'canSaveApplicationType' => $isSiteAdmin && $baseTypeKey !== '',
            'canSaveGlobal' => $isSiteAdmin,
        );

        if ($isDiscoveryMode) {
            $capabilities['canSaveTemporary'] = $isMember || $isHolonAdmin || $isOrganizationAdmin || $isSiteAdmin;
        } else {
            $capabilities['canSavePersonal'] = $holonId > 0 && $isMember;
            $capabilities['canSaveHolon'] = $holonId > 0 && ($isHolonAdmin || $isOrganizationAdmin || $isSiteAdmin);
        }

        $capabilities['canEdit'] = in_array(true, $capabilities, true);
        return $capabilities;
    }
}

if (!function_exists('omoApplicationViewPreferencesGetAccess')) {
    function omoApplicationViewPreferencesGetAccess($userId, Organization $organization, ?Holon $holon): array
    {
        $userId = (int)$userId;
        $organizationId = (int)$organization->getId();
        $holonId = $holon instanceof Holon ? (int)$holon->getId() : 0;
        $membership = $userId > 0 ? $organization->getMembership($userId, true) : null;
        $isMember = $membership !== null;
        $isOrganizationAdmin = $isMember
            && $membership->isOrganizationAdmin()
            && function_exists('commonCurrentUserIsAdminModeEnabled')
            && commonCurrentUserIsAdminModeEnabled($organizationId);
        $isSiteAdmin = $userId > 0
            && function_exists('commonUserHasSiteAdminOverride')
            && commonUserHasSiteAdminOverride($userId);
        $isHolonAdmin = $userId > 0
            && $holonId > 0
            && UserHolon::canUserManageDashboardHolonDefault($userId, $organizationId, $holonId);
        $templateKey = $holon instanceof Holon ? $holon->getDashboardDirectTemplateLayoutKey() : '';
        $baseTypeKey = $holon instanceof Holon ? $holon->getDashboardBaseTypeLayoutKey() : '';

        return array_merge(array(
            'interfaceLevel' => $organization->getInterfaceLevel(),
            'isMember' => $isMember,
            'isHolonAdmin' => $isHolonAdmin,
            'isOrganizationAdmin' => $isOrganizationAdmin,
            'isSiteAdmin' => $isSiteAdmin,
        ), omoApplicationViewPreferencesResolveCapabilities(
            $organization->getInterfaceLevel(),
            $holonId,
            $templateKey,
            $baseTypeKey,
            array(
                'isMember' => $isMember,
                'isHolonAdmin' => $isHolonAdmin,
                'isOrganizationAdmin' => $isOrganizationAdmin,
                'isSiteAdmin' => $isSiteAdmin,
            )
        ));
    }
}

if (!function_exists('omoApplicationViewPreferencesGetOrderedSaveScopes')) {
    function omoApplicationViewPreferencesGetOrderedSaveScopes(array $access): array
    {
        $scopes = array();
        foreach (array(
            'temporary' => 'canSaveTemporary',
            'personal' => 'canSavePersonal',
            'holon' => 'canSaveHolon',
            'organization_template' => 'canSaveOrganizationTemplate',
            'organization' => 'canSaveOrganization',
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

if (!function_exists('omoApplicationViewPreferencesTemporaryKey')) {
    function omoApplicationViewPreferencesTemporaryKey($userId, $organizationId, $holonId, $applicationKey): string
    {
        return (int)$userId . ':' . (int)$organizationId . ':' . max(0, (int)$holonId) . ':'
            . UserHolon::normalizeApplicationViewKey($applicationKey);
    }
}

if (!function_exists('omoApplicationViewPreferencesGetTemporaryView')) {
    function omoApplicationViewPreferencesGetTemporaryView($userId, $organizationId, $holonId, $applicationKey): ?array
    {
        $key = omoApplicationViewPreferencesTemporaryKey($userId, $organizationId, $holonId, $applicationKey);
        $views = $_SESSION['omo_application_temporary_views'] ?? array();
        if (!is_array($views) || !array_key_exists($key, $views)) {
            return null;
        }
        return UserHolon::normalizeApplicationView($views[$key]);
    }
}

if (!function_exists('omoApplicationViewPreferencesSaveTemporaryView')) {
    function omoApplicationViewPreferencesSaveTemporaryView($userId, $organizationId, $holonId, $applicationKey, array $view): array
    {
        $key = omoApplicationViewPreferencesTemporaryKey($userId, $organizationId, $holonId, $applicationKey);
        if (UserHolon::normalizeApplicationViewKey($applicationKey) === '') {
            return array('status' => false);
        }
        if (!isset($_SESSION['omo_application_temporary_views']) || !is_array($_SESSION['omo_application_temporary_views'])) {
            $_SESSION['omo_application_temporary_views'] = array();
        }
        $_SESSION['omo_application_temporary_views'][$key] = UserHolon::normalizeApplicationView($view);
        return array('status' => true);
    }
}

if (!function_exists('omoApplicationViewPreferencesClearTemporaryView')) {
    function omoApplicationViewPreferencesClearTemporaryView($userId, $organizationId, $holonId, $applicationKey): array
    {
        $key = omoApplicationViewPreferencesTemporaryKey($userId, $organizationId, $holonId, $applicationKey);
        if (isset($_SESSION['omo_application_temporary_views']) && is_array($_SESSION['omo_application_temporary_views'])) {
            unset($_SESSION['omo_application_temporary_views'][$key]);
            if ($_SESSION['omo_application_temporary_views'] === array()) {
                unset($_SESSION['omo_application_temporary_views']);
            }
        }
        return array('status' => true);
    }
}

if (!function_exists('omoApplicationViewPreferencesResolveView')) {
    function omoApplicationViewPreferencesResolveView(array $views): ?array
    {
        foreach (array('temporary', 'personal', 'holon', 'organizationTemplate', 'organization', 'applicationType', 'global') as $scope) {
            if (array_key_exists($scope, $views) && is_array($views[$scope])) {
                return $views[$scope];
            }
        }
        return null;
    }
}

if (!function_exists('omoApplicationViewPreferencesGetDefaultViews')) {
    function omoApplicationViewPreferencesGetDefaultViews($applicationKey, Organization $organization, ?Holon $holon): array
    {
        $applicationKey = UserHolon::normalizeApplicationViewKey($applicationKey);
        $typeId = $holon instanceof Holon ? (int)$holon->get('IDtypeholon') : 0;
        $applicationDefaults = $applicationKey !== ''
            ? ApplicationSetting::getApplicationViewDefaultsForType($applicationKey, $typeId)
            : array('baseType' => null, 'global' => null);

        return array(
            'holon' => $applicationKey !== '' && $holon instanceof Holon ? $holon->getApplicationViewDefault($applicationKey) : null,
            'organizationTemplate' => $applicationKey !== '' && $holon instanceof Holon
                ? $organization->getApplicationViewTemplateDefaultForHolon($holon, $applicationKey)
                : null,
            'organization' => $applicationKey !== '' ? $organization->getApplicationViewDefault($applicationKey) : null,
            'applicationType' => $applicationDefaults['baseType'] ?? null,
            'global' => $applicationDefaults['global'] ?? null,
        );
    }
}

if (!function_exists('omoApplicationViewPreferencesResolveDefault')) {
    function omoApplicationViewPreferencesResolveDefault($applicationKey, Organization $organization, ?Holon $holon): ?array
    {
        return omoApplicationViewPreferencesResolveView(
            omoApplicationViewPreferencesGetDefaultViews($applicationKey, $organization, $holon)
        );
    }
}

if (!function_exists('omoApplicationViewPreferencesGetEffectiveView')) {
    function omoApplicationViewPreferencesGetEffectiveView(array $context): ?array
    {
        return omoApplicationViewPreferencesResolveView(array(
            'temporary' => $context['temporaryView'] ?? null,
            'personal' => $context['personalView'] ?? null,
            'holon' => $context['defaultView'] ?? null,
        ));
    }
}

if (!function_exists('omoApplicationViewPreferencesGetInitialValue')) {
    function omoApplicationViewPreferencesGetInitialValue(array $context, $requestKey, $viewKey, $fallback = '')
    {
        $requestKey = trim((string)$requestKey);
        $viewKey = trim((string)$viewKey);
        if ($requestKey !== '' && array_key_exists($requestKey, $_GET)) {
            return $_GET[$requestKey];
        }
        $effectiveView = omoApplicationViewPreferencesGetEffectiveView($context);
        if ($viewKey !== '' && is_array($effectiveView) && array_key_exists($viewKey, $effectiveView)) {
            return $effectiveView[$viewKey];
        }
        return $fallback;
    }
}

if (!function_exists('omoApplicationViewPreferencesSaveLabel')) {
    function omoApplicationViewPreferencesSaveLabel($scope, array $context): string
    {
        $labels = array(
            'holon' => 'app_view.save_holon',
            'organization_template' => 'app_view.save_organization_template',
            'organization' => 'app_view.save_organization',
            'application_type' => 'app_view.save_application_type',
            'global' => 'app_view.save_global',
        );
        return isset($labels[$scope])
            ? omoApplicationViewPreferencesT($labels[$scope], array(
                'templateName' => $context['templateLabel'] ?? '',
                'typeName' => $context['typeLabel'] ?? '',
            ))
            : '';
    }
}

if (!function_exists('omoApplicationViewPreferencesGetContext')) {
    function omoApplicationViewPreferencesGetContext($applicationKey, Organization $organization, ?Holon $holon, $currentUserId = 0): array
    {
        $applicationKey = UserHolon::normalizeApplicationViewKey($applicationKey);
        $currentUserId = (int)$currentUserId;
        if ($currentUserId <= 0 && function_exists('commonGetCurrentUserId')) {
            $currentUserId = (int)commonGetCurrentUserId();
        }

        $organizationId = (int)$organization->getId();
        $holonId = $holon instanceof Holon ? (int)$holon->getId() : 0;
        $typeId = $holon instanceof Holon ? (int)$holon->get('IDtypeholon') : 0;
        $templateKey = $holon instanceof Holon ? $holon->getDashboardDirectTemplateLayoutKey() : '';
        $access = omoApplicationViewPreferencesGetAccess($currentUserId, $organization, $holon);
        $defaultViews = omoApplicationViewPreferencesGetDefaultViews($applicationKey, $organization, $holon);
        $personalView = !empty($access['canSavePersonal']) && $applicationKey !== '' && $holonId > 0
            ? UserHolon::getApplicationViewForUser($currentUserId, $holonId, $applicationKey)
            : null;
        $temporaryView = !empty($access['canSaveTemporary']) && $applicationKey !== ''
            ? omoApplicationViewPreferencesGetTemporaryView($currentUserId, $organizationId, $holonId, $applicationKey)
            : null;
        $personalResetAttributes = array(
            'activities' => array('data-activity-filter-restore' => ''),
            'calendar' => array('data-omo-calendar-filter-more-action' => 'restore-default'),
            'checklist' => array('data-checklist-filter-restore' => ''),
            'decision' => array('data-omo-decisions-filter-more-action' => 'restore-default'),
            'documents' => array('data-omo-documents-filter-more-action' => 'restore-default'),
            'policy' => array('data-policy-filter-more-action' => 'restore-default'),
            'projects' => array('data-omo-projects-filter-more-action' => 'restore-default'),
            'stats' => array('data-omo-stats-filter-more-action' => 'restore-default'),
            'team' => array('data-team-filter-more-action' => 'restore-default'),
        );

        if (empty($_SESSION['omo_application_view_preferences_csrf'])) {
            $_SESSION['omo_application_view_preferences_csrf'] = bin2hex(random_bytes(32));
        }

        $saveScopes = omoApplicationViewPreferencesGetOrderedSaveScopes($access);
        $context = array_merge($access, array(
            'application' => $applicationKey,
            'organizationId' => $organizationId,
            'holonId' => $holonId,
            'typeId' => $typeId,
            'typeLabel' => $holon instanceof Holon ? $holon->getTypeLabel() : '',
            'templateKey' => $templateKey,
            'templateLabel' => $holon instanceof Holon ? $holon->getDashboardTemplateLayoutLabel() : '',
            'temporaryView' => $temporaryView,
            'personalView' => $personalView,
            'holonView' => $defaultViews['holon'],
            'organizationTemplateView' => $defaultViews['organizationTemplate'],
            'organizationView' => $defaultViews['organization'],
            'applicationTypeView' => $defaultViews['applicationType'],
            'globalView' => $defaultViews['global'],
            'defaultView' => omoApplicationViewPreferencesResolveView($defaultViews),
            'csrfToken' => (string)$_SESSION['omo_application_view_preferences_csrf'],
            'endpoint' => '/omo/api/application_view_preferences.php',
            'personalResetAttributes' => $personalResetAttributes[$applicationKey] ?? array(),
            'primarySaveScope' => $saveScopes[0] ?? '',
        ));
        $context['primarySaveLabel'] = omoApplicationViewPreferencesSaveLabel($context['primarySaveScope'], $context);

        $pvApplicationTabId = isset($_GET['pv_application_tab_id']) ? max(0, (int)$_GET['pv_application_tab_id']) : 0;
        if ($pvApplicationTabId <= 0 || $applicationKey === '') {
            return $context;
        }

        $pvApplicationTab = new DocumentApplicationTab();
        $pvDocument = new \dbObject\Document();
        if (
            !$pvApplicationTab->load($pvApplicationTabId)
            || !$pvApplicationTab->matchesApplicationKey($applicationKey)
            || !$pvDocument->load((int)$pvApplicationTab->get('IDdocument'))
            || (int)$pvDocument->get('IDorganization') !== $organizationId
            || !$pvDocument->canUserOpenPvEditor($currentUserId, $organizationId)
        ) {
            return $context;
        }

        $pvApplicationView = $pvApplicationTab->getViewParametersArray();
        foreach (array('canSaveTemporary', 'canSavePersonal', 'canSaveHolon', 'canSaveOrganizationTemplate', 'canSaveOrganization', 'canSaveApplicationType', 'canSaveGlobal') as $capability) {
            $context[$capability] = false;
        }
        $context['pvApplicationTabId'] = $pvApplicationTabId;
        $context['pvApplicationViewRevision'] = substr(hash('sha256', (string)json_encode($pvApplicationView)), 0, 16);
        $context['isPvApplicationTab'] = true;
        $context['temporaryView'] = null;
        $context['personalView'] = $pvApplicationView;
        $context['defaultView'] = array();
        $context['canSavePersonal'] = $pvDocument->canUserManagePvDocument($currentUserId);
        $context['primarySaveScope'] = $context['canSavePersonal'] ? 'personal' : '';
        $context['primarySaveLabel'] = '';
        return $context;
    }
}

if (!function_exists('omoApplicationViewPreferencesRenderMenu')) {
    function omoApplicationViewPreferencesRenderMenu(array $context): string
    {
        $saveOptions = array();
        $restoreOptions = array();
        $primarySaveScope = trim((string)($context['primarySaveScope'] ?? ''));
        foreach (omoApplicationViewPreferencesGetOrderedSaveScopes($context) as $scope) {
            if ($scope === $primarySaveScope || in_array($scope, array('temporary', 'personal'), true)) {
                continue;
            }
            $saveOptions[] = array($scope, 'save', omoApplicationViewPreferencesSaveLabel($scope, $context));
        }

        $resetDefinitions = array(
            'temporary' => array('canSaveTemporary', 'temporaryView', 'app_view.clear_temporary'),
            'personal' => array('canSavePersonal', 'personalView', 'app_view.clear_personal'),
            'holon' => array('canSaveHolon', 'holonView', 'app_view.clear_holon'),
            'organization_template' => array('canSaveOrganizationTemplate', 'organizationTemplateView', 'app_view.clear_organization_template'),
            'organization' => array('canSaveOrganization', 'organizationView', 'app_view.clear_organization'),
            'application_type' => array('canSaveApplicationType', 'applicationTypeView', 'app_view.clear_application_type'),
            'global' => array('canSaveGlobal', 'globalView', 'app_view.clear_global'),
        );
        foreach ($resetDefinitions as $scope => $definition) {
            if (empty($context[$definition[0]]) || !is_array($context[$definition[1]] ?? null)) {
                continue;
            }
            $attributes = in_array($scope, array('temporary', 'personal'), true)
                ? ($context['personalResetAttributes'] ?? array())
                : array();
            $restoreOptions[] = array($scope, 'clear', omoApplicationViewPreferencesT($definition[2], array(
                'templateName' => $context['templateLabel'] ?? '',
                'typeName' => $context['typeLabel'] ?? '',
            )), $attributes);
        }
        if ($saveOptions === array() && $restoreOptions === array()) {
            return '';
        }

        $escape = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        $html = '<div class="generic-menu omo-app-view-save-menu" data-omo-app-view-save-menu>';
        $html .= '<button type="button" class="generic-menu-toggle" data-omo-app-view-save-menu-toggle aria-expanded="false" aria-label="' . $escape(omoApplicationViewPreferencesT('app_view.save_options')) . '">&#9662;</button>';
        $html .= '<div class="generic-menu-panel omo-app-view-save-menu__panel" data-omo-app-view-save-menu-panel role="menu" hidden>';
        foreach ($saveOptions as $option) {
            $html .= '<button type="button" class="generic-menu-item" data-omo-app-view-save-scope="' . $escape($option[0]) . '" data-omo-app-view-operation="' . $escape($option[1]) . '" role="menuitem">' . $escape($option[2]) . '</button>';
        }
        if ($saveOptions !== array() && $restoreOptions !== array()) {
            $html .= '<div class="omo-app-view-save-menu__separator" role="separator"></div>';
        }
        foreach ($restoreOptions as $option) {
            $attributes = '';
            foreach (is_array($option[3] ?? null) ? $option[3] : array() as $name => $value) {
                $attributes .= ' ' . $escape($name) . ($value === '' ? '' : '="' . $escape($value) . '"');
            }
            $html .= '<button type="button" class="generic-menu-item" data-omo-app-view-reset-scope="' . $escape($option[0]) . '" data-omo-app-view-operation="' . $escape($option[1]) . '"' . $attributes . ' role="menuitem">' . $escape($option[2]) . '</button>';
        }
        return $html . '</div></div>';
    }
}
