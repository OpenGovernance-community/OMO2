<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/translation.php';
require_once dirname(__DIR__, 3) . '/includes/site_update_admin.php';

header('Content-Type: application/json; charset=UTF-8');

if ((int)commonGetCurrentUserId() <= 0) {
    http_response_code(401);
    echo json_encode([
        'status' => false,
        'message' => omoSiteUpdateT('parameters.site_update.error.required'),
    ]);
    exit;
}

if (!commonCurrentUserIsSiteAdminModeEnabled()) {
    http_response_code(403);
    echo json_encode([
        'status' => false,
        'message' => omoSiteUpdateT('parameters.site_update.error.forbidden'),
    ]);
    exit;
}

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => false,
        'message' => omoSiteUpdateT('parameters.site_update.error.method_not_allowed'),
    ]);
    exit;
}

try {
    $force = isset($_POST['force']) && (string)$_POST['force'] === '1';
    echo json_encode(siteUpdateAdminRunUpdate((int)commonGetCurrentUserId(), $force), JSON_UNESCAPED_SLASHES);
    exit;
} catch (RuntimeException $exception) {
    $message = $exception->getMessage();
    $statusCode = stripos($message, 'deja en cours') !== false ? 423 : 409;

    http_response_code($statusCode);
    echo json_encode([
        'status' => false,
        'message' => $message,
    ]);
    exit;
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'message' => $exception->getMessage(),
    ]);
    exit;
}
