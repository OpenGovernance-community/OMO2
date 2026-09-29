<?php

/** Shared, title-free monthly and half-hour availability view. */
function commonCalendarRenderAvailabilityGrid(
    DateTimeImmutable $month,
    ?DateTimeImmutable $selectedDay,
    array $days,
    array $labels,
    string $controlAttribute,
    callable $makeControlValue,
    string $hint = '',
    bool $selectableSlots = false
): void {
    $escape = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $control = static function (DateTimeImmutable $targetMonth, ?DateTimeImmutable $targetDay = null) use ($controlAttribute, $makeControlValue, $escape): string {
        return $controlAttribute . '="' . $escape((string)$makeControlValue($targetMonth, $targetDay)) . '"';
    };
    $selectedData = $selectedDay ? ($days[$selectedDay->format('Y-m-d')] ?? null) : null;
    ?>
    <section class="calendar-freebusy" aria-label="<?= $escape($labels['heading']) ?>">
        <?php if ($hint !== ''): ?><div class="generic-description generic-description--relaxed"><?= $escape($hint) ?></div><?php endif; ?>
        <div class="calendar-freebusy-layout">
            <section class="calendar-freebusy-month generic-soft-panel">
                <div class="calendar-freebusy-month-head">
                    <h3 class="generic-card-title generic-card-title--medium"><?= $escape(commonUserAvailabilityFormatDate($month)) ?></h3>
                    <nav class="calendar-freebusy-nav" aria-label="<?= $escape($labels['heading']) ?>">
                        <button type="button" class="generic-action-button generic-action-button--secondary generic-action-button--icon-only" <?= $control($month->modify('-1 month')) ?> aria-label="<?= $escape($labels['previous_month']) ?>">&larr;</button>
                        <button type="button" class="generic-action-button generic-action-button--secondary generic-action-button--icon-only" <?= $control($month->modify('+1 month')) ?> aria-label="<?= $escape($labels['next_month']) ?>">&rarr;</button>
                    </nav>
                </div>
                <div class="calendar-freebusy-weekdays" aria-hidden="true">
                    <?php foreach (['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'] as $label): ?><span><?= $escape($label) ?></span><?php endforeach; ?>
                </div>
                <div class="calendar-freebusy-calendar">
                    <?php for ($empty = 1; $empty < (int)$month->format('N'); $empty++): ?><span aria-hidden="true"></span><?php endfor; ?>
                    <?php foreach ($days as $date => $data): $day = new DateTimeImmutable($date, $month->getTimezone()); $state = (string)$data['state']; ?>
                        <button type="button" class="calendar-freebusy-day" data-state="<?= $escape($state) ?>"<?= $selectedDay && $selectedDay->format('Y-m-d') === $date ? ' aria-current="date"' : '' ?> <?= $control($month, $day) ?> aria-label="<?= $escape(commonUserAvailabilityFormatDate($day, true) . ' : ' . $labels[$state]) ?>"><?= (int)$day->format('j') ?></button>
                    <?php endforeach; ?>
                </div>
                <ul class="calendar-freebusy-legend" aria-label="<?= $escape($labels['heading']) ?>">
                    <?php foreach (['free', 'partial', 'full'] as $state): ?><li data-state="<?= $escape($state) ?>"><span aria-hidden="true"></span><?= $escape($labels[$state]) ?></li><?php endforeach; ?>
                </ul>
            </section>
            <aside class="calendar-freebusy-day-panel generic-soft-panel" aria-live="polite">
                <?php if (!$selectedDay || !is_array($selectedData)): ?>
                    <div class="calendar-freebusy-empty"><strong class="generic-card-title generic-card-title--small"><?= $escape($labels['select_day']) ?></strong><span><?= $escape($labels['select_day_hint']) ?></span></div>
                <?php elseif (!$selectedData['slots']): ?>
                    <div class="calendar-freebusy-empty"><strong class="generic-card-title generic-card-title--small"><?= $escape(commonUserAvailabilityFormatDate($selectedDay, true)) ?></strong><span><?= $escape($labels['no_hours']) ?></span></div>
                <?php else: ?>
                    <div class="calendar-freebusy-day-head"><strong class="generic-card-title generic-card-title--small"><?= $escape(commonUserAvailabilityFormatDate($selectedDay, true)) ?></strong></div>
                    <?php if ($selectableSlots): ?>
                        <p class="calendar-freebusy-selection-hint"><?= $escape($labels['selection_hint']) ?></p>
                        <p class="calendar-freebusy-selection-feedback" data-omo-calendar-preview-selection-feedback data-range-blocked="<?= $escape($labels['range_blocked']) ?>" data-range-selected="<?= $escape($labels['range_selected']) ?>" role="status" aria-live="polite"></p>
                    <?php endif; ?>
                    <div class="calendar-freebusy-slots">
                        <?php $previousPauseEnd = null; foreach ($selectedData['slots'] as $slot): ?>
                            <?php if ($slot['pause']): ?>
                                <?php if ($previousPauseEnd === null || $previousPauseEnd != $slot['start']): ?>
                                    <div class="calendar-freebusy-pause"><span><?= $escape($labels['pause']) ?></span></div>
                                <?php endif; $previousPauseEnd = $slot['end']; ?>
                            <?php else: ?>
                                <?php $previousPauseEnd = null; ?>
                                <?php $isSelectable = $selectableSlots && !$slot['busy']; $slotTag = $isSelectable ? 'button' : 'div'; ?>
                                <<?= $slotTag ?><?= $isSelectable ? ' type="button"' : '' ?> class="calendar-freebusy-slot" data-state="<?= $slot['busy'] ? 'busy' : 'free' ?>"<?= $selectableSlots ? ' data-omo-calendar-preview-slot-start="' . $escape($slot['start']->format('Y-m-d\TH:i')) . '" data-omo-calendar-preview-slot-end="' . $escape($slot['end']->format('Y-m-d\TH:i')) . '"' : '' ?><?= $isSelectable ? ' aria-pressed="false"' : '' ?> aria-label="<?= $escape($slot['start']->format('H:i') . ' - ' . $slot['end']->format('H:i') . ' : ' . $labels[$slot['busy'] ? 'busy' : ($isSelectable ? 'select_slot' : 'available')]) ?>"><time datetime="<?= $escape($slot['start']->format(DateTimeInterface::ATOM)) ?>"><?= $escape($slot['start']->format('H:i') . ' - ' . $slot['end']->format('H:i')) ?></time><?php if ($slot['busy']): ?><span><?= $escape($labels['busy']) ?></span><?php endif; ?></<?= $slotTag ?>>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </aside>
        </div>
    </section>
    <?php
}
