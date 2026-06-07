<?php

	require_once("../config.php");
	require_once("../shared_functions.php");
	require_once("../common/auth.php");

	if (!checklogin()) {
		echo json_encode([
			'status' => false,
			'success' => false,
			'message' => 'Login requis',
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	}

	$currentUserId = function_exists('commonGetCurrentUserId')
		? (int)commonGetCurrentUserId()
		: (int)($_SESSION["currentUser"] ?? 0);
	if ($currentUserId <= 0) {
		echo json_encode([
			'status' => false,
			'success' => false,
			'message' => 'Utilisateur inconnu',
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	}

	$object = new \dbObject\user();
	if (!$object->load($currentUserId) || (int)$object->getId() <= 0) {
		echo json_encode([
			'status' => false,
			'success' => false,
			'message' => 'Utilisateur inconnu',
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	}

	if (!$object->canEdit()) {
		echo json_encode([
			'status' => false,
			'success' => false,
			'message' => "Vous n'avez pas le droit d'editer les informations de cet utilisateur",
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	}

	$data = $_POST;
	$passwordValidation = commonValidateUserPasswordUpdate(
		(string)$object->get('password'),
		(string)($data['current_password'] ?? ''),
		(string)($data['new_password'] ?? ''),
		(string)($data['new_password_confirm'] ?? ''),
		(string)($data['email'] ?? $object->get('email'))
	);
	if (empty($passwordValidation['status'])) {
		echo json_encode([
			'status' => false,
			'success' => false,
			'message' => (string)($passwordValidation['message'] ?? "Impossible d'enregistrer ce mot de passe."),
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	}

	unset(
		$data['current_password'],
		$data['new_password'],
		$data['new_password_confirm']
	);
	$data["id"] = $currentUserId;
	$object->loadFromArray($data);
	if (!empty($passwordValidation['shouldUpdate'])) {
		$object->set('password', commonHashUserPassword((string)$_POST['new_password']));
	}
	$saveResult = $object->save();

	if (!is_array($saveResult) || empty($saveResult['status'])) {
		echo json_encode([
			'status' => false,
			'success' => false,
			'message' => is_array($saveResult) && !empty($saveResult['text']) ? (string)$saveResult['text'] : "Impossible d'enregistrer ce profil.",
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	}

	$msg = !empty($passwordValidation['shouldUpdate'])
		? 'Profil et mot de passe enregistres'
		: 'Enregistrement reussi';
	$formCode = "";
	if (isset($_GET["origin"]) && $_GET["origin"] == "profil") {
		$scope = (isset($_GET["scope"]) && $_GET["scope"] == "organization") ? "organization" : "general";
		$target = "/popup/profil.php?scope=" . $scope;
		$formCode = "if (window.jQuery && document.getElementById('popup_content')) { refresh('#popup_content','" . $target . "'); } if (window.commonTopbarRefreshModalContent) { window.commonTopbarRefreshModalContent('" . $target . "'); }";
	}
	if (isset($_GET["origin"]) && $_GET["origin"] == "params") {
		$formCode = "refresh('#popup_content','/popup/parameters.php')";
	}

	echo json_encode([
		'status' => true,
		'success' => true,
		'message' => $msg,
		'script' => $formCode,
		'id' => $saveResult['id'] ?? ('0' . $currentUserId),
	], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

?>
