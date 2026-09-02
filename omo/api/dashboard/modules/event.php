<?php
if (!isset($calendarEvents, $dashboardEventCounts, $dashboardMetricLabels)) {
    return;
}
?>
<div class="omo-dashboard-metrics omo-dashboard-metrics--two" role="group">
    <?php foreach ($dashboardEventCounts as $metricKey => $metricCount): ?>
        <button type="button" class="omo-dashboard-metric" data-omo-dashboard-filter="<?= omoApiEscape($metricKey) ?>"><strong><?= (int)$metricCount ?></strong><span><?= omoApiEscape($dashboardMetricLabels['event'][$metricKey] ?? $metricKey) ?></span></button>
    <?php endforeach; ?>
</div>
<div class="omo-personal-space__item-list omo-dashboard-module__list">
    <?php foreach ($calendarEvents as $eventItem): ?>
        <button type="button" class="omo-personal-space__item-button" data-omo-dashboard-filter-item="<?= omoApiEscape(implode(' ', (array)($eventItem['filters'] ?? array()))) ?>" data-omo-personal-space-calendar-event-id="<?= (int)($eventItem['id'] ?? 0) ?>" data-omo-personal-space-calendar-holon-id="<?= (int)($eventItem['holonId'] ?? 0) ?>">
            <span class="omo-personal-space__item-title"><?= omoApiEscape($eventItem['title'] ?? '') ?></span><span class="omo-personal-space__item-meta"><?= omoApiEscape($eventItem['rangeLabel'] ?? '') ?></span><span class="omo-personal-space__item-meta"><?= omoApiEscape($eventItem['contextLabel'] ?? '') ?></span>
            <?php if (trim((string)($eventItem['locationLabel'] ?? '')) !== ''): ?><span class="omo-personal-space__item-meta omo-dashboard-event__location"><?= omoApiEscape($eventItem['locationLabel']) ?></span><?php endif; ?>
        </button>
    <?php endforeach; ?>
    <?php if ($calendarEvents === array()): ?><p class="omo-personal-space__empty"><?= omoApiEscape(t('personal_space.module.empty', [], $lang, $sourceLang)) ?></p><?php endif; ?>
</div>
