<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/shared_functions.php';
require_once __DIR__ . '/auth.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$returnTo = commonNormalizeLocalPath($_POST['return_to'] ?? ($_SERVER['HTTP_REFERER'] ?? '/'), '/');
$isAjax = function_exists('commonIsAjaxJsonRequest') ? commonIsAjaxJsonRequest() : false;
$organizationId = (int)($_POST['organization_id'] ?? ($_SESSION['currentOrganization'] ?? 0));

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    if ($isAjax) {
        http_response_code(405);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'status' => false,
            'message' => 'Requete invalide.',
            'redirect_to' => $returnTo,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    header('Location: ' . $returnTo);
    exit;
}

if ((int)commonGetCurrentUserId() <= 0) {
    if ($isAjax) {
        http_response_code(401);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'status' => false,
            'message' => 'Utilisateur non connecte.',
            'redirect_to' => $returnTo,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    header('Location: ' . $returnTo);
    exit;
}

if (!commonCurrentUserCanUseAdminMode($organizationId)) {
    if ($isAjax) {
        http_response_code(403);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'status' => false,
            'message' => 'Mode admin indisponible.',
            'redirect_to' => $returnTo,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    header('Location: ' . $returnTo);
    exit;
}

$enabled = !empty($_POST['enabled']);
$active = commonSetCurrentUserAdminMode($enabled, $organizationId);
session_write_close();

if ($isAjax) {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'status' => true,
        'active' => $active,
        'redirect_to' => $returnTo,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

header('Location: ' . $returnTo);
exit;
