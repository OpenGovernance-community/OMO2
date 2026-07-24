<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__) . '/translation.php';

use dbObject\Organization;

header('Content-Type: application/json; charset=UTF-8');

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

$templateId = (int)($_POST['template_id'] ?? 0);
$contextHolonId = (int)($_GET['cid'] ?? $_POST['cid'] ?? 0);
$templateScope = strtolower(trim((string)($_GET['template_scope'] ?? $_POST['template_scope'] ?? 'contextual')));
if ($templateScope === 'global') {
    $templateScope = 'descendants';
}
if (!in_array($templateScope, ['contextual', 'children', 'descendants'], true)) {
    $templateScope = 'contextual';
}

$result = $organization->deleteHolonTemplateDefinition(
    $templateId,
    (int)commonGetCurrentUserId(),
    $contextHolonId,
    $templateScope
);

if (!($result['status'] ?? false)) {
    http_response_code(422);
    echo json_encode([
        'status' => 'error',
        'message' => (string)($result['message'] ?? omoHolonTemplateT('parameters.holon_templates.error.delete_model')),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
