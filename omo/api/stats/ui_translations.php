<?php

require_once __DIR__ . '/../../translations.php';

function omoStatsUiSourceLang(): array
{
    static $sourceLang = [
        'feuille1' => ['text' => 'Feuille1', 'context' => 'Interface label in api/stats/edit.php'],
    ];
    return $sourceLang;
}

function omoStatsUiT(string $key, array $variables = []): string
{
    static $bundles = [];
    $sourceLang = omoStatsUiSourceLang();
    $locale = omoGetTranslationLocale();
    $bundle = $bundles[$locale] ??= loadTranslationBundle('omo_stats_ui', $locale, $sourceLang);
    return t($key, $variables, $bundle, $sourceLang);
}
