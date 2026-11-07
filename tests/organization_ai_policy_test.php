<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';
require_once dirname(__DIR__) . '/common/ai_client.php';
require_once dirname(__DIR__) . '/common/openai_audio.php';
require_once dirname(__DIR__) . '/common/assistant/ui.php';
use dbObject\{Organization, OrganizationPatreon, McpOauthGrant};

$before = $_SESSION;
$items = mcpFixtures();
try {
    $uid = (int)$items['user']->getId(); $org = $items['org']; $oid = (int)$org->getId();
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid];
    foreach (['AI_PROVIDER' => 'openai', 'AI_API_KEY' => 'fixture-never-sent', 'AI_MODEL' => 'fixture-text',
        'TRANSCRIPTION_PROVIDER' => 'openai', 'TRANSCRIPTION_API_KEY' => 'fixture-never-sent', 'TRANSCRIPTION_MODEL' => 'fixture-audio'] as $key => $value) $_ENV[$key] = $value;
    mcpCheck(OrganizationPatreon::findByOrganizationId($oid) === null && patreonIndividualCanUseAi($uid), 'Only the individual is subscribed');
    mcpCheck(!$items['other_org']->isAiBlocked() && Organization::allowsInternalAi((int)$items['other_org']->getId()), 'Existing or new organizations allow internal AI by default');
    mcpCheck(commonAiUserCanUse($uid) && commonAiUserCanTranscribe($uid), 'Default policy permits entitled text and audio tools');
    $code = McpOauthGrant::issueCode($items['client'], $uid, $oid, mcpAuthorizationRequest($items['client']));
    $tokens = McpOauthGrant::exchange($items['client'], mcpExchangeRequest($items['client'], $code));

    $org->setAiBlocked(true); mcpCheck(!empty($org->save()['status']), 'Save organization AI block');
    mcpCheck(patreonIndividualCanUseAi($uid), 'Individual subscription stays active');
    mcpCheck(!commonAiUserCanUse($uid) && !commonAiUserCanTranscribe($uid), 'Organization policy overrides individual entitlement and session context');
    ob_start(); commonAssistantRender($oid, 'Blocked organization'); $widget = ob_get_clean();
    mcpCheck($widget === '', 'Organization chat entry point is hidden');
    mcpCheck(!translationBundleQueueRefresh('blocked_org_test', 'en', ['example' => ['text' => 'Do not send', 'context' => 'Policy test']]), 'Blocked organization cannot queue new AI translations');
    $_SESSION['currentOrganization'] = (int)$items['other_org']->getId();
    commonAiSetUsageContext($uid, $oid, 'telegram_memo', 'telegram');
    mcpCheck(!commonAiUserCanUse($uid) && !commonAiUserCanTranscribe($uid), 'Explicit organization context wins over another open organization');

    // Invalid destinations/files must never be reached: rejection precedes all HTTP/file processing.
    $response = commonAiRequestJson(['url' => 'invalid-no-network', 'headers' => [], 'payload' => []]);
    mcpCheck(($response['code'] ?? '') === 'organization_ai_blocked', 'Shared text/chat transport rejects before sending any payload');
    $response = commonOpenAiRequestAudioTranscription('fixture-never-sent', '/nonexistent-audio', 'audio/webm', 'audio.webm', ['model' => 'fixture-audio']);
    mcpCheck(($response['code'] ?? '') === 'organization_ai_blocked', 'Audio transport rejects before accessing or sending audio');
    $denied = false;
    try { commonInfomaniakTranscriptionGet('/invalid-no-network', 'fixture-never-sent', 1); } catch (RuntimeException $error) { $denied = true; }
    mcpCheck($denied, 'Asynchronous audio polling also stops after a block');
    $_SESSION['currentOrganization'] = $oid;
    commonAiSetUsageContext($uid, 0, 'personal_memo', 'telegram');
    mcpCheck(commonAiRequestOrganization() === 0 && commonAiUserCanUse($uid), 'Explicit personal requests do not inherit an unrelated organization');
    commonAiSetUsageContext($uid, $oid, 'telegram_memo', 'telegram');

    $items['user']->set('siteadmin', 1); $items['user']->save(); $_SESSION['isSiteAdminModeEnabled'] = true;
    mcpCheck(!commonAiUserCanUse($uid) && !commonAiUserCanTranscribe($uid), 'Site administration cannot override organization AI security');
    mcpCheck(McpOauthGrant::authenticate($tokens['access_token'], omoMcpPublicUrl(), 'api') !== null
        && McpOauthGrant::authenticate($tokens['access_token'], omoMcpPublicUrl(), 'mcp') !== null, 'External API and MCP policies remain independent');
    mcpCheck($org->isApiEnabled() && $org->isMcpEnabled(), 'Changing AI policy preserves external choices');
    mcpCheck(commonAiOrganizationAllows((int)$items['other_org']->getId()) && !commonAiOrganizationAllows(PHP_INT_MAX), 'Other organizations remain independent; missing organizations fail closed');

    $org->setAiBlocked(false); $org->save();
    mcpCheck(commonAiUserCanUse($uid) && commonAiUserCanTranscribe($uid), 'Re-enabling AI is effective without a new session');
    echo "PASS organization AI defaults, individual/admin veto, context isolation, text/chat/audio transport guards and independent external policies\n";
} finally {
    unset($GLOBALS['commonAiUsageContext']); mcpCleanup($items); $_SESSION = $before;
}
