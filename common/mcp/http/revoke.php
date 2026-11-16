<?php
require_once __DIR__ . '/bootstrap.php';
omoMcpCheckOrigin();
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
omoMcpRequirePost();
omoMcpRateLimit('mcp_revoke', 120, 900);
$input = omoMcpFormInput();
$client = \dbObject\McpOauthClient::findByClientId($input['client_id'] ?? '');
if (!$client) omoMcpOauthError('invalid_client', 'Unknown OAuth client.');
\dbObject\McpOauthGrant::revokeToken($input['token'] ?? '', (int)$client->getId());
omoMcpJson([]);
