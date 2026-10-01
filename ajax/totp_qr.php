<?php

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';

if (!checklogin()) {
    http_response_code(401);
    exit;
}

$pending = $_SESSION['pending_totp_setup'] ?? null;
$userId = (int)commonGetCurrentUserId();
$nonce = (string)($_GET['nonce'] ?? '');
if (!is_array($pending)
    || (int)($pending['user_id'] ?? 0) !== $userId
    || (int)($pending['created_at'] ?? 0) < (time() - 900)
    || !is_string($pending['secret'] ?? null)
    || !is_string($pending['qr_nonce'] ?? null)
    || !hash_equals($pending['qr_nonce'], $nonce)) {
    http_response_code(404);
    exit;
}

header('Cache-Control: no-store, private');
header('Pragma: no-cache');
header('Content-Type: image/png');

$libraryPath = dirname(__DIR__) . '/library/phpqrcode/qrlib.php';
if (!is_file($libraryPath)) {
    http_response_code(500);
    exit;
}

require_once $libraryPath;
\QRcode::png(
    commonTotpBuildProvisioningUri($pending['secret'], (string)($pending['email'] ?? '')),
    null,
    \QR_ECLEVEL_M,
    5,
    1
);
