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
    mcpEnableDocumentCreation($items);
    $request = mcpAuthorizationRequest($items['client']);
    $request['scope'] = OMO_MCP_SCOPE . ' ' . OMO_MCP_CREATE_SCOPE;
    $code = \dbObject\McpOauthGrant::issueCode($items['client'], (int)$items['user']->getId(), (int)$items['org']->getId(), $request);
    $tokens = mcpDeployedRequest('/mcp/token.php', mcpExchangeRequest($items['client'], $code), null, true);
    mcpCheck($tokens['status'] === 200 && isset($tokens['json']['access_token']), 'Deployed OAuth token exchange failed');
    $token = $tokens['json']['access_token'];
    $path = parse_url($endpoint, PHP_URL_PATH);
    $ping = mcpDeployedRequest($path, ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'], $token);
    mcpCheck($ping['status'] === 200 && isset($ping['json']['result']), 'Canonical MCP URL must accept authenticated POST without redirect');
    $initialized = mcpDeployedRequest($path, ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'initialize',
        'params' => ['protocolVersion' => '2025-03-26', 'capabilities' => new stdClass(),
            'clientInfo' => ['name' => 'omo-deployment-check', 'version' => '1']]], $token);
    mcpCheck($initialized['status'] === 200 && isset($initialized['json']['result']['capabilities']['tools']), 'Deployed initialization failed');
    $listed = mcpDeployedRequest($path, ['jsonrpc' => '2.0', 'id' => 3, 'method' => 'tools/list', 'params' => new stdClass()], $token);
    $expectedTools = array_column(omoMcpTools(), 'name'); $discoveredTools = array_column($listed['json']['result']['tools'] ?? [], 'name');
    sort($expectedTools); sort($discoveredTools);
    mcpCheck($listed['status'] === 200 && $discoveredTools === $expectedTools, 'Deployed tool discovery must match the current operation registry');
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
