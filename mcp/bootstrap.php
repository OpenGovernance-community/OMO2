<?php
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/mcp/protocol.php';
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');
try {
    omoMcpPublicUrl();
} catch (RuntimeException $error) {
    omoMcpJson(['error' => 'mcp_not_configured', 'error_description' => $error->getMessage()], 503);
}
// Do not expose stack traces, SQL or token material if storage is not installed yet.
set_exception_handler(static function (Throwable $error): void {
    error_log('OMO MCP failure: ' . get_class($error));
    omoMcpJson(['error' => 'server_error', 'error_description' => 'MCP unavailable. Check migrations and server logs.'], 503);
});
