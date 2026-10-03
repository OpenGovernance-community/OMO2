<?php
// Only launched by the CLI file import test, on a random loopback port.
if (PHP_SAPI !== 'cli-server' || !getenv('MCP_TEST_STORAGE_SECRET')
    || !hash_equals(getenv('MCP_TEST_STORAGE_SECRET'), $_SERVER['PHP_AUTH_PW'] ?? '')) {
    http_response_code(404); exit;
}
$directory = getenv('MCP_TEST_STORAGE_DIR');
if (!$directory || !is_dir($directory)) { http_response_code(404); exit; }
$target = $directory . '/' . hash('sha256', $_SERVER['REQUEST_URI']) . '.bin';
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'MKCOL') { http_response_code(201); exit; }
if ($method === 'PUT') {
    if (is_file($directory . '/fail-put')) { http_response_code(503); exit; }
    file_put_contents($target, file_get_contents('php://input'));
    http_response_code(201); exit;
}
if ($method === 'DELETE') {
    if (is_file($target)) unlink($target);
    http_response_code(204); exit;
}
http_response_code(405);
