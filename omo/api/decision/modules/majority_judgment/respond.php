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

if (DecisionProcess::normalizeEvaluationMethod($decisionGroup->get('evaluation_method')) !== DecisionProcess::METHOD_MAJORITY_JUDGMENT) {
    omoDecisionModuleJsonResponse(400, [
        'status' => false,
        'message' => omoDecisionUiT('ce_scrutin_n_utilise_pas_le_jugement_majoritaire'),
    ]);
}

$config = omoDecisionMajorityJudgmentBuildConfig($decisionGroup);
$draftRequested = !empty($_POST['draft']) && !empty($config['one_proposal_at_a_time']);
$responseIsAnonymous = !empty($config['is_anonymous'])
    || (!empty($config['allow_anonymous_votes']) && !empty($_POST['is_anonymous']));
$activeMentions = (array)($config['mentions'] ?? []);
$scoreMap = [];
$proposalMeta = [];
$activeProposals = $decisionGroup->getProposals(true);

foreach ($activeProposals as $proposal) {
    $proposalId = (int)$proposal->getId();
    $scoreValue = isset($_POST['scores'][$proposalId]) ? $_POST['scores'][$proposalId] : null;
    if ($scoreValue === null || $scoreValue === '') {
        if ($draftRequested) {
            continue;
        }
        omoDecisionModuleJsonResponse(400, [
            'status' => false,
            'message' => omoDecisionUiT('veuillez_attribuer_une_mention_a_chaque_proposition'),
        ]);
    }

    $normalizedScore = omoDecisionMajorityJudgmentNormalizeScore($scoreValue);
    if (!array_key_exists($normalizedScore, $activeMentions)) {
        omoDecisionModuleJsonResponse(400, [
            'status' => false,
            'message' => omoDecisionUiT('une_mention_selectionnee_n_est_pas_active_sur_ce_scrutin'),
        ]);
    }

    $scoreMap[$proposalId] = $normalizedScore;
    $proposalMeta[$proposalId] = [
        'position' => (int)$proposal->get('position'),
        'title' => trim((string)$proposal->get('title')),
    ];
}

if (count($scoreMap) === 0 && !$draftRequested) {
    omoDecisionModuleJsonResponse(400, [
        'status' => false,
        'message' => omoDecisionUiT('aucune_proposition_active_pour_ce_scrutin'),
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

$response->set('status', $draftRequested ? DecisionResponse::STATUS_DRAFT : DecisionResponse::STATUS_SUBMITTED);
$response->set('parameters', omoDecisionMajorityJudgmentBuildResponseParameters($scoreMap, $proposalMeta, $config, $_POST['vote_weight'] ?? null, $responseIsAnonymous));

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
