<?php
require_once dirname(__DIR__) . '/common/lms/ui-translations.php';
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/shared_functions.php';
require_once BASE_PATH . '/common/auth.php';

commonRestoreRememberedUser();
include __DIR__ . '/inc/org.php';
require_once __DIR__ . '/inc/access.php';

$mission_id = (int)($_POST['mission_id'] ?? 0);
$parcours_id = (int)($_POST['parcours_id'] ?? 0);
$accessContext = lmsGetParcoursAccessContext((int)$org['id'], $parcours_id);
$isOrganizationAdmin = !empty($accessContext['isLoggedIn'])
	? lmsCurrentUserIsOrganizationAdmin((int)$org['id'], (int)($accessContext['userId'] ?? 0))
	: false;

if (empty($accessContext['exists'])) {
	http_response_code(404);
	echo commonLmsUiT('parcours_inconnu');
	exit;
}

if (empty($accessContext['canView'])) {
	http_response_code(!empty($accessContext['isLoggedIn']) ? 403 : 401);
	echo commonLmsUiT('acces_non_autorise');
	exit;
}

if ($mission_id <= 0 || $parcours_id <= 0) {
	http_response_code(400);
	echo commonLmsUiT('mission_ou_parcours_invalide');
	exit;
}

if (!lmsCanTrackProgress($accessContext)) {
	http_response_code(401);
	echo commonLmsUiT('login_required');
	exit;
}

$parcoursMission = new \dbObject\ParcoursMission();
if (!$parcoursMission->load([
	['IDparcours', $parcours_id],
	['IDmission', $mission_id],
])) {
	http_response_code(400);
	echo commonLmsUiT('mission_introuvable_dans_ce_parcours');
	exit;
}

if (\dbObject\Mission::countHomeworksForMission($mission_id, $isOrganizationAdmin) > 0) {
	if (lmsIsAnonymousViewer($accessContext)) {
		$doneHomeworkIds = lmsParseDoneHomeworkIds($_POST['done_homework_ids'] ?? '');
		if (!\dbObject\Mission::areHomeworkIdsComplete($mission_id, $doneHomeworkIds, $isOrganizationAdmin)) {
			http_response_code(409);
			echo commonLmsUiT('devoir_requis');
			exit;
		}
	} elseif (!\dbObject\UserHomework::hasCompletedAllForUserMission((int)$accessContext['userId'], $mission_id, $parcours_id, $isOrganizationAdmin)) {
		http_response_code(409);
		echo commonLmsUiT('devoir_requis');
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
	echo isset($result['text']) ? $result['text'] : commonLmsUiT('save_error');
	exit;
}

echo "OK";
