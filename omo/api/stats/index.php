<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';
require_once dirname(__DIR__, 3) . '/common/ethercalc.php';

use dbObject\ArrayDocument;
use dbObject\ArrayStatIndicator;
use dbObject\ArrayStatIndicatorGroup;
use dbObject\ArrayStatIndicatorImport;
use dbObject\Holon;
use dbObject\Document;
use dbObject\StatIndicator;
use dbObject\StatIndicatorGroup;
use dbObject\StatIndicatorReferencePoint;
use dbObject\StatIndicatorValue;

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$currentHolonId = isset($_GET['cid']) && is_numeric($_GET['cid']) ? (int)$_GET['cid'] : 0;
$openIndicatorId = isset($_GET['open_indicator_id']) && is_numeric($_GET['open_indicator_id']) ? (int)$_GET['open_indicator_id'] : 0;
$openGroupId = isset($_GET['open_group_id']) && is_numeric($_GET['open_group_id']) ? (int)$_GET['open_group_id'] : 0;
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
    'children' => omoStatsT('stats.scope.children'),
    'descendants' => omoStatsT('stats.scope.descendants'),
];
$scopeHolonIds = $statsScope === 'children' && $currentHolon instanceof Holon
    ? omoApiGetDirectChildScopeHolonIds($currentHolon)
    : ($statsScope === 'descendants' && $currentHolon instanceof Holon
        ? omoApiGetDescendantHolonIds($currentHolon)
        : []);
$indicators = new ArrayStatIndicator();
$indicators->loadForContext(
    $organizationId,
    $currentHolon instanceof Holon ? (int)$currentHolon->getId() : 0,
    $statsScope,
    $scopeHolonIds
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
    $scopeHolonIds
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
    $scopeHolonIds
);
$groupItems = omoStatsCollectionItems($groups, StatIndicatorGroup::class);
$canManage = omoStatsCanManageContext($context);
$canCreateIndicator = omoStatsCanCreateContext($context);
$emptyKey = $statsScope === 'children'
    ? 'stats.empty.children'
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
    $overdueInfo = omoStatsGetIndicatorOverdueInfo($indicator);
    $indicatorViewData[] = [
        'indicator' => $indicator,
        'values' => $values,
        'referencePoints' => $referencePoints,
        'latestValue' => $latestValue,
        'referencePercentage' => omoStatsGetIndicatorReferencePercentage($indicator, $latestValue, $referencePoints),
        'contextLabel' => $importedIndicatorLabels[(int)$indicator->getId()] ?? omoStatsContextLabel($indicator),
        'isImported' => isset($importedIndicatorLabels[(int)$indicator->getId()]),
        'importId' => $importedIndicatorIds[(int)$indicator->getId()] ?? 0,
        'canEditImport' => $importedIndicatorEditable[(int)$indicator->getId()] ?? false,
        'isOverdue' => (bool)$overdueInfo['is_overdue'],
        'overdueSeverity' => (string)$overdueInfo['severity'],
        'overdueDays' => (int)$overdueInfo['overdue_days'],
    ];
}
$groupViewData = [];
foreach ($groupItems as $group) {
    if (!$group->canView()) {
        continue;
    }
    $series = omoStatsGetGroupSeries($group);
    $latestSumValue = omoStatsGetGroupLatestSumValue($group, $series);
    $groupReferencePoints = omoStatsGetGroupReferencePoints($group);
    $groupReferencePointData = array_map(static function ($point) {
        $pointAt = $point->get('point_at');
        return [
            'position_percent' => (float)$point->get('position_percent'),
            'point_at' => $pointAt instanceof DateTimeInterface ? $pointAt->format('Y-m-d\\TH:i') : '',
            'value' => (float)$point->get('value'),
        ];
    }, $groupReferencePoints);
    $groupReferenceType = StatIndicator::normalizeReferenceType($group->get('reference_type'));
    $groupViewData[] = [
        'group' => $group,
        'series' => $series,
        'latestSumValue' => $latestSumValue,
        'memberCount' => count(omoStatsCollectionItems($group->getItems(), \dbObject\StatIndicatorGroupItem::class)),
        'indicatorIds' => array_values(array_map(static function ($item) {
            return $item instanceof \dbObject\StatIndicatorGroupItem ? (int)$item->get('IDstatindicator') : 0;
        }, omoStatsCollectionItems($group->getItems(), \dbObject\StatIndicatorGroupItem::class))),
        'referenceType' => $groupReferenceType,
        'referencePoints' => array_values($groupReferencePointData),
        'ceilingValue' => $groupReferenceType === StatIndicator::REFERENCE_CEILING
            ? omoStatsGetCeilingValue($groupReferencePoints)
            : null,
        'chartMinValue' => is_numeric($group->get('chart_min_value')) ? (float)$group->get('chart_min_value') : null,
        'canEdit' => omoStatsCanEditContextResource($group, $context),
        'overdueSeverity' => omoStatsGetGroupOverdueInfo($group)['severity'],
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
$pickerIndicators->loadForOrganization($organizationId);
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
$ethercalcPickerData = [];
$ethercalcPickerAvailable = omoEthercalcHasConfig();
if ($ethercalcPickerAvailable) {
    $pickerDocuments = new ArrayDocument();
    $pickerDocuments->load([
        'where' => [
            ['field' => 'IDorganization', 'value' => $organizationId],
            ['field' => 'active', 'value' => 1],
        ],
        'orderBy' => [
            ['field' => 'title', 'dir' => 'ASC'],
            ['field' => 'id', 'dir' => 'ASC'],
        ],
    ]);
    $pickerDocuments->filterVisibleForCurrentViewer($organizationId);
    foreach ($pickerDocuments as $document) {
        if (!($document instanceof Document) || !$document->isEthercalcDocument() || $document->getEthercalcRoomId() === '') {
            continue;
        }

        $ethercalcPickerData[] = [
            'id' => (int)$document->getId(),
            'name' => trim((string)$document->get('title')),
            'description' => trim((string)$document->get('description')),
        ];
    }
}
$displayItemCount = count($statsEntries);
?>
<link rel="stylesheet" href="/common/view-filter/view-filter.css?v=20260801-view-preferences-actions-height">
<link rel="stylesheet" href="/omo/api/stats/stats.css?v=20260807-range-handles">
<div
    class="omo-stats omo-panel-view"
    id="omo-stats-root"
    data-omo-stats-oid="<?= (int)$organizationId ?>"
    data-omo-stats-cid="<?= $currentHolon instanceof Holon ? (int)$currentHolon->getId() : 0 ?>"
    data-omo-stats-route-cid="<?= (int)$currentHolonId ?>"
    data-omo-stats-root-hid="<?= $rootHolon instanceof Holon ? (int)$rootHolon->getId() : 0 ?>"
    data-omo-stats-current-scope="<?= omoApiEscape($statsScope) ?>"
    data-omo-stats-current-sort="<?= omoApiEscape($statsSort) ?>"
    data-omo-stats-current-view="cards"
    data-omo-view-filter-pending="1"
    aria-busy="true"
    data-omo-stats-current-url="<?= omoApiEscape($currentUrl) ?>"
    data-omo-stats-create-url="<?= omoApiEscape($createUrl) ?>"
    data-omo-stats-detail-url="<?= omoApiEscape($detailBaseUrl) ?>"
    data-omo-stats-group-detail-url="<?= omoApiEscape($groupDetailBaseUrl) ?>"
    data-omo-stats-open-indicator-id="<?= (int)$openIndicatorId ?>"
    data-omo-stats-open-group-id="<?= (int)$openGroupId ?>"
    data-omo-stats-picker="<?= omoApiEscape(json_encode($pickerData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
    data-omo-stats-ethercalc-picker="<?= omoApiEscape(json_encode($ethercalcPickerData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
    data-omo-stats-ethercalc-available="<?= $ethercalcPickerAvailable ? '1' : '0' ?>"
>
    <header class="omo-stats__header omo-panel-view__header omo-panel-view__header--stacked">
        <div class="omo-panel-view__header-main">
            <div class="omo-panel-view__title-cluster">
                <span class="omo-panel-view__app-icon omo-stats__app-icon" aria-hidden="true">
                    <img src="images/tools/stats.png" alt="">
                </span>
                <div class="omo-panel-view__header-copy">
                    <div class="omo-stats__title-row generic-title-row generic-title-row--center">
                        <h2 class="omo-panel-view__title"><?= omoApiEscape(omoStatsT('stats.title')) ?></h2>
                        <span class="omo-panel-view__count" data-omo-stats-header-count><?= $displayItemCount ?></span>
                    </div>
                </div>
            </div>
            <?php if ($canCreateIndicator || $canManage): ?>
                <div class="omo-stats__header-actions" data-omo-header-actions>
                    <?php if ($canCreateIndicator): ?>
                        <button type="button" class="generic-action-button generic-action-button--main omo-mobile-corner-action" data-omo-stats-open-create><?= omoApiEscape(omoStatsT('stats.action.new')) ?></button>
                    <?php endif; ?>
                    <?php if ($canManage): ?>
                        <div class="omo-stats__more-menu generic-menu" data-omo-stats-more-menu>
                            <button type="button" class="generic-menu-toggle omo-stats__more-toggle" data-omo-stats-more-toggle aria-label="<?= omoApiEscape(omoStatsT('stats.action.more')) ?>" aria-expanded="false">...</button>
                            <div class="omo-stats__more-menu-panel generic-menu-panel generic-menu-panel--wide" data-omo-stats-more-panel hidden>
                                <button type="button" class="generic-menu-item" data-omo-stats-open-import><?= omoApiEscape(omoStatsT('stats.action.import')) ?></button>
                                <button type="button" class="generic-menu-item" data-omo-stats-open-group><?= omoApiEscape(omoStatsT('stats.action.group')) ?></button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="omo-panel-view__header-secondary">
            <div class="omo-stats__filter-toolbar omo-view-filter" data-omo-stats-filter-control role="group" aria-label="<?= omoApiEscape(omoStatsT('stats.filters.aria')) ?>">
                <div class="omo-view-filter__input">
                    <div class="omo-view-filter__chips">
                        <button type="button" class="omo-view-filter__chip" data-omo-stats-filter-toggle data-omo-stats-scope-chip aria-expanded="false" aria-controls="omo-stats-filter-panel"><?= omoApiEscape((string)($scopeLabels[$statsScope] ?? $statsScope)) ?></button>
                        <button type="button" class="omo-view-filter__chip" data-omo-stats-filter-toggle data-omo-stats-sort-chip aria-expanded="false" aria-controls="omo-stats-filter-panel"><?= omoApiEscape(omoStatsT('stats.controls.sort.' . $statsSort)) ?></button>
                        <button type="button" class="omo-view-filter__chip" data-omo-stats-filter-toggle data-omo-stats-view-chip aria-expanded="false" aria-controls="omo-stats-filter-panel"><?= omoApiEscape(omoStatsT('stats.view.cards')) ?></button>
                    </div>
                    <label class="omo-view-filter__search">
                        <input type="search" class="generic-form-control" data-omo-stats-quick-search placeholder="<?= omoApiEscape(omoStatsT('stats.search.placeholder')) ?>" aria-label="<?= omoApiEscape(omoStatsT('stats.search.aria')) ?>" autocomplete="off">
                    </label>
                </div>
                <section id="omo-stats-filter-panel" class="omo-view-filter__panel generic-soft-panel generic-soft-panel--stack" data-omo-stats-filter-panel hidden>
                    <div class="omo-view-filter__panel-grid">
                        <div class="omo-view-filter__group">
                            <span class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoStatsT('stats.filters.scope')) ?></span>
                            <div class="omo-segmented" role="group" aria-label="<?= omoApiEscape(omoStatsT('stats.filters.scope')) ?>">
                                <?php foreach ($availableScopes as $scopeKey): ?>
                                    <button type="button" class="omo-segmented__button<?= $statsScope === $scopeKey ? ' is-active' : '' ?>" data-omo-stats-scope="<?= omoApiEscape($scopeKey) ?>" aria-pressed="<?= $statsScope === $scopeKey ? 'true' : 'false' ?>"><?= omoApiEscape((string)($scopeLabels[$scopeKey] ?? $scopeKey)) ?></button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="omo-view-filter__group">
                            <span class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoStatsT('stats.filters.sort')) ?></span>
                            <div class="omo-segmented" role="group" aria-label="<?= omoApiEscape(omoStatsT('stats.controls.sort.aria')) ?>">
                                <button type="button" class="omo-segmented__button<?= $statsSort === 'temporal' ? ' is-active' : '' ?>" data-omo-stats-sort="temporal" aria-pressed="<?= $statsSort === 'temporal' ? 'true' : 'false' ?>"><?= omoApiEscape(omoStatsT('stats.controls.sort.temporal')) ?></button>
                                <button type="button" class="omo-segmented__button<?= $statsSort === 'alpha' ? ' is-active' : '' ?>" data-omo-stats-sort="alpha" aria-pressed="<?= $statsSort === 'alpha' ? 'true' : 'false' ?>"><?= omoApiEscape(omoStatsT('stats.controls.sort.alpha')) ?></button>
                            </div>
                        </div>
                        <div class="omo-view-filter__group">
                            <span class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoStatsT('stats.filters.view')) ?></span>
                            <div class="omo-segmented" role="group" aria-label="<?= omoApiEscape(omoStatsT('stats.filters.view')) ?>">
                                <button type="button" class="omo-segmented__button is-active" data-omo-stats-view="cards" aria-pressed="true"><?= omoApiEscape(omoStatsT('stats.view.cards')) ?></button>
                                <button type="button" class="omo-segmented__button" data-omo-stats-view="compact" aria-pressed="false"><?= omoApiEscape(omoStatsT('stats.view.compact')) ?></button>
                            </div>
                        </div>
                    </div>
                    <div class="omo-view-filter__actions">
                        <button type="button" class="generic-action-button generic-action-button--main" data-omo-stats-filter-apply><?= omoApiEscape(omoStatsT('stats.filters.apply')) ?></button>
                        <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-stats-filter-save><?= omoApiEscape(omoStatsT('stats.filters.save_view')) ?></button>
                        <div class="generic-menu omo-view-filter__actions-more" data-omo-stats-filter-more-menu>
                            <button type="button" class="generic-menu-toggle" data-omo-stats-filter-more-toggle aria-expanded="false" aria-label="<?= omoApiEscape(omoStatsT('stats.filters.more_actions')) ?>">&#8942;</button>
                            <div class="generic-menu-panel" data-omo-stats-filter-more-panel role="menu" hidden>
                                <button type="button" class="generic-menu-item" data-omo-stats-filter-more-action="apply-everywhere" role="menuitem"><?= omoApiEscape(omoStatsT('stats.filters.apply_everywhere')) ?></button>
                                <button type="button" class="generic-menu-item" data-omo-stats-filter-more-action="set-default" role="menuitem"><?= omoApiEscape(omoStatsT('stats.filters.set_default')) ?></button>
                                <button type="button" class="generic-menu-item" data-omo-stats-filter-more-action="restore-default" role="menuitem"><?= omoApiEscape(omoStatsT('stats.filters.restore_default')) ?></button>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </header>

    <div class="omo-panel-view__body">
        <div class="omo-panel-view__body_content omo-stats__body">
            <section data-omo-stats-view-panel="cards">
                <?php if ($displayItemCount === 0): ?>
                    <section class="generic-hero-panel accent generic-empty-hero" data-omo-stats-default-empty>
                        <h3 class="generic-empty-hero__title"><?= omoApiEscape(omoStatsT('stats.empty.title')) ?></h3>
                        <p class="generic-empty-hero__text"><?= omoApiEscape(omoStatsT($emptyKey)) ?></p>
                    </section>
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
                                <section class="omo-panel-group generic-file-list__group omo-stats__sort-group" data-omo-stats-search-group>
                                    <h3 class="omo-panel-group__title generic-file-list__group-title"><?= omoApiEscape($currentStatsCategory) ?></h3>
                                    <div class="omo-stats-grid omo-stats__sort-group-items">
                            <?php endif; ?>
                            <?php if ($statsEntry['kind'] === 'group'): ?>
                            <?php $groupItem = $statsEntry['data']; $group = $groupItem['group']; $latestSumValue = $groupItem['latestSumValue']; $groupOverdueSeverity = $groupItem['overdueSeverity']; ?>
                            <article
                                class="generic-section omo-stats-card omo-stats-card--group<?= $groupOverdueSeverity === 'error' ? ' omo-stats-card--overdue' : ($groupOverdueSeverity === 'warning' ? ' omo-stats-card--warning' : '') ?>"
                                data-omo-stats-group-id="<?= (int)$group->getId() ?>"
                                data-omo-stats-search-item
                                tabindex="0"
                                role="button"
                                aria-label="<?= omoApiEscape(omoStatsT('stats.card.open', ['name' => (string)$group->get('name')])) ?>"
                            >
                                <div class="omo-stats-card__header">
                                    <div>
                                        <span class="generic-card-title generic-card-title--eyebrow"><?= omoApiEscape(omoStatsT('stats.card.group')) ?></span>
                                        <h3 class="generic-card-title generic-card-title--big"><?= omoApiEscape((string)$group->get('name')) ?></h3>
                                    </div>
                                    <span class="omo-stats-card__value-count<?= $groupItem['canEdit'] ? ' omo-stats-card__value-count--with-menu' : '' ?>"><?= omoApiEscape(omoStatsT('stats.card.member_count', ['count' => $groupItem['memberCount']])) ?></span>
                                    <?php if ($groupItem['canEdit']): ?>
                                        <div class="omo-stats-item-menu generic-menu" data-omo-stats-item-menu>
                                            <button type="button" class="omo-stats-item-menu__toggle generic-menu-toggle" data-omo-stats-item-menu-toggle aria-label="<?= omoApiEscape(omoStatsT('stats.action.more')) ?>" aria-expanded="false">...</button>
                                            <div class="omo-stats-item-menu__panel generic-menu-panel generic-menu-panel--wide" data-omo-stats-item-menu-panel hidden>
                                                <button type="button" class="generic-menu-item" data-omo-stats-open-editor-url="<?= omoApiEscape($groupDetailBaseUrl . '&id=' . rawurlencode((string)$group->getId())) ?>"><?= omoApiEscape(omoStatsT('stats.action.detail')) ?></button>
                                                <button type="button" class="generic-menu-item" data-omo-stats-edit-group="<?= (int)$group->getId() ?>" data-omo-stats-group-name="<?= omoApiEscape((string)$group->get('name')) ?>" data-omo-stats-group-mode="<?= omoApiEscape((string)$group->get('display_mode')) ?>" data-omo-stats-group-indicators="<?= omoApiEscape(json_encode($groupItem['indicatorIds'])) ?>" data-omo-stats-group-reference-type="<?= omoApiEscape($groupItem['referenceType']) ?>" data-omo-stats-group-reference-points="<?= omoApiEscape(json_encode($groupItem['referencePoints'])) ?>" data-omo-stats-group-ceiling-value="<?= omoApiEscape((string)($groupItem['ceilingValue'] ?? '')) ?>" data-omo-stats-group-chart-min-value="<?= omoApiEscape((string)($groupItem['chartMinValue'] ?? '')) ?>"><?= omoApiEscape(omoStatsT('stats.action.edit_group')) ?></button>
                                                <button type="button" class="generic-menu-item generic-menu-item--danger" data-omo-stats-delete-group="<?= (int)$group->getId() ?>"><?= omoApiEscape(omoStatsT('stats.action.delete_group')) ?></button>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="omo-stats-card__chart"><?= omoStatsRenderGroupChart($group, $groupItem['series'], 'card', $groupOverdueSeverity) ?></div>
                                <div class="omo-stats-card__footer">
                                    <?php if (is_array($latestSumValue)): ?>
                                        <span><?= omoApiEscape(omoStatsT('stats.card.latest')) ?></span>
                                        <span class="omo-stats-card__latest-value">
                                            <strong><?= omoApiEscape(omoStatsFormatNumber($latestSumValue['value'])) ?></strong>
                                            <?php if (is_numeric($latestSumValue['referencePercentage'] ?? null)): ?>
                                                <span class="omo-stats-reference-percentage">(<?= omoApiEscape(omoStatsFormatNumber($latestSumValue['referencePercentage'])) ?>%)</span>
                                            <?php endif; ?>
                                        </span>
                                        <time><?= omoApiEscape(date('d.m.Y', (int)$latestSumValue['timestamp'])) ?></time>
                                    <?php else: ?>
                                        <span><?= omoApiEscape(StatIndicatorGroup::normalizeDisplayMode($group->get('display_mode')) === StatIndicatorGroup::DISPLAY_SUM ? omoStatsT('stats.group.mode.sum') : omoStatsT('stats.group.mode.overlay')) ?></span>
                                    <?php endif; ?>
                                </div>
                            </article>
                            <?php else: ?>
                            <?php $item = $statsEntry['data']; ?>
                            <?php
                            $indicator = $item['indicator'];
                            $latestValue = $item['latestValue'];
                            $referencePercentage = $item['referencePercentage'];
                            $overdueSeverity = $item['overdueSeverity'];
                            $overdueDays = $item['overdueDays'];
                            $indicatorName = trim((string)$indicator->get('name'));
                            ?>
                            <article
                                class="generic-section omo-stats-card<?= $overdueSeverity === 'error' ? ' omo-stats-card--overdue' : ($overdueSeverity === 'warning' ? ' omo-stats-card--warning' : '') ?>"
                                data-omo-stats-indicator-id="<?= (int)$indicator->getId() ?>"
                                data-omo-stats-search-item
                                tabindex="0"
                                role="button"
                                aria-label="<?= omoApiEscape(omoStatsT('stats.card.open', ['name' => $indicatorName])) ?>"
                            >
                                <div class="omo-stats-card__header">
                                    <div>
                                        <span class="generic-card-title generic-card-title--eyebrow"><?= omoApiEscape((string)$item['contextLabel']) ?></span>
                                        <h3 class="generic-card-title generic-card-title--big"><?= omoApiEscape($indicatorName) ?></h3>
                                    </div>
                                    <span class="omo-stats-card__value-count<?= $indicator->canEdit() ? ' omo-stats-card__value-count--with-menu' : '' ?>"><?= omoApiEscape(omoStatsT('stats.card.value_count', ['count' => count($item['values'])])) ?></span>
                                    <?php if ($item['isImported'] ? $item['canEditImport'] : $indicator->canEdit()): ?>
                                        <div class="omo-stats-item-menu generic-menu" data-omo-stats-item-menu>
                                            <button type="button" class="omo-stats-item-menu__toggle generic-menu-toggle" data-omo-stats-item-menu-toggle aria-label="<?= omoApiEscape(omoStatsT('stats.action.more')) ?>" aria-expanded="false">...</button>
                                            <div class="omo-stats-item-menu__panel generic-menu-panel generic-menu-panel--wide" data-omo-stats-item-menu-panel hidden>
                                                <?php if ($item['isImported']): ?>
                                                    <button type="button" class="generic-menu-item" data-omo-stats-edit-import="<?= (int)$item['importId'] ?>" data-omo-stats-indicator-id="<?= (int)$indicator->getId() ?>"><?= omoApiEscape(omoStatsT('stats.action.edit_import')) ?></button>
                                                    <button type="button" class="generic-menu-item generic-menu-item--danger" data-omo-stats-delete-import="<?= (int)$item['importId'] ?>"><?= omoApiEscape(omoStatsT('stats.action.delete_import')) ?></button>
                                                <?php else: ?>
                                                    <button type="button" class="generic-menu-item" data-omo-stats-open-editor-url="<?= omoApiEscape($detailBaseUrl . '&id=' . rawurlencode((string)$indicator->getId())) ?>"><?= omoApiEscape(omoStatsT('stats.action.detail')) ?></button>
                                                    <button type="button" class="generic-menu-item" data-omo-stats-open-editor-url="<?= omoApiEscape($createUrl . '&id=' . rawurlencode((string)$indicator->getId())) ?>"><?= omoApiEscape(omoStatsT('stats.action.edit')) ?></button>
                                                    <button type="button" class="generic-menu-item generic-menu-item--danger" data-omo-stats-delete-indicator="<?= (int)$indicator->getId() ?>"><?= omoApiEscape(omoStatsT('stats.action.delete_indicator')) ?></button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="omo-stats-card__chart">
                                    <?= omoStatsRenderChart($indicator, $item['values'], $item['referencePoints'], 'card', $overdueSeverity) ?>
                                </div>
                                <div class="omo-stats-card__footer">
                                    <span class="omo-stats-card__latest-label">
                                        <span><?= omoApiEscape(omoStatsT('stats.card.latest')) ?></span>
                                        <?php if ($overdueSeverity === 'warning'): ?>
                                            <span class="omo-stats-card__status omo-stats-card__status--warning"><?= omoApiEscape(omoStatsT('stats.card.to_complete')) ?></span>
                                        <?php elseif ($overdueSeverity === 'error' && $overdueDays > 0): ?>
                                            <span class="omo-stats-card__status omo-stats-card__status--overdue"><?= omoApiEscape(omoStatsT('stats.card.overdue_days', ['count' => $overdueDays])) ?></span>
                                        <?php endif; ?>
                                    </span>
                                    <?php if ($latestValue instanceof StatIndicatorValue): ?>
                                        <span class="omo-stats-card__latest-reading">
                                            <span class="omo-stats-card__latest-value">
                                                <strong><?= omoApiEscape(omoStatsFormatNumber($latestValue->get('value'))) ?></strong>
                                                <?php if (is_numeric($referencePercentage)): ?>
                                                    <span class="omo-stats-reference-percentage">(<?= omoApiEscape(omoStatsFormatNumber($referencePercentage)) ?>%)</span>
                                                <?php endif; ?>
                                            </span>
                                            <time><?= omoApiEscape(omoStatsFormatDateTime($latestValue->get('measured_at'), false)) ?></time>
                                        </span>
                                    <?php else: ?>
                                        <strong class="omo-stats-card__empty-value generic-meta"><?= omoApiEscape(omoStatsT('stats.card.no_value')) ?></strong>
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
                    <section class="generic-hero-panel accent generic-empty-hero" data-omo-stats-default-empty>
                        <h3 class="generic-empty-hero__title"><?= omoApiEscape(omoStatsT('stats.empty.title')) ?></h3>
                        <p class="generic-empty-hero__text"><?= omoApiEscape(omoStatsT($emptyKey)) ?></p>
                    </section>
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
                                <section class="omo-panel-group generic-file-list__group omo-stats__sort-group" data-omo-stats-search-group>
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
                            <?php $groupItem = $statsEntry['data']; $group = $groupItem['group']; $latestSumValue = $groupItem['latestSumValue']; $groupOverdueSeverity = $groupItem['overdueSeverity']; ?>
                            <article class="generic-file-list__item-shell" data-omo-stats-search-item>
                                <div
                                    class="generic-file-list__row omo-stats-compact__row omo-stats-compact__row--group<?= $groupOverdueSeverity === 'error' ? ' omo-stats-compact__row--overdue' : ($groupOverdueSeverity === 'warning' ? ' omo-stats-compact__row--warning' : '') ?>"
                                    data-omo-stats-group-id="<?= (int)$group->getId() ?>"
                                    tabindex="0"
                                    role="button"
                                    aria-label="<?= omoApiEscape(omoStatsT('stats.card.open', ['name' => (string)$group->get('name')])) ?>"
                                >
                                    <?php if ($groupItem['canEdit']): ?>
                                        <div class="omo-stats-item-menu generic-menu" data-omo-stats-item-menu>
                                            <button type="button" class="omo-stats-item-menu__toggle generic-menu-toggle" data-omo-stats-item-menu-toggle aria-label="<?= omoApiEscape(omoStatsT('stats.action.more')) ?>" aria-expanded="false">...</button>
                                            <div class="omo-stats-item-menu__panel generic-menu-panel generic-menu-panel--wide" data-omo-stats-item-menu-panel hidden>
                                                <button type="button" class="generic-menu-item" data-omo-stats-open-editor-url="<?= omoApiEscape($groupDetailBaseUrl . '&id=' . rawurlencode((string)$group->getId())) ?>"><?= omoApiEscape(omoStatsT('stats.action.detail')) ?></button>
                                                <button type="button" class="generic-menu-item" data-omo-stats-edit-group="<?= (int)$group->getId() ?>" data-omo-stats-group-name="<?= omoApiEscape((string)$group->get('name')) ?>" data-omo-stats-group-mode="<?= omoApiEscape((string)$group->get('display_mode')) ?>" data-omo-stats-group-indicators="<?= omoApiEscape(json_encode($groupItem['indicatorIds'])) ?>" data-omo-stats-group-reference-type="<?= omoApiEscape($groupItem['referenceType']) ?>" data-omo-stats-group-reference-points="<?= omoApiEscape(json_encode($groupItem['referencePoints'])) ?>" data-omo-stats-group-ceiling-value="<?= omoApiEscape((string)($groupItem['ceilingValue'] ?? '')) ?>" data-omo-stats-group-chart-min-value="<?= omoApiEscape((string)($groupItem['chartMinValue'] ?? '')) ?>"><?= omoApiEscape(omoStatsT('stats.action.edit_group')) ?></button>
                                                <button type="button" class="generic-menu-item generic-menu-item--danger" data-omo-stats-delete-group="<?= (int)$group->getId() ?>"><?= omoApiEscape(omoStatsT('stats.action.delete_group')) ?></button>
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
                                    <div class="generic-file-list__cell omo-stats-compact__latest" data-label="<?= omoApiEscape(omoStatsT('stats.column.latest')) ?>">
                                        <?php if (is_array($latestSumValue)): ?>
                                            <span class="omo-stats-compact__latest-value">
                                                <strong><?= omoApiEscape(omoStatsFormatNumber($latestSumValue['value'])) ?></strong>
                                                <?php if (is_numeric($latestSumValue['referencePercentage'] ?? null)): ?>
                                                    <span class="omo-stats-reference-percentage">(<?= omoApiEscape(omoStatsFormatNumber($latestSumValue['referencePercentage'])) ?>%)</span>
                                                <?php endif; ?>
                                            </span>
                                            <time><?= omoApiEscape(date('d.m.Y', (int)$latestSumValue['timestamp'])) ?></time>
                                        <?php else: ?>
                                            <span><?= omoApiEscape(StatIndicatorGroup::normalizeDisplayMode($group->get('display_mode')) === StatIndicatorGroup::DISPLAY_SUM ? omoStatsT('stats.group.mode.sum') : omoStatsT('stats.group.mode.overlay')) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="generic-file-list__cell omo-stats-compact__chart" data-label="<?= omoApiEscape(omoStatsT('stats.column.history')) ?>"><?= omoStatsRenderGroupChart($group, $groupItem['series'], 'compact', $groupOverdueSeverity) ?></div>
                                </div>
                            </article>
                            <?php else: ?>
                            <?php $item = $statsEntry['data']; ?>
                            <?php
                            $indicator = $item['indicator'];
                            $latestValue = $item['latestValue'];
                            $referencePercentage = $item['referencePercentage'];
                            $indicatorName = trim((string)$indicator->get('name'));
                            ?>
                            <article class="generic-file-list__item-shell" data-omo-stats-search-item>
                                <div
                                    class="generic-file-list__row omo-stats-compact__row<?= $item['overdueSeverity'] === 'error' ? ' omo-stats-compact__row--overdue' : ($item['overdueSeverity'] === 'warning' ? ' omo-stats-compact__row--warning' : '') ?>"
                                    data-omo-stats-indicator-id="<?= (int)$indicator->getId() ?>"
                                    tabindex="0"
                                    role="button"
                                    aria-label="<?= omoApiEscape(omoStatsT('stats.card.open', ['name' => $indicatorName])) ?>"
                                >
                                    <?php if ($item['isImported'] ? $item['canEditImport'] : $indicator->canEdit()): ?>
                                        <div class="omo-stats-item-menu generic-menu" data-omo-stats-item-menu>
                                            <button type="button" class="omo-stats-item-menu__toggle generic-menu-toggle" data-omo-stats-item-menu-toggle aria-label="<?= omoApiEscape(omoStatsT('stats.action.more')) ?>" aria-expanded="false">...</button>
                                            <div class="omo-stats-item-menu__panel generic-menu-panel generic-menu-panel--wide" data-omo-stats-item-menu-panel hidden>
                                                <?php if ($item['isImported']): ?>
                                                    <button type="button" class="generic-menu-item" data-omo-stats-edit-import="<?= (int)$item['importId'] ?>" data-omo-stats-indicator-id="<?= (int)$indicator->getId() ?>"><?= omoApiEscape(omoStatsT('stats.action.edit_import')) ?></button>
                                                    <button type="button" class="generic-menu-item generic-menu-item--danger" data-omo-stats-delete-import="<?= (int)$item['importId'] ?>"><?= omoApiEscape(omoStatsT('stats.action.delete_import')) ?></button>
                                                <?php else: ?>
                                                    <button type="button" class="generic-menu-item" data-omo-stats-open-editor-url="<?= omoApiEscape($detailBaseUrl . '&id=' . rawurlencode((string)$indicator->getId())) ?>"><?= omoApiEscape(omoStatsT('stats.action.detail')) ?></button>
                                                    <button type="button" class="generic-menu-item" data-omo-stats-open-editor-url="<?= omoApiEscape($createUrl . '&id=' . rawurlencode((string)$indicator->getId())) ?>"><?= omoApiEscape(omoStatsT('stats.action.edit')) ?></button>
                                                    <button type="button" class="generic-menu-item generic-menu-item--danger" data-omo-stats-delete-indicator="<?= (int)$indicator->getId() ?>"><?= omoApiEscape(omoStatsT('stats.action.delete_indicator')) ?></button>
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
                                            <span class="omo-stats-compact__latest-value">
                                                <strong><?= omoApiEscape(omoStatsFormatNumber($latestValue->get('value'))) ?></strong>
                                                <?php if (is_numeric($referencePercentage)): ?>
                                                    <span class="omo-stats-reference-percentage">(<?= omoApiEscape(omoStatsFormatNumber($referencePercentage)) ?>%)</span>
                                                <?php endif; ?>
                                            </span>
                                            <time><?= omoApiEscape(omoStatsFormatDateTime($latestValue->get('measured_at'), false)) ?></time>
                                        <?php else: ?>
                                            <span>—</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="generic-file-list__cell omo-stats-compact__chart" data-label="<?= omoApiEscape(omoStatsT('stats.column.history')) ?>">
                                        <?= omoStatsRenderChart($indicator, $item['values'], $item['referencePoints'], 'compact', $item['overdueSeverity']) ?>
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
            <div class="omo-empty-state omo-stats__search-empty" data-omo-stats-search-empty hidden><?= omoApiEscape(omoStatsT('stats.search.empty')) ?></div>
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
<script src="/omo/api/stats/reference-editor.js?v=20260724-ceiling"></script>
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
    var currentScope = root.getAttribute('data-omo-stats-current-scope') || 'contextual';
    var routeCid = Number(root.getAttribute('data-omo-stats-route-cid') || 0);
    var initialIndicatorId = Number(root.getAttribute('data-omo-stats-open-indicator-id') || 0);
    var initialGroupId = Number(root.getAttribute('data-omo-stats-open-group-id') || 0);
    var requestToken = 0;
    var listNeedsRefresh = false;
    var savedViewsStorageKey = 'omo.stats.saved-views.v2';
    var legacySavedViewsStorageKey = 'omo.stats.saved-views.v1';
    var sessionViewsStorageKey = 'omo.stats.session-views.v1';
    var searchStorageKey = 'omo.stats.quick-search.v1';
    var currentSort = root.getAttribute('data-omo-stats-current-sort') === 'alpha' ? 'alpha' : 'temporal';
    var currentView = 'cards';
    var currentSearch = '';
    var pendingFilters = null;
    var filterPanelOpen = false;
    var texts = <?= json_encode([
        'loading' => omoStatsT('stats.loading'),
        'loadError' => omoStatsT('stats.error.load'),
        'confirmDelete' => omoStatsT('stats.detail.confirm_delete'),
        'confirmDeleteIndicator' => omoStatsT('stats.detail.confirm_delete_indicator'),
        'confirmDeleteImport' => omoStatsT('stats.detail.confirm_delete_import'),
        'confirmDeleteGroup' => omoStatsT('stats.detail.confirm_delete_group'),
        'importTitle' => omoStatsT('stats.import.title'),
        'editImportTitle' => omoStatsT('stats.import.edit_title'),
        'importSourceIndicators' => omoStatsT('stats.import.source_indicators'),
        'importSourceEthercalc' => omoStatsT('stats.import.source_ethercalc'),
        'groupTitle' => omoStatsT('stats.group.title'),
        'editGroupTitle' => omoStatsT('stats.group.edit_title'),
        'search' => omoStatsT('stats.import.search'),
        'searchPlaceholder' => omoStatsT('stats.import.search_placeholder'),
        'visible' => omoStatsT('stats.import.visible'),
        'ethercalcDocument' => omoStatsT('stats.import.ethercalc.document'),
        'ethercalcNoDocuments' => omoStatsT('stats.import.ethercalc.no_documents'),
        'ethercalcMode' => omoStatsT('stats.import.ethercalc.mode'),
        'ethercalcModeCell' => omoStatsT('stats.import.ethercalc.mode_cell'),
        'ethercalcModeTable' => omoStatsT('stats.import.ethercalc.mode_table'),
        'ethercalcCell' => omoStatsT('stats.import.ethercalc.cell'),
        'ethercalcFrequency' => omoStatsT('stats.import.ethercalc.frequency'),
        'ethercalcFrequencyHourly' => omoStatsT('stats.import.ethercalc.frequency_hourly'),
        'ethercalcFrequencyDaily' => omoStatsT('stats.import.ethercalc.frequency_daily'),
        'ethercalcFrequencyWeekly' => omoStatsT('stats.import.ethercalc.frequency_weekly'),
        'ethercalcRange' => omoStatsT('stats.import.ethercalc.range'),
        'ethercalcDateColumn' => omoStatsT('stats.import.ethercalc.date_column'),
        'ethercalcValueColumns' => omoStatsT('stats.import.ethercalc.value_columns'),
        'ethercalcTableHelp' => omoStatsT('stats.import.ethercalc.table_help'),
        'ethercalcPrototypeAction' => omoStatsT('stats.import.ethercalc.prototype_action'),
        'ethercalcPrototypeNotice' => omoStatsT('stats.import.ethercalc.prototype_notice'),
        'groupName' => omoStatsT('stats.group.name'),
        'groupMode' => omoStatsT('stats.group.mode'),
        'overlay' => omoStatsT('stats.group.mode.overlay'),
        'sum' => omoStatsT('stats.group.mode.sum'),
        'cancel' => omoStatsT('stats.action.cancel'),
        'add' => omoStatsT('stats.action.add'),
        'update' => omoStatsT('stats.action.update'),
        'createGroup' => omoStatsT('stats.action.create_group'),
        'referenceTitle' => omoStatsT('stats.form.reference_title'),
        'referenceHelp' => omoStatsT('stats.form.reference_help'),
        'referenceNone' => omoStatsT('stats.form.reference_none'),
        'referenceCeiling' => omoStatsT('stats.form.reference_ceiling'),
        'referenceObjective' => omoStatsT('stats.form.reference_objective'),
        'ceilingTitle' => omoStatsT('stats.form.ceiling_title'),
        'ceilingHelp' => omoStatsT('stats.form.ceiling_help'),
        'ceilingValue' => omoStatsT('stats.form.ceiling_value'),
        'chartMinValue' => omoStatsT('stats.detail.chart_min_value'),
        'addReferencePoint' => omoStatsT('stats.form.add_point'),
        'referenceEndpoint' => omoStatsT('stats.form.endpoint'),
        'referenceIntermediate' => omoStatsT('stats.form.intermediate'),
        'referencePosition' => omoStatsT('stats.form.position'),
        'referenceDate' => omoStatsT('stats.form.point_date'),
        'referenceDateAuto' => omoStatsT('stats.form.point_date_auto'),
        'referenceValue' => omoStatsT('stats.form.point_value'),
        'removeReferencePoint' => omoStatsT('stats.form.remove_point'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    var statsMenuOwnerDocument = root.ownerDocument || document;
    var floatingStatsMenu = statsMenuOwnerDocument.querySelector('[data-omo-stats-floating-menu="1"]');
    if (!floatingStatsMenu) {
        floatingStatsMenu = statsMenuOwnerDocument.createElement('div');
        floatingStatsMenu.className = 'omo-stats-item-menu__panel generic-menu-panel generic-menu-panel--wide generic-menu-panel--floating omo-stats-item-menu__panel--floating';
        floatingStatsMenu.setAttribute('data-omo-stats-floating-menu', '1');
        floatingStatsMenu.setAttribute('role', 'menu');
        floatingStatsMenu.hidden = true;
        statsMenuOwnerDocument.body.appendChild(floatingStatsMenu);
    }
    var activeStatsMenuToggle = null;
    var floatingStatsMenuActions = new WeakMap();

    function positionStatsFloatingMenu(toggle) {
        if (!(toggle instanceof Element) || !toggle.isConnected) {
            closeStatsItemMenus();
            return;
        }

        floatingStatsMenu.hidden = false;
        floatingStatsMenu.style.visibility = 'hidden';
        floatingStatsMenu.style.top = '0px';
        floatingStatsMenu.style.left = '0px';

        var toggleRect = toggle.getBoundingClientRect();
        var menuRect = floatingStatsMenu.getBoundingClientRect();
        var viewportPadding = 12;
        var gap = 8;
        var top = toggleRect.bottom + gap;
        var left = toggleRect.right - menuRect.width;

        if (top + menuRect.height > window.innerHeight - viewportPadding) {
            top = Math.max(viewportPadding, toggleRect.top - menuRect.height - gap);
        }
        if (left + menuRect.width > window.innerWidth - viewportPadding) {
            left = Math.max(viewportPadding, window.innerWidth - menuRect.width - viewportPadding);
        }
        if (left < viewportPadding) {
            left = viewportPadding;
        }

        floatingStatsMenu.style.top = String(Math.round(top)) + 'px';
        floatingStatsMenu.style.left = String(Math.round(left)) + 'px';
        floatingStatsMenu.style.visibility = '';
    }

    function closeStatsItemMenus() {
        root.querySelectorAll('[data-omo-stats-item-menu]').forEach(function (menu) {
            menu.classList.remove('is-open');
        });
        root.querySelectorAll('[data-omo-stats-item-menu-panel]').forEach(function (panel) {
            panel.hidden = true;
        });
        root.querySelectorAll('[data-omo-stats-item-menu-toggle]').forEach(function (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
        });
        activeStatsMenuToggle = null;
        floatingStatsMenu.hidden = true;
        floatingStatsMenu.style.visibility = '';
        floatingStatsMenu.replaceChildren();
    }

    function openStatsItemMenu(toggle) {
        var menu = toggle ? toggle.closest('[data-omo-stats-item-menu]') : null;
        var panel = menu ? menu.querySelector('[data-omo-stats-item-menu-panel]') : null;
        var shouldOpen = !!toggle && !!panel && (activeStatsMenuToggle !== toggle || floatingStatsMenu.hidden);
        closeStatsItemMenus();

        if (!shouldOpen) {
            return;
        }

        var fragment = statsMenuOwnerDocument.createDocumentFragment();
        Array.prototype.forEach.call(panel.children, function (originalAction) {
            if (!(originalAction instanceof Element)) {
                return;
            }
            var floatingAction = originalAction.cloneNode(true);
            floatingAction.setAttribute('data-omo-stats-floating-menu-action', '1');
            floatingAction.setAttribute('role', 'menuitem');
            floatingStatsMenuActions.set(floatingAction, originalAction);
            fragment.appendChild(floatingAction);
        });
        floatingStatsMenu.replaceChildren(fragment);
        if (!floatingStatsMenu.childElementCount) {
            return;
        }

        activeStatsMenuToggle = toggle;
        menu.classList.add('is-open');
        toggle.setAttribute('aria-expanded', 'true');
        positionStatsFloatingMenu(toggle);
    }

    window.addEventListener('resize', function () {
        if (activeStatsMenuToggle) {
            positionStatsFloatingMenu(activeStatsMenuToggle);
        }
    });
    statsMenuOwnerDocument.addEventListener('scroll', function () {
        if (activeStatsMenuToggle) {
            positionStatsFloatingMenu(activeStatsMenuToggle);
        }
    }, true);

    function resolveUrl(url) {
        return typeof window.omoResolveAppUrl === 'function' ? window.omoResolveAppUrl(url) : url;
    }

    function normalizeScope(scope) {
        if (scope === 'global') {
            return 'descendants';
        }
        return scope === 'children' || scope === 'descendants' ? scope : 'contextual';
    }

    function normalizeSort(sortName) {
        return sortName === 'alpha' ? 'alpha' : 'temporal';
    }

    function normalizeView(viewName) {
        return viewName === 'compact' ? 'compact' : 'cards';
    }

    function getPreferencesContextKey() {
        return String(root.getAttribute('data-omo-stats-oid') || '0')
            + ':' + String(root.getAttribute('data-omo-stats-cid') || '0');
    }

    function createStoredFilters(filters) {
        return {
            scope: normalizeScope(filters && filters.scope),
            sort: normalizeSort(filters && filters.sort),
            view: normalizeView(filters && filters.view)
        };
    }

    function getStoredFiltersStore() {
        try {
            var storedValue = window.localStorage.getItem(savedViewsStorageKey);
            var savedViews = storedValue ? JSON.parse(storedValue) : null;
            if (savedViews && typeof savedViews === 'object' && savedViews.contexts && typeof savedViews.contexts === 'object') {
                return {
                    defaultView: savedViews.defaultView && typeof savedViews.defaultView === 'object' ? savedViews.defaultView : null,
                    contexts: savedViews.contexts
                };
            }

            var legacyValue = window.localStorage.getItem(legacySavedViewsStorageKey);
            var legacyViews = legacyValue ? JSON.parse(legacyValue) : null;
            return {
                defaultView: null,
                contexts: legacyViews && typeof legacyViews === 'object' ? legacyViews : {}
            };
        } catch (error) {
            return {defaultView: null, contexts: {}};
        }
    }

    function saveStoredFiltersStore(store) {
        try {
            window.localStorage.setItem(savedViewsStorageKey, JSON.stringify({
                defaultView: store.defaultView && typeof store.defaultView === 'object' ? store.defaultView : null,
                contexts: store.contexts && typeof store.contexts === 'object' ? store.contexts : {}
            }));
        } catch (error) {
        }
    }

    function getStoredFilters() {
        var filters = getStoredFiltersStore().contexts[getPreferencesContextKey()];
        return filters && typeof filters === 'object' ? filters : null;
    }

    function getDefaultStoredFilters() {
        return getStoredFiltersStore().defaultView;
    }

    function storeFilters(filters) {
        var store = getStoredFiltersStore();
        store.contexts[getPreferencesContextKey()] = createStoredFilters(filters);
        saveStoredFiltersStore(store);
    }

    function storeDefaultFilters(filters) {
        var store = getStoredFiltersStore();
        store.defaultView = createStoredFilters(filters);
        saveStoredFiltersStore(store);
    }

    function clearStoredFilters() {
        var store = getStoredFiltersStore();
        delete store.contexts[getPreferencesContextKey()];
        saveStoredFiltersStore(store);
    }

    function readStoredValue(storage, storageKey) {
        try {
            var rawValue = storage.getItem(storageKey);
            var values = rawValue ? JSON.parse(rawValue) : null;
            return values && typeof values === 'object'
                ? values[getPreferencesContextKey()] || null
                : null;
        } catch (error) {
            return null;
        }
    }

    function writeStoredFilters(storage, storageKey, filters) {
        try {
            var rawValue = storage.getItem(storageKey);
            var values = rawValue ? JSON.parse(rawValue) : {};
            if (!values || typeof values !== 'object') {
                values = {};
            }
            values[getPreferencesContextKey()] = createStoredFilters(filters);
            storage.setItem(storageKey, JSON.stringify(values));
        } catch (error) {
        }
    }

    function clearTemporaryFilters() {
        try {
            var rawValue = window.sessionStorage.getItem(sessionViewsStorageKey);
            var values = rawValue ? JSON.parse(rawValue) : {};
            if (!values || typeof values !== 'object') {
                return;
            }
            delete values[getPreferencesContextKey()];
            window.sessionStorage.setItem(sessionViewsStorageKey, JSON.stringify(values));
        } catch (error) {
        }
    }

    function clearAllTemporaryFilters() {
        try {
            window.sessionStorage.removeItem(sessionViewsStorageKey);
        } catch (error) {
        }
    }

    function readStoredSearch() {
        var value = readStoredValue(window.sessionStorage, searchStorageKey);
        return typeof value === 'string' ? value : '';
    }

    function writeStoredSearch(value) {
        try {
            var rawValue = window.sessionStorage.getItem(searchStorageKey);
            var values = rawValue ? JSON.parse(rawValue) : {};
            if (!values || typeof values !== 'object') {
                values = {};
            }
            values[getPreferencesContextKey()] = String(value || '');
            window.sessionStorage.setItem(searchStorageKey, JSON.stringify(values));
        } catch (error) {
        }
    }

    function buildScopeUrl(scope, sortName) {
        var organizationId = Number(root.getAttribute('data-omo-stats-oid') || 0);
        var query = ['oid=' + encodeURIComponent(String(organizationId))];
        var nextScope = normalizeScope(scope);
        var nextSort = normalizeSort(sortName);
        if (routeCid > 0) {
            query.push('cid=' + encodeURIComponent(String(routeCid)));
        }
        if (nextScope !== 'contextual') {
            query.push('stats_scope=' + encodeURIComponent(nextScope));
        }
        query.push('stats_sort=' + encodeURIComponent(nextSort));
        return '/omo/api/stats/index.php?' + query.join('&');
    }

    function setLoading(isLoading) {
        root.classList.toggle('is-loading', Boolean(isLoading));
        Array.prototype.forEach.call(root.querySelectorAll(
            '[data-omo-stats-filter-toggle], [data-omo-stats-scope], [data-omo-stats-sort], '
            + '[data-omo-stats-view], [data-omo-stats-filter-apply], [data-omo-stats-filter-save], '
            + '[data-omo-stats-filter-more-toggle], [data-omo-stats-filter-more-action]'
        ), function (button) {
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
        var view = normalizeView(viewName);
        currentView = view;
        root.setAttribute('data-omo-stats-current-view', view);
        Array.prototype.forEach.call(root.querySelectorAll('[data-omo-stats-view]'), function (button) {
            var active = button.getAttribute('data-omo-stats-view') === view;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
        Array.prototype.forEach.call(root.querySelectorAll('[data-omo-stats-view-panel]'), function (panel) {
            panel.hidden = panel.getAttribute('data-omo-stats-view-panel') !== view;
        });
        var activeButton = root.querySelector('[data-omo-stats-view="' + view + '"]');
        var viewChip = root.querySelector('[data-omo-stats-view-chip]');
        if (activeButton && viewChip) {
            viewChip.textContent = activeButton.textContent.trim();
        }
        applyQuickSearch();
    }

    function normalizeSearch(value) {
        return String(value || '')
            .toLocaleLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();
    }

    function applyQuickSearch() {
        var query = normalizeSearch(currentSearch);
        var activePanel = root.querySelector('[data-omo-stats-view-panel="' + currentView + '"]');
        Array.prototype.forEach.call(root.querySelectorAll('[data-omo-stats-search-item]'), function (item) {
            item.hidden = query !== '' && normalizeSearch(item.textContent || '').indexOf(query) === -1;
        });
        Array.prototype.forEach.call(root.querySelectorAll('[data-omo-stats-search-group]'), function (group) {
            group.hidden = query !== '' && !group.querySelector('[data-omo-stats-search-item]:not([hidden])');
        });
        Array.prototype.forEach.call(root.querySelectorAll('[data-omo-stats-default-empty]'), function (empty) {
            empty.hidden = query !== '';
        });

        var visibleCount = activePanel
            ? activePanel.querySelectorAll('[data-omo-stats-search-item]:not([hidden])').length
            : 0;
        var empty = root.querySelector('[data-omo-stats-search-empty]');
        if (empty) {
            empty.hidden = query === '' || visibleCount > 0;
        }
        var headerCount = root.querySelector('[data-omo-stats-header-count]');
        if (headerCount) {
            headerCount.textContent = query === ''
                ? String(<?= (int)$displayItemCount ?>)
                : String(visibleCount);
        }
    }

    function getActiveFilters() {
        return {
            scope: normalizeScope(currentScope),
            sort: normalizeSort(currentSort),
            view: normalizeView(currentView)
        };
    }

    function normalizeFilters(filters) {
        var active = getActiveFilters();
        var scope = normalizeScope(filters && filters.scope);
        if (!root.querySelector('[data-omo-stats-scope="' + scope + '"]')) {
            scope = active.scope;
        }
        return {
            scope: scope,
            sort: normalizeSort(filters && filters.sort),
            view: normalizeView(filters && filters.view)
        };
    }

    function syncFilterChoices() {
        if (!pendingFilters) {
            return;
        }
        pendingFilters = normalizeFilters(pendingFilters);
        [
            {selector: '[data-omo-stats-scope]', attribute: 'data-omo-stats-scope', value: pendingFilters.scope},
            {selector: '[data-omo-stats-sort]', attribute: 'data-omo-stats-sort', value: pendingFilters.sort},
            {selector: '[data-omo-stats-view]', attribute: 'data-omo-stats-view', value: pendingFilters.view}
        ].forEach(function (choice) {
            Array.prototype.forEach.call(root.querySelectorAll(choice.selector), function (button) {
                var active = button.getAttribute(choice.attribute) === choice.value;
                button.classList.toggle('is-active', active);
                button.setAttribute('aria-pressed', active ? 'true' : 'false');
            });
        });
    }

    function applyFilters(filters, active) {
        var next = normalizeFilters(filters);
        var previous = active || getActiveFilters();
        if (next.scope !== previous.scope || next.sort !== previous.sort) {
            currentScope = next.scope;
            currentSort = next.sort;
            refreshRoot(buildScopeUrl(next.scope, next.sort));
            return;
        }
        applyView(next.view);
        syncFilterChips();
    }

    function closeFilterMoreMenu() {
        Array.prototype.forEach.call(root.querySelectorAll('[data-omo-stats-filter-more-menu]'), function (menu) {
            var panel = menu.querySelector('[data-omo-stats-filter-more-panel]');
            var toggle = menu.querySelector('[data-omo-stats-filter-more-toggle]');
            if (panel) {
                panel.hidden = true;
            }
            menu.classList.remove('is-open');
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    function handleFilterOutsidePointerDown(event) {
        var control = root.querySelector('[data-omo-stats-filter-control]');
        if (control && control.contains(event.target)) {
            return;
        }
        closeFilterPanel(true, false);
    }

    function openFilterPanel() {
        var panel = root.querySelector('[data-omo-stats-filter-panel]');
        if (!panel || filterPanelOpen) {
            return;
        }
        pendingFilters = getActiveFilters();
        closeFilterMoreMenu();
        syncFilterChoices();
        panel.hidden = false;
        filterPanelOpen = true;
        Array.prototype.forEach.call(root.querySelectorAll('[data-omo-stats-filter-toggle]'), function (button) {
            button.setAttribute('aria-expanded', 'true');
        });
        document.addEventListener('pointerdown', handleFilterOutsidePointerDown, true);
    }

    function closeFilterPanel(applyChanges, saveView) {
        var panel = root.querySelector('[data-omo-stats-filter-panel]');
        if (!filterPanelOpen) {
            return;
        }
        filterPanelOpen = false;
        if (panel) {
            panel.hidden = true;
        }
        Array.prototype.forEach.call(root.querySelectorAll('[data-omo-stats-filter-toggle]'), function (button) {
            button.setAttribute('aria-expanded', 'false');
        });
        document.removeEventListener('pointerdown', handleFilterOutsidePointerDown, true);
        closeFilterMoreMenu();

        if (!applyChanges || !pendingFilters) {
            pendingFilters = null;
            return;
        }

        var active = getActiveFilters();
        var next = normalizeFilters(pendingFilters);
        pendingFilters = null;
        if (saveView) {
            storeFilters(next);
            clearTemporaryFilters();
        } else {
            writeStoredFilters(window.sessionStorage, sessionViewsStorageKey, next);
        }

        applyFilters(next, active);
    }

    function applyFilterMoreAction(action) {
        if (!filterPanelOpen || !pendingFilters) {
            return;
        }

        var active = getActiveFilters();
        var next = normalizeFilters(pendingFilters);
        closeFilterPanel(false, false);

        if (action === 'set-default') {
            clearStoredFilters();
            clearTemporaryFilters();
            storeDefaultFilters(next);
            applyFilters(next, active);
            return;
        }

        if (action === 'apply-everywhere') {
            var store = getStoredFiltersStore();
            store.defaultView = createStoredFilters(next);
            store.contexts = {};
            saveStoredFiltersStore(store);
            clearAllTemporaryFilters();
            applyFilters(next, active);
            return;
        }

        if (action === 'restore-default') {
            clearStoredFilters();
            clearTemporaryFilters();
            applyFilters(getDefaultStoredFilters() || {
                scope: 'contextual',
                sort: 'temporal',
                view: 'cards'
            }, active);
        }
    }

    function syncFilterChips() {
        [
            {button: '[data-omo-stats-scope="' + currentScope + '"]', chip: '[data-omo-stats-scope-chip]'},
            {button: '[data-omo-stats-sort="' + currentSort + '"]', chip: '[data-omo-stats-sort-chip]'},
            {button: '[data-omo-stats-view="' + currentView + '"]', chip: '[data-omo-stats-view-chip]'}
        ].forEach(function (entry) {
            var button = root.querySelector(entry.button);
            var chip = root.querySelector(entry.chip);
            if (button && chip) {
                chip.textContent = button.textContent.trim();
            }
        });
    }

    function initializeViewFilter() {
        currentSearch = readStoredSearch();
        var quickSearch = root.querySelector('[data-omo-stats-quick-search]');
        if (quickSearch) {
            quickSearch.value = currentSearch;
        }
        var temporary = readStoredValue(window.sessionStorage, sessionViewsStorageKey);
        var saved = getStoredFilters();
        var defaultFilters = getDefaultStoredFilters();
        var preferences = normalizeFilters(temporary || saved || defaultFilters || getActiveFilters());
        if ((Number.isInteger(initialIndicatorId) && initialIndicatorId > 0) || (Number.isInteger(initialGroupId) && initialGroupId > 0)) {
            preferences.scope = currentScope;
            preferences.sort = currentSort;
        }
        if (preferences.scope !== currentScope || preferences.sort !== currentSort) {
            currentScope = preferences.scope;
            currentSort = preferences.sort;
            refreshRoot(buildScopeUrl(preferences.scope, preferences.sort)).catch(function () {
                root.removeAttribute('data-omo-view-filter-pending');
                root.removeAttribute('aria-busy');
            });
            return;
        }
        applyView(preferences.view);
        syncFilterChips();
        root.removeAttribute('data-omo-view-filter-pending');
        root.removeAttribute('aria-busy');
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
            && /^stats-(?:i|g|indicator-|group-)(\d+)$/i.test(getCurrentRouteToken())
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
        var routeToken = typeof window.omoBuildStatsGroupRouteToken === 'function'
            ? window.omoBuildStatsGroupRouteToken(resolvedId)
            : 'stats-g' + String(resolvedId);
        if (typeof window.omoOpenDrawerHashState === 'function' && routeToken !== getCurrentRouteToken()) {
            window.omoOpenDrawerHashState(routeToken);
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

    function getEthercalcPickerItems() {
        try {
            var raw = root.getAttribute('data-omo-stats-ethercalc-picker') || '[]';
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
            + '<label class="omo-stats-picker__field"><span>' + escapeHtml(texts.chartMinValue) + '</span><input type="number" class="generic-form-control" data-omo-stats-group-editor-chart-min-value step="any"></label>'
            + '<div class="omo-stats-group-reference-editor" data-omo-stats-reference-editor>'
            + '<label class="omo-stats-field"><span>' + escapeHtml(texts.referenceTitle) + '</span><select class="generic-form-control" name="reference_type" data-omo-stats-reference-type><option value="none">' + escapeHtml(texts.referenceNone) + '</option><option value="ceiling">' + escapeHtml(texts.referenceCeiling) + '</option><option value="objective">' + escapeHtml(texts.referenceObjective) + '</option></select></label>'
            + '<section class="generic-soft-panel omo-stats-ceiling-editor" data-omo-stats-ceiling-editor hidden><div class="omo-stats-ceiling-editor__heading"><h3 class="generic-card-title generic-card-title--big">' + escapeHtml(texts.ceilingTitle) + '</h3><p>' + escapeHtml(texts.ceilingHelp) + '</p></div><label class="omo-stats-field"><span>' + escapeHtml(texts.ceilingValue) + '</span><input type="number" class="generic-form-control" name="ceiling_value" data-omo-stats-ceiling-value step="any"></label></section>'
            + '<section class="omo-stats-reference-editor" data-omo-stats-reference-panel>'
            + '<div class="omo-stats-reference-editor__heading"><div><h3 class="generic-card-title generic-card-title--big">' + escapeHtml(texts.referenceTitle) + '</h3><p>' + escapeHtml(texts.referenceHelp) + '</p></div><button type="button" class="generic-action-button generic-action-button--secondary" data-omo-stats-add-reference-point>' + escapeHtml(texts.addReferencePoint) + '</button></div>'
            + '<div class="omo-stats-reference-editor__rail" data-omo-stats-reference-rail></div>'
            + '<div class="omo-stats-reference-editor__points" data-omo-stats-reference-points></div>'
            + '</section>'
            + '</div>'
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
        var chartMinValueInput = drawerBody.querySelector('[data-omo-stats-group-editor-chart-min-value]');
        var referenceTypeInput = drawerBody.querySelector('[data-omo-stats-reference-type]');
        var ceilingValueInput = drawerBody.querySelector('[data-omo-stats-ceiling-value]');
        var feedback = drawerBody.querySelector('[data-omo-stats-group-editor-feedback]');
        var cancelButton = document.createElement('button');
        var saveButton = document.createElement('button');

        if (!form || !nameInput || !select || !modeInput) {
            setDrawerMessage(texts.loadError, true);
            return;
        }

        nameInput.value = isEditing ? String(editData.name || '') : '';
        modeInput.value = isEditing ? String(editData.displayMode || 'overlay') : 'overlay';
        if (chartMinValueInput) {
            chartMinValueInput.value = isEditing && editData.chartMinValue !== null && editData.chartMinValue !== undefined
                ? String(editData.chartMinValue)
                : '';
        }
        if (referenceTypeInput) {
            referenceTypeInput.value = isEditing ? String(editData.referenceType || 'none') : 'none';
        }
        if (ceilingValueInput) {
            ceilingValueInput.value = isEditing && editData.ceilingValue !== null && editData.ceilingValue !== undefined
                ? String(editData.ceilingValue)
                : '';
        }
        if (typeof window.omoStatsInitReferenceEditor === 'function') {
            window.omoStatsInitReferenceEditor(drawerBody, {
                points: isEditing && Array.isArray(editData.referencePoints) ? editData.referencePoints : [],
                labels: {
                    endpoint: texts.referenceEndpoint,
                    intermediate: texts.referenceIntermediate,
                    position: texts.referencePosition,
                    date: texts.referenceDate,
                    dateAuto: texts.referenceDateAuto,
                    value: texts.referenceValue,
                    remove: texts.removeReferencePoint
                }
            });
        }

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
            formData.append('chart_min_value', chartMinValueInput ? chartMinValueInput.value || '' : '');
            formData.append('reference_type', referenceTypeInput ? referenceTypeInput.value : 'none');
            if (ceilingValueInput && !ceilingValueInput.disabled) {
                formData.append('ceiling_value', ceilingValueInput.value || '');
            }
            Array.prototype.forEach.call(form.querySelectorAll('[name^="reference_points["]'), function (field) {
                if (!field.disabled) {
                    formData.append(field.name, field.value || '');
                }
            });
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
        var ethercalcAvailable = !isGroup && root.getAttribute('data-omo-stats-ethercalc-available') === '1';
        var pickerFieldsHtml = (isGroup ? '<label class="omo-stats-picker__field"><span>' + escapeHtml(texts.groupName) + '</span><input type="text" class="generic-form-control" data-omo-stats-picker-name></label>' : '')
            + '<label class="omo-stats-picker__field"><span>' + escapeHtml(texts.search) + '</span><input type="search" class="generic-form-control" data-omo-stats-picker-search placeholder="' + escapeHtml(texts.searchPlaceholder) + '"></label>'
            + '<label class="omo-stats-picker__field"><span>' + escapeHtml(texts.visible) + '</span><select class="generic-form-control omo-stats-picker__select" data-omo-stats-picker-select size="10"' + multiple + '></select></label>'
            + (isGroup ? '<label class="omo-stats-picker__field"><span>' + escapeHtml(texts.groupMode) + '</span><select class="generic-form-control" data-omo-stats-picker-mode><option value="overlay">' + escapeHtml(texts.overlay) + '</option><option value="sum">' + escapeHtml(texts.sum) + '</option></select></label>' : '');
        var ethercalcFieldsHtml = '';
        if (ethercalcAvailable) {
            ethercalcFieldsHtml = '<div class="generic-tabs omo-stats-picker__tabs" data-generic-tabs>'
                + '<div class="generic-tabs__list" aria-label="' + escapeHtml(texts.importTitle) + '">'
                + '<button type="button" class="generic-tabs__tab is-active" data-generic-tab data-generic-tab-target="omo-stats-import-indicators">' + escapeHtml(texts.importSourceIndicators) + '</button>'
                + '<button type="button" class="generic-tabs__tab" data-generic-tab data-generic-tab-target="omo-stats-import-ethercalc">' + escapeHtml(texts.importSourceEthercalc) + '</button>'
                + '</div>'
                + '<div class="generic-tabs__panels">'
                + '<section id="omo-stats-import-indicators" class="generic-tabs__panel omo-stats-picker" data-generic-tab-panel>' + pickerFieldsHtml + '</section>'
                + '<section id="omo-stats-import-ethercalc" class="generic-tabs__panel omo-stats-picker" data-generic-tab-panel hidden>'
                + '<label class="omo-stats-picker__field"><span>' + escapeHtml(texts.ethercalcDocument) + '</span><select class="generic-form-control" data-omo-stats-ethercalc-document></select></label>'
                + '<div class="generic-soft-panel" data-omo-stats-ethercalc-empty hidden>' + escapeHtml(texts.ethercalcNoDocuments) + '</div>'
                + '<label class="omo-stats-picker__field"><span>' + escapeHtml(texts.ethercalcMode) + '</span><select class="generic-form-control" data-omo-stats-ethercalc-mode><option value="cell">' + escapeHtml(texts.ethercalcModeCell) + '</option><option value="table">' + escapeHtml(texts.ethercalcModeTable) + '</option></select></label>'
                + '<section class="generic-soft-panel generic-soft-panel--stack" data-omo-stats-ethercalc-cell-fields>'
                + '<label class="omo-stats-picker__field"><span>' + escapeHtml(texts.ethercalcCell) + '</span><input type="text" class="generic-form-control" data-omo-stats-ethercalc-cell value="A1" placeholder="A1"></label>'
                + '<label class="omo-stats-picker__field"><span>' + escapeHtml(texts.ethercalcFrequency) + '</span><select class="generic-form-control" data-omo-stats-ethercalc-frequency><option value="hourly">' + escapeHtml(texts.ethercalcFrequencyHourly) + '</option><option value="daily">' + escapeHtml(texts.ethercalcFrequencyDaily) + '</option><option value="weekly">' + escapeHtml(texts.ethercalcFrequencyWeekly) + '</option></select></label>'
                + '</section>'
                + '<section class="generic-soft-panel generic-soft-panel--stack" data-omo-stats-ethercalc-table-fields hidden>'
                + '<label class="omo-stats-picker__field"><span>' + escapeHtml(texts.ethercalcRange) + '</span><input type="text" class="generic-form-control" data-omo-stats-ethercalc-range value="A1:C100" placeholder="A1:C100"></label>'
                + '<label class="omo-stats-picker__field"><span>' + escapeHtml(texts.ethercalcDateColumn) + '</span><input type="text" class="generic-form-control" data-omo-stats-ethercalc-date-column value="A" placeholder="A"></label>'
                + '<label class="omo-stats-picker__field"><span>' + escapeHtml(texts.ethercalcValueColumns) + '</span><input type="text" class="generic-form-control" data-omo-stats-ethercalc-value-columns value="B,C" placeholder="B,C"></label>'
                + '<p class="generic-description">' + escapeHtml(texts.ethercalcTableHelp) + '</p>'
                + '</section>'
                + '</section>'
                + '</div>'
                + '</div>';
        }
        var html = '<div class="omo-stats-picker">'
            + (ethercalcFieldsHtml || pickerFieldsHtml)
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
        var ethercalcTab = modalBody.querySelector('[data-generic-tab-target="omo-stats-import-ethercalc"]');
        var ethercalcDocumentSelect = modalBody.querySelector('[data-omo-stats-ethercalc-document]');
        var ethercalcEmpty = modalBody.querySelector('[data-omo-stats-ethercalc-empty]');
        var ethercalcModeSelect = modalBody.querySelector('[data-omo-stats-ethercalc-mode]');
        var ethercalcCellFields = modalBody.querySelector('[data-omo-stats-ethercalc-cell-fields]');
        var ethercalcTableFields = modalBody.querySelector('[data-omo-stats-ethercalc-table-fields]');
        if (nameInput && isEditing) {
            nameInput.value = String(editData.name || '');
        }
        if (modeInput && isEditing) {
            modeInput.value = String(editData.displayMode || 'overlay');
        }
        if (typeof window.initGenericTabs === 'function') {
            window.initGenericTabs(modalBody);
        }
        function renderEthercalcDocuments() {
            if (!ethercalcDocumentSelect) {
                return;
            }
            var documents = getEthercalcPickerItems();
            ethercalcDocumentSelect.innerHTML = '';
            documents.forEach(function (item) {
                var option = document.createElement('option');
                option.value = String(item.id || '');
                option.textContent = String(item.name || '');
                ethercalcDocumentSelect.appendChild(option);
            });
            ethercalcDocumentSelect.disabled = documents.length === 0;
            if (ethercalcEmpty) {
                ethercalcEmpty.hidden = documents.length > 0;
            }
        }
        function syncEthercalcMode() {
            var isTableMode = ethercalcModeSelect && ethercalcModeSelect.value === 'table';
            if (ethercalcCellFields) {
                ethercalcCellFields.hidden = !!isTableMode;
            }
            if (ethercalcTableFields) {
                ethercalcTableFields.hidden = !isTableMode;
            }
        }
        function isEthercalcTabActive() {
            return !!ethercalcTab && ethercalcTab.classList.contains('is-active');
        }
        function syncApplyButton() {
            if (!applyButton || isGroup) {
                return;
            }
            applyButton.textContent = isEthercalcTabActive()
                ? texts.ethercalcPrototypeAction
                : (isEditing ? texts.update : texts.add);
        }
        renderEthercalcDocuments();
        syncEthercalcMode();
        syncApplyButton();
        if (ethercalcModeSelect) {
            ethercalcModeSelect.addEventListener('change', syncEthercalcMode);
        }
        if (ethercalcTab) {
            ethercalcTab.closest('[data-generic-tabs]').addEventListener('click', function () {
                window.setTimeout(syncApplyButton, 0);
            });
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
                if (isEthercalcTabActive()) {
                    if (!ethercalcDocumentSelect || !ethercalcDocumentSelect.value) {
                        window.omoNotify(texts.ethercalcNoDocuments, 'error');
                        return;
                    }
                    window.omoNotify(texts.ethercalcPrototypeNotice, 'info');
                    return;
                }
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
                    window.omoNotify(error.message || texts.loadError, 'error');
                    applyButton.disabled = false;
                });
            });
        }
    }

    Array.prototype.forEach.call(root.querySelectorAll('[data-omo-stats-filter-toggle]'), function (button) {
        button.addEventListener('click', function () {
            if (filterPanelOpen) {
                closeFilterPanel(true, false);
            } else {
                openFilterPanel();
            }
        });
    });

    var filterPanel = root.querySelector('[data-omo-stats-filter-panel]');
    if (filterPanel) {
        filterPanel.addEventListener('click', function (event) {
            var moreToggle = event.target.closest('[data-omo-stats-filter-more-toggle]');
            if (moreToggle) {
                event.preventDefault();
                event.stopPropagation();
                var moreMenu = moreToggle.closest('[data-omo-stats-filter-more-menu]');
                var morePanel = moreMenu ? moreMenu.querySelector('[data-omo-stats-filter-more-panel]') : null;
                var isMoreMenuOpen = !!morePanel && !morePanel.hidden;
                closeFilterMoreMenu();
                if (!isMoreMenuOpen && morePanel) {
                    morePanel.hidden = false;
                    moreMenu.classList.add('is-open');
                    moreToggle.setAttribute('aria-expanded', 'true');
                }
                return;
            }
            var moreAction = event.target.closest('[data-omo-stats-filter-more-action]');
            if (moreAction) {
                event.preventDefault();
                event.stopPropagation();
                applyFilterMoreAction(moreAction.getAttribute('data-omo-stats-filter-more-action') || '');
                return;
            }
            var applyButton = event.target.closest('[data-omo-stats-filter-apply]');
            if (applyButton) {
                event.preventDefault();
                closeFilterPanel(true, false);
                return;
            }
            var saveButton = event.target.closest('[data-omo-stats-filter-save]');
            if (saveButton) {
                event.preventDefault();
                closeFilterPanel(true, true);
                return;
            }
            var scopeButton = event.target.closest('[data-omo-stats-scope]');
            if (scopeButton && pendingFilters) {
                pendingFilters.scope = normalizeScope(scopeButton.getAttribute('data-omo-stats-scope') || '');
                syncFilterChoices();
                return;
            }
            var sortButton = event.target.closest('[data-omo-stats-sort]');
            if (sortButton && pendingFilters) {
                pendingFilters.sort = normalizeSort(sortButton.getAttribute('data-omo-stats-sort') || '');
                syncFilterChoices();
                return;
            }
            var viewButton = event.target.closest('[data-omo-stats-view]');
            if (viewButton && pendingFilters) {
                pendingFilters.view = normalizeView(viewButton.getAttribute('data-omo-stats-view') || '');
                syncFilterChoices();
            }
        });
    }

    var quickSearch = root.querySelector('[data-omo-stats-quick-search]');
    if (quickSearch) {
        quickSearch.addEventListener('input', function () {
            currentSearch = quickSearch.value || '';
            writeStoredSearch(currentSearch);
            applyQuickSearch();
        });
        quickSearch.addEventListener('search', function () {
            currentSearch = quickSearch.value || '';
            writeStoredSearch(currentSearch);
            applyQuickSearch();
        });
    }

    root.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && filterPanelOpen) {
            closeFilterPanel(false, false);
        }
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
            openStatsItemMenu(toggle);
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
                referenceType: editGroupButton.getAttribute('data-omo-stats-group-reference-type') || 'none',
                ceilingValue: editGroupButton.getAttribute('data-omo-stats-group-ceiling-value') || '',
                chartMinValue: editGroupButton.getAttribute('data-omo-stats-group-chart-min-value') || '',
                referencePoints: (function () {
                    try {
                        var points = JSON.parse(editGroupButton.getAttribute('data-omo-stats-group-reference-points') || '[]');
                        return Array.isArray(points) ? points : [];
                    } catch (error) {
                        return [];
                    }
                })(),
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
                window.omoNotify(error.message || texts.loadError, 'error');
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
                window.omoNotify(error.message || texts.loadError, 'error');
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
            window.omoNotify(error.message || texts.loadError, 'error');
        });
    });

    statsMenuOwnerDocument.addEventListener('click', function (event) {
        var floatingAction = event.target.closest('[data-omo-stats-floating-menu-action]');
        if (floatingAction) {
            event.preventDefault();
            event.stopPropagation();
            var originalAction = floatingStatsMenuActions.get(floatingAction);
            closeStatsItemMenus();
            if (originalAction && originalAction.isConnected) {
                originalAction.click();
            }
            return;
        }
        if (event.target.closest('[data-omo-stats-floating-menu]')) {
            return;
        }
        if (event.target.closest('[data-omo-stats-item-menu]')) {
            return;
        }
        closeStatsItemMenus();
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
            if (moreMenu) {
                moreMenu.classList.toggle('is-open', !isOpen);
            }
        });
        document.addEventListener('click', function (event) {
            if (moreMenu && !moreMenu.contains(event.target)) {
                morePanel.hidden = true;
                moreToggle.setAttribute('aria-expanded', 'false');
                if (moreMenu) {
                    moreMenu.classList.remove('is-open');
                }
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
            if (moreMenu) {
                moreMenu.classList.remove('is-open');
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
                window.omoNotify(error.message || texts.loadError, 'error');
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
            var groupId = Number(detail.groupId || 0);
            if (groupId > 0) {
                openDrawerWithUrl(buildGroupDetailUrl(groupId));
            } else if (indicatorId > 0) {
                openDrawerWithUrl(buildDetailUrl(indicatorId));
            } else {
                closeDrawer({force: true});
            }
        };
        window.addEventListener('omo-stats-route-change', root.__omoStatsRouteHandler);
    }

    initializeViewFilter();

    if (Number.isInteger(initialGroupId) && initialGroupId > 0) {
        window.setTimeout(function () {
            openDrawerWithUrl(buildGroupDetailUrl(initialGroupId));
        }, 40);
    } else if (Number.isInteger(initialIndicatorId) && initialIndicatorId > 0) {
        window.setTimeout(function () {
            openDrawerWithUrl(buildDetailUrl(initialIndicatorId));
        }, 40);
    }
})();
</script>
