<?php
declare(strict_types=1);
// Isolated organization; all data is rolled back after exercising the real save paths.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';

use dbObject\{DbObject, Organization, ArrayApplication, OrganizationApplication, Holon, Property, Permission, HolonPermission, User, UserOrganization, UserHolon};

function fallbackCheck(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
function fallbackSave($object): void {
    $result = $object->save();
    fallbackCheck(!empty($result['status']), 'Fixture save failed: ' . json_encode($result));
}
$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $organization = new Organization();
    $organization->set('name', 'Property fallback test');
    $organization->set('shortname', 'fallback-' . bin2hex(random_bytes(5)));
    $organization->set('interface_level', Organization::INTERFACE_LEVEL_EXPERT);
    $lexicon = Organization::getInitialLexicon();
    foreach (Property::TYPES as $type) $lexicon[$type]['enabled'] = true;
    $organization->setLexicon($lexicon);
    fallbackSave($organization);
    $applications = new ArrayApplication();
    $applications->load(['where' => [['field' => 'hash', 'value' => 'structure']]]);
    foreach ($applications as $application) {
        $enabled = new OrganizationApplication();
        $enabled->set('IDorganization', $organization->getId());
        $enabled->set('IDapplication', $application->getId());
        $enabled->set('active', true);
        fallbackSave($enabled);
    }
    $root = new Holon();
    $root->set('name', 'Root');
    $root->set('IDorganization', $organization->getId());
    $root->set('IDtypeholon', 4);
    $root->set('active', true);
    $root->set('visible', true);
    fallbackSave($root);
    $root->set('IDholon_org', $root->getId());
    fallbackSave($root);
    $makeHolon = static function (string $name, int $typeId) use ($root): Holon {
        $holon = new Holon();
        $holon->set('name', $name);
        $holon->set('IDtypeholon', $typeId);
        $holon->set('IDholon_org', $root->getId());
        $holon->set('IDholon_parent', $root->getId());
        $holon->set('active', true);
        $holon->set('visible', true);
        fallbackSave($holon);
        return $holon;
    };
    $circle = $makeHolon('Circle', 2);
    $elsewhere = $makeHolon('Elsewhere', 2);
    $template = $makeHolon('Template', 1);
    $template->set('templatename', 'Template');
    fallbackSave($template);
    $template->syncTemplateProperties(array_map(static fn ($type) => [
        'id' => 0, 'type' => $type, 'name' => 'Required ' . $type, 'formatId' => 1, 'value' => '', 'mandatory' => true,
    ], Property::TYPES), (int)$root->getId());
    $user = new User();
    $user->set('email', 'fallback-' . bin2hex(random_bytes(5)) . '@example.invalid');
    $user->set('active', true);
    fallbackSave($user);
    $membership = new UserOrganization();
    $membership->set('IDuser', $user->getId());
    $membership->set('IDorganization', $organization->getId());
    $membership->set('active', true);
    fallbackSave($membership);
    $role = new UserHolon();
    $role->set('IDuser', $user->getId());
    $role->set('IDholon', $circle->getId());
    $role->set('active', true);
    $role->set('is_membership', true);
    fallbackSave($role);
    $_SESSION['currentUser'] = (int)$user->getId();
    $_SESSION['currentOrganization'] = (int)$organization->getId();
    $_SERVER['HTTP_HOST'] = 'omo.localtest.me';
    $grant = static function (Holon $source, string $key, string $range, string $profile = HolonPermission::MEMBER_TYPE_MEMBER): HolonPermission {
        $assignment = new HolonPermission();
        $assignment->set('IDholon', $source->getId());
        $assignment->set('IDpermission', Permission::findByKey($key)->getId());
        $assignment->set('range', $range);
        $assignment->set('member_type', $profile);
        fallbackSave($assignment);
        return $assignment;
    };
    foreach (Property::TYPES as $type) {
        fallbackCheck($circle->isAllowed(Property::permissionKey('EDIT', $type), false), 'Unconfigured values follow the usual member default of holon editing');
        fallbackCheck(!$circle->isAllowed(Property::permissionKey('CREATE', $type), false), 'Creating definitions must stay explicit');
        fallbackCheck(!$circle->isAllowed(Property::permissionKey('DELETE', $type), false), 'Deleting definitions must stay explicit');
    }
    $grant($circle, 'CAN_ADD_HOLON', HolonPermission::RANGE_SELF);
    $genericEdit = $grant($circle, 'CAN_EDIT_HOLON', HolonPermission::RANGE_DIRECT_CHILDREN, HolonPermission::MEMBER_TYPE_ADMIN);
    $editor = $organization->getHolonCreationEditorData((int)$circle->getId());
    $templateData = array_values(array_filter($editor['templateCatalog'], static fn ($entry) => $entry['id'] === (int)$template->getId()))[0];
    fallbackCheck($editor['canCreate'] && !$editor['canAddHolonProperties'], 'Holon creation does not grant definition creation');
    fallbackCheck(count(array_filter($templateData['properties'], static fn ($property) => $property['canEditValue'])) === count(Property::TYPES), 'All mandatory values must be enabled during creation');
    fallbackCheck(!array_filter(Property::getTypeOptions($lexicon, $circle), static fn ($option) => $option['canEdit']), 'The same actor cannot edit existing values without holon edit rights');
    $payload = ['templateId' => $template->getId(), 'name' => 'Created with required values', 'properties' => $templateData['properties']];
    foreach ($payload['properties'] as &$property) $property['value'] = 'Filled ' . $property['type'];
    unset($property);
    $created = $organization->saveHolonEditorDefinition($payload, (int)$user->getId(), (int)$circle->getId());
    fallbackCheck(!empty($created['status']), 'Mandatory property creation must save: ' . json_encode($created));
    $child = new Holon();
    fallbackCheck($child->load((int)$created['holon']['id']), 'Created holon must exist');
    $stored = $child->getHolonEditorPropertyDefinitions();
    fallbackCheck(array_column($stored, 'value') === array_column($payload['properties'], 'value'), 'All submitted mandatory values must persist');
    foreach (Property::TYPES as $type) {
        $key = Property::permissionKey('EDIT', $type);
        fallbackCheck(!$child->isAllowed($key, false), 'Create fallback must not leak into later editing');
        fallbackCheck(!commonCurrentUserHasPermission($key, $child, (int)$organization->getId(), true), 'Cached edit checks must agree');
    }
    $genericEdit->set('member_type', HolonPermission::MEMBER_TYPE_MEMBER);
    fallbackSave($genericEdit);
    $existing = $organization->getHolonCreationEditorData(0, (int)$child->getId());
    fallbackCheck(count(array_filter($existing['holon']['properties'], static fn ($property) => $property['canEditValue'])) === count(Property::TYPES), 'Holon edit must enable unconfigured values');
    $payload['properties'] = $existing['holon']['properties'];
    $payload['properties'][0]['value'] = 'Updated value';
    $saved = $organization->saveHolonEditorDefinition($payload, (int)$user->getId(), 0, (int)$child->getId());
    fallbackCheck(!empty($saved['status']), 'Holon edit fallback must save values: ' . json_encode($saved));
    fallbackCheck($child->getHolonEditorPropertyDefinitions()[0]['value'] === 'Updated value', 'Edited value must persist');
    $key = 'CAN_EDIT_TYPE1_PROPERTIES';
    fallbackCheck(commonCurrentUserHasPermission($key, $child, (int)$organization->getId(), true), 'Cached fallback must allow the granted holon scope');

    // An assignment anywhere in this organization suppresses fallback, even outside the actor's scopes.
    $explicit = $grant($elsewhere, $key, HolonPermission::RANGE_SELF);
    fallbackCheck(!$circle->canEditPropertyValue('type1', true), 'An explicit assignment elsewhere must block creation fallback');
    fallbackCheck(!$child->isAllowed($key, false), 'An explicit assignment elsewhere must block edit fallback');
    fallbackCheck(!commonCurrentUserHasPermission($key, $child, (int)$organization->getId(), true), 'Cached checks must respect explicit assignment');
    $payload['properties'][0]['value'] = 'Forbidden value';
    fallbackCheck(empty($organization->saveHolonEditorDefinition($payload, (int)$user->getId(), 0, (int)$child->getId())['status']), 'Server must deny an explicitly restricted value');
    fallbackCheck(empty($organization->saveHolonEditorDefinition($payload, (int)$user->getId(), (int)$circle->getId())['status']), 'Server must also deny it when creating another holon');
    $explicit->set('IDholon', $circle->getId());
    $explicit->set('range', HolonPermission::RANGE_DIRECT_CHILDREN);
    fallbackSave($explicit);
    fallbackCheck($child->isAllowed($key, false) && $circle->canEditPropertyValue('type1', true), 'Explicit child scopes must cover both existing and prospective children');
    $childEditor = $organization->getHolonCreationEditorData((int)$circle->getId());
    $childTemplate = array_values(array_filter($childEditor['templateCatalog'], static fn ($entry) => $entry['id'] === (int)$template->getId()))[0];
    fallbackCheck($childTemplate['properties'][0]['canEditValue'], 'Prospective inherited property values must be editable');
    fallbackCheck(!empty($organization->saveHolonEditorDefinition($payload, (int)$user->getId(), (int)$circle->getId())['status']), 'The server must allow explicitly granted child property values');
    $explicit->set('range', HolonPermission::RANGE_SELF);
    fallbackSave($explicit);
    fallbackCheck($circle->isAllowed($key, false) && !$circle->canEditPropertyValue('type1', true), 'Parent-only property grants must not leak into child creation');
    fallbackCheck(empty($organization->saveHolonEditorDefinition($payload, (int)$user->getId(), (int)$circle->getId())['status']), 'The server must reject values reserved to the parent');
    $explicit->set('range', HolonPermission::RANGE_DIRECT_CHILDREN);
    $explicit->set('member_type', HolonPermission::MEMBER_TYPE_ADMIN);
    fallbackSave($explicit);
    fallbackCheck(!$child->isAllowed($key, false), 'Admin-only property grants must not fall back to a member holon grant');
    $explicit->delete();
    fallbackCheck($child->isAllowed($key, false), 'Removing the last assignment restores fallback');

    // Collective decisions use the deciding collective, never the submitting person's generic rights.
    fallbackCheck(!HolonPermission::holonHasCollectivePermissionForHolonContext($organization->getId(), $circle->getId(), $key, $child->getId()), 'No collective grant must stay denied');
    $grant($circle, 'CAN_EDIT_HOLON', HolonPermission::RANGE_DIRECT_CHILDREN, HolonPermission::MEMBER_TYPE_COLLECTIVE);
    $grant($circle, 'CAN_ADD_HOLON', HolonPermission::RANGE_SELF, HolonPermission::MEMBER_TYPE_COLLECTIVE);
    fallbackCheck(HolonPermission::holonHasCollectivePermissionForHolonContext($organization->getId(), $circle->getId(), $key, $child->getId()), 'Collective edit fallback must work');
    fallbackCheck(HolonPermission::userHasCollectivePermissionForHolonContext($user->getId(), $organization->getId(), $key, $child->getId()), 'Collective action availability must use the same fallback');
    fallbackCheck(HolonPermission::holonHasCollectivePermissionForHolonContext($organization->getId(), $circle->getId(), $key, $circle->getId(), true), 'Collective create fallback must use the parent context');
    $explicit = $grant($elsewhere, $key, HolonPermission::RANGE_SELF);
    fallbackCheck(!HolonPermission::holonHasCollectivePermissionForHolonContext($organization->getId(), $circle->getId(), $key, $child->getId()), 'Even a personal assignment elsewhere suppresses collective fallback');
    fallbackCheck(!HolonPermission::userHasCollectivePermissionForHolonContext($user->getId(), $organization->getId(), $key, $child->getId()), 'Collective action availability must respect explicit assignments');
    $explicit->delete();
    // Explicit collective grants use the same prospective child as personal grants.
    $collectiveProperty = $grant($circle, $key, HolonPermission::RANGE_DIRECT_CHILDREN, HolonPermission::MEMBER_TYPE_COLLECTIVE);
    $collectiveEditor = $organization->getHolonCreationEditorData((int)$circle->getId(), 0, true, (int)$circle->getId());
    $collectiveTemplate = array_values(array_filter($collectiveEditor['templateCatalog'], static fn ($entry) => $entry['id'] === (int)$template->getId()))[0];
    fallbackCheck($collectiveTemplate['properties'][0]['canEditValue'], 'PV collective grants must enable the future child property');
    fallbackCheck(!empty($organization->saveHolonEditorDefinition($payload, (int)$user->getId(), (int)$circle->getId(), 0, true, (int)$circle->getId())['status']), 'Collective creation must pass the same server checks');
    $collectiveProperty->set('range', HolonPermission::RANGE_SELF);
    fallbackSave($collectiveProperty);
    fallbackCheck(!HolonPermission::holonHasCollectivePermissionForHolonContext($organization->getId(), $circle->getId(), $key, $circle->getId(), true), 'Collective rights on the parent alone must not cover its future child');
    fallbackCheck(empty($organization->saveHolonEditorDefinition($payload, (int)$user->getId(), (int)$circle->getId(), 0, true, (int)$circle->getId())['status']), 'Collective server checks must reject parent-only values');
    $collectiveProperty->delete();

    // Definition creation and deletion must also use the future element context.
    $createKey = Property::permissionKey('CREATE', 'type1');
    $deleteKey = Property::permissionKey('DELETE', 'type1');
    $createGrant = $grant($circle, $createKey, HolonPermission::RANGE_DIRECT_CHILDREN);
    $deleteGrant = $grant($circle, $deleteKey, HolonPermission::RANGE_DIRECT_CHILDREN);
    $definitionPayload = $payload;
    $definitionPayload['properties'][] = ['id' => 0, 'name' => 'Local definition', 'type' => 'type1', 'formatId' => 1, 'value' => 'Local value'];
    $definitionEditor = $organization->getHolonCreationEditorData((int)$circle->getId());
    $definitionType = array_values(array_filter($definitionEditor['propertyTypes'], static fn ($entry) => $entry['id'] === 'type1'))[0];
    fallbackCheck($definitionEditor['canAddHolonProperties'] && $definitionType['canCreate'] && $definitionType['canDelete'], 'Definition controls must respect child scopes before saving');
    fallbackCheck(!empty($organization->saveHolonEditorDefinition($definitionPayload, (int)$user->getId(), (int)$circle->getId())['status']), 'The server must allow a definition granted on future children');
    $createGrant->set('range', HolonPermission::RANGE_SELF);
    $deleteGrant->set('range', HolonPermission::RANGE_SELF);
    fallbackSave($createGrant);
    fallbackSave($deleteGrant);
    $definitionEditor = $organization->getHolonCreationEditorData((int)$circle->getId());
    $definitionType = array_values(array_filter($definitionEditor['propertyTypes'], static fn ($entry) => $entry['id'] === 'type1'))[0];
    fallbackCheck(!$definitionEditor['canAddHolonProperties'] && !$definitionType['canCreate'] && !$definitionType['canDelete'], 'Parent-only definition rights must stay unavailable on future children');
    fallbackCheck(empty($organization->saveHolonEditorDefinition($definitionPayload, (int)$user->getId(), (int)$circle->getId())['status']), 'The server must reject parent-only definition creation');
    $createGrant->delete();
    $deleteGrant->delete();

    // Every scope must agree before and after saving, including transparent groups.
    $group = $makeHolon('Group', 3);
    $group->set('IDholon_parent', $circle->getId());
    fallbackSave($group);
    $nestedCircle = $makeHolon('Nested circle', 2);
    $nestedCircle->set('IDholon_parent', $group->getId());
    fallbackSave($nestedCircle);
    $ranges = [HolonPermission::RANGE_SELF, HolonPermission::RANGE_DIRECT_CHILDREN,
        HolonPermission::RANGE_DESCENDANTS, HolonPermission::RANGE_PARENT_CIRCLE,
        HolonPermission::RANGE_PARENT_CIRCLE_ELEMENTS, HolonPermission::RANGE_PARENT_CIRCLE_DESCENDANTS,
        HolonPermission::RANGE_ORGANIZATION_ROOT, HolonPermission::RANGE_ORGANIZATION];
    foreach ([HolonPermission::MEMBER_TYPE_MEMBER, HolonPermission::MEMBER_TYPE_COLLECTIVE] as $profile) {
        $scopeGrant = $grant($circle, $key, HolonPermission::RANGE_SELF, $profile);
        foreach ($ranges as $range) {
            $scopeGrant->set('range', $range);
            fallbackSave($scopeGrant);
            foreach ([$root, $circle, $group, $nestedCircle, $elsewhere] as $parent) {
                $collective = $profile === HolonPermission::MEMBER_TYPE_COLLECTIVE;
                $before = $collective
                    ? HolonPermission::holonHasCollectivePermissionForHolonContext($organization->getId(), $circle->getId(), $key, $parent->getId(), true)
                    : $parent->canEditPropertyValue('type1', true);
                $prospective = $makeHolon('Scope comparison child', 1);
                $prospective->set('IDholon_parent', $parent->getId());
                fallbackSave($prospective);
                $after = $collective
                    ? HolonPermission::holonHasCollectivePermissionForHolonContext($organization->getId(), $circle->getId(), $key, $prospective->getId())
                    : $prospective->canEditPropertyValue('type1');
                fallbackCheck($before === $after, $profile . ' ' . $range . ': future child rights must match saved rights under ' . $parent->getId());
            }
        }
        $scopeGrant->delete();
    }
    $locked = $template->getTemplatePropertyDefinitions();
    $locked[0]['locked'] = true;
    $template->syncTemplateProperties($locked, (int)$root->getId());
    $lockedEditor = $organization->getHolonCreationEditorData((int)$circle->getId());
    $lockedTemplate = array_values(array_filter($lockedEditor['templateCatalog'], static fn ($entry) => $entry['id'] === (int)$template->getId()))[0];
    fallbackCheck(!$lockedTemplate['properties'][0]['canEditValue'], 'Inherited locks must still apply');
    fallbackCheck(empty($organization->saveHolonEditorDefinition($payload, (int)$user->getId(), (int)$circle->getId())['status']), 'Server must also enforce inherited locks');
    echo "property_value_fallback_integration_test: OK (all types, mandatory creation, edit, prospective scopes, definitions, cache, collective, groups, locks)\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
    DbObject::$preload = [];
}
