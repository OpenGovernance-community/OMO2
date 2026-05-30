<?php
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once dirname(__DIR__) . '/context.php';
require_once __DIR__ . '/shared.php';

use dbObject\DecisionParticipant;
use dbObject\DecisionGroup;
use dbObject\DecisionProcess;
use dbObject\DecisionResponse;

header('Content-Type: application/json; charset=UTF-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    omoDecisionModuleJsonResponse(405, [
        'status' => false,
        'message' => 'Methode non autorisee.',
    ]);
}

$context = omoDecisionResolveEditorContext($_POST);
if (empty($context['status']) || (string)($context['intent'] ?? '') !== 'participate') {
    omoDecisionModuleJsonResponse((int)($context['code'] ?? 403), [
        'status' => false,
        'message' => 'Vous ne pouvez pas participer a ce scrutin.',
    ]);
}

$decision = $context['decision'];
$participant = $context['participant'] ?? null;
$currentUserId = (int)($context['currentUserId'] ?? 0);
if ($decision instanceof DecisionProcess && (!$participant || (int)$participant->getId() <= 0) && !empty($context['isOwner']) && $currentUserId > 0) {
    $participant = DecisionParticipant::findByDecisionAndUser((int)$decision->getId(), $currentUserId);
    if (!$participant) {
        $participant = new DecisionParticipant();
        $participant->set('IDdecision_process', (int)$decision->getId());
        $participant->set('IDuser', $currentUserId);
        $participant->set('role', DecisionParticipant::ROLE_OWNER);
        $participant->set('status', DecisionParticipant::STATUS_ACTIVE);
        $participant->set('active', 1);

        $saveParticipant = $participant->save();
        if (empty($saveParticipant['status'])) {
            omoDecisionModuleJsonResponse(500, [
                'status' => false,
                'message' => 'Impossible de preparer votre participation.',
            ]);
        }
    }
}

if (!$decision instanceof DecisionProcess || !$participant || (int)$participant->getId() <= 0) {
    omoDecisionModuleJsonResponse(403, [
        'status' => false,
        'message' => 'Participant introuvable pour ce scrutin.',
    ]);
}

$decisionGroup = ($context['decisionGroup'] ?? null) instanceof DecisionGroup
    ? $context['decisionGroup']
    : $decision->getPrimaryGroup(false);
if (!$decisionGroup instanceof DecisionGroup && $decision instanceof DecisionProcess) {
    $decisionGroup = $decision->ensurePrimaryGroup();
}
if (!$decisionGroup || (int)$decisionGroup->getId() <= 0) {
    omoDecisionModuleJsonResponse(500, [
        'status' => false,
        'message' => 'Impossible de preparer le groupe de participation.',
    ]);
}

if (DecisionProcess::normalizeEvaluationMethod($decisionGroup->get('evaluation_method')) !== DecisionProcess::METHOD_MAJORITY_JUDGMENT) {
    omoDecisionModuleJsonResponse(400, [
        'status' => false,
        'message' => 'Ce scrutin n utilise pas le jugement majoritaire.',
    ]);
}

$scoreMap = [];
$proposalMeta = [];
$activeProposals = $decisionGroup->getProposals(true);

foreach ($activeProposals as $proposal) {
    $proposalId = (int)$proposal->getId();
    $scoreValue = isset($_POST['scores'][$proposalId]) ? $_POST['scores'][$proposalId] : null;
    if ($scoreValue === null || $scoreValue === '') {
        omoDecisionModuleJsonResponse(400, [
            'status' => false,
            'message' => 'Veuillez attribuer une mention a chaque proposition.',
        ]);
    }

    $scoreMap[$proposalId] = omoDecisionMajorityJudgmentNormalizeScore($scoreValue);
    $proposalMeta[$proposalId] = [
        'position' => (int)$proposal->get('position'),
        'title' => trim((string)$proposal->get('title')),
    ];
}

if (count($scoreMap) === 0) {
    omoDecisionModuleJsonResponse(400, [
        'status' => false,
        'message' => 'Aucune proposition active pour ce scrutin.',
    ]);
}

$response = DecisionResponse::findByDecisionAndParticipant((int)$decision->getId(), (int)$participant->getId(), (int)$decisionGroup->getId());
if (!$response) {
    $response = new DecisionResponse();
    $response->set('IDdecision_process', (int)$decision->getId());
    $response->set('IDdecision_group', (int)$decisionGroup->getId());
    $response->set('IDdecision_participant', (int)$participant->getId());
}

$response->set('status', DecisionResponse::STATUS_SUBMITTED);
$response->set('parameters', omoDecisionMajorityJudgmentBuildResponseParameters($scoreMap, $proposalMeta));

$saveResult = $response->save();
if (empty($saveResult['status'])) {
    omoDecisionModuleJsonResponse(500, [
        'status' => false,
        'message' => 'Impossible d enregistrer votre vote pour le moment.',
    ]);
}

omoDecisionModuleJsonResponse(200, [
    'status' => true,
    'message' => 'Vote enregistre.',
    'redirectUrl' => omoDecisionBuildContextualEditorUrl($context, 'participate'),
    'drawerTitle' => 'Prises de decision',
]);
