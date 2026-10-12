<?php
require_once dirname(__DIR__, 2) . '/shared_functions.php';
require_once dirname(__DIR__) . '/translation_bundles.php';

$sourceLang = [
    'more' => ['text' => 'Autres options', 'context' => 'Accessible label for the application view actions menu.'],
    'fullscreen' => ['text' => "Plein \u{00E9}cran", 'context' => 'Action expanding the application view to the whole screen.'],
    'exitFullscreen' => ['text' => "Quitter le plein \u{00E9}cran", 'context' => 'Action restoring the normal application view.'],
];
$locale = translationBundleResolveRequestLocale('lang', translationBundleGetSupportedLocales(), 'fr');
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
$bundle = loadTranslationBundle('common_panel_view_actions', $locale, $sourceLang);
$payload = [];
foreach ($sourceLang as $key => $definition) {
    $payload[$key] = t($key, [], $bundle, $sourceLang);
}
header('Content-Type: application/json; charset=UTF-8');
// Explicit locale URLs can be reused across page loads, including translated bundles.
$explicitLocale = translationBundleResolveSupportedLocale($_GET['lang'] ?? '', translationBundleGetSupportedLocales());
header($explicitLocale !== '' ? 'Cache-Control: private, max-age=300' : 'Cache-Control: private, no-store');
echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
