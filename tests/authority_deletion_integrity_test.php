<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';

use dbObject\{DbObject, ArrayOrganization, Holon, Property, HolonProperty, Authority, Rule, PropertyFormat};

function authorityDeletionCheck($condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
function authorityDeletionSave($object): void {
    $result = $object->save();
    authorityDeletionCheck(!empty($result['status']), (string)($result['text'] ?? 'Fixture save failed'));
}

$organizations = new ArrayOrganization();
$organizations->load(['limit' => 1]);
$organization = null;
foreach ($organizations as $candidate) { $organization = $candidate; break; }
authorityDeletionCheck($organization !== null, 'A local organization is required');
$root = $organization->getStructuralRootHolon();
authorityDeletionCheck($root instanceof Holon, 'A structural root is required');

$_SESSION['currentUser'] = 0;
$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $owner = new Holon();
    $owner->set('name', 'Authority deletion test owner');
    $owner->set('IDholon_org', $root->getId());
    $owner->set('IDholon_parent', $root->getId());
    $owner->set('IDtypeholon', 1);
    authorityDeletionSave($owner);

    $parent = new Authority();
    $parent->set('IDholon', $root->getId());
    $parent->set('label', 'Deletion test parent');
    authorityDeletionSave($parent);
    $victim = new Authority();
    $victim->set('IDholon', $owner->getId());
    $victim->set('IDauthority_parent', $parent->getId());
    $victim->set('label', 'Deletion test victim');
    authorityDeletionSave($victim);
    $child = new Authority();
    $child->set('IDholon', $owner->getId());
    $child->set('IDauthority_parent', $victim->getId());
    $child->set('label', 'Deletion test child');
    authorityDeletionSave($child);
    $instance = new Authority();
    $instance->set('IDholon', $owner->getId());
    $instance->set('IDauthority_template', $victim->getId());
    $instance->set('label', 'Deletion test template instance');
    authorityDeletionSave($instance);

    $rule = new Rule();
    $rule->set('IDauthority', $victim->getId());
    $rule->set('title', 'Deletion test rule');
    $rule->set('description', '<p>Keep this rule.</p>');
    $rule->set('scope', Rule::SCOPE_LOCAL);
    $rule->set('review_date', '2026-09-25');
    $rule->set('expiration_date', '2027-09-25');
    authorityDeletionSave($rule);

    $list = new Property();
    $list->set('name', 'Authority deletion list');
    $list->set('shortname', 'delete_auth_list');
    $list->set('IDholon_organization', $root->getId());
    $list->set('IDpropertyformat', PropertyFormat::FORMAT_LIST);
    $list->set('listitemtype', Property::LIST_ITEM_AUTHORITY);
    authorityDeletionSave($list);
    $ownerList = new HolonProperty();
    $ownerList->set('IDholon', $owner->getId());
    $ownerList->set('IDproperty', $list->getId());
    $ownerList->set('value', json_encode([$victim->getId(), $child->getId()]));
    $ownerList->set('active', true);
    authorityDeletionSave($ownerList);
    $rootList = new HolonProperty();
    $rootList->set('IDholon', $root->getId());
    $rootList->set('IDproperty', $list->getId());
    $rootList->set('value', json_encode([$victim->getId()]));
    $rootList->set('active', true);
    authorityDeletionSave($rootList);

    $htmlList = new Property();
    $htmlList->set('name', 'Authority deletion HTML list');
    $htmlList->set('shortname', 'delete_auth_html');
    $htmlList->set('IDholon_organization', $root->getId());
    $htmlList->set('IDpropertyformat', PropertyFormat::FORMAT_HTML_LIST);
    $htmlList->set('listitemtype', Property::LIST_ITEM_AUTHORITY);
    authorityDeletionSave($htmlList);
    $inactiveList = new HolonProperty();
    $inactiveList->set('IDholon', $owner->getId());
    $inactiveList->set('IDproperty', $htmlList->getId());
    $inactiveList->set('value', json_encode(['before' => '<p>Intro</p>', 'items' => [$victim->getId(), $child->getId()], 'after' => '']));
    $inactiveList->set('active', false);
    authorityDeletionSave($inactiveList);

    $result = $victim->applyDeletionPlan(['authority' => 'delete', 'children' => 'delete', 'rules' => 'delete']);
    authorityDeletionCheck(!empty($result['status']), (string)($result['text'] ?? 'Authority deletion failed'));
    authorityDeletionCheck(!(new Authority())->load($victim->getId(), true), 'The selected authority still exists');
    authorityDeletionCheck($child->load($child->getId(), true), 'The child authority was deleted');
    authorityDeletionCheck((int)$child->get('IDholon') === (int)$root->getId(), 'The child authority was not moved to the parent holon');
    authorityDeletionCheck((int)$child->get('IDauthority_parent') === (int)$parent->getId(), 'The child authority was not attached to the parent authority');
    authorityDeletionCheck($rule->load($rule->getId(), true), 'The associated rule was deleted');
    authorityDeletionCheck((int)$rule->get('IDauthority') === (int)$parent->getId(), 'The rule was not attached to the parent authority');
    authorityDeletionCheck($instance->load($instance->getId(), true), 'The template instance was deleted');
    authorityDeletionCheck((int)$instance->get('IDauthority_template') === 0 && !empty($instance->get('template_origin_lost')), 'The template instance kept a deleted source ID');
    authorityDeletionCheck($ownerList->load($ownerList->getId(), true), 'The owner list was deleted');
    authorityDeletionCheck(Property::listConversionParts($ownerList->get('value'), PropertyFormat::FORMAT_LIST)['items'] === [], 'The owner list kept a deleted or moved authority ID');
    authorityDeletionCheck($rootList->load($rootList->getId(), true), 'The root list was deleted');
    authorityDeletionCheck(Property::listConversionParts($rootList->get('value'), PropertyFormat::FORMAT_LIST)['items'] === [$child->getId()], 'The root list did not retain only the moved child');
    authorityDeletionCheck($inactiveList->load($inactiveList->getId(), true), 'The inactive list was deleted');
    authorityDeletionCheck(Property::listConversionParts($inactiveList->get('value'), PropertyFormat::FORMAT_HTML_LIST)['items'] === [], 'The inactive list kept the deleted authority ID');

    $secondVictim = new Authority();
    $secondVictim->set('IDholon', $owner->getId());
    $secondVictim->set('IDauthority_parent', $parent->getId());
    $secondVictim->set('label', 'Deletion test editor victim');
    authorityDeletionSave($secondVictim);
    $ownerList->set('value', json_encode([$secondVictim->getId()]));
    $ownerList->set('active', true);
    authorityDeletionSave($ownerList);
    $inactiveList->set('value', json_encode(['before' => '<p>Intro</p>', 'items' => [$secondVictim->getId()], 'after' => '']));
    $inactiveList->set('active', true);
    authorityDeletionSave($inactiveList);
    $definitions = [
        ['id' => $htmlList->getId(), 'formatId' => PropertyFormat::FORMAT_HTML_LIST, 'listItemType' => Property::LIST_ITEM_AUTHORITY],
        ['id' => $list->getId(), 'formatId' => PropertyFormat::FORMAT_LIST, 'listItemType' => Property::LIST_ITEM_AUTHORITY],
    ];
    $submitted = [
        $htmlList->getId() => json_encode(['before' => '<p>Intro</p>', 'items' => [$secondVictim->getId()], 'after' => '']),
        $list->getId() => json_encode([['id' => $secondVictim->getId(), 'delete' => true]]),
    ];
    $syncAuthorities = new ReflectionMethod($organization, 'syncSubmittedAuthorityPropertyValues');
    $args = [$owner, &$submitted, $definitions, 0, []];
    $syncResult = $syncAuthorities->invokeArgs($organization, $args);
    authorityDeletionCheck(!empty($syncResult['status']), (string)($syncResult['message'] ?? 'Editor authority deletion failed'));
    authorityDeletionCheck(Property::listConversionParts($submitted[$htmlList->getId()], PropertyFormat::FORMAT_HTML_LIST)['items'] === [], 'The editor would restore a deleted reference from an earlier property');
    $owner->syncEditorPropertyValues($submitted, $definitions);
    authorityDeletionCheck($inactiveList->load($inactiveList->getId(), true), 'The editor HTML list was deleted');
    authorityDeletionCheck(Property::listConversionParts($inactiveList->get('value'), PropertyFormat::FORMAT_HTML_LIST)['items'] === [], 'The editor restored a deleted authority reference');

    $localParent = new Authority();
    $localParent->set('IDholon', $owner->getId());
    $localParent->set('label', 'Deletion test local parent');
    $localParent->set('is_local', true);
    authorityDeletionSave($localParent);
    $localVictim = new Authority();
    $localVictim->set('IDholon', $owner->getId());
    $localVictim->set('IDauthority_parent', $localParent->getId());
    $localVictim->set('label', 'Deletion test local victim');
    authorityDeletionSave($localVictim);
    $localChild = new Authority();
    $localChild->set('IDholon', $owner->getId());
    $localChild->set('IDauthority_parent', $localVictim->getId());
    $localChild->set('label', 'Deletion test local child');
    authorityDeletionSave($localChild);
    $localRule = new Rule();
    $localRule->set('IDauthority', $localVictim->getId());
    $localRule->set('title', 'Deletion test local rule');
    $localRule->set('description', '<p>Keep this local rule.</p>');
    $localRule->set('scope', Rule::SCOPE_LOCAL);
    $localRule->set('review_date', '2026-09-25');
    $localRule->set('expiration_date', '2027-09-25');
    authorityDeletionSave($localRule);
    $localResult = $localVictim->applyDeletionPlan(['authority' => 'delete', 'children' => 'delete', 'rules' => 'delete']);
    authorityDeletionCheck(!empty($localResult['status']), (string)($localResult['text'] ?? 'Local authority deletion failed'));
    authorityDeletionCheck($localChild->load($localChild->getId(), true), 'The local child authority was deleted');
    authorityDeletionCheck((int)$localChild->get('IDholon') === (int)$owner->getId() && (int)$localChild->get('IDauthority_parent') === (int)$localParent->getId(), 'The local child did not stay with its parent authority');
    authorityDeletionCheck($localRule->load($localRule->getId(), true) && (int)$localRule->get('IDauthority') === (int)$localParent->getId(), 'The local rule did not stay with its parent authority');

    $rootless = new Authority();
    $rootless->set('IDholon', $owner->getId());
    $rootless->set('label', 'Deletion test authority without parent');
    $rootless->set('is_local', true);
    authorityDeletionSave($rootless);
    $rootlessChild = new Authority();
    $rootlessChild->set('IDholon', $owner->getId());
    $rootlessChild->set('IDauthority_parent', $rootless->getId());
    $rootlessChild->set('label', 'Deletion test child without grandparent');
    authorityDeletionSave($rootlessChild);
    $rootlessRule = new Rule();
    $rootlessRule->set('IDauthority', $rootless->getId());
    $rootlessRule->set('title', 'Deletion test rule without parent authority');
    $rootlessRule->set('description', '<p>Keep this rule in the parent holon.</p>');
    $rootlessRule->set('scope', Rule::SCOPE_LOCAL);
    $rootlessRule->set('review_date', '2026-09-25');
    $rootlessRule->set('expiration_date', '2027-09-25');
    authorityDeletionSave($rootlessRule);
    $rootlessResult = $rootless->applyDeletionPlan(['authority' => 'delete', 'children' => 'delete', 'rules' => 'delete']);
    authorityDeletionCheck(!empty($rootlessResult['status']), (string)($rootlessResult['text'] ?? 'Rootless authority deletion failed'));
    authorityDeletionCheck($rootlessChild->load($rootlessChild->getId(), true) && (int)$rootlessChild->get('IDholon') === (int)$root->getId() && (int)$rootlessChild->get('IDauthority_parent') === 0, 'A child without a parent authority was not preserved in the parent holon');
    authorityDeletionCheck($rootlessRule->load($rootlessRule->getId(), true) && (int)$rootlessRule->get('IDholon') === (int)$root->getId() && (int)$rootlessRule->get('IDauthority') === 0, 'A rule without a parent authority was not preserved in the parent holon');

    $delegatedHolon = new Holon();
    $delegatedHolon->set('name', 'Authority deletion test delegated holon');
    $delegatedHolon->set('IDholon_org', $root->getId());
    $delegatedHolon->set('IDholon_parent', $root->getId());
    $delegatedHolon->set('IDtypeholon', 1);
    authorityDeletionSave($delegatedHolon);
    $shell = new Authority();
    $shell->set('IDholon', $root->getId());
    $shell->set('label', 'Deletion test shell');
    $shell->set('is_shell', true);
    authorityDeletionSave($shell);
    $copy = new Authority();
    $copy->set('IDholon', $delegatedHolon->getId());
    $copy->set('IDauthority_parent', $shell->getId());
    $copy->set('label', 'Deletion test delegated copy');
    authorityDeletionSave($copy);
    $sourceList = new HolonProperty();
    $sourceList->set('IDholon', $delegatedHolon->getId());
    $sourceList->set('IDproperty', $list->getId());
    $sourceList->set('value', json_encode([$copy->getId()]));
    $sourceList->set('active', true);
    authorityDeletionSave($sourceList);
    $rootList->set('value', json_encode([$child->getId(), $copy->getId()]));
    authorityDeletionSave($rootList);
    $transferResult = Authority::reassignForHolonDeletion($delegatedHolon, $root);
    authorityDeletionCheck(!empty($transferResult['status']), (string)($transferResult['text'] ?? 'Delegated authority transfer failed'));
    authorityDeletionCheck(!(new Authority())->load($copy->getId(), true), 'The delegated copy was not removed');
    authorityDeletionCheck($rootList->load($rootList->getId(), true), 'The root list disappeared after delegated copy removal');
    authorityDeletionCheck(Property::listConversionParts($rootList->get('value'), PropertyFormat::FORMAT_LIST)['items'] === [$child->getId(), $shell->getId()], 'The root list kept the deleted copy ID');
    authorityDeletionCheck($sourceList->load($sourceList->getId(), true) && empty($sourceList->get('active')), 'The source list kept the deleted copy ID');

    echo "authority_deletion_integrity_test: OK\n";
} finally {
    $pdo->rollBack();
    DbObject::$preload = [];
}
