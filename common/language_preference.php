<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/shared_functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/translation_bundles.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: application/json; charset=UTF-8');

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => false,
        'message' => 'Requete invalide.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$currentUserId = (int)commonGetCurrentUserId();
if ($currentUserId <= 0) {
    http_response_code(401);
    echo json_encode([
        'status' => false,
        'message' => 'Utilisateur non connecte.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$supportedLocales = translationBundleGetSupportedLocales();
$rawLocale = trim((string)($_POST['locale'] ?? ''));
$normalizedLocale = strtolower(str_replace('_', '-', $rawLocale));
$resolvedLocale = $normalizedLocale === 'system'
    ? 'system'
    : translationBundleResolveSupportedLocale($normalizedLocale, $supportedLocales);

if ($resolvedLocale === '') {
    http_response_code(422);
    echo json_encode([
        'status' => false,
        'message' => 'Langue invalide.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$user = new \dbObject\user();
if (!$user->load($currentUserId)) {
    http_response_code(404);
    echo json_encode([
        'status' => false,
        'message' => 'Utilisateur introuvable.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$parameters = json_decode((string)$user->get('parameters'), true);
if (!is_array($parameters)) {
    $parameters = [];
}

if ($resolvedLocale === 'system') {
    unset($parameters['lang'], $parameters['locale']);
} else {
    $parameters['lang'] = $resolvedLocale;
}

$user->set('parameters', $parameters);
$saveResult = $user->save();
if (!is_array($saveResult) || empty($saveResult['status'])) {
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'message' => 'Impossible d enregistrer la langue.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

echo json_encode([
    'status' => true,
    'locale' => $resolvedLocale,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
