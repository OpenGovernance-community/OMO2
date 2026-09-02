<?php
if (!isset($overdueIndicators, $dashboardHolonId)) {
    return;
}
?>
<div class="omo-personal-space__item-list omo-dashboard-module__list">
    <?php foreach ($overdueIndicators as $overdueIndicator): ?>
        <button type="button" class="omo-personal-space__item-button omo-personal-space__item-button--overdue" data-omo-personal-space-indicator-id="<?= (int)$overdueIndicator['id'] ?>" data-omo-personal-space-indicator-holon-id="<?= (int)$dashboardHolonId ?>"<?= $dashboardModuleForcedOpenScope !== '' ? ' data-omo-personal-space-indicator-scope="' . omoApiEscape($dashboardModuleForcedOpenScope) . '"' : '' ?>><span class="omo-personal-space__item-topline"><span class="omo-personal-space__item-title"><?= omoApiEscape($overdueIndicator['title']) ?></span><span class="omo-personal-space__tag omo-personal-space__tag--danger"><?= omoApiEscape($overdueIndicator['severity'] === 'warning' ? t('personal_space.overdue.to_complete', [], $lang, $sourceLang) : t('personal_space.overdue.days', ['count' => (string)$overdueIndicator['overdueDays']], $lang, $sourceLang)) ?></span></span><span class="omo-personal-space__item-meta"><?= omoApiEscape($overdueIndicator['contextLabel']) ?></span></button>
    <?php endforeach; ?>
    <?php if ($overdueIndicators === array()): ?><p class="omo-personal-space__empty"><?= omoApiEscape(t('personal_space.module.empty', [], $lang, $sourceLang)) ?></p><?php endif; ?>
</div>
