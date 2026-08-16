<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__) . '/modules/context.php';
require_once dirname(__DIR__) . '/modules/consent/shared.php';
require_once dirname(__DIR__) . '/modules/vote/shared.php';
require_once __DIR__ . '/shared.php';
require_once dirname(__DIR__) . '/params/shared.php';
require_once dirname(__DIR__, 4) . '/common/notification_center.php';

use dbObject\ChatMessage;
use dbObject\DbObject;
use dbObject\DecisionGovernanceAction;
use dbObject\DecisionGroup;
use dbObject\DecisionParticipant;
use dbObject\DecisionProcess;
use dbObject\DecisionProposal;
use dbObject\Holon;
use dbObject\ObjectVisibility;
use dbObject\Rule;

header('Content-Type: application/json; charset=UTF-8');

$respond = static function ($code, array $payload) {
    http_response_code((int)$code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
};

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    $respond(405, ['status' => false, 'message' => 'Methode non autorisee.']);
}

$context = omoDecisionResolveEditorContext($_POST);
if (empty($context['status'])) {
    $respond((int)($context['code'] ?? 400), [
        'status' => false,
        'message' => 'Contexte de creation invalide.',
    ]);
}

$decision = ($context['decision'] ?? null) instanceof DecisionProcess ? $context['decision'] : null;
$currentUserId = (int)($context['currentUserId'] ?? 0);
$organizationId = (int)($context['organizationId'] ?? 0);
$targetHolonId = (int)($context['targetHolonId'] ?? 0);
$wasExistingDecision = $decision instanceof DecisionProcess;
$decisionSettings = omoDecisionParamsGetConfig($context['organization'] ?? null);
$governanceSettings = $decisionSettings['governance'];
$existingGovernanceGroup = $wasExistingDecision ? $decision->getPrimaryGroup(false) : null;
$governanceMethod = $existingGovernanceGroup instanceof DecisionGroup
    ? DecisionProcess::normalizeEvaluationMethod($existingGovernanceGroup->get('evaluation_method'))
    : (string)($governanceSettings['evaluation_method'] ?? DecisionProcess::METHOD_CONSENT);
if (!in_array($governanceMethod, [DecisionProcess::METHOD_SIMPLE_VOTE, DecisionProcess::METHOD_CONSENT], true)) {
    $governanceMethod = DecisionProcess::METHOD_CONSENT;
}
$governanceConsentConfig = $governanceMethod === DecisionProcess::METHOD_CONSENT && $existingGovernanceGroup instanceof DecisionGroup
    ? omoDecisionConsentBuildConfig($existingGovernanceGroup)
    : [
        'is_anonymous' => !empty($governanceSettings['show_live_votes']) && !empty($governanceSettings['live_votes_anonymous']),
        'allow_anonymous_votes' => false,
        'allow_consultation_proposals' => false,
        'allow_proposal_discussions' => true,
        'show_live_results' => !empty($governanceSettings['show_live_votes']),
    ];
$governanceVoteConfig = $governanceMethod === DecisionProcess::METHOD_SIMPLE_VOTE && $existingGovernanceGroup instanceof DecisionGroup
    ? omoDecisionVoteBuildConfig($existingGovernanceGroup)
    : [
        'choice_mode' => 'single',
        'max_choices' => 1,
        'is_anonymous' => !empty($governanceSettings['show_live_votes']) && !empty($governanceSettings['live_votes_anonymous']),
        'allow_anonymous_votes' => false,
        'allow_consultation_proposals' => false,
        'allow_proposal_discussions' => true,
        'show_live_results' => !empty($governanceSettings['show_live_votes']),
    ];

if ($targetHolonId <= 0) {
    $respond(422, ['status' => false, 'message' => omoDecisionGovernanceT('governance.error.holon')]);
}
if (!$wasExistingDecision && empty($governanceSettings['enabled'])) {
    $respond(403, ['status' => false, 'message' => omoDecisionGovernanceT('governance.error.disabled')]);
}

$targetHolon = new Holon();
if (!$targetHolon->load($targetHolonId)
    || !(bool)$targetHolon->get('active')
    || !in_array((int)$targetHolon->get('IDtypeholon'), [1, 2], true)) {
    $respond(422, ['status' => false, 'message' => omoDecisionGovernanceT('governance.error.holon')]);
}

if ($wasExistingDecision) {
    if (!$decision->isGovernanceWorkflow()) {
        $respond(400, ['status' => false, 'message' => 'Cette prise de decision n est pas un processus hors reorg.']);
    }
    if ((int)$decision->get('IDuser') !== $currentUserId) {
        $respond(403, ['status' => false, 'message' => omoDecisionGovernanceT('governance.error.owner')]);
    }
    if ($decision->hasEvaluationStarted()) {
        $respond(409, ['status' => false, 'message' => omoDecisionGovernanceT('governance.error.locked')]);
    }
} else {
    $decision = new DecisionProcess();
    $decision->set('IDorganization', $organizationId);
    $decision->set('IDholon', $targetHolonId);
    $decision->set('IDuser', $currentUserId);
}

$processTitle = trim((string)($_POST['process_title'] ?? ''));
$processDescription = trim((string)($_POST['process_description'] ?? ''));
$consentQuestion = trim((string)($_POST['consent_question'] ?? ''));
if ($processTitle === '' || mb_strlen($processTitle, 'UTF-8') > 190 || $processDescription === '') {
    $respond(422, ['status' => false, 'message' => 'Le titre et l intention sont obligatoires.']);
}
if ($consentQuestion === '' || mb_strlen($consentQuestion, 'UTF-8') > 1000) {
    $respond(422, ['status' => false, 'message' => 'La question est obligatoire.']);
}

try {
    $consultationEnd = new DateTimeImmutable(trim((string)($_POST['consultation_end_at'] ?? '')));
    $evaluationEnd = new DateTimeImmutable(trim((string)($_POST['evaluation_end_at'] ?? '')));
} catch (Throwable $exception) {
    $respond(422, ['status' => false, 'message' => 'Les dates du processus sont invalides.']);
}
if ($evaluationEnd <= $consultationEnd) {
    $respond(422, ['status' => false, 'message' => 'La fin du vote doit suivre la fin de la consultation.']);
}

$blueprint = json_decode((string)($_POST['governance_blueprint'] ?? ''), true);
if (!is_array($blueprint) || count($blueprint) < 1 || count($blueprint) > 50) {
    $respond(422, ['status' => false, 'message' => 'Ajoutez au moins une proposition valide.']);
}

$rulesById = [];
foreach (Rule::findDefinedInHolon($targetHolonId) as $rule) {
    if ($rule instanceof Rule) {
        $rulesById[(int)$rule->getId()] = $rule;
    }
}
$rolesById = [];
$roleStatesById = [];
$contextOrganization = ($context['organization'] ?? null) instanceof \dbObject\Organization ? $context['organization'] : null;
foreach (DecisionGovernanceAction::findRolesInGovernanceContext($targetHolon) as $role) {
    $rolesById[(int)$role->getId()] = $role;
    $roleStatesById[(int)$role->getId()] = omoDecisionGovernanceBuildRoleClientData($role, $contextOrganization, $targetHolonId)['state'];
}

$existingProposals = [];
$existingActionsByProposal = [];
if ($wasExistingDecision) {
    foreach ($decision->getProposals(false) as $proposal) {
        if (!$proposal instanceof DecisionProposal || (int)$proposal->get('active') !== 1) {
            continue;
        }
        $proposalId = (int)$proposal->getId();
        $existingProposals[$proposalId] = $proposal;
        $existingActionsByProposal[$proposalId] = [];
        foreach ($proposal->getGovernanceActions() as $action) {
            if ($action instanceof DecisionGovernanceAction) {
                $existingActionsByProposal[$proposalId][(int)$action->getId()] = $action;
            }
        }
    }
}

$normalizedProposals = [];
$usedTargetIds = [];
foreach (array_values($blueprint) as $proposalIndex => $proposalInput) {
    if (!is_array($proposalInput)) {
        $respond(422, ['status' => false, 'message' => 'Une proposition est invalide.']);
    }
    $proposalId = (int)($proposalInput['id'] ?? 0);
    if ($proposalId > 0 && !isset($existingProposals[$proposalId])) {
        $respond(422, ['status' => false, 'message' => 'Une proposition ne correspond pas a ce scrutin.']);
    }
    if ($proposalId > 0 && !$existingProposals[$proposalId]->canBeEditedByUser($currentUserId)) {
        $respond(403, ['status' => false, 'message' => omoDecisionGovernanceT('governance.error.owner')]);
    }
    $actionInputs = is_array($proposalInput['actions'] ?? null) ? array_values($proposalInput['actions']) : [];
    if (count($actionInputs) < 1 || count($actionInputs) > 50) {
        $respond(422, ['status' => false, 'message' => 'Chaque proposition doit contenir au moins une modification.']);
    }

    $normalizedActions = [];
    $actionDescriptions = [];
    $ruleTitles = [];
    $suggestedTitles = [];
    foreach ($actionInputs as $actionIndex => $actionInput) {
        $actionType = is_array($actionInput) ? trim((string)($actionInput['type'] ?? '')) : '';
        $isRoleAction = in_array($actionType, [
            DecisionGovernanceAction::TYPE_HOLON_CREATE,
            DecisionGovernanceAction::TYPE_HOLON_UPDATE,
            DecisionGovernanceAction::TYPE_HOLON_DELETE,
        ], true);
        if ($isRoleAction) {
            $targetId = (int)($actionInput['targetId'] ?? 0);
            $isCreate = $actionType === DecisionGovernanceAction::TYPE_HOLON_CREATE;
            if (($isCreate && $targetId !== 0) || (!$isCreate && !isset($rolesById[$targetId]))) {
                $respond(422, ['status' => false, 'message' => 'Le role choisi n appartient pas a ce cercle.']);
            }
            if (!$isCreate && isset($usedTargetIds['role:' . $targetId])) {
                $respond(422, ['status' => false, 'message' => 'Un meme role ne peut pas etre modifie plusieurs fois dans ce scrutin.']);
            }
            if (!$isCreate) $usedTargetIds['role:' . $targetId] = true;
            $actionId = (int)($actionInput['id'] ?? 0);
            $existingAction = $actionId > 0 ? ($existingActionsByProposal[$proposalId][$actionId] ?? null) : null;
            if ($actionId > 0 && (!$existingAction instanceof DecisionGovernanceAction || (string)$existingAction->get('action_type') !== $actionType || (int)$existingAction->get('target_id') !== $targetId || (string)$existingAction->get('status') !== DecisionGovernanceAction::STATUS_PENDING)) {
                $respond(422, ['status' => false, 'message' => 'Une modification existante ne peut plus etre remplacee.']);
            }
            $beforeState = [];
            $afterState = [];
            $roleName = '';
            if ($isCreate) {
                $validation = DecisionGovernanceAction::validateRoleState((array)($actionInput['after'] ?? []), $targetHolon);
                if (empty($validation['status'])) $respond(422, ['status' => false, 'message' => (string)$validation['message']]);
                $afterState = (array)$validation['state'];
                $roleName = $afterState['name'];
                $suggestedTitles[] = 'Creer le role ' . $roleName;
                $actionDescriptions[] = '<h4>Creer le role ' . htmlspecialchars($roleName, ENT_QUOTES, 'UTF-8') . '</h4>' . DecisionGovernanceAction::buildRoleStateDescription($afterState);
            } else {
                $role = $rolesById[$targetId];
                $beforeState = $existingAction instanceof DecisionGovernanceAction ? DecisionGovernanceAction::normalizeState($existingAction->get('before_state')) : ($roleStatesById[$targetId] ?? DecisionGovernanceAction::captureRoleState($role));
                $roleName = (string)$beforeState['name'];
                if ($actionType === DecisionGovernanceAction::TYPE_HOLON_DELETE) {
                    $suggestedTitles[] = 'Supprimer le role ' . $roleName;
                    $actionDescriptions[] = '<h4>Supprimer le role ' . htmlspecialchars($roleName, ENT_QUOTES, 'UTF-8') . '</h4>' . DecisionGovernanceAction::buildRoleStateDescription($beforeState);
                } else {
                    $validation = DecisionGovernanceAction::validateRoleState((array)($actionInput['after'] ?? []), $targetHolon, $role);
                    if (empty($validation['status'])) $respond(422, ['status' => false, 'message' => (string)$validation['message']]);
                    $afterState = (array)$validation['state'];
                    if ($beforeState === $afterState) $respond(422, ['status' => false, 'message' => 'La modification du role ne contient aucun changement.']);
                    $suggestedTitles[] = 'Modifier le role ' . $roleName;
                    $actionDescriptions[] = '<h4>Modifier le role ' . htmlspecialchars($roleName, ENT_QUOTES, 'UTF-8') . '</h4>' . DecisionGovernanceAction::buildRoleUpdateDescription($beforeState, $afterState);
                }
            }
            $normalizedActions[] = ['id' => $actionId, 'existing' => $existingAction, 'action_type' => $actionType, 'target_id' => $targetId, 'target_type' => DecisionGovernanceAction::TARGET_HOLON, 'before' => $beforeState, 'after' => $afterState, 'position' => $actionIndex + 1];
            continue;
        }
        if (!DecisionGovernanceAction::isImplementedType($actionType)
            || !in_array($actionType, [
                DecisionGovernanceAction::TYPE_RULE_CREATE,
                DecisionGovernanceAction::TYPE_RULE_UPDATE,
                DecisionGovernanceAction::TYPE_RULE_DELETE,
            ], true)) {
            $respond(422, ['status' => false, 'message' => 'Cette modification n est pas encore disponible.']);
        }
        $targetId = (int)($actionInput['targetId'] ?? 0);
        $requiresExistingRule = $actionType !== DecisionGovernanceAction::TYPE_RULE_CREATE;
        if (!$requiresExistingRule && $targetId !== 0) {
            $respond(422, ['status' => false, 'message' => 'Une creation de regle ne peut pas cibler une regle existante.']);
        }
        if ($requiresExistingRule && ($targetId <= 0 || !isset($rulesById[$targetId]))) {
            $respond(422, ['status' => false, 'message' => 'La regle choisie n appartient pas a ce contexte.']);
        }
        if ($requiresExistingRule && isset($usedTargetIds[$targetId])) {
            $respond(422, ['status' => false, 'message' => 'Une meme regle ne peut pas etre modifiee ou supprimee plusieurs fois dans ce scrutin.']);
        }
        if ($requiresExistingRule) {
            $usedTargetIds[$targetId] = true;
        }

        $actionId = (int)($actionInput['id'] ?? 0);
        $existingAction = null;
        if ($actionId > 0) {
            $existingAction = $existingActionsByProposal[$proposalId][$actionId] ?? null;
            if (!$existingAction instanceof DecisionGovernanceAction
                || (string)$existingAction->get('action_type') !== $actionType
                || (int)$existingAction->get('target_id') !== $targetId
                || (string)$existingAction->get('status') !== DecisionGovernanceAction::STATUS_PENDING) {
                $respond(422, ['status' => false, 'message' => 'Une modification existante ne peut plus etre remplacee.']);
            }
        }

        $beforeState = [];
        $afterState = [];
        $ruleTitle = '';
        if ($actionType === DecisionGovernanceAction::TYPE_RULE_CREATE) {
            $validation = DecisionGovernanceAction::validateRuleCreate(
                is_array($actionInput['after'] ?? null) ? $actionInput['after'] : [],
                $targetHolonId
            );
            if (empty($validation['status'])) {
                $respond(422, ['status' => false, 'message' => (string)($validation['message'] ?? 'Creation invalide.')]);
            }
            $beforeState = $existingAction instanceof DecisionGovernanceAction
                ? DecisionGovernanceAction::normalizeState($existingAction->get('before_state'))
                : [];
            $afterState = (array)$validation['state'];
            $ruleTitle = trim((string)$afterState['title']);
            $suggestedTitles[] = 'Creer la regle ' . $ruleTitle;
            $actionDescriptions[] = '<h4>Creer la regle ' . htmlspecialchars($ruleTitle, ENT_QUOTES, 'UTF-8') . '</h4>'
                . DecisionGovernanceAction::buildRuleStateDescription($afterState);
        } elseif ($actionType === DecisionGovernanceAction::TYPE_RULE_DELETE) {
            $rule = $rulesById[$targetId];
            $validation = DecisionGovernanceAction::validateRuleDelete($rule, $targetHolonId);
            if (empty($validation['status'])) {
                $respond(422, ['status' => false, 'message' => (string)($validation['message'] ?? 'Suppression invalide.')]);
            }
            $beforeState = $existingAction instanceof DecisionGovernanceAction
                ? DecisionGovernanceAction::normalizeState($existingAction->get('before_state'))
                : (array)$validation['state'];
            $afterState = [];
            $ruleTitle = trim((string)$beforeState['title']);
            $suggestedTitles[] = 'Supprimer la regle ' . $ruleTitle;
            $actionDescriptions[] = '<h4>Supprimer la regle ' . htmlspecialchars($ruleTitle, ENT_QUOTES, 'UTF-8') . '</h4>'
                . DecisionGovernanceAction::buildRuleStateDescription($beforeState);
        } else {
            $rule = $rulesById[$targetId];
            $beforeState = $existingAction instanceof DecisionGovernanceAction
                ? DecisionGovernanceAction::normalizeState($existingAction->get('before_state'))
                : DecisionGovernanceAction::captureRuleState($rule);
            $validation = DecisionGovernanceAction::validateRuleUpdate(
                $rule,
                is_array($actionInput['after'] ?? null) ? $actionInput['after'] : [],
                $targetHolonId
            );
            if (empty($validation['status'])) {
                $respond(422, ['status' => false, 'message' => (string)($validation['message'] ?? 'Modification invalide.')]);
            }
            $afterState = (array)$validation['state'];
            if ($beforeState === $afterState) {
                $respond(422, ['status' => false, 'message' => 'La modification de la regle ' . trim((string)$rule->get('title')) . ' ne contient aucun changement.']);
            }
            $ruleTitle = trim((string)$rule->get('title'));
            $suggestedTitles[] = 'Modifier la regle ' . $ruleTitle;
            $actionDescriptions[] = '<h4>Modifier la regle ' . htmlspecialchars($ruleTitle, ENT_QUOTES, 'UTF-8') . '</h4>'
                . DecisionGovernanceAction::buildRuleUpdateDescription($beforeState, $afterState);
        }
        $ruleTitles[] = $ruleTitle;
        $normalizedActions[] = [
            'id' => $actionId,
            'existing' => $existingAction,
            'action_type' => $actionType,
            'target_id' => $targetId,
            'target_type' => DecisionGovernanceAction::TARGET_RULE,
            'before' => $beforeState,
            'after' => $afterState,
            'position' => $actionIndex + 1,
        ];
    }

    $proposalTitle = trim((string)($proposalInput['title'] ?? ''));
    if ($proposalTitle === '') {
        $proposalTitle = count($suggestedTitles) === 1
            ? $suggestedTitles[0]
            : 'Modifier la gouvernance';
    }
    if (mb_strlen($proposalTitle, 'UTF-8') > 190) {
        $respond(422, ['status' => false, 'message' => 'Le titre d une proposition est trop long.']);
    }
    $proposalDescription = \dbObject\PropertyFormat::sanitizeHtml((string)($proposalInput['description'] ?? ''));
    if (mb_strlen($proposalDescription, 'UTF-8') > 10000) {
        $respond(422, ['status' => false, 'message' => 'La description d une proposition est trop longue.']);
    }
    if (trim($proposalDescription) === '') {
        $proposalDescription = implode('', $actionDescriptions);
    }
    $normalizedProposals[] = [
        'id' => $proposalId,
        'title' => $proposalTitle,
        'description' => $proposalDescription,
        'actions' => $normalizedActions,
        'position' => $proposalIndex + 1,
    ];
}

$pdo = DbObject::getPdo();
if (!$pdo) {
    $respond(500, ['status' => false, 'message' => 'Connexion a la base impossible.']);
}

$newProposalIds = [];
try {
    $pdo->beginTransaction();

    $visibilityType = (int)$targetHolon->get('IDtypeholon') === 1
        ? ObjectVisibility::TYPE_ROLE
        : ObjectVisibility::TYPE_CIRCLE;
    $decision->set('title', $processTitle);
    $decision->set('description', $processDescription);
    $decision->set('decision_type', DecisionProcess::TYPE_DECISION);
    $decision->set('evaluation_method', $governanceMethod);
    $decision->set('visibility_type', $visibilityType);
    $decision->set('status', $wasExistingDecision ? $decision->get('status') : DecisionProcess::STATUS_CONSULTATION);
    if (!$wasExistingDecision) {
        $decision->set('consultation_start_at', new DateTimeImmutable('now'));
    }
    $decision->set('consultation_end_at', $consultationEnd);
    $decision->set('evaluation_start_at', $consultationEnd);
    $decision->set('evaluation_end_at', $evaluationEnd);

    $parameters = $governanceMethod === DecisionProcess::METHOD_CONSENT
        ? omoDecisionConsentMergeConfigIntoParameters(
            $decision->get('parameters'),
            [
                'is_anonymous' => !empty($governanceConsentConfig['is_anonymous']),
                'allow_anonymous_votes' => !empty($governanceConsentConfig['allow_anonymous_votes']),
                'allow_consultation_proposals' => !empty($governanceConsentConfig['allow_consultation_proposals']),
                'allow_proposal_discussions' => !empty($governanceConsentConfig['allow_proposal_discussions']),
                'show_live_results' => !empty($governanceConsentConfig['show_live_results']),
            ],
            [
                'proposal_count' => count($normalizedProposals),
                'created_from_module' => 'consent',
                'governance_payload_version' => 1,
            ]
        )
        : omoDecisionVoteMergeConfigIntoParameters(
            $decision->get('parameters'),
            $governanceVoteConfig,
            [
                'proposal_count' => count($normalizedProposals),
                'created_from_module' => 'vote',
                'governance_payload_version' => 1,
            ]
        );
    $parameters['workflow_type'] = DecisionProcess::WORKFLOW_GOVERNANCE;
    $decision->set('parameters', $parameters);

    $saveDecision = $decision->save();
    if (!is_array($saveDecision) || empty($saveDecision['status'])) {
        throw new RuntimeException('decision_save_failed');
    }
    $decisionId = (int)$decision->getId();

    $group = $decision->ensurePrimaryGroup();
    if (!$group instanceof DecisionGroup) {
        throw new RuntimeException('decision_group_missing');
    }
    $group->set('title', $consentQuestion);
    $group->set('description', $processDescription);
    $group->set('decision_type', DecisionProcess::TYPE_DECISION);
    $group->set('evaluation_method', $governanceMethod);
    $group->set('parameters', $parameters);
    $group->set('active', 1);
    $saveGroup = $group->save();
    if (!is_array($saveGroup) || empty($saveGroup['status'])) {
        throw new RuntimeException('decision_group_save_failed');
    }
    $groupId = (int)$group->getId();

    $savedProposalIds = [];
    foreach ($normalizedProposals as $normalizedProposal) {
        $proposalId = (int)$normalizedProposal['id'];
        $proposal = $proposalId > 0 ? $existingProposals[$proposalId] : new DecisionProposal();
        $oldValues = $proposalId > 0 ? [
            'title' => trim((string)$proposal->get('title')),
            'description' => trim((string)$proposal->get('description')),
            'info_url' => trim((string)$proposal->get('info_url')),
        ] : null;
        $proposal->set('IDdecision_process', $decisionId);
        $proposal->set('IDdecision_group', $groupId);
        if ($proposalId <= 0) {
            $proposal->set('IDuser_author', $currentUserId);
        }
        $proposal->set('title', $normalizedProposal['title']);
        $proposal->set('description', $normalizedProposal['description']);
        $proposal->set('info_url', null);
        $proposal->set('position', (int)$normalizedProposal['position']);
        $proposal->set('active', 1);
        $proposalParameters = omoDecisionModuleDecodeParameters($proposal->get('parameters'));
        $proposalParameters[$governanceMethod] = ['ballot_position' => (int)$normalizedProposal['position']];
        $proposalParameters['proposal_type'] = 'governance';
        $proposal->set('parameters', $proposalParameters);
        $saveProposal = $proposal->save();
        if (!is_array($saveProposal) || empty($saveProposal['status'])) {
            throw new RuntimeException('proposal_save_failed');
        }
        $proposalId = (int)$proposal->getId();
        if ((int)$normalizedProposal['id'] <= 0) {
            $newProposalIds[] = $proposalId;
        }
        $savedProposalIds[$proposalId] = true;

        $savedActionIds = [];
        foreach ($normalizedProposal['actions'] as $normalizedAction) {
            $action = $normalizedAction['existing'] instanceof DecisionGovernanceAction
                ? $normalizedAction['existing']
                : new DecisionGovernanceAction();
            $action->set('IDdecision_proposal', $proposalId);
            $action->set('action_type', (string)$normalizedAction['action_type']);
            $action->set('target_type', (string)$normalizedAction['target_type']);
            $action->set('target_id', (int)$normalizedAction['target_id']);
            $action->set('before_state', $normalizedAction['before']);
            $action->set('after_state', $normalizedAction['after']);
            $action->set('parameters', ['payload_version' => 1]);
            $action->set('position', (int)$normalizedAction['position']);
            $action->set('status', DecisionGovernanceAction::STATUS_PENDING);
            $action->set('status_message', null);
            $action->set('applied_at', null);
            $action->set('updated_at', new DateTimeImmutable('now'));
            if ((int)$action->getId() <= 0) {
                $action->set('created_at', new DateTimeImmutable('now'));
            }
            $saveAction = $action->save();
            if (!is_array($saveAction) || empty($saveAction['status'])) {
                throw new RuntimeException('governance_action_save_failed');
            }
            $savedActionIds[(int)$action->getId()] = true;
        }

        foreach (($existingActionsByProposal[$proposalId] ?? []) as $existingActionId => $existingAction) {
            if (isset($savedActionIds[(int)$existingActionId])) {
                continue;
            }
            if ((string)$existingAction->get('status') === DecisionGovernanceAction::STATUS_PENDING) {
                $existingAction->set('status', DecisionGovernanceAction::STATUS_REMOVED);
                $existingAction->set('status_message', 'Modification retiree pendant la consultation.');
                $existingAction->set('updated_at', new DateTimeImmutable('now'));
                $existingAction->save();
            }
        }

        if (is_array($oldValues)) {
            $newValues = [
                'title' => trim((string)$proposal->get('title')),
                'description' => trim((string)$proposal->get('description')),
                'info_url' => '',
            ];
            if ($oldValues !== $newValues) {
                $thread = $proposal->getChatThread(true, $currentUserId);
                if ($thread) {
                    ChatMessage::createSystemMessage(
                        $thread,
                        'La proposition de gouvernance a ete modifiee.',
                        $currentUserId,
                        [
                            'action' => 'decision_proposal_updated',
                            'proposal_id' => $proposalId,
                            'old' => $oldValues,
                            'new' => $newValues,
                        ]
                    );
                }
            }
        }
    }

    foreach ($existingProposals as $existingProposalId => $existingProposal) {
        if (isset($savedProposalIds[(int)$existingProposalId])) {
            continue;
        }
        $existingProposal->set('active', 0);
        $existingProposal->set('updated_at', new DateTimeImmutable('now'));
        $archiveResult = $existingProposal->save();
        if (!is_array($archiveResult) || empty($archiveResult['status'])) {
            throw new RuntimeException('proposal_archive_failed');
        }
    }

    $owner = DecisionParticipant::findByDecisionAndUser($decisionId, $currentUserId);
    if (!$owner) {
        $owner = new DecisionParticipant();
    }
    $owner->set('IDdecision_process', $decisionId);
    $owner->set('IDuser', $currentUserId);
    $owner->set('role', DecisionParticipant::ROLE_OWNER);
    $owner->set('status', DecisionParticipant::STATUS_ACTIVE);
    $owner->set('active', 1);
    $ownerSave = $owner->save();
    if (!is_array($ownerSave) || empty($ownerSave['status'])) {
        throw new RuntimeException('owner_save_failed');
    }
    $participantSync = $decision->syncParticipantsFromInvitations();
    if (!is_array($participantSync) || empty($participantSync['status'])) {
        throw new RuntimeException('participant_sync_failed');
    }

    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('decision_governance_save_failed: ' . $exception->getMessage());
    $respond(500, ['status' => false, 'message' => omoDecisionGovernanceT('governance.error.generic')]);
}

foreach ($newProposalIds as $newProposalId) {
    $proposal = new DecisionProposal();
    if ($proposal->load($newProposalId)) {
        try {
            notificationCenterDispatchDecisionProposal($proposal);
        } catch (Throwable $exception) {
            error_log('decision_governance_notification_failed: ' . $exception->getMessage());
        }
    }
}

$respond(200, [
    'status' => true,
    'message' => $wasExistingDecision ? 'Prise de decision mise a jour.' : 'Prise de decision creee.',
    'decisionId' => (int)$decision->getId(),
    'redirectUrl' => omoDecisionBuildEditorUrl(
        $organizationId,
        $targetHolonId,
        (int)$decision->getId(),
        $governanceMethod,
        'manage',
        (int)$group->getId()
    ),
    'drawerTitle' => 'Prises de decision',
]);
