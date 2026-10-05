<?php
declare(strict_types=1);

// Exercise real permissions and saves without changing any existing organization.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';

use dbObject\{DbObject, Organization, ArrayApplication, OrganizationApplication, Holon, Permission, HolonPermission, User, UserOrganization, UserHolon, Document, ObjectVisibility};

function contentPermissionCheck(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
function contentPermissionSave($object): void {
    $result = $object->save();
    contentPermissionCheck(!empty($result['status']), 'Fixture save failed: ' . json_encode($result));
}

$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $org = new Organization();
    $org->set('name', 'Document content permissions');
    $org->set('shortname', 'content-' . bin2hex(random_bytes(5)));
    $org->set('interface_level', Organization::INTERFACE_LEVEL_EXPERT);
    contentPermissionSave($org);
    $orgId = (int)$org->getId();
    $apps = new ArrayApplication();
    $apps->load(['where' => [['field' => 'hash', 'value' => 'structure']]]);
    foreach ($apps as $app) {
        $enabled = new OrganizationApplication();
        $enabled->set('IDorganization', $orgId);
        $enabled->set('IDapplication', $app->getId());
        $enabled->set('active', true);
        contentPermissionSave($enabled);
    }
    $root = new Holon();
    $root->set('name', 'Root');
    $root->set('IDorganization', $orgId);
    $root->set('IDtypeholon', 4);
    $root->set('active', true);
    $root->set('visible', true);
    contentPermissionSave($root);
    $root->set('IDholon_org', $root->getId());
    contentPermissionSave($root);
    $circle = new Holon();
    $circle->set('name', 'Document circle');
    $circle->set('IDholon_org', $root->getId());
    $circle->set('IDholon_parent', $root->getId());
    $circle->set('IDtypeholon', 2);
    $circle->set('active', true);
    $circle->set('visible', true);
    contentPermissionSave($circle);
    $makeUser = static function (bool $member = true) use ($orgId): User {
        $user = new User();
        $user->set('email', 'content-' . bin2hex(random_bytes(5)) . '@example.invalid');
        $user->set('active', true);
        contentPermissionSave($user);
        if ($member) {
            $membership = new UserOrganization();
            $membership->set('IDuser', $user->getId());
            $membership->set('IDorganization', $orgId);
            $membership->set('active', true);
            contentPermissionSave($membership);
        }
        return $user;
    };
    $owner = $makeUser();
    $member = $makeUser();
    $manager = $makeUser();
    $outsider = $makeUser(false);
    $memberId = (int)$member->getId();
    $managerId = (int)$manager->getId();
    $link = new UserHolon();
    $link->set('IDuser', $managerId);
    $link->set('IDholon', $circle->getId());
    $link->set('active', true);
    $link->set('is_membership', true);
    contentPermissionSave($link);
    $grant = new HolonPermission();
    $grant->set('IDholon', $circle->getId());
    $grant->set('IDpermission', Permission::findByKey('CAN_EDIT_DOCUMENT')->getId());
    $grant->set('range', 'self');
    $grant->set('member_type', 'member');
    contentPermissionSave($grant);
    $_SERVER['HTTP_HOST'] = 'omo.localtest.me';
    $_SESSION = ['currentUser' => $memberId, 'currentOrganization' => $orgId];
    $doc = new Document();
    $doc->set('IDorganization', $orgId);
    $doc->set('IDholon', $circle->getId());
    $doc->set('IDuser', $owner->getId());
    $doc->set('title', 'Original title');
    $doc->set('description', 'Original description');
    $doc->set('keywords', 'original');
    $doc->set('content', '<p>Original content</p>');
    $doc->set('documenttype', Document::TYPE_HTML);
    $doc->set('active', true);
    contentPermissionSave($doc);
    contentPermissionCheck(!empty($doc->saveVisibilityRule('organization')['status']), 'Read scope must save');
    contentPermissionCheck(!empty($doc->saveEditVisibilityRule('organization')['status']), 'Edit scope must save');
    $read = $doc->getPrimaryVisibilityRuleRow();
    $edit = $doc->getPrimaryEditVisibilityRuleRow();
    $viewer = ObjectVisibility::buildCurrentViewerContext($orgId, $memberId);
    contentPermissionCheck(!$doc->hasObjectPermission('CAN_EDIT_DOCUMENT', $memberId), 'Content-only member must lack metadata permission');
    contentPermissionCheck(!$doc->canManageInOrganizationContext($orgId, $memberId, false), 'Metadata must stay protected');
    contentPermissionCheck(!$doc->canManageInOrganizationContextWithVisibilityRule($orgId, $memberId, $read, $viewer, false), 'Batched metadata check must stay protected');
    foreach ([Document::TYPE_HTML, Document::TYPE_EXTERNAL_LINK, Document::TYPE_UPLOADED_FILE, Document::TYPE_ETHERPAD, Document::TYPE_ETHERCALC, Document::TYPE_WHITEBOARD] as $type) {
        $doc->set('documenttype', $type);
        contentPermissionCheck($doc->canEditInOrganizationContext($orgId, $memberId, false), 'Organization edit scope must allow content: ' . $type);
        contentPermissionCheck($doc->canEditInOrganizationContextWithVisibilityRules($orgId, $memberId, $read, $edit, $viewer), 'Batched content check must agree: ' . $type);
    }
    $doc->set('documenttype', Document::TYPE_HTML);
    contentPermissionCheck(!$doc->canEditInOrganizationContext($orgId, $outsider->getId(), false), 'Outsiders must not edit organization documents');
    contentPermissionCheck(!$doc->canEditInOrganizationContext($orgId, 0, false), 'Anonymous viewers must not edit');
    $result = $doc->updateInOrganizationContext($orgId, $memberId, [
        'title' => 'Forged title', 'description' => 'Forged description', 'keywords' => 'forged',
        'content' => '<p>Member content</p>', 'visibility_type' => 'everyone', 'edit_visibility_type' => 'everyone',
        'documenttype' => Document::TYPE_EXTERNAL_LINK, 'IDholon' => 0,
    ]);
    contentPermissionCheck(!empty($result['status']), 'Content-only save must succeed: ' . json_encode($result));
    $saved = new Document();
    contentPermissionCheck($saved->load($doc->getId()), 'Saved document must load');
    contentPermissionCheck($saved->get('content') === '<p>Member content</p>', 'Member content must persist');
    foreach (['title' => 'Original title', 'description' => 'Original description', 'keywords' => 'original', 'documenttype' => Document::TYPE_HTML] as $field => $value) {
        contentPermissionCheck($saved->get($field) === $value, 'Forged metadata must be ignored: ' . $field);
    }
    contentPermissionCheck((int)$saved->get('IDholon') === (int)$circle->getId(), 'Document context must stay intact');
    contentPermissionCheck($saved->getPrimaryVisibilityRuleRow()['visibility_type'] === 'organization', 'Read scope must stay intact');
    contentPermissionCheck($saved->getPrimaryEditVisibilityRuleRow()['visibility_type'] === 'organization', 'Edit scope must stay intact');
    contentPermissionCheck(!empty($saved->saveEditVisibilityRule('self')['status']), 'Restricted edit scope must save');
    contentPermissionCheck(!$saved->canEditInOrganizationContext($orgId, $memberId, false), 'Private content must deny other members');
    contentPermissionCheck(!$saved->canEditInOrganizationContext($orgId, $managerId, false), 'Metadata permission must not bypass edit scope');
    contentPermissionCheck($saved->canManageInOrganizationContext($orgId, $managerId, false), 'Manager must still edit metadata');
    $result = $saved->updateInOrganizationContext($orgId, $managerId, [
        'title' => 'Manager title', 'description' => 'Manager description', 'keywords' => 'manager',
        'content' => '<p>Forbidden manager content</p>', 'visibility_type' => 'organization', 'edit_visibility_type' => 'self',
    ]);
    contentPermissionCheck(!empty($result['status']), 'Metadata-only save must succeed: ' . json_encode($result));
    $saved->load($doc->getId());
    contentPermissionCheck($saved->get('title') === 'Manager title', 'Manager metadata must persist');
    contentPermissionCheck($saved->get('content') === '<p>Member content</p>', 'Metadata-only save must preserve protected content');
    contentPermissionCheck(!empty($saved->saveVisibilityRule('self')['status']), 'Private read scope must save');
    contentPermissionCheck(!empty($saved->saveEditVisibilityRule('organization')['status']), 'Organization edit scope must save');
    contentPermissionCheck(!$saved->canEditInOrganizationContext($orgId, $memberId, false), 'Edit scope must not bypass read scope');
    $read = $saved->getPrimaryVisibilityRuleRow();
    $edit = $saved->getPrimaryEditVisibilityRuleRow();
    contentPermissionCheck(!$saved->canEditInOrganizationContextWithVisibilityRules($orgId, $memberId, $read, $edit, $viewer), 'Batched edit check must also respect private read scope');
    $result = $saved->updateInOrganizationContext($orgId, $memberId, ['content' => '<p>Denied</p>']);
    contentPermissionCheck(empty($result['status']), 'Unauthorized save must be rejected');
    echo "document_content_permissions_integration_test: OK\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
    DbObject::$preload = [];
}
