<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\StatIndicator;
use dbObject\StatIndicatorGroup;
use dbObject\StatIndicatorGroupItem;

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$currentHolonId = isset($_GET['cid']) && is_numeric($_GET['cid']) ? (int)$_GET['cid'] : 0;
$groupId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$group = omoStatsLoadGroup($groupId, $organizationId);

if (!($group instanceof StatIndicatorGroup)) {
    http_response_code(404);
    echo '<div class="omo-empty-state">' . omoApiEscape(omoStatsT('stats.error.not_found')) . '</div>';
    exit;
}

$context = omoStatsResolveContext($organizationId, $currentHolonId);
$canEdit = !empty($context['status']) && omoStatsCanEditContextResource($group, $context);
$groupItems = omoStatsCollectionItems($group->getItems(), StatIndicatorGroupItem::class);
$indicatorIds = [];
$sourceIndicators = [];
foreach ($groupItems as $item) {
    $indicatorIds[] = (int)$item->get('IDstatindicator');
    $indicator = $item->getIndicator();
    if ($indicator instanceof StatIndicator && $indicator->canView()) {
        $sourceIndicators[] = $indicator;
    }
}
$series = omoStatsGetGroupSeries($group);
$referencePointData = array_map(static function ($point) {
    $pointAt = $point->get('point_at');
    return [
        'position_percent' => (float)$point->get('position_percent'),
        'point_at' => $pointAt instanceof DateTimeInterface ? $pointAt->format('Y-m-d\\TH:i') : '',
        'value' => (float)$point->get('value'),
    ];
}, omoStatsGetGroupReferencePoints($group));
$groupCeilingValue = omoStatsGetGroupCeilingValue($group);
$chartMinValue = is_numeric($group->get('chart_min_value')) ? (float)$group->get('chart_min_value') : null;
$groupOverdueInfo = omoStatsGetGroupOverdueInfo($group);
$groupOverdueSeverity = (string)$groupOverdueInfo['severity'];
$chartData = omoStatsBuildGroupChartData($group, $series, $groupOverdueSeverity);
$displayMode = StatIndicatorGroup::normalizeDisplayMode($group->get('display_mode'));
$headerDescription = $displayMode === StatIndicatorGroup::DISPLAY_SUM
    ? omoStatsT('stats.group.mode.sum')
    : omoStatsT('stats.group.mode.overlay');
$colors = ['#2563eb', '#db2777', '#059669', '#d97706', '#7c3aed', '#0891b2'];
$seriesColors = [];
foreach ($series as $seriesIndex => $seriesItem) {
    $seriesIndicator = $seriesItem['indicator'] ?? null;
    if ($seriesIndicator instanceof StatIndicator) {
        $sourceIndex = isset($seriesItem['source_index']) ? (int)$seriesItem['source_index'] : $seriesIndex;
        $seriesColors[(int)$seriesIndicator->getId()] = $colors[$sourceIndex % count($colors)];
    }
}
?>
<article class="omo-stats-group-detail<?= $groupOverdueSeverity === 'error' ? ' omo-stats-group-detail--overdue' : ($groupOverdueSeverity === 'warning' ? ' omo-stats-group-detail--warning' : '') ?>" data-omo-stats-group-detail data-group-id="<?= (int)$groupId ?>">
    <div
        hidden
        data-omo-subdrawer-header
        data-omo-subdrawer-title="<?= omoApiEscape((string)$group->get('name')) ?>"
        data-omo-subdrawer-description="<?= omoApiEscape($headerDescription) ?>"
    >
        <?php if ($canEdit): ?>
            <button
                type="button"
                class="generic-action-button generic-action-button--main"
                data-omo-subdrawer-action
                data-omo-stats-edit-group="<?= (int)$groupId ?>"
                data-omo-stats-group-name="<?= omoApiEscape((string)$group->get('name')) ?>"
                data-omo-stats-group-mode="<?= omoApiEscape((string)$group->get('display_mode')) ?>"
                data-omo-stats-group-hide-same-holon-sources="<?= (int)$group->get('hide_same_holon_sources') === 1 ? '1' : '0' ?>"
                data-omo-stats-group-indicators="<?= omoApiEscape(json_encode($indicatorIds)) ?>"
                data-omo-stats-group-reference-type="<?= omoApiEscape(StatIndicator::normalizeReferenceType($group->get('reference_type'))) ?>"
                data-omo-stats-group-reference-points="<?= omoApiEscape(json_encode(array_values($referencePointData))) ?>"
                data-omo-stats-group-ceiling-value="<?= omoApiEscape((string)($groupCeilingValue ?? '')) ?>"
                data-omo-stats-group-chart-min-value="<?= omoApiEscape((string)($chartMinValue ?? '')) ?>"
            ><?= omoApiEscape(omoStatsT('stats.action.edit_group')) ?></button>
        <?php endif; ?>
    </div>

    <div class="omo-stats-detail__meta omo-stats-detail__meta--compact generic-meta">
        <span><strong><?= omoApiEscape(omoStatsT('stats.card.member_count', ['count' => count($sourceIndicators)])) ?></strong></span>
        <?php if ($chartMinValue !== null): ?>
            <span><strong><?= omoApiEscape(omoStatsT('stats.detail.chart_min_value')) ?> :</strong> <?= omoApiEscape(omoStatsFormatNumber($chartMinValue)) ?></span>
        <?php endif; ?>
            <?php if ($groupOverdueSeverity === 'warning'): ?>
                <span class="omo-stats-overdue-label omo-stats-overdue-label--warning"><?= omoApiEscape(omoStatsT('stats.card.to_complete')) ?></span>
            <?php elseif ($groupOverdueSeverity === 'error'): ?>
                <span class="omo-stats-overdue-label"><?= omoApiEscape(omoStatsT('stats.card.overdue')) ?></span>
            <?php endif; ?>
    </div>

    <section class="generic-section omo-stats-detail__chart-panel">
        <div class="omo-stats-interactive-chart" data-omo-stats-interactive-chart>
            <?= omoStatsRenderGroupChart($group, $series, 'large', $groupOverdueSeverity, true) ?>
            <?= omoStatsRenderInteractiveChartRange($chartData) ?>
        </div>
    </section>

    <section class="generic-section omo-stats-group-detail__sources">
        <h3 class="generic-card-title generic-card-title--big"><?= omoApiEscape(omoStatsT('stats.group.detail.sources')) ?></h3>
        <div class="omo-stats-group-detail__legend">
            <?php if ($displayMode === StatIndicatorGroup::DISPLAY_SUM): ?>
                <div class="omo-stats-group-detail__legend-item omo-stats-group-detail__legend-item--sum" style="--omo-stats-series-color: <?= omoApiEscape($colors[0]) ?>;">
                    <span class="omo-stats-group-detail__legend-dot" aria-hidden="true"></span>
                    <div><strong><?= omoApiEscape(omoStatsT('stats.group.detail.sum')) ?></strong></div>
                </div>
            <?php endif; ?>
            <?php foreach ($sourceIndicators as $sourceIndex => $indicator): ?>
                <?php $legendColor = $seriesColors[(int)$indicator->getId()] ?? '#94a3b8'; ?>
                <button
                    type="button"
                    class="omo-stats-group-detail__legend-item"
                    style="--omo-stats-series-color: <?= omoApiEscape($legendColor) ?>;"
                    data-omo-stats-open-indicator="<?= (int)$indicator->getId() ?>"
                    aria-label="<?= omoApiEscape(omoStatsT('stats.card.open', ['name' => (string)$indicator->get('name')])) ?>"
                >
                    <span class="omo-stats-group-detail__legend-dot" aria-hidden="true"></span>
                    <div>
                        <strong><?= omoApiEscape((string)$indicator->get('name')) ?></strong>
                        <span><?= omoApiEscape(omoStatsContextLabel($indicator)) ?></span>
                    </div>
                </button>
            <?php endforeach; ?>
        </div>
    </section>
</article>
<script src="/omo/api/stats/chart.js?v=20260807-range-handles"></script>
