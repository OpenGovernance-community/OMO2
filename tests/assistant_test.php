<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('OMO_API_LOCAL_LINKS', true);
require_once __DIR__ . '/mcp_test_helpers.php';
require_once dirname(__DIR__) . '/common/assistant/agent.php';

$before = $_SESSION; $envBefore = $_ENV;
$items = mcpFixtures();
function assistantReject(callable $operation, string $message): void
{
    try { $operation(); } catch (DomainException | InvalidArgumentException $error) { return; }
    throw new RuntimeException($message);
}
try {
    $oid = (int)$items['org']->getId(); $uid = (int)$items['user']->getId();
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid];
    $grant = commonAssistantGrant($oid);
    $identity = commonAssistantSessionIdentity($grant);
    $_SESSION['currentOrganization'] = (int)$items['other_org']->getId();
    $boundGrant = commonAssistantWithContext($oid, static fn () => commonAssistantGrant($oid));
    mcpCheck($boundGrant === $grant && $_SESSION['currentOrganization'] === (int)$items['other_org']->getId(), 'Page context survives background tabs without rewriting their session organization');
    $_SESSION['currentOrganization'] = $oid;
    $_SESSION['extendedAuthoritiesByOrganization'][$oid] = $uid;
    mcpCheck(commonAssistantSessionIdentity($grant) !== $identity, 'Privilege changes invalidate conversation identity');
    unset($_SESSION['extendedAuthoritiesByOrganization']);
    $chatId = str_repeat('a', 32);
    $_SESSION['common_assistant_chats'][$chatId] = ['identity' => $identity, 'updated' => time() - 7200,
        'history' => [['role' => 'user', 'content' => 'Private model context']],
        'messages' => [['role' => 'assistant', 'text' => 'Previous reply', 'links' => []]]];
    $snapshot = commonAssistantChatSnapshot(commonAssistantSessionChat($chatId, $identity));
    mcpCheck($snapshot['messages'][0]['text'] === 'Previous reply' && !isset($snapshot['history']), 'Full display history survives idle time without exposing model context');
    assistantReject(fn () => commonAssistantSessionChat($chatId, 'other-account-or-organization'), 'Stored chat cannot cross identity boundaries');
    $pending = ['pending_question' => 'Question during reload', 'busy_until' => time() + 120];
    mcpCheck(commonAssistantChatSnapshot($pending)['busy'], 'Reload recognizes a turn still running');
    $pending['busy_until'] = time() - 1;
    mcpCheck(commonAssistantChatSnapshot($pending)['failed'], 'Interrupted turn returns a recoverable question');
    mcpCheck(omoApiSourceBaseUrl() === '', 'Local assistant must not need a public MCP URL');
    assistantReject(fn () => commonAssistantGrant((int)$items['other_org']->getId()), 'Cannot select another organization');
    $tools = commonAssistantTools();
    foreach ($tools as $tool) mcpCheck(omoApiOperationScope($tool['name']) === OMO_MCP_SCOPE, 'Every tool must be read-only');
    foreach (['omo_create_event', 'omo_send_object_email', 'omo_create_document', 'omo_update_project', 'omo_create_decision', 'sql', 'unknown'] as $name) {
        assistantReject(fn () => commonAssistantExecute($name, (object)[], $grant), 'Writing must be denied before dispatch');
    }
    assistantReject(fn () => commonAssistantExecute('omo_get_holon', (object)['holon_id' => (int)$items['other_root']->getId()], $grant), 'Foreign objects must stay inaccessible');
    assistantReject(fn () => commonAssistantExecute('omo_get_holon', (object)['holon_id' => (int)$items['hidden']->getId()], $grant), 'Hidden holons must stay inaccessible');
    assistantReject(fn () => commonAssistantExecute('omo_catalog', (object)['IDuser' => 1], $grant), 'Cannot inject an identity');
    $list = commonAssistantExecute('omo_list_structure', (object)['limit' => 50], $grant);
    mcpCheck(in_array((int)$items['role']->getId(), array_column($list['items'], 'id'), true), 'Visible roles must be available');
    $links = [];
    $prepared = commonAssistantPrepareResult($list, $oid, $links);
    mcpCheck(count($links) > 0, 'Source links must be supplied');
    $eventUrl = '/omo/api/search/preview.php?oid=' . $oid . '&module=calendar&id=42&cid=0';
    mcpCheck(commonAssistantPageUrl($eventUrl, $oid) === '/omo/o/' . $oid . '/#calendar-e42', 'Event source must open the actual calendar');
    $url = '/omo/o/' . $oid . '/#calendar-e42';
    mcpCheck(commonAssistantFormatAnswer('[Reunion](https://invented.invalid' . $url . ') [X](https://evil.invalid)', [$url => []])
        === '[Reunion](' . $url . ') X', 'Only verified local source links survive model output');
    mcpCheck(commonAssistantFormatAnswer('[Reunion]( ' . $url . ' )', [$url => []]) === '[Reunion](' . $url . ')', 'Whitespace around model URLs is normalized');
    foreach (['javascript:alert(1)', 'https://evil.invalid/omo/o/' . $oid, '/omo/api/calendar/delete.php?id=42', '/omo/o/' . ($oid + 1)] as $url) {
        mcpCheck(commonAssistantPageUrl($url, $oid) === null, 'Unsafe source URLs must be rejected');
    }
    // An injected write request from the model gets an error result and never reaches a write operation.
    $_ENV['AI_PROVIDER'] = 'openai'; $_ENV['AI_MODEL'] = 'test-model';
    $step = 0;
    $blocked = commonAssistantRun($grant, [], 'Send mail', 'en', null, static function ($request) use (&$step): array {
        $step++;
        if ($step === 2) mcpCheck(str_contains(json_encode($request['payload']['input']), 'No action was performed'), 'The model receives an explicit denied-tool result');
        return ['status' => true, 'http_code' => 200, 'response' => ['status' => 'completed', 'output' => $step === 1
            ? [['type' => 'function_call', 'call_id' => 'blocked', 'name' => 'omo_send_object_email', 'arguments' => '{}']]
            : [['type' => 'message', 'role' => 'assistant', 'content' => [['type' => 'output_text', 'text' => 'Read only']]]]]];
    });
    mcpCheck($blocked['answer'] === 'Read only' && $step === 2, 'Forbidden model calls are handled safely');
    // Verify each native provider wire format and complete a two-step live read with a fake model.
    foreach (['openai', 'anthropic', 'mistral', 'openai_compatible'] as $provider) {
        $_ENV['AI_PROVIDER'] = $provider; $_ENV['AI_MODEL'] = 'test-model'; $_ENV['AI_API_KEY'] = 'test-key';
        $_ENV['AI_BASE_URL'] = 'https://example.invalid/v1';
        $step = 0;
        $request = static function (array $request) use (&$step, $provider): array {
            $step++;
            $payload = $request['payload'];
            mcpCheck($payload['model'] === 'test-model' && !empty($payload['tools']), 'Configured model and tool schemas are preserved');
            if ($step === 2) {
                $encoded = json_encode($payload);
                mcpCheck(str_contains($encoded, 'MCP role') && !str_contains($encoded, 'never-expose-this-key'), 'Only projected readable data reaches the model');
            }
            if ($provider === 'openai') {
                mcpCheck($payload['store'] === false && str_ends_with($request['url'], '/responses'), 'OpenAI uses stateless Responses');
                foreach ($payload['tools'] as $tool) mcpCheck(!isset($tool['parameters']['anyOf']), 'OpenAI rejects top-level anyOf');
                $response = ['status' => 'completed', 'output' => $step === 1
                    ? [['type' => 'function_call', 'call_id' => 'call_1', 'name' => 'omo_list_structure', 'arguments' => '{"limit":50}']]
                    : [['type' => 'message', 'role' => 'assistant', 'content' => [['type' => 'output_text', 'text' => 'Resultat verifie']]]]];
            } elseif ($provider === 'anthropic') {
                mcpCheck(isset($payload['tools'][0]['input_schema']), 'Anthropic tools use input_schema');
                $response = ['stop_reason' => $step === 1 ? 'tool_use' : 'end_turn', 'content' => $step === 1
                    ? [['type' => 'tool_use', 'id' => 'call_1', 'name' => 'omo_list_structure', 'input' => (object)['limit' => 50]]]
                    : [['type' => 'text', 'text' => 'Resultat verifie']]];
            } else {
                mcpCheck(isset($payload['tools'][0]['function']['parameters']), 'Chat-completion provider schema');
                $response = ['choices' => [['finish_reason' => $step === 1 ? 'tool_calls' : 'stop', 'message' => $step === 1
                    ? ['role' => 'assistant', 'content' => null, 'tool_calls' => [['id' => 'call_1', 'type' => 'function',
                        'function' => ['name' => 'omo_list_structure', 'arguments' => '{"limit":50}']]]]
                    : ['role' => 'assistant', 'content' => 'Resultat verifie']]]];
            }
            return ['status' => true, 'http_code' => 200, 'response' => $response];
        };
        $result = commonAssistantRun($grant, [], 'Quels roles sont visibles ?', 'fr', null, $request);
        mcpCheck($step === 2 && $result['answer'] === 'Resultat verifie' && count($result['links']) > 0, 'Each provider completes a real tool lookup');
    }
    $_SESSION['currentUser'] = 0;
    assistantReject(fn () => commonAssistantGrant($oid), 'Anonymous sessions cannot use the assistant');
    echo "PASS assistant read-only allowlist, identity, organization and hidden-object boundaries, source links and four provider tool loops\n";
} finally { $_ENV = $envBefore; $_SESSION = $before; mcpCleanup($items); }
