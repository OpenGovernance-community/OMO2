<?php
require_once dirname(__DIR__, 3) . '/shared_functions.php';
require_once dirname(__DIR__, 3) . '/common/auth.php';

commonRestoreRememberedUser();

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$currentUserId = (int)commonGetCurrentUserId();
$organizationId = (int)($_POST['oid'] ?? 0);
$action = trim((string)($_POST['action'] ?? ''));

if ($currentUserId <= 0) {
	http_response_code(401);
	echo json_encode(array(
		'status' => false,
		'message' => 'Connexion requise.',
	), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

if ($organizationId <= 0 || $action === '') {
	http_response_code(400);
	echo json_encode(array(
		'status' => false,
		'message' => 'Action organisation invalide.',
	), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

$organization = new \dbObject\Organization();
if (!$organization->load($organizationId)) {
	http_response_code(404);
	echo json_encode(array(
		'status' => false,
		'message' => 'Organisation introuvable.',
	), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

if (!$organization->getMembership($currentUserId, true)) {
	http_response_code(403);
	echo json_encode(array(
		'status' => false,
		'message' => "Vous n'avez pas acces a cette organisation.",
	), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

$response = array(
	'status' => false,
	'message' => 'Action inconnue.',
);

switch ($action) {
	case 'leave':
		$response = $organization->removeMember($currentUserId, array(
			'actorUserId' => $currentUserId,
		));
		break;

	case 'delete':
		if (!$organization->canDelete()) {
			http_response_code(403);
			$response = array(
				'status' => false,
				'message' => "Vous n'avez pas le droit de supprimer cette organisation.",
			);
			break;
		}

		$organizationName = trim((string)$organization->get('name'));
		$deleted = $organization->delete();
		$response = array(
			'status' => (bool)$deleted,
			'message' => $deleted
				? (($organizationName !== '' ? $organizationName : 'L organisation') . ' a ete supprimee.')
				: "L'organisation n'a pas pu etre supprimee.",
		);
		break;
}

if (!($response['status'] ?? false)) {
	if (http_response_code() < 400) {
		http_response_code(422);
	}
	echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

if ((int)($_SESSION['currentOrganization'] ?? 0) === $organizationId) {
	$_SESSION['currentOrganization'] = -1;
}

$response['redirect'] = commonBuildUrl('/omo/', commonGetRootHost());
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
