<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';
require_once dirname(__DIR__) . '/common/ai_access.php';
use dbObject\{User, UserPatreon, OrganizationPatreon, Project, TelegramChatDestination};

// Exercise the actual bot handlers and dbObjects, stopping before audio download or any external call.
$source = (string)file_get_contents(dirname(__DIR__) . '/telegram/memo_hook.php');
$functions = ['getMessageThreadId', 'getTelegramConnectedUserLabel', 'isTelegramPrivateChat', 'clearTelegramConnectState', 'beginTelegramConnectFlow',
    'handleTelegramConnectConversation', 'loadLastDocumentForActor', 'buildHolonPathLabel', 'buildHolonChoiceLabel',
    'telegramUserCanCreateDocumentInHolon', 'getVisibleHolonChildren', 'telegramUserCanUseOrganization',
    'telegramRoleCanReceiveGroupMemos', 'telegramProjectCanReceiveGroupMemos', 'telegramHolonHasGroupRoleDestination',
    'telegramLoadEligibleGroupProjects', 'telegramOrganizationHasGroupDestination', 'buildTelegramGroupRolePrompt',
    'buildTelegramGroupProjectPrompt', 'handleCallbackQuery', 'handleVoiceMessage', 'handleTextMessage'];
foreach ($functions as $name) {
    $start = strpos($source, "\tfunction " . $name . '(');
    $end = strpos($source, "\n\tfunction ", $start + 1);
    if ($end === false) $end = strpos($source, "\n\t\$content = file_get_contents('php://input'", $start);
    mcpCheck($start !== false && $end !== false, 'Handler source found: ' . $name);
    eval(substr($source, $start, $end - $start));
}
class TelegramAuthorizationReached extends RuntimeException {}
function loadLocalSession($actorId) {
    if (($GLOBALS['telegramTestMode'] ?? '') === 'voice') throw new TelegramAuthorizationReached();
    return $GLOBALS['telegramTestSessions'][$actorId] ?? new stdClass();
}
function saveLocalSession($data, $actorId) { $GLOBALS['telegramTestSessions'][$actorId] = $data; }
function sendMessage($chatId, $text, $markup = null, $threadId = null) { $GLOBALS['telegramTestMessages'][] = ['text' => $text, 'buttons' => $markup]; }
function editMessageText(...$args) { return true; }
function answerCallbackQuery(...$args) {}

function telegramCheckVoice(User $user, array $message, bool $expected, string $label, int $organizationId = 0): void
{
    unset($GLOBALS['commonAiUsageContext']); $GLOBALS['telegramTestMessages'] = []; $GLOBALS['telegramTestMode'] = 'voice';
    $allowed = false;
    try { handleVoiceMessage($message, $user, 10); } catch (TelegramAuthorizationReached $error) { $allowed = true; }
    mcpCheck($allowed === $expected, $label . ': ' . json_encode($GLOBALS['telegramTestMessages']));
    if ($allowed) {
        $context = $GLOBALS['commonAiUsageContext'];
        mcpCheck((int)($context['IDorganization'] ?? 0) === $organizationId, 'AI follows the actual destination organization');
        mcpCheck(commonAiUserCanTranscribe((int)$context['IDuser']) && commonAiUserCanUse((int)$context['IDuser']), 'Audio and metadata helpers accept the same authorized sponsorship');
    }
}

$before = $_SESSION; $items = mcpFixtures();
try {
    $owner = $items['user']; $uid = (int)$owner->getId(); $org = $items['org']; $oid = (int)$org->getId(); $hid = (int)$items['role']->getId();
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid];
    $GLOBALS['patreonCreatorCampaignId'] = ''; $GLOBALS['patreonCreatorUserId'] = '';
    foreach (['AI_PROVIDER' => 'openai', 'AI_API_KEY' => 'never-send-fixture', 'AI_MODEL' => 'fixture-text',
        'TRANSCRIPTION_PROVIDER' => 'openai', 'TRANSCRIPTION_API_KEY' => 'never-send-fixture', 'TRANSCRIPTION_MODEL' => 'fixture-audio'] as $key => $value) $_ENV[$key] = $value;
    mcpEnableDocumentCreation($items);
    $items['patreon_subscription']->set('currently_entitled_amount_cents', 0); $items['patreon_subscription']->save();
    $items['outsider'] = mcpFixture(User::class, ['firstname' => 'Unsubscribed sender', 'active' => 1]);
    $outsider = $items['outsider']; $unknown = new User();
    $items['project'] = mcpFixture(Project::class, ['IDorganization' => $oid, 'IDholon' => $hid, 'title' => 'Telegram destination', 'active' => 1]);
    $chatId = -random_int(100000000, 999999999); $actor = random_int(100000000, 999999999);
    $message = ['from' => ['id' => $actor], 'chat' => ['id' => $chatId, 'type' => 'supergroup'], 'message_id' => 42];

    $GLOBALS['telegramTestMode'] = 'connect';
    handleTextMessage(array_replace($message, ['chat' => ['id' => $actor], 'text' => '/connect']), $owner);
    mcpCheck(($GLOBALS['telegramTestSessions'][$actor]->connect->step ?? '') === 'await_email', 'Private /connect works without Patreon');
    handleTextMessage($message + ['text' => '/connect'], $owner);
    $prompt = end($GLOBALS['telegramTestMessages']);
    mcpCheck(str_contains(json_encode($prompt['buttons']), 'tg_dest_org_' . $oid), 'Unpaid user can select an authorized organization');
    $callback = ['id' => 'test-callback', 'from' => ['id' => $actor], 'message' => $message];
    handleCallbackQuery($callback + ['data' => 'tg_dest_role_' . $oid . '_' . $hid], $owner);
    $items['destination'] = TelegramChatDestination::findByTelegramChat($chatId);
    mcpCheck($items['destination'] instanceof TelegramChatDestination && $items['destination']->getDocumentContext()['type'] === 'role', '/connect binds a role without Patreon');
    handleCallbackQuery($callback + ['data' => 'tg_dest_project_' . $oid . '_' . $hid . '_' . $items['project']->getId()], $owner);
    $destination = TelegramChatDestination::findByTelegramChat($chatId);
    mcpCheck($destination->getDocumentContext()['type'] === 'project', '/connect binds a project without Patreon');
    $voice = $message + ['voice' => ['file_id' => 'never-download', 'duration' => 30]];
    $private = array_replace($voice, ['chat' => ['id' => $actor, 'type' => 'private']]);
    $unbound = array_replace($voice, ['chat' => ['id' => $chatId - 1, 'type' => 'supergroup']]);

    telegramCheckVoice($owner, $voice, false, 'No individual or organization subscription denies linked messages');
    $items['patreon_subscription']->set('currently_entitled_amount_cents', 100); $items['patreon_subscription']->save();
    telegramCheckVoice($owner, $private, true, 'Personal subscription allows private transcription');
    telegramCheckVoice($owner, $unbound, true, 'Personal subscription allows unbound group transcription');
    telegramCheckVoice($owner, $voice, true, 'Personal subscription allows an unpaid organization destination', $oid);
    telegramCheckVoice($outsider, $voice, false, 'Connector personal subscription does not sponsor other identified senders');
    telegramCheckVoice($unknown, $voice, false, 'Connector personal subscription does not sponsor unidentified senders');
    $items['patreon_subscription']->set('currently_entitled_amount_cents', 0); $items['patreon_subscription']->save();
    $items['outsider_patreon'] = mcpFixture(UserPatreon::class, ['IDuser' => $outsider->getId(), 'is_connected' => 1,
        'refresh_token' => 'fixture-only', 'patron_status' => 'active_patron', 'currently_entitled_amount_cents' => 100]);
    telegramCheckVoice($outsider, $voice, true, 'Sender personal subscription works even when the connector is unpaid', $oid);
    mcpCheck((int)$GLOBALS['commonAiUsageContext']['IDuser'] === (int)$outsider->getId(), 'Personal sponsorship stays attributed to the sender');
    $items['outsider_patreon']->set('is_connected', 0); $items['outsider_patreon']->save();
    $items['org_patreon'] = mcpFixture(OrganizationPatreon::class, ['IDorganization' => $oid, 'is_connected' => 1,
        'refresh_token' => 'fixture-only', 'patron_status' => 'active_patron', 'currently_entitled_amount_cents' => 4000]);
    telegramCheckVoice($owner, $voice, true, 'Organization subscription sponsors its member', $oid);
    telegramCheckVoice($outsider, $voice, true, 'Organization subscription sponsors other identified channel participants', $oid);
    telegramCheckVoice($unknown, $voice, true, 'Organization subscription sponsors unidentified channel participants', $oid);
    telegramCheckVoice($owner, $private, false, 'Organization subscription never pays for private messages');
    telegramCheckVoice($owner, $unbound, false, 'Organization subscription never pays for unbound groups');
    telegramCheckVoice($owner, $voice + ['message_thread_id' => 99], false, 'Destination sponsorship does not leak to another topic');
    TelegramChatDestination::saveForTelegramChat($chatId, null, $oid, TelegramChatDestination::TYPE_ROLE, $hid, $uid);
    telegramCheckVoice($outsider, $voice, true, 'Role destination uses the same organization sponsorship', $oid);
    $org->setAiBlocked(true); $org->save();
    telegramCheckVoice($unknown, $voice, false, 'Organization AI block overrides organization sponsorship');
    $items['patreon_subscription']->set('currently_entitled_amount_cents', 100); $items['patreon_subscription']->save();
    telegramCheckVoice($owner, $voice, false, 'Organization AI block overrides personal sponsorship');
    $org->setAiBlocked(false); $org->save();
    $items['org_patreon']->set('is_connected', 0); $items['org_patreon']->save();
    telegramCheckVoice($outsider, $voice, false, 'Disconnected organization cannot sponsor messages');
    $items['org_patreon']->set('is_connected', 1); $items['org_patreon']->set('currently_entitled_amount_cents', 3999); $items['org_patreon']->save();
    telegramCheckVoice($outsider, $voice, false, 'Insufficient organization contribution cannot sponsor messages');
    $items['document_assignment']->set('active', 0); $items['document_assignment']->save();
    telegramCheckVoice($owner, $voice, false, 'Lost destination permission stops even a personally subscribed connector');
    $owner->set('active', 0); $owner->save();
    telegramCheckVoice($outsider, $voice, false, 'Revoked connector stops destination access');
    echo "PASS unpaid /connect for roles/projects, personal versus destination sponsorship, all channel senders, private/topic boundaries, revocation and organization AI veto; no external calls\n";
} finally { unset($GLOBALS['commonAiUsageContext']); mcpCleanup($items); $_SESSION = $before; }
