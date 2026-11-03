<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';
use dbObject\{Parcours, Mission, ParcoursMission, Homework, MissionHomework};
mcpCheck(parse_url(omoMcpPublicUrl(), PHP_URL_HOST) === 'localtest.me', 'Local fixture only');
$items = [];
try {
    $items['org'] = mcpFixture(McpTestOrganization::class, ['name' => 'Mission reading fixture']);
    $items['parcours'] = mcpFixture(Parcours::class, ['title' => 'Reading confirmation', 'IDorganization' => $items['org']->getId(), 'ispublic' => 1]);
    foreach (['long' => 60, 'short' => 1, 'homework' => 30] as $key => $paragraphs) {
        $items[$key] = mcpFixture(Mission::class, ['title' => 'Reading ' . $key, 'resume' => 'Read the mission content before confirming.',
            'html' => '<div data-reading-content>' . str_repeat('<p>Read this paragraph before confirming the mission.</p>', $paragraphs) . '</div>']);
        $items[$key . '_link'] = mcpFixture(ParcoursMission::class, ['IDparcours' => $items['parcours']->getId(), 'IDmission' => $items[$key]->getId()]);
    }
    $items['task'] = mcpFixture(Homework::class, ['title' => 'Required reading task', 'detail' => 'Complete this task as well.']);
    $items['task_link'] = mcpFixture(MissionHomework::class, ['IDmission' => $items['homework']->getId(), 'IDhomework' => $items['task']->getId()]);
    echo json_encode(['origin' => omoMcpIssuer(), 'pid' => (int)$items['parcours']->getId(),
        'long' => (int)$items['long']->getId(), 'short' => (int)$items['short']->getId(), 'homework' => (int)$items['homework']->getId(), 'task' => (int)$items['task']->getId()]) . "\n";
    fflush(STDOUT);
    session_write_close();
    fgets(STDIN);
} finally { mcpCleanup($items); }
