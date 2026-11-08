<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/common/stats_stl.php';

function stlAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$reference = json_decode(file_get_contents(__DIR__ . '/fixtures/stats_stl_reference.json'), true, 512, JSON_THROW_ON_ERROR);
foreach ($reference['cases'] as $case) {
    $actual = StatsStl::trend($case['values']);
    stlAssert(count($actual) === count($case['trend']), 'Reference fit length changed.');
    foreach ($actual as $index => $value) {
        stlAssert(abs($value - $case['trend'][$index]) < 1e-6, 'STL diverges from statsmodels at day ' . $index);
    }
}

$week = [10, 15, 12, 11, 8, -25, -31]; // Zero-mean weekly cycle.
$linear = [];
for ($day = 0; $day < 90; $day++) {
    $linear[] = 100 + 0.5 * $day + $week[$day % 7];
}
foreach (StatsStl::trend($linear) as $day => $value) {
    stlAssert(abs($value - (100 + 0.5 * $day)) < 1e-6, 'Weekly seasonality must not distort linear growth.');
}
$spike = $linear;
$spike[45] += 1000;
$fitted = StatsStl::trend($spike);
stlAssert(abs($fitted[45] - 122.5) < 20, 'The robust fit must limit the influence of a 1000-unit spike to under 2%.');
foreach ([0, 12.5] as $constant) {
    foreach (StatsStl::trend(array_fill(0, 28, $constant)) as $value) {
        stlAssert(is_finite($value) && abs($value - $constant) < 1e-9, 'Constant and zero series must remain stable.');
    }
}
stlAssert(StatsStl::trend(array_fill(0, 20, 1)) === [], 'Three full weeks are required.');
foreach ([null, NAN, INF, 'invalid'] as $invalid) {
    $values = $linear;
    $values[5] = $invalid;
    stlAssert(StatsStl::trend($values) === [], 'Missing or invalid values must not become zeros.');
}

$start = new DateTimeImmutable('2026-01-01', new DateTimeZone('UTC'));
$today = $start->modify('+21 days');
$series = [];
for ($day = 0; $day < 22; $day++) {
    $series[] = ['date' => $start->modify('+' . $day . ' days'), 'value' => $linear[$day]];
}
$fit = siteStatsDailyTrend($series, $today);
stlAssert(count($fit) === 21 && !isset($fit[21]), 'Incomplete current day must be excluded.');
$series[21]['value'] = 1000000;
stlAssert(siteStatsDailyTrend($series, $today) === $fit, 'Current-day activity must not affect the fit.');
stlAssert(siteStatsDailyTrend(array_slice($series, 0, 21), $start->modify('+20 days')) === [], 'Twenty full days plus today is insufficient.');
unset($series[10]);
stlAssert(siteStatsDailyTrend(array_values($series), $today) === [], 'Calendar gaps must not be silently filled.');
stlAssert(siteStatsDailyTrend([['label' => '00h', 'value' => 1]], $today) === [], 'Hourly profiles cannot be fitted as daily observations.');
echo "stats_stl_test: OK (reference, weekly cycle, outliers, zeros, missing dates, current day)\n";
