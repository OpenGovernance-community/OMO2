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

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => false,
        'message' => 'Methode non autorisee.',
    ]);
    exit;
}

try {
    echo json_encode(siteUpdateAdminRunUpdate((int)commonGetCurrentUserId()), JSON_UNESCAPED_SLASHES);
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
