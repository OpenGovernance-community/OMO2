<?php
require_once dirname(__DIR__, 2) . '/shared_functions.php';
require_once dirname(__DIR__) . '/translation_bundles.php';

$sourceLang = [
    'count' => ['text' => 'Référence {current} sur {total}', 'context' => 'Position of the selected personal reference in meeting minutes. Keep {current} and {total}.'],
    'previous' => ['text' => 'Référence précédente', 'context' => 'Scroll up to the previous personal reference in meeting minutes.'],
    'next' => ['text' => 'Référence suivante', 'context' => 'Scroll down to the next personal reference in meeting minutes.'],
    'collapse' => ['text' => 'Glisser pour déplacer, cliquer pour réduire', 'context' => 'Handle tooltip and accessible name of the expanded personal reference navigator.'],
    'expand' => ['text' => 'Glisser pour déplacer, cliquer pour déployer', 'context' => 'Handle tooltip and accessible name of the minimized personal reference navigator.'],
];
$locale = translationBundleResolveRequestLocale('lang', translationBundleGetSupportedLocales(), 'fr');
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
$lang = loadTranslationBundle('common_pv_references', $locale, $sourceLang);
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: private, no-store');
echo json_encode([
    'position' => t('count', [], $lang, $sourceLang),
    'previous' => t('previous', [], $lang, $sourceLang),
    'next' => t('next', [], $lang, $sourceLang),
    'collapse' => t('collapse', [], $lang, $sourceLang),
    'expand' => t('expand', [], $lang, $sourceLang),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
