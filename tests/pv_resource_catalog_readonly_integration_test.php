<?php
declare(strict_types=1);

// Local CLI integration: existing organization, editable PV and member; no fixtures.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$organizationId = (int)($argv[1] ?? 0);
$documentId = (int)($argv[2] ?? 0);
$userId = (int)($argv[3] ?? 0);
if (min($organizationId, $documentId, $userId) <= 0) {
    fwrite(STDERR, "Usage: php tests/pv_resource_catalog_readonly_integration_test.php ORGANIZATION_ID DOCUMENT_ID USER_ID\n");
    exit(2);
}
$projectRoot = dirname(__DIR__);
function resourceRequest(array $input, int $userId, string $method = 'GET'): array {
    global $projectRoot, $organizationId;
    $setup = '<?php ' .
        '$_SERVER["HTTP_HOST"]="localtest.me";' .
        '$_SERVER["DOCUMENT_ROOT"]=' . var_export($projectRoot, true) . ';' .
        '$_SERVER["REQUEST_URI"]="/omo/api/documents/pv/resources.php";' .
        '$_SERVER["REQUEST_METHOD"]=' . var_export($method, true) . ';' .
        'require_once ' . var_export($projectRoot . '/shared_functions.php', true) . ';' .
        '$_ENV["DB_QUERY_LOG_ENABLED"]=$_SERVER["DB_QUERY_LOG_ENABLED"]="false";putenv("DB_QUERY_LOG_ENABLED=false");' .
        '$_SESSION=' . var_export(['currentUser' => $userId, 'currentOrganization' => $organizationId], true) . ';' .
        '$_GET=$_REQUEST=' . var_export($input, true) . ';' .
        'ob_start();register_shutdown_function(static function(){ $body=ob_get_clean();echo json_encode(["code"=>http_response_code()?:200,"body"=>$body]);});' .
        'require ' . var_export($projectRoot . '/omo/api/documents/pv/resources.php', true) . ';';
    $process = proc_open([PHP_BINARY], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) throw new RuntimeException('Unable to launch CLI endpoint check.');
    fwrite($pipes[0], $setup);
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $errors = stream_get_contents($pipes[2]); fclose($pipes[2]);
    if (proc_close($process) !== 0) throw new RuntimeException('Endpoint failed: ' . $errors);
    return json_decode($output, true, 512, JSON_THROW_ON_ERROR);
}
$base = ['oid' => $organizationId, 'id' => $documentId, 'type' => 'projects'];
foreach (['documents', 'decisions', 'projects', 'checklists', 'events', 'indicators'] as $type) {
    $result = resourceRequest(array_replace($base, ['type' => $type]), $userId);
    if (!in_array($result['code'], [200, 403], true)) throw new RuntimeException('Invalid resource response: ' . $type);
    $payload = json_decode($result['body'], true, 512, JSON_THROW_ON_ERROR);
    if ($result['code'] === 200 && ($payload['status'] !== true || !is_array($payload['items']))) throw new RuntimeException('Invalid catalogue: ' . $type);
    if ($type === 'projects' && $result['code'] !== 200) throw new RuntimeException('The supplied viewer must be allowed to open this PV.');
}
$denied = [
    [array_replace($base, ['type' => 'unknown']), $userId, 'GET', 400],
    [array_replace($base, ['id' => 0]), $userId, 'GET', 403],
    [array_replace($base, ['oid' => $organizationId + 1000000000]), $userId, 'GET', 403],
    [$base, 0, 'GET', 401],
    [$base, $userId, 'POST', 405],
];
foreach ($denied as [$input, $viewerId, $method, $expectedCode]) {
    $result = resourceRequest($input, $viewerId, $method);
    if ($result['code'] !== $expectedCode) throw new RuntimeException('Expected HTTP ' . $expectedCode . ', received ' . $result['code']);
    $payload = json_decode($result['body'], true);
    if (is_array($payload) && isset($payload['items'])) throw new RuntimeException('Denied response exposes catalogue data.');
}
echo "pv_resource_catalog_readonly_integration_test: OK\n";
