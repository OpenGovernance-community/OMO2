<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/common/choice/deferred-editor-fields.php';

use dbObject\Document;
use dbObject\DocumentPvPoint;
use dbObject\DeferredProposal;
use dbObject\DecisionGovernanceAction;
use dbObject\Rule;

$sourceLang = [
    'intro_create' => ['text' => 'Cette modification restera en attente jusqu’au traitement de ce point.', 'context' => 'Deferred rule proposal editor introduction'],
    'intro_edit' => ['text' => 'Modifiez la modification avant son traitement.', 'context' => 'Deferred rule proposal editor introduction'],
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
    'save_create' => ['text' => 'Ajouter la modification', 'context' => 'Deferred rule proposal submit button'],
    'save_edit' => ['text' => 'Enregistrer la modification', 'context' => 'Deferred rule proposal submit button'],
    'cancel' => ['text' => 'Annuler', 'context' => 'Deferred rule proposal cancel button'],
    'save_error' => ['text' => 'Impossible d’enregistrer la modification.', 'context' => 'Deferred rule proposal error'],
    'context_error' => ['text' => 'Impossible de charger les règles de cet espace.', 'context' => 'Deferred rule proposal error'],
    'denied_error' => ['text' => 'Accès refusé.', 'context' => 'Deferred rule proposal error'],
    'unavailable_error' => ['text' => 'Cette modification ne peut plus être modifiée.', 'context' => 'Deferred rule proposal error'],
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
    'scope' => Rule::SCOPE_LOCAL,
    'IDauthority' => null,
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
<section class="generic-section generic-section--stack generic-section--roomy omo-deferred-rule-editor" data-deferred-pv-rule-editor>
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
        <?php
        $scopeHolon = new \dbObject\Holon();
        $scopeContext = Rule::getScopeContext($scopeHolon->load($holonId) ? $scopeHolon : null);
        omoDeferredEditorRenderFields('rule', $editorState + ['scopeContext' => $scopeContext]);
        ?>
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
<link rel="stylesheet" href="<?= commonAssetUrl('/omo/api/deferred_proposals/pv_rule_editor.css') ?>">
<script src="<?= commonAssetUrl('/common/choice/rule-scope-fields.js') ?>"></script>
<?= commonPageScriptTags('/omo/api/deferred_proposals/pv_rule_editor.js', [
    'labels' => $holonLabels,
    'permissions' => $permissionsByOperation,
    'ruleStates' => $ruleStates,
    'proposalId' => $proposalId,
    'workflow' => ($workflow),
    'texts' => ['allowed' => $tr('allowed'), 'denied' => $tr('denied'), 'noPermission' => $tr('no_permission'), 'pickerUnavailable' => $tr('picker_unavailable'), 'emptyRules' => $tr('empty_rules'), 'saveError' => $tr('save_error'), 'contextError' => $tr('context_error')],
    'organizationId' => $organizationId,
    'pointId' => $pointId,
    'workflow2' => ($workflow && !$direct),
]) ?>
