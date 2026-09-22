<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\Document;
use dbObject\DocumentPvPoint;
use dbObject\DeferredProposal;
use dbObject\DecisionGovernanceAction;
use dbObject\Rule;

$sourceLang = [
    'intro_create' => ['text' => 'Cette modification restera en attente jusqu’au traitement de ce point.', 'context' => 'Deferred rule proposal editor introduction'],
    'intro_edit' => ['text' => 'Modifiez la proposition avant son traitement.', 'context' => 'Deferred rule proposal editor introduction'],
    'action' => ['text' => 'Action', 'context' => 'Deferred rule proposal form label'],
    'create' => ['text' => 'Créer une règle', 'context' => 'Deferred rule proposal action'],
    'update' => ['text' => 'Modifier une règle', 'context' => 'Deferred rule proposal action'],
    'delete' => ['text' => 'Supprimer une règle', 'context' => 'Deferred rule proposal action'],
    'holon' => ['text' => 'Espace concerné', 'context' => 'Deferred rule proposal form label'],
    'open_holon' => ['text' => 'Choisir un espace dans la structure', 'context' => 'Deferred rule proposal button aria label'],
    'picker_title' => ['text' => 'Choisir l’espace concerné', 'context' => 'Deferred rule proposal holon picker title'],
    'picker_help' => ['text' => 'Les espaces grisés ne disposent pas du droit collectif nécessaire pour cette action.', 'context' => 'Deferred rule proposal holon picker help'],
    'close' => ['text' => 'Fermer', 'context' => 'Deferred rule proposal holon picker close button'],
    'choose' => ['text' => 'Choisir cet espace', 'context' => 'Deferred rule proposal holon picker confirm button'],
    'allowed' => ['text' => 'Droit collectif disponible.', 'context' => 'Deferred rule proposal permission status'],
    'denied' => ['text' => 'Droit collectif indisponible.', 'context' => 'Deferred rule proposal permission status'],
    'no_permission' => ['text' => 'Le collectif ne dispose pas de ce droit dans l’espace sélectionné.', 'context' => 'Deferred rule proposal validation message'],
    'picker_unavailable' => ['text' => 'La navigation dans la structure n’est pas disponible.', 'context' => 'Deferred rule proposal error'],
    'rule' => ['text' => 'Règle', 'context' => 'Deferred rule proposal form label'],
    'empty_rules' => ['text' => 'Aucune règle disponible dans cet espace.', 'context' => 'Deferred rule proposal empty rule selector'],
    'title' => ['text' => 'Titre', 'context' => 'Deferred rule proposal form label'],
    'intention' => ['text' => 'Intention', 'context' => 'Deferred rule proposal form label'],
    'description' => ['text' => 'Règle', 'context' => 'Deferred rule proposal form label'],
    'review_date' => ['text' => 'Date de requestionnement', 'context' => 'Deferred rule proposal form label'],
    'expiration_date' => ['text' => 'Date d’échéance', 'context' => 'Deferred rule proposal form label'],
    'save_create' => ['text' => 'Ajouter la proposition', 'context' => 'Deferred rule proposal submit button'],
    'save_edit' => ['text' => 'Enregistrer la proposition', 'context' => 'Deferred rule proposal submit button'],
    'cancel' => ['text' => 'Annuler', 'context' => 'Deferred rule proposal cancel button'],
    'save_error' => ['text' => 'Impossible d’enregistrer la proposition.', 'context' => 'Deferred rule proposal error'],
    'context_error' => ['text' => 'Impossible de charger les règles de cet espace.', 'context' => 'Deferred rule proposal error'],
    'denied_error' => ['text' => 'Accès refusé.', 'context' => 'Deferred rule proposal error'],
    'unavailable_error' => ['text' => 'Cette proposition ne peut plus être modifiée.', 'context' => 'Deferred rule proposal error'],
    'no_holon_error' => ['text' => 'Aucun espace de la structure n’est disponible.', 'context' => 'Deferred rule proposal error'],
    'no_collective_context_error' => ['text' => 'Ce PV doit être rattaché à un espace pour utiliser ses droits collectifs.', 'context' => 'Deferred rule proposal error'],
    'delete_workflow_help' => ['text' => 'Cette règle sera supprimée uniquement lors du traitement du point, si elle n’a pas changé entre-temps.', 'context' => 'Deferred rule deletion confirmation'],
];
$lang = omoLoadTranslationBundle('omo_deferred_rule_proposal', $sourceLang);
$tr = static fn (string $key): string => t($key, [], $lang, $sourceLang);

$organizationId = (int)($_GET['oid'] ?? ($_SESSION['currentOrganization'] ?? 0));
$pointId = (int)($_GET['point_id'] ?? 0);
$proposalId = (int)($_GET['proposal_id'] ?? 0);
$workflow = !empty($_GET['workflow']);
$direct = !empty($_GET['direct']);
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
    echo '<div class="omo-empty-state">' . omoApiEscape($tr('denied_error')) . '</div>';
    exit;
}

$defaultReview = (new DateTimeImmutable('now'))->modify('+6 months')->format('Y-m-d');
$defaultExpiration = (new DateTimeImmutable('now'))->modify('+1 year')->format('Y-m-d');
$operation = DeferredProposal::OPERATION_CREATE;
$selectedRuleId = 0;
$proposalHolonId = 0;
$editorState = [
    'title' => '',
    'intention' => '',
    'description' => '',
    'review_date' => $defaultReview,
    'expiration_date' => $defaultExpiration,
];
if ($proposalId > 0) {
    $proposal = new DeferredProposal();
    if (!$proposal->load($proposalId)
        || (int)$proposal->get('IDdocument_pv_point') !== $pointId
        || (int)$proposal->get('IDorganization') !== $organizationId
        || (string)$proposal->get('target_type') !== DeferredProposal::TARGET_RULE
        || (string)$proposal->get('status') !== DeferredProposal::STATUS_PENDING) {
        http_response_code(404);
        echo '<div class="omo-empty-state">' . omoApiEscape($tr('unavailable_error')) . '</div>';
        exit;
    }
    $operation = (string)$proposal->get('operation');
    $selectedRuleId = (int)$proposal->get('target_id');
    $proposalHolonId = (int)$proposal->get('IDholon');
    $proposalState = DeferredProposal::normalizeState(
        $operation === DeferredProposal::OPERATION_DELETE
            ? $proposal->get('before_state')
            : $proposal->get('after_state')
    );
    $editorState = array_merge($editorState, array_intersect_key($proposalState, $editorState));
} elseif ($workflow) {
    $requestedOperation = trim((string)($_GET['operation'] ?? ''));
    if (in_array($requestedOperation, [DeferredProposal::OPERATION_CREATE, DeferredProposal::OPERATION_UPDATE, DeferredProposal::OPERATION_DELETE], true)) {
        $operation = $requestedOperation;
    }
    $selectedRuleId = (int)($_GET['rule_id'] ?? 0);
    $proposalHolonId = (int)($_GET['holon_id'] ?? 0);
}

$collectiveHolonId = (int)$document->getPvContextHolonId();
if ($collectiveHolonId <= 0) {
    http_response_code(422);
    echo '<div class="omo-empty-state">' . omoApiEscape($tr('no_collective_context_error')) . '</div>';
    exit;
}
$holonCatalog = DeferredProposal::getRuleTargetHolonCatalog($organizationId, $collectiveHolonId);
$holonId = $proposalHolonId > 0 ? $proposalHolonId : (int)$point->get('IDholon_concerned');
if ($holonId <= 0) $holonId = (int)$document->getPvContextHolonId();
if ($holonId <= 0 || !isset($holonCatalog[$holonId])) {
    http_response_code(422);
    echo '<div class="omo-empty-state">' . omoApiEscape($tr('no_holon_error')) . '</div>';
    exit;
}

$holonLabel = (string)$holonCatalog[$holonId]['label'];
$operationAllowed = !empty($holonCatalog[$holonId]['permissions'][$operation]);
$rules = [];
$ruleStates = [];
if ($operation !== DeferredProposal::OPERATION_CREATE && $operationAllowed) {
    foreach (Rule::findDefinedInHolon($holonId) as $rule) {
        if (!$rule instanceof Rule || (int)$rule->getId() <= 0) continue;
        $rules[] = $rule;
        $ruleStates[(int)$rule->getId()] = DecisionGovernanceAction::captureRuleState($rule);
    }
}
if ($workflow && $selectedRuleId > 0 && isset($ruleStates[$selectedRuleId])) {
    $editorState = array_merge($editorState, array_intersect_key($ruleStates[$selectedRuleId], $editorState));
}

$holonLabels = [];
$permissionsByOperation = [
    DeferredProposal::OPERATION_CREATE => [],
    DeferredProposal::OPERATION_UPDATE => [],
    DeferredProposal::OPERATION_DELETE => [],
];
foreach ($holonCatalog as $catalogHolonId => $catalogEntry) {
    $holonLabels[(int)$catalogHolonId] = (string)$catalogEntry['label'];
    foreach (array_keys($permissionsByOperation) as $catalogOperation) {
        if (!empty($catalogEntry['permissions'][$catalogOperation])) {
            $permissionsByOperation[$catalogOperation][] = (int)$catalogHolonId;
        }
    }
}
?>
<section class="generic-section generic-section--stack omo-deferred-rule-editor" data-deferred-pv-rule-editor>
    <p><?= omoApiEscape($tr($proposalId > 0 ? 'intro_edit' : 'intro_create')) ?></p>
    <form class="generic-form-stack" action="/omo/api/deferred_proposals/pv_rule_save.php" method="post">
        <input type="hidden" name="oid" value="<?= $organizationId ?>">
        <input type="hidden" name="point_id" value="<?= $pointId ?>">
        <input type="hidden" name="holon_id" value="<?= $holonId ?>" data-deferred-holon-id>
        <input type="hidden" name="proposal_id" value="<?= $proposalId ?>">
        <label class="generic-form-field"<?= $workflow ? ' hidden' : '' ?>><span class="generic-form-label"><?= omoApiEscape($tr('action')) ?></span><select class="generic-form-control" name="operation" data-deferred-operation><option value="create"<?= $operation === 'create' ? ' selected' : '' ?>><?= omoApiEscape($tr('create')) ?></option><option value="update"<?= $operation === 'update' ? ' selected' : '' ?>><?= omoApiEscape($tr('update')) ?></option><option value="delete"<?= $operation === 'delete' ? ' selected' : '' ?>><?= omoApiEscape($tr('delete')) ?></option></select></label>
        <label class="generic-form-field"<?= $workflow ? ' hidden' : '' ?>>
            <span class="generic-form-label"><?= omoApiEscape($tr('holon')) ?></span>
            <span class="omo-deferred-rule-editor__holon-control">
                <input class="generic-form-control" type="text" value="<?= omoApiEscape($holonLabel) ?>" readonly data-deferred-holon-label>
                <button class="generic-action-button generic-action-button--secondary generic-action-button--icon-only" type="button" data-deferred-holon-open title="<?= omoApiEscape($tr('open_holon')) ?>" aria-label="<?= omoApiEscape($tr('open_holon')) ?>"><img src="/omo/images/tools/connection.png" alt=""></button>
            </span>
            <small class="omo-deferred-rule-editor__permission" data-deferred-holon-permission<?= $operationAllowed ? ' hidden' : '' ?>><?= omoApiEscape($tr('no_permission')) ?></small>
        </label>
        <label class="generic-form-field" data-deferred-existing<?= $operation === DeferredProposal::OPERATION_CREATE || $workflow ? ' hidden' : '' ?>><span class="generic-form-label"><?= omoApiEscape($tr('rule')) ?></span><select class="generic-form-control" name="rule_id" data-deferred-rule-id<?= $rules ? '' : ' disabled' ?>><?php if (!$rules): ?><option value=""><?= omoApiEscape($tr('empty_rules')) ?></option><?php else: ?><?php foreach ($rules as $rule): ?><option value="<?= (int)$rule->getId() ?>"<?= (int)$rule->getId() === $selectedRuleId ? ' selected' : '' ?>><?= omoApiEscape((string)$rule->get('title')) ?></option><?php endforeach; ?><?php endif; ?></select></label>
        <?php if ($workflow && $operation === DeferredProposal::OPERATION_DELETE): ?>
            <div class="generic-soft-panel generic-soft-panel--stack"><strong><?= omoApiEscape((string)($editorState['title'] ?: $tr('rule'))) ?></strong><span><?= omoApiEscape($tr('delete_workflow_help')) ?></span></div>
        <?php endif; ?>
        <div data-deferred-fields<?= $operation === DeferredProposal::OPERATION_DELETE ? ' hidden' : '' ?>>
            <label class="generic-form-field"><span class="generic-form-label"><?= omoApiEscape($tr('title')) ?></span><input class="generic-form-control" name="title" maxlength="255" value="<?= omoApiEscape((string)$editorState['title']) ?>" required></label>
            <label class="generic-form-field"><span class="generic-form-label"><?= omoApiEscape($tr('intention')) ?></span><textarea class="generic-form-control" name="intention" rows="3"><?= omoApiEscape((string)$editorState['intention']) ?></textarea></label>
            <label class="generic-form-field"><span class="generic-form-label"><?= omoApiEscape($tr('description')) ?></span><textarea class="generic-form-control" name="description" rows="5" required><?= omoApiEscape((string)$editorState['description']) ?></textarea></label>
            <div class="generic-form-grid"><label class="generic-form-field"><span class="generic-form-label"><?= omoApiEscape($tr('review_date')) ?></span><input class="generic-form-control" type="date" name="review_date" value="<?= omoApiEscape((string)$editorState['review_date']) ?>" required></label><label class="generic-form-field"><span class="generic-form-label"><?= omoApiEscape($tr('expiration_date')) ?></span><input class="generic-form-control" type="date" name="expiration_date" value="<?= omoApiEscape((string)$editorState['expiration_date']) ?>" required></label></div>
        </div>
        <p class="generic-feedback" data-deferred-feedback hidden></p>
        <div class="generic-action-row"><button class="generic-action-button generic-action-button--main" type="submit" data-deferred-submit<?= $operationAllowed && ($operation === DeferredProposal::OPERATION_CREATE || $rules) ? '' : ' disabled' ?>><?= omoApiEscape($tr($proposalId > 0 ? 'save_edit' : 'save_create')) ?></button><button class="generic-action-button generic-action-button--secondary" type="button" data-deferred-cancel><?= omoApiEscape($tr('cancel')) ?></button></div>
    </form>
    <div class="omo-deferred-rule-editor__picker-layer" data-deferred-holon-picker hidden role="dialog" aria-modal="true" aria-labelledby="deferred-holon-picker-title">
        <div class="generic-soft-panel generic-soft-panel--elevated generic-soft-panel--stack omo-deferred-rule-editor__picker-panel">
            <div class="omo-deferred-rule-editor__picker-header"><div><h3 class="generic-card-title" id="deferred-holon-picker-title"><?= omoApiEscape($tr('picker_title')) ?></h3><p><?= omoApiEscape($tr('picker_help')) ?></p></div><button class="generic-action-button generic-action-button--quiet-icon generic-action-button--icon-only" type="button" data-deferred-holon-close aria-label="<?= omoApiEscape($tr('close')) ?>">&times;</button></div>
            <div data-deferred-holon-map></div>
            <div class="omo-deferred-rule-editor__picker-selection"><strong data-deferred-holon-candidate-label><?= omoApiEscape($holonLabel) ?></strong><span data-deferred-holon-candidate-status></span></div>
            <div class="generic-action-row"><button class="generic-action-button generic-action-button--main" type="button" data-deferred-holon-choose><?= omoApiEscape($tr('choose')) ?></button><button class="generic-action-button generic-action-button--secondary" type="button" data-deferred-holon-close><?= omoApiEscape($tr('cancel')) ?></button></div>
        </div>
    </div>
</section>
<style>
.omo-deferred-rule-editor__holon-control { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: var(--generic-space-2, 8px); align-items: stretch; }
.omo-deferred-rule-editor__holon-control .generic-action-button { min-height: 100%; }
.omo-deferred-rule-editor__holon-control img { width: 21px; height: 21px; object-fit: contain; }
.omo-deferred-rule-editor__permission { color: var(--color-danger, #b91c1c); font-weight: 700; }
.omo-deferred-rule-editor__picker-layer { position: fixed; inset: 0; z-index: 10020; display: grid; place-items: center; padding: 20px; background: rgba(15, 23, 42, 0.46); box-sizing: border-box; }
.omo-deferred-rule-editor__picker-layer[hidden] { display: none; }
.omo-deferred-rule-editor__picker-panel { width: min(680px, 100%); max-height: calc(100dvh - 40px); overflow: auto; }
.omo-deferred-rule-editor__picker-header { display: flex; align-items: flex-start; justify-content: space-between; gap: var(--generic-space-3, 12px); }
.omo-deferred-rule-editor__picker-header p { margin: 4px 0 0; color: var(--color-text-light); }
.omo-deferred-rule-editor__picker-panel .omo-holon-scope-picker__map { height: min(55dvh, 520px); margin-top: 0; }
.omo-deferred-rule-editor__picker-selection { display: flex; justify-content: space-between; gap: var(--generic-space-3, 12px); padding: 10px 12px; border: 1px solid var(--color-border); border-radius: var(--radius-md); }
.omo-deferred-rule-editor__picker-selection span { color: var(--color-text-light); text-align: right; }
</style>
<script>
(function () {
    const root = document.querySelector('[data-deferred-pv-rule-editor]'); if (!root) return;
    const form = root.querySelector('form'), operation = root.querySelector('[data-deferred-operation]'), existing = root.querySelector('[data-deferred-existing]'), ruleId = root.querySelector('[data-deferred-rule-id]'), fields = root.querySelector('[data-deferred-fields]'), feedback = root.querySelector('[data-deferred-feedback]'), submit = root.querySelector('[data-deferred-submit]');
    const holonInput = root.querySelector('[data-deferred-holon-id]'), holonLabel = root.querySelector('[data-deferred-holon-label]'), permissionMessage = root.querySelector('[data-deferred-holon-permission]');
    const pickerLayer = root.querySelector('[data-deferred-holon-picker]'), pickerHost = root.querySelector('[data-deferred-holon-map]'), candidateLabel = root.querySelector('[data-deferred-holon-candidate-label]'), candidateStatus = root.querySelector('[data-deferred-holon-candidate-status]'), chooseHolon = root.querySelector('[data-deferred-holon-choose]');
    const labels = <?= json_encode($holonLabels, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const permissions = <?= json_encode($permissionsByOperation, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    let ruleStates = <?= json_encode($ruleStates, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const proposalId = <?= $proposalId ?>;
    const workflow = <?= $workflow ? 'true' : 'false' ?>;
    const texts = <?= json_encode(['allowed' => $tr('allowed'), 'denied' => $tr('denied'), 'noPermission' => $tr('no_permission'), 'pickerUnavailable' => $tr('picker_unavailable'), 'emptyRules' => $tr('empty_rules'), 'saveError' => $tr('save_error'), 'contextError' => $tr('context_error')], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    let previousOperation = operation.value, picker = null, candidateHolonId = Number(holonInput.value || 0), contextRequest = 0;

    function allowedIds() { return Array.isArray(permissions[operation.value]) ? permissions[operation.value].map(Number) : []; }
    function isAllowed(id) { return allowedIds().indexOf(Number(id)) !== -1; }
    function showFeedback(message) { feedback.hidden = !message; feedback.textContent = message || ''; }
    function populateFromSelectedRule() { const state = ruleStates[String(ruleId.value || '')]; if (!state) return; ['title','intention','description','review_date','expiration_date'].forEach(function (name) { const field = form.elements.namedItem(name); if (field && Object.prototype.hasOwnProperty.call(state, name)) field.value = String(state[name] == null ? '' : state[name]); }); }
    function updateSubmitState() { const allowed = isAllowed(holonInput.value); permissionMessage.hidden = allowed; submit.disabled = !allowed || (operation.value !== 'create' && !Number(ruleId.value)); }
    function sync(populateRule) { const deletion = operation.value === 'delete'; existing.hidden = workflow || operation.value === 'create'; fields.hidden = deletion; fields.querySelectorAll('input,textarea').forEach(function (field) { field.required = !deletion && ['title','description','review_date','expiration_date'].indexOf(field.name) !== -1; }); if (populateRule && operation.value === 'update') populateFromSelectedRule(); updateSubmitState(); }
    function replaceRules(items, selectedId, populateRule) {
        ruleStates = {}; ruleId.innerHTML = '';
        (Array.isArray(items) ? items : []).forEach(function (item) { const option = document.createElement('option'); option.value = String(item.id || ''); option.textContent = String(item.title || ''); if (Number(item.id) === Number(selectedId)) option.selected = true; ruleId.appendChild(option); ruleStates[String(item.id)] = item.state || {}; });
        if (!ruleId.options.length) { const option = document.createElement('option'); option.value = ''; option.textContent = texts.emptyRules; ruleId.appendChild(option); ruleId.disabled = true; } else { ruleId.disabled = false; }
        if (populateRule && operation.value === 'update') populateFromSelectedRule(); updateSubmitState();
    }
    function loadContext(selectedId, populateRule) {
        const selectedHolonId = Number(selectedId || holonInput.value || 0), requestId = ++contextRequest;
        if (!isAllowed(selectedHolonId)) { replaceRules([], 0, false); updateSubmitState(); return Promise.resolve(false); }
        if (operation.value === 'create') { replaceRules([], 0, false); updateSubmitState(); return Promise.resolve(true); }
        submit.disabled = true; showFeedback('');
        const url = '/omo/api/deferred_proposals/pv_rule_context.php?oid=<?= $organizationId ?>&point_id=<?= $pointId ?>&holon_id=' + encodeURIComponent(String(selectedHolonId)) + '&operation=' + encodeURIComponent(operation.value);
        return fetch(url, {credentials:'same-origin'}).then(function (response) { return response.json().then(function (payload) { return {ok:response.ok,payload:payload}; }); }).then(function (result) { if (requestId !== contextRequest) return false; if (!result.ok || !result.payload.status) throw new Error(result.payload.message || texts.contextError); if (result.payload.holon && result.payload.holon.label) { labels[String(selectedHolonId)] = String(result.payload.holon.label); holonLabel.value = String(result.payload.holon.label); } replaceRules(result.payload.rules, 0, populateRule); return true; }).catch(function (error) { if (requestId === contextRequest) { replaceRules([], 0, false); showFeedback(error.message || texts.contextError); } return false; });
    }
    function updateCandidate(id) { candidateHolonId = Number(id || 0); const allowed = isAllowed(candidateHolonId); candidateLabel.textContent = String(labels[String(candidateHolonId)] || (picker && picker.getSelectedHolonLabel ? picker.getSelectedHolonLabel() : '') || ''); candidateStatus.textContent = allowed ? texts.allowed : texts.denied; chooseHolon.disabled = !allowed; }
    function closePicker() { pickerLayer.hidden = true; if (picker && typeof picker.destroy === 'function') picker.destroy(); picker = null; pickerHost.innerHTML = ''; }
    function openPicker() { if (typeof window.omoMountHolonScopePicker !== 'function') { showFeedback(texts.pickerUnavailable); return; } candidateHolonId = Number(holonInput.value || 0); pickerLayer.hidden = false; picker = window.omoMountHolonScopePicker({host:pickerHost, organizationId:<?= $organizationId ?>, initialHolonId:candidateHolonId, selectableHolonIds:allowedIds(), showModes:false, initialScope:'local', labelMode:'context', suppressInitialChange:true, onChange:updateCandidate, onReady:updateCandidate}); updateCandidate(candidateHolonId); }

    operation.addEventListener('change', function () { const populateRule = operation.value === 'update' && operation.value !== previousOperation; previousOperation = operation.value; sync(false); loadContext(holonInput.value, populateRule); });
    ruleId.addEventListener('change', function () { if (operation.value === 'update') populateFromSelectedRule(); updateSubmitState(); });
    root.querySelector('[data-deferred-holon-open]').addEventListener('click', openPicker);
    root.querySelectorAll('[data-deferred-holon-close]').forEach(function (button) { button.addEventListener('click', closePicker); });
    pickerLayer.addEventListener('click', function (event) { if (event.target === pickerLayer) closePicker(); });
    chooseHolon.addEventListener('click', function () { if (!isAllowed(candidateHolonId)) return; holonInput.value = String(candidateHolonId); holonLabel.value = String(labels[String(candidateHolonId)] || candidateLabel.textContent || ''); closePicker(); loadContext(candidateHolonId, operation.value === 'update'); });
    root.querySelector('[data-deferred-cancel]').addEventListener('click', function () { if (<?= $workflow && !$direct ? 'true' : 'false' ?>) { window.dispatchEvent(new CustomEvent('omo-deferred-proposal-editor-back')); return; } if (window.commonTopbarCloseModal) window.commonTopbarCloseModal(); });
    sync(proposalId <= 0 && operation.value === 'update');
    form.addEventListener('submit', function (event) { event.preventDefault(); showFeedback(''); if (!isAllowed(holonInput.value)) { showFeedback(texts.noPermission); return; } if (!form.reportValidity()) return; submit.disabled = true; fetch(form.action, {method:'POST', body:new FormData(form), credentials:'same-origin'}).then(function (response) { return response.json().then(function (payload) { return {ok:response.ok,payload:payload}; }); }).then(function (result) { if (!result.ok || !result.payload.status) throw new Error(result.payload.message || texts.saveError); window.dispatchEvent(new CustomEvent('omo-deferred-proposal-saved', {detail:{pointId:Number(result.payload.pointId || <?= $pointId ?>),proposalId:Number(result.payload.id || 0)}})); if (window.commonTopbarCloseModal) window.commonTopbarCloseModal(); }).catch(function (error) { showFeedback(error.message || texts.saveError); updateSubmitState(); }); });
}());
</script>
