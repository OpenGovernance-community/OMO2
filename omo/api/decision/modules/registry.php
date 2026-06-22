<?php
require_once __DIR__ . '/context.php';

use dbObject\DecisionProcess;

if (!function_exists('omoDecisionGetModuleRegistry')) {
    function omoDecisionGetModuleRegistry()
    {
        static $registry = null;

        if ($registry !== null) {
            return $registry;
        }

        $registry = [
            DecisionProcess::METHOD_SIMPLE_VOTE => [
                'key' => DecisionProcess::METHOD_SIMPLE_VOTE,
                'available' => true,
                'label_key' => 'decisions.edit.method.simple_vote.label',
                'description_key' => 'decisions.edit.method.simple_vote.description',
                'shared_file' => __DIR__ . '/vote/shared.php',
                'editor_file' => __DIR__ . '/vote/module.php',
                'source_lang_function' => 'omoDecisionVoteModuleGetSourceLang',
                'render_function' => 'omoDecisionVoteModuleRender',
            ],
            DecisionProcess::METHOD_MAJORITY_JUDGMENT => [
                'key' => DecisionProcess::METHOD_MAJORITY_JUDGMENT,
                'available' => true,
                'label_key' => 'decisions.edit.method.majority_judgment.label',
                'description_key' => 'decisions.edit.method.majority_judgment.description',
                'shared_file' => __DIR__ . '/majority_judgment/shared.php',
                'editor_file' => __DIR__ . '/majority_judgment/module.php',
                'source_lang_function' => 'omoDecisionMajorityJudgmentModuleGetSourceLang',
                'render_function' => 'omoDecisionMajorityJudgmentModuleRender',
            ],
            DecisionProcess::METHOD_CONSENT => [
                'key' => DecisionProcess::METHOD_CONSENT,
                'available' => true,
                'label_key' => 'decisions.edit.method.consent.label',
                'description_key' => 'decisions.edit.method.consent.description',
                'shared_file' => __DIR__ . '/consent/shared.php',
                'editor_file' => __DIR__ . '/consent/module.php',
                'source_lang_function' => 'omoDecisionConsentModuleGetSourceLang',
                'render_function' => 'omoDecisionConsentModuleRender',
            ],
        ];

        return $registry;
    }
}

if (!function_exists('omoDecisionGetModuleDefinition')) {
    function omoDecisionGetModuleDefinition($method)
    {
        $registry = omoDecisionGetModuleRegistry();
        $method = DecisionProcess::normalizeEvaluationMethod($method);
        return $registry[$method] ?? null;
    }
}
