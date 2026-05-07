<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/shared_functions.php';
require_once BASE_PATH . '/common/auth.php';

$cookieName = 'omo_test_cookie';
$cookieValue = 'ok_' . date('Ymd_His');
$expiresAt = time() + (7 * 24 * 60 * 60);

commonSetCookieValue($cookieName, $cookieValue, $expiresAt, false);

$payload = [
    'status' => 'cookie_set',
    'cookie' => [
        'name' => $cookieName,
        'value' => $cookieValue,
        'expires_at' => gmdate(DateTimeInterface::ATOM, $expiresAt),
        'domain_expected' => commonGetCookieDomain(),
        'path' => '/',
        'secure_expected' => commonShouldUseSecureCookies(),
        'http_only' => false,
        'same_site' => 'Lax',
    ],
    'request' => [
        'host' => commonGetRequestHost(),
        'scheme' => commonGetRequestScheme(),
        'request_uri' => (string)($_SERVER['REQUEST_URI'] ?? ''),
    ],
    'headers' => headers_list(),
];

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;
