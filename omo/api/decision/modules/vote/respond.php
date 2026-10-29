<?php
require_once __DIR__ . '/../../ui_translations.php';
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
        'message' => omoDecisionUiT('methode_non_autorisee'),
    ]);
}

$context = omoDecisionResolveEditorContext($_POST);
if (empty($context['status']) || (string)($context['intent'] ?? '') !== 'participate') {
    omoDecisionModuleJsonResponse((int)($context['code'] ?? 403), [
        'status' => false,
        'message' => omoDecisionUiT('vous_ne_pouvez_pas_participer_a_ce_scrutin'),
    ]);
}

$decision = $context['decision'];
if (!$decision instanceof DecisionProcess || !$decision->isParticipationOpen()) {
    omoDecisionModuleJsonResponse(403, [
        'status' => false,
        'message' => omoDecisionUiT('le_vote_n_est_pas_ouvert_pour_le_moment'),
    ]);
}

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
                'message' => omoDecisionUiT('impossible_de_preparer_votre_participation'),
            ]);
        }
    }
}

if (!$decision instanceof DecisionProcess || !$participant || (int)$participant->getId() <= 0) {
    omoDecisionModuleJsonResponse(403, [
        'status' => false,
        'message' => omoDecisionUiT('participant_introuvable_pour_ce_scrutin'),
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
        'message' => omoDecisionUiT('impossible_de_preparer_le_groupe_de_participation'),
    ]);
}

if (DecisionProcess::normalizeEvaluationMethod($decisionGroup->get('evaluation_method')) !== DecisionProcess::METHOD_SIMPLE_VOTE) {
    omoDecisionModuleJsonResponse(400, [
        'status' => false,
        'message' => omoDecisionUiT('ce_scrutin_n_utilise_pas_le_vote_simple'),
    ]);
}

$voteConfig = omoDecisionVoteBuildConfig($decisionGroup);
$draftRequested = !empty($_POST['draft']) && !empty($voteConfig['one_proposal_at_a_time']);
$responseIsAnonymous = !empty($voteConfig['is_anonymous'])
    || (!empty($voteConfig['allow_anonymous_votes']) && !empty($_POST['is_anonymous']));
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
        'message' => omoDecisionUiT('veuillez_selectionner_au_moins_une_proposition'),
    ]);
}

if ($choiceMode === 'multiple' && $maxChoices > 0 && count($proposalIds) > $maxChoices) {
    omoDecisionModuleJsonResponse(400, [
        'status' => false,
        'message' => omoDecisionUiT('vous_ne_pouvez_pas_selectionner_autant_de_propositions'),
    ]);
}

$selectedTitles = [];
$selectedPositions = [];
$matchedProposalIds = [];
foreach ($decisionGroup->getProposals(true) as $proposal) {
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
        'message' => omoDecisionUiT('au_moins_une_proposition_selectionnee_est_invalide'),
    ]);
}

$response = DecisionResponse::findByDecisionAndParticipant((int)$decision->getId(), (int)$participant->getId(), (int)$decisionGroup->getId());
if (
    $response instanceof DecisionResponse
    && DecisionResponse::normalizeStatus($response->get('status')) === DecisionResponse::STATUS_SUBMITTED
    && !$decision->areParticipantResponsesEditable()
) {
    omoDecisionModuleJsonResponse(403, [
        'status' => false,
        'message' => omoDecisionUiT('votre_reponse_a_deja_ete_soumise_et_ne_peut_plus_etre_modifiee'),
    ]);
}
if (!$response) {
    $response = new DecisionResponse();
    $response->set('IDdecision_process', (int)$decision->getId());
    $response->set('IDdecision_group', (int)$decisionGroup->getId());
    $response->set('IDdecision_participant', (int)$participant->getId());
}

$orderedProposalIds = array_values($matchedProposalIds);
$response->set('status', $draftRequested ? DecisionResponse::STATUS_DRAFT : DecisionResponse::STATUS_SUBMITTED);
$response->set('parameters', omoDecisionVoteBuildResponseParameters(
    $choiceMode,
    $orderedProposalIds,
    $selectedPositions,
    $selectedTitles,
    $_POST['vote_weight'] ?? null,
    $voteConfig,
    $responseIsAnonymous
));

$saveResult = $response->save();
if (empty($saveResult['status'])) {
    omoDecisionModuleJsonResponse(500, [
        'status' => false,
        'message' => omoDecisionUiT('impossible_d_enregistrer_votre_vote_pour_le_moment'),
    ]);
}

omoDecisionModuleJsonResponse(200, [
    'status' => true,
    'message' => $draftRequested ? 'Brouillon enregistre.' : 'Vote enregistre.',
    'redirectUrl' => omoDecisionBuildContextualEditorUrl($context, 'participate'),
    'drawerTitle' => 'Prises de decision',
]);
