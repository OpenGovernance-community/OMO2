<?php
declare(strict_types=1);

function indexingCheck(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

$root = dirname(__DIR__);
$fixture = sys_get_temp_dir() . '/omo-indexing-' . bin2hex(random_bytes(8));
mkdir($fixture . '/common', 0700, true);
mkdir($fixture . '/includes', 0700, true);
mkdir($fixture . '/docker/app', 0700, true);
foreach (['common/search_indexing.php', 'common/environment_subdomains.php', 'includes/env.php'] as $file) {
    copy($root . '/' . $file, $fixture . '/' . $file);
}
file_put_contents($fixture . '/index.php', <<<'PHP'
<?php
require_once __DIR__ . '/common/search_indexing.php';
commonSendSearchEngineIndexingHeaders();
if (isset($_GET['private'])) header('X-Robots-Tag: noindex, nofollow, noarchive');
header('Content-Type: application/json');
echo json_encode(['allowed' => commonSearchEngineIndexingAllowed()]);
PHP);
$socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
indexingCheck($socket !== false, 'Reserve fixture HTTP port.');
$address = stream_socket_get_name($socket, false);
fclose($socket);
$server = proc_open([PHP_BINARY, '-n', '-S', $address, '-t', $fixture], [
    0 => ['pipe', 'r'], 1 => ['file', $fixture . '/server.log', 'a'], 2 => ['file', $fixture . '/server.log', 'a'],
], $pipes);
indexingCheck(is_resource($server), 'Start fixture HTTP server.');
fclose($pipes[0]);

$request = static function (string $host, ?string $policy, bool $allowed, string $query = '', string $method = 'GET') use ($fixture, $address): void {
    file_put_contents($fixture . '/.env', $policy === null ? "# No indexing setting\n" : 'SEARCH_ENGINE_INDEXING=' . $policy . "\n");
    $body = file_get_contents('http://' . $address . '/' . $query, false, stream_context_create(['http' => [
        'method' => $method, 'header' => 'Host: ' . $host . "\r\n", 'ignore_errors' => true, 'timeout' => 5,
    ]]));
    $headers = implode("\n", http_get_last_response_headers() ?? []);
    indexingCheck($body !== false && str_contains($headers, '200 OK'), 'Fixture succeeds for ' . $host);
    indexingCheck(str_contains(strtolower($headers), 'x-robots-tag: noindex') === (!$allowed || $query !== ''), 'Correct HTTP indexing directive for ' . $host . ' / ' . $policy);
    if ($method !== 'HEAD') indexingCheck(json_decode($body, true)['allowed'] === $allowed, 'Effective policy for ' . $host . ' / ' . $policy);
    if ($query !== '') indexingCheck(str_contains($headers, 'noarchive'), 'Existing private-page directives remain intact.');
};

try {
    for ($attempt = 0; $attempt < 50; $attempt++) {
        $ready = @stream_socket_client('tcp://' . $address, $errno, $error, 0.1);
        if ($ready) { fclose($ready); break; }
        usleep(100000);
    }
    indexingCheck((bool)$ready, 'Fixture becomes ready.');
    foreach (['omo2.org', 'www.omo2.org', 'organisation.omo2.org', 'beta.omo2.org', 'organisation.beta.omo2.org', 'dev.omo2.org', 'deploy.omo2.org',
        'BETA.OMO2.ORG:443', 'localhost', 'localtest.me', 'organisation.localtest.me', 'omo.test', '127.0.0.1'] as $host) {
        $request($host, 'allow', true);
        $request($host, 'block', false);
    }
    $request('omo2.org', null, true);
    $request('beta.omo2.org', null, true);
    $request('beta.omo2.org', '', true);
    $request('beta.omo2.org', 'invalid', true);
    $request('omo2.org', 'allow', true, '?private=1');
    $request('beta.omo2.org', 'block', false, '', 'HEAD');
    // Local overrides must not accidentally change the production environment.
    file_put_contents($fixture . '/docker/app/.env.private', "SEARCH_ENGINE_INDEXING=block\n");
    $request('localtest.me', 'allow', false);
    $request('omo2.org', 'allow', true);

    require_once $root . '/includes/server_env_admin.php';
    $field = serverEnvAdminGetFieldMap()['SEARCH_ENGINE_INDEXING'] ?? [];
    indexingCheck(($field['default'] ?? '') === 'allow' && array_keys($field['options'] ?? []) === ['allow', 'block'], 'Existing server editor owns, defaults and validates the setting.');
    indexingCheck(serverEnvAdminValidateValues(['SEARCH_ENGINE_INDEXING' => 'block']) === [], 'Accept a valid policy.');
    indexingCheck(serverEnvAdminValidateValues(['SEARCH_ENGINE_INDEXING' => 'invalid']) !== [], 'Reject unknown policies at save time.');
    $submitted = serverEnvAdminReadSubmittedValues(['SEARCH_ENGINE_INDEXING' => 'block']);
    $values = serverEnvAdminMergeSubmittedValues($submitted, ['SEARCH_ENGINE_INDEXING' => 'allow']);
    indexingCheck($values['SEARCH_ENGINE_INDEXING'] === 'block', 'Read and merge the submitted selection.');

    // Exercise the actual writer on a disposable path; never write the installed .env.
    $writerSource = file_get_contents($root . '/includes/server_env_admin.php');
    $start = strpos($writerSource, 'function serverEnvAdminWriteValues(');
    $end = strpos($writerSource, 'function serverEnvAdminGetUnlockTtlSeconds()', $start);
    $writer = substr($writerSource, $start, $end - $start);
    eval(str_replace(['serverEnvAdminWriteValues(', 'serverEnvAdminGetEnvPath()', 'serverEnvAdminGetEnvTargetLabel()'],
        ['indexingWriteFixture(', "(\$GLOBALS['indexingFixturePath'])", "'fixture.env'"], $writer));
    $GLOBALS['indexingFixturePath'] = $fixture . '/saved.env';
    file_put_contents($GLOBALS['indexingFixturePath'], "CUSTOM_HOSTING_SETTING=keep\nSEARCH_ENGINE_INDEXING=allow\n");
    indexingWriteFixture($values);
    $saved = serverEnvAdminReadEnvDefaults($GLOBALS['indexingFixturePath']);
    indexingCheck($saved['SEARCH_ENGINE_INDEXING'] === 'block' && $saved['CUSTOM_HOSTING_SETTING'] === 'keep', 'Persist indexing policy without dropping hosting configuration.');
    echo "PASS indexing: host-independent allow/block policies, default allow, persistence, local overrides, GET/HEAD and private-page exclusions\n";
} finally {
    proc_terminate($server);
    proc_close($server);
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($fixture, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($iterator as $entry) {
        if ($entry->isDir()) rmdir($entry->getPathname());
        else unlink($entry->getPathname());
    }
    rmdir($fixture);
}
