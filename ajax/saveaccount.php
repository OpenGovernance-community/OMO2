<?php

	require_once("../config.php");
	require_once("../shared_functions.php");
	require_once("../common/auth.php");
	require_once("../common/profile_save_logging.php");

	function profileSaveRespondFailure($scope, $message, array $context = array())
	{
		commonLogProfileSaveFailure($scope, $message, $context);
		echo json_encode([
			'status' => false,
			'success' => false,
			'message' => $message,
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	}

	if (!checklogin()) {
		profileSaveRespondFailure('general', 'Login requis');
	}

	$currentUserId = function_exists('commonGetCurrentUserId')
		? (int)commonGetCurrentUserId()
		: (int)($_SESSION["currentUser"] ?? 0);
	if ($currentUserId <= 0) {
		profileSaveRespondFailure('general', 'Utilisateur inconnu', array(
			'resolved_user_id' => $currentUserId,
		));
	}

	$object = new \dbObject\user();
	if (!$object->load($currentUserId) || (int)$object->getId() <= 0) {
		profileSaveRespondFailure('general', 'Utilisateur inconnu', array(
			'load_user_id' => $currentUserId,
		));
	}

	if (!$object->canEdit()) {
		profileSaveRespondFailure('general', "Vous n'avez pas le droit d'editer les informations de cet utilisateur", array(
			'target_user_id' => (int)$object->getId(),
		));
	}

	$data = $_POST;
	$data["id"] = $currentUserId;
	try {
		$object->loadFromArray($data);
		$saveResult = $object->save();
	} catch (\Throwable $exception) {
		profileSaveRespondFailure('general', "Exception pendant l'enregistrement du profil.", array(
			'exception_message' => $exception->getMessage(),
			'exception_code' => $exception->getCode(),
			'db_error' => \dbObject\DbObject::getLastDbError(),
		));
	}

	if (!is_array($saveResult) || empty($saveResult['status'])) {
		profileSaveRespondFailure('general', is_array($saveResult) && !empty($saveResult['text']) ? (string)$saveResult['text'] : "Impossible d'enregistrer ce profil.", array(
			'save_result' => $saveResult,
			'db_error' => \dbObject\DbObject::getLastDbError(),
		));
	}

	$msg = 'Enregistrement reussi';
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
