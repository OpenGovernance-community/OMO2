<?php

function commonMeetingRenderSteps(array $labels, int $activeStep, bool $completed, string $ariaLabel): string
{
    $escape = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    $check = '<svg class="meeting-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m5 12 4 4L19 6"/></svg>';
    $html = '<ol class="meeting-steps" aria-label="' . $escape($ariaLabel) . '">';
    foreach (array_values($labels) as $index => $label) {
        $step = $index + 1;
        $isComplete = $completed || $step < $activeStep;
        $html .= '<li' . ($step === $activeStep ? ' aria-current="step"' : '')
            . ' data-complete="' . ($isComplete ? 'true' : 'false') . '">'
            . '<span class="meeting-steps__number">' . ($isComplete ? $check : $step) . '</span>'
            . '<span>' . $escape((string)$label) . '</span></li>';
    }
    return $html . '</ol>';
}
