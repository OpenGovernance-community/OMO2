<?php

function omoRuleDateRenderInput(string $name, $value): void
{
    static $sourceLang = [
        'format' => ['text' => 'Format : AAAA-MM-JJ', 'context' => 'Rule date keyboard input format'],
        'calendar' => ['text' => 'Choisir dans le calendrier', 'context' => 'Rule date calendar picker'],
        'invalid' => ['text' => 'Saisissez une date valide au format AAAA-MM-JJ.', 'context' => 'Invalid rule date keyboard input'],
    ];
    static $bundle = null;
    $bundle ??= omoLoadTranslationBundle('omo_rule_dates', $sourceLang);
    $escape = static fn ($text): string => htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
    $value = $value instanceof DateTimeInterface ? $value->format('Y-m-d') : (string)$value;
    ?>
    <span class="generic-date-input" data-generic-date-input data-date-invalid="<?= $escape(t('invalid', [], $bundle, $sourceLang)) ?>">
        <input class="generic-form-control" type="text" name="<?= $escape($name) ?>" value="<?= $escape($value) ?>" placeholder="AAAA-MM-JJ" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" maxlength="10" title="<?= $escape(t('format', [], $bundle, $sourceLang)) ?>" required data-generic-date-text>
        <span class="generic-date-input__picker" title="<?= $escape(t('calendar', [], $bundle, $sourceLang)) ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18"/></svg>
            <input type="date" value="<?= $escape($value) ?>" aria-label="<?= $escape(t('calendar', [], $bundle, $sourceLang)) ?>" data-generic-date-picker>
        </span>
    </span>
    <small class="generic-help-text"><?= $escape(t('format', [], $bundle, $sourceLang)) ?></small>
    <?php
}
