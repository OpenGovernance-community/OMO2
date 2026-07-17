<?php
require_once __DIR__ . '/bootstrap.php';

commonRestoreRememberedUser();
include __DIR__ . '/inc/org.php';
require_once __DIR__ . '/inc/access.php';

$mission_id = (int)($_POST['mission_id'] ?? 0);
$parcours_id = (int)($_POST['parcours_id'] ?? 0);
$accessContext = lmsGetParcoursAccessContext((int)$org['id'], $parcours_id);
$isOrganizationAdmin = !empty($accessContext['isOrganizationAdmin']);

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

if ($mission_id <= 0 || $parcours_id <= 0) {
	http_response_code(400);
	echo "Invalid mission or parcours";
	exit;
}

if (!lmsCanTrackProgress($accessContext)) {
	http_response_code(403);
	echo "Progress unavailable";
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

if (\dbObject\Mission::countHomeworksForMission($mission_id, $isOrganizationAdmin) > 0) {
	if (lmsIsAnonymousViewer($accessContext)) {
		$doneHomeworkIds = lmsParseDoneHomeworkIds($_POST['done_homework_ids'] ?? '');
		if (!\dbObject\Mission::areHomeworkIdsComplete($mission_id, $doneHomeworkIds, $isOrganizationAdmin)) {
			http_response_code(409);
			echo "Homework required";
			exit;
		}
	} elseif (!\dbObject\UserHomework::hasCompletedAllForUserMission((int)$accessContext['userId'], $mission_id, $parcours_id, $isOrganizationAdmin)) {
		http_response_code(409);
		echo "Homework required";
		exit;
	}
}

if (lmsIsAnonymousViewer($accessContext)) {
	echo "OK";
	exit;
}

$result = \dbObject\UserMission::markDone((int)$accessContext['userId'], $mission_id, $parcours_id);

if (empty($result['status'])) {
	http_response_code(500);
	echo isset($result['text']) ? $result['text'] : "Save error";
	exit;
}

echo "OK";
