<?php
declare(strict_types=1);

// Integration fixtures are rolled back; no application data is changed.
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/omo/api/documents/pv/helpers.php';

use dbObject\Document;
use dbObject\DocumentPvPoint;

function reportExpect(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

function reportSave($object): void
{
    $result = $object->save();
    reportExpect(!empty($result['status']), 'Fixture save failed: ' . json_encode($result));
}

$pdo = dbObject\DbObject::getPdo();
$pdo->beginTransaction();
try {
    $user = new dbObject\User();
    $user->set('email', 'pv-report-' . bin2hex(random_bytes(6)) . '@example.invalid');
    $user->set('firstname', 'Report <author> & text');
    reportSave($user);
    $userId = (int)$user->getId();
    $_SESSION['currentUser'] = $userId;
    $organization = new dbObject\Organization();
    $organization->set('name', 'PV report fixture');
    $organization->set('shortname', 'pvreport' . bin2hex(random_bytes(4)));
    reportSave($organization);
    $organizationId = (int)$organization->getId();
    $_SESSION['currentOrganization'] = $organizationId;
    $makeDocument = static function () use ($userId, $organizationId): Document {
        $document = new Document();
        foreach (['title' => 'Report fixture', 'documenttype' => 'pv', 'pvstage' => 'meeting',
            'IDorganization' => $organizationId, 'IDuser' => $userId, 'IDusercreation' => $userId,
            'IDuser_pv_editor' => $userId, 'active' => 1] as $key => $value) $document->set($key, $value);
        reportSave($document);
        return $document;
    };
    $makePoint = static function (Document $document, array $fields) use ($userId): DocumentPvPoint {
        $point = new DocumentPvPoint();
        foreach ($fields + ['IDdocument' => (int)$document->getId(), 'title' => 'Fixture point',
            'pointtype' => 'information', 'content' => '<p>Untreated draft content</p>',
            'IDuser_author' => $userId, 'active' => 1] as $key => $value) $point->set($key, $value);
        reportSave($point);
        return $point;
    };
    $document = $makeDocument();
    $group = $makePoint($document, ['title' => 'Mixed group', 'item_type' => 'group', 'position' => 1]);
    $untreated = $makePoint($document, ['title' => 'Unfinished <title> & text', 'position' => 1,
        'IDparent' => (int)$group->getId(), 'desired_duration_minutes' => 99, 'priority' => 1, 'pointtype' => 'consultation']);
    $handled = $makePoint($document, ['title' => 'Handled title', 'is_handled' => 1, 'position' => 2,
        'IDparent' => (int)$group->getId(), 'content' => '<p>Handled report content</p>', 'desired_duration_minutes' => 7]);
    $emptyGroup = $makePoint($document, ['title' => 'Unfinished group', 'item_type' => 'group', 'position' => 2]);
    $makePoint($document, ['title' => 'Second unfinished title', 'IDparent' => (int)$emptyGroup->getId(), 'position' => 1]);
    $confidential = $makePoint($document, ['title' => 'Secret unfinished title', 'is_confidential' => 1,
        'content' => '<p>Secret unfinished content</p>', 'position' => 3]);
    $makePoint($document, ['title' => 'Inactive title', 'active' => 0]);

    foreach (['preparation', 'meeting'] as $stage) {
        $document->set('pvstage', $stage);
        reportSave($document);
        $html = $document->getRenderedContentForCurrentViewer();
        reportExpect(str_contains($html, 'Untreated draft content') && str_contains($html, 'Secret unfinished content'), 'Open meetings retain all accessible agenda content.');
        reportExpect($document->renderPvUnhandledPointsForViewer() === '', 'Open meetings do not show the report-only list.');
    }
    foreach (['review', 'validated'] as $stage) {
        $document->set('pvstage', $stage);
        reportSave($document);
        $html = $document->getRenderedContentForCurrentViewer(['includePvDiscussionLinks' => true]);
        $listOffset = strpos($html, 'data-omo-pv-unhandled-list');
        reportExpect($listOffset !== false && $listOffset > strpos($html, 'Handled report content'), 'Unfinished titles are listed after the handled report content.');
        reportExpect(str_contains($html, 'Unfinished &lt;title&gt; &amp; text') && str_contains($html, 'Second unfinished title'), 'Public unfinished titles remain visible and escaped.');
        foreach (['Untreated draft content', 'Secret unfinished title', 'Secret unfinished content', 'Unfinished group', 'Inactive title'] as $hidden) {
            reportExpect(!str_contains($html, $hidden), 'Report must omit: ' . $hidden);
        }
        reportExpect(!str_contains(substr($html, 0, $listOffset), '99 min'), 'Unfinished duration does not affect the handled report body or timing totals.');
        $listHtml = substr($html, $listOffset);
        reportExpect(str_contains($listHtml, 'Report &lt;author&gt; &amp; text') && str_contains($listHtml, '99 min')
            && str_contains($listHtml, 'alt="Consultation"') && str_contains($listHtml, 'consultation.png')
            && str_contains($listHtml, 'generic-project-priority--p1'), 'Final cards include an escaped author, planned duration, type icon and colored priority.');
        reportExpect(!str_contains($listHtml, '<strong>Type</strong>'), 'The type is represented by an accessible icon without a redundant text field.');
        reportExpect(str_contains($html, 'Mixed group') && str_contains($html, '7 min'), 'Groups and timing of handled points remain visible.');
        $reportItems = $document->getPvReportUnhandledItems();
        reportExpect(array_column($reportItems, 'title') === ['Unfinished <title> & text', 'Second unfinished title'], 'List follows agenda order and excludes confidential, inactive and group items.');
        reportExpect($reportItems[0]['desiredDurationMinutes'] === 99 && $reportItems[0]['priority'] === 1
            && $reportItems[0]['pointType'] === 'consultation' && !array_key_exists('content', $reportItems[0]), 'Report metadata preserves the point values without including its draft content.');
        $payload = omoDocumentsPvEditorBuildContextualPointPayload($untreated, $document, $organizationId, $userId, '', [], false, [], [], '1.1');
        reportExpect($payload['isReportOmitted'] && $payload['cardHtml'] === '' && $payload['navHtml'] === '', 'Reviewed and validated editor payloads omit the unfinished card and navigation.');
        reportExpect(!str_contains(json_encode($payload), 'Untreated draft content'), 'Editor payload contains no hidden draft content.');
    }

    $onlyUntreated = $makeDocument();
    $makePoint($onlyUntreated, ['title' => 'Only unfinished title']);
    $onlyUntreated->set('pvstage', 'validated');
    reportSave($onlyUntreated);
    $html = $onlyUntreated->getRenderedContentForCurrentViewer();
    reportExpect(str_contains($html, 'Only unfinished title') && !str_contains($html, 'Untreated draft content'), 'A report without handled points still renders its final title list.');
    reportExpect(!str_contains($html, 'Duree prevue') && !str_contains($html, 'Non definie'), 'Unspecified planned duration is omitted entirely.');
    $onlySecret = $makeDocument();
    $makePoint($onlySecret, ['title' => 'Only secret title', 'is_confidential' => 1]);
    $onlySecret->set('pvstage', 'validated');
    reportSave($onlySecret);
    $html = $onlySecret->getRenderedContentForCurrentViewer();
    reportExpect(!str_contains($html, 'Only secret title') && !str_contains($html, 'data-omo-pv-unhandled-list'), 'A confidential-only report does not reveal a list, even to the editor.');

    $untreated->load($untreated->getId(), true);
    reportExpect($untreated->get('content') === '<p>Untreated draft content</p>' && !$untreated->isHandled(), 'Rendering never deletes or completes an unfinished point.');
    $future = $makeDocument();
    $future->set('datecreation', (new DateTimeImmutable())->modify('+1 day'));
    reportSave($future);
    $page = DocumentPvPoint::getImportablePage($future, $userId, 'global');
    reportExpect(in_array((int)$untreated->getId(), array_column($page['items'], 'id'), true), 'Hidden unfinished points remain available for import.');

    $document->set('pvstage', 'meeting');
    reportSave($document);
    reportExpect(str_contains($document->getRenderedContentForCurrentViewer(), 'Untreated draft content') && $document->renderPvUnhandledPointsForViewer() === '', 'Returning to meeting mode restores the agenda content.');
    echo "pv_report_unhandled_test: OK\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}
