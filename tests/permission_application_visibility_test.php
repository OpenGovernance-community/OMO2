<?php
declare(strict_types=1);

// Local database integration test; application changes and fixtures are rolled back.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';

use dbObject\{ArrayApplication, ArrayOrganization, DbObject, Holon, HolonPermission, Organization, OrganizationApplication, Permission};

function permissionVisibilityCheck(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

function permissionVisibilitySave($object): void
{
    $result = $object->save();
    permissionVisibilityCheck(!empty($result['status']), $result['text'] ?? 'Fixture save failed');
}

final class PermissionVisibilityOrganization extends Organization
{
    public function saveEditorPermissions(int $holonId, array $payload): bool
    {
        return $this->syncEditorPermissionAssignments($holonId, $payload);
    }
}

$appGroups = [
    'structure' => ['holons', 'properties'], 'team' => ['members'], 'calendar' => ['calendar'],
    'policy' => ['policy'], 'documents' => ['documents'], 'projects' => ['projects'],
    'stats' => ['stats'], 'activities' => ['recurring_tasks'], 'processus' => ['processes'],
    'decision' => ['decisions'], 'budget' => ['budget'],
];
$fullCatalog = Permission::getEditorCatalog();
foreach ($appGroups as $disabledApp => $hiddenGroups) {
    $enabledApps = array_values(array_diff(array_keys($appGroups), [$disabledApp]));
    $visible = Permission::getEditorCatalog([], $enabledApps);
    $expected = array_values(array_filter($fullCatalog, static fn($item) => !in_array($item['group'], $hiddenGroups, true)));
    permissionVisibilityCheck($visible === $expected, 'Only the disabled app rights must disappear: ' . $disabledApp);
}
$minimal = Permission::getEditorCatalog([], []);
permissionVisibilityCheck(array_values(array_unique(array_column($minimal, 'group'))) === ['organization', 'help'], 'Organization and Help remain available without optional apps');
permissionVisibilityCheck(Permission::getEditorCatalog() === $fullCatalog, 'The unrestricted catalog must remain available to permission evaluation and exports');

$organizations = new ArrayOrganization();
$organizations->load(['limit' => 1]);
$organization = new PermissionVisibilityOrganization();
foreach ($organizations as $candidate) $organization->load((int)$candidate->getId());
$root = $organization->getStructuralRootHolon();
permissionVisibilityCheck($root instanceof Holon, 'Local organization required');
$_SESSION['currentUser'] = 1;
$_SERVER['HTTP_HOST'] = 'omo.localtest.me';
$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $apps = new ArrayApplication();
    $apps->load();
    $links = [];
    foreach ($apps as $app) {
        $hash = $app->getRouteHash();
        if (!isset($appGroups[$hash])) continue;
        $link = new OrganizationApplication();
        if (!$link->load([['IDorganization', (int)$organization->getId()], ['IDapplication', (int)$app->getId()]])) {
            $link->set('IDorganization', $organization->getId());
            $link->set('IDapplication', $app->getId());
        }
        $link->set('active', true);
        permissionVisibilitySave($link);
        $links[$hash] = $link;
    }
    permissionVisibilityCheck(count($links) === count($appGroups), 'All application mappings must match installed apps');
    $authenticatedCatalog = $organization->getPermissionEditorCatalog();
    $_SESSION['currentUser'] = 0;
    permissionVisibilityCheck($organization->getPermissionEditorCatalog() === $authenticatedCatalog, 'Editor visibility depends on app activation, not the caller session');
    permissionVisibilityCheck(!in_array('documents', $organization->getEnabledApplicationHashes(0), true), 'Normal anonymous application navigation still respects login requirements');
    $_SESSION['currentUser'] = 1;

    $holon = new Holon();
    $holon->set('name', 'Permission visibility test');
    $holon->set('IDholon_org', $root->getId());
    $holon->set('IDholon_parent', $root->getId());
    $holon->set('IDtypeholon', 1);
    $holon->set('active', true);
    permissionVisibilitySave($holon);
    $id = (int)$holon->getId();
    $assignments = [];
    foreach (['member', 'admin', 'collective'] as $profile) {
        foreach (['CAN_CREATE_DOCUMENT', 'CAN_EDIT_HOLON', 'CAN_EDIT_TYPE1_PROPERTIES', 'CAN_EDIT_FAQ'] as $key) {
            $assignments[$profile][$key] = $profile === 'collective' ? ['self'] : [['range' => 'self', 'is_extended' => true]];
        }
    }
    permissionVisibilityCheck(HolonPermission::syncAssignmentsForHolon($id, $assignments), 'Seed all profiles and extended authorities');
    $before = HolonPermission::getAssignmentKeyMapForHolon($id);
    $documentPermission = Permission::findByKey('CAN_CREATE_DOCUMENT');
    $originalDocumentRow = HolonPermission::findByHolonAndPermission($id, (int)$documentPermission->getId(), 'self', 'member');
    $documentRowId = (int)$originalDocumentRow->getId();

    foreach (['structure', 'documents'] as $app) {
        $links[$app]->set('active', false);
        permissionVisibilitySave($links[$app]);
    }
    $visible = $organization->getPermissionEditorCatalog();
    permissionVisibilityCheck(!array_intersect(array_column($visible, 'group'), ['holons', 'properties', 'documents']), 'Actual organization activation must filter the editor');
    permissionVisibilityCheck(HolonPermission::getAssignmentKeyMapForHolon($id) === $before, 'Disabling apps must not change assignments');
    $stalePayload = ['permissions' => [], 'editablePermissionKeys' => array_column($visible, 'key')];
    permissionVisibilityCheck($organization->saveEditorPermissions($id, $stalePayload), 'Save the visible rights');
    $after = HolonPermission::getAssignmentKeyMapForHolon($id);
    foreach ($before as $profile => $permissions) {
        foreach ($permissions as $key => $ranges) {
            if ($key === 'CAN_EDIT_FAQ') {
                permissionVisibilityCheck(!isset($after[$profile][$key]), 'Visible rights can still be removed');
            } else {
                permissionVisibilityCheck(($after[$profile][$key] ?? null) === $ranges, 'Hidden rights and extended flags must survive: ' . $key . '/' . $profile);
            }
        }
    }
    $preservedRow = HolonPermission::findByHolonAndPermission($id, (int)$documentPermission->getId(), 'self', 'member');
    permissionVisibilityCheck((int)$preservedRow->getId() === $documentRowId, 'Hidden rows are retained, not recreated');
    foreach (['structure', 'documents'] as $app) {
        $links[$app]->set('active', true);
        permissionVisibilitySave($links[$app]);
    }
    permissionVisibilityCheck(in_array('documents', array_column($organization->getPermissionEditorCatalog(), 'group'), true), 'Reactivation reveals document rights');
    permissionVisibilityCheck($organization->saveEditorPermissions($id, $stalePayload), 'An editor opened before reactivation can still save');
    permissionVisibilityCheck(HolonPermission::getAssignmentKeyMapForHolon($id) === $after, 'A stale editor must not delete newly visible rights');
    permissionVisibilityCheck($organization->saveEditorPermissions($id, ['permissions' => []]), 'New editors can remove reactivated rights');
    permissionVisibilityCheck(HolonPermission::getAssignmentKeyMapForHolon($id) === [], 'Visible rights remain editable after reactivation');
    echo "permission_application_visibility_test: OK (all apps, profiles, extended flags, preservation, reactivation, stale editor, rollback)\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
    DbObject::$preload = [];
}
