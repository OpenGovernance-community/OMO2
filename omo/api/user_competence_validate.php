<?php
require_once __DIR__ . '/bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');

$currentUserId = (int)commonGetCurrentUserId();
$organizationId = (int)($_SESSION['currentOrganization'] ?? 0);
$userCompetenceId = (int)($_POST['id'] ?? 0);
$level = (int)($_POST['level'] ?? 0);

if ($currentUserId <= 0 || $organizationId <= 0 || !commonCurrentUserHasOrganizationAccess($organizationId)) {
	echo json_encode([
		'status' => false,
		'message' => 'Validation impossible dans ce contexte.',
	], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

$result = \dbObject\UserCompetence::saveValidationForViewer($userCompetenceId, $currentUserId, $organizationId, $level);

echo json_encode([
	'status' => !empty($result['status']),
	'message' => (string)($result['message'] ?? ($result['status'] ? 'Validation enregistree.' : "Impossible d'enregistrer cette validation.")),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
