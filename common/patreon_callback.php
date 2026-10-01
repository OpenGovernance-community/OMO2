<?php

require_once '../shared_functions.php';
require_once __DIR__ . '/patreon.php';

function patreonRenderCallbackPage($title, $message, $isSuccess, $returnOrigin = '')
{
	$title = (string)$title;
	$message = (string)$message;
	$returnOrigin = (string)$returnOrigin;
	$state = $isSuccess ? 'success' : 'error';
	?>
<!DOCTYPE html>
<html lang="fr">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?= htmlspecialchars($title) ?></title>
	<style>
		body {
			margin: 0;
			padding: 24px;
			font-family: Arial, Helvetica, sans-serif;
			background: #f8fafc;
			color: #0f172a;
		}
		.patreon-callback-card {
			max-width: 560px;
			margin: 40px auto;
			padding: 24px;
			border-radius: var(--radius-md);
			background: #ffffff;
			box-shadow: 0 20px 40px rgba(15, 23, 42, 0.12);
			border: 1px solid #e2e8f0;
		}
		.patreon-callback-card h1 {
			margin-top: 0;
			font-size: 24px;
		}
		.patreon-callback-card p {
			line-height: 1.6;
		}
		.patreon-callback-card--success {
			border-color: #86efac;
		}
		.patreon-callback-card--error {
			border-color: #fca5a5;
		}
	</style>
</head>
<body>
	<div class="patreon-callback-card patreon-callback-card--<?= htmlspecialchars($state) ?>">
		<h1><?= htmlspecialchars($title) ?></h1>
		<p><?= nl2br(htmlspecialchars($message)) ?></p>
		<?php if ($isSuccess): ?>
		<p>Cette fenêtre peut se fermer automatiquement.</p>
		<script>
			(function () {
				var returnOrigin = <?= json_encode($returnOrigin, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
				if (window.opener && !window.opener.closed) {
					try {
						window.opener.postMessage({ type: 'patreon-connected' }, returnOrigin);
					} catch (error) {
					}
				}

				setTimeout(function () {
					window.close();
				}, 1200);
			})();
		</script>
		<?php endif; ?>
	</div>
</body>
</html>
<?php
}

if (!patreonIsConfigured('oauth')) {
	patreonRenderCallbackPage('Configuration Patreon manquante', 'La configuration Patreon est incomplète : ' . patreonGetConfigurationMessage('oauth'), false);
	exit;
}

if (!patreonIsConnectHubRequest()) {
	patreonRenderCallbackPage('Connexion Patreon impossible', 'Le rappel Patreon doit être traité sur le domaine central configuré.', false);
	exit;
}

$receivedState = trim((string)($_GET['state'] ?? ''));
if ($receivedState === '') {
	patreonRenderCallbackPage('Connexion Patreon impossible', 'La session de sécurité a expiré. Merci de relancer la connexion depuis votre profil.', false);
	exit;
}

$transaction = \dbObject\PatreonOauthTransaction::claimByStateHash(hash('sha256', $receivedState));
if (!($transaction instanceof \dbObject\PatreonOauthTransaction)) {
	patreonRenderCallbackPage('Connexion Patreon expirée', 'La demande de connexion a expiré ou a déjà été utilisée. Merci de la relancer depuis votre profil.', false);
	exit;
}

$returnOrigin = (string)$transaction->get('return_origin');
if (!patreonIsAllowedReturnOrigin($returnOrigin)) {
	$transaction->markFailed();
	patreonRenderCallbackPage('Connexion Patreon refusée', 'Le domaine de retour n’est plus autorisé.', false);
	exit;
}

$userId = (int)$transaction->get('IDuser');
if ($userId <= 0) {
	$transaction->markFailed();
	patreonRenderCallbackPage('Connexion Patreon impossible', 'Utilisateur associé introuvable.', false);
	exit;
}

if (!empty($_GET['error'])) {
	$errorDescription = trim((string)($_GET['error_description'] ?? (string)$_GET['error']));
	$transaction->markFailed();
	patreonRenderCallbackPage('Connexion Patreon annulée', $errorDescription !== '' ? $errorDescription : 'L’autorisation Patreon a été refusée.', false);
	exit;
}

$code = trim((string)($_GET['code'] ?? ''));
if ($code === '') {
	$transaction->markFailed();
	patreonRenderCallbackPage('Connexion Patreon impossible', 'Aucun code OAuth Patreon n’a été reçu.', false);
	exit;
}

try {
	$tokens = patreonExchangeCodeForTokens($code);
	$connection = \dbObject\UserPatreon::loadOrCreateByUserId($userId);
	$connection->applyOauthTokens($tokens);
	$saveResult = $connection->save();
	if (empty($saveResult['status'])) {
		throw new RuntimeException('Impossible d’enregistrer les jetons Patreon.');
	}

	patreonSyncConnection($connection);
	if (empty($transaction->markCompleted()['status'])) {
		throw new RuntimeException('La connexion est active, mais sa confirmation n’a pas pu être enregistrée.');
	}
	patreonRenderCallbackPage('Compte Patreon connecté', 'La connexion Patreon est active et l’état de l’abonnement a été synchronisé.', true, $returnOrigin);
} catch (Throwable $exception) {
	$transaction->markFailed();
	patreonRenderCallbackPage('Connexion Patreon impossible', $exception->getMessage(), false);
}

exit;
?>
