<?php
declare(strict_types=1);
define('OMO_API_LOCAL_LINKS', true);
require_once __DIR__ . '/mcp_test_helpers.php';
require_once __DIR__ . '/rest_test_helpers.php';
require_once dirname(__DIR__) . '/common/assistant/agent.php';
use dbObject\{ArrayApplication, OrganizationApplication, Rule, McpOauthGrant};

$before = $_SESSION; $envBefore = $_ENV; $items = mcpFixtures();
$server = null; $router = null; $serverLog = null;
try {
    $oid = (int)$items['org']->getId(); $uid = (int)$items['user']->getId();
    $hid = (int)$items['role']->getId();
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid];
    $apps = new ArrayApplication();
    $apps->load(['where' => [['field' => 'hash', 'value' => 'policy']], 'limit' => 1]);
    $items['policy_app'] = mcpFixture(OrganizationApplication::class,
        ['IDorganization' => $oid, 'IDapplication' => $apps[0]->getId(), 'active' => 1]);
    $body = '<p>Obligation: prévenir le secrétariat.</p>' . str_repeat('<p>Article de règlement. Détail applicable.</p>', 700)
        . '<p>Exception finale: dispense écrite obligatoire.</p>';
    $base = ['IDholon' => $hid, 'title' => 'Chapter Alpha', 'description' => $body,
        'intention' => '<p>Intention: assurer la continuité.</p>', 'scope' => 'local',
        'review_date' => '2027-01-01', 'expiration_date' => '2030-01-01'];
    $items['rule'] = mcpFixture(Rule::class, $base);
    $items['hidden_rule'] = mcpFixture(Rule::class, ['IDholon' => $items['hidden']->getId(), 'title' => 'Hidden chapter'] + $base);
    $items['foreign_rule'] = mcpFixture(Rule::class, ['IDholon' => $items['other_root']->getId()] + $base);
    $rid = (int)$items['rule']->getId();
    $grant = commonAssistantGrant($oid);
    $call = static fn (string $tool, array $args) => omoApiExecuteOperation($tool, omoMcpToolArguments($tool, (object)$args), $grant);
    $listed = $call('omo_list_records', ['module' => 'rules', 'query' => 'Chapter Alpha']);
    mcpCheck(count($listed['items']) === 1, 'Only the accessible chapter is listed');
    $nav = $listed['items'][0]['record_details'];
    mcpCheck($nav['arguments']['context_holon_id'] === $hid, 'Detail navigation preserves local rule context');
    restCheckSchema('omo_list_records', ['status' => 200, 'data' => $listed]);
    $whole = ''; $args = $nav['arguments']; $pages = 0;
    do {
        $page = $call($nav['tool'], $args); $whole .= $page['text']; $pages++;
        restCheckSchema('omo_read_record', ['status' => 200, 'data' => $page]);
        $args['offset'] = $page['next_offset'];
    } while ($page['next_offset'] !== null);
    mcpCheck($pages > 1 && str_contains($whole, 'Obligation: prévenir le secrétariat.')
        && str_contains($whole, 'Exception finale: dispense écrite obligatoire.')
        && str_contains($whole, 'Intention: assurer la continuité.'), 'Full body and intention survive UTF-8 pagination');
    mcpCheck($call('omo_list_records', ['module' => 'rules', 'query' => 'secrétariat'])['items'] === [], 'List query matches titles only');
    $search = $call('omo_search', ['modules' => ['rules'], 'query' => 'secrétariat', 'context_holon_id' => $hid]);
    mcpCheck(in_array($rid, array_column($search['items'], 'record_id'), true), 'Search finds words inside rule contents');
    foreach (['hidden_rule', 'foreign_rule'] as $key) {
        $denied = false;
        try { $call('omo_read_record', ['module' => 'rules', 'record_id' => (int)$items[$key]->getId()]); }
        catch (DomainException $error) { $denied = true; }
        mcpCheck($denied, 'Detail cannot bypass visibility: ' . $key);
    }

    // Real HTTP transports, with isolated fixture Patreon settings (no site configuration changes).
    $auth = mcpAuthorizationRequest($items['client']);
    $code = McpOauthGrant::issueCode($items['client'], $uid, $oid, $auth);
    $tokens = McpOauthGrant::exchange($items['client'], mcpExchangeRequest($items['client'], $code));
    $host = parse_url(omoMcpPublicUrl(), PHP_URL_HOST);
    mcpCheck(in_array($host, ['localhost', '127.0.0.1', 'localtest.me'], true) || str_ends_with($host, '.localtest.me'), 'Local HTTP fixtures only');
    $router = tempnam(sys_get_temp_dir(), 'omo-rule-router-');
    $serverLog = tempnam(sys_get_temp_dir(), 'omo-rule-http-');
    $root = dirname(__DIR__);
    file_put_contents($router, '<?php $_SERVER["HTTPS"]="on"; require ' . var_export($root . '/shared_functions.php', true)
        . '; define("OMO_API_LOCAL_LINKS", true); $GLOBALS["patreonClientId"]="fixture-client"; $GLOBALS["patreonClientSecret"]="fixture-secret"; '
        . 'require str_starts_with($_SERVER["REQUEST_URI"],"/api/v1/") ? ' . var_export($root . '/api/v1/index.php', true)
        . ' : ' . var_export($root . '/mcp/index.php', true) . ';');
    $port = random_int(20000, 45000);
    $server = proc_open([PHP_BINARY, '-S', '127.0.0.1:' . $port, $router],
        [0 => ['pipe', 'r'], 1 => ['file', $serverLog, 'a'], 2 => ['file', $serverLog, 'a']], $pipes, $root);
    mcpCheck(is_resource($server), 'Start local test server'); fclose($pipes[0]);
    for ($attempt = 0; $attempt < 30; $attempt++) {
        $ready = @fsockopen('127.0.0.1', $port, $errno, $error, 0.1);
        if ($ready) { fclose($ready); break; }
        usleep(100000);
    }
    mcpCheck($attempt < 30, 'Local server ready');
    $http = static function (string $path, ?array $body = null) use ($host, $port, $tokens): array {
        $curl = curl_init('http://127.0.0.1:' . $port . $path);
        curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => ['Host: ' . $host, 'Authorization: Bearer ' . $tokens['access_token'], 'Content-Type: application/json']]);
        if ($body !== null) curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body, JSON_THROW_ON_ERROR));
        $raw = curl_exec($curl);
        if ($raw === false) throw new RuntimeException(curl_error($curl));
        return ['status' => curl_getinfo($curl, CURLINFO_RESPONSE_CODE), 'data' => json_decode($raw, true, 64, JSON_THROW_ON_ERROR)];
    };
    $offset = 0; $httpText = '';
    do {
        $args = $nav['arguments'] + ['offset' => $offset];
        $rest = $http('/api/v1/records/rules/' . $rid . '?context_holon_id=' . $hid . '&offset=' . $offset);
        $rpc = $http((string)parse_url(omoMcpPublicUrl(), PHP_URL_PATH), ['jsonrpc' => '2.0', 'id' => 1,
            'method' => 'tools/call', 'params' => ['name' => 'omo_read_record', 'arguments' => (object)$args]]);
        mcpCheck($rest['status'] === 200 && $rpc['status'] === 200 && empty($rpc['data']['result']['isError'])
            && $rest['data'] === $rpc['data']['result']['structuredContent'] && $rest['data'] === $call('omo_read_record', $args), 'HTTP REST/MCP return the same complete detail page');
        $httpText .= $rest['data']['text']; $offset = $rest['data']['next_offset'];
    } while ($offset !== null);
    mcpCheck($httpText === $whole, 'HTTP transports preserve every character');

    // A scripted model verifies the real chat tool loop; no external AI request is made.
    $_ENV['AI_PROVIDER'] = 'openai'; $_ENV['AI_MODEL'] = 'test-model';
    $step = 0; $modelText = '';
    $result = commonAssistantRun($grant, [], 'Quelle est la dispense prévue au chapitre Alpha ?', 'fr', null,
        static function (array $request) use (&$step, &$modelText): array {
            mcpCheck(str_contains($request['payload']['instructions'], omoApiAgentRuleInstructions()), 'Chat receives full rule reading instructions');
            $input = $request['payload']['input']; $last = end($input);
            if ($step === 0) {
                $tool = 'omo_list_records'; $args = ['module' => 'rules', 'query' => 'Chapter Alpha'];
            } else {
                $data = json_decode($last['output'], true, 64, JSON_THROW_ON_ERROR);
                if ($step === 1) {
                    $navigation = $data['items'][0]['record_details']; $tool = $navigation['tool']; $args = $navigation['arguments'];
                } else {
                    $modelText .= $data['text']; $tool = 'omo_read_record';
                    $args = ['module' => 'rules', 'record_id' => $data['record_id'], 'context_holon_id' => $data['context_holon_id'], 'offset' => $data['next_offset']];
                    if ($data['next_offset'] === null) {
                        mcpCheck(str_contains($modelText, 'Exception finale: dispense écrite obligatoire.')
                            && str_contains($modelText, 'Intention: assurer la continuité.'), 'Final exception and intention reach the chat model');
                        return ['status' => true, 'http_code' => 200, 'response' => ['status' => 'completed', 'output' => [
                            ['type' => 'message', 'role' => 'assistant', 'content' => [['type' => 'output_text', 'text' => 'Une dispense écrite est obligatoire.']]]]]];
                    }
                }
            }
            $step++;
            return ['status' => true, 'http_code' => 200, 'response' => ['status' => 'completed', 'output' => [
                ['type' => 'function_call', 'call_id' => 'rule_' . $step, 'name' => $tool, 'arguments' => json_encode($args, JSON_THROW_ON_ERROR)]]]];
        });
    mcpCheck($step > 2 && $result['answer'] === 'Une dispense écrite est obligatoire.', 'Chat reads beyond the chapter title and first page');
    echo "PASS rule full text, intention, UTF-8 pagination, content search, visibility, HTTP REST/MCP parity and internal chat tool loop\n";
} finally {
    if (is_resource($server)) { proc_terminate($server); proc_close($server); }
    if ($router !== null) unlink($router);
    if ($serverLog !== null) unlink($serverLog);
    mcpCleanup($items); $_SESSION = $before; $_ENV = $envBefore;
}
