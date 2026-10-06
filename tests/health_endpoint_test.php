<?php
declare(strict_types=1);

function assertHealthEndpoint(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}

// Isolated HTTP fixture: real endpoint and env loader, deterministic database probe.
$fixture = sys_get_temp_dir() . '/omo-health-test-' . bin2hex(random_bytes(8));
mkdir($fixture . '/omo', 0700, true);
mkdir($fixture . '/includes', 0700, true);
mkdir($fixture . '/class/dbobject', 0700, true);
copy(dirname(__DIR__) . '/omo/health.php', $fixture . '/omo/health.php');
copy(dirname(__DIR__) . '/includes/env.php', $fixture . '/includes/env.php');
file_put_contents($fixture . '/.env', "DB_HOST=fixture\nDB_NAME=fixture\nDB_USER=fixture\nDB_PASS=private-password\n");
file_put_contents($fixture . '/class/dbobject/dbobject.class.php', <<<'PHP'
<?php
namespace dbObject;
class DbObject {
    public static function checkDatabaseHealth(): bool {
        echo 'private-bootstrap-output';
        trigger_error('private-warning-output', E_USER_WARNING);
        if (($_GET['case'] ?? '') === 'throw') {
            throw new \RuntimeException('private-password and private-host');
        }
        if (($_GET['case'] ?? '') === 'abort') {
            exit;
        }
        return ($_GET['case'] ?? '') !== 'false';
    }
}
PHP);

$socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
assertHealthEndpoint($socket !== false, 'Unable to reserve the test HTTP port.');
$address = stream_socket_get_name($socket, false);
fclose($socket);
$log = $fixture . '/server.log';
$server = proc_open([PHP_BINARY, '-n', '-S', $address, '-t', $fixture], [
    0 => ['pipe', 'r'], 1 => ['file', $log, 'a'], 2 => ['file', $log, 'a'],
], $pipes);
assertHealthEndpoint(is_resource($server), 'Unable to start the fixture HTTP server.');
fclose($pipes[0]);

try {
    for ($attempt = 0; $attempt < 50; $attempt++) {
        $ready = @stream_socket_client('tcp://' . $address, $errno, $error, 0.1);
        if ($ready) { fclose($ready); break; }
        usleep(100000);
    }
    assertHealthEndpoint((bool)$ready, 'The fixture HTTP server must become ready.');
    foreach ([
        ['GET', '', 200, '{"status":"ok","database":"ok"}'],
        ['GET', 'throw', 503, '{"status":"error","database":"unavailable"}'],
        ['GET', 'false', 503, '{"status":"error","database":"unavailable"}'],
        ['HEAD', '', 200, ''],
        ['HEAD', 'throw', 503, ''],
        ['GET', 'abort', 503, ''],
        ['GET', 'missing', 503, '{"status":"error","database":"unavailable"}'],
    ] as [$method, $case, $status, $body]) {
        if ($case === 'missing') { unlink($fixture . '/.env'); }
        $context = stream_context_create(['http' => ['method' => $method, 'ignore_errors' => true, 'timeout' => 5]]);
        $response = file_get_contents('http://' . $address . '/omo/health.php?case=' . $case, false, $context);
        $headers = function_exists('http_get_last_response_headers') ? http_get_last_response_headers() : get_defined_vars()['http_response_header'];
        assertHealthEndpoint(str_contains($headers[0], ' ' . $status . ' '), 'Unexpected HTTP status for ' . $method . '/' . $case);
        assertHealthEndpoint($response === $body, 'Responses must remain minimal and hide diagnostics for ' . $case);
        $headerText = strtolower(implode("\n", $headers));
        assertHealthEndpoint(str_contains($headerText, 'content-type: application/json'), 'Responses must be JSON.');
        assertHealthEndpoint(str_contains($headerText, 'cache-control: no-store'), 'Responses must not be cached.');
        assertHealthEndpoint(str_contains($headerText, 'x-robots-tag: noindex'), 'Search engines must not index the endpoint.');
        assertHealthEndpoint(!str_contains($headerText, 'set-cookie:') && !str_contains($headerText, 'location:'), 'Health checks must not create sessions or redirect.');
    }
    echo "health_endpoint_test: OK\n";
} finally {
    proc_terminate($server);
    proc_close($server);
    // Remove only this test's explicitly named fixture files and empty directories.
    foreach (['omo/health.php', 'includes/env.php', 'class/dbobject/dbobject.class.php', '.env', 'server.log'] as $relative) {
        if (is_file($fixture . '/' . $relative)) { unlink($fixture . '/' . $relative); }
    }
    foreach (['omo', 'includes', 'class/dbobject', 'class', ''] as $relative) { rmdir($fixture . '/' . $relative); }
}
