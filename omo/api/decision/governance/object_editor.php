<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__) . '/modules/context.php';
require_once dirname(__DIR__) . '/params/shared.php';

use dbObject\ControlActivity;
use dbObject\DecisionProcess;
use dbObject\DeferredProposal;
use dbObject\StatIndicator;

$request = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
$context = omoDecisionResolveEditorContext($request);
$decision = ($context['decision'] ?? null) instanceof DecisionProcess ? $context['decision'] : null;
$organizationId = (int)($context['organizationId'] ?? 0);
$collectiveHolonId = (int)($context['targetHolonId'] ?? 0);
$currentUserId = (int)($context['currentUserId'] ?? 0);
$targetType = trim((string)($request['target_type'] ?? ''));
$operation = trim((string)($request['operation'] ?? ''));
$holonId = (int)($request['context_holon_id'] ?? 0);
$objectId = (int)($request['object_id'] ?? 0);
$settings = omoDecisionParamsGetConfig($context['organization'] ?? null);
$allowed = !empty($context['status'])
    && $organizationId > 0 && $collectiveHolonId > 0
    && in_array($targetType, [DeferredProposal::TARGET_RECURRING_TASK, DeferredProposal::TARGET_INDICATOR], true)
    && in_array($operation, [DeferredProposal::OPERATION_CREATE, DeferredProposal::OPERATION_UPDATE], true)
    && ($decision instanceof DecisionProcess
        ? $decision->isGovernanceWorkflow() && (int)$decision->get('IDuser') === $currentUserId && !$decision->hasConsultationEnded()
        : !empty($context['canCreate']) && !empty($settings['governance']['enabled']))
    && DeferredProposal::loadAllowedObjectTargetHolon($organizationId, $holonId, $operation, $collectiveHolonId, $targetType);
$object = $targetType === DeferredProposal::TARGET_RECURRING_TASK ? new ControlActivity() : new StatIndicator();
if ($allowed && $operation === DeferredProposal::OPERATION_UPDATE) {
    $allowed = $objectId > 0 && $object->load($objectId)
        && (int)$object->get('IDorganization') === $organizationId
        && (int)$object->get('IDholon') === $holonId
        && (int)$object->get('active') === 1;
} elseif ($operation === DeferredProposal::OPERATION_CREATE) {
    $allowed = $allowed && $objectId === 0;
}
if (!$allowed) {
    http_response_code(403);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['status' => false, 'message' => 'Accès refusé.'], JSON_UNESCAPED_UNICODE);
    } else echo '<div class="omo-empty-state">Accès refusé.</div>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $state = json_decode((string)($request['state'] ?? ''), true);
    if (!is_array($state)) $state = [];
    $token = bin2hex(random_bytes(16));
    $_SESSION['omo_deferred_object_editor'][$token] = [
        'organizationId' => $organizationId,
        'holonId' => $holonId,
        'objectId' => $objectId,
        'targetType' => $targetType,
        'operation' => $operation,
        'state' => $state,
        'userId' => $currentUserId,
        'createdAt' => time(),
    ];
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['status' => true, 'draftKey' => $token]);
    exit;
}

$token = (string)($request['draft_key'] ?? '');
$draft = $_SESSION['omo_deferred_object_editor'][$token] ?? null;
unset($_SESSION['omo_deferred_object_editor'][$token]);
if (!is_array($draft)
    || (int)$draft['organizationId'] !== $organizationId
    || (int)$draft['holonId'] !== $holonId
    || (int)$draft['objectId'] !== $objectId
    || (string)$draft['targetType'] !== $targetType
    || (string)$draft['operation'] !== $operation
    || (int)$draft['userId'] !== $currentUserId
    || time() - (int)$draft['createdAt'] > 300) {
    http_response_code(403);
    echo '<div class="omo-empty-state">Cet éditeur a expiré.</div>';
    exit;
}
$GLOBALS['omoDeferredObjectEditor'] = $draft + ['origin' => 'governance'];
require dirname(__DIR__, 2) . ($targetType === DeferredProposal::TARGET_RECURRING_TASK ? '/activities/edit.php' : '/stats/edit.php');
