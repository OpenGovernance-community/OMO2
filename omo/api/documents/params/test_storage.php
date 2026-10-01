<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array('status' => false, 'message' => omoDocumentsParamsT('documents.params.error.method')), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$currentUserId = (int)commonGetCurrentUserId();
$organizationId = isset($_POST['oid']) ? (int)$_POST['oid'] : (int)($_SESSION['currentOrganization'] ?? 0);
$organization = new \dbObject\Organization();
if ($currentUserId <= 0 || $organizationId <= 0 || !$organization->load($organizationId) || !omoDocumentsParamsCanManage($organizationId, $currentUserId)) {
    http_response_code(403);
    echo json_encode(array('status' => false, 'message' => omoDocumentsParamsT('documents.params.error.forbidden')), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$storedConfig = omoDocumentsParamsGetDocumentStorageConfig($organization, null);
$storageType = omoDocumentsParamsNormalizeStorageType($_POST['storage_type'] ?? '');
$appPassword = trim((string)($_POST[$storageType === 'kdrive' ? 'kdrive_app_password' : 'nextcloud_app_password'] ?? ''));
if ($appPassword === '' && ($storedConfig['type'] ?? '') === $storageType) {
    $appPassword = (string)($storedConfig['appPassword'] ?? '');
}

if ($storageType === 'kdrive') {
    $config = omoDocumentsParamsNormalizeDocumentStorageConfig(array(
        'driveId' => trim((string)($_POST['kdrive_id'] ?? '')),
        'username' => trim((string)($_POST['kdrive_username'] ?? '')),
        'appPassword' => $appPassword,
        'folder' => trim((string)($_POST['kdrive_folder'] ?? '')),
    ), 'kdrive');
} else {
    $config = omoDocumentsParamsNormalizeDocumentStorageConfig(array(
        'baseUrl' => trim((string)($_POST['nextcloud_base_url'] ?? '')),
        'username' => trim((string)($_POST['nextcloud_username'] ?? '')),
        'appPassword' => $appPassword,
        'folder' => trim((string)($_POST['nextcloud_folder'] ?? '')),
    ), 'nextcloud');
}

$result = $organization->testDocumentStorageConnection($config);
$status = is_array($result) && !empty($result['status']);
echo json_encode(array(
    'status' => $status,
    'message' => trim((string)($result['text'] ?? omoDocumentsParamsT('documents.params.feedback.storage_test_invalid'))),
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
