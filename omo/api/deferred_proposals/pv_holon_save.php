<?php
require_once __DIR__ . '/ui_translations.php';
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/common/choice/holon-removal-fields.php';

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
if ($organizationId <= 0 || $pointId <= 0 || !$point->load($pointId) || !$document->load((int)$point->get('IDdocument')) || (int)$document->get('IDorganization') !== $organizationId || !$document->canUserProposePvPointChange($point, $userId) || $point->isHandled() || $document->getPvStage() === Document::PV_STAGE_REVIEW) $respond(403, ['status' => false, 'message' => omoDeferredProposalsUiT('acces_refuse')]);
if (!in_array($operation, [DeferredProposal::OPERATION_CREATE, DeferredProposal::OPERATION_UPDATE, DeferredProposal::OPERATION_DELETE, DeferredProposal::OPERATION_MOVE], true)) $respond(422, ['status' => false, 'message' => omoDeferredProposalsUiT('action_invalide')]);
$collectiveHolonId = (int)$document->getPvContextHolonId();
$targetHolon = DeferredProposal::loadAllowedHolonTargetHolon($organizationId, $holonId, $operation, $collectiveHolonId);
if (!$targetHolon) $respond(403, ['status' => false, 'message' => omoDeferredProposalsUiT('le_collectif_du_pv_ne_dispose_pas_du_droit_necessaire_dans_cet_espace')]);

$proposal = new DeferredProposal();
if ($proposalId > 0 && (!$proposal->load($proposalId) || (int)$proposal->get('IDdocument_pv_point') !== $pointId || (int)$proposal->get('IDorganization') !== $organizationId || (string)$proposal->get('target_type') !== DeferredProposal::TARGET_HOLON || (string)$proposal->get('status') !== DeferredProposal::STATUS_PENDING)) $respond(404, ['status' => false, 'message' => omoDeferredProposalsUiT('cette_modification_ne_peut_plus_etre_modifiee')]);
if ($proposalId > 0 && ($operation === DeferredProposal::OPERATION_MOVE || (string)$proposal->get('operation') === DeferredProposal::OPERATION_MOVE)
    && ((string)$proposal->get('operation') !== $operation || (int)$proposal->get('target_id') !== $holonId)) {
    $respond(422, ['status' => false, 'message' => omoDeferredProposalsUiT('la_cible_et_le_type_du_deplacement_existant_ne_peuvent_pas_etre_remplaces')]);
}
$rawPayload = json_decode((string)($_POST['payload'] ?? '{}'), true);
if (!is_array($rawPayload)) $respond(422, ['status' => false, 'message' => omoDeferredProposalsUiT('le_contenu_de_l_editeur_est_invalide')]);
$organization = new Organization();
if (!$organization->load($organizationId)) $respond(404, ['status' => false, 'message' => omoDeferredProposalsUiT('organisation_introuvable')]);
$editingHolon = $operation === DeferredProposal::OPERATION_CREATE ? null : $targetHolon;
$contextHolon = $operation === DeferredProposal::OPERATION_CREATE ? $targetHolon : $targetHolon->getParentHolon();
if (!$contextHolon instanceof \dbObject\Holon) $respond(422, ['status' => false, 'message' => omoDeferredProposalsUiT('le_contexte_de_cet_espace_est_invalide')]);
$afterState = [];
if ($operation === DeferredProposal::OPERATION_DELETE) {
    if (!\dbObject\Authority::validateHolonRemovalChoice($rawPayload['authority_choice'] ?? null)) {
        $respond(422, ['status' => false, 'message' => omoHolonRemovalT('required')]);
    }
    $afterState = ['authority_choice' => $rawPayload['authority_choice']];
}
if ($operation === DeferredProposal::OPERATION_MOVE) {
    $moveBeforeState = $proposalId > 0 ? DeferredProposal::normalizeState($proposal->get('before_state')) : DeferredProposal::captureHolonMoveState($targetHolon);
    $validation = DeferredProposal::validateHolonMove($organization, $targetHolon, (int)($rawPayload['parent_id'] ?? 0), $collectiveHolonId);
    if (empty($validation['status'])) $respond(422, $validation);
    $afterState = $validation['state'];
} elseif ($operation !== DeferredProposal::OPERATION_DELETE) {
    $editorData = $organization->getHolonCreationEditorData((int)$contextHolon->getId(), $editingHolon ? (int)$editingHolon->getId() : 0, true);
    $allowedTemplateIds = array_map(static fn (array $template): int => (int)($template['id'] ?? 0), (array)($editorData['templateCatalog'] ?? []));
    if (!in_array((int)($rawPayload['templateId'] ?? 0), $allowedTemplateIds, true)) $respond(422, ['status' => false, 'message' => omoDeferredProposalsUiT('le_modele_choisi_n_est_pas_disponible_dans_cet_espace')]);
    $validation = DecisionGovernanceAction::validateHolonState(['editor_payload' => $rawPayload], $contextHolon, $editingHolon);
    if (empty($validation['status'])) $respond(422, ['status' => false, 'message' => (string)($validation['message'] ?? omoDeferredProposalsUiT('modification_invalide'))]);
    $afterState = (array)$validation['state'];
}
$proposal->set('IDorganization', $organizationId); $proposal->set('IDholon', (int)$contextHolon->getId()); $proposal->set('IDuser_author', $userId); $proposal->set('target_type', DeferredProposal::TARGET_HOLON); $proposal->set('operation', $operation); $proposal->set('target_id', $operation === DeferredProposal::OPERATION_CREATE ? null : (int)$targetHolon->getId()); $proposal->set('before_state', $operation === DeferredProposal::OPERATION_CREATE ? [] : ($operation === DeferredProposal::OPERATION_MOVE ? $moveBeforeState : DecisionGovernanceAction::captureHolonEditorState($targetHolon, $organization))); $proposal->set('after_state', $afterState); $proposal->set('IDdocument_pv_point', $pointId); if ($proposalId <= 0) $proposal->set('position', count(DeferredProposal::getForPvPoint($pointId)) + 1); $proposal->set('status', DeferredProposal::STATUS_PENDING); $proposal->set('parameters', ['payload_version' => 1, 'editor' => 'holon']);
$result = $proposal->save(); if (!is_array($result) || empty($result['status'])) $respond(500, ['status' => false, 'message' => omoDeferredProposalsUiT('impossible_d_enregistrer_la_modification')]);
$respond(200, ['status' => true, 'id' => (int)$proposal->getId(), 'pointId' => $pointId]);
