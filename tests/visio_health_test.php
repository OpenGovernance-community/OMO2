<?php
declare(strict_types=1);

function assertVisioHealth(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

$fixture = sys_get_temp_dir() . '/omo-visio-health-' . bin2hex(random_bytes(8));
foreach (['visio', 'common', 'includes', 'class/dbobject', 'jitsi'] as $directory) mkdir($fixture . '/' . $directory, 0700, true);
foreach (['visio/health.php', 'common/visio.php', 'common/visio_health.php', 'includes/env.php'] as $file) {
    copy(dirname(__DIR__) . '/' . $file, $fixture . '/' . $file);
}
file_put_contents($fixture . '/class/dbobject/dbobject.class.php', <<<'PHP'
<?php
namespace dbObject;
class DbObject {
    public static function checkDatabaseHealth(): bool {
        echo 'private-bootstrap-output';
        trigger_error('private-warning', E_USER_WARNING);
        $mode = trim(file_get_contents(dirname(__DIR__, 2) . '/mode'));
        if ($mode === 'database') throw new \RuntimeException('private-password private-host');
        return $mode !== 'database-false';
    }
}
PHP);
file_put_contents($fixture . '/jitsi/router.php', <<<'PHP'
<?php
$mode = trim(file_get_contents(dirname(__DIR__) . '/mode'));
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($mode === 'redirect') { header('Location: /private-success'); exit; }
if (($mode === 'jicofo' && $path === '/omo-health/jicofo')
    || ($mode === 'videobridge' && $path === '/omo-health/videobridge')) {
    http_response_code(503); echo 'private-backend-diagnostics'; exit;
}
if ($path === '/external_api.js') {
    echo $mode === 'frontend' ? '<html>Login</html>' : 'window.JitsiMeetExternalAPI = function() {};';
} elseif ($path === '/config.js') {
    echo $mode === 'config-js' ? '<html>Fallback</html>' : 'config.hosts = {};';
} elseif ($path === '/omo-health/jicofo') {
    echo $mode === 'fallback' ? '<html>Jitsi</html>' : 'OK';
} elseif ($path === '/omo-health/videobridge') {
    // Healthy JVB returns an empty 200.
} elseif ($path === '/http-bind') {
    $body = file_get_contents('php://input');
    if (str_contains($body, 'type="terminate"')) {
        file_put_contents(dirname(__DIR__) . '/closed', 'closed');
        echo '<body xmlns="http://jabber.org/protocol/httpbind" type="terminate"/>';
    } else {
        echo match ($mode) {
            'signaling' => '<body xmlns="http://jabber.org/protocol/httpbind" type="terminate"/>',
            'malformed' => '<html>Not XML',
            'wrong-namespace' => '<body xmlns="http://example.com" sid="private-session"/>',
            'doctype' => '<!DOCTYPE body [<!ENTITY private SYSTEM "file:///etc/passwd">]><body xmlns="http://jabber.org/protocol/httpbind" sid="private-session">&private;</body>',
            default => '<body xmlns="http://jabber.org/protocol/httpbind" sid="private-session"/>',
        };
    }
} else { http_response_code(404); }
PHP);

$servers = [];
$start = static function (string $root, ?string $router = null) use (&$servers, $fixture): string {
    $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
    assertVisioHealth($socket !== false, 'Reserve fixture port');
    $address = stream_socket_get_name($socket, false);
    fclose($socket);
    $command = [PHP_BINARY, '-S', $address, '-t', $root];
    if ($router !== null) $command[] = $router;
    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['file', $fixture . '/server.log', 'a'], 2 => ['file', $fixture . '/server.log', 'a']], $pipes);
    assertVisioHealth(is_resource($process), 'Start fixture server');
    fclose($pipes[0]);
    $servers[] = $process;
    for ($attempt = 0; $attempt < 50; $attempt++) {
        $ready = @stream_socket_client('tcp://' . $address, $errno, $error, 0.1);
        if ($ready) { fclose($ready); return 'http://' . $address; }
        usleep(100000);
    }
    throw new RuntimeException('Fixture server did not become ready');
};
try {
    file_put_contents($fixture . '/mode', 'ok');
    $jitsi = $start($fixture . '/jitsi', $fixture . '/jitsi/router.php');
    $env = "DB_HOST=fixture\nDB_NAME=fixture\nDB_USER=fixture\nDB_PASS=private-password\n"
        . "JITSI_ENABLED=true\nJITSI_URL=$jitsi\nJITSI_APP_ID=omo2\nJITSI_APP_SECRET=" . str_repeat('s', 32) . "\nJITSI_XMPP_DOMAIN=meet.jitsi\n";
    file_put_contents($fixture . '/.env', $env);
    $origin = $start($fixture);
    foreach (['ok', 'database', 'database-false', 'frontend', 'config-js', 'jicofo', 'videobridge', 'fallback', 'redirect', 'signaling', 'malformed', 'wrong-namespace', 'doctype', 'disabled', 'invalid'] as $mode) {
        file_put_contents($fixture . '/mode', $mode);
        if ($mode === 'disabled') file_put_contents($fixture . '/.env', str_replace('JITSI_ENABLED=true', 'JITSI_ENABLED=false', $env));
        if ($mode === 'invalid') file_put_contents($fixture . '/.env', str_replace("JITSI_URL=$jitsi", "JITSI_URL=$jitsi/invalid", $env));
        foreach (['GET', 'HEAD'] as $method) {
            $context = stream_context_create(['http' => ['method' => $method, 'ignore_errors' => true, 'timeout' => 20]]);
            $body = file_get_contents($origin . '/visio/health.php?url=https://example.com&jwt=private-visitor-token', false, $context);
            $headers = http_get_last_response_headers();
            $status = $mode === 'ok' ? 200 : 503;
            assertVisioHealth(str_contains($headers[0], ' ' . $status . ' '), $method . ' ' . $mode . ' status: ' . $body);
            $headerText = strtolower(implode("\n", $headers));
            assertVisioHealth(str_contains($headerText, 'cache-control: no-store'), 'Disable all caches');
            assertVisioHealth(str_contains($headerText, 'content-type: application/json'), 'JSON responses');
            assertVisioHealth(str_contains($headerText, 'x-robots-tag: noindex'), 'Prevent indexing');
            assertVisioHealth(!str_contains($headerText, 'set-cookie:') && !str_contains($headerText, 'location:'), 'No session or login redirect');
            assertVisioHealth(!str_contains($body, 'private-') && !str_contains($body, '127.0.0.1'), 'No diagnostics, credentials or targets leaked');
            if ($method === 'HEAD') { assertVisioHealth($body === '', 'HEAD has no body'); continue; }
            $json = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
            assertVisioHealth($json['status'] === ($status === 200 ? 'ok' : 'error'), 'Machine-readable overall status');
            if ($mode === 'ok') {
                assertVisioHealth(count($json['checks']) === 6 && array_unique(array_values($json['checks'])) === ['ok'], 'All six real probes pass');
                assertVisioHealth(is_file($fixture . '/closed'), 'BOSH session is explicitly closed');
            }
        }
    }
    echo "visio_health_test: OK (15 scenarios, GET/HEAD, real HTTP probes, BOSH cleanup, privacy and fail-closed responses)\n";
} finally {
    foreach ($servers as $process) { proc_terminate($process); proc_close($process); }
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($fixture, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) { $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname()); }
    rmdir($fixture);
}
