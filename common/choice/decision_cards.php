<?php

if (!function_exists('commonChoiceDecisionCardsEscape')) {
    function commonChoiceDecisionCardsEscape($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('commonChoiceDecisionCardsResolveDataAttribute')) {
    function commonChoiceDecisionCardsResolveDataAttribute($value, $fallback)
    {
        $value = trim((string)$value);
        if ($value === '' || !preg_match('/^data-[a-z0-9_-]+$/', $value)) {
            return $fallback;
        }

        return $value;
    }
}

if (!function_exists('commonChoiceRenderDecisionCard')) {
    function commonChoiceRenderDecisionCard(array $item, array $options = [])
    {
        $escape = $options['escape'] ?? null;
        if (!is_callable($escape)) {
            $escape = 'commonChoiceDecisionCardsEscape';
        }

        $fallbackTitle = trim((string)($options['fallbackTitle'] ?? 'Decision'));
        $openUrlAttribute = commonChoiceDecisionCardsResolveDataAttribute($options['openUrlAttribute'] ?? 'data-open-url', 'data-open-url');
        $openTitleAttribute = commonChoiceDecisionCardsResolveDataAttribute($options['openTitleAttribute'] ?? 'data-open-title', 'data-open-title');
        $preserveDescriptionBreaks = !empty($options['preserveDescriptionBreaks']);

        $title = trim((string)($item['title'] ?? ''));
        if ($title === '') {
            $title = $fallbackTitle;
        }

        $description = trim((string)($item['description'] ?? ''));
        $statusLabel = trim((string)($item['statusLabel'] ?? ''));
        $owner = is_array($item['owner'] ?? null) ? $item['owner'] : [];
        $ownerName = trim((string)($owner['displayName'] ?? ''));
        $ownerInitials = trim((string)($owner['initials'] ?? 'P'));
        if ($ownerInitials === '') {
            $ownerInitials = 'P';
        }
        $ownerPhotoUrl = trim((string)($owner['photoUrl'] ?? ''));
        $badges = is_array($item['badges'] ?? null) ? $item['badges'] : [];
        $actions = is_array($item['actions'] ?? null) ? $item['actions'] : [];
        $metaItems = is_array($item['metaItems'] ?? null) ? $item['metaItems'] : [];
        $stats = is_array($item['stats'] ?? null) ? $item['stats'] : [];
        $dateItems = is_array($item['dateItems'] ?? null) ? $item['dateItems'] : [];

        $ownerAvatarHtml = $ownerPhotoUrl !== ''
            ? '<img src="' . $escape($ownerPhotoUrl) . '" alt="' . $escape($ownerName !== '' ? $ownerName : $ownerInitials) . '" class="omo-decisions-card__owner-photo">'
            : '<span class="omo-decisions-card__owner-placeholder">' . $escape($ownerInitials) . '</span>';

        $badgesHtml = '';
        if (!empty($badges)) {
            $badgesHtml = '<span class="omo-decisions-card__badges">';
            foreach ($badges as $badge) {
                $badge = trim((string)$badge);
                if ($badge === '') {
                    continue;
                }
                $badgesHtml .= '<span class="omo-decisions-card__badge">' . $escape($badge) . '</span>';
            }
            $badgesHtml .= '</span>';
        }

        $actionsHtml = '<div class="omo-decisions-card__actions">';
        foreach ($actions as $action) {
            if (!is_array($action)) {
                continue;
            }

            $label = trim((string)($action['label'] ?? ''));
            $url = trim((string)($action['url'] ?? ''));
            if ($label === '' || $url === '') {
                continue;
            }

            $variant = trim((string)($action['variant'] ?? 'secondary'));
            if ($variant === '') {
                $variant = 'secondary';
            }

            $buttonTitle = trim((string)($action['title'] ?? $title));

            $actionsHtml .= '<button type="button" class="generic-action-button generic-action-button--' . $escape($variant) . ' omo-decisions-card__action" '
                . $openUrlAttribute . '="' . $escape($url) . '" '
                . $openTitleAttribute . '="' . $escape($buttonTitle !== '' ? $buttonTitle : $title) . '">'
                . $escape($label)
                . '</button>';
        }
        $actionsHtml .= '</div>';

        $metaHtml = '';
        foreach ($metaItems as $metaItem) {
            if (!is_array($metaItem)) {
                continue;
            }

            $label = trim((string)($metaItem['label'] ?? ''));
            $value = trim((string)($metaItem['value'] ?? ''));
            if ($label === '' || $value === '') {
                continue;
            }

            $metaHtml .= '<span class="omo-decisions-card__meta-item"><strong>' . $escape($label) . '</strong><span>' . $escape($value) . '</span></span>';
        }

        $statsHtml = '';
        foreach ($stats as $stat) {
            if (!is_array($stat)) {
                continue;
            }

            $label = trim((string)($stat['label'] ?? ''));
            $value = trim((string)($stat['value'] ?? ''));
            if ($label === '' || $value === '') {
                continue;
            }

            $statsHtml .= '<span class="omo-decisions-card__stat"><strong>' . $escape($value) . '</strong><span>' . $escape($label) . '</span></span>';
        }

        $dateHtml = '';
        foreach ($dateItems as $dateItem) {
            if (!is_array($dateItem)) {
                continue;
            }

            $label = trim((string)($dateItem['label'] ?? ''));
            $value = trim((string)($dateItem['value'] ?? ''));
            if ($label === '' || $value === '') {
                continue;
            }

            $dateHtml .= '<span class="omo-decisions-card__date"><strong>' . $escape($label) . '</strong><span>' . $escape($value) . '</span></span>';
        }

        if ($preserveDescriptionBreaks && $description !== '') {
            $description = nl2br($escape($description));
        } else {
            $description = $description !== '' ? $escape($description) : '';
        }

        return '<article class="generic-section generic-accordion generic-accordion--card generic-accordion--collapsible is-collapsed omo-decisions-card" data-generic-accordion="1">'
            . '<div class="omo-decisions-card__header generic-accordion__header">'
                . '<button type="button" class="omo-decisions-card__summary" data-generic-accordion-toggle aria-expanded="false">'
                    . '<span class="omo-decisions-card__owner-avatar">' . $ownerAvatarHtml . '</span>'
                    . '<span class="omo-decisions-card__summary-copy">'
                        . '<span class="omo-decisions-card__summary-top">'
                            . '<span class="omo-decisions-card__title">' . $escape($title) . '</span>'
                            . ($statusLabel !== '' ? '<span class="omo-decisions-card__status">' . $escape($statusLabel) . '</span>' : '')
                        . '</span>'
                        . '<span class="omo-decisions-card__summary-bottom">'
                            . ($ownerName !== '' ? '<span class="omo-decisions-card__owner-name">' . $escape($ownerName) . '</span>' : '')
                            . $badgesHtml
                        . '</span>'
                    . '</span>'
                    . '<span class="generic-accordion__toggle" aria-hidden="true">&#9662;</span>'
                . '</button>'
                . $actionsHtml
            . '</div>'
            . '<div class="omo-decisions-card__content generic-accordion__content">'
                . ($description !== '' ? '<p class="omo-decisions-card__description">' . $description . '</p>' : '')
                . ($metaHtml !== '' ? '<div class="omo-decisions-card__meta">' . $metaHtml . '</div>' : '')
                . ($statsHtml !== '' ? '<div class="omo-decisions-card__stats">' . $statsHtml . '</div>' : '')
                . ($dateHtml !== '' ? '<div class="omo-decisions-card__dates">' . $dateHtml . '</div>' : '')
            . '</div>'
        . '</article>';
    }
}
