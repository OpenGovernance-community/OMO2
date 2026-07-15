<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\StatIndicator;
use dbObject\StatIndicatorGroup;
use dbObject\StatIndicatorGroupItem;

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$groupId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$group = omoStatsLoadGroup($groupId, $organizationId);

if (!($group instanceof StatIndicatorGroup)) {
    http_response_code(404);
    echo '<div class="omo-empty-state">' . omoApiEscape(omoStatsT('stats.error.not_found')) . '</div>';
    exit;
}

$sourceIndicators = [];
foreach ($group->getItems() as $item) {
    if (!($item instanceof StatIndicatorGroupItem)) {
        continue;
    }
    $indicator = $item->getIndicator();
    if ($indicator instanceof StatIndicator && $indicator->canView()) {
        $sourceIndicators[] = $indicator;
    }
}
$series = omoStatsGetGroupSeries($group);
$isOverdue = omoStatsIsGroupOverdue($group);
$displayMode = StatIndicatorGroup::normalizeDisplayMode($group->get('display_mode'));
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
<article class="omo-stats-group-detail<?= $isOverdue ? ' omo-stats-group-detail--overdue' : '' ?>" data-omo-stats-group-detail data-group-id="<?= (int)$groupId ?>">
    <section class="generic-hero-panel accent omo-stats-detail__hero">
        <div class="omo-stats-detail__hero-main">
            <div>
                <span class="generic-card-title generic-card-title--eyebrow"><?= omoApiEscape(omoStatsT('stats.card.group')) ?></span>
                <h2 class="generic-card-title generic-card-title--large"><?= omoApiEscape((string)$group->get('name')) ?></h2>
            </div>
            <span class="omo-stats-card__value-count"><?= omoApiEscape(omoStatsT('stats.card.member_count', ['count' => count($sourceIndicators)])) ?></span>
        </div>
        <div class="omo-stats-detail__meta">
            <?php if ($isOverdue): ?>
                <span class="omo-stats-overdue-label"><?= omoApiEscape(omoStatsT('stats.card.overdue')) ?></span>
            <?php endif; ?>
            <span><strong><?= omoApiEscape(omoStatsT('stats.group.mode')) ?> :</strong> <?= omoApiEscape($displayMode === StatIndicatorGroup::DISPLAY_SUM ? omoStatsT('stats.group.mode.sum') : omoStatsT('stats.group.mode.overlay')) ?></span>
        </div>
    </section>

    <section class="generic-section omo-stats-detail__chart-panel">
        <?= omoStatsRenderGroupChart($group, $series, 'large', $isOverdue) ?>
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
                <div class="omo-stats-group-detail__legend-item" style="--omo-stats-series-color: <?= omoApiEscape($legendColor) ?>;">
                    <span class="omo-stats-group-detail__legend-dot" aria-hidden="true"></span>
                    <div>
                        <strong><?= omoApiEscape((string)$indicator->get('name')) ?></strong>
                        <span><?= omoApiEscape(omoStatsContextLabel($indicator)) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</article>
