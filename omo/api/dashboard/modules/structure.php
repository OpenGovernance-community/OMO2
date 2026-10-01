<?php
if (!isset($historyItems)) {
    return;
}
?>
<div class="omo-personal-space__item-list omo-dashboard-module__list">
    <?php foreach ($historyItems as $historyItem): ?>
        <div class="omo-personal-space__item-card"><span class="omo-personal-space__item-topline"><span class="omo-personal-space__item-title"><?= omoApiEscape($historyItem['actionLabel'] ?? '') ?></span><span class="omo-personal-space__item-meta"><?= omoApiEscape($historyItem['datecreation'] ?? '') ?></span></span><span class="omo-personal-space__item-copy"><?= omoApiEscape(strip_tags((string)($historyItem['contentDisplay'] ?? $historyItem['contentHtml'] ?? ''))) ?></span></div>
    <?php endforeach; ?>
    <?php if ($historyItems === array()): ?><p class="omo-personal-space__empty"><?= omoApiEscape(t('personal_space.module.empty', [], $lang, $sourceLang)) ?></p><?php endif; ?>
</div>
