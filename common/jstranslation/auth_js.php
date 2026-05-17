<?php

require_once dirname(__DIR__, 2) . '/shared_functions.php';
require_once dirname(__DIR__) . '/auth.php';

$sourceLang = commonGetAuthJsSourceLang();
$bundle = commonAuthLoadBundle('common_auth_js', $sourceLang);
$payload = [];

foreach (array_keys($sourceLang) as $key) {
    $payload[$key] = commonAuthT($key, [], $bundle, $sourceLang);
}

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
