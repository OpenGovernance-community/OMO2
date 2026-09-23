<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\DeferredProposal;
use dbObject\DecisionGovernanceAction;
use dbObject\Document;
use dbObject\DocumentPvPoint;
use dbObject\Organization;

header('Content-Type: application/json; charset=UTF-8');
$respond = static function (int $code, array $payload): void { http_response_code($code); echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit; };
$organizationId = (int)($_POST['oid'] ?? ($_SESSION['currentOrganization'] ?? 0));
$pointId = (int)($_POST['point_id'] ?? 0);
$proposalId = (int)($_POST['proposal_id'] ?? 0);
$operation = trim((string)($_POST['operation'] ?? ''));
$holonId = (int)($_POST['holon_id'] ?? 0);
$userId = (int)commonGetCurrentUserId();
$point = new DocumentPvPoint(); $document = new Document();
if ($organizationId <= 0 || $pointId <= 0 || !$point->load($pointId) || !$document->load((int)$point->get('IDdocument')) || (int)$document->get('IDorganization') !== $organizationId || !$document->canUserManagePvDocument($userId) || $point->isHandled() || $document->getPvStage() === Document::PV_STAGE_REVIEW) $respond(403, ['status' => false, 'message' => 'Accès refusé.']);
if (!in_array($operation, [DeferredProposal::OPERATION_CREATE, DeferredProposal::OPERATION_UPDATE, DeferredProposal::OPERATION_DELETE], true)) $respond(422, ['status' => false, 'message' => 'Action invalide.']);
$collectiveHolonId = (int)$document->getPvContextHolonId();
$targetHolon = DeferredProposal::loadAllowedHolonTargetHolon($organizationId, $holonId, $operation, $collectiveHolonId);
if (!$targetHolon) $respond(403, ['status' => false, 'message' => 'Le collectif du PV ne dispose pas du droit nécessaire dans cet espace.']);

$proposal = new DeferredProposal();
if ($proposalId > 0 && (!$proposal->load($proposalId) || (int)$proposal->get('IDdocument_pv_point') !== $pointId || (int)$proposal->get('IDorganization') !== $organizationId || (string)$proposal->get('target_type') !== DeferredProposal::TARGET_HOLON || (string)$proposal->get('status') !== DeferredProposal::STATUS_PENDING)) $respond(404, ['status' => false, 'message' => 'Cette modification ne peut plus être modifiée.']);
$rawPayload = json_decode((string)($_POST['payload'] ?? '{}'), true);
if (!is_array($rawPayload)) $respond(422, ['status' => false, 'message' => 'Le contenu de l’éditeur est invalide.']);
$organization = new Organization();
if (!$organization->load($organizationId)) $respond(404, ['status' => false, 'message' => 'Organisation introuvable.']);
$editingHolon = $operation === DeferredProposal::OPERATION_CREATE ? null : $targetHolon;
$contextHolon = $operation === DeferredProposal::OPERATION_CREATE ? $targetHolon : $targetHolon->getParentHolon();
if (!$contextHolon instanceof \dbObject\Holon) $respond(422, ['status' => false, 'message' => 'Le contexte de cet espace est invalide.']);
$afterState = [];
if ($operation !== DeferredProposal::OPERATION_DELETE) {
    $editorData = $organization->getHolonCreationEditorData((int)$contextHolon->getId(), $editingHolon ? (int)$editingHolon->getId() : 0, true);
    $allowedTemplateIds = array_map(static fn (array $template): int => (int)($template['id'] ?? 0), (array)($editorData['templateCatalog'] ?? []));
    if (!in_array((int)($rawPayload['templateId'] ?? 0), $allowedTemplateIds, true)) $respond(422, ['status' => false, 'message' => 'Le modèle choisi n’est pas disponible dans cet espace.']);
    $validation = DecisionGovernanceAction::validateHolonState(['editor_payload' => $rawPayload], $contextHolon, $editingHolon);
    if (empty($validation['status'])) $respond(422, ['status' => false, 'message' => (string)($validation['message'] ?? 'Modification invalide.')]);
    $afterState = (array)$validation['state'];
}
$proposal->set('IDorganization', $organizationId); $proposal->set('IDholon', (int)$contextHolon->getId()); $proposal->set('IDuser_author', $userId); $proposal->set('target_type', DeferredProposal::TARGET_HOLON); $proposal->set('operation', $operation); $proposal->set('target_id', $operation === DeferredProposal::OPERATION_CREATE ? null : (int)$targetHolon->getId()); $proposal->set('before_state', $operation === DeferredProposal::OPERATION_CREATE ? [] : DecisionGovernanceAction::captureHolonEditorState($targetHolon, $organization)); $proposal->set('after_state', $afterState); $proposal->set('IDdocument_pv_point', $pointId); if ($proposalId <= 0) $proposal->set('position', count(DeferredProposal::getForPvPoint($pointId)) + 1); $proposal->set('status', DeferredProposal::STATUS_PENDING); $proposal->set('parameters', ['payload_version' => 1, 'editor' => 'holon']);
$result = $proposal->save(); if (!is_array($result) || empty($result['status'])) $respond(500, ['status' => false, 'message' => 'Impossible d’enregistrer la modification.']);
$respond(200, ['status' => true, 'id' => (int)$proposal->getId(), 'pointId' => $pointId]);
