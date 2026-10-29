<?php
require_once __DIR__ . '/../../ui_translations.php';

require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once dirname(__DIR__) . '/context.php';
require_once dirname(__DIR__) . '/registry.php';
require_once __DIR__ . '/shared.php';

use dbObject\DbObject;
use dbObject\DecisionGroup;
use dbObject\DecisionProcess;
use dbObject\DecisionProposal;

header('Content-Type: application/json; charset=UTF-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    omoDecisionModuleJsonResponse(405, ['status' => false, 'message' => omoDecisionUiT('methode_non_autorisee')]);
}

$context = omoDecisionResolveManagementContext($_POST);
if (empty($context['status'])) {
    omoDecisionModuleJsonResponse((int)($context['code'] ?? 400), ['status' => false, 'message' => omoDecisionUiT('contexte_de_consultation_invalide')]);
}

$decision = $context['decision'] ?? null;
$decisionGroup = $context['decisionGroup'] ?? null;
if (!$decision instanceof DecisionProcess || !$decisionGroup instanceof DecisionGroup) {
    omoDecisionModuleJsonResponse(404, ['status' => false, 'message' => omoDecisionUiT('consultation_introuvable')]);
}
if (DecisionProcess::normalizeEvaluationMethod($decisionGroup->get('evaluation_method')) !== DecisionProcess::METHOD_CONSULTATION_ONLY) {
    omoDecisionModuleJsonResponse(409, ['status' => false, 'message' => omoDecisionUiT('cette_prise_de_decision_ne_peut_pas_etre_transformee_depuis_une_consultation_seule')]);
}
if (!$decision->hasConsultationEnded() || $decision->hasEvaluationStarted()) {
    omoDecisionModuleJsonResponse(409, ['status' => false, 'message' => omoDecisionUiT('la_consultation_doit_etre_terminee_avant_de_choisir_un_mode_de_vote')]);
}

$targetMethod = DecisionProcess::normalizeEvaluationMethod((string)($_POST['target_method'] ?? ''));
if (!in_array($targetMethod, [DecisionProcess::METHOD_SIMPLE_VOTE, DecisionProcess::METHOD_MAJORITY_JUDGMENT, DecisionProcess::METHOD_CONSENT], true)) {
    omoDecisionModuleJsonResponse(400, ['status' => false, 'message' => omoDecisionUiT('mode_de_vote_invalide')]);
}
$definition = omoDecisionGetModuleDefinition($targetMethod, (int)$context['organizationId']);
if (empty($definition['available']) || empty($definition['shared_file']) || !is_file((string)$definition['shared_file'])) {
    omoDecisionModuleJsonResponse(403, ['status' => false, 'message' => omoDecisionUiT('ce_mode_de_vote_n_est_pas_disponible_dans_cette_organisation')]);
}
require_once $definition['shared_file'];

$consultationConfig = omoDecisionConsultationOnlyBuildConfig($decisionGroup);
$config = [
    'is_anonymous' => !empty($consultationConfig['is_anonymous']),
    'allow_anonymous_votes' => false,
    'allow_consultation_proposals' => !empty($consultationConfig['allow_consultation_proposals']),
    'allow_proposal_discussions' => !empty($consultationConfig['allow_proposal_discussions']),
    'show_live_results' => false,
    'proposal_content' => $consultationConfig['proposal_content'],
    'vote_weight_enabled' => false,
    'vote_weight_question' => '',
    'vote_weight_options' => [],
];

$parameters = $decisionGroup->get('parameters');
if ($targetMethod === DecisionProcess::METHOD_SIMPLE_VOTE) {
    $parameters = omoDecisionVoteMergeConfigIntoParameters($parameters, array_merge($config, [
        'choice_mode' => 'single',
        'max_choices' => 1,
    ]), ['created_from_module' => 'consultation_conversion']);
} elseif ($targetMethod === DecisionProcess::METHOD_MAJORITY_JUDGMENT) {
    $parameters = omoDecisionMajorityJudgmentMergeConfigIntoParameters($parameters, $config, ['created_from_module' => 'consultation_conversion']);
} else {
    $parameters = omoDecisionConsentMergeConfigIntoParameters($parameters, $config, ['created_from_module' => 'consultation_conversion']);
}

$pdo = DbObject::getPdo();
if (!$pdo) {
    omoDecisionModuleJsonResponse(500, ['status' => false, 'message' => omoDecisionUiT('connexion_a_la_base_impossible')]);
}

$ownsTransaction = !$pdo->inTransaction();
try {
    if ($ownsTransaction) {
        $pdo->beginTransaction();
    }

    $decisionGroup->set('evaluation_method', $targetMethod);
    $decisionGroup->set('parameters', $parameters);
    if (empty($decisionGroup->save()['status'])) {
        throw new RuntimeException('decision_group_save_failed');
    }

    $primaryGroup = $decision->getPrimaryGroup(false);
    if ($primaryGroup instanceof DecisionGroup && (int)$primaryGroup->getId() === (int)$decisionGroup->getId()) {
        $decision->set('evaluation_method', $targetMethod);
        $decision->set('parameters', $parameters);
        if (empty($decision->save()['status'])) {
            throw new RuntimeException('decision_save_failed');
        }
    }

    $methodKey = $targetMethod;
    foreach ($decisionGroup->getProposals(false) as $proposal) {
        if (!$proposal instanceof DecisionProposal) {
            continue;
        }
        $proposalParameters = omoDecisionModuleDecodeParameters($proposal->get('parameters'));
        $proposalParameters[$methodKey] = ['ballot_position' => max(1, (int)$proposal->get('position'))];
        $proposal->set('parameters', $proposalParameters);
        if (empty($proposal->save()['status'])) {
            throw new RuntimeException('proposal_save_failed');
        }
    }

    if ($ownsTransaction) {
        $pdo->commit();
    }
} catch (Throwable $exception) {
    if ($ownsTransaction && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    omoDecisionModuleJsonResponse(500, ['status' => false, 'message' => omoDecisionUiT('impossible_de_changer_le_mode_de_cette_consultation_pour_le_moment')]);
}

omoDecisionModuleJsonResponse(200, [
    'status' => true,
    'drawerTitle' => 'Prises de décision',
    'redirectUrl' => omoDecisionBuildEditorUrl(
        (int)$context['organizationId'],
        (int)$context['targetHolonId'],
        (int)$decision->getId(),
        $targetMethod,
        'manage',
        (int)$decisionGroup->getId()
    ),
]);
