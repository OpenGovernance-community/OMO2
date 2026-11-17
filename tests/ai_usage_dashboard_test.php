<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$_SERVER['HTTP_HOST'] = 'localtest.me';
$_SERVER['REQUEST_METHOD'] = 'GET';
require_once dirname(__DIR__) . '/shared_functions.php';
if (!envIsLocalRuntimeHost()) throw new RuntimeException('Local runtime required');

function aiDashboardExpect(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

$admins = new \dbObject\ArrayUser();
$admins->loadHydrated(['where' => [['field' => 'siteadmin', 'value' => 1], ['field' => 'active', 'value' => 1]], 'limit' => 1]);
aiDashboardExpect(count($admins) > 0, 'An active local administrator is required');
$_SESSION = ['currentUser' => (int)$admins[0]->getId(), 'auth_security_version' => (int)$admins[0]->get('security_version')];
$pdo = \dbObject\DbObject::getPdo();
$pdo->beginTransaction();
try {
    $people = [];
    foreach (['<Personne> & test', '<Personne> & test'] as $name) {
        $person = new \dbObject\User();
        $person->set('firstname', $name);
        $person->set('email', 'ai-usage-' . bin2hex(random_bytes(8)) . '@example.invalid');
        aiDashboardExpect(!empty($person->save()['status']), 'Cannot create account');
        $people[] = (int)$person->getId();
    }
    $organizations = [];
    foreach (['AI usage A', 'AI usage B'] as $name) {
        $organization = new \dbObject\Organization();
        $organization->set('name', $name);
        aiDashboardExpect(!empty($organization->save()['status']), 'Cannot create organization');
        $organizations[] = (int)$organization->getId();
    }
    // Mix organizations, origins, features, providers, kinds and missing measurements.
    foreach ([
        [$people[0], $organizations[0], 'site', 'model', 'assistant', 'openai', 'model-a', 30, true],
        [$people[0], $organizations[1], 'telegram', 'model', 'summary', 'anthropic', 'model-b', 20, true],
        [$people[0], null, 'background', 'model', 'translation', 'openai', 'model-c', null, false],
        [$people[0], $organizations[0], 'mcp', 'exchange', 'list', '', '', null, true],
        [$people[1], $organizations[0], 'api', 'exchange', 'list', '', '', null, false],
        [null, $organizations[0], 'background', 'model', 'translation', 'openai', 'model-a', null, true],
        [null, $organizations[1], 'site', 'model', 'summary', 'anthropic', 'model-b', 0, true],
    ] as [$userId, $organizationId, $origin, $kind, $feature, $provider, $model, $tokens, $success]) {
        \dbObject\AiUsage::record(['IDuser' => $userId, 'IDorganization' => $organizationId, 'origin' => $origin,
            'kind' => $kind, 'feature' => $feature, 'provider' => $provider, 'model' => $model, 'total_tokens' => $tokens, 'success' => $success]);
    }
    $baseline = \dbObject\AiUsage::dashboard('2000-01-01', '2100-01-01');
    $rows = array_filter($baseline['rows'], static fn($row) => in_array((int)$row['IDuser'], $people, true));
    aiDashboardExpect(count($rows) === 2, 'Homonyms must remain separate accounts, one row each');
    $first = array_values($rows)[0];
    aiDashboardExpect((int)$first['IDuser'] === $people[0] && (int)$first['requests'] === 4 && (int)$first['total_tokens'] === 50
        && (int)$first['errors'] === 1 && (int)$first['measured_requests'] === 2, 'Calls must combine across organizations and missing measurements');
    $filtered = \dbObject\AiUsage::dashboard('2000-01-01', '2100-01-01', $organizations[0]);
    aiDashboardExpect(count($filtered['rows']) === 3 && (int)$filtered['totals']['requests'] === 4
        && (int)$filtered['totals']['users'] === 2 && (int)$filtered['totals']['tokens'] === 30, 'Organization filter and totals must agree');
    $background = \dbObject\AiUsage::dashboard('2000-01-01', '2100-01-01', $organizations[0], 'background');
    aiDashboardExpect(count($background['rows']) === 1 && $background['rows'][0]['IDuser'] === null
        && $background['rows'][0]['total_tokens'] === null && (int)$background['totals']['users'] === 0, 'Unassigned requests are retained without inventing a person or tokens');
    aiDashboardExpect(\dbObject\AiUsage::dashboard('2100-01-01', '2100-01-02', $organizations[0])['rows'] === [], 'Empty period has no people');

    $_GET = ['range' => 7, 'organization' => $organizations[0], 'trend' => 'none'];
    ob_start();
    require dirname(__DIR__) . '/common/admin/stats.php';
    $html = ob_get_clean();
    aiDashboardExpect(!preg_match('/Fatal error|Warning:|Deprecated:/', $html), 'No PHP diagnostics in dashboard');
    $document = new DOMDocument();
    @$document->loadHTML('<?xml encoding="UTF-8">' . $html);
    $xpath = new DOMXPath($document);
    aiDashboardExpect($xpath->query('//table[@id="ai-usage-table"]/thead/tr/th')->length === 4, 'Only four summary columns');
    $tableRows = $xpath->query('//table[@id="ai-usage-table"]/tbody/tr');
    aiDashboardExpect($tableRows->length === 3, 'Render one row per account and one unassigned row');
    aiDashboardExpect(str_contains($tableRows[0]->textContent, '<Personne> & test'), 'Names must be escaped and preserved');
    foreach ($tableRows as $index => $tableRow) {
        if ($index === 0) continue;
        $unassigned = str_contains($tableRow->textContent, siteStatsT('ai.unassigned'));
        aiDashboardExpect($tableRow->getElementsByTagName('td')->item(1)->textContent === ($unassigned ? siteStatsT('monitor.unavailable') : '-'),
            'Unknown model tokens stay unavailable; exchange-only accounts have no model tokens');
    }
    file_put_contents(dirname(__DIR__) . '/tmp/ai-usage-dashboard.html', $html);
    echo "ai_usage_dashboard_test: OK (person grouping, homonyms, organizations, origins, missing tokens, totals, empty period and summary rendering)\n";
} finally {
    $pdo->rollBack();
}
