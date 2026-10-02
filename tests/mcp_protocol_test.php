<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/common/environment_subdomains.php';
require_once dirname(__DIR__) . '/common/mcp/server.php';
function mcpProtocolCheck(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
commonLoadRuntimeEnvIfAvailable();
$_ENV['MCP_PUBLIC_URL'] = 'https://mcp.example.invalid/mcp';
$_ENV['MCP_ALLOW_LOCAL_HTTP'] = '0';
mcpProtocolCheck(omoMcpPublicUrl() === 'https://mcp.example.invalid/mcp', 'Canonical resource URL');
mcpProtocolCheck(omoMcpAuthorizationMetadata()['issuer'] === 'https://mcp.example.invalid', 'OAuth issuer');
$_ENV['MCP_PUBLIC_URL'] = 'https://mcp.example.invalid/mcp/';
mcpProtocolCheck(omoMcpPublicUrl() === 'https://mcp.example.invalid/mcp/', 'Canonical trailing slash is preserved');
mcpProtocolCheck(omoMcpIssuer() === 'https://mcp.example.invalid', 'Issuer excludes endpoint and trailing slash');
mcpProtocolCheck(omoMcpResourceMetadata()['resource'] === 'https://mcp.example.invalid/mcp/', 'Resource matches exact MCP URL');
mcpProtocolCheck(omoMcpResourceMetadataUrl() === 'https://mcp.example.invalid/.well-known/oauth-protected-resource/mcp/', 'Discovery preserves the canonical resource path');
$_ENV['MCP_PUBLIC_URL'] = 'https://mcp.example.invalid/mcp//';
$rejected = false;
try { omoMcpPublicUrl(); } catch (RuntimeException $error) { $rejected = true; }
mcpProtocolCheck($rejected, 'Ambiguous endpoint rejected');
$_ENV['MCP_PUBLIC_URL'] = 'https://mcp.example.invalid/mcp';
foreach (['https://evil.invalid/#fragment', 'https://user:pass@evil.invalid/cb', 'javascript:alert(1)',
    'http://evil.invalid/cb', "https://evil.invalid/\r\nLocation:x", 'http://127.0.0.1.evil.invalid/cb'] as $uri) {
    mcpProtocolCheck(!omoMcpValidRedirect($uri, true), 'Unsafe callback accepted');
}
mcpProtocolCheck(omoMcpValidRedirect('https://chatgpt.com/connector_platform_oauth_redirect'), 'HTTPS callback');
mcpProtocolCheck(!omoMcpValidRedirect('http://localhost:6274/oauth/callback'), 'HTTP callback disabled');
mcpProtocolCheck(omoMcpValidRedirect('http://localhost:6274/oauth/callback', true), 'Explicit local callback');
$request = ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize',
    'params' => (object)['protocolVersion' => 'future-version', 'capabilities' => new stdClass(), 'clientInfo' => (object)['name' => 'test', 'version' => '1']]];
$reply = omoMcpDispatch($request, []);
mcpProtocolCheck($reply['result']['protocolVersion'] === OMO_MCP_VERSIONS[0], 'Version negotiation');
mcpProtocolCheck(omoMcpDispatch(['jsonrpc' => '2.0', 'method' => 'notifications/initialized'], []) === null, 'Notification has no response');
mcpProtocolCheck(omoMcpDispatch(['jsonrpc' => '2.0', 'id' => 2, 'method' => 'delete_everything'], [])['error']['code'] === -32601, 'Unknown method');
mcpProtocolCheck(count(omoMcpTools()) === 8, 'Eight read-only tools');
foreach ([['omo_list_records', (object)[]],
    ['omo_list_records', (object)['module' => 'projects', 'date_from' => '2026-02-30']],
    ['omo_list_records', (object)['module' => 'projects', 'date_from' => '2026-10-02', 'date_to' => '2026-10-01']],
    ['omo_list_records', (object)['module' => 'projects', 'user_relation' => 'responsible']],
    ['omo_list_records', (object)['module' => 'projects', 'status' => 'x OR 1=1']],
    ['omo_list_records', (object)['module' => 'team', 'user_id' => '1']],
    ['omo_list_assignments', (object)['organization_id' => 1]]] as [$tool, $arguments]) {
    $rejected = false;
    try { omoMcpToolArguments($tool, $arguments); } catch (InvalidArgumentException $error) { $rejected = true; }
    mcpProtocolCheck($rejected, 'Invalid browse arguments accepted');
}
mcpProtocolCheck(omoMcpToolArguments('omo_list_records', (object)['module' => 'projects', 'parent_id' => 0])['parent_id'] === 0, 'Root parent filter');
foreach ([['omo_search', (object)['query' => ' ']], ['omo_search', (object)['query' => 'test', 'modules' => ['unknown']]],
    ['omo_search', (object)['query' => 'test', 'modules' => ['pv', 'pv']]],
    ['omo_search', (object)['query' => 'test', 'offset' => 651]],
    ['omo_read_record', (object)['module' => 'documents', 'record_id' => 1, 'mission_id' => 1]],
    ['omo_read_record', (object)['module' => '../../config', 'record_id' => 1]]] as [$tool, $args]) {
    $rejected = false;
    try { omoMcpToolArguments($tool, $args); } catch (InvalidArgumentException $error) { $rejected = true; }
    mcpProtocolCheck($rejected, 'Invalid read/search arguments accepted');
}
mcpProtocolCheck(omoMcpToolArguments('omo_search', (object)['query' => ' budget '])['query'] === 'budget', 'Search trims keywords');
foreach ([(object)['limit' => 51], (object)['organization_id' => 1], (object)['limit' => '20'], (object)['after_id' => -1], []] as $args) {
    $rejected = false;
    try { omoMcpToolArguments('omo_list_structure', $args); } catch (InvalidArgumentException $error) { $rejected = true; }
    mcpProtocolCheck($rejected, 'Invalid tool argument accepted');
}
$rejected = false;
try { omoMcpToolArguments('omo_get_holon', new stdClass()); } catch (InvalidArgumentException $error) { $rejected = true; }
mcpProtocolCheck($rejected, 'Missing ID rejected');
$_ENV['MCP_PUBLIC_URL'] = 'https://user:secret@mcp.example.invalid/mcp';
$rejected = false;
try { omoMcpPublicUrl(); } catch (RuntimeException $error) { $rejected = true; }
mcpProtocolCheck($rejected, 'Credentials forbidden in resource URL');
echo "mcp_protocol_test: OK\n";
