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
        <div class="omo-dashboard-event-item is-status-<?= omoApiEscape($eventItem['status'] ?? '') ?>" data-omo-dashboard-filter-item="<?= omoApiEscape(implode(' ', (array)($eventItem['filters'] ?? array()))) ?>">
            <button type="button" class="omo-personal-space__item-button" data-omo-personal-space-calendar-event-id="<?= (int)($eventItem['id'] ?? 0) ?>" data-omo-personal-space-calendar-holon-id="<?= (int)($eventItem['holonId'] ?? 0) ?>">
                <span class="omo-personal-space__item-title"><?= omoApiEscape($eventItem['title'] ?? '') ?></span><span class="omo-personal-space__item-meta"><?= omoApiEscape($eventItem['rangeLabel'] ?? '') ?></span><span class="omo-personal-space__item-meta"><?= omoApiEscape($eventItem['contextLabel'] ?? '') ?></span>
                <?php if (trim((string)($eventItem['locationLabel'] ?? '')) !== ''): ?><span class="omo-personal-space__item-meta omo-dashboard-event__location"><?= omoApiEscape($eventItem['locationLabel']) ?></span><?php endif; ?>
            </button>
            <?php if (trim((string)($eventItem['documentUrl'] ?? '')) !== ''): ?>
                <button
                    type="button"
                    class="omo-dashboard-event-document"
                    data-omo-personal-space-document-url="<?= omoApiEscape($eventItem['documentUrl']) ?>"
                    data-omo-personal-space-document-title="<?= omoApiEscape($eventItem['documentTitle'] ?? '') ?>"
                    data-omo-personal-space-document-pv-editor-url="<?= omoApiEscape($eventItem['documentPvEditorUrl'] ?? '') ?>"
                    aria-label="<?= omoApiEscape(t('personal_space.calendar.document.open', [], $lang, $sourceLang)) ?>"
                    title="<?= omoApiEscape($eventItem['documentTitle'] ?? '') ?>"
                >
                    <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="M6 3.5h8l4 4v13H6z"></path><path d="M14 3.5v4h4M9 12h6M9 15.5h6"></path></svg>
                </button>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    <?php if ($calendarEvents === array()): ?><p class="omo-personal-space__empty"><?= omoApiEscape(t('personal_space.module.empty', [], $lang, $sourceLang)) ?></p><?php endif; ?>
</div>
