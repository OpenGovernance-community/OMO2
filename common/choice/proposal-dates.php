<?php

function omoDecisionProposalCalendarIcon(): string
{
    return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M16 3v4M8 3v4M3 11h18"/></svg>';
}

function omoDecisionRenderProposalDates(array $item, callable $escape, bool $editable = true, string $prefix = 'proposal_', bool $array = true, bool $enabled = true): string
{
    $timezone = $item['timezone'] ?? date_default_timezone_get();
    $disabled = $editable ? '' : ' disabled';
    $html = '<div class="generic-soft-panel generic-form-stack generic-form-stack--compact choice-proposal-dates" data-omo-proposal-dates' . ($enabled ? '' : ' hidden') . '>'
        . '<div class="choice-proposal-dates__header"><span class="choice-proposal-dates__heading generic-form-label">' . omoDecisionProposalCalendarIcon() . $escape(omoDecisionProposalT('decisions.proposals.dates.range')) . '</span>'
        . '<div class="choice-proposal-dates__tools"><details class="choice-proposal-dates__timezone"><summary class="generic-meta" data-omo-proposal-timezone-label>' . $escape($timezone) . '</summary>'
        . '<label class="generic-form-field"><span class="generic-form-label">' . $escape(omoDecisionProposalT('decisions.proposals.dates.timezone')) . '</span><input class="generic-form-control generic-form-control--compact" type="text" data-omo-proposal-timezone name="' . $escape($prefix . 'timezone' . ($array ? '[]' : '')) . '" value="' . $escape($timezone) . '"' . $disabled . '></label></details>'
        . '<button type="button" class="generic-action-button generic-action-button--quiet-icon" data-omo-proposal-date-clear title="' . $escape(omoDecisionProposalT('decisions.proposals.dates.clear')) . '" aria-label="' . $escape(omoDecisionProposalT('decisions.proposals.dates.clear')) . '"' . (empty($item['start_at']) && empty($item['end_at']) ? ' hidden' : '') . $disabled . '><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 6 12 12M6 18 18 6"/></svg></button></div></div>'
        . '<div class="generic-form-grid generic-form-grid--pair generic-form-grid--compact">';
    foreach (['start_at' => 'start', 'end_at' => 'end'] as $field => $key) {
        $value = $item[$field] ?? null;
        $value = $value instanceof DateTimeInterface ? DateTimeImmutable::createFromInterface($value)->setTimezone(new DateTimeZone($timezone))->format('Y-m-d\TH:i') : str_replace(' ', 'T', substr((string)$value, 0, 16));
        $html .= '<label class="generic-form-field"><span class="generic-form-label">' . $escape(omoDecisionProposalT('decisions.proposals.dates.' . $key)) . '</span><input class="generic-form-control generic-form-control--compact" type="datetime-local" data-omo-proposal-date-' . $key . ' name="' . $escape($prefix . $field . ($array ? '[]' : '')) . '" value="' . $escape($value) . '"' . $disabled . '></label>';
    }
    return $html . '</div></div>';
}

function omoDecisionRenderProposalCalendarForItem(array $item, array $context, callable $escape): string
{
    $proposal = new \dbObject\DecisionProposal();
    if (empty($item['id']) || !$proposal->load((int)$item['id'])) return '';
    return omoDecisionRenderProposalCalendar($proposal, $context, $escape);
}

function omoDecisionRenderProposalCalendar(\dbObject\DecisionProposal $proposal, array $context, callable $escape): string
{
    $calendar = $proposal->getCalendarData();
    $group = $proposal->getDecisionGroup();
    $visible = $calendar['dateLabel'] !== '' && (!$group || $group->areProposalDatesEnabled());
    $status = $calendar['calendarStatus'] ?: 'option';
    $html = '<div class="choice-proposal-calendar generic-soft-panel" data-omo-proposal-calendar data-proposal-calendar-id="' . (int)$proposal->getId() . '" data-calendar-status="' . $escape($status) . '"' . ($visible ? '' : ' hidden') . '>';
    if (!$visible) return $html . '</div>';
    $zone = new DateTimeZone($calendar['timezone']);
    $start = new DateTimeImmutable($calendar['startAt'], $zone);
    $end = new DateTimeImmutable($calendar['endAt'], $zone);
    $locale = function_exists('omoGetTranslationLocale') ? omoGetTranslationLocale() : 'fr';
    $format = static function (DateTimeImmutable $date, string $pattern, string $fallback) use ($locale, $zone): string {
        if (class_exists(IntlDateFormatter::class)) {
            $formatter = new IntlDateFormatter($locale, IntlDateFormatter::NONE, IntlDateFormatter::NONE, $zone->getName(), null, $pattern);
            $formatted = $formatter->format($date);
            if ($formatted !== false) return $formatted;
        }
        return $date->format($fallback);
    };
    $sameDay = $start->format('Y-m-d') === $end->format('Y-m-d');
    $dateLabel = $sameDay ? $format($start, 'EEEE d MMMM yyyy', 'd.m.Y') : $format($start, 'd MMM yyyy', 'd.m.Y') . ' - ' . $format($end, 'd MMM yyyy', 'd.m.Y');
    $badge = ['confirmed' => 'success', 'cancelled' => 'muted', 'option' => 'warning'][$status] ?? 'warning';
    $html .= '<div class="choice-proposal-calendar__stamp" aria-hidden="true"><span>' . $escape($format($start, 'MMM', 'm')) . '</span><strong>' . $start->format('d') . '</strong></div>'
        . '<div class="choice-proposal-calendar__copy"><strong class="choice-proposal-calendar__date" data-omo-proposal-calendar-date>' . $escape($dateLabel) . '</strong><div class="choice-proposal-calendar__time"><span>' . $start->format('H:i') . ' &ndash; ' . $end->format('H:i') . '</span><span class="generic-meta">' . $escape($calendar['timezone']) . '</span></div></div>'
        . '<div class="choice-proposal-calendar__aside"><span class="generic-badge generic-badge--' . $badge . '" data-omo-proposal-calendar-status>' . $escape(omoDecisionProposalT('decisions.proposals.dates.' . $status)) . '</span>';
    $decision = $context['decision'] ?? null;
    if (!empty($context['canManage']) && $decision && in_array($decision->get('status'), ['results', 'archived'], true)) {
        $payload = omoDecisionModuleEncodeJsonPayload(omoDecisionBuildProposalDiscussionContextPayload($context));
        $html .= '<div class="choice-proposal-calendar__actions">';
        foreach (['confirmed' => 'm5 12 4 4L19 6', 'cancelled' => 'm6 6 12 12M6 18 18 6'] as $action => $path) {
            $label = omoDecisionProposalT('decisions.proposals.dates.' . ($action === 'confirmed' ? 'confirm' : 'cancel'));
            $selected = $action === $status;
            $html .= '<button type="button" class="generic-action-button generic-action-button--icon-only' . ($action === 'cancelled' ? ' generic-action-button--secondary' : '') . '" data-omo-proposal-calendar-action="' . $action . '" data-proposal-id="' . (int)$proposal->getId() . '" data-proposal-context="' . $escape($payload) . '" title="' . $escape($label) . '" aria-label="' . $escape($label) . '" aria-pressed="' . ($selected ? 'true' : 'false') . '"' . ($selected ? ' disabled' : '') . '><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="' . $path . '"/></svg></button>';
        }
        $html .= '</div>';
    }
    return $html . '</div></div>';
}
