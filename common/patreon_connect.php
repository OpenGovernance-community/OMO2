<?php

require_once '../shared_functions.php';
require_once __DIR__ . '/patreon.php';

$connected = checkLogin();
if (!$connected || empty($_SESSION['currentUser'])) {
	http_response_code(403);
	die('Connexion requise');
}

if (!patreonIsConfigured()) {
	http_response_code(500);
	die('La configuration Patreon est incomplète.');
}

$state = bin2hex(random_bytes(16));
$_SESSION['patreon_oauth_state'] = [
	'token' => $state,
	'user_id' => (int)$_SESSION['currentUser'],
	'created_at' => time(),
];

header('Location: ' . patreonBuildAuthorizeUrl($state));
exit;
?>
