<?php
require_once __DIR__ . '/../common/account-translations.php';


	require_once("../config.php");
	require_once("../shared_functions.php");
	require_once("../common/auth.php");
	if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); exit; }

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
			'message' => commonAccountUiT('copy.aucune_organisation_n_est_selectionnee'),
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	}

	$object = new \dbObject\UserOrganization();
	if (!$object->load([
		['IDuser', $currentUserId],
		['IDorganization', $currentOrganizationId],
	])) {
		http_response_code(403);
		echo json_encode(['status' => false, 'message' => commonAccountUiT('copy.adhesion_requise')]);
		exit;
	}

	if ((int)$object->getId() > 0 && !$object->canEdit()) {
		echo json_encode([
			'status' => false,
			'success' => false,
			'message' => commonAccountUiT('copy.vous_n_avez_pas_le_droit_d_editer_ce_profil_d_organisation'),
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	}

	if (!(bool)$object->get('active')) {
		http_response_code(403);
		echo json_encode(['status' => false, 'message' => commonAccountUiT('copy.adhesion_inactive')]);
		exit;
	}
	try {
		$object->loadProfileInput($_POST);
	} catch (\InvalidArgumentException $error) {
		http_response_code(400);
		echo json_encode(['status' => false, 'message' => commonAccountUiT('copy.donnees_ou_image_invalides')]);
		exit;
	}
	$saveResult = $object->save();

	if (!is_array($saveResult) || empty($saveResult['status'])) {
		echo json_encode([
			'status' => false,
			'success' => false,
			'message' => is_array($saveResult) && !empty($saveResult['text'])
				? (string)$saveResult['text']
				: "Impossible d'enregistrer le profil d'organisation.",
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	}

	echo json_encode([
		'status' => true,
		'success' => true,
		'message' => "Profil d'organisation enregistre.",
	], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

?>
