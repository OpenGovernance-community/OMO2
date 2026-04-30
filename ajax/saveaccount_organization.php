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
	$currentOrganizationId = (int)($_SESSION['currentOrganization'] ?? 0);

	if ($currentUserId <= 0 || $currentOrganizationId <= 0) {
		echo json_encode([
			'status' => false,
			'success' => false,
			'message' => "Aucune organisation n'est sélectionnée.",
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	}

	$object = new \dbObject\UserOrganization();
	if (!$object->load([
		['IDuser', $currentUserId],
		['IDorganization', $currentOrganizationId],
	])) {
		$object->set('IDuser', $currentUserId);
		$object->set('IDorganization', $currentOrganizationId);
	}

	if ((int)$object->getId() > 0 && !$object->canEdit()) {
		echo json_encode([
			'status' => false,
			'success' => false,
			'message' => "Vous n'avez pas le droit d'éditer ce profil d'organisation.",
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	}

	$data = $_POST;
	$data['IDuser'] = $currentUserId;
	$data['IDorganization'] = $currentOrganizationId;
	$object->loadFromArray($data);
	$object->set('IDuser', $currentUserId);
	$object->set('IDorganization', $currentOrganizationId);
	$object->set('active', true);

	$saveResult = $object->save();
	if (!is_array($saveResult) || empty($saveResult['status'])) {
		echo json_encode([
			'status' => false,
			'success' => false,
			'message' => "Impossible d'enregistrer le profil d'organisation.",
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	}

	echo json_encode([
		'status' => true,
		'success' => true,
		'message' => "Profil d'organisation enregistré.",
	], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

?>
