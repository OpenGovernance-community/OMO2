<?php

require_once dirname(__DIR__) . '/common.php';

use dbObject\DecisionProcess;
use dbObject\DecisionResponse;

if (!function_exists('omoDecisionMajorityJudgmentGetMethodKey')) {
    function omoDecisionMajorityJudgmentGetMethodKey()
    {
        return DecisionProcess::METHOD_MAJORITY_JUDGMENT;
    }
}

if (!function_exists('omoDecisionMajorityJudgmentGetNoOpinionScore')) {
    function omoDecisionMajorityJudgmentGetNoOpinionScore()
    {
        return 3;
    }
}

if (!function_exists('omoDecisionMajorityJudgmentGetDefaultMentionOptions')) {
    function omoDecisionMajorityJudgmentGetDefaultMentionOptions()
    {
        return [
            0 => ['label' => 'A rejeter', 'active' => 1],
            1 => ['label' => 'Insuffisant', 'active' => 1],
            2 => ['label' => 'Passable', 'active' => 1],
            3 => ['label' => 'Sans avis', 'active' => 1],
            4 => ['label' => 'Assez bien', 'active' => 1],
            5 => ['label' => 'Très bien', 'active' => 1],
            6 => ['label' => 'Excellent', 'active' => 1],
        ];
    }
}

if (!function_exists('omoDecisionMajorityJudgmentNormalizeMentionOptions')) {
    function omoDecisionMajorityJudgmentNormalizeMentionOptions($rawOptions)
    {
        $defaultOptions = omoDecisionMajorityJudgmentGetDefaultMentionOptions();
        $indexedOptions = [];

        if (is_array($rawOptions)) {
            foreach ($rawOptions as $optionKey => $rawOption) {
                $score = null;
                if (is_numeric($optionKey)) {
                    $score = (int)$optionKey;
                } elseif (is_array($rawOption) && isset($rawOption['score']) && is_numeric($rawOption['score'])) {
                    $score = (int)$rawOption['score'];
                }

                if ($score === null || $score < 0 || $score > 6) {
                    continue;
                }

                $indexedOptions[$score] = $rawOption;
            }
        }

        $normalizedOptions = [];
        foreach ($defaultOptions as $score => $defaultOption) {
            $rawOption = $indexedOptions[$score] ?? null;
            $label = '';
            $active = (int)$defaultOption['active'] === 1;

            if (is_array($rawOption)) {
                $label = trim((string)($rawOption['label'] ?? ''));
                if (array_key_exists('active', $rawOption)) {
                    $active = !empty($rawOption['active']);
                }
            } elseif ($rawOption !== null) {
                $label = trim((string)$rawOption);
            }

            $defaultLabel = (string)$defaultOption['label'];
            $normalizedOptions[$score] = [
                'score' => $score,
                'label' => $label !== '' ? $label : $defaultLabel,
                'active' => $active,
                'is_no_opinion' => $score === omoDecisionMajorityJudgmentGetNoOpinionScore(),
                'default_label' => $defaultLabel,
                'default_active' => (int)$defaultOption['active'] === 1,
            ];
        }

        return $normalizedOptions;
    }
}

if (!function_exists('omoDecisionMajorityJudgmentBuildMentionOptionsFromInput')) {
    function omoDecisionMajorityJudgmentBuildMentionOptionsFromInput($labelInput, $activeInput)
    {
        $defaultOptions = omoDecisionMajorityJudgmentGetDefaultMentionOptions();
        $normalizedOptions = [];

        foreach ($defaultOptions as $score => $defaultOption) {
            $rawLabel = is_array($labelInput) ? trim((string)($labelInput[$score] ?? '')) : '';
            $defaultLabel = (string)$defaultOption['label'];
        $normalizedOptions[$score] = [
            'score' => $score,
            'label' => $rawLabel !== '' ? $rawLabel : $defaultLabel,
            'active' => is_array($activeInput) ? !empty($activeInput[$score]) : ((int)$defaultOption['active'] === 1),
            'is_no_opinion' => $score === omoDecisionMajorityJudgmentGetNoOpinionScore(),
            'default_label' => $defaultLabel,
            'default_active' => (int)$defaultOption['active'] === 1,
        ];
        }

        return $normalizedOptions;
    }
}

if (!function_exists('omoDecisionMajorityJudgmentBuildScaleSummaryFromOptions')) {
    function omoDecisionMajorityJudgmentBuildScaleSummaryFromOptions(array $mentionOptions)
    {
        $activeLabels = [];
        foreach ($mentionOptions as $option) {
            if (empty($option['active'])) {
                continue;
            }
            $activeLabels[] = trim((string)($option['label'] ?? ''));
        }

        if (count($activeLabels) === 0) {
            return 'Aucune mention active';
        }

        return implode(' / ', $activeLabels);
    }
}

if (!function_exists('omoDecisionMajorityJudgmentGetLegendItems')) {
    function omoDecisionMajorityJudgmentGetLegendItems($decisionOrParameters = null)
    {
        $config = omoDecisionMajorityJudgmentBuildConfig($decisionOrParameters);
        $palette = [
            0 => ['color' => 'var(--color-palette-red, #c62828)', 'text_color' => '#ffffff'],
            1 => ['color' => 'var(--color-palette-orange, #ef6c00)', 'text_color' => '#ffffff'],
            2 => ['color' => 'var(--color-palette-yellow, #f9a825)', 'text_color' => '#0f172a'],
            3 => ['color' => 'var(--color-palette-gray, #9ca3af)', 'text_color' => '#0f172a'],
            4 => ['color' => 'var(--color-palette-green-light, #9ccc65)', 'text_color' => '#0f172a'],
            5 => ['color' => 'var(--color-palette-green-medium, #43a047)', 'text_color' => '#ffffff'],
            6 => ['color' => 'var(--color-palette-green-dark, #1b5e20)', 'text_color' => '#ffffff'],
        ];
        $items = [];

        foreach ((array)($config['mention_options'] ?? []) as $score => $option) {
            $score = (int)$score;
            if (empty($option['active'])) {
                continue;
            }

            $paletteItem = $palette[$score] ?? ['color' => 'var(--color-primary, #2563eb)', 'text_color' => '#ffffff'];
            $items[] = [
                'score' => $score,
                'label' => trim((string)($option['label'] ?? '')),
                'color' => (string)$paletteItem['color'],
                'text_color' => (string)$paletteItem['text_color'],
                'is_no_opinion' => !empty($option['is_no_opinion']),
            ];
        }

        return $items;
    }
}

if (!function_exists('omoDecisionMajorityJudgmentNormalizeScore')) {
    function omoDecisionMajorityJudgmentNormalizeScore($value)
    {
        $score = (int)$value;
        if ($score < 0) {
            return 0;
        }
        if ($score > 6) {
            return 6;
        }
        return $score;
    }
}

if (!function_exists('omoDecisionMajorityJudgmentGetEmptyDistribution')) {
    function omoDecisionMajorityJudgmentGetEmptyDistribution()
    {
        return [0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0];
    }
}

if (!function_exists('omoDecisionMajorityJudgmentNormalizeDistribution')) {
    function omoDecisionMajorityJudgmentNormalizeDistribution(array $distribution)
    {
        $normalized = omoDecisionMajorityJudgmentGetEmptyDistribution();

        foreach ($distribution as $score => $count) {
            $normalizedScore = omoDecisionMajorityJudgmentNormalizeScore($score);
            $normalized[$normalizedScore] = max(0, (int)$count);
        }

        return $normalized;
    }
}

if (!function_exists('omoDecisionMajorityJudgmentBuildConfig')) {
    function omoDecisionMajorityJudgmentBuildConfig($decisionOrParameters)
    {
        $methodParameters = [];
        $isConfigLikeArray = is_array($decisionOrParameters)
            && !array_key_exists(omoDecisionMajorityJudgmentGetMethodKey(), $decisionOrParameters)
            && (
                array_key_exists('mention_options', $decisionOrParameters)
                || array_key_exists('is_anonymous', $decisionOrParameters)
                || array_key_exists('allow_anonymous_votes', $decisionOrParameters)
                || array_key_exists('allow_consultation_proposals', $decisionOrParameters)
                || array_key_exists('allow_proposal_discussions', $decisionOrParameters)
                || array_key_exists('show_live_results', $decisionOrParameters)
                || array_key_exists('proposal_content', $decisionOrParameters)
            );

        if ($isConfigLikeArray) {
            $methodParameters = $decisionOrParameters;
        } else {
            $parameters = is_object($decisionOrParameters) && method_exists($decisionOrParameters, 'get')
                ? omoDecisionModuleDecodeParameters($decisionOrParameters->get('parameters'))
                : omoDecisionModuleDecodeParameters($decisionOrParameters);
            $methodParameters = omoDecisionModuleGetMethodParameters($parameters, omoDecisionMajorityJudgmentGetMethodKey());
        }

        $defaultMentionOptions = omoDecisionMajorityJudgmentNormalizeMentionOptions(
            omoDecisionMajorityJudgmentGetDefaultMentionOptions()
        );
        $mentionOptions = omoDecisionMajorityJudgmentNormalizeMentionOptions(
            $methodParameters['mention_options'] ?? ($methodParameters['mentions'] ?? [])
        );
        $mentionCustomizationEnabled = array_key_exists('mention_customization_enabled', $methodParameters)
            ? !empty($methodParameters['mention_customization_enabled'])
            : (json_encode($mentionOptions) !== json_encode($defaultMentionOptions));
        if (!$mentionCustomizationEnabled) {
            $mentionOptions = $defaultMentionOptions;
        }
        $mentions = [];
        $allMentions = [];
        $activeScores = [];
        $countedScores = [];
        $noOpinionScore = omoDecisionMajorityJudgmentGetNoOpinionScore();
        $hasNoOpinion = false;

        foreach ($mentionOptions as $score => $option) {
            $label = (string)$option['label'];
            $allMentions[$score] = $label;
            if (empty($option['active'])) {
                continue;
            }

            $mentions[$score] = $label;
            $activeScores[] = $score;
            if ($score === $noOpinionScore) {
                $hasNoOpinion = true;
                continue;
            }

            $countedScores[] = $score;
        }

        $voteWeightConfig = omoDecisionBlockSettingsBuildVoteWeightConfig($methodParameters);

        return [
            'is_anonymous' => !array_key_exists('is_anonymous', $methodParameters) || !empty($methodParameters['is_anonymous']),
            'allow_anonymous_votes' => !empty($methodParameters['allow_anonymous_votes']),
            'allow_consultation_proposals' => !empty($methodParameters['allow_consultation_proposals']),
            'allow_proposal_discussions' => !array_key_exists('allow_proposal_discussions', $methodParameters) || !empty($methodParameters['allow_proposal_discussions']),
            'show_live_results' => !empty($methodParameters['show_live_results']),
            'proposal_content' => omoDecisionNormalizeProposalContent($methodParameters['proposal_content'] ?? null),
            'scale_size' => count($activeScores),
            'mentions' => $mentions,
            'all_mentions' => $allMentions,
            'mention_options' => $mentionOptions,
            'mention_customization_enabled' => $mentionCustomizationEnabled,
            'active_scores' => $activeScores,
            'counted_scores' => $countedScores,
            'has_no_opinion' => $hasNoOpinion,
            'no_opinion_score' => $noOpinionScore,
            'no_opinion_label' => $hasNoOpinion ? (string)($allMentions[$noOpinionScore] ?? '') : '',
            'scale_summary' => omoDecisionMajorityJudgmentBuildScaleSummaryFromOptions($mentionOptions),
            'vote_weight_enabled' => !empty($voteWeightConfig['enabled']),
            'vote_weight_question' => (string)$voteWeightConfig['question'],
            'vote_weight_options' => (array)$voteWeightConfig['options'],
            'vote_weight_options_text' => (string)$voteWeightConfig['options_text'],
        ];
    }
}

if (!function_exists('omoDecisionMajorityJudgmentGetMentions')) {
    function omoDecisionMajorityJudgmentGetMentions($decisionOrParameters = null, $includeInactive = false)
    {
        if ($decisionOrParameters === null) {
            $decisionOrParameters = [
                'mention_options' => omoDecisionMajorityJudgmentGetDefaultMentionOptions(),
                'is_anonymous' => 1,
                'allow_consultation_proposals' => 0,
            ];
        }

        $config = omoDecisionMajorityJudgmentBuildConfig($decisionOrParameters);
        return $includeInactive ? $config['all_mentions'] : $config['mentions'];
    }
}

if (!function_exists('omoDecisionMajorityJudgmentGetCountedDistribution')) {
    function omoDecisionMajorityJudgmentGetCountedDistribution(array $distribution, $configOrParameters = null)
    {
        $normalized = omoDecisionMajorityJudgmentNormalizeDistribution($distribution);
        $config = omoDecisionMajorityJudgmentBuildConfig($configOrParameters);
        $countedScores = array_flip(array_map('intval', (array)($config['counted_scores'] ?? [])));

        foreach ($normalized as $score => $count) {
            if (!isset($countedScores[(int)$score])) {
                $normalized[(int)$score] = 0;
            }
        }

        return $normalized;
    }
}

if (!function_exists('omoDecisionMajorityJudgmentGetNoOpinionCount')) {
    function omoDecisionMajorityJudgmentGetNoOpinionCount(array $distribution, $configOrParameters = null)
    {
        $config = omoDecisionMajorityJudgmentBuildConfig($configOrParameters);
        if (empty($config['has_no_opinion'])) {
            return 0;
        }

        $normalized = omoDecisionMajorityJudgmentNormalizeDistribution($distribution);
        $noOpinionScore = (int)($config['no_opinion_score'] ?? omoDecisionMajorityJudgmentGetNoOpinionScore());
        return (int)($normalized[$noOpinionScore] ?? 0);
    }
}

if (!function_exists('omoDecisionMajorityJudgmentResolveMajorityScore')) {
    function omoDecisionMajorityJudgmentResolveMajorityScore(array $distribution, $configOrParameters = null)
    {
        $config = omoDecisionMajorityJudgmentBuildConfig($configOrParameters);
        $normalizedDistribution = omoDecisionMajorityJudgmentGetCountedDistribution($distribution, $config);
        $countedScores = array_values(array_map('intval', (array)($config['counted_scores'] ?? [])));
        $totalCount = array_sum($normalizedDistribution);

        if ($totalCount <= 0 || count($countedScores) === 0) {
            return null;
        }

        $threshold = (int)ceil($totalCount / 2);
        $running = 0;
        foreach ($countedScores as $score) {
            $running += (int)($normalizedDistribution[$score] ?? 0);
            if ($running >= $threshold) {
                return $score;
            }
        }

        return (int)end($countedScores);
    }
}

if (!function_exists('omoDecisionMajorityJudgmentCompareStats')) {
    function omoDecisionMajorityJudgmentCompareStats(array $leftStat, array $rightStat, $configOrParameters = null)
    {
        $config = omoDecisionMajorityJudgmentBuildConfig($configOrParameters);
        $leftDistribution = omoDecisionMajorityJudgmentGetCountedDistribution((array)($leftStat['distribution'] ?? []), $config);
        $rightDistribution = omoDecisionMajorityJudgmentGetCountedDistribution((array)($rightStat['distribution'] ?? []), $config);
        $leftRemaining = array_sum($leftDistribution);
        $rightRemaining = array_sum($rightDistribution);

        if ($leftRemaining <= 0 || $rightRemaining <= 0) {
            if ($leftRemaining === $rightRemaining) {
                return 0;
            }

            return $rightRemaining <=> $leftRemaining;
        }

        while ($leftRemaining > 0 && $rightRemaining > 0) {
            $leftMajorityScore = omoDecisionMajorityJudgmentResolveMajorityScore($leftDistribution, $config);
            $rightMajorityScore = omoDecisionMajorityJudgmentResolveMajorityScore($rightDistribution, $config);

            if ($leftMajorityScore === null || $rightMajorityScore === null) {
                break;
            }

            if ($leftMajorityScore !== $rightMajorityScore) {
                return $rightMajorityScore <=> $leftMajorityScore;
            }

            if ((int)($leftDistribution[$leftMajorityScore] ?? 0) <= 0 || (int)($rightDistribution[$rightMajorityScore] ?? 0) <= 0) {
                break;
            }

            $leftDistribution[$leftMajorityScore]--;
            $rightDistribution[$rightMajorityScore]--;
            $leftRemaining--;
            $rightRemaining--;
        }

        return 0;
    }
}

if (!function_exists('omoDecisionMajorityJudgmentMergeConfigIntoParameters')) {
    function omoDecisionMajorityJudgmentMergeConfigIntoParameters($value, array $config, array $extra = [])
    {
        $parameters = omoDecisionModuleDecodeParameters($value);
        $methodParameters = omoDecisionModuleGetMethodParameters($parameters, omoDecisionMajorityJudgmentGetMethodKey());
        $normalizedConfig = omoDecisionMajorityJudgmentBuildConfig($config);

        $methodParameters['is_anonymous'] = !empty($normalizedConfig['is_anonymous']) ? 1 : 0;
        $methodParameters['allow_anonymous_votes'] = !empty($normalizedConfig['allow_anonymous_votes']) ? 1 : 0;
        $methodParameters['allow_consultation_proposals'] = !empty($normalizedConfig['allow_consultation_proposals']) ? 1 : 0;
        $methodParameters['allow_proposal_discussions'] = !empty($normalizedConfig['allow_proposal_discussions']) ? 1 : 0;
        $methodParameters['show_live_results'] = !empty($normalizedConfig['show_live_results']) ? 1 : 0;
        $methodParameters['proposal_content'] = omoDecisionNormalizeProposalContent($normalizedConfig['proposal_content'] ?? ($methodParameters['proposal_content'] ?? null));
        unset($methodParameters['live_results_anonymous']);
        $methodParameters['mention_customization_enabled'] = !empty($normalizedConfig['mention_customization_enabled']) ? 1 : 0;
        $methodParameters['scale_size'] = (int)count((array)($normalizedConfig['active_scores'] ?? []));
        $methodParameters['mention_options'] = [];

        $mentionOptionsToSave = !empty($normalizedConfig['mention_customization_enabled'])
            ? (array)($normalizedConfig['mention_options'] ?? [])
            : omoDecisionMajorityJudgmentNormalizeMentionOptions(omoDecisionMajorityJudgmentGetDefaultMentionOptions());
        foreach ($mentionOptionsToSave as $score => $option) {
            $methodParameters['mention_options'][(string)$score] = [
                'label' => (string)($option['label'] ?? ''),
                'active' => !empty($option['active']) ? 1 : 0,
            ];
        }

        $methodParameters = omoDecisionBlockSettingsMergeVoteWeightConfig($methodParameters, [
            'vote_weight_enabled' => !empty($normalizedConfig['vote_weight_enabled']),
            'vote_weight_question' => $normalizedConfig['vote_weight_question'] ?? '',
            'vote_weight_options' => $normalizedConfig['vote_weight_options'] ?? [],
        ]);

        foreach ($extra as $extraKey => $extraValue) {
            $methodParameters[$extraKey] = $extraValue;
        }

        $parameters[omoDecisionMajorityJudgmentGetMethodKey()] = $methodParameters;
        return $parameters;
    }
}

if (!function_exists('omoDecisionMajorityJudgmentExtractScores')) {
    function omoDecisionMajorityJudgmentExtractScores($response)
    {
        if (!$response instanceof DecisionResponse) {
            return [];
        }

        $parameters = omoDecisionModuleDecodeParameters($response->get('parameters'));
        $methodParameters = omoDecisionModuleGetMethodParameters($parameters, omoDecisionMajorityJudgmentGetMethodKey());
        $scores = [];

        if (!empty($methodParameters['scores']) && is_array($methodParameters['scores'])) {
            foreach ($methodParameters['scores'] as $proposalId => $score) {
                $proposalId = (int)$proposalId;
                if ($proposalId <= 0) {
                    continue;
                }
                $scores[$proposalId] = omoDecisionMajorityJudgmentNormalizeScore($score);
            }
        }

        return $scores;
    }
}

if (!function_exists('omoDecisionMajorityJudgmentExtractVoteWeightSelection')) {
    function omoDecisionMajorityJudgmentExtractVoteWeightSelection($response, $configOrParameters = null)
    {
        return omoDecisionBlockSettingsExtractResponseVoteWeightSelection($response, omoDecisionMajorityJudgmentGetMethodKey(), $configOrParameters);
    }
}

if (!function_exists('omoDecisionMajorityJudgmentBuildResponseParameters')) {
    function omoDecisionMajorityJudgmentBuildResponseParameters(array $scoreMap, array $proposalMeta = [], $configOrParameters = null, $selectedWeight = null, $isAnonymous = false)
    {
        $config = omoDecisionMajorityJudgmentBuildConfig($configOrParameters);
        $mentions = (array)($config['all_mentions'] ?? omoDecisionMajorityJudgmentGetMentions(null, true));
        $normalizedScores = [];
        $scoreDetails = [];
        $weightPayload = omoDecisionBlockSettingsBuildResponseVoteWeightPayload($selectedWeight, $config);

        foreach ($scoreMap as $proposalId => $score) {
            $proposalId = (int)$proposalId;
            if ($proposalId <= 0) {
                continue;
            }

            $normalizedScore = omoDecisionMajorityJudgmentNormalizeScore($score);
            $normalizedScores[$proposalId] = $normalizedScore;
            $meta = $proposalMeta[$proposalId] ?? [];
            $scoreDetails[$proposalId] = [
                'score' => $normalizedScore,
                'mention' => (string)($mentions[$normalizedScore] ?? ''),
                'position' => (int)($meta['position'] ?? 0),
                'title' => (string)($meta['title'] ?? ''),
            ];
        }

        return [
            omoDecisionMajorityJudgmentGetMethodKey() => [
                'scores' => $normalizedScores,
                'details' => $scoreDetails,
                'vote_weight' => (string)$weightPayload['vote_weight'],
                'vote_weight_label' => (string)$weightPayload['vote_weight_label'],
                'is_anonymous' => !empty($isAnonymous) ? 1 : 0,
            ],
        ];
    }
}

if (!function_exists('omoDecisionMajorityJudgmentBuildStats')) {
    function omoDecisionMajorityJudgmentBuildStats(array $proposalObjects, $responses, $configOrParameters = null, $weighted = null)
    {
        $config = omoDecisionMajorityJudgmentBuildConfig($configOrParameters);
        $mentions = (array)($config['all_mentions'] ?? []);
        $useWeighted = $weighted === null ? !empty($config['vote_weight_enabled']) : (bool)$weighted;
        $scale = omoDecisionBlockSettingsGetVoteWeightScale(['options' => $config['vote_weight_options'] ?? []]);
        $effectiveScale = $useWeighted ? $scale : 1;
        $stats = [];

        foreach ($proposalObjects as $proposal) {
            $proposalId = (int)$proposal->getId();
            $stats[$proposalId] = [
                'distribution' => omoDecisionMajorityJudgmentGetEmptyDistribution(),
                'count' => 0,
                'counted_count' => 0,
                'no_opinion_count' => 0,
                'majority_score' => null,
                'majority_label' => '',
                'scale' => $effectiveScale,
            ];
        }

        foreach ($responses as $response) {
            $scores = omoDecisionMajorityJudgmentExtractScores($response);
            $weightSelection = $useWeighted
                ? omoDecisionMajorityJudgmentExtractVoteWeightSelection($response, $config)
                : ['units' => 1];
            $weightUnits = max(0, (int)($weightSelection['units'] ?? $effectiveScale));
            if ($weightUnits <= 0) {
                $weightUnits = $effectiveScale;
            }
            foreach ($scores as $proposalId => $score) {
                if (!isset($stats[$proposalId])) {
                    continue;
                }
                $stats[$proposalId]['distribution'][$score] += $weightUnits;
                $stats[$proposalId]['count'] += $weightUnits;
            }
        }

        foreach ($stats as $proposalId => $item) {
            if ($item['count'] <= 0) {
                continue;
            }

            $noOpinionCount = omoDecisionMajorityJudgmentGetNoOpinionCount($item['distribution'], $config);
            $countedCount = max(0, (int)array_sum(omoDecisionMajorityJudgmentGetCountedDistribution($item['distribution'], $config)));
            $stats[$proposalId]['no_opinion_count'] = $noOpinionCount;
            $stats[$proposalId]['counted_count'] = $countedCount;

            if ($countedCount <= 0) {
                continue;
            }

            $majorityScore = omoDecisionMajorityJudgmentResolveMajorityScore($item['distribution'], $config);
            if ($majorityScore === null) {
                continue;
            }

            $stats[$proposalId]['majority_score'] = $majorityScore;
            $stats[$proposalId]['majority_label'] = (string)($mentions[$majorityScore] ?? '');
        }

        return $stats;
    }
}
