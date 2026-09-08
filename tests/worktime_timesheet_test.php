<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';
require_once dirname(__DIR__) . '/class/dbobject/worktime.class.php';

use dbObject\WorkTime;

function assertWorkTimeTimesheet(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$root = dirname(__DIR__);
$trackApi = (string)file_get_contents($root . '/timer/api/track.php');
$timerIndex = (string)file_get_contents($root . '/timer/index.php');
$timerScript = (string)file_get_contents($root . '/timer/assets/timer.js');

assertWorkTimeTimesheet(
    str_contains($trackApi, "if (\$action === 'recent')")
        && str_contains($trackApi, "if (\$action === 'update')")
        && str_contains($trackApi, "if (\$action === 'delete')"),
    'The timer API must expose recent, update and delete actions for the timesheet.'
);
assertWorkTimeTimesheet(
    str_contains((string)file_get_contents($root . '/class/dbobject/worktime.class.php'), 'findRecentClosedForUser')
        && str_contains((string)file_get_contents($root . '/class/dbobject/worktime.class.php'), 'updateClosedForUser')
        && str_contains((string)file_get_contents($root . '/class/dbobject/worktime.class.php'), 'deleteClosedForUser'),
    'Work time history reads and writes must stay centralized in the dbObject class.'
);
assertWorkTimeTimesheet(
    str_contains($timerIndex, 'data-timer-timesheet')
        && str_contains($timerScript, 'setTimesheetOpen')
        && str_contains($timerScript, 'bindTimesheetSwipe')
        && str_contains($timerScript, "postAction('update'")
        && str_contains($timerScript, "postAction('delete'"),
    'The Timer must provide the vertical timesheet sheet and its edit actions.'
);

echo "worktime_timesheet_test: OK\n";
