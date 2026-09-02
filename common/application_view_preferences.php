<?php

use dbObject\ApplicationSetting;
use dbObject\Holon;
use dbObject\Organization;
use dbObject\UserHolon;

if (!function_exists('omoApplicationViewPreferencesSourceLang')) {
    function omoApplicationViewPreferencesSourceLang(): array
    {
        return array(
            'app_view.save_options' => array('text' => 'Autres options d enregistrement', 'context' => 'Accessible label for the application view save options menu.'),
            'app_view.save_organization_template' => array('text' => 'Enregistrer pour tous les holons du modèle {templateName}', 'context' => 'Save the current application view for holons directly inheriting one organization template.'),
            'app_view.save_application_type' => array('text' => 'Enregistrer pour tous les holons de type {typeName}', 'context' => 'Save the current application view for one base holon type in all organizations.'),
            'app_view.clear_organization_template' => array('text' => 'Effacer le défaut du modèle {templateName}', 'context' => 'Remove the organization template application view default.'),
            'app_view.restore_default' => array('text' => 'Restaurer la vue par defaut', 'context' => 'Remove the personal application view and restore the configured default.'),
            'app_view.save_error' => array('text' => 'Impossible d enregistrer cette vue par defaut.', 'context' => 'Error shown when a default application view cannot be saved.'),
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

if (!function_exists('omoApplicationViewPreferencesResolveDefault')) {
    function omoApplicationViewPreferencesResolveDefault($applicationKey, Organization $organization, ?Holon $holon): ?array
    {
        $applicationKey = UserHolon::normalizeApplicationViewKey($applicationKey);
        if ($applicationKey === '') {
            return null;
        }

        $organizationTemplateView = $holon instanceof Holon
            ? $organization->getApplicationViewTemplateDefaultForHolon($holon, $applicationKey)
            : null;
        if ($organizationTemplateView !== null) {
            return $organizationTemplateView;
        }
        $typeId = $holon instanceof Holon ? (int)$holon->get('IDtypeholon') : 0;
        $applicationDefaults = ApplicationSetting::getApplicationViewDefaultsForType($applicationKey, $typeId);
        $applicationTypeView = $applicationDefaults['baseType'] ?? null;
        if ($applicationTypeView !== null) {
            return $applicationTypeView;
        }

        return $applicationDefaults['global'] ?? null;
    }
}

if (!function_exists('omoApplicationViewPreferencesGetEffectiveView')) {
    function omoApplicationViewPreferencesGetEffectiveView(array $context): ?array
    {
        $personalView = $context['personalView'] ?? null;
        if (is_array($personalView)) {
            return $personalView;
        }

        $defaultView = $context['defaultView'] ?? null;
        return is_array($defaultView) ? $defaultView : null;
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

if (!function_exists('omoApplicationViewPreferencesGetContext')) {
    function omoApplicationViewPreferencesGetContext($applicationKey, Organization $organization, ?Holon $holon, $currentUserId = 0): array
    {
        $applicationKey = UserHolon::normalizeApplicationViewKey($applicationKey);
        $currentUserId = (int)$currentUserId;
        if ($currentUserId <= 0 && function_exists('commonGetCurrentUserId')) {
            $currentUserId = (int)commonGetCurrentUserId();
        }

        $organizationId = (int)$organization->getId();
        $typeId = $holon instanceof Holon ? (int)$holon->get('IDtypeholon') : 0;
        $typeLabel = $holon instanceof Holon ? $holon->getTypeLabel() : '';
        $templateKey = $holon instanceof Holon ? $holon->getDashboardDirectTemplateLayoutKey() : '';
        $templateLabel = $holon instanceof Holon ? $holon->getDashboardTemplateLayoutLabel() : '';
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
        $membership = $currentUserId > 0 ? $organization->getMembership($currentUserId, true) : null;
        $isOrganizationAdmin = $membership !== null
            && $membership->isOrganizationAdmin()
            && function_exists('commonCurrentUserIsAdminModeEnabled')
            && commonCurrentUserIsAdminModeEnabled($organizationId);
        $isSiteAdmin = $currentUserId > 0
            && function_exists('commonUserHasSiteAdminOverride')
            && commonUserHasSiteAdminOverride($currentUserId);
        $isDiscoveryMode = $organization->getInterfaceLevel() === Organization::INTERFACE_LEVEL_DISCOVERY;

        if (empty($_SESSION['omo_application_view_preferences_csrf'])) {
            $_SESSION['omo_application_view_preferences_csrf'] = bin2hex(random_bytes(32));
        }

        return array(
            'application' => $applicationKey,
            'organizationId' => $organizationId,
            'holonId' => $holon instanceof Holon ? (int)$holon->getId() : 0,
            'typeId' => $typeId,
            'typeLabel' => $typeLabel,
            'templateKey' => $templateKey,
            'templateLabel' => $templateLabel,
            'personalView' => $applicationKey !== '' && $holon instanceof Holon
                ? UserHolon::getApplicationViewForUser($currentUserId, (int)$holon->getId(), $applicationKey)
                : null,
            'defaultView' => $applicationKey !== '' ? omoApplicationViewPreferencesResolveDefault($applicationKey, $organization, $holon) : null,
            'csrfToken' => (string)$_SESSION['omo_application_view_preferences_csrf'],
            'endpoint' => '/omo/api/application_view_preferences.php',
            'canSavePersonal' => !$isDiscoveryMode && ($membership !== null || $isSiteAdmin),
            'personalResetAttributes' => $personalResetAttributes[$applicationKey] ?? array(),
            'primarySaveScope' => $isDiscoveryMode && ($isOrganizationAdmin || $isSiteAdmin) && $templateKey !== '' ? 'organization_template' : '',
            'canSaveOrganizationTemplate' => ($isOrganizationAdmin || $isSiteAdmin) && $templateKey !== '',
            'canSaveApplicationType' => $isSiteAdmin && $typeId > 0,
        );
    }
}

if (!function_exists('omoApplicationViewPreferencesRenderMenu')) {
    function omoApplicationViewPreferencesRenderMenu(array $context): string
    {
        $saveOptions = array();
        $restoreOptions = array();
        $primarySaveScope = trim((string)($context['primarySaveScope'] ?? ''));
        if (!empty($context['canSaveOrganizationTemplate'])) {
            if ($primarySaveScope !== 'organization_template') {
                $saveOptions[] = array('organization_template', 'save', omoApplicationViewPreferencesT('app_view.save_organization_template', array('templateName' => $context['templateLabel'] ?? '')));
            }
            $restoreOptions[] = array('organization_template', 'clear', omoApplicationViewPreferencesT('app_view.clear_organization_template', array('templateName' => $context['templateLabel'] ?? '')));
        }
        if (!empty($context['canSaveApplicationType'])) {
            $saveOptions[] = array('application_type', 'save', omoApplicationViewPreferencesT('app_view.save_application_type', array('typeName' => $context['typeLabel'] ?? '')));
        }
        if (!empty($context['canSavePersonal'])) {
            $restoreOptions[] = array('personal', 'clear', omoApplicationViewPreferencesT('app_view.restore_default'), $context['personalResetAttributes'] ?? array());
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
