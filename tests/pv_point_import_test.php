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
    $otherPoint = $makePoint($otherContext, ['content' => '<script>hidden</script><p>&nbsp;</p><p> First &amp; <strong>visible</strong>' . "\n" . ' line<br>Second line</p>']);
    $makePoint($recent, ['is_handled' => 1]);
    $makePoint($recent, ['item_type' => 'group']);
    $makePoint($recent, ['active' => 0]);
    $futurePoint = $makePoint($future);
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
    importExpect($page['items'][0]['contentPreview'] === 'Original content', 'Picker exposes plain content without HTML.');
    importExpect($global['items'][1]['contentPreview'] === 'First & visible line', 'Preview skips empty blocks, removes scripts and preserves the first visible line.');
    foreach (["First plain line\nSecond line" => 'First plain line', '<p>&nbsp;<br></p>' => '', str_repeat('x', 5000) => str_repeat('x', 500) . '...'] as $content => $expected) {
        $otherPoint->set('content', $content);
        importSave($otherPoint);
        $previewPage = DocumentPvPoint::getImportablePage($destination, $userId, 'global');
        importExpect($previewPage['items'][1]['contentPreview'] === $expected, 'Plain, empty and long content previews must remain bounded and single-line.');
    }
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
    importExpect(str_contains($oldPoint->getRenderedContentForViewer($organizationId), '#documents-d' . $destination->getId()), 'A deleted target leaves the original notice readable.');
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
    $member = new dbObject\User();
    $member->set('email', 'pv-member-' . bin2hex(random_bytes(6)) . '@example.invalid');
    importSave($member);
    $memberId = (int)$member->getId();
    $memberSource = $makeDocument('2026-09-20 12:00:00');
    $ownPoint = $makePoint($memberSource);
    $memberPoint = $makePoint($memberSource, ['IDuser_author' => $memberId]);
    importExpect(array_column(DocumentPvPoint::getImportablePage($destination, $userId)['items'], 'id') === [(int)$ownPoint->getId()], 'Mine is the default even for the PV editor.');
    $allMembers = DocumentPvPoint::getImportablePage($destination, $userId, 'local', [], 20, 'all');
    importExpect(array_column($allMembers['items'], 'id') === [(int)$memberPoint->getId(), (int)$ownPoint->getId()], 'The PV editor can list points by other members.');
    $result = DocumentPvPoint::importIntoDocument($destination, [(int)$memberPoint->getId()], $userId, 'local', $notice);
    importExpect(empty($result['status']), 'Forged point IDs cannot bypass the mine filter, even for an editor.');
    $staleEditorDestination = clone $destination;
    $destination->set('IDuser_pv_editor', $memberId);
    importSave($destination);
    importExpect(DocumentPvPoint::canImportIntoDocument($destination, $userId), 'A non-editor may still import their own points.');
    importExpect(DocumentPvPoint::getImportablePage($destination, $userId, 'local', [], 20, 'all')['items'] === [], 'Being the creator of the PV does not grant the all-members filter.');
    $result = DocumentPvPoint::importIntoDocument($destination, [(int)$ownPoint->getId()], $userId, 'local', $notice, 'all');
    importExpect(empty($result['status']), 'A non-editor cannot use all-members transfer, even with their own point ID.');
    $result = DocumentPvPoint::importIntoDocument($staleEditorDestination, [(int)$memberPoint->getId()], $userId, 'local', $notice, 'all');
    importExpect(empty($result['status']), 'The transfer rechecks the editor role after locking the destination, even with a stale editor object.');
    $result = DocumentPvPoint::importIntoDocument($destination, [(int)$ownPoint->getId(), (int)$memberPoint->getId()], $userId, 'local', $notice);
    importExpect(empty($result['status']), 'A mine transfer containing a foreign point must fail atomically.');
    $ownPoint->load($ownPoint->getId(), true);
    importExpect((int)$ownPoint->get('IDdocument') === (int)$memberSource->getId(), 'A rejected author-filter batch leaves own points untouched.');
    $result = DocumentPvPoint::importIntoDocument($destination, [(int)$ownPoint->getId()], $userId, 'local', $notice);
    importExpect(!empty($result['status']), 'A non-editor can transfer their own points.');
    $destination->set('IDuser_pv_editor', $userId);
    importSave($destination);
    $result = DocumentPvPoint::importIntoDocument($destination, [(int)$memberPoint->getId()], $userId, 'local', $notice, 'all');
    importExpect(!empty($result['status']), 'The current PV editor can transfer an eligible point authored by another member.');
    $memberPoint->load($memberPoint->getId(), true);
    importExpect((int)$memberPoint->get('IDuser_author') === $memberId, 'Importing another member point preserves its author.');
    // Future meetings remain separate from the default list, including when the PV was created earlier.
    $futureEvent = new dbObject\Event();
    foreach (['IDuser' => $userId, 'IDorganization' => $organizationId, 'title' => 'Future fixture event',
        'status' => 'confirmed', 'start_at' => '2026-11-01 12:00:00', 'end_at' => '2026-11-01 13:00:00', 'active' => 1] as $field => $value) $futureEvent->set($field, $value);
    importSave($futureEvent);
    $future->set('IDevent', (int)$futureEvent->getId());
    $future->set('datecreation', '2026-09-10 12:00:00');
    $future->set('pvstage', 'preparation');
    importSave($future);
    $nearFuture = $makeDocument('2026-10-20 12:00:00');
    $nearFirst = $makePoint($nearFuture);
    $nearSecond = $makePoint($nearFuture);
    $foreignFuture = $makePoint($nearFuture, ['IDuser_author' => $memberId]);
    $makePoint($nearFuture, ['is_handled' => 1]);
    $sameDate = $makeDocument('2026-10-02 12:00:00');
    $makePoint($sameDate);
    $futureElsewhere = $makeDocument('2026-12-01 12:00:00', (int)$holon->getId());
    $elsewherePoint = $makePoint($futureElsewhere);
    $template->set('datecreation', '2026-12-05 12:00:00');
    importSave($template);
    $after = DocumentPvPoint::getImportablePage($destination, $userId, 'local', [], 2, 'mine', 'after');
    importExpect(array_column($after['items'], 'id') === [(int)$futurePoint->getId(), (int)$nearSecond->getId()], 'Future points use meeting dates, descending chronology and the mine filter.');
    $afterNext = DocumentPvPoint::getImportablePage($destination, $userId, 'local', $after['cursor'], 2, 'mine', 'after');
    importExpect(array_column($afterNext['items'], 'id') === [(int)$nearFirst->getId()] && !$afterNext['hasMore'], 'Future pagination handles date ties and excludes handled, template and same-time points.');
    $afterGlobal = DocumentPvPoint::getImportablePage($destination, $userId, 'global', [], 20, 'all', 'after');
    importExpect(array_column($afterGlobal['items'], 'id') === [(int)$elsewherePoint->getId(), (int)$futurePoint->getId(), (int)$foreignFuture->getId(), (int)$nearSecond->getId(), (int)$nearFirst->getId()], 'After combines with global scope and all-members filtering.');
    $result = DocumentPvPoint::importIntoDocument($destination, [(int)$futurePoint->getId()], $userId, 'local', $notice);
    importExpect(empty($result['status']), 'The default before filter rejects a forged future point ID.');
    $beforeSource = $makeDocument('2026-09-10 12:00:00');
    $beforePoint = $makePoint($beforeSource);
    $result = DocumentPvPoint::importIntoDocument($destination, [(int)$futurePoint->getId(), (int)$beforePoint->getId()], $userId, 'local', $notice, 'mine', 'after');
    importExpect(empty($result['status']), 'A batch mixing periods fails atomically.');
    $futurePoint->load($futurePoint->getId(), true);
    importExpect((int)$futurePoint->get('IDdocument') === (int)$future->getId(), 'Rejected mixed-period transfers preserve the future agenda.');
    $result = DocumentPvPoint::importIntoDocument($destination, [(int)$futurePoint->getId()], $userId, 'local', $notice, 'mine', 'after');
    importExpect(!empty($result['status']) && $result['pointIds'] === [(int)$futurePoint->getId()], 'Preparation points can move directly from a future meeting to the destination.');
    $futurePoint->load($futurePoint->getId(), true);
    importExpect((int)$futurePoint->get('IDdocument') === (int)$destination->getId() && !$futurePoint->isMoved(), 'A future preparation transfer leaves no source notice.');
    $result = DocumentPvPoint::importIntoDocument($destination, [(int)$nearFirst->getId()], $userId, 'local', $notice, 'mine', 'after');
    importExpect(!empty($result['status']) && $result['pointIds'] === [(int)$nearFirst->getId()], 'Meeting-stage points can also move directly from a later meeting.');
    $futureReview = $makeDocument('2026-11-15 12:00:00');
    $futureReviewPoint = $makePoint($futureReview);
    $futureReview->set('pvstage', 'review');
    importSave($futureReview);
    $result = DocumentPvPoint::importIntoDocument($destination, [(int)$futureReviewPoint->getId()], $userId, 'local', $notice, 'mine', 'after');
    importExpect(!empty($result['status']) && $result['pointIds'][0] !== (int)$futureReviewPoint->getId(), 'A future PV already in review still requires a copy.');
    $futureReviewPoint->load($futureReviewPoint->getId(), true);
    importExpect($futureReviewPoint->isMoved() && str_contains((string)$futureReviewPoint->get('content'), '#documents-d' . $destination->getId()), 'The future review PV keeps its destination link.');
    // Dynamic notices follow direct moves, but never skip a retained closed-PV step.
    $chainA = $makeDocument('2026-07-01 12:00:00');
    $chainB = $makeDocument('2026-08-01 12:00:00');
    $chainC = $makeDocument('2026-09-01 12:00:00');
    $chainD = $makeDocument('2026-10-01 12:00:00');
    $chainA->set('pvstage', 'validated');
    importSave($chainA);
    $chainB->set('pvstage', 'preparation');
    importSave($chainB);
    $chainPointA = $makePoint($chainA);
    $result = DocumentPvPoint::importIntoDocument($chainB, [(int)$chainPointA->getId()], $userId, 'local', $notice);
    importExpect(!empty($result['status']), 'The closed A point can transfer to B.');
    $chainPointA->load($chainPointA->getId(), true);
    $livePointId = $result['pointIds'][0];
    $storedNoticeA = (string)$chainPointA->get('content');
    $renderedA = $chainPointA->buildViewerData($organizationId, $userId);
    importExpect(str_contains($renderedA['contentHtml'], '#documents-d' . $chainB->getId()), 'A initially points to B.');
    $reportA = $chainA->renderPvUnhandledPointsForViewer();
    importExpect(str_contains($reportA, 'Deplace vers') && str_contains($reportA, 'Meeting 2026-08-01'), 'The unfinished card explicitly names destination B.');
    $revisionA = $chainA->getPvEditorPollingRevision($organizationId);
    $result = DocumentPvPoint::importIntoDocument($chainC, [$livePointId], $userId, 'local', $notice);
    importExpect(!empty($result['status']) && $result['pointIds'] === [$livePointId], 'The open B point moves to C with the same identity.');
    $renderedAfterDirectMove = $chainPointA->buildEditorData($organizationId, $userId);
    importExpect(str_contains($renderedAfterDirectMove['contentHtml'], '#documents-d' . $chainC->getId())
        && !str_contains($renderedAfterDirectMove['contentHtml'], '#documents-d' . $chainB->getId()), 'A dynamically points directly to C after an open-stage move.');
    importExpect(count($chainB->getPvPoints()) === 0 && (string)$chainPointA->get('content') === $storedNoticeA, 'B retains no trace, and A is rendered dynamically without rewriting its archived content.');
    importExpect($renderedA['syncVersion'] !== $renderedAfterDirectMove['syncVersion']
        && $revisionA !== $chainA->getPvEditorPollingRevision($organizationId), 'Both point sync and polling detect a changed movement destination.');
    importExpect(str_contains($chainA->getRenderedContentForCurrentViewer(), '#documents-d' . $chainC->getId()), 'The old PV reader and exports use the same dynamic destination.');
    $reportAfterMove = $chainA->renderPvUnhandledPointsForViewer();
    importExpect(str_contains($reportAfterMove, 'Meeting 2026-09-01') && !str_contains($reportAfterMove, 'Meeting 2026-08-01'), 'The final card destination name follows a direct move from B to C.');
    $chainC->set('title', 'Meeting <C> & renamed');
    importSave($chainC);
    $renamedNotice = $chainPointA->getRenderedContentForViewer($organizationId);
    importExpect(str_contains($renamedNotice, '&lt;C&gt;') && str_contains($renamedNotice, '&amp; renamed'), 'Dynamic meeting labels are current and HTML escaped.');
    $renamedReport = $chainA->renderPvUnhandledPointsForViewer();
    importExpect(str_contains($renamedReport, 'Meeting &lt;C&gt; &amp; renamed') && !str_contains($renamedReport, '<C>'), 'Final card destination names follow renaming and remain escaped.');
    $chainC->set('pvstage', 'validated');
    importSave($chainC);
    $revisionAtClosedStep = $chainA->getPvEditorPollingRevision($organizationId);
    $result = DocumentPvPoint::importIntoDocument($chainD, [$livePointId], $userId, 'local', $notice);
    importExpect(!empty($result['status']) && $result['pointIds'][0] !== $livePointId, 'Moving after C closes creates a new target in D.');
    $closedPointC = new DocumentPvPoint();
    $closedPointC->load($livePointId, true);
    $noticeA = $chainPointA->getRenderedContentForViewer($organizationId);
    importExpect(str_contains($noticeA, '#documents-d' . $chainC->getId()) && !str_contains($noticeA, '#documents-d' . $chainD->getId())
        && str_contains($closedPointC->getRenderedContentForViewer($organizationId), '#documents-d' . $chainD->getId()), 'A stops at the retained C step, and C points to D.');
    importExpect($revisionAtClosedStep === $chainA->getPvEditorPollingRevision($organizationId), 'A does not poll changes beyond its retained target step.');
    importExpect(str_contains($chainA->renderPvUnhandledPointsForViewer(), 'Meeting &lt;C&gt; &amp; renamed')
        && !str_contains($chainA->renderPvUnhandledPointsForViewer(), 'Meeting 2026-10-01'), 'The final card retains C as the named destination after C closes and transfers to D.');
    $closedBSource = $makeDocument('2026-06-01 12:00:00');
    $closedBPoint = $makePoint($closedBSource);
    $closedBSource->set('pvstage', 'validated');
    importSave($closedBSource);
    $result = DocumentPvPoint::importIntoDocument($chainB, [(int)$closedBPoint->getId()], $userId, 'local', $notice);
    importExpect(!empty($result['status']), 'The second closed source can transfer to B.');
    $closedBPoint->load($closedBPoint->getId(), true);
    $pointInBId = $result['pointIds'][0];
    $chainB->set('pvstage', 'review');
    importSave($chainB);
    $result = DocumentPvPoint::importIntoDocument($chainD, [$pointInBId], $userId, 'local', $notice);
    importExpect(!empty($result['status']), 'B already in review transfers with a retained source notice.');
    $pointInB = new DocumentPvPoint();
    $pointInB->load($pointInBId, true);
    importExpect(str_contains($closedBPoint->getRenderedContentForViewer($organizationId), '#documents-d' . $chainB->getId())
        && str_contains($pointInB->getRenderedContentForViewer($organizationId), '#documents-d' . $chainD->getId()), 'A stays linked to B whenever B retains its own movement notice.');
    $destination->set('pvstage', 'review');
    importSave($destination);
    importExpect(!DocumentPvPoint::canImportIntoDocument($destination, $userId), 'Review destination must reject import.');
    echo "pv_point_import_test: OK\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}
