<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';
use dbObject\{ScrumSprint, ScrumSample, ScrumProject, Project, DbObject};
class ScrumUnestimatedProject extends Project { public function save() { return DbObject::save(); } }

function scrumExpectError(string $code, callable $action): void {
    try { $action(); } catch (RuntimeException $error) { mcpCheck($error->getMessage() === $code, 'Expected ' . $code . ', got ' . $error->getMessage()); return; }
    throw new RuntimeException('Expected error ' . $code);
}
$nodes = [1 => ['parent' => 0, 'size' => 'M', 'status' => 'ready'], 2 => ['parent' => 1, 'size' => 'M', 'status' => 'ready'], 3 => ['parent' => 1, 'size' => 'M', 'status' => 'ready']];
$calc = ScrumSprint::calculate($nodes);
mcpCheck($calc['total'] === 8 && $calc['nodes'][1]['own'] === 0, 'An underestimated parent must not add duplicate work');
$nodes[2]['status'] = 'review'; $nodes[3]['status'] = 'done';
mcpCheck(ScrumSprint::calculate($nodes)['remaining'] === 0, 'Review and done remove child work even with an open parent');
$nodes = [1 => ['parent' => 0, 'size' => 'XXL', 'status' => 'ready'], 2 => ['parent' => 1, 'size' => 'S', 'status' => 'done']];
$calc = ScrumSprint::calculate($nodes);
mcpCheck($calc['total'] === 256 && $calc['remaining'] === 255, 'A larger parent retains its own residual');
$nodes[3] = ['parent' => 2, 'size' => 'XL', 'status' => 'ready'];
$calc = ScrumSprint::calculate($nodes);
mcpCheck($calc['total'] === 256 && $calc['remaining'] === 256 && $calc['nodes'][1]['own'] === 192, 'Recursive child totals must be used');
scrumExpectError('tree', static fn () => ScrumSprint::calculate([1 => ['parent' => 2], 2 => ['parent' => 1]]));

$items = mcpFixtures(); $before = $_SESSION;
try {
    $oid = (int)$items['org']->getId(); $hid = (int)$items['root']->getId();
    $_SESSION = ['currentUser' => (int)$items['user']->getId(), 'currentOrganization' => $oid];
    $base = ['IDorganization' => $oid, 'IDholon' => $hid, 'active' => 1, 'status' => 'someday', 'project_size' => 'M'];
    $items['parent'] = mcpFixture(Project::class, ['title' => 'Scrum test parent'] + $base);
    $parentId = (int)$items['parent']->getId();
    $items['child1'] = mcpFixture(Project::class, ['title' => 'Scrum test child 1', 'IDproject_parent' => $parentId] + $base);
    $items['child2'] = mcpFixture(Project::class, ['title' => 'Scrum test child 2', 'IDproject_parent' => $parentId] + $base);
    $childId = (int)$items['child1']->getId();
    $sprintBase = ['IDorganization' => $oid, 'IDholon' => $hid, 'start_date' => ScrumSprint::now(), 'end_date' => ScrumSprint::now()->modify('+10 days'), 'state' => 'stopped', 'baseline' => 0, 'archived' => 0];
    $items['sprint'] = mcpFixture(ScrumSprint::class, ['title' => 'Scrum test sprint'] + $sprintBase);
    $sprint = $items['sprint'];
    $allow = static fn () => true;
    $items['unestimated'] = mcpFixture(ScrumUnestimatedProject::class, ['title' => 'Unestimated', 'project_size' => '?'] + $base);
    scrumExpectError('estimate', fn () => ScrumSprint::locked($oid, fn () => $sprint->import([(int)$items['unestimated']->getId()], [], $allow, $allow)));
    ScrumSprint::locked($oid, static fn () => $sprint->import([$parentId, $childId], [], $allow, $allow));
    mcpCheck(count($sprint->nodes()) === 3 && (int)$sprint->get('baseline') === 8, 'Import must include descendants once');
    $items['parent']->load($parentId, true);
    mcpCheck($items['parent']->get('status') === 'ready', 'Import must promote someday globally');
    $items['otherSprint'] = mcpFixture(ScrumSprint::class, ['title' => 'Scrum collision'] + $sprintBase);
    scrumExpectError('conflict', fn () => ScrumSprint::locked($oid, fn () => $items['otherSprint']->import([$childId], [], $allow, $allow)));
    scrumExpectError('partial', fn () => $sprint->removeTree($childId));
    ScrumSprint::locked($oid, fn () => $sprint->start());
    mcpCheck($sprint->get('state') === 'running', 'Start must freeze scope');
    scrumExpectError('locked', fn () => $sprint->import([$parentId], [], $allow, $allow));
    mcpCheck(!$items['child1']->delete(), 'Deleting an enrolled project cannot break the sprint tree');
    $items['parent']->set('status', 'done');
    mcpCheck(($items['parent']->save()['errorCode'] ?? '') === 'children_not_done', 'Parent completion must fail before child completion');
    $items['parent']->load($parentId, true);
    $items['child1']->load($childId, true); $items['child1']->set('status', 'review');
    ScrumSprint::checkedSave($items['child1']);
    $samples = ScrumSample::forSprint((int)$sprint->getId());
    mcpCheck(count($samples) === 2 && (int)end($samples)['remaining'] === 4, 'Status save must record burndown immediately');
    $items['child1']->set('title', 'Scrum renamed'); ScrumSprint::checkedSave($items['child1']);
    mcpCheck(count(ScrumSample::forSprint((int)$sprint->getId())) === 2, 'Unrelated edits must not add samples');
    $items['child1']->set('project_size', 'XXL'); ScrumSprint::checkedSave($items['child1']);
    mcpCheck(ScrumSprint::calculate($sprint->nodes())['total'] === 8, 'Live re-estimation must not rewrite a running baseline');
    $items['child1']->set('project_size', 'M'); ScrumSprint::checkedSave($items['child1']);
    $items['child1']->set('status', 'ready'); ScrumSprint::checkedSave($items['child1']);
    $samples = ScrumSample::forSprint((int)$sprint->getId());
    mcpCheck((int)end($samples)['remaining'] === 8, 'Reopening must increase remaining work');
    $newChild = new Project(); foreach (['title' => 'Must fail', 'IDproject_parent' => $parentId] + $base as $key => $value) { $newChild->set($key, $value); }
    mcpCheck(($newChild->save()['errorCode'] ?? '') === 'sprint_tree_locked', 'An active tree cannot gain untracked children');
    $sprint->sample('daily'); $sampleCount = count(ScrumSample::forSprint((int)$sprint->getId())); $sprint->sample('daily');
    mcpCheck(count(ScrumSample::forSprint((int)$sprint->getId())) === $sampleCount, 'Daily samples must be idempotent');
    $sprint->set('state', 'stopped'); ScrumSprint::checkedSave($sprint);
    ScrumSprint::locked($oid, fn () => $sprint->start());
    mcpCheck(count(ScrumSample::forSprint((int)$sprint->getId())) === 1, 'Restart replaces previous measurements');
    ScrumSprint::locked($oid, fn () => $sprint->sync($sprint->endAt()->modify('+1 second')));
    $frozen = $sprint->nodes(); $items['child1']->set('status', 'done'); ScrumSprint::checkedSave($items['child1']);
    mcpCheck($sprint->nodes() === $frozen, 'Finished snapshots cannot change with live projects');
    ScrumSprint::locked($oid, fn () => $items['otherSprint']->import([$parentId], [], $allow, $allow));
    mcpCheck(count($items['otherSprint']->nodes()) === 3, 'A finished sprint releases its projects for the next sprint');
    $items['otherSprint']->delete();
    $check = new Project(); mcpCheck($check->load($parentId, true), 'Deleting a sprint must keep projects');
    mcpCheck(ScrumProject::forSprint((int)$items['otherSprint']->getId()) === [], 'Deleting a sprint must delete associations');
    echo "Scrum calculation and lifecycle tests passed.\n";
} finally { mcpCleanup($items); $_SESSION = $before; }
