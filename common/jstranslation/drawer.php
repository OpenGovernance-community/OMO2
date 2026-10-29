<?php
require_once dirname(__DIR__, 2) . '/shared_functions.php';
require_once dirname(__DIR__) . '/translation_bundles.php';

$sourceLang = [
    'discard' => [
        'text' => "Terminer l'édition en cours ? Les données non enregistrées seront perdues.",
        'context' => 'Confirmation before closing an object creation or editing drawer with unsaved data. Cancel keeps the editor open.',
    ],
];
$locale = translationBundleResolveRequestLocale('lang', translationBundleGetSupportedLocales(), 'fr');
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
$bundle = loadTranslationBundle('common_drawer', $locale, $sourceLang);
$payload = [];
foreach ($sourceLang as $key => $definition) {
    $payload[$key] = t($key, [], $bundle, $sourceLang);
}
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: private, max-age=300');
echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
