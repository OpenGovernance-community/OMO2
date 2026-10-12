<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';

use dbObject\{ArrayProject, DbObject, Holon, Organization, Project};

function projectHydrationCheck(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
function projectHydrationFixture(string $class, array $fields): DbObject
{
    $item = new $class();
    foreach ($fields as $field => $value) $item->set($field, $value);
    projectHydrationCheck(!empty($item->save()['status']), 'Fixture save failed: ' . $class);
    return $item;
}
function projectHydrationSnapshot(ArrayProject $projects): array
{
    $result = [];
    foreach ($projects as $project) {
        $result[(int)$project->getId()] = [
            $project->get('title'), (int)$project->get('IDorganization'), (int)$project->get('IDholon'),
            $project->get('description'), $project->get('priority'), $project->get('calculated_importance'),
            $project->get('created_at')->format('Y-m-d H:i:s'),
        ];
    }
    return $result;
}

$pdo = DbObject::getPdo();
$session = $_SESSION;
$pdo->beginTransaction();
try {
    $nonce = bin2hex(random_bytes(8));
    $org = projectHydrationFixture(Organization::class, ['name' => 'Hydration test', 'shortname' => 'hydrate-' . $nonce]);
    $other = projectHydrationFixture(Organization::class, ['name' => 'Other hydration test', 'shortname' => 'other-hydrate-' . $nonce]);
    $oid = (int)$org->getId();
    $_SESSION['currentOrganization'] = $oid;
    $root = projectHydrationFixture(Holon::class, ['IDorganization' => $oid, 'IDtypeholon' => 4, 'name' => 'Root', 'active' => 1, 'visible' => 1]);
    $child = projectHydrationFixture(Holon::class, ['IDorganization' => $oid, 'IDholon_parent' => $root->getId(),
        'IDholon_org' => $root->getId(), 'IDtypeholon' => 2, 'name' => 'Child', 'active' => 1, 'visible' => 1]);
    $projects = [];
    foreach (['local' => [$oid, $root->getId(), 1, Project::KIND_STANDARD],
        'child' => [$oid, $child->getId(), 1, Project::KIND_STANDARD],
        'organization' => [$oid, null, 1, Project::KIND_STANDARD],
        'archived' => [$oid, $root->getId(), 0, Project::KIND_STANDARD],
        'template' => [$oid, $root->getId(), 1, Project::KIND_CHECKLIST_TEMPLATE],
        'foreign' => [$other->getId(), null, 1, Project::KIND_STANDARD]] as $name => [$projectOid, $hid, $active, $kind]) {
        $projects[$name] = projectHydrationFixture(Project::class, ['IDorganization' => $projectOid, 'IDholon' => $hid,
            'title' => $name, 'description' => '<p>Hydrated content</p>', 'active' => $active, 'project_kind' => $kind]);
    }
    $cases = [
        [$root->getId(), 'contextual', [], false, ['local']],
        [$root->getId(), 'contextual', [], true, ['local', 'organization']],
        [$root->getId(), 'children', [$child->getId()], false, ['local', 'child']],
        [$root->getId(), 'descendants', [$child->getId()], true, ['local', 'child', 'organization']],
        [0, 'contextual', [], false, ['organization']],
    ];
    foreach ($cases as [$hid, $scope, $descendants, $includeOrg, $names]) {
        DbObject::enableReadOnlyMemoization(false);
        DbObject::$preload = [];
        $legacy = new ArrayProject();
        $legacy->loadForContext($oid, $hid, $scope, $descendants, $includeOrg);
        $expected = projectHydrationSnapshot($legacy);
        $expectedIds = array_map(static fn(string $name): int => (int)$projects[$name]->getId(), $names);
        $actualIds = array_keys($expected);
        sort($expectedIds); sort($actualIds);
        projectHydrationCheck($actualIds === $expectedIds, 'Scope and organization filtering: ' . $scope);
        DbObject::$preload = [];
        $before = DbObject::getPerformanceCounters()['queries'];
        $hydrated = new ArrayProject();
        $hydrated->loadForContext($oid, $hid, $scope, $descendants, $includeOrg, true);
        projectHydrationCheck(projectHydrationSnapshot($hydrated) === $expected, 'Hydration preserves fields and ordering: ' . $scope);
        projectHydrationCheck(DbObject::getPerformanceCounters()['queries'] - $before === 1, 'No per-project SQL after full hydration.');
    }
    $projects['local']->set('title', 'Changed after read');
    projectHydrationCheck(!empty($projects['local']->save()['status']), 'Update fixture.');
    $fresh = new ArrayProject();
    $fresh->loadForContext($oid, $root->getId(), 'contextual', [], false, true);
    projectHydrationCheck($fresh[0]->get('title') === 'Changed after read', 'Hydration observes later writes.');
    echo "project_context_hydration_test: OK (scopes, isolation, order, fields, one query, fresh writes; rollback)\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $_SESSION = $session;
    DbObject::$preload = [];
}
