<?php
if (!isset($recentDocuments, $dashboardDocumentCounts, $dashboardMetricLabels, $formatDocumentSummaryDate)) {
    return;
}
?>
<div class="omo-dashboard-metrics omo-dashboard-metrics--two" role="group">
    <?php foreach ($dashboardDocumentCounts as $metricKey => $metricCount): ?>
        <button type="button" class="omo-dashboard-metric" data-omo-dashboard-filter="<?= omoApiEscape($metricKey) ?>"><strong><?= (int)$metricCount ?></strong><span><?= omoApiEscape($dashboardMetricLabels['documents'][$metricKey] ?? $metricKey) ?></span></button>
    <?php endforeach; ?>
</div>
<div class="omo-personal-space__item-list omo-dashboard-module__list">
    <?php foreach (array_slice($recentDocuments, 0, 20) as $documentItem): ?>
        <button type="button" class="omo-personal-space__item-button" data-omo-dashboard-filter-item="<?= omoApiEscape(implode(' ', $documentItem['filters'])) ?>" data-omo-personal-space-document-url="<?= omoApiEscape($documentItem['contextUrl'] ?? '') ?>" data-omo-personal-space-document-title="<?= omoApiEscape($documentItem['title'] ?? '') ?>">
            <span class="omo-personal-space__item-title"><?= omoApiEscape($documentItem['title'] ?? '') ?></span><span class="omo-personal-space__item-meta"><?= omoApiEscape($formatDocumentSummaryDate($documentItem['datemodification'] ?? $documentItem['datecreation'] ?? null)) ?></span>
        </button>
    <?php endforeach; ?>
    <?php if ($recentDocuments === array()): ?><p class="omo-personal-space__empty"><?= omoApiEscape(t('personal_space.module.empty', [], $lang, $sourceLang)) ?></p><?php endif; ?>
</div>
