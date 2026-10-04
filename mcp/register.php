<?php
require_once __DIR__ . '/bootstrap.php';
omoMcpCheckOrigin();
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
omoMcpRequirePost();
omoMcpRateLimit('mcp_registration', 30, 3600);
try {
    if (!str_starts_with(strtolower($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json')) throw new InvalidArgumentException('Use application/json.');
    $input = omoMcpInput();
    $uris = $input['redirect_uris'] ?? null;
    $name = $input['client_name'] ?? 'MCP client';
    if (!is_array($uris) || count($uris) < 1 || count($uris) > 5 || !is_string($name)
        || trim($name) === '' || mb_strlen($name, 'UTF-8') > 150 || preg_match('/[\x00-\x1f\x7f]/', $name)) {
        throw new InvalidArgumentException('Provide a client_name and 1 to 5 redirect_uris.');
    }
    foreach ($uris as $uri) {
        if (!is_string($uri) || !omoMcpValidRedirect($uri, commonReadRuntimeEnvBool('MCP_ALLOW_LOCAL_HTTP', false))) {
            throw new InvalidArgumentException('Redirects must use HTTPS. Loopback HTTP requires MCP_ALLOW_LOCAL_HTTP=1.');
        }
    }
    $grantTypes = $input['grant_types'] ?? ['authorization_code', 'refresh_token'];
    if (($input['token_endpoint_auth_method'] ?? 'none') !== 'none'
        || ($input['response_types'] ?? ['code']) !== ['code'] || !is_array($grantTypes)
        || array_filter($grantTypes, static fn ($value) => !is_string($value))
        || array_diff($grantTypes, ['authorization_code', 'refresh_token'])) {
        throw new InvalidArgumentException('Only public clients, authorization_code and refresh_token are supported.');
    }
    $client = \dbObject\McpOauthClient::register(trim($name), array_values(array_unique($uris)));
    omoMcpJson(['client_id' => $client->get('client_id'), 'client_id_issued_at' => (int)$client->get('created_at'),
        'client_name' => $client->get('name'), 'redirect_uris' => $uris, 'token_endpoint_auth_method' => 'none',
        'grant_types' => ['authorization_code', 'refresh_token'], 'response_types' => ['code'], 'scope' => OMO_MCP_SCOPE], 201);
} catch (InvalidArgumentException | JsonException $error) {
    omoMcpOauthError('invalid_client_metadata', $error->getMessage());
}
