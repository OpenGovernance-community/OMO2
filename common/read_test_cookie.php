<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/shared_functions.php';
require_once BASE_PATH . '/common/auth.php';

$cookieName = 'omo_test_cookie';
$cookieValue = isset($_COOKIE[$cookieName]) ? (string)$_COOKIE[$cookieName] : '';

$payload = [
    'status' => 'cookie_read',
    'cookie' => [
        'name' => $cookieName,
        'present' => $cookieValue !== '',
        'value' => $cookieValue,
    ],
    'request' => [
        'host' => commonGetRequestHost(),
        'scheme' => commonGetRequestScheme(),
        'request_uri' => (string)($_SERVER['REQUEST_URI'] ?? ''),
        'cookie_domain_expected' => commonGetCookieDomain(),
        'secure_cookies_expected' => commonShouldUseSecureCookies(),
    ],
];

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;
