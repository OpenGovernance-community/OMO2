<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';
require_once dirname(__DIR__) . '/common/ai_access.php';
require_once dirname(__DIR__) . '/common/mcp/ui.php';
use dbObject\{UserPatreon, OrganizationPatreon, AiUsage, McpOauthGrant};

$items = mcpFixtures();
try {
    $uid = (int)$items['user']->getId(); $oid = (int)$items['org']->getId(); $other = (int)$items['other_org']->getId();
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid];
    $_ENV['MCP_PUBLIC_URL'] = 'https://mcp.example.invalid/mcp';
    $_ENV['AI_API_KEY'] = 'fixture-never-sent'; $_ENV['AI_PROVIDER'] = 'openai';
    $GLOBALS['patreonClientId'] = 'fixture'; $GLOBALS['patreonClientSecret'] = 'fixture';
    $GLOBALS['patreonCreatorUserId'] = ''; $GLOBALS['patreonCreatorCampaignId'] = '77';
    $GLOBALS['patreonOrganizationMinimumAmount'] = '40';
    $personal = $items['patreon_subscription']; $personal->set('currently_entitled_amount_cents', 0); $personal->save();
    $identity = (string)random_int(100000000, 999999999);
    $tokens = ['access_token' => 'test-access', 'refresh_token' => 'test-refresh', 'token_expires_at' => new DateTime('+1 day')];
    $org = OrganizationPatreon::loadOrCreateByOrganizationId($oid);
    UserPatreon::withConnectionLock(static fn () => $org->assertIdentityAvailable($identity));
    $org->applyOauthTokens($tokens);
    $org->applyPatreonProfile(['patreon_user_id' => $identity, 'campaign_id' => '77', 'patron_status' => 'active_patron', 'currently_entitled_amount_cents' => 4000, 'campaign_lifetime_support_cents' => 0]);
    $orgSaved = $org->save(); mcpCheck(!empty($orgSaved['status']), 'Organization connection saves: ' . json_encode($orgSaved)); $items['org_patreon'] = $org;
    mcpCheck(patreonUserCanUseAi($uid, $oid) && commonAiUserCanUse($uid, null, null, $oid), 'Org pays for ordinary member');
    mcpCheck(!patreonUserCanUseAi($uid) && !patreonUserCanUseAi($uid, $other), 'Org does not authorize personal or other-org usage');
    mcpCheck(count(omoMcpEligibleOrganizations($uid)) === 1, 'MCP consent offers subscribed org');
    foreach ([3999 => false, 4000 => true, 4100 => true] as $amount => $expected) {
        $org->set('currently_entitled_amount_cents', $amount); $org->save();
        mcpCheck(patreonUserCanUseAi($uid, $oid) === $expected, 'Inclusive configured threshold');
    }
    $GLOBALS['patreonOrganizationMinimumAmount'] = '42'; mcpCheck(!patreonUserCanUseAi($uid, $oid), 'Environment threshold applied');
    foreach (['invalid', '0', '-40', '9999999999999999999999999999999999999999999'] as $invalid) {
        $GLOBALS['patreonOrganizationMinimumAmount'] = $invalid; mcpCheck(!patreonUserCanUseAi($uid, $oid), 'Invalid threshold fails closed');
    }
    $GLOBALS['patreonOrganizationMinimumAmount'] = '40';
    $org->set('patron_status', 'declined_patron'); $org->save(); mcpCheck(!patreonUserCanUseAi($uid, $oid), 'Declined subscription denied');
    $org->set('patron_status', 'active_patron'); $org->save();
    $items['membership']->set('active', 0); $items['membership']->save(); mcpCheck(!patreonUserCanUseAi($uid, $oid), 'Removed membership denied');
    $items['membership']->set('active', 1); $items['membership']->save();
    mcpCheck(!patreonUserCanManageOrganization($uid, $oid), 'Member cannot link subscription');
    $items['membership']->setOrganizationAdmin(true); $items['membership']->save();
    mcpCheck(patreonUserCanManageOrganization($uid, $oid), 'Persisted admin can manage own org');
    mcpCheck(!patreonUserCanManageOrganization($uid, $other), 'No cross-org configuration');
    $transaction = \dbObject\PatreonOauthTransaction::createPending($uid, hash('sha256', random_bytes(32)), 'https://example.invalid', $oid);
    $items['transaction'] = $transaction;
    mcpCheck((int)$transaction->get('IDorganization') === $oid, 'OAuth transaction freezes organization');
    $secondOrg = OrganizationPatreon::loadOrCreateByOrganizationId($other);
    $denied = false; try { UserPatreon::withConnectionLock(static fn () => $secondOrg->assertIdentityAvailable($identity)); } catch (DomainException) { $denied = true; }
    mcpCheck($denied, 'Same identity cannot fund second organization');
    UserPatreon::withConnectionLock(static fn () => $personal->assertIdentityAvailable($identity));
    $personal->applyOauthTokens($tokens); $personal->set('patreon_user_id', $identity); $personal->set('currently_entitled_amount_cents', 100); $personal->save();
    mcpCheck(patreonUserCanUseAi($uid), 'Same identity can fund one individual and one org');
    $items['user2'] = mcpFixture(\dbObject\User::class, ['email' => 'patreon-' . $identity . '@example.invalid', 'active' => 1]);
    $second = UserPatreon::loadOrCreateByUserId((int)$items['user2']->getId());
    $denied = false; try { UserPatreon::withConnectionLock(static fn () => $second->assertIdentityAvailable($identity)); } catch (DomainException) { $denied = true; }
    mcpCheck($denied, 'New second person rejected');
    // Simulate a pre-migration duplicate without going through the new attachment guard.
    $second->applyOauthTokens($tokens); $second->set('patreon_user_id', $identity); $second->set('patron_status', 'active_patron'); $second->set('currently_entitled_amount_cents', 100); $second->save(); $items['legacy'] = $second;
    UserPatreon::withConnectionLock(static fn () => $second->assertIdentityAvailable($identity));
    mcpCheck(patreonUserCanUseAi((int)$items['user2']->getId()), 'Legacy duplicate retained');
    $rotated = array_replace($tokens, ['access_token' => 'rotated', 'refresh_token' => 'rotated-refresh']);
    UserPatreon::withConnectionLock(static fn () => UserPatreon::shareIdentityTokens($identity, $rotated));
    $org->load((int)$org->getId(), true); $personal->load((int)$personal->getId(), true);
    mcpCheck($org->get('refresh_token') === 'rotated-refresh' && $personal->get('refresh_token') === 'rotated-refresh', 'Rotated identity tokens shared safely');
    mcpCheck(patreonSelectMembership(['included' => [['type' => 'member', 'attributes' => ['patron_status' => 'active_patron'], 'relationships' => ['campaign' => ['data' => ['id' => '88']]]]]]) === null, 'Unrelated creator membership rejected');
    $personal->disconnect();
    $request = mcpAuthorizationRequest($items['client']);
    $code = McpOauthGrant::issueCode($items['client'], $uid, $oid, $request);
    $oauth = McpOauthGrant::exchange($items['client'], mcpExchangeRequest($items['client'], $code));
    mcpCheck($oauth !== null && McpOauthGrant::authenticate($oauth['access_token'], omoMcpPublicUrl()) !== null, 'Org subscription authorizes OAuth token');
    $org->disconnect();
    mcpCheck(McpOauthGrant::authenticate($oauth['access_token'], omoMcpPublicUrl()) === null, 'Disconnect stops existing MCP token');
    mcpCheck(McpOauthGrant::exchange($items['client'], ['grant_type' => 'refresh_token', 'resource' => omoMcpPublicUrl(), 'refresh_token' => $oauth['refresh_token']]) === null, 'Disconnect blocks refresh');

    $pdo = \dbObject\DbObject::getPdo(); $pdo->beginTransaction();
    try {
        commonAiSetUsageContext($uid, $oid, 'assistant');
        commonAiRecordUsage('model', 'openai', 'test', 'Salut', 'Bonjour', ['usage' => ['prompt_tokens' => 12, 'completion_tokens' => 4, 'total_tokens' => 16]], 200);
        commonAiRecordUsage('model', 'anthropic', 'test', 'Salut', 'Bonjour', ['usage' => ['input_tokens' => 10, 'cache_read_input_tokens' => 5, 'cache_creation_input_tokens' => 2, 'output_tokens' => 4]], 200);
        commonAiRecordUsage('model', 'openai_compatible', 'test', 'é', 'réponse', [], 200);
        commonAiSetUsageContext($uid, $oid, 'omo_list_records', 'mcp');
        commonAiRecordUsage('exchange', '', '', 'abc', '12345', ['usage' => ['total_tokens' => 999]], 200);
        commonAiSetUsageContext($uid, $oid, 'omo_list_records', 'api');
        commonAiRecordUsage('exchange', '', '', 'abc', 'error', [], 403);
        $rows = AiUsage::dashboard('2000-01-01', '2100-01-01', $oid)['rows']; mcpCheck(count($rows) === 5, 'Separate origins, providers and kinds');
        $by = []; foreach ($rows as $row) $by[$row['provider'] ?: $row['origin']] = $row;
        mcpCheck((int)$by['openai']['total_tokens'] === 16 && (int)$by['anthropic']['total_tokens'] === 21, 'Provider token counts include Anthropic cache');
        mcpCheck($by['openai_compatible']['total_tokens'] === null && (int)$by['openai_compatible']['input_chars'] === 1, 'Unknown tokens stay null and characters count Unicode');
        mcpCheck($by['mcp']['total_tokens'] === null && (int)$by['mcp']['output_chars'] === 5 && (int)$by['api']['errors'] === 1, 'Exchange metrics never invent model tokens; failures counted');
        mcpCheck(AiUsage::dashboard('2000-01-01', '2100-01-01', $other)['rows'] === [], 'Usage summary isolated by organization');
    } finally { $pdo->rollBack(); }
    echo "PASS organization entitlement, nominal threshold, OAuth scope/revocation, identity uniqueness, legacy duplicates, token rotation and separate usage counters\n";
} finally { mcpCleanup($items); }
