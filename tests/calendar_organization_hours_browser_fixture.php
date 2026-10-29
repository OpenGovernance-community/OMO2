<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';
mcpCheck(parse_url(omoMcpPublicUrl(), PHP_URL_HOST) === 'localtest.me', 'Local fixture only');
$items = mcpFixtures(false);
try {
    $items['membership']->setOrganizationAdmin(true);
    $items['membership']->save();
    $items['busy_day'] = mcpFixture(\dbObject\Event::class, [
        'IDuser' => $items['user']->getId(), 'IDorganization' => $items['org']->getId(),
        'IDholon' => $items['role']->getId(), 'title' => 'Private fixture event',
        'start_at' => new DateTimeImmutable('2030-01-07 07:00:00'),
        'end_at' => new DateTimeImmutable('2030-01-07 19:00:00'), 'active' => 1, 'status' => 'confirmed',
    ]);
    echo json_encode(['origin' => omoMcpIssuer(), 'oid' => (int)$items['org']->getId(),
        'uid' => (int)$items['user']->getId(), 'otherOid' => (int)$items['other_org']->getId(),
        'email' => $items['user']->get('email'), 'password' => $items['password']]) . "\n";
    fflush(STDOUT);
    session_write_close();
    fgets(STDIN);
} finally {
    mcpCleanup($items);
}
