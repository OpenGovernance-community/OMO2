<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/shared_functions.php';
require_once BASE_PATH . '/common/auth.php';

commonRestoreRememberedUser();
include __DIR__ . '/inc/org.php';
require_once __DIR__ . '/inc/access.php';

$mission_id = (int)($_POST['mission_id'] ?? 0);
$homework_id = (int)($_POST['homework_id'] ?? 0);
$parcours_id = (int)($_POST['parcours_id'] ?? 0);
$done = (int)($_POST['done'] ?? 0) > 0;
$accessContext = lmsGetParcoursAccessContext((int)$org['id'], $parcours_id);

if (empty($accessContext['exists'])) {
	http_response_code(404);
	echo "Unknown parcours";
	exit;
}

if (empty($accessContext['canView'])) {
	http_response_code(!empty($accessContext['isLoggedIn']) ? 403 : 401);
	echo "Unauthorized";
	exit;
}

if ($mission_id <= 0 || $homework_id <= 0 || $parcours_id <= 0) {
	http_response_code(400);
	echo "Invalid homework request";
	exit;
}

if (!lmsCanTrackProgress($accessContext)) {
	http_response_code(401);
	echo "Login required";
	exit;
}

$parcoursMission = new \dbObject\ParcoursMission();
if (!$parcoursMission->load([
	['IDparcours', $parcours_id],
	['IDmission', $mission_id],
])) {
	http_response_code(400);
	echo "Mission not found in parcours";
	exit;
}

if (!\dbObject\MissionHomework::existsForMission($mission_id, $homework_id)) {
	http_response_code(400);
	echo "Homework not found in mission";
	exit;
}

if (lmsIsAnonymousViewer($accessContext)) {
	echo "OK";
	exit;
}

$result = \dbObject\UserHomework::markDone((int)$accessContext['userId'], $mission_id, $homework_id, $parcours_id, $done);

if (empty($result['status'])) {
	http_response_code(500);
	echo isset($result['text']) ? $result['text'] : "Save error";
	exit;
}

echo "OK";
