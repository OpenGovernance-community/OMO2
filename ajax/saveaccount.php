<?php

	require_once("../config.php");
	require_once("../shared_functions.php");

	$object = new \dbObject\user();
	$data = $_POST;

	if (isset($data["id"]) && $data["id"] > 0) {
		$object->load($data["id"]);
		if (!$object->getId() > 0) {
			echo '{"status":false, "success":false, "message":"Utilisateur inconnu"}';
			exit;
		}
		if (!$object->canEdit()) {
			echo '{"status":false, "success":false, "message":"Vous n\'avez pas le droit d\'éditer les informations de cet utilisateur"}';
			exit;
		}
	} else {
		echo '{"status":false, "success":false, "message":"Erreur, impossible de créer un nouveau compte ici"}';
		exit;
	}

	$object->loadFromArray($data);
	$object->save();

	$msg = 'Enregistrement réussi';
	$formCode = "";
	if (isset($_GET["origin"]) && $_GET["origin"] == "profil") {
		$scope = (isset($_GET["scope"]) && $_GET["scope"] == "organization") ? "organization" : "general";
		$target = "/popup/profil.php?scope=" . $scope;
		$formCode = "if (window.jQuery && document.getElementById('popup_content')) { refresh('#popup_content','" . $target . "'); } if (window.commonTopbarRefreshModalContent) { window.commonTopbarRefreshModalContent('" . $target . "'); }";
	}
	if (isset($_GET["origin"]) && $_GET["origin"] == "params") {
		$formCode = "refresh('#popup_content','/popup/parameters.php')";
	}

	echo '{"status":true, "success":true, "message":"' . $msg . '","script": "' . $formCode . '"}';

?>
