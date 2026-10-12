<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';

use dbObject\{DbObject, ArrayOrganization, Holon, Authority};

function delegationGuardCheck($condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
function delegationGuardSave($object): void {
    $result = $object->save();
    delegationGuardCheck(!empty($result['status']), (string)($result['text'] ?? 'Fixture save failed'));
}

$organizations = new ArrayOrganization();
$organizations->load();
$organization = null;
$root = null;
foreach ($organizations as $candidate) {
    $candidateRoot = $candidate->getStructuralRootHolon();
    if ($candidateRoot instanceof Holon) { $organization = $candidate; $root = $candidateRoot; break; }
}
delegationGuardCheck($organization !== null, 'A local organization is required');
delegationGuardCheck($root instanceof Holon, 'A structural root is required');

$_SESSION['currentUser'] = 0;
$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $source = new Holon();
    $source->set('name', 'Delegation guard source');
    $source->set('IDholon_org', $root->getId());
    $source->set('IDholon_parent', $root->getId());
    $source->set('IDtypeholon', 1);
    delegationGuardSave($source);
    $target = new Holon();
    $target->set('name', 'Delegation guard target');
    $target->set('IDholon_org', $root->getId());
    $target->set('IDholon_parent', $source->getId());
    $target->set('IDtypeholon', 1);
    delegationGuardSave($target);
    $group = new Holon();
    foreach (['name' => 'Delegation guard group', 'IDholon_org' => $root->getId(), 'IDholon_parent' => $source->getId(), 'IDtypeholon' => 3] as $field => $value) $group->set($field, $value);
    delegationGuardSave($group);
    $nestedGroup = new Holon();
    foreach (['name' => 'Delegation guard nested group', 'IDholon_org' => $root->getId(), 'IDholon_parent' => $group->getId(), 'IDtypeholon' => 3] as $field => $value) $nestedGroup->set($field, $value);
    delegationGuardSave($nestedGroup);
    $target->set('IDholon_parent', $nestedGroup->getId());
    delegationGuardSave($target);
    $other = new Holon();
    $other->set('name', 'Delegation guard other');
    $other->set('IDholon_org', $root->getId());
    $other->set('IDholon_parent', $source->getId());
    $other->set('IDtypeholon', 1);
    delegationGuardSave($other);

    $authority = new Authority();
    $authority->set('IDholon', $source->getId());
    $authority->set('label', 'Delegation guard authority');
    $authority->set('is_local', true);
    delegationGuardSave($authority);
    $localChild = new Authority();
    $localChild->set('IDholon', $source->getId());
    $localChild->set('IDauthority_parent', $authority->getId());
    $localChild->set('label', 'Delegation guard local child');
    delegationGuardSave($localChild);
    $remoteGrandchild = new Authority();
    $remoteGrandchild->set('IDholon', $other->getId());
    $remoteGrandchild->set('IDauthority_parent', $localChild->getId());
    $remoteGrandchild->set('label', 'Delegation guard remote grandchild');
    delegationGuardSave($remoteGrandchild);

    $catalog = Authority::getEditorCatalogForOrganization($organization->getId());
    $catalogById = array_column($catalog, null, 'id');
    delegationGuardCheck(!empty($catalogById[$authority->getId()]['hasExternalDescendant']), 'The editor did not detect the delegated branch');
    delegationGuardCheck(!empty($catalogById[$localChild->getId()]['hasExternalDescendant']), 'The editor did not detect the nested delegated branch');
    $blocked = $authority->delegateCompletelyToHolon($target);
    delegationGuardCheck(empty($blocked['status']) && str_contains((string)($blocked['text'] ?? ''), 'deja deleguee'), 'Complete delegation was not refused');
    delegationGuardCheck($authority->load($authority->getId(), true) && (int)$authority->get('IDholon') === (int)$source->getId() && !$authority->isShell(), 'A refused delegation changed the source authority');
    delegationGuardCheck($remoteGrandchild->load($remoteGrandchild->getId(), true) && (int)$remoteGrandchild->get('IDholon') === (int)$other->getId(), 'A refused delegation moved the delegated branch');

    $remoteGrandchild->set('IDholon', $source->getId());
    delegationGuardSave($remoteGrandchild);
    $catalog = Authority::getEditorCatalogForOrganization($organization->getId());
    $catalogById = array_column($catalog, null, 'id');
    delegationGuardCheck(empty($catalogById[$authority->getId()]['hasExternalDescendant']), 'The editor still blocks a local authority tree');
    $allowed = $authority->delegateCompletelyToHolon($target);
    delegationGuardCheck(!empty($allowed['status']), (string)($allowed['text'] ?? 'Complete delegation without external branches failed'));
    delegationGuardCheck($localChild->load($localChild->getId(), true) && (int)$localChild->get('IDholon') === (int)$target->getId(), 'The local child was not delegated');
    delegationGuardCheck($remoteGrandchild->load($remoteGrandchild->getId(), true) && (int)$remoteGrandchild->get('IDholon') === (int)$target->getId(), 'The local grandchild was not delegated');

    $delegatedRoot = new Authority();
    delegationGuardCheck($delegatedRoot->load((int)$allowed['authorityId']), 'The delegated authority must exist');
    $reversed = $delegatedRoot->applyDeletionPlan(['authority' => 'delete']);
    delegationGuardCheck(!empty($reversed['status']), 'Reversing a delegation must cross nested groups');
    delegationGuardCheck($authority->load($authority->getId(), true) && !$authority->isShell(), 'Reversal must restore the source shell');

    echo "authority_complete_delegation_guard_test: OK\n";
} finally {
    $pdo->rollBack();
    DbObject::$preload = [];
}
