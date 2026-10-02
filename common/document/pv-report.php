<?php
require_once dirname(__DIR__) . '/translation_bundles.php';

function commonPvReportT(string $key): string
{
    static $lang = null;
    $sourceLang = [
        'pv.report.unhandled' => ['text' => 'Points non traites', 'context' => 'Final list of non-confidential unfinished agenda points in reviewed and validated minutes.'],
        'pv.report.empty' => ['text' => 'Aucun point traite dans ce PV.', 'context' => 'Empty reviewed or validated minutes, with no handled point or public unfinished title.'],
        'pv.report.author' => ['text' => 'Auteur', 'context' => 'Author label in an unfinished agenda point summary.'],
        'pv.report.author_missing' => ['text' => 'Non renseigne', 'context' => 'Fallback when an unfinished agenda point has no author.'],
        'pv.report.duration' => ['text' => 'Duree prevue', 'context' => 'Planned duration label in an unfinished agenda point summary.'],
        'pv.report.priority' => ['text' => 'Priorite', 'context' => 'Accessible label for an unfinished agenda point priority, P1 to P5.'],
        'pv.report.type.information' => ['text' => 'Information', 'context' => 'Accessible label and tooltip of the information agenda point icon.'],
        'pv.report.type.consultation' => ['text' => 'Consultation', 'context' => 'Accessible label and tooltip of the consultation agenda point icon.'],
        'pv.report.type.decision' => ['text' => 'Decision', 'context' => 'Accessible label and tooltip of the decision agenda point icon.'],
        'pv.report.moved_to' => ['text' => 'Deplace vers', 'context' => 'Label before the linked destination minutes name of an unfinished moved agenda point.'],
    ];
    if ($lang === null) {
        $locale = translationBundleResolveRequestLocale('lang', translationBundleGetSupportedLocales(), 'fr');
        $lang = loadTranslationBundle('common_pv_report', $locale, $sourceLang);
    }
    return t($key, [], $lang, $sourceLang);
}

function commonPvReportRenderUnhandledPoints(array $items): string
{
    if (!$items) return '';
    $escape = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    $html = '<section class="generic-section generic-stack generic-stack--compact" data-omo-pv-unhandled-list>'
        . '<h3 class="generic-card-title">' . $escape(commonPvReportT('pv.report.unhandled')) . '</h3>'
        . '<ul class="common-pv-report__list generic-stack generic-stack--compact">';
    foreach ($items as $item) {
        $title = $escape($item['title']);
        $movementHtml = '';
        if (!empty($item['url']) && !empty($item['movementDestinationLabel'])) {
            $movementHtml = '<span class="common-pv-report__movement"><strong>' . $escape(commonPvReportT('pv.report.moved_to')) . '</strong> : '
                . '<a href="' . $escape($item['url']) . '">' . $escape($item['movementDestinationLabel']) . '</a></span>';
        }
        $priority = max(1, min(5, (int)($item['priority'] ?? 3)));
        $pointType = in_array($item['pointType'] ?? '', ['information', 'consultation', 'decision'], true) ? $item['pointType'] : 'information';
        $pointTypeLabel = $escape(commonPvReportT('pv.report.type.' . $pointType));
        $author = trim((string)($item['authorLabel'] ?? ''));
        $duration = $item['desiredDurationMinutes'] ?? null;
        $durationHtml = $duration === null || $duration === '' ? ''
            : '<span><strong>' . $escape(commonPvReportT('pv.report.duration')) . '</strong> : ' . max(0, (int)$duration) . ' min</span>';
        $html .= '<li class="generic-soft-panel generic-soft-panel--stack generic-soft-panel--accent common-pv-report__item common-pv-report__item--p' . $priority . '">'
            . '<div class="common-pv-report__heading">'
            . '<span class="common-pv-report__icon"><img class="common-pv-report__type-icon" src="/omo/assets/images/documents/pv-point-type/' . $pointType . '.png" alt="' . $pointTypeLabel . '" title="' . $pointTypeLabel . '"></span>'
            . '<h4 class="generic-card-title generic-card-title--medium common-pv-report__title">' . $title . '</h4>'
            . '<span class="generic-project-priority generic-project-priority--p' . $priority . '" aria-label="' . $escape(commonPvReportT('pv.report.priority') . ' P' . $priority) . '">P' . $priority . '</span>'
            . '</div><div class="common-pv-report__meta generic-meta generic-meta--compact">'
            . '<span><strong>' . $escape(commonPvReportT('pv.report.author')) . '</strong> : ' . $escape($author !== '' ? $author : commonPvReportT('pv.report.author_missing')) . '</span>'
            . $durationHtml
            . $movementHtml
            . '</div></li>';
    }
    return $html . '</ul></section>';
}
