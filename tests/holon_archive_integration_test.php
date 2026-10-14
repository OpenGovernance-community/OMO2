<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';

use dbObject\{DbObject, Organization, Holon, ArrayHolon, HolonShareLink, User, UserOrganization, UserHolon, Application, OrganizationApplication, History};

function archiveCheck(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
}
function archiveFixture(string $class, array $values): DbObject {
    $object = new $class();
    foreach ($values as $field => $value) $object->set($field, $value);
    archiveCheck(!empty($object->save()['status']), 'Fixture failed: ' . $class);
    return $object;
}
function archiveReload(Holon $holon): void {
    archiveCheck((bool)$holon->load($holon->getId(), true), 'Holon unexpectedly deleted');
}

$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $nonce = bin2hex(random_bytes(6));
    $org = archiveFixture(Organization::class, ['name' => 'Archive test', 'shortname' => 'archive-' . $nonce]);
    $app = new Application();
    archiveCheck((bool)$app->load(['hash', 'structure']), 'Structure application missing');
    archiveFixture(OrganizationApplication::class, ['IDorganization' => $org->getId(), 'IDapplication' => $app->getId(), 'active' => 1]);
    $user = archiveFixture(User::class, ['email' => 'archive-' . $nonce . '@example.invalid', 'active' => 1]);
    archiveFixture(UserOrganization::class, ['IDuser' => $user->getId(), 'IDorganization' => $org->getId(), 'active' => 1, 'parameters' => ['isAdmin' => true]]);
    $root = archiveFixture(Holon::class, ['name' => 'Root', 'IDorganization' => $org->getId(), 'IDtypeholon' => 4, 'active' => 1, 'visible' => 1]);
    $root->set('IDholon_org', $root->getId()); $root->save();
    $make = static fn(string $name, int $type, Holon $parent, array $extra = []): Holon => archiveFixture(Holon::class,
        $extra + ['name' => $name, 'IDtypeholon' => $type, 'IDholon_parent' => $parent->getId(), 'IDholon_org' => $root->getId(), 'active' => 1, 'visible' => 1]);
    $parent = $make('Parent', 2, $root);
    $circle = $make('Circle', 2, $parent);
    $group = $make('Group', 3, $circle);
    $role = $make('Role', 1, $group, ['parameters' => ['custom' => 'preserved']]);
    $hidden = $make('Hidden template', 1, $circle, ['visible' => 0, 'templatename' => 'Local template']);
    $older = $make('Earlier archive', 1, $circle);
    $inactive = $make('Previously inactive', 1, $circle, ['active' => 0]);
    $assignment = archiveFixture(UserHolon::class, ['IDuser' => $user->getId(), 'IDholon' => $role->getId(), 'active' => 1, 'is_membership' => 1]);
    $_SESSION['currentUser'] = $user->getId(); $_SESSION['currentOrganization'] = $org->getId();
    archiveCheck(commonSetCurrentUserAdminMode(true, $org->getId()), 'Admin fixture unavailable');
    archiveCheck(!$root->canManageArchive(), 'Organization root must not be archivable');
    archiveCheck(!$group->canManageArchive(), 'Standalone group archive is outside the supported UI');
    archiveCheck(!$hidden->canManageArchive(), 'Templates cannot be archived independently');

    archiveCheck(!empty($older->changeArchiveState($org->getId(), false, 'reassign')['status']), 'Independent child archive failed');
    archiveReload($older);
    $olderBatch = $older->getParametersArray()['holon_archive']['batch'];
    $beforeHistory = History::getLatestOrganizationEntryId($org->getId());
    $result = $circle->changeArchiveState($org->getId(), false, 'reassign');
    archiveCheck(!empty($result['status']) && $result['count'] === 4, 'Circle must archive active descendants, including hidden templates: ' . json_encode($result));
    foreach ([$circle, $group, $role, $hidden] as $node) {
        archiveReload($node);
        archiveCheck(!$node->get('active') && $node->get('archived_at') instanceof DateTimeInterface, 'Archive fields missing');
        archiveCheck(!$node->canViewDetail() && !$node->canEdit(), 'Direct reads and edits must be denied');
    }
    archiveReload($older);
    archiveCheck($older->getParametersArray()['holon_archive']['batch'] === $olderBatch, 'Earlier archive provenance must survive');
    archiveCheck(!$older->canManageArchive(true), 'Cannot restore a child under an archived parent');
    archiveCheck(empty($older->changeArchiveState($org->getId(), true)['status']), 'Server must reject restoring a child under an archived parent');
    archiveCheck(History::getLatestOrganizationEntryId($org->getId()) > $beforeHistory, 'History must invalidate structure and permission caches');
    archiveCheck(count($parent->getChildren()) === 0, 'Current navigation must hide archived branches');
    $collection = new ArrayHolon();
    $collection->loadHydrated(['where' => [['field' => 'IDholon_org', 'value' => $root->getId()]]]);
    foreach ($collection as $node) archiveCheck(!in_array($node->getId(), [$circle->getId(), $group->getId(), $role->getId(), $hidden->getId(), $older->getId()]), 'Generic selectors must exclude archives');
    $collection->loadArchivesForOrganization($root->getId());
    $restorable = [];
    foreach ($collection as $node) if ($node->canManageArchive(true)) $restorable[] = (int)$node->getId();
    archiveCheck($restorable === [(int)$circle->getId()], 'Only the independently restorable archive root should be listed');
    archiveCheck((bool)$assignment->load($assignment->getId(), true) && $assignment->get('active'), 'Assignments must be preserved');
    $share = new HolonShareLink();
    foreach (['IDorganization' => $org->getId(), 'IDholon' => $circle->getId(), 'allow_structure' => 1, 'allow_people' => 1, 'active' => 1] as $field => $value) $share->set($field, $value);
    archiveCheck(!$share->canViewOrganization($org->getId()) && !$share->canViewHolon($role), 'Archived shared scopes must never fall back to the organization root');
    $rows = ArrayHolon::fetchStructureRows($root->getId());
    archiveCheck(!in_array($circle->getId(), array_column($rows, 'id')), 'Bulk structure rows must exclude archived navigation roots');

    $_SESSION['currentUser'] = 0;
    archiveCheck(empty($circle->changeArchiveState($org->getId(), true)['status']), 'Anonymous restoration must fail');
    $_SESSION['currentUser'] = $user->getId();
    archiveCheck(empty($circle->changeArchiveState($org->getId() + 999999, true)['status']), 'Cross-organization restoration must fail');
    $result = $circle->changeArchiveState($org->getId(), true);
    archiveCheck(!empty($result['status']) && $result['count'] === 4, 'Restoration failed: ' . json_encode($result));
    foreach ([$circle, $group, $role, $hidden] as $node) {
        archiveReload($node);
        archiveCheck((bool)$node->get('active') && $node->get('archived_at') === null, 'Restoration fields incorrect');
    }
    archiveCheck($role->getParametersArray() === ['custom' => 'preserved'], 'Restoration must preserve unrelated parameters and remove batch metadata');
    archiveReload($older); archiveReload($inactive);
    archiveCheck(!$older->get('active') && !$inactive->get('active'), 'Previously inactive children must remain inactive');
    archiveCheck($older->canManageArchive(true), 'An earlier archive becomes individually restorable again');
    archiveCheck(!empty($older->changeArchiveState($org->getId(), true)['status']), 'Earlier archive should restore independently');

    // A template still in use outside the subtree must not disappear underneath an active instance.
    $outside = $make('External instance', 1, $parent, ['IDholon_template' => $hidden->getId()]);
    $result = $circle->changeArchiveState($org->getId(), false, 'reassign');
    archiveCheck(($result['reason'] ?? '') === 'template_in_use', 'External template dependency must block the archive');
    archiveReload($circle);
    archiveCheck((bool)$circle->get('active'), 'Rejected archive must not modify any state');
    archiveCheck($outside->delete(), 'External fixture cleanup failed');

    $uniqueTemplate = $make('Unique template', 1, $parent, ['visible' => 0, 'templatename' => 'Unique role', 'unique' => 1]);
    $uniqueRole = $make('Unique role', 1, $parent, ['IDholon_template' => $uniqueTemplate->getId()]);
    archiveCheck(!empty($uniqueRole->changeArchiveState($org->getId(), false, 'reassign')['status']), 'Unique instance archive failed');
    $replacement = $make('Replacement', 1, $parent, ['IDholon_template' => $uniqueTemplate->getId()]);
    archiveCheck(($uniqueRole->changeArchiveState($org->getId(), true)['reason'] ?? '') === 'template_conflict', 'Restoration must respect unique templates created while archived');
    archiveReload($uniqueRole);
    archiveCheck(!$uniqueRole->get('active') && $uniqueRole->get('archived_at') instanceof DateTimeInterface, 'Restore conflict must roll back');
    archiveCheck($replacement->delete(), 'Replacement cleanup failed');
    archiveCheck(!empty($uniqueRole->changeArchiveState($org->getId(), true)['status']), 'Restore must succeed once the conflicting replacement is removed');

    archiveCheck(!empty($circle->changeArchiveState($org->getId(), false, 'reassign')['status']), 'Second archive failed');
    $result = $org->deleteHolonDefinition($parent->getId(), $user->getId(), false, 'reassign');
    archiveCheck(!empty($result['status']), 'Parent deletion failed: ' . json_encode($result));
    foreach ([$parent, $circle, $group, $role, $hidden, $older, $inactive] as $node) {
        archiveCheck(!(new Holon())->load($node->getId(), true), 'Parent deletion must include all archived and inactive descendants');
    }
    archiveCheck(!(new UserHolon())->load($assignment->getId(), true), 'Assignment cascade must still run on permanent deletion');
    echo "holon_archive_integration_test: OK\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}
