<?php
if (!isset($dashboardRuleItems, $dashboardRuleCounts, $dashboardMetricLabels)) {
    return;
}
?>
<div class="omo-dashboard-metrics" role="group">
    <?php foreach ($dashboardRuleCounts as $metricKey => $metricCount): ?>
        <button type="button" class="omo-dashboard-metric" data-omo-dashboard-filter="<?= omoApiEscape($metricKey) ?>"><strong><?= (int)$metricCount ?></strong><span><?= omoApiEscape($dashboardMetricLabels['rules'][$metricKey] ?? $metricKey) ?></span></button>
    <?php endforeach; ?>
</div>
<div class="omo-personal-space__item-list omo-dashboard-module__list">
    <?php foreach (array_slice($dashboardRuleItems, 0, 20) as $ruleItem): ?>
        <button type="button" class="omo-personal-space__item-button" data-omo-dashboard-filter-item="<?= omoApiEscape(implode(' ', $ruleItem['filters'])) ?>" data-omo-personal-space-route-token="policy"<?= $dashboardModuleForcedOpenScope !== '' ? ' data-omo-personal-space-forced-scope="' . omoApiEscape($dashboardModuleForcedOpenScope) . '"' : '' ?>>
            <span class="omo-personal-space__item-title"><?= omoApiEscape($ruleItem['title']) ?></span>
            <span class="omo-personal-space__item-meta"><?= omoApiEscape(implode(' · ', array_map(static function ($filter) use ($dashboardMetricLabels): string { return (string)($dashboardMetricLabels['rules'][$filter] ?? $filter); }, $ruleItem['filters']))) ?></span>
        </button>
    <?php endforeach; ?>
    <?php if ($dashboardRuleItems === array()): ?><p class="omo-personal-space__empty"><?= omoApiEscape(t('personal_space.module.empty', [], $lang, $sourceLang)) ?></p><?php endif; ?>
</div>
