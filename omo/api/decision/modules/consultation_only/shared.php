<?php

require_once dirname(__DIR__) . '/common.php';

use dbObject\DecisionProcess;

if (!function_exists('omoDecisionConsultationOnlyGetMethodKey')) {
    function omoDecisionConsultationOnlyGetMethodKey()
    {
        return DecisionProcess::METHOD_CONSULTATION_ONLY;
    }
}

if (!function_exists('omoDecisionConsultationOnlyBuildConfig')) {
    function omoDecisionConsultationOnlyBuildConfig($decisionOrParameters)
    {
        $config = [];
        $methodKey = omoDecisionConsultationOnlyGetMethodKey();
        $isConfigLikeArray = is_array($decisionOrParameters)
            && !array_key_exists($methodKey, $decisionOrParameters)
            && (
                array_key_exists('allow_consultation_proposals', $decisionOrParameters)
                || array_key_exists('is_anonymous', $decisionOrParameters)
                || array_key_exists('allow_proposal_discussions', $decisionOrParameters)
                || array_key_exists('proposal_content', $decisionOrParameters)
            );

        if ($isConfigLikeArray) {
            $config = $decisionOrParameters;
        } else {
            $parameters = is_object($decisionOrParameters) && method_exists($decisionOrParameters, 'get')
                ? omoDecisionModuleDecodeParameters($decisionOrParameters->get('parameters'))
                : omoDecisionModuleDecodeParameters($decisionOrParameters);
            $config = omoDecisionModuleGetMethodParameters($parameters, $methodKey);
        }

        return [
            'choice_mode' => 'single',
            'max_choices' => 1,
            'is_anonymous' => !array_key_exists('is_anonymous', $config) || !empty($config['is_anonymous']),
            'allow_anonymous_votes' => false,
            'allow_consultation_proposals' => !empty($config['allow_consultation_proposals']),
            'allow_proposal_discussions' => !array_key_exists('allow_proposal_discussions', $config) || !empty($config['allow_proposal_discussions']),
            'show_live_results' => false,
            'proposal_content' => omoDecisionNormalizeProposalContent($config['proposal_content'] ?? null),
            'vote_weight_enabled' => false,
            'vote_weight_question' => '',
            'vote_weight_options' => [],
            'vote_weight_options_text' => '',
        ];
    }
}

if (!function_exists('omoDecisionConsultationOnlyMergeConfigIntoParameters')) {
    function omoDecisionConsultationOnlyMergeConfigIntoParameters($value, array $config, array $extra = [])
    {
        $parameters = omoDecisionModuleDecodeParameters($value);
        $methodKey = omoDecisionConsultationOnlyGetMethodKey();
        $methodParameters = omoDecisionModuleGetMethodParameters($parameters, $methodKey);

        $methodParameters['allow_consultation_proposals'] = !empty($config['allow_consultation_proposals']) ? 1 : 0;
        $methodParameters['is_anonymous'] = !empty($config['is_anonymous']) ? 1 : 0;
        $methodParameters['allow_proposal_discussions'] = !empty($config['allow_proposal_discussions']) ? 1 : 0;
        $methodParameters['proposal_content'] = omoDecisionNormalizeProposalContent($config['proposal_content'] ?? ($methodParameters['proposal_content'] ?? null));

        foreach ($extra as $key => $extraValue) {
            $methodParameters[$key] = $extraValue;
        }

        $parameters[$methodKey] = $methodParameters;
        return $parameters;
    }
}
