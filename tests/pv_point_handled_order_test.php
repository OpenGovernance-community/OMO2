<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/omo/api/documents/pv/helpers.php';

use dbObject\Document;
use dbObject\DocumentPvPoint;

// Fixture-only bypass to represent historical completion dates without raw SQL.
class HandledOrderFixturePoint extends DocumentPvPoint
{
    public function saveFixtureState() { return dbObject\DbObject::save(); }
}
function handledExpect(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
function handledSave($object): void
{
    $result = $object->save();
    handledExpect(!empty($result['status']), 'Fixture save: ' . json_encode($result));
}
$pdo = dbObject\DbObject::getPdo();
$pdo->beginTransaction();
try {
    $user = new dbObject\User();
    $user->set('email', 'pv-handled-' . bin2hex(random_bytes(6)) . '@example.invalid');
    handledSave($user);
    $userId = (int)$user->getId();
    $_SESSION['currentUser'] = $userId;
    $organization = new dbObject\Organization();
    $organization->set('name', 'PV handled order');
    $organization->set('shortname', 'handled' . bin2hex(random_bytes(4)));
    handledSave($organization);
    $organizationId = (int)$organization->getId();
    $_SESSION['currentOrganization'] = $organizationId;
    $document = new Document();
    foreach (['title' => 'Chronological report', 'documenttype' => 'pv', 'pvstage' => 'meeting',
        'IDorganization' => $organizationId, 'IDuser' => $userId, 'IDusercreation' => $userId,
        'IDuser_pv_editor' => $userId, 'active' => 1] as $key => $value) $document->set($key, $value);
    handledSave($document);
    $makePoint = static function (array $values) use ($document, $userId): HandledOrderFixturePoint {
        $point = new HandledOrderFixturePoint();
        foreach ($values + ['IDdocument' => $document->getId(), 'title' => 'Point', 'pointtype' => 'information',
            'IDuser_author' => $userId, 'IDuser_modification' => $userId, 'active' => 1] as $key => $value) $point->set($key, $value);
        handledSave($point);
        return $point;
    };
    $groupA = $makePoint(['title' => 'Group A', 'item_type' => 'group', 'position' => 1]);
    $groupB = $makePoint(['title' => 'Group B', 'item_type' => 'group', 'position' => 2]);
    $late = $makePoint(['title' => 'A late', 'IDparent' => $groupA->getId(), 'position' => 1]);
    handledExpect($late->get('handled_at') === null, 'Unchecked points have no completion date.');
    $late->set('is_handled', 1);
    $late->set('handled_at', '2000-01-01 00:00:00');
    handledSave($late);
    $firstTime = $late->get('handled_at');
    handledExpect($firstTime instanceof DateTimeInterface && abs(time() - $firstTime->getTimestamp()) < 5, 'First check records server time, ignoring a supplied timestamp.');
    $late->set('handled_at', '2001-01-01 00:00:00');
    $late->set('content', '<p>Updated content</p>');
    handledSave($late);
    handledExpect($late->get('handled_at') == $firstTime, 'Edits preserve the stored completion time.');
    $late->set('is_handled', 0);
    handledSave($late);
    handledExpect($late->get('handled_at') === null, 'Unchecking clears completion time.');
    $late->set('is_handled', 1);
    $late->set('title', '');
    handledExpect(empty($late->save()['status']), 'Invalid completion fails validation.');
    $late->load($late->getId(), true);
    handledExpect(!$late->isHandled() && $late->get('handled_at') === null, 'Failed completion does not persist a timestamp.');
    $early = $makePoint(['title' => 'A early', 'IDparent' => $groupA->getId(), 'position' => 3, 'is_handled' => 1]);
    $legacy = $makePoint(['title' => 'A legacy', 'IDparent' => $groupA->getId(), 'position' => 2, 'is_handled' => 1]);
    $bLate = $makePoint(['title' => 'B late', 'IDparent' => $groupB->getId(), 'position' => 1, 'is_handled' => 1]);
    $bEarly = $makePoint(['title' => 'B early', 'IDparent' => $groupB->getId(), 'position' => 2, 'is_handled' => 1]);
    foreach ([[$late, '2026-10-16 10:20:00'], [$early, '2026-10-16 10:00:00'],
        [$legacy, null], [$bLate, '2026-10-16 10:30:00'], [$bEarly, '2026-10-16 10:10:00']] as [$point, $date]) {
        $point->set('is_handled', 1);
        $point->set('handled_at', $date);
        handledExpect(!empty($point->saveFixtureState()['status']), 'Historical fixture saved.');
    }
    handledSave($legacy);
    handledExpect($legacy->get('handled_at') === null, 'Legacy handled points stay undated after edits.');
    $late->set('is_handled', 0);
    handledSave($late);
    $late->set('is_handled', 1);
    handledSave($late);
    handledExpect(abs(time() - $late->get('handled_at')->getTimestamp()) < 5, 'Rechecking uses a new server time.');
    $late->set('handled_at', '2026-10-16 10:20:00');
    $late->saveFixtureState();
    $groupA->set('is_handled', 1);
    $groupA->set('handled_at', '2026-10-16 10:00:00');
    handledSave($groupA);
    handledExpect(!$groupA->isHandled() && $groupA->get('handled_at') === null, 'Groups never have completion timestamps.');

    $items = [$groupA, $groupB, $late, $early, $legacy, $bLate, $bEarly];
    $map = DocumentPvPoint::buildReportPositionMap($items);
    handledExpect($map[$groupA->getId()] === 1 && $map[$groupB->getId()] === 2, 'Group order stays fixed despite interleaved completion times.');
    handledExpect($map[$early->getId()] === 1 && $map[$legacy->getId()] === 2 && $map[$late->getId()] === 3, 'Dated points sort around the legacy slot.');
    $bLate->set('handled_at', $bEarly->get('handled_at'));
    $tieMap = DocumentPvPoint::buildReportPositionMap([$bLate, $bEarly]);
    handledExpect($tieMap[$bLate->getId()] === 1, 'Equal timestamps retain agenda order.');
    $bLate->load($bLate->getId(), true);
    $meetingHtml = $document->getRenderedContentForCurrentViewer();
    handledExpect(strpos($meetingHtml, 'A late') < strpos($meetingHtml, 'A early') && !str_contains($meetingHtml, 'data-omo-pv-handled-at'), 'Meeting keeps the editable agenda order.');
    $result = $document->updatePvStageInOrganizationContext($organizationId, $userId, 'review');
    handledExpect(!empty($result['status']), 'Official review transition: ' . json_encode($result));
    foreach ([$early, $legacy, $late, $bEarly, $bLate] as $point) {
        $parentId = $point->get('IDparent');
        $point->load($point->getId(), true);
        handledExpect((int)$point->get('position') === $map[$point->getId()] && $point->get('IDparent') == $parentId, 'Review transition persists order without changing groups.');
    }
    $payload = omoDocumentsPvEditorBuildContextualPointPayload($early, $document, $organizationId, $userId, '', [], false, [], [], '1.1');
    handledExpect(str_contains($payload['cardHtml'], 'data-omo-pv-handled-at') && str_contains($payload['cardHtml'], '10:00'), 'Review editor card shows completion time.');
    foreach (['review', 'validated'] as $stage) {
        if ($stage === 'validated') {
            $result = $document->updatePvStageInOrganizationContext($organizationId, $userId, $stage);
            handledExpect(!empty($result['status']), 'Official validation succeeds.');
        }
        $html = $document->getRenderedContentForCurrentViewer();
        $labels = ['Group A', 'A early', 'A legacy', 'A late', 'Group B', 'B early', 'B late'];
        $previous = -1;
        foreach ($labels as $label) {
            $offset = strpos($html, $label);
            handledExpect($offset !== false && $offset > $previous, 'Report chronological order: ' . $label);
            $previous = $offset;
        }
        handledExpect(substr_count($html, 'data-omo-pv-handled-at') === 4 && str_contains($html, '16.10.2026 a 10:20'), 'Only dated handled points show completion time.');
    }
    // Reads must still sort by completion time if legacy/direct code changes positions.
    $early->set('position', 5);
    $early->saveFixtureState();
    $html = $document->getRenderedContentForCurrentViewer();
    handledExpect(strpos($html, 'A early') < strpos($html, 'A late'), 'Validated viewer sorts timestamps independently of stored agenda order.');
    $early->load($early->getId(), true);
    handledExpect((int)$early->get('position') === 5, 'Viewing does not write positions.');
    echo "pv_point_handled_order_test: OK\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}
