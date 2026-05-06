<?php

	require_once("../config.php");
	require_once("../shared_functions.php");
	require_once("../common/auth.php");
	require_once("../common/profile_save_logging.php");

	function profileOrganizationRespondFailure($message, array $context = array())
	{
		commonLogProfileSaveFailure('organization', $message, $context);
		echo json_encode([
			'status' => false,
			'success' => false,
			'message' => $message,
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	}

	if (!checklogin()) {
		profileOrganizationRespondFailure('Login requis');
	}

	$currentUserId = function_exists('commonGetCurrentUserId')
		? (int)commonGetCurrentUserId()
		: (int)($_SESSION["currentUser"] ?? 0);
	$currentOrganizationId = (int)($_SESSION['currentOrganization'] ?? 0);

	if ($currentUserId <= 0 || $currentOrganizationId <= 0) {
		profileOrganizationRespondFailure("Aucune organisation n'est selectionnee.", array(
			'resolved_user_id' => $currentUserId,
			'resolved_organization_id' => $currentOrganizationId,
		));
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
		profileOrganizationRespondFailure("Vous n'avez pas le droit d'editer ce profil d'organisation.", array(
			'target_membership_id' => (int)$object->getId(),
			'target_user_id' => (int)$object->get('IDuser'),
			'target_organization_id' => (int)$object->get('IDorganization'),
		));
	}

	$data = $_POST;
	$data['IDuser'] = $currentUserId;
	$data['IDorganization'] = $currentOrganizationId;

	try {
		$object->loadFromArray($data);
		$object->set('IDuser', $currentUserId);
		$object->set('IDorganization', $currentOrganizationId);
		$object->set('active', true);
		$saveResult = $object->save();
	} catch (\Throwable $exception) {
		profileOrganizationRespondFailure("Exception pendant l'enregistrement du profil d'organisation.", array(
			'exception_message' => $exception->getMessage(),
			'exception_code' => $exception->getCode(),
			'db_error' => \dbObject\DbObject::getLastDbError(),
		));
	}

	if (!is_array($saveResult) || empty($saveResult['status'])) {
		profileOrganizationRespondFailure("Impossible d'enregistrer le profil d'organisation.", array(
			'save_result' => $saveResult,
			'db_error' => \dbObject\DbObject::getLastDbError(),
		));
	}

	echo json_encode([
		'status' => true,
		'success' => true,
		'message' => "Profil d'organisation enregistre.",
	], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

?>
