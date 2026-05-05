<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/shared_functions.php';
require_once BASE_PATH . '/common/auth.php';

commonRestoreRememberedUser();
include __DIR__ . '/inc/org.php';

$returnTo = commonNormalizeLocalPath($_GET['return_to'] ?? '/lms/', '/lms/');
$organizationColor = commonGetOrganizationExplicitColor($org);
$config = [
	'loginSendPath' => '/common/login_send.php',
	'loginVerifyPath' => '/common/login_verify.php',
	'returnTo' => $returnTo,
	'orgDomain' => $org['domain'] ?? '',
	'orgName' => $org['name'] ?? '',
	'hasOrgDomain' => !empty($org['domain']),
];
?>
<style>
.lms-login-drawer-shell {
	padding: 8px;
	background: transparent;
}

.lms-login-drawer-shell .auth-card {
	padding: 24px;
	background: rgba(255, 255, 255, 0.96);
	border: 1px solid var(--auth-border, #e5e7eb);
	border-radius: 18px;
	box-shadow: 0 24px 80px rgba(15, 23, 42, 0.08);
}

.lms-login-drawer-shell .auth-card h2 {
	font-size: 28px;
}

.lms-login-drawer-shell .auth-copy {
	margin-bottom: 6px;
}
</style>
<?php if ($organizationColor !== ''): ?>
<style>
	:root {
		--color-primary: <?php echo htmlspecialchars($organizationColor); ?>;
		--auth-primary: <?php echo htmlspecialchars($organizationColor); ?>;
	}
</style>
<?php endif; ?>
<div class="lms-login-drawer-shell">
	<div class="auth-card">
		<h2>Connexion</h2>
		<p class="auth-copy">Un code de connexion vous sera envoye par e-mail.</p>

		<div class="auth-email-row" id="authEmailRow">
			<input type="text" id="authEmailInput" placeholder="<?php echo !empty($org['domain']) ? 'username' : 'nom@domaine.ch'; ?>" autofocus>
			<?php if (!empty($org['domain'])): ?>
				<div class="auth-email-domain" id="authEmailDomain">@<?php echo htmlspecialchars($org['domain']); ?></div>
			<?php endif; ?>
		</div>

		<?php if (!empty($org['domain'])): ?>
			<button type="button" class="auth-link-btn" id="authToggleMode">Utiliser une autre adresse e-mail</button>
		<?php endif; ?>

		<label class="auth-remember">
			<input type="checkbox" id="authRememberMe"> Se souvenir de moi sur cet appareil
		</label>

		<div id="authChallengeBox" class="auth-challenge" style="display:none;">
			<p id="authChallengeQuestion"></p>
			<input type="text" id="authChallengeAnswer" placeholder="Votre reponse">
			<button type="button" id="authChallengeSubmit">Valider</button>
		</div>

		<div id="authCodeBox" class="auth-code-box" style="display:none;">
			<p>Entrez le code recu par e-mail sur cet appareil.</p>
			<input type="text" id="authCodeInput" inputmode="text" autocomplete="one-time-code" maxlength="6" placeholder="ABC123">
			<button type="button" id="authCodeSubmit">Valider le code</button>
		</div>

		<button type="button" class="auth-link-btn auth-resend" id="authResendLink" style="display:none;">Envoyer un nouveau code</button>
		<button type="button" class="auth-submit" id="authLoginSubmit">Envoyer le code</button>
		<div id="authStatus" class="auth-status" aria-live="polite"></div>
	</div>
</div>

<script>
window.commonLoginConfig = <?php echo json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
</script>
<script src="/common/assets/auth.js"></script>
