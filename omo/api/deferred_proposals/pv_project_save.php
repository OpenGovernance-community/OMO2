<?php
require_once __DIR__ . '/ui_translations.php';
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
if ($organizationId <= 0 || $pointId <= 0 || !$point->load($pointId) || !$document->load((int)$point->get('IDdocument')) || (int)$document->get('IDorganization') !== $organizationId || !$document->canUserProposePvPointChange($point, $userId) || $point->isHandled() || $document->getPvStage() === Document::PV_STAGE_REVIEW) $respond(403, ['status' => false, 'message' => omoDeferredProposalsUiT('acces_refuse')]);
if (!in_array($operation, [DeferredProposal::OPERATION_CREATE, DeferredProposal::OPERATION_PROPOSE, DeferredProposal::OPERATION_UPDATE, DeferredProposal::OPERATION_DELETE], true)) $respond(422, ['status' => false, 'message' => omoDeferredProposalsUiT('action_invalide')]);
$isCreation = DeferredProposal::isProjectCreationOperation($operation);
$collectiveHolonId = (int)$document->getPvContextHolonId();
$contextHolon = DeferredProposal::loadAllowedProjectTargetHolon($organizationId, $holonId, $operation, $collectiveHolonId);
if (!$contextHolon instanceof Holon) $respond(403, ['status' => false, 'message' => omoDeferredProposalsUiT('le_collectif_du_pv_ne_dispose_pas_du_droit_necessaire_dans_cet_espace')]);
$proposal = new DeferredProposal();
if ($proposalId > 0 && (!$proposal->load($proposalId) || (int)$proposal->get('IDdocument_pv_point') !== $pointId || (int)$proposal->get('IDorganization') !== $organizationId || (string)$proposal->get('target_type') !== DeferredProposal::TARGET_PROJECT || (string)$proposal->get('status') !== DeferredProposal::STATUS_PENDING)) $respond(404, ['status' => false, 'message' => omoDeferredProposalsUiT('cette_modification_ne_peut_plus_etre_modifiee')]);
if (($isCreation && $projectId !== 0)
    || ($proposalId > 0 && ((string)$proposal->get('operation') !== $operation
        || (!$isCreation && (int)$proposal->get('IDholon') !== $holonId)
        || (int)$proposal->get('target_id') !== $projectId))) {
    $respond(422, ['status' => false, 'message' => omoDeferredProposalsUiT('le_contexte_de_cette_modification_a_change')]);
}
$project = new Project();
if (!$isCreation && (!$project->load($projectId) || (int)$project->get('IDorganization') !== $organizationId || (int)$project->get('IDholon') !== $holonId || !$project->currentViewerCanAccessVisibility($userId))) $respond(404, ['status' => false, 'message' => omoDeferredProposalsUiT('le_projet_selectionne_est_introuvable_dans_cet_espace')]);
$afterState = [];
if ($operation !== DeferredProposal::OPERATION_DELETE) {
    $afterState = DeferredProposal::normalizeProjectState($_POST, $operation === DeferredProposal::OPERATION_UPDATE ? $project : null);
    $afterState['IDholon'] = $holonId;
    if ((int)$afterState['IDuser_proposed'] > 0
        && !\dbObject\UserOrganization::hasActiveMembership((int)$afterState['IDuser_proposed'], $organizationId)
        && ($isCreation || (int)$afterState['IDuser_proposed'] !== (int)$project->get('IDuser_proposed'))) {
        $respond(422, ['status' => false, 'message' => omoDeferredProposalsUiT('la_personne_proposant_le_projet_doit_etre_membre_de_l_organisation')]);
    }
    if ($afterState['title'] === '') $respond(422, ['status' => false, 'message' => omoDeferredProposalsUiT('le_titre_du_projet_est_obligatoire')]);
    if ($afterState['planned_start_date'] !== '' && $afterState['planned_end_date'] !== '' && $afterState['planned_end_date'] < $afterState['planned_start_date']) $respond(422, ['status' => false, 'message' => omoDeferredProposalsUiT('la_fin_planifiee_doit_suivre_le_debut_planifie')]);
    if ($afterState['status'] === Project::STATUS_BLOCKED && !Project::hasValidBlockedDetails($organizationId, $afterState['blocked_reason'], $afterState['blocked_until'])) $respond(422, ['status' => false, 'message' => omoDeferredProposalsUiT('renseignez_les_champs_de_blocage_obligatoires_selon_les_parametres_de_votre_organisation')]);
}
$proposal->set('IDorganization', $organizationId); $proposal->set('IDholon', $holonId); $proposal->set('IDuser_author', $userId); $proposal->set('target_type', DeferredProposal::TARGET_PROJECT); $proposal->set('operation', $operation); $proposal->set('target_id', $isCreation ? null : $projectId); $proposal->set('before_state', $isCreation ? [] : DeferredProposal::captureProjectState($project)); $proposal->set('after_state', $afterState); $proposal->set('IDdocument_pv_point', $pointId); if ($proposalId <= 0) $proposal->set('position', count(DeferredProposal::getForPvPoint($pointId)) + 1); $proposal->set('status', DeferredProposal::STATUS_PENDING); $proposal->set('parameters', ['payload_version' => 1, 'editor' => 'project']);
$result = $proposal->save(); if (!is_array($result) || empty($result['status'])) $respond(500, ['status' => false, 'message' => omoDeferredProposalsUiT('impossible_d_enregistrer_la_modification')]);
$respond(200, ['status' => true, 'id' => (int)$proposal->getId(), 'pointId' => $pointId]);
