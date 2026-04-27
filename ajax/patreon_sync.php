<?php

require_once '../shared_functions.php';
require_once '../common/patreon.php';

header('Content-Type: application/json; charset=UTF-8');

$connected = checkLogin();
if (!$connected || empty($_SESSION['currentUser'])) {
	echo json_encode([
		'status' => false,
		'message' => 'Connexion requise.',
	]);
	exit;
}

if (!patreonIsConfigured()) {
	echo json_encode([
		'status' => false,
		'message' => 'La configuration Patreon est incomplète.',
	]);
	exit;
}

$connection = \dbObject\UserPatreon::findByUserId((int)$_SESSION['currentUser']);
if ($connection === false || !$connection->isConnected()) {
	echo json_encode([
		'status' => false,
		'message' => 'Aucun compte Patreon connecté.',
	]);
	exit;
}

try {
	patreonSyncConnection($connection);
	echo json_encode([
		'status' => true,
		'message' => 'Compte Patreon synchronisé.',
		'script' => "refresh('#popup_content','/popup/profil.php')",
	]);
} catch (Throwable $exception) {
	echo json_encode([
		'status' => false,
		'message' => $exception->getMessage(),
		'script' => "refresh('#popup_content','/popup/profil.php')",
	]);
}

exit;
?>
