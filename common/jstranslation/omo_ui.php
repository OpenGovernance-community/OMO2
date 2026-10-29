<?php
require_once dirname(__DIR__, 2) . '/shared_functions.php';
require_once __DIR__ . '/omo_ui_sources.php';

$payload = omoUiJsPayload();
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
