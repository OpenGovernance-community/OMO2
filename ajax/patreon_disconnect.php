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

$connection = \dbObject\UserPatreon::findByUserId((int)$_SESSION['currentUser']);
if ($connection === false) {
	echo json_encode([
		'status' => true,
		'message' => 'Aucun compte Patreon n’était connecté.',
		'script' => "refresh('#popup_content','/popup/profil.php')",
	]);
	exit;
}

$result = $connection->disconnect();
if (empty($result['status'])) {
	echo json_encode([
		'status' => false,
		'message' => 'Impossible de déconnecter le compte Patreon.',
	]);
	exit;
}

echo json_encode([
	'status' => true,
	'message' => 'Compte Patreon déconnecté.',
	'script' => "refresh('#popup_content','/popup/profil.php')",
]);
exit;
?>
