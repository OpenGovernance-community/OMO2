<?php

use dbObject\DecisionProcess;
use dbObject\Organization;
use dbObject\OrganizationApplication;

if (!function_exists('omoDecisionParamsSourceLang')) {
    function omoDecisionParamsSourceLang()
    {
        return [
            'decisions.params.title' => ['text' => 'Parametres Decisions', 'context' => 'Decision application settings title.'],
            'decisions.params.description' => ['text' => 'Definissez les modes de scrutin disponibles et les valeurs initiales des decisions hors reorg.', 'context' => 'Decision application settings description.'],
            'decisions.params.description.discovery' => ['text' => 'Definissez les modes de scrutin disponibles dans votre organisation.', 'context' => 'Decision application settings description in discovery mode.'],
            'decisions.params.section.methods' => ['text' => 'Modes de scrutin disponibles', 'context' => 'Available decision methods section title.'],
            'decisions.params.section.governance' => ['text' => 'Decisions hors reorg', 'context' => 'Governance workflow settings section title.'],
            'decisions.params.field.simple_vote' => ['text' => 'Vote simple', 'context' => 'Simple vote availability label.'],
            'decisions.params.field.majority_judgment' => ['text' => 'Jugement majoritaire', 'context' => 'Majority judgment availability label.'],
            'decisions.params.field.consent' => ['text' => 'Consentement', 'context' => 'Consent availability label.'],
            'decisions.params.field.governance_enabled' => ['text' => 'Activer les decisions hors reorg', 'context' => 'Governance workflow availability label.'],
            'decisions.params.field.governance_method' => ['text' => 'Methode de validation', 'context' => 'Default evaluation method for governance workflows.'],
            'decisions.params.option.governance_method.simple_vote' => ['text' => 'Vote simple', 'context' => 'Simple vote option for governance workflow settings.'],
            'decisions.params.option.governance_method.consent' => ['text' => 'Consentement', 'context' => 'Consent option for governance workflow settings.'],
            'decisions.params.field.governance_question' => ['text' => 'Question de consentement par defaut', 'context' => 'Default governance consent question label.'],
            'decisions.params.field.governance_question_hint' => ['text' => 'Si ce champ est vide, la question devra etre saisie lors de la creation de chaque decision hors reorg.', 'context' => 'Default governance question empty state help.'],
            'decisions.params.field.consultation_days' => ['text' => 'Delai de consultation (jours)', 'context' => 'Default governance consultation duration label.'],
            'decisions.params.field.vote_days' => ['text' => 'Delai de vote (jours)', 'context' => 'Default governance vote duration label.'],
            'decisions.params.field.show_live_votes' => ['text' => 'Afficher les votes pendant le scrutin', 'context' => 'Show live governance vote results label.'],
            'decisions.params.field.live_votes_anonymous' => ['text' => 'Rendre les votes anonymes', 'context' => 'Governance vote anonymity label.'],
            'decisions.params.field.live_votes_anonymous_hint' => ['text' => 'Cette option est utilisee uniquement lorsque les votes en cours sont affiches.', 'context' => 'Governance vote anonymity help.'],
            'decisions.params.action.save' => ['text' => 'Enregistrer', 'context' => 'Decision settings save button.'],
            'decisions.params.action.saving' => ['text' => 'Enregistrement...', 'context' => 'Decision settings saving button.'],
            'decisions.params.feedback.saved' => ['text' => 'Parametres Decisions enregistres.', 'context' => 'Decision settings saved feedback.'],
            'decisions.params.error.login' => ['text' => 'Connectez-vous pour acceder a ce panneau.', 'context' => 'Decision settings login error.'],
            'decisions.params.error.organization' => ['text' => 'Aucune organisation active.', 'context' => 'Decision settings organization error.'],
            'decisions.params.error.forbidden' => ['text' => 'Activez le mode admin de cette organisation pour modifier les parametres Decisions.', 'context' => 'Decision settings permission error.'],
            'decisions.params.error.unavailable' => ['text' => 'L application Decisions n est pas disponible pour cette organisation.', 'context' => 'Decision settings app unavailable error.'],
            'decisions.params.error.method' => ['text' => 'Methode non autorisee.', 'context' => 'Decision settings request method error.'],
            'decisions.params.error.save_failed' => ['text' => 'Impossible d enregistrer les parametres Decisions pour le moment.', 'context' => 'Decision settings save error.'],
        ];
    }
}

if (!function_exists('omoDecisionParamsT')) {
    function omoDecisionParamsT($key, array $variables = [])
    {
        static $sourceLang = null;
        static $bundle = null;
        if ($sourceLang === null) {
            $sourceLang = omoDecisionParamsSourceLang();
            $bundle = function_exists('omoLoadTranslationBundle')
                ? omoLoadTranslationBundle('omo_decision_params', $sourceLang)
                : [];
        }
        return function_exists('t') ? t((string)$key, $variables, $bundle, $sourceLang) : (string)($sourceLang[$key]['text'] ?? $key);
    }
}

if (!function_exists('omoDecisionParamsGetDefaultConfig')) {
    function omoDecisionParamsGetDefaultConfig(): array
    {
        return [
            'methods' => [
                DecisionProcess::METHOD_SIMPLE_VOTE => true,
                DecisionProcess::METHOD_MAJORITY_JUDGMENT => true,
                DecisionProcess::METHOD_CONSENT => true,
            ],
            'governance' => [
                'enabled' => false,
                'evaluation_method' => DecisionProcess::METHOD_CONSENT,
                'question' => '',
                'consultation_days' => 5,
                'vote_days' => 5,
                'show_live_votes' => false,
                'live_votes_anonymous' => false,
            ],
        ];
    }
}

if (!function_exists('omoDecisionParamsNormalizeConfig')) {
    function omoDecisionParamsNormalizeConfig(array $config): array
    {
        $defaults = omoDecisionParamsGetDefaultConfig();
        $hasMethods = array_key_exists('methods', $config) && is_array($config['methods']);
        $methods = $hasMethods ? $config['methods'] : [];
        $governance = is_array($config['governance'] ?? null) ? $config['governance'] : [];
        $normalizedMethods = [];
        foreach ($defaults['methods'] as $method => $enabled) {
            $normalizedMethods[$method] = array_key_exists($method, $methods)
                ? !empty($methods[$method])
                : (!$hasMethods && $enabled);
        }
        $question = trim((string)($governance['question'] ?? ''));
        if (mb_strlen($question, 'UTF-8') > 1000) {
            $question = mb_substr($question, 0, 1000, 'UTF-8');
        }
        $consultationDays = max(0, min(365, (int)($governance['consultation_days'] ?? $defaults['governance']['consultation_days'])));
        $voteDays = max(1, min(365, (int)($governance['vote_days'] ?? $defaults['governance']['vote_days'])));
        $showLiveVotes = !empty($governance['show_live_votes']);
        $governanceMethod = trim((string)($governance['evaluation_method'] ?? $defaults['governance']['evaluation_method']));
        if (!in_array($governanceMethod, [DecisionProcess::METHOD_SIMPLE_VOTE, DecisionProcess::METHOD_CONSENT], true)) {
            $governanceMethod = DecisionProcess::METHOD_CONSENT;
        }
        return [
            'methods' => $normalizedMethods,
            'governance' => [
                'enabled' => !empty($governance['enabled']),
                'evaluation_method' => $governanceMethod,
                'question' => $question,
                'consultation_days' => $consultationDays,
                'vote_days' => $voteDays,
                'show_live_votes' => $showLiveVotes,
                'live_votes_anonymous' => $showLiveVotes && !empty($governance['live_votes_anonymous']),
            ],
        ];
    }
}

if (!function_exists('omoDecisionParamsGetApplicationLink')) {
    function omoDecisionParamsGetApplicationLink(int $organizationId, bool $createIfMissing = false): ?OrganizationApplication
    {
        return $createIfMissing
            ? OrganizationApplication::ensureByOrganizationAndDirectory($organizationId, 'decision')
            : OrganizationApplication::loadByOrganizationAndDirectory($organizationId, 'decision', true);
    }
}

if (!function_exists('omoDecisionParamsCanUseGovernance')) {
    function omoDecisionParamsCanUseGovernance(?Organization $organization): bool
    {
        return $organization instanceof Organization
            && (int)$organization->getId() > 0
            && $organization->getInterfaceLevel() >= Organization::INTERFACE_LEVEL_AUTONOMOUS;
    }
}

if (!function_exists('omoDecisionParamsGetStoredConfig')) {
    function omoDecisionParamsGetStoredConfig(?Organization $organization): array
    {
        if (!$organization instanceof Organization || (int)$organization->getId() <= 0) {
            return omoDecisionParamsGetDefaultConfig();
        }
        $application = omoDecisionParamsGetApplicationLink((int)$organization->getId(), false);
        $parameters = $application instanceof OrganizationApplication ? $application->getParametersArray() : [];
        return omoDecisionParamsNormalizeConfig(is_array($parameters['decisionSettings'] ?? null) ? $parameters['decisionSettings'] : []);
    }
}

if (!function_exists('omoDecisionParamsGetConfig')) {
    function omoDecisionParamsGetConfig(?Organization $organization): array
    {
        $config = omoDecisionParamsGetStoredConfig($organization);
        if (!omoDecisionParamsCanUseGovernance($organization)) {
            $config['governance']['enabled'] = false;
        }
        return $config;
    }
}

if (!function_exists('omoDecisionParamsGetConfigForOrganizationId')) {
    function omoDecisionParamsGetConfigForOrganizationId(int $organizationId): array
    {
        $organization = new Organization();
        return $organizationId > 0 && $organization->load($organizationId)
            ? omoDecisionParamsGetConfig($organization)
            : omoDecisionParamsGetDefaultConfig();
    }
}

if (!function_exists('omoDecisionParamsCanManage')) {
    function omoDecisionParamsCanManage(int $organizationId, ?int $userId = null): bool
    {
        $userId = $userId ?? (function_exists('commonGetCurrentUserId') ? (int)commonGetCurrentUserId() : 0);
        if ($organizationId <= 0 || $userId <= 0) {
            return false;
        }
        if (function_exists('commonUserHasOrganizationAccess') && !commonUserHasOrganizationAccess($userId, $organizationId)) {
            return false;
        }
        if (function_exists('commonUserHasSiteAdminOverride') && commonUserHasSiteAdminOverride($userId)) {
            return true;
        }
        return $userId === (int)commonGetCurrentUserId()
            && function_exists('commonCurrentUserCanUseAdminMode')
            && commonCurrentUserCanUseAdminMode($organizationId);
    }
}

if (!function_exists('omoDecisionParamsStoreConfig')) {
    function omoDecisionParamsStoreConfig(Organization $organization, array $config): array
    {
        $application = omoDecisionParamsGetApplicationLink((int)$organization->getId(), false);
        if (!$application instanceof OrganizationApplication) {
            return ['status' => false, 'text' => omoDecisionParamsT('decisions.params.error.unavailable')];
        }
        $parameters = $application->getParametersArray();
        $normalizedConfig = omoDecisionParamsNormalizeConfig($config);
        if (!omoDecisionParamsCanUseGovernance($organization)) {
            $normalizedConfig['governance'] = omoDecisionParamsGetStoredConfig($organization)['governance'];
        }
        $parameters['decisionSettings'] = $normalizedConfig;
        $application->setParametersArray($parameters);
        $result = $application->save();
        return is_array($result) && !empty($result['status'])
            ? ['status' => true, 'config' => $parameters['decisionSettings']]
            : ['status' => false, 'text' => omoDecisionParamsT('decisions.params.error.save_failed')];
    }
}

?>
