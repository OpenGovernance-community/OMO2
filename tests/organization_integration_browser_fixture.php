<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';
require_once dirname(__DIR__) . '/common/patreon.php';
mcpCheck(parse_url(omoMcpPublicUrl(), PHP_URL_HOST) === 'localtest.me', 'Local organization settings fixture only');
$items = mcpFixtures(false);
try {
    $items['membership']->setOrganizationAdmin(true);
    $items['patreon_subscription']->set('is_connected', 0); $items['patreon_subscription']->save();
    mcpCheck(!patreonUserCanUseAi((int)$items['user']->getId(), (int)$items['org']->getId()), 'Organization admin has no Patreon entitlement');
    mcpCheck(\dbObject\OrganizationPatreon::findByOrganizationId((int)$items['org']->getId()) === null, 'Organization has no Patreon link');
    $items['org']->setMcpEnabled(false); $items['org']->setApiEnabled(false); $items['org']->save();
    echo json_encode(['origin' => omoMcpIssuer(), 'mcpEndpoint' => omoMcpPublicUrl(), 'oid' => $items['org']->getId(), 'otherOid' => $items['other_org']->getId(), 'email' => $items['user']->get('email'), 'password' => $items['password']], JSON_THROW_ON_ERROR), "\n";
    fflush(STDOUT); session_write_close(); fgets(STDIN);
} finally { mcpCleanup($items); }
