<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';

use dbObject\{Application, DbObject, Holon, Organization, OrganizationApplication, Permission, Tension, User, UserHolon, UserOrganization};

function tensionExpect(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}

function tensionFixture(string $class, array $fields): DbObject
{
    $object = new $class();
    foreach ($fields as $field => $value) { $object->set($field, $value); }
    $result = $object->save();
    tensionExpect(!empty($result['status']), $class . ': ' . json_encode($result));
    return $object;
}

final class TensionPermissionOrganization extends Organization
{
    public function saveEditorPermissions(int $holonId, array $permissions): bool
    {
        return $this->syncEditorPermissionAssignments($holonId, ['permissions' => $permissions]);
    }
}

$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $nonce = bin2hex(random_bytes(6));
    $user = tensionFixture(User::class, ['email' => 'tension-' . $nonce . '@example.invalid', 'firstname' => 'Member', 'active' => 1]);
    $organization = tensionFixture(TensionPermissionOrganization::class, ['name' => 'Tension rights', 'shortname' => 'tension-' . $nonce,
        'interface_level' => Organization::INTERFACE_LEVEL_EXPERT]);
    $uid = (int)$user->getId(); $oid = (int)$organization->getId();
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid];
    $_SERVER['HTTP_HOST'] = 'omo.localtest.me';
    tensionFixture(UserOrganization::class, ['IDorganization' => $oid, 'IDuser' => $uid, 'active' => 1]);
    $unassignedUser = tensionFixture(User::class, ['email' => 'tension-unassigned-' . $nonce . '@example.invalid', 'firstname' => 'Unassigned', 'active' => 1]);
    $unassignedUid = (int)$unassignedUser->getId();
    tensionFixture(UserOrganization::class, ['IDorganization' => $oid, 'IDuser' => $unassignedUid, 'active' => 1]);
    $app = new Application();
    tensionExpect((bool)$app->load([['hash', 'structure']]), 'Structure application is required.');
    tensionFixture(OrganizationApplication::class, ['IDorganization' => $oid, 'IDapplication' => $app->getId(), 'active' => 1]);
    $root = tensionFixture(Holon::class, ['IDorganization' => $oid, 'name' => 'Root', 'IDtypeholon' => 4, 'active' => 1, 'visible' => 1]);
    $root->set('IDholon_org', $root->getId()); $root->save();
    $makeSpace = static fn(array $fields): Holon => tensionFixture(Holon::class, array_merge([
        'IDorganization' => null, 'IDholon_org' => $root->getId(), 'IDholon_parent' => $root->getId(),
        'name' => 'Space', 'IDtypeholon' => 2, 'active' => 1, 'visible' => 1,
    ], $fields));
    $source = $makeSpace(['name' => 'Source']);
    $child = $makeSpace(['name' => 'Child', 'IDholon_parent' => $source->getId()]);
    $other = $makeSpace(['name' => 'Other']);
    $template = $makeSpace(['name' => 'Template', 'templatename' => 'Tension template', 'visible' => 0, 'IDtypeholon' => 1]);
    $instance = $makeSpace(['name' => 'Instance', 'IDholon_template' => $template->getId(), 'IDtypeholon' => 1]);
    tensionFixture(UserHolon::class, ['IDuser' => $uid, 'IDholon' => $source->getId(), 'active' => 1, 'is_membership' => 1]);
    tensionFixture(UserHolon::class, ['IDuser' => $uid, 'IDholon' => $instance->getId(), 'active' => 1, 'is_membership' => 1]);
    $can = static fn(?int $id): bool => Tension::canCreateInOrganizationContext($oid, $id, $uid);
    $keys = static fn(array $data): array => array_column($data['permissionCatalog'], 'key');
    tensionExpect(in_array('CAN_CREATE_TENSION', $keys($organization->getHolonCreationEditorData((int)$root->getId(), (int)$source->getId())), true), 'Holon editor must expose tension rights.');
    tensionExpect(in_array('CAN_CREATE_TENSION', $keys($organization->getHolonTemplateEditorData((int)$root->getId())), true), 'Template editor must expose tension rights.');
    $catalog = array_column(Permission::getEditorCatalog([], []), null, 'key');
    tensionExpect(!empty($catalog['CAN_CREATE_TENSION']['isContextual']) && $catalog['CAN_CREATE_TENSION']['group'] === 'tensions', 'Tension rights stay contextual and available without an optional app.');
    tensionExpect($can((int)$source->getId()) && $can((int)$other->getId()) && $can(null)
        && count(Tension::loadCreationTargets($oid, $uid)) === 5, 'An unconfigured right must allow all valid destinations.');
    tensionExpect(Tension::canCreateInOrganizationContext($oid, (int)$other->getId(), $unassignedUid), 'Default access must include organization members without any holon assignment.');

    tensionExpect($organization->saveEditorPermissions((int)$source->getId(), ['member' => ['CAN_CREATE_TENSION' => ['self']]]), 'Save local tension rights through the shared editor path.');
    tensionExpect($can((int)$source->getId()) && !$can((int)$child->getId()) && !$can((int)$other->getId()) && !$can(null), 'Self scope must stay local and must not allow organization scope.');
    tensionExpect(!Tension::canCreateInOrganizationContext($oid, (int)$source->getId(), $unassignedUid)
        && count(Tension::loadCreationTargets($oid, $unassignedUid)) === 0, 'The first assignment must remove default access from members without a grant.');
    tensionExpect($organization->saveEditorPermissions((int)$source->getId(), ['member' => ['CAN_CREATE_TENSION' => ['self', 'descendants']]]), 'Save descendant scope.');
    tensionExpect($can((int)$child->getId()) && !$can((int)$other->getId()), 'Descendant permission must allow a space without direct membership.');
    tensionExpect($organization->saveEditorPermissions((int)$template->getId(), ['member' => ['CAN_CREATE_TENSION' => ['self']]]), 'Save template tension rights through the shared editor path.');
    tensionExpect($can((int)$instance->getId()) && !$can((int)$template->getId()), 'Template rights must apply to instances; templates are not creation destinations.');
    $targetIds = [];
    foreach (Tension::loadCreationTargets($oid, $uid) as $target) { $targetIds[] = (int)$target->getId(); }
    tensionExpect(in_array((int)$child->getId(), $targetIds, true) && in_array((int)$instance->getId(), $targetIds, true)
        && !in_array((int)$other->getId(), $targetIds, true) && !in_array((int)$template->getId(), $targetIds, true), 'Selector targets must agree with effective permissions.');

    tensionExpect($organization->saveEditorPermissions((int)$source->getId(), ['collective' => ['CAN_CREATE_TENSION' => ['organization']]]), 'Save collective-only tension rights.');
    tensionExpect(!$can((int)$source->getId()) && !$can((int)$other->getId()), 'Collective-only rights must not grant personal creation.');
    tensionExpect($organization->saveEditorPermissions((int)$source->getId(), ['member' => ['CAN_CREATE_TENSION' => ['organization_root']]]), 'Save organization root scope.');
    tensionExpect($can(null) && $can((int)$root->getId()) && !$can((int)$other->getId()), 'Organization root scope must authorize only the organization target.');
    tensionExpect($organization->saveEditorPermissions((int)$source->getId(), ['member' => ['CAN_CREATE_TENSION' => ['organization']]]), 'Save organization scope.');
    tensionExpect($can(null) && $can((int)$other->getId()), 'Organization scope must allow both organization and holon targets.');
    $tension = new Tension();
    tensionExpect(!empty($tension->createInOrganizationContext($oid, null, $uid, ['title' => 'Organization tension', 'description' => 'Permitted organization target.'])['status']), 'Authorized organization-wide tension must save.');
    tensionExpect((int)$tension->get('IDholon') === 0 && (int)$tension->get('IDorganization') === $oid, 'Organization scope must retain its attachment.');
    $foreign = tensionFixture(Organization::class, ['name' => 'Foreign organization', 'shortname' => 'foreign-' . $nonce]);
    foreach ([['visible' => 0], ['active' => 0], ['archived_at' => '2026-11-02 12:00:00'], ['IDtypeholon' => 3], ['IDorganization' => $foreign->getId()]] as $fields) {
        $invalid = $makeSpace($fields);
        tensionExpect(!$can((int)$invalid->getId()), 'Organization permission cannot authorize an invalid destination.');
    }
    tensionExpect(!Tension::canCreateInOrganizationContext($oid, (int)$other->getId(), 0), 'Anonymous creation must fail.');
    tensionExpect($organization->saveEditorPermissions((int)$source->getId(), []), 'Remove local rights.');
    tensionExpect(!$can((int)$other->getId()), 'A remaining template assignment must keep default access restricted.');
    tensionExpect($organization->saveEditorPermissions((int)$template->getId(), []), 'Remove the final template assignment.');
    tensionExpect($can((int)$other->getId()) && $can(null)
        && Tension::canCreateInOrganizationContext($oid, (int)$source->getId(), $unassignedUid), 'Removing the last assignment must restore default access for all members.');
    echo "tension_permissions_test: OK (default access, first and last assignment, editors, scopes, template inheritance, creation, rollback)\n";
} finally {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    DbObject::$preload = [];
}
