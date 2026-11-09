<?php
require_once __DIR__ . '/../common/account-translations.php';


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

	$user = new \dbObject\User();
	if (!$user->load($currentUserId) || (int)$user->getId() <= 0) {
		echo json_encode([
			'status' => false,
			'message' => 'Utilisateur inconnu',
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	}

	$result = $user->deleteCompetenceDeclaration((int)($_POST['id'] ?? 0));

	echo json_encode([
		'status' => !empty($result['status']),
		'message' => (string)($result['message'] ?? ($result['status'] ? commonAccountUiT('copy.competence_supprimee') : commonAccountUiT('copy.impossible_de_supprimer_cette_competence'))),
	], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

?>
