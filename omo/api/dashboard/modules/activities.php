<?php
if (!isset($dashboardActivityItems, $dashboardActivityCounts, $dashboardMetricLabels)) {
    return;
}

$formatActivityDate = static function ($value) use ($lang, $sourceLang): string {
    return $value instanceof DateTimeInterface
        ? $value->format('d.m.Y')
        : t('personal_space.date.unknown', [], $lang, $sourceLang);
};
?>
<div class="omo-dashboard-metrics" role="group">
    <?php foreach ($dashboardActivityCounts as $metricKey => $metricCount): ?>
        <button type="button" class="omo-dashboard-metric" data-omo-dashboard-filter="<?= omoApiEscape($metricKey) ?>"><strong><?= (int)$metricCount ?></strong><span><?= omoApiEscape($dashboardMetricLabels['activities'][$metricKey] ?? $metricKey) ?></span></button>
    <?php endforeach; ?>
</div>
<div class="omo-personal-space__item-list omo-dashboard-module__list">
    <?php foreach (array_slice($dashboardActivityItems, 0, 20) as $activityItem): ?>
        <?php
        $date = $activityItem['metric'] === 'overdue'
            ? ($activityItem['deadlineAt'] ?? null)
            : ($activityItem['occurrenceAt'] ?? null);
        $dateTextKey = $activityItem['metric'] === 'soon'
            ? 'personal_space.activities.soon_for'
            : ($activityItem['metric'] === 'overdue'
                ? 'personal_space.activities.overdue_for'
                : 'personal_space.activities.due_for');
        ?>
        <button type="button" class="omo-personal-space__item-button" data-omo-dashboard-filter-item="<?= omoApiEscape((string)$activityItem['metric']) ?>" data-omo-personal-space-route-token="activities"<?= $dashboardModuleForcedOpenScope !== '' ? ' data-omo-personal-space-forced-scope="' . omoApiEscape($dashboardModuleForcedOpenScope) . '"' : '' ?>>
            <span class="omo-personal-space__item-title"><?= omoApiEscape($activityItem['title']) ?></span>
            <span class="omo-personal-space__item-meta"><?= omoApiEscape($activityItem['holonLabel']) ?></span>
            <span class="omo-personal-space__item-meta"><?= omoApiEscape(t($dateTextKey, array('date' => $formatActivityDate($date)), $lang, $sourceLang)) ?></span>
        </button>
    <?php endforeach; ?>
    <?php if ($dashboardActivityItems === array()): ?><p class="omo-personal-space__empty"><?= omoApiEscape(t('personal_space.module.empty', [], $lang, $sourceLang)) ?></p><?php endif; ?>
    <?php if (count($dashboardActivityItems) > 20): ?><p class="omo-dashboard-module__more"><?= omoApiEscape(t('personal_space.module.more', ['count' => count($dashboardActivityItems) - 20], $lang, $sourceLang)) ?></p><?php endif; ?>
</div>
