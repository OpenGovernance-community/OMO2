<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/includes/env.php';
require_once dirname(__DIR__) . '/common/auth.php';

function authMailConfigCheck(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

// No database, token creation or mail delivery is needed to validate configuration.
if (($argv[1] ?? '') === 'invalid') {
    ini_set('error_log', 'php://stderr');
    $_ENV['AUTH_PUBLIC_URL'] = $argv[2] ?? '';
    $_SERVER['HTTP_HOST'] = 'beta.opengov.tools';
    register_shutdown_function(static function (): void { echo "\n" . http_response_code(); });
    commonAuthRequireMailConfiguration([], commonGetAuthPhpSourceLang());
    throw new RuntimeException('Invalid configuration was accepted');
}

foreach (['', 'http://beta.opengov.tools', 'https://beta.opengov.tools/not-an-origin'] as $url) {
    $process = proc_open([PHP_BINARY, __FILE__, 'invalid', $url],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    authMailConfigCheck(is_resource($process), 'Unable to start configuration check');
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    authMailConfigCheck(proc_close($process) === 0, 'Configuration response failed: ' . $error);
    [$body, $status] = explode("\n", trim($output), 2);
    $response = json_decode($body, true);
    authMailConfigCheck($status === '503' && ($response['error'] ?? '') === 'mail_configuration'
        && !empty($response['message']), 'Invalid configuration must return an explicit JSON service error');
    authMailConfigCheck(str_contains($error, 'AUTH_PUBLIC_URL'), 'Server log must identify the missing or invalid setting');
}

$_ENV['AUTH_PUBLIC_URL'] = 'https://beta.opengov.tools';
$_SERVER['HTTP_HOST'] = 'untrusted.invalid';
commonAuthRequireMailConfiguration([], commonGetAuthPhpSourceLang());
authMailConfigCheck(commonSecurityUrl('/common/login_verify.php?token=test') === 'https://beta.opengov.tools/common/login_verify.php?token=test',
    'Configured links must use the canonical origin, regardless of request Host');
$_ENV['AUTH_PUBLIC_URL'] = '';
$_SERVER['HTTP_HOST'] = 'localhost';
commonAuthRequireMailConfiguration([], commonGetAuthPhpSourceLang());
echo "auth_mail_configuration_test: OK\n";
