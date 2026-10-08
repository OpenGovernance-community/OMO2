<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/omo/api/documents/pv/helpers.php';

function reviewExpect(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

$ui = omoDocumentsPvEditorBuildUiText();
$data = ['id' => 1, 'documentId' => 1, 'organizationId' => 1, 'isReview' => true,
    'canEditNow' => true, 'canEditPointDetails' => true, 'canAssignAuthor' => true,
    'hasStructureApplication' => true, 'title' => 'Review fixture', 'priority' => 2,
    'pointType' => 'decision', 'concernedHolonLabel' => 'Original space'];
$html = omoDocumentsPvEditorRenderPointCard($data, $ui);
foreach (['data-omo-pv-point-priority-option', 'data-omo-pv-point-type-option',
    'data-omo-pv-point-author=', 'data-omo-pv-point-concerned-holon=',
    'data-omo-pv-point-duration=', 'data-omo-pv-point-confidential='] as $control) {
    reviewExpect(!str_contains($html, $control), 'Review must freeze ' . $control);
}
reviewExpect(str_contains($html, 'data-omo-pv-point-title=') && str_contains($html, 'data-omo-pv-point-editor-host='), 'Review keeps transcript correction.');
reviewExpect(str_contains($html, 'data-omo-chat-open'), 'Review keeps error reports.');
$data['isReview'] = false;
$meetingHtml = omoDocumentsPvEditorRenderPointCard($data, $ui);
reviewExpect(str_contains($meetingHtml, 'data-omo-pv-point-priority-option') && str_contains($meetingHtml, 'data-omo-pv-point-type-option'), 'Meeting keeps point settings.');

// Exercise the real save handler's field assignments with a hostile/stale payload.
// No writes, locks, messages or external requests leave this test boundary.
$source = file_get_contents(dirname(__DIR__) . '/omo/api/documents/pv/action.php');
$start = strpos($source, '    $isReview =', strpos($source, "if (\$action === 'save_point')"));
$end = strpos($source, "    \$point->set('IDuser_modification'", $start);
reviewExpect($start !== false && $end !== false, 'Save handler boundary is available.');
$assignments = substr($source, $start, $end - $start);
$document = new class {
    public function getPvStage(): string { return \dbObject\Document::PV_STAGE_REVIEW; }
    public function isPvEditor($userId): bool { return true; }
    public function canUserManagePvStructure($organizationId, $userId): bool { return true; }
};
$point = new class {
    public array $values = ['title' => 'Original', 'content' => '<p>Original</p>', 'priority' => 2,
        'pointtype' => 'decision', 'IDuser_author' => 7, 'author_email' => null,
        'IDholon_concerned' => 8, 'desired_duration_minutes' => 15, 'is_confidential' => 1];
    public function get($key) { return $this->values[$key] ?? null; }
    public function set($key, $value): void { $this->values[$key] = $value; }
};
$before = $point->values;
$isPublicParticipation = false;
$organizationId = 1;
$currentUserId = 7;
$_POST = ['title' => 'Corrected', 'content' => '<p>Corrected</p>', 'priority' => 5,
    'pointtype' => 'information', 'author' => 'email:other@example.invalid',
    'concerned_holon_id' => 99, 'desired_duration_minutes' => 90, 'is_confidential' => 0];
eval($assignments);
reviewExpect($point->get('title') === 'Corrected' && $point->get('content') === '<p>Corrected</p>', 'Transcript corrections still save.');
foreach ($before as $field => $value) {
    if (in_array($field, ['title', 'content'], true)) continue;
    reviewExpect($point->get($field) === $value, 'Review save must preserve ' . $field);
}

$pv = new \dbObject\Document();
$pv->set('id', 123);
$pv->set('documenttype', \dbObject\Document::TYPE_PV);
$pv->set('pvstage', \dbObject\Document::PV_STAGE_REVIEW);
reviewExpect(empty(\dbObject\DocumentApplicationTab::createForDocument($pv, 1)['status']), 'Application creation is rejected in review before accessing storage.');

echo "pv_review_controls_test: OK\n";
