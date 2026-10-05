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
    $teamApps = new \dbObject\ArrayApplication();
    $teamApps->load(['where' => [['field' => 'hash', 'value' => 'team']], 'limit' => 1]);
    mcpCheck(count($teamApps) === 1, 'Team app must exist');
    $items['team_app'] = mcpFixture(\dbObject\OrganizationApplication::class,
        ['IDorganization' => $items['org']->getId(), 'IDapplication' => $teamApps[0]->getId(), 'active' => 1]);
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
    $eventRequest = array_replace($request, ['scope' => OMO_MCP_SCOPE . ' ' . OMO_MCP_EVENT_SCOPE]);
    $eventStart = mcpHttp('/mcp/authorize.php?' . http_build_query($eventRequest));
    $eventConsent = mcpHttp($eventStart['headers']['location']);
    mcpCheck($eventConsent['status'] === 200 && str_contains($eventConsent['body'], 'Creer des evenements')
        && !str_contains($eventConsent['body'], 'Cette autorisation est limitee a la lecture.'), 'Event creation consent is explicitly displayed');
    preg_match('/name="csrf" value="([a-f0-9]+)"/', $eventConsent['body'], $eventCsrf);
    $eventAuthorized = mcpHttp($eventStart['headers']['location'], ['csrf' => $eventCsrf[1], 'decision' => 'allow', 'organization_id' => $items['org']->getId()]);
    parse_str(parse_url($eventAuthorized['headers']['location'], PHP_URL_QUERY), $eventCallback);
    $eventTokens = mcpHttpJson(mcpHttp('/mcp/token.php', mcpExchangeRequest($registered, $eventCallback['code'])));
    mcpCheck($eventTokens['scope'] === $eventRequest['scope'], 'Event creation scope survives consent and token exchange');
    $decisionRequest = array_replace($request, ['scope' => OMO_MCP_SCOPE . ' ' . OMO_MCP_DECISION_SCOPE]);
    $decisionStart = mcpHttp('/mcp/authorize.php?' . http_build_query($decisionRequest));
    $decisionConsent = mcpHttp($decisionStart['headers']['location']);
    mcpCheck($decisionConsent['status'] === 200 && str_contains($decisionConsent['body'], 'Creer des scrutins')
        && str_contains($decisionConsent['body'], 'evenements provisoires')
        && !str_contains($decisionConsent['body'], 'Cette autorisation est limitee a la lecture.'), 'Decision and calendar effects explicitly disclosed at consent');
    preg_match('/name="csrf" value="([a-f0-9]+)"/', $decisionConsent['body'], $decisionCsrf);
    $decisionAuthorized = mcpHttp($decisionStart['headers']['location'], ['csrf' => $decisionCsrf[1], 'decision' => 'allow', 'organization_id' => $items['org']->getId()]);
    parse_str(parse_url($decisionAuthorized['headers']['location'], PHP_URL_QUERY), $decisionCallback);
    $decisionTokens = mcpHttpJson(mcpHttp('/mcp/token.php', mcpExchangeRequest($registered, $decisionCallback['code'])));
    mcpCheck($decisionTokens['scope'] === $decisionRequest['scope'], 'Decision scope survives native OAuth consent and exchange');
    $connections = mcpHttp('/mcp/connections.php');
    mcpCheck(str_contains($connections['body'], 'Creer des evenements'), 'Event consent is visible in personal connections');
    mcpCheck(str_contains($connections['body'], 'Creer des scrutins'), 'Decision consent is visible in personal connections');
    $cookieOnly = mcpHttpRpc('tools/list', [], null);
    mcpCheck($cookieOnly['status'] === 401, 'Logged-in browser cookie cannot authorize MCP');
    $initialized = mcpHttpRpc('initialize', ['protocolVersion' => '2025-11-25', 'capabilities' => new stdClass(),
        'clientInfo' => (object)['name' => 'test', 'version' => '1']], $tokens['access_token']);
    mcpCheck(mcpHttpJson($initialized)['result']['serverInfo']['name'] === 'OpenMyOrganization', 'MCP initialization');
    $tools = mcpHttpRpc('tools/list', [], $tokens['access_token']);
    mcpCheck(count(mcpHttpJson($tools)['result']['tools']) === 19, 'Authenticated tool discovery');
    foreach (['omo_connection_info' => new stdClass(), 'omo_catalog' => new stdClass(),
        'omo_list_records' => (object)['module' => 'structure', 'limit' => 1], 'omo_list_assignments' => new stdClass(),
        'omo_list_structure' => (object)['limit' => 1],
        'omo_get_member' => (object)['user_id' => (int)$items['user']->getId()],
        'omo_list_object_members' => (object)['object_type' => 'holon', 'object_id' => (int)$items['root']->getId()],
        'omo_get_holon' => (object)['holon_id' => (int)$items['role']->getId()],
        'omo_search' => (object)['query' => 'MCP', 'modules' => ['structure']],
        'omo_read_record' => (object)['module' => 'structure', 'record_id' => (int)$items['role']->getId()]] as $tool => $arguments) {
        $reply = mcpHttpRpc('tools/call', ['name' => $tool, 'arguments' => $arguments], $tokens['access_token']);
        mcpCheck(!mcpHttpJson($reply)['result']['isError'], 'Tool succeeds: ' . $tool);
    }
    mcpEnableDocumentCreation($items);
    $memberPreview = mcpHttpJson(mcpHttpRpc('tools/call', ['name' => 'omo_list_object_members', 'arguments' => (object)['object_type' => 'holon', 'object_id' => (int)$items['root']->getId()]], $tokens['access_token']))['result']['structuredContent'];
    mcpCheck($memberPreview['total'] === 1 && !$memberPreview['mail_authorized'], 'Member list exposes consent separately from OMO rights');
    $emailArgs = ['object_type' => 'holon', 'object_id' => (int)$items['root']->getId(), 'subject' => 'Test', 'message' => 'Test', 'audience_token' => $memberPreview['audience_token'], 'request_key' => 'http-mail-read-denied'];
    $emailDenied = mcpHttpJson(mcpHttpRpc('tools/call', ['name' => 'omo_send_object_email', 'arguments' => (object)$emailArgs], $tokens['access_token']));
    mcpCheck($emailDenied['result']['isError'] && str_contains($emailDenied['result']['_meta']['mcp/www_authenticate'][0], 'mail:send'), 'HTTP mail write requires explicit consent');
    $nativePath = '/omo/api/object_mail/index.php?' . http_build_query(['oid' => $items['org']->getId(), 'object_type' => 'holon', 'object_id' => $items['root']->getId()]);
    $nativePage = mcpHttp($nativePath);
    mcpCheck($nativePage['status'] === 200 && str_contains($nativePage['body'], 'name="audience_token"'), 'Native authenticated composer exposes fixed audience');
    preg_match('/name="csrf" value="([a-f0-9]+)"/', $nativePage['body'], $nativeCsrf);
    $nativeDenied = mcpHttp($nativePath, ['csrf' => 'invalid'] + $emailArgs);
    mcpCheck($nativeDenied['status'] === 400 && str_contains($nativeDenied['body'], 'Formulaire invalide'), 'Native CSRF blocks writes');
    $nativeValidation = mcpHttp($nativePath, ['csrf' => $nativeCsrf[1], 'request_key' => 'http-native-invalid', 'audience_token' => $memberPreview['audience_token']]);
    mcpCheck($nativeValidation['status'] === 400 && str_contains($nativeValidation['body'], 'Champ invalide'), 'Native CSRF persists; incomplete mail cannot enqueue');
    $createArgs = ['title' => 'HTTP created document', 'request_key' => 'http-creation-test',
        'holon_id' => (int)$items['role']->getId(), 'content' => 'Text saved through authenticated HTTP'];
    $writeDenied = mcpHttpJson(mcpHttpRpc('tools/call', ['name' => 'omo_create_document', 'arguments' => (object)$createArgs], $tokens['access_token']));
    mcpCheck($writeDenied['result']['isError'] && isset($writeDenied['result']['_meta']['mcp/www_authenticate']), 'Read-only grant challenges for creation consent');
    $upgrade = mcpHttp('/mcp/token.php', ['client_id' => $registered->get('client_id'), 'grant_type' => 'refresh_token',
        'refresh_token' => $tokens['refresh_token'], 'resource' => $endpoint, 'scope' => OMO_MCP_SCOPE . ' ' . OMO_MCP_CREATE_SCOPE]);
    mcpCheck($upgrade['status'] === 400, 'Refresh cannot add creation scope');
    $writeRequest = array_replace($request, ['scope' => OMO_MCP_SCOPE . ' ' . OMO_MCP_CREATE_SCOPE . ' ' . OMO_MCP_MAIL_SCOPE]);
    $writeStart = mcpHttp('/mcp/authorize.php?' . http_build_query($writeRequest));
    $writePath = $writeStart['headers']['location']; $writePage = mcpHttp($writePath);
    mcpCheck(str_contains($writePage['body'], 'Envoyer des e-mails') && str_contains($writePage['body'], 'Creer des documents'), 'Mail and document scopes are both displayed explicitly');
    preg_match('/name="csrf" value="([a-f0-9]+)"/', $writePage['body'], $writeCsrf);
    $writeConsent = mcpHttp($writePath, ['csrf' => $writeCsrf[1], 'decision' => 'allow', 'organization_id' => $items['org']->getId()]);
    parse_str(parse_url($writeConsent['headers']['location'], PHP_URL_QUERY), $writeCallback);
    $writeTokens = mcpHttpJson(mcpHttp('/mcp/token.php', mcpExchangeRequest($registered, $writeCallback['code'])));
    mcpCheck($writeTokens['scope'] === $writeRequest['scope'], 'Creation scope preserved in token response');
    if (in_array('--mailpit', $argv, true)) {
        mcpCheck(($GLOBALS['mailHost'] ?? '') === 'mailpit' && (int)($GLOBALS['mailPort'] ?? 0) === 1025 && empty($GLOBALS['mailAuth']), 'SMTP integration is restricted to local Mailpit');
        $sendArgs = array_replace($emailArgs, ['request_key' => 'http-direct-mail', 'subject' => 'Synthetic OMO direct test']);
        $direct = mcpHttpJson(mcpHttpRpc('tools/call', ['name' => 'omo_send_object_email', 'arguments' => (object)$sendArgs], $writeTokens['access_token']));
        mcpCheck(!$direct['result']['isError'] && $direct['result']['structuredContent']['delivery']['sent'] === 1 && $direct['result']['structuredContent']['complete'], 'Small MCP email delivered directly through local SMTP');
        $items['http_direct_mail'] = new \dbObject\ObjectMail(); $items['http_direct_mail']->load($direct['result']['structuredContent']['mail_id']);
        $nativeSent = mcpHttp($nativePath, ['csrf' => $nativeCsrf[1], 'subject' => 'Synthetic OMO native test', 'message' => 'Synthetic Mailpit-only delivery', 'request_key' => 'http-native-mail', 'audience_token' => $memberPreview['audience_token']]);
        mcpCheck($nativeSent['status'] === 303, 'Native form delivers and redirects to tracking');
        parse_str(parse_url($nativeSent['headers']['location'], PHP_URL_QUERY), $nativeTracking);
        $items['http_native_mail'] = new \dbObject\ObjectMail(); $items['http_native_mail']->load((int)$nativeTracking['mail_id']);
        $uid = (int)$items['user']->getId(); $oid = (int)$items['org']->getId();
        $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid];
        mcpCheck(\dbObject\ObjectMail::status($oid, (int)$nativeTracking['mail_id'])['delivery']['sent'] === 1, 'Native small audience is delivered before redirect');
        for ($index = 0; $index < 5; $index++) {
            $items['mail_user_' . $index] = mcpFixture(\dbObject\User::class, ['firstname' => 'SMTP fixture', 'email' => 'smtp-' . bin2hex(random_bytes(6)) . '@example.invalid', 'active' => 1]);
            $items['mail_membership_' . $index] = mcpFixture(\dbObject\UserOrganization::class, ['IDuser' => $items['mail_user_' . $index]->getId(), 'IDorganization' => $oid, 'active' => 1]);
            $items['mail_assignment_' . $index] = mcpFixture(\dbObject\UserHolon::class, ['IDuser' => $items['mail_user_' . $index]->getId(), 'IDholon' => $items['role']->getId(), 'is_membership' => 1, 'active' => 1]);
        }
        $group = mcpHttpJson(mcpHttpRpc('tools/call', ['name' => 'omo_list_object_members', 'arguments' => (object)['object_type' => 'holon', 'object_id' => (int)$items['root']->getId()]], $writeTokens['access_token']))['result']['structuredContent'];
        mcpCheck($group['recipient_count'] === 6, 'Async fixture has six synthetic recipients');
        $queuedArgs = array_replace($sendArgs, ['request_key' => 'http-async-mail', 'subject' => 'Synthetic OMO automatic worker test', 'audience_token' => $group['audience_token']]);
        $queued = mcpHttpJson(mcpHttpRpc('tools/call', ['name' => 'omo_send_object_email', 'arguments' => (object)$queuedArgs], $writeTokens['access_token']))['result'];
        mcpCheck(!$queued['isError'] && $queued['structuredContent']['delivery_mode'] === 'queued', 'Large HTTP audience launches deferred sending');
        $items['http_async_mail'] = new \dbObject\ObjectMail(); $items['http_async_mail']->load($queued['structuredContent']['mail_id']);
        $deadline = microtime(true) + 10;
        do {
            usleep(100000);
            $asyncStatus = mcpHttpJson(mcpHttpRpc('tools/call', ['name' => 'omo_object_email_status', 'arguments' => (object)['mail_id' => $queued['structuredContent']['mail_id']]], $writeTokens['access_token']))['result']['structuredContent'];
        } while (!$asyncStatus['complete'] && microtime(true) < $deadline);
        mcpCheck($asyncStatus['complete'] && $asyncStatus['delivery']['sent'] === 6, 'Automatic CLI worker delivers every queued fixture without browser maintenance');
        echo "mcp_http_test: local Mailpit direct/native/automatic worker OK\n";
    }
    $spaces = mcpHttpJson(mcpHttpRpc('tools/call', ['name' => 'omo_list_document_spaces', 'arguments' => (object)['kind' => 'holons']], $writeTokens['access_token']));
    mcpCheck($spaces['result']['structuredContent']['items'][0]['holon_id'] === (int)$items['role']->getId(), 'Creation destination discovered over HTTP');
    $created = mcpHttpJson(mcpHttpRpc('tools/call', ['name' => 'omo_create_document', 'arguments' => (object)$createArgs], $writeTokens['access_token']));
    mcpCheck(!$created['result']['isError'] && $created['result']['structuredContent']['created'], 'Document created over HTTP');
    $items['http_document'] = new \dbObject\Document(); $items['http_document']->load($created['result']['structuredContent']['record']['record_id']);
    $retry = mcpHttpJson(mcpHttpRpc('tools/call', ['name' => 'omo_create_document', 'arguments' => (object)$createArgs], $writeTokens['access_token']));
    mcpCheck($retry['result']['structuredContent']['replayed'], 'HTTP retry does not duplicate');
    foreach ([
        'html' => ['content' => '<h2>HTTP Memo</h2><p onclick="bad()"><strong>Formatted</strong></p><script>bad()</script>', 'content_format' => 'html'],
        'markdown' => ['content' => "## HTTP Memo\n\n**Formatted**\n\n<script>bad()</script>", 'content_format' => 'markdown'],
        'url' => ['external_url' => 'https://example.invalid/http-project#section'],
    ] as $kind => $payload) {
        $formattedArgs = ['title' => 'HTTP ' . $kind, 'request_key' => 'http-formatted-' . $kind, 'holon_id' => (int)$items['role']->getId()] + $payload;
        $formatted = mcpHttpJson(mcpHttpRpc('tools/call', ['name' => 'omo_create_document', 'arguments' => (object)$formattedArgs], $writeTokens['access_token']));
        mcpCheck(!$formatted['result']['isError'], 'Formatted content or link created over HTTP');
        $data = $formatted['result']['structuredContent'];
        $items['http_' . $kind] = new \dbObject\Document();
        mcpCheck($items['http_' . $kind]->load($data['record']['record_id']), 'HTTP document persisted');
        if ($kind === 'url') {
            mcpCheck($data['document_type'] === \dbObject\Document::TYPE_EXTERNAL_LINK && $data['external_url'] === $payload['external_url'], 'HTTP URL is a native link');
        } else {
            $savedContent = (string)$items['http_' . $kind]->get('content');
            mcpCheck($data['document_type_label'] === 'Memo' && str_contains($savedContent, '<h2>HTTP Memo</h2>')
                && str_contains($savedContent, '<strong>Formatted</strong>') && !str_contains($savedContent, 'bad()'), 'HTTP Memo preserves formatting through the security filter');
        }
    }
    // Two simultaneous retries must converge on a single committed document.
    $parallel = curl_multi_init(); $handles = []; $parallelArgs = array_replace($createArgs, ['request_key' => 'http-parallel-creation']);
    for ($index = 0; $index < 2; $index++) {
        $handle = curl_init($endpoint);
        curl_setopt_array($handle, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => 0, CURLOPT_RESOLVE => [$host . ':443:127.0.0.1'],
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json', 'Authorization: Bearer ' . $writeTokens['access_token']],
            CURLOPT_POSTFIELDS => json_encode(['jsonrpc' => '2.0', 'id' => $index, 'method' => 'tools/call',
                'params' => ['name' => 'omo_create_document', 'arguments' => (object)$parallelArgs]], JSON_THROW_ON_ERROR)]);
        $handles[] = $handle; curl_multi_add_handle($parallel, $handle);
    }
    do { curl_multi_exec($parallel, $running); if ($running) curl_multi_select($parallel, 0.2); } while ($running);
    $parallelIds = []; $replayCount = 0;
    foreach ($handles as $handle) {
        $reply = json_decode(curl_multi_getcontent($handle), true, 32, JSON_THROW_ON_ERROR);
        mcpCheck(curl_getinfo($handle, CURLINFO_RESPONSE_CODE) === 200 && !$reply['result']['isError'], 'Concurrent creation succeeds');
        $parallelIds[] = $reply['result']['structuredContent']['record']['record_id'];
        $replayCount += (int)$reply['result']['structuredContent']['replayed'];
        curl_multi_remove_handle($parallel, $handle);
    }
    unset($parallel, $handles, $handle);
    $items['parallel_document'] = new \dbObject\Document(); $items['parallel_document']->load($parallelIds[0]);
    mcpCheck($parallelIds[0] === $parallelIds[1] && $replayCount === 1, 'Simultaneous requests create exactly one document');
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
    $readGrantId = \dbObject\McpOauthGrant::authenticate($tokens['access_token'], $endpoint)['id'];
    $writeGrantId = \dbObject\McpOauthGrant::authenticate($writeTokens['access_token'], $endpoint)['id'];
    $revoked = mcpHttp('/mcp/connections.php', ['csrf' => $csrf[1], 'grant_id' => $readGrantId]);
    mcpCheck($revoked['status'] === 303, 'Personal grant revoked');
    mcpCheck(mcpHttpRpc('tools/list', [], $tokens['access_token'])['status'] === 401, 'Revoked token refused');
    mcpHttp('/mcp/connections.php', ['csrf' => $csrf[1], 'grant_id' => $writeGrantId]);
    mcpCheck(mcpHttpRpc('tools/call', ['name' => 'omo_create_document', 'arguments' => (object)$createArgs], $writeTokens['access_token'])['status'] === 401, 'Revoked write consent refuses creation');
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
    echo "mcp_http_test: OK (19 tools, member lists, native composer/CSRF, read/mail/document/event/decision consent, document creation, isolation, revocation)\n";
