<?php
require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/common/omo_fake_cron.php';

header('Content-Type: application/json; charset=UTF-8');

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => false,
        'message' => 'Method not allowed.',
    ], JSON_UNESCAPED_SLASHES);
    exit;
}

echo json_encode(array_merge([
    'status' => true,
], omo_run_fake_cron_maintenance(50, false, 'runtime_endpoint')), JSON_UNESCAPED_SLASHES);
