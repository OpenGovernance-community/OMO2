<?php
	require_once("../config.php");
	require_once("../shared_functions.php");

	header('Content-Type: application/json; charset=UTF-8');

	function circleLoadOrgaError($message) {
		echo json_encode(
			array(
				'error' => true,
				'errorMsg' => (string)$message,
			),
			JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
		);
		exit;
	}

	if (!isset($_GET['id']) || (int)$_GET['id'] <= 0) {
		circleLoadOrgaError(T_('Accès refusé (aucun ID spécifié)'));
	}

	$orga = new \dbObject\Holon();
	if (!$orga->load((int)$_GET['id'])) {
		circleLoadOrgaError(T_('Organisation introuvable'));
	}

	$hasUserAccess = isset($_SESSION["currentUser"]) && (int)$orga->get("IDuser") === (int)$_SESSION["currentUser"];
	$hasShareAccess = isset($_GET["accesskey"]) && $_GET["accesskey"] !== "" && $_GET["accesskey"] === $orga->get("accesskey");

	if (!$hasUserAccess && !$hasShareAccess) {
		circleLoadOrgaError(
			T_('Accès refusé')."(".$_GET['id'].(isset($_GET["accesskey"])?", ".$_GET["accesskey"]:"").")"
		);
	}

	echo $orga->toRepresentationJson(array(
		'representation' => 'circle',
		'includeDbId' => true,
		'includeProperties' => true,
		'includePropertyAncestors' => true,
		'includeChildren' => true,
		'includeSize' => true,
		'jsonFlags' => JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
	));
?>
