<?php
declare(strict_types=1);

// Reuse the in-memory persistence fixture and exercise the real permission checks.
require_once __DIR__ . '/extended_authorities_test.php';

use dbObject\DbObject;
use dbObject\HolonPermission as HP;

DbObject::$holons = [];
foreach ([
    1 => [0, 4, 0], 2 => [1, 2, 0], 3 => [7, 1, 9],
    4 => [2, 2, 0], 5 => [4, 1, 0], 6 => [5, 1, 0],
    7 => [8, 3, 0], 8 => [2, 3, 0], 9 => [1, 1, 0],
    10 => [8, 1, 0], 11 => [1, 2, 0], 12 => [4, 3, 0],
    13 => [12, 1, 0], 14 => [1, 3, 0], 15 => [14, 1, 0],
] as $id => [$parent, $type, $template]) {
    DbObject::$holons[$id] = ['id' => $id, 'name' => 'Element ' . $id, 'IDholon_parent' => $parent,
        'IDholon_template' => $template, 'IDtypeholon' => $type, 'IDorganization' => 42, 'IDholon_org' => 1, 'active' => 1];
}

function checkParentScope(string $range, array $expected): void
{
    DbObject::$grants = [];
    foreach (['member', 'admin', 'collective'] as $index => $profile) {
        $permissionId = $index + 1;
        DbObject::$grants[$permissionId] = ['id' => $permissionId, 'IDholon' => 9, 'IDpermission' => $permissionId,
            'permission_key' => \dbObject\Permission::KEYS[$permissionId], 'range' => $range,
            'member_type' => $profile, 'is_extended' => false];
    }
    // Isolate synthetic assignments; production cache behavior is unchanged.
    DbObject::$memo = [];
    $_SESSION = ['currentUser' => 7, 'currentOrganization' => 42];
    foreach (array_keys(DbObject::$holons) as $target) {
        $allowed = in_array($target, $expected, true);
        extendedAssert(HP::userHasPermissionForHolonContext(7, 42, 'CAN_MOVE_HOLON', $target) === $allowed, $range . ': member target ' . $target);
        extendedAssert(HP::userHasPermissionForHolonContext(9, 42, 'CAN_EDIT_HOLON', $target) === $allowed, $range . ': admin target ' . $target);
        extendedAssert(HP::holonHasCollectivePermissionForHolonContext(42, 3, 'CAN_DELETE_HOLON', $target) === $allowed, $range . ': collective target ' . $target);
    }
    // The matrix must expose real target roots, including scopes resolving to several roots.
    $details = HP::buildEffectivePermissionDetailsForOrganization(7, 42, ['CAN_MOVE_HOLON']);
    extendedAssert(count($details['rows']) > 0, 'Scope details are present');
    foreach ($details['rows'] as $row) {
        extendedAssert(in_array($row['scopeHolonId'], $expected, true), 'Scope details must not include an excluded parent or an unresolved target');
    }
}

checkParentScope(HP::RANGE_PARENT_CIRCLE, [2]);
checkParentScope(HP::RANGE_PARENT_CIRCLE_ELEMENTS, [3, 4, 10]);
checkParentScope(HP::RANGE_PARENT_CIRCLE_DESCENDANTS, [3, 4, 5, 6, 7, 8, 10, 12, 13]);

// A role below a root-level group uses the organization as its structural parent.
DbObject::$holons[3]['IDholon_parent'] = 14;
checkParentScope(HP::RANGE_PARENT_CIRCLE, [1]);
checkParentScope(HP::RANGE_PARENT_CIRCLE_ELEMENTS, [2, 3, 9, 11, 15]);
checkParentScope(HP::RANGE_PARENT_CIRCLE_DESCENDANTS, [2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15]);

// Parent means the nearest non-group element, even when it is not a circle.
DbObject::$holons[3]['IDholon_parent'] = 5;
checkParentScope(HP::RANGE_PARENT_CIRCLE, [5]);
checkParentScope(HP::RANGE_PARENT_CIRCLE_ELEMENTS, [3, 6]);
checkParentScope(HP::RANGE_PARENT_CIRCLE_DESCENDANTS, [3, 6]);

$labels = HP::getRangeLabels();
extendedAssert($labels[HP::RANGE_PARENT_CIRCLE] === 'Parent seul', 'Parent label');
extendedAssert($labels[HP::RANGE_PARENT_CIRCLE_ELEMENTS] === 'Enfants direct du parent', 'Direct children label');
extendedAssert($labels[HP::RANGE_PARENT_CIRCLE_DESCENDANTS] === 'Descendants du parent', 'Descendants label');
echo "parent_permission_scopes_test: OK (depth, nested groups, excluded parent, root parent, inherited member/admin/collective grants, matrix)\n";
