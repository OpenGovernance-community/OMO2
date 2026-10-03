<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';
$endpoint = omoMcpPublicUrl();
mcpCheck(parse_url($endpoint, PHP_URL_HOST) === 'localtest.me', 'Browser fixtures are restricted to local Docker');
$callback = $argv[1] ?? '';
mcpCheck(preg_match('~^http://127\.0\.0\.1:[0-9]+/callback$~D', $callback) === 1
    && commonReadRuntimeEnvBool('MCP_ALLOW_LOCAL_HTTP', false), 'Browser test requires an enabled loopback callback');
$items = mcpFixtures();
try {
    $items['client']->set('redirect_uris', json_encode([$callback], JSON_THROW_ON_ERROR));
    mcpCheck(!empty($items['client']->save()['status']), 'Register browser test callback');
    $request = mcpAuthorizationRequest($items['client']) + ['response_type' => 'code', 'code_challenge_method' => 'S256'];
    $request['redirect_uri'] = $callback;
    echo json_encode(['origin' => omoMcpIssuer(), 'authorization' => '/mcp/authorize.php?' . http_build_query($request),
        'email' => $items['user']->get('email'), 'password' => $items['password']], JSON_THROW_ON_ERROR) . "\n";
    fflush(STDOUT);
    fgets(STDIN);
} finally { mcpCleanup($items); }
