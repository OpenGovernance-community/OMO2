<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\ControlActivity;
use dbObject\DeferredProposal;
use dbObject\Document;
use dbObject\DocumentPvPoint;
use dbObject\StatIndicator;

header('Content-Type: application/json; charset=UTF-8');
$respond = static function (int $code, array $payload): void { http_response_code($code); echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit; };
$organizationId = (int)($_POST['oid'] ?? ($_SESSION['currentOrganization'] ?? 0));
$pointId = (int)($_POST['point_id'] ?? 0); $proposalId = (int)($_POST['proposal_id'] ?? 0);
$targetType = trim((string)($_POST['target_type'] ?? '')); $operation = trim((string)($_POST['operation'] ?? ''));
$holonId = (int)($_POST['holon_id'] ?? 0); $objectId = (int)($_POST['object_id'] ?? 0); $userId = (int)commonGetCurrentUserId();
$point = new DocumentPvPoint(); $document = new Document();
if (!in_array($targetType, [DeferredProposal::TARGET_RECURRING_TASK, DeferredProposal::TARGET_INDICATOR], true)
    || !in_array($operation, [DeferredProposal::OPERATION_CREATE, DeferredProposal::OPERATION_UPDATE, DeferredProposal::OPERATION_DELETE], true)
    || !$point->load($pointId) || !$document->load((int)$point->get('IDdocument'))
    || (int)$document->get('IDorganization') !== $organizationId || !$document->canUserManagePvDocument($userId)
    || $point->isHandled() || $document->getPvStage() === Document::PV_STAGE_REVIEW) $respond(403, ['status' => false, 'message' => 'Accès refusé.']);
$proposal = new DeferredProposal();
if ($proposalId > 0 && (!$proposal->load($proposalId) || (int)$proposal->get('IDdocument_pv_point') !== $pointId || (string)$proposal->get('target_type') !== $targetType || (string)$proposal->get('status') !== DeferredProposal::STATUS_PENDING)) $respond(404, ['status' => false, 'message' => 'Cette modification ne peut plus être modifiée.']);
if ($proposalId > 0 && ((string)$proposal->get('operation') !== $operation || (int)$proposal->get('IDholon') !== $holonId || (int)$proposal->get('target_id') !== $objectId)) $respond(422, ['status' => false, 'message' => 'Le contexte de cette modification a changé.']);
$contextHolon = DeferredProposal::loadAllowedObjectTargetHolon($organizationId, $holonId, $operation, (int)$document->getPvContextHolonId(), $targetType);
if (!$contextHolon) $respond(403, ['status' => false, 'message' => 'Le collectif du PV ne dispose pas du droit nécessaire.']);
$object = $targetType === DeferredProposal::TARGET_RECURRING_TASK ? new ControlActivity() : new StatIndicator();
if ($operation !== DeferredProposal::OPERATION_CREATE && (!$object->load($objectId) || (int)$object->get('IDorganization') !== $organizationId || (int)$object->get('IDholon') !== $holonId || (int)$object->get('active') !== 1)) $respond(404, ['status' => false, 'message' => 'L’élément demandé est introuvable.']);
if ($operation === DeferredProposal::OPERATION_CREATE && $objectId !== 0) $respond(422, ['status' => false, 'message' => 'Sélection invalide.']);
$before = $operation === DeferredProposal::OPERATION_CREATE ? [] : ($proposalId > 0 ? DeferredProposal::normalizeState($proposal->get('before_state')) : ($targetType === DeferredProposal::TARGET_RECURRING_TASK ? DeferredProposal::captureRecurringTaskState($object) : DeferredProposal::captureIndicatorState($object)));
$input = $_POST; $input['IDholon'] = $holonId;
if ($targetType === DeferredProposal::TARGET_RECURRING_TASK) {
    $after = $operation === DeferredProposal::OPERATION_DELETE ? [] : DeferredProposal::normalizeRecurringTaskState($input, $operation === DeferredProposal::OPERATION_UPDATE ? $object : null);
    if ($operation !== DeferredProposal::OPERATION_DELETE && $after['title'] === '') $respond(422, ['status' => false, 'message' => 'Le titre est obligatoire.']);
} else {
    try {
        $after = $operation === DeferredProposal::OPERATION_DELETE ? [] : DeferredProposal::normalizeIndicatorEditorState($input, $organizationId, $operation === DeferredProposal::OPERATION_UPDATE ? $object : null);
    } catch (InvalidArgumentException $exception) {
        $respond(422, ['status' => false, 'message' => $exception->getMessage()]);
    }
    if ($operation !== DeferredProposal::OPERATION_DELETE && $after['name'] === '') $respond(422, ['status' => false, 'message' => 'Le nom est obligatoire.']);
}
if ((int)($after['IDuser_responsible'] ?? 0) > 0 && !\dbObject\UserOrganization::hasActiveMembership((int)$after['IDuser_responsible'], $organizationId)) $after['IDuser_responsible'] = null;
$proposal->set('IDorganization', $organizationId); $proposal->set('IDholon', $holonId); $proposal->set('IDuser_author', $userId);
$proposal->set('target_type', $targetType); $proposal->set('operation', $operation); $proposal->set('target_id', $operation === DeferredProposal::OPERATION_CREATE ? null : $objectId);
$proposal->set('before_state', $before); $proposal->set('after_state', $after); $proposal->set('IDdocument_pv_point', $pointId);
if ($proposalId <= 0) $proposal->set('position', count(DeferredProposal::getForPvPoint($pointId)) + 1);
$proposal->set('status', DeferredProposal::STATUS_PENDING); $proposal->set('parameters', ['payload_version' => 1, 'editor' => $targetType]);
$result = $proposal->save();
if (!is_array($result) || empty($result['status'])) $respond(500, ['status' => false, 'message' => 'La modification ne peut pas être enregistrée.']);
$respond(200, ['status' => true, 'id' => (int)$proposal->getId(), 'pointId' => $pointId]);
