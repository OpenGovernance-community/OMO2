<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\DeferredProposal;
use dbObject\DecisionGovernanceAction;
use dbObject\Document;
use dbObject\DocumentPvPoint;

$sourceLang = [
    'intro' => ['text' => 'Choisissez l’action et l’espace concerné. Les espaces grisés ne disposent pas du droit collectif du PV.', 'context' => 'Deferred holon proposal selection introduction'],
    'action' => ['text' => 'Action', 'context' => 'Deferred holon proposal action label'],
    'create' => ['text' => 'Créer un rôle ou cercle', 'context' => 'Deferred holon proposal create action'],
    'update' => ['text' => 'Modifier un rôle ou cercle', 'context' => 'Deferred holon proposal update action'],
    'delete' => ['text' => 'Supprimer un rôle ou cercle', 'context' => 'Deferred holon proposal delete action'],
    'holon' => ['text' => 'Espace concerné', 'context' => 'Deferred holon proposal target label'],
    'open_holon' => ['text' => 'Choisir un espace dans la structure', 'context' => 'Deferred holon proposal open picker label'],
    'picker_title' => ['text' => 'Choisir l’espace concerné', 'context' => 'Deferred holon proposal picker title'],
    'picker_help' => ['text' => 'Les espaces désactivés ne disposent pas du droit collectif nécessaire pour cette action.', 'context' => 'Deferred holon proposal picker help'],
    'allowed' => ['text' => 'Droit collectif disponible.', 'context' => 'Deferred holon proposal permission allowed'],
    'denied' => ['text' => 'Droit collectif indisponible.', 'context' => 'Deferred holon proposal permission denied'],
    'continue' => ['text' => 'Ouvrir l’éditeur', 'context' => 'Deferred holon proposal continue button'],
    'cancel' => ['text' => 'Annuler', 'context' => 'Deferred holon proposal cancel button'],
    'close' => ['text' => 'Fermer', 'context' => 'Deferred holon proposal close button'],
    'delete_intro' => ['text' => 'La suppression sera appliquée uniquement au traitement du point, si l’espace n’a pas changé entre-temps.', 'context' => 'Deferred holon proposal deletion introduction'],
    'delete_confirm' => ['text' => 'Ajouter la suppression', 'context' => 'Deferred holon proposal deletion confirm button'],
    'save_error' => ['text' => 'Impossible d’enregistrer la proposition.', 'context' => 'Deferred holon proposal save error'],
    'denied_error' => ['text' => 'Accès refusé.', 'context' => 'Deferred holon proposal denied error'],
    'unavailable_error' => ['text' => 'Cette proposition ne peut plus être modifiée.', 'context' => 'Deferred holon proposal unavailable error'],
    'no_collective_context_error' => ['text' => 'Ce PV doit être rattaché à un espace pour utiliser ses droits collectifs.', 'context' => 'Deferred holon proposal missing collective context'],
    'delete_descendants' => ['one' => 'Cette suppression comprend également {count} élément imbriqué.', 'other' => 'Cette suppression comprend également {count} éléments imbriqués.', 'context' => 'Deferred holon deletion descendant warning'],
];
$lang = omoLoadTranslationBundle('omo_deferred_holon_proposal', $sourceLang);
$tr = static fn (string $key, array $variables = []): string => t($key, $variables, $lang, $sourceLang);

$organizationId = (int)($_GET['oid'] ?? ($_SESSION['currentOrganization'] ?? 0));
$pointId = (int)($_GET['point_id'] ?? 0);
$proposalId = (int)($_GET['proposal_id'] ?? 0);
$stage = trim((string)($_GET['stage'] ?? 'select'));
$workflow = !empty($_GET['workflow']);
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

$proposal = null;
$operation = DeferredProposal::OPERATION_CREATE;
$targetHolonId = (int)$point->get('IDholon_concerned');
if ($proposalId > 0) {
    $proposal = new DeferredProposal();
    if (!$proposal->load($proposalId)
        || (int)$proposal->get('IDdocument_pv_point') !== $pointId
        || (int)$proposal->get('IDorganization') !== $organizationId
        || (string)$proposal->get('target_type') !== DeferredProposal::TARGET_HOLON
        || (string)$proposal->get('status') !== DeferredProposal::STATUS_PENDING) {
        http_response_code(404);
        echo '<div class="omo-empty-state">' . omoApiEscape($tr('unavailable_error')) . '</div>';
        exit;
    }
    $operation = (string)$proposal->get('operation');
    $targetHolonId = $operation === DeferredProposal::OPERATION_CREATE
        ? (int)$proposal->get('IDholon')
        : (int)$proposal->get('target_id');
}
if ($proposal === null && isset($_GET['operation'])) {
    $candidateOperation = trim((string)$_GET['operation']);
    if (in_array($candidateOperation, [DeferredProposal::OPERATION_CREATE, DeferredProposal::OPERATION_UPDATE, DeferredProposal::OPERATION_DELETE], true)) {
        $operation = $candidateOperation;
    }
}
if ($proposal === null && isset($_GET['holon_id'])) {
    $targetHolonId = (int)$_GET['holon_id'];
}

$collectiveHolonId = (int)$document->getPvContextHolonId();
if ($collectiveHolonId <= 0) {
    http_response_code(422);
    echo '<div class="omo-empty-state">' . omoApiEscape($tr('no_collective_context_error')) . '</div>';
    exit;
}

if ($stage === 'capture') {
    $targetHolon = DeferredProposal::loadAllowedHolonTargetHolon($organizationId, $targetHolonId, $operation, $collectiveHolonId);
    if (!$targetHolon) {
        http_response_code(403);
        echo '<div class="omo-empty-state">' . omoApiEscape($tr('denied_error')) . '</div>';
        exit;
    }
    $initialPayload = $proposal instanceof DeferredProposal && $operation !== DeferredProposal::OPERATION_DELETE
        ? (array)(DeferredProposal::normalizeState($proposal->get('after_state'))['editor_payload'] ?? [])
        : [];
    if ($operation === DeferredProposal::OPERATION_DELETE) {
        $descendantCount = $targetHolon->countVisibleDescendants();
        ?>
        <section class="generic-section generic-section--stack" data-deferred-holon-delete>
            <p><?= omoApiEscape($tr('delete_intro')) ?></p>
            <?php if ($descendantCount > 0): ?><p class="generic-feedback"><?= omoApiEscape($tr('delete_descendants', ['count' => $descendantCount])) ?></p><?php endif; ?>
            <p class="generic-feedback" data-deferred-feedback hidden></p>
            <div class="generic-action-row">
                <button type="button" class="generic-action-button generic-action-button--danger" data-deferred-delete-submit><?= omoApiEscape($tr('delete_confirm')) ?></button>
                <button type="button" class="generic-action-button generic-action-button--secondary" data-deferred-cancel><?= omoApiEscape($tr('cancel')) ?></button>
            </div>
        </section>
        <script>
        (function () {
            const root = document.querySelector('[data-deferred-holon-delete]'); if (!root) return;
            const submit = root.querySelector('[data-deferred-delete-submit]'), feedback = root.querySelector('[data-deferred-feedback]');
            function show(message) { feedback.hidden = !message; feedback.textContent = message || ''; }
            root.querySelector('[data-deferred-cancel]').addEventListener('click', function () { if (<?= $workflow ? 'true' : 'false' ?>) { window.dispatchEvent(new CustomEvent('omo-deferred-proposal-editor-back')); return; } if (window.commonTopbarCloseModal) window.commonTopbarCloseModal(); });
            submit.addEventListener('click', function () { submit.disabled = true; show(''); const data = new FormData(); data.append('oid', '<?= $organizationId ?>'); data.append('point_id', '<?= $pointId ?>'); data.append('proposal_id', '<?= $proposalId ?>'); data.append('operation', 'delete'); data.append('holon_id', '<?= $targetHolonId ?>'); data.append('payload', '{}'); fetch('/omo/api/deferred_proposals/pv_holon_save.php', {method:'POST', body:data, credentials:'same-origin'}).then(function (response) { return response.json().then(function (payload) { return {ok:response.ok,payload:payload}; }); }).then(function (result) { if (!result.ok || !result.payload.status) throw new Error(result.payload.message || <?= json_encode($tr('save_error')) ?>); window.dispatchEvent(new CustomEvent('omo-deferred-proposal-saved', {detail:{pointId:Number(result.payload.pointId || <?= $pointId ?>),proposalId:Number(result.payload.id || 0)}})); if (window.commonTopbarCloseModal) window.commonTopbarCloseModal(); }).catch(function (error) { show(error.message || <?= json_encode($tr('save_error')) ?>); submit.disabled = false; }); });
        }());
        </script>
        <?php
        exit;
    }

    $editorHolonId = $operation === DeferredProposal::OPERATION_CREATE ? 0 : (int)$targetHolon->getId();
    ?><script>window.omoHolonGovernanceInitialPayload = <?= json_encode($initialPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script><?php
    $_GET['oid'] = $organizationId;
    $_GET['cid'] = $operation === DeferredProposal::OPERATION_CREATE ? (int)$targetHolon->getId() : (int)$targetHolon->get('IDholon_parent');
    $_GET['hid'] = $editorHolonId;
    $_GET['governance_capture'] = 1;
    require dirname(__DIR__) . '/holons/create.php';
    ?>
    <script>
    (function () {
        const endpoint = '/omo/api/deferred_proposals/pv_holon_save.php';
        const saveError = <?= json_encode($tr('save_error'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        let saving = false;
        window.addEventListener('omo-holon-governance-capture', function (event) {
            if (saving || !event || !event.detail) return;
            const detail = event.detail;
            if (Number(detail.holonId || 0) !== <?= $editorHolonId ?> || Number(detail.contextHolonId || 0) !== <?= (int)$_GET['cid'] ?>) return;
            saving = true;
            const data = new FormData();
            data.append('oid', '<?= $organizationId ?>');
            data.append('point_id', '<?= $pointId ?>');
            data.append('proposal_id', '<?= $proposalId ?>');
            data.append('operation', <?= json_encode($operation) ?>);
            data.append('holon_id', '<?= $targetHolonId ?>');
            data.append('payload', JSON.stringify(detail.payload || {}));
            fetch(endpoint, {method:'POST', body:data, credentials:'same-origin'})
                .then(function (response) { return response.json().then(function (payload) { return {ok:response.ok,payload:payload}; }); })
                .then(function (result) { if (!result.ok || !result.payload.status) throw new Error(result.payload.message || saveError); window.dispatchEvent(new CustomEvent('omo-deferred-proposal-saved', {detail:{pointId:Number(result.payload.pointId || <?= $pointId ?>),proposalId:Number(result.payload.id || 0)}})); if (window.commonTopbarCloseModal) window.commonTopbarCloseModal(); })
                .catch(function (error) { saving = false; if (typeof window.commonNotify === 'function') window.commonNotify(error.message || saveError, 'error'); else window.alert(error.message || saveError); });
        });
    }());
    </script>
    <?php
    exit;
}

$catalog = DeferredProposal::getHolonTargetHolonCatalog($organizationId, $collectiveHolonId);
if (!isset($catalog[$targetHolonId])) $targetHolonId = (int)$document->getPvContextHolonId();
$labels = [];
$permissions = [DeferredProposal::OPERATION_CREATE => [], DeferredProposal::OPERATION_UPDATE => [], DeferredProposal::OPERATION_DELETE => []];
foreach ($catalog as $catalogHolonId => $entry) {
    $labels[(int)$catalogHolonId] = (string)$entry['label'];
    foreach (array_keys($permissions) as $catalogOperation) {
        if (!empty($entry['permissions'][$catalogOperation])) $permissions[$catalogOperation][] = (int)$catalogHolonId;
    }
}
$targetLabel = (string)($labels[$targetHolonId] ?? '');
?>
<section class="generic-section generic-section--stack omo-deferred-holon-selector" data-deferred-holon-selector>
    <p><?= omoApiEscape($tr('intro')) ?></p>
    <label class="generic-form-field"><span class="generic-form-label"><?= omoApiEscape($tr('action')) ?></span><select class="generic-form-control" data-deferred-operation<?= $proposal instanceof DeferredProposal ? ' disabled' : '' ?>><option value="create"<?= $operation === 'create' ? ' selected' : '' ?>><?= omoApiEscape($tr('create')) ?></option><option value="update"<?= $operation === 'update' ? ' selected' : '' ?>><?= omoApiEscape($tr('update')) ?></option><option value="delete"<?= $operation === 'delete' ? ' selected' : '' ?>><?= omoApiEscape($tr('delete')) ?></option></select></label>
    <label class="generic-form-field"><span class="generic-form-label"><?= omoApiEscape($tr('holon')) ?></span><span class="omo-deferred-holon-selector__control"><input class="generic-form-control" type="text" readonly value="<?= omoApiEscape($targetLabel) ?>" data-deferred-holon-label><button class="generic-action-button generic-action-button--secondary generic-action-button--icon-only" type="button" data-deferred-holon-open aria-label="<?= omoApiEscape($tr('open_holon')) ?>"><img src="/omo/images/tools/connection.png" alt=""></button></span><small class="omo-deferred-holon-selector__permission" data-deferred-holon-permission hidden></small></label>
    <p class="generic-feedback" data-deferred-feedback hidden></p>
    <div class="generic-action-row"><button class="generic-action-button generic-action-button--main" type="button" data-deferred-continue><?= omoApiEscape($tr('continue')) ?></button><button class="generic-action-button generic-action-button--secondary" type="button" data-deferred-cancel><?= omoApiEscape($tr('cancel')) ?></button></div>
    <div class="omo-deferred-holon-selector__picker-layer" data-deferred-holon-picker hidden role="dialog" aria-modal="true"><div class="generic-soft-panel generic-soft-panel--elevated generic-soft-panel--stack omo-deferred-holon-selector__picker-panel"><div class="omo-deferred-holon-selector__picker-header"><div><h3 class="generic-card-title"><?= omoApiEscape($tr('picker_title')) ?></h3><p><?= omoApiEscape($tr('picker_help')) ?></p></div><button class="generic-action-button generic-action-button--quiet-icon generic-action-button--icon-only" type="button" data-deferred-holon-close aria-label="<?= omoApiEscape($tr('close')) ?>">&times;</button></div><div data-deferred-holon-map></div><div class="generic-action-row"><button class="generic-action-button generic-action-button--main" type="button" data-deferred-holon-choose><?= omoApiEscape($tr('continue')) ?></button><button class="generic-action-button generic-action-button--secondary" type="button" data-deferred-holon-close><?= omoApiEscape($tr('cancel')) ?></button></div></div></div>
</section>
<style>
.omo-deferred-holon-selector__control { display:grid; grid-template-columns:minmax(0, 1fr) auto; gap:var(--generic-space-2, 8px); }
.omo-deferred-holon-selector__control img { width:21px; height:21px; object-fit:contain; }
.omo-deferred-holon-selector__permission { color:var(--color-danger, #b91c1c); font-weight:700; }
.omo-deferred-holon-selector__picker-layer { position:fixed; inset:0; z-index:10020; display:grid; place-items:center; padding:20px; background:rgba(15,23,42,.46); }
.omo-deferred-holon-selector__picker-layer[hidden] { display:none; }
.omo-deferred-holon-selector__picker-panel { width:min(680px,100%); max-height:calc(100dvh - 40px); overflow:auto; }
.omo-deferred-holon-selector__picker-header { display:flex; justify-content:space-between; gap:12px; }
.omo-deferred-holon-selector__picker-panel .omo-holon-scope-picker__map { height:min(55dvh,520px); }
</style>
<script>
(function () {
    const root = document.querySelector('[data-deferred-holon-selector]'); if (!root) return;
    const operation = root.querySelector('[data-deferred-operation]'), label = root.querySelector('[data-deferred-holon-label]'), permission = root.querySelector('[data-deferred-holon-permission]'), proceed = root.querySelector('[data-deferred-continue]'), pickerLayer = root.querySelector('[data-deferred-holon-picker]'), pickerHost = root.querySelector('[data-deferred-holon-map]');
    const labels = <?= json_encode($labels, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, permissions = <?= json_encode($permissions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>, texts = <?= json_encode(['allowed' => $tr('allowed'), 'denied' => $tr('denied')], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    let targetId = <?= $targetHolonId ?>, candidateId = targetId, picker = null;
    function allowedIds() { return Array.isArray(permissions[operation.value]) ? permissions[operation.value].map(Number) : []; }
    function allowed(id) { return allowedIds().indexOf(Number(id)) !== -1; }
    function sync() { const ok = allowed(targetId); label.value = String(labels[String(targetId)] || ''); permission.hidden = ok; permission.textContent = ok ? '' : texts.denied; proceed.disabled = !ok; }
    function closePicker() { pickerLayer.hidden = true; if (picker && typeof picker.destroy === 'function') picker.destroy(); picker = null; pickerHost.innerHTML = ''; }
    function updateCandidate(id) { candidateId = Number(id || 0); root.querySelector('[data-deferred-holon-choose]').disabled = !allowed(candidateId); }
    function openPicker() { if (typeof window.omoMountHolonScopePicker !== 'function') return; candidateId = targetId; pickerLayer.hidden = false; picker = window.omoMountHolonScopePicker({host:pickerHost, organizationId:<?= $organizationId ?>, initialHolonId:targetId, selectableHolonIds:allowedIds(), showModes:false, initialScope:'local', labelMode:'context', suppressInitialChange:true, onChange:updateCandidate, onReady:updateCandidate}); updateCandidate(targetId); }
    operation.addEventListener('change', sync); root.querySelector('[data-deferred-holon-open]').addEventListener('click', openPicker); root.querySelectorAll('[data-deferred-holon-close]').forEach(function (button) { button.addEventListener('click', closePicker); }); root.querySelector('[data-deferred-holon-choose]').addEventListener('click', function () { if (!allowed(candidateId)) return; targetId = candidateId; closePicker(); sync(); }); root.querySelector('[data-deferred-cancel]').addEventListener('click', function () { if (window.commonTopbarCloseModal) window.commonTopbarCloseModal(); }); proceed.addEventListener('click', function () { if (!allowed(targetId) || typeof window.commonTopbarOpenModal !== 'function') return; const url = '/omo/api/deferred_proposals/pv_holon_editor.php?stage=capture&oid=<?= $organizationId ?>&point_id=<?= $pointId ?>&proposal_id=<?= $proposalId ?>&operation=' + encodeURIComponent(operation.value) + '&holon_id=' + encodeURIComponent(String(targetId)); window.commonTopbarOpenModal(<?= json_encode($tr('holon')) ?>, url, 'fetch'); }); sync();
}());
</script>
