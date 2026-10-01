<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';
require_once dirname(__DIR__) . '/class/dbobject/worktime.class.php';

function assertWorkTimeShortSwitch(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$source = (string)file_get_contents(dirname(__DIR__) . '/class/dbobject/worktime.class.php');

assertWorkTimeShortSwitch(
    \dbObject\WorkTime::SHORT_SWITCH_MAXIMUM_SECONDS === 10
        && \dbObject\WorkTime::SHORT_SWITCH_CONTINUITY_MAXIMUM_SECONDS === 5 * 60,
    'Short switched work time must require a ten-second duration and a five-minute continuity window.'
);
assertWorkTimeShortSwitch(
    str_contains($source, 'mergeShortSwitchIntoPrevious($current, $now)')
        && str_contains($source, 'loadPreviousClosedRow')
        && str_contains($source, "get('end_reason')) !== self::END_REASON_SWITCH")
        && str_contains($source, 'SHORT_SWITCH_CONTINUITY_MAXIMUM_SECONDS'),
    'A short switched entry must only merge into a nearby entry that ended by switch.'
);
assertWorkTimeShortSwitch(
    str_contains($source, "!self::mergeShortSwitchIntoPrevious(\$current, \$now) && !\$current->delete()"),
    'A short switched entry without a continuous previous switch must be discarded without changing the earlier entry.'
);
echo "worktime_short_switch_test: OK\n";
