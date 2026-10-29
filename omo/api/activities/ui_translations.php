<?php

require_once __DIR__ . '/../../translations.php';

function omoActivitiesUiSourceLang(): array
{
    static $sourceLang = [
        'mois' => ['text' => 'Mois ', 'context' => 'Validation or action feedback in api/activities/shared.php'],
    ];
    return $sourceLang;
}

function omoActivitiesUiT(string $key, array $variables = []): string
{
    static $bundles = [];
    $sourceLang = omoActivitiesUiSourceLang();
    $locale = omoGetTranslationLocale();
    $bundle = $bundles[$locale] ??= loadTranslationBundle('omo_activities_ui', $locale, $sourceLang);
    return t($key, $variables, $bundle, $sourceLang);
}
