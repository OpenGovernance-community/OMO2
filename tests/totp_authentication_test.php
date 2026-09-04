<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/common/auth.php';

function assertTotpAuthenticationTest(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$encryptionKey = 'test-only-totp-encryption-key-with-at-least-thirty-two-characters';
putenv('AUTH_TOTP_ENCRYPTION_KEY=' . $encryptionKey);
$_ENV['AUTH_TOTP_ENCRYPTION_KEY'] = $encryptionKey;
$_SERVER['AUTH_TOTP_ENCRYPTION_KEY'] = $encryptionKey;

$rfcSecret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';
assertTotpAuthenticationTest(
    commonTotpGetCode($rfcSecret, 59) === '287082',
    'TOTP must match the RFC 6238 SHA-1 test vector reduced to six digits.'
);
assertTotpAuthenticationTest(
    commonTotpVerifyCode($rfcSecret, '287082', 59),
    'A valid TOTP code must be accepted.'
);
assertTotpAuthenticationTest(
    !commonTotpVerifyCode($rfcSecret, '000000', 59),
    'An invalid TOTP code must be rejected.'
);

$secret = commonTotpGenerateSecret();
$encrypted = commonTotpEncryptSecret($secret);
assertTotpAuthenticationTest(
    is_string($encrypted) && $encrypted !== '' && !str_contains($encrypted, $secret),
    'TOTP secrets must be encrypted before database storage.'
);
assertTotpAuthenticationTest(
    commonTotpDecryptSecret($encrypted) === $secret,
    'Encrypted TOTP secrets must decrypt with the configured key.'
);
assertTotpAuthenticationTest(
    str_starts_with((string)commonTotpBuildQrDataUrl(commonTotpBuildProvisioningUri($secret, 'user@example.test')), 'data:image/png;base64,'),
    'The TOTP QR encoder must produce a valid PNG image.'
);

$authSource = file_get_contents(dirname(__DIR__) . '/common/auth.php');
$cardDavSource = file_get_contents(dirname(__DIR__) . '/common/carddav.php');
$authJavaScript = file_get_contents(dirname(__DIR__) . '/common/assets/auth.js');
$migration = file_get_contents(dirname(__DIR__) . '/sql/2026-09-04-03-user-totp-authentication.sql');
$qrEndpoint = file_get_contents(dirname(__DIR__) . '/ajax/totp_qr.php');
assertTotpAuthenticationTest(
    is_string($authSource)
        && str_contains($authSource, 'commonBeginTotpLogin($user, $ipAddress, $remember)')
        && str_contains($authSource, 'function commonHandleTotpLoginVerify')
        && str_contains($authSource, "if ((bool)\$loginToken->get('mfa_pending'))"),
    'Both password and email login flows must require TOTP before creating a session.'
);
assertTotpAuthenticationTest(
    is_string($qrEndpoint)
        && str_contains($qrEndpoint, "\$_SESSION['pending_totp_setup']")
        && str_contains($qrEndpoint, 'hash_equals'),
    'The TOTP QR endpoint must require the authenticated pending setup session.'
);
assertTotpAuthenticationTest(
    is_string($authJavaScript)
        && str_contains($authJavaScript, 'const pendingTokenLifetimeMs = 5 * 60 * 1000;')
        && str_contains($authJavaScript, 'Tokens stored by older versions had no expiry')
        && str_contains($authJavaScript, 'function expirePendingTokens()')
        && str_contains($authJavaScript, 'function setFirstFactorVisible(visible)')
        && str_contains($authJavaScript, "'auth.totp.title': 'Double authentification'"),
    'Browser-stored login steps must expire and first-factor fields must not be shown blank during TOTP.'
);
assertTotpAuthenticationTest(
    is_string($cardDavSource) && !str_contains($cardDavSource, 'totp_'),
    'CalDAV/CardDAV must not require browser TOTP authentication.'
);
assertTotpAuthenticationTest(
    is_string($migration)
        && str_starts_with($migration, '-- @migration')
        && str_contains($migration, 'totp_enabled')
        && str_contains($migration, 'mfa_pending'),
    'The TOTP schema changes must be deployed as an automatic migration.'
);

echo "totp_authentication_test: OK\n";
