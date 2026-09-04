<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/common/auth.php';

function assertAuthRateLimitTest(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

putenv('AUTH_RATE_LIMIT_SECRET=test-only-secret');
putenv('AUTH_TRUSTED_PROXY_IPS=');
$_SERVER['HTTP_HOST'] = 'example.test';
$_SERVER['REMOTE_ADDR'] = '203.0.113.20';
$_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.9';

assertAuthRateLimitTest(
    commonGetRequestIp() === '203.0.113.20',
    'Forwarded headers from an untrusted peer must be ignored.'
);

putenv('AUTH_TRUSTED_PROXY_IPS=203.0.113.0/24');
assertAuthRateLimitTest(
    commonGetRequestIp() === '198.51.100.9',
    'A trusted proxy must expose the validated forwarded client address.'
);

$_SERVER['HTTP_X_FORWARDED_FOR'] = '192.0.2.77, 198.51.100.9';
assertAuthRateLimitTest(
    commonGetRequestIp() === '198.51.100.9',
    'User supplied addresses before the proxy supplied client address must not win.'
);

assertAuthRateLimitTest(commonIpMatchesRange('10.20.30.40', '10.20.0.0/16'), 'IPv4 CIDR matching must work.');
assertAuthRateLimitTest(!commonIpMatchesRange('10.21.30.40', '10.20.0.0/16'), 'IPv4 CIDR mismatches must fail.');
assertAuthRateLimitTest(commonIpMatchesRange('2001:db8::1234', '2001:db8::/32'), 'IPv6 CIDR matching must work.');
assertAuthRateLimitTest(!commonIpMatchesRange('2001:db9::1', '2001:db8::/32'), 'IPv6 CIDR mismatches must fail.');

$passwordAccountPolicy = commonGetAuthRateLimitPolicy('password_account');
$magicEmailPolicy = commonGetAuthRateLimitPolicy('magic_email_hour');
$resetEmailPolicy = commonGetAuthRateLimitPolicy('reset_email_hour');
assertAuthRateLimitTest(
    is_array($passwordAccountPolicy)
        && $passwordAccountPolicy['maximum'] === 5
        && $passwordAccountPolicy['window'] === 900,
    'Password limits must allow five failures per account over fifteen minutes.'
);
assertAuthRateLimitTest(
    is_array($magicEmailPolicy) && $magicEmailPolicy['maximum'] === 5 && $magicEmailPolicy['window'] === 3600,
    'Magic email limits must cap hourly delivery per account.'
);
assertAuthRateLimitTest(
    is_array($resetEmailPolicy) && $resetEmailPolicy['maximum'] === 3 && $resetEmailPolicy['window'] === 3600,
    'Password reset limits must cap hourly delivery per account.'
);

$rateLimitSource = file_get_contents(dirname(__DIR__) . '/common/auth.php');
assertAuthRateLimitTest(
    is_string($rateLimitSource)
        && str_contains($rateLimitSource, "['policy' => 'reset_ip_hour', 'kind' => 'ip', 'value' => \$ipAddress]")
        && str_contains($rateLimitSource, 'if (empty($combined[\'available\']) || empty($combined[\'allowed\']))'),
    'An IP limit must stop additional account buckets once a request is rejected.'
);

$firstHash = commonAuthHashIdentifier('email', 'User@Example.test');
$secondHash = commonAuthHashIdentifier('email', 'user@example.test');
assertAuthRateLimitTest(strlen($firstHash) === 64, 'Rate limit keys must be SHA-256 HMAC values.');
assertAuthRateLimitTest(hash_equals($firstHash, $secondHash), 'Email rate limit keys must be case insensitive.');
assertAuthRateLimitTest(!str_contains($firstHash, 'user@example.test'), 'Rate limit keys must not expose email addresses.');

$temporaryRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'omo-auth-log-test-' . bin2hex(random_bytes(5));
assertAuthRateLimitTest(mkdir($temporaryRoot, 0770), 'The temporary private log directory must be created.');
putenv('RUNTIME_LOG_DIR=' . $temporaryRoot);
$_SERVER['REMOTE_ADDR'] = '198.51.100.25';
$_SERVER['HTTP_X_FORWARDED_FOR'] = '';
putenv('AUTH_TRUSTED_PROXY_IPS=');

assertAuthRateLimitTest(
    commonAuthSecurityLog('password_login', 'failed', [
        'email' => 'private@example.test',
        'reason' => 'invalid_credentials',
    ]),
    'Authentication events must be writable.'
);

$logPath = commonRuntimeLogPath('auth/authentication.jsonl');
$logContent = file_get_contents($logPath);
assertAuthRateLimitTest(is_string($logContent) && $logContent !== '', 'The authentication log must contain an event.');
assertAuthRateLimitTest(!str_contains($logContent, 'private@example.test'), 'The log must not contain raw email addresses.');
assertAuthRateLimitTest(!str_contains($logContent, '198.51.100.25'), 'The log must not contain raw IP addresses.');
$logEvent = json_decode(trim($logContent), true);
assertAuthRateLimitTest(
    is_array($logEvent) && isset($logEvent['account_hash'], $logEvent['ip_hash']),
    'The log must contain correlatable anonymized account and IP identifiers.'
);

$sharedFunctionsSource = file_get_contents(dirname(__DIR__) . '/shared_functions.php');
$legacyLoginSource = file_get_contents(dirname(__DIR__) . '/ajax/login.php');
assertAuthRateLimitTest(
    is_string($sharedFunctionsSource) && !str_contains($sharedFunctionsSource, 'currentCodeCookieValue'),
    'Legacy password-hash cookies must no longer restore a session.'
);
assertAuthRateLimitTest(
    is_string($legacyLoginSource) && !str_contains($legacyLoginSource, 'appSetCookie($currentCode'),
    'The legacy login endpoint must no longer issue password-hash cookies.'
);

$migrationContent = file_get_contents(dirname(__DIR__) . '/sql/2026-09-04-01-auth-rate-limit.sql');
assertAuthRateLimitTest(
    is_string($migrationContent) && str_starts_with($migrationContent, '-- @migration'),
    'The authentication rate limit schema must be an automatic migration.'
);

unlink($logPath);
rmdir(dirname($logPath));
rmdir($temporaryRoot);

echo "auth_rate_limit_test: OK\n";
