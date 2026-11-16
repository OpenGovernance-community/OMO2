<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';
$endpoint = omoMcpPublicUrl();
$host = parse_url($endpoint, PHP_URL_HOST);
mcpCheck(in_array($host, ['localtest.me', 'dev.opengov.tools', 'beta.opengov.tools'], true), 'Discovery smoke test is restricted to local Docker, Dev and Beta');
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();

function mcpDeployedRequest(string $path, array $body, ?string $token = null, bool $form = false): array
{
    global $host;
    $headers = ['Accept: application/json, text/event-stream',
        'Content-Type: ' . ($form ? 'application/x-www-form-urlencoded' : 'application/json')];
    if ($token !== null) $headers[] = 'Authorization: Bearer ' . $token;
    $curl = curl_init(omoMcpIssuer() . $path);
    curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => $headers, CURLOPT_POSTFIELDS => $form ? http_build_query($body) : json_encode($body, JSON_THROW_ON_ERROR)]);
    if ($host === 'localtest.me') {
        curl_setopt_array($curl, [CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_RESOLVE => ['localtest.me:443:127.0.0.1']]);
    }
    $raw = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $json = is_string($raw) ? json_decode($raw, true) : null;
    // Never log tokens, callback codes, bodies or organization/user data.
    echo '[MCP smoke] ' . json_encode(['path' => $path, 'method' => $body['method'] ?? 'token',
        'status' => $status, 'json' => is_array($json), 'curl_error' => curl_errno($curl),
        'oauth_error' => is_string($json['error'] ?? null) ? $json['error'] : null,
        'rpc_error' => $json['error']['code'] ?? null], JSON_THROW_ON_ERROR) . "\n";
    unset($curl);
    return ['status' => $status, 'json' => $json];
}

$items = mcpFixtures();
try {
    mcpEnableDocumentCreation($items);
    $registration = mcpDeployedRequest('/mcp/register.php', ['client_name' => 'MCP discovery fixture',
        'redirect_uris' => ['https://client.example.invalid/callback'], 'token_endpoint_auth_method' => 'none']);
    mcpCheck($registration['status'] === 201 && ($registration['json']['scope'] ?? '') === implode(' ', omoMcpSupportedScopes()), 'Fresh client registration includes all available action scopes');
    $client = \dbObject\McpOauthClient::findByClientId($registration['json']['client_id']);
    mcpCheck($client !== null, 'Fresh OAuth client persisted');
    $items['registered_client'] = $client;
    $request = mcpAuthorizationRequest($client);
    unset($request['scope']);
    $alternateResource = str_ends_with($endpoint, '/') ? substr($endpoint, 0, -1) : $endpoint . '/';
    $request = omoMcpValidateAuthorization(array_replace($request, ['resource' => $alternateResource,
        'response_type' => 'code', 'code_challenge_method' => 'S256', 'transport' => 'mcp']), $client);
    mcpCheck($request['resource'] === $endpoint, 'OAuth consent normalizes the alternate endpoint spelling');
    $code = \dbObject\McpOauthGrant::issueCode($client, (int)$items['user']->getId(), (int)$items['org']->getId(), $request);
    $exchange = array_replace(mcpExchangeRequest($client, $code), ['resource' => $alternateResource]);
    $foreignResource = mcpDeployedRequest('/mcp/token.php', array_replace($exchange, ['resource' => 'https://evil.invalid/mcp/']), null, true);
    mcpCheck($foreignResource['status'] === 400 && ($foreignResource['json']['error'] ?? '') === 'invalid_target', 'OAuth token exchange rejects foreign resources');
    $tokens = mcpDeployedRequest('/mcp/token.php', $exchange, null, true);
    mcpCheck($tokens['status'] === 200 && isset($tokens['json']['access_token']), 'Deployed OAuth token exchange failed');
    mcpCheck($tokens['json']['scope'] === implode(' ', omoMcpSupportedScopes()), 'Default writing consent survives the token exchange');
    $tokens = mcpDeployedRequest('/mcp/token.php', ['grant_type' => 'refresh_token',
        'client_id' => $client->get('client_id'), 'refresh_token' => $tokens['json']['refresh_token'],
        'resource' => $alternateResource], null, true);
    mcpCheck($tokens['status'] === 200 && isset($tokens['json']['access_token']), 'OAuth refresh accepts the alternate endpoint spelling');
    $missingResourceCode = \dbObject\McpOauthGrant::issueCode($client, (int)$items['user']->getId(), (int)$items['org']->getId(), $request);
    $withoutResource = mcpExchangeRequest($client, $missingResourceCode);
    unset($withoutResource['resource']);
    $wrongPkce = mcpDeployedRequest('/mcp/token.php', array_replace($withoutResource, ['code_verifier' => str_repeat('b', 64)]), null, true);
    mcpCheck($wrongPkce['status'] === 400 && ($wrongPkce['json']['error'] ?? '') === 'invalid_grant', 'Omitting resource still requires the correct PKCE verifier');
    $emptyResource = mcpDeployedRequest('/mcp/token.php', array_replace($withoutResource, ['resource' => '']), null, true);
    mcpCheck($emptyResource['status'] === 400 && ($emptyResource['json']['error'] ?? '') === 'invalid_target', 'An explicit empty resource is rejected');
    $tokens = mcpDeployedRequest('/mcp/token.php', $withoutResource, null, true);
    mcpCheck($tokens['status'] === 200 && isset($tokens['json']['access_token']), 'OAuth code exchange can use the single default resource');
    $tokens = mcpDeployedRequest('/mcp/token.php', ['grant_type' => 'refresh_token',
        'client_id' => $client->get('client_id'), 'refresh_token' => $tokens['json']['refresh_token']], null, true);
    mcpCheck($tokens['status'] === 200 && isset($tokens['json']['access_token']), 'OAuth refresh can use the single default resource');
    $token = $tokens['json']['access_token'];
    foreach (['/mcp', '/mcp/'] as $path) {
        $unauthorized = mcpDeployedRequest($path, ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping']);
        mcpCheck($unauthorized['status'] === 401, 'Both MCP URLs require bearer authentication without redirect');
        $ping = mcpDeployedRequest($path, ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'], $token);
        mcpCheck($ping['status'] === 200 && isset($ping['json']['result']), 'Both MCP URLs accept authenticated POST without redirect');
        $initialized = mcpDeployedRequest($path, ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'initialize',
            'params' => ['protocolVersion' => '2025-03-26', 'capabilities' => new stdClass(),
                'clientInfo' => ['name' => 'omo-deployment-check', 'version' => '1']]], $token);
        mcpCheck($initialized['status'] === 200 && isset($initialized['json']['result']['capabilities']['tools']), 'Both MCP URLs initialize without redirect');
        $listed = mcpDeployedRequest($path, ['jsonrpc' => '2.0', 'id' => 3, 'method' => 'tools/list', 'params' => new stdClass()], $token);
        $expectedTools = array_column(omoMcpTools(), 'name'); $discoveredTools = array_column($listed['json']['result']['tools'] ?? [], 'name');
        sort($expectedTools); sort($discoveredTools);
        mcpCheck($listed['status'] === 200 && $discoveredTools === $expectedTools, 'Deployed tool discovery must match the current operation registry');
    }
    $path = parse_url($endpoint, PHP_URL_PATH);
    $info = mcpDeployedRequest($path, ['jsonrpc' => '2.0', 'id' => 4, 'method' => 'tools/call',
        'params' => ['name' => 'omo_connection_info', 'arguments' => new stdClass()]], $token);
    mcpCheck($info['status'] === 200 && ($info['json']['result']['structuredContent']['connected'] ?? false), 'Deployed tool call failed');
    foreach (['omo_catalog' => new stdClass(), 'omo_list_records' => (object)['module' => 'structure'],
        'omo_list_assignments' => new stdClass(), 'omo_list_document_spaces' => (object)['kind' => 'holons']] as $name => $arguments) {
        $reply = mcpDeployedRequest($path, ['jsonrpc' => '2.0', 'id' => 5, 'method' => 'tools/call',
            'params' => ['name' => $name, 'arguments' => $arguments]], $token);
        mcpCheck($reply['status'] === 200 && ($reply['json']['result']['isError'] ?? true) === false, 'Deployed browsing tool failed');
    }
    foreach (['text' => 'Temporary deployment test only.',
        'html' => '<p><strong>Temporary deployment test only.</strong></p>',
        'markdown' => '**Temporary deployment test only.**'] as $format => $content) {
        $arguments = (object)['title' => 'MCP deployment fixture ' . $format, 'request_key' => 'deployment-document-' . $format,
            'holon_id' => (int)$items['role']->getId(), 'content_format' => $format, 'content' => $content];
        $created = mcpDeployedRequest($path, ['jsonrpc' => '2.0', 'id' => 6, 'method' => 'tools/call',
            'params' => ['name' => 'omo_create_document', 'arguments' => $arguments]], $token);
        $data = $created['json']['result']['structuredContent'] ?? [];
        mcpCheck($created['status'] === 200 && ($created['json']['result']['isError'] ?? true) === false
            && ($data['created'] ?? false), 'Deployed ' . $format . ' Memo creation failed');
        $items['created_document_' . $format] = new \dbObject\Document();
        $document = $items['created_document_' . $format];
        mcpCheck($document->load((int)($data['record']['record_id'] ?? 0), true)
            && $document->getDocumentType() === \dbObject\Document::TYPE_HTML, 'Deployed Memo was not persisted');
        $expectedContent = $format === 'text' ? 'Temporary deployment test only.' : '<strong>Temporary deployment test only.</strong>';
        mcpCheck(str_contains((string)$document->get('content'), $expectedContent), 'Deployed Memo lost content or formatting');
        $read = mcpDeployedRequest($path, ['jsonrpc' => '2.0', 'id' => 7, 'method' => 'tools/call',
            'params' => ['name' => 'omo_read_record', 'arguments' => (object)['module' => 'documents',
                'record_id' => (int)$document->getId(), 'context_holon_id' => $data['record']['context_holon_id']]]], $token);
        mcpCheck($read['status'] === 200 && ($read['json']['result']['isError'] ?? true) === false
            && str_contains($read['json']['result']['structuredContent']['text'] ?? '', 'Temporary deployment test only.'),
            'Deployed Memo cannot be read in a new HTTP request');
        $retry = mcpDeployedRequest($path, ['jsonrpc' => '2.0', 'id' => 8, 'method' => 'tools/call',
            'params' => ['name' => 'omo_create_document', 'arguments' => $arguments]], $token);
        mcpCheck($retry['status'] === 200 && ($retry['json']['result']['structuredContent']['replayed'] ?? false)
            && ($retry['json']['result']['structuredContent']['record']['record_id'] ?? 0) === (int)$document->getId(),
            'Deployed Memo retry must return the saved document');
    }
    echo "[MCP smoke] OK: OAuth creation consent, authenticated routing, current tool registry and persisted text/HTML/Markdown Memos\n";
} finally {
    mcpCleanup($items);
    echo "[MCP smoke] Temporary fixtures removed\n";
}
