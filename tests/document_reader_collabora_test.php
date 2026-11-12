<?php
declare(strict_types=1);
// Local Docker + Collabora only; isolated documents and a temporary WebDAV server.
$_SERVER['HTTP_HOST'] = 'localtest.me';
$_SERVER['HTTPS'] = 'on';
require_once __DIR__ . '/mcp_test_helpers.php';
require_once dirname(__DIR__) . '/common/collabora.php';
use dbObject\{Document, User, UserOrganization, OrganizationApplication, Application};

function readerOfficeHttp(string $path, bool $authenticated = false, ?string $post = null, array $headers = []): array
{
    $cookie = session_name() . '=' . session_id();
    if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
    $curl = curl_init('https://localhost' . $path);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false, CURLOPT_TIMEOUT => 25,
        CURLOPT_HTTPHEADER => array_merge(array_filter(['Host: localtest.me', $authenticated ? 'Cookie: ' . $cookie : null]), $headers)]);
    if ($post !== null) curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $post]);
    $body = curl_exec($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    session_start();
    mcpCheck(is_string($body), 'Office reader HTTP request failed.');
    return ['status' => $status, 'body' => $body, 'json' => json_decode($body, true)];
}

function readerOfficeToken(array $response): string
{
    mcpCheck($response['status'] === 200, 'Collabora launcher must load: ' . $response['status'] . ' ' . strip_tags($response['body']));
    $dom = new DOMDocument(); @$dom->loadHTML($response['body']);
    $xpath = new DOMXPath($dom);
    $token = $xpath->query('//input[@name="access_token"]')->item(0)?->getAttribute('value') ?? '';
    mcpCheck($token !== '', 'Launcher provides a WOPI token.');
    mcpCheck($xpath->query('//input[@name="permission"]')->item(0)?->getAttribute('value') === 'readonly', 'Launcher forces strict read-only mode.');
    return $token;
}

$before = $_SESSION;
$items = [];
$directory = sys_get_temp_dir() . '/omo-reader-office-' . bin2hex(random_bytes(8));
mkdir($directory, 0700);
$socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
mcpCheck(is_resource($socket), 'Reserve fixture port');
$address = stream_socket_get_name($socket, false); fclose($socket);
$secret = bin2hex(random_bytes(16));
$process = null;
try {
    $process = proc_open([PHP_BINARY, '-S', $address, __DIR__ . '/webdav_storage_fixture.php'],
        [0 => ['file', '/dev/null', 'r'], 1 => ['file', '/dev/null', 'w'], 2 => ['file', $directory . '/server.log', 'w']], $pipes, null,
        array_merge(getenv(), ['WEBDAV_TEST_SECRET' => $secret, 'WEBDAV_TEST_DIR' => $directory]));
    mcpCheck(is_resource($process), 'Start storage fixture');
    $ready = false;
    for ($attempt = 0; $attempt < 50; $attempt++) {
        $probe = @stream_socket_client('tcp://' . $address, $errno, $error, 0.1);
        if ($probe) { fclose($probe); $ready = true; break; }
        usleep(100000);
    }
    mcpCheck($ready, 'Storage fixture ready');
    $nonce = bin2hex(random_bytes(6));
    $items['org'] = mcpFixture(McpTestOrganization::class, ['name' => 'Office reader', 'shortname' => 'reader-office-' . $nonce]);
    $oid = (int)$items['org']->getId();
    foreach (['structure', 'documents'] as $hash) {
        $app = new Application(); mcpCheck((bool)$app->load(['hash', $hash]), 'Application exists');
        $items['app_' . $hash] = mcpFixture(OrganizationApplication::class, ['IDorganization' => $oid, 'IDapplication' => $app->getId(), 'active' => 1]);
    }
    $items['app_documents']->setParametersArray([
        'storage' => ['type' => 'webdav', 'baseUrl' => 'http://' . $address . '/dav/Compte%20test',
            'username' => 'fixture', 'appPassword' => $secret, 'folder' => 'Documents partagés'],
        'collabora' => ['baseUrl' => 'https://document.localtest.me', 'internalUrl' => 'http://collabora:9980'],
    ]);
    $items['app_documents']->save();
    $items['owner'] = mcpFixture(User::class, ['email' => 'reader-office-' . $nonce . '@example.invalid', 'active' => 1]);
    $uid = (int)$items['owner']->getId();
    $items['membership'] = mcpFixture(UserOrganization::class, ['IDorganization' => $oid, 'IDuser' => $uid, 'active' => 1]);
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid];
    $items['doc'] = mcpFixture(Document::class, ['title' => 'Office reader fixture', 'IDorganization' => $oid, 'IDuser' => $uid,
        'documenttype' => Document::TYPE_UPLOADED_FILE, 'storedfilename' => 'reader.txt', 'storedfilepath' => '/reader.txt',
        'storedfilemime' => 'text/plain', 'storedfilesize' => 21, 'active' => 1]);
    $doc = $items['doc']; $doc->saveVisibilityRule('everyone'); $doc->saveEditVisibilityRule('self');
    $content = 'READER_OFFICE_CONTENT';
    $storagePath = '/dav/Compte%20test/Documents%20partag%C3%A9s/reader.txt';
    file_put_contents($directory . '/' . hash('sha256', $storagePath) . '.bin', $content);
    $code = (string)$doc->get('readercode');
    $open = '/omo/api/documents/collabora/open.php?reader=1&code=' . $code;
    $response = readerOfficeHttp($doc->buildReaderUrl());
    mcpCheck($response['status'] === 200 && str_contains($response['body'], 'collabora/open.php?reader=1&amp;code=' . $code), 'Reader mounts Collabora using the opaque code');
    mcpCheck(!str_contains($response['body'], '/document.php?id='), 'Reader contains no numeric document URL');
    file_put_contents(dirname(__DIR__) . '/tmp/document-reader-office.html', $response['body']);
    $guestToken = readerOfficeToken(readerOfficeHttp($open));
    $doc->getCollaboraSigningSecret();
    $payload = omoCollaboraVerifyWopiToken($doc, $guestToken);
    mcpCheck(is_array($payload) && $payload['reader'] === true && $payload['userId'] === 0, 'Anonymous token is scoped to the reader');
    mcpCheck(strlen((string)$doc->get('codeedit')) >= 40, 'Legacy documents receive an unpredictable signing secret');
    $wopi = '/omo/api/documents/collabora/wopi.php/' . $doc->getId() . '?access_token=' . rawurlencode($guestToken);
    $info = readerOfficeHttp($wopi);
    mcpCheck($info['status'] === 200 && $info['json']['UserCanWrite'] === false && $info['json']['ReadOnly'] === true && $info['json']['IsAnonymousUser'] === true && $info['json']['SupportsUpdate'] === false, 'WOPI advertises strict read-only anonymous access');
    $contentsUrl = str_replace('?access_token=', '/contents?access_token=', $wopi);
    mcpCheck(readerOfficeHttp($contentsUrl)['body'] === $content, 'Collabora can retrieve actual file contents anonymously');
    mcpCheck(readerOfficeHttp($contentsUrl, false, 'FORGED_WRITE', ['X-WOPI-Override: PUT'])['status'] === 403, 'Anonymous token cannot write');
    $ownerToken = readerOfficeToken(readerOfficeHttp($open, true));
    $ownerWopi = '/omo/api/documents/collabora/wopi.php/' . $doc->getId() . '?access_token=' . rawurlencode($ownerToken);
    $ownerInfo = readerOfficeHttp($ownerWopi);
    mcpCheck($ownerInfo['json']['UserCanWrite'] === false && $ownerInfo['json']['ReadOnly'] === true, 'Owner also receives strict read-only access from the reader');
    mcpCheck(readerOfficeHttp(str_replace('?access_token=', '/contents?access_token=', $ownerWopi), true, 'OWNER_WRITE', ['X-WOPI-Override: PUT'])['status'] === 403, 'Owner reader token cannot write');
    $normalToken = omoCollaboraBuildWopiToken($doc, $uid);
    $normalInfo = readerOfficeHttp('/omo/api/documents/collabora/wopi.php/' . $doc->getId() . '?access_token=' . rawurlencode($normalToken));
    mcpCheck($normalInfo['status'] === 200 && $normalInfo['json']['UserCanWrite'] === true && $normalInfo['json']['ReadOnly'] === false, 'Normal OMO editing keeps the owner rights');
    $parts = explode('.', $guestToken);
    $payload['reader'] = false; $payload['userId'] = $uid;
    $forged = omoCollaboraBase64UrlEncode(json_encode($payload)) . '.' . $parts[1];
    mcpCheck(omoCollaboraVerifyWopiToken($doc, $forged) === false, 'Changing the read-only token invalidates its signature');
    mcpCheck(readerOfficeHttp('/omo/api/documents/collabora/open.php?reader=1&id=' . $doc->getId())['status'] === 404, 'Collabora reader requires the opaque code');
    // Optional live browser verification while this isolated storage server is running.
    if (getenv('DOCUMENT_READER_BROWSER_FIXTURE')) {
        $fixture = dirname(__DIR__) . '/tmp/document-reader-live.json';
        file_put_contents($fixture, json_encode(['url' => 'https://localtest.me' . $doc->buildReaderUrl(),
            'ownerCookie' => ['name' => session_name(), 'value' => session_id(), 'domain' => 'localtest.me', 'path' => '/', 'secure' => true],
            'editorUrl' => 'https://localtest.me/omo/api/documents/collabora/open.php?id=' . $doc->getId() . '&oid=' . $oid]));
        session_write_close();
        for ($attempt = 0; $attempt < 900 && is_file($fixture); $attempt++) {
            clearstatcache(true, $fixture);
            usleep(100000);
        }
        if (is_file($fixture)) unlink($fixture);
        session_start();
    }
    $doc->saveVisibilityRule('self');
    mcpCheck(readerOfficeHttp($open)['status'] === 404 && readerOfficeHttp($wopi)['status'] === 403, 'Visibility changes revoke existing anonymous Collabora tokens');
    mcpCheck(readerOfficeHttp($ownerWopi)['status'] === 200, 'Private owner token remains readable');
    mcpCheck(readerOfficeHttp($contentsUrl)['status'] === 403, 'Revoked anonymous token cannot download contents');
    mcpCheck(file_get_contents($directory . '/' . hash('sha256', $storagePath) . '.bin') === $content, 'Read-only attempts leave the file unchanged');
    echo "document_reader_collabora_test: OK\n";
} finally {
    mcpCleanup($items);
    $_SESSION = $before;
    if (is_resource($process)) { proc_terminate($process); proc_close($process); }
    foreach (glob($directory . '/*') as $temporaryFile) unlink($temporaryFile);
    rmdir($directory);
}
