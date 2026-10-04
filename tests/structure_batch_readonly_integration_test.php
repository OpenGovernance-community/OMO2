<?php
declare(strict_types=1);

// Explicit integration check against existing data. No fixtures or business writes.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$organizationId = (int)($argv[1] ?? 0);
$userId = (int)($argv[2] ?? 0);
if ($organizationId <= 0) { fwrite(STDERR, "Usage: php tests/structure_batch_readonly_integration_test.php ORGANIZATION_ID [USER_ID]\n"); exit(2); }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';
foreach (['DB_QUERY_LOG_ENABLED' => 'true', 'DB_QUERY_LOG_MIN_MS' => '50', 'DB_QUERY_LOG_PATH' => sys_get_temp_dir() . '/structure-batch-test-' . getmypid() . '.jsonl'] as $key => $value) {
    $_ENV[$key] = $_SERVER[$key] = $value;
    putenv($key . '=' . $value);
}
$_SESSION = ['currentUser' => $userId, 'currentOrganization' => $organizationId];

use dbObject\ArrayHolon;
use dbObject\DbObject;
use dbObject\DeferredProposal;
use dbObject\Holon;
use dbObject\HolonPermission;
use dbObject\Organization;

function batchAssert(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
function batchNodeData(Holon $node): array {
    return [(int)$node->get('IDholon_parent'), (int)$node->get('IDtypeholon'), $node->getDisplayName(), $node->getFullDisplayName(), (bool)$node->get('active'), (bool)$node->get('visible')];
}
$stats = new ReflectionProperty(DbObject::class, '_sqlPerformanceStats');
$queryCount = static fn (): int => $stats->getValue()['queryCount'];
$organization = new Organization();
batchAssert($organization->load($organizationId), 'Organization must exist.');
$root = $organization->getEnabledStructuralRootHolon();
batchAssert($root instanceof Holon, 'Organization must have an enabled structure.');
$rootId = (int)$root->getId();

foreach ([false, true] as $includeHidden) {
    DbObject::enableReadOnlyMemoization(false);
    DbObject::$preload = [];
    $expected = [];
    $walk = static function (Holon $node) use (&$walk, &$expected, $includeHidden): void {
        $id = (int)$node->getId();
        if (isset($expected[$id])) return;
        $expected[$id] = batchNodeData($node);
        foreach ($node->getChildren($includeHidden) as $child) $walk($child);
    };
    $before = $queryCount();
    $walk($root);
    $legacyQueries = $queryCount() - $before;
    DbObject::$preload = [];
    $tree = new ArrayHolon();
    $before = $queryCount();
    $tree->loadSubtreeHydrated($rootId, $includeHidden);
    $batchQueries = $queryCount() - $before;
    $children = $tree->getChildrenByParentId();
    $actual = [];
    $walkBatch = static function (Holon $node) use (&$walkBatch, &$actual, $children): void {
        $id = (int)$node->getId();
        if (isset($actual[$id])) return;
        $actual[$id] = batchNodeData($node);
        foreach ($children[$id] ?? [] as $child) $walkBatch($child);
    };
    $walkBatch($tree->get($rootId));
    batchAssert($actual === $expected, 'Batched tree must preserve traversal order, fields, and hidden/inactive branch boundaries.');
    batchAssert($queryCount() - $before === $batchQueries, 'Reading hydrated fields and adjacency must not trigger individual loads.');
    batchAssert($batchQueries <= $legacyQueries + 1, 'Batching must not add per-node queries.');
    echo 'Tree hidden=' . (int)$includeHidden . ': ' . count($actual) . ' nodes, ' . $legacyQueries . ' -> ' . $batchQueries . " queries\n";
}

$tree = new ArrayHolon();
$tree->loadSubtreeHydrated(0);
batchAssert(count($tree) === 0, 'Invalid roots must not load every organization.');
DbObject::enableReadOnlyMemoization();
$first = $root->getChildren();
$count = count($first);
$first->exchangeArray([]);
$before = $queryCount();
batchAssert(count($root->getChildren()) === $count, 'Mutating a returned collection must not corrupt cached membership.');
batchAssert($queryCount() === $before, 'Repeated child enumeration must reuse its request snapshot.');
DbObject::enableReadOnlyMemoization(false);
$root->getChildren();
batchAssert($queryCount() > $before, 'Disabling memoization restores fresh collection reads.');

$collectives = array_slice(array_keys($expected), 0, 3);
$collectives[] = 0;
foreach ($collectives as $collectiveId) {
    DbObject::enableReadOnlyMemoization(false);
    $separate = [
        DeferredProposal::TARGET_RULE => DeferredProposal::getRuleTargetHolonCatalog($organizationId, $collectiveId),
        DeferredProposal::TARGET_HOLON => DeferredProposal::getHolonTargetHolonCatalog($organizationId, $collectiveId),
        DeferredProposal::TARGET_PROJECT => DeferredProposal::getProjectTargetHolonCatalog($organizationId, $collectiveId),
        DeferredProposal::TARGET_RECURRING_TASK => DeferredProposal::getObjectTargetHolonCatalog($organizationId, $collectiveId, DeferredProposal::TARGET_RECURRING_TASK),
        DeferredProposal::TARGET_INDICATOR => DeferredProposal::getObjectTargetHolonCatalog($organizationId, $collectiveId, DeferredProposal::TARGET_INDICATOR),
    ];
    batchAssert(DeferredProposal::getTargetHolonCatalogs($organizationId, $collectiveId) === $separate, 'Combined permission keys must retain all five independent catalogues.');
    $keys = ['CAN_ADD_HOLON', 'CAN_MOVE_HOLON', 'CAN_CREATE_RULE'];
    $fresh = HolonPermission::buildHolonCollectivePermissionSetForOrganization($organizationId, $collectiveId, $keys);
    DbObject::enableReadOnlyMemoization();
    batchAssert(HolonPermission::buildHolonCollectivePermissionSetForOrganization($organizationId, $collectiveId, $keys) === $fresh, 'Collective memo must preserve grants and denials.');
    $before = $queryCount();
    batchAssert(HolonPermission::buildHolonCollectivePermissionSetForOrganization($organizationId, $collectiveId, $keys) === $fresh, 'Repeated collective lookup must remain identical.');
    batchAssert($queryCount() === $before, 'Repeated collective lookup must issue no queries.');
}
DbObject::enableReadOnlyMemoization(false);
foreach (array_slice(array_keys($expected), 0, 8) as $nodeId) {
    $node = new Holon();
    if (!$node->load($nodeId) || !$node->getParentHolon()) continue;
    $parentId = (int)$node->get('IDholon_parent');
    foreach ([false, true] as $collective) {
        DbObject::enableReadOnlyMemoization();
        $full = $organization->getHolonCreationEditorData($parentId, $nodeId, $collective);
        $state = $organization->getHolonEditorState($parentId, $nodeId, $collective);
        batchAssert($state === ($full['holon'] ?? []), 'State-only reads must preserve all fields, properties and permission flags.');
    }
}
DbObject::enableReadOnlyMemoization(false);
echo "structure_batch_readonly_integration_test: OK\n";
