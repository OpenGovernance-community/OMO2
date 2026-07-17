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

$password = isset($_POST['password']) ? (string)$_POST['password'] : '';
if ($password === '') {
    http_response_code(422);
    echo json_encode([
        'status' => false,
        'message' => omoServerEnvT('parameters.server_env.error.password_required'),
    ]);
    exit;
}

if (!serverEnvAdminHasLocalPassword()) {
    http_response_code(409);
    echo json_encode([
        'status' => false,
        'message' => omoServerEnvT('parameters.server_env.error.password_unavailable'),
    ]);
    exit;
}

if (!serverEnvAdminVerifyCurrentUserPassword($password)) {
    http_response_code(422);
    echo json_encode([
        'status' => false,
        'message' => omoServerEnvT('parameters.server_env.error.password_invalid'),
    ]);
    exit;
}

serverEnvAdminRememberUnlocked();

echo json_encode([
    'status' => true,
    'message' => omoServerEnvT('parameters.server_env.status.unlocked'),
]);
exit;
