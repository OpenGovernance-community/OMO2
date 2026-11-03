<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';
mcpCheck(parse_url(omoMcpPublicUrl(), PHP_URL_HOST) === 'localtest.me', 'Local fixture only');
$items = mcpFixtures(false);
try {
    $items['membership']->setOrganizationAdmin(true);
    $items['membership']->save();
    $items['org']->set('color', '#ffd966');
    $items['org']->save();
    $items['parcours'] = mcpFixture(\dbObject\Parcours::class, ['title' => 'Video settings fixture', 'IDorganization' => $items['org']->getId(), 'ispublic' => 1]);
    $items['attachment'] = mcpFixture(\dbObject\OrganizationParcours::class, ['IDorganization' => $items['org']->getId(), 'IDparcours' => $items['parcours']->getId(), 'everybody' => 1, 'anonymous' => 1]);
    foreach (['vimeo' => 'https://vimeo.com/123456789', 'youtube' => 'https://youtu.be/abc123'] as $provider => $url) {
        $items[$provider] = mcpFixture(\dbObject\Mission::class, ['title' => 'Video ' . $provider, 'resume' => 'Video settings test', 'html' => '<p>Mission content</p>', 'video' => $url]);
        $items[$provider . '_link'] = mcpFixture(\dbObject\ParcoursMission::class, ['IDparcours' => $items['parcours']->getId(), 'IDmission' => $items[$provider]->getId()]);
    }
    echo json_encode(['origin' => omoMcpIssuer(), 'oid' => (int)$items['org']->getId(), 'otherOid' => (int)$items['other_org']->getId(),
        'id' => (int)$items['parcours']->getId(), 'vimeo' => (int)$items['vimeo']->getId(), 'youtube' => (int)$items['youtube']->getId(),
        'email' => $items['user']->get('email'), 'password' => $items['password']]) . "\n";
    fflush(STDOUT);
    session_write_close();
    fgets(STDIN);
} finally {
    mcpCleanup($items);
}
