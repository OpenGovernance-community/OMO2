<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array(
        'status' => false,
        'message' => omoDocumentsParamsT('documents.params.error.method'),
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$currentUserId = (int)commonGetCurrentUserId();
$organizationId = isset($_POST['oid']) ? (int)$_POST['oid'] : (int)($_SESSION['currentOrganization'] ?? 0);

if ($currentUserId <= 0) {
    http_response_code(403);
    echo json_encode(array(
        'status' => false,
        'message' => omoDocumentsParamsT('documents.params.error.login'),
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$organization = new \dbObject\Organization();
if ($organizationId <= 0 || !$organization->load($organizationId)) {
    http_response_code(404);
    echo json_encode(array(
        'status' => false,
        'message' => omoDocumentsParamsT('documents.params.error.organization'),
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$organizationApplication = omoDocumentsParamsGetApplicationLink($organizationId, false);
if (!$organizationApplication) {
    http_response_code(404);
    echo json_encode(array(
        'status' => false,
        'message' => omoDocumentsParamsT('documents.params.error.unavailable'),
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!omoDocumentsParamsCanManage($organizationId, $currentUserId)) {
    http_response_code(403);
    echo json_encode(array(
        'status' => false,
        'message' => omoDocumentsParamsT('documents.params.error.forbidden'),
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$result = omoDocumentsParamsStoreNextcloudConfig($organization, $_POST, true);
if (!is_array($result) || empty($result['status'])) {
    http_response_code(422);
    echo json_encode(array(
        'status' => false,
        'message' => trim((string)($result['text'] ?? omoDocumentsParamsT('documents.params.error.save_failed'))),
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$defaultsResult = omoDocumentsParamsStoreVisibilityDefaults($organization, $_POST);
if (!is_array($defaultsResult) || empty($defaultsResult['status'])) {
    http_response_code(422);
    echo json_encode(array(
        'status' => false,
        'message' => trim((string)($defaultsResult['text'] ?? omoDocumentsParamsT('documents.params.error.save_failed'))),
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

echo json_encode(array(
    'status' => true,
    'message' => trim((string)($defaultsResult['text'] ?? $result['text'] ?? omoDocumentsParamsT('documents.params.feedback.saved'))),
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
