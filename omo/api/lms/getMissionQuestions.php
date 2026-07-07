<?php
require_once __DIR__ . '/bootstrap.php';

commonRestoreRememberedUser();
include __DIR__ . '/inc/org.php';
require_once __DIR__ . '/inc/access.php';

$mission_id = (int)($_GET['mission_id'] ?? 0);
$parcours_id = (int)($_GET['parcours_id'] ?? 0);
$accessContext = lmsGetParcoursAccessContext((int)($org['id'] ?? 0), $parcours_id);

if (empty($accessContext['exists'])) {
	http_response_code(404);
	echo json_encode([]);
	exit;
}

if (empty($accessContext['canView'])) {
	http_response_code(!empty($accessContext['isLoggedIn']) ? 403 : 401);
	echo json_encode([]);
	exit;
}

$parcoursMission = new \dbObject\ParcoursMission();
if (
	$mission_id <= 0
	|| $parcours_id <= 0
	|| !$parcoursMission->load([
		['IDparcours', $parcours_id],
		['IDmission', $mission_id],
	])
) {
	http_response_code(404);
	echo json_encode([]);
	exit;
}

$questions = \dbObject\Question::fetchQuestionsForMission($mission_id);

echo json_encode($questions ?: []);
