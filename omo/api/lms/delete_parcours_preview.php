<?php
require_once __DIR__ . '/bootstrap.php';

commonRestoreRememberedUser();
include __DIR__ . '/inc/org.php';

header('Content-Type: application/json; charset=utf-8');

$currentUserId = (int)commonGetCurrentUserId();
$organizationId = (int)($org['id'] ?? 0);
$hasOrganizationAccess = commonUserHasOrganizationAccess($currentUserId, $organizationId);
$parcoursId = (int)($_POST['id'] ?? ($_GET['id'] ?? 0));

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

$previewResult = $parcours->previewDeleteForOrganization($organizationId);
if (!is_array($previewResult) || empty($previewResult['status'])) {
	http_response_code(500);
	echo json_encode(array(
		'status' => false,
		'success' => false,
		'message' => is_array($previewResult) && !empty($previewResult['message'])
			? (string)$previewResult['message']
			: 'Impossible de preparer la suppression de ce parcours.',
	), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

echo json_encode(array(
	'status' => true,
	'success' => true,
	'action' => (string)($previewResult['action'] ?? 'none'),
	'message' => (string)($previewResult['message'] ?? ''),
	'confirmMessage' => (string)($previewResult['confirmMessage'] ?? ''),
	'totalOrganizationCount' => (int)($previewResult['totalOrganizationCount'] ?? 0),
	'otherOrganizationCount' => (int)($previewResult['otherOrganizationCount'] ?? 0),
	'isOwner' => !empty($previewResult['isOwner']),
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
