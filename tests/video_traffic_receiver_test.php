<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/common/visio_traffic.php';
function trafficReceiverExpect(bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); }
$fixture = sys_get_temp_dir() . '/omo-traffic-receiver-' . bin2hex(random_bytes(8));
foreach (['visio', 'common', 'includes', 'class/dbobject'] as $directory) mkdir($fixture . '/' . $directory, 0700, true);
foreach (['visio/traffic.php', 'common/visio.php', 'common/visio_traffic.php', 'includes/env.php'] as $file) copy(dirname(__DIR__) . '/' . $file, $fixture . '/' . $file);
$secret = str_repeat('private-jwt-key-', 3);
file_put_contents($fixture . '/.env', "JITSI_ENABLED=true\nJITSI_URL=https://meet.example.com\nJITSI_APP_SECRET=$secret\nDB_HOST=fixture\nDB_NAME=fixture\nDB_USER=fixture\n");
file_put_contents($fixture . '/class/dbobject/dbobject.class.php', '<?php namespace dbObject; class DbObject {}');
file_put_contents($fixture . '/class/dbobject/videotrafficcollector.class.php', <<<'PHP'
<?php namespace dbObject;
class VideoTrafficCollector { public static function failure(): void { file_put_contents(dirname(__DIR__, 2) . '/failure', 'recorded'); } }
PHP);
file_put_contents($fixture . '/class/dbobject/videotraffic.class.php', <<<'PHP'
<?php namespace dbObject;
class VideoTraffic {
    public static function ingest(array $observations): array {
        file_put_contents(dirname(__DIR__, 2) . '/stored', 'received');
        echo 'private-bootstrap-output';
        if (is_file(dirname(__DIR__, 2) . '/fail-storage')) throw new RuntimeException('private-database-details');
        return ['samples' => 0, 'endpoints' => count($observations), 'unmatched' => 0];
    }
}
PHP);
$socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
$address = stream_socket_get_name($socket, false); fclose($socket);
$server = proc_open([PHP_BINARY, '-S', $address, '-t', $fixture], [0 => ['pipe', 'r'], 1 => ['file', $fixture . '/log', 'a'], 2 => ['file', $fixture . '/log', 'a']], $pipes);
trafficReceiverExpect(is_resource($server), 'Start HTTP receiver fixture'); fclose($pipes[0]);
$send = static function (string $method, string $body, ?string $timestamp = null, ?string $key = null) use ($address, $secret): array {
    $timestamp ??= (string)time();
    $signature = hash_hmac('sha256', $timestamp . "\n" . $body, $key ?? visioTrafficSigningKey($secret));
    $curl = curl_init('http://' . $address . '/visio/traffic.php');
    $headers = [];
    curl_setopt_array($curl, [CURLOPT_CUSTOMREQUEST => $method, CURLOPT_POSTFIELDS => $body, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Cookie: PHPSESSID=private-browser-session', 'X-OMO-Traffic-Timestamp: ' . $timestamp, 'X-OMO-Traffic-Signature: ' . $signature],
        CURLOPT_HEADERFUNCTION => static function ($handle, string $line) use (&$headers): int { $headers[] = $line; return strlen($line); }]);
    $response = curl_exec($curl);
    return [(int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE), $response, strtolower(implode('', $headers))];
};
try {
    for ($i = 0; $i < 50; $i++) {
        $ready = @stream_socket_client('tcp://' . $address, $errno, $error, 0.1);
        if ($ready) { fclose($ready); break; } usleep(100000);
    }
    $valid = '{"version":1,"source_status":"ok","observations":[]}';
    foreach ([['GET', $valid, null, null, 405], ['POST', $valid, null, 'wrong-key', 403],
        ['POST', $valid, (string)(time() - 300), null, 403], ['POST', $valid, (string)(time() + 300), null, 403],
        ['POST', str_repeat('x', 1048577), null, null, 413], ['POST', '{', null, null, 422],
        ['POST', '{"version":2,"source_status":"ok","observations":[]}', null, null, 422],
    ] as [$method, $body, $timestamp, $key, $expected]) {
        [$status, $response, $headers] = $send($method, $body, $timestamp, $key);
        trafficReceiverExpect($status === $expected, 'Reject invalid request with expected status: ' . $expected . '/' . $status);
        trafficReceiverExpect(!is_file($fixture . '/stored') && !is_file($fixture . '/failure'), 'No database work before successful authentication and envelope validation');
    }
    [$status, $response, $headers] = $send('POST', $valid);
    trafficReceiverExpect($status === 200 && json_decode($response, true)['status'] === 'ok', 'Accept valid signed heartbeat');
    trafficReceiverExpect(is_file($fixture . '/stored') && !str_contains($response, 'private-'), 'Only minimal receiver result is returned');
    trafficReceiverExpect(!str_contains($headers, 'set-cookie:') && !str_contains($headers, 'location:') && str_contains($headers, 'cache-control: no-store'), 'No browser session, redirect or cache');
    [$status] = $send('POST', '{"version":1,"source_status":"unavailable","observations":[]}');
    trafficReceiverExpect($status === 200 && is_file($fixture . '/failure'), 'Source failures reach the status table');
    file_put_contents($fixture . '/fail-storage', 'yes');
    [$status, $response] = $send('POST', $valid);
    trafficReceiverExpect($status === 503 && $response === '{"status":"error"}', 'Storage failure remains unavailable and hides diagnostics');
    echo "video_traffic_receiver_test: OK (authentication, expiry, size, protocol, heartbeat, source/storage failure and privacy)\n";
} finally {
    proc_terminate($server); proc_close($server);
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($fixture, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    rmdir($fixture);
}
