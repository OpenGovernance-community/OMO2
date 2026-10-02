<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';
use dbObject\McpOauthClient;

$endpoint = omoMcpPublicUrl();
$host = parse_url($endpoint, PHP_URL_HOST);
mcpCheck(in_array($host, ['localhost', '127.0.0.1', 'localtest.me'], true) || str_ends_with($host, '.localtest.me'),
    'HTTP integration test is restricted to local development hosts');
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
$jar = tempnam(sys_get_temp_dir(), 'omo-mcp-test-');
$items = mcpFixtures();
$registered = null;
function mcpHttp(string $path, ?array $body = null, bool $json = false, ?string $token = null, array $headers = []): array
{
    global $host, $jar;
    $curl = curl_init(omoMcpIssuer() . $path);
    $responseHeaders = [];
    $headers[] = 'Accept: application/json, text/event-stream';
    if ($body !== null) $headers[] = 'Content-Type: ' . ($json ? 'application/json' : 'application/x-www-form-urlencoded');
    if ($token !== null) $headers[] = 'Authorization: Bearer ' . $token;
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => 0, // Only the checked local Docker certificate.
        CURLOPT_RESOLVE => [$host . ':443:127.0.0.1', $host . ':80:127.0.0.1'],
        CURLOPT_COOKIEFILE => $jar, CURLOPT_COOKIEJAR => $jar, CURLOPT_HTTPHEADER => $headers,
        CURLOPT_HEADERFUNCTION => static function ($handle, string $line) use (&$responseHeaders): int {
            $pair = explode(':', trim($line), 2);
            if (count($pair) === 2) $responseHeaders[strtolower($pair[0])] = trim($pair[1]);
            return strlen($line);
        }]);
    if ($body !== null) {
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json ? json_encode($body, JSON_THROW_ON_ERROR) : http_build_query($body));
    }
    $response = curl_exec($curl);
    if ($response === false) throw new RuntimeException('Local HTTP request failed: ' . curl_error($curl));
    $result = ['status' => curl_getinfo($curl, CURLINFO_RESPONSE_CODE), 'headers' => $responseHeaders, 'body' => $response];
    unset($curl);
    return $result;
}
function mcpHttpJson(array $response): array
{
    try { return json_decode($response['body'], true, 32, JSON_THROW_ON_ERROR); }
    catch (JsonException $error) { throw new RuntimeException('Non-JSON response, HTTP ' . $response['status']); }
}
function mcpHttpRpc(string $method, array $params, ?string $token): array
{
    return mcpHttp(parse_url(omoMcpPublicUrl(), PHP_URL_PATH), ['jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => (object)$params], true, $token);
}
try {
    $metadata = mcpHttp(parse_url(omoMcpResourceMetadataUrl(), PHP_URL_PATH));
    mcpCheck($metadata['status'] === 200 && mcpHttpJson($metadata)['resource'] === $endpoint, 'Resource discovery route');
    $authMetadata = mcpHttp('/.well-known/oauth-authorization-server');
    mcpCheck(mcpHttpJson($authMetadata)['code_challenge_methods_supported'] === ['S256'], 'PKCE discovery');
    $unauth = mcpHttpRpc('tools/list', [], null);
    mcpCheck($unauth['status'] === 401 && str_contains($unauth['headers']['www-authenticate'], 'resource_metadata='), '401 challenge');
    $origin = mcpHttp('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'], true, null, ['Origin: https://evil.invalid']);
    mcpCheck($origin['status'] === 403, 'Untrusted Origin rejected');
    $registration = mcpHttp('/mcp/register.php', ['client_name' => 'HTTP MCP test',
        'redirect_uris' => ['https://client.example.invalid/callback'], 'token_endpoint_auth_method' => 'none'], true);
    mcpCheck($registration['status'] === 201, 'Dynamic registration');
    $registered = McpOauthClient::findByClientId(mcpHttpJson($registration)['client_id']);
    mcpCheck($registered !== null, 'Registered client persisted');
    $request = mcpAuthorizationRequest($registered) + ['response_type' => 'code', 'code_challenge_method' => 'S256'];
    $start = mcpHttp('/mcp/authorize.php?' . http_build_query($request));
    mcpCheck($start['status'] === 303, 'Authorization request stored in browser session');
    $consentPath = $start['headers']['location'];
    $loginPage = mcpHttp($consentPath);
    mcpCheck($loginPage['status'] === 200 && str_contains($loginPage['body'], '/common/login_password.php'), 'Existing OMO login displayed');
    $login = mcpHttp('/common/login_password.php', ['email' => $items['user']->get('email'),
        'password' => $items['password'], 'return_to' => $consentPath], false, null, ['X-Requested-With: XMLHttpRequest']);
    mcpCheck(mcpHttpJson($login)['status'] === 'ok', 'Existing password login succeeds');
    $consent = mcpHttp($consentPath);
    mcpCheck($consent['status'] === 200 && str_contains($consent['body'], 'HTTP MCP test')
        && str_contains($consent['body'], 'name="organization_id"'), 'Organization consent rendered');
    mcpCheck(str_contains($consent['body'], '/common/assets/components.css'), 'Shared consent styles loaded');
    mcpCheck(($consent['headers']['content-security-policy'] ?? '') ===
        "frame-ancestors 'none'; form-action 'self' https://client.example.invalid; base-uri 'none'",
        'Consent CSP permits only this registered callback origin in addition to self');
    preg_match('/name="csrf" value="([a-f0-9]+)"/', $consent['body'], $match);
    mcpCheck(isset($match[1]), 'Consent CSRF token');
    $wrongCsrf = mcpHttp($consentPath, ['csrf' => 'wrong', 'decision' => 'allow', 'organization_id' => $items['org']->getId()]);
    mcpCheck($wrongCsrf['status'] === 403, 'CSRF rejected');
    $foreign = mcpHttp($consentPath, ['csrf' => $match[1], 'decision' => 'allow', 'organization_id' => $items['other_org']->getId()]);
    mcpCheck($foreign['status'] === 403, 'Foreign organization consent rejected');
    $authorized = mcpHttp($consentPath, ['csrf' => $match[1], 'decision' => 'allow', 'organization_id' => $items['org']->getId()]);
    mcpCheck($authorized['status'] === 303, 'Consent redirects to registered callback');
    parse_str(parse_url($authorized['headers']['location'], PHP_URL_QUERY), $callback);
    mcpCheck($callback['state'] === 'test-state' && $callback['iss'] === omoMcpIssuer(), 'OAuth state and issuer returned');
    $tokens = mcpHttp('/mcp/token.php', mcpExchangeRequest($registered, $callback['code']));
    mcpCheck($tokens['status'] === 200, 'OAuth HTTP token exchange');
    $tokens = mcpHttpJson($tokens);
    $cookieOnly = mcpHttpRpc('tools/list', [], null);
    mcpCheck($cookieOnly['status'] === 401, 'Logged-in browser cookie cannot authorize MCP');
    $initialized = mcpHttpRpc('initialize', ['protocolVersion' => '2025-11-25', 'capabilities' => new stdClass(),
        'clientInfo' => (object)['name' => 'test', 'version' => '1']], $tokens['access_token']);
    mcpCheck(mcpHttpJson($initialized)['result']['serverInfo']['name'] === 'omo', 'MCP initialization');
    $tools = mcpHttpRpc('tools/list', [], $tokens['access_token']);
    mcpCheck(count(mcpHttpJson($tools)['result']['tools']) === 5, 'Authenticated tool discovery');
    foreach (['omo_connection_info' => new stdClass(), 'omo_list_structure' => (object)['limit' => 1],
        'omo_get_holon' => (object)['holon_id' => (int)$items['role']->getId()],
        'omo_search' => (object)['query' => 'MCP', 'modules' => ['structure']],
        'omo_read_record' => (object)['module' => 'structure', 'record_id' => (int)$items['role']->getId()]] as $tool => $arguments) {
        $reply = mcpHttpRpc('tools/call', ['name' => $tool, 'arguments' => $arguments], $tokens['access_token']);
        mcpCheck(!mcpHttpJson($reply)['result']['isError'], 'Tool succeeds: ' . $tool);
    }
    $forbidden = mcpHttpRpc('tools/call', ['name' => 'omo_get_holon', 'arguments' => (object)['holon_id' => (int)$items['other_root']->getId()]], $tokens['access_token']);
    mcpCheck(mcpHttpJson($forbidden)['result']['isError'], 'Foreign holon rejected over HTTP');
    $items['app']->set('active', 0);
    $items['app']->save();
    $disabledModule = mcpHttpRpc('tools/call', ['name' => 'omo_list_structure', 'arguments' => new stdClass()], $tokens['access_token']);
    mcpCheck(mcpHttpJson($disabledModule)['result']['isError'], 'Disabled structure module respected');
    $items['app']->set('active', 1);
    $items['app']->save();
    $items['user']->set('active', 0);
    $items['user']->save();
    mcpCheck(mcpHttpRpc('tools/list', [], $tokens['access_token'])['status'] === 401, 'Disabled account refused');
    $items['user']->set('active', 1);
    $items['user']->save();
    $notice = mcpHttp(parse_url($endpoint, PHP_URL_PATH), ['jsonrpc' => '2.0', 'method' => 'notifications/initialized'], true, $tokens['access_token']);
    mcpCheck($notice['status'] === 202 && $notice['body'] === '', 'MCP notification response');
    $connections = mcpHttp('/mcp/connections.php');
    mcpCheck(($connections['headers']['content-security-policy'] ?? '') ===
        "frame-ancestors 'none'; form-action 'self'; base-uri 'none'", 'Connection management CSP remains same-origin');
    preg_match('/name="csrf" value="([a-f0-9]+)"/', $connections['body'], $csrf);
    preg_match('/name="grant_id" value="([0-9]+)"/', $connections['body'], $grantId);
    mcpCheck(isset($csrf[1], $grantId[1]), 'Connection management rendered');
    $revoked = mcpHttp('/mcp/connections.php', ['csrf' => $csrf[1], 'grant_id' => $grantId[1]]);
    mcpCheck($revoked['status'] === 303, 'Personal grant revoked');
    mcpCheck(mcpHttpRpc('tools/list', [], $tokens['access_token'])['status'] === 401, 'Revoked token refused');
    $renew = mcpHttp('/mcp/token.php', ['client_id' => $registered->get('client_id'), 'grant_type' => 'refresh_token',
        'refresh_token' => $tokens['refresh_token'], 'resource' => $endpoint]);
    mcpCheck($renew['status'] === 400 && mcpHttpJson($renew)['error'] === 'invalid_grant', 'Revoked refresh refused');
    $startDenied = mcpHttp('/mcp/authorize.php?' . http_build_query($request));
    $deniedPath = $startDenied['headers']['location'];
    $deniedPage = mcpHttp($deniedPath);
    preg_match('/name="csrf" value="([a-f0-9]+)"/', $deniedPage['body'], $denyCsrf);
    $denied = mcpHttp($deniedPath, ['csrf' => $denyCsrf[1], 'decision' => 'deny']);
    parse_str(parse_url($denied['headers']['location'], PHP_URL_QUERY), $deniedCallback);
    mcpCheck($deniedCallback['error'] === 'access_denied' && $deniedCallback['state'] === 'test-state'
        && $deniedCallback['iss'] === omoMcpIssuer(), 'Consent denial returns state and issuer');
} finally {
    if ($registered) $registered->delete();
    mcpCleanup($items);
    if (is_file($jar)) unlink($jar);
}
echo "mcp_http_test: OK (discovery, login, consent, five tools, isolation, revocation)\n";
