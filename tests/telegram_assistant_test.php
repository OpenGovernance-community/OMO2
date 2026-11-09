<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('OMO_API_LOCAL_LINKS', true);
require_once __DIR__ . '/mcp_test_helpers.php';
require_once dirname(__DIR__) . '/common/assistant/telegram.php';

// Exercise the real hook routing without running its HTTP/authentication entry point.
$source = file_get_contents(dirname(__DIR__) . '/telegram/memo_hook.php');
foreach (['getMessageThreadId', 'loadTelegramUserByActorId', 'isTelegramPrivateChat', 'handleTextMessage'] as $name) {
    $start = strpos($source, "\tfunction " . $name . '(');
    $end = strpos($source, "\n\tfunction ", $start + 1);
    if ($end === false) $end = strpos($source, "\n\t\$content = file_get_contents('php://input'", $start);
    mcpCheck($start !== false && $end !== false, 'Hook handler found: ' . $name);
    eval(substr($source, $start, $end - $start));
}
function loadLocalSession($actorId) { return $GLOBALS['telegramAssistantPreferences'] ?? new stdClass(); }
function saveLocalSession(...$args) {}
function handleTelegramConnectConversation(...$args) { return false; }
function sendMessage($chatId, $text, $markup = null, $threadId = null) {
    $GLOBALS['telegramAssistantMessages'][] = ['chat' => $chatId, 'text' => $text, 'thread' => $threadId];
    return count($GLOBALS['telegramAssistantMessages']);
}
function deleteMessage(...$args) {}
function telegramApiRequest($method, $params = []) {
    mcpCheck($method === 'getMe', 'Addressing only requests bot identity');
    return ['ok' => true, 'result' => ['id' => 123456, 'is_bot' => true, 'username' => 'OMOTestBot']];
}

$before = $_SESSION; $envBefore = $_ENV; $items = mcpFixtures();
$actor = random_int(100000000, 999999999);
$statePath = null;
try {
    $user = $items['user']; $uid = (int)$user->getId(); $oid = (int)$items['org']->getId();
    $user->set('telegramID', (string)$actor); $user->save();
    $_ENV['TELEGRAM_WEBHOOK_SECRET'] = bin2hex(random_bytes(32));
    $_ENV['AUTH_PUBLIC_URL'] = 'https://telegram-assistant.example.invalid';
    $_ENV['AI_PROVIDER'] = 'openai'; $_ENV['AI_MODEL'] = 'fixture-model'; $_ENV['AI_API_KEY'] = 'never-send';
    $directory = rtrim(sys_get_temp_dir(), '/\\') . '/omo-telegram-' . substr(hash('sha256', dirname(__DIR__)), 0, 20);
    $statePath = $directory . '/' . hash_hmac('sha256', (string)$actor, $_ENV['TELEGRAM_WEBHOOK_SECRET']) . '.json';
    $_SESSION = ['currentUser' => $uid, 'isAdmin' => true, 'currentOrganization' => $oid,
        'extendedAuthoritiesByOrganization' => [$oid => $uid], 'isSiteAdminModeEnabled' => true];
    $session = $_SESSION;
    commonTelegramAssistantWithIdentity($user, static function () use ($uid, $user): void {
        mcpCheck($_SESSION === ['currentUser' => $uid, 'userRef' => $user], 'Only the linked identity is installed');
    });
    mcpCheck($_SESSION === $session, 'Browser session restored without privilege leakage');

    $message = ['from' => ['id' => $actor], 'chat' => ['id' => $actor, 'type' => 'private'], 'message_id' => 1, 'text' => 'Quels rôles existent ?'];
    $steps = 0; $requests = [];
    $request = static function (array $request) use (&$steps, &$requests, $uid, $oid): array {
        $steps++; $requests[] = $request;
        mcpCheck(empty($_SESSION['isAdmin']) && empty($_SESSION['extendedAuthoritiesByOrganization']), 'No browser admin override in tools');
        mcpCheck($GLOBALS['commonAiUsageContext'] === ['IDuser' => $uid, 'IDorganization' => $oid, 'feature' => 'assistant', 'origin' => 'telegram'], 'Usage belongs to sender and organization');
        mcpCheck(str_contains($request['payload']['instructions'], 'Never use tables') && !str_contains($request['payload']['instructions'], 'Use a Markdown pipe table'), 'Telegram format instructions replace web tables');
        return ['status' => true, 'http_code' => 200, 'response' => ['status' => 'completed', 'output' => $steps === 1
            ? [['type' => 'function_call', 'call_id' => 'read-roles', 'name' => 'omo_list_structure', 'arguments' => '{"limit":50}']]
            : [['type' => 'message', 'role' => 'assistant', 'content' => [['type' => 'output_text', 'text' => 'Réponse vérifiée']]]]]];
    };
    $GLOBALS['telegramAssistantMessages'] = [];
    commonTelegramAssistantHandle($message, $user, '', $request);
    mcpCheck($steps === 2, 'Telegram completes the shared tool loop');
    mcpCheck(str_contains(end($GLOBALS['telegramAssistantMessages'])['text'], 'Réponse vérifiée'), 'Answer delivered');
    $state = json_decode(file_get_contents($statePath), true);
    mcpCheck(count($state['chats'][$oid]['history']) === 2, 'Turn persisted privately');
    mcpCheck($_SESSION === $session, 'Full turn restores browser context');
    foreach ($GLOBALS['telegramAssistantMessages'] as $sent) mcpCheck($sent['chat'] === $actor, 'Private answers stay private');
    commonTelegramAssistantHandle($message, $user, '', $request);
    mcpCheck($steps === 2, 'Duplicate webhook does not call the provider again');

    $followup = array_replace($message, ['message_id' => 2, 'text' => 'Et leurs membres ?']);
    commonTelegramAssistantHandle($followup, $user, '', $request);
    mcpCheck(count($requests[2]['payload']['input']) === 3, 'Follow-up reuses the same bounded history');

    $group = array_replace($message, ['chat' => ['id' => -$actor, 'type' => 'supergroup'], 'message_id' => 3, 'text' => '/ask@OMOTestBot Quels rôles ?']);
    $items['destination'] = mcpFixture(\dbObject\TelegramChatDestination::class, ['telegram_chat_id' => (string)-$actor,
        'telegram_thread_id' => '__main__', 'destination_type' => 'role', 'IDorganization' => $oid,
        'IDholon' => $items['role']->getId(), 'IDuser_configured' => $uid, 'active' => 1]);
    $GLOBALS['telegramAssistantMessages'] = [];
    commonTelegramAssistantHandle($group, $user, '/ask', $request);
    foreach ($GLOBALS['telegramAssistantMessages'] as $sent) {
        if ($sent['chat'] === -$actor) mcpCheck(!str_contains($sent['text'], 'Réponse vérifiée') && !str_contains($sent['text'], 'MCP role'), 'No organization answer in the group');
    }
    mcpCheck(end($GLOBALS['telegramAssistantMessages'])['chat'] === $actor, 'Group request answered to its author privately');
    $beforeSteps = $steps;
    commonTelegramAssistantHandle(array_replace($group, ['text' => 'Discussion ordinaire', 'message_id' => 4]), $user, '', $request);
    mcpCheck($steps === $beforeSteps, 'Ordinary group text ignored');
    $beforeMessages = count($GLOBALS['telegramAssistantMessages']);
    foreach ([
        ['text' => '/ask Quels rôles ?'],
        ['text' => '/ask@OtherBot Quels rôles ?'],
        ['text' => '@OtherBot Quels rôles ?', 'entities' => [['type' => 'mention', 'offset' => 0, 'length' => 9]]],
        ['text' => 'Et la suite ?', 'reply_to_message' => ['from' => ['id' => 999999, 'is_bot' => true]]],
        ['text' => 'Et la suite ?', 'reply_to_message' => ['from' => ['id' => 123456, 'is_bot' => false]]],
        ['text' => '@OMOTestBot Quels rôles ?', 'entities' => [['type' => 'code', 'offset' => 0, 'length' => 11]]],
        ['text' => '@OMOTestBot Quels rôles ?', 'entities' => [['type' => 'mention', 'offset' => 0, 'length' => 11]], 'forward_origin' => ['type' => 'user']],
    ] as $ignored) {
        $ignoredMessage = array_replace($group, $ignored, ['message_id' => 40]);
        commonTelegramAssistantHandle($ignoredMessage, $user, '/ask', $request);
        handleTextMessage($ignoredMessage, $user);
        mcpCheck($steps === $beforeSteps && count($GLOBALS['telegramAssistantMessages']) === $beforeMessages, 'Unaddressed group text and other bots remain silent');
    }
    $mentionText = '😀 @omotestbot, Quels rôles ?';
    $mention = array_replace($group, ['text' => $mentionText, 'message_id' => 41,
        'entities' => [['type' => 'mention', 'offset' => 3, 'length' => 11]]]);
    commonTelegramAssistantHandle($mention, $user, '', $request);
    mcpCheck($steps === $beforeSteps + 1, 'Actual bot mention activates assistant with UTF-16 emoji offsets');
    mcpCheck(end($requests)['payload']['input'][count(end($requests)['payload']['input']) - 1]['content'] === '😀 , Quels rôles ?', 'Only the current addressed message reaches the model');
    $reply = array_replace($group, ['text' => 'Et la suite ?', 'message_id' => 42,
        'reply_to_message' => ['from' => ['id' => 123456, 'is_bot' => true], 'text' => 'Untrusted previous message']]);
    commonTelegramAssistantHandle($reply, $user, '', $request);
    mcpCheck($steps === $beforeSteps + 2 && !str_contains(json_encode(end($requests)['payload']['input']), 'Untrusted previous message'), 'Reply to this bot activates assistant without importing group replies');
    mcpCheck(telegramTextRoute(array_replace($group, ['text' => 'Bot, question ?', 'entities' => [['type' => 'text_mention', 'offset' => 0, 'length' => 3, 'user' => ['id' => 123456]]]]))['text'] === 'question ?', 'Direct user-ID mention is recognized');
    mcpCheck(telegramTextRoute(array_replace($group, ['text' => '/help@OtherBot'])) === null, 'Legacy commands addressed to another bot are ignored too');
    $beforeSteps = $steps;

    $items['org']->setAiBlocked(true); $items['org']->save();
    commonTelegramAssistantHandle(array_replace($message, ['message_id' => 5]), $user, '', $request);
    mcpCheck($steps === $beforeSteps && str_contains(end($GLOBALS['telegramAssistantMessages'])['text'], 'bloquées'), 'Organization block wins over personal subscription');
    $items['org']->setAiBlocked(false); $items['org']->save();
    $items['membership']->set('active', 0); $items['membership']->save();
    commonTelegramAssistantHandle(array_replace($group, ['message_id' => 6]), $user, '/ask', $request);
    mcpCheck($steps === $beforeSteps && str_contains(end($GLOBALS['telegramAssistantMessages'])['text'], 'plus accessible'), 'Group connector never grants sender membership');
    $items['membership']->set('active', 1); $items['membership']->save();

    commonTelegramAssistantHandle(array_replace($message, ['text' => '/organization ' . $items['other_org']->getId(), 'message_id' => 7]), $user, '/organization', $request);
    mcpCheck($steps === $beforeSteps && str_contains(end($GLOBALS['telegramAssistantMessages'])['text'], 'plus accessible'), 'Organization selection enforces membership');
    handleTextMessage(array_replace($message, ['text' => '/new', 'message_id' => 8]), $user);
    $state = json_decode(file_get_contents($statePath), true);
    mcpCheck(empty($state['chats'][$oid]), 'New conversation clears history');
    $items['patreon_subscription']->set('is_connected', 0); $items['patreon_subscription']->save();
    $beforeSteps = $steps;
    commonTelegramAssistantHandle(array_replace($message, ['message_id' => 9]), $user, '', $request);
    mcpCheck($steps === $beforeSteps && str_contains(end($GLOBALS['telegramAssistantMessages'])['text'], 'indisponible'), 'Missing subscription prevents provider calls');
    $items['patreon_subscription']->set('is_connected', 1); $items['patreon_subscription']->save();
    $GLOBALS['telegramAssistantPreferences'] = (object)['active' => false];
    commonTelegramAssistantHandle(array_replace($message, ['message_id' => 10]), $user, '', $request);
    mcpCheck($steps === $beforeSteps && str_contains(end($GLOBALS['telegramAssistantMessages'])['text'], 'désactivé'), 'Stop disables questions too');
    unset($GLOBALS['telegramAssistantPreferences']);

    $url = '/omo/o/' . $oid . '/c/' . $items['role']->getId();
    $rendered = commonTelegramAssistantText(['answer' => "| Rôle | État |\n| --- | --- |\n| A | Actif |\n\n[Ouvrir]($url) [Faux](https://evil.invalid)",
        'links' => [['url' => $url, 'label' => 'Rôle']]], $oid, 'https://telegram-assistant.example.invalid');
    mcpCheck(str_contains($rendered, 'Rôle : A ; État : Actif') && !str_contains($rendered, '|') && !str_contains($rendered, 'evil.invalid'), 'Tables converted and unknown links removed');
    mcpCheck(str_contains($rendered, 'https://telegram-assistant.example.invalid' . $url), 'Source URL uses canonical host');
    $GLOBALS['telegramAssistantMessages'] = [];
    $long = str_repeat('😀é', 3000);
    commonTelegramAssistantSend($actor, $long);
    $joined = '';
    foreach ($GLOBALS['telegramAssistantMessages'] as $sent) {
        mcpCheck(mb_check_encoding($sent['text'], 'UTF-8') && strlen(mb_convert_encoding($sent['text'], 'UTF-16LE', 'UTF-8')) / 2 <= 4096, 'Unicode chunks respect message limit');
        $joined .= $sent['text'];
    }
    mcpCheck($joined === $long, 'Unicode chunking preserves all text');
    $rate = array_fill(0, 20, 1000);
    try { commonAssistantConsumeRate($rate, 1001); throw new RuntimeException('Throttle missing'); }
    catch (DomainException $error) { mcpCheck($error->getMessage() === 'rate', 'Shared throttle rejects question 21'); }
    commonAssistantConsumeRate($rate, 1601);
    mcpCheck(count($rate) === 1, 'Throttle expires after ten minutes');

    $identity = $state['identity'];
    commonTelegramAssistantWithState($actor, $identity, static function () use ($actor, $identity): void {
        try { commonTelegramAssistantWithState($actor, $identity, static fn () => null); throw new RuntimeException('Lock missing'); }
        catch (DomainException $error) { mcpCheck($error->getMessage() === 'busy', 'Concurrent Telegram turn rejected'); }
    });
    commonTelegramAssistantWithState($actor, 'relinked-account', static function (array &$state): void {
        mcpCheck($state === ['identity' => 'relinked-account'], 'Relinking drops old history and organization selection');
    });
    echo "PASS Telegram tool loop, history, identity isolation, membership and AI veto, retry deduplication, group privacy, reset, rendering, Unicode chunks, rate and concurrency; no external calls\n";
} finally {
    if ($statePath !== null && is_file($statePath)) unlink($statePath);
    unset($GLOBALS['telegramAssistantMessages'], $GLOBALS['telegramAssistantPreferences']);
    mcpCleanup($items); $_SESSION = $before; $_ENV = $envBefore;
}
