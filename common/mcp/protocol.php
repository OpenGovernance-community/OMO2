<?php
// Shared protocol helpers; all persistence and data access live in dbObject classes.
const OMO_MCP_SCOPE = 'organization:read';
const OMO_MCP_CREATE_SCOPE = 'documents:create';
const OMO_MCP_MAIL_SCOPE = 'mail:send';

function omoMcpNormalizeScope(string $scope): ?string
{
    $scopes = preg_split('/ +/', trim($scope), -1, PREG_SPLIT_NO_EMPTY);
    if (!in_array(OMO_MCP_SCOPE, $scopes, true) || array_diff($scopes, [OMO_MCP_SCOPE, OMO_MCP_CREATE_SCOPE, OMO_MCP_MAIL_SCOPE])) return null;
    return implode(' ', array_values(array_intersect([OMO_MCP_SCOPE, OMO_MCP_CREATE_SCOPE, OMO_MCP_MAIL_SCOPE], $scopes)));
}
function omoMcpCanCreateDocuments(array $grant): bool
{
    return in_array(OMO_MCP_CREATE_SCOPE, explode(' ', $grant['scope'] ?? ''), true);
}
function omoMcpCanSendMail(array $grant): bool
{
    return in_array(OMO_MCP_MAIL_SCOPE, explode(' ', $grant['scope'] ?? ''), true);
}
const OMO_MCP_MODULES = ['structure', 'team', 'calendar', 'rules', 'documents', 'pv',
    'decision', 'projects', 'stats', 'processus', 'activities', 'faq', 'tutorials'];
const OMO_MCP_VERSIONS = ['2025-11-25', '2025-06-18', '2025-03-26'];

function omoMcpPublicUrl(): string
{
    $url = trim((string)commonReadRuntimeEnvValue('MCP_PUBLIC_URL', ''));
    $parts = parse_url($url);
    $local = is_array($parts) && in_array(strtolower($parts['host'] ?? ''), ['localhost', '127.0.0.1', '[::1]'], true);
    if (!$parts || strlen($url) > 512 || isset($parts['user']) || isset($parts['pass'])
        || isset($parts['query']) || isset($parts['fragment']) || !in_array($parts['path'] ?? '', ['/mcp', '/mcp/'], true)
        || !filter_var($url, FILTER_VALIDATE_URL)
        || (($parts['scheme'] ?? '') !== 'https' && !($local && ($parts['scheme'] ?? '') === 'http'
            && commonReadRuntimeEnvBool('MCP_ALLOW_LOCAL_HTTP', false)))) {
        throw new RuntimeException('Configure MCP_PUBLIC_URL with the canonical HTTPS URL ending in /mcp or /mcp/.');
    }
    return $url;
}

function omoMcpIssuer(): string { return substr(rtrim(omoMcpPublicUrl(), '/'), 0, -4); }
function omoMcpResourceMetadataUrl(): string
{
    return omoMcpIssuer() . '/.well-known/oauth-protected-resource' . parse_url(omoMcpPublicUrl(), PHP_URL_PATH);
}
function omoMcpChallenge(string $error = ''): string
{
    return 'Bearer resource_metadata="' . omoMcpResourceMetadataUrl() . '", scope="' . OMO_MCP_SCOPE . '"'
        . ($error !== '' ? ', error="' . $error . '"' : '');
}
function omoMcpResourceMetadata(): array
{
    return ['resource' => omoMcpPublicUrl(), 'authorization_servers' => [omoMcpIssuer()],
        'scopes_supported' => [OMO_MCP_SCOPE, OMO_MCP_CREATE_SCOPE, OMO_MCP_MAIL_SCOPE], 'bearer_methods_supported' => ['header'], 'resource_name' => 'OMO'];
}
function omoMcpAuthorizationMetadata(): array
{
    $issuer = omoMcpIssuer();
    return ['issuer' => $issuer, 'authorization_endpoint' => $issuer . '/mcp/authorize.php',
        'token_endpoint' => $issuer . '/mcp/token.php', 'registration_endpoint' => $issuer . '/mcp/register.php',
        'revocation_endpoint' => $issuer . '/mcp/revoke.php', 'response_types_supported' => ['code'],
        'grant_types_supported' => ['authorization_code', 'refresh_token'],
        'token_endpoint_auth_methods_supported' => ['none'], 'revocation_endpoint_auth_methods_supported' => ['none'],
        'code_challenge_methods_supported' => ['S256'], 'scopes_supported' => [OMO_MCP_SCOPE, OMO_MCP_CREATE_SCOPE, OMO_MCP_MAIL_SCOPE],
        'authorization_response_iss_parameter_supported' => true];
}
function omoMcpJson(array $body, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('Pragma: no-cache');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function omoMcpOauthError(string $error, string $description, int $status = 400): never
{
    omoMcpJson(['error' => $error, 'error_description' => $description], $status);
}
function omoMcpInput(): array
{
    $raw = file_get_contents('php://input', false, null, 0, 1048577);
    if ($raw === false || strlen($raw) > 1048576) throw new InvalidArgumentException('Request too large.');
    $value = json_decode($raw, false, 32, JSON_THROW_ON_ERROR);
    if (!$value instanceof stdClass) throw new InvalidArgumentException('Expected a JSON object.');
    return get_object_vars($value);
}
function omoMcpFormInput(): array
{
    if (!str_starts_with(strtolower((string)($_SERVER['CONTENT_TYPE'] ?? '')), 'application/x-www-form-urlencoded')) {
        omoMcpOauthError('invalid_request', 'Use application/x-www-form-urlencoded.');
    }
    foreach ($_POST as $value) {
        if (!is_string($value) || strlen($value) > 4096) omoMcpOauthError('invalid_request', 'Invalid form parameter.');
    }
    return $_POST;
}
function omoMcpRequirePost(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        header('Allow: POST');
        omoMcpOauthError('invalid_request', 'Use POST.', 405);
    }
}
function omoMcpValidRedirect(string $uri, bool $allowLoopback = false): bool
{
    if (strlen($uri) > 2048 || preg_match('/[\x00-\x20\x7f]/', $uri) || !filter_var($uri, FILTER_VALIDATE_URL)) return false;
    $parts = parse_url($uri);
    if (!$parts || isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])) return false;
    return ($parts['scheme'] ?? '') === 'https' || ($allowLoopback && ($parts['scheme'] ?? '') === 'http'
        && in_array(strtolower($parts['host'] ?? ''), ['localhost', '127.0.0.1', '[::1]'], true));
}
function omoMcpValidateAuthorization(array $input, \dbObject\McpOauthClient $client): array
{
    foreach (['client_id', 'redirect_uri', 'response_type', 'code_challenge', 'code_challenge_method', 'resource', 'scope', 'state'] as $field) {
        if (isset($input[$field]) && !is_string($input[$field])) throw new InvalidArgumentException('Invalid authorization parameter.');
    }
    if (($input['client_id'] ?? '') !== $client->get('client_id') || !$client->allowsRedirect($input['redirect_uri'] ?? '')) {
        throw new InvalidArgumentException('Invalid client or redirect URI.');
    }
    if (($input['response_type'] ?? '') !== 'code' || ($input['code_challenge_method'] ?? '') !== 'S256'
        || !preg_match('/^[A-Za-z0-9_-]{43}$/D', $input['code_challenge'] ?? '')
        || ($input['resource'] ?? '') !== omoMcpPublicUrl() || omoMcpNormalizeScope($input['scope'] ?? OMO_MCP_SCOPE) === null
        || strlen($input['state'] ?? '') > 2048) throw new InvalidArgumentException('Expected code + PKCE S256, the MCP resource and supported scopes including organization:read.');
    return ['client_id' => $input['client_id'], 'redirect_uri' => $input['redirect_uri'],
        'code_challenge' => $input['code_challenge'], 'resource' => $input['resource'],
        'scope' => omoMcpNormalizeScope($input['scope'] ?? OMO_MCP_SCOPE), 'state' => $input['state'] ?? ''];
}
function omoMcpRedirect(array $request, array $result): never
{
    $result['state'] = $request['state'];
    $result['iss'] = omoMcpIssuer();
    header('Cache-Control: no-store');
    header('Location: ' . $request['redirect_uri'] . (str_contains($request['redirect_uri'], '?') ? '&' : '?')
        . http_build_query($result, '', '&', PHP_QUERY_RFC3986), true, 303);
    exit;
}
function omoMcpRateLimit(string $scope, int $maximum, int $window): void
{
    $limit = \dbObject\AuthRateLimit::consume($scope, commonAuthHashIdentifier('mcp_ip', $_SERVER['REMOTE_ADDR'] ?? ''),
        $maximum, $window, $window);
    if (empty($limit['allowed'])) {
        header('Retry-After: ' . max(1, (int)($limit['retry_after'] ?? 60)));
        omoMcpOauthError('temporarily_unavailable', 'Too many requests. Try again later.', 429);
    }
}
function omoMcpCheckOrigin(): void
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin === '') return;
    $allowed = array_filter(array_map('trim', explode(',', (string)commonReadRuntimeEnvValue('MCP_ALLOWED_ORIGINS', ''))));
    $allowed[] = omoMcpIssuer();
    if (!in_array($origin, $allowed, true)) omoMcpJson(['error' => 'Origin not allowed.'], 403);
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Authorization, Content-Type, MCP-Protocol-Version, Accept');
    header('Access-Control-Expose-Headers: WWW-Authenticate');
}
