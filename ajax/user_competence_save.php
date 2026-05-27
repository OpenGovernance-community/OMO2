<?php

	require_once("../config.php");
	require_once("../shared_functions.php");
	require_once("../common/auth.php");

	if (!checklogin()) {
		echo json_encode([
			'status' => false,
			'message' => 'Login requis',
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	}

	$currentUserId = function_exists('commonGetCurrentUserId')
		? (int)commonGetCurrentUserId()
		: (int)($_SESSION["currentUser"] ?? 0);
	$currentOrganizationId = (int)($_SESSION['currentOrganization'] ?? 0);

	$user = new \dbObject\User();
	if (!$user->load($currentUserId) || (int)$user->getId() <= 0) {
		echo json_encode([
			'status' => false,
			'message' => 'Utilisateur inconnu',
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	}

	$result = $user->saveCompetenceDeclaration($_POST, $currentOrganizationId);

	echo json_encode([
		'status' => !empty($result['status']),
		'message' => (string)($result['message'] ?? ($result['status'] ? 'Competence enregistree.' : "Impossible d'enregistrer cette competence.")),
	], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

?>
