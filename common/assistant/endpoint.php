<?php
// No share token, query-string organization or model-generated identity can select this session.
$_GET = $_POST = $_REQUEST = [];
define('OMO_API_LOCAL_LINKS', true);
require_once dirname(__DIR__, 2) . '/shared_functions.php';
require_once dirname(__DIR__) . '/ai_access.php';
require_once __DIR__ . '/agent.php';
require_once __DIR__ . '/translations.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
$reply = static function (int $status, array $body): never {
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
};
$fail = static function (string $key, int $status = 400) use ($reply): never {
    $reply($status, ['status' => false, 'code' => $key, 'message' => commonAssistantT($key)]);
};
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { header('Allow: POST'); $fail('invalid', 405); }
if (!str_starts_with(strtolower($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json')) $fail('invalid', 415);
$raw = file_get_contents('php://input', false, null, 0, 18000);
if (strlen($raw) >= 18000) $fail('invalid', 413);
$input = json_decode($raw, true, 16);
if (!is_array($input)) $fail('invalid');
$oid = $input['organizationId'] ?? null;
if (!is_int($oid) || $oid <= 0) $fail('context', 409);
$csrf = $input['csrf'] ?? null;
$pageContext = $_SESSION['common_assistant_contexts'][$oid] ?? [];
if (!is_string($csrf) || empty($pageContext['csrf']) || ($pageContext['user'] ?? 0) !== commonGetCurrentUserId()
    || !hash_equals($pageContext['csrf'], $csrf)) $fail('context', 403);
try { $grant = commonAssistantWithContext($oid, static fn () => commonAssistantGrant($oid)); }
catch (Throwable $error) { $fail('context', 403); }
commonAiSetUsageContext($grant['IDuser'], $oid, 'assistant');
if (!commonAiUserCanUse($grant['IDuser'], null, null, $oid)) $fail('unavailable', 403);
$chatId = $input['conversationId'] ?? null;
if (!is_string($chatId) || !preg_match('/^[a-f0-9]{32}$/D', $chatId)) $fail('invalid');
$identity = commonAssistantSessionIdentity($grant);
try { $chat = commonAssistantSessionChat($chatId, $identity); }
catch (DomainException $error) { $fail('context', 403); }
$action = $input['action'] ?? 'ask';
if (!in_array($action, ['ask', 'state', 'reset'], true)) $fail('invalid');
if ($action === 'state') {
    $reply(200, ['status' => true, 'organizationId' => $oid, 'conversationId' => $chatId] + commonAssistantChatSnapshot($chat));
}
if ($action === 'reset') {
    unset($_SESSION['common_assistant_chats'][$chatId]);
    $reply(200, ['status' => true, 'organizationId' => $oid, 'conversationId' => $chatId]);
}
$question = $input['question'] ?? null;
if (!is_string($question) || !mb_check_encoding($question, 'UTF-8')
    || trim($question) === '' || mb_strlen($question, 'UTF-8') > 4000) $fail('invalid');
$question = trim($question);
$cid = $input['holonId'] ?? null;
if ($cid !== null && (!is_int($cid) || $cid < 1)) $fail('invalid');
$now = time();
if (($chat['busy_until'] ?? 0) > $now) $fail('busy', 409);
$rate = array_values(array_filter($_SESSION['common_assistant_rate'] ?? [], static fn ($time) => $time > $now - 600));
if (count($rate) >= 20) $fail('rate', 429);
$rate[] = $now;
$_SESSION['common_assistant_rate'] = $rate;
$turn = bin2hex(random_bytes(12));
$_SESSION['common_assistant_chats'][$chatId] = $chat + ['updated' => $now];
$_SESSION['common_assistant_chats'][$chatId]['busy_until'] = $now + 125;
$_SESSION['common_assistant_chats'][$chatId]['turn'] = $turn;
$_SESSION['common_assistant_chats'][$chatId]['pending_question'] = $question;
$locale = commonAuthGetTranslationLocale();
ignore_user_abort(true); // Finish and save the answer even if the browser reloads during this turn.
session_write_close(); // The user can keep browsing while the model is working.
set_time_limit(120);
$result = null; $failure = null;
try {
    $result = commonAssistantWithContext($oid, static fn () => commonAssistantRun($grant, $chat['history'], $question, $locale, $cid));
} catch (Throwable $error) {
    $failure = in_array($error->getMessage(), ['limit', 'provider'], true) ? $error->getMessage() : 'error';
    error_log('OMO assistant failure: ' . get_class($error) . ' category=' . $failure);
}
// Reopen the current session instead of overwriting concurrent navigation/logout changes.
session_start();
$stored = $_SESSION['common_assistant_chats'][$chatId] ?? [];
if (($stored['turn'] ?? '') !== $turn || commonGetCurrentUserId() !== $grant['IDuser']
    || ($_SESSION['common_assistant_contexts'][$oid] ?? []) !== $pageContext
    || commonAssistantSessionIdentity($grant) !== $identity) $fail('context', 409);
try { commonAssistantWithContext($oid, static fn () => commonAssistantGrant($oid)); }
catch (Throwable $error) { $fail('context', 403); }
unset($_SESSION['common_assistant_chats'][$chatId]['busy_until']);
$_SESSION['common_assistant_chats'][$chatId]['updated'] = time();
if ($failure !== null) $fail($failure, 503);
$history = $chat['history'];
$history[] = ['role' => 'user', 'content' => $question];
$history[] = ['role' => 'assistant', 'content' => mb_substr($result['answer'], 0, 10000, 'UTF-8')];
$_SESSION['common_assistant_chats'][$chatId]['history'] = array_slice($history, -12);
$_SESSION['common_assistant_chats'][$chatId]['messages'][] = ['role' => 'user', 'text' => $question];
$_SESSION['common_assistant_chats'][$chatId]['messages'][] = ['role' => 'assistant', 'text' => $result['answer'], 'links' => $result['links']];
unset($_SESSION['common_assistant_chats'][$chatId]['pending_question']);
$reply(200, ['status' => true, 'organizationId' => $oid, 'conversationId' => $chatId] + $result);
