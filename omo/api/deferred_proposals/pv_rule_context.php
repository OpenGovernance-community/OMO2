<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\DecisionGovernanceAction;
use dbObject\DeferredProposal;
use dbObject\Document;
use dbObject\DocumentPvPoint;
use dbObject\Rule;

header('Content-Type: application/json; charset=UTF-8');
$respond = static function (int $code, array $payload): void {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
};

$organizationId = (int)($_GET['oid'] ?? ($_SESSION['currentOrganization'] ?? 0));
$pointId = (int)($_GET['point_id'] ?? 0);
$holonId = (int)($_GET['holon_id'] ?? 0);
$operation = trim((string)($_GET['operation'] ?? ''));
$userId = (int)commonGetCurrentUserId();
$point = new DocumentPvPoint();
$document = new Document();
if ($organizationId <= 0
    || $pointId <= 0
    || !$point->load($pointId)
    || !$document->load((int)$point->get('IDdocument'))
    || (int)$document->get('IDorganization') !== $organizationId
    || !$document->canUserManagePvDocument($userId)
    || $point->isHandled()
    || $document->getPvStage() === Document::PV_STAGE_REVIEW) {
    $respond(403, ['status' => false, 'message' => 'Accès refusé.']);
}

$collectiveHolonId = (int)$document->getPvContextHolonId();
$holon = DeferredProposal::loadAllowedRuleTargetHolon(
    $organizationId,
    $holonId,
    $operation,
    $collectiveHolonId
);
if (!$holon) {
    $respond(403, ['status' => false, 'message' => 'Vous ne pouvez pas effectuer cette action dans cet espace.']);
}

$rulesPayload = [];
if ($operation !== DeferredProposal::OPERATION_CREATE) {
    foreach (Rule::findDefinedInHolon($holonId) as $rule) {
        if (!$rule instanceof Rule || (int)$rule->getId() <= 0) continue;
        $rulesPayload[] = [
            'id' => (int)$rule->getId(),
            'title' => (string)$rule->get('title'),
            'state' => DecisionGovernanceAction::captureRuleState($rule),
        ];
    }
}

$respond(200, [
    'status' => true,
    'holon' => ['id' => (int)$holon->getId(), 'label' => (string)$holon->getFullDisplayName()],
    'rules' => $rulesPayload,
]);
