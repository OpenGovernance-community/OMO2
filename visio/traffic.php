<?php
declare(strict_types=1);

// Stateless machine endpoint: authenticate before loading any database code.
ini_set('display_errors', '0');
http_response_code(503);
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo '{"status":"error"}';
    exit;
}
ob_start(static fn (string $output): string => '');
$status = 503;
$result = ['status' => 'error'];
$authenticated = false;
try {
    require_once dirname(__DIR__) . '/includes/env.php';
    foreach (envGetRuntimeEnvPaths() as $path) loadEnv($path);
    require_once dirname(__DIR__) . '/common/visio.php';
    require_once dirname(__DIR__) . '/common/visio_traffic.php';
    $config = visioConfig();
    $body = file_get_contents('php://input', false, null, 0, 1048577);
    if (!is_string($body) || strlen($body) > 1048576) {
        $status = 413;
    } elseif (!$config['enabled'] || !visioTrafficAuthenticate($body,
        (string)($_SERVER['HTTP_X_OMO_TRAFFIC_TIMESTAMP'] ?? ''),
        (string)($_SERVER['HTTP_X_OMO_TRAFFIC_SIGNATURE'] ?? ''), $config['secret'])) {
        $status = 403;
    } else {
        $authenticated = true;
        $payload = json_decode($body, true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($payload) || ($payload['version'] ?? null) !== 1
            || !in_array($payload['source_status'] ?? '', ['ok', 'unavailable'], true)
            || !is_array($payload['observations'] ?? null)) throw new InvalidArgumentException('Invalid traffic payload');
        foreach (['dbServer' => 'DB_HOST', 'dbName' => 'DB_NAME', 'dbUser' => 'DB_USER', 'dbPassword' => 'DB_PASS'] as $global => $key) {
            $GLOBALS[$global] = (string)envValue($key, '');
        }
        if ($GLOBALS['dbServer'] === '' || $GLOBALS['dbName'] === '' || $GLOBALS['dbUser'] === '') throw new RuntimeException('Missing database settings');
        // Direct includes are required by this intentionally isolated, session-free bootstrap.
        require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';
        require_once dirname(__DIR__) . '/class/dbobject/videotrafficcollector.class.php';
        require_once dirname(__DIR__) . '/class/dbobject/videotraffic.class.php';
        if ($payload['source_status'] === 'unavailable') {
            if ($payload['observations'] !== []) throw new InvalidArgumentException('Invalid failed source payload');
            \dbObject\VideoTrafficCollector::failure();
            $result = ['status' => 'ok', 'source_status' => 'unavailable'];
        } else {
            $result = ['status' => 'ok'] + \dbObject\VideoTraffic::ingest($payload['observations']);
        }
        $status = 200;
    }
} catch (JsonException | InvalidArgumentException $error) {
    $status = 422;
} catch (Throwable $error) {
    error_log('Visio traffic receiver failed (' . get_class($error) . ').');
    if ($authenticated && class_exists('dbObject\\VideoTrafficCollector', false)) {
        try { \dbObject\VideoTrafficCollector::failure(); } catch (Throwable $ignored) {}
    }
}
ob_end_clean();
http_response_code($status);
echo json_encode($result, JSON_THROW_ON_ERROR);
