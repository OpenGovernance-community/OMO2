<?php
	require_once("../config.php");
	require_once("../shared_functions.php");

	header('Content-Type: application/json; charset=UTF-8');

	function pvLoadDecodePayload($rawData) {
		$rawData = (string)$rawData;
		$decoded = json_decode($rawData, true);
		if (is_array($decoded)) {
			return $decoded;
		}

		$decoded = json_decode(urldecode($rawData), true);
		if (is_array($decoded)) {
			return $decoded;
		}

		return null;
	}

	if (!checkLogin()) {
		echo json_encode(array(
			'error' => true,
			'errorMsg' => T_('Connexion requise')
		));
		exit;
	}

	if (!isset($_GET['id']) || (int)$_GET['id'] <= 0) {
		echo json_encode(array(
			'error' => true,
			'errorMsg' => T_('Identifiant invalide')
		));
		exit;
	}

	$pv = new \dbObject\PV();
	if (!$pv->load((int)$_GET['id'])) {
		echo json_encode(array(
			'error' => true,
			'errorMsg' => T_('Document introuvable')
		));
		exit;
	}

	if ((int)$pv->get("IDuser") !== (int)$_SESSION["currentUser"]) {
		echo json_encode(array(
			'error' => true,
			'errorMsg' => T_('Acces refuse')
		));
		exit;
	}

	$data = pvLoadDecodePayload($pv->get("data"));
	if (!is_array($data)) {
		echo json_encode(array(
			'error' => true,
			'errorMsg' => T_('Le document sauvegarde n est plus lisible')
		));
		exit;
	}

	echo json_encode($data);
?>
