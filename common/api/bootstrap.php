<?php
require_once dirname(__DIR__, 2) . '/shared_functions.php';
require_once dirname(__DIR__) . '/mcp/protocol.php';
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');
try {
    omoMcpPublicUrl();
} catch (RuntimeException $error) {
    omoMcpJson(['error' => 'mcp_not_configured', 'error_description' => $error->getMessage()], 503);
}
// Never expose stack traces, SQL, payloads or tokens through either transport.
set_exception_handler(static function (Throwable $error): void {
    error_log('OMO API failure: ' . get_class($error));
    omoMcpJson(['error' => 'server_error', 'error_description' => 'Integration unavailable. Check dependencies, migrations and server logs.'], 503);
});

/** MCP and REST are two transports of the same OAuth resource and organization grant. */
function omoApiAuthenticate(): array
{
    // A browser cookie or a share link never authorizes the API.
    if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
    $_SESSION = [];
    $authorization = (string)($_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? ''));
    $grant = preg_match('/^Bearer ([A-Za-z0-9_-]{43})$/iD', $authorization, $match)
        ? \dbObject\McpOauthGrant::authenticate($match[1], omoMcpPublicUrl()) : null;
    if (!$grant) {
        header('WWW-Authenticate: ' . omoMcpChallenge($authorization === '' ? '' : 'invalid_token'));
        omoMcpJson(['error' => 'unauthorized', 'error_description' => 'Connect your OMO account using OAuth.'], 401);
    }
    $_SESSION = ['currentUser' => (int)$grant['IDuser'], 'currentOrganization' => (int)$grant['IDorganization']];
    return $grant;
}
