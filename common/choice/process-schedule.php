<?php

use dbObject\DecisionProcess;

function omoDecisionScheduleGetSourceLang(): array
{
    return [
        'decisions.schedule.consultation' => ['text' => "Definir une phase d'elaboration", 'context' => 'Checkbox opening the dates of the proposal and discussion phase.'],
        'decisions.schedule.consultation_help' => ['text' => "La phase d'elaboration precede le vote. Elle permet de recueillir les propositions et d'en debattre avant leur evaluation.", 'context' => 'Explanation of the elaboration phase next to its scheduling checkbox.'],
        'decisions.schedule.evaluation' => ['text' => "Definir une phase d'evaluation", 'context' => 'Checkbox opening the dates of the voting phase.'],
        'decisions.schedule.evaluation_help' => ['text' => "La phase d'evaluation est la periode pendant laquelle les participants votent sur les propositions selon la methode du scrutin.", 'context' => 'Explanation of the evaluation phase next to its scheduling checkbox.'],
        'decisions.schedule.start' => ['text' => 'Debut', 'context' => 'Label for the start date and time of a decision phase.'],
        'decisions.schedule.end' => ['text' => 'Fin', 'context' => 'Label for the end date and time of a decision phase.'],
    ];
}

function omoDecisionRenderProcessSchedule(?DecisionProcess $decision, bool $canEditStartDates, bool $canEditEndDates, array $lang, array $sourceLang, callable $escape, bool $consultationOnly = false, bool $readonlyStart = false): string
{
    ob_start();
    foreach ($consultationOnly ? ['consultation'] : ['consultation', 'evaluation'] as $phase) {
        $values = [];
        foreach (['start', 'end'] as $boundary) {
            $value = $decision ? DecisionProcess::normalizeDateTimeValue($decision->get($phase . '_' . $boundary . '_at')) : null;
            $values[$boundary] = $value instanceof DateTimeInterface ? $value->format('Y-m-d\TH:i') : '';
        }
        $checked = $values['start'] !== '' || $values['end'] !== '';
        $label = t('decisions.schedule.' . $phase, [], $lang, $sourceLang);
        $help = t('decisions.schedule.' . $phase . '_help', [], $lang, $sourceLang);
        ?>
        <div class="generic-accordion generic-accordion--collapsible generic-accordion--action-only generic-accordion--inline<?= $checked ? '' : ' is-collapsed' ?>" data-generic-accordion data-omo-decision-schedule-phase="<?= $escape($phase) ?>">
            <div class="generic-accordion__header">
                <div class="generic-heading-with-help">
                    <label class="generic-checkbox"><input type="checkbox" data-generic-accordion-toggle<?= $checked ? ' checked' : '' ?>><span><?= $escape($label) ?></span></label>
                    <details class="generic-context-help generic-context-help--compact" data-generic-context-help-hover><summary aria-label="<?= $escape($help) ?>">?</summary><div class="generic-context-help__content"><?= $escape($help) ?></div></details>
                </div>
            </div>
            <div class="generic-accordion__content generic-form-grid generic-form-grid--pair generic-form-grid--compact">
                <?php foreach (['start', 'end'] as $boundary): ?>
                <label class="generic-form-field">
                    <span class="generic-form-label"><?= $escape(t('decisions.schedule.' . $boundary, [], $lang, $sourceLang)) ?></span>
                    <input type="datetime-local" name="<?= $escape($phase . '_' . $boundary . '_at') ?>" class="generic-form-control generic-form-control--compact" value="<?= $escape($values[$boundary]) ?>"<?= ($boundary === 'start' ? $canEditStartDates : $canEditEndDates) ? '' : ($boundary === 'start' && $readonlyStart ? ' readonly' : ' disabled') ?>>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
    return ob_get_clean();
}
