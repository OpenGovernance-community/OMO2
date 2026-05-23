<?php
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once dirname(__DIR__) . '/context.php';
require_once __DIR__ . '/shared.php';

use dbObject\DbObject;
use dbObject\DecisionParticipant;
use dbObject\DecisionProcess;
use dbObject\DecisionProposal;

header('Content-Type: application/json; charset=UTF-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    omoDecisionModuleJsonResponse(405, [
        'status' => false,
        'message' => 'Methode non autorisee.',
    ]);
}

$context = omoDecisionResolveEditorContext($_POST);
if (empty($context['status'])) {
    omoDecisionModuleJsonResponse((int)($context['code'] ?? 400), [
        'status' => false,
        'message' => 'Contexte de creation invalide.',
    ]);
}

$decision = $context['decision'];
$currentUserId = (int)$context['currentUserId'];
$organizationId = (int)$context['organizationId'];
$targetHolonId = (int)$context['targetHolonId'];
$decisionId = $decision instanceof DecisionProcess ? (int)$decision->getId() : 0;
$coreLocked = $decision instanceof DecisionProcess ? $decision->hasConsultationStarted() : false;
$startDatesLocked = $decision instanceof DecisionProcess ? $decision->hasSubmittedResponses() : false;

$title = trim((string)($_POST['title'] ?? ''));
$description = trim((string)($_POST['description'] ?? ''));
$decisionType = DecisionProcess::normalizeDecisionType((string)($_POST['decision_type'] ?? DecisionProcess::TYPE_DECISION));
$status = DecisionProcess::normalizeStatus((string)($_POST['status'] ?? DecisionProcess::STATUS_DRAFT));
$consultationStartAt = trim((string)($_POST['consultation_start_at'] ?? ''));
$consultationEndAt = trim((string)($_POST['consultation_end_at'] ?? ''));
$evaluationStartAt = trim((string)($_POST['evaluation_start_at'] ?? ''));
$evaluationEndAt = trim((string)($_POST['evaluation_end_at'] ?? ''));
$choiceMode = omoDecisionVoteNormalizeChoiceMode($_POST['choice_mode'] ?? 'single');
$maxChoices = omoDecisionVoteNormalizeMaxChoices($_POST['max_choices'] ?? 1, $choiceMode);
$isAnonymous = !empty($_POST['is_anonymous']);
$allowConsultationProposals = !empty($_POST['allow_consultation_proposals']);
$proposalLines = isset($_POST['proposals']) && is_array($_POST['proposals'])
    ? omoDecisionModuleParseUniqueTextItems($_POST['proposals'])
    : omoDecisionModuleParseUniqueTextItems($_POST['proposals_text'] ?? '');

if (!$coreLocked && $title === '') {
    omoDecisionModuleJsonResponse(400, [
        'status' => false,
        'message' => 'Le titre du scrutin est obligatoire.',
    ]);
}

if (!$coreLocked && count($proposalLines) < 2) {
    omoDecisionModuleJsonResponse(400, [
        'status' => false,
        'message' => 'Un vote simple a besoin d au moins deux propositions.',
    ]);
}

if ($decision instanceof DecisionProcess) {
    $existingMethod = DecisionProcess::normalizeEvaluationMethod($decision->get('evaluation_method'));
    if ($existingMethod !== DecisionProcess::METHOD_SIMPLE_VOTE) {
        omoDecisionModuleJsonResponse(400, [
            'status' => false,
            'message' => 'Cette prise de decision n utilise pas le module de vote simple.',
        ]);
    }
} else {
    $decision = new DecisionProcess();
    $decision->set('IDorganization', $organizationId);
    $decision->set('IDuser', $currentUserId);
    if ($targetHolonId > 0) {
        $decision->set('IDholon', $targetHolonId);
    }
    $decision->set('evaluation_method', DecisionProcess::METHOD_SIMPLE_VOTE);
}

$currentVoteConfig = $decision instanceof DecisionProcess
    ? omoDecisionVoteBuildConfig($decision->get('parameters'))
    : [
        'choice_mode' => $choiceMode,
        'max_choices' => $maxChoices,
        'is_anonymous' => $isAnonymous,
        'allow_consultation_proposals' => $allowConsultationProposals,
    ];
$canEditProposals = !$coreLocked || (!$startDatesLocked && !empty($currentVoteConfig['allow_consultation_proposals']));

if ($canEditProposals && count($proposalLines) < 2) {
    omoDecisionModuleJsonResponse(400, [
        'status' => false,
        'message' => 'Un vote simple a besoin d au moins deux propositions.',
    ]);
}

if (!$coreLocked && $choiceMode === 'multiple' && $maxChoices > 0 && $maxChoices > count($proposalLines)) {
    $maxChoices = max(count($proposalLines), 1);
}

$pdo = DbObject::getPdo();
if (!$pdo) {
    omoDecisionModuleJsonResponse(500, [
        'status' => false,
        'message' => 'Connexion a la base impossible.',
    ]);
}

try {
    $pdo->beginTransaction();

    if (!$coreLocked) {
        $decision->set('title', $title);
        $decision->set('description', $description !== '' ? $description : null);
        $decision->set('decision_type', $decisionType);
    }

    $decision->set('status', $status);
    $decision->set('evaluation_method', DecisionProcess::METHOD_SIMPLE_VOTE);

    if (!$startDatesLocked) {
        $decision->set('consultation_start_at', $consultationStartAt !== '' ? $consultationStartAt : null);
        $decision->set('evaluation_start_at', $evaluationStartAt !== '' ? $evaluationStartAt : null);
    }

    $decision->set('consultation_end_at', $consultationEndAt !== '' ? $consultationEndAt : null);
    $decision->set('evaluation_end_at', $evaluationEndAt !== '' ? $evaluationEndAt : null);

    $existingVoteParameters = omoDecisionModuleGetMethodParameters($decision->get('parameters'), omoDecisionVoteGetMethodKey());
    $proposalCount = $canEditProposals
        ? count($proposalLines)
        : (int)($existingVoteParameters['proposal_count'] ?? count($proposalLines));
    $extraParameters = [
        'proposal_count' => $proposalCount,
        'created_from_module' => 'vote',
    ];

    if (!$coreLocked) {
        $parameters = omoDecisionVoteMergeConfigIntoParameters($decision->get('parameters'), [
            'choice_mode' => $choiceMode,
            'max_choices' => $choiceMode === 'multiple' ? $maxChoices : 1,
            'is_anonymous' => $isAnonymous,
            'allow_consultation_proposals' => $allowConsultationProposals,
        ], $extraParameters);
    } else {
        $parameters = omoDecisionModuleDecodeParameters($decision->get('parameters'));
        $lockedVoteParameters = omoDecisionModuleGetMethodParameters($parameters, omoDecisionVoteGetMethodKey());
        foreach ($extraParameters as $extraKey => $extraValue) {
            $lockedVoteParameters[$extraKey] = $extraValue;
        }
        $parameters[omoDecisionVoteGetMethodKey()] = $lockedVoteParameters;
    }

    $decision->set('parameters', $parameters);

    $saveDecision = $decision->save();
    if (empty($saveDecision['status'])) {
        throw new RuntimeException('decision_save_failed');
    }

    $decisionId = (int)$decision->getId();

    $ownerParticipant = DecisionParticipant::findByDecisionAndUser($decisionId, $currentUserId);
    if (!$ownerParticipant) {
        $ownerParticipant = new DecisionParticipant();
    }
    $ownerParticipant->set('IDdecision_process', $decisionId);
    $ownerParticipant->set('IDuser', $currentUserId);
    $ownerParticipant->set('role', DecisionParticipant::ROLE_OWNER);
    $ownerParticipant->set('status', DecisionParticipant::STATUS_ACTIVE);
    $ownerParticipant->set('active', 1);
    $saveParticipant = $ownerParticipant->save();
    if (empty($saveParticipant['status'])) {
        throw new RuntimeException('participant_save_failed');
    }

    if ($canEditProposals) {
        $existingActiveProposals = [];
        foreach ($decision->getProposals(false) as $proposal) {
            if ((int)$proposal->get('active') !== 1) {
                continue;
            }
            $existingActiveProposals[] = $proposal;
        }

        foreach ($proposalLines as $index => $proposalTitle) {
            $proposal = $existingActiveProposals[$index] ?? new DecisionProposal();
            $proposal->set('IDdecision_process', $decisionId);
            $proposal->set('title', $proposalTitle);
            $proposal->set('description', null);
            $proposal->set('position', $index + 1);
            $proposal->set('parameters', [
                omoDecisionVoteGetMethodKey() => [
                    'ballot_position' => $index + 1,
                ],
            ]);
            $proposal->set('active', 1);

            $saveProposal = $proposal->save();
            if (empty($saveProposal['status'])) {
                throw new RuntimeException('proposal_save_failed');
            }
        }

        for ($index = count($proposalLines); $index < count($existingActiveProposals); $index++) {
            $proposal = $existingActiveProposals[$index];
            $proposal->set('active', 0);
            $saveProposal = $proposal->save();
            if (empty($saveProposal['status'])) {
                throw new RuntimeException('proposal_archive_failed');
            }
        }
    }

    $syncResult = $decision->syncParticipantsFromInvitations();
    if (!is_array($syncResult) || empty($syncResult['status'])) {
        throw new RuntimeException('participant_sync_failed');
    }

    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    omoDecisionModuleJsonResponse(500, [
        'status' => false,
        'message' => 'Impossible d enregistrer ce scrutin pour le moment.',
    ]);
}

omoDecisionModuleJsonResponse(200, [
    'status' => true,
    'message' => $context['decision'] instanceof DecisionProcess
        ? 'Scrutin mis a jour.'
        : 'Scrutin cree.',
    'decisionId' => $decisionId,
    'redirectUrl' => omoDecisionBuildEditorUrl($organizationId, $targetHolonId, $decisionId, DecisionProcess::METHOD_SIMPLE_VOTE, 'manage'),
    'drawerTitle' => 'Prises de decision',
]);
