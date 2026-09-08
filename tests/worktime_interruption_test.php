<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';
require_once dirname(__DIR__) . '/class/dbobject/worktime.class.php';

function assertWorkTimeInterruption(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$root = dirname(__DIR__);
$workTimeSource = (string)file_get_contents($root . '/class/dbobject/worktime.class.php');
$trackApi = (string)file_get_contents($root . '/timer/api/track.php');
$timerIndex = (string)file_get_contents($root . '/timer/index.php');
$timerScript = (string)file_get_contents($root . '/timer/assets/timer.js');

assertWorkTimeInterruption(
    \dbObject\WorkTime::MAX_RESUMABLE_HEARTBEAT_AGE_SECONDS === 8 * 60 * 60
        && \dbObject\WorkTime::END_REASON_INTERRUPTED === 'interrupted',
    'Interrupted work time must use a dedicated reason after eight hours without a signal.'
);
assertWorkTimeInterruption(
    str_contains($workTimeSource, 'closeStaleOpenForUser')
        && str_contains($workTimeSource, 'markInterrupted')
        && str_contains($workTimeSource, 'MAX_RESUMABLE_HEARTBEAT_AGE_SECONDS'),
    'Stale open work time must be closed without extending its confirmed duration.'
);
assertWorkTimeInterruption(
    str_contains($trackApi, 'interruptedEntry')
        && str_contains($timerIndex, 'closeStaleOpenForUser')
        && str_contains($timerScript, "entry.endReason === 'interrupted'"),
    'The Timer must expose interrupted work time to the user and identify it in the timesheet.'
);

echo "worktime_interruption_test: OK\n";
