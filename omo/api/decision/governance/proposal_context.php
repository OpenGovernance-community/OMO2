<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__) . '/modules/context.php';
require_once dirname(__DIR__) . '/params/shared.php';
require_once __DIR__ . '/shared.php';

use dbObject\ArrayProject;
use dbObject\DecisionGovernanceAction;
use dbObject\DecisionProcess;
use dbObject\DeferredProposal;
use dbObject\Holon;
use dbObject\Project;
use dbObject\Rule;
use dbObject\ArrayControlActivity;
use dbObject\ArrayStatIndicator;
use dbObject\ControlActivity;
use dbObject\StatIndicator;

header('Content-Type: application/json; charset=UTF-8');
$respond = static function (int $code, array $payload): void {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
};

$context = omoDecisionResolveEditorContext($_GET);
if (empty($context['status'])) $respond((int)($context['code'] ?? 403), ['status' => false, 'message' => 'Accès refusé.']);
$decision = ($context['decision'] ?? null) instanceof DecisionProcess ? $context['decision'] : null;
$currentUserId = (int)($context['currentUserId'] ?? 0);
$organizationId = (int)($context['organizationId'] ?? 0);
$collectiveHolonId = (int)($context['targetHolonId'] ?? 0);
$settings = omoDecisionParamsGetConfig($context['organization'] ?? null);
if ($organizationId <= 0 || $collectiveHolonId <= 0
    || ($decision instanceof DecisionProcess && (!$decision->isGovernanceWorkflow() || (int)$decision->get('IDuser') !== $currentUserId || $decision->hasConsultationEnded()))
    || (!($decision instanceof DecisionProcess) && (empty($context['canCreate']) || empty($settings['governance']['enabled'])))) {
    $respond(403, ['status' => false, 'message' => 'Accès refusé.']);
}

$targetType = trim((string)($_GET['target_type'] ?? ''));
$operation = trim((string)($_GET['operation'] ?? ''));
$contextHolonId = (int)($_GET['context_holon_id'] ?? 0);
if (!in_array($targetType, [DeferredProposal::TARGET_RULE, DeferredProposal::TARGET_HOLON, DeferredProposal::TARGET_PROJECT, DeferredProposal::TARGET_RECURRING_TASK, DeferredProposal::TARGET_INDICATOR], true)
    || !in_array($operation, [DeferredProposal::OPERATION_CREATE, DeferredProposal::OPERATION_UPDATE, DeferredProposal::OPERATION_DELETE, DeferredProposal::OPERATION_MOVE], true)) {
    $respond(422, ['status' => false, 'message' => 'Sélection invalide.']);
}

$objects = [];
$ruleScopeContext = null;
$contextHolon = null;
if ($targetType === DeferredProposal::TARGET_HOLON && $operation === DeferredProposal::OPERATION_MOVE && (int)($_GET['moving_holon_id'] ?? 0) > 0) {
    $movingHolon = DeferredProposal::loadAllowedHolonTargetHolon($organizationId, (int)$_GET['moving_holon_id'], $operation, $collectiveHolonId);
    if (!$movingHolon || (int)$movingHolon->get('IDholon_parent') !== $contextHolonId) $respond(403, ['status' => false, 'message' => 'Deplacement non autorise.']);
    $moveData = $context['organization']->getHolonMoveEditorData((int)$movingHolon->getId(), $collectiveHolonId);
    $respond(200, ['status' => true, 'destinations' => $moveData['destinations'], 'before' => DeferredProposal::captureHolonMoveState($movingHolon)]);
}
if ($targetType === DeferredProposal::TARGET_RULE) {
    $contextHolon = DeferredProposal::loadAllowedRuleTargetHolon($organizationId, $contextHolonId, $operation, $collectiveHolonId);
    if (!($contextHolon instanceof Holon)) $respond(403, ['status' => false, 'message' => 'Le collectif ne dispose pas du droit nécessaire dans cet espace.']);
    $ruleScopeContext = Rule::getScopeContext($contextHolon);
    if ($operation !== DeferredProposal::OPERATION_CREATE) {
        foreach (Rule::findDefinedInHolon($contextHolonId) as $rule) {
            if (!$rule instanceof Rule || (int)$rule->getId() <= 0) continue;
            $objects[] = ['id' => (int)$rule->getId(), 'label' => trim((string)$rule->get('title')), 'state' => DecisionGovernanceAction::captureRuleState($rule)];
        }
    }
} elseif ($targetType === DeferredProposal::TARGET_HOLON) {
    $catalog = DeferredProposal::getHolonTargetHolonCatalog($organizationId, $collectiveHolonId);
    $contextHolon = new Holon();
    if (!isset($catalog[$contextHolonId]) || !$contextHolon->load($contextHolonId)) $respond(422, ['status' => false, 'message' => 'Le contexte structurel est invalide.']);
    if ($operation === DeferredProposal::OPERATION_CREATE) {
        if (empty($catalog[$contextHolonId]['permissions'][$operation])) $respond(403, ['status' => false, 'message' => 'Le collectif ne peut pas créer d’élément ici.']);
    } else {
        foreach ($contextHolon->getChildren() as $child) {
            if (!$child instanceof Holon || empty($catalog[(int)$child->getId()]['permissions'][$operation])) continue;
            $objects[] = [
                'id' => (int)$child->getId(),
                'label' => trim((string)$child->getDisplayName()),
                'typeLabel' => trim((string)$child->getTemplateLabel()),
                'state' => omoDecisionGovernanceBuildRoleClientData($child, $context['organization'] ?? null, $contextHolonId)['state'],
            ];
        }
    }
} elseif ($targetType === DeferredProposal::TARGET_PROJECT) {
    $contextHolon = DeferredProposal::loadAllowedProjectTargetHolon($organizationId, $contextHolonId, $operation, $collectiveHolonId);
    if (!($contextHolon instanceof Holon)) $respond(403, ['status' => false, 'message' => 'Le collectif ne dispose pas du droit nécessaire dans cet espace.']);
    if ($operation !== DeferredProposal::OPERATION_CREATE) {
        $projects = new ArrayProject();
        $projects->loadForContext($organizationId, $contextHolonId, 'contextual');
        foreach ($projects as $project) {
            if (!$project instanceof Project || (int)$project->getId() <= 0) continue;
            $objects[] = ['id' => (int)$project->getId(), 'label' => trim((string)$project->get('title')), 'state' => DeferredProposal::captureProjectState($project)];
        }
        usort($objects, static fn (array $left, array $right): int => strcasecmp((string)$left['label'], (string)$right['label']));
    }
} else {
    $contextHolon = DeferredProposal::loadAllowedObjectTargetHolon($organizationId, $contextHolonId, $operation, $collectiveHolonId, $targetType);
    if (!($contextHolon instanceof Holon)) $respond(403, ['status' => false, 'message' => 'Le collectif ne dispose pas du droit nécessaire dans cet espace.']);
    if ($operation !== DeferredProposal::OPERATION_CREATE) {
        $items = $targetType === DeferredProposal::TARGET_RECURRING_TASK ? new ArrayControlActivity() : new ArrayStatIndicator();
        $items->load(['where' => [['field' => 'IDorganization', 'value' => $organizationId], ['field' => 'IDholon', 'value' => $contextHolonId], ['field' => 'active', 'value' => 1]], 'hydrate' => true]);
        foreach ($items as $item) {
            if ($targetType === DeferredProposal::TARGET_RECURRING_TASK && $item instanceof ControlActivity) {
                $objects[] = ['id' => (int)$item->getId(), 'label' => trim((string)$item->get('title')), 'state' => DeferredProposal::captureRecurringTaskState($item)];
            } elseif ($targetType === DeferredProposal::TARGET_INDICATOR && $item instanceof StatIndicator && !$item->isHiddenFromCatalog()) {
                $objects[] = ['id' => (int)$item->getId(), 'label' => trim((string)$item->get('name')), 'state' => DeferredProposal::captureIndicatorState($item)];
            }
        }
        usort($objects, static fn (array $left, array $right): int => strcasecmp((string)$left['label'], (string)$right['label']));
    }
}

$respond(200, [
    'status' => true,
    'context' => ['id' => (int)$contextHolon->getId(), 'label' => (string)$contextHolon->getFullDisplayName()],
    'objects' => $objects,
    'ruleScopeContext' => $ruleScopeContext,
]);
