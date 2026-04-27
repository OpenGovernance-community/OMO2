<?php

require_once '../shared_functions.php';
require_once __DIR__ . '/patreon.php';

function patreonRenderCallbackPage($title, $message, $isSuccess)
{
	$title = (string)$title;
	$message = (string)$message;
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
			border-radius: 18px;
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
				if (window.opener && !window.opener.closed) {
					try {
						window.opener.postMessage({ type: 'patreon-connected' }, window.location.origin);
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

if (!patreonIsConfigured()) {
	patreonRenderCallbackPage('Configuration Patreon manquante', 'Les identifiants Patreon ne sont pas encore configurés sur ce site.', false);
	exit;
}

if (!isset($_SESSION['patreon_oauth_state']) || !is_array($_SESSION['patreon_oauth_state'])) {
	patreonRenderCallbackPage('Connexion Patreon impossible', 'La session de sécurité a expiré. Merci de relancer la connexion depuis votre profil.', false);
	exit;
}

$storedState = $_SESSION['patreon_oauth_state'];
unset($_SESSION['patreon_oauth_state']);

$createdAt = (int)($storedState['created_at'] ?? 0);
if ($createdAt <= 0 || $createdAt < (time() - 1800)) {
	patreonRenderCallbackPage('Connexion Patreon expirée', 'La demande de connexion a expiré. Merci de relancer l’autorisation depuis votre profil.', false);
	exit;
}

$receivedState = (string)($_GET['state'] ?? '');
if ($receivedState === '' || !hash_equals((string)($storedState['token'] ?? ''), $receivedState)) {
	patreonRenderCallbackPage('Connexion Patreon refusée', 'Le jeton de sécurité OAuth est invalide.', false);
	exit;
}

$userId = (int)($storedState['user_id'] ?? 0);
if ($userId <= 0) {
	patreonRenderCallbackPage('Connexion Patreon impossible', 'Utilisateur associé introuvable.', false);
	exit;
}

if (!empty($_GET['error'])) {
	$errorDescription = trim((string)($_GET['error_description'] ?? (string)$_GET['error']));
	patreonRenderCallbackPage('Connexion Patreon annulée', $errorDescription !== '' ? $errorDescription : 'L’autorisation Patreon a été refusée.', false);
	exit;
}

$code = trim((string)($_GET['code'] ?? ''));
if ($code === '') {
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
	patreonRenderCallbackPage('Compte Patreon connecté', 'La connexion Patreon est active et l’état de l’abonnement a été synchronisé.', true);
} catch (Throwable $exception) {
	patreonRenderCallbackPage('Connexion Patreon impossible', $exception->getMessage(), false);
}

exit;
?>
