<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/translation.php';
require_once dirname(__DIR__, 3) . '/includes/server_env_admin.php';
require_once dirname(__DIR__, 3) . '/common/ai_infomaniak.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['status' => false]);
    exit;
}
$error = null;
if ((int)commonGetCurrentUserId() <= 0) {
    http_response_code(401);
    $error = 'parameters.server_env.error.required';
} elseif (!commonCurrentUserIsSiteAdminModeEnabled()) {
    http_response_code(403);
    $error = 'parameters.server_env.error.forbidden';
} elseif (!serverEnvAdminIsUnlocked()) {
    http_response_code(423);
    $error = 'parameters.server_env.error.unlock_required';
}
if ($error !== null) {
    echo json_encode(['status' => false, 'message' => omoServerEnvT($error), 'requiresUnlock' => http_response_code() === 423]);
    exit;
}

try {
    $token = is_string($_POST['token'] ?? null) ? commonAiNormalizeInfomaniakToken($_POST['token']) : '';
    // Never forward a saved key belonging to a different provider.
    if ($token === '') $token = serverEnvAdminGetSavedInfomaniakToken();
    if ($token === '') throw new RuntimeException('token_missing');
    $productId = is_string($_POST['productId'] ?? null) ? trim($_POST['productId']) : '';
    $discovery = commonAiInfomaniakDiscover($token, $productId);
    serverEnvAdminRememberInfomaniakDiscovery($token, $discovery);
    echo json_encode(['status' => true] + $discovery, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
} catch (Throwable $error) {
    $reason = in_array($error->getMessage(), ['token_format', 'token_length', 'token_missing', 'token_invalid', 'token_denied', 'rate_limit', 'timeout', 'response', 'products', 'product', 'product_format', 'models'], true) ? $error->getMessage() : 'connection';
    http_response_code(422);
    echo json_encode(['status' => false, 'message' => omoServerEnvT('parameters.server_env.infomaniak.error.' . $reason)]);
}
