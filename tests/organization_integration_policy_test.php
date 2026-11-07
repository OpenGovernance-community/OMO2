<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';
require_once dirname(__DIR__) . '/common/patreon.php';
require_once dirname(__DIR__) . '/common/mcp/ui.php';
require_once dirname(__DIR__) . '/common/assistant/agent.php';
use dbObject\{Organization, McpOauthGrant};
$before = $_SESSION;
$items = mcpFixtures();
$server = null; $router = null; $serverLog = null;
try {
    $org = $items['org']; $oid = (int)$org->getId(); $uid = (int)$items['user']->getId();
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid];
    mcpCheck(\dbObject\OrganizationPatreon::findByOrganizationId($oid) === null, 'Organization has no Patreon link');
    mcpCheck(patreonIndividualCanUseAi($uid), 'Member uses an individual Patreon entitlement');
    mcpCheck(!$items['other_org']->isApiEnabled() && !$items['other_org']->isMcpEnabled(), 'New or unconfigured organizations block both integrations by default');
    $request = mcpAuthorizationRequest($items['client']);
    $request['scope'] .= ' ' . OMO_MCP_CREATE_SCOPE;
    $code = McpOauthGrant::issueCode($items['client'], $uid, $oid, $request);
    $tokens = McpOauthGrant::exchange($items['client'], mcpExchangeRequest($items['client'], $code));
    mcpCheck($tokens !== null, 'Explicit opt-in allows OAuth setup');
    $apiGrant = McpOauthGrant::authenticate($tokens['access_token'], omoMcpPublicUrl(), 'api');
    $mcpGrant = McpOauthGrant::authenticate($tokens['access_token'], omoMcpPublicUrl(), 'mcp');
    $host = (string)parse_url(omoMcpPublicUrl(), PHP_URL_HOST);
    mcpCheck($host === 'localtest.me' || str_ends_with($host, '.localtest.me'), 'HTTP checks are restricted to local Docker');
    // Isolate unrelated partial Patreon settings without editing the site's .env.
    $router = tempnam(sys_get_temp_dir(), 'omo-integration-router-');
    $serverLog = tempnam(sys_get_temp_dir(), 'omo-integration-http-');
    $root = dirname(__DIR__);
    file_put_contents($router, '<?php $_SERVER["HTTPS"]="on"; require ' . var_export($root . '/shared_functions.php', true)
        . '; $GLOBALS["patreonClientId"]="fixture-client"; $GLOBALS["patreonClientSecret"]="fixture-secret"; '
        . 'require str_starts_with($_SERVER["REQUEST_URI"],"/api/v1/") ? ' . var_export($root . '/api/v1/index.php', true) . ' : ' . var_export($root . '/mcp/index.php', true) . ';');
    $port = random_int(20000, 45000);
    $server = proc_open([PHP_BINARY, '-S', '127.0.0.1:' . $port, $router], [0 => ['pipe', 'r'], 1 => ['file', $serverLog, 'a'], 2 => ['file', $serverLog, 'a']], $pipes, $root);
    mcpCheck(is_resource($server), 'Start isolated loopback test server');
    fclose($pipes[0]);
    for ($attempt = 0; $attempt < 30; $attempt++) {
        $ready = @fsockopen('127.0.0.1', $port, $errno, $error, 0.1);
        if ($ready) { fclose($ready); break; }
        usleep(100000);
    }
    mcpCheck($attempt < 30, 'Loopback test server ready');
    $http = static function (string $transport) use ($tokens, $host, $port): int {
        $path = $transport === 'api' ? '/api/v1/catalog' : (string)parse_url(omoMcpPublicUrl(), PHP_URL_PATH);
        $curl = curl_init('http://127.0.0.1:' . $port . $path);
        $options = [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => ['Host: ' . $host, 'Authorization: Bearer ' . $tokens['access_token'], 'Content-Type: application/json', 'Accept: application/json']];
        if ($transport === 'mcp') { $options[CURLOPT_POST] = true; $options[CURLOPT_POSTFIELDS] = '{"jsonrpc":"2.0","id":1,"method":"tools/list"}'; }
        curl_setopt_array($curl, $options); $body = curl_exec($curl);
        if ((int)curl_getinfo($curl, CURLINFO_HTTP_CODE) !== 200 && (int)curl_getinfo($curl, CURLINFO_HTTP_CODE) !== 401) {
            echo json_encode(['transport' => $transport, 'status' => curl_getinfo($curl, CURLINFO_HTTP_CODE), 'error' => is_string($body) ? substr($body, 0, 300) : curl_error($curl)]), "\n";
        }
        return (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
    };
    if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
    foreach ([[true, false], [false, true], [true, true], [false, false]] as [$api, $mcp]) {
        $org->setApiEnabled($api); $org->setMcpEnabled($mcp);
        mcpCheck(!empty($org->save()['status']), 'Save independent policy choices');
        mcpCheck((McpOauthGrant::authenticate($tokens['access_token'], omoMcpPublicUrl(), 'api') !== null) === $api, 'Existing API tokens follow only the API switch');
        mcpCheck((McpOauthGrant::authenticate($tokens['access_token'], omoMcpPublicUrl(), 'mcp') !== null) === $mcp, 'Existing MCP tokens follow only the MCP switch');
        mcpCheck(McpOauthGrant::hasActiveScopeAuthorization($apiGrant, OMO_MCP_CREATE_SCOPE) === $api, 'API write authorization rechecks its own policy');
        mcpCheck(McpOauthGrant::hasActiveScopeAuthorization($mcpGrant, OMO_MCP_CREATE_SCOPE) === $mcp, 'MCP write authorization rechecks its own policy');
        $apiStatus = $http('api');
        mcpCheck($apiStatus === ($api ? 200 : 401), 'REST endpoint enforces the API switch: got ' . $apiStatus);
        mcpCheck($http('mcp') === ($mcp ? 200 : 401), 'MCP endpoint enforces the MCP switch');
        $eligible = array_map(static fn ($organization) => (int)$organization->getId(), omoMcpEligibleOrganizations($uid));
        mcpCheck(in_array($oid, $eligible, true) === ($api || $mcp), 'OAuth selection requires at least one permitted transport');
    }
    mcpCheck(commonAssistantGrant($oid)['IDorganization'] === $oid, 'Built-in organization assistant remains available when external integrations are blocked');
    mcpCheck(McpOauthGrant::exchange($items['client'], ['grant_type' => 'refresh_token', 'resource' => omoMcpPublicUrl(), 'refresh_token' => $tokens['refresh_token']]) === null, 'Both blocked prevents token renewal');
    $rejected = false;
    try { McpOauthGrant::issueCode($items['client'], $uid, $oid, $request); } catch (DomainException $error) { $rejected = true; }
    mcpCheck($rejected, 'Both blocked prevents new consent grants');
    $org->setApiEnabled(true); $org->save();
    $pending = McpOauthGrant::issueCode($items['client'], $uid, $oid, $request);
    $org->setApiEnabled(false); $org->save();
    mcpCheck(McpOauthGrant::exchange($items['client'], mcpExchangeRequest($items['client'], $pending)) === null, 'An unused authorization code cannot bypass a later block');
    mcpCheck(McpOauthGrant::authenticate($tokens['access_token'], omoMcpPublicUrl(), 'unknown') === null, 'Unknown transports fail closed');
    echo "PASS organization API/MCP defaults, independent switches, HTTP read/write authorization, OAuth and unaffected built-in assistant\n";
} finally {
    if (is_resource($server)) { proc_terminate($server); proc_close($server); }
    if ($router !== null) unlink($router);
    if ($serverLog !== null) unlink($serverLog);
    mcpCleanup($items); $_SESSION = $before;
}
