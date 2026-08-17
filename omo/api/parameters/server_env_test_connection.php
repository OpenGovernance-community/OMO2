<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/translation.php';
require_once dirname(__DIR__, 3) . '/includes/server_env_admin.php';

header('Content-Type: application/json; charset=UTF-8');

if ((int)commonGetCurrentUserId() <= 0) {
    http_response_code(401);
    echo json_encode([
        'status' => false,
        'message' => omoServerEnvT('parameters.server_env.error.required'),
    ]);
    exit;
}

if (!commonCurrentUserIsSiteAdminModeEnabled()) {
    http_response_code(403);
    echo json_encode([
        'status' => false,
        'message' => omoServerEnvT('parameters.server_env.error.forbidden'),
    ]);
    exit;
}

if (!serverEnvAdminIsUnlocked()) {
    http_response_code(423);
    echo json_encode([
        'status' => false,
        'message' => omoServerEnvT('parameters.server_env.error.unlock_required'),
        'requiresUnlock' => true,
    ]);
    exit;
}

$service = trim((string)($_POST['service'] ?? ''));
if (!in_array($service, ['etherpad', 'ethercalc'], true)) {
    http_response_code(422);
    echo json_encode([
        'status' => false,
        'message' => omoServerEnvT('parameters.server_env.error.invalid_connection_service'),
    ]);
    exit;
}

try {
    $submittedValues = serverEnvAdminReadSubmittedValues($_POST);
    $currentValues = serverEnvAdminBuildCurrentValues();
    $mergedValues = serverEnvAdminMergeSubmittedValues($submittedValues, $currentValues);
    $result = serverEnvAdminTestConnection($service, $mergedValues);

    http_response_code(!empty($result['status']) ? 200 : 422);
    echo json_encode($result);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'message' => serverEnvAdminConnectionTestError($service, ''),
    ]);
}
