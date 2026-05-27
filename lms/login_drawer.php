<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/shared_functions.php';
require_once BASE_PATH . '/common/auth.php';

commonRestoreRememberedUser();
include __DIR__ . '/inc/org.php';

$returnTo = commonNormalizeLocalPath($_GET['return_to'] ?? '/lms/', '/lms/');
$organizationColor = commonGetOrganizationExplicitColor($org);
$authSourceLang = commonGetAuthPhpSourceLang();
$authLang = commonAuthLoadBundle('common_auth_page', $authSourceLang);
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
		<h2><?php echo htmlspecialchars(commonAuthT('auth.page.login.title_default', [], $authLang, $authSourceLang)); ?></h2>
		<p class="auth-copy"><?php echo htmlspecialchars(commonAuthT('auth.copy.login_code', [], $authLang, $authSourceLang)); ?></p>

		<div class="auth-email-row" id="authEmailRow">
			<input type="text" id="authEmailInput" placeholder="<?php echo htmlspecialchars(!empty($org['domain']) ? commonAuthT('auth.placeholder.username', [], $authLang, $authSourceLang) : commonAuthT('auth.placeholder.full_email', [], $authLang, $authSourceLang)); ?>" autofocus>
			<?php if (!empty($org['domain'])): ?>
				<div class="auth-email-domain" id="authEmailDomain">@<?php echo htmlspecialchars($org['domain']); ?></div>
			<?php endif; ?>
		</div>

		<?php if (!empty($org['domain'])): ?>
			<button type="button" class="auth-link-btn" id="authToggleMode"><?php echo htmlspecialchars(commonAuthT('auth.toggle.use_other_email', [], $authLang, $authSourceLang)); ?></button>
		<?php endif; ?>

		<label class="auth-remember">
			<input type="checkbox" id="authRememberMe"> <?php echo htmlspecialchars(commonAuthT('auth.remember_me', [], $authLang, $authSourceLang)); ?>
		</label>

		<div id="authChallengeBox" class="auth-challenge" style="display:none;">
			<p id="authChallengeQuestion"></p>
			<input type="text" id="authChallengeAnswer" placeholder="<?php echo htmlspecialchars(commonAuthT('auth.challenge.answer_placeholder', [], $authLang, $authSourceLang)); ?>">
			<button type="button" id="authChallengeSubmit"><?php echo htmlspecialchars(commonAuthT('auth.button.validate', [], $authLang, $authSourceLang)); ?></button>
		</div>

		<div id="authCodeBox" class="auth-code-box" style="display:none;">
			<p><?php echo htmlspecialchars(commonAuthT('auth.code.instructions', [], $authLang, $authSourceLang)); ?></p>
			<input type="text" id="authCodeInput" inputmode="text" autocomplete="one-time-code" maxlength="6" placeholder="<?php echo htmlspecialchars(commonAuthT('auth.code.placeholder', [], $authLang, $authSourceLang)); ?>">
			<button type="button" id="authCodeSubmit"><?php echo htmlspecialchars(commonAuthT('auth.button.validate_code', [], $authLang, $authSourceLang)); ?></button>
		</div>

		<button type="button" class="auth-link-btn auth-resend" id="authResendLink" style="display:none;"><?php echo htmlspecialchars(commonAuthT('auth.button.resend_code', [], $authLang, $authSourceLang)); ?></button>
		<button type="button" class="auth-submit" id="authLoginSubmit"><?php echo htmlspecialchars(commonAuthT('auth.button.send_code', [], $authLang, $authSourceLang)); ?></button>
		<div id="authStatus" class="auth-status" aria-live="polite"></div>
	</div>
</div>

<script>
window.commonLoginConfig = <?php echo json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
</script>
<script src="/common/assets/auth.js"></script>
