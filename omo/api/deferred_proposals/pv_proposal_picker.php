<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\DeferredProposal;
use dbObject\Document;
use dbObject\DocumentPvPoint;
use dbObject\Holon;

$sourceLang = [
    'intro' => ['text' => 'Préparez la modification en choisissant son objet, son action et son contexte. Rien ne sera appliqué avant le traitement du point.', 'context' => 'Deferred proposal workflow introduction'],
    'object_type' => ['text' => 'Objet', 'context' => 'Deferred proposal target type label'],
    'rule' => ['text' => 'Règle', 'context' => 'Deferred proposal rule type'],
    'rule_help' => ['text' => 'Règles et politiques de l’espace', 'context' => 'Deferred proposal rule type help'],
    'holon' => ['text' => 'Rôle ou cercle', 'context' => 'Deferred proposal holon type'],
    'holon_help' => ['text' => 'Éléments de la structure', 'context' => 'Deferred proposal holon type help'],
    'project' => ['text' => 'Projet', 'context' => 'Deferred proposal project type'],
    'project_help' => ['text' => 'Projets de l’espace', 'context' => 'Deferred proposal project type help'],
    'action' => ['text' => 'Action', 'context' => 'Deferred proposal operation label'],
    'create' => ['text' => 'Création', 'context' => 'Deferred proposal create operation'],
    'update' => ['text' => 'Modification', 'context' => 'Deferred proposal update operation'],
    'delete' => ['text' => 'Suppression', 'context' => 'Deferred proposal delete operation'],
    'context' => ['text' => 'Contexte', 'context' => 'Deferred proposal context label'],
    'open_context' => ['text' => 'Choisir un contexte dans la structure', 'context' => 'Deferred proposal context picker button'],
    'context_picker' => ['text' => 'Choisir le contexte', 'context' => 'Deferred proposal context picker title'],
    'context_help' => ['text' => 'Les espaces grisés ne sont pas utilisables avec les droits collectifs du PV.', 'context' => 'Deferred proposal context picker help'],
    'object' => ['text' => 'Élément concerné', 'context' => 'Deferred proposal existing object label'],
    'empty' => ['text' => 'Aucun élément disponible dans ce contexte.', 'context' => 'Deferred proposal empty object selector'],
    'loading' => ['text' => 'Chargement…', 'context' => 'Deferred proposal loading label'],
    'create_rule' => ['text' => 'Créer la règle', 'context' => 'Deferred proposal open rule creation editor'],
    'update_rule' => ['text' => 'Modifier la règle', 'context' => 'Deferred proposal open rule update editor'],
    'delete_rule' => ['text' => 'Supprimer la règle', 'context' => 'Deferred proposal open rule deletion editor'],
    'create_holon' => ['text' => 'Créer le rôle ou cercle', 'context' => 'Deferred proposal open holon creation editor'],
    'update_holon' => ['text' => 'Modifier le rôle ou cercle', 'context' => 'Deferred proposal open holon update editor'],
    'delete_holon' => ['text' => 'Supprimer le rôle ou cercle', 'context' => 'Deferred proposal open holon deletion editor'],
    'create_project' => ['text' => 'Créer le projet', 'context' => 'Deferred proposal open project creation editor'],
    'propose_project' => ['text' => 'Proposer un projet', 'context' => 'Deferred proposal open project proposal editor'],
    'update_project' => ['text' => 'Modifier le projet', 'context' => 'Deferred proposal open project update editor'],
    'delete_project' => ['text' => 'Supprimer le projet', 'context' => 'Deferred proposal open project deletion editor'],
    'choose' => ['text' => 'Choisir ce contexte', 'context' => 'Deferred proposal context picker confirm'],
    'cancel' => ['text' => 'Annuler', 'context' => 'Deferred proposal cancel button'],
    'close' => ['text' => 'Fermer', 'context' => 'Deferred proposal context picker close'],
    'allowed' => ['text' => 'Contexte disponible.', 'context' => 'Deferred proposal allowed context'],
    'denied_context' => ['text' => 'Ce contexte n’est pas disponible pour cette action.', 'context' => 'Deferred proposal denied context'],
    'load_error' => ['text' => 'Impossible de charger les éléments de ce contexte.', 'context' => 'Deferred proposal context load error'],
    'editor_error' => ['text' => 'Impossible d’ouvrir l’éditeur.', 'context' => 'Deferred proposal editor load error'],
    'denied' => ['text' => 'Accès refusé.', 'context' => 'Deferred proposal denied message'],
    'unavailable' => ['text' => 'Cette modification ne peut plus être modifiée.', 'context' => 'Deferred proposal unavailable message'],
];
$lang = omoLoadTranslationBundle('omo_deferred_proposal_workflow', $sourceLang);
$tr = static fn (string $key): string => t($key, [], $lang, $sourceLang);

$organizationId = (int)($_GET['oid'] ?? ($_SESSION['currentOrganization'] ?? 0));
$pointId = (int)($_GET['point_id'] ?? 0);
$proposalId = (int)($_GET['proposal_id'] ?? 0);
$userId = (int)commonGetCurrentUserId();
$point = new DocumentPvPoint();
$document = new Document();
if ($organizationId <= 0 || $pointId <= 0 || !$point->load($pointId)
    || !$document->load((int)$point->get('IDdocument'))
    || (int)$document->get('IDorganization') !== $organizationId
    || !$document->canUserManagePvDocument($userId)
    || $point->isHandled()
    || $document->getPvStage() === Document::PV_STAGE_REVIEW) {
    http_response_code(403);
    echo '<div class="omo-empty-state">' . omoApiEscape($tr('denied')) . '</div>';
    exit;
}

$targetType = DeferredProposal::TARGET_RULE;
$operation = DeferredProposal::OPERATION_CREATE;
$contextHolonId = (int)$point->get('IDholon_concerned');
$objectId = 0;
if ($proposalId > 0) {
    $proposal = new DeferredProposal();
    if (!$proposal->load($proposalId)
        || (int)$proposal->get('IDdocument_pv_point') !== $pointId
        || (int)$proposal->get('IDorganization') !== $organizationId
        || !in_array((string)$proposal->get('target_type'), [DeferredProposal::TARGET_RULE, DeferredProposal::TARGET_HOLON, DeferredProposal::TARGET_PROJECT], true)
        || (string)$proposal->get('status') !== DeferredProposal::STATUS_PENDING) {
        http_response_code(404);
        echo '<div class="omo-empty-state">' . omoApiEscape($tr('unavailable')) . '</div>';
        exit;
    }
    $targetType = (string)$proposal->get('target_type');
    $operation = (string)$proposal->get('operation');
    $objectId = (int)$proposal->get('target_id');
    $contextHolonId = (int)$proposal->get('IDholon');
    if ($targetType === DeferredProposal::TARGET_HOLON && $operation !== DeferredProposal::OPERATION_CREATE && $objectId > 0) {
        $targetHolon = new Holon();
        $parentHolon = $targetHolon->load($objectId) ? $targetHolon->getParentHolon() : null;
        if ($parentHolon instanceof Holon) $contextHolonId = (int)$parentHolon->getId();
    }
}
if ($contextHolonId <= 0) $contextHolonId = (int)$document->getPvContextHolonId();
$collectiveHolonId = (int)$document->getPvContextHolonId();

$ruleCatalog = DeferredProposal::getRuleTargetHolonCatalog($organizationId, $collectiveHolonId);
$holonCatalog = DeferredProposal::getHolonTargetHolonCatalog($organizationId, $collectiveHolonId);
$projectCatalog = DeferredProposal::getProjectTargetHolonCatalog($organizationId, $collectiveHolonId);
$projectCreationModes = [];
$contextLabels = [];
foreach ([$ruleCatalog, $holonCatalog, $projectCatalog] as $catalog) {
    foreach ($catalog as $holonId => $entry) $contextLabels[(int)$holonId] = (string)($entry['label'] ?? '');
}
$contextPermissions = [
    DeferredProposal::TARGET_RULE => ['create' => [], 'update' => [], 'delete' => []],
    DeferredProposal::TARGET_HOLON => ['create' => [], 'update' => [], 'delete' => []],
    DeferredProposal::TARGET_PROJECT => ['create' => [], 'update' => [], 'delete' => []],
];
foreach ($ruleCatalog as $holonId => $entry) {
    foreach (array_keys($contextPermissions[DeferredProposal::TARGET_RULE]) as $catalogOperation) {
        if (!empty($entry['permissions'][$catalogOperation])) $contextPermissions[DeferredProposal::TARGET_RULE][$catalogOperation][] = (int)$holonId;
    }
}
foreach ($holonCatalog as $holonId => $entry) {
    if (!empty($entry['permissions'][DeferredProposal::OPERATION_CREATE])) $contextPermissions[DeferredProposal::TARGET_HOLON]['create'][] = (int)$holonId;
    foreach ([DeferredProposal::OPERATION_UPDATE, DeferredProposal::OPERATION_DELETE] as $catalogOperation) {
        if (empty($entry['permissions'][$catalogOperation])) continue;
        $targetHolon = new Holon();
        $parentHolon = $targetHolon->load((int)$holonId) ? $targetHolon->getParentHolon() : null;
        if ($parentHolon instanceof Holon) $contextPermissions[DeferredProposal::TARGET_HOLON][$catalogOperation][] = (int)$parentHolon->getId();
    }
}
foreach ($projectCatalog as $holonId => $entry) {
    $projectCreationModes[(int)$holonId] = (string)($entry['project_creation_mode'] ?? '');
    foreach (array_keys($contextPermissions[DeferredProposal::TARGET_PROJECT]) as $catalogOperation) {
        if (!empty($entry['permissions'][$catalogOperation])) $contextPermissions[DeferredProposal::TARGET_PROJECT][$catalogOperation][] = (int)$holonId;
    }
}
foreach ($contextPermissions as &$operations) {
    foreach ($operations as &$ids) $ids = array_values(array_unique(array_map('intval', $ids)));
    unset($ids);
}
unset($operations);

$targetRegistry = [
    DeferredProposal::TARGET_RULE => ['label' => $tr('rule'), 'help' => $tr('rule_help')],
    DeferredProposal::TARGET_HOLON => ['label' => $tr('holon'), 'help' => $tr('holon_help')],
    DeferredProposal::TARGET_PROJECT => ['label' => $tr('project'), 'help' => $tr('project_help')],
];
$buttonLabels = [
    DeferredProposal::TARGET_RULE => ['create' => $tr('create_rule'), 'update' => $tr('update_rule'), 'delete' => $tr('delete_rule')],
    DeferredProposal::TARGET_HOLON => ['create' => $tr('create_holon'), 'update' => $tr('update_holon'), 'delete' => $tr('delete_holon')],
    DeferredProposal::TARGET_PROJECT => ['create' => $tr('create_project'), 'propose' => $tr('propose_project'), 'update' => $tr('update_project'), 'delete' => $tr('delete_project')],
];
?>
<section class="generic-section generic-section--stack generic-section--roomy omo-deferred-workflow" data-deferred-proposal-workflow>
    <p><?= omoApiEscape($tr('intro')) ?></p>
    <div class="generic-form-field">
        <span class="generic-form-label"><?= omoApiEscape($tr('object_type')) ?></span>
        <div class="omo-deferred-workflow__types" role="group" aria-label="<?= omoApiEscape($tr('object_type')) ?>">
            <?php foreach ($targetRegistry as $registryType => $definition): ?>
                <button type="button" class="generic-soft-panel omo-deferred-workflow__type<?= $registryType === $targetType ? ' is-selected' : '' ?>" data-deferred-target-type="<?= omoApiEscape($registryType) ?>" aria-pressed="<?= $registryType === $targetType ? 'true' : 'false' ?>"<?= $proposalId > 0 && $registryType !== $targetType ? ' disabled' : '' ?>>
                    <strong><?= omoApiEscape((string)$definition['label']) ?></strong><span><?= omoApiEscape((string)$definition['help']) ?></span>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="generic-form-grid omo-deferred-workflow__selection-row">
        <label class="generic-form-field"><span class="generic-form-label"><?= omoApiEscape($tr('action')) ?></span><select class="generic-form-control" data-deferred-operation<?= $proposalId > 0 ? ' disabled' : '' ?>><option value="create"<?= $operation === 'create' ? ' selected' : '' ?>><?= omoApiEscape($tr('create')) ?></option><option value="update"<?= $operation === 'update' ? ' selected' : '' ?>><?= omoApiEscape($tr('update')) ?></option><option value="delete"<?= $operation === 'delete' ? ' selected' : '' ?>><?= omoApiEscape($tr('delete')) ?></option></select></label>
        <label class="generic-form-field"><span class="generic-form-label"><?= omoApiEscape($tr('context')) ?></span><span class="omo-deferred-workflow__context-control"><input class="generic-form-control" type="text" readonly data-deferred-context-label value="<?= omoApiEscape((string)($contextLabels[$contextHolonId] ?? '')) ?>"><button class="generic-action-button generic-action-button--secondary generic-action-button--icon-only" type="button" data-deferred-context-open title="<?= omoApiEscape($tr('open_context')) ?>" aria-label="<?= omoApiEscape($tr('open_context')) ?>"<?= $proposalId > 0 ? ' disabled' : '' ?>><img src="/omo/images/tools/connection.png" alt=""></button></span></label>
    </div>
    <div class="generic-form-grid omo-deferred-workflow__object-row" data-deferred-object-row hidden>
        <label class="generic-form-field"><span class="generic-form-label"><?= omoApiEscape($tr('object')) ?></span><select class="generic-form-control" data-deferred-object></select></label>
        <div class="generic-form-field omo-deferred-workflow__launch-field"><span class="generic-form-label" aria-hidden="true">&nbsp;</span><button class="generic-action-button generic-action-button--main" type="button" data-deferred-open-editor></button></div>
    </div>
    <div class="generic-action-row" data-deferred-create-row><button class="generic-action-button generic-action-button--main" type="button" data-deferred-open-editor></button></div>
    <p class="generic-feedback" data-deferred-feedback hidden></p>
    <div class="omo-deferred-workflow__picker-layer" data-deferred-context-picker hidden role="dialog" aria-modal="true"><div class="generic-soft-panel generic-soft-panel--elevated generic-soft-panel--stack omo-deferred-workflow__picker-panel"><div class="omo-deferred-workflow__picker-header"><div><h3 class="generic-card-title"><?= omoApiEscape($tr('context_picker')) ?></h3><p><?= omoApiEscape($tr('context_help')) ?></p></div><button class="generic-action-button generic-action-button--quiet-icon generic-action-button--icon-only" type="button" data-deferred-context-close aria-label="<?= omoApiEscape($tr('close')) ?>">&times;</button></div><div data-deferred-context-map></div><div class="omo-deferred-workflow__picker-selection"><strong data-deferred-context-candidate-label></strong><span data-deferred-context-candidate-status></span></div><div class="generic-action-row"><button class="generic-action-button generic-action-button--main" type="button" data-deferred-context-choose><?= omoApiEscape($tr('choose')) ?></button><button class="generic-action-button generic-action-button--secondary" type="button" data-deferred-context-close><?= omoApiEscape($tr('cancel')) ?></button></div></div></div>
</section>
<link rel="stylesheet" href="/common/choice/deferred-proposal-picker.css?v=20260923-shared">
<script>
(function(){
const root=document.querySelector('[data-deferred-proposal-workflow]');if(!root)return;
const operation=root.querySelector('[data-deferred-operation]'),contextLabel=root.querySelector('[data-deferred-context-label]'),objectRow=root.querySelector('[data-deferred-object-row]'),objectSelect=root.querySelector('[data-deferred-object]'),createRow=root.querySelector('[data-deferred-create-row]'),feedback=root.querySelector('[data-deferred-feedback]');
const pickerLayer=root.querySelector('[data-deferred-context-picker]'),pickerHost=root.querySelector('[data-deferred-context-map]'),candidateLabel=root.querySelector('[data-deferred-context-candidate-label]'),candidateStatus=root.querySelector('[data-deferred-context-candidate-status]'),chooseContext=root.querySelector('[data-deferred-context-choose]');
const labels=<?= json_encode($contextLabels, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>,permissions=<?= json_encode($contextPermissions, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,projectCreationModes=<?= json_encode($projectCreationModes, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,buttonLabels=<?= json_encode($buttonLabels, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>,texts=<?= json_encode(['empty'=>$tr('empty'),'loading'=>$tr('loading'),'allowed'=>$tr('allowed'),'denied'=>$tr('denied_context'),'loadError'=>$tr('load_error'),'editorError'=>$tr('editor_error')], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
const initialObjectId=<?= $objectId ?>,proposalId=<?= $proposalId ?>,selectionLocked=proposalId>0;let targetType=<?= json_encode($targetType) ?>,contextId=<?= $contextHolonId ?>,candidateContextId=contextId,picker=null,contextRequest=0,initialObjectPending=initialObjectId;
function allowedContextIds(){const byType=permissions[targetType]||{},ids=byType[operation.value];return Array.isArray(ids)?ids.map(Number):[]}function contextAllowed(id){return allowedContextIds().indexOf(Number(id))!==-1}function showFeedback(message){feedback.hidden=!message;feedback.textContent=message||''}
function updateTypeButtons(){root.querySelectorAll('[data-deferred-target-type]').forEach(function(button){const selected=button.getAttribute('data-deferred-target-type')===targetType;button.classList.toggle('is-selected',selected);button.setAttribute('aria-pressed',selected?'true':'false')})}function chooseDefaultContext(){const ids=allowedContextIds();if(!contextAllowed(contextId))contextId=ids.length?Number(ids[0]):0;contextLabel.value=String(labels[String(contextId)]||'')}
function updateLaunchButtons(){const labelsForType=buttonLabels[targetType]||{};const isProjectProposal=targetType==='project'&&operation.value==='create'&&String(projectCreationModes[String(contextId)]||'')==='propose';const label=String(isProjectProposal?labelsForType.propose:labelsForType[operation.value]||'');root.querySelectorAll('[data-deferred-open-editor]').forEach(function(button){button.textContent=label;button.disabled=!contextAllowed(contextId)||(operation.value!=='create'&&!Number(objectSelect.value||0))})}
function replaceObjects(items,selectedId){objectSelect.innerHTML='';(Array.isArray(items)?items:[]).forEach(function(item){const option=document.createElement('option');option.value=String(item.id||'');option.textContent=String(item.label||'');if(item.typeLabel)option.textContent+=' — '+String(item.typeLabel);if(Number(item.id)===Number(selectedId))option.selected=true;objectSelect.appendChild(option)});if(!objectSelect.options.length){const option=document.createElement('option');option.value='';option.textContent=texts.empty;objectSelect.appendChild(option);objectSelect.disabled=true}else objectSelect.disabled=selectionLocked;updateLaunchButtons()}
function loadObjects(){const requestId=++contextRequest;showFeedback('');chooseDefaultContext();const isCreate=operation.value==='create';objectRow.hidden=isCreate;createRow.hidden=!isCreate;updateLaunchButtons();if(!contextAllowed(contextId)){replaceObjects([],0);return Promise.resolve(false)}const selectedId=initialObjectPending;initialObjectPending=0;if(isCreate){replaceObjects([],0);updateLaunchButtons();return Promise.resolve(true)}objectSelect.disabled=true;objectSelect.innerHTML='<option>'+texts.loading+'</option>';const url='/omo/api/deferred_proposals/pv_proposal_context.php?oid=<?= $organizationId ?>&point_id=<?= $pointId ?>&target_type='+encodeURIComponent(targetType)+'&operation='+encodeURIComponent(operation.value)+'&context_holon_id='+encodeURIComponent(String(contextId));return fetch(url,{credentials:'same-origin'}).then(function(response){return response.json().then(function(payload){return{ok:response.ok,payload:payload}})}).then(function(result){if(requestId!==contextRequest)return false;if(!result.ok||!result.payload.status)throw new Error(result.payload.message||texts.loadError);if(result.payload.context&&result.payload.context.label){labels[String(contextId)]=String(result.payload.context.label);contextLabel.value=String(result.payload.context.label)}replaceObjects(result.payload.objects,selectedId);return true}).catch(function(error){if(requestId===contextRequest){replaceObjects([],0);showFeedback(error.message||texts.loadError)}return false})}
function updateCandidate(id){candidateContextId=Number(id||0);const allowed=contextAllowed(candidateContextId);candidateLabel.textContent=String(labels[String(candidateContextId)]||(picker&&picker.getSelectedHolonLabel?picker.getSelectedHolonLabel():'')||'');candidateStatus.textContent=allowed?texts.allowed:texts.denied;chooseContext.disabled=!allowed}function closePicker(){pickerLayer.hidden=true;if(picker&&typeof picker.destroy==='function')picker.destroy();picker=null;pickerHost.innerHTML=''}function openPicker(){if(typeof window.omoMountHolonScopePicker!=='function'){showFeedback(texts.editorError);return}candidateContextId=contextId;pickerLayer.hidden=false;picker=window.omoMountHolonScopePicker({host:pickerHost,organizationId:<?= $organizationId ?>,initialHolonId:contextId,selectableHolonIds:allowedContextIds(),showModes:false,initialScope:'local',labelMode:'context',suppressInitialChange:true,onChange:updateCandidate,onReady:updateCandidate});updateCandidate(contextId)}
function buildEditorUrl(){const objectId=Number(objectSelect.value||0),common='direct=1&oid=<?= $organizationId ?>&point_id=<?= $pointId ?>&proposal_id='+proposalId+'&operation='+encodeURIComponent(operation.value);if(targetType==='rule')return'/omo/api/deferred_proposals/pv_rule_editor.php?workflow=1&'+common+'&holon_id='+encodeURIComponent(String(contextId))+'&rule_id='+encodeURIComponent(String(objectId));if(targetType==='project')return'/omo/api/deferred_proposals/pv_project_editor.php?stage=capture&'+common+'&holon_id='+encodeURIComponent(String(contextId))+'&project_id='+encodeURIComponent(String(objectId));return'/omo/api/deferred_proposals/pv_holon_editor.php?stage=capture&'+common+'&holon_id='+encodeURIComponent(String(operation.value==='create'?contextId:objectId))}
function openEditor(){if(!contextAllowed(contextId)||(operation.value!=='create'&&!Number(objectSelect.value||0)))return;showFeedback('');if(typeof window.commonTopbarRefreshModalContent!=='function'){showFeedback(texts.editorError);return}window.commonTopbarRefreshModalContent(buildEditorUrl())}
root.querySelectorAll('[data-deferred-target-type]').forEach(function(button){button.addEventListener('click',function(){if(button.disabled)return;targetType=String(button.getAttribute('data-deferred-target-type')||'rule');initialObjectPending=0;updateTypeButtons();chooseDefaultContext();loadObjects()})});operation.addEventListener('change',function(){initialObjectPending=0;chooseDefaultContext();loadObjects()});objectSelect.addEventListener('change',updateLaunchButtons);root.querySelectorAll('[data-deferred-open-editor]').forEach(function(button){button.addEventListener('click',openEditor)});root.querySelector('[data-deferred-context-open]').addEventListener('click',openPicker);root.querySelectorAll('[data-deferred-context-close]').forEach(function(button){button.addEventListener('click',closePicker)});pickerLayer.addEventListener('click',function(event){if(event.target===pickerLayer)closePicker()});chooseContext.addEventListener('click',function(){if(!contextAllowed(candidateContextId))return;contextId=candidateContextId;contextLabel.value=String(labels[String(contextId)]||candidateLabel.textContent||'');closePicker();initialObjectPending=0;loadObjects()});updateTypeButtons();chooseDefaultContext();loadObjects();
}());
</script>
