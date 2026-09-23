<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/common/choice/deferred-editor-fields.php';

use dbObject\ArrayProject;
use dbObject\DeferredProposal;
use dbObject\Document;
use dbObject\DocumentPvPoint;
use dbObject\Holon;
use dbObject\Project;

$sourceLang = [
    'title_create' => ['text' => 'Nouveau projet', 'context' => 'Deferred project proposal editor title'],
    'title_update' => ['text' => 'Modifier le projet', 'context' => 'Deferred project proposal editor title'],
    'title_delete' => ['text' => 'Supprimer le projet', 'context' => 'Deferred project proposal editor title'],
    'denied' => ['text' => 'Accès refusé.', 'context' => 'Deferred project proposal forbidden error'],
    'unavailable' => ['text' => 'Cette modification ne peut plus être modifiée.', 'context' => 'Deferred project proposal unavailable error'],
    'save' => ['text' => 'Enregistrer la modification', 'context' => 'Deferred project proposal save button'],
    'propose' => ['text' => 'Proposer le projet', 'context' => 'Deferred project proposal submit button when only proposal permission is granted'],
    'cancel' => ['text' => 'Annuler', 'context' => 'Deferred project proposal cancel button'],
    'delete_intro' => ['text' => 'La suppression sera appliquée uniquement lorsque cette modification sera validée.', 'context' => 'Deferred project deletion confirmation intro'],
    'delete_children' => ['text' => 'Ce projet contient des sous-projets. Ils doivent être traités avant sa suppression.', 'context' => 'Deferred project deletion children warning'],
    'delete_confirm' => ['text' => 'Proposer la suppression', 'context' => 'Deferred project deletion confirm button'],
    'save_error' => ['text' => 'Impossible d’enregistrer la modification.', 'context' => 'Deferred project proposal save error'],
];
$lang = omoLoadTranslationBundle('omo_deferred_project_editor', $sourceLang);
$tr = static fn (string $key): string => t($key, [], $lang, $sourceLang);

$organizationId = (int)($_GET['oid'] ?? ($_SESSION['currentOrganization'] ?? 0));
$pointId = (int)($_GET['point_id'] ?? 0);
$proposalId = (int)($_GET['proposal_id'] ?? 0);
$operation = trim((string)($_GET['operation'] ?? DeferredProposal::OPERATION_CREATE));
$holonId = (int)($_GET['holon_id'] ?? 0);
$projectId = (int)($_GET['project_id'] ?? 0);
$userId = (int)commonGetCurrentUserId();
$point = new DocumentPvPoint();
$document = new Document();
if ($organizationId <= 0 || $pointId <= 0 || !$point->load($pointId)
    || !$document->load((int)$point->get('IDdocument'))
    || (int)$document->get('IDorganization') !== $organizationId
    || !$document->canUserManagePvDocument($userId)
    || $point->isHandled()
    || $document->getPvStage() === Document::PV_STAGE_REVIEW
    || !in_array($operation, [DeferredProposal::OPERATION_CREATE, DeferredProposal::OPERATION_UPDATE, DeferredProposal::OPERATION_DELETE], true)) {
    http_response_code(403); echo '<div class="omo-empty-state">' . omoApiEscape($tr('denied')) . '</div>'; exit;
}
$collectiveHolonId = (int)$document->getPvContextHolonId();
$proposal = null;
if ($proposalId > 0) {
    $proposal = new DeferredProposal();
    if (!$proposal->load($proposalId) || (int)$proposal->get('IDdocument_pv_point') !== $pointId || (int)$proposal->get('IDorganization') !== $organizationId || (string)$proposal->get('target_type') !== DeferredProposal::TARGET_PROJECT || (string)$proposal->get('status') !== DeferredProposal::STATUS_PENDING) {
        http_response_code(404); echo '<div class="omo-empty-state">' . omoApiEscape($tr('unavailable')) . '</div>'; exit;
    }
    $operation = (string)$proposal->get('operation'); $holonId = (int)$proposal->get('IDholon'); $projectId = (int)$proposal->get('target_id');
}
$contextHolon = DeferredProposal::loadAllowedProjectTargetHolon($organizationId, $holonId, $operation, $collectiveHolonId);
if (!$contextHolon instanceof Holon) { http_response_code(403); echo '<div class="omo-empty-state">' . omoApiEscape($tr('denied')) . '</div>'; exit; }
$project = new Project();
if ($operation !== DeferredProposal::OPERATION_CREATE && (!$project->load($projectId) || (int)$project->get('IDorganization') !== $organizationId || (int)$project->get('IDholon') !== $holonId)) {
    http_response_code(404); echo '<div class="omo-empty-state">' . omoApiEscape($tr('unavailable')) . '</div>'; exit;
}
$state = $proposal instanceof DeferredProposal && $operation !== DeferredProposal::OPERATION_DELETE
    ? DeferredProposal::normalizeProjectState(DeferredProposal::normalizeState($proposal->get('after_state')), $operation === DeferredProposal::OPERATION_UPDATE ? $project : null)
    : ($operation === DeferredProposal::OPERATION_UPDATE ? DeferredProposal::captureProjectState($project) : DeferredProposal::normalizeProjectState(['IDholon' => $holonId, 'status' => Project::STATUS_IN_PROGRESS, 'project_size' => Project::SIZE_M]));
$children = new ArrayProject(); if ($operation === DeferredProposal::OPERATION_DELETE) $children->loadForParent($projectId, true);
$projectCatalog = DeferredProposal::getProjectTargetHolonCatalog($organizationId, $collectiveHolonId);
$saveLabel = $operation === DeferredProposal::OPERATION_CREATE
    && (string)($projectCatalog[$holonId]['project_creation_mode'] ?? '') === 'propose'
    ? $tr('propose')
    : $tr('save');
?>
<section class="generic-section generic-section--stack generic-section--roomy omo-deferred-project-editor" data-deferred-project-editor>
<?php if ($operation === DeferredProposal::OPERATION_DELETE): ?>
    <p><?= omoApiEscape($tr('delete_intro')) ?></p>
    <?php if (count($children) > 0): ?><p class="generic-feedback"><?= omoApiEscape($tr('delete_children')) ?></p><?php endif; ?>
    <div class="generic-action-row"><button type="button" class="generic-action-button generic-action-button--danger" data-deferred-project-save<?= count($children) > 0 ? ' disabled' : '' ?>><?= omoApiEscape($tr('delete_confirm')) ?></button><button type="button" class="generic-action-button generic-action-button--secondary" data-deferred-project-cancel><?= omoApiEscape($tr('cancel')) ?></button></div>
<?php else: ?>
    <form class="generic-form-stack" data-deferred-project-form>
        <?php omoDeferredEditorRenderFields('project', $state); ?>
        <p class="generic-feedback" data-deferred-project-feedback hidden></p><div class="generic-action-row"><button class="generic-action-button generic-action-button--main" type="submit"><?= omoApiEscape($saveLabel) ?></button><button class="generic-action-button generic-action-button--secondary" type="button" data-deferred-project-cancel><?= omoApiEscape($tr('cancel')) ?></button></div>
    </form>
<?php endif; ?>
</section>
<script>
(function(){const root=document.querySelector('[data-deferred-project-editor]');if(!root)return;const feedback=root.querySelector('[data-deferred-project-feedback]');const save=root.querySelector('[data-deferred-project-save]');function show(m){if(feedback){feedback.hidden=!m;feedback.textContent=m||''}}function close(){window.dispatchEvent(new CustomEvent('omo-deferred-proposal-editor-back'))}function submit(data){if(save)save.disabled=true;fetch('/omo/api/deferred_proposals/pv_project_save.php',{method:'POST',body:data,credentials:'same-origin'}).then(r=>r.json().then(p=>({ok:r.ok,p}))).then(r=>{if(!r.ok||!r.p.status)throw new Error(r.p.message||<?= json_encode($tr('save_error')) ?>);window.dispatchEvent(new CustomEvent('omo-deferred-proposal-saved',{detail:{pointId:Number(r.p.pointId||<?= $pointId ?>),proposalId:Number(r.p.id||0)}}));if(window.commonTopbarCloseModal)window.commonTopbarCloseModal()}).catch(e=>{show(e.message||<?= json_encode($tr('save_error')) ?>);if(save)save.disabled=false})}root.querySelectorAll('[data-deferred-project-cancel]').forEach(b=>b.addEventListener('click',close));const form=root.querySelector('[data-deferred-project-form]');if(form)form.addEventListener('submit',e=>{e.preventDefault();if(!form.reportValidity())return;const data=new FormData(form);data.append('oid','<?= $organizationId ?>');data.append('point_id','<?= $pointId ?>');data.append('proposal_id','<?= $proposalId ?>');data.append('operation',<?= json_encode($operation) ?>);data.append('holon_id','<?= $holonId ?>');data.append('project_id','<?= $projectId ?>');submit(data)});if(save)save.addEventListener('click',()=>{const data=new FormData();data.append('oid','<?= $organizationId ?>');data.append('point_id','<?= $pointId ?>');data.append('proposal_id','<?= $proposalId ?>');data.append('operation','delete');data.append('holon_id','<?= $holonId ?>');data.append('project_id','<?= $projectId ?>');submit(data)})}());
</script>
