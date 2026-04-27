<?php

require_once '../shared_functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/patreon.php';

function patreonRenderConnectErrorPage($message)
{
	?>
<!DOCTYPE html>
<html lang="fr">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Connexion Patreon indisponible</title>
	<style>
		body {
			margin: 0;
			padding: 24px;
			font-family: Arial, Helvetica, sans-serif;
			background: #f8fafc;
			color: #0f172a;
		}
		.patreon-connect-card {
			max-width: 560px;
			margin: 40px auto;
			padding: 24px;
			border-radius: 18px;
			background: #ffffff;
			box-shadow: 0 20px 40px rgba(15, 23, 42, 0.12);
			border: 1px solid #e2e8f0;
		}
		.patreon-connect-card h1 {
			margin-top: 0;
			font-size: 24px;
		}
		.patreon-connect-card p {
			line-height: 1.6;
		}
	</style>
</head>
<body>
	<div class="patreon-connect-card">
		<h1>Connexion Patreon indisponible</h1>
		<p><?= nl2br(htmlspecialchars((string)$message)) ?></p>
	</div>
</body>
</html>
<?php
}

$connected = checkLogin();
$currentUserId = function_exists('commonGetCurrentUserId')
	? (int)commonGetCurrentUserId()
	: (int)($_SESSION['currentUser'] ?? 0);

if (!$connected || $currentUserId <= 0) {
	http_response_code(403);
	die('Connexion requise');
}

if (!patreonIsConfigured('oauth')) {
	http_response_code(503);
	patreonRenderConnectErrorPage('La configuration Patreon est incomplète : ' . patreonGetConfigurationMessage('oauth'));
	exit;
}

$state = bin2hex(random_bytes(16));
$_SESSION['patreon_oauth_state'] = [
	'token' => $state,
	'user_id' => $currentUserId,
	'created_at' => time(),
];

header('Location: ' . patreonBuildAuthorizeUrl($state));
exit;
?>
