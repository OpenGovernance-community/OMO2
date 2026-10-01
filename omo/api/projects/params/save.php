<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

header('Content-Type: application/json; charset=UTF-8');
if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => false, 'message' => omoProjectsParamsT('projects.params.error.method')]);
    exit;
}

$userId = (int)commonGetCurrentUserId();
$organizationId = isset($_POST['oid']) ? (int)$_POST['oid'] : (int)($_SESSION['currentOrganization'] ?? 0);
$organization = new \dbObject\Organization();
if ($userId <= 0) {
    http_response_code(403);
    echo json_encode(['status' => false, 'message' => omoProjectsParamsT('projects.params.error.login')]);
    exit;
}
if ($organizationId <= 0 || !$organization->load($organizationId)) {
    http_response_code(404);
    echo json_encode(['status' => false, 'message' => omoProjectsParamsT('projects.params.error.organization')]);
    exit;
}
if (!omoProjectsParamsCanManage($organizationId, $userId)) {
    http_response_code(403);
    echo json_encode(['status' => false, 'message' => omoProjectsParamsT('projects.params.error.forbidden')]);
    exit;
}

$result = omoProjectsParamsStoreConfig($organization, $_POST);
if (empty($result['status'])) {
    http_response_code(422);
}
echo json_encode([
    'status' => !empty($result['status']),
    'message' => (string)($result['message'] ?? omoProjectsParamsT('projects.params.error.save')),
    'changed' => !empty($result['changed']),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
