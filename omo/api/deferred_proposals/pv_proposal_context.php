<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\DeferredProposal;
use dbObject\DecisionGovernanceAction;
use dbObject\Document;
use dbObject\DocumentPvPoint;
use dbObject\Holon;
use dbObject\Rule;
use dbObject\ArrayProject;
use dbObject\Project;

header('Content-Type: application/json; charset=UTF-8');
$respond = static function (int $code, array $payload): void {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
};

$organizationId = (int)($_GET['oid'] ?? ($_SESSION['currentOrganization'] ?? 0));
$pointId = (int)($_GET['point_id'] ?? 0);
$targetType = trim((string)($_GET['target_type'] ?? ''));
$operation = trim((string)($_GET['operation'] ?? ''));
$contextHolonId = (int)($_GET['context_holon_id'] ?? 0);
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
if (!in_array($targetType, [DeferredProposal::TARGET_RULE, DeferredProposal::TARGET_HOLON, DeferredProposal::TARGET_PROJECT], true)
    || !in_array($operation, [DeferredProposal::OPERATION_CREATE, DeferredProposal::OPERATION_UPDATE, DeferredProposal::OPERATION_DELETE], true)) {
    $respond(422, ['status' => false, 'message' => 'Sélection invalide.']);
}

$collectiveHolonId = (int)$document->getPvContextHolonId();
$objects = [];
if ($targetType === DeferredProposal::TARGET_RULE) {
    $contextHolon = DeferredProposal::loadAllowedRuleTargetHolon(
        $organizationId,
        $contextHolonId,
        $operation,
        $collectiveHolonId
    );
    if (!$contextHolon) {
        $respond(403, ['status' => false, 'message' => 'Le collectif du PV ne dispose pas du droit nécessaire dans cet espace.']);
    }
    if ($operation !== DeferredProposal::OPERATION_CREATE) {
        foreach (Rule::findDefinedInHolon($contextHolonId) as $rule) {
            if (!$rule instanceof Rule || (int)$rule->getId() <= 0) continue;
            $objects[] = [
                'id' => (int)$rule->getId(),
                'label' => trim((string)$rule->get('title')),
                'state' => DecisionGovernanceAction::captureRuleState($rule),
            ];
        }
    }
} elseif ($targetType === DeferredProposal::TARGET_HOLON) {
    $catalog = DeferredProposal::getHolonTargetHolonCatalog($organizationId, $collectiveHolonId);
    $contextHolon = new Holon();
    if (!isset($catalog[$contextHolonId])
        || !$contextHolon->load($contextHolonId)
        || !in_array((int)$contextHolon->get('IDtypeholon'), [2, 3, 4], true)) {
        $respond(422, ['status' => false, 'message' => 'Le contexte structurel est invalide.']);
    }
    if ($operation === DeferredProposal::OPERATION_CREATE) {
        if (empty($catalog[$contextHolonId]['permissions'][$operation])) {
            $respond(403, ['status' => false, 'message' => 'Le collectif du PV ne peut pas créer d’espace ici.']);
        }
    } else {
        foreach ($contextHolon->getChildren() as $child) {
            if (!$child instanceof Holon) continue;
            $childId = (int)$child->getId();
            if ($childId <= 0 || empty($catalog[$childId]['permissions'][$operation])) continue;
            $objects[] = [
                'id' => $childId,
                'label' => trim((string)$child->getDisplayName()),
                'typeLabel' => trim((string)$child->getTemplateLabel()),
            ];
        }
    }
} else {
    $contextHolon = DeferredProposal::loadAllowedProjectTargetHolon(
        $organizationId,
        $contextHolonId,
        $operation,
        $collectiveHolonId
    );
    if (!$contextHolon) {
        $respond(403, ['status' => false, 'message' => 'Le collectif du PV ne dispose pas du droit nécessaire dans cet espace.']);
    }
    if ($operation !== DeferredProposal::OPERATION_CREATE) {
        $projects = new ArrayProject();
        $projects->loadForContext($organizationId, $contextHolonId, 'contextual');
        foreach ($projects as $project) {
            if (!$project instanceof Project || (int)$project->getId() <= 0) continue;
            $objects[] = [
                'id' => (int)$project->getId(),
                'label' => trim((string)$project->get('title')),
                'state' => DeferredProposal::captureProjectState($project),
            ];
        }
        usort($objects, static fn (array $left, array $right): int => strcasecmp((string)$left['label'], (string)$right['label']));
    }
}

$respond(200, [
    'status' => true,
    'context' => ['id' => (int)$contextHolon->getId(), 'label' => (string)$contextHolon->getFullDisplayName()],
    'objects' => $objects,
]);
