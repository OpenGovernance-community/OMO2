<?php
if (!isset($dashboardDecisionItems, $dashboardDecisionCounts, $dashboardMetricLabels)) {
    return;
}
?>
<div class="omo-dashboard-metrics" role="group">
    <?php foreach ($dashboardDecisionCounts as $metricKey => $metricCount): ?>
        <button type="button" class="omo-dashboard-metric generic-action-button generic-action-button--metric generic-action-button--no-lift<?= $metricKey === 'pending' && (int)$metricCount > 0 ? ' generic-action-button--metric-alert' : '' ?>" aria-pressed="false" data-omo-dashboard-filter="<?= omoApiEscape($metricKey) ?>"><strong><?= (int)$metricCount ?></strong><span><?= omoApiEscape($dashboardMetricLabels['decisions'][$metricKey]) ?></span></button>
    <?php endforeach; ?>
</div>
<div class="omo-personal-space__item-list omo-dashboard-module__list">
    <?php foreach ($dashboardDecisionItems as $decisionItem): ?>
        <button type="button" class="omo-personal-space__item-button" data-omo-dashboard-filter-item="<?= omoApiEscape(implode(' ', $decisionItem['filters'])) ?>" data-omo-personal-space-route-token="decision-d<?= (int)$decisionItem['id'] ?>" data-omo-personal-space-forced-scope="<?= omoApiEscape($dashboardModuleScope) ?>">
            <span class="omo-personal-space__item-title"><?= omoApiEscape($decisionItem['title']) ?></span>
            <span class="omo-personal-space__item-meta"><?= omoApiEscape(implode(' · ', array_map(static function ($filter) use ($dashboardMetricLabels): string { return (string)$dashboardMetricLabels['decisions'][$filter]; }, $decisionItem['filters']))) ?></span>
        </button>
    <?php endforeach; ?>
    <?php if ($dashboardDecisionItems === array()): ?><p class="omo-personal-space__empty"><?= omoApiEscape(t('personal_space.decisions.empty', [], $lang, $sourceLang)) ?></p><?php endif; ?>
    <p class="omo-dashboard-module__more" data-omo-dashboard-more data-omo-dashboard-more-template="<?= omoApiEscape(t('personal_space.module.more_template', [], $lang, $sourceLang)) ?>"<?= count($dashboardDecisionItems) > 20 ? '' : ' hidden' ?>><?= count($dashboardDecisionItems) > 20 ? omoApiEscape(t('personal_space.module.more', ['count' => count($dashboardDecisionItems) - 20], $lang, $sourceLang)) : '' ?></p>
</div>
