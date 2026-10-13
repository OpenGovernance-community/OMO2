<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';
use dbObject\{ArrayDbObject, PerformanceRequest, PerformanceHourly, User};

class CalDavMonitorRequests extends ArrayDbObject { public static function objectName() { return PerformanceRequest::class; } }
class CalDavMonitorHours extends ArrayDbObject { public static function objectName() { return PerformanceHourly::class; } }
$organization = random_int(1900000000, 1999999990);
$nonce = bin2hex(random_bytes(6));
$start = gmdate('Y-m-d 00:00:00');
$end = gmdate('Y-m-d 00:00:00', time() + 86400);
$items = [];
$record = static function (array $fields = []) use ($organization, $start): void {
    mcpCheck(PerformanceRequest::record($fields + ['request_id' => bin2hex(random_bytes(16)), 'started_at' => $start,
        'route' => '/omo/api/caldav/index.php', 'method' => 'PROPFIND', 'kind' => 'web', 'IDorganization' => $organization,
        'duration_ms' => 300, 'cpu_ms' => 20, 'sql_ms' => 40, 'query_count' => 7]), 'Store monitoring fixture.');
};
$dashboard = static fn($sort = 'total', $hourly = false) => PerformanceHourly::calDavDashboard($start, $end, $organization, $sort, $hourly);
try {
    $items['first'] = mcpFixture(User::class, ['firstname' => '<CalDAV & test>', 'email' => 'caldav-monitor-' . $nonce . '@example.invalid', 'active' => 1]);
    $items['second'] = mcpFixture(User::class, ['firstname' => 'CalDAV second', 'email' => 'caldav-monitor-other-' . $nonce . '@example.invalid', 'active' => 1]);
    $record(['IDuser' => $items['first']->getId()]);
    $record(['IDuser' => $items['first']->getId(), 'started_at' => substr($start, 0, 11) . '01:00:00', 'status_code' => 429]);
    $record(['IDuser' => $items['second']->getId(), 'method' => 'PROPPATC', 'duration_ms' => 1000, 'cpu_ms' => 100, 'status_code' => 500]);
    $record(['IDuser' => 0, 'method' => 'OPTIONS', 'duration_ms' => 50, 'cpu_ms' => null]);
    $record(['IDuser' => $items['first']->getId(), 'route' => '/common/calendar/feed.php', 'duration_ms' => 9999]);
    $record(['IDuser' => $items['first']->getId(), 'route' => '/omo/api/carddav/index.php', 'duration_ms' => 9999]);
    $record(['IDuser' => $items['first']->getId(), 'kind' => 'cron', 'duration_ms' => 9999]);
    $record(['IDuser' => $items['first']->getId(), 'IDorganization' => $organization + 1]);
    $record(['IDuser' => $items['first']->getId(), 'started_at' => $end]);
    $live = $dashboard();
    mcpCheck((int)$live['totals']['requests'] === 4 && (int)$live['totals']['active_users'] === 2, 'CalDAV, date, kind and organization isolation.');
    mcpCheck((int)$live['totals']['propfind'] === 2 && (int)$live['totals']['proppatch'] === 1, 'Legacy truncated PROPPATCH counted.');
    mcpCheck((float)$live['totals']['duration_ms'] === 1650.0 && (float)$live['totals']['cpu_ms'] === 140.0
        && (int)$live['totals']['cpu_samples'] === 3 && (int)$live['totals']['client_errors'] === 1 && (int)$live['totals']['errors'] === 1,
        'CPU availability, durations and HTTP errors preserved.');
    mcpCheck((int)$live['users'][0]['user_id'] === (int)$items['second']->getId(), 'Default ranking uses total duration.');
    mcpCheck((int)$dashboard('volume')['users'][0]['user_id'] === (int)$items['first']->getId(), 'Volume ranking uses request count.');
    mcpCheck((int)$dashboard('cpu')['users'][0]['user_id'] === (int)$items['second']->getId(), 'CPU ranking uses CPU sum.');
    $byUser = array_column($live['users'], null, 'user_id');
    mcpCheck($byUser[$items['first']->getId()]['email'] === $items['first']->get('email'), 'Current contact email is available.');
    mcpCheck(isset($byUser[0]) && $byUser[0]['email'] === null, 'Anonymous traffic remains visible without a contact.');
    mcpCheck(count($live['timeline']) === 1 && count($dashboard('total', true)['timeline']) === 2, 'Daily and chronological hourly grouping.');
    PerformanceRequest::aggregate();
    mcpCheck($dashboard()['totals'] === $live['totals'] && $dashboard()['users'] === $live['users'], 'Aggregation does not double count monitoring or contact rows.');
    $record(['IDuser' => $items['first']->getId(), 'duration_ms' => 100]);
    mcpCheck((int)$dashboard()['totals']['requests'] === 5, 'Pending samples combine with existing aggregates.');

    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/stats_dashboard_access_test.php') . ' --render admin 1 ' . $organization;
    exec($command, $output, $code);
    mcpCheck($code === 0, 'Render dashboard with isolated fixtures.');
    $render = json_decode(implode("\n", $output), true, 512, JSON_THROW_ON_ERROR);
    mcpCheck($render['status'] === 200 && str_contains($render['html'], 'id="caldav-requests-title"')
        && str_contains($render['html'], 'id="caldav-cpu-title"'), 'Dedicated CalDAV charts rendered.');
    mcpCheck(str_contains($render['html'], '&lt;CalDAV &amp; test&gt;') && !str_contains($render['html'], '<CalDAV & test>')
        && str_contains($render['html'], 'mailto:' . rawurlencode($items['first']->get('email'))), 'Names escaped and email contact link rendered.');
    mcpCheck(!preg_match('/Fatal error|Warning:|Deprecated:/', $render['html']), 'No PHP diagnostics in the page.');
    if (($argv[1] ?? '') === '--preview') file_put_contents('/tmp/caldav-monitoring-preview.html', $render['html']);
    echo "caldav_monitoring_test: OK (live/aggregated totals, methods, contacts, sorting, scope, anonymous traffic, charts and escaping)\n";
} finally {
    foreach ([new CalDavMonitorRequests(), new CalDavMonitorHours()] as $list) {
        $list->loadHydrated(['where' => [['field' => 'IDorganization', 'op' => 'IN', 'value' => [$organization, $organization + 1]]]]);
        foreach ($list as $row) $row->delete();
    }
    mcpCleanup($items);
}
