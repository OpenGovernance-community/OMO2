<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\ArrayStatIndicator;
use dbObject\ArrayStatIndicatorGroup;
use dbObject\ArrayStatIndicatorImport;
use dbObject\Holon;
use dbObject\StatIndicator;
use dbObject\StatIndicatorGroup;
use dbObject\StatIndicatorReferencePoint;
use dbObject\StatIndicatorValue;

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$currentHolonId = isset($_GET['cid']) && is_numeric($_GET['cid']) ? (int)$_GET['cid'] : 0;
$openIndicatorId = isset($_GET['open_indicator_id']) && is_numeric($_GET['open_indicator_id']) ? (int)$_GET['open_indicator_id'] : 0;
$context = omoStatsResolveContext($organizationId, $currentHolonId);

if (empty($context['status'])) {
    http_response_code(403);
    ?>
    <div class="omo-stats omo-panel-view">
        <div class="omo-panel-view__body"><div class="omo-panel-view__body_content"><div class="omo-empty-state"><?= omoApiEscape((string)($context['message'] ?? omoStatsT('stats.error.context'))) ?></div></div></div>
    </div>
    <?php
    exit;
}

$organization = $context['organization'];
$rootHolon = $context['rootHolon'];
$currentHolon = $context['currentHolon'];
$canToggleScope = $currentHolon instanceof Holon;
$availableScopes = omoApiGetAvailableContextScopes($canToggleScope, $currentHolon, $rootHolon);
$statsScope = omoApiNormalizeContextScope($_GET['stats_scope'] ?? 'contextual', $availableScopes);
$scopeActiveIndex = omoApiResolveContextScopeIndex($statsScope, $availableScopes);
$requestedStatsSort = (string)($_GET['stats_sort'] ?? 'temporal');
$statsSort = in_array($requestedStatsSort, ['temporal', 'alpha'], true)
    ? $requestedStatsSort
    : 'temporal';
$scopeLabels = [
    'contextual' => omoStatsT('stats.scope.contextual'),
    'descendants' => omoStatsT('stats.scope.descendants'),
    'global' => omoStatsT('stats.scope.global'),
];
$descendantHolonIds = $statsScope === 'descendants' && $currentHolon instanceof Holon
    ? omoApiGetDescendantHolonIds($currentHolon)
    : [];
$indicators = new ArrayStatIndicator();
$indicators->loadForContext(
    $organizationId,
    $currentHolon instanceof Holon ? (int)$currentHolon->getId() : 0,
    $statsScope,
    $descendantHolonIds
);
$indicatorItems = omoStatsCollectionItems($indicators, StatIndicator::class);
$indicatorById = [];
foreach ($indicatorItems as $indicator) {
    $indicatorById[(int)$indicator->getId()] = $indicator;
}

$imports = new ArrayStatIndicatorImport();
$imports->loadForContext(
    $organizationId,
    $currentHolon instanceof Holon ? (int)$currentHolon->getId() : 0,
    $statsScope,
    $descendantHolonIds
);
$importedIndicatorLabels = [];
$importedIndicatorIds = [];
$importedIndicatorEditable = [];
foreach ($imports as $import) {
    $sourceIndicator = $import->getIndicator();
    if (!($sourceIndicator instanceof StatIndicator) || !$sourceIndicator->canView()) {
        continue;
    }
    $sourceId = (int)$sourceIndicator->getId();
    if (isset($indicatorById[$sourceId])) {
        continue;
    }
    $indicatorItems[] = $sourceIndicator;
    $indicatorById[$sourceId] = $sourceIndicator;
    $importedIndicatorLabels[$sourceId] = omoStatsT('stats.card.imported');
    $importedIndicatorIds[$sourceId] = (int)$import->getId();
    $importedIndicatorEditable[$sourceId] = omoStatsCanEditContextResource($import, $context);
}

$groups = new ArrayStatIndicatorGroup();
$groups->loadForContext(
    $organizationId,
    $currentHolon instanceof Holon ? (int)$currentHolon->getId() : 0,
    $statsScope,
    $descendantHolonIds
);
$groupItems = omoStatsCollectionItems($groups, StatIndicatorGroup::class);
$canCreate = omoStatsCanManageContext($context);
$emptyKey = $statsScope === 'global'
    ? 'stats.empty.global'
    : ($statsScope === 'descendants' ? 'stats.empty.descendants' : 'stats.empty.contextual');

$currentUrl = '/omo/api/stats/index.php?oid=' . rawurlencode((string)$organizationId);
if ($currentHolonId > 0) {
    $currentUrl .= '&cid=' . rawurlencode((string)$currentHolonId);
}
if ($statsScope !== 'contextual') {
    $currentUrl .= '&stats_scope=' . rawurlencode($statsScope);
}
$currentUrl .= '&stats_sort=' . rawurlencode($statsSort);
$createUrl = '/omo/api/stats/edit.php?oid=' . rawurlencode((string)$organizationId);
if ($currentHolonId > 0) {
    $createUrl .= '&cid=' . rawurlencode((string)$currentHolonId);
}
$detailBaseUrl = '/omo/api/stats/detail.php?oid=' . rawurlencode((string)$organizationId);
if ($currentHolonId > 0) {
    $detailBaseUrl .= '&cid=' . rawurlencode((string)$currentHolonId);
}
$groupDetailBaseUrl = '/omo/api/stats/group_detail.php?oid=' . rawurlencode((string)$organizationId);
if ($currentHolonId > 0) {
    $groupDetailBaseUrl .= '&cid=' . rawurlencode((string)$currentHolonId);
}

$indicatorViewData = [];
foreach ($indicatorItems as $indicator) {
    $values = omoStatsCollectionItems($indicator->getMeasurements(), StatIndicatorValue::class);
    $referencePoints = omoStatsCollectionItems($indicator->getReferencePoints(), StatIndicatorReferencePoint::class);
    $latestValue = count($values) > 0 ? $values[count($values) - 1] : null;
    $indicatorViewData[] = [
        'indicator' => $indicator,
        'values' => $values,
        'referencePoints' => $referencePoints,
        'latestValue' => $latestValue,
        'contextLabel' => $importedIndicatorLabels[(int)$indicator->getId()] ?? omoStatsContextLabel($indicator),
        'isImported' => isset($importedIndicatorLabels[(int)$indicator->getId()]),
        'importId' => $importedIndicatorIds[(int)$indicator->getId()] ?? 0,
        'canEditImport' => $importedIndicatorEditable[(int)$indicator->getId()] ?? false,
        'isOverdue' => omoStatsIsIndicatorOverdue($indicator),
    ];
}
$groupViewData = [];
foreach ($groupItems as $group) {
    if (!$group->canView()) {
        continue;
    }
    $series = omoStatsGetGroupSeries($group);
    $groupViewData[] = [
        'group' => $group,
        'series' => $series,
        'memberCount' => count(omoStatsCollectionItems($group->getItems(), \dbObject\StatIndicatorGroupItem::class)),
        'indicatorIds' => array_values(array_map(static function ($item) {
            return $item instanceof \dbObject\StatIndicatorGroupItem ? (int)$item->get('IDstatindicator') : 0;
        }, omoStatsCollectionItems($group->getItems(), \dbObject\StatIndicatorGroupItem::class))),
        'canEdit' => omoStatsCanEditContextResource($group, $context),
        'isOverdue' => omoStatsIsGroupOverdue($group),
    ];
}

$statsEntries = [];
foreach ($groupViewData as $groupItem) {
    $group = $groupItem['group'];
    $name = trim((string)$group->get('name'));
    $groupFrequencyRank = 70;
    foreach ($group->getItems() as $groupSourceItem) {
        $sourceIndicator = $groupSourceItem instanceof \dbObject\StatIndicatorGroupItem
            ? $groupSourceItem->getIndicator()
            : null;
        if ($sourceIndicator instanceof StatIndicator) {
            $groupFrequencyRank = min(
                $groupFrequencyRank,
                omoStatsMeasurementFrequencyRank($sourceIndicator->get('measurement_frequency'))
            );
        }
    }
    $frequencyLabelsByRank = [
        10 => StatIndicator::FREQUENCY_DAILY,
        20 => StatIndicator::FREQUENCY_WEEKLY,
        30 => StatIndicator::FREQUENCY_MONTHLY,
        40 => StatIndicator::FREQUENCY_QUARTERLY,
        50 => StatIndicator::FREQUENCY_SEMIANNUAL,
        60 => StatIndicator::FREQUENCY_YEARLY,
    ];
    $groupFrequency = $frequencyLabelsByRank[$groupFrequencyRank] ?? null;
    $statsEntries[] = [
        'kind' => 'group',
        'data' => $groupItem,
        'name' => $name,
        'frequencyRank' => $groupFrequencyRank,
        'category' => $statsSort === 'alpha'
            ? (mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8') ?: '#')
            : omoStatsMeasurementFrequencyLabel($groupFrequency),
    ];
}
foreach ($indicatorViewData as $item) {
    $indicator = $item['indicator'];
    $name = trim((string)$indicator->get('name'));
    $frequency = $indicator->get('measurement_frequency');
    $statsEntries[] = [
        'kind' => 'indicator',
        'data' => $item,
        'name' => $name,
        'frequencyRank' => omoStatsMeasurementFrequencyRank($frequency),
        'category' => $statsSort === 'alpha'
            ? (mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8') ?: '#')
            : omoStatsMeasurementFrequencyLabel($frequency),
    ];
}
usort($statsEntries, static function (array $left, array $right) use ($statsSort) {
    if ($statsSort === 'alpha') {
        $comparison = strcasecmp((string)$left['name'], (string)$right['name']);
    } else {
        $comparison = ((int)$left['frequencyRank']) <=> ((int)$right['frequencyRank']);
        if ($comparison === 0) {
            $comparison = strcasecmp((string)$left['name'], (string)$right['name']);
        }
    }
    return $comparison !== 0 ? $comparison : strcmp((string)$left['kind'], (string)$right['kind']);
});

$pickerIndicators = new ArrayStatIndicator();
$pickerIndicators->loadForContext($organizationId, 0, 'global');
$pickerItems = omoStatsCollectionItems($pickerIndicators, StatIndicator::class);
$pickerData = [];
foreach ($pickerItems as $indicator) {
    $pickerData[] = [
        'id' => (int)$indicator->getId(),
        'name' => trim((string)$indicator->get('name')),
        'context' => omoStatsContextLabel($indicator),
        'description' => trim((string)$indicator->get('description')),
    ];
}
$displayItemCount = count($statsEntries);
?>
<link rel="stylesheet" href="/omo/api/stats/stats.css?v=20260715-7">
<div
    class="omo-stats omo-panel-view"
    id="omo-stats-root"
    data-omo-stats-oid="<?= (int)$organizationId ?>"
    data-omo-stats-cid="<?= $currentHolon instanceof Holon ? (int)$currentHolon->getId() : 0 ?>"
    data-omo-stats-route-cid="<?= (int)$currentHolonId ?>"
    data-omo-stats-root-hid="<?= $rootHolon instanceof Holon ? (int)$rootHolon->getId() : 0 ?>"
    data-omo-stats-scope="<?= omoApiEscape($statsScope) ?>"
    data-omo-stats-current-url="<?= omoApiEscape($currentUrl) ?>"
    data-omo-stats-create-url="<?= omoApiEscape($createUrl) ?>"
    data-omo-stats-detail-url="<?= omoApiEscape($detailBaseUrl) ?>"
    data-omo-stats-group-detail-url="<?= omoApiEscape($groupDetailBaseUrl) ?>"
    data-omo-stats-open-indicator-id="<?= (int)$openIndicatorId ?>"
    data-omo-stats-picker="<?= omoApiEscape(json_encode($pickerData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
>
    <header class="omo-stats__header omo-panel-view__header omo-panel-view__header--stacked">
        <div class="omo-panel-view__header-main">
            <div class="omo-panel-view__title-cluster">
                <span class="omo-panel-view__app-icon omo-stats__app-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M4 19V5M4 19H21M7 15l4-4 3 2 5-7"/><circle cx="7" cy="15" r="1"/><circle cx="11" cy="11" r="1"/><circle cx="14" cy="13" r="1"/><circle cx="19" cy="6" r="1"/></svg>
                </span>
                <div class="omo-panel-view__header-copy">
                    <div class="omo-stats__title-row">
                        <h2 class="omo-panel-view__title"><?= omoApiEscape(omoStatsT('stats.title')) ?></h2>
                        <span class="omo-panel-view__count"><?= $displayItemCount ?></span>
                    </div>
                    <p class="omo-panel-view__description"><?= omoApiEscape(omoStatsT('stats.description')) ?></p>
                </div>
            </div>
            <?php if ($canCreate): ?>
                <div class="omo-stats__header-actions">
                    <button type="button" class="generic-action-button generic-action-button--main omo-mobile-corner-action" data-omo-stats-open-create><?= omoApiEscape(omoStatsT('stats.action.new')) ?></button>
                    <div class="omo-stats__more-menu" data-omo-stats-more-menu>
                        <button type="button" class="generic-action-button generic-action-button--secondary omo-stats__more-toggle" data-omo-stats-more-toggle aria-label="<?= omoApiEscape(omoStatsT('stats.action.more')) ?>" aria-expanded="false">...</button>
                        <div class="omo-stats__more-menu-panel" data-omo-stats-more-panel hidden>
                            <button type="button" data-omo-stats-open-import><?= omoApiEscape(omoStatsT('stats.action.import')) ?></button>
                            <button type="button" data-omo-stats-open-group><?= omoApiEscape(omoStatsT('stats.action.group')) ?></button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div class="omo-panel-view__header-secondary">
            <div class="omo-scope-toolbar__main">
                <?php if (count($availableScopes) > 1): ?>
                    <div
                        class="omo-scope-toggle"
                        data-omo-scope-switch="<?= omoApiEscape($statsScope) ?>"
                        style="--omo-scope-option-count: <?= count($availableScopes) ?>; --omo-scope-active-index: <?= (int)$scopeActiveIndex ?>;"
                    >
                        <?php foreach ($availableScopes as $scopeIndex => $scopeKey): ?>
                            <button
                                type="button"
                                class="omo-scope-toggle__button<?= $statsScope === $scopeKey ? ' is-active' : '' ?>"
                                data-omo-stats-scope="<?= omoApiEscape($scopeKey) ?>"
                                data-omo-scope-option="<?= omoApiEscape($scopeKey) ?>"
                                data-omo-scope-index="<?= (int)$scopeIndex ?>"
                                aria-pressed="<?= $statsScope === $scopeKey ? 'true' : 'false' ?>"
                            ><span class="omo-scope-toggle__text"><?= omoApiEscape($scopeLabels[$scopeKey] ?? $scopeKey) ?></span></button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="omo-stats__header-view-controls">
                <div class="omo-segmented" role="group" aria-label="<?= omoApiEscape(omoStatsT('stats.controls.sort.aria')) ?>">
                    <button type="button" class="omo-segmented__button<?= $statsSort === 'temporal' ? ' is-active' : '' ?>" data-omo-stats-sort="temporal" aria-pressed="<?= $statsSort === 'temporal' ? 'true' : 'false' ?>"><?= omoApiEscape(omoStatsT('stats.controls.sort.temporal')) ?></button>
                    <button type="button" class="omo-segmented__button<?= $statsSort === 'alpha' ? ' is-active' : '' ?>" data-omo-stats-sort="alpha" aria-pressed="<?= $statsSort === 'alpha' ? 'true' : 'false' ?>"><?= omoApiEscape(omoStatsT('stats.controls.sort.alpha')) ?></button>
                </div>
                <div class="omo-segmented" aria-label="<?= omoApiEscape(omoStatsT('stats.title')) ?>">
                    <button type="button" class="omo-segmented__button is-active" data-omo-stats-view="cards" aria-pressed="true"><?= omoApiEscape(omoStatsT('stats.view.cards')) ?></button>
                    <button type="button" class="omo-segmented__button" data-omo-stats-view="compact" aria-pressed="false"><?= omoApiEscape(omoStatsT('stats.view.compact')) ?></button>
                </div>
            </div>
        </div>
    </header>

    <div class="omo-panel-view__body">
        <div class="omo-panel-view__body_content omo-stats__body">
            <section data-omo-stats-view-panel="cards">
                <?php if ($displayItemCount === 0): ?>
                    <div class="omo-empty-state"><?= omoApiEscape(omoStatsT($emptyKey)) ?></div>
                <?php else: ?>
                    <div class="omo-stats-grid">
                        <?php $currentStatsCategory = null; ?>
                        <?php foreach ($statsEntries as $statsEntry): ?>
                            <?php
                            if ($statsEntry['category'] !== $currentStatsCategory):
                                if ($currentStatsCategory !== null):
                            ?>
                                    </div>
                                </section>
                            <?php
                                endif;
                                $currentStatsCategory = $statsEntry['category'];
                            ?>
                                <section class="omo-panel-group generic-file-list__group omo-stats__sort-group">
                                    <h3 class="omo-panel-group__title generic-file-list__group-title"><?= omoApiEscape($currentStatsCategory) ?></h3>
                                    <div class="omo-stats-grid omo-stats__sort-group-items">
                            <?php endif; ?>
                            <?php if ($statsEntry['kind'] === 'group'): ?>
                            <?php $groupItem = $statsEntry['data']; $group = $groupItem['group']; ?>
                            <article
                                class="generic-section omo-stats-card omo-stats-card--group<?= $groupItem['isOverdue'] ? ' omo-stats-card--overdue' : '' ?>"
                                data-omo-stats-group-id="<?= (int)$group->getId() ?>"
                                tabindex="0"
                                role="button"
                                aria-label="<?= omoApiEscape(omoStatsT('stats.card.open', ['name' => (string)$group->get('name')])) ?>"
                            >
                                <div class="omo-stats-card__header">
                                    <div>
                                        <span class="generic-card-title generic-card-title--eyebrow"><?= omoApiEscape(omoStatsT('stats.card.group')) ?></span>
                                        <h3 class="generic-card-title generic-card-title--big"><?= omoApiEscape((string)$group->get('name')) ?></h3>
                                    </div>
                                    <span class="omo-stats-card__value-count"><?= omoApiEscape(omoStatsT('stats.card.member_count', ['count' => $groupItem['memberCount']])) ?></span>
                                    <?php if ($groupItem['canEdit']): ?>
                                        <div class="omo-stats-item-menu" data-omo-stats-item-menu>
                                            <button type="button" class="omo-stats-item-menu__toggle" data-omo-stats-item-menu-toggle aria-label="<?= omoApiEscape(omoStatsT('stats.action.more')) ?>" aria-expanded="false">...</button>
                                            <div class="omo-stats-item-menu__panel" data-omo-stats-item-menu-panel hidden>
                                                <button type="button" data-omo-stats-edit-group="<?= (int)$group->getId() ?>" data-omo-stats-group-name="<?= omoApiEscape((string)$group->get('name')) ?>" data-omo-stats-group-mode="<?= omoApiEscape((string)$group->get('display_mode')) ?>" data-omo-stats-group-indicators="<?= omoApiEscape(json_encode($groupItem['indicatorIds'])) ?>"><?= omoApiEscape(omoStatsT('stats.action.edit_group')) ?></button>
                                                <button type="button" data-omo-stats-delete-group="<?= (int)$group->getId() ?>"><?= omoApiEscape(omoStatsT('stats.action.delete_group')) ?></button>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="omo-stats-card__chart"><?= omoStatsRenderGroupChart($group, $groupItem['series'], 'card', $groupItem['isOverdue']) ?></div>
                                <div class="omo-stats-card__footer"><span><?= omoApiEscape(StatIndicatorGroup::normalizeDisplayMode($group->get('display_mode')) === StatIndicatorGroup::DISPLAY_SUM ? omoStatsT('stats.group.mode.sum') : omoStatsT('stats.group.mode.overlay')) ?></span></div>
                            </article>
                            <?php else: ?>
                            <?php $item = $statsEntry['data']; ?>
                            <?php
                            $indicator = $item['indicator'];
                            $latestValue = $item['latestValue'];
                            $indicatorName = trim((string)$indicator->get('name'));
                            ?>
                            <article
                                class="generic-section omo-stats-card<?= $item['isOverdue'] ? ' omo-stats-card--overdue' : '' ?>"
                                data-omo-stats-indicator-id="<?= (int)$indicator->getId() ?>"
                                tabindex="0"
                                role="button"
                                aria-label="<?= omoApiEscape(omoStatsT('stats.card.open', ['name' => $indicatorName])) ?>"
                            >
                                <div class="omo-stats-card__header">
                                    <div>
                                        <span class="generic-card-title generic-card-title--eyebrow"><?= omoApiEscape((string)$item['contextLabel']) ?></span>
                                        <h3 class="generic-card-title generic-card-title--big"><?= omoApiEscape($indicatorName) ?></h3>
                                    </div>
                                    <span class="omo-stats-card__value-count"><?= omoApiEscape(omoStatsT('stats.card.value_count', ['count' => count($item['values'])])) ?></span>
                                    <?php if ($item['isImported'] ? $item['canEditImport'] : $indicator->canEdit()): ?>
                                        <div class="omo-stats-item-menu" data-omo-stats-item-menu>
                                            <button type="button" class="omo-stats-item-menu__toggle" data-omo-stats-item-menu-toggle aria-label="<?= omoApiEscape(omoStatsT('stats.action.more')) ?>" aria-expanded="false">...</button>
                                            <div class="omo-stats-item-menu__panel" data-omo-stats-item-menu-panel hidden>
                                                <?php if ($item['isImported']): ?>
                                                    <button type="button" data-omo-stats-edit-import="<?= (int)$item['importId'] ?>" data-omo-stats-indicator-id="<?= (int)$indicator->getId() ?>"><?= omoApiEscape(omoStatsT('stats.action.edit_import')) ?></button>
                                                    <button type="button" data-omo-stats-delete-import="<?= (int)$item['importId'] ?>"><?= omoApiEscape(omoStatsT('stats.action.delete_import')) ?></button>
                                                <?php else: ?>
                                                    <button type="button" data-omo-stats-open-editor-url="<?= omoApiEscape($detailBaseUrl . '&id=' . rawurlencode((string)$indicator->getId())) ?>"><?= omoApiEscape(omoStatsT('stats.action.edit')) ?></button>
                                                    <button type="button" data-omo-stats-delete-indicator="<?= (int)$indicator->getId() ?>"><?= omoApiEscape(omoStatsT('stats.action.delete_indicator')) ?></button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="omo-stats-card__chart">
                                    <?= omoStatsRenderChart($indicator, $item['values'], $item['referencePoints'], 'card', $item['isOverdue']) ?>
                                </div>
                                <div class="omo-stats-card__footer">
                                    <span><?= omoApiEscape(omoStatsT('stats.card.latest')) ?></span>
                                    <?php if ($latestValue instanceof StatIndicatorValue): ?>
                                        <strong><?= omoApiEscape(omoStatsFormatNumber($latestValue->get('value'))) ?></strong>
                                        <time><?= omoApiEscape(omoStatsFormatDateTime($latestValue->get('measured_at'), false)) ?></time>
                                    <?php else: ?>
                                        <strong class="omo-stats-card__empty-value"><?= omoApiEscape(omoStatsT('stats.card.no_value')) ?></strong>
                                    <?php endif; ?>
                                </div>
                            </article>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php if ($currentStatsCategory !== null): ?>
                                    </div>
                                </section>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="generic-file-list generic-file-list--structured omo-stats-compact" data-omo-stats-view-panel="compact" hidden>
                <?php if ($displayItemCount === 0): ?>
                    <div class="omo-empty-state"><?= omoApiEscape(omoStatsT($emptyKey)) ?></div>
                <?php else: ?>
                        <?php $currentStatsCategory = null; ?>
                        <?php foreach ($statsEntries as $statsEntry): ?>
                            <?php
                            if ($statsEntry['category'] !== $currentStatsCategory):
                                if ($currentStatsCategory !== null):
                            ?>
                                    </div>
                                </section>
                            <?php
                                endif;
                                $currentStatsCategory = $statsEntry['category'];
                            ?>
                                <section class="omo-panel-group generic-file-list__group omo-stats__sort-group">
                                    <h3 class="omo-panel-group__title generic-file-list__group-title"><?= omoApiEscape($currentStatsCategory) ?></h3>
                                    <div class="omo-stats-compact__sort-group-table omo-panel-view__body_content generic-file-list__table">
                                        <div class="generic-file-list__header">
                                            <div class="generic-file-list__header-cell"><?= omoApiEscape(omoStatsT('stats.column.indicator')) ?></div>
                                            <div class="generic-file-list__header-cell"><?= omoApiEscape(omoStatsT('stats.column.context')) ?></div>
                                            <div class="generic-file-list__header-cell"><?= omoApiEscape(omoStatsT('stats.column.latest')) ?></div>
                                            <div class="generic-file-list__header-cell"><?= omoApiEscape(omoStatsT('stats.column.history')) ?></div>
                                        </div>
                            <?php endif; ?>
                            <?php if ($statsEntry['kind'] === 'group'): ?>
                            <?php $groupItem = $statsEntry['data']; $group = $groupItem['group']; ?>
                            <article class="generic-file-list__item-shell">
                                <div
                                    class="generic-file-list__row omo-stats-compact__row omo-stats-compact__row--group<?= $groupItem['isOverdue'] ? ' omo-stats-compact__row--overdue' : '' ?>"
                                    data-omo-stats-group-id="<?= (int)$group->getId() ?>"
                                    tabindex="0"
                                    role="button"
                                    aria-label="<?= omoApiEscape(omoStatsT('stats.card.open', ['name' => (string)$group->get('name')])) ?>"
                                >
                                    <?php if ($groupItem['canEdit']): ?>
                                        <div class="omo-stats-item-menu" data-omo-stats-item-menu>
                                            <button type="button" class="omo-stats-item-menu__toggle" data-omo-stats-item-menu-toggle aria-label="<?= omoApiEscape(omoStatsT('stats.action.more')) ?>" aria-expanded="false">...</button>
                                            <div class="omo-stats-item-menu__panel" data-omo-stats-item-menu-panel hidden>
                                                <button type="button" data-omo-stats-edit-group="<?= (int)$group->getId() ?>" data-omo-stats-group-name="<?= omoApiEscape((string)$group->get('name')) ?>" data-omo-stats-group-mode="<?= omoApiEscape((string)$group->get('display_mode')) ?>" data-omo-stats-group-indicators="<?= omoApiEscape(json_encode($groupItem['indicatorIds'])) ?>"><?= omoApiEscape(omoStatsT('stats.action.edit_group')) ?></button>
                                                <button type="button" data-omo-stats-delete-group="<?= (int)$group->getId() ?>"><?= omoApiEscape(omoStatsT('stats.action.delete_group')) ?></button>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <div class="generic-file-list__cell generic-file-list__cell--name" data-label="<?= omoApiEscape(omoStatsT('stats.column.indicator')) ?>">
                                        <div class="generic-file-list__name-main">
                                            <span class="omo-stats-compact__dot omo-stats-compact__dot--group" aria-hidden="true"></span>
                                            <div class="generic-file-list__title-block">
                                                <strong class="generic-file-list__title"><?= omoApiEscape((string)$group->get('name')) ?></strong>
                                                <span class="generic-file-list__meta-line"><?= omoApiEscape(omoStatsT('stats.card.member_count', ['count' => $groupItem['memberCount']])) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="generic-file-list__cell" data-label="<?= omoApiEscape(omoStatsT('stats.column.context')) ?>"><?= omoApiEscape(omoStatsT('stats.card.group')) ?></div>
                                    <div class="generic-file-list__cell omo-stats-compact__latest" data-label="<?= omoApiEscape(omoStatsT('stats.column.latest')) ?>"><span><?= omoApiEscape(StatIndicatorGroup::normalizeDisplayMode($group->get('display_mode')) === StatIndicatorGroup::DISPLAY_SUM ? omoStatsT('stats.group.mode.sum') : omoStatsT('stats.group.mode.overlay')) ?></span></div>
                                    <div class="generic-file-list__cell omo-stats-compact__chart" data-label="<?= omoApiEscape(omoStatsT('stats.column.history')) ?>"><?= omoStatsRenderGroupChart($group, $groupItem['series'], 'compact', $groupItem['isOverdue']) ?></div>
                                </div>
                            </article>
                            <?php else: ?>
                            <?php $item = $statsEntry['data']; ?>
                            <?php
                            $indicator = $item['indicator'];
                            $latestValue = $item['latestValue'];
                            $indicatorName = trim((string)$indicator->get('name'));
                            ?>
                            <article class="generic-file-list__item-shell">
                                <div
                                    class="generic-file-list__row omo-stats-compact__row<?= $item['isOverdue'] ? ' omo-stats-compact__row--overdue' : '' ?>"
                                    data-omo-stats-indicator-id="<?= (int)$indicator->getId() ?>"
                                    tabindex="0"
                                    role="button"
                                    aria-label="<?= omoApiEscape(omoStatsT('stats.card.open', ['name' => $indicatorName])) ?>"
                                >
                                    <?php if ($item['isImported'] ? $item['canEditImport'] : $indicator->canEdit()): ?>
                                        <div class="omo-stats-item-menu" data-omo-stats-item-menu>
                                            <button type="button" class="omo-stats-item-menu__toggle" data-omo-stats-item-menu-toggle aria-label="<?= omoApiEscape(omoStatsT('stats.action.more')) ?>" aria-expanded="false">...</button>
                                            <div class="omo-stats-item-menu__panel" data-omo-stats-item-menu-panel hidden>
                                                <?php if ($item['isImported']): ?>
                                                    <button type="button" data-omo-stats-edit-import="<?= (int)$item['importId'] ?>" data-omo-stats-indicator-id="<?= (int)$indicator->getId() ?>"><?= omoApiEscape(omoStatsT('stats.action.edit_import')) ?></button>
                                                    <button type="button" data-omo-stats-delete-import="<?= (int)$item['importId'] ?>"><?= omoApiEscape(omoStatsT('stats.action.delete_import')) ?></button>
                                                <?php else: ?>
                                                    <button type="button" data-omo-stats-open-editor-url="<?= omoApiEscape($detailBaseUrl . '&id=' . rawurlencode((string)$indicator->getId())) ?>"><?= omoApiEscape(omoStatsT('stats.action.edit')) ?></button>
                                                    <button type="button" data-omo-stats-delete-indicator="<?= (int)$indicator->getId() ?>"><?= omoApiEscape(omoStatsT('stats.action.delete_indicator')) ?></button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <div class="generic-file-list__cell generic-file-list__cell--name" data-label="<?= omoApiEscape(omoStatsT('stats.column.indicator')) ?>">
                                        <div class="generic-file-list__name-main">
                                            <span class="omo-stats-compact__dot" aria-hidden="true"></span>
                                            <div class="generic-file-list__title-block">
                                                <strong class="generic-file-list__title"><?= omoApiEscape($indicatorName) ?></strong>
                                                <span class="generic-file-list__meta-line"><?= omoApiEscape(omoStatsT('stats.card.value_count', ['count' => count($item['values'])])) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="generic-file-list__cell" data-label="<?= omoApiEscape(omoStatsT('stats.column.context')) ?>"><?= omoApiEscape((string)$item['contextLabel']) ?></div>
                                    <div class="generic-file-list__cell omo-stats-compact__latest" data-label="<?= omoApiEscape(omoStatsT('stats.column.latest')) ?>">
                                        <?php if ($latestValue instanceof StatIndicatorValue): ?>
                                            <strong><?= omoApiEscape(omoStatsFormatNumber($latestValue->get('value'))) ?></strong>
                                            <time><?= omoApiEscape(omoStatsFormatDateTime($latestValue->get('measured_at'), false)) ?></time>
                                        <?php else: ?>
                                            <span>—</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="generic-file-list__cell omo-stats-compact__chart" data-label="<?= omoApiEscape(omoStatsT('stats.column.history')) ?>">
                                        <?= omoStatsRenderChart($indicator, $item['values'], $item['referencePoints'], 'compact', $item['isOverdue']) ?>
                                    </div>
                                </div>
                            </article>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php if ($currentStatsCategory !== null): ?>
                                    </div>
                                </section>
                        <?php endif; ?>
                <?php endif; ?>
            </section>
        </div>
    </div>

    <div class="omo-overlay-drawer omo-stats__detail-drawer" data-omo-stats-drawer hidden>
        <div class="omo-overlay-drawer__backdrop" data-omo-stats-drawer-close></div>
        <div class="omo-overlay-drawer__panel">
            <div class="omo-overlay-drawer__header generic-drawer-header generic-drawer-header--sticky">
                <div class="omo-overlay-drawer__header-copy generic-drawer-header__copy">
                    <h3 class="omo-overlay-drawer__title" data-omo-subdrawer-title><?= omoApiEscape(omoStatsT('stats.drawer.title')) ?></h3>
                    <p class="omo-overlay-drawer__description" data-omo-subdrawer-description><?= omoApiEscape(omoStatsT('stats.drawer.description')) ?></p>
                </div>
                <div class="generic-drawer-header__actions">
                    <div class="omo-stats__drawer-custom-actions" data-omo-subdrawer-actions></div>
                    <button type="button" class="omo-overlay-drawer__close generic-action-button generic-action-button--secondary" data-omo-stats-drawer-close><?= omoApiEscape(omoStatsT('stats.action.close')) ?></button>
                </div>
            </div>
            <div class="omo-overlay-drawer__body" data-omo-stats-drawer-body></div>
        </div>
    </div>
</div>
<script src="/common/drawer/subdrawer.js"></script>
<script>
(function () {
    var root = document.getElementById('omo-stats-root');
    if (!root || root.dataset.omoStatsReady === '1') {
        return;
    }
    root.dataset.omoStatsReady = '1';

    var drawer = root.querySelector('[data-omo-stats-drawer]');
    var drawerBody = root.querySelector('[data-omo-stats-drawer-body]');
    var drawerController = drawer && typeof window.omoCreateSubdrawerController === 'function'
        ? window.omoCreateSubdrawerController({ drawer: drawer })
        : null;
    if (drawerController) {
        drawer.__omoSubdrawerController = drawerController;
        window.omoStatsDrawer = drawerController;
    }
    var currentUrl = root.getAttribute('data-omo-stats-current-url') || '';
    var createUrl = root.getAttribute('data-omo-stats-create-url') || '';
    var detailBaseUrl = root.getAttribute('data-omo-stats-detail-url') || '';
    var groupDetailBaseUrl = root.getAttribute('data-omo-stats-group-detail-url') || '';
    var currentScope = root.getAttribute('data-omo-stats-scope') || 'contextual';
    var routeCid = Number(root.getAttribute('data-omo-stats-route-cid') || 0);
    var initialIndicatorId = Number(root.getAttribute('data-omo-stats-open-indicator-id') || 0);
    var requestToken = 0;
    var listNeedsRefresh = false;
    var storageKey = 'omoStatsDisplayMode';
    var texts = <?= json_encode([
        'loading' => omoStatsT('stats.loading'),
        'loadError' => omoStatsT('stats.error.load'),
        'confirmDelete' => omoStatsT('stats.detail.confirm_delete'),
        'confirmDeleteIndicator' => omoStatsT('stats.detail.confirm_delete_indicator'),
        'confirmDeleteImport' => omoStatsT('stats.detail.confirm_delete_import'),
        'confirmDeleteGroup' => omoStatsT('stats.detail.confirm_delete_group'),
        'importTitle' => omoStatsT('stats.import.title'),
        'editImportTitle' => omoStatsT('stats.import.edit_title'),
        'groupTitle' => omoStatsT('stats.group.title'),
        'editGroupTitle' => omoStatsT('stats.group.edit_title'),
        'search' => omoStatsT('stats.import.search'),
        'searchPlaceholder' => omoStatsT('stats.import.search_placeholder'),
        'visible' => omoStatsT('stats.import.visible'),
        'groupName' => omoStatsT('stats.group.name'),
        'groupMode' => omoStatsT('stats.group.mode'),
        'overlay' => omoStatsT('stats.group.mode.overlay'),
        'sum' => omoStatsT('stats.group.mode.sum'),
        'cancel' => omoStatsT('stats.action.cancel'),
        'add' => omoStatsT('stats.action.add'),
        'update' => omoStatsT('stats.action.update'),
        'createGroup' => omoStatsT('stats.action.create_group'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    function resolveUrl(url) {
        return typeof window.omoResolveAppUrl === 'function' ? window.omoResolveAppUrl(url) : url;
    }

    function normalizeScope(scope) {
        return scope === 'global' || scope === 'descendants' ? scope : 'contextual';
    }

    function buildScopeUrl(scope) {
        var organizationId = Number(root.getAttribute('data-omo-stats-oid') || 0);
        var query = ['oid=' + encodeURIComponent(String(organizationId))];
        var nextScope = normalizeScope(scope);
        if (routeCid > 0) {
            query.push('cid=' + encodeURIComponent(String(routeCid)));
        }
        if (nextScope !== 'contextual') {
            query.push('stats_scope=' + encodeURIComponent(nextScope));
        }
        try {
            var currentSort = new URL(currentUrl, window.location.origin).searchParams.get('stats_sort');
            if (currentSort === 'alpha' || currentSort === 'temporal') {
                query.push('stats_sort=' + encodeURIComponent(currentSort));
            }
        } catch (error) {
            // Keep the default sort when the current URL cannot be parsed.
        }
        return '/omo/api/stats/index.php?' + query.join('&');
    }

    function setLoading(isLoading) {
        root.classList.toggle('is-loading', Boolean(isLoading));
        Array.prototype.forEach.call(root.querySelectorAll('[data-omo-stats-scope]'), function (button) {
            button.disabled = Boolean(isLoading);
        });
    }

    function refreshRoot(url) {
        var targetUrl = url || currentUrl;
        if (!targetUrl) {
            return Promise.resolve(null);
        }
        if (typeof window.omoReplaceFetchedPanelRoot !== 'function') {
            window.location.href = resolveUrl(targetUrl);
            return Promise.resolve(null);
        }
        return window.omoReplaceFetchedPanelRoot({
            rootSelector: '#omo-stats-root',
            currentRoot: root,
            url: resolveUrl(targetUrl),
            setLoadingState: setLoading
        });
    }

    function applyView(viewName) {
        var view = viewName === 'compact' ? 'compact' : 'cards';
        Array.prototype.forEach.call(root.querySelectorAll('[data-omo-stats-view]'), function (button) {
            var active = button.getAttribute('data-omo-stats-view') === view;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
        Array.prototype.forEach.call(root.querySelectorAll('[data-omo-stats-view-panel]'), function (panel) {
            panel.hidden = panel.getAttribute('data-omo-stats-view-panel') !== view;
        });
        try {
            window.localStorage.setItem(storageKey, view);
        } catch (error) {
        }
    }

    function executeFetchedScripts(container) {
        var scripts = Array.prototype.slice.call(container ? container.querySelectorAll('script') : []);
        var chain = Promise.resolve();
        scripts.forEach(function (script) {
            chain = chain.then(function () {
                var source = String(script.getAttribute('src') || '').trim();
                if (source) {
                    var existing = Array.prototype.some.call(document.querySelectorAll('script[src]'), function (candidate) {
                        return candidate !== script && String(candidate.getAttribute('src') || '') === source;
                    });
                    if (existing) {
                        return null;
                    }
                    return new Promise(function (resolve) {
                        var external = document.createElement('script');
                        Array.prototype.forEach.call(script.attributes, function (attribute) {
                            external.setAttribute(attribute.name, attribute.value);
                        });
                        external.onload = resolve;
                        external.onerror = resolve;
                        document.body.appendChild(external);
                    });
                }
                var executable = document.createElement('script');
                executable.text = script.textContent || '';
                document.body.appendChild(executable);
                document.body.removeChild(executable);
                return null;
            });
        });
        return chain;
    }

    function buildDetailUrl(indicatorId) {
        return detailBaseUrl + (detailBaseUrl.indexOf('?') === -1 ? '?' : '&') + 'id=' + encodeURIComponent(String(indicatorId));
    }

    function buildGroupDetailUrl(groupId) {
        return groupDetailBaseUrl + (groupDetailBaseUrl.indexOf('?') === -1 ? '?' : '&') + 'id=' + encodeURIComponent(String(groupId));
    }

    function setDrawerMessage(message, isError) {
        if (!drawerBody) {
            return;
        }
        if (drawerController) {
            drawerController.resetHeader();
        }
        drawerBody.innerHTML = '<div class="generic-section' + (isError ? ' omo-stats-feedback is-error' : '') + '"></div>';
        drawerBody.firstElementChild.textContent = message;
    }

    function openDrawerWithUrl(url) {
        if (!drawer || !drawerBody || !url) {
            return Promise.resolve(false);
        }
        setDrawerMessage(texts.loading, false);
        drawer.hidden = false;
        window.requestAnimationFrame(function () {
            drawer.classList.add('is-open');
        });
        var localToken = ++requestToken;
        return fetch(resolveUrl(url), {
            credentials: 'same-origin',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            cache: 'no-store'
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('load_failed');
            }
            return response.text();
        }).then(function (html) {
            if (localToken !== requestToken || !drawerBody) {
                return false;
            }
            drawerBody.innerHTML = html;
            if (drawerController) {
                drawerController.applyContentHeader(drawerBody);
            }
            if (typeof window.initGenericComponents === 'function') {
                window.initGenericComponents(drawerBody);
            }
            return executeFetchedScripts(drawerBody).then(function () {
                if (typeof window.initGenericComponents === 'function') {
                    window.initGenericComponents(drawerBody);
                }
                if (typeof window.omoStatsInitInteractiveCharts === 'function') {
                    window.omoStatsInitInteractiveCharts(drawerBody);
                }
                return true;
            });
        }).catch(function () {
            if (localToken === requestToken) {
                setDrawerMessage(texts.loadError, true);
            }
            return false;
        });
    }

    function getCurrentRouteToken() {
        if (typeof window.omoParsePopupHashState !== 'function') {
            return '';
        }
        var state = window.omoParsePopupHashState();
        return state && state.routeToken ? String(state.routeToken) : '';
    }

    function closeDrawer(options) {
        var settings = options && typeof options === 'object' ? options : {};
        if (
            settings.force !== true
            && /^stats-(?:i|indicator-)(\d+)$/i.test(getCurrentRouteToken())
            && typeof window.omoOpenDrawerHashState === 'function'
        ) {
            window.omoOpenDrawerHashState('stats');
            return;
        }
        if (!drawer) {
            return;
        }
        drawer.classList.remove('is-open');
        window.setTimeout(function () {
            if (!drawer.classList.contains('is-open')) {
                drawer.hidden = true;
                if (drawerBody) {
                    drawerBody.innerHTML = '';
                }
                if (listNeedsRefresh) {
                    listNeedsRefresh = false;
                    refreshRoot(currentUrl);
                }
            }
        }, 180);
    }

    function openIndicator(indicatorId) {
        var resolvedId = Number(indicatorId || 0);
        if (!Number.isInteger(resolvedId) || resolvedId <= 0) {
            return;
        }
        var routeToken = typeof window.omoBuildStatsIndicatorRouteToken === 'function'
            ? window.omoBuildStatsIndicatorRouteToken(resolvedId)
            : 'stats-i' + String(resolvedId);
        if (typeof window.omoOpenDrawerHashState === 'function' && routeToken !== getCurrentRouteToken()) {
            window.omoOpenDrawerHashState(routeToken);
            return;
        }
        openDrawerWithUrl(buildDetailUrl(resolvedId));
    }

    function openGroup(groupId) {
        var resolvedId = Number(groupId || 0);
        if (!Number.isInteger(resolvedId) || resolvedId <= 0) {
            return;
        }
        openDrawerWithUrl(buildGroupDetailUrl(resolvedId));
    }

    function postFormData(formData) {
        return fetch(resolveUrl('/omo/api/stats/action.php'), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            body: formData
        }).then(function (response) {
            return response.json().catch(function () {
                return null;
            }).then(function (payload) {
                if (!response.ok || !payload || payload.success !== true) {
                    throw new Error(payload && payload.message ? payload.message : texts.loadError);
                }
                return payload;
            });
        });
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (character) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[character];
        });
    }

    function getPickerItems() {
        try {
            var raw = root.getAttribute('data-omo-stats-picker') || '[]';
            var items = JSON.parse(raw);
            return Array.isArray(items) ? items : [];
        } catch (error) {
            return [];
        }
    }

    function openGroupDrawerEditor(editData) {
        if (!drawer || !drawerBody) {
            return;
        }

        var isEditing = editData && typeof editData === 'object';
        var groupId = isEditing ? Number(editData.id || 0) : 0;
        var selectedIds = isEditing && Array.isArray(editData.indicatorIds)
            ? editData.indicatorIds.map(function (id) { return String(id); })
            : [];
        var formId = 'omoStatsGroupEditorForm';
        var items = getPickerItems();
        var formHtml = '<form id="' + formId + '" class="omo-stats-picker omo-stats-group-editor" data-omo-stats-group-editor-form>'
            + '<label class="omo-stats-picker__field"><span>' + escapeHtml(texts.groupName) + '</span><input type="text" class="generic-form-control" data-omo-stats-group-editor-name required></label>'
            + '<label class="omo-stats-picker__field"><span>' + escapeHtml(texts.search) + '</span><input type="search" class="generic-form-control" data-omo-stats-group-editor-search placeholder="' + escapeHtml(texts.searchPlaceholder) + '"></label>'
            + '<label class="omo-stats-picker__field"><span>' + escapeHtml(texts.visible) + '</span><select class="generic-form-control omo-stats-picker__select" data-omo-stats-group-editor-select size="10" multiple></select></label>'
            + '<label class="omo-stats-picker__field"><span>' + escapeHtml(texts.groupMode) + '</span><select class="generic-form-control" data-omo-stats-group-editor-mode><option value="overlay">' + escapeHtml(texts.overlay) + '</option><option value="sum">' + escapeHtml(texts.sum) + '</option></select></label>'
            + '<div class="omo-stats-feedback" data-omo-stats-group-editor-feedback role="status"></div>'
            + '</form>';

        drawerBody.innerHTML = formHtml;
        drawer.hidden = false;
        window.requestAnimationFrame(function () {
            drawer.classList.add('is-open');
        });

        var form = drawerBody.querySelector('[data-omo-stats-group-editor-form]');
        var nameInput = drawerBody.querySelector('[data-omo-stats-group-editor-name]');
        var searchInput = drawerBody.querySelector('[data-omo-stats-group-editor-search]');
        var select = drawerBody.querySelector('[data-omo-stats-group-editor-select]');
        var modeInput = drawerBody.querySelector('[data-omo-stats-group-editor-mode]');
        var feedback = drawerBody.querySelector('[data-omo-stats-group-editor-feedback]');
        var cancelButton = document.createElement('button');
        var saveButton = document.createElement('button');

        if (!form || !nameInput || !select || !modeInput) {
            setDrawerMessage(texts.loadError, true);
            return;
        }

        nameInput.value = isEditing ? String(editData.name || '') : '';
        modeInput.value = isEditing ? String(editData.displayMode || 'overlay') : 'overlay';

        function retainVisibleSelection() {
            selectedIds = Array.prototype.map.call(select.selectedOptions, function (option) {
                return option.value;
            }).concat(selectedIds.filter(function (id) {
                return !Array.prototype.some.call(select.options, function (option) {
                    return option.value === id;
                });
            }));
            selectedIds = selectedIds.filter(function (id, index, values) {
                return values.indexOf(id) === index;
            });
        }

        function renderOptions() {
            retainVisibleSelection();

            var query = String(searchInput ? searchInput.value : '').trim().toLocaleLowerCase();
            select.innerHTML = '';
            items.forEach(function (item) {
                var haystack = [item.name, item.context, item.description].join(' ').toLocaleLowerCase();
                if (query && haystack.indexOf(query) === -1) {
                    return;
                }
                var option = document.createElement('option');
                option.value = String(item.id || '');
                option.textContent = String(item.name || '') + (item.context ? ' - ' + String(item.context) : '');
                option.selected = selectedIds.indexOf(option.value) !== -1;
                select.appendChild(option);
            });
        }

        cancelButton.type = 'button';
        cancelButton.className = 'generic-action-button generic-action-button--secondary';
        cancelButton.textContent = texts.cancel;
        cancelButton.addEventListener('click', function () {
            if (Number.isInteger(groupId) && groupId > 0) {
                openDrawerWithUrl(buildGroupDetailUrl(groupId));
                return;
            }
            closeDrawer({ force: true });
        });

        saveButton.type = 'submit';
        saveButton.setAttribute('form', formId);
        saveButton.className = 'generic-action-button generic-action-button--main';
        saveButton.textContent = isEditing ? texts.update : texts.createGroup;

        if (drawerController) {
            drawerController.setHeader({
                title: isEditing ? texts.editGroupTitle : texts.groupTitle,
                description: '',
                actions: [cancelButton, saveButton]
            });
        }

        renderOptions();
        nameInput.focus();
        if (searchInput) {
            searchInput.addEventListener('input', renderOptions);
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            retainVisibleSelection();
            var selectedIndicatorIds = selectedIds.filter(Boolean);
            var formData = new FormData();
            formData.append('stats_action', isEditing ? 'update_group' : 'create_group');
            formData.append('oid', root.getAttribute('data-omo-stats-oid') || '');
            formData.append('cid', root.getAttribute('data-omo-stats-cid') || '');
            if (isEditing) {
                formData.append('group_id', String(groupId));
            }
            formData.append('name', nameInput.value || '');
            formData.append('display_mode', modeInput.value || 'overlay');
            selectedIndicatorIds.forEach(function (id) {
                formData.append('indicator_ids[]', id);
            });

            saveButton.disabled = true;
            cancelButton.disabled = true;
            if (feedback) {
                feedback.textContent = '';
                feedback.className = 'omo-stats-feedback';
            }

            postFormData(formData).then(function (payload) {
                listNeedsRefresh = true;
                var savedGroupId = Number(payload && payload.id ? payload.id : groupId);
                if (Number.isInteger(savedGroupId) && savedGroupId > 0) {
                    openDrawerWithUrl(buildGroupDetailUrl(savedGroupId));
                    return;
                }
                closeDrawer({ force: true });
            }).catch(function (error) {
                if (feedback) {
                    feedback.textContent = error.message || texts.loadError;
                    feedback.className = 'omo-stats-feedback is-error';
                }
                saveButton.disabled = false;
                cancelButton.disabled = false;
            });
        });
    }

    function openContextPicker(mode, editData) {
        if (mode === 'group') {
            openGroupDrawerEditor(editData);
            return;
        }

        if (typeof window.commonTopbarOpenModal !== 'function') {
            return;
        }
        var isGroup = mode === 'group';
        var isEditing = editData && typeof editData === 'object';
        var title = isGroup
            ? (isEditing ? texts.editGroupTitle : texts.groupTitle)
            : (isEditing ? texts.editImportTitle : texts.importTitle);
        var multiple = isGroup ? ' multiple' : '';
        var selectedIds = isEditing && Array.isArray(editData.indicatorIds)
            ? editData.indicatorIds.map(function (id) { return String(id); })
            : [];
        var firstRender = true;
        var html = '<div class="omo-stats-picker">'
            + (isGroup ? '<label class="omo-stats-picker__field"><span>' + escapeHtml(texts.groupName) + '</span><input type="text" class="generic-form-control" data-omo-stats-picker-name></label>' : '')
            + '<label class="omo-stats-picker__field"><span>' + escapeHtml(texts.search) + '</span><input type="search" class="generic-form-control" data-omo-stats-picker-search placeholder="' + escapeHtml(texts.searchPlaceholder) + '"></label>'
            + '<label class="omo-stats-picker__field"><span>' + escapeHtml(texts.visible) + '</span><select class="generic-form-control omo-stats-picker__select" data-omo-stats-picker-select size="10"' + multiple + '></select></label>'
            + (isGroup ? '<label class="omo-stats-picker__field"><span>' + escapeHtml(texts.groupMode) + '</span><select class="generic-form-control" data-omo-stats-picker-mode><option value="overlay">' + escapeHtml(texts.overlay) + '</option><option value="sum">' + escapeHtml(texts.sum) + '</option></select></label>' : '')
            + '<div class="omo-stats-picker__actions"><button type="button" class="generic-action-button generic-action-button--secondary" data-omo-stats-picker-cancel>' + escapeHtml(texts.cancel) + '</button><button type="button" class="generic-action-button generic-action-button--main" data-omo-stats-picker-apply>' + escapeHtml(isEditing ? texts.update : (isGroup ? texts.createGroup : texts.add)) + '</button></div>'
            + '</div>';
        window.commonTopbarOpenModal(title, html, 'html');
        var modalBody = document.getElementById('commonTopbarModalBody');
        if (!modalBody) {
            return;
        }
        var items = getPickerItems();
        var searchInput = modalBody.querySelector('[data-omo-stats-picker-search]');
        var select = modalBody.querySelector('[data-omo-stats-picker-select]');
        var nameInput = modalBody.querySelector('[data-omo-stats-picker-name]');
        var modeInput = modalBody.querySelector('[data-omo-stats-picker-mode]');
        var applyButton = modalBody.querySelector('[data-omo-stats-picker-apply]');
        if (nameInput && isEditing) {
            nameInput.value = String(editData.name || '');
        }
        if (modeInput && isEditing) {
            modeInput.value = String(editData.displayMode || 'overlay');
        }
        function renderOptions() {
            if (!select) {
                return;
            }
            if (!firstRender) {
                selectedIds = Array.prototype.map.call(select.selectedOptions, function (option) {
                    return option.value;
                });
            }
            var query = String(searchInput ? searchInput.value : '').trim().toLocaleLowerCase();
            select.innerHTML = '';
            items.forEach(function (item) {
                var haystack = [item.name, item.context, item.description].join(' ').toLocaleLowerCase();
                if (query && haystack.indexOf(query) === -1) {
                    return;
                }
                var option = document.createElement('option');
                option.value = String(item.id || '');
                option.textContent = String(item.name || '') + (item.context ? ' - ' + String(item.context) : '');
                option.selected = selectedIds.indexOf(option.value) !== -1;
                select.appendChild(option);
            });
            firstRender = false;
        }
        renderOptions();
        if (searchInput) {
            searchInput.focus();
            searchInput.addEventListener('input', renderOptions);
        }
        var cancelButton = modalBody.querySelector('[data-omo-stats-picker-cancel]');
        if (cancelButton) {
            cancelButton.addEventListener('click', function () {
                if (typeof window.commonTopbarCloseModal === 'function') {
                    window.commonTopbarCloseModal();
                }
            });
        }
        if (applyButton) {
            applyButton.addEventListener('click', function () {
                var selectedIds = Array.prototype.map.call(select ? select.selectedOptions : [], function (option) {
                    return option.value;
                }).filter(Boolean);
                var formData = new FormData();
                formData.append('stats_action', isEditing ? (isGroup ? 'update_group' : 'update_import') : (isGroup ? 'create_group' : 'import_indicator'));
                formData.append('oid', root.getAttribute('data-omo-stats-oid') || '');
                formData.append('cid', root.getAttribute('data-omo-stats-cid') || '');
                if (isGroup) {
                    if (isEditing) {
                        formData.append('group_id', String(editData.id || ''));
                    }
                    formData.append('name', nameInput ? nameInput.value : '');
                    formData.append('display_mode', modeInput ? modeInput.value : 'overlay');
                    selectedIds.forEach(function (id) {
                        formData.append('indicator_ids[]', id);
                    });
                } else {
                    if (isEditing) {
                        formData.append('import_id', String(editData.id || ''));
                    }
                    formData.append('indicator_id', selectedIds[0] || '');
                }
                applyButton.disabled = true;
                postFormData(formData).then(function () {
                    if (typeof window.commonTopbarCloseModal === 'function') {
                        window.commonTopbarCloseModal();
                    }
                    return refreshRoot(currentUrl);
                }).catch(function (error) {
                    window.alert(error.message || texts.loadError);
                    applyButton.disabled = false;
                });
            });
        }
    }

    Array.prototype.forEach.call(root.querySelectorAll('[data-omo-stats-scope]'), function (button) {
        button.addEventListener('click', function () {
            var nextScope = normalizeScope(button.getAttribute('data-omo-stats-scope') || '');
            if (nextScope !== currentScope) {
                currentScope = nextScope;
                refreshRoot(buildScopeUrl(nextScope));
            }
        });
    });

    Array.prototype.forEach.call(root.querySelectorAll('[data-omo-stats-sort]'), function (button) {
        button.addEventListener('click', function () {
            var nextSort = button.getAttribute('data-omo-stats-sort') === 'alpha' ? 'alpha' : 'temporal';
            var nextUrl;
            try {
                nextUrl = new URL(currentUrl, window.location.origin);
                nextUrl.searchParams.set('stats_sort', nextSort);
                nextUrl = nextUrl.pathname + nextUrl.search;
            } catch (error) {
                nextUrl = currentUrl;
            }
            if (nextUrl !== currentUrl) {
                refreshRoot(nextUrl);
            }
        });
    });

    Array.prototype.forEach.call(root.querySelectorAll('[data-omo-stats-view]'), function (button) {
        button.addEventListener('click', function () {
            applyView(button.getAttribute('data-omo-stats-view') || 'cards');
        });
    });

    Array.prototype.forEach.call(root.querySelectorAll('[data-omo-stats-indicator-id]'), function (item) {
        function activate(event) {
            if (event.target.closest('[data-omo-stats-item-menu], [data-omo-stats-open-editor-url]')) {
                return;
            }
            if (event.type === 'keydown' && event.key !== 'Enter' && event.key !== ' ') {
                return;
            }
            event.preventDefault();
            openIndicator(item.getAttribute('data-omo-stats-indicator-id'));
        }
        item.addEventListener('click', activate);
        item.addEventListener('keydown', activate);
    });

    Array.prototype.forEach.call(root.querySelectorAll('[data-omo-stats-group-id]'), function (item) {
        function activate(event) {
            if (event.target.closest('[data-omo-stats-item-menu], [data-omo-stats-edit-group], [data-omo-stats-delete-group]')) {
                return;
            }
            if (event.type === 'keydown' && event.key !== 'Enter' && event.key !== ' ') {
                return;
            }
            event.preventDefault();
            openGroup(item.getAttribute('data-omo-stats-group-id'));
        }
        item.addEventListener('click', activate);
        item.addEventListener('keydown', activate);
    });

    root.addEventListener('click', function (event) {
        var toggle = event.target.closest('[data-omo-stats-item-menu-toggle]');
        if (toggle) {
            event.preventDefault();
            event.stopPropagation();
            var menu = toggle.closest('[data-omo-stats-item-menu]');
            var panel = menu ? menu.querySelector('[data-omo-stats-item-menu-panel]') : null;
            if (panel) {
                var isOpen = !panel.hidden;
                root.querySelectorAll('[data-omo-stats-item-menu-panel]').forEach(function (candidate) {
                    candidate.hidden = true;
                });
                root.querySelectorAll('[data-omo-stats-item-menu-toggle]').forEach(function (candidate) {
                    candidate.setAttribute('aria-expanded', 'false');
                });
                panel.hidden = isOpen;
                toggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
            }
            return;
        }

        var editorLink = event.target.closest('[data-omo-stats-open-editor-url]');
        if (editorLink) {
            event.preventDefault();
            event.stopPropagation();
            openDrawerWithUrl(editorLink.getAttribute('data-omo-stats-open-editor-url') || '');
            return;
        }

        var editImportButton = event.target.closest('[data-omo-stats-edit-import]');
        if (editImportButton) {
            event.preventDefault();
            event.stopPropagation();
            openContextPicker('import', {
                id: editImportButton.getAttribute('data-omo-stats-edit-import') || '',
                indicatorIds: [editImportButton.getAttribute('data-omo-stats-indicator-id') || '']
            });
            return;
        }

        var editGroupButton = event.target.closest('[data-omo-stats-edit-group]');
        if (editGroupButton) {
            event.preventDefault();
            event.stopPropagation();
            var groupIndicatorIds = [];
            try {
                groupIndicatorIds = JSON.parse(editGroupButton.getAttribute('data-omo-stats-group-indicators') || '[]');
            } catch (error) {
                groupIndicatorIds = [];
            }
            openContextPicker('group', {
                id: editGroupButton.getAttribute('data-omo-stats-edit-group') || '',
                name: editGroupButton.getAttribute('data-omo-stats-group-name') || '',
                displayMode: editGroupButton.getAttribute('data-omo-stats-group-mode') || 'overlay',
                indicatorIds: Array.isArray(groupIndicatorIds) ? groupIndicatorIds : []
            });
            return;
        }

        var deleteImportButton = event.target.closest('[data-omo-stats-delete-import]');
        if (deleteImportButton) {
            event.preventDefault();
            event.stopPropagation();
            if (!window.confirm(texts.confirmDeleteImport)) {
                return;
            }
            var deleteImportData = new FormData();
            deleteImportData.append('stats_action', 'delete_import');
            deleteImportData.append('import_id', deleteImportButton.getAttribute('data-omo-stats-delete-import') || '');
            deleteImportData.append('oid', root.getAttribute('data-omo-stats-oid') || '');
            deleteImportButton.disabled = true;
            postFormData(deleteImportData).then(function () {
                return refreshRoot(currentUrl);
            }).catch(function (error) {
                deleteImportButton.disabled = false;
                window.alert(error.message || texts.loadError);
            });
            return;
        }

        var deleteGroupButton = event.target.closest('[data-omo-stats-delete-group]');
        if (deleteGroupButton) {
            event.preventDefault();
            event.stopPropagation();
            if (!window.confirm(texts.confirmDeleteGroup)) {
                return;
            }
            var deleteGroupData = new FormData();
            deleteGroupData.append('stats_action', 'delete_group');
            deleteGroupData.append('group_id', deleteGroupButton.getAttribute('data-omo-stats-delete-group') || '');
            deleteGroupData.append('oid', root.getAttribute('data-omo-stats-oid') || '');
            deleteGroupButton.disabled = true;
            postFormData(deleteGroupData).then(function () {
                return refreshRoot(currentUrl);
            }).catch(function (error) {
                deleteGroupButton.disabled = false;
                window.alert(error.message || texts.loadError);
            });
            return;
        }

        var deleteButton = event.target.closest('[data-omo-stats-delete-indicator]');
        if (!deleteButton) {
            return;
        }
        event.preventDefault();
        event.stopPropagation();
        if (!window.confirm(texts.confirmDeleteIndicator)) {
            return;
        }
        var formData = new FormData();
        formData.append('stats_action', 'delete_indicator');
        formData.append('indicator_id', deleteButton.getAttribute('data-omo-stats-delete-indicator') || '');
        formData.append('oid', root.getAttribute('data-omo-stats-oid') || '');
        deleteButton.disabled = true;
        postFormData(formData).then(function () {
            closeDrawer({force: true});
            return refreshRoot(currentUrl);
        }).catch(function (error) {
            deleteButton.disabled = false;
            window.alert(error.message || texts.loadError);
        });
    });

    document.addEventListener('click', function (event) {
        if (event.target.closest('[data-omo-stats-item-menu]')) {
            return;
        }
        root.querySelectorAll('[data-omo-stats-item-menu-panel]').forEach(function (panel) {
            panel.hidden = true;
        });
        root.querySelectorAll('[data-omo-stats-item-menu-toggle]').forEach(function (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
        });
    });

    var createButton = root.querySelector('[data-omo-stats-open-create]');
    if (createButton) {
        createButton.addEventListener('click', function () {
            openDrawerWithUrl(createUrl);
        });
    }

    var moreMenu = root.querySelector('[data-omo-stats-more-menu]');
    var moreToggle = root.querySelector('[data-omo-stats-more-toggle]');
    var morePanel = root.querySelector('[data-omo-stats-more-panel]');
    if (moreToggle && morePanel) {
        moreToggle.addEventListener('click', function (event) {
            event.preventDefault();
            var isOpen = !morePanel.hidden;
            morePanel.hidden = isOpen;
            moreToggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
        });
        document.addEventListener('click', function (event) {
            if (moreMenu && !moreMenu.contains(event.target)) {
                morePanel.hidden = true;
                moreToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }
    Array.prototype.forEach.call(root.querySelectorAll('[data-omo-stats-open-import], [data-omo-stats-open-group]'), function (button) {
        button.addEventListener('click', function () {
            if (morePanel) {
                morePanel.hidden = true;
            }
            if (moreToggle) {
                moreToggle.setAttribute('aria-expanded', 'false');
            }
            openContextPicker(button.hasAttribute('data-omo-stats-open-group') ? 'group' : 'import');
        });
    });

    Array.prototype.forEach.call(root.querySelectorAll('[data-omo-stats-drawer-close]'), function (button) {
        button.addEventListener('click', closeDrawer);
    });

    if (drawer) {
        drawer.addEventListener('click', function (event) {
            var editButton = event.target.closest('[data-omo-stats-open-editor-url]');
            if (editButton) {
                event.preventDefault();
                openDrawerWithUrl(editButton.getAttribute('data-omo-stats-open-editor-url') || '');
                return;
            }

            var cancelButton = event.target.closest('[data-omo-stats-cancel-editor]');
            if (cancelButton) {
                event.preventDefault();
                var indicatorId = Number(cancelButton.getAttribute('data-indicator-id') || 0);
                if (indicatorId > 0) {
                    openDrawerWithUrl(buildDetailUrl(indicatorId));
                } else {
                    closeDrawer({force: true});
                }
                return;
            }

            var deleteButton = event.target.closest('[data-omo-stats-delete-value]');
            if (!deleteButton) {
                return;
            }
            event.preventDefault();
            if (!window.confirm(texts.confirmDelete)) {
                return;
            }
            var detail = deleteButton.closest('[data-omo-stats-detail]');
            var formData = new FormData();
            formData.append('stats_action', 'delete_value');
            formData.append('value_id', deleteButton.getAttribute('data-omo-stats-delete-value') || '');
            formData.append('oid', root.getAttribute('data-omo-stats-oid') || '');
            deleteButton.disabled = true;
            postFormData(formData).then(function () {
                listNeedsRefresh = true;
                return openDrawerWithUrl(detail ? detail.getAttribute('data-detail-url') : '');
            }).catch(function (error) {
                deleteButton.disabled = false;
                window.alert(error.message || texts.loadError);
            });
        });

    }

    if (drawerBody) {
        drawerBody.addEventListener('submit', function (event) {
            var form = event.target.closest('[data-omo-stats-add-value-form]');
            if (!form) {
                return;
            }
            event.preventDefault();
            var submitButton = form.querySelector('button[type="submit"]');
            var feedback = form.querySelector('[data-omo-stats-value-feedback]');
            if (submitButton) {
                submitButton.disabled = true;
            }
            if (feedback) {
                feedback.textContent = '';
                feedback.className = 'omo-stats-feedback';
            }
            postFormData(new FormData(form)).then(function () {
                var detail = form.closest('[data-omo-stats-detail]');
                listNeedsRefresh = true;
                return openDrawerWithUrl(detail ? detail.getAttribute('data-detail-url') : '');
            }).catch(function (error) {
                if (feedback) {
                    feedback.textContent = error.message || texts.loadError;
                    feedback.className = 'omo-stats-feedback is-error';
                }
            }).finally(function () {
                if (submitButton) {
                    submitButton.disabled = false;
                }
            });
        });
    }

    window.omoStatsAfterIndicatorSave = function () {
        var editor = drawerBody ? drawerBody.querySelector('[data-omo-stats-editor]') : null;
        var indicatorId = Number(editor ? (editor.getAttribute('data-indicator-id') || 0) : 0);
        if (indicatorId > 0) {
            listNeedsRefresh = true;
            openDrawerWithUrl(buildDetailUrl(indicatorId));
            return;
        }
        listNeedsRefresh = false;
        closeDrawer({force: true});
        refreshRoot(currentUrl);
    };
    window.omoStatsRefreshCurrentView = function () {
        return refreshRoot(currentUrl);
    };

    if (!root.__omoStatsRouteHandler) {
        root.__omoStatsRouteHandler = function (routeEvent) {
            if (!document.body.contains(root)) {
                return;
            }
            var detail = routeEvent && routeEvent.detail ? routeEvent.detail : {};
            var indicatorId = Number(detail.indicatorId || 0);
            if (indicatorId > 0) {
                openDrawerWithUrl(buildDetailUrl(indicatorId));
            } else {
                closeDrawer({force: true});
            }
        };
        window.addEventListener('omo-stats-route-change', root.__omoStatsRouteHandler);
    }

    var preferredView = 'cards';
    try {
        preferredView = window.localStorage.getItem(storageKey) || 'cards';
    } catch (error) {
    }
    applyView(preferredView);

    if (Number.isInteger(initialIndicatorId) && initialIndicatorId > 0) {
        window.setTimeout(function () {
            openDrawerWithUrl(buildDetailUrl(initialIndicatorId));
        }, 40);
    }
})();
</script>
