<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';
require_once dirname(__DIR__) . '/class/dbobject/worktime.class.php';

function assertWorkTimeDailyAggregation(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$timezone = new DateTimeZone('Europe/Zurich');
$rangeStart = new DateTimeImmutable('2026-09-01 00:00:00', $timezone);
$rangeEnd = new DateTimeImmutable('2026-09-04 00:00:00', $timezone);
$daily = \dbObject\WorkTime::aggregateMeasuredIntervalsByDay([
    ['started_at' => '2026-08-31 23:30:00', 'ended_at' => '2026-09-01 01:00:00'],
    ['started_at' => '2026-09-01 23:30:00', 'ended_at' => '2026-09-02 00:30:00'],
    ['started_at' => '2026-09-03 12:00:00', 'ended_at' => '2026-09-04 01:00:00'],
    ['started_at' => 'invalid', 'ended_at' => '2026-09-02 12:00:00'],
], $rangeStart, $rangeEnd);

assertWorkTimeDailyAggregation(array_keys($daily) === ['2026-09-01', '2026-09-02', '2026-09-03'], 'The requested period must contain every day.');
assertWorkTimeDailyAggregation($daily['2026-09-01'] === 5400, 'Intervals must be clipped and split on the first day.');
assertWorkTimeDailyAggregation($daily['2026-09-02'] === 1800, 'The segment after midnight must be assigned to the next day.');
assertWorkTimeDailyAggregation($daily['2026-09-03'] === 43200, 'The final interval must be clipped at the exclusive range end.');

echo "worktime_daily_aggregation_test: OK\n";

