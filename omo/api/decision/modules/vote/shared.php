<?php

require_once dirname(__DIR__) . '/common.php';

use dbObject\DecisionProcess;
use dbObject\DecisionResponse;

if (!function_exists('omoDecisionVoteGetMethodKey')) {
    function omoDecisionVoteGetMethodKey()
    {
        return DecisionProcess::METHOD_SIMPLE_VOTE;
    }
}

if (!function_exists('omoDecisionVoteNormalizeChoiceMode')) {
    function omoDecisionVoteNormalizeChoiceMode($value)
    {
        return trim((string)$value) === 'multiple' ? 'multiple' : 'single';
    }
}

if (!function_exists('omoDecisionVoteNormalizeMaxChoices')) {
    function omoDecisionVoteNormalizeMaxChoices($value, $choiceMode = 'single')
    {
        $choiceMode = omoDecisionVoteNormalizeChoiceMode($choiceMode);
        $maxChoices = (int)$value;

        if ($choiceMode !== 'multiple') {
            return 1;
        }

        return $maxChoices < 0 ? 0 : $maxChoices;
    }
}

if (!function_exists('omoDecisionVoteBuildConfig')) {
    function omoDecisionVoteBuildConfig($decisionOrParameters)
    {
        $simpleVote = [];
        $isConfigLikeArray = is_array($decisionOrParameters)
            && !array_key_exists(omoDecisionVoteGetMethodKey(), $decisionOrParameters)
            && (
                array_key_exists('choice_mode', $decisionOrParameters)
                || array_key_exists('max_choices', $decisionOrParameters)
                || array_key_exists('is_anonymous', $decisionOrParameters)
                || array_key_exists('allow_anonymous_votes', $decisionOrParameters)
                || array_key_exists('allow_consultation_proposals', $decisionOrParameters)
                || array_key_exists('allow_proposal_discussions', $decisionOrParameters)
                || array_key_exists('show_live_results', $decisionOrParameters)
                || array_key_exists('proposal_content', $decisionOrParameters)
                || array_key_exists('vote_weight_enabled', $decisionOrParameters)
                || array_key_exists('vote_weight_options', $decisionOrParameters)
                || array_key_exists('vote_weighting', $decisionOrParameters)
            );

        if ($isConfigLikeArray) {
            $simpleVote = $decisionOrParameters;
        } else {
            $parameters = is_object($decisionOrParameters) && method_exists($decisionOrParameters, 'get')
                ? omoDecisionModuleDecodeParameters($decisionOrParameters->get('parameters'))
                : omoDecisionModuleDecodeParameters($decisionOrParameters);
            $simpleVote = omoDecisionModuleGetMethodParameters($parameters, omoDecisionVoteGetMethodKey());
        }

        $choiceMode = omoDecisionVoteNormalizeChoiceMode($simpleVote['choice_mode'] ?? 'single');
        $voteWeightConfig = omoDecisionBlockSettingsBuildVoteWeightConfig($simpleVote);

        return [
            'choice_mode' => $choiceMode,
            'max_choices' => omoDecisionVoteNormalizeMaxChoices($simpleVote['max_choices'] ?? 1, $choiceMode),
            'is_anonymous' => !array_key_exists('is_anonymous', $simpleVote) || !empty($simpleVote['is_anonymous']),
            'allow_anonymous_votes' => !empty($simpleVote['allow_anonymous_votes']),
            'allow_consultation_proposals' => !empty($simpleVote['allow_consultation_proposals']),
            'allow_proposal_discussions' => !array_key_exists('allow_proposal_discussions', $simpleVote) || !empty($simpleVote['allow_proposal_discussions']),
            'show_live_results' => !empty($simpleVote['show_live_results']),
            'proposal_content' => omoDecisionNormalizeProposalContent($simpleVote['proposal_content'] ?? null),
            'vote_weight_enabled' => !empty($voteWeightConfig['enabled']),
            'vote_weight_question' => (string)$voteWeightConfig['question'],
            'vote_weight_options' => (array)$voteWeightConfig['options'],
            'vote_weight_options_text' => (string)$voteWeightConfig['options_text'],
        ];
    }
}

if (!function_exists('omoDecisionVoteMergeConfigIntoParameters')) {
    function omoDecisionVoteMergeConfigIntoParameters($value, array $config, array $extra = [])
    {
        $parameters = omoDecisionModuleDecodeParameters($value);
        $simpleVote = omoDecisionModuleGetMethodParameters($parameters, omoDecisionVoteGetMethodKey());

        $choiceMode = omoDecisionVoteNormalizeChoiceMode($config['choice_mode'] ?? ($simpleVote['choice_mode'] ?? 'single'));
        $maxChoices = omoDecisionVoteNormalizeMaxChoices($config['max_choices'] ?? ($simpleVote['max_choices'] ?? 1), $choiceMode);

        $simpleVote['choice_mode'] = $choiceMode;
        $simpleVote['max_choices'] = $maxChoices;
        $simpleVote['is_anonymous'] = !empty($config['is_anonymous']) ? 1 : 0;
        $simpleVote['allow_anonymous_votes'] = !empty($config['allow_anonymous_votes']) ? 1 : 0;
        $simpleVote['allow_consultation_proposals'] = !empty($config['allow_consultation_proposals']) ? 1 : 0;
        $simpleVote['allow_proposal_discussions'] = !empty($config['allow_proposal_discussions']) ? 1 : 0;
        $simpleVote['show_live_results'] = !empty($config['show_live_results']) ? 1 : 0;
        $simpleVote['proposal_content'] = omoDecisionNormalizeProposalContent($config['proposal_content'] ?? ($simpleVote['proposal_content'] ?? null));
        unset($simpleVote['live_results_anonymous']);
        $simpleVote = omoDecisionBlockSettingsMergeVoteWeightConfig($simpleVote, [
            'vote_weight_enabled' => !empty($config['vote_weight_enabled']),
            'vote_weight_question' => $config['vote_weight_question'] ?? '',
            'vote_weight_options' => $config['vote_weight_options'] ?? [],
        ]);

        foreach ($extra as $extraKey => $extraValue) {
            $simpleVote[$extraKey] = $extraValue;
        }

        $parameters[omoDecisionVoteGetMethodKey()] = $simpleVote;
        return $parameters;
    }
}

if (!function_exists('omoDecisionVoteExtractSelectedProposalIds')) {
    function omoDecisionVoteExtractSelectedProposalIds($response)
    {
        if (!$response instanceof DecisionResponse) {
            return [];
        }

        $parameters = omoDecisionModuleDecodeParameters($response->get('parameters'));
        $simpleVote = omoDecisionModuleGetMethodParameters($parameters, omoDecisionVoteGetMethodKey());

        $proposalIds = [];
        if (!empty($simpleVote['selected_proposal_ids']) && is_array($simpleVote['selected_proposal_ids'])) {
            foreach ($simpleVote['selected_proposal_ids'] as $proposalId) {
                $proposalId = (int)$proposalId;
                if ($proposalId > 0) {
                    $proposalIds[$proposalId] = $proposalId;
                }
            }
        }

        $legacyProposalId = (int)($simpleVote['selected_proposal_id'] ?? 0);
        if ($legacyProposalId > 0) {
            $proposalIds[$legacyProposalId] = $legacyProposalId;
        }

        return array_values($proposalIds);
    }
}

if (!function_exists('omoDecisionVoteExtractSelectedProposalId')) {
    function omoDecisionVoteExtractSelectedProposalId($response)
    {
        $proposalIds = omoDecisionVoteExtractSelectedProposalIds($response);
        return count($proposalIds) > 0 ? (int)$proposalIds[0] : 0;
    }
}

if (!function_exists('omoDecisionVoteExtractVoteWeightSelection')) {
    function omoDecisionVoteExtractVoteWeightSelection($response, $configOrParameters = null)
    {
        return omoDecisionBlockSettingsExtractResponseVoteWeightSelection($response, omoDecisionVoteGetMethodKey(), $configOrParameters);
    }
}

if (!function_exists('omoDecisionVoteBuildResponseParameters')) {
    function omoDecisionVoteBuildResponseParameters($choiceMode, array $proposalIds, array $positions, array $titles, $selectedWeight = null, $configOrParameters = null, $isAnonymous = false)
    {
        $choiceMode = omoDecisionVoteNormalizeChoiceMode($choiceMode);
        $proposalIds = array_values(array_map('intval', $proposalIds));
        $positions = array_values(array_map('intval', $positions));
        $titles = array_values(array_map('strval', $titles));
        $weightPayload = omoDecisionBlockSettingsBuildResponseVoteWeightPayload($selectedWeight, $configOrParameters);

        return [
            omoDecisionVoteGetMethodKey() => [
                'choice_mode' => $choiceMode,
                'selected_proposal_id' => count($proposalIds) > 0 ? (int)$proposalIds[0] : 0,
                'selected_proposal_ids' => $proposalIds,
                'selected_position' => count($positions) > 0 ? (int)$positions[0] : 0,
                'selected_positions' => $positions,
                'selected_title' => count($titles) > 0 ? (string)$titles[0] : '',
                'selected_titles' => $titles,
                'vote_weight' => (string)$weightPayload['vote_weight'],
                'vote_weight_label' => (string)$weightPayload['vote_weight_label'],
                'is_anonymous' => !empty($isAnonymous) ? 1 : 0,
            ],
        ];
    }
}

if (!function_exists('omoDecisionVoteBuildTallies')) {
    function omoDecisionVoteBuildTallies($responses, $configOrParameters = null)
    {
        $config = omoDecisionVoteBuildConfig($configOrParameters);
        $scale = omoDecisionBlockSettingsGetVoteWeightScale(['options' => $config['vote_weight_options'] ?? []]);
        $tallies = [
            'unweighted_total_count' => 0,
            'weighted_total_units' => 0,
            'scale' => $scale,
            'proposal_unweighted_counts' => [],
            'proposal_weighted_units' => [],
        ];

        foreach ($responses as $response) {
            $proposalIds = omoDecisionVoteExtractSelectedProposalIds($response);
            if (count($proposalIds) === 0) {
                continue;
            }

            $weightSelection = omoDecisionVoteExtractVoteWeightSelection($response, $config);
            $weightUnits = max(0, (int)($weightSelection['units'] ?? $scale));
            if ($weightUnits <= 0) {
                $weightUnits = $scale;
            }

            $tallies['unweighted_total_count']++;
            $tallies['weighted_total_units'] += $weightUnits;

            foreach ($proposalIds as $proposalId) {
                $proposalId = (int)$proposalId;
                if ($proposalId <= 0) {
                    continue;
                }

                if (!isset($tallies['proposal_unweighted_counts'][$proposalId])) {
                    $tallies['proposal_unweighted_counts'][$proposalId] = 0;
                }
                if (!isset($tallies['proposal_weighted_units'][$proposalId])) {
                    $tallies['proposal_weighted_units'][$proposalId] = 0;
                }

                $tallies['proposal_unweighted_counts'][$proposalId]++;
                $tallies['proposal_weighted_units'][$proposalId] += $weightUnits;
            }
        }

        return $tallies;
    }
}
