<?php

require_once '../shared_functions.php';
require_once '../common/auth.php';
require_once '../common/patreon.php';

header('Content-Type: application/json; charset=UTF-8');

$connected = checkLogin();
$currentUserId = function_exists('commonGetCurrentUserId')
	? (int)commonGetCurrentUserId()
	: (int)($_SESSION['currentUser'] ?? 0);

if (!$connected || $currentUserId <= 0) {
	echo json_encode([
		'status' => false,
		'message' => 'Connexion requise.',
	]);
	exit;
}

$connection = \dbObject\UserPatreon::findByUserId($currentUserId);
if ($connection === false) {
	echo json_encode([
		'status' => true,
		'message' => 'Aucun compte Patreon n’était connecté.',
		'script' => "if (window.jQuery && document.getElementById('popup_content')) { refresh('#popup_content','/popup/profil.php'); } if (window.commonTopbarRefreshModalContent) { window.commonTopbarRefreshModalContent('/popup/profil.php'); }",
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
	'script' => "if (window.jQuery && document.getElementById('popup_content')) { refresh('#popup_content','/popup/profil.php'); } if (window.commonTopbarRefreshModalContent) { window.commonTopbarRefreshModalContent('/popup/profil.php'); }",
]);
exit;
?>
