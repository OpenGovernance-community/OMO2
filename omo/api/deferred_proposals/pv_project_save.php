<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\DeferredProposal;
use dbObject\Document;
use dbObject\DocumentPvPoint;
use dbObject\Holon;
use dbObject\Project;

header('Content-Type: application/json; charset=UTF-8');
$respond = static function (int $code, array $payload): void { http_response_code($code); echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit; };
$organizationId = (int)($_POST['oid'] ?? ($_SESSION['currentOrganization'] ?? 0));
$pointId = (int)($_POST['point_id'] ?? 0);
$proposalId = (int)($_POST['proposal_id'] ?? 0);
$operation = trim((string)($_POST['operation'] ?? ''));
$holonId = (int)($_POST['holon_id'] ?? 0);
$projectId = (int)($_POST['project_id'] ?? 0);
$userId = (int)commonGetCurrentUserId();
$point = new DocumentPvPoint(); $document = new Document();
if ($organizationId <= 0 || $pointId <= 0 || !$point->load($pointId) || !$document->load((int)$point->get('IDdocument')) || (int)$document->get('IDorganization') !== $organizationId || !$document->canUserManagePvDocument($userId) || $point->isHandled() || $document->getPvStage() === Document::PV_STAGE_REVIEW) $respond(403, ['status' => false, 'message' => 'Accès refusé.']);
if (!in_array($operation, [DeferredProposal::OPERATION_CREATE, DeferredProposal::OPERATION_UPDATE, DeferredProposal::OPERATION_DELETE], true)) $respond(422, ['status' => false, 'message' => 'Action invalide.']);
$collectiveHolonId = (int)$document->getPvContextHolonId();
$contextHolon = DeferredProposal::loadAllowedProjectTargetHolon($organizationId, $holonId, $operation, $collectiveHolonId);
if (!$contextHolon instanceof Holon) $respond(403, ['status' => false, 'message' => 'Le collectif du PV ne dispose pas du droit nécessaire dans cet espace.']);
$proposal = new DeferredProposal();
if ($proposalId > 0 && (!$proposal->load($proposalId) || (int)$proposal->get('IDdocument_pv_point') !== $pointId || (int)$proposal->get('IDorganization') !== $organizationId || (string)$proposal->get('target_type') !== DeferredProposal::TARGET_PROJECT || (string)$proposal->get('status') !== DeferredProposal::STATUS_PENDING)) $respond(404, ['status' => false, 'message' => 'Cette modification ne peut plus être modifiée.']);
$project = new Project();
if ($operation !== DeferredProposal::OPERATION_CREATE && (!$project->load($projectId) || (int)$project->get('IDorganization') !== $organizationId || (int)$project->get('IDholon') !== $holonId)) $respond(404, ['status' => false, 'message' => 'Le projet sélectionné est introuvable dans cet espace.']);
$afterState = [];
if ($operation !== DeferredProposal::OPERATION_DELETE) {
    $afterState = DeferredProposal::normalizeProjectState($_POST, $operation === DeferredProposal::OPERATION_UPDATE ? $project : null);
    $afterState['IDholon'] = $holonId;
    if ($afterState['title'] === '') $respond(422, ['status' => false, 'message' => 'Le titre du projet est obligatoire.']);
    if ($afterState['planned_start_date'] !== '' && $afterState['planned_end_date'] !== '' && $afterState['planned_end_date'] < $afterState['planned_start_date']) $respond(422, ['status' => false, 'message' => 'La fin planifiée doit suivre le début planifié.']);
    if ($afterState['status'] === Project::STATUS_BLOCKED && ($afterState['blocked_reason'] === '' || $afterState['blocked_until'] === '')) $respond(422, ['status' => false, 'message' => 'Un projet bloqué doit préciser le motif et la date de relance.']);
}
$proposal->set('IDorganization', $organizationId); $proposal->set('IDholon', $holonId); $proposal->set('IDuser_author', $userId); $proposal->set('target_type', DeferredProposal::TARGET_PROJECT); $proposal->set('operation', $operation); $proposal->set('target_id', $operation === DeferredProposal::OPERATION_CREATE ? null : $projectId); $proposal->set('before_state', $operation === DeferredProposal::OPERATION_CREATE ? [] : DeferredProposal::captureProjectState($project)); $proposal->set('after_state', $afterState); $proposal->set('IDdocument_pv_point', $pointId); if ($proposalId <= 0) $proposal->set('position', count(DeferredProposal::getForPvPoint($pointId)) + 1); $proposal->set('status', DeferredProposal::STATUS_PENDING); $proposal->set('parameters', ['payload_version' => 1, 'editor' => 'project']);
$result = $proposal->save(); if (!is_array($result) || empty($result['status'])) $respond(500, ['status' => false, 'message' => 'Impossible d’enregistrer la modification.']);
$respond(200, ['status' => true, 'id' => (int)$proposal->getId(), 'pointId' => $pointId]);
