<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/shared_functions.php';

$mission_id = (int)$_POST['mission_id'];
$parcours_id = (int)$_POST['parcours_id'];
$user_id = (int)($_SESSION['currentUser'] ?? 0);

if ($user_id <= 0) {
    http_response_code(401);
    echo "Unauthorized";
    exit;
}

if ($mission_id <= 0 || $parcours_id <= 0) {
    http_response_code(400);
    echo "Invalid mission or parcours";
    exit;
}

$result = \dbObject\UserMission::markDone($user_id, $mission_id, $parcours_id);

if (empty($result['status'])) {
    http_response_code(500);
    echo isset($result['text']) ? $result['text'] : "Save error";
    exit;
}

echo "OK";
