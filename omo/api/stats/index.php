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
$context['pvMeetingPermission'] = commonResolvePvMeetingPermissionContext($organizationId);
$pvMeetingQuery = is_array($context['pvMeetingPermission'])
    ? '&pv_meeting_document_id=' . (int)$context['pvMeetingPermission']['documentId']
        . '&pv_meeting_editor_token=' . rawurlencode((string)($_GET['pv_meeting_editor_token'] ?? ''))
    : '';

$organization = $context['organization'];
$rootHolon = $context['rootHolon'];
$currentHolon = $context['currentHolon'];
$currentUserId = function_exists('commonGetCurrentUserId') ? (int)commonGetCurrentUserId() : 0;
$applicationViewPreferences = omoApplicationViewPreferencesGetContext('stats', $organization, $currentHolon, $currentUserId);
$canToggleScope = $currentHolon instanceof Holon;
$availableScopes = omoApiGetAvailableContextScopes($canToggleScope, $currentHolon, $rootHolon);
$statsScope = omoApiNormalizeContextScope(
    omoApplicationViewPreferencesGetInitialValue($applicationViewPreferences, 'stats_scope', 'scope', 'contextual'),
    $availableScopes
);
$scopeActiveIndex = omoApiResolveContextScopeIndex($statsScope, $availableScopes);
$requestedStatsSort = (string)omoApplicationViewPreferencesGetInitialValue(
    $applicationViewPreferences,
    'stats_sort',
    'sort',
    'temporal'
);
$statsSort = in_array($requestedStatsSort, ['temporal', 'alpha'], true)
    ? $requestedStatsSort
    : 'temporal';
$requestedStatsAssignment = strtolower(trim((string)omoApplicationViewPreferencesGetInitialValue(
    $applicationViewPreferences,
    'stats_assignment',
    'assignment',
    'all'
)));
$statsAssignment = in_array($requestedStatsAssignment, ['mine', 'roles'], true)
    ? $requestedStatsAssignment
    : 'all';
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
$includeOrganizationItems = $currentHolon instanceof Holon && $rootHolon instanceof Holon
    && (int)$currentHolon->getId() === (int)$rootHolon->getId();
$indicators = new ArrayStatIndicator();
$indicators->loadForContext(
    $organizationId,
    $currentHolon instanceof Holon ? (int)$currentHolon->getId() : 0,
    $statsScope,
    $scopeHolonIds,
    $includeOrganizationItems
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
    $scopeHolonIds,
    $includeOrganizationItems
);
$importedIndicatorLabels = [];
$importedIndicatorIds = [];
$importedIndicatorEditable = [];
foreach ($imports as $import) {
    $sourceIndicator = $import->getIndicator();
    if (!($sourceIndicator instanceof StatIndicator) || $sourceIndicator->isHiddenFromCatalog() || !$sourceIndicator->canView()) {
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
    $importedIndicatorDeletable[$sourceId] = omoStatsCanDeleteContextResource($import, $context);
}

$groups = new ArrayStatIndicatorGroup();
$groups->loadForContext(
    $organizationId,
    $currentHolon instanceof Holon ? (int)$currentHolon->getId() : 0,
    $statsScope,
    $scopeHolonIds,
    $includeOrganizationItems
);
$groupItems = omoStatsCollectionItems($groups, StatIndicatorGroup::class);
$indicatorItems = array_values(array_filter($indicatorItems, static function (StatIndicator $indicator) use ($statsAssignment, $currentUserId, $organizationId): bool {
    return omoStatsMatchesAssignment($indicator, $statsAssignment, $currentUserId, $organizationId);
}));
$groupItems = array_values(array_filter($groupItems, static function (StatIndicatorGroup $group) use ($statsAssignment, $currentUserId, $organizationId): bool {
    if ($statsAssignment === 'all') {
        return true;
    }

    $groupHolon = $group->getHolon();
    if ((int)$group->get('IDholon') === 0) {
        return \dbObject\UserOrganization::hasActiveMembership($currentUserId, $organizationId);
    }
    return $groupHolon instanceof Holon
        && omoStatsUserIsAssociatedWithHolon($currentUserId, $organizationId, $groupHolon);
}));
$canManage = omoStatsCanCreateContext($context);
$canCreateIndicator = omoStatsCanCreateContext($context);
$emptyKey = $statsScope === 'children'
    ? 'stats.empty.children'
    : ($statsScope === 'descendants' ? 'stats.empty.descendants' : 'stats.empty.contextual');

$currentUrl = '/omo/api/stats/index.php?oid=' . rawurlencode((string)$organizationId);
if ($currentHolonId > 0) {
    $currentUrl .= '&cid=' . rawurlencode((string)$currentHolonId);
}
$currentUrl .= '&stats_scope=' . rawurlencode($statsScope);
$currentUrl .= '&stats_sort=' . rawurlencode($statsSort);
$currentUrl .= '&stats_assignment=' . rawurlencode($statsAssignment);
$currentUrl .= $pvMeetingQuery;
$createUrl = '/omo/api/stats/edit.php?oid=' . rawurlencode((string)$organizationId);
if ($currentHolonId > 0) {
    $createUrl .= '&cid=' . rawurlencode((string)$currentHolonId);
}
$createUrl .= $pvMeetingQuery;
$detailBaseUrl = '/omo/api/stats/detail.php?oid=' . rawurlencode((string)$organizationId);
if ($currentHolonId > 0) {
    $detailBaseUrl .= '&cid=' . rawurlencode((string)$currentHolonId);
}
$detailBaseUrl .= $pvMeetingQuery;
$groupDetailBaseUrl = '/omo/api/stats/group_detail.php?oid=' . rawurlencode((string)$organizationId);
if ($currentHolonId > 0) {
    $groupDetailBaseUrl .= '&cid=' . rawurlencode((string)$currentHolonId);
}
$groupDetailBaseUrl .= $pvMeetingQuery;

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
        'responsibilityLabel' => omoStatsResponsibleAssignmentLabel($indicator),
        'isImported' => isset($importedIndicatorLabels[(int)$indicator->getId()]),
        'importId' => $importedIndicatorIds[(int)$indicator->getId()] ?? 0,
        'canEditImport' => $importedIndicatorEditable[(int)$indicator->getId()] ?? false,
        'canDeleteImport' => $importedIndicatorDeletable[(int)$indicator->getId()] ?? false,
        'canDelete' => omoStatsCanDeleteIndicator($indicator, $context),
        'canEdit' => omoStatsCanEditIndicator($indicator, $context),
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
        'hideSameHolonSources' => (int)$group->get('hide_same_holon_sources') === 1,
        'canEdit' => omoStatsCanEditContextResource($group, $context),
        'canDelete' => omoStatsCanDeleteContextResource($group, $context),
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
                omoStatsMeasurementFrequencyRank($sourceIndicator)
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
    $frequency = $indicator->getEffectiveMeasurementFrequency();
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
$displayItemCount = count($statsEntries);
?>
<link rel="stylesheet" href="/common/view-filter/view-filter.css?v=20260902-save-menu">
<link rel="stylesheet" href="<?= commonAssetUrl('/omo/api/stats/stats.css') ?>">
<div
    class="omo-stats omo-panel-view"
    id="omo-stats-root"
    data-omo-stats-oid="<?= (int)$organizationId ?>"
    data-omo-stats-cid="<?= $currentHolon instanceof Holon ? (int)$currentHolon->getId() : 0 ?>"
    data-omo-app-view-preferences="<?= omoApiEscape(json_encode($applicationViewPreferences, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
    data-omo-stats-route-cid="<?= (int)$currentHolonId ?>"
    data-omo-stats-root-hid="<?= $rootHolon instanceof Holon ? (int)$rootHolon->getId() : 0 ?>"
    data-omo-stats-current-scope="<?= omoApiEscape($statsScope) ?>"
    data-omo-stats-current-sort="<?= omoApiEscape($statsSort) ?>"
    data-omo-stats-current-assignment="<?= omoApiEscape($statsAssignment) ?>"
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
                        <button type="button" class="omo-view-filter__chip" data-omo-stats-filter-toggle data-omo-stats-assignment-chip aria-expanded="false" aria-controls="omo-stats-filter-panel"><?= omoApiEscape(omoStatsT('stats.assignment.' . $statsAssignment)) ?></button>
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
                            <span class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoStatsT('stats.filters.assignment')) ?></span>
                            <div class="omo-segmented" role="group" aria-label="<?= omoApiEscape(omoStatsT('stats.assignment.aria')) ?>">
                                <button type="button" class="omo-segmented__button<?= $statsAssignment === 'mine' ? ' is-active' : '' ?>" data-omo-stats-assignment="mine" aria-pressed="<?= $statsAssignment === 'mine' ? 'true' : 'false' ?>"><?= omoApiEscape(omoStatsT('stats.assignment.mine')) ?></button>
                                <button type="button" class="omo-segmented__button<?= $statsAssignment === 'roles' ? ' is-active' : '' ?>" data-omo-stats-assignment="roles" aria-pressed="<?= $statsAssignment === 'roles' ? 'true' : 'false' ?>"><?= omoApiEscape(omoStatsT('stats.assignment.roles')) ?></button>
                                <button type="button" class="omo-segmented__button<?= $statsAssignment === 'all' ? ' is-active' : '' ?>" data-omo-stats-assignment="all" aria-pressed="<?= $statsAssignment === 'all' ? 'true' : 'false' ?>"><?= omoApiEscape(omoStatsT('stats.assignment.all')) ?></button>
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
                        <?php if (!empty($applicationViewPreferences['canSavePersonal']) || !empty($applicationViewPreferences['canSaveTemporary'])): ?>
                            <button type="button" class="generic-action-button generic-action-button--secondary"<?= !empty($applicationViewPreferences['canSavePersonal']) ? ' data-omo-stats-filter-save' : '' ?> data-omo-app-view-save-scope="<?= omoApiEscape($applicationViewPreferences['primarySaveScope']) ?>"><?= omoApiEscape(omoStatsT('stats.filters.save_view')) ?></button>
                        <?php elseif (($applicationViewPreferences['primarySaveScope'] ?? '') !== ''): ?>
                            <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-app-view-save-scope="<?= omoApiEscape($applicationViewPreferences['primarySaveScope']) ?>"><?= omoApiEscape($applicationViewPreferences['primarySaveLabel'] ?? '') ?></button>
                        <?php endif; ?>
                        <?= omoApplicationViewPreferencesRenderMenu($applicationViewPreferences) ?>
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
                                    <?php if ($groupItem['canEdit'] || $groupItem['canDelete']): ?>
                                        <div class="omo-stats-item-menu generic-menu" data-omo-stats-item-menu>
                                            <button type="button" class="omo-stats-item-menu__toggle generic-menu-toggle" data-omo-stats-item-menu-toggle aria-label="<?= omoApiEscape(omoStatsT('stats.action.more')) ?>" aria-expanded="false">...</button>
                                            <div class="omo-stats-item-menu__panel generic-menu-panel generic-menu-panel--wide" data-omo-stats-item-menu-panel hidden>
                                                <button type="button" class="generic-menu-item" data-omo-stats-open-editor-url="<?= omoApiEscape($groupDetailBaseUrl . '&id=' . rawurlencode((string)$group->getId())) ?>"><?= omoApiEscape(omoStatsT('stats.action.detail')) ?></button>
                                                <?php if ($groupItem['canEdit']): ?><button type="button" class="generic-menu-item" data-omo-stats-edit-group="<?= (int)$group->getId() ?>" data-omo-stats-group-name="<?= omoApiEscape((string)$group->get('name')) ?>" data-omo-stats-group-mode="<?= omoApiEscape((string)$group->get('display_mode')) ?>" data-omo-stats-group-hide-same-holon-sources="<?= $groupItem['hideSameHolonSources'] ? '1' : '0' ?>" data-omo-stats-group-indicators="<?= omoApiEscape(json_encode($groupItem['indicatorIds'])) ?>" data-omo-stats-group-reference-type="<?= omoApiEscape($groupItem['referenceType']) ?>" data-omo-stats-group-reference-points="<?= omoApiEscape(json_encode($groupItem['referencePoints'])) ?>" data-omo-stats-group-ceiling-value="<?= omoApiEscape((string)($groupItem['ceilingValue'] ?? '')) ?>" data-omo-stats-group-chart-min-value="<?= omoApiEscape((string)($groupItem['chartMinValue'] ?? '')) ?>"><?= omoApiEscape(omoStatsT('stats.action.edit_group')) ?></button><?php endif; ?>
                                                <?php if ($groupItem['canDelete']): ?><button type="button" class="generic-menu-item generic-menu-item--danger" data-omo-stats-delete-group="<?= (int)$group->getId() ?>"><?= omoApiEscape(omoStatsT('stats.action.delete_group')) ?></button><?php endif; ?>
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
                                data-omo-stats-import-id="<?= (int)$item['importId'] ?>"
                                data-omo-stats-search-item
                                tabindex="0"
                                role="button"
                                aria-label="<?= omoApiEscape(omoStatsT('stats.card.open', ['name' => $indicatorName])) ?>"
                            >
                                <div class="omo-stats-card__header">
                                    <div>
                                        <span class="generic-card-title generic-card-title--eyebrow"><?= omoApiEscape((string)$item['contextLabel']) ?></span>
                                        <h3 class="generic-card-title generic-card-title--big"><?= omoApiEscape($indicatorName) ?></h3>
                                        <span class="generic-meta generic-meta--small"><?= omoApiEscape(omoStatsT('stats.responsibility.label')) ?> : <?= omoApiEscape((string)$item['responsibilityLabel']) ?></span>
                                    </div>
                                    <span class="omo-stats-card__value-count<?= $item['canEdit'] ? ' omo-stats-card__value-count--with-menu' : '' ?>"><?= omoApiEscape(omoStatsT('stats.card.value_count', ['count' => count($item['values'])])) ?></span>
                                    <?php if ($item['isImported'] ? ($item['canEditImport'] || $item['canDeleteImport']) : ($item['canEdit'] || $item['canDelete'])): ?>
                                        <div class="omo-stats-item-menu generic-menu" data-omo-stats-item-menu>
                                            <button type="button" class="omo-stats-item-menu__toggle generic-menu-toggle" data-omo-stats-item-menu-toggle aria-label="<?= omoApiEscape(omoStatsT('stats.action.more')) ?>" aria-expanded="false">...</button>
                                            <div class="omo-stats-item-menu__panel generic-menu-panel generic-menu-panel--wide" data-omo-stats-item-menu-panel hidden>
                                                <?php if ($item['isImported']): ?>
                                                    <?php if ($item['canEditImport']): ?><button type="button" class="generic-menu-item" data-omo-stats-edit-import="<?= (int)$item['importId'] ?>" data-omo-stats-indicator-id="<?= (int)$indicator->getId() ?>"><?= omoApiEscape(omoStatsT('stats.action.edit_import')) ?></button><?php endif; ?>
                                                    <?php if ($item['canDeleteImport']): ?><button type="button" class="generic-menu-item generic-menu-item--danger" data-omo-stats-delete-import="<?= (int)$item['importId'] ?>"><?= omoApiEscape(omoStatsT('stats.action.delete_import')) ?></button><?php endif; ?>
                                                <?php else: ?>
                                                    <button type="button" class="generic-menu-item" data-omo-stats-open-editor-url="<?= omoApiEscape($detailBaseUrl . '&id=' . rawurlencode((string)$indicator->getId())) ?>"><?= omoApiEscape(omoStatsT('stats.action.detail')) ?></button>
                                                    <?php if ($item['canEdit']): ?><button type="button" class="generic-menu-item" data-omo-stats-open-editor-url="<?= omoApiEscape($createUrl . '&id=' . rawurlencode((string)$indicator->getId())) ?>"><?= omoApiEscape(omoStatsT('stats.action.edit')) ?></button><?php endif; ?>
                                                    <?php if ($item['canDelete']): ?><button type="button" class="generic-menu-item generic-menu-item--danger" data-omo-stats-delete-indicator="<?= (int)$indicator->getId() ?>"><?= omoApiEscape(omoStatsT('stats.action.delete_indicator')) ?></button><?php endif; ?>
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
                                    <?php if ($groupItem['canEdit'] || $groupItem['canDelete']): ?>
                                        <div class="omo-stats-item-menu generic-menu" data-omo-stats-item-menu>
                                            <button type="button" class="omo-stats-item-menu__toggle generic-menu-toggle" data-omo-stats-item-menu-toggle aria-label="<?= omoApiEscape(omoStatsT('stats.action.more')) ?>" aria-expanded="false">...</button>
                                            <div class="omo-stats-item-menu__panel generic-menu-panel generic-menu-panel--wide" data-omo-stats-item-menu-panel hidden>
                                                <button type="button" class="generic-menu-item" data-omo-stats-open-editor-url="<?= omoApiEscape($groupDetailBaseUrl . '&id=' . rawurlencode((string)$group->getId())) ?>"><?= omoApiEscape(omoStatsT('stats.action.detail')) ?></button>
                                                <?php if ($groupItem['canEdit']): ?><button type="button" class="generic-menu-item" data-omo-stats-edit-group="<?= (int)$group->getId() ?>" data-omo-stats-group-name="<?= omoApiEscape((string)$group->get('name')) ?>" data-omo-stats-group-mode="<?= omoApiEscape((string)$group->get('display_mode')) ?>" data-omo-stats-group-hide-same-holon-sources="<?= $groupItem['hideSameHolonSources'] ? '1' : '0' ?>" data-omo-stats-group-indicators="<?= omoApiEscape(json_encode($groupItem['indicatorIds'])) ?>" data-omo-stats-group-reference-type="<?= omoApiEscape($groupItem['referenceType']) ?>" data-omo-stats-group-reference-points="<?= omoApiEscape(json_encode($groupItem['referencePoints'])) ?>" data-omo-stats-group-ceiling-value="<?= omoApiEscape((string)($groupItem['ceilingValue'] ?? '')) ?>" data-omo-stats-group-chart-min-value="<?= omoApiEscape((string)($groupItem['chartMinValue'] ?? '')) ?>"><?= omoApiEscape(omoStatsT('stats.action.edit_group')) ?></button><?php endif; ?>
                                                <?php if ($groupItem['canDelete']): ?><button type="button" class="generic-menu-item generic-menu-item--danger" data-omo-stats-delete-group="<?= (int)$group->getId() ?>"><?= omoApiEscape(omoStatsT('stats.action.delete_group')) ?></button><?php endif; ?>
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
                                    data-omo-stats-import-id="<?= (int)$item['importId'] ?>"
                                    tabindex="0"
                                    role="button"
                                    aria-label="<?= omoApiEscape(omoStatsT('stats.card.open', ['name' => $indicatorName])) ?>"
                                >
                                    <?php if ($item['isImported'] ? ($item['canEditImport'] || $item['canDeleteImport']) : ($item['canEdit'] || $item['canDelete'])): ?>
                                        <div class="omo-stats-item-menu generic-menu" data-omo-stats-item-menu>
                                            <button type="button" class="omo-stats-item-menu__toggle generic-menu-toggle" data-omo-stats-item-menu-toggle aria-label="<?= omoApiEscape(omoStatsT('stats.action.more')) ?>" aria-expanded="false">...</button>
                                            <div class="omo-stats-item-menu__panel generic-menu-panel generic-menu-panel--wide" data-omo-stats-item-menu-panel hidden>
                                                <?php if ($item['isImported']): ?>
                                                    <?php if ($item['canEditImport']): ?><button type="button" class="generic-menu-item" data-omo-stats-edit-import="<?= (int)$item['importId'] ?>" data-omo-stats-indicator-id="<?= (int)$indicator->getId() ?>"><?= omoApiEscape(omoStatsT('stats.action.edit_import')) ?></button><?php endif; ?>
                                                    <?php if ($item['canDeleteImport']): ?><button type="button" class="generic-menu-item generic-menu-item--danger" data-omo-stats-delete-import="<?= (int)$item['importId'] ?>"><?= omoApiEscape(omoStatsT('stats.action.delete_import')) ?></button><?php endif; ?>
                                                <?php else: ?>
                                                    <button type="button" class="generic-menu-item" data-omo-stats-open-editor-url="<?= omoApiEscape($detailBaseUrl . '&id=' . rawurlencode((string)$indicator->getId())) ?>"><?= omoApiEscape(omoStatsT('stats.action.detail')) ?></button>
                                                    <?php if ($item['canEdit']): ?><button type="button" class="generic-menu-item" data-omo-stats-open-editor-url="<?= omoApiEscape($createUrl . '&id=' . rawurlencode((string)$indicator->getId())) ?>"><?= omoApiEscape(omoStatsT('stats.action.edit')) ?></button><?php endif; ?>
                                                    <?php if ($item['canDelete']): ?><button type="button" class="generic-menu-item generic-menu-item--danger" data-omo-stats-delete-indicator="<?= (int)$indicator->getId() ?>"><?= omoApiEscape(omoStatsT('stats.action.delete_indicator')) ?></button><?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <div class="generic-file-list__cell generic-file-list__cell--name" data-label="<?= omoApiEscape(omoStatsT('stats.column.indicator')) ?>">
                                        <div class="generic-file-list__name-main">
                                            <span class="omo-stats-compact__dot" aria-hidden="true"></span>
                                            <div class="generic-file-list__title-block">
                                                <strong class="generic-file-list__title"><?= omoApiEscape($indicatorName) ?></strong>
                                                <span class="generic-file-list__meta-line"><?= omoApiEscape(omoStatsT('stats.card.value_count', ['count' => count($item['values'])])) ?> · <?= omoApiEscape(omoStatsT('stats.responsibility.label')) ?> : <?= omoApiEscape((string)$item['responsibilityLabel']) ?></span>
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

    <div class="omo-overlay-drawer omo-overlay-drawer--detail-panel omo-stats__detail-drawer" data-omo-stats-drawer hidden>
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
<script src="/common/drawer/subdrawer.js?v=20260906-slide-right"></script>
<script src="/omo/api/stats/reference-editor.js?v=20260724-ceiling"></script>
<script src="/omo/assets/js/application-view-preferences.js?v=20260917-filter-hierarchy"></script>
<?= commonPageScriptTags('/omo/api/stats/stats.js', [
    'texts' => [
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
        'importSourceSpreadsheet' => omoStatsT('stats.import.source_spreadsheet'),
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
        'ethercalcName' => omoStatsT('stats.import.ethercalc.name'),
        'ethercalcCell' => omoStatsT('stats.import.ethercalc.cell'),
        'ethercalcFrequency' => omoStatsT('stats.import.ethercalc.frequency'),
        'ethercalcMeasurementFrequency' => omoStatsT('stats.import.ethercalc.frequency_measurement'),
        'ethercalcSyncFrequency' => omoStatsT('stats.import.ethercalc.frequency_sync'),
        'ethercalcFrequencyHourly' => omoStatsT('stats.import.ethercalc.frequency_hourly'),
        'ethercalcFrequencyDaily' => omoStatsT('stats.import.ethercalc.frequency_daily'),
        'ethercalcFrequencyWeekly' => omoStatsT('stats.import.ethercalc.frequency_weekly'),
        'ethercalcFrequencyMonthly' => omoStatsT('stats.import.ethercalc.frequency_monthly'),
        'ethercalcFrequencyQuarterly' => omoStatsT('stats.import.ethercalc.frequency_quarterly'),
        'ethercalcFrequencySemiannual' => omoStatsT('stats.import.ethercalc.frequency_semiannual'),
        'ethercalcFrequencyYearly' => omoStatsT('stats.import.ethercalc.frequency_yearly'),
        'ethercalcRange' => omoStatsT('stats.import.ethercalc.range'),
        'ethercalcDateColumn' => omoStatsT('stats.import.ethercalc.date_column'),
        'ethercalcValueColumns' => omoStatsT('stats.import.ethercalc.value_columns'),
        'ethercalcTableHelp' => omoStatsT('stats.import.ethercalc.table_help'),
        'ethercalcCreateAction' => omoStatsT('stats.import.ethercalc.create_action'),
        'spreadsheetDocument' => omoStatsT('stats.import.spreadsheet.document'),
        'spreadsheetNoDocuments' => omoStatsT('stats.import.spreadsheet.no_documents'),
        'spreadsheetSheet' => omoStatsT('stats.import.spreadsheet.sheet'),
        'spreadsheetMode' => omoStatsT('stats.import.spreadsheet.mode'),
        'spreadsheetModeCell' => omoStatsT('stats.import.spreadsheet.mode_cell'),
        'spreadsheetModeTable' => omoStatsT('stats.import.spreadsheet.mode_table'),
        'spreadsheetName' => omoStatsT('stats.import.spreadsheet.name'),
        'spreadsheetCell' => omoStatsT('stats.import.spreadsheet.cell'),
        'spreadsheetFrequency' => omoStatsT('stats.import.spreadsheet.frequency'),
        'spreadsheetMeasurementFrequency' => omoStatsT('stats.import.spreadsheet.frequency_measurement'),
        'spreadsheetSyncFrequency' => omoStatsT('stats.import.spreadsheet.frequency_sync'),
        'spreadsheetFrequencyHourly' => omoStatsT('stats.import.spreadsheet.frequency_hourly'),
        'spreadsheetFrequencyDaily' => omoStatsT('stats.import.spreadsheet.frequency_daily'),
        'spreadsheetFrequencyWeekly' => omoStatsT('stats.import.spreadsheet.frequency_weekly'),
        'spreadsheetFrequencyMonthly' => omoStatsT('stats.import.spreadsheet.frequency_monthly'),
        'spreadsheetFrequencyQuarterly' => omoStatsT('stats.import.spreadsheet.frequency_quarterly'),
        'spreadsheetFrequencySemiannual' => omoStatsT('stats.import.spreadsheet.frequency_semiannual'),
        'spreadsheetFrequencyYearly' => omoStatsT('stats.import.spreadsheet.frequency_yearly'),
        'spreadsheetRange' => omoStatsT('stats.import.spreadsheet.range'),
        'spreadsheetDateColumn' => omoStatsT('stats.import.spreadsheet.date_column'),
        'spreadsheetValueColumns' => omoStatsT('stats.import.spreadsheet.value_columns'),
        'spreadsheetTableHelp' => omoStatsT('stats.import.spreadsheet.table_help'),
        'spreadsheetCreateAction' => omoStatsT('stats.import.spreadsheet.create_action'),
        'groupName' => omoStatsT('stats.group.name'),
        'groupNameHelp' => omoStatsT('stats.group.name_help'),
        'groupIndicators' => omoStatsT('stats.group.indicators'),
        'groupIndicatorsHelp' => omoStatsT('stats.group.indicators_help'),
        'groupMode' => omoStatsT('stats.group.mode'),
        'groupModeHelp' => omoStatsT('stats.group.mode_help'),
        'groupHideSameHolonSources' => omoStatsT('stats.group.hide_same_holon_sources'),
        'groupHideSameHolonSourcesHelp' => omoStatsT('stats.group.hide_same_holon_sources_help'),
        'groupChart' => omoStatsT('stats.group.chart'),
        'groupChartMinValueHelp' => omoStatsT('stats.group.chart_min_value_help'),
        'groupReferenceType' => omoStatsT('stats.group.reference_type'),
        'groupReferenceTypeHelp' => omoStatsT('stats.group.reference_type_help'),
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
    ],
    'displayItemCount' => (int)$displayItemCount,
]) ?>
