<?php

require_once dirname(__DIR__) . '/common.php';

use dbObject\DecisionProcess;
use dbObject\DecisionResponse;

if (!function_exists('omoDecisionConsentGetMethodKey')) {
    function omoDecisionConsentGetMethodKey()
    {
        return DecisionProcess::METHOD_CONSENT;
    }
}

if (!function_exists('omoDecisionConsentGetChoices')) {
    function omoDecisionConsentGetChoices()
    {
        return [
            'favor' => 'Pour',
            'no_objection' => 'Pas d objection',
            'objection' => 'Objection',
        ];
    }
}

if (!function_exists('omoDecisionConsentGetChoiceRenderOrder')) {
    function omoDecisionConsentGetChoiceRenderOrder()
    {
        return [
            'objection',
            'no_objection',
            'favor',
        ];
    }
}

if (!function_exists('omoDecisionConsentGetChoiceUiMap')) {
    function omoDecisionConsentGetChoiceUiMap()
    {
        return [
            'objection' => [
                'label' => 'Objection',
                'icon_url' => '/common/choice/assets/consent-objection.png',
                'theme' => 'objection',
            ],
            'no_objection' => [
                'label' => 'Pas d objection',
                'icon_url' => '/common/choice/assets/consent-favor.png',
                'theme' => 'no_objection',
            ],
            'favor' => [
                'label' => 'Pour',
                'icon_url' => '/common/choice/assets/consent-no-objection.png',
                'theme' => 'favor',
            ],
        ];
    }
}

if (!function_exists('omoDecisionConsentNormalizeChoice')) {
    function omoDecisionConsentNormalizeChoice($value)
    {
        $value = trim((string)$value);
        $choices = omoDecisionConsentGetChoices();
        return array_key_exists($value, $choices) ? $value : 'no_objection';
    }
}

if (!function_exists('omoDecisionConsentBuildConfig')) {
    function omoDecisionConsentBuildConfig($decisionOrParameters)
    {
        $isConfigLikeArray = is_array($decisionOrParameters)
            && !array_key_exists(omoDecisionConsentGetMethodKey(), $decisionOrParameters)
            && (
                array_key_exists('is_anonymous', $decisionOrParameters)
                || array_key_exists('allow_anonymous_votes', $decisionOrParameters)
                || array_key_exists('allow_consultation_proposals', $decisionOrParameters)
                || array_key_exists('allow_proposal_discussions', $decisionOrParameters)
                || array_key_exists('show_live_results', $decisionOrParameters)
                || array_key_exists('randomize_proposal_order', $decisionOrParameters)
                || array_key_exists('one_proposal_at_a_time', $decisionOrParameters)
                || array_key_exists('proposal_content', $decisionOrParameters)
                || array_key_exists('vote_weight_enabled', $decisionOrParameters)
            );
        if ($isConfigLikeArray) {
            $methodParameters = $decisionOrParameters;
        } else {
            $parameters = is_object($decisionOrParameters) && method_exists($decisionOrParameters, 'get')
                ? omoDecisionModuleDecodeParameters($decisionOrParameters->get('parameters'))
                : omoDecisionModuleDecodeParameters($decisionOrParameters);
            $methodParameters = omoDecisionModuleGetMethodParameters($parameters, omoDecisionConsentGetMethodKey());
        }
        $voteWeightConfig = omoDecisionBlockSettingsBuildVoteWeightConfig($methodParameters);

        return [
            'is_anonymous' => !array_key_exists('is_anonymous', $methodParameters) || !empty($methodParameters['is_anonymous']),
            'allow_anonymous_votes' => !empty($methodParameters['allow_anonymous_votes']),
            'allow_consultation_proposals' => !empty($methodParameters['allow_consultation_proposals']),
            'allow_proposal_discussions' => !array_key_exists('allow_proposal_discussions', $methodParameters) || !empty($methodParameters['allow_proposal_discussions']),
            'show_live_results' => !empty($methodParameters['show_live_results']),
            'randomize_proposal_order' => !empty($methodParameters['randomize_proposal_order']),
            'one_proposal_at_a_time' => !empty($methodParameters['one_proposal_at_a_time']),
            'proposal_content' => omoDecisionNormalizeProposalContent($methodParameters['proposal_content'] ?? null),
            'choices' => omoDecisionConsentGetChoices(),
            'vote_weight_enabled' => !empty($voteWeightConfig['enabled']),
            'vote_weight_question' => (string)$voteWeightConfig['question'],
            'vote_weight_options' => (array)$voteWeightConfig['options'],
            'vote_weight_options_text' => (string)$voteWeightConfig['options_text'],
        ];
    }
}

if (!function_exists('omoDecisionConsentMergeConfigIntoParameters')) {
    function omoDecisionConsentMergeConfigIntoParameters($value, array $config, array $extra = [])
    {
        $parameters = omoDecisionModuleDecodeParameters($value);
        $methodParameters = omoDecisionModuleGetMethodParameters($parameters, omoDecisionConsentGetMethodKey());

        $methodParameters['is_anonymous'] = !empty($config['is_anonymous']) ? 1 : 0;
        $methodParameters['allow_anonymous_votes'] = !empty($config['allow_anonymous_votes']) ? 1 : 0;
        $methodParameters['allow_consultation_proposals'] = !empty($config['allow_consultation_proposals']) ? 1 : 0;
        $methodParameters['allow_proposal_discussions'] = !empty($config['allow_proposal_discussions']) ? 1 : 0;
        $methodParameters['show_live_results'] = !empty($config['show_live_results']) ? 1 : 0;
        $methodParameters['randomize_proposal_order'] = !empty($config['randomize_proposal_order']) ? 1 : 0;
        $methodParameters['one_proposal_at_a_time'] = !empty($config['one_proposal_at_a_time']) ? 1 : 0;
        $methodParameters['proposal_content'] = omoDecisionNormalizeProposalContent($config['proposal_content'] ?? ($methodParameters['proposal_content'] ?? null));
        unset($methodParameters['live_results_anonymous']);
        $methodParameters = omoDecisionBlockSettingsMergeVoteWeightConfig($methodParameters, [
            'vote_weight_enabled' => !empty($config['vote_weight_enabled']),
            'vote_weight_question' => $config['vote_weight_question'] ?? '',
            'vote_weight_options' => $config['vote_weight_options'] ?? [],
        ]);

        foreach ($extra as $extraKey => $extraValue) {
            $methodParameters[$extraKey] = $extraValue;
        }

        $parameters[omoDecisionConsentGetMethodKey()] = $methodParameters;
        return $parameters;
    }
}

if (!function_exists('omoDecisionConsentExtractChoices')) {
    function omoDecisionConsentExtractChoices($response)
    {
        if (!$response instanceof DecisionResponse) {
            return [];
        }

        $parameters = omoDecisionModuleDecodeParameters($response->get('parameters'));
        $methodParameters = omoDecisionModuleGetMethodParameters($parameters, omoDecisionConsentGetMethodKey());
        $choices = [];

        if (!empty($methodParameters['choices']) && is_array($methodParameters['choices'])) {
            foreach ($methodParameters['choices'] as $proposalId => $choice) {
                $proposalId = (int)$proposalId;
                if ($proposalId <= 0) {
                    continue;
                }

                $choices[$proposalId] = omoDecisionConsentNormalizeChoice($choice);
            }
        }

        return $choices;
    }
}

if (!function_exists('omoDecisionConsentBuildResponseParameters')) {
    function omoDecisionConsentBuildResponseParameters(array $choiceMap, array $proposalMeta = [], $isAnonymous = false)
    {
        $choiceLabels = omoDecisionConsentGetChoices();
        $normalizedChoices = [];
        $choiceDetails = [];

        foreach ($choiceMap as $proposalId => $choice) {
            $proposalId = (int)$proposalId;
            if ($proposalId <= 0) {
                continue;
            }

            $normalizedChoice = omoDecisionConsentNormalizeChoice($choice);
            $normalizedChoices[$proposalId] = $normalizedChoice;
            $meta = $proposalMeta[$proposalId] ?? [];
            $choiceDetails[$proposalId] = [
                'choice' => $normalizedChoice,
                'label' => (string)$choiceLabels[$normalizedChoice],
                'position' => (int)($meta['position'] ?? 0),
                'title' => (string)($meta['title'] ?? ''),
            ];
        }

        return [
            omoDecisionConsentGetMethodKey() => [
                'choices' => $normalizedChoices,
                'details' => $choiceDetails,
                'is_anonymous' => !empty($isAnonymous) ? 1 : 0,
            ],
        ];
    }
}

if (!function_exists('omoDecisionConsentBuildStats')) {
    function omoDecisionConsentBuildStats(array $proposalObjects, $responses)
    {
        $choiceLabels = omoDecisionConsentGetChoices();
        $choiceKeys = array_keys($choiceLabels);
        $stats = [];

        foreach ($proposalObjects as $proposal) {
            $proposalId = (int)$proposal->getId();
            $stats[$proposalId] = [
                'distribution' => [
                    'favor' => 0,
                    'no_objection' => 0,
                    'objection' => 0,
                ],
                'count' => 0,
                'objection_count' => 0,
                'has_objection' => false,
                'dominant_choice' => 'no_objection',
            ];
        }

        foreach ($responses as $response) {
            $choices = omoDecisionConsentExtractChoices($response);
            foreach ($choices as $proposalId => $choice) {
                if (!isset($stats[$proposalId])) {
                    continue;
                }

                $choice = omoDecisionConsentNormalizeChoice($choice);
                $stats[$proposalId]['distribution'][$choice]++;
                $stats[$proposalId]['count']++;
            }
        }

        foreach ($stats as $proposalId => $item) {
            $stats[$proposalId]['objection_count'] = (int)$item['distribution']['objection'];
            $stats[$proposalId]['has_objection'] = $stats[$proposalId]['objection_count'] > 0;

            $dominantChoice = 'no_objection';
            $dominantCount = -1;
            foreach ($choiceKeys as $choiceKey) {
                $choiceCount = (int)($item['distribution'][$choiceKey] ?? 0);
                if ($choiceCount > $dominantCount) {
                    $dominantCount = $choiceCount;
                    $dominantChoice = $choiceKey;
                }
            }
            $stats[$proposalId]['dominant_choice'] = $dominantChoice;
        }

        return $stats;
    }
}
