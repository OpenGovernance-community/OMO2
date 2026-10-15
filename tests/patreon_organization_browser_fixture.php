<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';
require_once dirname(__DIR__) . '/common/patreon.php';
mcpCheck(parse_url(omoMcpPublicUrl(), PHP_URL_HOST) === 'localtest.me', 'Local fixture only');
$items = mcpFixtures(false);
try {
    $uid = (int)$items['user']->getId(); $oid = (int)$items['org']->getId();
    $items['user']->set('siteadmin', 1); $items['user']->save();
    $items['membership']->setOrganizationAdmin(true); $items['membership']->save();
    $items['org_patreon'] = mcpFixture(\dbObject\OrganizationPatreon::class, ['IDorganization' => $oid,
        'is_connected' => 1, 'refresh_token' => 'fixture-not-a-token', 'patreon_user_id' => (string)random_int(100000000, 999999999),
        'patron_status' => 'active_patron', 'currently_entitled_amount_cents' => 4000, 'campaign_id' => patreonGetCreatorCampaignId()]);
    foreach (['site', 'api', 'mcp'] as $origin) {
        $items['usage_' . $origin] = mcpFixture(\dbObject\AiUsage::class, ['IDuser' => $uid, 'IDorganization' => $oid,
            'origin' => $origin, 'kind' => $origin === 'site' ? 'model' : 'exchange', 'feature' => $origin === 'site' ? 'assistant' : 'omo_list_records',
            'total_tokens' => $origin === 'site' ? 42 : null, 'input_chars' => 100, 'output_chars' => 200, 'success' => 1, 'created_at' => new DateTime()]);
    }
    echo json_encode(['origin' => omoMcpIssuer(), 'oid' => $oid, 'otherOid' => (int)$items['other_org']->getId(),
        'email' => $items['user']->get('email'), 'password' => $items['password']]) . "\n";
    fflush(STDOUT); session_write_close(); fgets(STDIN);
} finally { mcpCleanup($items); }
