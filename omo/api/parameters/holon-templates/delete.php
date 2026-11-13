<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__) . '/translation.php';
require_once __DIR__ . '/access.php';

use dbObject\Organization;

header('Content-Type: application/json; charset=UTF-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['status' => 'error', 'message' => omoHolonTemplateT('parameters.holon_templates.error.delete_model')]);
    exit;
}

$organizationId = (int)($_SESSION['currentOrganization'] ?? 0);
if ($organizationId <= 0) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => omoHolonTemplateT('parameters.holon_templates.error.no_organization'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$organization = new Organization();
if (!$organization->load($organizationId)) {
    http_response_code(404);
    echo json_encode([
        'status' => 'error',
        'message' => omoHolonTemplateT('parameters.holon_templates.error.organization_not_found'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$discoveryModeAccess = omoHolonTemplateDiscoveryModeAccess($organization);
if (empty($discoveryModeAccess['status'])) {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => (string)($discoveryModeAccess['message'] ?? omoHolonTemplateT('parameters.holon_templates.error.discovery_mode')),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$adminModeAccess = omoHolonTemplateAdminModeAccess($organizationId);
if (empty($adminModeAccess['status'])) {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => (string)($adminModeAccess['message'] ?? omoHolonTemplateT('parameters.holon_templates.error.admin_required')),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$templateId = (int)($_POST['template_id'] ?? 0);
$contextHolonId = (int)($_GET['cid'] ?? $_POST['cid'] ?? 0);
$templateScope = strtolower(trim((string)($_GET['template_scope'] ?? $_POST['template_scope'] ?? 'contextual')));
if ($templateScope === 'global') {
    $templateScope = 'descendants';
}
if (!in_array($templateScope, ['contextual', 'children', 'descendants'], true)) {
    $templateScope = 'contextual';
}

$preview = ($_POST['action'] ?? '') === 'preview';
$result = $organization->deleteHolonTemplateDefinition(
    $templateId,
    (int)commonGetCurrentUserId(),
    $contextHolonId,
    $templateScope,
    ['count' => $_POST['confirmation_count'] ?? null, 'signature' => $_POST['impact_signature'] ?? null],
    $preview
);

if (!($result['status'] ?? false)) {
    http_response_code(422);
    echo json_encode([
        'status' => 'error',
        'message' => in_array($result['reason'] ?? '', ['confirmation_required', 'impact_changed'], true)
            ? omoHolonTemplateT('parameters.holon_templates.error.' . $result['reason'])
            : (string)($result['message'] ?? omoHolonTemplateT('parameters.holon_templates.error.delete_model')),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($preview) {
    echo json_encode(['status' => 'ok', 'impact' => $result['impact'], 'template' => $result['template']],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (function_exists('commonClearCurrentUserPermissionCache')) {
    commonClearCurrentUserPermissionCache();
}

echo json_encode([
    'status' => 'ok',
    'message' => omoHolonTemplateT('parameters.holon_templates.status.deleted_model'),
    'template' => $result['template'] ?? null,
    'data' => $result['data'] ?? null,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
