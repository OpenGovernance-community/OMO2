<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$_SERVER['HTTP_HOST'] = 'localtest.me';
$_SERVER['REQUEST_METHOD'] = 'GET';
require_once dirname(__DIR__) . '/shared_functions.php';
if (!envIsLocalRuntimeHost()) throw new RuntimeException('Local runtime required');
function dailyTrafficExpect(bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); }
$users = new \dbObject\ArrayUser();
$users->loadHydrated(['where' => [['field' => 'siteadmin', 'value' => 1], ['field' => 'active', 'value' => 1]], 'limit' => 1]);
dailyTrafficExpect(count($users) > 0, 'An active local site administrator is required');
$_SESSION = ['currentUser' => (int)$users[0]->getId(), 'auth_security_version' => (int)$users[0]->get('security_version'), 'currentOrganization' => 1];
$days = ($argv[1] ?? '') === '--year' ? 365 : 7;
$_GET = ['range' => $days, 'organization' => 2147483647, 'trend' => 'none'];
$end = new DateTimeImmutable('today', new DateTimeZone('UTC'));
$start = $end->modify('-' . ($days - 1) . ' days');
$until = $end->modify('+1 day')->format('Y-m-d H:i:s');
$pdo = \dbObject\VideoTraffic::getPdo();
$pdo->beginTransaction();
try {
    $expected = [];
    foreach (\dbObject\VideoTraffic::dashboard($start->format('Y-m-d H:i:s'), $until, 'organization', 0)['daily'] as $row) {
        $expected[$row['period_date']] = [(int)$row['received_bytes'], (int)$row['sent_bytes']];
    }
    $organization = new \dbObject\Organization();
    $organization->set('name', 'Daily traffic fixture');
    dailyTrafficExpect(!empty($organization->save()['status']), 'Organization fixture failed');
    foreach ([[-1, null, 250000, 750000], [-1, $organization->getId(), 500000, 1500000], [-3, null, 125000, 250000], [1, null, 999000000, 999000000]] as [$offset, $organizationId, $received, $sent]) {
        $date = $end->modify(($offset > 0 ? '+' : '') . $offset . ' days');
        $sample = new \dbObject\VideoTraffic();
        foreach (['endpoint_key' => bin2hex(random_bytes(32)), 'room_code' => bin2hex(random_bytes(16)), 'IDuser' => $users[0]->getId(),
            'IDorganization' => $organizationId, 'sampled_at' => $date->format('Y-m-d H:i:s'), 'received_total' => $received, 'sent_total' => $sent,
            'received_bytes' => $received, 'sent_bytes' => $sent] as $field => $value) $sample->set($field, $value);
        dailyTrafficExpect(!empty($sample->save()['status']), 'Traffic fixture failed');
        if ($offset < 1) {
            $key = $date->format('Y-m-d');
            $expected[$key] = [($expected[$key][0] ?? 0) + $received, ($expected[$key][1] ?? 0) + $sent];
        }
    }
    ob_start();
    require dirname(__DIR__) . '/common/admin/stats.php';
    $html = ob_get_clean();
    dailyTrafficExpect(!preg_match('/Fatal error|Warning:|Deprecated:/', $html), 'No PHP diagnostics in the dashboard');
    dailyTrafficExpect($visioTraffic['rows'] === [], 'The organization filter still applies to the table');
    dailyTrafficExpect(str_contains($html, 'id="visio-daily-title"'), 'Daily chart must be rendered even with an empty filtered table');
    foreach ($visioDailySeries as $seriesIndex => $item) {
        dailyTrafficExpect(count($item['series']) === $days, 'Every selected day is present, including in the annual range');
        foreach ($item['series'] as $point) {
            $key = $point['date']->format('Y-m-d');
            dailyTrafficExpect(abs($point['value'] - ($expected[$key][$seriesIndex] ?? 0) / 1000000) < 1e-9,
                'Daily sum, organization scope, empty day, date boundary or decimal conversion is incorrect for ' . $key);
        }
    }
    if ($days === 7) file_put_contents(dirname(__DIR__) . '/tmp/visio-daily-test.html', $html);
    echo "video_traffic_daily_test: OK ($days daily buckets, both directions, all organizations, no organization, decimal Mo and exclusive end)\n";
} finally { $pdo->rollBack(); }
