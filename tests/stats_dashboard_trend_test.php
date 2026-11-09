<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$_SERVER['HTTP_HOST'] = 'localtest.me';
$_SERVER['REQUEST_METHOD'] = 'GET';
require_once dirname(__DIR__) . '/shared_functions.php';
$users = new \dbObject\ArrayUser();
$users->loadHydrated(['where' => [['field' => 'siteadmin', 'value' => 1], ['field' => 'active', 'value' => 1]], 'limit' => 1]);
if (!count($users)) { throw new RuntimeException('An active local site administrator is required.'); }
$_SESSION = ['currentUser' => (int)$users[0]->getId(), 'auth_security_version' => (int)$users[0]->get('security_version'), 'currentOrganization' => 1];
$_GET = ['range' => 30, 'trend' => ($argv[1] ?? '') === '--hidden' ? 'none' : 'stl'];
ob_start();
require dirname(__DIR__) . '/common/admin/stats.php';
$html = ob_get_clean();
function trendChartAssert(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}
trendChartAssert(!preg_match('/Fatal error|Warning:|Deprecated:/', $html), 'Dashboard contains PHP diagnostics.');
trendChartAssert(str_contains($html, 'name="trend"'), 'Trend selector missing.');
if ($_GET['trend'] === 'none') {
    trendChartAssert(!str_contains($html, '<path class="site-stats-chart__trend'), 'Hide option must remove every trend.');
    echo "stats_dashboard_trend_test: OK (hidden)\n";
    exit;
}

$start = new DateTimeImmutable('2026-01-01', new DateTimeZone('UTC'));
$rows = [];
for ($day = 0; $day < 90; $day++) {
    $rows[] = ['period_date' => $start->modify('+' . $day . ' days')->format('Y-m-d'), 'total' => 100 + $day];
}
$daily = siteStatsBuildSeries($rows, $start, $start->modify('+89 days'), 'daily');
$weekly = siteStatsBuildSeries($rows, $start, $start->modify('+89 days'), 'weekly');
trendChartAssert(array_sum(array_column($weekly, 'value')) === array_sum(array_column($daily, 'value')), 'Weekly aggregation must preserve counts.');
trendChartAssert($weekly[0]['trend'] === null && $weekly[count($weekly) - 1]['trend'] === null, 'Partial weeks must not receive a full-week trend.');
$dailyFit = siteStatsDailyTrend($daily);
foreach ($weekly as $week) {
    if ($week['trend'] === null) { continue; }
    $expected = 0.0;
    foreach ($daily as $index => $point) {
        if ($point['date']->modify('monday this week') == $week['date']) { $expected += $dailyFit[$index]; }
    }
    trendChartAssert(abs($week['trend'] - $expected) < 1e-8, 'Weekly trends must be sums of daily STL, not a second decomposition.');
}
$chart = siteStatsRenderBarChart('test', [['series' => $daily]], 'Test', 'daily');
trendChartAssert(substr_count($chart, '<path class="site-stats-chart__trend') === 1, 'Daily curve missing.');
trendChartAssert(!preg_match('/NaN|INF/', $chart), 'Chart coordinates must remain finite.');
trendChartAssert(!str_contains(siteStatsRenderChart('short', array_slice($daily, 0, 7), 'Short', 'daily'), '<path'), 'Short periods must not show a misleading trend.');
trendChartAssert(!str_contains(siteStatsRenderChart('hour', $daily, 'Hour', 'hourly'), '<path'), 'Hourly graphs must not receive a weekly daily fit.');
trendChartAssert(str_contains(siteStatsRenderChart('week', $weekly, 'Week', 'weekly'), '<path'), 'Weekly aggregate trend missing.');
$dual = siteStatsRenderDualChart('dual', $daily, $daily, 'Dual', 'daily', 'First', 'Second');
trendChartAssert(substr_count($dual, '<path class="site-stats-chart__trend') === 2 && str_contains($dual, 'site-stats-chart__trend--secondary'), 'Dual charts need two distinguishable trends.');
$negative = siteStatsRenderChart('negative', [
    ['date' => $start, 'value' => 10, 'trend' => -20.0],
    ['date' => $start->modify('+7 days'), 'value' => 10, 'trend' => 80.0],
], 'Negative fitted edge', 'weekly');
preg_match('/<path[^>]+d="([^"]+)"/', $negative, $path);
preg_match_all('/[ML]([0-9.]+),([0-9.]+)/', $path[1] ?? '', $coordinates, PREG_SET_ORDER);
trendChartAssert(count($coordinates) === 2, 'Negative fitted values must not be discarded.');
foreach ($coordinates as $coordinate) {
    trendChartAssert((float)$coordinate[2] >= 20 && (float)$coordinate[2] <= 182, 'Trend must stay inside the plot, including negative estimates.');
}
echo "stats_dashboard_trend_test: OK (selector, daily and weekly curves, partial weeks, short periods, two series)\n";
