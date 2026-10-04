<?php
// MCP arguments come exclusively from JSON-RPC. Ignore legacy share or meeting query/form context.
$_GET = $_POST = $_REQUEST = [];
require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__) . '/common/mcp/server.php';
omoMcpCheckOrigin();
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
$grant = omoApiAuthenticate();
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
