<?php

require_once dirname(__DIR__) . '/shared.php';

use dbObject\Organization;
use dbObject\OrganizationApplication;
use dbObject\ProjectImportanceCalculator;

if (!function_exists('omoProjectsParamsSourceLang')) {
    function omoProjectsParamsSourceLang()
    {
        return [
            'projects.params.title' => ['text' => 'Paramètres Projets', 'context' => 'Projects application settings title.'],
            'projects.params.application' => ['text' => 'Projets', 'context' => 'Projects application label.'],
            'projects.params.description' => ['text' => "Réglez ici l'affichage et le calcul d'importance stratégique partagés par les projets de cette organisation.", 'context' => 'Projects application settings description.'],
            'projects.params.display_title' => ['text' => "Affichage de l'application", 'context' => 'Projects application display settings section title.'],
            'projects.params.columns' => ['text' => 'Colonnes du Kanban', 'context' => 'Projects application settings label for enabled Kanban columns.'],
            'projects.params.classification' => ['text' => 'Classifications', 'context' => 'Projects application settings label for enabled project classifications.'],
            'projects.params.use_priority' => ['text' => 'Utiliser la priorité', 'context' => 'Projects application setting toggling priority.'],
            'projects.params.use_importance' => ['text' => "Utiliser l'importance stratégique", 'context' => 'Projects application setting toggling strategic importance.'],
            'projects.params.use_size' => ['text' => 'Utiliser la taille des projets', 'context' => 'Projects application setting toggling project size.'],
            'projects.params.parent_weight' => ['text' => 'Poids du parent', 'context' => 'Projects importance formula parent weight.'],
            'projects.params.parent_weight_hint' => ['text' => "Part de l'importance stratégique calculée du projet parent dans la moyenne géométrique.", 'context' => 'Projects importance formula parent weight help.'],
            'projects.params.local_weight' => ['text' => "Poids de l'importance stratégique locale", 'context' => 'Projects importance formula local weight.'],
            'projects.params.depth_penalty' => ['text' => 'Pénalité de profondeur du projet racine', 'context' => 'Projects root project holarchy depth penalty.'],
            'projects.params.depth_penalty_hint' => ['text' => "Applique exp(-pénalité × profondeur) uniquement au projet racine d'une chaîne non ancrée dans le holon racine. Zéro désactive cette pénalité.", 'context' => 'Projects root project holarchy depth penalty help.'],
            'projects.params.formula' => ['text' => "Une importance stratégique absente hérite de la dernière valeur renseignée dans sa chaîne de projets. Sans aucune valeur renseignée, le score est 0. La pénalité de profondeur ne concerne que le projet racine. Les scores sont enregistrés sur une échelle normalisée de 0 à 1.", 'context' => 'Projects importance formula explanation.'],
            'projects.params.save' => ['text' => 'Enregistrer', 'context' => 'Projects application settings save action.'],
            'projects.params.saving' => ['text' => 'Enregistrement…', 'context' => 'Projects application settings saving action.'],
            'projects.params.saved' => ['text' => 'Paramètres Projets enregistrés et importances stratégiques recalculées.', 'context' => 'Projects application settings success.'],
            'projects.params.saved_display' => ['text' => 'Paramètres Projets enregistrés.', 'context' => 'Projects application display settings success.'],
            'projects.params.unchanged' => ['text' => "Aucun paramètre Projets n'a changé.", 'context' => 'Projects application settings unchanged feedback.'],
            'projects.params.error.login' => ['text' => 'Connectez-vous pour accéder à ce panneau.', 'context' => 'Projects application settings login error.'],
            'projects.params.error.organization' => ['text' => 'Aucune organisation active.', 'context' => 'Projects application settings organization error.'],
            'projects.params.error.unavailable' => ['text' => "L'application Projets n'est pas disponible pour cette organisation.", 'context' => 'Projects application settings unavailable error.'],
            'projects.params.error.forbidden' => ['text' => 'Activez le mode admin de cette organisation pour modifier les paramètres Projets.', 'context' => 'Projects application settings forbidden error.'],
            'projects.params.error.invalid' => ['text' => 'Les valeurs de calcul sont invalides.', 'context' => 'Projects application settings validation error.'],
            'projects.params.error.columns' => ['text' => 'Sélectionnez au moins une colonne du Kanban.', 'context' => 'Projects application settings validation error when no Kanban column is selected.'],
            'projects.params.error.save' => ['text' => "Impossible d'enregistrer les paramètres Projets.", 'context' => 'Projects application settings save error.'],
            'projects.params.error.method' => ['text' => 'Méthode non autorisée.', 'context' => 'Projects application settings HTTP method error.'],
        ];
    }
}

if (!function_exists('omoProjectsParamsLang')) {
    function omoProjectsParamsLang()
    {
        static $lang = null;
        if ($lang === null) {
            $lang = omoLoadTranslationBundle('omo_projects_params', omoProjectsParamsSourceLang());
        }
        return $lang;
    }
}

if (!function_exists('omoProjectsParamsT')) {
    function omoProjectsParamsT($key, array $replace = [])
    {
        return t($key, $replace, omoProjectsParamsLang(), omoProjectsParamsSourceLang());
    }
}

if (!function_exists('omoProjectsParamsCanManage')) {
    function omoProjectsParamsCanManage(int $organizationId, int $userId): bool
    {
        if ($organizationId <= 0 || $userId <= 0) {
            return false;
        }

        if (
            function_exists('commonUserHasSiteAdminOverride')
            && commonUserHasSiteAdminOverride($userId)
        ) {
            return true;
        }

        return $userId === (int)commonGetCurrentUserId()
            && function_exists('commonCurrentUserCanUseAdminMode')
            && commonCurrentUserCanUseAdminMode($organizationId);
    }
}

if (!function_exists('omoProjectsParamsGetApplicationLink')) {
    function omoProjectsParamsGetApplicationLink(int $organizationId, bool $ensure = false): ?OrganizationApplication
    {
        return $ensure
            ? OrganizationApplication::ensureByOrganizationAndDirectory($organizationId, 'projects')
            : OrganizationApplication::loadByOrganizationAndDirectory($organizationId, 'projects', false);
    }
}

if (!function_exists('omoProjectsParamsGetConfig')) {
    function omoProjectsParamsGetConfig(Organization $organization): array
    {
        return ProjectImportanceCalculator::getConfigForOrganization((int)$organization->getId());
    }
}

if (!function_exists('omoProjectsParamsGetDisplayConfig')) {
    function omoProjectsParamsGetDisplayConfig(Organization $organization): array
    {
        return omoProjectsGetDisplayConfig((int)$organization->getId());
    }
}

if (!function_exists('omoProjectsParamsStoreConfig')) {
    function omoProjectsParamsStoreConfig(Organization $organization, array $values): array
    {
        $organizationId = (int)$organization->getId();
        $link = omoProjectsParamsGetApplicationLink($organizationId, false);
        if (!($link instanceof OrganizationApplication)) {
            return ['status' => false, 'message' => omoProjectsParamsT('projects.params.error.unavailable')];
        }

        $rawParentWeight = str_replace(',', '.', trim((string)($values['parent_weight'] ?? '')));
        $rawDepthPenalty = str_replace(',', '.', trim((string)($values['depth_penalty'] ?? '')));
        if (!is_numeric($rawParentWeight) || !is_numeric($rawDepthPenalty)) {
            return ['status' => false, 'message' => omoProjectsParamsT('projects.params.error.invalid')];
        }
        $parentWeight = (float)$rawParentWeight;
        $depthPenalty = (float)$rawDepthPenalty;
        if ($parentWeight < 0 || $parentWeight > 1 || $depthPenalty < 0) {
            return ['status' => false, 'message' => omoProjectsParamsT('projects.params.error.invalid')];
        }

        $current = ProjectImportanceCalculator::getConfigForOrganization($organizationId);
        $next = ProjectImportanceCalculator::normalizeConfig([
            'parentWeight' => $parentWeight,
            'depthPenalty' => $depthPenalty,
        ]);
        $storedParameters = $link->getParametersArray();
        $storedConfig = $storedParameters[ProjectImportanceCalculator::PARAMETERS_KEY] ?? [];
        $hasLegacyRootNeutral = is_array($storedConfig) && array_key_exists('rootNeutralImportance', $storedConfig);
        $calculationVersion = (int)($storedParameters['importanceCalculationVersion'] ?? 0);
        $saveDisplay = !empty($values['save_display']);
        $calculationChanged = !$saveDisplay && (
            abs($current['parentWeight'] - $next['parentWeight']) > 0.00000001
            || abs($current['depthPenalty'] - $next['depthPenalty']) > 0.00000001
            || $hasLegacyRootNeutral
            || $calculationVersion < ProjectImportanceCalculator::CALCULATION_VERSION
        );
        $currentDisplay = omoProjectsNormalizeDisplayConfig($storedParameters['display'] ?? null);
        $nextDisplay = $currentDisplay;
        if ($saveDisplay) {
            $enabledStatuses = $values['enabled_statuses'] ?? [];
            if (!is_array($enabledStatuses) || count($enabledStatuses) === 0) {
                return ['status' => false, 'message' => omoProjectsParamsT('projects.params.error.columns')];
            }
            $nextDisplay = omoProjectsNormalizeDisplayConfig([
                'enabledStatuses' => $enabledStatuses,
                'usePriority' => isset($values['use_priority']),
                'useImportance' => isset($values['use_importance']),
                'useSize' => isset($values['use_size']),
            ]);
            if (count($nextDisplay['enabledStatuses']) === 0) {
                return ['status' => false, 'message' => omoProjectsParamsT('projects.params.error.columns')];
            }
        }
        $displayChanged = $currentDisplay !== $nextDisplay;
        if (!$calculationChanged && !$displayChanged) {
            return ['status' => true, 'message' => omoProjectsParamsT('projects.params.unchanged'), 'changed' => false];
        }

        $parameters = $storedParameters;
        if ($calculationChanged) {
            $parameters[ProjectImportanceCalculator::PARAMETERS_KEY] = $next;
            $parameters['importanceCalculationVersion'] = ProjectImportanceCalculator::CALCULATION_VERSION;
        }
        $parameters['display'] = $nextDisplay;
        $pdo = \dbObject\DbObject::getPdo();
        $startedTransaction = false;
        try {
            if ($pdo instanceof \PDO && !$pdo->inTransaction()) {
                $pdo->beginTransaction();
                $startedTransaction = true;
            }
            $link->setParametersArray($parameters);
            $saveResult = $link->save();
            if (!is_array($saveResult) || empty($saveResult['status'])) {
                throw new \RuntimeException('Unable to save projects parameters.');
            }
            if ($calculationChanged) {
                ProjectImportanceCalculator::recalculateOrganization($organizationId);
            }
            if ($startedTransaction && $pdo instanceof \PDO && $pdo->inTransaction()) {
                $pdo->commit();
            }
        } catch (\Throwable $exception) {
            if ($startedTransaction && $pdo instanceof \PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['status' => false, 'message' => omoProjectsParamsT('projects.params.error.save')];
        }

        return [
            'status' => true,
            'message' => omoProjectsParamsT($calculationChanged ? 'projects.params.saved' : 'projects.params.saved_display'),
            'changed' => true,
        ];
    }
}
