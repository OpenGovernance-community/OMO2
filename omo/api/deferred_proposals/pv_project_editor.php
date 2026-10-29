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
    'create' => ['text' => 'Créer le projet', 'context' => 'Deferred project creation submit button'],
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
    || !$document->canUserProposePvPointChange($point, $userId)
    || $point->isHandled()
    || $document->getPvStage() === Document::PV_STAGE_REVIEW
    || !in_array($operation, [DeferredProposal::OPERATION_CREATE, DeferredProposal::OPERATION_PROPOSE, DeferredProposal::OPERATION_UPDATE, DeferredProposal::OPERATION_DELETE], true)) {
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
$isCreation = DeferredProposal::isProjectCreationOperation($operation);
if (!$isCreation && (!$project->load($projectId) || (int)$project->get('IDorganization') !== $organizationId || (int)$project->get('IDholon') !== $holonId || !$project->currentViewerCanAccessVisibility($userId))) {
    http_response_code(404); echo '<div class="omo-empty-state">' . omoApiEscape($tr('unavailable')) . '</div>'; exit;
}
$state = $proposal instanceof DeferredProposal && $operation !== DeferredProposal::OPERATION_DELETE
    ? DeferredProposal::normalizeProjectState(DeferredProposal::normalizeState($proposal->get('after_state')), $operation === DeferredProposal::OPERATION_UPDATE ? $project : null)
    : ($operation === DeferredProposal::OPERATION_UPDATE ? DeferredProposal::captureProjectState($project) : DeferredProposal::normalizeProjectState(['IDholon' => $holonId, 'IDuser_proposed' => $userId, 'status' => Project::STATUS_IN_PROGRESS, 'project_size' => Project::SIZE_M]));
$memberLabels = Project::getProposerOptions($organizationId);
$children = new ArrayProject(); if ($operation === DeferredProposal::OPERATION_DELETE) $children->loadForParent($projectId, true);
$projectCatalog = DeferredProposal::getProjectTargetHolonCatalog($organizationId, $collectiveHolonId);
$saveLabel = $isCreation ? $tr($operation === DeferredProposal::OPERATION_PROPOSE ? 'propose' : 'create') : $tr('save');
?>
<section class="generic-section generic-section--stack generic-section--roomy omo-deferred-project-editor" data-deferred-project-editor>
<?php if ($operation === DeferredProposal::OPERATION_DELETE): ?>
    <p><?= omoApiEscape($tr('delete_intro')) ?></p>
    <?php if (count($children) > 0): ?><p class="generic-feedback"><?= omoApiEscape($tr('delete_children')) ?></p><?php endif; ?>
    <div class="generic-action-row"><button type="button" class="generic-action-button generic-action-button--danger" data-deferred-project-save<?= count($children) > 0 ? ' disabled' : '' ?>><?= omoApiEscape($tr('delete_confirm')) ?></button><button type="button" class="generic-action-button generic-action-button--secondary" data-deferred-project-cancel><?= omoApiEscape($tr('cancel')) ?></button></div>
<?php else: ?>
    <form class="generic-form-stack" data-deferred-project-form>
        <?php omoDeferredEditorRenderFields('project', $state, $memberLabels, $isCreation
            ? static fn () => omoDeferredEditorRenderCreationHolonField($organizationId, $holonId, $projectCatalog, $operation)
            : null); ?>
        <p class="generic-feedback" data-deferred-project-feedback hidden></p><div class="generic-action-row"><button class="generic-action-button generic-action-button--main" type="submit"><?= omoApiEscape($saveLabel) ?></button><button class="generic-action-button generic-action-button--secondary" type="button" data-deferred-project-cancel><?= omoApiEscape($tr('cancel')) ?></button></div>
    </form>
<?php endif; ?>
</section>
<script src="<?= commonAssetUrl('/common/choice/proposal-html.js') ?>"></script>
<script src="<?= commonAssetUrl('/common/choice/deferred-project-fields.js') ?>"></script>
<script>if(window.omoInitDeferredProjectFields){window.omoInitDeferredProjectFields(document);}</script>
<script>
(function () {
    const root = document.querySelector('[data-deferred-project-editor]');
    if (!root) return;
    const feedback = root.querySelector('[data-deferred-project-feedback]');
    const save = root.querySelector('[data-deferred-project-save]');
    const form = root.querySelector('[data-deferred-project-form]');
    const holonField = form?.elements.namedItem('holon_id');
    function show(message) {
        if (message && typeof window.commonNotify === 'function') {
            if (feedback) { feedback.hidden = true; feedback.textContent = ''; }
            window.commonNotify(String(message), 'error');
        } else if (feedback) {
            feedback.hidden = !message; feedback.textContent = message || '';
        }
    }
    function submit(data) {
        if (save) save.disabled = true;
        fetch('/omo/api/deferred_proposals/pv_project_save.php', {method: 'POST', body: data, credentials: 'same-origin'})
            .then(response => response.json().then(payload => ({ok: response.ok, payload})))
            .then(({ok, payload}) => {
                if (!ok || !payload.status) throw new Error(payload.message || <?= json_encode($tr('save_error')) ?>);
                window.dispatchEvent(new CustomEvent('omo-deferred-proposal-saved', {detail: {
                    pointId: Number(payload.pointId || <?= $pointId ?>), proposalId: Number(payload.id || 0)
                }}));
                window.commonTopbarCloseModal?.();
            }).catch(error => {
                show(error.message || <?= json_encode($tr('save_error')) ?>);
                if (save) save.disabled = false;
            });
    }
    root.querySelectorAll('[data-deferred-project-cancel]').forEach(button => button.addEventListener('click', () => {
        window.commonTopbarCloseModal?.();
    }));
    if (form) form.addEventListener('submit', event => {
        event.preventDefault();
        if (!form.reportValidity()) return;
        const htmlEditor = form.querySelector('[data-omo-deferred-html="description"]');
        if (htmlEditor && window.omoProposalHtml) form.elements.namedItem('description').value = window.omoProposalHtml.getValue(htmlEditor);
        const data = new FormData(form);
        data.set('oid', '<?= $organizationId ?>');
        data.set('point_id', '<?= $pointId ?>');
        data.set('proposal_id', '<?= $proposalId ?>');
        data.set('operation', <?= json_encode($operation) ?>);
        data.set('holon_id', holonField ? holonField.value : '<?= $holonId ?>');
        data.set('project_id', '<?= $projectId ?>');
        submit(data);
    });
    if (save) save.addEventListener('click', () => {
        const data = new FormData();
        Object.entries(<?= json_encode(['oid' => $organizationId, 'point_id' => $pointId, 'proposal_id' => $proposalId, 'operation' => 'delete', 'holon_id' => $holonId, 'project_id' => $projectId]) ?>)
            .forEach(([key, value]) => data.set(key, String(value)));
        submit(data);
    });
}());
</script>
