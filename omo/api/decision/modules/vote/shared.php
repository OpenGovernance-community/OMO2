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
        $parameters = is_object($decisionOrParameters) && method_exists($decisionOrParameters, 'get')
            ? omoDecisionModuleDecodeParameters($decisionOrParameters->get('parameters'))
            : omoDecisionModuleDecodeParameters($decisionOrParameters);
        $simpleVote = omoDecisionModuleGetMethodParameters($parameters, omoDecisionVoteGetMethodKey());
        $choiceMode = omoDecisionVoteNormalizeChoiceMode($simpleVote['choice_mode'] ?? 'single');

        return [
            'choice_mode' => $choiceMode,
            'max_choices' => omoDecisionVoteNormalizeMaxChoices($simpleVote['max_choices'] ?? 1, $choiceMode),
            'is_anonymous' => !empty($simpleVote['is_anonymous']),
            'allow_consultation_proposals' => !empty($simpleVote['allow_consultation_proposals']),
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
        $simpleVote['allow_consultation_proposals'] = !empty($config['allow_consultation_proposals']) ? 1 : 0;

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

if (!function_exists('omoDecisionVoteBuildResponseParameters')) {
    function omoDecisionVoteBuildResponseParameters($choiceMode, array $proposalIds, array $positions, array $titles)
    {
        $choiceMode = omoDecisionVoteNormalizeChoiceMode($choiceMode);
        $proposalIds = array_values(array_map('intval', $proposalIds));
        $positions = array_values(array_map('intval', $positions));
        $titles = array_values(array_map('strval', $titles));

        return [
            omoDecisionVoteGetMethodKey() => [
                'choice_mode' => $choiceMode,
                'selected_proposal_id' => count($proposalIds) > 0 ? (int)$proposalIds[0] : 0,
                'selected_proposal_ids' => $proposalIds,
                'selected_position' => count($positions) > 0 ? (int)$positions[0] : 0,
                'selected_positions' => $positions,
                'selected_title' => count($titles) > 0 ? (string)$titles[0] : '',
                'selected_titles' => $titles,
            ],
        ];
    }
}
