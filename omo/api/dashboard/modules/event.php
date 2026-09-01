<?php
if (!isset($calendarEvents)) {
    return;
}
?>
<div class="omo-personal-space__item-list omo-dashboard-module__list">
    <?php foreach ($calendarEvents as $eventItem): ?>
        <button type="button" class="omo-personal-space__item-button" data-omo-personal-space-calendar-event-id="<?= (int)($eventItem['id'] ?? 0) ?>" data-omo-personal-space-calendar-holon-id="<?= (int)($eventItem['holonId'] ?? 0) ?>">
            <span class="omo-personal-space__item-title"><?= omoApiEscape($eventItem['title'] ?? '') ?></span><span class="omo-personal-space__item-meta"><?= omoApiEscape($eventItem['rangeLabel'] ?? '') ?></span><span class="omo-personal-space__item-meta"><?= omoApiEscape($eventItem['contextLabel'] ?? '') ?></span>
        </button>
    <?php endforeach; ?>
    <?php if ($calendarEvents === array()): ?><p class="omo-personal-space__empty"><?= omoApiEscape(t('personal_space.module.empty', [], $lang, $sourceLang)) ?></p><?php endif; ?>
</div>
