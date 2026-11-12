<?php
declare(strict_types=1);

// Isolated storage test: no application session, SQL connection or external provider.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';
require_once dirname(__DIR__) . '/class/dbobject/organization.class.php';
require_once dirname(__DIR__) . '/omo/api/documents/params/shared.php';

function webdavCheck(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

class WebdavTestOrganization extends \dbObject\Organization
{
    public array $config = [];
    public function getApplicationParametersByDirectory(string $directory, bool $activeOnly = false): array
    {
        return ['storage' => $this->config];
    }
    public function url(string $path = ''): string
    {
        return $this->buildWebdavDocumentsDavUrl($this->getDocumentStorageConfig(), $path);
    }
    public function entries(string $xml, string $path = ''): ?array
    {
        return $this->parseWebdavDocumentsEntries($xml, $path, $this->getDocumentStorageConfig());
    }
}

class WebdavResponseOrganization extends WebdavTestOrganization
{
    public string $responseBody = '';
    protected function executeWebdavDocumentsRequest(string $method, string $relativePath, array $config, array $options = []): array
    {
        return ['status' => true, 'httpCode' => 207, 'body' => $this->responseBody];
    }
}

\dbObject\DbObject::withSourceUiMessages(function (): void {
    $organization = new WebdavTestOrganization();
    $organization->config = ['type' => 'webdav', 'baseUrl' => 'https://dav.example.com/dav/Compte%20test/',
        'username' => 'fixture', 'appPassword' => ' secret ', 'folder' => 'Documents partagés'];
    $config = $organization->getDocumentStorageConfig();
    webdavCheck($organization->hasDocumentStorage() && !$organization->hasNextcloudDocumentStorage(), 'Generic WebDAV must not fall back to Nextcloud');
    webdavCheck($organization->getDocumentStorageLabel() === 'WebDAV', 'Generic provider label');
    webdavCheck($organization->url('été/rapport & bilan.txt') === 'https://dav.example.com/dav/Compte%20test/Documents%20partag%C3%A9s/%C3%A9t%C3%A9/rapport%20%26%20bilan.txt', 'Preserve exact endpoint and encode appended path segments once');
    webdavCheck($organization->url('../escape') === '' && $organization->url("safe/../../escape") === '', 'Reject traversal before sending a request');
    foreach (['ftp://dav.example.com/root', 'https://user:secret@dav.example.com/root', 'https://dav.example.com/root?token=secret',
        'https://dav.example.com/root#fragment', 'https://dav.example.com/%2e%2e/root', "https://dav.example.com/\r\nInjected", 'https://dav.example.com/%invalid'] as $url) {
        webdavCheck(!omoDocumentsParamsHasDocumentStorageConfig(array_replace($config, ['baseUrl' => $url])), 'Reject invalid endpoint: ' . $url);
    }
    webdavCheck(!omoDocumentsParamsHasDocumentStorageConfig(array_replace($config, ['folder' => '../outside'])), 'Reject root traversal');
    $values = ['document_storage_enabled' => 1, 'storage_type' => 'webdav', 'webdav_base_url' => $config['baseUrl'],
        'webdav_username' => 'fixture', 'webdav_folder' => $config['folder'], 'webdav_app_password' => ''];
    webdavCheck(omoDocumentsParamsBuildDocumentStorageConfigFromValues($values, $config)['appPassword'] === ' secret ', 'Keep saved password unchanged on blank input');
    webdavCheck(omoDocumentsParamsBuildDocumentStorageConfigFromValues($values, array_replace($config, ['type' => 'nextcloud']))['appPassword'] === '', 'Never reuse another provider password');
    webdavCheck(omoDocumentsParamsStorageIdentity($config) !== omoDocumentsParamsStorageIdentity(array_replace($config, ['folder' => 'other'])), 'Root changes retain the existing change confirmation');

    $root = '/dav/Compte%20test/Documents%20partag%C3%A9s';
    $response = static function (string $href, bool $folder, int $status = 200): string {
        return '<d:response><d:href>' . htmlspecialchars($href, ENT_XML1) . '</d:href><d:propstat><d:prop><d:resourcetype>'
            . ($folder ? '<d:collection/>' : '') . '</d:resourcetype><d:getcontenttype>text/plain</d:getcontenttype><d:getcontentlength>12</d:getcontentlength>'
            . '</d:prop><d:status>HTTP/1.1 ' . $status . ' Status</d:status></d:propstat></d:response>';
    };
    $xml = '<d:multistatus xmlns:d="DAV:">' . $response($root . '/', true)
        . $response('https://dav.example.com' . $root . '/r%C3%A9sum%C3%A9%20%26%20test.txt', false)
        . $response('Dossier%20%C3%A9t%C3%A9/', true)
        . $response('/dav/Compte%20test/Documents%20partag%C3%A9s-other/leak.txt', false)
        . $response($root . '/../outside.txt', false)
        . $response('https://other.example.com' . $root . '/leak.txt', false)
        . $response($root . '/missing.txt', false, 404) . '</d:multistatus>';
    $entries = $organization->entries($xml);
    webdavCheck(count($entries) === 3 && $entries[0]['path'] === '' && $entries[1]['path'] === 'résumé & test.txt'
        && $entries[2]['path'] === 'Dossier été' && $entries[2]['isFolder'], 'Parse absolute and relative hrefs, exclude failed and out-of-root entries');
    webdavCheck($organization->entries('<html>Login page</html>') === null, 'Do not accept login HTML as WebDAV metadata');
    $responseOrganization = new WebdavResponseOrganization();
    $responseOrganization->config = $organization->config;
    $responseOrganization->responseBody = $xml;
    webdavCheck($responseOrganization->testDocumentStorageConnection($config)['status'], 'Connection test verifies the configured collection');
    $listing = $responseOrganization->listDocumentStorageDirectory('');
    webdavCheck($listing['status'] && count($listing['entries']) === 2 && $listing['entries'][0]['isFolder']
        && $listing['entries'][1]['path'] === 'résumé & test.txt', 'Directory adapter returns sorted direct children relative to the configured root');
    webdavCheck($responseOrganization->getDocumentStorageDirectoryInfo('')['isFolder'], 'Resolve configured root metadata');
    $responseOrganization->responseBody = '<html>Login page</html>';
    webdavCheck(!$responseOrganization->testDocumentStorageConnection($config)['status'], 'Connection test rejects successful HTTP login pages');
    $organization->config = ['type' => 'kdrive', 'driveId' => '123456', 'username' => 'fixture', 'appPassword' => 'secret', 'folder' => 'Documents'];
    webdavCheck($organization->isKdriveDocumentStorage() && $organization->url('file.txt') === 'https://123456.connect.kdrive.infomaniak.com/Documents/file.txt', 'Preserve kDrive URL routing');
    $organization->config = ['type' => 'nextcloud', 'baseUrl' => 'https://cloud.example.com', 'username' => 'fixture', 'appPassword' => 'secret', 'folder' => 'Documents'];
    webdavCheck(!$organization->isWebdavDocumentStorage() && $organization->hasNextcloudDocumentStorage(), 'Preserve Nextcloud routing');

    if (!function_exists('curl_init')) {
        echo "webdav_storage_test: OK (configuration, paths, XML, provider routing; HTTP requires cURL)\n";
        return;
    }
    $directory = sys_get_temp_dir() . '/omo-webdav-test-' . bin2hex(random_bytes(8));
    mkdir($directory, 0700);
    $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
    webdavCheck(is_resource($socket), 'Reserve local fixture port');
    $address = stream_socket_get_name($socket, false);
    fclose($socket);
    $secret = bin2hex(random_bytes(16));
    $process = null;
    try {
        $null = PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';
        $process = proc_open([PHP_BINARY, '-S', $address, __DIR__ . '/webdav_storage_fixture.php'],
            [0 => ['file', $null, 'r'], 1 => ['file', $null, 'w'], 2 => ['file', $directory . '/server.log', 'w']], $pipes, null,
            array_merge(getenv(), ['WEBDAV_TEST_SECRET' => $secret, 'WEBDAV_TEST_DIR' => $directory]));
        webdavCheck(is_resource($process), 'Start local WebDAV fixture');
        $ready = false;
        for ($attempt = 0; $attempt < 50; $attempt++) {
            $probe = @stream_socket_client('tcp://' . $address, $errno, $error, 0.1);
            if ($probe) { fclose($probe); $ready = true; break; }
            usleep(100000);
        }
        webdavCheck($ready, 'WebDAV fixture is ready');
        $organization->config = ['type' => 'webdav', 'baseUrl' => 'http://' . $address . '/dav/Compte%20test',
            'username' => 'fixture', 'appPassword' => $secret, 'folder' => 'Documents partagés'];
        webdavCheck($organization->testDocumentStorageConnection($organization->config)['status'], 'Test configured collection');
        $badConfig = array_replace($organization->config, ['appPassword' => 'wrong']);
        webdavCheck(!$organization->testDocumentStorageConnection($badConfig)['status'], 'Report rejected credentials');
        file_put_contents($directory . '/upload.txt', 'first content');
        $uploaded = $organization->uploadDocumentFileToStorage(42, ['tmp_name' => $directory . '/upload.txt', 'error' => UPLOAD_ERR_OK,
            'name' => 'été & bilan.txt', 'type' => 'text/plain', 'size' => 13]);
        webdavCheck($uploaded['status'], 'Upload with automatic intermediate collections');
        $path = $uploaded['relativePath'];
        webdavCheck($path === 'omo-documents/42/été-bilan.txt', 'Preserve existing document path layout');
        webdavCheck($organization->downloadDocumentFileFromStorage($path)['body'] === 'first content', 'Download exact bytes');
        webdavCheck($organization->updateDocumentFileContentsOnStorage($path, 'updated content', 'text/plain')['status'], 'Update through shared Collabora storage API');
        webdavCheck($organization->downloadDocumentFileFromStorage($path)['body'] === 'updated content', 'Read updated bytes');
        $listing = $organization->listDocumentStorageDirectory('');
        webdavCheck($listing['status'] && count($listing['entries']) === 2 && $listing['entries'][0]['isFolder']
            && $listing['entries'][1]['path'] === 'résumé & test.txt', 'Browse collection with encoded endpoint and root');
        webdavCheck($organization->getDocumentStorageDirectoryInfo('Dossier été')['isFolder'], 'Resolve remote folder without Nextcloud file IDs');
        webdavCheck($organization->deleteDocumentFileFromStorage($path)['status'] && !$organization->downloadDocumentFileFromStorage($path)['status'], 'Delete uploaded file');
        webdavCheck($organization->deleteDocumentFileFromStorage($path)['status'], 'Deleting missing files remains idempotent');
        file_put_contents($directory . '/fail-put', '1');
        webdavCheck(!$organization->updateDocumentFileContentsOnStorage('test.txt', 'fail')['status'], 'Surface HTTP failure');
        $other = new WebdavTestOrganization();
        webdavCheck($other->deleteDocumentFileFromStorage('missing.txt', $organization->config)['status'], 'Cleanup can explicitly use the previous storage configuration');
    } finally {
        if (is_resource($process)) { proc_terminate($process); proc_close($process); }
        foreach (glob($directory . '/*') as $file) unlink($file);
        rmdir($directory);
    }
    echo "webdav_storage_test: OK (configuration, paths, XML, provider routing, local HTTP CRUD, browse, authentication, failures)\n";
});
