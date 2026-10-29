<?php
require_once __DIR__ . '/ui_translations.php';
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/common/choice/deferred-editor-fields.php';

use dbObject\ControlActivity;
use dbObject\DeferredProposal;
use dbObject\Document;
use dbObject\DocumentPvPoint;
use dbObject\StatIndicator;

$organizationId = (int)($_GET['oid'] ?? ($_SESSION['currentOrganization'] ?? 0));
$pointId = (int)($_GET['point_id'] ?? 0);
$proposalId = (int)($_GET['proposal_id'] ?? 0);
$targetType = trim((string)($_GET['target_type'] ?? ''));
$operation = trim((string)($_GET['operation'] ?? DeferredProposal::OPERATION_CREATE));
$holonId = (int)($_GET['holon_id'] ?? 0);
$objectId = (int)($_GET['object_id'] ?? 0);
$allowedTypes = [DeferredProposal::TARGET_RECURRING_TASK, DeferredProposal::TARGET_INDICATOR];
$allowedOperations = [DeferredProposal::OPERATION_CREATE, DeferredProposal::OPERATION_UPDATE, DeferredProposal::OPERATION_DELETE];
$point = new DocumentPvPoint();
$document = new Document();
$userId = (int)commonGetCurrentUserId();
if (!in_array($targetType, $allowedTypes, true)
    || !in_array($operation, $allowedOperations, true)
    || !$point->load($pointId)
    || !$document->load((int)$point->get('IDdocument'))
    || (int)$document->get('IDorganization') !== $organizationId
    || !$document->canUserProposePvPointChange($point, $userId)
    || $point->isHandled()
    || $document->getPvStage() === Document::PV_STAGE_REVIEW) {
    http_response_code(403); echo ('<div class="omo-empty-state">' . htmlspecialchars(omoDeferredProposalsUiT('acces_refuse'), ENT_QUOTES, 'UTF-8') . '</div>'); exit;
}
if ($proposalId > 0) {
    $proposal = new DeferredProposal();
    if (!$proposal->load($proposalId) || (int)$proposal->get('IDdocument_pv_point') !== $pointId
        || (string)$proposal->get('target_type') !== $targetType || (string)$proposal->get('status') !== DeferredProposal::STATUS_PENDING) {
        http_response_code(404); echo ('<div class="omo-empty-state">' . htmlspecialchars(omoDeferredProposalsUiT('cette_modification_ne_peut_plus_etre_modifiee'), ENT_QUOTES, 'UTF-8') . '</div>'); exit;
    }
    $operation = (string)$proposal->get('operation');
    $holonId = (int)$proposal->get('IDholon');
    $objectId = (int)$proposal->get('target_id');
}
$contextHolon = DeferredProposal::loadAllowedObjectTargetHolon($organizationId, $holonId, $operation, (int)$document->getPvContextHolonId(), $targetType);
if (!$contextHolon) { http_response_code(403); echo ('<div class="omo-empty-state">' . htmlspecialchars(omoDeferredProposalsUiT('le_collectif_du_pv_ne_dispose_pas_du_droit_necessaire'), ENT_QUOTES, 'UTF-8') . '</div>'); exit; }
$object = $targetType === DeferredProposal::TARGET_RECURRING_TASK ? new ControlActivity() : new StatIndicator();
if ($operation !== DeferredProposal::OPERATION_CREATE
    && (!$object->load($objectId) || (int)$object->get('IDorganization') !== $organizationId || (int)$object->get('IDholon') !== $holonId || (int)$object->get('active') !== 1)) {
    http_response_code(404); echo ('<div class="omo-empty-state">' . htmlspecialchars(omoDeferredProposalsUiT('l_element_demande_est_introuvable'), ENT_QUOTES, 'UTF-8') . '</div>'); exit;
}
if ($operation === DeferredProposal::OPERATION_CREATE && $objectId !== 0) { http_response_code(422); echo ('<div class="omo-empty-state">' . htmlspecialchars(omoDeferredProposalsUiT('selection_invalide'), ENT_QUOTES, 'UTF-8') . '</div>'); exit; }
$state = $proposalId > 0
    ? DeferredProposal::normalizeState($proposal->get($operation === DeferredProposal::OPERATION_DELETE ? 'before_state' : 'after_state'))
    : ($operation === DeferredProposal::OPERATION_CREATE ? [] : ($targetType === DeferredProposal::TARGET_RECURRING_TASK ? DeferredProposal::captureRecurringTaskState($object) : DeferredProposal::captureIndicatorState($object)));
$GLOBALS['omoDeferredObjectEditor'] = [
    'origin' => 'pv',
    'organizationId' => $organizationId,
    'holonId' => $holonId,
    'objectId' => $objectId,
    'targetType' => $targetType,
    'operation' => $operation,
    'state' => $state,
    'pointId' => $pointId,
    'proposalId' => $proposalId,
    'creationHolonCatalog' => $operation === DeferredProposal::OPERATION_CREATE
        ? DeferredProposal::getObjectTargetHolonCatalog($organizationId, (int)$document->getPvContextHolonId(), $targetType)
        : [],
];
?>
<?php if ($operation === DeferredProposal::OPERATION_DELETE): ?>
<section class="generic-section generic-section--stack generic-section--roomy" data-deferred-object-editor>
    <p><?= htmlspecialchars(omoDeferredProposalsUiT('la_suppression_sera_appliquee_uniquement_apres_validation_de_la_proposition_et_si_l_elemen'), ENT_QUOTES, 'UTF-8') ?></p>
    <strong><?= omoApiEscape((string)($state[$targetType === DeferredProposal::TARGET_INDICATOR ? 'name' : 'title'] ?? '')) ?></strong>
    <div class="generic-action-row"><button type="button" class="generic-action-button generic-action-button--danger" data-save><?= htmlspecialchars(omoDeferredProposalsUiT('ajouter_la_suppression'), ENT_QUOTES, 'UTF-8') ?></button><button type="button" class="generic-action-button generic-action-button--secondary" data-cancel><?= htmlspecialchars(omoDeferredProposalsUiT('annuler'), ENT_QUOTES, 'UTF-8') ?></button></div>
</section>
<script>
(function(){const root=document.querySelector('[data-deferred-object-editor]');if(!root)return;root.querySelector('[data-cancel]').addEventListener('click',()=>window.commonTopbarCloseModal?.());root.querySelector('[data-save]').addEventListener('click',()=>{const data=new FormData();Object.entries(<?= json_encode(['oid'=>$organizationId,'point_id'=>$pointId,'proposal_id'=>$proposalId,'target_type'=>$targetType,'operation'=>$operation,'holon_id'=>$holonId,'object_id'=>$objectId]) ?>).forEach(([key,value])=>data.set(key,String(value)));fetch('/omo/api/deferred_proposals/pv_object_save.php',{method:'POST',body:data,credentials:'same-origin'}).then(response=>response.json().then(result=>({ok:response.ok,result}))).then(({ok,result})=>{if(!ok||!result.status)throw new Error(result.message||'Enregistrement impossible.');window.dispatchEvent(new CustomEvent('omo-deferred-proposal-saved',{detail:{pointId:<?= $pointId ?>,proposalId:Number(result.id||0)}}));window.commonTopbarCloseModal?.()}).catch(error=>window.commonNotify?.(error.message,'error'))})}());
</script>
<?php else: ?>
<?php require dirname(__DIR__) . ($targetType === DeferredProposal::TARGET_RECURRING_TASK ? '/activities/edit.php' : '/stats/edit.php'); ?>
<?php endif; ?>
