<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';
$endpoint = omoMcpPublicUrl();
$host = parse_url($endpoint, PHP_URL_HOST);
mcpCheck(in_array($host, ['localtest.me', 'dev.opengov.tools'], true), 'Discovery smoke test is restricted to local Docker and Dev');
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
        'rpc_error' => $json['error']['code'] ?? null], JSON_THROW_ON_ERROR) . "\n";
    unset($curl);
    return ['status' => $status, 'json' => $json];
}

$items = mcpFixtures();
try {
    $request = mcpAuthorizationRequest($items['client']);
    $code = \dbObject\McpOauthGrant::issueCode($items['client'], (int)$items['user']->getId(), (int)$items['org']->getId(), $request);
    $tokens = mcpDeployedRequest('/mcp/token.php', mcpExchangeRequest($items['client'], $code), null, true);
    mcpCheck($tokens['status'] === 200 && isset($tokens['json']['access_token']), 'Deployed OAuth token exchange failed');
    $token = $tokens['json']['access_token'];
    $pathsOk = true;
    foreach (['/mcp', '/mcp/', '/mcp/index.php'] as $path) {
        $ping = mcpDeployedRequest($path, ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'], $token);
        $pathsOk = $pathsOk && $ping['status'] === 200 && isset($ping['json']['result']);
    }
    mcpCheck($pathsOk, 'Deployed authenticated MCP routing failed');
    $initialized = mcpDeployedRequest('/mcp', ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'initialize',
        'params' => ['protocolVersion' => '2025-03-26', 'capabilities' => new stdClass(),
            'clientInfo' => ['name' => 'omo-deployment-check', 'version' => '1']]], $token);
    mcpCheck($initialized['status'] === 200 && isset($initialized['json']['result']['capabilities']['tools']), 'Deployed initialization failed');
    $listed = mcpDeployedRequest('/mcp', ['jsonrpc' => '2.0', 'id' => 3, 'method' => 'tools/list', 'params' => new stdClass()], $token);
    mcpCheck($listed['status'] === 200 && count($listed['json']['result']['tools'] ?? []) === 5, 'Deployed tool discovery failed');
    $info = mcpDeployedRequest('/mcp', ['jsonrpc' => '2.0', 'id' => 4, 'method' => 'tools/call',
        'params' => ['name' => 'omo_connection_info', 'arguments' => new stdClass()]], $token);
    mcpCheck($info['status'] === 200 && ($info['json']['result']['structuredContent']['connected'] ?? false), 'Deployed tool call failed');
    echo "[MCP smoke] OK: OAuth, authenticated routing, initialization, five tools and connection info\n";
} finally {
    mcpCleanup($items);
    echo "[MCP smoke] Temporary fixtures removed\n";
}
