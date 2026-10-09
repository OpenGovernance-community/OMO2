<?php

/** Shared duration choices for event editors and public-booking defaults. */
function commonCalendarRenderTimeBufferSelect(string $name, int $value, bool $enabled = true): void
{
    static $sourceLang = [
        'none' => ['text' => 'Aucun', 'context' => 'No attached preparation or closing time.'],
        'minutes' => ['text' => '{minutes} minutes', 'context' => 'Attached time duration in minutes.'],
        'hours' => ['text' => '{hours}h', 'context' => 'Attached time duration in whole hours.'],
        'hours_minutes' => ['text' => '{hours}h{minutes}', 'context' => 'Attached time duration in hours and minutes.'],
    ];
    static $bundle = null;
    $bundle ??= omoLoadTranslationBundle('omo_calendar_time_buffers', $sourceLang);
    $escape = static fn($text): string => htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
    $choices = [0, 15, 30, 45, 60, 90, 120, 150, 180, 240];
    // Retain durations saved before the preset selector was introduced.
    if ($value > 0 && !in_array($value, $choices, true)) { $choices[] = $value; sort($choices); }
    ?>
    <select class="generic-form-control" name="<?= $escape($name) ?>"<?= $enabled ? '' : ' disabled' ?>>
        <?php foreach ($choices as $minutes):
            $key = $minutes === 0 ? 'none' : ($minutes < 60 ? 'minutes' : ($minutes % 60 === 0 ? 'hours' : 'hours_minutes'));
            $label = t($key, ['hours' => (string)intdiv($minutes, 60), 'minutes' => (string)($minutes < 60 ? $minutes : $minutes % 60)], $bundle, $sourceLang);
            ?>
            <option value="<?= $minutes ?>"<?= $minutes === $value ? ' selected' : '' ?>><?= $escape($label) ?></option>
        <?php endforeach; ?>
    </select>
    <?php
}

/** The same read-only event summary and personal controls for local/imported events. */
function commonCalendarRenderPersonalTimeBufferForm(array $options): void
{
    $sourceLang = [
        'title' => ['text' => 'Temps avant / apres', 'context' => 'Personal event preparation and closing time editor.'],
        'enable' => ['text' => 'Definir mes temps de preparation/cloture', 'context' => 'Enable personal time around this event.'],
        'before' => ['text' => 'Preparation / deplacement avant', 'context' => 'Personal time before an event.'],
        'after' => ['text' => 'Cloture / deplacement apres', 'context' => 'Personal time after an event.'],
        'save' => ['text' => 'Enregistrer', 'context' => 'Save personal event time settings.'],
    ];
    $bundle = omoLoadTranslationBundle('omo_calendar_personal_time_buffers', $sourceLang);
    $translate = static fn(string $key): string => t($key, [], $bundle, $sourceLang);
    $escape = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    [$preparation, $closing] = $options['buffers'];
    $enabled = $preparation > 0 || $closing > 0;
    $formId = $options['formId'];
    ?>
    <div class="omo-calendar-create">
    <div hidden data-omo-calendar-drawer-header data-omo-calendar-drawer-title="<?= $escape($translate('title')) ?>" data-omo-calendar-drawer-description="<?= $escape($options['description']) ?>" data-omo-subdrawer-header data-omo-subdrawer-title="<?= $escape($translate('title')) ?>">
        <button type="submit" form="<?= $escape($formId) ?>" class="generic-action-button generic-action-button--main" data-omo-calendar-drawer-action data-omo-subdrawer-action data-omo-calendar-create-submit><?= $escape($translate('save')) ?></button>
    </div>
    <form id="<?= $escape($formId) ?>" class="generic-drawer-content generic-form-stack" method="post" action="<?= $escape($options['action']) ?>" data-omo-calendar-create-form data-omo-calendar-personal-time-buffers-form<?= !empty($options['external']) ? ' data-omo-calendar-external-event-form' : '' ?>>
        <input type="hidden" name="csrf" value="<?= $escape($options['csrf']) ?>">
        <section class="generic-section generic-section--stack">
            <h3 class="generic-card-title"><?= $escape($options['title']) ?></h3>
            <p class="generic-meta-value"><?= $escape($options['schedule']) ?></p>
            <p class="generic-help-text"><?= $escape($options['hint']) ?></p>
        </section>
        <label class="generic-checkbox"><input type="checkbox" name="time_buffers_enabled" value="1" data-omo-calendar-buffers-toggle<?= $enabled ? ' checked' : '' ?>><?= $escape($translate('enable')) ?></label>
        <div class="generic-form-grid generic-form-grid--pair" data-omo-calendar-buffers-fields<?= $enabled ? '' : ' hidden' ?>>
            <?php foreach (['preparation_minutes' => ['before', $preparation], 'closing_minutes' => ['after', $closing]] as $field => [$key, $value]): ?>
                <label class="generic-form-field"><span class="generic-form-label"><?= $escape($translate($key)) ?></span>
                    <?php commonCalendarRenderTimeBufferSelect($field, $value, $enabled); ?>
                </label>
            <?php endforeach; ?>
        </div>
        <p class="generic-feedback generic-feedback--collapse-empty" data-omo-calendar-create-feedback></p>
    </form>
    </div>
    <?php
}
