<?php
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once dirname(__DIR__) . '/context.php';
require_once __DIR__ . '/shared.php';

use dbObject\DbObject;
use dbObject\DecisionGroup;
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
$selectedGroup = $context['decisionGroup'] ?? null;
$currentUserId = (int)$context['currentUserId'];
$organizationId = (int)$context['organizationId'];
$targetHolonId = (int)$context['targetHolonId'];
$decisionId = $decision instanceof DecisionProcess ? (int)$decision->getId() : 0;
$coreLocked = $decision instanceof DecisionProcess ? $decision->hasConsultationStarted() : false;
$startDatesLocked = $decision instanceof DecisionProcess ? $decision->hasSubmittedResponses() : false;

$processTitle = trim((string)($_POST['process_title'] ?? ''));
$processDescription = trim((string)($_POST['process_description'] ?? ''));
$title = trim((string)($_POST['title'] ?? ''));
$description = trim((string)($_POST['description'] ?? ''));
$decisionType = DecisionProcess::normalizeDecisionType((string)($_POST['decision_type'] ?? DecisionProcess::TYPE_DECISION));
$status = DecisionProcess::normalizeStatus((string)($_POST['status'] ?? DecisionProcess::STATUS_DRAFT));
$consultationStartAt = trim((string)($_POST['consultation_start_at'] ?? ''));
$consultationEndAt = trim((string)($_POST['consultation_end_at'] ?? ''));
$evaluationStartAt = trim((string)($_POST['evaluation_start_at'] ?? ''));
$evaluationEndAt = trim((string)($_POST['evaluation_end_at'] ?? ''));
$isAnonymous = !empty($_POST['is_anonymous']);
$allowConsultationProposals = !empty($_POST['allow_consultation_proposals']);
$submittedMentionOptions = omoDecisionMajorityJudgmentBuildMentionOptionsFromInput(
    $_POST['mention_labels'] ?? [],
    $_POST['mention_active'] ?? []
);
$proposalItems = omoDecisionBuildProposalItemsFromInput(
    $_POST['proposals'] ?? [],
    $_POST['proposal_descriptions'] ?? [],
    $_POST['proposal_info_urls'] ?? []
);

if (!$coreLocked && $processTitle === '') {
    omoDecisionModuleJsonResponse(400, [
        'status' => false,
        'message' => 'Le titre du processus est obligatoire.',
    ]);
}

if (!$coreLocked && $title === '') {
    omoDecisionModuleJsonResponse(400, [
        'status' => false,
        'message' => 'Le titre du groupe est obligatoire.',
    ]);
}

if (!$coreLocked && count($proposalItems) < 2) {
    omoDecisionModuleJsonResponse(400, [
        'status' => false,
        'message' => 'Le jugement majoritaire a besoin d au moins deux propositions.',
    ]);
}

if ($decision instanceof DecisionProcess) {
    $existingMethod = $selectedGroup instanceof DecisionGroup
        ? DecisionProcess::normalizeEvaluationMethod($selectedGroup->get('evaluation_method'))
        : DecisionProcess::normalizeEvaluationMethod($decision->get('evaluation_method'));
    if ($existingMethod !== DecisionProcess::METHOD_MAJORITY_JUDGMENT) {
        omoDecisionModuleJsonResponse(400, [
            'status' => false,
            'message' => 'Cette prise de decision n utilise pas le module de jugement majoritaire.',
        ]);
    }
} else {
    $decision = new DecisionProcess();
    $decision->set('IDorganization', $organizationId);
    $decision->set('IDuser', $currentUserId);
    if ($targetHolonId > 0) {
        $decision->set('IDholon', $targetHolonId);
    }
    $decision->set('evaluation_method', DecisionProcess::METHOD_MAJORITY_JUDGMENT);
}

$currentConfig = $decision instanceof DecisionProcess
    ? omoDecisionMajorityJudgmentBuildConfig($selectedGroup instanceof DecisionGroup ? $selectedGroup : $decision->get('parameters'))
    : [
        'is_anonymous' => $isAnonymous,
        'allow_consultation_proposals' => $allowConsultationProposals,
        'mention_options' => $submittedMentionOptions,
    ];
$canEditSettings = !$coreLocked;
$configToSave = [
    'is_anonymous' => $canEditSettings ? $isAnonymous : !empty($currentConfig['is_anonymous']),
    'allow_consultation_proposals' => $canEditSettings ? $allowConsultationProposals : !empty($currentConfig['allow_consultation_proposals']),
    'mention_options' => $canEditSettings
        ? $submittedMentionOptions
        : (array)($currentConfig['mention_options'] ?? omoDecisionMajorityJudgmentGetDefaultMentionOptions()),
];
$normalizedConfigToSave = omoDecisionMajorityJudgmentBuildConfig($configToSave);
$countedScaleSize = count((array)($normalizedConfigToSave['counted_scores'] ?? []));
if ($canEditSettings && $countedScaleSize < 2) {
    omoDecisionModuleJsonResponse(400, [
        'status' => false,
        'message' => 'Le jugement majoritaire a besoin d au moins deux mentions actives hors centre.',
    ]);
}
$canEditProposals = !$coreLocked || (!$startDatesLocked && !empty($currentConfig['allow_consultation_proposals']));

if ($canEditProposals && count($proposalItems) < 2) {
    omoDecisionModuleJsonResponse(400, [
        'status' => false,
        'message' => 'Le jugement majoritaire a besoin d au moins deux propositions.',
    ]);
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

    $decision->set('status', $status);
    $decision->set('evaluation_method', DecisionProcess::METHOD_MAJORITY_JUDGMENT);
    if (!$coreLocked) {
        $decision->set('title', $processTitle);
        $decision->set('description', $processDescription !== '' ? $processDescription : null);
    }

    if (!$startDatesLocked) {
        $decision->set('consultation_start_at', $consultationStartAt !== '' ? $consultationStartAt : null);
        $decision->set('evaluation_start_at', $evaluationStartAt !== '' ? $evaluationStartAt : null);
    }

    $decision->set('consultation_end_at', $consultationEndAt !== '' ? $consultationEndAt : null);
    $decision->set('evaluation_end_at', $evaluationEndAt !== '' ? $evaluationEndAt : null);

    $decisionGroup = $selectedGroup instanceof DecisionGroup ? $selectedGroup : null;
    $primaryGroup = $decision instanceof DecisionProcess ? $decision->getPrimaryGroup(false) : null;
    $isPrimaryGroup = !$decisionGroup || ($primaryGroup instanceof DecisionGroup && (int)$primaryGroup->getId() === (int)$decisionGroup->getId());
    $existingParameters = omoDecisionModuleGetMethodParameters(
        $decisionGroup instanceof DecisionGroup ? $decisionGroup->get('parameters') : $decision->get('parameters'),
        omoDecisionMajorityJudgmentGetMethodKey()
    );
    $proposalCount = $canEditProposals
        ? count($proposalItems)
        : (int)($existingParameters['proposal_count'] ?? count($proposalItems));

    $parameters = omoDecisionMajorityJudgmentMergeConfigIntoParameters(
        $decisionGroup instanceof DecisionGroup ? $decisionGroup->get('parameters') : $decision->get('parameters'),
        $normalizedConfigToSave,
        [
            'proposal_count' => $proposalCount,
            'created_from_module' => 'majority_judgment',
        ]
    );
    $saveDecision = $decision->save();
    if (empty($saveDecision['status'])) {
        throw new RuntimeException('decision_save_failed');
    }

    $decisionId = (int)$decision->getId();
    if (!$decisionGroup instanceof DecisionGroup) {
        $decisionGroup = $decision->ensurePrimaryGroup();
        $isPrimaryGroup = true;
    }
    if (!$decisionGroup instanceof DecisionGroup || (int)$decisionGroup->getId() <= 0) {
        throw new RuntimeException('decision_group_save_failed');
    }
    if (!$coreLocked) {
        $decisionGroup->set('title', $title);
        $decisionGroup->set('description', $description !== '' ? $description : null);
        $decisionGroup->set('decision_type', $decisionType);
    }
    $decisionGroup->set('evaluation_method', DecisionProcess::METHOD_MAJORITY_JUDGMENT);
    $decisionGroup->set('parameters', $parameters);
    $saveDecisionGroup = $decisionGroup->save();
    if (empty($saveDecisionGroup['status'])) {
        throw new RuntimeException('decision_group_update_failed');
    }
    $decisionGroupId = (int)$decisionGroup->getId();

    if ($isPrimaryGroup) {
        $decision->set('decision_type', $decisionType);
        $decision->set('parameters', $parameters);
        $decision->set('evaluation_method', DecisionProcess::METHOD_MAJORITY_JUDGMENT);
        $saveDecisionMirror = $decision->save();
        if (empty($saveDecisionMirror['status'])) {
            throw new RuntimeException('decision_mirror_save_failed');
        }
    }

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
        foreach ($decisionGroup->getProposals(false) as $proposal) {
            if ((int)$proposal->get('active') !== 1) {
                continue;
            }
            $existingActiveProposals[] = $proposal;
        }

        foreach ($proposalItems as $index => $proposalItem) {
            $proposal = $existingActiveProposals[$index] ?? new DecisionProposal();
            $proposal->set('IDdecision_process', $decisionId);
            $proposal->set('IDdecision_group', $decisionGroupId);
            $proposal->set('title', (string)$proposalItem['title']);
            $proposal->set('description', $proposalItem['description'] ?? null);
            $proposal->set('info_url', $proposalItem['info_url'] ?? null);
            $proposal->set('position', $index + 1);
            $proposal->set('parameters', [
                omoDecisionMajorityJudgmentGetMethodKey() => [
                    'ballot_position' => $index + 1,
                ],
            ]);
            $proposal->set('active', 1);

            $saveProposal = $proposal->save();
            if (empty($saveProposal['status'])) {
                throw new RuntimeException('proposal_save_failed');
            }
        }

        for ($index = count($proposalItems); $index < count($existingActiveProposals); $index++) {
            $proposal = $existingActiveProposals[$index];
            $proposal->set('active', 0);
            $saveProposal = $proposal->save();
            if (empty($saveProposal['status'])) {
                throw new RuntimeException('proposal_archive_failed');
            }
        }
    }

    $inlineInvitationResult = omoDecisionPersistInlineInvitationDraft($decision, $context, $_POST);
    if (!is_array($inlineInvitationResult) || empty($inlineInvitationResult['status'])) {
        throw new InvalidArgumentException(
            trim((string)($inlineInvitationResult['message'] ?? 'Impossible d enregistrer les invitations pour le moment.'))
        );
    }

    $syncResult = $decision->syncParticipantsFromInvitations();
    if (!is_array($syncResult) || empty($syncResult['status'])) {
        throw new RuntimeException('participant_sync_failed');
    }

    $pdo->commit();
} catch (InvalidArgumentException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    omoDecisionModuleJsonResponse(422, [
        'status' => false,
        'message' => trim((string)$exception->getMessage()) !== ''
            ? trim((string)$exception->getMessage())
            : 'Impossible d enregistrer les invitations pour le moment.',
    ]);
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
    'redirectUrl' => omoDecisionBuildEditorUrl($organizationId, $targetHolonId, $decisionId, DecisionProcess::METHOD_MAJORITY_JUDGMENT, 'manage', $decisionGroupId),
    'drawerTitle' => 'Prises de decision',
]);
