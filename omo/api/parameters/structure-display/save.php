<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

header('Content-Type: application/json; charset=UTF-8');

$organizationId = (int)($_SESSION['currentOrganization'] ?? 0);
$organization = new \dbObject\Organization();

if ($organizationId <= 0 || !$organization->load($organizationId)) {
    http_response_code(404);
    echo json_encode(array('status' => 'error', 'message' => omoStructureDisplayT('parameters.structure_display.error.organization')), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

if ($organization->getEnabledStructuralRootHolon() === null) {
    http_response_code(422);
    echo json_encode(array('status' => 'error', 'message' => omoStructureDisplayT('parameters.structure_display.error.structure')), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

$access = omoStructureDisplayAdminModeAccess($organizationId);
if (empty($access['status'])) {
    http_response_code(403);
    echo json_encode(array('status' => 'error', 'message' => (string)($access['message'] ?? omoStructureDisplayT('parameters.structure_display.error.admin_required'))), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

$organization->setStructureDisplaySettings(array(
    'fadeOpacityStep' => $_POST['fadeOpacityStep'] ?? null,
    'maxDescendantDepth' => $_POST['maxDescendantDepth'] ?? null,
    'labelAutoMinRadius' => $_POST['labelAutoMinRadius'] ?? null,
    'labelHoverMinRadius' => $_POST['labelHoverMinRadius'] ?? null,
    'labelMinFontSize' => $_POST['labelMinFontSize'] ?? null,
    'textOutlineEnabled' => isset($_POST['textOutlineEnabled']),
));

$result = $organization->save();
if (empty($result['status'])) {
    http_response_code(422);
    echo json_encode(array('status' => 'error', 'message' => omoStructureDisplayT('parameters.structure_display.status.error')), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(array(
    'status' => 'ok',
    'settings' => $organization->getStructureDisplaySettings(),
    'message' => omoStructureDisplayT('parameters.structure_display.status.saved'),
), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
