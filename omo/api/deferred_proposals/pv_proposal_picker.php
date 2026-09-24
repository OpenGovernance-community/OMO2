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
    'recurring_task' => ['text' => 'Tâche récurrente', 'context' => 'Deferred proposal recurring task type'],
    'recurring_task_help' => ['text' => 'Tâches planifiées de l’espace', 'context' => 'Deferred proposal recurring task type help'],
    'indicator' => ['text' => 'Indicateur', 'context' => 'Deferred proposal indicator type'],
    'indicator_help' => ['text' => 'Indicateurs et mesures de l’espace', 'context' => 'Deferred proposal indicator type help'],
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
    'create_recurring_task' => ['text' => 'Créer la tâche récurrente', 'context' => 'Deferred proposal recurring task creation editor'],
    'update_recurring_task' => ['text' => 'Modifier la tâche récurrente', 'context' => 'Deferred proposal recurring task update editor'],
    'delete_recurring_task' => ['text' => 'Supprimer la tâche récurrente', 'context' => 'Deferred proposal recurring task deletion editor'],
    'create_indicator' => ['text' => 'Créer l’indicateur', 'context' => 'Deferred proposal indicator creation editor'],
    'update_indicator' => ['text' => 'Modifier l’indicateur', 'context' => 'Deferred proposal indicator update editor'],
    'delete_indicator' => ['text' => 'Supprimer l’indicateur', 'context' => 'Deferred proposal indicator deletion editor'],
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
        || !in_array((string)$proposal->get('target_type'), [DeferredProposal::TARGET_RULE, DeferredProposal::TARGET_HOLON, DeferredProposal::TARGET_PROJECT, DeferredProposal::TARGET_RECURRING_TASK, DeferredProposal::TARGET_INDICATOR], true)
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
$recurringTaskCatalog = DeferredProposal::getObjectTargetHolonCatalog($organizationId, $collectiveHolonId, DeferredProposal::TARGET_RECURRING_TASK);
$indicatorCatalog = DeferredProposal::getObjectTargetHolonCatalog($organizationId, $collectiveHolonId, DeferredProposal::TARGET_INDICATOR);
$projectCreationModes = [];
$contextLabels = [];
foreach ([$ruleCatalog, $holonCatalog, $projectCatalog, $recurringTaskCatalog, $indicatorCatalog] as $catalog) {
    foreach ($catalog as $holonId => $entry) $contextLabels[(int)$holonId] = (string)($entry['label'] ?? '');
}
$contextPermissions = [
    DeferredProposal::TARGET_RULE => ['create' => [], 'update' => [], 'delete' => []],
    DeferredProposal::TARGET_HOLON => ['create' => [], 'update' => [], 'delete' => []],
    DeferredProposal::TARGET_PROJECT => ['create' => [], 'update' => [], 'delete' => []],
    DeferredProposal::TARGET_RECURRING_TASK => ['create' => [], 'update' => [], 'delete' => []],
    DeferredProposal::TARGET_INDICATOR => ['create' => [], 'update' => [], 'delete' => []],
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
foreach ([DeferredProposal::TARGET_RECURRING_TASK => $recurringTaskCatalog, DeferredProposal::TARGET_INDICATOR => $indicatorCatalog] as $catalogType => $catalog) {
    foreach ($catalog as $holonId => $entry) foreach (array_keys($contextPermissions[$catalogType]) as $catalogOperation) {
        if (!empty($entry['permissions'][$catalogOperation])) $contextPermissions[$catalogType][$catalogOperation][] = (int)$holonId;
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
    DeferredProposal::TARGET_RECURRING_TASK => ['label' => $tr('recurring_task'), 'help' => $tr('recurring_task_help')],
    DeferredProposal::TARGET_INDICATOR => ['label' => $tr('indicator'), 'help' => $tr('indicator_help')],
];
$buttonLabels = [
    DeferredProposal::TARGET_RULE => ['create' => $tr('create_rule'), 'update' => $tr('update_rule'), 'delete' => $tr('delete_rule')],
    DeferredProposal::TARGET_HOLON => ['create' => $tr('create_holon'), 'update' => $tr('update_holon'), 'delete' => $tr('delete_holon')],
    DeferredProposal::TARGET_PROJECT => ['create' => $tr('create_project'), 'propose' => $tr('propose_project'), 'update' => $tr('update_project'), 'delete' => $tr('delete_project')],
    DeferredProposal::TARGET_RECURRING_TASK => ['create' => $tr('create_recurring_task'), 'update' => $tr('update_recurring_task'), 'delete' => $tr('delete_recurring_task')],
    DeferredProposal::TARGET_INDICATOR => ['create' => $tr('create_indicator'), 'update' => $tr('update_indicator'), 'delete' => $tr('delete_indicator')],
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
<?= commonPageScriptTags('/omo/api/deferred_proposals/pv_proposal_picker.js', [
    'labels' => $contextLabels,
    'contextPermissions' => $contextPermissions,
    'projectCreationModes' => $projectCreationModes,
    'buttonLabels' => $buttonLabels,
    'tr' => ['empty'=>$tr('empty'),'loading'=>$tr('loading'),'allowed'=>$tr('allowed'),'denied'=>$tr('denied_context'),'loadError'=>$tr('load_error'),'editorError'=>$tr('editor_error')],
    'initialObjectId' => $objectId,
    'proposalId' => $proposalId,
    'targetType' => $targetType,
    'contextHolonId' => $contextHolonId,
    'organizationId' => $organizationId,
    'pointId' => $pointId,
]) ?>
