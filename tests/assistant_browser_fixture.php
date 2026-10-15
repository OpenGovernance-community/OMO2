<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';
require_once dirname(__DIR__) . '/common/ai_access.php';
mcpCheck(parse_url(omoMcpPublicUrl(), PHP_URL_HOST) === 'localtest.me', 'Assistant browser fixture is local-only');
$items = mcpFixtures();
try {
    $oid = (int)$items['org']->getId(); $uid = (int)$items['user']->getId();
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid];
    $items['user']->set('firstname', 'Camille'); $items['user']->set('lastname', 'AssistantTest'); $items['user']->save();
    $liveAdmin = (in_array('--live', $argv, true) || in_array('--authorized', $argv, true)) && !patreonUserCanUseAi($uid);
    if ($liveAdmin) {
        // A disposable local account only; ordinary-member permissions are exercised in assistant_test.php.
        $items['user']->set('siteadmin', 1); $items['user']->save();
        $_SESSION['isSiteAdminModeEnabled'] = true;
    }
    $items['role']->set('name', 'Coordination Suisse'); $items['role']->save();
    $items['assignment'] = mcpFixture(\dbObject\UserHolon::class, ['IDuser' => $uid,
        'IDholon' => $items['role']->getId(), 'active' => 1, 'is_membership' => 1]);
    $apps = new \dbObject\ArrayApplication();
    $apps->load(['where' => [['field' => 'hash', 'op' => 'IN', 'value' => ['team', 'calendar']]]]);
    foreach ($apps as $app) $items['app_' . $app->get('hash')] = mcpFixture(\dbObject\OrganizationApplication::class,
        ['IDorganization' => $oid, 'IDapplication' => $app->getId(), 'active' => 1]);
    $start = new DateTimeImmutable('tomorrow 14:00', new DateTimeZone('Europe/Zurich'));
    $items['event'] = mcpFixture(\dbObject\Event::class, ['IDuser' => $uid, 'IDorganization' => $oid,
        'IDholon' => $items['role']->getId(), 'title' => 'Reunion Suisse', 'active' => 1, 'status' => 'confirmed',
        'start_at' => $start->format('Y-m-d H:i:s'), 'end_at' => $start->modify('+1 hour')->format('Y-m-d H:i:s'), 'timezone' => 'Europe/Zurich']);
    if (\dbObject\UserPatreon::isStorageAvailable()) $items['patreon'] = mcpFixture(\dbObject\UserPatreon::class,
        ['IDuser' => $uid, 'is_connected' => 1, 'patron_status' => 'active_patron', 'currently_entitled_amount_cents' => 100,
            'patreon_user_id' => 'assistant-test-' . $uid, 'refresh_token' => 'assistant-test-not-a-real-refresh-token',
            'last_sync_at' => date('Y-m-d H:i:s')]);
    $ready = $liveAdmin ? commonAiIsConfigured() : commonAiUserCanUse($uid);
    $configured = commonAiIsConfigured();
    echo json_encode(['origin' => omoMcpIssuer(), 'oid' => $oid, 'cid' => (int)$items['role']->getId(),
        'otherOid' => (int)$items['other_org']->getId(), 'eventId' => (int)$items['event']->getId(),
        'date' => $start->format('Y-m-d'), 'email' => $items['user']->get('email'), 'password' => $items['password'],
        'aiReady' => $ready, 'configured' => $configured, 'liveAdmin' => $liveAdmin,
        'provider' => commonAiGetProvider(), 'model' => commonAiGetModel()], JSON_THROW_ON_ERROR) . "\n";
    fflush(STDOUT);
    session_write_close();
    fgets(STDIN);
} finally { mcpCleanup($items); }
