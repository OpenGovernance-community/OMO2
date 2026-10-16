<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';

use dbObject\{ArrayDocument, DbObject, Document, Event, EventInvitation, Holon, Organization, Project, ProjectDocument, User, UserOrganization};

function documentsMeetingPvCheck(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}
function documentsMeetingPvSave($object): void
{
    $result = $object->save();
    documentsMeetingPvCheck(!empty($result['status']), 'Fixture save: ' . json_encode($result));
}
function documentsMeetingPvFixture(string $class, array $fields)
{
    $object = new $class();
    foreach ($fields as $field => $value) { $object->set($field, $value); }
    documentsMeetingPvSave($object);
    return $object;
}

$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $nonce = 'documents-meeting-pv-' . bin2hex(random_bytes(5));
    $organization = documentsMeetingPvFixture(Organization::class, ['name' => $nonce, 'shortname' => $nonce]);
    $oid = (int)$organization->getId();
    $owner = documentsMeetingPvFixture(User::class, ['email' => $nonce . '-owner@example.invalid', 'active' => 1]);
    $viewer = documentsMeetingPvFixture(User::class, ['email' => $nonce . '-viewer@example.invalid', 'active' => 1]);
    $uid = (int)$viewer->getId();
    foreach ([$owner, $viewer] as $user) {
        documentsMeetingPvFixture(UserOrganization::class, ['IDorganization' => $oid, 'IDuser' => $user->getId(), 'active' => 1]);
    }
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid];
    $holon = documentsMeetingPvFixture(Holon::class, ['name' => $nonce, 'IDorganization' => $oid,
        'IDuser' => $owner->getId(), 'active' => 1]);
    $hid = (int)$holon->getId();
    $makeDocument = static function (array $fields) use ($oid, $hid, $owner): Document {
        $document = documentsMeetingPvFixture(Document::class, $fields + ['IDorganization' => $oid,
            'IDholon' => $hid, 'IDuser' => $owner->getId(), 'title' => 'Document', 'active' => 1,
            'project_visible_in_holon' => 0]);
        documentsMeetingPvCheck(!empty($document->saveVisibilityRule('organization')['status']), 'Save visibility');
        return $document;
    };
    $event = documentsMeetingPvFixture(Event::class, ['IDorganization' => $oid, 'IDholon' => $hid,
        'IDuser' => $owner->getId(), 'title' => 'Meeting', 'active' => 1, 'status' => Event::STATUS_CONFIRMED,
        'start_at' => new DateTimeImmutable('+1 day'), 'end_at' => new DateTimeImmutable('+1 day +1 hour')]);
    $invitation = documentsMeetingPvFixture(EventInvitation::class, ['IDevent' => $event->getId(),
        'IDuser' => $uid, 'invitation_type' => EventInvitation::TYPE_USER,
        'status' => EventInvitation::STATUS_INVITED, 'active' => 1]);
    $folder = $makeDocument(['documenttype' => Document::TYPE_FOLDER, 'estDossier' => 1]);
    $nested = $makeDocument(['documenttype' => Document::TYPE_FOLDER, 'estDossier' => 1,
        'IDdocument_parent' => $folder->getId()]);
    $pv = $makeDocument(['documenttype' => Document::TYPE_PV, 'IDevent' => $event->getId(),
        'pvstage' => Document::PV_STAGE_PREPARATION]);
    $attachment = $makeDocument(['documenttype' => Document::TYPE_HTML, 'IDevent' => $event->getId()]);
    $load = static function (string $scope = 'contextual', array $holonIds = []) use ($oid, $hid): array {
        $documents = new ArrayDocument();
        $documents->loadVisibleForOrganizationContext($oid, $hid, $scope, $holonIds);
        return array_column(array_map(static fn(Document $document): array => [
            'id' => (int)$document->getId(), 'parentId' => (int)$document->get('IDdocument_parent'),
        ], $documents->getArrayCopy()), null, 'id');
    };
    $pvId = (int)$pv->getId();
    documentsMeetingPvCheck($pv->canViewDirectlyInOrganization($oid), 'Participant can open meeting PV');
    documentsMeetingPvCheck(isset($load()[$pvId]), 'Participant must also see meeting PV in Documents');
    documentsMeetingPvCheck(!isset($load()[$attachment->getId()]), 'Ordinary event attachments still follow their visibility checkbox');
    foreach ([0, (int)$folder->getId(), (int)$nested->getId()] as $parentId) {
        $pv->set('IDdocument_parent', $parentId ?: null);
        documentsMeetingPvSave($pv);
        foreach (['contextual', 'children', 'descendants'] as $scope) {
            $listed = $load($scope, [$hid]);
            documentsMeetingPvCheck(isset($listed[$pvId]) && $listed[$pvId]['parentId'] === $parentId,
                'Meeting PV retains its folder in scope ' . $scope);
        }
    }
    $nested->saveVisibilityRule('self');
    documentsMeetingPvCheck(!isset($load()[$pvId]), 'Private parent still hides nested PV');
    $nested->saveVisibilityRule('organization');
    $folder->set('active', 0); documentsMeetingPvSave($folder);
    documentsMeetingPvCheck(!isset($load()[$pvId]), 'Archived ancestor still hides nested PV');
    $folder->set('active', 1); documentsMeetingPvSave($folder);

    $invitation->set('status', EventInvitation::STATUS_REVOKED); documentsMeetingPvSave($invitation);
    documentsMeetingPvCheck(!isset($load()[$pvId]), 'Preparation PV stays hidden from non-participants');
    $pv->set('pvstage', Document::PV_STAGE_VALIDATED); documentsMeetingPvSave($pv);
    documentsMeetingPvCheck(!isset($load()[$pvId]), 'Future validated PV still respects meeting gate');
    $event->set('start_at', new DateTimeImmutable('-2 days'));
    $event->set('end_at', new DateTimeImmutable('-2 days +1 hour')); documentsMeetingPvSave($event);
    documentsMeetingPvCheck(isset($load()[$pvId]), 'Past validated PV appears according to its normal visibility');
    $pv->saveVisibilityRule('self');
    documentsMeetingPvCheck(!isset($load()[$pvId]), 'Private validated PV stays hidden');
    $pv->saveVisibilityRule('organization');
    $pv->set('active', 0); documentsMeetingPvSave($pv);
    documentsMeetingPvCheck(!isset($load()[$pvId]), 'Archived PV stays hidden');
    $pv->set('active', 1); documentsMeetingPvSave($pv);

    $project = documentsMeetingPvFixture(Project::class, ['IDorganization' => $oid, 'IDholon' => $hid,
        'IDuser' => $owner->getId(), 'title' => 'Project', 'status' => Project::STATUS_READY, 'active' => 1]);
    $event->set('IDproject', $project->getId()); documentsMeetingPvSave($event);
    documentsMeetingPvCheck(isset($load()[$pvId]), 'Meeting project association must not hide its PV');
    $link = documentsMeetingPvFixture(ProjectDocument::class, ['IDproject' => $project->getId(), 'IDdocument' => $pvId]);
    documentsMeetingPvCheck(!isset($load()[$pvId]), 'Explicit project-only association retains its visibility setting');
    $pv->set('project_visible_in_holon', 1); documentsMeetingPvSave($pv);
    documentsMeetingPvCheck(isset($load()[$pvId]), 'Explicit project document can be shown in the holon');
    echo "documents_meeting_pv_list_test: OK\n";
} finally {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    DbObject::$preload = [];
}
