<?php
// MCP arguments come exclusively from JSON-RPC. Ignore legacy share or meeting query/form context.
$_GET = $_POST = $_REQUEST = [];
require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__) . '/common/mcp/server.php';
omoMcpCheckOrigin();
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
// A browser cookie never authorizes this endpoint. Close the real session before installing bearer identity.
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
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST, OPTIONS');
    omoMcpJson(['error' => 'This stateless MCP endpoint uses POST; no SSE stream is provided.'], 405);
}
$version = $_SERVER['HTTP_MCP_PROTOCOL_VERSION'] ?? null;
if ($version !== null && !in_array($version, OMO_MCP_VERSIONS, true)) omoMcpJson(['error' => 'Unsupported MCP protocol version.'], 400);
if (!str_starts_with(strtolower($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json')) {
    omoMcpJson(omoMcpRpcError(null, -32600, 'Use application/json.'), 415);
}
try { $message = omoMcpInput(); }
catch (JsonException $error) { omoMcpJson(omoMcpRpcError(null, -32700, 'Invalid JSON.'), 400); }
catch (InvalidArgumentException $error) { omoMcpJson(omoMcpRpcError(null, -32600, $error->getMessage()), 400); }
$result = omoMcpDispatch($message, $grant);
if ($result === null) { http_response_code(202); exit; }
omoMcpJson($result);
