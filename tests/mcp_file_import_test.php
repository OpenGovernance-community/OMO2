<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';
use dbObject\{McpDocumentCreation, Document};
$before = $_SESSION; $items = []; $process = null;
$directory = sys_get_temp_dir() . '/omo-mcp-storage-' . bin2hex(random_bytes(8));
mkdir($directory, 0700);
$socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
mcpCheck(is_resource($socket), 'Local storage fixture port');
$address = stream_socket_get_name($socket, false); fclose($socket);
$secret = bin2hex(random_bytes(24));
try {
    mcpCheck(PHP_SAPI === 'cli' && !preg_match('/(?:fpm|cgi)/i', basename(PHP_BINARY)), 'File test requires PHP CLI');
    $process = proc_open([PHP_BINARY, '-S', $address, __DIR__ . '/mcp_storage_fixture.php'],
        [0 => ['file', '/dev/null', 'r'], 1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes, null,
        ['MCP_TEST_STORAGE_SECRET' => $secret, 'MCP_TEST_STORAGE_DIR' => $directory]);
    mcpCheck(is_resource($process), 'Local test storage started');
    $port = (int)substr($address, strrpos($address, ':') + 1); $ready = false;
    for ($attempt = 0; $attempt < 50; $attempt++) {
        $probe = @fsockopen('127.0.0.1', $port, $errno, $error, 0.1);
        if ($probe) { fclose($probe); $ready = true; break; }
        usleep(100000);
    }
    mcpCheck($ready, 'Local storage fixture is ready');
    $items = mcpFixtures(); mcpEnableDocumentCreation($items);
    $items['documents_app']->setParametersArray(['storage' => ['type' => 'nextcloud', 'baseUrl' => 'http://' . $address,
        'username' => 'fixture', 'appPassword' => $secret, 'folder' => '']]);
    $items['documents_app']->save();
    $oid = (int)$items['org']->getId(); $uid = (int)$items['user']->getId(); $hid = (int)$items['role']->getId();
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid];
    $authorization = mcpAuthorizationRequest($items['client']); $authorization['scope'] = OMO_MCP_SCOPE . ' ' . OMO_MCP_CREATE_SCOPE;
    $code = \dbObject\McpOauthGrant::issueCode($items['client'], $uid, $oid, $authorization);
    $tokens = \dbObject\McpOauthGrant::exchange($items['client'], mcpExchangeRequest($items['client'], $code));
    $grant = \dbObject\McpOauthGrant::authenticate($tokens['access_token'], omoMcpPublicUrl());
    $args = ['title' => 'MCP original attachment', 'holon_id' => $hid, 'request_key' => 'original-file-import',
        'file' => ['file_id' => 'fixture-remote-file', 'file_name' => 'attachment.txt', 'mime_type' => 'application/fake',
            'download_url' => 'https://raw.githubusercontent.com/OpenGovernance-community/OMO2/3e07dc4c07881b3ec90c698b471c69f0796f9d80/docs/MCP.md']];
    $temporaryBefore = glob(sys_get_temp_dir() . '/omo-mcp-*');
    $created = McpDocumentCreation::create($grant, $args);
    $items['file_document'] = new Document(); $items['file_document']->load($created['record']['record_id']);
    mcpCheck($items['file_document']->getDocumentType() === Document::TYPE_UPLOADED_FILE, 'Original file type stored');
    mcpCheck($created['file_name'] === 'attachment.txt' && $created['file_size'] > 100, 'Original file metadata returned');
    mcpCheck($items['file_document']->get('storedfilemime') !== 'application/fake', 'MIME detected from bytes, not trusted from caller');
    $stored = glob($directory . '/*.bin');
    mcpCheck(count($stored) === 1 && str_starts_with(file_get_contents($stored[0]), '# Serveur MCP OMO'), 'Downloaded bytes reached normal OMO storage');
    $retryArgs = $args; $retryArgs['file']['download_url'] = 'https://example.invalid/expired';
    $retry = McpDocumentCreation::create($grant, $retryArgs);
    mcpCheck($retry['replayed'] && $retry['record']['record_id'] === $created['record']['record_id'], 'Retry succeeds without downloading expired signed URL');
    file_put_contents($directory . '/fail-put', '1');
    $failed = false;
    try { McpDocumentCreation::create($grant, array_replace($args, ['request_key' => 'failed-storage-import'])); }
    catch (DomainException $error) { $failed = true; }
    mcpCheck($failed, 'Storage failure reported');
    $records = new \dbObject\ArrayDocument();
    $records->load(['where' => [['field' => 'IDorganization', 'value' => $oid]]]);
    mcpCheck(count($records) === 1, 'Storage failure rolls back document instead of leaving a placeholder');
    mcpCheck(glob(sys_get_temp_dir() . '/omo-mcp-*') === $temporaryBefore, 'Temporary downloaded files removed on success and failure');
} finally {
    mcpCleanup($items); $_SESSION = $before;
    if (is_resource($process)) { proc_terminate($process); proc_close($process); }
    foreach (glob($directory . '/*') as $file) unlink($file);
    rmdir($directory);
}
echo "mcp_file_import_test: OK (public HTTPS source, local mock storage, retry, rollback, cleanup)\n";
