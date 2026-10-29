<?php
require_once __DIR__ . '/ui_translations.php';
require_once __DIR__ . '/bootstrap.php';

commonRestoreRememberedUser();
include __DIR__ . '/inc/org.php';
require_once __DIR__ . '/inc/access.php';

$mission_id = (int)($_POST['mission_id'] ?? 0);
$homework_id = (int)($_POST['homework_id'] ?? 0);
$parcours_id = (int)($_POST['parcours_id'] ?? 0);
$done = (int)($_POST['done'] ?? 0) > 0;
$accessContext = lmsGetParcoursAccessContext((int)$org['id'], $parcours_id);
$isOrganizationAdmin = !empty($accessContext['isOrganizationAdmin']);

if (empty($accessContext['exists'])) {
	http_response_code(404);
	echo omoLmsUiT('parcours_inconnu');
	exit;
}

if (empty($accessContext['canView'])) {
	http_response_code(!empty($accessContext['isLoggedIn']) ? 403 : 401);
	echo omoLmsUiT('acces_non_autorise');
	exit;
}

if ($mission_id <= 0 || $homework_id <= 0 || $parcours_id <= 0) {
	http_response_code(400);
	echo omoLmsUiT('demande_de_devoir_invalide');
	exit;
}

if (!lmsCanTrackProgress($accessContext)) {
	http_response_code(403);
	echo omoLmsUiT('progression_indisponible');
	exit;
}

$parcoursMission = new \dbObject\ParcoursMission();
if (!$parcoursMission->load([
	['IDparcours', $parcours_id],
	['IDmission', $mission_id],
])) {
	http_response_code(400);
	echo omoLmsUiT('mission_introuvable_dans_ce_parcours');
	exit;
}

if (!\dbObject\MissionHomework::existsForMission($mission_id, $homework_id)
	|| !in_array($homework_id, \dbObject\Mission::fetchHomeworkIdsForMission($mission_id, $isOrganizationAdmin), true)) {
	http_response_code(400);
	echo omoLmsUiT('devoir_introuvable_dans_cette_mission');
	exit;
}

if (lmsIsAnonymousViewer($accessContext)) {
	echo omoLmsUiT('ok');
	exit;
}

$result = \dbObject\UserHomework::markDone((int)$accessContext['userId'], $mission_id, $homework_id, $parcours_id, $done);

if (empty($result['status'])) {
	http_response_code(500);
	echo isset($result['text']) ? $result['text'] : "Save error";
	exit;
}

echo omoLmsUiT('ok');
