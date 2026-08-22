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
$organization = new \dbObject\Organization();

if ($currentUserId <= 0) {
    http_response_code(403);
    echo json_encode(array(
        'status' => false,
        'message' => omoDocumentsParamsT('documents.params.error.login'),
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($organizationId <= 0 || !$organization->load($organizationId)) {
    http_response_code(404);
    echo json_encode(array(
        'status' => false,
        'message' => omoDocumentsParamsT('documents.params.error.organization'),
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

$storedConfig = omoDocumentsParamsGetNextcloudConfig($organization, null, true);
$postedBaseUrl = trim((string)($_POST['nextcloud_base_url'] ?? ''));
$postedUsername = trim((string)($_POST['nextcloud_username'] ?? ''));
$postedPassword = trim((string)($_POST['nextcloud_app_password'] ?? ''));
$testConfig = omoDocumentsParamsNormalizeNextcloudConfig(array(
    'baseUrl' => $postedBaseUrl,
    'username' => $postedUsername,
    'appPassword' => $postedPassword !== ''
        ? $postedPassword
        : (
            $postedBaseUrl === (string)$storedConfig['baseUrl']
            && $postedUsername === (string)$storedConfig['username']
                ? (string)$storedConfig['appPassword']
                : ''
        ),
    'folder' => trim((string)($_POST['nextcloud_folder'] ?? '')),
));

$result = $organization->testNextcloudDocumentStorageConnection($testConfig);
$status = is_array($result) && !empty($result['status']);
if (!$status) {
    http_response_code(422);
}

echo json_encode(array(
    'status' => $status,
    'message' => trim((string)($result['text'] ?? omoDocumentsParamsT('documents.params.feedback.nextcloud_test_invalid'))),
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
