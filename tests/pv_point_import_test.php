<?php
declare(strict_types=1);

// Integration test against the migrated development database. All fixtures roll back.
require_once dirname(__DIR__) . '/shared_functions.php';

use dbObject\Document;
use dbObject\DocumentPvPoint;
use dbObject\DeferredProposal;

function importExpect(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

function importSave($object): void
{
    $result = $object->save();
    importExpect(!empty($result['status']), 'Fixture save failed: ' . json_encode($result) . ' ' . json_encode(dbObject\DbObject::getLastDbError()));
}

$pdo = dbObject\DbObject::getPdo();
$pdo->beginTransaction();
try {
    $user = new dbObject\User();
    $user->set('email', 'pv-import-' . bin2hex(random_bytes(6)) . '@example.invalid');
    $user->set('firstname', 'Import');
    importSave($user);
    $userId = (int)$user->getId();
    $_SESSION['currentUser'] = $userId;
    $organization = new dbObject\Organization();
    $organization->set('name', 'PV import fixture');
    $organization->set('shortname', 'pvimport' . bin2hex(random_bytes(4)));
    importSave($organization);
    $organizationId = (int)$organization->getId();
    $_SESSION['currentOrganization'] = $organizationId;
    $holon = new dbObject\Holon();
    $holon->set('name', 'Other context');
    $holon->set('IDorganization', $organizationId);
    $holon->set('active', 1);
    $holon->set('visible', 1);
    importSave($holon);
    $makeDocument = static function (string $date, int $context = 0) use ($userId, $organizationId): Document {
        $document = new Document();
        foreach (['title' => 'Meeting ' . $date, 'documenttype' => 'pv', 'pvstage' => 'meeting',
            'IDorganization' => $organizationId, 'IDuser' => $userId, 'IDusercreation' => $userId, 'IDuser_pv_editor' => $userId,
            'IDholon' => $context ?: null, 'active' => 1, 'datecreation' => $date] as $key => $value) $document->set($key, $value);
        importSave($document);
        return $document;
    };
    $makePoint = static function (Document $document, array $fields = []) use ($userId): DocumentPvPoint {
        $point = new DocumentPvPoint();
        foreach ($fields + ['IDdocument' => (int)$document->getId(), 'title' => 'Fixture point',
            'pointtype' => 'decision', 'content' => '<p>Original content</p>', 'IDuser_author' => $userId,
            'priority' => 1, 'desired_duration_minutes' => 12, 'actual_duration_minutes' => 4, 'active' => 1] as $key => $value) $point->set($key, $value);
        importSave($point);
        return $point;
    };
    $destination = $makeDocument('2026-10-02 12:00:00');
    $old = $makeDocument('2026-09-01 12:00:00');
    $recent = $makeDocument('2026-09-30 12:00:00');
    $otherContext = $makeDocument('2026-09-29 12:00:00', (int)$holon->getId());
    // Meeting dates and contexts take precedence over document creation dates.
    foreach ([[$old, '2026-09-01 12:00:00', 0], [$otherContext, '2026-09-29 12:00:00', (int)$holon->getId()]] as [$document, $date, $context]) {
        $event = new dbObject\Event();
        foreach (['IDuser' => $userId, 'IDorganization' => $organizationId, 'IDholon' => $context ?: null,
            'title' => 'Fixture event', 'status' => 'confirmed', 'start_at' => $date,
            'end_at' => (new DateTimeImmutable($date))->modify('+1 hour'), 'active' => 1] as $field => $value) $event->set($field, $value);
        importSave($event);
        $document->set('IDevent', (int)$event->getId());
        $document->set('IDholon', null);
        $document->set('datecreation', '2026-11-15 12:00:00');
        importSave($document);
    }
    $future = $makeDocument('2026-11-01 12:00:00');
    $template = $makeDocument('2026-09-28 12:00:00');
    $template->set('is_template', 1);
    importSave($template);
    $oldPoint = $makePoint($old);
    $recentPoint = $makePoint($recent, ['is_confidential' => 1]);
    $otherPoint = $makePoint($otherContext);
    $makePoint($recent, ['is_handled' => 1]);
    $makePoint($recent, ['item_type' => 'group']);
    $makePoint($recent, ['active' => 0]);
    $makePoint($future);
    $makePoint($template);
    $makePoint($destination);
    // Validated source minutes must also offer their unfinished points.
    $old->set('pvstage', 'validated');
    importSave($old);

    $page = DocumentPvPoint::getImportablePage($destination, $userId, 'local', [], 1);
    importExpect(array_column($page['items'], 'id') === [(int)$recentPoint->getId()], 'Newest meeting must be first, including a visible confidential point.');
    $next = DocumentPvPoint::getImportablePage($destination, $userId, 'local', $page['cursor'], 2);
    importExpect(array_column($next['items'], 'id') === [(int)$oldPoint->getId()] && !$next['hasMore'], 'Pagination excludes handled, inactive, groups, templates, future and other contexts.');
    $global = DocumentPvPoint::getImportablePage($destination, $userId, 'global');
    importExpect(array_column($global['items'], 'id') === [(int)$recentPoint->getId(), (int)$otherPoint->getId(), (int)$oldPoint->getId()], 'Global scope includes the other accessible context.');
    importExpect(DocumentPvPoint::getImportablePage($destination, 0)['items'] === [], 'Anonymous imports must be refused.');

    $holonLink = new dbObject\DocumentPvPointHolon();
    $holonLink->set('IDdocument_pv_point', (int)$oldPoint->getId());
    $holonLink->set('IDholon', (int)$holon->getId());
    $holonLink->set('position', 1);
    importSave($holonLink);
    $tension = new dbObject\Tension();
    foreach (['IDorganization' => $organizationId, 'IDuser' => $userId, 'title' => 'Fixture tension', 'description' => 'Fixture description', 'active' => 1] as $field => $value) $tension->set($field, $value);
    importSave($tension);
    $tensionLink = new dbObject\DocumentPvPointTension();
    $tensionLink->set('IDdocument_pv_point', (int)$oldPoint->getId());
    $tensionLink->set('IDtension', (int)$tension->getId());
    importSave($tensionLink);
    $proposal = new DeferredProposal();
    foreach (['IDorganization' => $organizationId, 'IDuser_author' => $userId, 'target_type' => 'rule',
        'operation' => 'create', 'status' => 'pending', 'IDdocument_pv_point' => (int)$oldPoint->getId(),
        'before_state' => [], 'after_state' => ['title' => 'Proposed rule']] as $field => $value) $proposal->set($field, $value);
    importSave($proposal);

    $recentPoint->set('IDuser_editing', $userId);
    $recentPoint->set('edit_lock_token', 'another-browser');
    $recentPoint->set('dateedition', new DateTimeImmutable());
    importSave($recentPoint);
    $notice = 'Ce point n a pas ete traite ici. Deplace vers {meeting}.';
    $stalePoint = new DocumentPvPoint();
    $stalePoint->load($oldPoint->getId(), true);
    $staleRecentPoint = new DocumentPvPoint();
    $staleRecentPoint->load($recentPoint->getId(), true);
    $beforeCount = count($destination->getPvPoints());
    $result = DocumentPvPoint::importIntoDocument($destination, [(int)$oldPoint->getId(), (int)$recentPoint->getId()], $userId, 'local', $notice);
    importExpect(!$result['status'] && count($destination->getPvPoints()) === $beforeCount, 'A locked point cancels the entire batch.');
    $oldPoint->load($oldPoint->getId(), true);
    importExpect(!$oldPoint->isMoved() && str_contains((string)$oldPoint->get('content'), 'Original content'), 'Failed batch must preserve old minutes.');
    $recentPoint->set('IDuser_editing', null);
    $recentPoint->set('edit_lock_token', null);
    $recentPoint->set('dateedition', null);
    importSave($recentPoint);
    $result = DocumentPvPoint::importIntoDocument($destination, [(int)$oldPoint->getId(), (int)$recentPoint->getId()], $userId, 'local', $notice);
    importExpect(!empty($result['status']) && count($result['pointIds']) === 2, 'Multiple points must transfer successfully: ' . json_encode($result));
    importExpect($result['pointIds'][1] === (int)$recentPoint->getId(), 'An open meeting point is moved with the same identity.');
    $recentPoint->load($recentPoint->getId(), true);
    importExpect((int)$recentPoint->get('IDdocument') === (int)$destination->getId()
        && !$recentPoint->isMoved() && $recentPoint->get('content') === '<p>Original content</p>'
        && $recentPoint->getDurationMinutesValue('actual_duration_minutes') === 4, 'Moving from meeting mode preserves content and metadata without a source notice.');
    importExpect(!in_array((int)$recentPoint->getId(), array_map(static fn($point) => (int)$point->getId(), $recent->getPvPoints()->getArrayCopy()), true), 'The moved point disappears from the open source meeting.');
    importExpect(empty($staleRecentPoint->save()['status']), 'A stale source cannot move the point back while saving.');
    $staleRecentPoint->delete();
    importExpect($recentPoint->load($recentPoint->getId(), true), 'A stale source delete cannot remove the destination point.');
    $copy = new DocumentPvPoint();
    $copy->load($result['pointIds'][0], true);
    importExpect($copy->get('content') === '<p>Original content</p>' && (int)$copy->get('priority') === 1
        && $copy->getDurationMinutesValue('desired_duration_minutes') === 12
        && $copy->getDurationMinutesValue('actual_duration_minutes') === null && !$copy->isHandled(), 'Copy preserves content and planning but resets completion and measured duration.');
    importExpect(count($copy->getAddressedHolonItems()) === 1 && count($copy->getTensionItems()) === 1
        && count(DeferredProposal::getForPvPoint((int)$copy->getId(), true)) === 1, 'Holon links, tensions and pending proposals must follow the point.');
    $oldPoint->load($oldPoint->getId(), true);
    importExpect($oldPoint->isMoved() && !$oldPoint->isHandled()
        && (int)$oldPoint->get('IDpoint_moved_to') === (int)$copy->getId()
        && str_contains((string)$oldPoint->get('content'), '#documents-d' . $destination->getId())
        && !str_contains((string)$oldPoint->get('content'), 'Original content'), 'Old minutes retain the destination and an explicit unhandled notice.');
    importExpect(!$old->canUserEditPvPoint($oldPoint, $userId) && empty($oldPoint->save()['status']), 'Moved points must be immutable.');
    importExpect(!$stalePoint->isMoved() && empty($stalePoint->save()['status']), 'A stale browser cannot overwrite a transfer after loading the original point.');
    importExpect(DocumentPvPoint::getImportablePage($destination, $userId)['items'] === [], 'Transferred points disappear from future import lists.');
    $result = DocumentPvPoint::importIntoDocument($destination, [(int)$oldPoint->getId()], $userId, 'local', $notice);
    importExpect(!$result['status'] && count($destination->getPvPoints()) === $beforeCount + 2, 'Retrying a successful import must not duplicate the point.');
    importExpect((bool)$copy->delete(), 'The destination point can still be deleted through the normal flow.');
    $oldPoint->load($oldPoint->getId(), true);
    importExpect($oldPoint->isMoved() && !$oldPoint->get('IDpoint_moved_to')
        && DocumentPvPoint::getImportablePage($destination, $userId)['items'] === [], 'Deleting the target must preserve the source transfer notice and prevent reimport.');
    $otherContext->set('pvstage', 'preparation');
    importSave($otherContext);
    $group = $makePoint($otherContext, ['item_type' => 'group']);
    $otherPoint->set('IDparent', (int)$group->getId());
    importSave($otherPoint);
    $result = DocumentPvPoint::importIntoDocument($destination, [(int)$otherPoint->getId()], $userId, 'global', $notice);
    importExpect(!empty($result['status']) && $result['pointIds'] === [(int)$otherPoint->getId()], 'Preparation mode also moves the same point directly.');
    $otherPoint->load($otherPoint->getId(), true);
    importExpect(!$otherPoint->isMoved() && !$otherPoint->get('IDparent')
        && (int)$otherPoint->get('IDdocument') === (int)$destination->getId(), 'A moved point is appended outside its old group without a transfer marker.');
    $reviewSource = $makeDocument('2026-09-22 12:00:00');
    $reviewPoint = $makePoint($reviewSource);
    $reviewSource->set('pvstage', 'review');
    importSave($reviewSource);
    $result = DocumentPvPoint::importIntoDocument($destination, [(int)$reviewPoint->getId()], $userId, 'local', $notice);
    importExpect(!empty($result['status']) && $result['pointIds'][0] !== (int)$reviewPoint->getId(), 'Review mode already requires a copy and a source trace.');
    $reviewPoint->load($reviewPoint->getId(), true);
    importExpect($reviewPoint->isMoved() && (int)$reviewPoint->get('IDdocument') === (int)$reviewSource->getId()
        && str_contains((string)$reviewPoint->get('content'), '#documents-d' . $destination->getId()), 'The review source retains the destination link.');
    $destination->set('pvstage', 'review');
    importSave($destination);
    importExpect(!DocumentPvPoint::canImportIntoDocument($destination, $userId), 'Review destination must reject import.');
    echo "pv_point_import_test: OK\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}
