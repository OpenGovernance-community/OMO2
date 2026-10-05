<?php
declare(strict_types=1);

// Exercise real document checks in an isolated organization, then roll back.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';

use dbObject\{DbObject, Organization, ArrayApplication, OrganizationApplication, Holon, Permission, HolonPermission, User, UserOrganization, UserHolon, Document, ObjectVisibility};

function groupPermissionCheck(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
function groupPermissionSave($object): void {
    $result = $object->save();
    groupPermissionCheck(!empty($result['status']), 'Fixture save failed: ' . json_encode($result));
}

$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $organization = new Organization();
    $organization->set('name', 'Group membership permissions');
    $organization->set('shortname', 'groups-' . bin2hex(random_bytes(5)));
    $organization->set('interface_level', Organization::INTERFACE_LEVEL_EXPERT);
    groupPermissionSave($organization);
    $orgId = (int)$organization->getId();
    $applications = new ArrayApplication();
    $applications->load(['where' => [['field' => 'hash', 'value' => 'structure']]]);
    foreach ($applications as $application) {
        $enabled = new OrganizationApplication();
        $enabled->set('IDorganization', $orgId);
        $enabled->set('IDapplication', $application->getId());
        $enabled->set('active', true);
        groupPermissionSave($enabled);
    }
    $root = new Holon();
    $root->set('name', 'Root');
    $root->set('IDorganization', $orgId);
    $root->set('IDtypeholon', 4);
    $root->set('active', true);
    $root->set('visible', true);
    groupPermissionSave($root);
    $root->set('IDholon_org', $root->getId());
    groupPermissionSave($root);
    $makeHolon = static function (string $name, int $type, Holon $parent) use ($root): Holon {
        $holon = new Holon();
        $holon->set('name', $name);
        $holon->set('IDtypeholon', $type);
        $holon->set('IDholon_org', $root->getId());
        $holon->set('IDholon_parent', $parent->getId());
        $holon->set('active', true);
        $holon->set('visible', true);
        groupPermissionSave($holon);
        return $holon;
    };
    $outer = $makeHolon('Outer circle', 2, $root);
    $circle = $makeHolon('Containing circle', 2, $outer);
    $group = $makeHolon('Group', 3, $circle);
    $nestedGroup = $makeHolon('Nested group', 3, $group);
    $role = $makeHolon('Role', 1, $nestedGroup);
    $otherCircle = $makeHolon('Other circle', 2, $root);
    $template = $makeHolon('Circle template', 2, $root);
    $template->set('templatename', 'Circle template');
    groupPermissionSave($template);
    $circle->set('IDholon_template', $template->getId());
    groupPermissionSave($circle);
    $user = new User();
    $user->set('email', 'groups-' . bin2hex(random_bytes(5)) . '@example.invalid');
    $user->set('active', true);
    groupPermissionSave($user);
    $userId = (int)$user->getId();
    $membership = new UserOrganization();
    $membership->set('IDuser', $userId);
    $membership->set('IDorganization', $orgId);
    $membership->set('active', true);
    groupPermissionSave($membership);
    $link = new UserHolon();
    $link->set('IDuser', $userId);
    $link->set('IDholon', $role->getId());
    $link->set('active', true);
    $link->set('is_membership', true);
    groupPermissionSave($link);
    $_SERVER['HTTP_HOST'] = 'omo.localtest.me';
    $_SESSION = ['currentUser' => $userId, 'currentOrganization' => $orgId];
    $grant = static function (Holon $source, string $key, string $range, string $profile = 'member', bool $extended = false): HolonPermission {
        $assignment = new HolonPermission();
        $assignment->set('IDholon', $source->getId());
        $assignment->set('IDpermission', Permission::findByKey($key)->getId());
        $assignment->set('range', $range);
        $assignment->set('member_type', $profile);
        $assignment->set('is_extended', $extended);
        groupPermissionSave($assignment);
        return $assignment;
    };
    $grant($template, 'CAN_EDIT_DOCUMENT', 'self');
    $grant($outer, 'CAN_EDIT_DOCUMENT', 'self');
    $grant($otherCircle, 'CAN_EDIT_DOCUMENT', 'self');
    $grant($circle, 'CAN_EDIT_PROJECT', 'self');
    $grant($circle, 'CAN_DELETE_DOCUMENT', 'self', 'admin');
    $grant($circle, 'CAN_DELETE_PROJECT', 'self', 'collective');
    $grant($circle, 'CAN_EDIT_HOLON', 'self', 'member', true);
    // Supply rules explicitly to keep document fixtures transient while using real checks.
    $document = new class extends Document {
        public function getPrimaryVisibilityRuleRow() { return ['visibility_type' => 'organization']; }
        public function getPrimaryEditVisibilityRuleRow() { return ['visibility_type' => 'organization']; }
    };
    $document->set('IDorganization', $orgId);
    $document->set('IDholon', $circle->getId());
    $can = static fn (string $key, Holon $target) => HolonPermission::userHasPermissionForHolonContext($userId, $orgId, $key, $target->getId());
    groupPermissionCheck($document->canEditInOrganizationContext($orgId, $userId, false), 'Role in nested groups must edit content open to organization members');
    groupPermissionCheck($document->canEditInOrganizationContext($orgId, $userId, true), 'Content editing must also work with session caching enabled');
    groupPermissionCheck($document->canManageInOrganizationContext($orgId, $userId, false), 'Role in nested groups must manage document metadata through its circle grant');
    groupPermissionCheck($document->canManageInOrganizationContext($orgId, $userId, true), 'Session permission cache must include metadata permissions through circle membership');
    $oldCache = $_SESSION['permissionCacheByOrganization'][$orgId];
    $oldCache['cacheVersion'] = 27;
    groupPermissionCheck(!commonIsCurrentUserPermissionCacheEntryFresh($oldCache, $orgId, $userId), 'Sessions created before the fix must rebuild their permission cache');
    groupPermissionCheck($can('CAN_EDIT_PROJECT', $circle), 'Other object permissions must also use circle membership');
    foreach ([$outer, $otherCircle, $root, $role] as $target) {
        groupPermissionCheck(!$can('CAN_EDIT_DOCUMENT', $target), 'Circle self scope must not expand to other contexts');
    }
    groupPermissionCheck(!$can('CAN_DELETE_DOCUMENT', $circle), 'Membership must not grant circle administration');
    groupPermissionCheck(!$can('CAN_DELETE_PROJECT', $circle), 'Membership must not activate collective grants');
    groupPermissionCheck(!$can('CAN_EDIT_HOLON', $circle), 'Extended grants must remain dormant');
    groupPermissionCheck(commonSetCurrentUserExtendedAuthorities(true, $orgId), 'Circle extended grants must make activation available');
    groupPermissionCheck($can('CAN_EDIT_HOLON', $circle), 'Activated circle extended grant must apply');
    commonSetCurrentUserExtendedAuthorities(false, $orgId);
    $details = HolonPermission::buildEffectivePermissionDetailsForOrganization($userId, $orgId, ['CAN_EDIT_DOCUMENT']);
    groupPermissionCheck(count($details['rows']) === 1 && $details['rows'][0]['assignedHolonId'] === (int)$circle->getId(), 'Permission matrix must expose the inferred circle assignment');
    $context = ObjectVisibility::buildCurrentViewerContext($orgId, $userId);
    groupPermissionCheck(ObjectVisibility::viewerCanAccessRule(['visibility_type' => 'circle', 'IDholon' => $circle->getId()], $context), 'Circle visibility must accept roles inside groups');
    // The same role has the same membership with zero, one, or several groups.
    foreach ([$circle, $group, $nestedGroup] as $parent) {
        $role->set('IDholon_parent', $parent->getId());
        groupPermissionSave($role);
        groupPermissionCheck($can('CAN_EDIT_DOCUMENT', $circle), 'Groups must not change circle membership');
    }
    $link->setHolonAdmin(true);
    groupPermissionCheck(!$can('CAN_DELETE_DOCUMENT', $circle), 'Role administration alone must not imply circle administration');
    $roleTemplate = $makeHolon('Parent admin template', 1, $root);
    $roleTemplate->set('templatename', 'Parent admin template');
    $roleTemplate->set('adminparent', true);
    groupPermissionSave($roleTemplate);
    $role->set('IDholon_template', $roleTemplate->getId());
    groupPermissionSave($role);
    groupPermissionCheck($can('CAN_DELETE_DOCUMENT', $circle), 'Explicit parent admin role must cross transparent groups');
    groupPermissionCheck(!$can('CAN_DELETE_DOCUMENT', $outer), 'Parent administration must stop at the containing circle');
    $rootGroup = $makeHolon('Root group', 3, $root);
    $role->set('IDholon_parent', $rootGroup->getId());
    groupPermissionSave($role);
    $grant($root, 'CAN_EDIT_DOCUMENT', 'self');
    $document->set('IDholon', $root->getId());
    groupPermissionCheck($document->canEditInOrganizationContext($orgId, $userId, false), 'Organization document editing must work with only a role in a root-level group');
    groupPermissionCheck(!$can('CAN_EDIT_DOCUMENT', $circle), 'Moving the role must remove its previous circle membership');
    $role->set('IDholon_parent', $nestedGroup->getId());
    groupPermissionSave($role);
    $link->set('active', false);
    groupPermissionSave($link);
    groupPermissionCheck(!$can('CAN_EDIT_DOCUMENT', $circle), 'Inactive assignments must not infer membership');
    $link->delete();
    $preferences = UserHolon::saveDashboardLayoutForUser($userId, $role->getId(), UserHolon::getDefaultDashboardLayout());
    groupPermissionCheck(!empty($preferences['status']), 'Preference fixture must save');
    groupPermissionCheck(!$can('CAN_EDIT_DOCUMENT', $circle), 'Preference-only links must not infer membership');
    echo "group_membership_permissions_integration_test: OK\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
    DbObject::$preload = [];
}
