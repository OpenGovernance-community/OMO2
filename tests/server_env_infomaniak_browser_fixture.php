<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';
mcpCheck(parse_url(omoMcpPublicUrl(), PHP_URL_HOST) === 'localtest.me', 'Server settings browser fixtures are local-only');
$items = mcpFixtures(false);
try {
    $items['user']->set('siteadmin', 1);
    mcpCheck(!empty($items['user']->save()['status']), 'Create disposable site administrator');
    echo json_encode(['origin' => omoMcpIssuer(), 'oid' => $items['org']->getId(), 'email' => $items['user']->get('email'), 'password' => $items['password']], JSON_THROW_ON_ERROR) . "\n";
    fflush(STDOUT);
    session_write_close();
    fgets(STDIN);
} finally { mcpCleanup($items); }
