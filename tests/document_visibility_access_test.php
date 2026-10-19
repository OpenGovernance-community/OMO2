<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';
require_once dirname(__DIR__) . '/omo/api/calendar/permissions_shared.php';

use dbObject\{DbObject, Document, ArrayDocument, Organization, User, UserOrganization, Event, EventInvitation};

function visibilityCheck(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
function visibilityFixture(string $class, array $fields): DbObject
{
    $object = new $class();
    foreach ($fields as $field => $value) $object->set($field, $value);
    visibilityCheck(!empty($object->save()['status']), 'Save fixture ' . $class);
    return $object;
}

$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $nonce = bin2hex(random_bytes(6));
    $org = visibilityFixture(Organization::class, ['name' => 'Document visibility', 'shortname' => $nonce]);
    $oid = (int)$org->getId();
    $owner = visibilityFixture(User::class, ['email' => "owner-$nonce@example.invalid", 'active' => 1]);
    $reader = visibilityFixture(User::class, ['email' => "reader-$nonce@example.invalid", 'active' => 1]);
    foreach ([$owner, $reader] as $user) {
        visibilityFixture(UserOrganization::class, ['IDuser' => $user->getId(), 'IDorganization' => $oid, 'active' => 1]);
    }
    $uid = (int)$reader->getId();
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid];
    $event = visibilityFixture(Event::class, ['IDorganization' => $oid, 'IDuser' => $owner->getId(),
        'title' => 'Visibility meeting', 'active' => 1, 'status' => Event::STATUS_CONFIRMED,
        'start_at' => new DateTimeImmutable('+1 day'), 'end_at' => new DateTimeImmutable('+1 day +1 hour')]);
    visibilityFixture(EventInvitation::class, ['IDevent' => $event->getId(), 'IDuser' => $owner->getId(),
        'invitation_type' => EventInvitation::TYPE_USER, 'status' => EventInvitation::STATUS_INVITED, 'active' => 1]);
    $pad = visibilityFixture(Document::class, ['IDorganization' => $oid, 'IDuser' => $owner->getId(),
        'title' => 'Organization PAD', 'documenttype' => Document::TYPE_ETHERPAD, 'IDevent' => $event->getId(), 'active' => 1]);
    $folder = visibilityFixture(Document::class, ['IDorganization' => $oid, 'IDuser' => $owner->getId(),
        'title' => 'Folder', 'documenttype' => Document::TYPE_FOLDER, 'estDossier' => 1, 'active' => 1]);
    visibilityCheck(!empty($pad->saveVisibilityRule('organization')['status']), 'Save read scope');
    visibilityCheck(!empty($pad->saveEditVisibilityRule('self')['status']), 'Save edit scope');
    visibilityCheck(!empty($folder->saveVisibilityRule('organization')['status']), 'Save folder scope');
    $assertRead = static function (bool $expected, string $label) use ($oid, $uid, $pad, $folder, $event): void {
        visibilityCheck($pad->canViewInOrganizationContext($oid, null, $uid) === $expected, "$label: opening");
        visibilityCheck($pad->canViewDirectlyInOrganization($oid, $uid) === $expected, "$label: direct opening");
        visibilityCheck((omoCalendarBuildAssociatedDocumentOpenData($event, $pad, $uid, $oid)['url'] !== '') === $expected, "$label: event link");
        $list = new ArrayDocument();
        $list->exchangeArray([$pad, $folder]);
        $list->filterVisibleForCurrentViewer($oid);
        $ids = array_map(static fn($doc) => (int)$doc->getId(), $list->getArrayCopy());
        visibilityCheck(in_array((int)$pad->getId(), $ids, true) === $expected, "$label: document list");
    };
    $assertRead(true, 'Organization read and restricted edit');
    visibilityCheck(!$pad->canEditInOrganizationContext($oid, $uid, false), 'A reader must not edit');
    visibilityCheck($pad->canEditInOrganizationContext($oid, (int)$owner->getId(), false), 'Owner can edit');
    $pad->saveEditVisibilityRule('organization');
    visibilityCheck($pad->canEditInOrganizationContext($oid, $uid, false), 'Organization editing');
    $pad->saveVisibilityRule('self');
    $assertRead(false, 'Private read with organization edit');
    visibilityCheck(!$pad->canEditInOrganizationContext($oid, $uid, false), 'Editing cannot bypass private reading');
    // Calendar callers can check a viewer other than the current session identity.
    $_SESSION['currentUser'] = (int)$owner->getId();
    visibilityCheck(omoCalendarBuildAssociatedDocumentOpenData($event, $pad, $uid, $oid)['url'] === '', 'Event link checks the explicit viewer');
    $_SESSION['currentUser'] = $uid;
    $pad->saveVisibilityRule('organization');
    $pad->set('active', 0); $pad->save();
    $assertRead(false, 'Archived PAD');
    $pad->set('active', 1); $pad->set('IDdocument_parent', $folder->getId()); $pad->save();
    $assertRead(true, 'Visible folder');
    $folder->saveVisibilityRule('self');
    $assertRead(false, 'Private folder');
    $folder->saveVisibilityRule('organization');
    $folder->set('active', 0); $folder->save();
    $assertRead(false, 'Archived folder');
    $folder->set('active', 1); $folder->set('estDossier', 0); $folder->set('documenttype', Document::TYPE_HTML); $folder->save();
    $assertRead(false, 'Invalid parent type');
    echo "document_visibility_access_test: OK\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
    DbObject::$preload = [];
}
