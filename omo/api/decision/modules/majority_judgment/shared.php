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
                'distribution' => [0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0],
                'count' => 0,
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

            $threshold = (int)ceil($item['count'] / 2);
            $running = 0;
            $majorityScore = 0;
            for ($score = 0; $score <= 6; $score++) {
                $running += (int)$item['distribution'][$score];
                if ($running >= $threshold) {
                    $majorityScore = $score;
                    break;
                }
            }

            $stats[$proposalId]['majority_score'] = $majorityScore;
            $stats[$proposalId]['majority_label'] = (string)$mentions[$majorityScore];
        }

        return $stats;
    }
}
