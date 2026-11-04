<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';
mcpCheck(parse_url(omoMcpIssuer(), PHP_URL_HOST) === 'localtest.me', 'Tension browser fixture is local-only.');
$items = mcpFixtures(false);
try {
    $oid = (int)$items['org']->getId();
    $uid = (int)$items['user']->getId();
    $items['membership']->set('parameters', ['isAdmin' => true]);
    $items['membership']->save();
    $items['root']->set('IDholon_org', $items['root']->getId());
    $items['root']->save();
    $items['other_author'] = mcpFixture(\dbObject\User::class, [
        'firstname' => 'Other author', 'email' => 'other-tension-' . bin2hex(random_bytes(6)) . '@example.invalid', 'active' => 1,
    ]);
    $items['other_member'] = mcpFixture(\dbObject\UserOrganization::class, [
        'IDorganization' => $oid, 'IDuser' => $items['other_author']->getId(), 'active' => 1,
    ]);
    $items['own_tension'] = mcpFixture(\dbObject\Tension::class, [
        'IDorganization' => $oid, 'IDholon' => $items['root']->getId(), 'IDuser' => $uid,
        'title' => 'Own tension', 'description' => "Synthetic description\nSecond line <script>unsafe</script>", 'active' => 1,
    ]);
    $items['other_tension'] = mcpFixture(\dbObject\Tension::class, [
        'IDorganization' => $oid, 'IDuser' => $items['other_author']->getId(),
        'title' => 'Other tension', 'description' => 'Another author in the same organization.', 'active' => 1,
    ]);
    $items['foreign_tension'] = mcpFixture(\dbObject\Tension::class, [
        'IDorganization' => $items['other_org']->getId(), 'IDuser' => $uid,
        'title' => 'Foreign tension', 'description' => 'Another organization.', 'active' => 1,
    ]);
    echo json_encode(['origin' => 'https://localtest.me', 'oid' => $oid,
        'otherOid' => (int)$items['other_org']->getId(), 'ownId' => (int)$items['own_tension']->getId(),
        'otherId' => (int)$items['other_tension']->getId(), 'foreignId' => (int)$items['foreign_tension']->getId(), 'email' => $items['user']->get('email'),
        'password' => $items['password']]) . "\n";
    fflush(STDOUT);
    if (session_status() === PHP_SESSION_ACTIVE) { session_write_close(); }
    fgets(STDIN);
} finally {
    $tensions = new \dbObject\ArrayTension();
    $tensions->loadForOrganization((int)$items['org']->getId(), true);
    foreach ($tensions as $tension) { $tension->delete(); }
    mcpCleanup($items);
}
