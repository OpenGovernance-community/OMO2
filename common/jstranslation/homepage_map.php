<?php
require_once dirname(__DIR__, 2) . '/shared_functions.php';
require_once dirname(__DIR__) . '/translation_bundles.php';

$sourceLang = [
    'current' => ['text' => 'Etat actuel', 'context' => 'Current organization maturity mean in a public map popup.'],
    'desired' => ['text' => 'Ambition', 'context' => 'Desired organization maturity mean in a public map popup.'],
    'maturity' => ['text' => "Maturit\u{00E9} organisationnelle", 'context' => 'Title above the public organization maturity scores and in the chart dialog.'],
    'open' => ['text' => 'Voir le graphique et sa legende', 'context' => 'Accessible action for the organization maturity scores in the map.'],
    'close' => ['text' => 'Fermer', 'context' => 'Accessible action to close the organization maturity chart dialog.'],
    'cluster' => ['text' => '{count} organisations : cliquer pour zoomer', 'context' => 'Tooltip and accessible label for a cluster containing at least two organizations on the public map.'],
];
$locale = translationBundleResolveRequestLocale('lang', translationBundleGetSupportedLocales(), 'fr');
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
$bundle = loadTranslationBundle('homepage_map', $locale, $sourceLang);
$payload = [];
foreach ($sourceLang as $key => $definition) {
    $payload[$key] = t($key, [], $bundle, $sourceLang);
}
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: private, max-age=300');
echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
