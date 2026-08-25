<?php
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once dirname(__DIR__) . '/context.php';
require_once __DIR__ . '/shared.php';
require_once dirname(__DIR__) . '/consultation_only/shared.php';
require_once dirname(__DIR__, 5) . '/common/notification_center.php';

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
$createGroupRequested = $decision instanceof DecisionProcess && trim((string)($_POST['group_action'] ?? '')) === 'create';
if ($createGroupRequested) {
    $selectedGroup = null;
}
$currentUserId = (int)$context['currentUserId'];
$organizationId = (int)$context['organizationId'];
$targetHolonId = (int)$context['targetHolonId'];
$decisionId = $decision instanceof DecisionProcess ? (int)$decision->getId() : 0;
$coreLocked = $decision instanceof DecisionProcess ? $decision->hasEvaluationStarted() : false;
$startDatesLocked = $coreLocked || ($decision instanceof DecisionProcess && $decision->hasSubmittedResponses());
$evaluationMethod = DecisionProcess::normalizeEvaluationMethod((string)($_POST['evaluation_method'] ?? DecisionProcess::METHOD_SIMPLE_VOTE));
if (!in_array($evaluationMethod, [DecisionProcess::METHOD_SIMPLE_VOTE, DecisionProcess::METHOD_CONSULTATION_ONLY], true)) {
    omoDecisionModuleJsonResponse(400, ['status' => false, 'message' => 'Methode de scrutin invalide.']);
}
$consultationOnly = $evaluationMethod === DecisionProcess::METHOD_CONSULTATION_ONLY;
$methodKey = $consultationOnly ? omoDecisionConsultationOnlyGetMethodKey() : omoDecisionVoteGetMethodKey();
if ($createGroupRequested && $coreLocked) {
    omoDecisionModuleJsonResponse(409, ['status' => false, 'message' => 'Il n’est plus possible d’ajouter une question après le début du vote.']);
}

$processTitle = trim((string)($_POST['process_title'] ?? ''));
$processDescription = trim((string)($_POST['process_description'] ?? ''));
$title = trim((string)($_POST['title'] ?? ''));
$description = trim((string)($_POST['description'] ?? ''));
$decisionType = DecisionProcess::normalizeDecisionType((string)($_POST['decision_type'] ?? DecisionProcess::TYPE_DECISION));
$decisionType = $consultationOnly ? DecisionProcess::TYPE_CONSULTATION : $decisionType;
$visibilityType = DecisionProcess::normalizeVisibilityType((string)($_POST['visibility_type'] ?? DecisionProcess::getDefaultVisibilityType()));
$status = DecisionProcess::normalizeStatus((string)($_POST['status'] ?? DecisionProcess::STATUS_DRAFT));
$consultationStartAt = trim((string)($_POST['consultation_start_at'] ?? ''));
$consultationEndAt = trim((string)($_POST['consultation_end_at'] ?? ''));
$evaluationStartAt = trim((string)($_POST['evaluation_start_at'] ?? ''));
$evaluationEndAt = trim((string)($_POST['evaluation_end_at'] ?? ''));
$choiceMode = omoDecisionVoteNormalizeChoiceMode($_POST['choice_mode'] ?? 'single');
$maxChoices = omoDecisionVoteNormalizeMaxChoices($_POST['max_choices'] ?? 1, $choiceMode);
$isAnonymous = !empty($_POST['is_anonymous']);
$allowAnonymousVotes = !empty($_POST['allow_anonymous_votes']);
$allowConsultationProposals = !empty($_POST['allow_consultation_proposals']);
$allowProposalDiscussions = !empty($_POST['allow_proposal_discussions']);
$showLiveResults = !empty($_POST['show_live_results']);
$proposalContentInput = omoDecisionNormalizeProposalContent([
    'title' => !empty($_POST['proposal_content_title']),
    'description' => !empty($_POST['proposal_content_description']),
    'url' => !empty($_POST['proposal_content_url']),
]);
$voteWeightConfig = omoDecisionBlockSettingsBuildVoteWeightConfig([
    'enabled' => !empty($_POST['vote_weight_enabled']),
    'question' => $_POST['vote_weight_question'] ?? '',
    'options' => $_POST['vote_weight_options_json'] ?? [],
]);
$proposalItems = omoDecisionBuildProposalItemsFromInput(
    $_POST['proposals'] ?? [],
    $_POST['proposal_descriptions'] ?? [],
    $_POST['proposal_info_urls'] ?? [],
    $_POST['proposal_ids'] ?? [],
    $proposalContentInput
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

if ($decision instanceof DecisionProcess) {
    $existingMethod = $selectedGroup instanceof DecisionGroup
        ? DecisionProcess::normalizeEvaluationMethod($selectedGroup->get('evaluation_method'))
        : DecisionProcess::normalizeEvaluationMethod($decision->get('evaluation_method'));
    if (!$createGroupRequested && $existingMethod !== $evaluationMethod) {
        omoDecisionModuleJsonResponse(400, [
            'status' => false,
            'message' => 'Cette prise de decision n utilise pas ce module.',
        ]);
    }
} else {
    $decision = new DecisionProcess();
    $decision->set('IDorganization', $organizationId);
    $decision->set('IDuser', $currentUserId);
    if ($targetHolonId > 0) {
        $decision->set('IDholon', $targetHolonId);
    }
    $decision->set('evaluation_method', $evaluationMethod);
}

if ($consultationOnly && !in_array($status, [DecisionProcess::STATUS_DRAFT, DecisionProcess::STATUS_SCHEDULED, DecisionProcess::STATUS_CONSULTATION], true)) {
    omoDecisionModuleJsonResponse(400, [
        'status' => false,
        'message' => 'Une consultation seule ne peut pas entrer en phase de vote.',
    ]);
}

$resolvedVisibility = $decision->resolveVisibilityRuleInput($visibilityType);
if (!$coreLocked && ($resolvedVisibility['status'] ?? false) !== true) {
    omoDecisionModuleJsonResponse(400, [
        'status' => false,
        'message' => trim((string)($resolvedVisibility['text'] ?? 'Visibilite invalide pour cette prise de decision.')),
    ]);
}

$currentVoteConfig = $decision instanceof DecisionProcess
    ? ($consultationOnly
        ? omoDecisionConsultationOnlyBuildConfig($selectedGroup instanceof DecisionGroup ? $selectedGroup : $decision->get('parameters'))
        : omoDecisionVoteBuildConfig($selectedGroup instanceof DecisionGroup ? $selectedGroup : $decision->get('parameters')))
    : [
        'choice_mode' => $choiceMode,
        'max_choices' => $maxChoices,
        'is_anonymous' => $isAnonymous,
        'allow_anonymous_votes' => $allowAnonymousVotes,
        'allow_consultation_proposals' => $allowConsultationProposals,
        'allow_proposal_discussions' => $allowProposalDiscussions,
        'show_live_results' => $showLiveResults,
        'proposal_content' => $proposalContentInput,
        'vote_weight_enabled' => !empty($voteWeightConfig['enabled']),
        'vote_weight_question' => (string)$voteWeightConfig['question'],
        'vote_weight_options' => (array)$voteWeightConfig['options'],
    ];
if (!$consultationOnly && $decision instanceof DecisionProcess
    && $coreLocked
    && (
        $isAnonymous !== !empty($currentVoteConfig['is_anonymous'])
        || $allowAnonymousVotes !== !empty($currentVoteConfig['allow_anonymous_votes'])
    )) {
    omoDecisionModuleJsonResponse(409, [
        'status' => false,
        'message' => 'Les conditions d anonymat ne peuvent plus etre modifiees apres le debut du vote.',
    ]);
}
if (!$consultationOnly && $decision instanceof DecisionProcess
    && !$coreLocked
    && !empty($currentVoteConfig['is_anonymous'])
    && !$isAnonymous
    && !$decision->canEnableNamedVote()) {
    omoDecisionModuleJsonResponse(409, [
        'status' => false,
        'message' => 'Le vote nominatif ne peut plus etre active apres une participation.',
    ]);
}
$canEditProposals = !$coreLocked;
$allowsEmptyProposalList = omoDecisionCanSaveEmptyConsultationProposalList(
    !$coreLocked ? $allowConsultationProposals : !empty($currentVoteConfig['allow_consultation_proposals']),
    !$startDatesLocked ? $consultationStartAt : $decision->get('consultation_start_at'),
    $consultationEndAt
);

if (!$consultationOnly && $canEditProposals && count($proposalItems) < 2 && !(count($proposalItems) === 0 && $allowsEmptyProposalList)) {
    omoDecisionModuleJsonResponse(400, [
        'status' => false,
        'message' => count($proposalItems) === 0 && (!$coreLocked ? $allowConsultationProposals : !empty($currentVoteConfig['allow_consultation_proposals']))
            ? 'La creation sans proposition exige une periode de consultation complete.'
            : 'Un vote simple a besoin d au moins deux propositions.',
    ]);
}

if (!$coreLocked && $choiceMode === 'multiple' && $maxChoices > 0 && $maxChoices > count($proposalItems)) {
    $maxChoices = max(count($proposalItems), 1);
}

$pdo = DbObject::getPdo();
if (!$pdo) {
    omoDecisionModuleJsonResponse(500, [
        'status' => false,
        'message' => 'Connexion a la base impossible.',
    ]);
}

$newProposalIds = [];
$ownsTransaction = !$pdo->inTransaction();
try {
    if ($ownsTransaction) {
        $pdo->beginTransaction();
    }

    $decision->set('status', $status);
    $decision->set('evaluation_method', $evaluationMethod);
    if (!$coreLocked) {
        $decision->set('title', $processTitle);
        $decision->set('description', $processDescription !== '' ? $processDescription : null);
        $decision->set('visibility_type', (string)($resolvedVisibility['type'] ?? DecisionProcess::getDefaultVisibilityType()));
    }

    if (!$startDatesLocked) {
        $decision->set('consultation_start_at', $consultationStartAt !== '' ? $consultationStartAt : null);
        $decision->set('consultation_end_at', $consultationEndAt !== '' ? $consultationEndAt : null);
        $decision->set('evaluation_start_at', !$consultationOnly && $evaluationStartAt !== '' ? $evaluationStartAt : null);
        $decision->set('evaluation_end_at', !$consultationOnly && $evaluationEndAt !== '' ? $evaluationEndAt : null);
    }

    $decisionGroup = $selectedGroup instanceof DecisionGroup ? $selectedGroup : null;
    $primaryGroup = $decision instanceof DecisionProcess ? $decision->getPrimaryGroup(false) : null;
    $createAdditionalGroup = $createGroupRequested && $primaryGroup instanceof DecisionGroup;
    $isPrimaryGroup = (!$decisionGroup && !$createAdditionalGroup) || ($primaryGroup instanceof DecisionGroup && (int)$primaryGroup->getId() === (int)$decisionGroup->getId());
    $existingVoteParameters = omoDecisionModuleGetMethodParameters(
        $decisionGroup instanceof DecisionGroup ? $decisionGroup->get('parameters') : $decision->get('parameters'),
        $methodKey
    );
    $proposalCount = $canEditProposals
        ? count($proposalItems)
        : (int)($existingVoteParameters['proposal_count'] ?? count($proposalItems));
    $extraParameters = [
        'proposal_count' => $proposalCount,
        'created_from_module' => $consultationOnly ? 'consultation_only' : 'vote',
    ];

    if (!$coreLocked) {
        $configToSave = [
            'choice_mode' => $choiceMode,
            'max_choices' => $choiceMode === 'multiple' ? $maxChoices : 1,
            'is_anonymous' => $isAnonymous,
            'allow_anonymous_votes' => $allowAnonymousVotes,
            'allow_consultation_proposals' => $allowConsultationProposals,
            'allow_proposal_discussions' => $allowProposalDiscussions,
            'show_live_results' => $showLiveResults,
            'proposal_content' => $proposalContentInput,
            'vote_weight_enabled' => !empty($voteWeightConfig['enabled']),
            'vote_weight_question' => (string)$voteWeightConfig['question'],
            'vote_weight_options' => (array)$voteWeightConfig['options'],
        ];
        $parameters = $consultationOnly
            ? omoDecisionConsultationOnlyMergeConfigIntoParameters($decisionGroup instanceof DecisionGroup ? $decisionGroup->get('parameters') : $decision->get('parameters'), $configToSave, $extraParameters)
            : omoDecisionVoteMergeConfigIntoParameters($decisionGroup instanceof DecisionGroup ? $decisionGroup->get('parameters') : $decision->get('parameters'), $configToSave, $extraParameters);
    } else {
        $parameters = omoDecisionModuleDecodeParameters($decisionGroup instanceof DecisionGroup ? $decisionGroup->get('parameters') : $decision->get('parameters'));
        $lockedVoteParameters = omoDecisionModuleGetMethodParameters($parameters, $methodKey);
        foreach ($extraParameters as $extraKey => $extraValue) {
            $lockedVoteParameters[$extraKey] = $extraValue;
        }
        $parameters[$methodKey] = $lockedVoteParameters;
    }

    $saveDecision = $decision->save();
    if (empty($saveDecision['status'])) {
        throw new RuntimeException('decision_save_failed');
    }

    $decisionId = (int)$decision->getId();
    if (!$decisionGroup instanceof DecisionGroup) {
        if ($createAdditionalGroup) {
            $decisionGroup = $decision->addDecisionGroup($evaluationMethod, $decisionType, $title, $description !== '' ? $description : null);
            $isPrimaryGroup = false;
        } else {
            $decisionGroup = $decision->ensurePrimaryGroup();
            $isPrimaryGroup = true;
        }
    }
    if (!$decisionGroup instanceof DecisionGroup || (int)$decisionGroup->getId() <= 0) {
        throw new RuntimeException('decision_group_save_failed');
    }
    if (!$coreLocked) {
        $decisionGroup->set('title', $title);
        $decisionGroup->set('description', $description !== '' ? $description : null);
        $decisionGroup->set('decision_type', $decisionType);
    }
    $decisionGroup->set('evaluation_method', $evaluationMethod);
    $decisionGroup->set('parameters', $parameters);
    $saveDecisionGroup = $decisionGroup->save();
    if (empty($saveDecisionGroup['status'])) {
        throw new RuntimeException('decision_group_update_failed');
    }
    $decisionGroupId = (int)$decisionGroup->getId();

    if ($isPrimaryGroup) {
        $decision->set('decision_type', $decisionType);
        $decision->set('parameters', $parameters);
        $decision->set('evaluation_method', $evaluationMethod);
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
            $existingActiveProposals[(int)$proposal->getId()] = $proposal;
        }

        $savedProposalIds = [];
        foreach ($proposalItems as $index => $proposalItem) {
            $proposalId = (int)($proposalItem['id'] ?? 0);
            if ($proposalId > 0 && !isset($existingActiveProposals[$proposalId])) {
                throw new RuntimeException('proposal_context_mismatch');
            }
            $proposal = $proposalId > 0 ? $existingActiveProposals[$proposalId] : new DecisionProposal();
            $proposal->set('IDdecision_process', $decisionId);
            $proposal->set('IDdecision_group', $decisionGroupId);
            if ($proposalId <= 0) {
                $proposal->set('IDuser_author', $currentUserId > 0 ? $currentUserId : null);
            }
            $proposal->set('title', (string)$proposalItem['title']);
            $proposal->set('description', $proposalItem['description'] ?? null);
            $proposal->set('info_url', $proposalItem['info_url'] ?? null);
            $proposal->set('position', $index + 1);
            $proposalParameters = omoDecisionModuleDecodeParameters($proposal->get('parameters'));
            $proposalParameters[$methodKey] = ['ballot_position' => $index + 1];
            $proposal->set('parameters', $proposalParameters);
            $proposal->set('active', 1);

            $saveProposal = $proposal->save();
            if (empty($saveProposal['status'])) {
                throw new RuntimeException('proposal_save_failed');
            }
            if ($proposalId <= 0) {
                $newProposalIds[] = (int)$proposal->getId();
            }
            $savedProposalIds[(int)$proposal->getId()] = true;
        }

        foreach ($existingActiveProposals as $existingProposalId => $proposal) {
            if (isset($savedProposalIds[(int)$existingProposalId])) {
                continue;
            }
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

    if ($ownsTransaction) {
        $pdo->commit();
    }
} catch (InvalidArgumentException $exception) {
    if ($ownsTransaction && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    omoDecisionModuleJsonResponse(422, [
        'status' => false,
        'message' => trim((string)$exception->getMessage()) !== ''
            ? trim((string)$exception->getMessage())
            : 'Impossible d enregistrer les invitations pour le moment.',
    ]);
} catch (Throwable $exception) {
    if ($ownsTransaction && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    omoDecisionModuleJsonResponse(500, [
        'status' => false,
        'message' => 'Impossible d enregistrer ce scrutin pour le moment.',
    ]);
}

if (!empty($GLOBALS['omoDecisionDeferProposalNotifications'])) {
    $GLOBALS['omoDecisionDeferredProposalIds'] = array_values(array_unique(array_merge(
        (array)($GLOBALS['omoDecisionDeferredProposalIds'] ?? []),
        $newProposalIds
    )));
} else {
    foreach ($newProposalIds as $newProposalId) {
        $newProposal = new DecisionProposal();
        if ($newProposal->load($newProposalId)) {
            try {
                notificationCenterDispatchDecisionProposal($newProposal);
            } catch (Throwable $exception) {
                error_log('decision_proposal_notification_failed: ' . $exception->getMessage());
            }
        }
    }
}

omoDecisionModuleJsonResponse(200, [
    'status' => true,
    'message' => $context['decision'] instanceof DecisionProcess
        ? 'Scrutin mis a jour.'
        : 'Scrutin cree.',
    'decisionId' => $decisionId,
    'redirectUrl' => omoDecisionBuildEditorUrl($organizationId, $targetHolonId, $decisionId, $evaluationMethod, 'manage', $decisionGroupId),
    'drawerTitle' => 'Prises de decision',
]);
