<?php

require_once dirname(__DIR__) . '/import_common.php';
require_once __DIR__ . '/shared.php';

use dbObject\DecisionGroup;
use dbObject\DecisionProcess;

if (!function_exists('omoDecisionMajorityJudgmentImportBlock')) {
    function omoDecisionMajorityJudgmentImportBlock(DecisionProcess $decision, DecisionGroup $group, array $processBlueprint, array $blockBlueprint, $isPrimaryGroup = false)
    {
        $proposals = is_array($blockBlueprint['proposals'] ?? null) ? $blockBlueprint['proposals'] : [];
        if (count($proposals) < 2) {
            return [
                'status' => false,
                'message' => 'Le jugement majoritaire importe au moins deux propositions par bloc.',
            ];
        }

        $config = omoDecisionMajorityJudgmentBuildConfig($blockBlueprint['method_config'] ?? []);
        if (count((array)($config['counted_scores'] ?? [])) < 2) {
            $config = omoDecisionMajorityJudgmentBuildConfig([
                'mention_options' => omoDecisionMajorityJudgmentGetDefaultMentionOptions(),
                'is_anonymous' => !empty($config['is_anonymous']),
                'allow_consultation_proposals' => !empty($config['allow_consultation_proposals']),
            ]);
        }

        $parameters = omoDecisionMajorityJudgmentMergeConfigIntoParameters([], $config, [
            'proposal_count' => count($proposals),
            'created_from_module' => 'majority_judgment',
        ]);

        $group->set('decision_type', DecisionProcess::normalizeDecisionType((string)($blockBlueprint['decision_type'] ?? DecisionProcess::TYPE_DECISION)));
        $group->set('evaluation_method', DecisionProcess::METHOD_MAJORITY_JUDGMENT);
        $group->set('title', trim((string)($blockBlueprint['title'] ?? '')) !== '' ? trim((string)$blockBlueprint['title']) : 'Bloc');
        $group->set('description', trim((string)($blockBlueprint['description'] ?? '')) !== '' ? trim((string)$blockBlueprint['description']) : null);
        $group->set('parameters', $parameters);

        $saveGroup = $group->save();
        if (empty($saveGroup['status'])) {
            return [
                'status' => false,
                'message' => 'Impossible d enregistrer le bloc de jugement majoritaire importe.',
            ];
        }

        if ($isPrimaryGroup) {
            $decision->set('decision_type', DecisionProcess::normalizeDecisionType((string)($blockBlueprint['decision_type'] ?? DecisionProcess::TYPE_DECISION)));
            $decision->set('evaluation_method', DecisionProcess::METHOD_MAJORITY_JUDGMENT);
            $decision->set('parameters', $parameters);
            $saveDecision = $decision->save();
            if (empty($saveDecision['status'])) {
                return [
                    'status' => false,
                    'message' => 'Impossible de synchroniser le processus importe.',
                ];
            }
        }

        return omoDecisionImportSyncGroupProposals($decision, $group, $proposals, omoDecisionMajorityJudgmentGetMethodKey());
    }
}
