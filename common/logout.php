<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/shared_functions.php';
require_once BASE_PATH . '/common/auth.php';

commonLogoutUser();

$accept = $_SERVER['HTTP_ACCEPT'] ?? '';
if (stripos($accept, 'application/json') !== false) {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['status' => 'ok']);
    exit;
}

$returnTo = commonNormalizeLocalPath($_GET['return_to'] ?? '/', '/');
header('Location: ' . $returnTo);
exit;

