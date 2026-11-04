<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/tension_translations.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo json_encode(['status' => false, 'message' => omoTensionT('delete_failed')]);
    exit;
}

$userId = (int)commonGetCurrentUserId();
$organizationId = (int)($_POST['oid'] ?? 0);
$tension = new \dbObject\Tension();
if ($userId <= 0 || $organizationId <= 0 || !commonCurrentUserHasOrganizationAccess($organizationId)) {
    http_response_code($userId <= 0 ? 401 : 403);
    $result = ['status' => false, 'message' => omoTensionT('forbidden')];
} elseif (!$tension->load((int)($_POST['id'] ?? 0)) || (int)$tension->get('IDorganization') !== $organizationId) {
    http_response_code(404);
    $result = ['status' => false, 'message' => omoTensionT('not_found')];
} elseif (!$tension->canDeleteInOrganizationContext($organizationId, $userId)) {
    http_response_code(403);
    $result = ['status' => false, 'message' => omoTensionT('forbidden')];
} elseif (!$tension->deleteInOrganizationContext($organizationId, $userId)) {
    http_response_code(500);
    $result = ['status' => false, 'message' => omoTensionT('delete_failed')];
} else {
    $result = ['status' => true, 'message' => omoTensionT('deleted')];
}
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
