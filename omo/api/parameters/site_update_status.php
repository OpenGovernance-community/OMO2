<?php
require_once __DIR__ . '/../bootstrap.php';
require_once dirname(__DIR__, 3) . '/includes/site_update_admin.php';

header('Content-Type: application/json; charset=UTF-8');

if ((int)commonGetCurrentUserId() <= 0) {
    http_response_code(401);
    echo json_encode([
        'status' => false,
        'message' => 'Connexion requise.',
    ]);
    exit;
}

if (!commonCurrentUserIsSiteAdmin()) {
    http_response_code(403);
    echo json_encode([
        'status' => false,
        'message' => 'Acces reserve a l admin du site.',
    ]);
    exit;
}

try {
    echo json_encode(siteUpdateAdminCheckVersionStatus(), JSON_UNESCAPED_SLASHES);
    exit;
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'message' => $exception->getMessage(),
    ]);
    exit;
}
