<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\ArrayChecklist;
use dbObject\ArrayChecklistItemOccurrence;
use dbObject\Checklist;
use dbObject\ChecklistItem;
use dbObject\ChecklistItemOccurrence;
use dbObject\ChecklistTrigger;
use dbObject\Holon;
use dbObject\Project;

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$currentHolonId = isset($_GET['cid']) && is_numeric($_GET['cid']) ? (int)$_GET['cid'] : 0;
$openChecklistId = isset($_GET['open_checklist_id']) && is_numeric($_GET['open_checklist_id']) ? (int)$_GET['open_checklist_id'] : 0;
$context = omoChecklistResolveContext($organizationId, $currentHolonId);
if (empty($context['status'])) {
    http_response_code(403);
    ?>
    <div class="omo-checklist omo-panel-view">
        <div class="omo-panel-view__body"><div class="omo-panel-view__body_content"><div class="omo-empty-state"><?= omoApiEscape((string)($context['message'] ?? omoChecklistT('checklist.error.context'))) ?></div></div></div>
    </div>
    <?php
    exit;
}

$rootHolon = $context['rootHolon'];
$currentHolon = $context['currentHolon'];
$availableScopes = omoApiGetAvailableContextScopes($currentHolon instanceof Holon, $currentHolon, $rootHolon);
$checklistScope = omoApiNormalizeContextScope($_GET['checklist_scope'] ?? 'contextual', $availableScopes);
$scopeActiveIndex = omoApiResolveContextScopeIndex($checklistScope, $availableScopes);
$scopeHolonIds = $checklistScope === 'children'
    ? omoApiGetDirectChildScopeHolonIds($currentHolon)
    : ($checklistScope === 'descendants' ? omoApiGetDescendantHolonIds($currentHolon) : [(int)$currentHolon->getId()]);

$checklists = new ArrayChecklist();
$checklists->loadForContext(
    $organizationId,
    $currentHolon instanceof Holon ? (int)$currentHolon->getId() : 0,
    $checklistScope,
    $scopeHolonIds
);
$checklistRows = [];
foreach ($checklists as $checklist) {
    if (!($checklist instanceof Checklist) || !omoChecklistCanView($checklist)) {
        continue;
    }
    $templateRoot = $checklist->getTemplateRoot();
    if (!($templateRoot instanceof Project)) {
        continue;
    }
    $itemCount = 0;
    foreach ($checklist->getItems(true) as $item) {
        if ($item instanceof ChecklistItem) {
            $itemCount++;
        }
    }
    $holon = $templateRoot->getHolon();
    $updatedAt = $checklist->get('updated_at');
    $openRunCount = count($checklist->getOpenRuns());
    $recurringActiveCount = 0;
    $trigger = omoChecklistGetPrimaryTrigger($checklist);
    $isContainerChecklist = $trigger instanceof ChecklistTrigger
        && ChecklistTrigger::normalizeTriggerType($trigger->get('trigger_type')) === ChecklistTrigger::TYPE_CONTAINER;
    if ($isContainerChecklist) {
        foreach ($checklist->getItems(true) as $item) {
            if (!($item instanceof ChecklistItem)) {
                continue;
            }
            $occurrences = new ArrayChecklistItemOccurrence();
            $occurrences->loadForItem((int)$item->getId());
            foreach ($occurrences as $occurrence) {
                if (!($occurrence instanceof ChecklistItemOccurrence)) {
                    continue;
                }
                $project = $occurrence->getProject();
                if ($project instanceof Project && (int)$project->get('active') === 1 && Project::normalizeStatus($project->get('status')) !== Project::STATUS_DONE) {
                    $recurringActiveCount++;
                }
            }
        }
    }
    $checklistRows[] = [
        'checklist' => $checklist,
        'root' => $templateRoot,
        'title' => trim((string)$templateRoot->get('title')),
        'description' => trim(strip_tags((string)$templateRoot->get('description'))),
        'holon' => $holon instanceof Holon ? trim((string)$holon->getDisplayName()) : '',
        'itemCount' => $itemCount,
        'openRunCount' => $openRunCount,
        'recurringActiveCount' => $recurringActiveCount,
        'triggerLabel' => omoChecklistTriggerLabel(omoChecklistGetPrimaryTrigger($checklist)),
        'updated' => $updatedAt instanceof DateTimeInterface ? $updatedAt->format('d.m.Y') : '',
    ];
}
usort($checklistRows, static function (array $left, array $right) {
    return strcasecmp((string)$left['title'], (string)$right['title']);
});

$currentUrl = '/omo/api/checklist/index.php?oid=' . rawurlencode((string)$organizationId);
if ($currentHolonId > 0) {
    $currentUrl .= '&cid=' . rawurlencode((string)$currentHolonId);
}
if ($checklistScope !== 'contextual') {
    $currentUrl .= '&checklist_scope=' . rawurlencode($checklistScope);
}
$createUrl = '/omo/api/checklist/edit.php?oid=' . rawurlencode((string)$organizationId);
if ($currentHolonId > 0) {
    $createUrl .= '&cid=' . rawurlencode((string)$currentHolonId);
}
$detailUrl = '/omo/api/checklist/detail.php?oid=' . rawurlencode((string)$organizationId);
if ($currentHolonId > 0) {
    $detailUrl .= '&cid=' . rawurlencode((string)$currentHolonId);
}
$canCreate = omoChecklistCanCreateContext($context);
$texts = [
    'loading' => omoChecklistT('checklist.loading'),
    'loadingError' => omoChecklistT('checklist.error.load'),
];
?>
<link rel="stylesheet" href="/omo/api/checklist/checklist.css?v=20260722-shared-subdrawer-width">
<div
    class="omo-checklist omo-panel-view"
    id="omo-checklist-root"
    data-checklist-oid="<?= (int)$organizationId ?>"
    data-checklist-route-cid="<?= (int)$currentHolonId ?>"
    data-checklist-scope="<?= omoApiEscape($checklistScope) ?>"
    data-checklist-current-url="<?= omoApiEscape($currentUrl) ?>"
    data-checklist-create-url="<?= omoApiEscape($createUrl) ?>"
    data-checklist-detail-url="<?= omoApiEscape($detailUrl) ?>"
    data-checklist-open-checklist-id="<?= (int)$openChecklistId ?>"
    data-checklist-texts="<?= omoApiEscape(json_encode($texts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
>
    <header class="omo-checklist__header omo-panel-view__header omo-panel-view__header--stacked">
        <div class="omo-panel-view__header-main">
            <div class="omo-panel-view__title-cluster">
                <span class="omo-panel-view__app-icon omo-checklist__app-icon" aria-hidden="true"><img src="/omo/images/tools/checklist.png" alt=""></span>
                <div class="omo-panel-view__header-copy">
                    <div class="omo-checklist__title-row">
                        <h2 class="omo-panel-view__title"><?= omoApiEscape(omoChecklistT('checklist.title')) ?></h2>
                        <span class="omo-panel-view__count"><?= count($checklistRows) ?></span>
                    </div>
                    <p class="omo-panel-view__description"><?= omoApiEscape(omoChecklistT('checklist.description')) ?></p>
                </div>
            </div>
            <?php if ($canCreate): ?>
                <button type="button" class="generic-action-button generic-action-button--main omo-mobile-corner-action" data-checklist-open-create><?= omoApiEscape(omoChecklistT('checklist.action.new')) ?></button>
            <?php endif; ?>
        </div>
        <?php if (count($availableScopes) > 1): ?>
            <div class="omo-panel-view__header-secondary">
                <div class="omo-scope-toolbar__main">
                    <div
                        class="omo-scope-toggle"
                        data-omo-scope-switch="<?= omoApiEscape($checklistScope) ?>"
                        style="--omo-scope-option-count: <?= count($availableScopes) ?>; --omo-scope-active-index: <?= (int)$scopeActiveIndex ?>;"
                    >
                        <?php foreach ($availableScopes as $scopeIndex => $scopeKey): ?>
                            <button
                                type="button"
                                class="omo-scope-toggle__button<?= $checklistScope === $scopeKey ? ' is-active' : '' ?>"
                                data-checklist-scope-option="<?= omoApiEscape($scopeKey) ?>"
                                data-omo-scope-option="<?= omoApiEscape($scopeKey) ?>"
                                data-omo-scope-index="<?= (int)$scopeIndex ?>"
                                aria-pressed="<?= $checklistScope === $scopeKey ? 'true' : 'false' ?>"
                            ><span class="omo-scope-toggle__text"><?= omoApiEscape(omoChecklistT('checklist.scope.' . $scopeKey)) ?></span></button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </header>

    <div class="omo-panel-view__body">
        <div class="omo-panel-view__body_content omo-checklist__body">
            <?php if (count($checklistRows) === 0): ?>
                <div class="omo-empty-state"><?= omoApiEscape(omoChecklistT('checklist.empty.' . $checklistScope)) ?></div>
            <?php else: ?>
                <div class="generic-file-list generic-file-list--structured omo-checklist-list">
                    <div class="generic-file-list__table">
                        <div class="generic-file-list__header" aria-hidden="true">
                            <span class="generic-file-list__header-cell"><?= omoApiEscape(omoChecklistT('checklist.form.title')) ?></span>
                            <span class="generic-file-list__header-cell"><?= omoApiEscape(omoChecklistT('checklist.detail.context')) ?></span>
                            <span class="generic-file-list__header-cell"><?= omoApiEscape(omoChecklistT('checklist.detail.trigger')) ?></span>
                            <span class="generic-file-list__header-cell"><?= omoApiEscape(omoChecklistT('checklist.detail.updated')) ?></span>
                        </div>
                        <?php foreach ($checklistRows as $row): ?>
                            <?php $checklist = $row['checklist']; ?>
                            <div class="generic-file-list__item-shell">
                                <article
                                    class="generic-file-list__row omo-checklist-list__row"
                                    data-checklist-open-detail
                                    data-checklist-id="<?= (int)$checklist->getId() ?>"
                                    tabindex="0"
                                    role="button"
                                    aria-label="<?= omoApiEscape((string)$row['title']) ?>"
                                >
                                    <div class="generic-file-list__cell generic-file-list__cell--name" data-label="<?= omoApiEscape(omoChecklistT('checklist.form.title')) ?>">
                                        <div class="generic-file-list__name-main">
                                            <span class="generic-file-list__icon-box omo-checklist-list__icon" aria-hidden="true"><img src="/omo/images/tools/checklist.png" alt=""></span>
                                            <span class="generic-file-list__title-block">
                                                    <span class="generic-file-list__title-row">
                                                        <strong class="generic-file-list__title"><?= omoApiEscape((string)$row['title']) ?></strong>
                                                        <span class="omo-checklist-status omo-checklist-status--<?= omoApiEscape(Checklist::normalizeStatus($checklist->get('status'))) ?>"><?= omoApiEscape(omoChecklistStatusLabel($checklist->get('status'))) ?></span>
                                                    </span>
                                                    <?php if ((int)$row['recurringActiveCount'] > 0): ?><span class="omo-checklist-list__active-count"><?= omoApiEscape(omoChecklistT('checklist.detail.recurring_instance_count', ['count' => (int)$row['recurringActiveCount']])) ?></span><?php endif; ?>
                                                <span class="generic-file-list__meta-line"><?= omoApiEscape(omoChecklistT('checklist.detail.item_count', ['count' => (int)$row['itemCount']])) ?> · <?= omoApiEscape(omoChecklistT('checklist.detail.open_run_count', ['count' => (int)$row['openRunCount']])) ?><?= $row['description'] !== '' ? ' · ' . omoApiEscape(mb_strimwidth((string)$row['description'], 0, 90, '…', 'UTF-8')) : '' ?></span>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="generic-file-list__cell" data-label="<?= omoApiEscape(omoChecklistT('checklist.detail.context')) ?>"><?= omoApiEscape((string)$row['holon']) ?></div>
                                    <div class="generic-file-list__cell" data-label="<?= omoApiEscape(omoChecklistT('checklist.detail.trigger')) ?>"><?= omoApiEscape((string)$row['triggerLabel']) ?></div>
                                    <div class="generic-file-list__cell generic-file-list__cell--date" data-label="<?= omoApiEscape(omoChecklistT('checklist.detail.updated')) ?>"><?= omoApiEscape((string)$row['updated']) ?></div>
                                </article>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="omo-overlay-drawer omo-checklist__drawer" data-checklist-drawer hidden>
        <div class="omo-overlay-drawer__backdrop" data-checklist-drawer-close></div>
        <div class="omo-overlay-drawer__panel">
            <div class="omo-overlay-drawer__header generic-drawer-header generic-drawer-header--sticky">
                <div class="omo-overlay-drawer__header-copy generic-drawer-header__copy">
                    <h3 class="omo-overlay-drawer__title" data-omo-subdrawer-title><?= omoApiEscape(omoChecklistT('checklist.drawer.title')) ?></h3>
                    <p class="omo-overlay-drawer__description" data-omo-subdrawer-description><?= omoApiEscape(omoChecklistT('checklist.drawer.description')) ?></p>
                </div>
                <div class="generic-drawer-header__actions">
                    <div data-omo-subdrawer-actions></div>
                    <button type="button" class="omo-overlay-drawer__close generic-action-button generic-action-button--secondary" data-checklist-drawer-close><?= omoApiEscape(omoChecklistT('checklist.action.close')) ?></button>
                </div>
            </div>
            <div class="omo-overlay-drawer__body" data-checklist-drawer-body></div>
        </div>
    </div>
</div>
<script src="/common/drawer/subdrawer.js"></script>
<script src="/omo/api/checklist/checklist.js?v=20260722-checklist-detail-route"></script>
