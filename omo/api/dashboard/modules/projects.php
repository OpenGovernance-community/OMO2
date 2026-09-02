<?php
if (!isset($dashboardProjectItems, $dashboardProjectCounts, $dashboardMetricLabels)) {
    return;
}
?>
<div class="omo-dashboard-metrics" role="group">
    <?php foreach ($dashboardProjectCounts as $metricKey => $metricCount): ?>
        <button type="button" class="omo-dashboard-metric" data-omo-dashboard-filter="<?= omoApiEscape($metricKey) ?>"><strong><?= (int)$metricCount ?></strong><span><?= omoApiEscape($dashboardMetricLabels['projects'][$metricKey] ?? $metricKey) ?></span></button>
    <?php endforeach; ?>
</div>
<div class="omo-personal-space__item-list omo-dashboard-module__list">
    <?php foreach ($dashboardProjectItems as $projectItem): ?>
        <button type="button" class="omo-personal-space__item-button omo-dashboard-project-item omo-dashboard-project-item--<?= omoApiEscape($projectItem['status']) ?>" data-omo-dashboard-filter-item="<?= omoApiEscape(implode(' ', $projectItem['filters'])) ?>" data-omo-personal-space-project-id="<?= (int)$projectItem['id'] ?>" data-omo-personal-space-project-holon-id="<?= (int)$projectItem['holonId'] ?>"<?= $dashboardModuleForcedOpenScope !== '' ? ' data-omo-personal-space-project-scope="' . omoApiEscape($dashboardModuleForcedOpenScope) . '"' : '' ?>>
            <span class="omo-dashboard-project-item__status-bar" aria-hidden="true"></span>
            <span class="omo-personal-space__item-topline"><span class="omo-personal-space__item-title"><?= omoApiEscape($projectItem['title']) ?></span><?php if ($projectItem['priority'] !== null): ?><span class="generic-project-priority generic-project-priority--p<?= (int)$projectItem['priority'] ?>">P<?= (int)$projectItem['priority'] ?></span><?php endif; ?></span>
            <span class="omo-personal-space__item-meta"><?= omoApiEscape($projectItem['holonLabel']) ?><?php if (!empty($projectItem['responsibleLabel'])): ?> · <?= omoApiEscape($projectItem['responsibleLabel']) ?><?php endif; ?><?php if ((int)$projectItem['overdueDays'] > 0): ?> · <?= omoApiEscape(t('personal_space.overdue.days', ['count' => (string)$projectItem['overdueDays']], $lang, $sourceLang)) ?><?php endif; ?></span>
        </button>
    <?php endforeach; ?>
    <?php if ($dashboardProjectItems === array()): ?><p class="omo-personal-space__empty"><?= omoApiEscape(t('personal_space.module.empty', [], $lang, $sourceLang)) ?></p><?php endif; ?>
    <p class="omo-dashboard-module__more" data-omo-dashboard-more data-omo-dashboard-more-template="<?= omoApiEscape(t('personal_space.module.more_template', [], $lang, $sourceLang)) ?>"<?= count($dashboardProjectItems) > 20 ? '' : ' hidden' ?>><?= count($dashboardProjectItems) > 20 ? omoApiEscape(t('personal_space.module.more', ['count' => count($dashboardProjectItems) - 20], $lang, $sourceLang)) : '' ?></p>
</div>
