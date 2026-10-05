<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/openai_text.php';
require_once dirname(__DIR__) . '/common/openai_audio.php';
require_once dirname(__DIR__) . '/common/faq_ai.php';
require_once dirname(__DIR__) . '/shared/openai.php';

function aiAccessExpect(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

function aiAccessSave($object): void
{
    aiAccessExpect(!empty($object->save()['status']), 'Could not save access fixture.');
}

if (!function_exists('curl_init')) {
    $GLOBALS['OpenAI'] = 'test-key-never-sent';
    aiAccessExpect(!commonAiIsConfigured('test-model'), 'Missing cURL disables AI even with a key and model.');
    aiAccessExpect(!commonAiUserCanUse(1, 'test-model'), 'Missing cURL disables user AI before Patreon queries.');
    aiAccessExpect(empty(commonOpenAiRequestChatCompletion('test-key', ['model' => 'test'])['status']), 'Chat fails cleanly without cURL.');
    aiAccessExpect(empty(commonOpenAiRequestAudioTranscription('test-key', '', '', '', ['model' => 'test'])['status']), 'Audio fails cleanly without cURL.');
    echo "PASS AI configuration guards without cURL\n";
    return;
}

// Execute the real Telegram voice handler without dispatching a webhook or messages.
$telegramSource = (string)file_get_contents(dirname(__DIR__) . '/telegram/memo_hook.php');
$start = strpos($telegramSource, "\tfunction handleVoiceMessage(");
$end = strpos($telegramSource, "\tfunction handleTextMessage(", $start);
eval(substr($telegramSource, $start, $end - $start));
function isTelegramPrivateChat(array $message): bool { return $message['chat']['type'] === 'private'; }
function getMessageThreadId(array $message): ?int { return null; }
function sendMessage($chatId, $text, $markup = null, $threadId = null) { $GLOBALS['aiAccessTelegramMessages'][] = $text; }
function loadLocalSession($actorId) { throw new RuntimeException('Denied audio must stop before session/file processing.'); }

$pdo = dbObject\DbObject::getPdo();
$pdo->beginTransaction();
try {
    $GLOBALS['OpenAI'] = 'test-key-never-sent';
    $patreonKeys = ['patreonClientId', 'patreonClientSecret', 'patreonCreatorCampaignId', 'patreonConnectUrl', 'patreonRedirectUri', 'patreonConnectAllowedOrigins'];
    foreach ($patreonKeys as $key) $GLOBALS[$key] = '';
    $user = new dbObject\User();
    $user->set('email', 'ai-access-' . bin2hex(random_bytes(6)) . '@example.invalid');
    $user->set('siteadmin', true);
    aiAccessSave($user);
    $userId = (int)$user->getId();
    $_SESSION['currentUser'] = $userId;
    aiAccessExpect(commonAiUserCanUse($userId), 'Configured AI works without Patreon, including for administrators.');
    aiAccessExpect(!commonAiUserCanUse(0), 'Anonymous users cannot use AI.');
    aiAccessExpect(!commonAiUserCanUse($userId, ' '), 'An empty model is rejected.');
    aiAccessExpect(!commonAiUserCanUse($userId, null, ' '), 'An empty provider key is rejected.');
    foreach ($patreonKeys as $key) {
        $GLOBALS[$key] = 'partial-configuration';
        aiAccessExpect(!commonAiUserCanUse($userId), 'Partial Patreon configuration must fail closed: ' . $key);
        $GLOBALS[$key] = '';
    }
    $GLOBALS['patreonClientId'] = 'configured-client';
    $GLOBALS['patreonClientSecret'] = 'configured-secret';
    aiAccessExpect(!commonAiUserCanUse($userId), 'No subscription must deny even a site administrator.');
    $subscription = dbObject\UserPatreon::loadOrCreateByUserId($userId);
    aiAccessExpect($subscription instanceof dbObject\UserPatreon, 'Patreon storage is available.');
    foreach ([
        ['active_patron', 0, 1, 'refresh', false],
        ['active_patron', -1, 1, 'refresh', false],
        ['former_patron', 500, 1, 'refresh', false],
        ['declined_patron', 500, 1, 'refresh', false],
        ['active_patron', 500, 0, 'refresh', false],
        ['active_patron', 500, 1, '', false],
        ['active_patron', 1, 1, 'refresh', true],
    ] as [$status, $amount, $connected, $refresh, $expected]) {
        $subscription->set('patron_status', $status);
        $subscription->set('currently_entitled_amount_cents', $amount);
        $subscription->set('is_connected', $connected);
        $subscription->set('refresh_token', $refresh);
        aiAccessSave($subscription);
        aiAccessExpect(commonAiUserCanUse($userId) === $expected, 'Entitlement must require connected, active and strictly positive amount.');
    }
    $storageFlag = new ReflectionProperty(dbObject\UserPatreon::class, 'storageAvailable');
    $storageFlag->setValue(null, false);
    aiAccessExpect(!commonAiUserCanUse($userId), 'Unavailable Patreon storage cannot open access.');
    $storageFlag->setValue(null, true);
    $user->set('siteadmin', false);
    aiAccessSave($user);
    aiAccessExpect(commonAiUserCanUse($userId), 'A regular paid user has the same entitlement as an administrator.');
    $GLOBALS['OpenAI'] = ' ';
    aiAccessExpect(!commonAiUserCanUse($userId), 'A paid subscription cannot enable unconfigured AI.');
    $GLOBALS['aiAccessTelegramMessages'] = [];
    $voice = ['from' => ['id' => 123], 'chat' => ['id' => 123, 'type' => 'private'], 'voice' => ['file_id' => 'never-download', 'duration' => 30]];
    handleVoiceMessage($voice, $user, 10);
    aiAccessExpect(count($GLOBALS['aiAccessTelegramMessages']) === 1, 'Telegram explains missing configuration before processing audio.');
    $GLOBALS['OpenAI'] = 'test-key-never-sent';
    $subscription->set('currently_entitled_amount_cents', 0);
    aiAccessSave($subscription);
    $GLOBALS['aiAccessTelegramMessages'] = [];
    handleVoiceMessage($voice, $user, 10);
    aiAccessExpect(count($GLOBALS['aiAccessTelegramMessages']) === 1, 'Telegram explains unpaid access before processing audio.');
    aiAccessExpect(empty(commonOpenAiRewriteSelectedDocumentText('text', 'text')['status']), 'Rewrite stops before the provider call.');
    aiAccessExpect(empty(commonOpenAiSummarizeSelectedDocumentText('text', 'text')['status']), 'Document/PV summary stops before the provider call.');
    aiAccessExpect(empty(commonOpenAiSummarizeGovernanceChanges([['action' => 'create']], 'fr')['status']), 'Governance summary stops before the provider call.');
    aiAccessExpect(empty(commonOpenAiTranscribeUploadedAudio([])['status']), 'Audio transcription stops before file processing.');
    aiAccessExpect(say('never send') === '', 'Legacy generation also denies unpaid administrators.');
    $called = false;
    aiAccessExpect(faqAiGenerateDraft('question', 'description', function () use (&$called) { $called = true; return []; }) === '' && !$called, 'FAQ silently skips AI for an unpaid author.');
    $GLOBALS['OpenAI'] = '';
    aiAccessExpect(empty(commonOpenAiRequestChatCompletion('', ['model' => 'test'])['status']), 'Low-level chat rejects missing configuration.');
    aiAccessExpect(empty(commonOpenAiRequestAudioTranscription('', '', '', '', ['model' => 'test'])['status']), 'Low-level audio rejects missing configuration.');
} finally {
    $pdo->rollBack();
}
echo "PASS AI entitlement, configuration, document, FAQ and Telegram guards\n";
