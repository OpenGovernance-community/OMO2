<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__) . '/translation.php';

use dbObject\Organization;

header('Content-Type: application/json; charset=UTF-8');

$organizationId = (int)($_SESSION['currentOrganization'] ?? 0);
if ($organizationId <= 0) {
    http_response_code(400);
    echo json_encode(
        array(
            'status' => 'error',
            'message' => omoHolonTemplateT('parameters.holon_templates.error.no_organization'),
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

$organization = new Organization();
if (!$organization->load($organizationId)) {
    http_response_code(404);
    echo json_encode(
        array(
            'status' => 'error',
            'message' => omoHolonTemplateT('parameters.holon_templates.error.organization_not_found'),
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

$contextHolonId = (int)($_GET['cid'] ?? $_POST['cid'] ?? 0);
$holonId = (int)($_GET['hid'] ?? $_POST['hid'] ?? 0);
$templateScope = strtolower(trim((string)($_GET['template_scope'] ?? $_POST['template_scope'] ?? 'contextual')));
if ($templateScope !== 'global') {
    $templateScope = 'contextual';
}

$rawPayload = $_POST['payload'] ?? file_get_contents('php://input');
$payload = json_decode($rawPayload, true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(
        array(
            'status' => 'error',
            'message' => omoHolonTemplateT('parameters.holon_templates.error.invalid_request'),
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

$result = $holonId > 0
    ? $organization->saveHolonDefinitionEditor($payload, (int)commonGetCurrentUserId(), $holonId)
    : $organization->saveHolonTemplateDefinition($payload, (int)commonGetCurrentUserId(), $contextHolonId, $templateScope);

if (!($result['status'] ?? false)) {
    http_response_code(422);
    echo json_encode(
        array(
            'status' => 'error',
            'message' => (string)($result['message'] ?? ($holonId > 0
                ? omoHolonTemplateT('parameters.holon_templates.error.save_organization')
                : omoHolonTemplateT('parameters.holon_templates.error.save_model'))),
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

if (function_exists('commonClearCurrentUserPermissionCache')) {
    commonClearCurrentUserPermissionCache();
}

echo json_encode(
    array(
        'status' => 'ok',
        'message' => (string)($result['message'] ?? ($holonId > 0
            ? omoHolonTemplateT('parameters.holon_templates.status.saved_organization')
            : omoHolonTemplateT('parameters.holon_templates.status.saved_model'))),
        'template' => $result['template'] ?? null,
        'data' => $result['data'] ?? ($holonId > 0
            ? $organization->getHolonDefinitionEditorData($holonId)
            : $organization->getHolonTemplateEditorData($contextHolonId, $templateScope)),
    ),
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
