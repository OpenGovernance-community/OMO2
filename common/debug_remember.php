<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/shared_functions.php';
require_once BASE_PATH . '/common/auth.php';

function commonMaskDebugToken($value)
{
    $value = (string)$value;
    $length = strlen($value);
    if ($length <= 8) {
        return $length > 0 ? str_repeat('*', $length) : '';
    }

    return substr($value, 0, 4) . str_repeat('*', max(0, $length - 8)) . substr($value, -4);
}

$rememberCookieName = commonGetRememberCookieName();
$rememberCookie = commonGetRememberCookieValue();
$legacyRememberCookie = isset($_COOKIE['remember_token']) ? (string)$_COOKIE['remember_token'] : '';
$rememberRecord = false;
if ($rememberCookie !== '') {
    $rememberRecord = \dbObject\UserRemember::findValidByToken($rememberCookie);
}

$sessionUserBefore = (int)($_SESSION['currentUser'] ?? 0);
$restoredUserId = commonRestoreRememberedUser();
$sessionUserAfter = (int)($_SESSION['currentUser'] ?? 0);

$headers = headers_list();
$setCookieHeaders = [];
foreach ($headers as $headerLine) {
    if (stripos($headerLine, 'Set-Cookie:') === 0) {
        $setCookieHeaders[] = $headerLine;
    }
}

$payload = [
    'request' => [
        'host' => commonGetRequestHost(),
        'scheme' => commonGetRequestScheme(),
        'https' => (string)($_SERVER['HTTPS'] ?? ''),
        'server_port' => (string)($_SERVER['SERVER_PORT'] ?? ''),
        'x_forwarded_proto' => (string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''),
        'request_uri' => (string)($_SERVER['REQUEST_URI'] ?? ''),
        'cookie_domain_expected' => commonGetCookieDomain(),
        'secure_cookies_expected' => commonShouldUseSecureCookies(),
        'remember_cookie_name_expected' => $rememberCookieName,
    ],
    'session' => [
        'session_id' => session_id(),
        'session_name' => session_name(),
        'current_user_before_restore' => $sessionUserBefore,
        'current_user_after_restore' => $sessionUserAfter,
        'restored_user_id' => (int)$restoredUserId,
    ],
    'remember_cookie' => [
        'name' => $rememberCookieName,
        'present' => $rememberCookie !== '',
        'length' => strlen($rememberCookie),
        'masked' => commonMaskDebugToken($rememberCookie),
    ],
    'legacy_remember_cookie' => [
        'name' => 'remember_token',
        'present' => $legacyRememberCookie !== '',
        'length' => strlen($legacyRememberCookie),
        'masked' => commonMaskDebugToken($legacyRememberCookie),
    ],
    'remember_record' => [
        'found' => $rememberRecord ? true : false,
        'user_id' => $rememberRecord ? (int)$rememberRecord->get('IDuser') : 0,
        'expires_at' => ($rememberRecord && $rememberRecord->get('expires_at') instanceof DateTimeInterface)
            ? $rememberRecord->get('expires_at')->format(DateTimeInterface::ATOM)
            : null,
        'browser' => $rememberRecord ? (string)$rememberRecord->get('browser') : '',
        'os' => $rememberRecord ? (string)$rememberRecord->get('os') : '',
        'ip' => $rememberRecord ? (string)$rememberRecord->get('ip') : '',
    ],
    'legacy_cookies' => [
        'currentUser_present' => isset($_COOKIE['currentUser']),
        'currentCode_present' => isset($_COOKIE['currentCode']),
    ],
    'response' => [
        'set_cookie_headers' => $setCookieHeaders,
    ],
];

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;
