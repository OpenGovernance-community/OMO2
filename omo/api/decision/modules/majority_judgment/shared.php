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

if (!function_exists('omoDecisionMajorityJudgmentGetMentions')) {
    function omoDecisionMajorityJudgmentGetMentions()
    {
        return [
            0 => 'Aucunement',
            1 => 'Tres peu',
            2 => 'Pas assez',
            3 => 'Sans avis',
            4 => 'Suffisamment',
            5 => 'Correctement',
            6 => 'Parfaitement',
        ];
    }
}

if (!function_exists('omoDecisionMajorityJudgmentGetNoOpinionScore')) {
    function omoDecisionMajorityJudgmentGetNoOpinionScore()
    {
        return 3;
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

if (!function_exists('omoDecisionMajorityJudgmentGetCountedDistribution')) {
    function omoDecisionMajorityJudgmentGetCountedDistribution(array $distribution)
    {
        $normalized = omoDecisionMajorityJudgmentNormalizeDistribution($distribution);
        $normalized[omoDecisionMajorityJudgmentGetNoOpinionScore()] = 0;
        return $normalized;
    }
}

if (!function_exists('omoDecisionMajorityJudgmentGetNoOpinionCount')) {
    function omoDecisionMajorityJudgmentGetNoOpinionCount(array $distribution)
    {
        $normalized = omoDecisionMajorityJudgmentNormalizeDistribution($distribution);
        return (int)($normalized[omoDecisionMajorityJudgmentGetNoOpinionScore()] ?? 0);
    }
}

if (!function_exists('omoDecisionMajorityJudgmentResolveMajorityScore')) {
    function omoDecisionMajorityJudgmentResolveMajorityScore(array $distribution)
    {
        $normalizedDistribution = omoDecisionMajorityJudgmentGetCountedDistribution($distribution);
        $totalCount = array_sum($normalizedDistribution);
        if ($totalCount <= 0) {
            return null;
        }

        $threshold = (int)ceil($totalCount / 2);
        $running = 0;
        for ($score = 0; $score <= 6; $score++) {
            $running += (int)$normalizedDistribution[$score];
            if ($running >= $threshold) {
                return $score;
            }
        }

        return 6;
    }
}

if (!function_exists('omoDecisionMajorityJudgmentCompareStats')) {
    function omoDecisionMajorityJudgmentCompareStats(array $leftStat, array $rightStat)
    {
        $leftDistribution = omoDecisionMajorityJudgmentGetCountedDistribution((array)($leftStat['distribution'] ?? []));
        $rightDistribution = omoDecisionMajorityJudgmentGetCountedDistribution((array)($rightStat['distribution'] ?? []));
        $leftRemaining = array_sum($leftDistribution);
        $rightRemaining = array_sum($rightDistribution);

        if ($leftRemaining <= 0 || $rightRemaining <= 0) {
            if ($leftRemaining === $rightRemaining) {
                return 0;
            }

            return $rightRemaining <=> $leftRemaining;
        }

        while ($leftRemaining > 0 && $rightRemaining > 0) {
            $leftMajorityScore = omoDecisionMajorityJudgmentResolveMajorityScore($leftDistribution);
            $rightMajorityScore = omoDecisionMajorityJudgmentResolveMajorityScore($rightDistribution);

            if ($leftMajorityScore === null || $rightMajorityScore === null) {
                break;
            }

            if ($leftMajorityScore !== $rightMajorityScore) {
                return $rightMajorityScore <=> $leftMajorityScore;
            }

            if ((int)$leftDistribution[$leftMajorityScore] <= 0 || (int)$rightDistribution[$rightMajorityScore] <= 0) {
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

if (!function_exists('omoDecisionMajorityJudgmentBuildConfig')) {
    function omoDecisionMajorityJudgmentBuildConfig($decisionOrParameters)
    {
        $parameters = is_object($decisionOrParameters) && method_exists($decisionOrParameters, 'get')
            ? omoDecisionModuleDecodeParameters($decisionOrParameters->get('parameters'))
            : omoDecisionModuleDecodeParameters($decisionOrParameters);
        $methodParameters = omoDecisionModuleGetMethodParameters($parameters, omoDecisionMajorityJudgmentGetMethodKey());

        return [
            'is_anonymous' => !empty($methodParameters['is_anonymous']),
            'allow_consultation_proposals' => !empty($methodParameters['allow_consultation_proposals']),
            'scale_size' => 7,
            'mentions' => omoDecisionMajorityJudgmentGetMentions(),
        ];
    }
}

if (!function_exists('omoDecisionMajorityJudgmentMergeConfigIntoParameters')) {
    function omoDecisionMajorityJudgmentMergeConfigIntoParameters($value, array $config, array $extra = [])
    {
        $parameters = omoDecisionModuleDecodeParameters($value);
        $methodParameters = omoDecisionModuleGetMethodParameters($parameters, omoDecisionMajorityJudgmentGetMethodKey());

        $methodParameters['is_anonymous'] = !empty($config['is_anonymous']) ? 1 : 0;
        $methodParameters['allow_consultation_proposals'] = !empty($config['allow_consultation_proposals']) ? 1 : 0;
        $methodParameters['scale_size'] = 7;

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

if (!function_exists('omoDecisionMajorityJudgmentBuildResponseParameters')) {
    function omoDecisionMajorityJudgmentBuildResponseParameters(array $scoreMap, array $proposalMeta = [])
    {
        $mentions = omoDecisionMajorityJudgmentGetMentions();
        $normalizedScores = [];
        $scoreDetails = [];

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
                'mention' => (string)$mentions[$normalizedScore],
                'position' => (int)($meta['position'] ?? 0),
                'title' => (string)($meta['title'] ?? ''),
            ];
        }

        return [
            omoDecisionMajorityJudgmentGetMethodKey() => [
                'scores' => $normalizedScores,
                'details' => $scoreDetails,
            ],
        ];
    }
}

if (!function_exists('omoDecisionMajorityJudgmentBuildStats')) {
    function omoDecisionMajorityJudgmentBuildStats(array $proposalObjects, $responses)
    {
        $mentions = omoDecisionMajorityJudgmentGetMentions();
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
            ];
        }

        foreach ($responses as $response) {
            $scores = omoDecisionMajorityJudgmentExtractScores($response);
            foreach ($scores as $proposalId => $score) {
                if (!isset($stats[$proposalId])) {
                    continue;
                }
                $stats[$proposalId]['distribution'][$score]++;
                $stats[$proposalId]['count']++;
            }
        }

        foreach ($stats as $proposalId => $item) {
            if ($item['count'] <= 0) {
                continue;
            }

            $noOpinionCount = omoDecisionMajorityJudgmentGetNoOpinionCount($item['distribution']);
            $countedCount = max(0, (int)$item['count'] - $noOpinionCount);
            $stats[$proposalId]['no_opinion_count'] = $noOpinionCount;
            $stats[$proposalId]['counted_count'] = $countedCount;

            if ($countedCount <= 0) {
                continue;
            }

            $majorityScore = omoDecisionMajorityJudgmentResolveMajorityScore($item['distribution']);
            if ($majorityScore === null) {
                continue;
            }

            $stats[$proposalId]['majority_score'] = $majorityScore;
            $stats[$proposalId]['majority_label'] = (string)$mentions[$majorityScore];
        }

        return $stats;
    }
}
