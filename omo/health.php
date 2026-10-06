<?php
declare(strict_types=1);

// Fail closed, including when bootstrap fails; never expose PHP diagnostics.
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
http_response_code(503);
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Robots-Tag: noindex, nofollow');
ob_start(static fn (string $output): string => '');

$healthy = false;
try {
    // Intentionally isolated from shared_functions: no login, session or installer.
    require_once dirname(__DIR__) . '/includes/env.php';
    foreach (envGetRuntimeEnvPaths() as $envPath) {
        loadEnv($envPath);
    }
    foreach (['dbServer' => 'DB_HOST', 'dbName' => 'DB_NAME', 'dbUser' => 'DB_USER', 'dbPassword' => 'DB_PASS'] as $global => $key) {
        $GLOBALS[$global] = (string)envValue($key, '');
    }
    if ($GLOBALS['dbServer'] === '' || $GLOBALS['dbName'] === '' || $GLOBALS['dbUser'] === '') {
        throw new RuntimeException('Database configuration unavailable.');
    }
    // Direct include is required by this standalone, minimal bootstrap.
    require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';
    $healthy = \dbObject\DbObject::checkDatabaseHealth();
} catch (Throwable $exception) {
    error_log('OMO health check failed (' . get_class($exception) . ').');
}

ob_end_clean();
http_response_code($healthy ? 200 : 503);
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD') {
    echo $healthy ? '{"status":"ok","database":"ok"}' : '{"status":"error","database":"unavailable"}';
}
