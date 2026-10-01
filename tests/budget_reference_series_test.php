<?php
declare(strict_types=1);

if (!function_exists('omoApiEscape')) {
    function omoApiEscape($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('omoLoadTranslationBundle')) {
    function omoLoadTranslationBundle(string $domain, array $sourceLang): array
    {
        return $sourceLang;
    }
}

if (!function_exists('t')) {
    function t(string $key, array $replace, array $lang, array $sourceLang): string
    {
        $entry = $lang[$key] ?? $sourceLang[$key] ?? array('text' => $key);
        $text = is_array($entry) ? (string)($entry['text'] ?? $key) : (string)$entry;

        foreach ($replace as $name => $value) {
            $text = str_replace('{' . $name . '}', (string)$value, $text);
        }

        return $text;
    }
}

require_once dirname(__DIR__) . '/omo/api/budget/shared.php';

function assertBudgetReferenceSeries(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$roundedDurationScale = omoBudgetResolveChartScale(20000);
assertBudgetReferenceSeries((int)$roundedDurationScale['step'] === 5400, 'The duration scale must round its step to one hour and thirty minutes.');
assertBudgetReferenceSeries((int)$roundedDurationScale['max'] === 21600, 'The rounded duration scale must retain the complete data range.');
assertBudgetReferenceSeries((int)$roundedDurationScale['intervals'] === 4, 'Both chart axes must use four intervals.');

$timezone = new DateTimeZone('Europe/Zurich');
$monthly = omoBudgetBuildTimeBudgetReferenceSeries(
    [['hours' => 31, 'recurrence' => 'month']],
    ['2026-08-30', '2026-08-31', '2026-09-01', '2026-09-02'],
    $timezone
);
$septemberBoundary = (new DateTimeImmutable('2026-09-01 00:00:00', $timezone))->getTimestamp();
$monthlyResetValues = array_values(array_map(static function (array $point): int {
    return (int)$point['value'];
}, array_filter($monthly, static function (array $point) use ($septemberBoundary): bool {
    return (int)$point['timestamp'] === $septemberBoundary;
})));

assertBudgetReferenceSeries(count($monthlyResetValues) === 2, 'A monthly budget must draw both sides of its reset boundary.');
assertBudgetReferenceSeries($monthlyResetValues[0] > 30 * 3600, 'The monthly curve must reach its budget immediately before reset.');
assertBudgetReferenceSeries($monthlyResetValues[1] === 0, 'The monthly curve must restart from zero on the first day of the month.');

$annual = omoBudgetBuildTimeBudgetReferenceSeries(
    [['hours' => 365, 'recurrence' => 'year']],
    ['2025-12-31', '2026-01-01', '2026-01-02'],
    $timezone
);
$yearBoundary = (new DateTimeImmutable('2026-01-01 00:00:00', $timezone))->getTimestamp();
$annualResetValues = array_values(array_map(static function (array $point): int {
    return (int)$point['value'];
}, array_filter($annual, static function (array $point) use ($yearBoundary): bool {
    return (int)$point['timestamp'] === $yearBoundary;
})));

assertBudgetReferenceSeries(count($annualResetValues) === 2, 'An annual budget must draw both sides of its reset boundary.');
assertBudgetReferenceSeries($annualResetValues[0] > 364 * 3600, 'The annual curve must reach its budget immediately before reset.');
assertBudgetReferenceSeries($annualResetValues[1] === 0, 'The annual curve must restart from zero on January first.');

$combinedMonthly = omoBudgetBuildTimeBudgetReferenceSeries(
    [
        ['hours' => 4, 'recurrence' => 'month'],
        ['hours' => 2, 'recurrence' => 'month'],
    ],
    array_map(static function (int $day): string {
        return (new DateTimeImmutable('2026-08-29', new DateTimeZone('Europe/Zurich')))->modify('+' . $day . ' days')->format('Y-m-d');
    }, range(0, 7)),
    $timezone
);
$combinedSeptemberResetValues = array_values(array_map(static function (array $point): int {
    return (int)$point['value'];
}, array_filter($combinedMonthly, static function (array $point) use ($septemberBoundary): bool {
    return (int)$point['timestamp'] === $septemberBoundary;
})));

assertBudgetReferenceSeries(count($combinedSeptemberResetValues) === 2, 'People sharing one recurrence must produce one combined reset.');
assertBudgetReferenceSeries($combinedSeptemberResetValues[0] === 6 * 3600, 'Monthly budgets of four and two hours must produce a six-hour limit.');
assertBudgetReferenceSeries($combinedSeptemberResetValues[1] === 0, 'The combined six-hour limit must restart from zero on the first day of the month.');

$monthlyCumulative = omoBudgetBuildCumulativeTimeSeries(
    [
        '2026-08-30' => 3600,
        '2026-08-31' => 3600,
        '2026-09-01' => 1800,
        '2026-09-02' => 900,
    ],
    $timezone,
    'month',
    7200
);
$monthlyCumulativeValues = array_map(static function (array $point): int {
    return (int)$point['value'];
}, $monthlyCumulative);
assertBudgetReferenceSeries($monthlyCumulativeValues[0] === 7200, 'The cumulative curve must include time measured before the visible range.');
assertBudgetReferenceSeries(in_array(14400, $monthlyCumulativeValues, true), 'The cumulative curve must continue until the holon budget period ends.');
$cumulativeSeptemberResetValues = array_values(array_map(static function (array $point): int {
    return (int)$point['value'];
}, array_filter($monthlyCumulative, static function (array $point) use ($septemberBoundary): bool {
    return (int)$point['timestamp'] === $septemberBoundary;
})));
assertBudgetReferenceSeries($cumulativeSeptemberResetValues === [14400, 0], 'The holon monthly budget must reset the measured cumulative curve on the first day of the month.');
assertBudgetReferenceSeries((int)$monthlyCumulative[count($monthlyCumulative) - 1]['value'] === 2700, 'The cumulative curve must restart with the new monthly period values.');

$uninterruptedCumulative = omoBudgetBuildCumulativeTimeSeries(
    ['2026-08-31' => 3600, '2026-09-01' => 1800],
    $timezone
);
assertBudgetReferenceSeries((int)$uninterruptedCumulative[count($uninterruptedCumulative) - 1]['value'] === 5400, 'The cumulative curve must remain uninterrupted without a direct holon budget.');

$categorizedChart = omoBudgetRenderMeasuredTimeChart(
    ['2026-09-01' => 7200],
    $timezone,
    [
        'project' => ['2026-09-01' => 3600],
        'role' => ['2026-09-01' => 1800],
        'circle' => ['2026-09-01' => 1800],
    ]
);
assertBudgetReferenceSeries(substr_count($categorizedChart, 'omo-budget__daily-bar--') === 3, 'The chart must render one segment for every daily time category.');
assertBudgetReferenceSeries(str_contains($categorizedChart, 'omo-budget__legend-item--project'), 'The chart must render the project time legend.');

echo "budget_reference_series_test: OK\n";
