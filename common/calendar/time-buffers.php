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
