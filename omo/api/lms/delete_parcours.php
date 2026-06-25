<?php
require_once __DIR__ . '/bootstrap.php';

commonRestoreRememberedUser();
include __DIR__ . '/inc/org.php';

header('Content-Type: application/json; charset=utf-8');

$currentUserId = (int)commonGetCurrentUserId();
$organizationId = (int)($org['id'] ?? 0);
$hasOrganizationAccess = commonUserHasOrganizationAccess($currentUserId, $organizationId);
$parcoursId = (int)($_POST['id'] ?? 0);

if ($currentUserId <= 0 || !$hasOrganizationAccess || $organizationId <= 0) {
	http_response_code(403);
	echo json_encode(array(
		'status' => false,
		'success' => false,
		'message' => 'Acces refuse.',
	), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

if ($parcoursId <= 0) {
	http_response_code(400);
	echo json_encode(array(
		'status' => false,
		'success' => false,
		'message' => 'Parcours invalide.',
	), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

$parcours = new \dbObject\Parcours();
$link = \dbObject\OrganizationParcours::loadForOrganizationParcours($organizationId, $parcoursId);
if ($link === null || !$parcours->load($parcoursId)) {
	http_response_code(404);
	echo json_encode(array(
		'status' => false,
		'success' => false,
		'message' => 'Parcours introuvable.',
	), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

$deleteResult = $parcours->deleteForOrganization($organizationId);
if (!is_array($deleteResult) || empty($deleteResult['status'])) {
	http_response_code(500);
	echo json_encode(array(
		'status' => false,
		'success' => false,
		'message' => is_array($deleteResult) && !empty($deleteResult['message'])
			? (string)$deleteResult['message']
			: 'Impossible de supprimer ce parcours.',
	), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

echo json_encode(array(
	'status' => true,
	'success' => true,
	'action' => (string)($deleteResult['action'] ?? 'delete'),
	'message' => (string)($deleteResult['message'] ?? 'Parcours supprime.'),
	'remainingOrganizationCount' => (int)($deleteResult['remainingOrganizationCount'] ?? 0),
	'deletedMissionCount' => (int)($deleteResult['deletedMissionCount'] ?? 0),
	'deletedQuestionCount' => (int)($deleteResult['deletedQuestionCount'] ?? 0),
	'deletedHomeworkCount' => (int)($deleteResult['deletedHomeworkCount'] ?? 0),
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
