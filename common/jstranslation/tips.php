<?php
require_once dirname(__DIR__, 2) . '/shared_functions.php';
require_once dirname(__DIR__) . '/tips/translations.php';
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: private, max-age=300');
$payload = [];
foreach (['title', 'next', 'read', 'close', 'error'] as $key) $payload[$key] = commonTipsT($key);
echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
