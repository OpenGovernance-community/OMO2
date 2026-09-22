<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\DecisionGovernanceAction;
use dbObject\DeferredProposal;
use dbObject\Document;
use dbObject\DocumentPvPoint;
use dbObject\Rule;

header('Content-Type: application/json; charset=UTF-8');
$respond = static function (int $code, array $payload): void { http_response_code($code); echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit; };
$organizationId = (int)($_POST['oid'] ?? ($_SESSION['currentOrganization'] ?? 0));
$pointId = (int)($_POST['point_id'] ?? 0);
$holonId = (int)($_POST['holon_id'] ?? 0);
$proposalId = (int)($_POST['proposal_id'] ?? 0);
$userId = (int)commonGetCurrentUserId();
$point = new DocumentPvPoint(); $document = new Document();
if ($organizationId <= 0 || $pointId <= 0 || !$point->load($pointId) || !$document->load((int)$point->get('IDdocument')) || (int)$document->get('IDorganization') !== $organizationId || !$document->canUserManagePvDocument($userId) || $point->isHandled() || $document->getPvStage() === Document::PV_STAGE_REVIEW) $respond(403, ['status' => false, 'message' => 'Accès refusé.']);
$operation = trim((string)($_POST['operation'] ?? '')); if (!in_array($operation, ['create', 'update', 'delete'], true)) $respond(422, ['status' => false, 'message' => 'Action invalide.']);
$collectiveHolonId = (int)$document->getPvContextHolonId();
$holon = DeferredProposal::loadAllowedRuleTargetHolon($organizationId, $holonId, $operation, $collectiveHolonId);
if (!$holon) $respond(403, ['status' => false, 'message' => 'Vous ne pouvez pas effectuer cette action dans cet espace.']);
$ruleId = (int)($_POST['rule_id'] ?? 0); $rule = new Rule();
if ($operation !== 'create' && (!$rule->load($ruleId) || !($rule->getHolon() instanceof \dbObject\Holon) || (int)$rule->getHolon()->getId() !== (int)$holon->getId())) $respond(422, ['status' => false, 'message' => 'Règle introuvable dans cet espace.']);
if ($operation === 'create') $validation = DecisionGovernanceAction::validateRuleCreate($_POST, $holonId);
elseif ($operation === 'delete') $validation = DecisionGovernanceAction::validateRuleDelete($rule, $holonId);
else $validation = DecisionGovernanceAction::validateRuleUpdate($rule, $_POST, $holonId);
if (empty($validation['status'])) $respond(422, ['status' => false, 'message' => (string)($validation['message'] ?? 'Proposition invalide.')]);
$proposal = new DeferredProposal();
if ($proposalId > 0 && (!$proposal->load($proposalId)
    || (int)$proposal->get('IDdocument_pv_point') !== $pointId
    || (int)$proposal->get('IDorganization') !== $organizationId
    || (string)$proposal->get('target_type') !== DeferredProposal::TARGET_RULE
    || (string)$proposal->get('status') !== DeferredProposal::STATUS_PENDING)) {
    $respond(404, ['status' => false, 'message' => 'Cette proposition ne peut plus être modifiée.']);
}
$proposal->set('IDorganization', $organizationId); $proposal->set('IDholon', $holonId); $proposal->set('IDuser_author', $userId);
$proposal->set('target_type', DeferredProposal::TARGET_RULE); $proposal->set('operation', $operation); $proposal->set('target_id', $operation === 'create' ? null : $ruleId);
$proposal->set('before_state', $operation === 'create' ? [] : DecisionGovernanceAction::captureRuleState($rule)); $proposal->set('after_state', $operation === 'delete' ? [] : $validation['state']);
$proposal->set('IDdocument_pv_point', $pointId); if ($proposalId <= 0) $proposal->set('position', count(DeferredProposal::getForPvPoint($pointId)) + 1); $proposal->set('status', DeferredProposal::STATUS_PENDING); $proposal->set('parameters', ['payload_version' => 1]);
$result = $proposal->save(); if (!is_array($result) || empty($result['status'])) $respond(500, ['status' => false, 'message' => 'Impossible d’enregistrer la proposition.']);
$respond(200, ['status' => true, 'id' => (int)$proposal->getId(), 'pointId' => $pointId]);
