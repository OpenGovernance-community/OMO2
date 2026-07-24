<?php

use dbObject\Organization;
use dbObject\OrganizationApplication;
use dbObject\ProjectImportanceCalculator;

if (!function_exists('omoProjectsParamsSourceLang')) {
    function omoProjectsParamsSourceLang()
    {
        return [
            'projects.params.title' => ['text' => 'Parametres Projets', 'context' => 'Projects application settings title.'],
            'projects.params.application' => ['text' => 'Projets', 'context' => 'Projects application label.'],
            'projects.params.description' => ['text' => 'Reglez ici le calcul d importance strategique partage par les projets de cette organisation.', 'context' => 'Projects application settings description.'],
            'projects.params.parent_weight' => ['text' => 'Poids du parent', 'context' => 'Projects importance formula parent weight.'],
            'projects.params.parent_weight_hint' => ['text' => 'Part de l importance strategique calculee du projet parent dans la moyenne geometrique.', 'context' => 'Projects importance formula parent weight help.'],
            'projects.params.local_weight' => ['text' => 'Poids de l importance strategique locale', 'context' => 'Projects importance formula local weight.'],
            'projects.params.depth_penalty' => ['text' => 'Penalite de profondeur du projet racine', 'context' => 'Projects root project holarchy depth penalty.'],
            'projects.params.depth_penalty_hint' => ['text' => 'Applique exp(-penalite x profondeur) uniquement au projet racine d une chaine non ancree dans le holon racine. Zero desactive cette penalite.', 'context' => 'Projects root project holarchy depth penalty help.'],
            'projects.params.formula' => ['text' => 'Une importance strategique absente herite de la derniere valeur renseignee dans sa chaine de projets. Sans aucune valeur renseignee, le score est 0. La penalite de profondeur ne concerne que le projet racine. Les scores sont enregistres sur une echelle normalisee de 0 a 1.', 'context' => 'Projects importance formula explanation.'],
            'projects.params.save' => ['text' => 'Enregistrer', 'context' => 'Projects application settings save action.'],
            'projects.params.saving' => ['text' => 'Enregistrement...', 'context' => 'Projects application settings saving action.'],
            'projects.params.saved' => ['text' => 'Parametres Projets enregistres et importances strategiques recalculees.', 'context' => 'Projects application settings success.'],
            'projects.params.unchanged' => ['text' => 'Aucun parametre de calcul n a change.', 'context' => 'Projects application settings unchanged feedback.'],
            'projects.params.error.login' => ['text' => 'Connectez-vous pour acceder a ce panneau.', 'context' => 'Projects application settings login error.'],
            'projects.params.error.organization' => ['text' => 'Aucune organisation active.', 'context' => 'Projects application settings organization error.'],
            'projects.params.error.unavailable' => ['text' => 'L application Projets n est pas disponible pour cette organisation.', 'context' => 'Projects application settings unavailable error.'],
            'projects.params.error.forbidden' => ['text' => 'Activez le mode admin de cette organisation pour modifier les parametres Projets.', 'context' => 'Projects application settings forbidden error.'],
            'projects.params.error.invalid' => ['text' => 'Les valeurs de calcul sont invalides.', 'context' => 'Projects application settings validation error.'],
            'projects.params.error.save' => ['text' => 'Impossible d enregistrer les parametres Projets.', 'context' => 'Projects application settings save error.'],
            'projects.params.error.method' => ['text' => 'Methode non autorisee.', 'context' => 'Projects application settings HTTP method error.'],
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
        return $organizationId > 0
            && $userId > 0
            && function_exists('commonUserHasAdminOverride')
            && commonUserHasAdminOverride($userId, $organizationId);
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
        $changed = abs($current['parentWeight'] - $next['parentWeight']) > 0.00000001
            || abs($current['depthPenalty'] - $next['depthPenalty']) > 0.00000001
            || $hasLegacyRootNeutral
            || $calculationVersion < ProjectImportanceCalculator::CALCULATION_VERSION;
        if (!$changed) {
            return ['status' => true, 'message' => omoProjectsParamsT('projects.params.unchanged'), 'changed' => false];
        }

        $parameters = $storedParameters;
        $parameters[ProjectImportanceCalculator::PARAMETERS_KEY] = $next;
        $parameters['importanceCalculationVersion'] = ProjectImportanceCalculator::CALCULATION_VERSION;
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
            ProjectImportanceCalculator::recalculateOrganization($organizationId);
            if ($startedTransaction && $pdo instanceof \PDO && $pdo->inTransaction()) {
                $pdo->commit();
            }
        } catch (\Throwable $exception) {
            if ($startedTransaction && $pdo instanceof \PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['status' => false, 'message' => omoProjectsParamsT('projects.params.error.save')];
        }

        return ['status' => true, 'message' => omoProjectsParamsT('projects.params.saved'), 'changed' => true];
    }
}
