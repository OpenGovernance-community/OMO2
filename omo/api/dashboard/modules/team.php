<?php
if (!isset($teamEvents)) {
    return;
}
?>
<div class="omo-personal-space__item-list omo-dashboard-module__list">
    <?php foreach ($teamEvents as $event): ?>
        <button type="button" class="omo-personal-space__item-button" data-omo-personal-space-user-id="<?= (int)($event['userId'] ?? 0) ?>">
            <span class="omo-personal-space__item-topline"><span class="omo-personal-space__item-title"><?= omoApiEscape($event['displayName'] ?? '') ?></span><span class="omo-personal-space__tag"><?= omoApiEscape(($event['tagType'] ?? '') === 'pro' ? t('personal_space.team.tag.pro', [], $lang, $sourceLang) : t('personal_space.team.tag.personal', [], $lang, $sourceLang)) ?></span></span>
            <span class="omo-personal-space__item-meta"><?= omoApiEscape($event['headline'] ?? '') ?></span>
        </button>
    <?php endforeach; ?>
    <?php if ($teamEvents === array()): ?><p class="omo-personal-space__empty"><?= omoApiEscape(t('personal_space.module.empty', [], $lang, $sourceLang)) ?></p><?php endif; ?>
</div>
