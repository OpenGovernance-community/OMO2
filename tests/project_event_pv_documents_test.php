<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';
require_once dirname(__DIR__) . '/omo/translations.php';
require_once dirname(__DIR__) . '/omo/api/projects/shared.php';

use dbObject\{DbObject, Organization, User, UserOrganization, Project, ProjectDocument, ArrayDocument, Document, Event, EventInvitation};

function projectPvCheck(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}
function projectPvSave($object): void
{
    $result = $object->save();
    projectPvCheck(!empty($result['status']), 'Fixture save failed: ' . json_encode($result));
}
function projectPvFixture(string $class, array $fields)
{
    $object = new $class();
    foreach ($fields as $field => $value) { $object->set($field, $value); }
    projectPvSave($object);
    return $object;
}

$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $nonce = 'project-pv-' . bin2hex(random_bytes(5));
    $org = projectPvFixture(Organization::class, ['name' => $nonce, 'shortname' => $nonce]);
    $oid = (int)$org->getId();
    $owner = projectPvFixture(User::class, ['email' => "$nonce-owner@example.invalid", 'active' => 1]);
    $member = projectPvFixture(User::class, ['email' => "$nonce-member@example.invalid", 'active' => 1]);
    foreach ([$owner, $member] as $user) {
        projectPvFixture(UserOrganization::class, ['IDorganization' => $oid, 'IDuser' => $user->getId(), 'active' => 1]);
    }
    $uid = (int)$member->getId();
    $_SERVER['HTTP_HOST'] = 'omo.localtest.me';
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid];
    $project = projectPvFixture(Project::class, ['IDorganization' => $oid, 'IDuser' => $owner->getId(),
        'title' => 'Project with meeting PV', 'status' => Project::STATUS_READY, 'active' => 1]);
    $otherProject = projectPvFixture(Project::class, ['IDorganization' => $oid,
        'title' => 'Other project', 'status' => Project::STATUS_READY, 'active' => 1]);
    $event = projectPvFixture(Event::class, ['IDorganization' => $oid, 'IDproject' => $project->getId(),
        'IDuser' => $owner->getId(), 'title' => 'Project meeting', 'status' => Event::STATUS_CONFIRMED,
        'start_at' => new DateTimeImmutable('+1 day'), 'end_at' => new DateTimeImmutable('+1 day +1 hour'), 'active' => 1]);
    projectPvFixture(EventInvitation::class, ['IDevent' => $event->getId(), 'IDuser' => $owner->getId(),
        'invitation_type' => EventInvitation::TYPE_USER, 'status' => EventInvitation::STATUS_INVITED, 'active' => 1]);
    $invitation = projectPvFixture(EventInvitation::class, ['IDevent' => $event->getId(), 'IDuser' => $uid,
        'invitation_type' => EventInvitation::TYPE_USER, 'status' => EventInvitation::STATUS_INVITED, 'active' => 1]);
    $pv = projectPvFixture(Document::class, ['IDorganization' => $oid, 'IDuser' => $owner->getId(),
        'IDevent' => $event->getId(), 'documenttype' => Document::TYPE_PV, 'pvstage' => Document::PV_STAGE_PREPARATION,
        'title' => 'Invited private PV', 'active' => 1]);
    projectPvCheck(!empty($pv->saveVisibilityRule('self')['status']), 'Save private PV visibility');
    $memo = projectPvFixture(Document::class, ['IDorganization' => $oid, 'IDuser' => $owner->getId(),
        'documenttype' => Document::TYPE_HTML, 'title' => 'Direct memo', 'active' => 1]);
    projectPvFixture(ProjectDocument::class, ['IDproject' => $project->getId(), 'IDdocument' => $memo->getId()]);
    $list = static function (Project $target) use ($oid): array {
        return array_column(omoProjectsGetVisibleDocuments($target, $oid), null, 'id');
    };
    $visible = $list($project);
    projectPvCheck(isset($visible[$pv->getId()], $visible[$memo->getId()]), 'Meeting PV and direct document must both be listed');
    projectPvCheck(!$visible[$pv->getId()]['isDirectAssociation'], 'Derived PV must not offer direct detachment');
    projectPvCheck($visible[$memo->getId()]['isDirectAssociation'], 'Direct document remains detachable');
    projectPvCheck(count($project->getDocuments()) === 1, 'No persistent association is created for the PV');
    projectPvCheck($list($otherProject) === [], 'Unrelated project must not list the PV');
    $link = projectPvFixture(ProjectDocument::class, ['IDproject' => $project->getId(), 'IDdocument' => $pv->getId()]);
    projectPvCheck(count($list($project)) === 2 && $list($project)[$pv->getId()]['isDirectAssociation'], 'Dual association must appear once');
    projectPvCheck((bool)$link->delete(), 'Detach explicit PV link');
    projectPvCheck(isset($list($project)[$pv->getId()]), 'Meeting association survives explicit detachment');

    $event->set('IDproject', $otherProject->getId()); projectPvSave($event);
    projectPvCheck(!isset($list($project)[$pv->getId()]) && isset($list($otherProject)[$pv->getId()]), 'Moving meeting updates both project lists');
    $event->set('IDproject', null); projectPvSave($event);
    projectPvCheck($list($otherProject) === [], 'Detaching meeting removes derived PV');
    $event->set('IDproject', $project->getId()); projectPvSave($event);
    foreach ([Event::STATUS_CANCELLED, Event::STATUS_DRAFT] as $status) {
        $event->set('status', $status); projectPvSave($event);
        projectPvCheck(!isset($list($project)[$pv->getId()]), 'Cancelled/draft meeting must not expose PV to participant');
    }
    $event->set('status', Event::STATUS_CONFIRMED); $event->set('active', 0); projectPvSave($event);
    projectPvCheck(!isset($list($project)[$pv->getId()]), 'Inactive meeting excluded');
    $event->set('active', 1); projectPvSave($event);
    $pv->set('active', 0); projectPvSave($pv);
    projectPvCheck(!isset($list($project)[$pv->getId()]), 'Archived PV excluded');
    $pv->set('active', 1); projectPvSave($pv);
    $invitation->set('status', EventInvitation::STATUS_REVOKED); projectPvSave($invitation);
    projectPvCheck(!isset($list($project)[$pv->getId()]), 'Private PV stays hidden after invitation revocation');
    projectPvCheck(!empty($pv->saveVisibilityRule('organization')['status']), 'Save organization visibility');
    projectPvCheck(!isset($list($project)[$pv->getId()]), 'Preparation PV stays hidden from non-participant even with organization visibility');
    $pv->set('pvstage', Document::PV_STAGE_VALIDATED); projectPvSave($pv);
    projectPvCheck(!isset($list($project)[$pv->getId()]), 'Validated future PV still respects meeting gate');
    $event->set('start_at', new DateTimeImmutable('-2 days')); $event->set('end_at', new DateTimeImmutable('-2 days +1 hour')); projectPvSave($event);
    projectPvCheck(isset($list($project)[$pv->getId()]), 'Published past PV is visible according to its normal scope');

    $foreignOrg = projectPvFixture(Organization::class, ['name' => "$nonce-foreign", 'shortname' => "$nonce-foreign"]);
    $event->set('IDorganization', $foreignOrg->getId()); projectPvSave($event);
    projectPvCheck(!isset($list($project)[$pv->getId()]), 'Cross-organization event must not add documents');
    $event->set('IDorganization', $oid); projectPvSave($event);
    $pv->set('IDorganization', $foreignOrg->getId()); projectPvSave($pv);
    projectPvCheck(!isset($list($project)[$pv->getId()]), 'Cross-organization PV must not appear');
    $candidates = new ArrayDocument();
    $candidates->loadPvForProjectEvents((int)$project->getId(), (int)$foreignOrg->getId());
    projectPvCheck(count($candidates) === 0, 'Loader checks project organization');
    echo "project_event_pv_documents_test: OK\n";
} finally {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    DbObject::$preload = [];
}
