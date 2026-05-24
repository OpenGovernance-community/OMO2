<?php
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once dirname(__DIR__) . '/context.php';
require_once __DIR__ . '/shared.php';

use dbObject\DecisionParticipant;
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

if (DecisionProcess::normalizeEvaluationMethod($decision->get('evaluation_method')) !== DecisionProcess::METHOD_SIMPLE_VOTE) {
    omoDecisionModuleJsonResponse(400, [
        'status' => false,
        'message' => 'Ce scrutin n utilise pas le vote simple.',
    ]);
}

$voteConfig = omoDecisionVoteBuildConfig($decision->get('parameters'));
$choiceMode = (string)$voteConfig['choice_mode'];
$maxChoices = (int)$voteConfig['max_choices'];
$proposalIds = [];

if ($choiceMode === 'multiple') {
    $rawProposalIds = isset($_POST['proposal_ids']) && is_array($_POST['proposal_ids']) ? $_POST['proposal_ids'] : [];
    foreach ($rawProposalIds as $proposalId) {
        $proposalId = (int)$proposalId;
        if ($proposalId > 0) {
            $proposalIds[$proposalId] = $proposalId;
        }
    }
} else {
    $proposalId = (int)($_POST['proposal_id'] ?? 0);
    if ($proposalId > 0) {
        $proposalIds[$proposalId] = $proposalId;
    }
}

if (count($proposalIds) === 0) {
    omoDecisionModuleJsonResponse(400, [
        'status' => false,
        'message' => 'Veuillez selectionner au moins une proposition.',
    ]);
}

if ($choiceMode === 'multiple' && $maxChoices > 0 && count($proposalIds) > $maxChoices) {
    omoDecisionModuleJsonResponse(400, [
        'status' => false,
        'message' => 'Vous ne pouvez pas selectionner autant de propositions.',
    ]);
}

$selectedTitles = [];
$selectedPositions = [];
$matchedProposalIds = [];
foreach ($decision->getProposals(true) as $proposal) {
    $proposalId = (int)$proposal->getId();
    if (!isset($proposalIds[$proposalId])) {
        continue;
    }

    $matchedProposalIds[$proposalId] = $proposalId;
    $selectedPositions[] = (int)$proposal->get('position');
    $selectedTitles[] = trim((string)$proposal->get('title'));
}

if (count($matchedProposalIds) !== count($proposalIds)) {
    omoDecisionModuleJsonResponse(400, [
        'status' => false,
        'message' => 'Au moins une proposition selectionnee est invalide.',
    ]);
}

$response = DecisionResponse::findByDecisionAndParticipant((int)$decision->getId(), (int)$participant->getId());
if (!$response) {
    $response = new DecisionResponse();
    $response->set('IDdecision_process', (int)$decision->getId());
    $response->set('IDdecision_participant', (int)$participant->getId());
}

$orderedProposalIds = array_values($matchedProposalIds);
$response->set('status', DecisionResponse::STATUS_SUBMITTED);
$response->set('parameters', omoDecisionVoteBuildResponseParameters(
    $choiceMode,
    $orderedProposalIds,
    $selectedPositions,
    $selectedTitles
));

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
