<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';
mcpCheck(parse_url(omoMcpPublicUrl(), PHP_URL_HOST) === 'localtest.me', 'Parameter panel fixtures are local-only');
$items = mcpFixtures(false);
try {
    $items['user']->set('siteadmin', 1);
    mcpCheck(!empty($items['user']->save()['status']), 'Create disposable site administrator');
    foreach (['projects', 'decision', 'documents'] as $directory) {
        $apps = new \dbObject\ArrayApplication();
        $apps->load(['where' => [['field' => 'directory', 'value' => $directory]], 'limit' => 1]);
        mcpCheck(count($apps) === 1, 'Application must exist: ' . $directory);
        $items['app_' . $directory] = mcpFixture(\dbObject\OrganizationApplication::class, [
            'IDorganization' => $items['org']->getId(), 'IDapplication' => $apps[0]->getId(), 'active' => 1,
        ]);
    }
    echo json_encode(['origin' => omoMcpIssuer(), 'oid' => $items['org']->getId(), 'email' => $items['user']->get('email'), 'password' => $items['password']], JSON_THROW_ON_ERROR) . "\n";
    fflush(STDOUT);
    session_write_close();
    fgets(STDIN);
} finally {
    mcpCleanup($items);
}
