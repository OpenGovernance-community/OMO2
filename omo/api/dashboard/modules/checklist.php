<?php
if (!isset($dashboardChecklistItems)) {
    return;
}
?>
<div class="omo-personal-space__item-list omo-dashboard-module__list">
    <?php foreach ($dashboardChecklistItems as $checklistItem): ?>
        <button type="button" class="omo-personal-space__item-button" data-omo-personal-space-checklist-id="<?= (int)$checklistItem['id'] ?>" data-omo-personal-space-checklist-holon-id="<?= (int)$checklistItem['holonId'] ?>">
            <span class="omo-personal-space__item-title"><?= omoApiEscape((string)$checklistItem['title']) ?></span>
            <?php if ($checklistItem['holonLabel'] !== ''): ?><span class="omo-personal-space__item-meta"><?= omoApiEscape((string)$checklistItem['holonLabel']) ?></span><?php endif; ?>
            <span class="omo-personal-space__item-meta"><?= omoApiEscape(omoChecklistT('checklist.responsibility.label')) ?> : <?= omoApiEscape((string)$checklistItem['responsibilityLabel']) ?></span>
        </button>
    <?php endforeach; ?>
    <?php if ($dashboardChecklistItems === array()): ?><p class="omo-personal-space__empty"><?= omoApiEscape(t('personal_space.module.empty', [], $lang, $sourceLang)) ?></p><?php endif; ?>
    <p class="omo-dashboard-module__more" data-omo-dashboard-more data-omo-dashboard-more-template="<?= omoApiEscape(t('personal_space.module.more_template', [], $lang, $sourceLang)) ?>"<?= count($dashboardChecklistItems) > 20 ? '' : ' hidden' ?>><?= count($dashboardChecklistItems) > 20 ? omoApiEscape(t('personal_space.module.more', ['count' => count($dashboardChecklistItems) - 20], $lang, $sourceLang)) : '' ?></p>
</div>
