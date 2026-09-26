<?php
declare(strict_types=1);

// Read-only integration test; use an existing organization and member, without creating fixtures.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$organizationId = (int)($argv[1] ?? 0);
$userId = (int)($argv[2] ?? 0);
if ($organizationId <= 0 || $userId <= 0) {
    fwrite(STDERR, "Usage: php tests/topbar_search_counts_readonly_test.php ORGANIZATION_ID USER_ID [QUERY]\n");
    exit(2);
}
require_once dirname(__DIR__) . '/shared_functions.php';
$_SESSION['currentUser'] = $userId;
$_SESSION['currentOrganization'] = $organizationId;
$organization = new \dbObject\Organization();
if (!$organization->load($organizationId)) { throw new RuntimeException('Organization not found'); }
$viewer = \dbObject\SearchJob::buildViewerContextFromGlobals($organizationId, 0);
$queries = isset($argv[3]) ? [$argv[3]] : ['budget', 'test', 'gestion'];
$exercisedLimit = false;
foreach ($queries as $query) {
    $options = ['limit' => 1, 'perScopeLimit' => 14, 'viewerContext' => $viewer];
    $limited = $organization->searchTopbarResults($query, [], $options);
    $complete = $organization->searchTopbarResults($query, [], $options + ['retainAllScopes' => true]);
    foreach ([$limited, $complete] as $payload) {
        $actual = array_count_values(array_column($payload['results'], 'module'));
        foreach ($payload['counts'] as $module => $count) {
            if ($count !== ($actual[$module] ?? 0)) { throw new RuntimeException('Counter differs from filterable results: ' . $module); }
            if ($count > 14) { throw new RuntimeException('Per-module limit lost'); }
        }
        if (array_sum($payload['counts']) !== count($payload['results'])) { throw new RuntimeException('Total differs from counters'); }
    }
    if (count($complete['results']) > 1) {
        $exercisedLimit = true;
        if (count($limited['results']) !== 1) { throw new RuntimeException('Explicit global limit must still work'); }
        if ($complete['results'][0] !== $limited['results'][0]) { throw new RuntimeException('Global ranking changed'); }
    }
    echo $query . ': ' . count($complete['results']) . " filterable results, consistent counters\n";
}
if (!$exercisedLimit) { throw new RuntimeException('Choose a query with at least two visible results to exercise truncation'); }
echo "topbar_search_counts_readonly_test: OK\n";
