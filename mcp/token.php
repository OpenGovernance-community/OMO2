<?php
require_once __DIR__ . '/bootstrap.php';
omoMcpCheckOrigin();
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
omoMcpRequirePost();
omoMcpRateLimit('mcp_token', 120, 900);
$input = omoMcpFormInput();
if (!in_array($input['grant_type'] ?? '', ['authorization_code', 'refresh_token'], true)) {
    omoMcpOauthError('unsupported_grant_type', 'Use authorization_code or refresh_token.');
}
if (($input['resource'] ?? '') !== omoMcpPublicUrl()) omoMcpOauthError('invalid_target', 'Invalid MCP resource.');
$client = \dbObject\McpOauthClient::findByClientId($input['client_id'] ?? '');
if (!$client) omoMcpOauthError('invalid_client', 'Unknown OAuth client.');
$result = \dbObject\McpOauthGrant::exchange($client, $input);
if (!$result) omoMcpOauthError('invalid_grant', 'Invalid, expired or already used grant.');
omoMcpJson($result);
