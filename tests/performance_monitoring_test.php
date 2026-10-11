<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
putenv('DB_QUERY_LOG_ENABLED=false');
require_once dirname(__DIR__) . '/shared_functions.php';
use dbObject\{DbObject, PerformanceRequest, PerformanceHourly, ArrayDbObject};

function perfExpect(bool $ok, string $message): void { if (!$ok) { throw new RuntimeException($message); } }
class PerfRequests extends ArrayDbObject { public static function objectName() { return PerformanceRequest::class; } }
class PerfHours extends ArrayDbObject { public static function objectName() { return PerformanceHourly::class; } }
$organization = random_int(1900000000, 2000000000);
$nonce = bin2hex(random_bytes(8));
$now = gmdate('Y-m-d H:10:00');
$dayStart = gmdate('Y-m-d 00:00:00');
$dayEnd = gmdate('Y-m-d 00:00:00', time() + 86400);
$record = static function (array $fields = []) use ($organization, $nonce, $now): void {
    perfExpect(PerformanceRequest::record($fields + ['request_id' => bin2hex(random_bytes(16)), 'started_at' => $now,
        'route' => '/test-' . $nonce . '.php', 'method' => 'GET', 'kind' => 'web', 'IDorganization' => $organization,
        'IDuser' => 10, 'duration_ms' => 100, 'cpu_ms' => 10, 'sql_ms' => 20, 'query_count' => 3]), 'Sample stored.');
};
$dashboard = static fn($cron = 'all', $kind = 'web') => PerformanceHourly::dashboard($dayStart, $dayEnd, $organization, $kind, $cron);
try {
    perfExpect(PerformanceRequest::available() && PerformanceHourly::available(), 'Apply the monitoring migration first.');
    $before = DbObject::getPerformanceCounters();
    PerformanceRequest::available(); // table existence may be cached; use an actual dbObject read below.
    $list = new PerfRequests(); $list->loadHydrated(['where' => [['field' => 'IDorganization', 'value' => $organization]]]);
    $after = DbObject::getPerformanceCounters();
    perfExpect($after['queries'] > $before['queries'], 'SQL counters work while detailed SQL logging is disabled.');
    $record();
    $record(['duration_ms' => 300, 'cpu_ms' => 20, 'cron_state' => 2, 'cron_calls' => 1, 'cron_ms' => 150, 'status_code' => 500]);
    $live = $dashboard();
    perfExpect((int)$live['totals']['requests'] === 2 && (int)$live['totals']['active_users'] === 1, 'Live samples and distinct users.');
    perfExpect((float)$live['totals']['duration_ms'] === 400.0 && (int)$live['totals']['errors'] === 1, 'Duration and errors.');
    perfExpect(PerformanceHourly::percentile($live['totals']) === 500.0, 'p95 histogram upper bound.');
    perfExpect((int)$dashboard('none')['totals']['requests'] === 1 && (float)$dashboard('ran')['totals']['cron_ms'] === 150.0, 'Same page with and without cron.');
    PerformanceRequest::aggregate(1, 10);
    $aggregated = $dashboard();
    perfExpect($aggregated['totals'] === $live['totals'], 'Aggregation preserves every total.');
    PerformanceRequest::aggregate();
    perfExpect($dashboard()['totals'] === $live['totals'], 'Repeated aggregation is idempotent.');
    $record(['duration_ms' => 40, 'cpu_ms' => null, 'IDuser' => 11]);
    perfExpect((int)$dashboard()['totals']['requests'] === 3 && (int)$dashboard()['totals']['active_users'] === 2, 'Live + aggregated samples do not overlap.');
    PerformanceRequest::aggregate();
    perfExpect((int)$dashboard()['totals']['cpu_samples'] === 2 && (float)$dashboard()['totals']['cpu_ms'] === 30.0, 'Unavailable CPU is not confused with zero CPU.');
    $record(['kind' => 'cron', 'method' => 'RUN', 'route' => 'test-cron-' . $nonce, 'duration_ms' => 80]);
    perfExpect((int)$dashboard('all', 'cron')['totals']['requests'] === 1 && (int)$dashboard()['totals']['requests'] === 3, 'Cron is separate from inclusive page totals.');
    $record(['started_at' => gmdate('Y-m-d H:i:s', time() - 31 * 86400)]);
    PerformanceRequest::aggregate();
    $allRequests = new PerfRequests(); $allRequests->loadHydrated(['where' => [['field' => 'IDorganization', 'value' => $organization]]]);
    perfExpect(count($allRequests) === 4, 'Old raw samples are pruned only after aggregation.');
    $history = PerformanceHourly::dashboard(gmdate('Y-m-d 00:00:00', time() - 32 * 86400), $dayEnd, $organization);
    perfExpect((int)$history['totals']['requests'] === 4, 'Hourly history survives raw retention.');
    $record(['started_at' => gmdate('Y-m-d H:i:s', strtotime('-14 months'))]);
    PerformanceRequest::aggregate();
    $oldHistory = PerformanceHourly::dashboard(gmdate('Y-m-d 00:00:00', strtotime('-15 months')), $dayEnd, $organization);
    perfExpect((int)$oldHistory['totals']['requests'] === 4, 'Hourly history beyond thirteen months is pruned.');
    $other = PerformanceHourly::dashboard($dayStart, $dayEnd, $organization - 1);
    perfExpect((int)$other['totals']['requests'] === 0, 'Organization isolation.');

    $GLOBALS['commonPerformanceCron'] = ['cron_state' => 0, 'cron_calls' => 0, 'cron_ms' => 0];
    $result = commonPerformanceCron('cron', 'test-skipped-' . $nonce, static fn() => ['skipped' => 'recently_completed']);
    perfExpect($result['skipped'] === 'recently_completed' && $GLOBALS['commonPerformanceCron']['cron_state'] === 1, 'Skipped cron is attributed to the page.');
    commonPerformanceCron('cron', 'test-ran-' . $nonce, static function () use ($nonce) {
        try { commonPerformanceCron('task', 'test-failed-' . $nonce, static function () { throw new RuntimeException('test'); }); }
        catch (RuntimeException $expected) {}
        return ['performanceFailedTasks' => ['test']];
    });
    perfExpect($GLOBALS['commonPerformanceCron']['cron_state'] === 2 && $GLOBALS['commonPerformanceCron']['cron_calls'] === 2, 'Executed cron takes precedence over skipped cron.');
    $calls = new PerfRequests(); $calls->loadHydrated(['where' => [['field' => 'route', 'op' => 'LIKE', 'value' => '%-' . $nonce]]]);
    $failures = 0;
    foreach ($calls as $call) { if ((int)$call->get('status_code') === 500) { $failures++; } }
    perfExpect($failures === 2, 'Failed task and enclosing cron are recorded as errors.');
    echo "performance_monitoring_test: OK (live/aggregated equivalence, idempotence, retention, distinct users, CPU, SQL, cron attribution and errors)\n";
} finally {
    foreach ([new PerfRequests(), new PerfHours()] as $list) {
        $list->loadHydrated(['where' => [['field' => 'IDorganization', 'value' => $organization]]]);
        foreach ($list as $row) { $row->delete(); }
    }
    $calls = new PerfRequests(); $calls->loadHydrated(['where' => [['field' => 'route', 'op' => 'LIKE', 'value' => '%-' . $nonce]]]);
    foreach ($calls as $row) { $row->delete(); }
}
