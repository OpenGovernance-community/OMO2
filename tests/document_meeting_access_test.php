<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';
require_once dirname(__DIR__) . '/omo/api/calendar/permissions_shared.php';

use dbObject\{DbObject, Document, ArrayDocument, Event, EventInvitation, EventSharedDocument, ObjectVisibility,
    Organization, User, UserOrganization, Holon, UserHolon};

function meetingDocumentCheck(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}
function meetingDocumentFixture(string $class, array $fields)
{
    $object = new $class();
    foreach ($fields as $field => $value) { $object->set($field, $value); }
    meetingDocumentSave($object);
    return $object;
}
function meetingDocumentSave($object): void
{
    $result = $object->save();
    meetingDocumentCheck(!empty($result['status']), 'Fixture save: ' . json_encode($result));
}

$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $nonce = 'meeting-document-' . bin2hex(random_bytes(5));
    $org = meetingDocumentFixture(Organization::class, ['name' => $nonce, 'shortname' => $nonce]);
    $oid = (int)$org->getId();
    $application = new \dbObject\Application();
    meetingDocumentCheck($application->load(['hash', 'structure']), 'Structure application exists');
    meetingDocumentFixture(\dbObject\OrganizationApplication::class,
        ['IDorganization' => $oid, 'IDapplication' => $application->getId(), 'active' => 1]);
    $root = meetingDocumentFixture(Holon::class, ['name' => 'Root', 'IDorganization' => $oid,
        'IDtypeholon' => 4, 'active' => 1, 'visible' => 1]);
    $root->set('IDholon_org', $root->getId()); meetingDocumentSave($root);
    $holon = meetingDocumentFixture(Holon::class, ['name' => 'Invited circle', 'IDorganization' => $oid,
        'IDholon_org' => $root->getId(), 'IDholon_parent' => $root->getId(),
        'IDtypeholon' => 2, 'active' => 1, 'visible' => 1]);
    $users = [];
    foreach (['owner', 'participant', 'other', 'outsider'] as $name) {
        $users[$name] = meetingDocumentFixture(User::class, ['email' => "$nonce-$name@example.invalid", 'active' => 1]);
        if ($name !== 'outsider') {
            meetingDocumentFixture(UserOrganization::class, ['IDorganization' => $oid, 'IDuser' => $users[$name]->getId(), 'active' => 1]);
        }
    }
    $owner = (int)$users['owner']->getId();
    $uid = (int)$users['participant']->getId();
    $_SERVER['HTTP_HOST'] = 'omo.localtest.me';
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid];
    $makeEvent = static function () use ($oid, $owner): Event {
        return meetingDocumentFixture(Event::class, ['IDorganization' => $oid, 'IDuser' => $owner,
            'title' => 'Cooperative meeting', 'status' => Event::STATUS_CONFIRMED, 'active' => 1,
            'start_at' => new DateTimeImmutable('+1 day'), 'end_at' => new DateTimeImmutable('+1 day +1 hour')]);
    };
    $invite = static function (Event $event, int $userId): EventInvitation {
        return meetingDocumentFixture(EventInvitation::class, ['IDevent' => $event->getId(), 'IDuser' => $userId,
            'invitation_type' => EventInvitation::TYPE_USER, 'status' => EventInvitation::STATUS_INVITED, 'active' => 1]);
    };
    $event = $makeEvent();
    $invite($event, $owner);
    $invitation = $invite($event, $uid);
    $invite($event, (int)$users['outsider']->getId());
    $makeDocument = static function (string $type) use ($oid, $owner): Document {
        $document = meetingDocumentFixture(Document::class, ['IDorganization' => $oid, 'IDuser' => $owner,
            'title' => 'Private cooperative document', 'documenttype' => $type, 'active' => 1,
            'datecreation' => new DateTimeImmutable('-1 day')]);
        meetingDocumentCheck(!empty($document->saveVisibilityRule('self')['status']), 'Save read scope');
        meetingDocumentCheck(!empty($document->saveEditVisibilityRule('self')['status']), 'Save edit scope');
        return $document;
    };
    $pad = $makeDocument(Document::TYPE_ETHERPAD);
    meetingDocumentCheck(!$pad->canViewDirectlyInOrganization($oid), 'Unattached private PAD is denied');
    $pad->set('IDevent', $event->getId());
    meetingDocumentSave($pad);

    $assertAccess = static function (Document $document, bool $expected, string $label) use ($oid, $uid): void {
        $viewer = ObjectVisibility::buildCurrentViewerContext($oid, $uid);
        meetingDocumentCheck($document->canViewInOrganizationContext($oid, null, $uid) === $expected, "$label: direct read");
        meetingDocumentCheck($document->canViewDirectlyInOrganization($oid) === $expected, "$label: calendar read");
        meetingDocumentCheck($document->canEditInOrganizationContext($oid, $uid, false) === $expected, "$label: edit");
        meetingDocumentCheck($document->canEditInOrganizationContextWithVisibilityRules($oid, $uid,
            $document->getPrimaryVisibilityRuleRow(), $document->getPrimaryEditVisibilityRuleRow(), $viewer) === $expected, "$label: batched edit");
    };
    $assertAccess($pad, true, 'Direct meeting association');
    $pad->set('IDevent', null); meetingDocumentSave($pad);
    $assertAccess($pad, false, 'Detached direct association');
    $pad->set('IDevent', $event->getId()); meetingDocumentSave($pad);
    meetingDocumentCheck(!$pad->canManageInOrganizationContext($oid, $uid, false), 'Participation does not grant metadata management');
    meetingDocumentCheck(!$pad->canDeleteInOrganizationContext($oid, $uid), 'Participation does not grant deletion');
    meetingDocumentCheck(omoCalendarBuildAssociatedDocumentOpenData($event, $pad, $uid, $oid)['url'] !== '', 'Calendar exposes the document link');
    foreach ([0, (int)$users['other']->getId(), (int)$users['outsider']->getId()] as $deniedId) {
        meetingDocumentCheck(!$pad->canViewInOrganizationContext($oid, null, $deniedId), 'Anonymous/non-participant/outsider read denied');
        meetingDocumentCheck(!$pad->canEditInOrganizationContext($oid, $deniedId, false), 'Anonymous/non-participant/outsider edit denied');
    }
    meetingDocumentCheck(!$pad->canUserAccessCooperativeDocumentThroughMeeting($uid, $oid + 100000), 'Organization isolation');
    meetingDocumentCheck(!$pad->canViewInOrganizationContext($oid, 999999, $uid), 'Holon context isolation');

    // A shared item is accessible without opening its private parent or siblings.
    $folder = $makeDocument(Document::TYPE_FOLDER);
    $folder->set('estDossier', 1);
    meetingDocumentSave($folder);
    $pad->set('IDdocument_parent', $folder->getId());
    meetingDocumentSave($pad);
    $assertAccess($pad, true, 'PAD inside private folder');
    meetingDocumentCheck(!$folder->canViewDirectlyInOrganization($oid), 'Parent folder stays private');
    $sibling = $makeDocument(Document::TYPE_ETHERPAD);
    $sibling->set('IDdocument_parent', $folder->getId());
    meetingDocumentSave($sibling);
    $assertAccess($sibling, false, 'Unshared sibling');
    $list = new ArrayDocument();
    $list->exchangeArray([$folder, $pad, $sibling]);
    $list->filterVisibleForCurrentViewer($oid);
    meetingDocumentCheck(count($list) === 1 && (int)$list[0]->getId() === (int)$pad->getId(), 'List exposes only the invited document');

    foreach ([Document::TYPE_ETHERCALC, Document::TYPE_WHITEBOARD, Document::TYPE_UPLOADED_FILE,
        Document::TYPE_COLLABORA_DOCUMENT, Document::TYPE_COLLABORA_SPREADSHEET,
        Document::TYPE_COLLABORA_PRESENTATION, Document::TYPE_COLLABORA_DRAWING] as $type) {
        $document = $makeDocument($type);
        if ($type === Document::TYPE_UPLOADED_FILE) {
            $document->set('storedfilename', 'meeting.odt');
            $document->set('storedfilepath', 'test/meeting.odt');
            meetingDocumentSave($document);
        }
        meetingDocumentCheck(EventSharedDocument::attach($event, $document), 'Attach shared document');
        $assertAccess($document, true, 'Shared ' . $type);
        meetingDocumentCheck($document->getPrimaryVisibilityRuleRow()['visibility_type'] === 'self', 'Read scope unchanged');
        meetingDocumentCheck($document->getPrimaryEditVisibilityRuleRow()['visibility_type'] === 'self', 'Edit scope unchanged');
    }
    foreach ([Document::TYPE_HTML, Document::TYPE_EXTERNAL_LINK, Document::TYPE_UPLOADED_FILE, Document::TYPE_PV] as $type) {
        $document = $makeDocument($type);
        $document->set('IDevent', $event->getId());
        meetingDocumentSave($document);
        meetingDocumentCheck(!$document->canUserAccessCooperativeDocumentThroughMeeting($uid, $oid), 'Other types keep their access rules: ' . $type);
    }

    foreach ([Event::STATUS_DRAFT, Event::STATUS_CANCELLED] as $status) {
        $event->set('status', $status); meetingDocumentSave($event);
        $assertAccess($pad, false, $status . ' meeting');
    }
    $event->set('status', Event::STATUS_CONFIRMED); $event->set('active', 0); meetingDocumentSave($event);
    $assertAccess($pad, false, 'Inactive meeting');
    $event->set('active', 1); meetingDocumentSave($event);
    $invitation->set('status', EventInvitation::STATUS_REVOKED); meetingDocumentSave($invitation);
    $assertAccess($pad, false, 'Revoked invitation');

    // The same PAD can still be shared through a second meeting.
    $secondEvent = $makeEvent();
    $invite($secondEvent, $uid);
    meetingDocumentCheck(EventSharedDocument::attach($secondEvent, $pad), 'Attach second meeting');
    $assertAccess($pad, true, 'Second meeting grants access');
    $link = new EventSharedDocument();
    meetingDocumentCheck($link->load([['IDevent', $secondEvent->getId()], ['IDdocument', $pad->getId()]]), 'Load shared relation');
    meetingDocumentCheck((bool)$link->delete(), 'Detach shared relation');
    $assertAccess($pad, false, 'Detached second meeting');

    $emailInvitation = meetingDocumentFixture(EventInvitation::class, ['IDevent' => $event->getId(),
        'invitation_type' => EventInvitation::TYPE_EMAIL, 'email' => $users['participant']->get('email'),
        'status' => EventInvitation::STATUS_INVITED, 'active' => 1]);
    $assertAccess($pad, true, 'Invitation by account email');
    $emailInvitation->set('status', EventInvitation::STATUS_REVOKED); meetingDocumentSave($emailInvitation);
    $assertAccess($pad, false, 'Revoked email invitation');

    meetingDocumentFixture(UserHolon::class, ['IDholon' => $holon->getId(), 'IDuser' => $uid,
        'active' => 1, 'is_membership' => 1]);
    meetingDocumentFixture(EventInvitation::class, ['IDevent' => $event->getId(), 'IDholon' => $holon->getId(),
        'invitation_type' => EventInvitation::TYPE_HOLON, 'status' => EventInvitation::STATUS_INVITED, 'active' => 1]);
    meetingDocumentCheck(in_array($uid, $event->getEffectiveInvitationTargets($oid)['userIds'], true),
        'Circle fixture: ' . json_encode(['contains' => $org->containsHolon($holon),
            'members' => $holon->getAssociatedMemberUserIds(['organizationId' => $oid, 'skipPermissionFilter' => true]),
            'targets' => $event->getEffectiveInvitationTargets($oid)]));
    $assertAccess($pad, true, 'Invitation through circle');
    $implicitEvent = $makeEvent();
    $implicitEvent->set('IDholon', $holon->getId()); meetingDocumentSave($implicitEvent);
    $implicitPad = $makeDocument(Document::TYPE_ETHERPAD);
    $implicitPad->set('IDevent', $implicitEvent->getId()); meetingDocumentSave($implicitPad);
    $assertAccess($implicitPad, true, 'Implicit circle participants');
    meetingDocumentCheck(!$implicitPad->canViewInOrganizationContext($oid, null, (int)$users['other']->getId()),
        'Implicit circle does not include unrelated members');
    $pad->set('active', 0); meetingDocumentSave($pad);
    $assertAccess($pad, false, 'Archived PAD');
    echo "document_meeting_access_test: OK\n";
} finally {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    DbObject::$preload = [];
}
