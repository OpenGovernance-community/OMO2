<?php
require_once dirname(__DIR__, 4) . '/shared_functions.php';
require_once dirname(__DIR__, 4) . '/common/auth.php';
require_once dirname(__DIR__, 4) . '/common/ethercalc.php';
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: private, no-store');
$link = omoEthercalcResolveShareToken((string)($_GET['auth'] ?? ''), (string)($_GET['room'] ?? ''));
echo json_encode(['canEdit' => $link !== null]);
