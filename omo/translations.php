<?php

require_once dirname(__DIR__) . '/common/translation_bundles.php';

function omoGetTranslationLocale()
{
    return translationBundleResolveRequestLocale('lang', translationBundleGetSupportedLocales(), 'fr');
}

function omoLoadTranslationBundle(string $bundleKey, array $sourceLang): array
{
    return loadTranslationBundle($bundleKey, omoGetTranslationLocale(), $sourceLang);
}

?>
