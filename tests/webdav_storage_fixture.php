<?php
declare(strict_types=1);
// Only used by the isolated test on a random loopback port.
if (PHP_SAPI !== 'cli-server' || !getenv('WEBDAV_TEST_SECRET') || !is_dir(getenv('WEBDAV_TEST_DIR') ?: '')) {
    http_response_code(404); exit;
}
if (($_SERVER['PHP_AUTH_USER'] ?? '') !== 'fixture' || !hash_equals(getenv('WEBDAV_TEST_SECRET'), $_SERVER['PHP_AUTH_PW'] ?? '')) {
    header('WWW-Authenticate: Basic realm="WebDAV fixture"');
    http_response_code(401); exit;
}
$path = (string)parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$root = '/dav/Compte%20test/Documents%20partag%C3%A9s';
if ($path !== $root && !str_starts_with($path, $root . '/')) { http_response_code(404); exit; }
$directory = getenv('WEBDAV_TEST_DIR');
$file = $directory . '/' . hash('sha256', $path) . '.bin';
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'MKCOL') { http_response_code(201); exit; }
if ($method === 'PUT') {
    if (is_file($directory . '/fail-put')) { http_response_code(503); exit; }
    file_put_contents($file, file_get_contents('php://input'));
    http_response_code(201); exit;
}
if ($method === 'GET') {
    if (!is_file($file)) { http_response_code(404); exit; }
    header('Content-Type: text/plain'); readfile($file); exit;
}
if ($method === 'DELETE') {
    if (!is_file($file)) { http_response_code(404); exit; }
    unlink($file); http_response_code(204); exit;
}
if ($method === 'PROPFIND') {
    if (!str_ends_with($path, '/')) { header('Location: ' . $path . '/', true, 301); exit; }
    $entry = static function (string $href, bool $folder): string {
        return '<d:response><d:href>' . htmlspecialchars($href, ENT_XML1) . '</d:href><d:propstat><d:prop><d:resourcetype>'
            . ($folder ? '<d:collection/>' : '') . '</d:resourcetype></d:prop><d:status>HTTP/1.1 200 OK</d:status></d:propstat></d:response>';
    };
    header('Content-Type: application/xml; charset=UTF-8'); http_response_code(207);
    echo '<d:multistatus xmlns:d="DAV:">' . $entry($path, true);
    if (($_SERVER['HTTP_DEPTH'] ?? '') === '1') {
        echo $entry($path . 'r%C3%A9sum%C3%A9%20%26%20test.txt', false) . $entry($path . 'Dossier%20%C3%A9t%C3%A9/', true);
        echo $entry($root . '-outside/leak.txt', false);
    }
    echo '</d:multistatus>'; exit;
}
http_response_code(405);
