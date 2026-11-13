<?php

require_once __DIR__ . '/../translation_bundles.php';

function commonChoiceParticipationSummary(int $respondents, int $incomplete): string
{
    if ($incomplete <= 0) return (string)$respondents;

    static $sourceLang = [
        'respondents.incomplete' => [
            'one' => '{total}, dont {count} incomplet',
            'other' => '{total}, dont {count} incomplets',
            'context' => 'Distinct decision respondents, including those missing a submitted response in at least one active evaluation block.',
        ],
    ];
    static $bundles = [];
    $locale = translationBundleResolveRequestLocale();
    $bundle = $bundles[$locale] ??= loadTranslationBundle('common_choice_participation_summary', $locale, $sourceLang);
    return t('respondents.incomplete', ['total' => $respondents, 'count' => $incomplete], $bundle, $sourceLang);
}
