<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/shared_functions.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
$respond = static function (int $code, array $data): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
};
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') $respond(405, ['status' => false]);
if ((int)commonGetCurrentUserId() <= 0) $respond(401, ['status' => false]);
$token = (string)($_SESSION['extended_authorities_csrf'] ?? '');
if ($token === '' || !hash_equals($token, (string)($_POST['csrf_token'] ?? ''))) $respond(403, ['status' => false]);
$organizationId = (int)($_POST['organization_id'] ?? 0);
if ($organizationId <= 0 || $organizationId !== (int)($_SESSION['currentOrganization'] ?? 0)) $respond(403, ['status' => false]);
$enabled = ($_POST['enabled'] ?? '') === '1';
if ($enabled && !commonCurrentUserCanUseExtendedAuthorities($organizationId)) $respond(403, ['status' => false]);
$active = commonSetCurrentUserExtendedAuthorities($enabled, $organizationId);
session_write_close();
$respond(200, ['status' => true, 'active' => $active]);
