<?php
	require_once("../config.php");
	require_once("../shared_functions.php");

	header('Content-Type: application/json; charset=UTF-8');

	if (!checkLogin()) {
		echo json_encode([
			'status' => 'error',
			'message' => T_('Connexion requise')
		]);
		exit;
	}

	$tmp = file_get_contents('php://input');
	$data = json_decode($tmp, true);

	if (!is_array($data) || !array_key_exists('id', $data)) {
		echo json_encode([
			'status' => 'error',
			'message' => T_('Les donnees fournies sont invalides.')
		]);
		exit;
	}

	$pv = new \dbObject\PV();
	$requestedId = (int)($data['id'] ?? 0);

	if ($requestedId > 0) {
		if (!$pv->load($requestedId)) {
			echo json_encode([
				'status' => 'error',
				'message' => T_('Document introuvable')
			]);
			exit;
		}

		if ((int)$pv->get("IDuser") !== (int)$_SESSION["currentUser"]) {
			echo json_encode([
				'status' => 'error',
				'message' => T_('Acces refuse')
			]);
			exit;
		}
	} else {
		$pv->set("IDuser", (int)$_SESSION["currentUser"]);
	}

	$pv->set("data", urlencode(json_encode($data)));
	$saveResult = $pv->save();
	$pvId = (int)$pv->getId();

	if (
		!is_array($saveResult)
		|| empty($saveResult['status'])
		|| $pvId <= 0
	) {
		$dbError = \dbObject\DbObject::getLastDbError();
		$message = is_array($saveResult) && !empty($saveResult['text'])
			? (string)$saveResult['text']
			: T_('La sauvegarde a echoue.');

		echo json_encode([
			'status' => 'error',
			'message' => $message,
			'debug' => $dbError['message'] ?? ''
		]);
		exit;
	}

	echo json_encode([
		'status' => 'ok',
		'id' => $pvId,
	]);
?>
