<?php
declare(strict_types=1);
// Integration test: run against the local test database; all fixtures roll back.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$_SERVER['HTTP_HOST'] = 'localtest.me';
require_once __DIR__ . '/mcp_test_helpers.php';

use dbObject\{DbObject, Document};

$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $items = mcpFixtures();
    $userId = (int)$items['user']->getId();
    $document = mcpFixture(Document::class, [
        'title' => 'Timer database fixture', 'documenttype' => Document::TYPE_PV,
        'pvstage' => Document::PV_STAGE_MEETING, 'IDorganization' => $items['org']->getId(),
        'IDuser' => $userId, 'IDusercreation' => $userId,
        'IDuser_pv_editor' => $userId, 'active' => 1,
    ]);
    mcpCheck($document->getPvTimerState()['revision'] === '', 'A new PV has no stored timer revision.');
    $commands = [
        ['play', 0, 'stopwatch', 'playing'],
        ['pause', null, 'countdown', 'paused'],
        ['play', null, 'countdown', 'playing'],
        ['stop', null, 'countdown', 'stopped'],
        ['play', 90, 'countdown', 'playing'],
        ['pause', null, 'countdown', 'paused'],
        ['stop', null, 'countdown', 'stopped'],
    ];
    foreach ($commands as [$command, $seconds, $mode, $expectedStatus]) {
        $previousRevision = $document->getPvTimerState()['revision'];
        mcpCheck($document->updatePvTimer($userId, $command, $previousRevision, $seconds, $mode), 'Timer command failed: ' . $command);
        mcpCheck($document->getPvTimerState()['status'] === $expectedStatus, 'The command must persist its playback state.');
        $fresh = new Document();
        mcpCheck($fresh->load($document->getId(), true), 'Reload timer from database.');
        mcpCheck($fresh->getPvTimerState() === $document->getPvTimerState(), 'Other screens read the same persisted timer.');
        mcpCheck(!$fresh->updatePvTimer($userId, 'stop', $previousRevision), 'An outdated tab cannot overwrite a newer command.');
    }
    echo "pv_timer_database_test: OK (first start, pause, resume, stop, countdown, persisted state and stale revisions)\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}
