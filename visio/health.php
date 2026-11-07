<?php
declare(strict_types=1);

// Standalone monitor: no session, login, translation generation or installer redirect.
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
http_response_code(503);
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Robots-Tag: noindex, nofollow');
ob_start(static fn (string $output): string => '');
$result = ['status' => 'error'];
try {
    require_once dirname(__DIR__) . '/includes/env.php';
    foreach (envGetRuntimeEnvPaths() as $path) loadEnv($path);
    require_once dirname(__DIR__) . '/common/visio.php';
    require_once dirname(__DIR__) . '/common/visio_health.php';
    $result = visioHealthCheck(visioConfig(), static function (): bool {
        foreach (['dbServer' => 'DB_HOST', 'dbName' => 'DB_NAME', 'dbUser' => 'DB_USER', 'dbPassword' => 'DB_PASS'] as $global => $key) {
            $GLOBALS[$global] = (string)envValue($key, '');
        }
        if ($GLOBALS['dbServer'] === '' || $GLOBALS['dbName'] === '' || $GLOBALS['dbUser'] === '') return false;
        // Direct include is required by this intentionally isolated bootstrap.
        require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';
        return \dbObject\DbObject::checkDatabaseHealth();
    });
} catch (Throwable $exception) {
    error_log('Visio health check failed (' . get_class($exception) . ').');
}
ob_end_clean();
http_response_code($result['status'] === 'ok' ? 200 : 503);
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD') echo json_encode($result, JSON_THROW_ON_ERROR);
