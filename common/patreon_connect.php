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
		body { margin: 0; padding: 24px; font-family: Arial, Helvetica, sans-serif; background: #f8fafc; color: #0f172a; }
		.patreon-connect-card { max-width: 560px; margin: 40px auto; padding: 24px; border-radius: var(--radius-md); background: #fff; box-shadow: 0 20px 40px rgba(15, 23, 42, .12); border: 1px solid #e2e8f0; }
		.patreon-connect-card h1 { margin-top: 0; font-size: 24px; }
		.patreon-connect-card p { line-height: 1.6; }
	</style>
</head>
<body>
	<div class="patreon-connect-card">
		<h1>Connexion Patreon indisponible</h1>
		<p><?= nl2br(htmlspecialchars((string)$message, ENT_QUOTES, 'UTF-8')) ?></p>
	</div>
</body>
</html>
<?php
}

function patreonAppendQueryParameter(string $url, string $name, string $value): string
{
	$separator = strpos($url, '?') === false ? '?' : '&';
	return $url . $separator . rawurlencode($name) . '=' . rawurlencode($value);
}

$handoffToken = trim((string)($_GET['handoff'] ?? ''));
if ($handoffToken !== '') {
	if (!patreonIsConnectHubRequest()) {
		http_response_code(400);
		patreonRenderConnectErrorPage('Ce point de connexion Patreon n’est pas configuré sur ce domaine.');
		exit;
	}

	if (!patreonIsConfigured('oauth')) {
		http_response_code(503);
		patreonRenderConnectErrorPage('La configuration Patreon est incomplète : ' . patreonGetConfigurationMessage('oauth'));
		exit;
	}

	$transaction = \dbObject\PatreonOauthTransaction::claimByHandoffHash(hash('sha256', $handoffToken));
	if (!($transaction instanceof \dbObject\PatreonOauthTransaction)
		|| !patreonIsAllowedReturnOrigin((string)$transaction->get('return_origin'))) {
		http_response_code(400);
		patreonRenderConnectErrorPage('La demande de connexion a expiré ou n’est pas valide. Merci de la relancer depuis votre profil.');
		exit;
	}

	$state = bin2hex(random_bytes(32));
	if (empty($transaction->prepareAuthorization(hash('sha256', $state))['status'])) {
		http_response_code(500);
		patreonRenderConnectErrorPage('Impossible de préparer la connexion Patreon. Merci de réessayer.');
		exit;
	}

	header('Location: ' . patreonBuildAuthorizeUrl($state));
	exit;
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

$returnOrigin = patreonGetRequestOrigin();
if ($returnOrigin === '' || !patreonIsAllowedReturnOrigin($returnOrigin)) {
	http_response_code(400);
	patreonRenderConnectErrorPage('Ce domaine n’est pas autorisé pour la connexion Patreon.');
	exit;
}

$connectUrl = patreonGetConnectUrl();
$handoffToken = bin2hex(random_bytes(32));
$transaction = \dbObject\PatreonOauthTransaction::createPending(
	$currentUserId,
	hash('sha256', $handoffToken),
	$returnOrigin
);
if (!($transaction instanceof \dbObject\PatreonOauthTransaction)) {
	http_response_code(500);
	patreonRenderConnectErrorPage('Impossible d’enregistrer la demande de connexion Patreon. Merci de réessayer.');
	exit;
}

header('Location: ' . patreonAppendQueryParameter($connectUrl, 'handoff', $handoffToken));
exit;
