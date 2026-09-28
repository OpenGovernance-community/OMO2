<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';

use dbObject\{DbObject, Organization, OrganizationParcours, Parcours, ParcoursParcours, ParcoursMission, Mission, User, UserOrganization, UserMission, Permission, Holon, HolonPermission, UserHolon};

function parcoursDeletionCheck(bool $ok, string $message): void
{
    if (!$ok) throw new RuntimeException($message);
}
function parcoursDeletionSave($object): void
{
    $result = $object->save();
    parcoursDeletionCheck(!empty($result['status']), get_class($object) . ': ' . json_encode($result) . ' ' . json_encode(DbObject::getLastDbError()));
}
function parcoursDeletionAttach(int $organization, int $parcours): void
{
    $result = OrganizationParcours::attachParcoursToOrganization($organization, $parcours, ['everybody' => true, 'anonymous' => true]);
    parcoursDeletionCheck(!empty($result['status']), $result['message'] ?? 'Attach failed');
}
function parcoursDeletionFixture(int $organization, bool $pack = false): Parcours
{
    $p = new Parcours();
    $p->set('title', 'Deletion test ' . bin2hex(random_bytes(4)));
    $p->set('description', 'Content to preserve');
    $p->set('IDorganization', $organization);
    $p->set('ispublic', true);
    $p->set('isbasic', true);
    $p->set('ispack', $pack);
    parcoursDeletionSave($p);
    parcoursDeletionAttach($organization, (int)$p->getId());
    return $p;
}
function parcoursDeletionMission(Parcours $p): Mission
{
    $mission = new Mission();
    $mission->set('title', 'Preserved mission');
    $mission->set('resume', 'Preserved resume');
    $mission->set('html', '<p>Original learning content</p>');
    parcoursDeletionSave($mission);
    $link = new ParcoursMission();
    $link->set('IDparcours', $p->getId());
    $link->set('IDmission', $mission->getId());
    parcoursDeletionSave($link);
    return $mission;
}

$_SERVER['HTTP_HOST'] = 'omo.localtest.me';
$_SESSION['currentUser'] = 0;
$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $orgIds = [];
    $users = [];
    for ($i = 0; $i < 3; $i++) {
        $org = new Organization();
        $org->set('name', 'Parcours deletion fixture ' . $i);
        parcoursDeletionSave($org);
        $orgIds[] = (int)$org->getId();
        $user = new User();
        $user->set('email', 'parcours-delete-' . bin2hex(random_bytes(8)) . '@example.invalid');
        $user->set('active', true);
        parcoursDeletionSave($user);
        $users[] = (int)$user->getId();
        $membership = new UserOrganization();
        $membership->set('IDuser', $user->getId());
        $membership->set('IDorganization', $org->getId());
        $membership->set('active', true);
        parcoursDeletionSave($membership);
    }
    [$owner, $consumer, $newcomer] = $orgIds;
    $root = new Holon();
    $root->set('name', 'Deletion permissions fixture');
    $root->set('IDorganization', $owner);
    $root->set('IDtypeholon', 2);
    $root->set('active', true);
    $root->set('visible', true);
    parcoursDeletionSave($root);
    $rootMembership = new UserHolon();
    $rootMembership->set('IDuser', $users[0]);
    $rootMembership->set('IDholon', $root->getId());
    $rootMembership->set('active', true);
    $rootMembership->set('is_membership', true);
    parcoursDeletionSave($rootMembership);
    $can = static fn($key) => HolonPermission::userHasPermissionForHolonContext($users[0], $owner, $key, $root->getId());
    parcoursDeletionCheck(!$can('CAN_DELETE_PARCOURS'), 'Unassigned delete must be denied even to organization members');
    $grant = new HolonPermission();
    $grant->set('IDholon', $root->getId());
    $grant->set('IDpermission', Permission::findByKey('CAN_CREATE_PARCOURS')->getId());
    $grant->set('member_type', HolonPermission::MEMBER_TYPE_MEMBER);
    $grant->set('range', HolonPermission::RANGE_ORGANIZATION);
    parcoursDeletionSave($grant);
    parcoursDeletionCheck($can('CAN_CREATE_PARCOURS') && !$can('CAN_DELETE_PARCOURS'), 'Create cannot grant deletion');
    $grant->set('IDpermission', Permission::findByKey('CAN_DELETE_PARCOURS')->getId());
    parcoursDeletionSave($grant);
    parcoursDeletionCheck($can('CAN_DELETE_PARCOURS'), 'An explicit deletion grant is effective');
    $unused = parcoursDeletionFixture($owner);
    $unusedMission = parcoursDeletionMission($unused);
    $unusedId = (int)$unused->getId();
    parcoursDeletionCheck($unused->previewDeleteForOrganization($owner)['action'] === 'delete', 'Unused parcours can be deleted');
    $result = $unused->deleteForOrganization($owner);
    parcoursDeletionCheck(!empty($result['status']) && $result['action'] === 'delete', 'Unused parcours deletion succeeds');
    parcoursDeletionCheck(!(new Parcours())->load($unusedId, true), 'Unused parcours is physically removed');
    parcoursDeletionCheck(!(new Mission())->load((int)$unusedMission->getId(), true), 'Unused mission is cleaned up');

    $shared = parcoursDeletionFixture($owner);
    $mission = parcoursDeletionMission($shared);
    $sharedId = (int)$shared->getId();
    parcoursDeletionAttach($consumer, $sharedId);
    parcoursDeletionCheck($shared->previewDeleteForOrganization($owner)['action'] === 'archive', 'An importing organization counts as usage even without progress');
    $result = $shared->deleteForOrganization($owner);
    parcoursDeletionCheck(!empty($result['status']) && $result['action'] === 'archive', 'Shared parcours is retired');
    $shared->load($sharedId, true);
    parcoursDeletionCheck($shared->get('isarchived') && !$shared->get('ispublic') && !$shared->get('isbasic'), 'Retired parcours is unpublished');
    parcoursDeletionCheck($shared->get('description') === 'Content to preserve', 'Retirement preserves content');
    parcoursDeletionCheck((new Mission())->load((int)$mission->getId(), true), 'Shared missions remain intact');
    parcoursDeletionCheck(!Parcours::loadImportableForOrganization($newcomer, $sharedId), 'Retired parcours cannot be imported');
    parcoursDeletionCheck(!in_array($sharedId, array_column(Parcours::fetchImportableForOrganization($newcomer), 'id')), 'Retired parcours disappears from import lists');
    parcoursDeletionCheck(empty(OrganizationParcours::attachParcoursToOrganization($newcomer, $sharedId)['status']), 'Direct attachment cannot bypass retirement');
    parcoursDeletionCheck(!empty(OrganizationParcours::resolveAccessContext($consumer, $sharedId, $users[1])['canView']), 'Existing organization members retain access');
    parcoursDeletionCheck(empty(OrganizationParcours::resolveAccessContext($consumer, $sharedId, $users[2])['canView']), 'A public link does not expose the retired parcours to another organization');
    parcoursDeletionCheck(empty(Parcours::resolveBasicCatalogAccessContext($sharedId, $users[2])['canView']), 'Direct basic catalog access is blocked for new users');
    parcoursDeletionCheck(!in_array($sharedId, array_column(Parcours::fetchBasicCatalogWithProgress($users[2]), 'id')), 'Basic catalog hides retired parcours');
    $shared->set('title', 'Unauthorized rewrite');
    parcoursDeletionCheck(empty($shared->save()['status']), 'Retired content is read-only');
    $shared->load($sharedId, true);
    parcoursDeletionCheck($shared->get('title') !== 'Unauthorized rewrite', 'Read-only protection preserves persisted title');

    $started = parcoursDeletionFixture($owner);
    $startedMission = parcoursDeletionMission($started);
    $progress = new UserMission();
    $progress->set('IDuser', $users[0]);
    $progress->set('IDparcours', $started->getId());
    $progress->set('IDmission', $startedMission->getId());
    $progress->set('done', new DateTimeImmutable());
    parcoursDeletionSave($progress);
    parcoursDeletionCheck($started->previewDeleteForOrganization($owner)['action'] === 'archive', 'Owner organization learner activity also prevents deletion');
    parcoursDeletionCheck($started->deleteForOrganization($owner)['action'] === 'archive', 'Learner progress forces retirement');
    parcoursDeletionCheck((new UserMission())->load((int)$progress->getId(), true), 'Progress survives retirement');
    parcoursDeletionCheck(!empty(Parcours::resolveBasicCatalogAccessContext((int)$started->getId(), $users[0])['canView']), 'Existing individual learner retains direct access');
    parcoursDeletionCheck(in_array((int)$started->getId(), array_column(Parcours::fetchBasicCatalogWithProgress($users[0]), 'id')), 'Existing learner can still find the retired parcours');

    $pack = parcoursDeletionFixture($owner, true);
    $child = parcoursDeletionFixture($owner);
    parcoursDeletionMission($child);
    parcoursDeletionCheck(!empty(ParcoursParcours::attachChildToParent($pack->getId(), $child->getId())['status']), 'Pack fixture created');
    parcoursDeletionAttach($consumer, (int)$pack->getId());
    parcoursDeletionCheck($child->previewDeleteForOrganization($owner)['action'] === 'archive', 'Pack exposure prevents deletion without a direct import');
    parcoursDeletionCheck($child->deleteForOrganization($owner)['action'] === 'archive', 'Pack child can be retired by its owner');
    parcoursDeletionCheck(OrganizationParcours::loadForOrganizationParcours($consumer, (int)$child->getId()) !== null, 'Existing pack consumers retain a stable link');
    parcoursDeletionAttach($newcomer, (int)$pack->getId());
    parcoursDeletionCheck(empty(OrganizationParcours::resolveAccessContext($newcomer, (int)$child->getId(), $users[2])['canView']), 'Importing an old pack does not bypass retirement');
    $newPackChildren = Parcours::fetchPackChildrenForOrganizationWithProgress($newcomer, (int)$pack->getId(), $users[2], true, true);
    parcoursDeletionCheck(!in_array((int)$child->getId(), array_column($newPackChildren, 'id')), 'Retired pack child is hidden even from management lists in new organizations');
    $oldPackChildren = Parcours::fetchPackChildrenForOrganizationWithProgress($consumer, (int)$pack->getId(), $users[1], true);
    parcoursDeletionCheck(in_array((int)$child->getId(), array_column($oldPackChildren, 'id')), 'Existing pack consumers still see the child');

    $startedPack = parcoursDeletionFixture($owner, true);
    $startedChild = parcoursDeletionFixture($owner);
    $packMission = parcoursDeletionMission($startedChild);
    parcoursDeletionCheck(!empty(ParcoursParcours::attachChildToParent($startedPack->getId(), $startedChild->getId())['status']), 'Pack learner fixture created');
    $packProgress = new UserMission();
    $packProgress->set('IDuser', $users[2]);
    $packProgress->set('IDparcours', $startedChild->getId());
    $packProgress->set('IDmission', $packMission->getId());
    $packProgress->set('done', new DateTimeImmutable());
    parcoursDeletionSave($packProgress);
    parcoursDeletionCheck($startedPack->deleteForOrganization($owner)['action'] === 'archive', 'Activity in a child protects its pack');
    parcoursDeletionCheck(!empty(Parcours::resolveBasicCatalogAccessContext((int)$startedPack->getId(), $users[2])['canView']), 'An individual learner retains access to a retired pack');
    parcoursDeletionCheck(in_array((int)$startedPack->getId(), array_column(Parcours::fetchBasicCatalogWithProgress($users[2]), 'id')), 'An individual learner can still find the retired pack');

    $foreign = parcoursDeletionFixture($owner);
    parcoursDeletionAttach($consumer, (int)$foreign->getId());
    parcoursDeletionCheck($foreign->deleteForOrganization($consumer)['action'] === 'detach', 'Non-owner only detaches');
    $foreign->load((int)$foreign->getId(), true);
    parcoursDeletionCheck(!$foreign->get('isarchived') && $foreign->get('ispublic'), 'Detaching does not retire another organization content');
    parcoursDeletionCheck(Permission::findByKey('CAN_DELETE_PARCOURS') !== null && Permission::requiresExplicitAssignment('CAN_DELETE_PARCOURS'), 'Deletion permission exists and requires explicit assignment');
    $helpKeys = array_column(array_filter(Permission::getEditorCatalog(), static fn($item) => $item['group'] === 'help'), 'key');
    parcoursDeletionCheck(array_slice($helpKeys, -3) === ['CAN_CREATE_PARCOURS', 'CAN_EDIT_PARCOURS', 'CAN_DELETE_PARCOURS'], 'Deletion appears next to other parcours actions');
    echo "parcours_deletion_test: OK (unused, shared, learner progress, private retirement, packs, read-only, detach, permission, rollback)\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
    DbObject::$preload = [];
}
