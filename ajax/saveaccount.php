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
			'message' => commonAccountUiT('copy.vous_n_avez_pas_le_droit_d_editer_les_informations_de_cet_utilisateur'),
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	}

	$data = $_POST;
	foreach (['current_password', 'new_password', 'new_password_confirm', 'allow_password_login'] as $field) {
		if (isset($data[$field]) && !is_string($data[$field])) {
			http_response_code(400);
			echo json_encode(['status' => false, 'message' => commonAccountUiT('copy.donnees_invalides')]);
			exit;
		}
	}
	$passwordValidation = commonValidateUserPasswordUpdate(
		(string)$object->get('password'),
		(string)($data['current_password'] ?? ''),
		(string)($data['new_password'] ?? ''),
		(string)($data['new_password_confirm'] ?? ''),
		(string)$object->get('email')
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
		$data['new_password_confirm'],
		$data['allow_password_login']
	);
	try {
		$object->loadProfileInput($data);
	} catch (\InvalidArgumentException $error) {
		http_response_code(400);
		echo json_encode(['status' => false, 'message' => commonAccountUiT('copy.donnees_ou_image_invalides')]);
		exit;
	}
	if (!empty($passwordValidation['shouldUpdate'])) {
		$object->set('password', commonHashUserPassword((string)$_POST['new_password']));
	}
	if (array_key_exists('allow_password_login', $_POST)) $object->set(
		'allow_password_login',
		commonUserHasPasswordHash((string)$object->get('password'))
			&& !empty($_POST['allow_password_login'])
			? 1
			: 0
	);
	try {
		$saveResult = $object->saveSecurityChanges(!empty($passwordValidation['shouldUpdate']));
	} catch (\Throwable $error) {
		error_log('Profile security save failed: ' . get_class($error));
		http_response_code(503);
		echo json_encode(['status' => false, 'message' => commonAccountUiT('copy.impossible_d_enregistrer_ce_profil')]);
		exit;
	}

	if (!is_array($saveResult) || empty($saveResult['status'])) {
		echo json_encode([
			'status' => false,
			'success' => false,
			'message' => is_array($saveResult) && !empty($saveResult['text']) ? (string)$saveResult['text'] : "Impossible d'enregistrer ce profil.",
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	}

	if (!empty($passwordValidation['shouldUpdate'])) {
		$_SESSION['auth_security_version'] = (int)$object->get('security_version');
		commonExpireCookieValue(commonGetRememberCookieName(), true);
		session_regenerate_id(true);
	}
	$msg = !empty($passwordValidation['shouldUpdate'])
		? commonAccountUiT('copy.profil_et_mot_de_passe_enregistres')
		: commonAccountUiT('copy.enregistrement_reussi');
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
