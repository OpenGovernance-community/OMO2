<?php

use dbObject\DecisionProcess;
use dbObject\Organization;
use dbObject\OrganizationApplication;

if (!function_exists('omoDecisionParamsSourceLang')) {
    function omoDecisionParamsSourceLang()
    {
        return [
            'decisions.params.title' => ['text' => 'Paramètres de Décisions', 'context' => 'Decision application settings title.'],
            'decisions.params.description' => ['text' => 'Définissez les modes de scrutin disponibles et les valeurs initiales des décisions hors réorg.', 'context' => 'Decision application settings description.'],
            'decisions.params.description.discovery' => ['text' => 'Définissez les modes de scrutin disponibles dans votre organisation.', 'context' => 'Decision application settings description in discovery mode.'],
            'decisions.params.section.methods' => ['text' => 'Modes de scrutin disponibles', 'context' => 'Available decision methods section title.'],
            'decisions.params.section.governance' => ['text' => 'Décisions hors réorg', 'context' => 'Governance workflow settings section title.'],
            'decisions.params.field.simple_vote' => ['text' => 'Vote simple', 'context' => 'Simple vote availability label.'],
            'decisions.params.field.majority_judgment' => ['text' => 'Jugement majoritaire', 'context' => 'Majority judgment availability label.'],
            'decisions.params.field.consent' => ['text' => 'Consentement', 'context' => 'Consent availability label.'],
            'decisions.params.field.consultation_only' => ['text' => 'Consultation seule', 'context' => 'Consultation only availability label.'],
            'decisions.params.field.governance_enabled' => ['text' => 'Activer les décisions hors réorg', 'context' => 'Governance workflow availability label.'],
            'decisions.params.field.governance_method' => ['text' => 'Méthode de validation', 'context' => 'Default evaluation method for governance workflows.'],
            'decisions.params.field.minimum_participation' => ['text' => 'Participation minimale pour valider', 'context' => 'Optional participation quota for all governance decisions.'],
            'decisions.params.field.minimum_participation_hint' => ['text' => 'Choisissez un nombre de personnes ou un pourcentage des invités, puis indiquez le minimum. Laissez le champ vide pour ne pas imposer de minimum. Les réponses « Pas d’objection » comptent ; les absences de réponse ne comptent pas. Le pourcentage est arrondi au nombre de personnes supérieur. Ce seuil s’applique aux décisions hors réorg lors de leur clôture.', 'context' => 'Governance participation quota format, counting and scope.'],
            'decisions.params.field.minimum_participation_unit' => ['text' => 'Type de minimum', 'context' => 'Accessible label of the governance participation quota unit selector.'],
            'decisions.params.option.minimum_participation.count' => ['text' => 'Nombre minimum', 'context' => 'Governance quota expressed as an absolute number of people.'],
            'decisions.params.option.minimum_participation.percent' => ['text' => 'Pourcentage minimum', 'context' => 'Governance quota expressed as a percentage of invitees.'],
            'decisions.params.error.minimum_participation' => ['text' => 'La participation minimale doit être vide, un nombre entier positif ou un pourcentage supérieur à 0 et inférieur ou égal à 100% (deux décimales au maximum).', 'context' => 'Invalid governance participation quota.'],
            'decisions.params.option.governance_method.simple_vote' => ['text' => 'Vote simple', 'context' => 'Simple vote option for governance workflow settings.'],
            'decisions.params.option.governance_method.consent' => ['text' => 'Consentement', 'context' => 'Consent option for governance workflow settings.'],
            'decisions.params.field.governance_question' => ['text' => 'Question de consentement par défaut', 'context' => 'Default governance consent question label.'],
            'decisions.params.field.governance_question_hint' => ['text' => 'Si ce champ est vide, la question devra être saisie lors de la création de chaque décision hors réorg.', 'context' => 'Default governance question empty state help.'],
            'decisions.params.field.consultation_days' => ['text' => 'Délai de consultation (jours)', 'context' => 'Default governance consultation duration label.'],
            'decisions.params.field.vote_days' => ['text' => 'Délai de vote (jours)', 'context' => 'Default governance vote duration label.'],
            'decisions.params.field.show_live_votes' => ['text' => 'Afficher les votes pendant le scrutin', 'context' => 'Show live governance vote results label.'],
            'decisions.params.field.live_votes_anonymous' => ['text' => 'Rendre les votes anonymes', 'context' => 'Governance vote anonymity label.'],
            'decisions.params.field.live_votes_anonymous_hint' => ['text' => 'Cette option est utilisée uniquement lorsque les votes en cours sont affichés.', 'context' => 'Governance vote anonymity help.'],
            'decisions.params.action.save' => ['text' => 'Enregistrer', 'context' => 'Decision settings save button.'],
            'decisions.params.action.saving' => ['text' => 'Enregistrement…', 'context' => 'Decision settings saving button.'],
            'decisions.params.feedback.saved' => ['text' => 'Paramètres de Décisions enregistrés.', 'context' => 'Decision settings saved feedback.'],
            'decisions.params.error.login' => ['text' => 'Connectez-vous pour accéder à ce panneau.', 'context' => 'Decision settings login error.'],
            'decisions.params.error.organization' => ['text' => 'Aucune organisation active.', 'context' => 'Decision settings organization error.'],
            'decisions.params.error.forbidden' => ['text' => 'Activez le mode administrateur de cette organisation pour modifier les paramètres de Décisions.', 'context' => 'Decision settings permission error.'],
            'decisions.params.error.unavailable' => ['text' => 'L’application Décisions n’est pas disponible pour cette organisation.', 'context' => 'Decision settings app unavailable error.'],
            'decisions.params.error.method' => ['text' => 'Méthode non autorisée.', 'context' => 'Decision settings request method error.'],
            'decisions.params.error.save_failed' => ['text' => 'Impossible d’enregistrer les paramètres de Décisions pour le moment.', 'context' => 'Decision settings save error.'],
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
                DecisionProcess::METHOD_CONSULTATION_ONLY => true,
            ],
            'governance' => [
                'enabled' => false,
                'evaluation_method' => DecisionProcess::METHOD_CONSENT,
                'question' => '',
                'minimum_participation' => '',
                'minimum_participation_updated_at' => '',
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
                : ((!$hasMethods || $method === DecisionProcess::METHOD_CONSULTATION_ONLY) && $enabled);
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
        $minimumParticipation = $governance['minimum_participation'] ?? '';
        if (array_key_exists('minimum_participation_unit', $governance)) {
            $unit = $governance['minimum_participation_unit'];
            if (!in_array($unit, ['count', 'percent'], true)
                || (!is_scalar($minimumParticipation) && $minimumParticipation !== null)
                || str_contains((string)$minimumParticipation, '%')) {
                throw new \InvalidArgumentException('Invalid participation quota unit or value');
            }
            if ($unit === 'percent' && trim((string)$minimumParticipation) !== '') $minimumParticipation .= '%';
        }
        return [
            'methods' => $normalizedMethods,
            'governance' => [
                'enabled' => !empty($governance['enabled']),
                'evaluation_method' => $governanceMethod,
                'question' => $question,
                'minimum_participation' => DecisionProcess::normalizeGovernanceMinimumParticipation($minimumParticipation),
                'minimum_participation_updated_at' => (string)($governance['minimum_participation_updated_at'] ?? ''),
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
        try {
            $normalizedConfig = omoDecisionParamsNormalizeConfig($config);
        } catch (\InvalidArgumentException $exception) {
            return ['status' => false, 'text' => omoDecisionParamsT('decisions.params.error.minimum_participation')];
        }
        $previousGovernance = omoDecisionParamsGetStoredConfig($organization)['governance'];
        $normalizedConfig['governance']['minimum_participation_updated_at'] = $normalizedConfig['governance']['minimum_participation'] !== $previousGovernance['minimum_participation']
            ? (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s')
            : $previousGovernance['minimum_participation_updated_at'];
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
