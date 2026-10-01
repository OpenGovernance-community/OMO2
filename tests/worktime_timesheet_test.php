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
$workTimeApi = (string)file_get_contents($root . '/timer/api/worktime.php');
$timerIndex = (string)file_get_contents($root . '/timer/index.php');
$timerScript = (string)file_get_contents($root . '/timer/assets/timer.js');
$parseManualDateTime = new ReflectionMethod(WorkTime::class, 'parseManualDateTime');
$parseManualDateTime->setAccessible(true);

assertWorkTimeTimesheet(
    str_contains($workTimeApi, "if (\$action === 'recent')")
        && str_contains($workTimeApi, "if (\$action === 'create')")
        && str_contains($workTimeApi, "if (\$action === 'update')")
        && str_contains($workTimeApi, "if (\$action === 'delete')"),
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
        && str_contains($timerScript, 'bindTimesheetRefresh')
        && str_contains($timerScript, 'recentRequestId')
        && str_contains($timerScript, 'createManualEntry')
        && str_contains($timerScript, "postAction('update'")
        && str_contains($timerScript, "postAction('delete'"),
    'The Timer must provide the vertical timesheet sheet, its refresh gestures and its entry actions.'
);
assertWorkTimeTimesheet(
    $parseManualDateTime->invoke(null, '2026-09-08T14:30') instanceof DateTimeImmutable
        && $parseManualDateTime->invoke(null, '2026-09-08T14:30:00') instanceof DateTimeImmutable,
    'Manual time edits must accept browser datetime-local values with or without seconds.'
);
assertWorkTimeTimesheet(
    str_contains($timerIndex, "'apiUrl' => '/timer/api/worktime.php'")
        && str_contains($workTimeApi, "if (\$action === 'state')"),
    'The Timer must expose its work time API through a neutral endpoint name.'
);

echo "worktime_timesheet_test: OK\n";
