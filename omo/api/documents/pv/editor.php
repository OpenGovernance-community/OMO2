<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once __DIR__ . '/helpers.php';
require_once dirname(__DIR__, 4) . '/common/patreon.php';
require_once dirname(__DIR__, 4) . '/common/openai_text.php';
require_once dirname(__DIR__, 4) . '/common/object_visibility_selector.php';
require_once dirname(__DIR__, 2) . '/stats/shared.php';

$sourceLang = omoDocumentsPvEditorSourceLang();
$lang = omoLoadTranslationBundle('omo_documents_pv_editor', $sourceLang);

function omoDocumentsPvEditorT($key, array $replace = [])
{
    global $lang, $sourceLang;
    return t($key, $replace, $lang, $sourceLang);
}

$documentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$organizationId = isset($_GET['oid']) ? (int)$_GET['oid'] : (int)($_SESSION['currentOrganization'] ?? 0);
$currentUserId = (int)commonGetCurrentUserId();
$tokenSessionKey = $organizationId . ':' . $documentId . ':' . $currentUserId;
if (!isset($_SESSION['omo_pv_editor_tokens']) || !is_array($_SESSION['omo_pv_editor_tokens'])) {
    $_SESSION['omo_pv_editor_tokens'] = [];
}
$editorToken = trim((string)($_SESSION['omo_pv_editor_tokens'][$tokenSessionKey] ?? ''));
if ($editorToken === '') {
    try {
        $editorToken = bin2hex(random_bytes(16));
    } catch (\Throwable $exception) {
        $editorToken = uniqid('pv', true);
    }
    $_SESSION['omo_pv_editor_tokens'][$tokenSessionKey] = $editorToken;
}

$document = new \dbObject\Document();
$accessGranted = $documentId > 0
    && $organizationId > 0
    && $currentUserId > 0
    && $document->load($documentId)
    && $document->canUserOpenPvEditor($currentUserId, $organizationId);

$escape = 'omoApiEscape';
$uiText = omoDocumentsPvEditorBuildUiText('omoDocumentsPvEditorT');

$formatter = class_exists('IntlDateFormatter')
    ? new IntlDateFormatter('fr_FR', IntlDateFormatter::MEDIUM, IntlDateFormatter::SHORT)
    : null;

$formatDateTime = static function ($value) use ($formatter): string {
    if (!$value instanceof DateTimeInterface) {
        return '';
    }

    if ($formatter instanceof IntlDateFormatter) {
        $formatted = $formatter->format($value);
        if (is_string($formatted) && $formatted !== '') {
            return $formatted;
        }
    }

    return $value->format('d.m.Y H:i');
};

$formatProjectDate = static function ($value): string {
    return $value instanceof DateTimeInterface ? $value->format('d.m.Y') : '';
};

if (!$accessGranted) {
    http_response_code(403);
    ?>
    <div class="omo-empty-state"><?= $escape(omoDocumentsPvEditorT('documents.pv_editor.error.forbidden')) ?></div>
    <?php
    exit;
}

$event = $document->getAssociatedEvent();
$hasAssociatedEvent = $event instanceof \dbObject\Event;
$resourcePickerInitialHolonId = (int)$document->get('IDholon');
if ($resourcePickerInitialHolonId <= 0 && $hasAssociatedEvent) {
    $resourcePickerInitialHolonId = (int)$event->get('IDholon');
}
$organization = new \dbObject\Organization();
$hasOrganization = $organizationId > 0 && $organization->load($organizationId);
$projectEmbedCreateHolon = $resourcePickerInitialHolonId > 0 ? new \dbObject\Holon() : null;
if (!($projectEmbedCreateHolon instanceof \dbObject\Holon) || !$projectEmbedCreateHolon->load($resourcePickerInitialHolonId)) {
    $projectEmbedCreateHolon = $hasOrganization ? $organization->getEnabledStructuralRootHolon() : null;
}
$projectEmbedCreateHolonId = $projectEmbedCreateHolon instanceof \dbObject\Holon ? (int)$projectEmbedCreateHolon->getId() : 0;
$projectEmbedCreateHolonLabel = $projectEmbedCreateHolon instanceof \dbObject\Holon ? trim((string)$projectEmbedCreateHolon->getDisplayName()) : '';
$projectEmbedCreateResponsibleLabel = $currentUserId > 0
    ? trim((string)\dbObject\DocumentPvPoint::getUserDisplayNameForOrganization($currentUserId, $organizationId))
    : '';
$hasTeamApplication = $hasOrganization && $organization->isApplicationEnabled('team', $currentUserId);
$hasStructureApplication = $hasOrganization && $organization->isStructureApplicationEnabled($currentUserId);
$hasDocumentsApplication = $hasOrganization && $organization->isApplicationEnabled('documents', $currentUserId);
$hasDecisionApplication = $hasOrganization && $organization->isApplicationEnabled('decision', $currentUserId);
$hasCalendarApplication = $hasOrganization && $organization->isApplicationEnabled('calendar', $currentUserId);
$hasStatsApplication = $hasOrganization && $organization->isApplicationEnabled('stats', $currentUserId);
$openAiAvailable = commonOpenAiGetApiKey() !== '';
$canUseAiTools = $openAiAvailable && patreonUserCanUseAi($currentUserId);
$hasUpcomingAssociatedEvent = $hasAssociatedEvent && $event->isUpcoming();
$canManagePvStage = $document->canManagePvStage($organizationId, $currentUserId);
$pvEditorUserId = $document->getPvEditorUserId();
$pvEditorLabel = $pvEditorUserId > 0
    ? \dbObject\DocumentPvPoint::getUserDisplayNameForOrganization($pvEditorUserId, $organizationId)
    : '';
$pvCreatorUserId = $document->getCreatedByUserId();
$pvCreatorLabel = $pvCreatorUserId > 0
    ? \dbObject\DocumentPvPoint::getUserDisplayNameForOrganization($pvCreatorUserId, $organizationId)
    : '';
$isPvEditor = $document->isPvEditor($currentUserId);
$canManagePvDocument = $document->canUserManagePvDocument($currentUserId);
$isPvReview = $document->getPvStage() === \dbObject\Document::PV_STAGE_REVIEW;
$canEditPvDocumentHeader = $canManagePvDocument && !$isPvReview;
$canPassPvEditor = $isPvEditor && !$isPvReview;
$isPvTemplate = $document->isPvTemplate();
$canCreatePvGroups = $document->canUserCreatePvGroups($currentUserId);
$canClaimPvEditor = $document->canUserClaimPvEditor($organizationId, $currentUserId);
$canReplacePvEditor = $document->canUserReplacePvEditor($organizationId, $currentUserId);
$pvEditorHandoverOpen = $document->isPvEditorHandoverOpen();
$isPvValidated = $document->isPvValidated();
$locationData = $hasAssociatedEvent ? $event->getLocationDisplayData() : [];
$locationParts = [];
if (trim((string)($locationData['address'] ?? '')) !== '') {
    $locationParts[] = trim((string)$locationData['address']);
}
if (trim((string)($locationData['videoUrl'] ?? '')) !== '') {
    $locationParts[] = trim((string)$locationData['videoUrl']);
}
$eventStartAt = $hasAssociatedEvent ? $event->get('start_at') : null;
$eventEndAt = $hasAssociatedEvent ? $event->get('end_at') : null;
$documentTitle = trim((string)$document->get('title')) !== ''
    ? trim((string)$document->get('title'))
    : omoDocumentsPvEditorT('documents.pv_editor.page.title');
$documentDescription = trim((string)$document->get('description'));
$documentVisibility = $document->getVisibilityDisplayData($organizationId);
$documentVisibilityType = (string)($documentVisibility['type'] ?? \dbObject\ObjectVisibility::TYPE_ORGANIZATION);
$documentVisibilityOptions = \dbObject\ObjectVisibility::getVisibilityTypeOptions();
$documentModifiedAt = $document->get('datemodification');
$documentModifiedAtValue = $documentModifiedAt instanceof DateTimeInterface ? $documentModifiedAt->format('Y-m-d H:i:s.u') : '';
$documentSyncVersion = hash('sha256', implode('|', [
    $documentModifiedAtValue,
    $document->getPvStage(),
    (string)$pvEditorUserId,
    $pvEditorHandoverOpen ? '1' : '0',
    $documentTitle,
    $documentDescription,
    $documentVisibilityType,
    $isPvTemplate ? '1' : '0',
]));
$pollingRevision = $document->getPvEditorPollingRevision($organizationId);
$eventTitle = $hasAssociatedEvent
    ? (trim((string)$event->get('title')) !== ''
        ? trim((string)$event->get('title'))
        : ('Événement n°' . (int)$event->getId()))
    : '';
$eventSchedule = '';
if ($eventStartAt instanceof DateTimeInterface) {
    $eventSchedule = $formatDateTime($eventStartAt);
    if ($eventEndAt instanceof DateTimeInterface) {
        $eventSchedule .= $eventStartAt->format('Y-m-d') === $eventEndAt->format('Y-m-d')
            ? ' - ' . $eventEndAt->format('H:i')
            : ' - ' . $formatDateTime($eventEndAt);
    }
}
$eventLocation = implode(' | ', $locationParts);
$eventStartAtIso = $eventStartAt instanceof DateTimeInterface ? $eventStartAt->format(DATE_ATOM) : '';
$eventEndAtIso = $eventEndAt instanceof DateTimeInterface ? $eventEndAt->format(DATE_ATOM) : '';
$pvStage = $document->getPvStage();
$pvStageOptions = [
    \dbObject\Document::PV_STAGE_PREPARATION => omoDocumentsPvEditorT('documents.pv_editor.field.stage.preparation'),
    \dbObject\Document::PV_STAGE_MEETING => omoDocumentsPvEditorT('documents.pv_editor.field.stage.meeting'),
    \dbObject\Document::PV_STAGE_REVIEW => omoDocumentsPvEditorT('documents.pv_editor.field.stage.review'),
    \dbObject\Document::PV_STAGE_VALIDATED => omoDocumentsPvEditorT('documents.pv_editor.field.stage.validated'),
];
$hasPvInvitationEditor = true;
$canManagePvInvitations = $hasPvInvitationEditor
    && $pvStage === \dbObject\Document::PV_STAGE_PREPARATION
    && $canManagePvDocument;
$pvInvitationPopupUrl = '/omo/api/calendar/invitations_popup.php?oid=' . rawurlencode((string)$organizationId)
    . ($hasAssociatedEvent
        ? '&id=' . rawurlencode((string)(int)$event->getId())
        : '&document_id=' . rawurlencode((string)(int)$document->getId()))
    . '&pv_editor=1';

$points = new \dbObject\ArrayDocumentPvPoint();
$points = $document->getVisiblePvPointsForUser($currentUserId, true);
$groupSummaryMap = omoDocumentsPvEditorBuildGroupSummaryMap($points);
$pointPositionLabels = \dbObject\DocumentPvPoint::buildHierarchyPositionLabels($points);
$authorOptions = $document->getPvPointAuthorOptions($organizationId);
$pointCards = [];
$pointNavItems = [];
$pointPayloads = [];
$embeddableDocumentsPayload = [];
$embeddableDecisionsPayload = [];
$embeddableEventsPayload = [];
$embeddableIndicatorsPayload = [];
$embeddableProjectsPayload = [];
$embeddableChecklistsPayload = [];
$attendancePayload = $hasTeamApplication
    ? omoDocumentsPvEditorBuildAttendancePayloadFromDocument($document, $organizationId)
    : null;

if ($hasDocumentsApplication) {
    $embeddableDocuments = new \dbObject\ArrayDocument();
    $embeddableDocuments->loadVisibleForOrganization($organizationId);

    foreach ($embeddableDocuments as $embeddableDocument) {
        if (
            !($embeddableDocument instanceof \dbObject\Document)
            || !$embeddableDocument->canBeEmbedded()
            || (int)$embeddableDocument->getId() <= 0
            || (int)$embeddableDocument->getId() === (int)$document->getId()
        ) {
            continue;
        }

        $embeddableDocumentsPayload[] = [
            'id' => (int)$embeddableDocument->getId(),
            'contextHolonId' => (int)$embeddableDocument->get('IDholon'),
            'title' => trim((string)$embeddableDocument->get('title')),
            'description' => trim((string)$embeddableDocument->get('description')),
            'contextLabel' => trim((string)$embeddableDocument->getOrganizationContextLabel()),
        ];
    }

    usort($embeddableDocumentsPayload, static function (array $left, array $right): int {
        return strnatcasecmp((string)($left['title'] ?? ''), (string)($right['title'] ?? ''));
    });
}

if ($hasDecisionApplication) {
    $embeddableDecisions = new \dbObject\ArrayDecisionProcess();
    $decisionTypeLabels = [
        \dbObject\DecisionProcess::TYPE_DECISION => omoDocumentsPvEditorT('documents.pv_editor.decision.type.decision'),
        \dbObject\DecisionProcess::TYPE_CONSULTATION => omoDocumentsPvEditorT('documents.pv_editor.decision.type.consultation'),
    ];

    foreach ($embeddableDecisions->loadVisibleForOrganization($organizationId, $currentUserId) as $embeddableDecision) {
        if (!($embeddableDecision instanceof \dbObject\DecisionProcess) || (int)$embeddableDecision->getId() <= 0) {
            continue;
        }

        $decisionType = \dbObject\DecisionProcess::normalizeDecisionType($embeddableDecision->get('decision_type'));
        $embeddableDecisionsPayload[] = [
            'id' => (int)$embeddableDecision->getId(),
            'contextHolonId' => (int)$embeddableDecision->get('IDholon'),
            'title' => trim((string)$embeddableDecision->get('title')),
            'typeLabel' => (string)($decisionTypeLabels[$decisionType] ?? $decisionType),
            'summary' => $embeddableDecision->getCompactEmbedSummary(),
        ];
    }

    usort($embeddableDecisionsPayload, static function (array $left, array $right): int {
        return strnatcasecmp((string)($left['title'] ?? ''), (string)($right['title'] ?? ''));
    });
}

$embeddableProjects = new \dbObject\ArrayProject();
$embeddableProjects->loadForOrganization($organizationId, true, \dbObject\Project::KIND_STANDARD, true);
foreach ($embeddableProjects as $embeddableProject) {
    if (!($embeddableProject instanceof \dbObject\Project) || (int)$embeddableProject->getId() <= 0) {
        continue;
    }
    $projectHolon = $embeddableProject->getHolon();
    $projectSummary = trim(preg_replace('/\s+/', ' ', strip_tags((string)$embeddableProject->get('description'))));
    $projectResponsibleId = (int)$embeddableProject->get('IDuser');
    $projectStatus = \dbObject\Project::normalizeStatus($embeddableProject->get('status'));
    $projectStatusCatalog = \dbObject\Project::getStatusCatalog();
    $embeddableProjectsPayload[] = [
        'id' => (int)$embeddableProject->getId(),
        'contextHolonId' => (int)$embeddableProject->get('IDholon'),
        'contextLabel' => $projectHolon instanceof \dbObject\Holon ? trim((string)$projectHolon->getDisplayName()) : '',
        'title' => trim((string)$embeddableProject->get('title')),
        'summary' => $projectSummary,
        'responsibleLabel' => $projectResponsibleId > 0
            ? trim((string)\dbObject\DocumentPvPoint::getUserDisplayNameForOrganization($projectResponsibleId, $organizationId))
            : '',
        'status' => $projectStatus,
        'statusLabel' => trim((string)($projectStatusCatalog[$projectStatus]['label'] ?? $projectStatus)),
        'priorityLabel' => \dbObject\Project::normalizeLevel($embeddableProject->get('priority')) !== null
            ? 'P' . (string)\dbObject\Project::normalizeLevel($embeddableProject->get('priority'))
            : '',
        'sizeLabel' => \dbObject\Project::normalizeSize($embeddableProject->get('project_size')),
        'plannedStartLabel' => $formatProjectDate($embeddableProject->get('planned_start_date')),
        'plannedEndLabel' => $formatProjectDate($embeddableProject->get('planned_end_date')),
    ];
}
usort($embeddableProjectsPayload, static function (array $left, array $right): int {
    return strnatcasecmp((string)($left['title'] ?? ''), (string)($right['title'] ?? ''));
});

if ($hasOrganization && ($organization->isApplicationEnabled('processus', $currentUserId) || $organization->isApplicationEnabled('checklist', $currentUserId))) {
    $embeddableChecklists = new \dbObject\ArrayChecklist();
    $embeddableChecklists->loadForOrganization($organizationId, true, true);
    foreach ($embeddableChecklists as $embeddableChecklist) {
        if (!($embeddableChecklist instanceof \dbObject\Checklist) || \dbObject\Checklist::normalizeStatus($embeddableChecklist->get('status')) === \dbObject\Checklist::STATUS_RETIRED) { continue; }
        $checklistRoot = $embeddableChecklist->getTemplateRoot();
        if (!($checklistRoot instanceof \dbObject\Project)) { continue; }
        $checklistHolon = $checklistRoot->getHolon();
        $review = $embeddableChecklist->getPvReviewSummary();
        $embeddableChecklistsPayload[] = [
            'id' => (int)$embeddableChecklist->getId(),
            'contextHolonId' => (int)$checklistRoot->get('IDholon'),
            'contextLabel' => $checklistHolon instanceof \dbObject\Holon ? trim((string)$checklistHolon->getDisplayName()) : '',
            'title' => trim((string)$review['title']),
            'summary' => !empty($review['isContainer']) ? omoDocumentsPvEditorT('documents.pv_editor.checklist.review_container') : omoDocumentsPvEditorT('documents.pv_editor.checklist.review_runs'),
        ];
    }
    usort($embeddableChecklistsPayload, static fn (array $left, array $right): int => strnatcasecmp((string)($left['title'] ?? ''), (string)($right['title'] ?? '')));
}

if ($hasCalendarApplication) {
    $embeddableEvents = new \dbObject\ArrayEvent();
    $embeddableEvents->loadVisibleForOrganization($organizationId, $currentUserId);

    foreach ($embeddableEvents as $embeddableEvent) {
        if (!($embeddableEvent instanceof \dbObject\Event) || (int)$embeddableEvent->getId() <= 0) {
            continue;
        }

        $startAt = $embeddableEvent->get('start_at');
        $endAt = $embeddableEvent->get('end_at');
        $scheduleLabel = trim(
            ($startAt instanceof DateTimeInterface ? $formatDateTime($startAt) : '')
            . ($endAt instanceof DateTimeInterface ? ' - ' . $formatDateTime($endAt) : '')
        );
        $locationData = $embeddableEvent->getLocationDisplayData();
        $locationLabel = trim(implode(' | ', array_filter([
            trim((string)($locationData['address'] ?? '')),
            trim((string)($locationData['videoUrl'] ?? '')),
        ])));
        $embeddableEventsPayload[] = [
            'id' => (int)$embeddableEvent->getId(),
            'contextHolonId' => (int)$embeddableEvent->get('IDholon'),
            'title' => trim((string)$embeddableEvent->get('title')),
            'scheduleLabel' => $scheduleLabel,
            'locationLabel' => $locationLabel,
            'startAt' => $startAt instanceof DateTimeInterface ? $startAt->format(DATE_ATOM) : '',
        ];
    }

    usort($embeddableEventsPayload, static function (array $left, array $right): int {
        return strcmp((string)($left['startAt'] ?? ''), (string)($right['startAt'] ?? ''));
    });
}

if ($hasStatsApplication) {
    $embeddableIndicators = new \dbObject\ArrayStatIndicator();
    $embeddableIndicators->loadForOrganization($organizationId);

    foreach ($embeddableIndicators as $embeddableIndicator) {
        if (!($embeddableIndicator instanceof \dbObject\StatIndicator) || (int)$embeddableIndicator->getId() <= 0) {
            continue;
        }

        $embeddableIndicatorsPayload[] = omoDocumentsPvEditorBuildIndicatorEmbedPayload(
            $embeddableIndicator,
            $isPvEditor,
            'omoDocumentsPvEditorT'
        );
    }

    $embeddableIndicatorGroups = new \dbObject\ArrayStatIndicatorGroup();
    $embeddableIndicatorGroups->loadForOrganization($organizationId);
    foreach ($embeddableIndicatorGroups as $embeddableIndicatorGroup) {
        if (!($embeddableIndicatorGroup instanceof \dbObject\StatIndicatorGroup) || !$embeddableIndicatorGroup->canView() || (int)$embeddableIndicatorGroup->getId() <= 0) {
            continue;
        }

        $groupSeries = omoStatsGetGroupSeries($embeddableIndicatorGroup);
        $groupMode = \dbObject\StatIndicatorGroup::normalizeDisplayMode($embeddableIndicatorGroup->get('display_mode'));
        $groupMemberCount = count(omoStatsCollectionItems($embeddableIndicatorGroup->getItems(), \dbObject\StatIndicatorGroupItem::class));
        $groupIsOverdue = omoStatsIsGroupOverdue($embeddableIndicatorGroup);
        $embeddableIndicatorsPayload[] = [
            'id' => (int)$embeddableIndicatorGroup->getId(),
            'kind' => 'group',
            'contextHolonId' => (int)$embeddableIndicatorGroup->get('IDholon'),
            'title' => trim((string)$embeddableIndicatorGroup->get('name')),
            'contextLabel' => $groupMode === \dbObject\StatIndicatorGroup::DISPLAY_SUM
                ? omoDocumentsPvEditorT('documents.pv_editor.indicator.group_sum')
                : omoDocumentsPvEditorT('documents.pv_editor.indicator.group_overlay'),
            'valueLabel' => omoDocumentsPvEditorT('documents.pv_editor.indicator.group_members', ['count' => $groupMemberCount]),
            'dateLabel' => '',
            'statusLabel' => $groupIsOverdue
                ? omoDocumentsPvEditorT('documents.pv_editor.indicator.overdue')
                : omoDocumentsPvEditorT('documents.pv_editor.indicator.current'),
            'isOverdue' => $groupIsOverdue,
            'overdueSeverity' => $groupIsOverdue ? 'error' : 'none',
            'chartHtml' => omoStatsRenderGroupChart($embeddableIndicatorGroup, $groupSeries, 'compact', $groupIsOverdue),
        ];
    }

    usort($embeddableIndicatorsPayload, static function (array $left, array $right): int {
        return strnatcasecmp((string)($left['title'] ?? ''), (string)($right['title'] ?? ''));
    });
}

$authorHolonOptions = omoDocumentsPvEditorBuildAuthorHolonOptions(
    $document,
    $authorOptions,
    $hasStructureApplication
);
$pointDiscussionSummaryMap = $pvStage === \dbObject\Document::PV_STAGE_REVIEW
    ? omoDocumentsPvEditorBuildPointDiscussionSummaryMap($organizationId, $points, $currentUserId)
    : [];

foreach ($points as $point) {
    if (!($point instanceof \dbObject\DocumentPvPoint) || (int)$point->getId() <= 0) {
        continue;
    }

    $payload = omoDocumentsPvEditorBuildContextualPointPayload(
        $point,
        $document,
        $organizationId,
        $currentUserId,
        $editorToken,
        $uiText,
        $hasStructureApplication,
        $authorOptions,
        $authorHolonOptions,
        (string)($pointPositionLabels[(int)$point->getId()] ?? '--'),
        $groupSummaryMap[(int)$point->getId()] ?? [],
        $pointDiscussionSummaryMap[(int)$point->getId()] ?? []
    );
    if (!$point->isGroup()) {
        $pointCards[] = $payload['cardHtml'];
    }
    $pointNavItems[] = $payload['navHtml'];
    $pointPayloads[] = $payload;
}
$isPvReviewDiscussion = $pvStage === \dbObject\Document::PV_STAGE_REVIEW;
?>
<?php if ($isPvReviewDiscussion): ?>
<link rel="stylesheet" href="/common/chat/thread.css?v=20260821-pv-review-access-2">
<link rel="stylesheet" href="/common/choice/change-details.css?v=20260821-pv-review-access-2">
<?php endif; ?>
<div
    class="omo-pv-editor"
    data-omo-pv-editor-root="1"
    data-omo-pv-editor-document-id="<?= (int)$document->getId() ?>"
    data-omo-pv-editor-oid="<?= (int)$organizationId ?>"
    data-omo-pv-editor-user-id="<?= (int)$currentUserId ?>"
    data-omo-pv-editor-token="<?= $escape($editorToken) ?>"
    data-omo-pv-editor-action-url="<?= $escape('/omo/api/documents/pv/action.php') ?>"
>
    <style>
    .omo-pv-editor {
        --omo-pv-editor-sidebar-width: 280px;
        display: grid;
        grid-template-columns: minmax(220px, var(--omo-pv-editor-sidebar-width)) 10px minmax(0, 1fr);
        grid-template-rows: minmax(0, 1fr);
         min-height: 100%;
        height: 100%;
        max-height: 100%;
          background: linear-gradient(180deg, color-mix(in srgb, var(--color-surface-alt, #f8fafc) 84%, white) 0%, var(--color-bg, #eef2ff) 100%);
        overflow: hidden;
    }

    .omo-pv-editor__page-head {
        min-width: 0;
    }

    .omo-pv-editor__header-card {
        display: grid;
        gap: 20px 26px;
        align-items: start;
        grid-template-columns: minmax(270px, 1.15fr) minmax(320px, 0.95fr);
        padding: 22px 24px;
    }

    .omo-pv-editor__page-title {
        display: grid;
        grid-template-columns:  minmax(0, 1fr);
        gap: 16px;
        align-items: center;
        min-width: 0;
    }

    .omo-pv-editor__identity-icon {
        display: grid;
        place-items: center;
        width: 72px;
        height: 72px;
        border: 1px solid color-mix(in srgb, var(--color-primary, #2563eb) 12%, var(--color-border, #d1d5db));
        border-radius: var(--radius-md);
        background: linear-gradient(145deg, #ffffff, color-mix(in srgb, var(--color-primary, #2563eb) 8%, #f8fbff));
        box-shadow: 0 12px 28px -18px color-mix(in srgb, var(--color-primary, #2563eb) 48%, transparent);
    }

    .omo-pv-editor__identity-icon img {
        display: block;
        width: 46px;
        height: 46px;
        object-fit: contain;
    }

    .omo-pv-editor__page-title h2,
    .omo-pv-editor__page-title p {
        margin: 0;
    }

    .omo-pv-editor__page-title h2 {
        color: var(--color-text, #10253a);
        font-size: clamp(1.35rem, 2vw, 1.85rem);
        font-weight: 820;
        line-height: 1.15;
    }

    .omo-pv-editor__page-title p {
        color: var(--color-text-light, #64748b);
        font-size: 0.95rem;
    }

    .omo-pv-editor__document-meta-editor {
        display: grid;
        gap: 5px;
    }

    .omo-pv-editor__document-title-input,
    .omo-pv-editor__document-description-input {
        width: 100%;
        padding: 2px 0;
        border: 0;
        border-bottom: 1px solid transparent;
        border-radius: 0;
        background: transparent;
        color: inherit;
        font: inherit;
        outline: none;
    }

    .omo-pv-editor__document-title-input {
        font-size: clamp(1.35rem, 2vw, 1.85rem);
        font-weight: 820;
        line-height: 1.15;
    }

    .omo-pv-editor__document-description-input {
        min-height: 24px;
        max-height: 150px;
        resize: none;
        overflow-y: hidden;
        color: var(--color-text-light, #64748b);
        font-size: 0.95rem;
        line-height: 1.4;
    }

    .omo-pv-editor__document-title-input:focus,
    .omo-pv-editor__document-description-input:focus {
        border-bottom-color: var(--color-primary, #2563eb);
    }

    .omo-pv-editor__document-meta-actions {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
        min-height: 0;
    }

    .omo-pv-editor__document-visibility {
        display: inline-flex;
        align-items: center;
        justify-self: end;
        align-self: start;
    }

    .omo-pv-editor__document-visibility .omo-visibility-choice {
        --omo-visibility-toggle-inset: 2px;
    }

    .omo-pv-editor__document-visibility .omo-visibility-choice__button {
        min-width: 34px;
        min-height: 30px;
        padding: 5px 7px;
    }

    .omo-pv-editor__document-visibility .omo-visibility-choice__icon-shell {
        width: 16px;
        height: 16px;
        flex-basis: 16px;
    }

    .omo-pv-editor__document-visibility .omo-visibility-choice__icon {
        width: 15px;
        height: 15px;
    }

    .omo-pv-editor__document-meta-save {
        min-height: 28px;
        height: 28px;
        padding: 4px 9px;
        font-size: 0.78rem;
        line-height: 1;
    }

    .omo-pv-editor__document-meta-status {
        color: var(--color-text-light, #64748b);
        font-size: 0.78rem;
        font-weight: 600;
    }

    .omo-pv-editor__page-meta {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 8px;
    }

    .omo-pv-editor__identity-copy .omo-pv-editor__page-meta {
        justify-content: flex-start;
    }

    .omo-pv-editor__event-info {
        grid-column: 1 / -1;
        display: flex;
        align-items: center;
        gap: 12px 24px;
        min-width: 0;
        padding: 13px 0 2px;
        border-top: 1px solid color-mix(in srgb, var(--color-border, #d1d5db) 72%, transparent);
    }

    .omo-pv-editor__event-info-item {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
        color: var(--color-text-light, #64748b);
        font-size: 0.88rem;
        line-height: 1.3;
    }

    .omo-pv-editor__event-info-item + .omo-pv-editor__event-info-item {
        padding-left: 24px;
        border-left: 1px solid color-mix(in srgb, var(--color-border, #d1d5db) 64%, transparent);
    }

    .omo-pv-editor__event-info-icon {
        display: grid;
        place-items: center;
        width: 28px;
        height: 28px;
        flex: 0 0 28px;
        border-radius: var(--radius-md);
        background: color-mix(in srgb, var(--color-primary, #2563eb) 8%, white);
    }

    .omo-pv-editor__event-info-icon img {
        display: block;
        width: 17px;
        height: 17px;
        object-fit: contain;
    }

    .omo-pv-editor__event-info-value {
        min-width: 0;
        overflow-wrap: anywhere;
    }

    .omo-pv-editor__event-info-item--schedule .omo-pv-editor__event-info-value,
    .omo-pv-editor__event-info-item--location .omo-pv-editor__event-info-value {
        color: var(--color-text, #10253a);
        font-weight: 750;
    }

    .omo-pv-editor__page-side {
        justify-items: stretch;
        align-content: start;
        align-self: start;
        min-width: 0;
    }

    .omo-pv-editor__more-actions {
        position: relative;
        justify-self: end;
    }

    .omo-pv-editor__more-actions > summary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 30px;
        border: 1px solid var(--color-border, #cbd5e1);
        border-radius: var(--radius-md);
        background: var(--color-surface, #fff);
        cursor: pointer;
        font-weight: 800;
        list-style: none;
    }

    .omo-pv-editor__more-actions > summary::-webkit-details-marker {
        display: none;
    }

    .omo-pv-editor__more-actions-menu {
        position: absolute;
        z-index: 12;
        top: calc(100% + 5px);
        right: 0;
        width: max-content;
        min-width: 190px;
        padding: 6px;
        border: 1px solid var(--color-border, #cbd5e1);
        border-radius: var(--radius-md);
        background: var(--color-surface, #fff);
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.16);
    }

    .omo-pv-editor__more-actions-menu .generic-action-button {
        width: 100%;
        justify-content: flex-start;
    }

    .omo-pv-editor__header-card .omo-pv-editor__more-actions > summary {
        width: 42px;
        height: 38px;
    }

    .omo-pv-editor__meta-pill {
        display: inline-flex;
        align-items: center;
        max-width: 100%;
        padding: 8px 12px;
        border-radius: 999px;
        background: color-mix(in srgb, var(--color-surface-alt, #f8fafc) 78%, white 22%);
        color: var(--color-text-light, #475569);
        font-size: 0.88rem;
        font-weight: 600;
    }

    .omo-pv-editor__stage-field {
        display: grid;
        gap: 6px;
        width: 100%;
        justify-items: stretch;
    }

    .omo-pv-editor__stage-flow {
        display: flex;
        align-items: stretch;
        width: 100%;
        min-width: 0;
        overflow-x: auto;
        scrollbar-width: thin;
        isolation: isolate;
        padding: 1px;
        border: 1px solid color-mix(in srgb, var(--color-border, #d1d5db) 76%, white);
        border-radius: var(--radius-md);
        background: color-mix(in srgb, var(--color-surface, #fff) 92%, transparent);
    }

    .omo-pv-editor__stage-option {
        --omo-pv-stage-color: #64748b;
        position: relative;
        flex: 1 0 104px;
        min-width: 0;
        height: 46px;
        /* The extra 2px removes the anti-aliased seam between each interlocking pair. */
        margin-left: -24px;
        padding: 0 15px 0 30px;
        border: 0;
        /* The left V receives the previous step point; the right V enters the next step. */
        clip-path: polygon(11px 0, calc(100% - 11px) 0, 100% 50%, calc(100% - 11px) 100%, 11px 100%, 0 50%);
        background: color-mix(in srgb, var(--omo-pv-stage-color) 43%, white 57%);
        color: color-mix(in srgb, var(--omo-pv-stage-color) 78%, #102a3c 22%);
        font: inherit;
        font-size: 0.78rem;
        font-weight: 800;
        line-height: 1;
        letter-spacing: 0.01em;
        text-align: center;
        white-space: nowrap;
        cursor: pointer;
        transition: background 160ms ease, color 160ms ease, filter 160ms ease;
    }

    .omo-pv-editor__stage-option::before {
        display: inline-grid;
        place-items: center;
        width: 24px;
        height: 24px;
        margin-right: 6px;
        border: 1px solid color-mix(in srgb, var(--omo-pv-stage-color) 35%, white);
        border-radius: 50%;
        background: color-mix(in srgb, var(--omo-pv-stage-color) 10%, white);
        color: var(--omo-pv-stage-color);
        vertical-align: middle;
    }

    .omo-pv-editor__stage-option:nth-child(1)::before { content: "1"; }
    .omo-pv-editor__stage-option:nth-child(2)::before { content: "2"; }
    .omo-pv-editor__stage-option:nth-child(3)::before { content: "3"; }
    .omo-pv-editor__stage-option:nth-child(4)::before { content: "4"; }

    .omo-pv-editor__stage-option.is-active::before {
        border-color: color-mix(in srgb, white 72%, transparent);
        background: color-mix(in srgb, white 88%, transparent);
    }

    .omo-pv-editor__stage-option:first-child {
        margin-left: 0;
        padding-left: 14px;
        clip-path: polygon(0 0, calc(100% - 11px) 0, 100% 50%, calc(100% - 11px) 100%, 0 100%);
    }

    .omo-pv-editor__stage-option:hover:not(:disabled),
    .omo-pv-editor__stage-option:focus-visible {
        filter: brightness(0.95);
    }

    .omo-pv-editor__stage-option:focus-visible {
        outline: 2px solid var(--color-primary, #2563eb);
        outline-offset: -2px;
    }

    .omo-pv-editor__stage-option.is-active {
        background: var(--omo-pv-stage-color);
        color: #fff;
        box-shadow: inset 0 0 0 1px color-mix(in srgb, #000 16%, transparent);
    }

    /* Earlier segments sit above the following notch to form interlocking arrows. */
    .omo-pv-editor__stage-option:nth-child(1) {
        z-index: 4;
    }

    .omo-pv-editor__stage-option:nth-child(2) {
        z-index: 3;
    }

    .omo-pv-editor__stage-option:nth-child(3) {
        z-index: 2;
    }

    .omo-pv-editor__stage-option:nth-child(4) {
        z-index: 1;
    }

    .omo-pv-editor__stage-option:disabled {
        cursor: default;
    }

    .omo-pv-editor__stage-option--preparation {
        --omo-pv-stage-color: #a16207;
    }

    .omo-pv-editor__stage-option--meeting {
        --omo-pv-stage-color: #0369a1;
    }

    .omo-pv-editor__stage-option--review {
        --omo-pv-stage-color: #7e22ce;
    }

    .omo-pv-editor__stage-option--validated {
        --omo-pv-stage-color: #15803d;
    }

    .omo-pv-editor__stage-help {
        color: var(--color-text-light, #64748b);
        font-size: 0.8rem;
        text-align: right;
    }

    .omo-pv-editor__stage-choice {
        width: 100%;
        min-width: 0;
    }

    .omo-pv-editor__stage-choice-button {
        min-width: 0;
        width: 100%;
        min-height: 36px;
        padding: 7px 9px;
        font-size: 0.78rem;
        font-weight: 700;
    }

    .omo-pv-editor__stage-choice-button.is-active {
        color: var(--color-text, #1f2937);
        font-weight: 800;
    }

    .omo-pv-editor__stage-choice-button:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .omo-pv-editor__stage-choice .omo-visibility-choice__text {
        position: static;
        width: auto;
        height: auto;
        padding: 0;
        margin: 0;
        overflow: visible;
        clip: auto;
        white-space: nowrap;
    }

    .omo-pv-editor__attendance {
        display: grid;
        grid-template-columns: 48px minmax(0, 1fr);
        gap: 10px 14px;
        align-items: center;
        width: 100%;
        padding: 16px 20px;
        border: 1px solid color-mix(in srgb, var(--color-border, #d1d5db) 78%, white 22%);
        border-radius: var(--radius-md);
        background:
            radial-gradient(circle at 92% 50%, color-mix(in srgb, var(--color-primary, #2563eb) 8%, transparent), transparent 18%),
            color-mix(in srgb, var(--color-surface, #fff) 95%, white);
        box-shadow: 0 18px 40px -34px rgba(15, 23, 42, 0.42);
    }

    .omo-pv-editor__attendance-icon {
        display: grid;
        place-items: center;
        width: 48px;
        height: 48px;
        border-radius: var(--radius-md);
        background: color-mix(in srgb, var(--color-primary, #2563eb) 10%, white);
        color: var(--color-primary, #2563eb);
    }

    .omo-pv-editor__attendance-icon svg {
        width: 27px;
        height: 27px;
        fill: none;
        stroke: currentColor;
        stroke-linecap: round;
        stroke-linejoin: round;
        stroke-width: 1.8;
    }

    .omo-pv-editor__attendance-body {
        min-width: 0;
    }

    .omo-pv-editor__attendance .omo-pv-editor__field-label {
        color: var(--color-text, #10253a);
        font-size: 0.95rem;
        letter-spacing: 0;
        text-transform: none;
    }

    .omo-pv-editor__attendance-head {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 8px;
        min-width: 0;
        flex-wrap: wrap;
    }

    .omo-pv-editor__attendance-count {
        padding: 3px 8px;
        border-radius: 999px;
        background: color-mix(in srgb, var(--color-surface, #ffffff) 78%, transparent);
        color: var(--color-text-light, #475569);
        font-size: 0.74rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .omo-pv-editor__attendance-list {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px 10px;
    }

    .omo-pv-editor__attendance-item {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        min-width: 0;
    }

    .omo-pv-editor__attendance-item input {
        margin: 0;
        flex: 0 0 auto;
    }

    .omo-pv-editor__attendance-copy {
        display: inline-flex;
        align-items: baseline;
        gap: 4px;
        min-width: 0;
    }

    .omo-pv-editor__attendance-name {
        min-width: 0;
    }

    .omo-pv-editor__attendance-name {
        font-size: 0.86rem;
        font-weight: 600;
        color: var(--color-text, #0f172a);
    }

    .omo-pv-editor__attendance-empty {
        margin: 0;
        color: var(--color-text-light, #64748b);
        font-size: 0.84rem;
    }

    .omo-pv-editor__sidebar,
    .omo-pv-editor__main {
        min-width: 0;
        min-height: 0;
    }

    .omo-pv-editor__sidebar {
        display: grid;
        grid-template-rows: minmax(0, 1fr) auto;
        gap: 10px;
        align-content: start;
        overflow: hidden;
        padding-right: 4px;
    }

    .omo-pv-editor__resizer {
        width: 10px;
        min-width: 10px;
        min-height: 0;
        cursor: col-resize;
        position: relative;
        align-self: stretch;
        border: 0;
        padding: 0;
        background:
            linear-gradient(
                90deg,
                color-mix(in srgb, var(--color-border, #e5e7eb) 28%, transparent),
                color-mix(in srgb, var(--color-border, #e5e7eb) 72%, transparent),
                color-mix(in srgb, var(--color-border, #e5e7eb) 28%, transparent)
            );
    }

    .omo-pv-editor__resizer::before {
        content: "";
        position: absolute;
        top: 50%;
        left: calc(50% - 2px);
        width: 4px;
        height: 44px;
        border-radius: 999px;
        transform: translate(-50%, -50%);
        background: color-mix(in srgb, var(--color-text-light, #64748b) 55%, transparent);
    }

    .omo-pv-editor__resizer:hover {
        background:
            linear-gradient(
                90deg,
                color-mix(in srgb, var(--color-border, #e5e7eb) 22%, transparent),
                color-mix(in srgb, var(--color-border, #e5e7eb) 88%, transparent),
                color-mix(in srgb, var(--color-border, #e5e7eb) 22%, transparent)
            );
    }

    .omo-pv-editor__panel {
        --generic-section-padding-block: 14px;
        --generic-section-padding-inline: 14px;
        --generic-section-border: color-mix(in srgb, var(--color-border, #d1d5db) 84%, white 16%);
        --generic-section-background: color-mix(in srgb, var(--color-surface, #ffffff) 92%, white 8%);
        --generic-section-shadow: 0 18px 40px -32px rgba(15, 23, 42, 0.35);
    }

    .omo-pv-editor__agenda-panel {
        display: grid;
        grid-template-rows: auto minmax(0, 1fr);
        gap: 12px;
        min-height: 0;
    }

    .omo-pv-editor__field-label {
        color: var(--color-text-light, #64748b);
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.03em;
        text-transform: uppercase;
    }

    .omo-pv-editor__nav {
        align-content: start;
        grid-auto-rows: max-content;
        overflow: auto;
        min-height: 0;
        padding-right: 4px;
    }

    .omo-pv-editor__nav-row {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        gap: 0;
        align-items: stretch;
        position: relative;
        overflow: hidden;
        border: 1px solid color-mix(in srgb, var(--color-border, #d1d5db) 84%, white 16%);
        border-radius: var(--radius-md);
        background: color-mix(in srgb, var(--color-surface, #ffffff) 94%, transparent);
        transition: transform 140ms ease, margin 140ms ease;
    }

    .omo-pv-editor__nav-group {
        min-width: 0;
        position: relative;
    }

    .omo-pv-editor__group-head {
        display: grid;
        grid-template-columns: auto auto auto minmax(0, 1fr);
        grid-template-rows: auto auto;
        align-items: center;
        min-height: 38px;
        overflow: hidden;
        border: 1px solid color-mix(in srgb, var(--color-primary, #2563eb) 22%, var(--color-border, #d1d5db));
        border-radius: var(--radius-md);
        background: color-mix(in srgb, var(--color-primary, #2563eb) 7%, var(--color-surface, #fff));
        position: relative;
    }

    .omo-pv-editor__group-head > .omo-pv-editor__nav-handle {
        grid-row: 1 / -1;
        align-self: stretch;
    }

    .omo-pv-editor__group-toggle {
        display: inline-grid;
        place-items: center;
        width: 28px;
        height: 100%;
        padding: 0;
        border: 0;
        background: transparent;
        color: var(--color-text-light, #64748b);
        cursor: pointer;
    }

    .omo-pv-editor__group-toggle span {
        transition: transform 150ms ease;
    }

    .omo-pv-editor__nav-group.is-collapsed > .omo-pv-editor__group-head .omo-pv-editor__group-toggle span {
        transform: rotate(-90deg);
    }

    .omo-pv-editor__group-title,
    .omo-pv-editor__group-title-input {
        min-width: 0;
        margin: 0;
        padding: 7px 9px 7px 2px;
        border: 0;
        background: transparent;
        color: var(--color-text, #0f172a);
        font: inherit;
        font-weight: 750;
    }

    .omo-pv-editor__group-copy {
        display: contents;
        min-width: 0;
    }

    .omo-pv-editor__group-title,
    .omo-pv-editor__group-title-input {
        grid-column: 4;
        grid-row: 1;
    }

    .omo-pv-editor__group-summary {
        grid-column: 3 / -1;
        grid-row: 2;
        min-width: 0;
        padding: 0 9px 5px 0;
        overflow: hidden;
        color: var(--color-text-light, #64748b);
        font-size: 0.8rem;
        font-weight: 600;
        line-height: 1.2;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .omo-pv-editor__group-order {
        min-width: 26px;
        padding-inline: 7px;
        align-self: start;
        margin-top: 7px;
    }

    .omo-pv-editor__group-title-input:focus {
        outline: 0;
        box-shadow: inset 0 -2px var(--color-primary, #2563eb);
    }

    .omo-pv-editor__group-children,
    .omo-pv-editor__nav-root {
        align-content: start;
        min-width: 0;
    }

    .omo-pv-editor__group-children {
        margin-left: 17px;
        padding-left: 8px;
        border-left: 1px solid color-mix(in srgb, var(--color-primary, #2563eb) 22%, transparent);
    }

    .omo-pv-editor__nav-group.is-collapsed > .omo-pv-editor__group-children {
        display: none;
    }

    .omo-pv-editor__nav-row.is-handled {
        opacity: 0.8;
    }

    .omo-pv-editor__nav-row.is-focused {
        border-color: color-mix(in srgb, var(--color-primary, #2563eb) 62%, var(--color-border, #d1d5db));
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-primary, #2563eb) 16%, transparent);
    }

    .omo-pv-editor__nav-row.is-focused > .omo-pv-editor__nav-item {
        background: color-mix(in srgb, var(--color-primary, #2563eb) 10%, transparent);
    }

    .omo-pv-editor__nav-row.is-dragging {
        opacity: 0.45;
        transform: scale(0.985);
    }

    .omo-pv-editor__drop-indicator {
        position: fixed;
        z-index: 2147483000;
        pointer-events: none;
        transition: left 60ms linear, top 60ms linear, width 60ms linear, height 60ms linear;
    }

    .omo-pv-editor__drop-indicator--line {
        height: 6px;
        border-radius: 999px;
        background: var(--color-primary, #2563eb);
        box-shadow: 0 0 0 4px color-mix(in srgb, var(--color-primary, #2563eb) 18%, transparent);
    }

    .omo-pv-editor__drop-indicator--inside {
        display: grid;
        place-items: center;
        min-height: 38px;
        border: 3px solid var(--color-primary, #2563eb);
        border-radius: var(--radius-md);
        background: color-mix(in srgb, var(--color-primary, #2563eb) 15%, transparent);
        box-shadow: 0 8px 24px color-mix(in srgb, var(--color-primary, #2563eb) 22%, transparent);
        color: var(--color-primary, #2563eb);
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.03em;
        text-transform: uppercase;
    }

    .omo-pv-editor__nav-item {
        display: grid;
        gap: 2px;
        align-items: center;
        width: 100%;
        min-width: 0;
        padding: 8px 10px 8px 9px;
        border: 0;
        border-radius: 0;
        background: transparent;
        color: var(--color-text, #0f172a);
        text-align: left;
        cursor: pointer;
    }

    .omo-pv-editor__nav-item:hover,
    .omo-pv-editor__nav-row.is-drop-before .omo-pv-editor__nav-item,
    .omo-pv-editor__nav-row.is-drop-after .omo-pv-editor__nav-item {
        background: color-mix(in srgb, var(--color-primary, #2563eb) 6%, transparent);
    }

    .omo-pv-editor__nav-group.is-drop-inside > .omo-pv-editor__group-head {
        border-color: var(--color-primary, #2563eb);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-primary, #2563eb) 16%, transparent);
    }

    .omo-pv-editor__nav-order,
    .omo-pv-editor__point-ownership {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 18px;
        min-width: 26px;
        padding: 0 7px;
        border-radius: 999px;
        background: color-mix(in srgb, var(--color-surface-alt, #f8fafc) 78%, var(--color-text, #1f2937) 8%);
        color: var(--color-text-light, #475569);
        font-size: 10px;
        font-weight: 700;
    }

    .omo-pv-editor__nav-title {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 0.95rem;
    }

    .omo-pv-editor__nav-titleline {
        display: flex;
        align-items: center;
        gap: 7px;
        min-width: 0;
    }

    .omo-pv-editor__nav-point-type-icon {
        width: 16px;
        height: 16px;
        flex: 0 0 16px;
        object-fit: contain;
        filter: var(--omo-pv-type-icon-filter, none);
    }

    .omo-pv-editor__nav-meta {
        color: var(--color-text-light, #64748b);
        font-size: 0.8rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .omo-pv-editor__nav-actions {
        display: inline-flex;
        align-items: stretch;
        border-left: 1px solid color-mix(in srgb, var(--color-border, #d1d5db) 74%, white 26%);
    }

    .omo-pv-editor__nav-check {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        min-height: 44px;
        background: transparent;
        cursor: pointer;
    }

    .omo-pv-editor__nav-check input {
        margin: 0;
    }

    .omo-pv-editor__nav-check-label {
        display: none;
    }

    .omo-pv-editor__nav-handle {
        --generic-drag-handle-min-width: 28px;
        --generic-drag-handle-min-height: 100%;
        --generic-drag-handle-padding: 0;
        --generic-drag-handle-radius: 0;
        width: 22px;
        min-width: 22px;
        height: auto;
        min-height: 100%;
        justify-content: center;
        align-items: center;
        line-height: 1;
        font-size: 14px;
        writing-mode: vertical-rl;
        user-select: none;
        border-right: 1px solid color-mix(in srgb, var(--color-border, #d1d5db) 74%, white 26%);
        opacity: 0.7;
    }

    .omo-pv-editor__main {
        grid-auto-rows: max-content;
        align-content: start;
        min-height: 0;
        overflow: auto;
        padding-right: 4px;
    }

    .omo-pv-editor__toolbar {
        display: flex;
        align-items: center;
        gap: 8px;
        justify-content: flex-end;
    }

    .omo-pv-editor__auto-save {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: var(--color-text-light, #64748b);
        font-size: 0.82rem;
        font-weight: 700;
        line-height: 1.2;
        cursor: pointer;
    }

    .omo-pv-editor__auto-save input {
        width: 16px;
        height: 16px;
        margin: 0;
        accent-color: var(--color-primary, #2563eb);
        cursor: inherit;
    }

    .omo-pv-editor__auto-save input:disabled {
        cursor: not-allowed;
    }

    .omo-pv-editor__secretary-actions .omo-pv-editor__auto-save {
        flex-basis: 100%;
        justify-content: flex-end;
        margin-top: 2px;
    }

    .omo-pv-editor__delete-dropzone {
        display: none;
        width: 42px;
        height: 42px;
        min-width: 42px;
        margin-right: auto;
        padding: 9px;
        border: 1px dashed color-mix(in srgb, var(--color-danger, #dc2626) 62%, var(--color-border, #d1d5db));
        border-radius: var(--radius-md);
        background: color-mix(in srgb, var(--color-danger, #dc2626) 8%, var(--color-surface, #fff));
        color: var(--color-danger, #dc2626);
        cursor: copy;
    }

    .omo-pv-editor__delete-dropzone.is-visible {
        display: inline-grid;
        place-items: center;
    }

    .omo-pv-editor__delete-dropzone.is-active {
        background: var(--color-danger, #dc2626);
        border-color: var(--color-danger, #dc2626);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-danger, #dc2626) 18%, transparent);
    }

    .omo-pv-editor__delete-dropzone img {
        display: block;
        width: 24px;
        height: 24px;
        object-fit: contain;
    }

    .omo-pv-editor__delete-dropzone.is-active img {
        filter: brightness(0) invert(1);
    }

    .omo-pv-editor__add-button {
        width: 42px;
        height: 42px;
        min-width: 42px;
        padding: 9px;
        display: inline-grid;
        place-items: center;
    }

    .omo-pv-editor__add-button img {
        display: block;
        width: 24px;
        height: 24px;
        object-fit: contain;
    }

    .omo-pv-editor__add-button.generic-action-button--main img {
        filter: brightness(0) invert(1);
    }

    .omo-pv-editor__points {
        min-height: 0;
        overflow: visible;
        padding-right: 4px;
    }

    .omo-pv-editor__point-card {
        scroll-margin-top: 18px;
    }

    .omo-pv-editor .omo-document-pv__point-head {
        display: block;
        margin-bottom: 10px;
    }

    .omo-pv-editor .omo-document-pv__point-main {
        min-width: 0;
    }

    .omo-pv-editor .omo-document-pv__point-topline {
        display: flex;
        align-items: center;
        flex-wrap: nowrap;
        gap: 8px;
        margin-bottom: 6px;
        min-width: 0;
    }

    .omo-pv-editor .omo-document-pv__point-order {
        min-width: 0;
        min-height: 20px;
        padding: 0 7px;
        border-radius: 999px;
        font-size: 0.72rem;
        line-height: 20px;
    }

    .omo-pv-editor .omo-document-pv__point-title {
        flex: 1 1 auto;
        min-width: 80px;
        margin: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .omo-pv-editor__point-reorder-actions {
        display: inline-flex;
        align-items: center;
        gap: 2px;
        margin-left: auto;
        border: 1px solid color-mix(in srgb, var(--color-border, #d1d5db) 74%, white 26%);
        border-radius: 999px;
        overflow: hidden;
        background: color-mix(in srgb, var(--color-surface, #ffffff) 92%, transparent);
    }

    .omo-pv-editor__point-move-button {
        display: grid;
        place-items: center;
        width: 28px;
        min-height: 24px;
        border: 0;
        background: transparent;
        color: var(--color-text-light, #64748b);
        font: inherit;
        font-size: 0.76rem;
        cursor: pointer;
    }

    .omo-pv-editor__point-move-button + .omo-pv-editor__point-move-button {
        border-left: 1px solid color-mix(in srgb, var(--color-border, #d1d5db) 74%, white 26%);
    }

    .omo-pv-editor__point-move-button:hover,
    .omo-pv-editor__point-move-button:focus-visible {
        background: color-mix(in srgb, var(--color-primary, #2563eb) 10%, transparent);
        color: var(--color-primary, #2563eb);
        outline: none;
    }

    .omo-pv-editor__sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }

    .omo-pv-editor__point-title-input {
        flex: 1 1 auto;
        width: auto;
        min-width: 0;
        margin: 0;
        padding: 0;
        border: 0;
        border-radius: 0;
        background: transparent;
        color: var(--color-text, #0f172a);
        font: inherit;
        font-size: 1.28rem;
        font-weight: 800;
        line-height: 1.2;
        outline: none;
        box-shadow: none;
    }

    .omo-pv-editor__point-title-input:focus-visible {
        box-shadow: 0 2px 0 color-mix(in srgb, var(--color-primary, #2563eb) 58%, transparent);
    }

    .omo-pv-editor__point-duration-shell {
        display: inline-flex;
        align-items: baseline;
        gap: 3px;
        flex: 0 0 auto;
        color: var(--color-text-light, #64748b);
        font-size: 0.86rem;
        font-weight: 800;
        white-space: nowrap;
    }

    .omo-pv-editor__point-duration-input {
        width: 46px;
        min-width: 0;
        padding: 0;
        border: 0;
        border-radius: 0;
        background: transparent;
        color: var(--color-text, #0f172a);
        font: inherit;
        font-size: 1.08rem;
        font-weight: 900;
        line-height: 1.1;
        text-align: right;
        outline: none;
        box-shadow: none;
        appearance: textfield;
    }

    .omo-pv-editor__point-duration-input::-webkit-outer-spin-button,
    .omo-pv-editor__point-duration-input::-webkit-inner-spin-button {
        margin: 0;
        appearance: none;
    }

    .omo-pv-editor__point-duration-input:focus-visible {
        box-shadow: 0 2px 0 color-mix(in srgb, var(--color-primary, #2563eb) 58%, transparent);
    }

    .omo-pv-editor__point-duration-readonly {
        flex: 0 0 auto;
        color: var(--color-text-light, #64748b);
        font-size: 0.86rem;
        font-weight: 800;
        white-space: nowrap;
    }

    .omo-pv-editor__type-switch {
        flex: 0 0 auto;
        --omo-pv-type-icon-size: 17px;
    }

    .omo-pv-editor__type-switch-button {
        min-width: 34px;
        min-height: 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 5px 8px;
        line-height: 0;
    }

    .omo-pv-editor__point-type-icon {
        width: 16px;
        height: 16px;
        object-fit: contain;
        opacity: 0.7;
        filter: var(--omo-pv-type-icon-filter, none);
    }

    .omo-pv-editor__type-switch-button.is-active .omo-pv-editor__point-type-icon,
    .omo-pv-editor__type-switch-button[aria-checked="true"] .omo-pv-editor__point-type-icon,
    .omo-document-pv__point-type .omo-pv-editor__point-type-icon {
        opacity: 1;
    }

    .omo-pv-editor .omo-document-pv__point-type {
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    html[data-theme="dark"] .omo-pv-editor__point-type-icon {
        filter: invert(1);
    }

    html[data-theme="dark"] .omo-pv-editor__nav-point-type-icon {
        filter: invert(1);
    }

    .omo-pv-editor__field {
        display: grid;
        gap: 6px;
    }

    .omo-pv-editor__field--compact .generic-form-control {
        text-align: right;
    }

    .omo-pv-editor__point-meta-line {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        color: var(--color-text-light, #64748b);
        font-size: 0.86rem;
    }

    .omo-pv-editor__point-author,
    .omo-pv-editor__point-concerned-readonly {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .omo-pv-editor__point-concerned {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        min-width: 0;
    }

    .omo-pv-editor__point-confidential {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        color: var(--color-text-light, #64748b);
        cursor: pointer;
        font-size: 0.8rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .omo-pv-editor__point-confidential input {
        width: 15px;
        height: 15px;
        margin: 0;
    }

    .omo-pv-editor__point-author-select-shell {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        min-width: 0;
    }

    .omo-pv-editor__secretary {
        grid-column: 1 / -1;
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center;
        gap: 14px 22px;
        padding-top: 18px;
        border-top: 1px solid color-mix(in srgb, var(--color-border, #d1d5db) 74%, transparent);
    }

    .omo-pv-editor__secretary-people,
    .omo-pv-editor__secretary-actions {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px 18px;
        min-width: 0;
    }

    .omo-pv-editor__secretary-actions {
        justify-content: flex-end;
        gap: 8px;
    }

    .omo-pv-editor__person-block {
        position: relative;
        display: grid;
        grid-template-columns: 40px minmax(0, 1fr);
        gap: 10px;
        align-items: center;
        min-width: 170px;
    }

    .omo-pv-editor__person-block + .omo-pv-editor__person-block {
        padding-left: 18px;
        border-left: 1px solid color-mix(in srgb, var(--color-border, #d1d5db) 72%, transparent);
    }

    .omo-pv-editor__person-icon {
        display: grid;
        place-items: center;
        width: 40px;
        height: 40px;
        border-radius: var(--radius-md);
        background: color-mix(in srgb, var(--color-primary, #2563eb) 8%, var(--color-surface-alt, #f8fafc));
        color: color-mix(in srgb, var(--color-primary, #2563eb) 65%, var(--color-text, #0f172a));
        font-size: 0.74rem;
        font-weight: 900;
    }

    .omo-pv-editor__person-copy {
        display: grid;
        gap: 2px;
        min-width: 0;
    }

    .omo-pv-editor__person-copy .omo-pv-editor__field-label {
        text-transform: none;
        letter-spacing: 0;
    }

    .omo-pv-editor__header-card .generic-action-button {
        min-height: 38px;
    }

    .omo-pv-editor__secretary-name {
        font-weight: 750;
        color: var(--color-text, #183247);
    }

    .omo-pv-editor__secretary-state {
        display: block;
        color: var(--color-success, #15803d);
        font-size: 0.75rem;
    }

    .omo-pv-editor__secretary-state[hidden] {
        display: none;
    }

    .omo-pv-editor__secretary-state.is-waiting {
        color: var(--color-primary, #2563eb);
    }

    .omo-pv-editor__secretary-claim {
        box-shadow: 0 10px 24px -16px color-mix(in srgb, var(--color-primary, #2563eb) 72%, transparent);
    }

    .omo-pv-editor__secretary-claim.is-waiting {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        opacity: 0.9;
    }

    .omo-pv-editor__secretary-claim-spinner {
        width: 1em;
        height: 1em;
        flex: 0 0 auto;
        animation: omo-pv-editor-handover-spin 0.9s linear infinite;
    }

    @keyframes omo-pv-editor-handover-spin {
        to { transform: rotate(360deg); }
    }

    .omo-pv-editor__point-concerned-label {
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .omo-pv-editor__point-concerned-select {
        max-width: min(360px, 42vw);
        min-width: 130px;
        padding: 2px 22px 2px 8px;
        border: 1px solid color-mix(in srgb, var(--color-border, #d1d5db) 74%, white 26%);
        border-radius: 999px;
        background: color-mix(in srgb, var(--color-surface, #ffffff) 90%, transparent);
        color: var(--color-text, #0f172a);
        font: inherit;
        font-size: 0.82rem;
        font-weight: 700;
    }

    .omo-pv-editor__point-footer {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
        margin-top: 12px;
    }

    .omo-pv-editor__point-footer--readonly {
        margin-bottom: 10px;
    }

    .omo-pv-editor__point-note,
    .omo-pv-editor__point-status {
        color: var(--color-text-light, #64748b);
        font-size: 0.88rem;
        line-height: 1.4;
    }

    .omo-pv-editor__point-actions {
        display: inline-flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
    }

    .omo-pv-editor .omo-simple-html-field .note-toolbar {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 4px;
    }

    .omo-pv-editor .omo-simple-html-field .note-toolbar .omo-pv-editor__point-actions--toolbar {
        margin-left: auto;
        padding-left: 8px;
        border-left: 1px solid color-mix(in srgb, var(--color-border, #d1d5db) 76%, transparent);
    }

    .omo-pv-editor .omo-simple-html-field .note-toolbar .omo-pv-editor__save-button,
    .omo-pv-editor .omo-simple-html-field .note-toolbar .omo-pv-editor__delete-button,
    .omo-pv-editor .omo-simple-html-field .note-toolbar .omo-chat-popup-trigger {
        min-height: 28px;
        height: 28px;
    }

    .omo-pv-editor .omo-simple-html-field .note-toolbar .omo-pv-editor__save-button,
    .omo-pv-editor .omo-simple-html-field .note-toolbar .omo-chat-popup-trigger {
        padding: 4px 9px;
        font-size: 0.78rem;
        line-height: 1;
    }

    .omo-pv-editor .omo-simple-html-field .note-toolbar .omo-pv-editor__delete-button {
        width: 28px;
        min-width: 28px;
        padding: 6px;
        margin-right: 8px;
    }

    .omo-pv-editor .omo-simple-html-field .note-toolbar .omo-pv-editor__point-status {
        max-width: 130px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .omo-pv-editor__delete-button {
        width: 42px;
        height: 42px;
        min-height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 10px;
        border: 0;
        border-radius: var(--radius-md);
        background: #dc2626;
        color: #fff;
        cursor: pointer;
        box-shadow: 0 5px 12px -8px rgba(127, 29, 29, 0.9);
    }

    .omo-pv-editor__delete-button:hover,
    .omo-pv-editor__delete-button:focus-visible {
        background: #b91c1c;
        transform: translateY(-1px);
    }

    .omo-pv-editor__delete-button img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        filter: invert(1);
    }

    .omo-pv-editor__save-button:disabled {
        cursor: not-allowed;
    }

    .omo-pv-editor__save-button.is-saving:disabled {
        cursor: wait;
    }

    @media (max-width: 620px) {
        .omo-pv-editor .omo-simple-html-field .note-toolbar .omo-pv-editor__point-actions--toolbar {
            width: 100%;
            margin-left: 0;
            padding: 6px 0 0;
            border-top: 1px solid color-mix(in srgb, var(--color-border, #d1d5db) 76%, transparent);
            border-left: 0;
        }
    }

    .omo-pv-editor__empty {
        padding: 24px;
    }

    .omo-pv-editor__timing {
        position: sticky;
        bottom: 0;
        display: grid;
        gap: 8px;
        align-self: end;
        justify-items: center;
        padding: 10px;
        background:
            linear-gradient(
                180deg,
                color-mix(in srgb, var(--color-surface, #ffffff) 82%, transparent) 0%,
                color-mix(in srgb, var(--color-surface, #ffffff) 96%, white 4%) 24%
            );
        backdrop-filter: blur(6px);
    }

    .omo-pv-editor__timing-chart-shell {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr);
        gap: 12px;
        align-items: center;
        justify-items: stretch;
        width: 100%;
    }

    .omo-pv-editor__timing-chart {
        width: 116px;
        aspect-ratio: 1;
        border-radius: 50%;
        position: relative;
        background: #e2e8f0;
        box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--color-border, #d1d5db) 72%, white 28%);
        overflow: hidden;
    }

    .omo-pv-editor__timing-chart svg {
        display: block;
        width: 100%;
        height: 100%;
    }

    .omo-pv-editor__timing-center {
        position: absolute;
        inset: 0;
        z-index: 1;
        display: grid;
        place-content: center;
        text-align: center;
        pointer-events: none;
    }

    .omo-pv-editor__timing-center strong {
        color: var(--color-text, #0f172a);
        font-size: 1.35rem;
        font-weight: 900;
        line-height: 1;
    }

    .omo-pv-editor__timing-center span {
        color: var(--color-text-light, #64748b);
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .omo-pv-editor__timing-legend {
        display: grid;
        gap: 4px;
        min-width: 0;
        width: 100%;
    }

    .omo-pv-editor__timing-legend-item {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        gap: 8px;
        align-items: center;
        color: var(--color-text-light, #475569);
        font-size: 0.78rem;
        line-height: 1.2;
    }

    .omo-pv-editor__timing-legend-item[hidden] {
        display: none !important;
    }

    .omo-pv-editor__timing-legend-swatch {
        width: 10px;
        height: 10px;
        border-radius: 999px;
    }

    @media (max-width: 980px) {
        .omo-pv-editor {
            grid-template-columns: 1fr;
            grid-template-rows: auto auto auto;
            overflow: visible;
        }

        .omo-pv-editor__header-card {
            grid-template-columns: 1fr;
        }

        .omo-pv-editor__page-side,
        .omo-pv-editor__page-meta {
            justify-items: start;
            justify-content: flex-start;
        }

        .omo-pv-editor__document-visibility {
            justify-self: end;
        }

        .omo-pv-editor__event-info {
            align-items: flex-start;
        }

        .omo-pv-editor__stage-help {
            text-align: left;
        }

        .omo-pv-editor__secretary {
            grid-template-columns: 1fr;
        }

        .omo-pv-editor__secretary-actions {
            justify-content: flex-start;
        }

        .omo-pv-editor__secretary-actions .omo-pv-editor__auto-save {
            justify-content: flex-start;
        }

        .omo-pv-editor__nav {
            max-height: none;
        }

        .omo-pv-editor__sidebar,
        .omo-pv-editor__main {
            overflow: visible;
            padding-right: 0;
        }

        .omo-pv-editor__resizer {
            display: none;
        }

        .omo-pv-editor__editable-grid {
            grid-template-columns: 1fr;
        }

        .omo-pv-editor__point-footer {
            flex-direction: column;
            align-items: stretch;
        }

        .omo-pv-editor__timing-chart-shell {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 620px) {
        .omo-pv-editor__header-card {
            padding: 16px;
        }

        .omo-pv-editor__page-title {
            grid-template-columns: 52px minmax(0, 1fr);
            gap: 11px;
        }

        .omo-pv-editor__identity-icon {
            width: 52px;
            height: 52px;
            border-radius: var(--radius-md);
        }

        .omo-pv-editor__identity-icon img {
            width: 34px;
            height: 34px;
        }

        .omo-pv-editor__stage-option {
            flex-basis: 92px;
            height: 40px;
            padding-right: 11px;
            font-size: 0.7rem;
        }

        .omo-pv-editor__stage-option::before {
            display: none;
        }

        .omo-pv-editor__person-block,
        .omo-pv-editor__person-block + .omo-pv-editor__person-block {
            width: 100%;
            padding-left: 0;
            border-left: 0;
        }

        .omo-pv-editor__attendance {
            grid-template-columns: 38px minmax(0, 1fr);
            padding: 13px 14px;
        }

        .omo-pv-editor__attendance-icon {
            width: 38px;
            height: 38px;
            border-radius: var(--radius-md);
        }

        .omo-pv-editor__attendance-icon svg {
            width: 22px;
            height: 22px;
        }

        .omo-pv-editor__event-info {
            display: grid;
            grid-template-columns: 1fr;
            gap: 9px;
        }

        .omo-pv-editor__event-info-item + .omo-pv-editor__event-info-item {
            padding-left: 0;
            border-left: 0;
        }
    }

    .omo-pv-editor .note-editable .omo-pv-editor__project-review {
        display: block;
        margin: 7px 0 10px;
        padding: 7px 9px;
        border: 1px solid color-mix(in srgb, var(--color-border, #d1d5db) 84%, #2563eb 16%);
        border-radius: var(--radius-sm);
        background: color-mix(in srgb, var(--color-surface-alt, #f8fafc) 72%, var(--color-surface, #fff));
    }

    .omo-pv-editor .note-editable .omo-pv-editor__project-review-toggle {
        display: grid;
        grid-template-columns: auto minmax(70px, 1fr);
        gap: 8px;
        align-items: center;
        width: 100%;
        padding: 2px 0;
        border: 0;
        background: transparent;
        color: var(--color-text-light, #64748b);
        cursor: pointer;
        font: inherit;
        text-align: left;
    }

    .omo-pv-editor .note-editable .omo-pv-editor__project-review-label {
        font-size: 0.72rem;
        font-weight: 750;
    }

    .omo-pv-editor .note-editable .omo-project-embed__status {
        --omo-project-status-color: #99a3b1;
        --omo-project-status-text: #4d5968;
        display: inline-flex !important;
        align-items: center;
        min-height: 18px;
        padding: 2px 6px;
        border-radius: 999px;
        background: color-mix(in srgb, var(--omo-project-status-color) 19%, var(--color-surface, #fff)) !important;
        color: var(--omo-project-status-text) !important;
        font-size: 0.68rem !important;
        font-style: normal !important;
        font-weight: 750 !important;
        line-height: 1.2;
    }

    .omo-pv-editor .note-editable .omo-project-embed__status--ready { --omo-project-status-color: #5e88d5; --omo-project-status-text: #294c8b; }
    .omo-pv-editor .note-editable .omo-project-embed__status--in_progress { --omo-project-status-color: #d0a857; --omo-project-status-text: #735518; }
    .omo-pv-editor .note-editable .omo-project-embed__status--blocked { --omo-project-status-color: #d67272; --omo-project-status-text: #842f35; }
    .omo-pv-editor .note-editable .omo-project-embed__status--review { --omo-project-status-color: #9884c7; --omo-project-status-text: #5f4b91; }
    .omo-pv-editor .note-editable .omo-project-embed__status--done { --omo-project-status-color: #6fa98d; --omo-project-status-text: #2f6d4a; }
    .omo-pv-editor .note-editable .omo-project-embed__status--someday { --omo-project-status-color: #99a3b1; --omo-project-status-text: #4d5968; }

    .omo-pv-editor .note-editable .omo-project-status-bar {
        display: flex;
        width: 100%;
        height: 6px;
        min-height: 6px;
        overflow: hidden;
        border-radius: 999px;
        background: var(--color-border, #d1d5db);
    }

    .omo-pv-editor .note-editable .omo-project-status-bar__segment {
        min-width: 0;
        border-right: 2px solid color-mix(in srgb, #fff 100%, transparent);
    }

    .omo-pv-editor .note-editable .omo-project-status-bar__segment:last-child { border-right: 0; }
    .omo-pv-editor .note-editable .omo-project-status-bar__segment--ready { background: #5e88d5; }
    .omo-pv-editor .note-editable .omo-project-status-bar__segment--in_progress { background: #d0a857; }
    .omo-pv-editor .note-editable .omo-project-status-bar__segment--blocked { background: #d67272; }
    .omo-pv-editor .note-editable .omo-project-status-bar__segment--review { background: #9884c7; }
    .omo-pv-editor .note-editable .omo-project-status-bar__segment--done { background: #6fa98d; }
    .omo-pv-editor .note-editable .omo-project-status-bar__segment--someday { background: #99a3b1; }

    .omo-pv-editor .note-editable .omo-pv-editor__project-review-children {
        display: block;
        padding-top: 7px;
    }

    .omo-pv-editor .note-editable .omo-pv-editor__project-review-children[hidden] { display: none; }

    .omo-pv-editor .note-editable .omo-project-embed__children-list {
        display: grid;
        gap: 6px;
    }

    .omo-pv-editor .note-editable .omo-project-embed__child {
        display: grid;
        gap: 5px;
        padding: 6px 7px;
        border-radius: var(--radius-sm);
        background: color-mix(in srgb, var(--color-surface, #fff) 70%, var(--color-surface-alt, #f8fafc));
    }

    .omo-pv-editor .note-editable .omo-project-embed__child-head {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(65px, 38%);
        gap: 7px;
        align-items: center;
    }

    .omo-pv-editor .note-editable .omo-project-embed__child-copy {
        display: flex;
        flex-wrap: wrap;
        gap: 3px 6px;
        align-items: baseline;
        min-width: 0;
    }

    .omo-pv-editor .note-editable .omo-project-embed__child-title {
        color: var(--color-text, #0f172a);
        font-size: 0.8rem;
        font-weight: 650;
        text-decoration: none;
    }

    .omo-pv-editor .note-editable .omo-project-embed__child-meta {
        color: var(--color-text-light, #64748b);
        font-size: 0.7rem;
        white-space: nowrap;
    }

    .omo-pv-editor .note-editable .omo-project-embed__child-toggle {
        display: block;
        width: 100%;
        padding: 3px 0;
        border: 0;
        background: transparent;
        cursor: pointer;
    }

    .omo-pv-editor .note-editable .omo-project-embed__child > .omo-project-embed__children {
        display: block;
        margin-left: 8px;
        padding-top: 3px;
    }

    .omo-pv-editor .note-editable .omo-pv-editor__project-review-loading {
        display: block;
        color: var(--color-text-light, #64748b);
        font-size: 0.78rem;
    }
    </style>

    <aside class="omo-pv-editor__sidebar">
        <section class="omo-pv-editor__panel generic-section omo-pv-editor__agenda-panel">
            <div class="omo-pv-editor__toolbar">
                <button type="button" class="omo-pv-editor__delete-dropzone" data-omo-pv-delete-dropzone title="<?= $escape((string)$uiText['deleteItem']) ?>" aria-label="<?= $escape((string)$uiText['deleteItem']) ?>"<?= $isPvReview ? ' hidden' : '' ?>><img src="/omo/assets/images/documents/poubelle.png" alt="" aria-hidden="true"></button>
                <?php if ($canCreatePvGroups): ?>
                    <button type="button" class="generic-action-button generic-action-button--secondary omo-pv-editor__add-button" data-omo-pv-editor-add-group title="<?= $escape(omoDocumentsPvEditorT('documents.pv_editor.action.add_group')) ?>" aria-label="<?= $escape(omoDocumentsPvEditorT('documents.pv_editor.action.add_group')) ?>"><img src="/omo/assets/images/documents/add-folder.png" alt="" aria-hidden="true"></button>
                <?php endif; ?>
                <button type="button" class="generic-action-button generic-action-button--main omo-pv-editor__add-button" data-omo-pv-editor-add-point<?= $isPvValidated || $isPvReview ? ' disabled' : '' ?> title="<?= $escape(omoDocumentsPvEditorT('documents.pv_editor.action.add_point')) ?>" aria-label="<?= $escape(omoDocumentsPvEditorT('documents.pv_editor.action.add_point')) ?>"><img src="/omo/assets/images/documents/add.png" alt="" aria-hidden="true"></button>
            </div>
            <div class="omo-pv-editor__nav generic-stack generic-stack--compact" data-omo-pv-editor-nav>
                <?php if (count($pointNavItems) === 0): ?>
                    <div class="omo-empty-state omo-pv-editor__empty"><?= $escape(omoDocumentsPvEditorT('documents.pv_editor.nav.empty')) ?></div>
                <?php else: ?>
                    <?= implode('', $pointNavItems) ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="omo-pv-editor__panel generic-section omo-pv-editor__timing" data-omo-pv-timing-panel>
            <div class="omo-pv-editor__timing-chart-shell">
                <div class="omo-pv-editor__timing-chart" data-omo-pv-timing-chart="1">
                    <svg viewBox="0 0 100 100" aria-hidden="true" focusable="false">
                        <circle cx="50" cy="50" r="50" fill="#e2e8f0" data-omo-pv-timing-outer-base></circle>
                        <path data-omo-pv-timing-outer-handled fill="#22c55e"></path>
                        <path data-omo-pv-timing-outer-remaining fill="#f59e0b"></path>
                        <path data-omo-pv-timing-outer-buffer fill="#cbd5e1"></path>
                        <circle cx="50" cy="50" r="34" fill="#e2e8f0"></circle>
                        <path data-omo-pv-timing-sector fill="#ef4444"></path>
                    </svg>
                    <div class="omo-pv-editor__timing-center">
                        <strong data-omo-pv-timing-center><?= $escape((string)$uiText['notStartedValue']) ?></strong>

                    </div>
                </div>
                <div class="omo-pv-editor__timing-legend">
                    <div class="omo-pv-editor__timing-legend-item">
                        <span class="omo-pv-editor__timing-legend-swatch" style="background:#22c55e"></span>
                        <span><?= $escape((string)$uiText['handledLegend']) ?></span>
                        <strong data-omo-pv-timing-legend-handled><?= $escape((string)$uiText['notStartedValue']) ?></strong>
                    </div>
                    <div class="omo-pv-editor__timing-legend-item">
                        <span class="omo-pv-editor__timing-legend-swatch" style="background:#f59e0b"></span>
                        <span><?= $escape((string)$uiText['remainingLegend']) ?></span>
                        <strong data-omo-pv-timing-legend-remaining><?= $escape((string)$uiText['notStartedValue']) ?></strong>
                    </div>
                    <div class="omo-pv-editor__timing-legend-item" data-omo-pv-timing-buffer-legend<?= $hasAssociatedEvent ? '' : ' hidden' ?>>
                        <span class="omo-pv-editor__timing-legend-swatch" style="background:#cbd5e1" data-omo-pv-timing-buffer-swatch></span>
                        <span data-omo-pv-timing-buffer-label><?= $escape((string)$uiText['marginLegend']) ?></span>
                        <strong data-omo-pv-timing-legend-buffer><?= $escape((string)$uiText['notStartedValue']) ?></strong>
                    </div>
                </div>
            </div>
        </section>
    </aside>

    <button
        type="button"
        class="omo-pv-editor__resizer"
        data-omo-pv-editor-resizer
        aria-label="<?= $escape($documentTitle !== '' ? $documentTitle : omoDocumentsPvEditorT('documents.pv_editor.page.title')) ?>"
    ></button>

    <section class="omo-pv-editor__main generic-stack">
        <section class="omo-pv-editor__page-head generic-stack">
            <div class="omo-pv-editor__panel generic-section omo-pv-editor__header-card">
            <div class="omo-pv-editor__page-title">
                <div class="omo-pv-editor__identity-copy generic-stack generic-stack--compact">
                <?php if ($canEditPvDocumentHeader): ?>
                    <div class="omo-pv-editor__document-meta-editor" data-omo-pv-document-meta-editor>
                        <input
                            type="text"
                            class="omo-pv-editor__document-title-input"
                            value="<?= $escape($documentTitle) ?>"
                            aria-label="<?= $escape(omoDocumentsPvEditorT('documents.pv_editor.field.document_title')) ?>"
                            data-omo-pv-document-title
                        >
                        <textarea
                            class="omo-pv-editor__document-description-input"
                            rows="1"
                            placeholder="<?= $escape(omoDocumentsPvEditorT('documents.pv_editor.field.document_description')) ?>"
                            aria-label="<?= $escape(omoDocumentsPvEditorT('documents.pv_editor.field.document_description')) ?>"
                            data-omo-pv-document-description
                        ><?= $escape($documentDescription) ?></textarea>
                        <div class="omo-pv-editor__document-meta-actions">
                            <button type="button" class="generic-action-button omo-pv-editor__document-meta-save" data-omo-pv-document-meta-save disabled><?= $escape(omoDocumentsPvEditorT('documents.pv_editor.state.saved')) ?></button>
                            <?php if ($canUseAiTools): ?>
                                <button type="button" class="generic-action-button generic-action-button--secondary omo-pv-editor__document-meta-summary" data-omo-pv-document-auto-summary<?= $document->getPvStage() !== \dbObject\Document::PV_STAGE_REVIEW ? ' hidden' : '' ?>><?= $escape((string)$uiText['autoSummary']) ?></button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <h2 data-omo-pv-document-title-display><?= $escape($documentTitle) ?></h2>
                    <p data-omo-pv-document-description-display<?= $documentDescription === '' ? ' hidden' : '' ?>><?= $escape($documentDescription) ?></p>
                <?php endif; ?>
                </div>
            </div>
            <div class="omo-pv-editor__page-side generic-stack">
                <?php if ($canEditPvDocumentHeader): ?>
                    <div class="omo-pv-editor__document-visibility" data-omo-pv-document-visibility>
                        <?= commonRenderObjectVisibilitySelector(array(
                            'inputName' => 'pv_document_visibility',
                            'idPrefix' => 'pv-document-visibility',
                            'ariaLabel' => omoDocumentsPvEditorT('documents.pv_editor.field.document_visibility'),
                            'selectedValue' => $documentVisibilityType,
                            'optionLabels' => $documentVisibilityOptions,
                        )) ?>
                    </div>
                <?php endif; ?>
                <div class="omo-pv-editor__stage-field">
                    <div
                        class="omo-visibility-choice omo-pv-editor__stage-choice"
                        data-omo-pv-stage-choice
                        role="group"
                        aria-label="<?= $escape((string)$uiText['stage']) ?>"
                        style="--omo-visibility-option-count: <?= max(1, count($pvStageOptions)) ?>; --omo-visibility-active-index: <?= max(0, array_search($pvStage, array_keys($pvStageOptions), true)) ?>;"
                    >
                        <?php foreach ($pvStageOptions as $stageValue => $stageLabel): ?>
                            <button
                                type="button"
                                class="omo-visibility-choice__button omo-pv-editor__stage-choice-button<?= $pvStage === $stageValue ? ' is-active' : '' ?>"
                                data-omo-pv-stage-option
                                data-omo-pv-stage-value="<?= $escape($stageValue) ?>"
                                aria-pressed="<?= $pvStage === $stageValue ? 'true' : 'false' ?>"
                                <?= $canManagePvStage && (!$isPvReview || $stageValue === \dbObject\Document::PV_STAGE_VALIDATED) ? '' : ' disabled' ?>
                            ><span class="omo-visibility-choice__text"><?= $escape($stageLabel) ?></span></button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php if (!$canManagePvStage): ?>
                    <div class="omo-pv-editor__stage-help"><?= $escape((string)$uiText['stageReadonly']) ?></div>
                <?php endif; ?>
            </div>
            <?php if ($eventTitle !== '' || $eventSchedule !== '' || $eventLocation !== ''): ?>
                <div class="omo-pv-editor__event-info">
                    <?php if ($eventTitle !== ''): ?>
                        <div class="omo-pv-editor__event-info-item omo-pv-editor__event-info-item--title">
                            <span class="omo-pv-editor__event-info-icon" aria-hidden="true"><img src="/omo/assets/images/documents/event-name.png" alt="" class="black-icon"></span>
                            <span class="omo-pv-editor__event-info-value"><?= $escape($eventTitle) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($eventSchedule !== ''): ?>
                        <div class="omo-pv-editor__event-info-item omo-pv-editor__event-info-item--schedule">
                            <span class="omo-pv-editor__event-info-icon" aria-hidden="true"><img src="/omo/assets/images/documents/event-schedule.png" alt="" class="black-icon"></span>
                            <span class="omo-pv-editor__event-info-value"><?= $escape($eventSchedule) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($eventLocation !== ''): ?>
                        <div class="omo-pv-editor__event-info-item omo-pv-editor__event-info-item--location">
                            <span class="omo-pv-editor__event-info-icon" aria-hidden="true"><img src="/omo/assets/images/documents/event-location.png" alt="" class="black-icon"></span>
                            <span class="omo-pv-editor__event-info-value"><?= $escape($eventLocation) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="omo-pv-editor__secretary" data-omo-pv-secretary>
                <div class="omo-pv-editor__secretary-people">
                    <div class="omo-pv-editor__person-block">
                        <span class="omo-pv-editor__person-icon" aria-hidden="true">AU</span>
                        <span class="omo-pv-editor__person-copy">
                            <span class="omo-pv-editor__field-label"><?= $escape((string)$uiText['initialAuthor']) ?></span>
                            <span class="omo-pv-editor__secretary-name" data-omo-pv-initial-author><?= $escape($pvCreatorLabel !== '' ? $pvCreatorLabel : (string)$uiText['pvEditorEmpty']) ?></span>
                        </span>
                    </div>
                    <div class="omo-pv-editor__person-block">
                        <span class="omo-pv-editor__person-icon" aria-hidden="true">PV</span>
                        <span class="omo-pv-editor__person-copy">
                            <span class="omo-pv-editor__field-label"><?= $escape((string)$uiText['pvEditor']) ?></span>
                            <span class="omo-pv-editor__secretary-name" data-omo-pv-secretary-name><?= $escape($pvEditorLabel !== '' ? $pvEditorLabel : (string)$uiText['pvEditorEmpty']) ?></span>
                            <span class="omo-pv-editor__secretary-state<?= $isPvEditor && $pvEditorHandoverOpen ? ' is-waiting' : '' ?>" data-omo-pv-secretary-state<?= $isPvEditor ? '' : ' hidden' ?>><?= $escape($isPvEditor && $pvEditorHandoverOpen ? (string)$uiText['pvEditorHandoverWaiting'] : (string)$uiText['pvEditorActive']) ?></span>
                        </span>
                    </div>
                </div>
                <div class="omo-pv-editor__secretary-actions">
                    <button type="button" class="generic-action-button generic-action-button--main omo-pv-editor__secretary-claim<?= $isPvEditor && $pvEditorHandoverOpen ? ' is-waiting' : '' ?>" data-omo-pv-claim-secretary data-omo-pv-secretary-action="<?= $escape($isPvEditor ? 'pass_pv_editor' : ($canClaimPvEditor ? 'claim_pv_editor' : 'replace_pv_editor')) ?>"<?= (!$isPvReview && ($canPassPvEditor || $canClaimPvEditor || $canReplacePvEditor)) ? '' : ' hidden' ?><?= $isPvEditor && $pvEditorHandoverOpen ? ' disabled' : '' ?>><?php if ($isPvEditor && $pvEditorHandoverOpen): ?><svg class="omo-pv-editor__secretary-claim-spinner" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-dasharray="32 18"></circle></svg><span><?= $escape((string)$uiText['pvEditorHandoverWaiting']) ?></span><?php else: ?><?= $escape($isPvEditor ? (string)$uiText['passPvEditor'] : ($canClaimPvEditor && $pvEditorUserId > 0 ? (string)$uiText['reclaimPvEditor'] : ($canReplacePvEditor ? (string)$uiText['replacePvEditor'] : (string)$uiText['claimPvEditor']))) ?><?php endif; ?></button>
                    <?php if ($pvInvitationPopupUrl !== ''): ?>
                        <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-pv-invitations-url="<?= $escape($pvInvitationPopupUrl) ?>" data-omo-pv-invitations-title="<?= $escape((string)$uiText['inviteTitle']) ?>"<?= $canManagePvInvitations ? '' : ' hidden' ?>><?= $escape((string)$uiText['invite']) ?></button>
                    <?php endif; ?>
                    <details class="omo-pv-editor__more-actions" data-omo-pv-more-actions>
                        <summary aria-label="<?= $escape((string)$uiText['moreActions']) ?>" title="<?= $escape((string)$uiText['moreActions']) ?>">...</summary>
                        <div class="omo-pv-editor__more-actions-menu">
                            <a
                                class="generic-action-button"
                                href="<?= $escape('/omo/api/documents/pv/export_pdf.php?id=' . rawurlencode((string)(int)$document->getId()) . '&oid=' . rawurlencode((string)$organizationId)) ?>"
                                download
                            ><?= $escape((string)$uiText['exportPdf']) ?></a>
                            <?php if ($canEditPvDocumentHeader): ?>
                                <button
                                    type="button"
                                    class="generic-action-button"
                                    data-omo-pv-template-toggle
                                    data-omo-pv-template-mark-label="<?= $escape((string)$uiText['markTemplate']) ?>"
                                    data-omo-pv-template-unmark-label="<?= $escape((string)$uiText['unmarkTemplate']) ?>"
                                    data-omo-pv-template-state="<?= $isPvTemplate ? '1' : '0' ?>"
                                ><?= $escape($isPvTemplate ? (string)$uiText['unmarkTemplate'] : (string)$uiText['markTemplate']) ?></button>
                            <?php endif; ?>
                        </div>
                    </details>
                    <?php if (!$isPvReview): ?>
                        <label class="omo-pv-editor__auto-save">
                            <input type="checkbox" data-omo-pv-auto-save checked<?= $isPvValidated ? ' disabled' : '' ?>>
                            <span><?= $escape(omoDocumentsPvEditorT('documents.pv_editor.field.auto_save')) ?></span>
                        </label>
                    <?php endif; ?>
                </div>
            </div>
            </div>
            <?php if ($hasTeamApplication): ?>
            <div class="omo-pv-editor__attendance" data-omo-pv-attendance-root<?= is_array($attendancePayload) ? '' : ' hidden' ?>>
                <div class="omo-pv-editor__attendance-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M16 20v-1.5a4.5 4.5 0 0 0-4.5-4.5h-4A4.5 4.5 0 0 0 3 18.5V20"></path><circle cx="9.5" cy="7" r="3.5"></circle><path d="M17 10a3 3 0 1 0 0-6M18 14c2.2.7 3 2.2 3 4.5V20"></path></svg>
                </div>
                <div class="omo-pv-editor__attendance-body generic-stack generic-stack--compact">
                <div class="omo-pv-editor__attendance-head">
                    <span class="omo-pv-editor__field-label"><?= $escape((string)$uiText['attendance']) ?></span>
                    <span class="omo-pv-editor__attendance-count" data-omo-pv-attendance-count>
                        <?= $escape(str_replace(
                            ['{present}', '{total}'],
                            [
                                (string)(is_array($attendancePayload) ? (int)($attendancePayload['presentCount'] ?? 0) : 0),
                                (string)(is_array($attendancePayload) ? (int)($attendancePayload['totalCount'] ?? 0) : 0),
                            ],
                            (string)$uiText['attendanceCount']
                        )) ?>
                    </span>
                </div>
                <div class="omo-pv-editor__attendance-list" data-omo-pv-attendance-list>
                    <?php if (is_array($attendancePayload) && count((array)($attendancePayload['entries'] ?? [])) > 0): ?>
                        <?php foreach ((array)$attendancePayload['entries'] as $attendanceEntry): ?>
                            <?php $attendanceSecondaryLabel = trim((string)($attendanceEntry['secondaryLabel'] ?? '')); ?>
                            <label class="omo-pv-editor__attendance-item"<?= $attendanceSecondaryLabel !== '' ? ' title="' . $escape($attendanceSecondaryLabel) . '"' : '' ?>>
                                <input
                                    type="checkbox"
                                    data-omo-pv-attendance-toggle="<?= $escape((string)($attendanceEntry['identityKey'] ?? '')) ?>"
                                    <?= !$canEditPvDocumentHeader ? 'disabled' : '' ?>
                                    <?= !empty($attendanceEntry['isPresent']) ? 'checked' : '' ?>
                                >
                                <span class="omo-pv-editor__attendance-copy">
                                    <span class="omo-pv-editor__attendance-name"><?= $escape((string)($attendanceEntry['displayLabel'] ?? '')) ?></span>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <p class="omo-pv-editor__attendance-empty" data-omo-pv-attendance-empty<?= is_array($attendancePayload) && count((array)($attendancePayload['entries'] ?? [])) > 0 ? ' hidden' : '' ?>>
                    <?= $escape((string)$uiText['attendanceEmpty']) ?>
                </p>
                </div>
            </div>
            <?php endif; ?>
        </section>
        <div class="omo-pv-editor__points generic-stack" data-omo-pv-editor-points>
            <?= count($pointCards) > 0 ? implode('', $pointCards) : '' ?>
        </div>
    </section>
</div>

<?php if ($isPvReviewDiscussion): ?>
<script src="/common/choice/word-diff.js?v=20260821-pv-review-access-2"></script>
<script src="/common/choice/change-details.js?v=20260821-pv-review-access-2"></script>
<script src="/common/chat/thread.js?v=20260821-pv-review-access-2"></script>
<?php endif; ?>

<script>
(function () {
    const root = document.querySelector('[data-omo-pv-editor-root="1"]');
    if (!root || root.dataset.omoPvEditorReady === '1') {
        return;
    }

    const actionUrl = String(root.getAttribute('data-omo-pv-editor-action-url') || '').trim();
    const documentId = Number(root.getAttribute('data-omo-pv-editor-document-id') || 0);
    const organizationId = Number(root.getAttribute('data-omo-pv-editor-oid') || 0);
    const currentUserId = Number(root.getAttribute('data-omo-pv-editor-user-id') || 0);
    const editorToken = String(root.getAttribute('data-omo-pv-editor-token') || '').trim();
    const nav = root.querySelector('[data-omo-pv-editor-nav]');
    const pointsContainer = root.querySelector('[data-omo-pv-editor-points]');
    const mainPanel = root.querySelector('.omo-pv-editor__main');
    const addButton = root.querySelector('[data-omo-pv-editor-add-point]');
    const addGroupButton = root.querySelector('[data-omo-pv-editor-add-group]');
    const autoSaveToggle = root.querySelector('[data-omo-pv-auto-save]');
    const deleteDropzone = root.querySelector('[data-omo-pv-delete-dropzone]');
    const resizer = root.querySelector('[data-omo-pv-editor-resizer]');
    const stageButtons = Array.from(root.querySelectorAll('[data-omo-pv-stage-option]'));
    const stageChoice = root.querySelector('[data-omo-pv-stage-choice]');
    const attendanceRoot = root.querySelector('[data-omo-pv-attendance-root]');
    const attendanceList = root.querySelector('[data-omo-pv-attendance-list]');
    const attendanceCount = root.querySelector('[data-omo-pv-attendance-count]');
    const attendanceEmpty = root.querySelector('[data-omo-pv-attendance-empty]');
    const secretaryName = root.querySelector('[data-omo-pv-secretary-name]');
    const secretaryState = root.querySelector('[data-omo-pv-secretary-state]');
    const claimSecretaryButton = root.querySelector('[data-omo-pv-claim-secretary]');
    const templateToggleButton = root.querySelector('[data-omo-pv-template-toggle]');
    const invitationsButton = root.querySelector('[data-omo-pv-invitations-url]');
    const documentTitleInput = root.querySelector('[data-omo-pv-document-title]');
    const documentDescriptionInput = root.querySelector('[data-omo-pv-document-description]');
    const documentVisibilitySelect = root.querySelector('[data-omo-pv-document-visibility]');
    const documentMetaSaveButton = root.querySelector('[data-omo-pv-document-meta-save]');
    const documentAutoSummaryButton = root.querySelector('[data-omo-pv-document-auto-summary]');
    const documentMetaStatus = root.querySelector('[data-omo-pv-document-meta-status]');
    const documentTitleDisplay = root.querySelector('[data-omo-pv-document-title-display]');
    const documentDescriptionDisplay = root.querySelector('[data-omo-pv-document-description-display]');
    const initialPointPayloads = <?= json_encode($pointPayloads, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const initialDocumentPayload = <?= json_encode([
        'pvStage' => $pvStage,
        'pvStageLabel' => (string)($pvStageOptions[$pvStage] ?? ''),
        'title' => $documentTitle,
        'description' => $documentDescription,
        'visibilityType' => $documentVisibilityType,
        'syncVersion' => $documentSyncVersion,
        'canManagePvStage' => $canManagePvStage,
        'pvEditorUserId' => $pvEditorUserId,
        'pvEditorLabel' => $pvEditorLabel,
        'pvCreatorUserId' => $pvCreatorUserId,
        'pvCreatorLabel' => $pvCreatorLabel,
        'isPvEditor' => $isPvEditor,
        'canManagePvDocument' => $canManagePvDocument,
        'canClaimPvEditor' => $canClaimPvEditor,
        'canReplacePvEditor' => $canReplacePvEditor,
        'pvEditorHandoverOpen' => $pvEditorHandoverOpen,
        'isPvValidated' => $isPvValidated,
        'isPvTemplate' => $isPvTemplate,
        'canManagePvTemplate' => $canEditPvDocumentHeader,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const initialAttendancePayload = <?= json_encode($attendancePayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const initialPollingRevision = <?= json_encode($pollingRevision, JSON_UNESCAPED_SLASHES) ?>;
    const attendanceEnabled = <?= json_encode($hasTeamApplication) ?>;
    const eventStartAtIso = <?= json_encode($eventStartAtIso, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const eventEndAtIso = <?= json_encode($eventEndAtIso, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const saveLabel = <?= json_encode(omoDocumentsPvEditorT('documents.pv_editor.action.save'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const savingLabel = <?= json_encode(omoDocumentsPvEditorT('documents.pv_editor.action.saving'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const savedLabel = <?= json_encode(omoDocumentsPvEditorT('documents.pv_editor.state.saved'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const autoSummaryLoadingLabel = <?= json_encode((string)$uiText['autoSummaryLoading'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const autoSummaryReadyLabel = <?= json_encode((string)$uiText['autoSummaryReady'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const autoSummaryAvailable = <?= $canUseAiTools ? 'true' : 'false' ?>;
    let autoSummaryPending = false;
    const dirtyLabel = <?= json_encode(omoDocumentsPvEditorT('documents.pv_editor.state.dirty'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const unsavedCloseMessage = <?= json_encode(omoDocumentsPvEditorT('documents.pv_editor.warning.unsaved_close'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const deletePointMessage = <?= json_encode(omoDocumentsPvEditorT('documents.pv_editor.warning.delete_point'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const deleteItemMessage = <?= json_encode((string)$uiText['deleteItemMessage'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const validateIrreversibleMessage = <?= json_encode(omoDocumentsPvEditorT('documents.pv_editor.warning.validate_irreversible'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const unsavedHandoverMessage = <?= json_encode((string)$uiText['unsavedHandover'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const notStartedValue = <?= json_encode((string)$uiText['notStartedValue'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const marginLegendLabel = <?= json_encode((string)$uiText['marginLegend'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const overrunLegendLabel = <?= json_encode((string)$uiText['overrunLegend'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const groupPointsLabel = <?= json_encode((string)$uiText['groupPoints'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const groupMinutesLabel = <?= json_encode((string)$uiText['groupMinutes'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const autoSaveDelayMs = 10000;
    const activeSyncPollDelayMs = 5000;
    const idleSyncPollDelayMs = 15000;
    const autoSaveTimers = new Map();
    const pointChangeVersions = new Map();
    let documentMetadataAutoSaveTimer = null;
    let documentMetadataChangeVersion = 0;
    let documentMetadataSaving = false;
    const canEmbedDocuments = <?= $hasDocumentsApplication ? 'true' : 'false' ?>;
    const resourcePickerOrganizationId = <?= (int)$organizationId ?>;
    const resourcePickerInitialHolonId = <?= (int)$resourcePickerInitialHolonId ?>;
    const resourcePickerScopeUi = <?= json_encode([
        'local' => omoDocumentsPvEditorT('documents.pv_editor.embed.scope_local'),
        'children' => omoDocumentsPvEditorT('documents.pv_editor.embed.scope_children'),
        'descendants' => omoDocumentsPvEditorT('documents.pv_editor.embed.scope_descendants'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const embeddableDocuments = <?= json_encode($embeddableDocumentsPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const documentEmbedUi = <?= json_encode([
        'buttonTitle' => omoDocumentsPvEditorT('documents.pv_editor.embed.button_title'),
        'modalTitle' => omoDocumentsPvEditorT('documents.pv_editor.embed.modal_title'),
        'search' => omoDocumentsPvEditorT('documents.pv_editor.embed.search'),
        'searchPlaceholder' => omoDocumentsPvEditorT('documents.pv_editor.embed.search_placeholder'),
        'quickSearchPlaceholder' => omoDocumentsPvEditorT('documents.pv_editor.embed.quick_search_placeholder'),
        'visibleDocuments' => omoDocumentsPvEditorT('documents.pv_editor.embed.visible_documents'),
        'none' => omoDocumentsPvEditorT('documents.pv_editor.embed.none'),
        'insert' => omoDocumentsPvEditorT('documents.pv_editor.embed.insert'),
        'cancel' => omoDocumentsPvEditorT('documents.pv_editor.action.cancel'),
        'remove' => omoDocumentsPvEditorT('documents.pv_editor.action.remove_embed'),
        'linkedLabel' => omoDocumentsPvEditorT('documents.pv_editor.embed.linked_label'),
        'openExternal' => omoDocumentsPvEditorT('documents.pv_editor.embed.open_external'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const canEmbedDecisions = <?= $hasDecisionApplication ? 'true' : 'false' ?>;
    const embeddableDecisions = <?= json_encode($embeddableDecisionsPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const decisionEmbedUi = <?= json_encode([
        'buttonTitle' => omoDocumentsPvEditorT('documents.pv_editor.decision.button_title'),
        'modalTitle' => omoDocumentsPvEditorT('documents.pv_editor.decision.modal_title'),
        'search' => omoDocumentsPvEditorT('documents.pv_editor.embed.search'),
        'searchPlaceholder' => omoDocumentsPvEditorT('documents.pv_editor.embed.search_placeholder'),
        'quickSearchPlaceholder' => omoDocumentsPvEditorT('documents.pv_editor.embed.quick_search_placeholder'),
        'visibleDecisions' => omoDocumentsPvEditorT('documents.pv_editor.decision.visible'),
        'none' => omoDocumentsPvEditorT('documents.pv_editor.embed.none'),
        'insert' => omoDocumentsPvEditorT('documents.pv_editor.decision.insert'),
        'cancel' => omoDocumentsPvEditorT('documents.pv_editor.action.cancel'),
        'remove' => omoDocumentsPvEditorT('documents.pv_editor.action.remove_embed'),
        'linkedLabel' => omoDocumentsPvEditorT('documents.pv_editor.decision.linked_label'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const canEmbedProjects = true;
    const embeddableProjects = <?= json_encode($embeddableProjectsPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const projectEmbedUi = <?= json_encode([
        'buttonTitle' => omoDocumentsPvEditorT('documents.pv_editor.project.button_title'),
        'modalTitle' => omoDocumentsPvEditorT('documents.pv_editor.project.modal_title'),
        'search' => omoDocumentsPvEditorT('documents.pv_editor.embed.search'),
        'quickSearchPlaceholder' => omoDocumentsPvEditorT('documents.pv_editor.embed.quick_search_placeholder'),
        'visibleProjects' => omoDocumentsPvEditorT('documents.pv_editor.project.visible'),
        'none' => omoDocumentsPvEditorT('documents.pv_editor.embed.none'),
        'insert' => omoDocumentsPvEditorT('documents.pv_editor.project.insert'),
        'cancel' => omoDocumentsPvEditorT('documents.pv_editor.action.cancel'),
        'remove' => omoDocumentsPvEditorT('documents.pv_editor.action.remove_embed'),
        'linkedLabel' => omoDocumentsPvEditorT('documents.pv_editor.project.linked_label'),
        'openExternal' => omoDocumentsPvEditorT('documents.pv_editor.embed.open_external'),
        'plannedDate' => omoDocumentsPvEditorT('documents.pv_editor.project.planned_date', ['date' => '{date}']),
        'endDate' => omoDocumentsPvEditorT('documents.pv_editor.project.end_date', ['date' => '{date}']),
        'tabExisting' => omoDocumentsPvEditorT('documents.pv_editor.project.tab_existing'),
        'tabNew' => omoDocumentsPvEditorT('documents.pv_editor.project.tab_new'),
        'tabsAria' => omoDocumentsPvEditorT('documents.pv_editor.project.tabs_aria'),
        'titleLabel' => omoDocumentsPvEditorT('documents.pv_editor.project.title'),
        'descriptionLabel' => omoDocumentsPvEditorT('documents.pv_editor.project.description'),
        'statusLabel' => omoDocumentsPvEditorT('documents.pv_editor.project.status'),
        'priorityLabel' => omoDocumentsPvEditorT('documents.pv_editor.project.priority'),
        'sizeLabel' => omoDocumentsPvEditorT('documents.pv_editor.project.size'),
        'startDateLabel' => omoDocumentsPvEditorT('documents.pv_editor.project.start_date'),
        'endDateLabel' => omoDocumentsPvEditorT('documents.pv_editor.project.end_date_label'),
        'holonLabel' => omoDocumentsPvEditorT('documents.pv_editor.project.holon'),
        'responsibleLabel' => omoDocumentsPvEditorT('documents.pv_editor.project.responsible'),
        'responsibleEmpty' => omoDocumentsPvEditorT('documents.pv_editor.project.responsible_empty'),
        'membersLoading' => omoDocumentsPvEditorT('documents.pv_editor.project.members_loading'),
        'membersEmpty' => omoDocumentsPvEditorT('documents.pv_editor.project.members_empty'),
        'createInsert' => omoDocumentsPvEditorT('documents.pv_editor.project.create_insert'),
        'createError' => omoDocumentsPvEditorT('documents.pv_editor.project.create_error'),
        'childrenLabel' => omoDocumentsPvEditorT('documents.pv_editor.project.children'),
        'childrenLoading' => omoDocumentsPvEditorT('documents.pv_editor.project.children_loading'),
        'childrenEmpty' => omoDocumentsPvEditorT('documents.pv_editor.project.children_empty'),
        'childrenError' => omoDocumentsPvEditorT('documents.pv_editor.project.children_error'),
        'statusOptions' => array_map(static fn ($status, $definition) => ['value' => $status, 'label' => (string)($definition['label'] ?? $status)], array_keys(\dbObject\Project::getStatusCatalog()), \dbObject\Project::getStatusCatalog()),
        'sizeOptions' => \dbObject\Project::sizes(),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const projectEmbedCreateContext = <?= json_encode([
        'organizationId' => $organizationId,
        'holonId' => $projectEmbedCreateHolonId,
        'holonLabel' => $projectEmbedCreateHolonLabel,
        'responsibleId' => $currentUserId,
        'responsibleLabel' => $projectEmbedCreateResponsibleLabel,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const canEmbedChecklists = <?= count($embeddableChecklistsPayload) > 0 ? 'true' : 'false' ?>;
    const canCompleteChecklistProjects = <?= $canEditPvDocumentHeader ? 'true' : 'false' ?>;
    const embeddableChecklists = <?= json_encode($embeddableChecklistsPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const checklistEmbedUi = <?= json_encode([
        'buttonTitle' => omoDocumentsPvEditorT('documents.pv_editor.checklist.button_title'),
        'modalTitle' => omoDocumentsPvEditorT('documents.pv_editor.checklist.modal_title'),
        'quickSearchPlaceholder' => omoDocumentsPvEditorT('documents.pv_editor.embed.quick_search_placeholder'),
        'visible' => omoDocumentsPvEditorT('documents.pv_editor.checklist.visible'),
        'none' => omoDocumentsPvEditorT('documents.pv_editor.embed.none'),
        'insert' => omoDocumentsPvEditorT('documents.pv_editor.checklist.insert'),
        'cancel' => omoDocumentsPvEditorT('documents.pv_editor.action.cancel'),
        'remove' => omoDocumentsPvEditorT('documents.pv_editor.action.remove_embed'),
        'completeArchive' => omoDocumentsPvEditorT('documents.pv_editor.checklist.complete_archive'),
        'completeArchiving' => omoDocumentsPvEditorT('documents.pv_editor.checklist.complete_archiving'),
        'completeArchiveError' => omoDocumentsPvEditorT('documents.pv_editor.checklist.complete_archive_error'),
        'emptyRuns' => omoDocumentsPvEditorT('documents.pv_editor.checklist.empty_runs'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const checklistRunReviewCache = new Map();
    const canEmbedEvents = <?= $hasCalendarApplication ? 'true' : 'false' ?>;
    const embeddableEvents = <?= json_encode($embeddableEventsPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const eventEmbedUi = <?= json_encode([
        'buttonTitle' => omoDocumentsPvEditorT('documents.pv_editor.event.button_title'),
        'modalTitle' => omoDocumentsPvEditorT('documents.pv_editor.event.modal_title'),
        'search' => omoDocumentsPvEditorT('documents.pv_editor.embed.search'),
        'searchPlaceholder' => omoDocumentsPvEditorT('documents.pv_editor.embed.search_placeholder'),
        'quickSearchPlaceholder' => omoDocumentsPvEditorT('documents.pv_editor.embed.quick_search_placeholder'),
        'visibleEvents' => omoDocumentsPvEditorT('documents.pv_editor.event.visible'),
        'none' => omoDocumentsPvEditorT('documents.pv_editor.embed.none'),
        'insert' => omoDocumentsPvEditorT('documents.pv_editor.event.insert'),
        'cancel' => omoDocumentsPvEditorT('documents.pv_editor.action.cancel'),
        'remove' => omoDocumentsPvEditorT('documents.pv_editor.action.remove_embed'),
        'tabExisting' => omoDocumentsPvEditorT('documents.pv_editor.event.tab_existing'),
        'tabNew' => omoDocumentsPvEditorT('documents.pv_editor.event.tab_new'),
        'tabsAria' => omoDocumentsPvEditorT('documents.pv_editor.event.tabs_aria'),
        'titleLabel' => omoDocumentsPvEditorT('documents.pv_editor.event.title'),
        'descriptionLabel' => omoDocumentsPvEditorT('documents.pv_editor.event.description'),
        'startAtLabel' => omoDocumentsPvEditorT('documents.pv_editor.event.start_at'),
        'endAtLabel' => omoDocumentsPvEditorT('documents.pv_editor.event.end_at'),
        'createInsert' => omoDocumentsPvEditorT('documents.pv_editor.event.create_insert'),
        'createError' => omoDocumentsPvEditorT('documents.pv_editor.event.create_error'),
        'endAfterStart' => omoDocumentsPvEditorT('documents.pv_editor.event.end_after_start'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const eventEmbedCreateContext = <?= json_encode([
        'organizationId' => $organizationId,
        'holonId' => $projectEmbedCreateHolonId,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const canEmbedIndicators = <?= $hasStatsApplication ? 'true' : 'false' ?>;
    const embeddableIndicators = <?= json_encode($embeddableIndicatorsPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const indicatorEmbedUi = <?= json_encode([
        'buttonTitle' => omoDocumentsPvEditorT('documents.pv_editor.indicator.button_title'),
        'modalTitle' => omoDocumentsPvEditorT('documents.pv_editor.indicator.modal_title'),
        'search' => omoDocumentsPvEditorT('documents.pv_editor.embed.search'),
        'searchPlaceholder' => omoDocumentsPvEditorT('documents.pv_editor.embed.search_placeholder'),
        'quickSearchPlaceholder' => omoDocumentsPvEditorT('documents.pv_editor.embed.quick_search_placeholder'),
        'visibleIndicators' => omoDocumentsPvEditorT('documents.pv_editor.indicator.visible'),
        'none' => omoDocumentsPvEditorT('documents.pv_editor.embed.none'),
        'insert' => omoDocumentsPvEditorT('documents.pv_editor.indicator.insert'),
        'cancel' => omoDocumentsPvEditorT('documents.pv_editor.action.cancel'),
        'remove' => omoDocumentsPvEditorT('documents.pv_editor.action.remove_embed'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const indicatorValueUi = {
        enabled: true,
        allowedIndicatorIds: embeddableIndicators
            .filter(function (item) { return String(item && item.kind || 'indicator') !== 'group' && item && item.canAddValue; })
            .map(function (item) { return Number(item.id); }),
        placeholder: <?= json_encode(omoDocumentsPvEditorT('documents.pv_editor.indicator.value_placeholder'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        inputLabel: <?= json_encode(omoDocumentsPvEditorT('documents.pv_editor.indicator.value_placeholder'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        addLabel: <?= json_encode(omoDocumentsPvEditorT('documents.pv_editor.indicator.add_value'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
    };
    const activeLockPointIds = new Set();
    const pendingLockPointIds = new Set();
    const pendingUnlockPointIds = new Set();
    let knownPointSignatures = {};
    let currentPointPayloads = {};
    let currentDocumentPayload = initialDocumentPayload && typeof initialDocumentPayload === 'object'
        ? initialDocumentPayload
        : {};
    let knownDocumentSyncVersion = String(currentDocumentPayload.syncVersion || '');
    let currentAttendancePayload = initialAttendancePayload && typeof initialAttendancePayload === 'object'
        ? initialAttendancePayload
        : null;
    let currentAttendanceSignature = '';
    let knownPollingRevision = String(initialPollingRevision || '');
    let syncPollTimer = null;
    let syncPollPending = null;
    let lockHeartbeatTimer = null;
    let editorLifecycleObserver = null;
    let editorWasPollingActive = false;
    let allowNextExternalClose = false;

    function escapeDocumentEmbedHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function truncateDocumentEmbedSummary(value, maximumLength, maximumSentences) {
        const limit = Number.isInteger(maximumLength) ? maximumLength : 420;
        const sentenceLimit = Number.isInteger(maximumSentences) ? maximumSentences : 3;
        let summary = String(value || '').replace(/\s+/g, ' ').trim();
        if (summary === '') {
            return '';
        }

        const sentences = summary.split(/(?<=[.!?])\s+/);
        if (sentences.length > sentenceLimit) {
            summary = sentences.slice(0, sentenceLimit).join(' ').trim() + '...';
        }

        return summary.length > limit
            ? summary.slice(0, Math.max(1, limit - 3)).trim() + '...'
            : summary;
    }

    function mountPvResourceScopePicker(modalBody, selector, render) {
        const host = modalBody instanceof Element ? modalBody.querySelector(selector) : null;
        if (!(host instanceof Element) || typeof window.omoMountHolonScopePicker !== 'function') {
            const picker = host instanceof Element ? host.closest('.omo-resource-picker') : null;
            if (picker instanceof Element) {
                picker.classList.remove('omo-resource-picker');
            }
            return null;
        }

        return window.omoMountHolonScopePicker({
            host: host,
            organizationId: resourcePickerOrganizationId,
            initialHolonId: resourcePickerInitialHolonId,
            initialScope: 'local',
            labels: resourcePickerScopeUi,
            onChange: render
        });
    }

    function openPvEmbeddedResourceByHash(resourceHash) {
        const normalizedHash = String(resourceHash || '').replace(/^#/, '');
        if (!/^(?:(?:documents|decision|projects)-d\d+|(?:processus|checklist)-c\d+|calendar-e\d+|stats(?:-(?:i|g)\d+)?)$/.test(normalizedHash)) {
            return;
        }

        if (/^projects-d\d+$/i.test(normalizedHash) && typeof window.omoOpenProjectEmbedRoute === 'function') {
            window.omoOpenProjectEmbedRoute(normalizedHash, {sourceElement: root});
            return;
        }

        if (typeof window.omoPeekPersistentExternalPanelDrawer === 'function') {
            window.omoPeekPersistentExternalPanelDrawer({
                persistKeyPrefix: 'omo-pv-preparation-',
                contentSelector: '[data-omo-pv-editor-root]'
            });
        }

        if (typeof window.omoSetDrawerHashState === 'function') {
            window.omoSetDrawerHashState({routeToken: normalizedHash, open: true});
            return;
        }

        window.location.hash = '#' + normalizedHash;
    }

    if (root instanceof Element) {
        root.addEventListener('focusin', function (event) {
            const target = event.target instanceof Element ? event.target : null;
            const card = target ? target.closest('[data-omo-pv-point-card]') : null;
            const pointId = card ? Number(card.getAttribute('data-omo-pv-point-card') || 0) : 0;
            if (pointId > 0) {
                setFocusedPoint(pointId);
                syncPointLockState(pointId);
            }
        }, true);

        root.addEventListener('focusout', function (event) {
            const target = event.target instanceof Element ? event.target : null;
            const card = target ? target.closest('[data-omo-pv-point-card]') : null;
            if (!card) {
                return;
            }

            const pointId = Number(card.getAttribute('data-omo-pv-point-card') || 0);
            window.setTimeout(function () {
                const activeElement = document.activeElement;
                const activeCard = activeElement instanceof Element
                    ? activeElement.closest('[data-omo-pv-point-card]')
                    : null;
                const activePointId = activeCard ? Number(activeCard.getAttribute('data-omo-pv-point-card') || 0) : 0;
                if (pointId > 0 && activePointId !== pointId) {
                    clearFocusedPoint(pointId);
                    syncPointLockState(pointId);
                }
            }, 0);
        }, true);

        root.addEventListener('click', function (event) {
            const targetNode = event.target && event.target.closest ? event.target : null;
            const checklistArchiveButton = targetNode ? targetNode.closest('[data-omo-checklist-complete-archive]') : null;
            if (checklistArchiveButton instanceof HTMLButtonElement) {
                event.preventDefault();
                event.stopPropagation();
                completePvChecklistProject(checklistArchiveButton);
                return;
            }
            const checklistContainerToggle = targetNode ? targetNode.closest('[data-omo-checklist-container-toggle]') : null;
            if (checklistContainerToggle instanceof HTMLButtonElement) {
                event.preventDefault();
                event.stopPropagation();
                loadPvChecklistContainerReview(checklistContainerToggle);
                return;
            }
            const checklistRunToggle = targetNode ? targetNode.closest('[data-omo-checklist-run-toggle]') : null;
            if (checklistRunToggle instanceof HTMLButtonElement) {
                event.preventDefault();
                event.stopPropagation();
                loadPvChecklistRunReview(checklistRunToggle);
                return;
            }
            const documentLink = targetNode ? targetNode.closest('.omo-document-embed a[href^="#documents-d"]') : null;
            const decisionLink = targetNode ? targetNode.closest('.omo-decision-embed a[href^="#decision-d"]') : null;
            const eventLink = targetNode ? targetNode.closest('.omo-event-embed a[href^="#calendar-e"]') : null;
            const indicatorLink = targetNode ? targetNode.closest('.omo-indicator-embed a[href^="#stats"]') : null;
            const projectLink = targetNode ? targetNode.closest('.omo-project-embed a[href^="#projects-d"], .omo-checklist-embed .omo-project-embed__child-title[href^="#projects-d"], .omo-checklist-embed .omo-checklist-embed__item-segment[href^="#projects-d"]') : null;
            const checklistLink = targetNode ? targetNode.closest('.omo-checklist-embed a[href^="#processus-c"], .omo-checklist-embed a[href^="#checklist-c"]') : null;
            const resourceLink = documentLink || decisionLink || eventLink || indicatorLink || projectLink || checklistLink;
            if (!resourceLink || resourceLink.matches('[data-omo-document-embed-external], .omo-document-embed__external, .omo-project-embed__external')) {
                return;
            }

            const resourceHash = String(resourceLink.getAttribute('href') || '');
            if (!/^#(?:(?:documents|decision|projects)-d\d+|(?:processus|checklist)-c\d+|calendar-e\d+|stats(?:-(?:i|g)\d+)?)$/.test(resourceHash)) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();
            openPvEmbeddedResourceByHash(resourceHash);
        }, true);
    }

    function buildPvDocumentEmbedHtml(documentItem) {
        const documentId = Number.parseInt(String(documentItem && documentItem.id || ''), 10);
        if (!Number.isInteger(documentId) || documentId <= 0) {
            return '';
        }

        const title = String(documentItem.title || '').trim() || ('Document #' + String(documentId));
        const description = truncateDocumentEmbedSummary(documentItem.description || '');
        const documentHash = '#documents-d' + String(documentId);
        const externalUrl = String(window.location.pathname || '/omo/') + documentHash;
        let html = '<span class="omo-document-embed" contenteditable="false" data-omo-embed-type="document"'
            + ' data-omo-document-id="' + String(documentId) + '"'
            + ' data-omo-document-title="' + escapeDocumentEmbedHtml(title) + '"';

        if (description !== '') {
            html += ' data-omo-document-description="' + escapeDocumentEmbedHtml(description) + '"';
        }

        html += '><strong><a class="omo-document-embed__title" href="' + documentHash + '">' + escapeDocumentEmbedHtml(title) + '</a>'
            + '<a class="omo-document-embed__external" href="' + escapeDocumentEmbedHtml(externalUrl) + '" target="_blank" rel="noopener noreferrer" title="' + escapeDocumentEmbedHtml(documentEmbedUi.openExternal || '') + '" aria-label="' + escapeDocumentEmbedHtml(documentEmbedUi.openExternal || '') + '">&#8599;</a></strong>';
        if (description !== '') {
            html += '<em>' + escapeDocumentEmbedHtml(description) + '</em>';
        }
        return html + '</span>';
    }

    function openPvDocumentEmbedPicker(field, targetNode) {
        if (
            !canEmbedDocuments
            || !field
            || typeof field.createTemporaryCursorMarker !== 'function'
            || typeof field.replaceMarkerWithHtml !== 'function'
            || typeof window.commonTopbarOpenModal !== 'function'
        ) {
            return;
        }

        const currentDocumentId = targetNode instanceof Element
            ? Number.parseInt(String(targetNode.getAttribute('data-omo-document-id') || ''), 10)
            : 0;
        let marker = targetNode ? null : field.createTemporaryCursorMarker();
        let resolved = false;
        const modalHtml = ''
            + '<div class="omo-document-embed-picker omo-resource-picker">'
            + '<aside class="omo-resource-picker__navigation" data-omo-pv-document-embed-scope></aside>'
            + '<div class="omo-resource-picker__content">'
            + '<label class="omo-resource-picker__quick-search"><img src="/common/assets/icon-topbar-search.png" alt="" aria-hidden="true"><input type="search" class="generic-form-control" data-omo-pv-document-embed-search aria-label="' + escapeDocumentEmbedHtml(documentEmbedUi.search || '') + '" placeholder="' + escapeDocumentEmbedHtml(documentEmbedUi.quickSearchPlaceholder || '') + '"></label>'
            + '<div class="omo-document-embed-picker__field"><select class="generic-form-control omo-document-embed-picker__select" data-omo-pv-document-embed-select aria-label="' + escapeDocumentEmbedHtml(documentEmbedUi.visibleDocuments || '') + '" size="10"></select></div>'
            + '<div class="omo-document-embed-picker__preview"><div class="omo-document-embed-picker__preview-title" data-omo-pv-document-embed-title></div>'
            + '<div class="omo-document-embed-picker__preview-description" data-omo-pv-document-embed-description hidden></div></div>'
            + '<div class="omo-document-embed-picker__actions">'
            + (targetNode ? '<button type="button" class="generic-action-button generic-action-button--danger" data-omo-pv-embed-remove>' + escapeDocumentEmbedHtml(documentEmbedUi.remove || '') + '</button>' : '')
            + '<button type="button" class="generic-action-button generic-action-button--secondary" data-omo-pv-document-embed-cancel>' + escapeDocumentEmbedHtml(documentEmbedUi.cancel || '') + '</button>'
            + '<button type="button" class="generic-action-button generic-action-button--main" data-omo-pv-document-embed-insert disabled>' + escapeDocumentEmbedHtml(documentEmbedUi.insert || '') + '</button></div></div></div>';

        window.commonTopbarOpenModal(documentEmbedUi.modalTitle || '', modalHtml, 'html');
        const modalBody = document.getElementById('commonTopbarModalBody');
        if (!(modalBody instanceof Element)) {
            if (marker) field.removeTemporaryMarker(marker);
            return;
        }

        const searchNode = modalBody.querySelector('[data-omo-pv-document-embed-search]');
        const selectNode = modalBody.querySelector('[data-omo-pv-document-embed-select]');
        const titleNode = modalBody.querySelector('[data-omo-pv-document-embed-title]');
        const descriptionNode = modalBody.querySelector('[data-omo-pv-document-embed-description]');
        const cancelButton = modalBody.querySelector('[data-omo-pv-document-embed-cancel]');
        const insertButton = modalBody.querySelector('[data-omo-pv-document-embed-insert]');
        const removeButton = modalBody.querySelector('[data-omo-pv-embed-remove]');
        let selectedItem = null;
        let scopePicker = null;

        const cleanup = function () {
            if (marker && typeof field.removeTemporaryMarker === 'function') {
                field.removeTemporaryMarker(marker);
            }
            marker = null;
        };
        const render = function () {
            const query = String(searchNode && searchNode.value || '').trim().toLowerCase();
            const matches = embeddableDocuments.filter(function (item) {
                return (!scopePicker || scopePicker.matches(item.contextHolonId))
                    && (query === '' || [item.title, item.description, item.contextLabel].join(' ').toLowerCase().indexOf(query) >= 0);
            });
            if (selectNode) {
                selectNode.innerHTML = '';
                matches.forEach(function (item) {
                    const option = document.createElement('option');
                    option.value = String(item.id || '');
                    option.textContent = String(item.title || '').trim() || ('Document #' + String(item.id || ''));
                    selectNode.appendChild(option);
                });
                selectNode.disabled = matches.length === 0;
            }
            selectedItem = matches.find(function (item) { return Number(item.id) === currentDocumentId; }) || matches[0] || null;
            if (selectNode && selectedItem) selectNode.value = String(selectedItem.id);
            updatePreview();
        };
        const updatePreview = function () {
            if (selectNode && selectNode.value !== '') {
                selectedItem = embeddableDocuments.find(function (item) { return String(item.id || '') === String(selectNode.value); }) || null;
            }
            const title = selectedItem ? (String(selectedItem.title || '').trim() || ('Document #' + String(selectedItem.id || ''))) : String(documentEmbedUi.none || '');
            if (titleNode) titleNode.textContent = title;
            if (descriptionNode) {
                descriptionNode.textContent = selectedItem ? String(selectedItem.description || '') : '';
                descriptionNode.hidden = descriptionNode.textContent === '';
            }
            if (insertButton) insertButton.disabled = !selectedItem;
        };

        scopePicker = mountPvResourceScopePicker(modalBody, '[data-omo-pv-document-embed-scope]', render);

        window.addEventListener('common-topbar-modal-close', function () {
            if (!resolved) cleanup();
        }, {once: true});
        if (searchNode) {
            searchNode.addEventListener('input', render);
            searchNode.focus();
        }
        if (selectNode) selectNode.addEventListener('change', updatePreview);
        if (cancelButton) cancelButton.addEventListener('click', function () {
            cleanup();
            if (typeof window.commonTopbarCloseModal === 'function') window.commonTopbarCloseModal();
        });
        if (removeButton) removeButton.addEventListener('click', function () {
            if (targetNode && typeof field.removeNode === 'function') {
                resolved = field.removeNode(targetNode);
            }
            if (typeof window.commonTopbarCloseModal === 'function') window.commonTopbarCloseModal();
        });
        if (insertButton) insertButton.addEventListener('click', function () {
            const embedHtml = buildPvDocumentEmbedHtml(selectedItem);
            if (embedHtml !== '' && targetNode && typeof field.replaceNodeWithHtml === 'function') {
                resolved = true;
                field.replaceNodeWithHtml(targetNode, embedHtml);
            } else if (embedHtml !== '' && marker) {
                resolved = true;
                field.replaceMarkerWithHtml(marker, embedHtml);
                marker = null;
            }
            if (typeof window.commonTopbarCloseModal === 'function') window.commonTopbarCloseModal();
        });
        render();
    }

    function buildPvDecisionEmbedHtml(decisionItem) {
        const decisionId = Number.parseInt(String(decisionItem && decisionItem.id || ''), 10);
        if (!Number.isInteger(decisionId) || decisionId <= 0) {
            return '';
        }

        const title = String(decisionItem.title || '').trim() || ('Décision n°' + String(decisionId));
        const typeLabel = String(decisionItem.typeLabel || '').trim();
        const summary = String(decisionItem.summary || '').trim();
        const displaySummary = [typeLabel, summary].filter(function (value) { return value !== ''; }).join(' - ');
        return '<span class="omo-decision-embed" contenteditable="false" data-omo-embed-type="decision"'
            + ' data-omo-decision-id="' + String(decisionId) + '"'
            + ' data-omo-decision-title="' + escapeDocumentEmbedHtml(title) + '"'
            + (typeLabel !== '' ? ' data-omo-decision-type="' + escapeDocumentEmbedHtml(typeLabel) + '"' : '')
            + (summary !== '' ? ' data-omo-decision-summary="' + escapeDocumentEmbedHtml(summary) + '"' : '')
            + '><strong><a class="omo-decision-embed__title" href="#decision-d' + String(decisionId) + '">' + escapeDocumentEmbedHtml(title) + '</a></strong>'
            + (displaySummary !== '' ? '<em>' + escapeDocumentEmbedHtml(displaySummary) + '</em>' : '')
            + '</span>';
    }

    function openPvDecisionEmbedPicker(field, targetNode) {
        if (!canEmbedDecisions || !field || typeof field.createTemporaryCursorMarker !== 'function' || typeof field.replaceMarkerWithHtml !== 'function' || typeof window.commonTopbarOpenModal !== 'function') {
            return;
        }

        const currentDecisionId = targetNode instanceof Element
            ? Number.parseInt(String(targetNode.getAttribute('data-omo-decision-id') || ''), 10)
            : 0;
        let marker = targetNode ? null : field.createTemporaryCursorMarker();
        let resolved = false;
        const modalHtml = '<div class="omo-document-embed-picker omo-resource-picker">'
            + '<aside class="omo-resource-picker__navigation" data-omo-pv-decision-embed-scope></aside>'
            + '<div class="omo-resource-picker__content">'
            + '<label class="omo-resource-picker__quick-search"><img src="/common/assets/icon-topbar-search.png" alt="" aria-hidden="true"><input type="search" class="generic-form-control" data-omo-pv-decision-embed-search aria-label="' + escapeDocumentEmbedHtml(decisionEmbedUi.search || '') + '" placeholder="' + escapeDocumentEmbedHtml(decisionEmbedUi.quickSearchPlaceholder || '') + '"></label>'
            + '<div class="omo-document-embed-picker__field"><select class="generic-form-control omo-document-embed-picker__select" data-omo-pv-decision-embed-select aria-label="' + escapeDocumentEmbedHtml(decisionEmbedUi.visibleDecisions || '') + '" size="10"></select></div>'
            + '<div class="omo-document-embed-picker__preview"><div class="omo-document-embed-picker__preview-title" data-omo-pv-decision-embed-title></div><div class="omo-document-embed-picker__preview-context" data-omo-pv-decision-embed-type hidden></div><div class="omo-document-embed-picker__preview-description" data-omo-pv-decision-embed-summary hidden></div></div>'
            + '<div class="omo-document-embed-picker__actions">'
            + (targetNode ? '<button type="button" class="generic-action-button generic-action-button--danger" data-omo-pv-embed-remove>' + escapeDocumentEmbedHtml(decisionEmbedUi.remove || '') + '</button>' : '')
            + '<button type="button" class="generic-action-button generic-action-button--secondary" data-omo-pv-decision-embed-cancel>' + escapeDocumentEmbedHtml(decisionEmbedUi.cancel || '') + '</button><button type="button" class="generic-action-button generic-action-button--main" data-omo-pv-decision-embed-insert disabled>' + escapeDocumentEmbedHtml(decisionEmbedUi.insert || '') + '</button></div></div></div>';

        window.commonTopbarOpenModal(decisionEmbedUi.modalTitle || '', modalHtml, 'html');
        const modalBody = document.getElementById('commonTopbarModalBody');
        if (!(modalBody instanceof Element)) {
            if (marker) field.removeTemporaryMarker(marker);
            return;
        }

        const searchNode = modalBody.querySelector('[data-omo-pv-decision-embed-search]');
        const selectNode = modalBody.querySelector('[data-omo-pv-decision-embed-select]');
        const titleNode = modalBody.querySelector('[data-omo-pv-decision-embed-title]');
        const typeNode = modalBody.querySelector('[data-omo-pv-decision-embed-type]');
        const summaryNode = modalBody.querySelector('[data-omo-pv-decision-embed-summary]');
        const cancelButton = modalBody.querySelector('[data-omo-pv-decision-embed-cancel]');
        const insertButton = modalBody.querySelector('[data-omo-pv-decision-embed-insert]');
        const removeButton = modalBody.querySelector('[data-omo-pv-embed-remove]');
        let selectedItem = null;
        let scopePicker = null;
        const cleanup = function () { if (marker) field.removeTemporaryMarker(marker); marker = null; };
        const updatePreview = function () {
            if (selectNode && selectNode.value !== '') selectedItem = embeddableDecisions.find(function (item) { return String(item.id) === String(selectNode.value); }) || null;
            if (titleNode) titleNode.textContent = selectedItem ? (String(selectedItem.title || '').trim() || ('Décision n°' + String(selectedItem.id))) : String(decisionEmbedUi.none || '');
            if (typeNode) { typeNode.textContent = selectedItem ? String(selectedItem.typeLabel || '') : ''; typeNode.hidden = typeNode.textContent === ''; }
            if (summaryNode) { summaryNode.textContent = selectedItem ? String(selectedItem.summary || '') : ''; summaryNode.hidden = summaryNode.textContent === ''; }
            if (insertButton) insertButton.disabled = !selectedItem;
        };
        const render = function () {
            const query = String(searchNode && searchNode.value || '').trim().toLowerCase();
            const matches = embeddableDecisions.filter(function (item) { return (!scopePicker || scopePicker.matches(item.contextHolonId)) && (query === '' || [item.title, item.typeLabel, item.summary].join(' ').toLowerCase().indexOf(query) >= 0); });
            if (selectNode) {
                selectNode.innerHTML = '';
                matches.forEach(function (item) { const option = document.createElement('option'); option.value = String(item.id); option.textContent = (String(item.title || '').trim() || ('Décision n°' + String(item.id))) + (item.typeLabel ? ' - ' + String(item.typeLabel) : ''); selectNode.appendChild(option); });
                selectNode.disabled = matches.length === 0;
            }
            selectedItem = matches.find(function (item) { return Number(item.id) === currentDecisionId; }) || matches[0] || null;
            if (selectNode && selectedItem) selectNode.value = String(selectedItem.id);
            updatePreview();
        };
        scopePicker = mountPvResourceScopePicker(modalBody, '[data-omo-pv-decision-embed-scope]', render);
        window.addEventListener('common-topbar-modal-close', function () { if (!resolved) cleanup(); }, {once: true});
        if (searchNode) { searchNode.addEventListener('input', render); searchNode.focus(); }
        if (selectNode) selectNode.addEventListener('change', updatePreview);
        if (cancelButton) cancelButton.addEventListener('click', function () { cleanup(); window.commonTopbarCloseModal(); });
        if (removeButton) removeButton.addEventListener('click', function () { if (targetNode && typeof field.removeNode === 'function') resolved = field.removeNode(targetNode); window.commonTopbarCloseModal(); });
        if (insertButton) insertButton.addEventListener('click', function () { const embedHtml = buildPvDecisionEmbedHtml(selectedItem); if (embedHtml !== '' && targetNode && typeof field.replaceNodeWithHtml === 'function') { resolved = true; field.replaceNodeWithHtml(targetNode, embedHtml); } else if (embedHtml !== '' && marker) { resolved = true; field.replaceMarkerWithHtml(marker, embedHtml); marker = null; } window.commonTopbarCloseModal(); });
        render();
    }

    function buildPvChecklistEmbedHtml(item) {
        const checklistId = Number.parseInt(String(item && item.id || ''), 10);
        if (!Number.isInteger(checklistId) || checklistId <= 0) return '';
        const title = String(item.title || '').trim() || ('Processus #' + String(checklistId));
        const summary = String(item.contextLabel || '').trim();
        return '<span class="omo-checklist-embed" contenteditable="false" data-omo-embed-type="checklist" data-omo-checklist-id="' + String(checklistId) + '" data-omo-checklist-title="' + escapeDocumentEmbedHtml(title) + '"><strong><a href="#processus-c' + String(checklistId) + '">' + escapeDocumentEmbedHtml(title) + '</a></strong>' + (summary ? '<em>' + escapeDocumentEmbedHtml(summary) + '</em>' : '') + '</span>';
    }

    function openPvChecklistEmbedPicker(field, targetNode) {
        if (!canEmbedChecklists || !field || typeof field.createTemporaryCursorMarker !== 'function' || typeof window.commonTopbarOpenModal !== 'function') return;
        const currentId = targetNode instanceof Element ? Number(targetNode.getAttribute('data-omo-checklist-id') || 0) : 0;
        let marker = targetNode ? null : field.createTemporaryCursorMarker(), resolved = false, selected = null;
        const html = '<div class="omo-document-embed-picker omo-resource-picker"><aside class="omo-resource-picker__navigation" data-omo-pv-checklist-scope></aside><div class="omo-resource-picker__content"><label class="omo-resource-picker__quick-search"><input type="search" class="generic-form-control" data-omo-pv-checklist-search placeholder="' + escapeDocumentEmbedHtml(checklistEmbedUi.quickSearchPlaceholder || '') + '"></label><div class="omo-document-embed-picker__field"><select class="generic-form-control omo-document-embed-picker__select" data-omo-pv-checklist-select size="10" aria-label="' + escapeDocumentEmbedHtml(checklistEmbedUi.visible || '') + '"></select></div><div class="omo-document-embed-picker__preview" data-omo-pv-checklist-preview></div><div class="omo-document-embed-picker__actions">' + (targetNode ? '<button type="button" class="generic-action-button generic-action-button--danger" data-omo-pv-checklist-remove>' + escapeDocumentEmbedHtml(checklistEmbedUi.remove || '') + '</button>' : '') + '<button type="button" class="generic-action-button generic-action-button--secondary" data-omo-pv-checklist-cancel>' + escapeDocumentEmbedHtml(checklistEmbedUi.cancel || '') + '</button><button type="button" class="generic-action-button generic-action-button--main" data-omo-pv-checklist-insert disabled>' + escapeDocumentEmbedHtml(checklistEmbedUi.insert || '') + '</button></div></div></div>';
        window.commonTopbarOpenModal(checklistEmbedUi.modalTitle || '', html, 'html');
        const body = document.getElementById('commonTopbarModalBody'); if (!(body instanceof Element)) { if (marker) field.removeTemporaryMarker(marker); return; }
        const search = body.querySelector('[data-omo-pv-checklist-search]'), select = body.querySelector('[data-omo-pv-checklist-select]'), preview = body.querySelector('[data-omo-pv-checklist-preview]'), insert = body.querySelector('[data-omo-pv-checklist-insert]');
        let scopePicker = null;
        const cleanup = function () { if (marker) field.removeTemporaryMarker(marker); marker = null; };
        const update = function () { selected = embeddableChecklists.find(function (entry) { return String(entry.id) === String(select && select.value || ''); }) || null; if (preview) preview.innerHTML = selected ? buildPvChecklistEmbedHtml(selected) : escapeDocumentEmbedHtml(checklistEmbedUi.none || ''); if (insert) insert.disabled = !selected; };
        const render = function () { const query = String(search && search.value || '').trim().toLowerCase(); const matches = embeddableChecklists.filter(function (entry) { const holonId = Number(entry.contextHolonId || 0); const inScope = !scopePicker || scopePicker.matches(holonId); return inScope && (query === '' || [entry.title, entry.contextLabel, entry.summary].join(' ').toLowerCase().indexOf(query) >= 0); }); if (select) { select.innerHTML = ''; matches.forEach(function (entry) { const option = document.createElement('option'); option.value = String(entry.id); option.textContent = String(entry.title || ''); select.appendChild(option); }); select.value = String((matches.find(function (entry) { return Number(entry.id) === currentId; }) || matches[0] || {}).id || ''); } update(); };
        scopePicker = mountPvResourceScopePicker(body, '[data-omo-pv-checklist-scope]', render);
        body.querySelectorAll('[data-omo-pv-checklist-cancel]').forEach(function (button) { button.addEventListener('click', function () { cleanup(); window.commonTopbarCloseModal(); }); });
        const remove = body.querySelector('[data-omo-pv-checklist-remove]'); if (remove) remove.addEventListener('click', function () { if (targetNode) resolved = field.removeNode(targetNode); window.commonTopbarCloseModal(); });
        if (search) search.addEventListener('input', render); if (select) select.addEventListener('change', update); if (insert) insert.addEventListener('click', function () { const embed = buildPvChecklistEmbedHtml(selected); if (!embed) return; if (targetNode) resolved = field.replaceNodeWithHtml(targetNode, embed); else if (marker) { resolved = field.replaceMarkerWithHtml(marker, embed); marker = null; } if (resolved) window.setTimeout(function () { refreshPvChecklistEmbedReviews(field); }, 0); window.commonTopbarCloseModal(); });
        window.addEventListener('common-topbar-modal-close', function () { if (!resolved) cleanup(); }, {once: true}); render();
    }

    function buildPvProjectEmbedHtml(projectItem) {
        const projectId = Number.parseInt(String(projectItem && projectItem.id || ''), 10);
        if (!Number.isInteger(projectId) || projectId <= 0) return '';
        const title = String(projectItem.title || '').trim() || ('Projet #' + String(projectId));
        const contextLabel = String(projectItem.contextLabel || '').trim();
        const responsibleLabel = String(projectItem.responsibleLabel || '').trim();
        const status = String(projectItem.status || '').trim();
        const statusLabel = String(projectItem.statusLabel || '').trim();
        const priorityLabel = String(projectItem.priorityLabel || '').trim();
        const sizeLabel = String(projectItem.sizeLabel || '').trim();
        const plannedStartLabel = String(projectItem.plannedStartLabel || '').trim();
        const plannedEndLabel = String(projectItem.plannedEndLabel || '').trim();
        const projectHash = '#projects-d' + String(projectId);
        const externalUrl = String(window.location.pathname || '/omo/') + projectHash;
        const metadata = [contextLabel, responsibleLabel];
        if (plannedStartLabel !== '') metadata.push(String(projectEmbedUi.plannedDate || '').replace('{date}', plannedStartLabel));
        if (plannedEndLabel !== '') metadata.push(String(projectEmbedUi.endDate || '').replace('{date}', plannedEndLabel));
        const statusClass = /^(?:someday|ready|in_progress|blocked|review|done)$/.test(status) ? status : '';
        const statusCapsule = statusLabel !== ''
            ? '<em class="omo-project-embed__status' + (statusClass !== '' ? ' omo-project-embed__status--' + statusClass : '') + '">' + escapeDocumentEmbedHtml(statusLabel) + '</em>'
            : '';
        const priorityCapsule = statusCapsule + (priorityLabel !== '' ? '<em>' + escapeDocumentEmbedHtml(priorityLabel) + '</em>' : '');
        return '<span class="omo-project-embed" contenteditable="false" data-omo-embed-type="project" data-omo-project-id="' + String(projectId) + '" data-omo-project-title="' + escapeDocumentEmbedHtml(title) + '" data-omo-project-status="' + statusClass + '" data-omo-project-status-label="' + escapeDocumentEmbedHtml(statusLabel) + '"><strong><a href="' + projectHash + '">' + escapeDocumentEmbedHtml(title) + '</a><a class="omo-project-embed__external" href="' + escapeDocumentEmbedHtml(externalUrl) + '" target="_blank" rel="noopener noreferrer" title="' + escapeDocumentEmbedHtml(projectEmbedUi.openExternal || '') + '" aria-label="' + escapeDocumentEmbedHtml(projectEmbedUi.openExternal || '') + '">&#8599;</a>' + priorityCapsule + (sizeLabel !== '' ? '<em>' + escapeDocumentEmbedHtml(sizeLabel) + '</em>' : '') + '</strong>' + (metadata.length > 0 ? '<em>' + escapeDocumentEmbedHtml(metadata.join(' · ')) + '</em>' : '') + '</span>';
    }

    function openPvProjectEmbedPicker(field, targetNode) {
        if (!field || typeof field.createTemporaryCursorMarker !== 'function' || typeof window.commonTopbarOpenModal !== 'function') return;
        const currentProjectId = targetNode instanceof Element ? Number.parseInt(String(targetNode.getAttribute('data-omo-project-id') || ''), 10) : 0;
        let marker = targetNode ? null : field.createTemporaryCursorMarker(), resolved = false;
        const tabPrefix = 'omo-pv-project-picker-' + Math.random().toString(36).slice(2, 10), existingTabId = tabPrefix + '-existing', newTabId = tabPrefix + '-new';
        const statusOptions = Array.isArray(projectEmbedUi.statusOptions) ? projectEmbedUi.statusOptions : [], sizeOptions = Array.isArray(projectEmbedUi.sizeOptions) ? projectEmbedUi.sizeOptions : [];
        const statusOptionsHtml = statusOptions.map(function (option) { return '<option value="' + escapeDocumentEmbedHtml(option.value || '') + '">' + escapeDocumentEmbedHtml(option.label || option.value || '') + '</option>'; }).join('');
        const sizeOptionsHtml = sizeOptions.map(function (size) { return '<option value="' + escapeDocumentEmbedHtml(size) + '"' + (String(size) === 'M' ? ' selected' : '') + '>' + escapeDocumentEmbedHtml(size) + '</option>'; }).join('');
        const html = '<div class="generic-tabs omo-document-embed-picker" data-generic-tabs><div class="generic-tabs__list" aria-label="' + escapeDocumentEmbedHtml(projectEmbedUi.tabsAria || '') + '"><button type="button" class="generic-tabs__tab is-active" data-generic-tab data-generic-tab-target="' + existingTabId + '">' + escapeDocumentEmbedHtml(projectEmbedUi.tabExisting || '') + '</button><button type="button" class="generic-tabs__tab" data-generic-tab data-generic-tab-target="' + newTabId + '">' + escapeDocumentEmbedHtml(projectEmbedUi.tabNew || '') + '</button></div><div class="generic-tabs__panels"><section id="' + existingTabId + '" class="generic-tabs__panel" data-generic-tab-panel><div class="omo-resource-picker"><aside class="omo-resource-picker__navigation" data-omo-pv-project-embed-scope></aside><div class="omo-resource-picker__content"><label class="omo-resource-picker__quick-search"><input type="search" class="generic-form-control" data-omo-pv-project-embed-search placeholder="' + escapeDocumentEmbedHtml(projectEmbedUi.quickSearchPlaceholder || '') + '"></label><div class="omo-document-embed-picker__field"><select class="generic-form-control omo-document-embed-picker__select" data-omo-pv-project-embed-select size="10"></select></div><div class="omo-document-embed-picker__preview" data-omo-pv-project-embed-preview></div><div class="omo-document-embed-picker__actions">' + (targetNode ? '<button type="button" class="generic-action-button generic-action-button--danger" data-omo-pv-embed-remove>' + escapeDocumentEmbedHtml(projectEmbedUi.remove || '') + '</button>' : '') + '<button type="button" class="generic-action-button generic-action-button--secondary" data-omo-pv-project-embed-cancel>' + escapeDocumentEmbedHtml(projectEmbedUi.cancel || '') + '</button><button type="button" class="generic-action-button generic-action-button--main" data-omo-pv-project-embed-insert disabled>' + escapeDocumentEmbedHtml(projectEmbedUi.insert || '') + '</button></div></div></div></section><section id="' + newTabId + '" class="generic-tabs__panel" data-generic-tab-panel hidden><form data-omo-pv-project-create-form class="omo-document-embed-picker__quick-form"><label>' + escapeDocumentEmbedHtml(projectEmbedUi.titleLabel || '') + '<input required name="title" class="generic-form-control" type="text"></label><label>' + escapeDocumentEmbedHtml(projectEmbedUi.descriptionLabel || '') + '<textarea name="description" class="generic-form-control" rows="3"></textarea></label><div class="omo-document-embed-picker__quick-form-grid"><label>' + escapeDocumentEmbedHtml(projectEmbedUi.statusLabel || '') + '<select name="status" class="generic-form-control">' + statusOptionsHtml + '</select></label><label>' + escapeDocumentEmbedHtml(projectEmbedUi.priorityLabel || '') + '<select name="priority" class="generic-form-control"><option value=""></option><option value="1">P1</option><option value="2">P2</option><option value="3">P3</option><option value="4">P4</option><option value="5">P5</option></select></label><label>' + escapeDocumentEmbedHtml(projectEmbedUi.sizeLabel || '') + '<select name="project_size" class="generic-form-control">' + sizeOptionsHtml + '</select></label><label>' + escapeDocumentEmbedHtml(projectEmbedUi.startDateLabel || '') + '<input name="planned_start_date" class="generic-form-control" type="date"></label><label>' + escapeDocumentEmbedHtml(projectEmbedUi.endDateLabel || '') + '<input name="planned_end_date" class="generic-form-control" type="date"></label></div><div class="omo-document-embed-picker__actions"><button type="button" class="generic-action-button generic-action-button--secondary" data-omo-pv-project-embed-cancel>' + escapeDocumentEmbedHtml(projectEmbedUi.cancel || '') + '</button><button type="submit" class="generic-action-button generic-action-button--main" data-omo-pv-project-create-submit>' + escapeDocumentEmbedHtml(projectEmbedUi.createInsert || '') + '</button></div></form></section></div></div>';
        window.commonTopbarOpenModal(projectEmbedUi.modalTitle || '', html, 'html');
        const body = document.getElementById('commonTopbarModalBody'); if (!(body instanceof Element)) { if (marker) field.removeTemporaryMarker(marker); return; }
        const projectPickerTabs = body.querySelector('.generic-tabs.omo-document-embed-picker');
        const projectScopeHost = body.querySelector('[data-omo-pv-project-embed-scope]');
        const projectScopeNavigation = projectScopeHost ? projectScopeHost.closest('.omo-resource-picker__navigation') : null;
        if (projectPickerTabs instanceof Element && projectScopeNavigation instanceof Element) {
            const resourcePicker = projectScopeNavigation.closest('.omo-resource-picker');
            if (resourcePicker instanceof Element) resourcePicker.classList.remove('omo-resource-picker');
            projectPickerTabs.classList.add('omo-project-embed-picker');
            projectPickerTabs.appendChild(projectScopeNavigation);
        }
        if (typeof window.initGenericComponents === 'function') window.initGenericComponents(body);
        const search = body.querySelector('[data-omo-pv-project-embed-search]'), select = body.querySelector('[data-omo-pv-project-embed-select]'), preview = body.querySelector('[data-omo-pv-project-embed-preview]'), cancelButtons = Array.from(body.querySelectorAll('[data-omo-pv-project-embed-cancel]')), insert = body.querySelector('[data-omo-pv-project-embed-insert]'), remove = body.querySelector('[data-omo-pv-embed-remove]'), createForm = body.querySelector('[data-omo-pv-project-create-form]'), createSubmit = body.querySelector('[data-omo-pv-project-create-submit]'); let selected = null, scopePicker = null;
        if (createForm instanceof HTMLFormElement) {
            const enforceHolonMember = document.createElement('input');
            enforceHolonMember.type = 'hidden';
            enforceHolonMember.name = 'enforce_holon_member';
            enforceHolonMember.value = '1';
            createForm.appendChild(enforceHolonMember);
            const responsibleField = document.createElement('label');
            responsibleField.textContent = String(projectEmbedUi.responsibleLabel || '');
            const responsibleSelect = document.createElement('select');
            responsibleSelect.name = 'IDuser';
            responsibleSelect.className = 'generic-form-control';
            responsibleSelect.disabled = true;
            const responsibleNote = document.createElement('small');
            responsibleField.appendChild(responsibleSelect);
            responsibleField.appendChild(responsibleNote);
            createForm.insertBefore(responsibleField, createForm.firstChild);
            let membersRequestId = 0;
            const loadMembers = function (holonId) {
                const requestId = ++membersRequestId;
                responsibleSelect.disabled = true;
                responsibleSelect.innerHTML = '<option value="">' + escapeDocumentEmbedHtml(projectEmbedUi.membersLoading || '') + '</option>';
                responsibleNote.textContent = '';
                fetch('/omo/api/projects/members.php?oid=' + encodeURIComponent(String(projectEmbedCreateContext.organizationId || 0)) + '&hid=' + encodeURIComponent(String(holonId || 0)), {credentials: 'same-origin'})
                    .then(function (response) { return response.json(); })
                    .then(function (payload) {
                        if (requestId !== membersRequestId || !payload || !payload.success) return;
                        projectEmbedCreateContext.holonId = Number(payload.holon && payload.holon.id || 0);
                        projectEmbedCreateContext.holonLabel = String(payload.holon && payload.holon.label || '');
                        const members = Array.isArray(payload.members) ? payload.members : [];
                        responsibleSelect.innerHTML = '<option value="">' + escapeDocumentEmbedHtml(projectEmbedUi.responsibleEmpty || '') + '</option>';
                        members.forEach(function (member) {
                            const option = document.createElement('option');
                            option.value = String(member.id || '');
                            option.textContent = String(member.label || '');
                            if (Number(member.id || 0) === Number(projectEmbedCreateContext.responsibleId || 0)) option.selected = true;
                            responsibleSelect.appendChild(option);
                        });
                        if (members.length === 0) responsibleNote.textContent = String(projectEmbedUi.membersEmpty || '');
                        responsibleSelect.disabled = false;
                        const selectedOption = responsibleSelect.selectedOptions[0];
                        projectEmbedCreateContext.responsibleId = Number(responsibleSelect.value || 0);
                        projectEmbedCreateContext.responsibleLabel = selectedOption ? String(selectedOption.textContent || '') : '';
                    })
                    .catch(function () {
                        if (requestId !== membersRequestId) return;
                        responsibleSelect.innerHTML = '<option value="">' + escapeDocumentEmbedHtml(projectEmbedUi.responsibleEmpty || '') + '</option>';
                        responsibleSelect.disabled = false;
                    });
            };
            responsibleSelect.addEventListener('change', function () {
                const selectedOption = responsibleSelect.selectedOptions[0];
                projectEmbedCreateContext.responsibleId = Number(responsibleSelect.value || 0);
                projectEmbedCreateContext.responsibleLabel = selectedOption ? String(selectedOption.textContent || '') : '';
            });
            createForm.__omoPvProjectLoadMembers = loadMembers;
        }
        const cleanup = function () { if (marker) field.removeTemporaryMarker(marker); marker = null; };
        const insertProject = function (project) { const embed = buildPvProjectEmbedHtml(project); if (!embed) return false; if (targetNode && typeof field.replaceNodeWithHtml === 'function') { resolved = true; field.replaceNodeWithHtml(targetNode, embed); } else if (marker && typeof field.replaceMarkerWithHtml === 'function') { resolved = true; field.replaceMarkerWithHtml(marker, embed); marker = null; } else return false; window.setTimeout(function () { refreshPvProjectEmbedReviews(field); }, 0); window.commonTopbarCloseModal(); return true; };
        const update = function () { if (select && select.value) selected = embeddableProjects.find(function (item) { return String(item.id) === String(select.value); }) || null; if (preview) preview.innerHTML = selected ? buildPvProjectEmbedHtml(selected) : escapeDocumentEmbedHtml(projectEmbedUi.none || ''); if (insert) insert.disabled = !selected; };
        const render = function () { const query = String(search && search.value || '').trim().toLowerCase(), selectedHolonId = scopePicker && typeof scopePicker.getSelectedHolonId === 'function' ? Number(scopePicker.getSelectedHolonId() || 0) : 0, matches = embeddableProjects.filter(function (item) { const itemHolonId = Number(item.contextHolonId || 0); const matchesScope = !scopePicker || scopePicker.matches(itemHolonId) || (selectedHolonId > 0 && itemHolonId === selectedHolonId); return matchesScope && (query === '' || [item.title, item.contextLabel, item.summary].join(' ').toLowerCase().indexOf(query) >= 0); }); if (select) { select.innerHTML = ''; matches.forEach(function (item) { const option = document.createElement('option'); option.value = String(item.id); option.textContent = String(item.title || '').trim() || ('Projet #' + String(item.id)); select.appendChild(option); }); } selected = matches.find(function (item) { return Number(item.id) === currentProjectId; }) || matches[0] || null; if (select && selected) select.value = String(selected.id); update(); };
        if (projectScopeHost instanceof Element && typeof window.omoMountHolonScopePicker === 'function') {
            scopePicker = window.omoMountHolonScopePicker({host: projectScopeHost, organizationId: resourcePickerOrganizationId, initialHolonId: Number(projectEmbedCreateContext.holonId || resourcePickerInitialHolonId || 0), initialScope: 'local', labels: resourcePickerScopeUi, onChange: function (holonId) { render(); if (createForm && typeof createForm.__omoPvProjectLoadMembers === 'function') createForm.__omoPvProjectLoadMembers(holonId); }});
        } else {
            scopePicker = mountPvResourceScopePicker(body, '[data-omo-pv-project-embed-scope]', render);
            if (createForm && typeof createForm.__omoPvProjectLoadMembers === 'function') createForm.__omoPvProjectLoadMembers(projectEmbedCreateContext.holonId);
        }
        window.addEventListener('common-topbar-modal-close', function () { if (!resolved) cleanup(); }, {once: true}); if (search) search.addEventListener('input', render); if (select) select.addEventListener('change', update); cancelButtons.forEach(function (button) { button.addEventListener('click', function () { cleanup(); window.commonTopbarCloseModal(); }); }); if (remove) remove.addEventListener('click', function () { if (targetNode && typeof field.removeNode === 'function') resolved = field.removeNode(targetNode); window.commonTopbarCloseModal(); }); if (insert) insert.addEventListener('click', function () { if (selected) insertProject(selected); });
        if (createForm instanceof HTMLFormElement) createForm.addEventListener('submit', function (event) { event.preventDefault(); const titleInput = createForm.elements.namedItem('title'), title = titleInput ? String(titleInput.value || '').trim() : ''; if (title === '') { if (titleInput && typeof titleInput.focus === 'function') titleInput.focus(); return; } if (createSubmit) createSubmit.disabled = true; const formData = new FormData(createForm); formData.set('project_action', 'save_project'); formData.set('oid', String(projectEmbedCreateContext.organizationId || 0)); formData.set('cid', String(projectEmbedCreateContext.holonId || 0)); formData.set('IDuser', String(projectEmbedCreateContext.responsibleId || 0)); fetch('/omo/api/projects/action.php', {method: 'POST', body: formData, credentials: 'same-origin'}).then(function (response) { return response.json(); }).then(function (payload) { const projectId = Number(payload && payload.id || 0); if (!payload || !payload.success || projectId <= 0) throw new Error(payload && payload.message ? payload.message : projectEmbedUi.createError || ''); const statusSelect = createForm.elements.namedItem('status'), sizeSelect = createForm.elements.namedItem('project_size'), prioritySelect = createForm.elements.namedItem('priority'), startInput = createForm.elements.namedItem('planned_start_date'), endInput = createForm.elements.namedItem('planned_end_date'), statusValue = statusSelect ? String(statusSelect.value || '') : '', statusOption = statusOptions.find(function (option) { return String(option.value) === statusValue; }) || {}, toDateLabel = function (input) { const value = input ? String(input.value || '') : ''; return /^\d{4}-\d{2}-\d{2}$/.test(value) ? value.slice(8, 10) + '.' + value.slice(5, 7) + '.' + value.slice(0, 4) : ''; }, project = {id: projectId, title: title, summary: String(formData.get('description') || ''), contextHolonId: Number(projectEmbedCreateContext.holonId || 0), contextLabel: String(projectEmbedCreateContext.holonLabel || ''), responsibleLabel: String(projectEmbedCreateContext.responsibleLabel || ''), status: statusValue, statusLabel: String(statusOption.label || statusValue), priorityLabel: prioritySelect && prioritySelect.value ? 'P' + String(prioritySelect.value) : '', sizeLabel: sizeSelect ? String(sizeSelect.value || 'M') : 'M', plannedStartLabel: toDateLabel(startInput), plannedEndLabel: toDateLabel(endInput)}; embeddableProjects.push(project); if (typeof window.omoRefreshProjectsDrawerAfterMutation === 'function') window.omoRefreshProjectsDrawerAfterMutation(); insertProject(project); }).catch(function (error) { window.alert(String(error && error.message || projectEmbedUi.createError || '')); }).finally(function () { if (createSubmit) createSubmit.disabled = false; }); });
        render();
    }

    function buildPvEventEmbedHtml(eventItem) {
        const eventId = Number.parseInt(String(eventItem && eventItem.id || ''), 10);
        if (!Number.isInteger(eventId) || eventId <= 0) {
            return '';
        }

        const title = String(eventItem.title || '').trim() || ('Événement n°' + String(eventId));
        const scheduleLabel = String(eventItem.scheduleLabel || '').trim();
        const locationLabel = truncateDocumentEmbedSummary(eventItem.locationLabel || '', 420, 1);
        const summary = [scheduleLabel, locationLabel].filter(function (value) { return value !== ''; }).join(' - ');
        return '<span class="omo-event-embed" contenteditable="false" data-omo-embed-type="event"'
            + ' data-omo-event-id="' + String(eventId) + '"'
            + ' data-omo-event-title="' + escapeDocumentEmbedHtml(title) + '"'
            + (scheduleLabel !== '' ? ' data-omo-event-schedule="' + escapeDocumentEmbedHtml(scheduleLabel) + '"' : '')
            + (locationLabel !== '' ? ' data-omo-event-location="' + escapeDocumentEmbedHtml(locationLabel) + '"' : '')
            + '><strong><a class="omo-event-embed__title" href="#calendar-e' + String(eventId) + '">' + escapeDocumentEmbedHtml(title) + '</a></strong>'
            + (summary !== '' ? '<em>' + escapeDocumentEmbedHtml(summary) + '</em>' : '')
            + '</span>';
    }

    function openPvEventEmbedPicker(field, targetNode) {
        if (!canEmbedEvents || !field || typeof field.createTemporaryCursorMarker !== 'function' || typeof field.replaceMarkerWithHtml !== 'function' || typeof window.commonTopbarOpenModal !== 'function') {
            return;
        }

        const currentEventId = targetNode instanceof Element
            ? Number.parseInt(String(targetNode.getAttribute('data-omo-event-id') || ''), 10)
            : 0;
        let marker = targetNode ? null : field.createTemporaryCursorMarker();
        let resolved = false;
        const tabPrefix = 'omo-pv-event-picker-' + Math.random().toString(36).slice(2, 10);
        const existingTabId = tabPrefix + '-existing';
        const newTabId = tabPrefix + '-new';
        const modalHtml = '<div class="generic-tabs omo-document-embed-picker" data-generic-tabs>'
            + '<div class="generic-tabs__list" aria-label="' + escapeDocumentEmbedHtml(eventEmbedUi.tabsAria || '') + '">'
            + '<button type="button" class="generic-tabs__tab is-active" data-generic-tab data-generic-tab-target="' + existingTabId + '">' + escapeDocumentEmbedHtml(eventEmbedUi.tabExisting || '') + '</button>'
            + '<button type="button" class="generic-tabs__tab" data-generic-tab data-generic-tab-target="' + newTabId + '">' + escapeDocumentEmbedHtml(eventEmbedUi.tabNew || '') + '</button></div>'
            + '<div class="generic-tabs__panels">'
            + '<section id="' + existingTabId + '" class="generic-tabs__panel" data-generic-tab-panel><div class="omo-resource-picker">'
            + '<aside class="omo-resource-picker__navigation" data-omo-pv-event-embed-scope></aside>'
            + '<div class="omo-resource-picker__content">'
            + '<label class="omo-resource-picker__quick-search"><img src="/common/assets/icon-topbar-search.png" alt="" aria-hidden="true"><input type="search" class="generic-form-control" data-omo-pv-event-embed-search aria-label="' + escapeDocumentEmbedHtml(eventEmbedUi.search || '') + '" placeholder="' + escapeDocumentEmbedHtml(eventEmbedUi.quickSearchPlaceholder || '') + '"></label>'
            + '<div class="omo-document-embed-picker__field"><select class="generic-form-control omo-document-embed-picker__select" data-omo-pv-event-embed-select aria-label="' + escapeDocumentEmbedHtml(eventEmbedUi.visibleEvents || '') + '" size="10"></select></div>'
            + '<div class="omo-document-embed-picker__preview"><div class="omo-document-embed-picker__preview-title" data-omo-pv-event-embed-title></div><div class="omo-document-embed-picker__preview-context" data-omo-pv-event-embed-schedule hidden></div><div class="omo-document-embed-picker__preview-description" data-omo-pv-event-embed-description hidden></div></div>'
            + '<div class="omo-document-embed-picker__actions">'
            + (targetNode ? '<button type="button" class="generic-action-button generic-action-button--danger" data-omo-pv-embed-remove>' + escapeDocumentEmbedHtml(eventEmbedUi.remove || '') + '</button>' : '')
            + '<button type="button" class="generic-action-button generic-action-button--secondary" data-omo-pv-event-embed-cancel>' + escapeDocumentEmbedHtml(eventEmbedUi.cancel || '') + '</button><button type="button" class="generic-action-button generic-action-button--main" data-omo-pv-event-embed-insert disabled>' + escapeDocumentEmbedHtml(eventEmbedUi.insert || '') + '</button></div></div></div></section>'
            + '<section id="' + newTabId + '" class="generic-tabs__panel" data-generic-tab-panel hidden><form data-omo-pv-event-create-form class="omo-document-embed-picker__quick-form">'
            + '<label>' + escapeDocumentEmbedHtml(eventEmbedUi.titleLabel || '') + '<input required name="title" class="generic-form-control" type="text"></label>'
            + '<label>' + escapeDocumentEmbedHtml(eventEmbedUi.descriptionLabel || '') + '<textarea name="description" class="generic-form-control" rows="3"></textarea></label>'
            + '<div class="omo-document-embed-picker__quick-form-grid"><label>' + escapeDocumentEmbedHtml(eventEmbedUi.startAtLabel || '') + '<input required name="start_at" class="generic-form-control" type="datetime-local"></label><label>' + escapeDocumentEmbedHtml(eventEmbedUi.endAtLabel || '') + '<input required name="end_at" class="generic-form-control" type="datetime-local"></label></div>'
            + '<div class="omo-document-embed-picker__actions"><button type="button" class="generic-action-button generic-action-button--secondary" data-omo-pv-event-embed-cancel>' + escapeDocumentEmbedHtml(eventEmbedUi.cancel || '') + '</button><button type="submit" class="generic-action-button generic-action-button--main" data-omo-pv-event-create-submit>' + escapeDocumentEmbedHtml(eventEmbedUi.createInsert || '') + '</button></div></form></section>'
            + '</div></div>';

        window.commonTopbarOpenModal(eventEmbedUi.modalTitle || '', modalHtml, 'html');
        const modalBody = document.getElementById('commonTopbarModalBody');
        if (!(modalBody instanceof Element)) {
            if (marker) field.removeTemporaryMarker(marker);
            return;
        }

        const eventPickerTabs = modalBody.querySelector('.generic-tabs.omo-document-embed-picker');
        const eventScopeHost = modalBody.querySelector('[data-omo-pv-event-embed-scope]');
        const eventScopeNavigation = eventScopeHost ? eventScopeHost.closest('.omo-resource-picker__navigation') : null;
        if (eventPickerTabs instanceof Element && eventScopeNavigation instanceof Element) {
            const resourcePicker = eventScopeNavigation.closest('.omo-resource-picker');
            if (resourcePicker instanceof Element) resourcePicker.classList.remove('omo-resource-picker');
            eventPickerTabs.classList.add('omo-event-embed-picker');
            eventPickerTabs.appendChild(eventScopeNavigation);
        }
        if (typeof window.initGenericComponents === 'function') window.initGenericComponents(modalBody);

        const searchNode = modalBody.querySelector('[data-omo-pv-event-embed-search]');
        const selectNode = modalBody.querySelector('[data-omo-pv-event-embed-select]');
        const titleNode = modalBody.querySelector('[data-omo-pv-event-embed-title]');
        const scheduleNode = modalBody.querySelector('[data-omo-pv-event-embed-schedule]');
        const descriptionNode = modalBody.querySelector('[data-omo-pv-event-embed-description]');
        const cancelButtons = Array.from(modalBody.querySelectorAll('[data-omo-pv-event-embed-cancel]'));
        const insertButton = modalBody.querySelector('[data-omo-pv-event-embed-insert]');
        const removeButton = modalBody.querySelector('[data-omo-pv-embed-remove]');
        const createForm = modalBody.querySelector('[data-omo-pv-event-create-form]');
        const createSubmit = modalBody.querySelector('[data-omo-pv-event-create-submit]');
        let selectedItem = null;
        let scopePicker = null;
        const cleanup = function () { if (marker) field.removeTemporaryMarker(marker); marker = null; };
        const insertEvent = function (eventItem) {
            const embedHtml = buildPvEventEmbedHtml(eventItem);
            if (embedHtml === '') return false;
            if (targetNode && typeof field.replaceNodeWithHtml === 'function') {
                resolved = true;
                field.replaceNodeWithHtml(targetNode, embedHtml);
            } else if (marker) {
                resolved = true;
                field.replaceMarkerWithHtml(marker, embedHtml);
                marker = null;
            } else {
                return false;
            }
            window.commonTopbarCloseModal();
            return true;
        };
        const updatePreview = function () {
            if (selectNode && selectNode.value !== '') selectedItem = embeddableEvents.find(function (item) { return String(item.id) === String(selectNode.value); }) || null;
            if (titleNode) titleNode.textContent = selectedItem ? (String(selectedItem.title || '').trim() || ('Événement n°' + String(selectedItem.id))) : String(eventEmbedUi.none || '');
            if (scheduleNode) { scheduleNode.textContent = selectedItem ? String(selectedItem.scheduleLabel || '') : ''; scheduleNode.hidden = scheduleNode.textContent === ''; }
            if (descriptionNode) { descriptionNode.textContent = selectedItem ? String(selectedItem.locationLabel || '') : ''; descriptionNode.hidden = descriptionNode.textContent === ''; }
            if (insertButton) insertButton.disabled = !selectedItem;
        };
        const render = function () {
            const query = String(searchNode && searchNode.value || '').trim().toLowerCase();
            const matches = embeddableEvents.filter(function (item) { return (!scopePicker || scopePicker.matches(item.contextHolonId)) && (query === '' || [item.title, item.scheduleLabel, item.locationLabel].join(' ').toLowerCase().indexOf(query) >= 0); });
            if (selectNode) {
                selectNode.innerHTML = '';
                matches.forEach(function (item) { const option = document.createElement('option'); option.value = String(item.id); option.textContent = (String(item.title || '').trim() || ('Événement n°' + String(item.id))) + (item.scheduleLabel ? ' - ' + String(item.scheduleLabel) : ''); selectNode.appendChild(option); });
                selectNode.disabled = matches.length === 0;
            }
            selectedItem = matches.find(function (item) { return Number(item.id) === currentEventId; }) || matches[0] || null;
            if (selectNode && selectedItem) selectNode.value = String(selectedItem.id);
            updatePreview();
        };
        if (createForm instanceof HTMLFormElement) {
            const startInput = createForm.elements.namedItem('start_at');
            const endInput = createForm.elements.namedItem('end_at');
            const shiftDateTimeLocalValue = function (value, hours) {
                const match = String(value || '').match(/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})$/);
                if (!match) return '';
                const date = new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]), Number(match[4]), Number(match[5]));
                if (Number.isNaN(date.getTime())) return '';
                date.setHours(date.getHours() + Number(hours || 0));
                const pad = function (number) { return String(number).padStart(2, '0'); };
                return String(date.getFullYear()) + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate()) + 'T' + pad(date.getHours()) + ':' + pad(date.getMinutes());
            };
            const validateEventEnd = function () {
                if (!(startInput instanceof HTMLInputElement) || !(endInput instanceof HTMLInputElement)) return;
                const isBeforeStart = startInput.value !== '' && endInput.value !== '' && endInput.value < startInput.value;
                endInput.setCustomValidity(isBeforeStart ? String(eventEmbedUi.endAfterStart || '') : '');
            };
            const syncEventEndWithStart = function () {
                if (!(startInput instanceof HTMLInputElement) || !(endInput instanceof HTMLInputElement)) return;
                const startValue = String(startInput.value || '');
                if (startValue === '') {
                    endInput.removeAttribute('min');
                    validateEventEnd();
                    return;
                }

                endInput.min = startValue;
                const shouldSetAutomaticEnd = endInput.value === ''
                    || endInput.dataset.omoEventAutoEnd === '1'
                    || endInput.value < startValue;
                if (shouldSetAutomaticEnd) {
                    const automaticEnd = shiftDateTimeLocalValue(startValue, 1);
                    if (automaticEnd !== '') {
                        endInput.value = automaticEnd;
                        endInput.dataset.omoEventAutoEnd = '1';
                    }
                }
                validateEventEnd();
            };
            const syncEventStartWithEnd = function () {
                if (!(startInput instanceof HTMLInputElement) || !(endInput instanceof HTMLInputElement)) return;
                const endValue = String(endInput.value || '');
                if (endValue === '') {
                    startInput.removeAttribute('max');
                    validateEventEnd();
                    return;
                }

                startInput.max = endValue;
                const shouldSetAutomaticStart = startInput.value === ''
                    || startInput.dataset.omoEventAutoStart === '1'
                    || startInput.value > endValue;
                if (shouldSetAutomaticStart) {
                    const automaticStart = shiftDateTimeLocalValue(endValue, -1);
                    if (automaticStart !== '') {
                        startInput.value = automaticStart;
                        startInput.dataset.omoEventAutoStart = '1';
                    }
                }
                syncEventEndWithStart();
            };
            if (startInput instanceof HTMLInputElement) {
                startInput.addEventListener('input', function () { startInput.dataset.omoEventAutoStart = '0'; });
                startInput.addEventListener('change', syncEventEndWithStart);
            }
            if (endInput instanceof HTMLInputElement) {
                endInput.addEventListener('input', function () {
                    endInput.dataset.omoEventAutoEnd = '0';
                    syncEventStartWithEnd();
                });
                endInput.addEventListener('change', syncEventStartWithEnd);
            }
            syncEventEndWithStart();
        }
        if (eventScopeHost instanceof Element && typeof window.omoMountHolonScopePicker === 'function') {
            scopePicker = window.omoMountHolonScopePicker({
                host: eventScopeHost,
                organizationId: resourcePickerOrganizationId,
                initialHolonId: Number(eventEmbedCreateContext.holonId || resourcePickerInitialHolonId || 0),
                initialScope: 'local',
                labels: resourcePickerScopeUi,
                onChange: function (holonId) {
                    eventEmbedCreateContext.holonId = Number(holonId || 0);
                    render();
                }
            });
        } else {
            scopePicker = mountPvResourceScopePicker(modalBody, '[data-omo-pv-event-embed-scope]', render);
        }
        window.addEventListener('common-topbar-modal-close', function () { if (!resolved) cleanup(); }, {once: true});
        if (searchNode) { searchNode.addEventListener('input', render); searchNode.focus(); }
        if (selectNode) selectNode.addEventListener('change', updatePreview);
        cancelButtons.forEach(function (button) { button.addEventListener('click', function () { cleanup(); window.commonTopbarCloseModal(); }); });
        if (removeButton) removeButton.addEventListener('click', function () { if (targetNode && typeof field.removeNode === 'function') resolved = field.removeNode(targetNode); window.commonTopbarCloseModal(); });
        if (insertButton) insertButton.addEventListener('click', function () { if (selectedItem) insertEvent(selectedItem); });
        if (createForm instanceof HTMLFormElement) createForm.addEventListener('submit', function (event) {
            event.preventDefault();
            const titleInput = createForm.elements.namedItem('title');
            const title = titleInput ? String(titleInput.value || '').trim() : '';
            if (title === '') {
                if (titleInput && typeof titleInput.focus === 'function') titleInput.focus();
                return;
            }
            if (createSubmit) createSubmit.disabled = true;
            const formData = new FormData(createForm);
            formData.set('action', 'create_event');
            formData.set('document_id', String(documentId));
            formData.set('oid', String(eventEmbedCreateContext.organizationId || 0));
            formData.set('editor_token', editorToken);
            formData.set('IDholon', String(eventEmbedCreateContext.holonId || 0));
            fetch(actionUrl, {method: 'POST', body: formData, credentials: 'same-origin'})
                .then(function (response) { return response.json(); })
                .then(function (payload) {
                    const newEvent = payload && payload.event && typeof payload.event === 'object' ? payload.event : null;
                    if (!payload || !payload.status || !newEvent || Number(newEvent.id || 0) <= 0) {
                        throw new Error(payload && payload.message ? payload.message : eventEmbedUi.createError || '');
                    }
                    embeddableEvents.push(newEvent);
                    embeddableEvents.sort(function (left, right) { return String(left.startAt || '').localeCompare(String(right.startAt || '')); });
                    insertEvent(newEvent);
                })
                .catch(function (error) { window.alert(String(error && error.message || eventEmbedUi.createError || '')); })
                .finally(function () { if (createSubmit) createSubmit.disabled = false; });
        });
        render();
    }

    function buildPvIndicatorEmbedHtml(indicatorItem) {
        const indicatorId = Number.parseInt(String(indicatorItem && indicatorItem.id || ''), 10);
        if (!Number.isInteger(indicatorId) || indicatorId <= 0) {
            return '';
        }

        const title = String(indicatorItem.title || '').trim() || ('Indicateur #' + String(indicatorId));
        const description = String(indicatorItem.description || '').trim();
        const indicatorKind = String(indicatorItem && indicatorItem.kind || '').trim() === 'group' ? 'group' : 'indicator';
        const routeHash = indicatorKind === 'group' ? ('stats-g' + String(indicatorId)) : ('stats-i' + String(indicatorId));
        const valueLabel = String(indicatorItem.valueLabel || '').trim();
        const dateLabel = String(indicatorItem.dateLabel || '').trim();
        const statusLabel = String(indicatorItem.statusLabel || '').trim();
        const contextLabel = String(indicatorItem.contextLabel || '').trim();
        const chartMinLabel = String(indicatorItem.chartMinLabel || '').trim();
        const chartMaxLabel = String(indicatorItem.chartMaxLabel || '').trim();
        const chartHtml = String(indicatorItem.chartHtml || '').trim();
        const overdueSeverity = indicatorItem && indicatorItem.overdueSeverity === 'warning' ? 'warning' : 'error';
        const statusClass = indicatorItem && indicatorItem.isOverdue
            ? (overdueSeverity === 'warning' ? ' omo-indicator-embed--warning' : ' omo-indicator-embed--overdue')
            : (statusLabel !== '' ? ' omo-indicator-embed--current' : '');
        const statusDotClass = indicatorItem && indicatorItem.isOverdue
            ? (overdueSeverity === 'warning' ? ' omo-indicator-embed__status-dot--warning' : ' omo-indicator-embed__status-dot--overdue')
            : (statusLabel !== '' ? ' omo-indicator-embed__status-dot--current' : ' omo-indicator-embed__status-dot--unknown');
        return '<span class="omo-indicator-embed' + statusClass + '" contenteditable="false" data-omo-embed-type="indicator"'
            + ' data-omo-indicator-id="' + String(indicatorId) + '"'
            + ' data-omo-indicator-kind="' + indicatorKind + '"'
            + ' data-omo-indicator-title="' + escapeDocumentEmbedHtml(title) + '"'
            + (description !== '' ? ' data-omo-indicator-description="' + escapeDocumentEmbedHtml(description) + '"' : '')
            + (valueLabel !== '' ? ' data-omo-indicator-value="' + escapeDocumentEmbedHtml(valueLabel) + '"' : '')
            + (dateLabel !== '' ? ' data-omo-indicator-date="' + escapeDocumentEmbedHtml(dateLabel) + '"' : '')
            + (statusLabel !== '' ? ' data-omo-indicator-status="' + escapeDocumentEmbedHtml(statusLabel) + '"' : '')
            + (contextLabel !== '' ? ' data-omo-indicator-context="' + escapeDocumentEmbedHtml(contextLabel) + '"' : '')
            + (chartMinLabel !== '' ? ' data-omo-indicator-chart-min="' + escapeDocumentEmbedHtml(chartMinLabel) + '"' : '')
            + (chartMaxLabel !== '' ? ' data-omo-indicator-chart-max="' + escapeDocumentEmbedHtml(chartMaxLabel) + '"' : '')
            + (indicatorItem && indicatorItem.isOverdue ? ' data-omo-indicator-overdue-severity="' + overdueSeverity + '"' : '')
            + (indicatorItem && indicatorItem.isOverdue ? ' data-omo-indicator-overdue="1"' : '')
            + '><span class="omo-indicator-embed__main">'
            + '<span class="omo-indicator-embed__chart">'
            + '<span class="omo-indicator-embed__chart-plot">'
            + (chartHtml !== '' ? '<span class="omo-indicator-embed__chart-svg">' + chartHtml + '</span>' : '')
            + '</span></span>'
            + '<span class="omo-indicator-embed__copy"><strong><a class="omo-indicator-embed__title" href="#' + routeHash + '"><span class="omo-indicator-embed__status-dot' + statusDotClass + '" aria-hidden="true"></span><span>' + escapeDocumentEmbedHtml(title) + '</span></a></strong>'
            + (description !== '' ? '<span class="omo-indicator-embed__description">' + escapeDocumentEmbedHtml(description) + '</span>' : '')
            + '</span>'
            + '<span class="omo-indicator-embed__values"><b>' + escapeDocumentEmbedHtml(valueLabel) + '</b>'
            + (dateLabel !== '' ? '<time>' + escapeDocumentEmbedHtml(dateLabel) + '</time>' : '')
            + (statusLabel !== '' ? '<em>' + escapeDocumentEmbedHtml(statusLabel) + '</em>' : '')
            + '</span></span></span>';
    }

    function addPvIndicatorValue(field, pointId, context) {
        if (!field || !context || !context.node || !context.input || !context.button) {
            return;
        }

        const rawValue = String(context.input.value || '').trim();
        const indicatorId = Number.parseInt(String(context.indicatorId || ''), 10);
        if (!rawValue || !Number.isInteger(indicatorId) || indicatorId <= 0) {
            context.input.focus();
            return;
        }

        context.input.disabled = true;
        context.button.disabled = true;
        const previousLabel = context.button.textContent;
        context.button.textContent = <?= json_encode(omoDocumentsPvEditorT('documents.pv_editor.indicator.value_saving'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const formData = new FormData();
        formData.append('action', 'add_indicator_value');
        formData.append('document_id', String(documentId));
        formData.append('oid', String(organizationId));
        formData.append('editor_token', editorToken);
        formData.append('point_id', String(pointId));
        formData.append('indicator_id', String(indicatorId));
        formData.append('value', rawValue);

        fetch(actionUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        })
            .then(function (response) {
                return response.json().then(function (payload) {
                    if (!response.ok || !payload || payload.status !== true || !payload.indicator) {
                        throw payload || new Error('indicator_value_failed');
                    }
                    return payload;
                });
            })
            .then(function (payload) {
                if (typeof field.replaceNodeWithHtml === 'function') {
                    field.replaceNodeWithHtml(context.node, buildPvIndicatorEmbedHtml(payload.indicator), false);
                }
            })
            .catch(function (payload) {
                context.input.disabled = false;
                context.button.disabled = false;
                context.button.textContent = previousLabel;
                if (window.alert) {
                    window.alert(String(payload && payload.message || <?= json_encode(omoDocumentsPvEditorT('documents.pv_editor.indicator.value_error'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>));
                }
            });
    }

    function refreshPvIndicatorEmbedSnapshots(field) {
        if (!field || typeof field.getEditableElement !== 'function' || typeof field.replaceNodeWithHtml !== 'function') {
            return;
        }

        const editable = field.getEditableElement();
        if (!editable) {
            return;
        }

        editable.querySelectorAll('.omo-indicator-embed[data-omo-embed-type="indicator"]').forEach(function (embedNode) {
            if (String(embedNode.getAttribute('data-omo-indicator-kind') || '') === 'group') {
                return;
            }

            const indicatorId = Number.parseInt(String(embedNode.getAttribute('data-omo-indicator-id') || ''), 10);
            const indicatorItem = embeddableIndicators.find(function (item) {
                return String(item && item.kind || 'indicator') !== 'group' && Number(item && item.id) === indicatorId;
            });
            if (indicatorItem) {
                field.replaceNodeWithHtml(embedNode, buildPvIndicatorEmbedHtml(indicatorItem), false);
            }
        });
    }

    function refreshPvProjectEmbedReviews(field) {
        if (!field || typeof field.getEditableElement !== 'function') {
            return;
        }

        const editable = field.getEditableElement();
        if (!(editable instanceof Element)) {
            return;
        }

        editable.querySelectorAll('[data-omo-project-embed-runtime]').forEach(function (runtime) {
            runtime.remove();
        });

        editable.querySelectorAll('.omo-project-embed[data-omo-embed-type="project"]').forEach(function (embedNode) {
            const projectId = Number.parseInt(String(embedNode.getAttribute('data-omo-project-id') || ''), 10);
            if (!Number.isInteger(projectId) || projectId <= 0) {
                return;
            }

            fetch('/omo/api/projects/children.php?id=' + encodeURIComponent(String(projectId)) + '&embed=pv&format=json', {
                credentials: 'same-origin',
                cache: 'no-store'
            }).then(function (response) {
                if (!response.ok) {
                    throw new Error('Impossible de charger la relecture du projet.');
                }
                return response.json();
            }).then(function (payload) {
                if (!payload || !payload.success || !embedNode.isConnected) {
                    return;
                }

                const status = String(payload.status || '').trim();
                const statusLabel = String(payload.statusLabel || '').trim();
                embedNode.setAttribute('data-omo-project-status', status);
                embedNode.setAttribute('data-omo-project-status-label', statusLabel);
                const header = embedNode.querySelector(':scope > strong');
                if (header instanceof Element) {
                    Array.from(header.children).filter(function (childNode) {
                        if (childNode.tagName !== 'EM') {
                            return false;
                        }
                        const capsuleLabel = String(childNode.textContent || '').trim().toUpperCase();
                        return !/^P[1-5]$/.test(capsuleLabel) && !/^(?:S|M|L|XL|XXL)$/.test(capsuleLabel);
                    }).forEach(function (oldStatusNode) {
                        oldStatusNode.remove();
                    });
                    const metadata = Array.from(embedNode.children).find(function (child) {
                        return child instanceof HTMLElement && child.tagName === 'EM';
                    });
                    if (metadata instanceof HTMLElement) {
                        const parts = String(metadata.textContent || '').split(' · ');
                        const lastPart = String(parts[parts.length - 1] || '').trim();
                        const knownStatusLabels = projectEmbedUi.statusOptions.map(function (option) {
                            return String(option && option.label || '').trim();
                        });
                        if (knownStatusLabels.indexOf(lastPart) >= 0) {
                            parts.pop();
                            metadata.textContent = parts.join(' · ');
                            if (String(metadata.textContent || '').trim() === '') {
                                metadata.remove();
                            }
                        }
                    }

                    if (statusLabel !== '') {
                        const currentStatusNode = document.createElement('em');
                        currentStatusNode.className = 'omo-project-embed__status'
                            + (/^(?:someday|ready|in_progress|blocked|review|done)$/.test(status) ? ' omo-project-embed__status--' + status : '');
                        currentStatusNode.textContent = statusLabel;
                        const firstCapsule = Array.from(header.children).find(function (child) {
                            return child.tagName === 'EM';
                        });
                        header.insertBefore(currentStatusNode, firstCapsule || null);
                    }
                }

                if (!payload.hasChildren || !payload.statusBarHtml) {
                    return;
                }

                const runtime = document.createElement('span');
                runtime.className = 'omo-pv-editor__project-review';
                runtime.setAttribute('contenteditable', 'false');
                runtime.setAttribute('data-omo-project-embed-runtime', '1');
                runtime.setAttribute('data-omo-project-node', '');
                runtime.setAttribute('data-omo-project-id', String(projectId));
                runtime.setAttribute('data-omo-project-children-loading', String(projectEmbedUi.childrenLoading || ''));
                runtime.setAttribute('data-omo-project-children-empty', String(projectEmbedUi.childrenEmpty || ''));
                runtime.setAttribute('data-omo-project-children-error', String(projectEmbedUi.childrenError || ''));
                runtime.innerHTML = '<button type="button" class="omo-pv-editor__project-review-toggle" data-omo-project-embed-toggle aria-expanded="false">'
                    + '<span class="omo-pv-editor__project-review-label">' + escapeDocumentEmbedHtml(projectEmbedUi.childrenLabel || '') + '</span>'
                    + String(payload.statusBarHtml) + '</button>'
                    + '<span class="omo-pv-editor__project-review-children omo-project-embed__children" data-omo-project-embed-children hidden></span>';

                embedNode.appendChild(runtime);
            }).catch(function () {
                // The embedded project remains editable when its live review cannot be loaded.
            });
        });
    }

    function getPvChecklistRunReview(runId) {
        const normalizedRunId = Number(runId || 0);
        if (!Number.isInteger(normalizedRunId) || normalizedRunId <= 0) {
            return Promise.reject(new Error('Instance de processus invalide.'));
        }
        if (checklistRunReviewCache.has(normalizedRunId)) {
            return checklistRunReviewCache.get(normalizedRunId);
        }
        const request = fetch('/omo/api/checklist/pv_run_review.php?id=' + encodeURIComponent(String(normalizedRunId)), {
            credentials: 'same-origin',
            cache: 'no-store'
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('Impossible de charger l’instance du processus.');
            }
            return response.json();
        }).then(function (payload) {
            if (!payload || !payload.success) {
                throw new Error('Impossible de charger l’instance du processus.');
            }
            return payload;
        }).catch(function (error) {
            checklistRunReviewCache.delete(normalizedRunId);
            throw error;
        });
        checklistRunReviewCache.set(normalizedRunId, request);
        return request;
    }

    function buildPvChecklistRunItemsBar(items) {
        let html = '<span class="omo-project-status-bar omo-checklist-embed__items-bar">';
        (Array.isArray(items) ? items : []).forEach(function (item) {
            const projectId = Number(item && item.projectId || 0);
            const status = String(item && item.status || 'someday');
            const statusClass = /^(?:someday|ready|in_progress|blocked|review|done)$/.test(status) ? status : 'someday';
            const title = escapeDocumentEmbedHtml(String(item && item.title || 'Element'));
            const statusLabel = escapeDocumentEmbedHtml(String(item && item.statusLabel || 'En attente'));
            const size = escapeDocumentEmbedHtml(String(item && item.size || 'M'));
            const weight = Math.max(1, Number(item && item.weight || 1));
            const tooltip = title + ' - ' + statusLabel + ' - ' + size;
            const segment = '<span class="omo-project-status-bar__segment omo-project-status-bar__segment--' + statusClass + '" style="flex:1 1 0" title="' + tooltip + '"></span>';
            html += projectId > 0
                ? '<a href="#projects-d' + String(projectId) + '" class="omo-checklist-embed__item-segment" style="flex:' + String(weight) + ' 1 0">' + segment + '</a>'
                : '<span class="omo-project-status-bar__segment omo-project-status-bar__segment--' + statusClass + '" style="flex:' + String(weight) + ' 1 0" title="' + tooltip + '"></span>';
        });
        return html + '</span>';
    }

    function buildPvChecklistItemsList(items) {
        let html = '<span class="omo-checklist-embed__items-list">';
        (Array.isArray(items) ? items : []).forEach(function (item) {
            const projectId = Number(item && item.projectId || 0);
            const status = String(item && item.status || 'someday');
            const statusClass = /^(?:someday|ready|in_progress|blocked|review|done)$/.test(status) ? status : 'someday';
            const title = escapeDocumentEmbedHtml(String(item && item.title || 'Element'));
            const statusLabel = escapeDocumentEmbedHtml(String(item && item.statusLabel || 'En attente'));
            const roleLabel = String(item && item.holonLabel || '').trim();
            const responsibleLabel = String(item && item.responsibleLabel || '').trim();
            const metadata = [roleLabel, responsibleLabel].filter(function (label) { return label !== ''; });
            const titleHtml = projectId > 0
                ? '<a class="omo-project-embed__child-title" href="#projects-d' + String(projectId) + '">' + title + '</a>'
                : '<span class="omo-project-embed__child-title">' + title + '</span>';
            html += '<span class="omo-checklist-embed__item"><span class="omo-project-embed__child-copy omo-checklist-embed__item-copy">' + titleHtml
                + (metadata.length > 0 ? '<span class="omo-project-embed__child-meta">' + escapeDocumentEmbedHtml(metadata.join(' - ')) + '</span>' : '')
                + '</span>'
                + '<span class="omo-checklist-embed__item-summary"' + (projectId > 0 ? ' data-omo-checklist-item-project-summary="' + String(projectId) + '"' : '') + '><span class="omo-project-embed__status omo-project-embed__status--' + statusClass + '">' + statusLabel + '</span></span>'
                + (canCompleteChecklistProjects && projectId > 0 ? '<button type="button" class="generic-action-button generic-action-button--secondary omo-checklist-embed__complete-archive" data-omo-checklist-complete-archive data-omo-project-id="' + String(projectId) + '">' + escapeDocumentEmbedHtml(String(checklistEmbedUi.completeArchive || '')) + '</button>' : '')
                + '</span>';
        });
        return html + '</span>';
    }

    function refreshPvChecklistRunItemSubprojectBars(childrenHost) {
        childrenHost.querySelectorAll('[data-omo-checklist-item-project-summary]').forEach(function (summaryNode) {
            const projectId = Number(summaryNode.getAttribute('data-omo-checklist-item-project-summary') || 0);
            if (!Number.isInteger(projectId) || projectId <= 0) return;
            fetch('/omo/api/projects/children.php?id=' + encodeURIComponent(String(projectId)) + '&embed=pv&format=json', {
                credentials: 'same-origin',
                cache: 'no-store'
            }).then(function (response) {
                if (!response.ok) throw new Error('Impossible de charger la relecture du projet.');
                return response.json();
            }).then(function (payload) {
                if (!payload || !payload.success || !payload.hasChildren || !payload.statusBarHtml || !summaryNode.isConnected) return;
                summaryNode.innerHTML = String(payload.statusBarHtml);
                summaryNode.classList.add('is-project-summary');
            }).catch(function () {});
        });
    }

    function refreshPvChecklistEmbedReviews(field) {
        const editable = field && typeof field.getEditableElement === 'function' ? field.getEditableElement() : null;
        if (!(editable instanceof Element)) return;
        const expandedStates = new Map();
        editable.querySelectorAll('.omo-checklist-embed[data-omo-embed-type="checklist"]').forEach(function (embedNode) {
            const checklistId = Number(embedNode.getAttribute('data-omo-checklist-id') || 0);
            const runtime = embedNode.querySelector('[data-omo-checklist-embed-runtime]');
            const childrenHost = runtime ? runtime.querySelector('[data-omo-checklist-run-children]') : null;
            if (checklistId <= 0 || !(runtime instanceof Element) || !(childrenHost instanceof Element) || childrenHost.hidden) return;
            const containerToggle = runtime.querySelector('[data-omo-checklist-container-toggle][aria-expanded="true"]');
            if (containerToggle instanceof HTMLButtonElement) {
                expandedStates.set(checklistId, {type: 'container'});
                return;
            }
            const runToggle = runtime.querySelector('[data-omo-checklist-run-toggle][aria-expanded="true"]');
            const runId = runToggle instanceof HTMLButtonElement ? Number(runToggle.getAttribute('data-omo-checklist-run-id') || 0) : 0;
            if (Number.isInteger(runId) && runId > 0) {
                expandedStates.set(checklistId, {type: 'run', runId: runId});
            }
        });
        checklistRunReviewCache.clear();
        editable.querySelectorAll('[data-omo-checklist-embed-runtime]').forEach(function (node) { node.remove(); });
        editable.querySelectorAll('.omo-checklist-embed[data-omo-embed-type="checklist"]').forEach(function (embedNode) {
            const checklistId = Number(embedNode.getAttribute('data-omo-checklist-id') || 0); if (checklistId <= 0) return;
            fetch('/omo/api/checklist/pv_review.php?id=' + encodeURIComponent(String(checklistId)), {credentials: 'same-origin', cache: 'no-store'}).then(function (response) { return response.json(); }).then(function (payload) {
                if (!payload || !payload.success || !embedNode.isConnected) return;
                const runtime = document.createElement('span'); runtime.className = 'omo-checklist-embed__review' + (Number(payload.overdueCount || 0) > 0 ? ' is-overdue' : ''); runtime.setAttribute('contenteditable', 'false'); runtime.setAttribute('data-omo-checklist-embed-runtime', '1');
                const entries = Array.isArray(payload.entries) ? payload.entries : [];
                const hasNoRuns = !payload.isContainer && entries.length === 0;
                const label = payload.isContainer ? 'Activités récurrentes' : 'Instances en cours';
                let overview = '';
                if (payload.isContainer) {
                    overview = '<button type="button" class="omo-checklist-embed__container-toggle" data-omo-checklist-container-toggle aria-expanded="false"><span class="omo-project-status-bar">';
                    entries.forEach(function (entry) {
                        const projectId = Number(entry && entry.projectId || 0);
                        const status = String(entry && entry.status || 'someday');
                        const weight = Math.max(1, Number(entry && entry.weight || 1));
                        if (projectId > 0 && /^(?:someday|ready|in_progress|blocked|review|done)$/.test(status)) overview += '<span class="omo-project-status-bar__segment omo-project-status-bar__segment--' + status + '" style="flex:' + String(weight) + ' 1 0" title="' + escapeDocumentEmbedHtml(String(entry.title || '')) + '"></span>';
                    });
                    overview += '</span></button>';
                } else {
                    overview = '<span class="omo-checklist-embed__instances">';
                    if (!hasNoRuns) {
                        entries.forEach(function (entry) {
                            const runId = Number(entry && entry.runId || 0);
                            const status = String(entry && entry.status || 'someday');
                            if (runId <= 0) return;
                            const statusClass = /^(?:someday|ready|in_progress|blocked|review|done)$/.test(status) ? status : 'someday';
                            overview += '<span class="omo-checklist-embed__instance"><button type="button" class="omo-checklist-embed__instance-toggle" data-omo-checklist-run-toggle data-omo-checklist-run-id="' + String(runId) + '" aria-expanded="false">'
                                + '<span class="omo-checklist-embed__instance-title">' + escapeDocumentEmbedHtml(String(entry.title || 'Instance')) + '</span>'
                                + '<span class="omo-project-embed__status omo-project-embed__status--' + statusClass + '">' + escapeDocumentEmbedHtml(String(entry.statusLabel || status)) + '</span></button>'
                                + '<span class="omo-checklist-embed__instance-bar" data-omo-checklist-run-bar="' + String(runId) + '"></span></span>';
                        });
                    }
                    overview += '</span>';
                }
                const labelHtml = hasNoRuns
                    ? '<em class="omo-checklist-embed__empty-runs">' + escapeDocumentEmbedHtml(String(checklistEmbedUi.emptyRuns || 'Aucune instance en cours.')) + '</em>'
                    : escapeDocumentEmbedHtml(label) + (Number(payload.overdueCount || 0) > 0 ? ' &#9888;' : '');
                runtime.innerHTML = '<span class="omo-checklist-embed__review-label">' + labelHtml + '</span>' + overview
                    + '<span class="omo-pv-editor__project-review-children omo-project-embed__children omo-checklist-embed__children" data-omo-checklist-run-children hidden></span>';
                if (payload.isContainer) {
                    runtime.__omoChecklistContainerItems = entries;
                }
                embedNode.appendChild(runtime);
                if (!payload.isContainer) {
                    entries.forEach(function (entry) {
                        const runId = Number(entry && entry.runId || 0);
                        const barHost = runtime.querySelector('[data-omo-checklist-run-bar="' + String(runId) + '"]');
                        if (!(barHost instanceof Element) || runId <= 0) return;
                        getPvChecklistRunReview(runId).then(function (runPayload) {
                            if (!barHost.isConnected) return;
                            const items = Array.isArray(runPayload.items) ? runPayload.items : [];
                            if (items.length > 0) barHost.innerHTML = buildPvChecklistRunItemsBar(items);
                        }).catch(function () {});
                    });
                }
                const expandedState = expandedStates.get(checklistId);
                if (expandedState && expandedState.type === 'container') {
                    const containerToggle = runtime.querySelector('[data-omo-checklist-container-toggle]');
                    if (containerToggle instanceof HTMLButtonElement) loadPvChecklistContainerReview(containerToggle);
                } else if (expandedState && expandedState.type === 'run') {
                    const runToggle = runtime.querySelector('[data-omo-checklist-run-toggle][data-omo-checklist-run-id="' + String(expandedState.runId) + '"]');
                    if (runToggle instanceof HTMLButtonElement) loadPvChecklistRunReview(runToggle);
                }
            }).catch(function () {});
        });
    }

    function loadPvChecklistRunReview(toggle) {
        const runtime = toggle.closest('[data-omo-checklist-embed-runtime]');
        const childrenHost = runtime ? runtime.querySelector('[data-omo-checklist-run-children]') : null;
        const runId = Number(toggle.getAttribute('data-omo-checklist-run-id') || 0);
        if (!(runtime instanceof Element) || !(childrenHost instanceof Element) || !Number.isInteger(runId) || runId <= 0) {
            return;
        }

        if (childrenHost.dataset.omoChecklistRunId === String(runId) && !childrenHost.hidden) {
            childrenHost.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
            toggle.classList.remove('is-expanded');
            return;
        }

        runtime.querySelectorAll('[data-omo-checklist-run-toggle]').forEach(function (button) {
            button.setAttribute('aria-expanded', 'false');
            button.classList.remove('is-expanded');
        });
        childrenHost.hidden = false;
        childrenHost.textContent = 'Chargement des éléments…';
        childrenHost.className = 'omo-pv-editor__project-review-children omo-project-embed__children omo-checklist-embed__children omo-project-embed__children-loading';
        toggle.disabled = true;

        getPvChecklistRunReview(runId).then(function (payload) {
            if (!runtime.isConnected) {
                throw new Error('Impossible de charger l’instance du processus.');
            }
            const items = Array.isArray(payload.items) ? payload.items : [];
            childrenHost.dataset.omoChecklistRunId = String(runId);
            childrenHost.className = 'omo-pv-editor__project-review-children omo-project-embed__children omo-checklist-embed__children';
            if (items.length === 0) {
                childrenHost.textContent = 'Aucun élément dans cette instance.';
            } else {
                childrenHost.innerHTML = buildPvChecklistItemsList(items);
                refreshPvChecklistRunItemSubprojectBars(childrenHost);
            }
            toggle.setAttribute('aria-expanded', 'true');
            toggle.classList.add('is-expanded');
        }).catch(function () {
            childrenHost.className = 'omo-pv-editor__project-review-children omo-project-embed__children omo-checklist-embed__children omo-project-embed__children-error';
            childrenHost.textContent = 'Impossible de charger les éléments de cette instance.';
            childrenHost.hidden = false;
            toggle.setAttribute('aria-expanded', 'false');
        }).finally(function () {
            toggle.disabled = false;
        });
    }

    function loadPvChecklistContainerReview(toggle) {
        const runtime = toggle.closest('[data-omo-checklist-embed-runtime]');
        const childrenHost = runtime ? runtime.querySelector('[data-omo-checklist-run-children]') : null;
        const items = runtime && Array.isArray(runtime.__omoChecklistContainerItems) ? runtime.__omoChecklistContainerItems : [];
        if (!(runtime instanceof Element) || !(childrenHost instanceof Element)) {
            return;
        }
        if (childrenHost.dataset.omoChecklistReviewType === 'container' && !childrenHost.hidden) {
            childrenHost.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
            toggle.classList.remove('is-expanded');
            return;
        }

        childrenHost.hidden = false;
        childrenHost.dataset.omoChecklistReviewType = 'container';
        childrenHost.className = 'omo-pv-editor__project-review-children omo-project-embed__children omo-checklist-embed__children';
        if (items.length === 0) {
            childrenHost.textContent = 'Aucune activité récurrente active.';
        } else {
            childrenHost.innerHTML = buildPvChecklistItemsList(items);
            refreshPvChecklistRunItemSubprojectBars(childrenHost);
        }
        toggle.setAttribute('aria-expanded', 'true');
        toggle.classList.add('is-expanded');
    }

    function completePvChecklistProject(button) {
        const projectId = Number(button.getAttribute('data-omo-project-id') || 0);
        if (!canCompleteChecklistProjects || button.disabled || !Number.isInteger(projectId) || projectId <= 0) {
            return;
        }
        const previousLabel = button.textContent;
        button.disabled = true;
        button.textContent = String(checklistEmbedUi.completeArchiving || '');
        const formData = new FormData();
        formData.append('action', 'complete_archive_checklist_project');
        formData.append('document_id', String(documentId));
        formData.append('oid', String(organizationId));
        formData.append('editor_token', editorToken);
        formData.append('project_id', String(projectId));
        fetch(actionUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        }).then(function (response) {
            return response.json().then(function (payload) {
                if (!response.ok || !payload || payload.status !== true) {
                    throw payload || new Error('checklist_project_archive_failed');
                }
                return payload;
            });
        }).then(function () {
            if (typeof window.omoRefreshProjectsDrawerAfterMutation === 'function') {
                window.omoRefreshProjectsDrawerAfterMutation();
            }
            const editorHost = button.closest('[data-omo-pv-point-editor-host]');
            const fieldContainer = button.closest('.omo-simple-html-field');
            const field = editorHost && editorHost.__omoPvPointField
                ? editorHost.__omoPvPointField
                : (fieldContainer && fieldContainer.__omoSimpleHtmlField);
            if (field && typeof field.getEditableElement === 'function') {
                refreshPvChecklistEmbedReviews(field);
            }
        }).catch(function (payload) {
            if (window.alert) {
                window.alert(String(payload && payload.message || checklistEmbedUi.completeArchiveError || ''));
            }
        }).finally(function () {
            if (button.isConnected) {
                button.disabled = false;
                button.textContent = previousLabel;
            }
        });
    }

    function getPvIndicatorEmbedItemKey(indicatorItem) {
        const kind = String(indicatorItem && indicatorItem.kind || '') === 'group' ? 'group' : 'indicator';
        return kind + ':' + String(indicatorItem && indicatorItem.id || '');
    }

    function openPvIndicatorEmbedPicker(field, targetNode) {
        if (!canEmbedIndicators || !field || typeof field.createTemporaryCursorMarker !== 'function' || typeof field.replaceMarkerWithHtml !== 'function' || typeof window.commonTopbarOpenModal !== 'function') {
            return;
        }

        const currentIndicatorId = targetNode instanceof Element
            ? Number.parseInt(String(targetNode.getAttribute('data-omo-indicator-id') || ''), 10)
            : 0;
        const currentIndicatorKind = targetNode instanceof Element && String(targetNode.getAttribute('data-omo-indicator-kind') || '') === 'group'
            ? 'group'
            : 'indicator';
        let marker = targetNode ? null : field.createTemporaryCursorMarker();
        let resolved = false;
        const modalHtml = '<div class="omo-document-embed-picker omo-resource-picker">'
            + '<aside class="omo-resource-picker__navigation" data-omo-pv-indicator-embed-scope></aside>'
            + '<div class="omo-resource-picker__content">'
            + '<label class="omo-resource-picker__quick-search"><img src="/common/assets/icon-topbar-search.png" alt="" aria-hidden="true"><input type="search" class="generic-form-control" data-omo-pv-indicator-embed-search aria-label="' + escapeDocumentEmbedHtml(indicatorEmbedUi.search || '') + '" placeholder="' + escapeDocumentEmbedHtml(indicatorEmbedUi.quickSearchPlaceholder || '') + '"></label>'
            + '<div class="omo-document-embed-picker__field"><select class="generic-form-control omo-document-embed-picker__select" data-omo-pv-indicator-embed-select aria-label="' + escapeDocumentEmbedHtml(indicatorEmbedUi.visibleIndicators || '') + '" size="10"></select></div>'
            + '<div class="omo-document-embed-picker__preview omo-document-embed-picker__preview--indicator" data-omo-pv-indicator-embed-preview></div>'
            + '<div class="omo-document-embed-picker__actions">'
            + (targetNode ? '<button type="button" class="generic-action-button generic-action-button--danger" data-omo-pv-embed-remove>' + escapeDocumentEmbedHtml(indicatorEmbedUi.remove || '') + '</button>' : '')
            + '<button type="button" class="generic-action-button generic-action-button--secondary" data-omo-pv-indicator-embed-cancel>' + escapeDocumentEmbedHtml(indicatorEmbedUi.cancel || '') + '</button><button type="button" class="generic-action-button generic-action-button--main" data-omo-pv-indicator-embed-insert disabled>' + escapeDocumentEmbedHtml(indicatorEmbedUi.insert || '') + '</button></div></div></div>';

        window.commonTopbarOpenModal(indicatorEmbedUi.modalTitle || '', modalHtml, 'html');
        const modalBody = document.getElementById('commonTopbarModalBody');
        if (!(modalBody instanceof Element)) {
            if (marker) field.removeTemporaryMarker(marker);
            return;
        }

        const searchNode = modalBody.querySelector('[data-omo-pv-indicator-embed-search]');
        const selectNode = modalBody.querySelector('[data-omo-pv-indicator-embed-select]');
        const previewNode = modalBody.querySelector('[data-omo-pv-indicator-embed-preview]');
        const cancelButton = modalBody.querySelector('[data-omo-pv-indicator-embed-cancel]');
        const insertButton = modalBody.querySelector('[data-omo-pv-indicator-embed-insert]');
        const removeButton = modalBody.querySelector('[data-omo-pv-embed-remove]');
        let selectedItem = null;
        let scopePicker = null;
        const cleanup = function () { if (marker) field.removeTemporaryMarker(marker); marker = null; };
        const updatePreview = function () {
            if (selectNode && selectNode.value !== '') selectedItem = embeddableIndicators.find(function (item) { return getPvIndicatorEmbedItemKey(item) === String(selectNode.value); }) || null;
            if (previewNode) previewNode.innerHTML = selectedItem ? buildPvIndicatorEmbedHtml(selectedItem) : escapeDocumentEmbedHtml(indicatorEmbedUi.none || '');
            if (insertButton) insertButton.disabled = !selectedItem;
        };
        const render = function () {
            const query = String(searchNode && searchNode.value || '').trim().toLowerCase();
            const matches = embeddableIndicators.filter(function (item) { return (!scopePicker || scopePicker.matches(item.contextHolonId)) && (query === '' || [item.title, item.contextLabel, item.valueLabel, item.statusLabel].join(' ').toLowerCase().indexOf(query) >= 0); });
            if (selectNode) {
                selectNode.innerHTML = '';
                matches.forEach(function (item) { const option = document.createElement('option'); option.value = getPvIndicatorEmbedItemKey(item); option.textContent = (String(item.title || '').trim() || ('Indicateur #' + String(item.id))) + (item.valueLabel ? ' - ' + String(item.valueLabel) : ''); selectNode.appendChild(option); });
                selectNode.disabled = matches.length === 0;
            }
            selectedItem = matches.find(function (item) { return Number(item.id) === currentIndicatorId && String(item.kind || 'indicator') === currentIndicatorKind; }) || matches[0] || null;
            if (selectNode && selectedItem) selectNode.value = getPvIndicatorEmbedItemKey(selectedItem);
            updatePreview();
        };
        scopePicker = mountPvResourceScopePicker(modalBody, '[data-omo-pv-indicator-embed-scope]', render);
        window.addEventListener('common-topbar-modal-close', function () { if (!resolved) cleanup(); }, {once: true});
        if (searchNode) { searchNode.addEventListener('input', render); searchNode.focus(); }
        if (selectNode) selectNode.addEventListener('change', updatePreview);
        if (cancelButton) cancelButton.addEventListener('click', function () { cleanup(); window.commonTopbarCloseModal(); });
        if (removeButton) removeButton.addEventListener('click', function () { if (targetNode && typeof field.removeNode === 'function') resolved = field.removeNode(targetNode); window.commonTopbarCloseModal(); });
        if (insertButton) insertButton.addEventListener('click', function () { const embedHtml = buildPvIndicatorEmbedHtml(selectedItem); if (embedHtml !== '' && targetNode && typeof field.replaceNodeWithHtml === 'function') { resolved = true; field.replaceNodeWithHtml(targetNode, embedHtml); } else if (embedHtml !== '' && marker) { resolved = true; field.replaceMarkerWithHtml(marker, embedHtml); marker = null; } window.commonTopbarCloseModal(); });
        render();
    }

    function getDocumentVisibilityValue() {
        if (!(documentVisibilitySelect instanceof Element)) {
            return '';
        }

        const checked = documentVisibilitySelect.querySelector('.omo-visibility-choice__input:checked');
        return checked instanceof HTMLInputElement ? checked.value : '';
    }

    function setDocumentVisibilityValue(value) {
        if (!(documentVisibilitySelect instanceof Element)) {
            return;
        }

        const input = Array.from(documentVisibilitySelect.querySelectorAll('.omo-visibility-choice__input'))
            .find(function (candidate) {
                return candidate instanceof HTMLInputElement && candidate.value === String(value);
            });
        if (input instanceof HTMLInputElement) {
            input.checked = true;
            if (typeof window.omoSyncVisibilityChoices === 'function') {
                window.omoSyncVisibilityChoices(documentVisibilitySelect);
            }
        }
    }

    function resizeDocumentDescriptionInput() {
        if (!(documentDescriptionInput instanceof HTMLTextAreaElement)) {
            return;
        }

        documentDescriptionInput.style.height = 'auto';
        const maxHeight = 150;
        const contentHeight = Math.max(24, documentDescriptionInput.scrollHeight);
        const nextHeight = Math.min(maxHeight, contentHeight);
        documentDescriptionInput.style.height = nextHeight + 'px';
        documentDescriptionInput.style.overflowY = contentHeight > maxHeight ? 'auto' : 'hidden';
    }

    function normalizeDocumentMetadataText(value) {
        return String(value || '').replace(/\r\n?/g, '\n').trim();
    }

    function documentMetadataIsDirty() {
        if (
            !(documentTitleInput instanceof HTMLInputElement)
            || !(documentDescriptionInput instanceof HTMLTextAreaElement)
            || !(documentVisibilitySelect instanceof Element)
        ) {
            return false;
        }

        return normalizeDocumentMetadataText(documentTitleInput.value) !== normalizeDocumentMetadataText(currentDocumentPayload.title)
            || normalizeDocumentMetadataText(documentDescriptionInput.value) !== normalizeDocumentMetadataText(currentDocumentPayload.description)
            || getDocumentVisibilityValue() !== String(currentDocumentPayload.visibilityType || '');
    }

    function syncDocumentMetadataUi() {
        const isDirty = documentMetadataIsDirty();
        const canGenerateAutoSummary = autoSummaryAvailable && currentDocumentPayload.pvStage === 'review';
        if (documentAutoSummaryButton instanceof HTMLButtonElement) {
            documentAutoSummaryButton.hidden = !canGenerateAutoSummary;
            documentAutoSummaryButton.disabled = autoSummaryPending;
        }
        if (documentMetaSaveButton instanceof HTMLButtonElement) {
            documentMetaSaveButton.disabled = !isDirty || documentMetadataSaving;
            documentMetaSaveButton.classList.toggle('generic-action-button--main', isDirty);
            documentMetaSaveButton.classList.toggle('is-saving', documentMetadataSaving);
            documentMetaSaveButton.textContent = documentMetadataSaving
                ? savingLabel
                : (isDirty ? saveLabel : savedLabel);
        }
    }

    function documentPayloadHasRemoteChanges(documentPayload) {
        if (!documentPayload || typeof documentPayload !== 'object') {
            return false;
        }

        const nextSyncVersion = String(documentPayload.syncVersion || '');
        return nextSyncVersion !== '' && nextSyncVersion !== knownDocumentSyncVersion;
    }

    if (!actionUrl || !Number.isInteger(documentId) || documentId <= 0 || !pointsContainer || !nav) {
        return;
    }

    root.dataset.omoPvEditorReady = '1';

    (function initResizer() {
        if (!(resizer instanceof Element)) {
            return;
        }

        let isResizing = false;

        const applySidebarWidth = function (nextWidth) {
            const numericWidth = Number(nextWidth || 0);
            if (!Number.isFinite(numericWidth) || numericWidth <= 0) {
                return;
            }

            const rootRect = root.getBoundingClientRect();
            const minWidth = 220;
            const maxWidth = Math.max(minWidth, Math.floor(rootRect.width * 0.58));
            const clampedWidth = Math.max(minWidth, Math.min(maxWidth, Math.round(numericWidth)));
            root.style.setProperty('--omo-pv-editor-sidebar-width', clampedWidth + 'px');
        };

        resizer.addEventListener('mousedown', function (event) {
            if (event.button !== 0 || window.matchMedia('(max-width: 980px)').matches) {
                return;
            }

            isResizing = true;
            document.body.classList.add('resizing');
            event.preventDefault();
        });

        document.addEventListener('mousemove', function (event) {
            if (!isResizing) {
                return;
            }

            const rootRect = root.getBoundingClientRect();
            applySidebarWidth(event.clientX - rootRect.left - 5);
        });

        document.addEventListener('mouseup', function () {
            if (!isResizing) {
                return;
            }

            isResizing = false;
            document.body.classList.remove('resizing');
        });
    })();

    function ensureHtmlFieldLibrary(callback) {
        const htmlFieldVersion = '20260903-toolbar-insert-focus';
        if (
            window.omoSimpleHtmlField
            && typeof window.omoSimpleHtmlField.mount === 'function'
            && String(window.omoSimpleHtmlField.version || '') === htmlFieldVersion
        ) {
            callback();
            return;
        }

        const selector = 'script[data-omo-simple-html-field-script="1"][data-omo-simple-html-field-version="' + htmlFieldVersion + '"]';
        const existing = document.querySelector(selector);
        if (existing) {
            if (existing.getAttribute('data-loaded') === '1') {
                callback();
            } else {
                existing.addEventListener('load', callback, { once: true });
            }
            return;
        }

        const script = document.createElement('script');
        script.src = '/omo/assets/js/simple-html-field.js?v=' + encodeURIComponent(htmlFieldVersion);
        script.async = false;
        script.setAttribute('data-omo-simple-html-field-script', '1');
        script.setAttribute('data-omo-simple-html-field-version', htmlFieldVersion);
        script.onload = function () {
            script.setAttribute('data-loaded', '1');
            callback();
        };
        document.head.appendChild(script);
    }

    function syncEmptyNavState() {
        if (!nav) {
            return;
        }

        const hasItems = nav.querySelector('[data-omo-pv-nav-node]') !== null;
        const emptyState = nav.querySelector('.omo-pv-editor__empty');
        if (hasItems && emptyState) {
            emptyState.remove();
            return;
        }

        if (!hasItems && !emptyState) {
            const state = document.createElement('div');
            state.className = 'omo-empty-state omo-pv-editor__empty';
            state.textContent = <?= json_encode(omoDocumentsPvEditorT('documents.pv_editor.nav.empty'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
            nav.appendChild(state);
        }
    }

    function setPointDirtySuppressed(pointId, isSuppressed) {
        const card = root.querySelector('[data-omo-pv-point-card="' + pointId + '"]');
        if (!card) {
            return;
        }

        if (isSuppressed) {
            card.setAttribute('data-omo-pv-point-suppress-dirty', '1');
        } else {
            card.removeAttribute('data-omo-pv-point-suppress-dirty');
        }
    }

    function isPointDirtySuppressed(pointId) {
        const card = root.querySelector('[data-omo-pv-point-card="' + pointId + '"]');
        return !!card && card.getAttribute('data-omo-pv-point-suppress-dirty') === '1';
    }

    function suppressPointDirtyDuring(pointId, callback, releaseDelayMs) {
        setPointDirtySuppressed(pointId, true);
        try {
            callback();
        } finally {
            window.setTimeout(function () {
                setPointDirtySuppressed(pointId, false);
            }, Math.max(0, Number(releaseDelayMs || 140)));
        }
    }

    function syncPointDirtyUi(pointId) {
        const card = root.querySelector('[data-omo-pv-point-card="' + pointId + '"]');
        const isDirty = !!card && card.getAttribute('data-omo-pv-point-dirty') === '1';
        const isSaving = !!card && card.getAttribute('data-omo-pv-point-saving') === '1';

        if (!card) {
            return;
        }

        const saveButton = root.querySelector('[data-omo-pv-point-save="' + pointId + '"]');
        if (saveButton) {
            saveButton.disabled = !isDirty || isSaving;
            saveButton.setAttribute('aria-disabled', (!isDirty || isSaving) ? 'true' : 'false');
            saveButton.classList.toggle('generic-action-button--main', isDirty);
            saveButton.classList.toggle('is-saving', isSaving);
            saveButton.textContent = isSaving ? savingLabel : (isDirty ? saveLabel : savedLabel);
        }
    }

    function setFocusedPoint(pointId) {
        if (!nav) {
            return;
        }

        nav.querySelectorAll('[data-omo-pv-point-nav-row].is-focused').forEach(function (row) {
            if (Number(row.getAttribute('data-omo-pv-point-nav-row') || 0) !== pointId) {
                row.classList.remove('is-focused');
            }
        });

        const row = nav.querySelector('[data-omo-pv-point-nav-row="' + pointId + '"]');
        if (row) {
            row.classList.add('is-focused');
        }
    }

    function clearFocusedPoint(pointId) {
        if (!nav) {
            return;
        }

        const row = nav.querySelector('[data-omo-pv-point-nav-row="' + pointId + '"]');
        if (row) {
            row.classList.remove('is-focused');
        }
    }

    function pointWantsLock(pointId) {
        const card = root.querySelector('[data-omo-pv-point-card="' + pointId + '"]');
        if (!card) {
            return false;
        }

        return card.getAttribute('data-omo-pv-point-dirty') === '1'
            || card.contains(document.activeElement);
    }

    function syncPointLockState(pointId) {
        if (!Number.isInteger(pointId) || pointId <= 0) {
            return Promise.resolve(null);
        }

        return pointWantsLock(pointId)
            ? ensurePointLock(pointId)
            : releasePointLock(pointId);
    }

    function syncDirtyUi() {
        root.querySelectorAll('[data-omo-pv-point-card]').forEach(function (card) {
            const pointId = Number(card.getAttribute('data-omo-pv-point-card') || 0);
            if (Number.isInteger(pointId) && pointId > 0) {
                syncPointDirtyUi(pointId);
            }
        });
    }

    function isAutoSaveEnabled() {
        return autoSaveToggle instanceof HTMLInputElement
            && autoSaveToggle.checked
            && !autoSaveToggle.disabled
            && currentDocumentPayload.isPvValidated !== true
            && String(currentDocumentPayload.pvStage || '') !== 'review';
    }

    function clearDocumentMetadataAutoSave() {
        if (documentMetadataAutoSaveTimer !== null) {
            window.clearTimeout(documentMetadataAutoSaveTimer);
            documentMetadataAutoSaveTimer = null;
        }
    }

    function stopPvEditorBackgroundWork() {
        autoSaveTimers.forEach(function (timerId) {
            window.clearTimeout(timerId);
        });
        autoSaveTimers.clear();
        clearDocumentMetadataAutoSave();

        if (syncPollTimer !== null) {
            window.clearTimeout(syncPollTimer);
            syncPollTimer = null;
        }
        syncPollPending = null;
        if (lockHeartbeatTimer !== null) {
            window.clearInterval(lockHeartbeatTimer);
            lockHeartbeatTimer = null;
        }
        if (editorLifecycleObserver instanceof MutationObserver) {
            editorLifecycleObserver.disconnect();
            editorLifecycleObserver = null;
        }

        activeLockPointIds.clear();
        pendingLockPointIds.clear();
        pendingUnlockPointIds.clear();
    }

    function scheduleDocumentMetadataAutoSave() {
        clearDocumentMetadataAutoSave();
        if (!isAutoSaveEnabled() || !root.isConnected || documentMetadataSaving || !documentMetadataIsDirty()) {
            return;
        }

        documentMetadataAutoSaveTimer = window.setTimeout(function () {
            documentMetadataAutoSaveTimer = null;
            if (!isAutoSaveEnabled() || documentMetadataSaving || !documentMetadataIsDirty()) {
                return;
            }

            saveDocumentMetadata();
        }, autoSaveDelayMs);
    }

    function markDocumentMetadataDirty(scheduleAutoSave = true) {
        documentMetadataChangeVersion += 1;
        syncDocumentMetadataUi();
        if (scheduleAutoSave) {
            scheduleDocumentMetadataAutoSave();
        }
    }

    function clearPointAutoSave(pointId) {
        const timerId = autoSaveTimers.get(pointId);
        if (timerId !== undefined) {
            window.clearTimeout(timerId);
            autoSaveTimers.delete(pointId);
        }
    }

    function schedulePointAutoSave(pointId) {
        clearPointAutoSave(pointId);
        if (!isAutoSaveEnabled() || !root.isConnected) {
            return;
        }

        const card = root.querySelector('[data-omo-pv-point-card="' + pointId + '"]');
        if (!card || card.getAttribute('data-omo-pv-point-dirty') !== '1') {
            return;
        }

        autoSaveTimers.set(pointId, window.setTimeout(function () {
            autoSaveTimers.delete(pointId);
            const currentCard = root.querySelector('[data-omo-pv-point-card="' + pointId + '"]');
            if (!isAutoSaveEnabled() || !currentCard || currentCard.getAttribute('data-omo-pv-point-dirty') !== '1') {
                return;
            }

            if (currentCard.getAttribute('data-omo-pv-point-saving') === '1') {
                schedulePointAutoSave(pointId);
                return;
            }

            savePoint(pointId, true);
        }, autoSaveDelayMs));
    }

    function markPointDirty(pointId, isDirty, scheduleAutoSave = true) {
        if (isDirty && isPointDirtySuppressed(pointId)) {
            return;
        }

        const card = root.querySelector('[data-omo-pv-point-card="' + pointId + '"]');
        if (card) {
            card.setAttribute('data-omo-pv-point-dirty', isDirty ? '1' : '0');
            if (!isDirty) {
                card.removeAttribute('data-omo-pv-point-saving');
                clearPointAutoSave(pointId);
            } else if (scheduleAutoSave) {
                pointChangeVersions.set(pointId, (pointChangeVersions.get(pointId) || 0) + 1);
                schedulePointAutoSave(pointId);
            }
        }

        syncPointDirtyUi(pointId);
        syncPointLockState(pointId);
    }

    function buildPointSignatureMap(pointPayloads) {
        const signatures = {};
        if (!Array.isArray(pointPayloads)) {
            return signatures;
        }

        pointPayloads.forEach(function (pointPayload) {
            if (!pointPayload || !pointPayload.id) {
                return;
            }

            const lock = pointPayload.lock && typeof pointPayload.lock === 'object' ? pointPayload.lock : {};
            signatures[String(pointPayload.id)] = [
                String(pointPayload.syncVersion || ''),
                lock.isActive ? 1 : 0,
                Number(lock.userId || 0),
                lock.isOwnedByCurrentSession ? 1 : 0,
                String(lock.token || ''),
                Number(pointPayload.discussionMessageCount || 0)
            ].join('|');
        });

        return signatures;
    }

    function updateKnownPointSignatures(pointPayloads) {
        knownPointSignatures = buildPointSignatureMap(pointPayloads);
    }

    function mergeKnownPointSignature(pointPayload) {
        if (!pointPayload || !pointPayload.id) {
            return;
        }

        const singleSignatureMap = buildPointSignatureMap([pointPayload]);
        knownPointSignatures[String(pointPayload.id)] = String(singleSignatureMap[String(pointPayload.id)] || '');
        mergeCurrentPointPayload(pointPayload);
    }

    function pointCollectionHasRemoteChanges(pointPayloads) {
        const nextSignatures = buildPointSignatureMap(pointPayloads);
        const nextKeys = Object.keys(nextSignatures).sort();
        const currentKeys = Object.keys(knownPointSignatures).sort();
        if (nextKeys.length !== currentKeys.length) {
            return true;
        }

        for (let index = 0; index < nextKeys.length; index += 1) {
            const nextKey = nextKeys[index];
            const currentKey = currentKeys[index];
            if (nextKey !== currentKey) {
                return true;
            }

            if (String(nextSignatures[nextKey]) !== String(knownPointSignatures[currentKey] || '')) {
                return true;
            }
        }

        return false;
    }

    function syncCurrentPointPayloads(pointPayloads) {
        currentPointPayloads = {};
        if (!Array.isArray(pointPayloads)) {
            return;
        }

        pointPayloads.forEach(function (pointPayload) {
            if (!pointPayload || !pointPayload.id) {
                return;
            }

            currentPointPayloads[String(pointPayload.id)] = pointPayload;
        });
    }

    function mergeCurrentPointPayload(pointPayload) {
        if (!pointPayload || !pointPayload.id) {
            return;
        }

        currentPointPayloads[String(pointPayload.id)] = pointPayload;
    }

    function syncDocumentStageUi(documentPayload) {
        if (stageButtons.length === 0 || !documentPayload || typeof documentPayload !== 'object') {
            return;
        }

        const nextStage = String(documentPayload.pvStage || '').trim();
        const canManageStage = documentPayload.canManagePvStage === true;
        let activeIndex = 0;
        stageButtons.forEach(function (button) {
            const isActive = String(button.getAttribute('data-omo-pv-stage-value') || '') === nextStage;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            button.disabled = !canManageStage
                || (nextStage === 'review' && String(button.getAttribute('data-omo-pv-stage-value') || '') !== 'validated');
            if (isActive) {
                activeIndex = stageButtons.indexOf(button);
            }
        });
        if (stageChoice instanceof Element) {
            stageChoice.style.setProperty('--omo-visibility-option-count', String(Math.max(1, stageButtons.length)));
            stageChoice.style.setProperty('--omo-visibility-active-index', String(activeIndex));
        }
    }

    function syncPvEditorUi(documentPayload) {
        if (!documentPayload || typeof documentPayload !== 'object') {
            return;
        }

        if (secretaryName instanceof Element) {
            const label = String(documentPayload.pvEditorLabel || '').trim();
            secretaryName.textContent = label !== '' ? label : <?= json_encode((string)$uiText['pvEditorEmpty'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        }

        const isCurrentEditor = currentUserId > 0
            && Number(documentPayload.pvEditorUserId || 0) === currentUserId;
        const isWaitingForReplacement = isCurrentEditor && documentPayload.pvEditorHandoverOpen === true;
        if (secretaryState instanceof Element) {
            secretaryState.hidden = !isCurrentEditor;
            secretaryState.classList.toggle('is-waiting', isWaitingForReplacement);
            secretaryState.textContent = isWaitingForReplacement
                ? <?= json_encode((string)$uiText['pvEditorHandoverWaiting'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
                : <?= json_encode((string)$uiText['pvEditorActive'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        }

        if (claimSecretaryButton instanceof HTMLButtonElement) {
            const canClaimEditor = documentPayload.canClaimPvEditor === true;
            const canReplaceEditor = documentPayload.canReplacePvEditor === true;
            const hasEditor = Number(documentPayload.pvEditorUserId || 0) > 0;
            const isReview = String(documentPayload.pvStage || '') === 'review';
            claimSecretaryButton.hidden = isReview || (!isCurrentEditor && !canClaimEditor && !canReplaceEditor);
            claimSecretaryButton.disabled = claimSecretaryButton.hidden || isWaitingForReplacement;
            claimSecretaryButton.classList.toggle('is-waiting', isWaitingForReplacement);
            claimSecretaryButton.dataset.omoPvSecretaryAction = isCurrentEditor
                ? 'pass_pv_editor'
                : (canClaimEditor ? 'claim_pv_editor' : 'replace_pv_editor');
            claimSecretaryButton.replaceChildren();
            if (isWaitingForReplacement) {
                const spinner = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                spinner.setAttribute('class', 'omo-pv-editor__secretary-claim-spinner');
                spinner.setAttribute('viewBox', '0 0 24 24');
                spinner.setAttribute('aria-hidden', 'true');
                const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                circle.setAttribute('cx', '12');
                circle.setAttribute('cy', '12');
                circle.setAttribute('r', '8');
                circle.setAttribute('fill', 'none');
                circle.setAttribute('stroke', 'currentColor');
                circle.setAttribute('stroke-width', '3');
                circle.setAttribute('stroke-linecap', 'round');
                circle.setAttribute('stroke-dasharray', '32 18');
                spinner.appendChild(circle);
                claimSecretaryButton.appendChild(spinner);
                claimSecretaryButton.appendChild(document.createTextNode(<?= json_encode((string)$uiText['pvEditorHandoverWaiting'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>));
            } else {
                claimSecretaryButton.textContent = isCurrentEditor
                    ? <?= json_encode((string)$uiText['passPvEditor'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
                    : (canClaimEditor
                        ? (hasEditor
                            ? <?= json_encode((string)$uiText['reclaimPvEditor'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
                            : <?= json_encode((string)$uiText['claimPvEditor'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>)
                        : <?= json_encode((string)$uiText['replacePvEditor'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>);
            }
        }

        if (invitationsButton instanceof HTMLButtonElement) {
            const canManageInvitations = documentPayload.canManagePvDocument === true
                && String(documentPayload.pvStage || '') === 'preparation';
            invitationsButton.hidden = !canManageInvitations;
            invitationsButton.disabled = !canManageInvitations;
        }

        if (addButton instanceof HTMLButtonElement) {
            addButton.disabled = documentPayload.isPvValidated === true;
        }
        if (autoSaveToggle instanceof HTMLInputElement) {
            const autoSaveAvailable = documentPayload.isPvValidated !== true
                && String(documentPayload.pvStage || '') !== 'review';
            autoSaveToggle.disabled = !autoSaveAvailable;
            if (!autoSaveAvailable) {
                autoSaveToggle.checked = false;
                getDirtyPointIds().forEach(clearPointAutoSave);
                clearDocumentMetadataAutoSave();
            }
        }

        if (templateToggleButton instanceof HTMLButtonElement) {
            const isTemplate = documentPayload.isPvTemplate === true;
            templateToggleButton.dataset.omoPvTemplateState = isTemplate ? '1' : '0';
            templateToggleButton.textContent = isTemplate
                ? String(templateToggleButton.dataset.omoPvTemplateUnmarkLabel || '')
                : String(templateToggleButton.dataset.omoPvTemplateMarkLabel || '');
            templateToggleButton.disabled = documentPayload.canManagePvTemplate !== true;
        }

        if (documentTitleDisplay instanceof Element) {
            documentTitleDisplay.textContent = String(documentPayload.title || '');
        }
        if (documentDescriptionDisplay instanceof Element) {
            const description = String(documentPayload.description || '').trim();
            documentDescriptionDisplay.textContent = description;
            documentDescriptionDisplay.hidden = description === '';
        }

        if (!documentMetadataIsDirty()) {
            if (documentTitleInput instanceof HTMLInputElement) {
                documentTitleInput.value = String(documentPayload.title || '');
            }
            if (documentDescriptionInput instanceof HTMLTextAreaElement) {
                documentDescriptionInput.value = String(documentPayload.description || '');
                resizeDocumentDescriptionInput();
            }
            if (documentVisibilitySelect instanceof Element && documentPayload.visibilityType) {
                setDocumentVisibilityValue(documentPayload.visibilityType);
            }
        }
        syncDocumentMetadataUi();
    }

    function mergeCurrentDocumentPayload(documentPayload) {
        if (!documentPayload || typeof documentPayload !== 'object') {
            return;
        }

        currentDocumentPayload = Object.assign({}, currentDocumentPayload, documentPayload);
        knownDocumentSyncVersion = String(currentDocumentPayload.syncVersion || knownDocumentSyncVersion || '');
        if (currentDocumentPayload.isPvValidated === true) {
            stopPvEditorBackgroundWork();
        }
        syncDocumentStageUi(currentDocumentPayload);
        syncPvEditorUi(currentDocumentPayload);
        syncDocumentMetadataUi();
    }

    function formatAttendanceCount(presentCount, totalCount) {
        return <?= json_encode((string)$uiText['attendanceCount'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
            .replace('{present}', String(Math.max(0, Number(presentCount || 0))))
            .replace('{total}', String(Math.max(0, Number(totalCount || 0))));
    }

    function buildAttendanceSignature(attendancePayload) {
        if (!attendancePayload || typeof attendancePayload !== 'object') {
            return '';
        }

        const entries = Array.isArray(attendancePayload.entries) ? attendancePayload.entries : [];
        return JSON.stringify({
            eventId: Number(attendancePayload.eventId || 0),
            presentCount: Number(attendancePayload.presentCount || 0),
            totalCount: Number(attendancePayload.totalCount || entries.length || 0),
            entries: entries.map(function (entry) {
                return [
                    String(entry && entry.identityKey ? entry.identityKey : ''),
                    String(entry && entry.displayLabel ? entry.displayLabel : ''),
                    String(entry && entry.secondaryLabel ? entry.secondaryLabel : ''),
                    entry && entry.isPresent ? 1 : 0
                ];
            })
        });
    }

    function renderAttendancePayload(attendancePayload) {
        const nextAttendancePayload = attendancePayload && typeof attendancePayload === 'object'
            ? attendancePayload
            : null;
        const nextAttendanceSignature = buildAttendanceSignature(nextAttendancePayload);
        if (nextAttendanceSignature === currentAttendanceSignature) {
            currentAttendancePayload = nextAttendancePayload;
            return;
        }

        const scrollAnchor = captureMainScrollAnchor();
        const focusedEditor = captureFocusedEditor();
        currentAttendancePayload = nextAttendancePayload;
        currentAttendanceSignature = nextAttendanceSignature;

        if (!(attendanceRoot instanceof Element) || !(attendanceList instanceof Element) || !(attendanceCount instanceof Element) || !(attendanceEmpty instanceof Element)) {
            return;
        }

        const entries = currentAttendancePayload && Array.isArray(currentAttendancePayload.entries)
            ? currentAttendancePayload.entries
            : [];
        const presentCount = currentAttendancePayload ? Number(currentAttendancePayload.presentCount || 0) : 0;
        const totalCount = currentAttendancePayload ? Number(currentAttendancePayload.totalCount || entries.length || 0) : 0;

        attendanceRoot.hidden = currentAttendancePayload === null;
        attendanceList.innerHTML = '';
        attendanceCount.textContent = formatAttendanceCount(presentCount, totalCount);

        if (entries.length === 0) {
            attendanceEmpty.hidden = false;
            restoreMainScrollAnchor(scrollAnchor);
            restoreFocusedEditor(focusedEditor);
            return;
        }

        attendanceEmpty.hidden = true;
        entries.forEach(function (entry) {
            if (!entry || !entry.identityKey) {
                return;
            }

            const label = document.createElement('label');
            label.className = 'omo-pv-editor__attendance-item';

            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.checked = !!entry.isPresent;
            checkbox.disabled = !(currentDocumentPayload
                && currentDocumentPayload.canManagePvDocument === true
                && String(currentDocumentPayload.pvStage || '') !== 'review');
            checkbox.setAttribute('data-omo-pv-attendance-toggle', String(entry.identityKey));

            const copy = document.createElement('span');
            copy.className = 'omo-pv-editor__attendance-copy';

            const name = document.createElement('span');
            name.className = 'omo-pv-editor__attendance-name';
            name.textContent = String(entry.displayLabel || '');
            copy.appendChild(name);

            const secondaryLabel = String(entry.secondaryLabel || '').trim();
            if (secondaryLabel !== '' && secondaryLabel !== name.textContent) {
                label.title = secondaryLabel;
            }

            label.appendChild(checkbox);
            label.appendChild(copy);
            attendanceList.appendChild(label);
        });

        restoreMainScrollAnchor(scrollAnchor);
        restoreFocusedEditor(focusedEditor);
    }

    function formatMinutesLabel(minutes) {
        const numericMinutes = Number(minutes || 0);
        if (!Number.isFinite(numericMinutes)) {
            return notStartedValue;
        }

        return Math.max(0, Math.round(numericMinutes)) + "'";
    }

    function getMeetingDurationMinutes() {
        if (!eventStartAtIso || !eventEndAtIso) {
            return 0;
        }

        const startAt = new Date(eventStartAtIso);
        const endAt = new Date(eventEndAtIso);
        const diff = Math.round((endAt.getTime() - startAt.getTime()) / 60000);
        return Number.isFinite(diff) && diff > 0 ? diff : 0;
    }

    function getTimerRemainingMeetingMinutes(meetingDurationMinutes, fallbackDurationMinutes) {
        const fallbackMinutes = Math.max(0, Number(fallbackDurationMinutes || 0));
        if (!eventStartAtIso || !eventEndAtIso) {
            return fallbackMinutes;
        }

        const startAt = new Date(eventStartAtIso);
        const endAt = new Date(eventEndAtIso);
        const now = new Date();
        if (now < startAt) {
            return Math.max(0, Number(meetingDurationMinutes || 0));
        }

        if (now > endAt) {
            return 0;
        }

        return Math.max(0, Math.round((endAt.getTime() - now.getTime()) / 60000));
    }

    function collectTimingPointState() {
        return Object.keys(currentPointPayloads).filter(function (pointId) {
            const payload = currentPointPayloads[pointId] || {};
            return payload.isGroup !== true;
        }).map(function (pointId) {
            const payload = currentPointPayloads[pointId] || {};
            const numericPointId = Number(pointId || 0);
            const card = root.querySelector('[data-omo-pv-point-card="' + numericPointId + '"]');
            const durationField = card ? card.querySelector('[data-omo-pv-point-duration="' + numericPointId + '"]') : null;
            const handledInput = root.querySelector('[data-omo-pv-point-handled="' + numericPointId + '"]');

            return {
                id: numericPointId,
                durationMinutes: durationField
                    ? Math.max(0, parseInt(String(durationField.value || '0'), 10) || 0)
                    : Math.max(0, parseInt(String(payload.desiredDurationMinutes || 0), 10) || 0),
                isHandled: handledInput ? !!handledInput.checked : !!payload.isHandled,
            };
        });
    }

    function polarPoint(radius, angleDeg) {
        return {
            x: 50 + radius * Math.cos(angleDeg * Math.PI / 180),
            y: 50 + radius * Math.sin(angleDeg * Math.PI / 180),
        };
    }

    function buildTimerSectorPath(ratio, radius) {
        const normalizedRatio = Math.max(0, Math.min(1, Number(ratio || 0)));
        if (normalizedRatio <= 0) {
            return '';
        }

        const centerX = 50;
        const centerY = 50;
        const sectorRadius = Math.max(1, Number(radius || 34));
        const angle = (normalizedRatio * 360) - 90;
        const endPoint = polarPoint(sectorRadius, angle);
        const largeArcFlag = normalizedRatio > 0.5 ? 1 : 0;

        if (normalizedRatio >= 0.999) {
            const topY = centerY - sectorRadius;
            const bottomY = centerY + sectorRadius;
            return [
                'M', centerX, centerY,
                'L', centerX, topY,
                'A', sectorRadius, sectorRadius, 0, 1, 1, centerX, bottomY,
                'A', sectorRadius, sectorRadius, 0, 1, 1, centerX, topY,
                'Z',
            ].join(' ');
        }

        return [
            'M', centerX, centerY,
            'L', centerX, centerY - sectorRadius,
            'A', sectorRadius, sectorRadius, 0, largeArcFlag, 1, endPoint.x, endPoint.y,
            'Z',
        ].join(' ');
    }

    function buildDonutSegmentPath(startRatio, endRatio, outerRadius, innerRadius) {
        const start = Math.max(0, Math.min(0.999999, Number(startRatio || 0)));
        const end = Math.max(start, Math.min(0.999999, Number(endRatio || 0)));
        if (end <= start) {
            return '';
        }

        const startAngle = (start * 360) - 90;
        const endAngle = (end * 360) - 90;
        const largeArcFlag = (end - start) > 0.5 ? 1 : 0;
        const outerStart = polarPoint(outerRadius, startAngle);
        const outerEnd = polarPoint(outerRadius, endAngle);
        const innerEnd = polarPoint(innerRadius, endAngle);
        const innerStart = polarPoint(innerRadius, startAngle);

        return [
            'M', outerStart.x, outerStart.y,
            'A', outerRadius, outerRadius, 0, largeArcFlag, 1, outerEnd.x, outerEnd.y,
            'L', innerEnd.x, innerEnd.y,
            'A', innerRadius, innerRadius, 0, largeArcFlag, 0, innerStart.x, innerStart.y,
            'Z',
        ].join(' ');
    }

    function renderTimingSummary() {
        const meetingDurationMinutes = getMeetingDurationMinutes();
        const pointState = collectTimingPointState();
        let totalPointMinutes = 0;
        let remainingPointMinutes = 0;

        pointState.forEach(function (point) {
            totalPointMinutes += point.durationMinutes;
            if (!point.isHandled) {
                remainingPointMinutes += point.durationMinutes;
            }
        });

        const handledPointMinutes = Math.max(0, totalPointMinutes - remainingPointMinutes);
        const plannedDurationMinutes = meetingDurationMinutes > 0 ? meetingDurationMinutes : totalPointMinutes;
        const timerRemainingMinutes = getTimerRemainingMeetingMinutes(meetingDurationMinutes, plannedDurationMinutes);
        const timerRatio = plannedDurationMinutes > 0
            ? Math.max(0, Math.min(1, timerRemainingMinutes / plannedDurationMinutes))
            : 0;
        const marginMinutes = meetingDurationMinutes > totalPointMinutes
            ? meetingDurationMinutes - totalPointMinutes
            : 0;
        const overrunMinutes = meetingDurationMinutes > 0 && totalPointMinutes > meetingDurationMinutes
            ? totalPointMinutes - meetingDurationMinutes
            : 0;
        const bufferMinutes = marginMinutes > 0 ? marginMinutes : overrunMinutes;
        const outerTotalMinutes = handledPointMinutes + remainingPointMinutes + bufferMinutes;
        const chartNode = root.querySelector('[data-omo-pv-timing-chart="1"]');
        const sectorNode = root.querySelector('[data-omo-pv-timing-sector]');
        const outerHandledNode = root.querySelector('[data-omo-pv-timing-outer-handled]');
        const outerRemainingNode = root.querySelector('[data-omo-pv-timing-outer-remaining]');
        const outerBufferNode = root.querySelector('[data-omo-pv-timing-outer-buffer]');
        const outerBaseNode = root.querySelector('[data-omo-pv-timing-outer-base]');
        const centerNode = root.querySelector('[data-omo-pv-timing-center]');
        const handledLegendNode = root.querySelector('[data-omo-pv-timing-legend-handled]');
        const remainingLegendNode = root.querySelector('[data-omo-pv-timing-legend-remaining]');
        const bufferLegendNode = root.querySelector('[data-omo-pv-timing-legend-buffer]');
        const bufferLegendLabelNode = root.querySelector('[data-omo-pv-timing-buffer-label]');
        const bufferLegendSwatchNode = root.querySelector('[data-omo-pv-timing-buffer-swatch]');

        if (centerNode) {
            centerNode.textContent = plannedDurationMinutes > 0 ? formatMinutesLabel(timerRemainingMinutes) : notStartedValue;
        }
        if (handledLegendNode) {
            handledLegendNode.textContent = formatMinutesLabel(handledPointMinutes);
        }
        if (remainingLegendNode) {
            remainingLegendNode.textContent = formatMinutesLabel(remainingPointMinutes);
        }
        if (bufferLegendNode) {
            bufferLegendNode.textContent = formatMinutesLabel(marginMinutes > 0 ? marginMinutes : overrunMinutes);
        }
        if (bufferLegendLabelNode) {
            bufferLegendLabelNode.textContent = overrunMinutes > 0 ? overrunLegendLabel : marginLegendLabel;
        }
        if (bufferLegendSwatchNode) {
            bufferLegendSwatchNode.style.background = overrunMinutes > 0 ? '#ef4444' : '#cbd5e1';
        }
        if (sectorNode) {
            sectorNode.setAttribute('d', buildTimerSectorPath(timerRatio, 34));
        }
        if (outerHandledNode || outerRemainingNode || outerBufferNode) {
            if (outerBaseNode) {
                outerBaseNode.setAttribute('fill', meetingDurationMinutes > 0 ? '#e2e8f0' : 'transparent');
            }

            let cursorRatio = 0;
            const applyOuterSegment = function (node, minutes) {
                if (!node) {
                    return;
                }

                const segmentRatio = outerTotalMinutes > 0 ? Math.max(0, Number(minutes || 0)) / outerTotalMinutes : 0;
                node.setAttribute('d', buildDonutSegmentPath(cursorRatio, cursorRatio + segmentRatio, 50, 37));
                cursorRatio += segmentRatio;
            };

            applyOuterSegment(outerHandledNode, handledPointMinutes);
            applyOuterSegment(outerRemainingNode, remainingPointMinutes);
            applyOuterSegment(outerBufferNode, bufferMinutes);
            if (outerBufferNode) {
                outerBufferNode.setAttribute('fill', overrunMinutes > 0 ? '#ef4444' : '#cbd5e1');
            }
        }
        if (chartNode) {
            chartNode.setAttribute('title', plannedDurationMinutes > 0
                ? formatMinutesLabel(timerRemainingMinutes)
                : notStartedValue);
        }
    }

    function mountEditableCard(card) {
        if (!(card instanceof Element)) {
            return;
        }

        if (String(card.getAttribute('data-omo-pv-point-editable') || '') !== '1') {
            return;
        }

        const pointId = Number(card.getAttribute('data-omo-pv-point-card') || 0);
        if (!Number.isInteger(pointId) || pointId <= 0) {
            return;
        }

        const titleField = card.querySelector('[data-omo-pv-point-title="' + pointId + '"]');
        const typeField = card.querySelector('[data-omo-pv-point-type="' + pointId + '"]');
        const durationField = card.querySelector('[data-omo-pv-point-duration="' + pointId + '"]');
        const authorField = card.querySelector('[data-omo-pv-point-author="' + pointId + '"]');
        const concernedHolonField = card.querySelector('[data-omo-pv-point-concerned-holon="' + pointId + '"]');
        const confidentialField = card.querySelector('[data-omo-pv-point-confidential="' + pointId + '"]');
        const editorHost = card.querySelector('[data-omo-pv-point-editor-host="' + pointId + '"]');
        const sourceField = card.querySelector('[data-omo-pv-point-content-source="' + pointId + '"]');

        if (!editorHost || !sourceField || editorHost.__omoPvPointMounted === true) {
            return;
        }

        ensureHtmlFieldLibrary(function () {
            if (!window.omoSimpleHtmlField || typeof window.omoSimpleHtmlField.mount !== 'function') {
                return;
            }

            let field = null;
            suppressPointDirtyDuring(pointId, function () {
                const customButtons = [];
                if (canEmbedDocuments) {
                    customButtons.push({
                        name: 'omoPvDocumentEmbed',
                        group: 'omo-pv-document-embed',
                        label: 'Document',
                        title: documentEmbedUi.buttonTitle || 'Insérer un document',
                        className: 'note-btn-light omo-pv-editor__document-embed-button',
                        focusForInsertion: true,
                        onClick: function (context) {
                            openPvDocumentEmbedPicker(context && context.api ? context.api : field);
                        }
                    });
                }
                if (canEmbedDecisions) {
                    customButtons.push({
                        name: 'omoPvDecisionEmbed',
                        group: 'omo-pv-decision-embed',
                        label: 'Décision',
                        title: decisionEmbedUi.buttonTitle || 'Insérer une décision',
                        className: 'note-btn-light omo-pv-editor__decision-embed-button',
                        focusForInsertion: true,
                        onClick: function (context) {
                            openPvDecisionEmbedPicker(context && context.api ? context.api : field);
                        }
                    });
                }
                if (canEmbedProjects) {
                    customButtons.push({
                        name: 'omoPvProjectEmbed', group: 'omo-pv-project-embed', label: 'Projet', title: projectEmbedUi.buttonTitle || 'Insérer un projet', className: 'note-btn-light omo-pv-editor__project-embed-button', focusForInsertion: true,
                        onClick: function (context) { openPvProjectEmbedPicker(context && context.api ? context.api : field); }
                    });
                }
                if (canEmbedChecklists) {
                    customButtons.push({ name: 'omoPvChecklistEmbed', group: 'omo-pv-checklist-embed', label: 'Processus', title: checklistEmbedUi.buttonTitle || 'Insérer un processus', className: 'note-btn-light omo-pv-editor__checklist-embed-button', focusForInsertion: true, onClick: function (context) { openPvChecklistEmbedPicker(context && context.api ? context.api : field); } });
                }
                if (canEmbedEvents) {
                    customButtons.push({
                        name: 'omoPvEventEmbed',
                        group: 'omo-pv-event-embed',
                        label: 'Date',
                        title: eventEmbedUi.buttonTitle || 'Insérer une date',
                        className: 'note-btn-light omo-pv-editor__event-embed-button',
                        focusForInsertion: true,
                        onClick: function (context) {
                            openPvEventEmbedPicker(context && context.api ? context.api : field);
                        }
                    });
                }
                if (canEmbedIndicators) {
                    customButtons.push({
                        name: 'omoPvIndicatorEmbed',
                        group: 'omo-pv-indicator-embed',
                        label: 'Indicateur',
                        title: indicatorEmbedUi.buttonTitle || 'Insérer un indicateur',
                        className: 'note-btn-light omo-pv-editor__indicator-embed-button',
                        focusForInsertion: true,
                        onClick: function (context) {
                            openPvIndicatorEmbedPicker(context && context.api ? context.api : field);
                        }
                    });
                }
                field = window.omoSimpleHtmlField.mount(editorHost, {
                    value: String(sourceField.value || ''),
                    placeholder: <?= json_encode(omoDocumentsPvEditorT('documents.pv_editor.field.content'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                    customButtons: customButtons,
                    indicatorValueUi: indicatorValueUi,
                    resourceGapHelperLabel: <?= json_encode((string)$uiText['embedAddLine'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                    onChange: function () {
                        if (isPointDirtySuppressed(pointId)) {
                            return;
                        }

                        ensurePointLock(pointId);
                        markPointDirty(pointId, true);
                    },
                    onIndicatorValueAdd: function (context) {
                        addPvIndicatorValue(field, pointId, context);
                    },
                    onReady: function (api) {
                        refreshPvIndicatorEmbedSnapshots(api);
                        refreshPvProjectEmbedReviews(api);
                        refreshPvChecklistEmbedReviews(api);
                        const toolbar = editorHost.querySelector('.note-toolbar');
                        const pointActions = card.querySelector('.omo-pv-editor__point-actions');
                        if (toolbar instanceof Element && pointActions instanceof Element) {
                            pointActions.classList.add('omo-pv-editor__point-actions--toolbar');
                            toolbar.appendChild(pointActions);
                        }
                    },
                    onDoubleClick: function (context) {
                        const targetNode = context && context.target && context.target.closest
                            ? context.target
                            : null;
                        const documentEmbed = targetNode ? targetNode.closest('.omo-document-embed[data-omo-embed-type="document"]') : null;
                        const decisionEmbed = targetNode ? targetNode.closest('.omo-decision-embed[data-omo-embed-type="decision"]') : null;
                        const projectEmbed = targetNode ? targetNode.closest('.omo-project-embed[data-omo-embed-type="project"]') : null;
                        const checklistEmbed = targetNode ? targetNode.closest('.omo-checklist-embed[data-omo-embed-type="checklist"]') : null;
                        const eventEmbed = targetNode ? targetNode.closest('.omo-event-embed[data-omo-embed-type="event"]') : null;
                        const indicatorEmbed = targetNode ? targetNode.closest('.omo-indicator-embed[data-omo-embed-type="indicator"]') : null;
                        if (!documentEmbed && !decisionEmbed && !projectEmbed && !checklistEmbed && !eventEmbed && !indicatorEmbed) {
                            return;
                        }

                        if (context && context.event && typeof context.event.preventDefault === 'function') {
                            context.event.preventDefault();
                        }
                        if (documentEmbed) {
                            openPvDocumentEmbedPicker(field, documentEmbed);
                        } else if (decisionEmbed) {
                            openPvDecisionEmbedPicker(field, decisionEmbed);
                        } else if (projectEmbed) {
                            openPvProjectEmbedPicker(field, projectEmbed);
                        } else if (checklistEmbed) {
                            openPvChecklistEmbedPicker(field, checklistEmbed);
                        } else if (eventEmbed) {
                            openPvEventEmbedPicker(field, eventEmbed);
                        } else if (indicatorEmbed) {
                            openPvIndicatorEmbedPicker(field, indicatorEmbed);
                        }
                    }
                });
            }, 220);

            editorHost.__omoPvPointMounted = true;
            editorHost.__omoPvPointField = field;

            if (titleField) {
                titleField.addEventListener('focus', function () {
                    setFocusedPoint(pointId);
                    ensurePointLock(pointId);
                });
                titleField.addEventListener('input', function () {
                    ensurePointLock(pointId);
                    markPointDirty(pointId, true);
                });
            }

            if (typeField) {
                typeField.addEventListener('focus', function () {
                    setFocusedPoint(pointId);
                    ensurePointLock(pointId);
                });
                typeField.addEventListener('change', function () {
                    ensurePointLock(pointId);
                    markPointDirty(pointId, true);
                });
            }

            if (durationField) {
                durationField.addEventListener('focus', function () {
                    setFocusedPoint(pointId);
                    ensurePointLock(pointId);
                });
                durationField.addEventListener('input', function () {
                    ensurePointLock(pointId);
                    markPointDirty(pointId, true);
                    renderTimingSummary();
                });
            }

            if (authorField) {
                authorField.addEventListener('focus', function () {
                    setFocusedPoint(pointId);
                    ensurePointLock(pointId);
                });
                authorField.addEventListener('change', function () {
                    if (concernedHolonField instanceof HTMLSelectElement) {
                        let optionsByUser = {};
                        try {
                            optionsByUser = JSON.parse(String(authorField.getAttribute('data-omo-pv-point-author-holons') || '{}')) || {};
                        } catch (error) {
                            optionsByUser = {};
                        }

                        const nextOptions = Array.isArray(optionsByUser[String(authorField.value || '')])
                            ? optionsByUser[String(authorField.value || '')]
                            : [];
                        concernedHolonField.innerHTML = '';
                        const emptyOption = document.createElement('option');
                        emptyOption.value = '0';
                        emptyOption.textContent = <?= json_encode((string)$uiText['concernedHolonEmpty'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
                        concernedHolonField.appendChild(emptyOption);
                        nextOptions.forEach(function (option) {
                            if (!option || !option.id || !option.label) {
                                return;
                            }
                            const optionNode = document.createElement('option');
                            optionNode.value = String(option.id);
                            optionNode.textContent = String(option.label);
                            concernedHolonField.appendChild(optionNode);
                        });
                        concernedHolonField.value = '0';
                    }
                    ensurePointLock(pointId);
                    markPointDirty(pointId, true);
                });
            }

            if (concernedHolonField) {
                concernedHolonField.addEventListener('focus', function () {
                    setFocusedPoint(pointId);
                    ensurePointLock(pointId);
                });
                concernedHolonField.addEventListener('change', function () {
                    ensurePointLock(pointId);
                    markPointDirty(pointId, true);
                });
            }

            if (confidentialField) {
                confidentialField.addEventListener('change', function () {
                    ensurePointLock(pointId);
                    markPointDirty(pointId, true);
                });
            }

            editorHost.addEventListener('focusin', function () {
                setFocusedPoint(pointId);
                ensurePointLock(pointId);
            });
        });
    }

    function mountEditableCards(scope) {
        const container = scope instanceof Element ? scope : root;
        container.querySelectorAll('[data-omo-pv-point-card]').forEach(mountEditableCard);
    }

    function getDirtyPointIds() {
        return Array.from(root.querySelectorAll('[data-omo-pv-point-card][data-omo-pv-point-dirty="1"]')).map(function (card) {
            return Number(card.getAttribute('data-omo-pv-point-card') || 0);
        }).filter(function (pointId) {
            return Number.isInteger(pointId) && pointId > 0;
        });
    }

    function hasUnsavedPointChanges() {
        if (!root.isConnected) {
            return false;
        }

        return documentMetadataSaving
            || root.querySelector('[data-omo-pv-point-save]:not(:disabled), [data-omo-pv-document-meta-save]:not(:disabled)') !== null;
    }

    function releaseActiveLocksWithBeacon() {
        if (activeLockPointIds.size === 0) {
            return;
        }

        const pointIds = Array.from(activeLockPointIds).filter(function (pointId) {
            return Number.isInteger(pointId) && pointId > 0;
        });
        const formData = new FormData();
        formData.append('action', 'release_locks');
        formData.append('document_id', String(documentId));
        formData.append('oid', String(organizationId));
        formData.append('editor_token', editorToken);
        formData.append('point_ids', pointIds.join(','));

        const beaconSent = typeof navigator.sendBeacon === 'function'
            && navigator.sendBeacon(actionUrl, formData);
        if (!beaconSent && typeof window.fetch === 'function') {
            window.fetch(actionUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                keepalive: true
            }).catch(function () {
                // A stale lock will still expire server-side after the timeout.
            });
        }
        activeLockPointIds.clear();
        pendingLockPointIds.clear();
        pendingUnlockPointIds.clear();
    }

    function confirmCloseWithUnsavedChanges() {
        if (!hasUnsavedPointChanges()) {
            releaseActiveLocksWithBeacon();
            return true;
        }

        const canClose = window.confirm(unsavedCloseMessage);
        if (canClose) {
            releaseActiveLocksWithBeacon();
        }

        return canClose;
    }

    const previousPvEditorCloseGuard = typeof window.omoPvEditorConfirmCanClose === 'function'
        ? window.omoPvEditorConfirmCanClose
        : null;
    window.omoPvEditorConfirmCanClose = function () {
        if (allowNextExternalClose) {
            allowNextExternalClose = false;
            return true;
        }

        if (root.isConnected) {
            return confirmCloseWithUnsavedChanges();
        }

        return previousPvEditorCloseGuard ? previousPvEditorCloseGuard() : true;
    };

    document.addEventListener('click', function (event) {
        const closeTarget = event.target && event.target.closest
            ? event.target.closest('[data-omo-external-panel-drawer-close="1"], [data-omo-external-panel-drawer-peek-dismiss="1"]')
            : null;
        if (!closeTarget || !root.isConnected) {
            return;
        }

        const drawer = root.closest('[data-omo-external-panel-drawer="1"]');
        if (!drawer || !drawer.contains(closeTarget)) {
            return;
        }

        if (!confirmCloseWithUnsavedChanges()) {
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();
            return;
        }

        allowNextExternalClose = true;
        window.setTimeout(function () {
            allowNextExternalClose = false;
        }, 0);
    }, true);

    function captureDraftState() {
        const drafts = {};

        root.querySelectorAll('[data-omo-pv-point-card][data-omo-pv-point-editable="1"]').forEach(function (card) {
            const pointId = Number(card.getAttribute('data-omo-pv-point-card') || 0);
            if (!Number.isInteger(pointId) || pointId <= 0) {
                return;
            }

            const titleField = card.querySelector('[data-omo-pv-point-title="' + pointId + '"]');
            const typeField = card.querySelector('[data-omo-pv-point-type="' + pointId + '"]');
            const durationField = card.querySelector('[data-omo-pv-point-duration="' + pointId + '"]');
            const authorField = card.querySelector('[data-omo-pv-point-author="' + pointId + '"]');
            const concernedHolonField = card.querySelector('[data-omo-pv-point-concerned-holon="' + pointId + '"]');
            const confidentialField = card.querySelector('[data-omo-pv-point-confidential="' + pointId + '"]');
            const editorHost = card.querySelector('[data-omo-pv-point-editor-host="' + pointId + '"]');
            const sourceField = card.querySelector('[data-omo-pv-point-content-source="' + pointId + '"]');
            const statusNode = card.querySelector('[data-omo-pv-point-status="' + pointId + '"]');
            const saveButton = card.querySelector('[data-omo-pv-point-save="' + pointId + '"]');
            const htmlField = editorHost && editorHost.__omoPvPointField ? editorHost.__omoPvPointField : null;
            const hasActiveSaveButton = !!saveButton && !saveButton.disabled;

            drafts[pointId] = {
                title: titleField ? String(titleField.value || '') : '',
                pointType: typeField ? String(typeField.value || '') : 'information',
                desiredDurationMinutes: durationField ? String(durationField.value || '') : '',
                authorValue: authorField ? String(authorField.value || '') : '',
                concernedHolonId: concernedHolonField ? String(concernedHolonField.value || '0') : '0',
                isConfidential: confidentialField ? !!confidentialField.checked : false,
                content: htmlField && typeof htmlField.getValue === 'function'
                    ? String(htmlField.getValue() || '')
                    : (sourceField ? String(sourceField.value || '') : ''),
                statusText: statusNode ? String(statusNode.textContent || '') : '',
                isDirty: card.getAttribute('data-omo-pv-point-dirty') === '1' || hasActiveSaveButton,
            };
        });

        return drafts;
    }

    function updatePointTypeSwitch(card, pointId, pointType) {
        if (!(card instanceof Element)) {
            return;
        }

        const resolvedType = String(pointType || '').trim();
        card.querySelectorAll('[data-omo-pv-point-type-option="' + pointId + '"]').forEach(function (button) {
            const isActive = String(button.getAttribute('data-omo-pv-point-type-value') || '') === resolvedType;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-checked', isActive ? 'true' : 'false');
            button.tabIndex = isActive ? 0 : -1;
        });

        const activeOption = card.querySelector('[data-omo-pv-point-type-option="' + pointId + '"][data-omo-pv-point-type-value="' + resolvedType + '"]');
        const navIcon = nav ? nav.querySelector('[data-omo-pv-point-nav-type-icon="' + pointId + '"]') : null;
        const optionIcon = activeOption ? activeOption.querySelector('img') : null;
        if (navIcon instanceof HTMLImageElement && optionIcon instanceof HTMLImageElement) {
            navIcon.src = optionIcon.currentSrc || optionIcon.src;
            navIcon.alt = String(activeOption.getAttribute('aria-label') || '');
        }
    }

    function selectPointTypeOption(typeButton, focusSelectedButton) {
        if (!(typeButton instanceof HTMLButtonElement)) {
            return;
        }

        const pointId = Number(typeButton.getAttribute('data-omo-pv-point-type-option') || 0);
        const nextType = String(typeButton.getAttribute('data-omo-pv-point-type-value') || '').trim();
        const card = pointId > 0 ? root.querySelector('[data-omo-pv-point-card="' + pointId + '"]') : null;
        const typeField = card ? card.querySelector('[data-omo-pv-point-type="' + pointId + '"]') : null;
        if (!(card instanceof Element) || !(typeField instanceof HTMLInputElement) || nextType === '') {
            return;
        }

        typeField.value = nextType;
        updatePointTypeSwitch(card, pointId, nextType);
        typeField.dispatchEvent(new Event('change', { bubbles: true }));
        if (focusSelectedButton) {
            typeButton.focus();
        }
    }

    function restoreDraftState(drafts) {
        if (!drafts || typeof drafts !== 'object') {
            return;
        }

        function applyEditorDraft(pointId, content, attempt) {
            const card = root.querySelector('[data-omo-pv-point-card="' + pointId + '"]');
            if (!(card instanceof Element)) {
                return;
            }

            const editorHost = card.querySelector('[data-omo-pv-point-editor-host="' + pointId + '"]');
            const sourceField = card.querySelector('[data-omo-pv-point-content-source="' + pointId + '"]');
            const htmlField = editorHost && editorHost.__omoPvPointField ? editorHost.__omoPvPointField : null;
            if (sourceField) {
                sourceField.value = String(content || '');
            }

            if (htmlField && typeof htmlField.setValue === 'function') {
                suppressPointDirtyDuring(pointId, function () {
                    htmlField.setValue(String(content || ''));
                }, 180);
                return;
            }

            if ((attempt || 0) >= 12) {
                return;
            }

            window.setTimeout(function () {
                applyEditorDraft(pointId, content, (attempt || 0) + 1);
            }, 60);
        }

        Object.keys(drafts).forEach(function (pointKey) {
            const pointId = Number(pointKey || 0);
            if (!Number.isInteger(pointId) || pointId <= 0) {
                return;
            }

            const draft = drafts[pointKey];
            const card = root.querySelector('[data-omo-pv-point-card="' + pointId + '"]');
            if (!(card instanceof Element) || !draft) {
                return;
            }

            const titleField = card.querySelector('[data-omo-pv-point-title="' + pointId + '"]');
            const typeField = card.querySelector('[data-omo-pv-point-type="' + pointId + '"]');
            const durationField = card.querySelector('[data-omo-pv-point-duration="' + pointId + '"]');
            const authorField = card.querySelector('[data-omo-pv-point-author="' + pointId + '"]');
            const concernedHolonField = card.querySelector('[data-omo-pv-point-concerned-holon="' + pointId + '"]');
            const confidentialField = card.querySelector('[data-omo-pv-point-confidential="' + pointId + '"]');
            const editorHost = card.querySelector('[data-omo-pv-point-editor-host="' + pointId + '"]');
            const sourceField = card.querySelector('[data-omo-pv-point-content-source="' + pointId + '"]');
            const statusNode = card.querySelector('[data-omo-pv-point-status="' + pointId + '"]');
            const htmlField = editorHost && editorHost.__omoPvPointField ? editorHost.__omoPvPointField : null;

            if (titleField) {
                titleField.value = String(draft.title || '');
            }

            if (typeField) {
                typeField.value = String(draft.pointType || 'information');
                updatePointTypeSwitch(card, pointId, typeField.value);
            }

            if (durationField) {
                durationField.value = String(draft.desiredDurationMinutes || '');
            }

            if (authorField) {
                authorField.value = String(draft.authorValue || '');
            }

            if (concernedHolonField) {
                concernedHolonField.value = String(draft.concernedHolonId || '0');
            }

            if (confidentialField) {
                confidentialField.checked = draft.isConfidential === true;
            }

            applyEditorDraft(pointId, draft.content || '', 0);

            if (statusNode) {
                statusNode.textContent = String(draft.statusText || '');
            }

            if (draft.isDirty === true) {
                card.setAttribute('data-omo-pv-point-dirty', '1');
                syncPointDirtyUi(pointId);
            } else {
                markPointDirty(pointId, false);
            }
        });
    }

    function replacePointHtml(pointPayload) {
        if (!pointPayload || !pointPayload.id) {
            return null;
        }

        const pointId = Number(pointPayload.id || 0);
        if (pointPayload.isGroup === true) {
            mergeKnownPointSignature(pointPayload);
            mergeCurrentPointPayload(pointPayload);
            renderNavTreeFromPayloads();
            return null;
        }
        const temp = document.createElement('div');
        temp.innerHTML = String(pointPayload.cardHtml || '').trim();
        const nextCard = temp.firstElementChild;
        if (!(nextCard instanceof Element)) {
            return null;
        }

        const currentCard = root.querySelector('[data-omo-pv-point-card="' + pointId + '"]');
        const currentPayload = currentPointPayloads[String(pointId)] || null;
        const cardContentIsEquivalent = currentCard instanceof Element
            && currentCard.className === nextCard.className
            && currentCard.innerHTML === nextCard.innerHTML;
        const navContentMayHaveChanged = !currentPayload
            || String(currentPayload.navHtml || '') !== String(pointPayload.navHtml || '')
            || Number(currentPayload.parentId || 0) !== Number(pointPayload.parentId || 0)
            || Number(currentPayload.position || 0) !== Number(pointPayload.position || 0);

        if (cardContentIsEquivalent) {
            mergeKnownPointSignature(pointPayload);
            mergeCurrentPointPayload(pointPayload);
            if (navContentMayHaveChanged) {
                replacePointNavHtml(pointPayload);
            }
            renderTimingSummary();
            return currentCard;
        }

        if (currentCard && currentCard.parentNode) {
            currentCard.parentNode.replaceChild(nextCard, currentCard);
        } else {
            pointsContainer.appendChild(nextCard);
        }

        replacePointNavHtml(pointPayload);

        syncEmptyNavState();
        mountEditableCard(nextCard);
        mergeKnownPointSignature(pointPayload);
        mergeCurrentPointPayload(pointPayload);
        renderTimingSummary();
        return nextCard;
    }

    function replacePointNavHtml(pointPayload) {
        if (!pointPayload || !pointPayload.id) {
            return null;
        }

        mergeKnownPointSignature(pointPayload);
        mergeCurrentPointPayload(pointPayload);
        renderNavTreeFromPayloads();
        renderTimingSummary();
        return nav.querySelector('[data-omo-pv-nav-node="' + Number(pointPayload.id || 0) + '"]');
    }

    function getOrderedPointIdsFromPayloads() {
        const childrenByParent = {};
        Object.keys(currentPointPayloads).forEach(function (key) {
            const item = currentPointPayloads[key];
            if (!item || !item.id) return;
            const parentId = Math.max(0, Number(item.parentId || 0));
            if (!childrenByParent[parentId]) childrenByParent[parentId] = [];
            childrenByParent[parentId].push(item);
        });
        Object.keys(childrenByParent).forEach(function (parentId) {
            childrenByParent[parentId].sort(function (left, right) {
                return Number(left.position || 0) - Number(right.position || 0) || Number(left.id || 0) - Number(right.id || 0);
            });
        });

        const orderedPointIds = [];
        const visited = new Set();
        const walk = function (parentId) {
            (childrenByParent[parentId] || []).forEach(function (item) {
                const id = Number(item.id || 0);
                if (id <= 0 || visited.has(id)) return;
                visited.add(id);
                if (item.isGroup === true) {
                    walk(id);
                } else {
                    orderedPointIds.push(id);
                }
            });
        };
        walk(0);
        Object.keys(currentPointPayloads).forEach(function (key) {
            const item = currentPointPayloads[key];
            const id = Number(item && item.id || 0);
            if (id > 0 && item.isGroup !== true && !visited.has(id)) orderedPointIds.push(id);
        });
        return orderedPointIds;
    }

    function renderNavTreeFromPayloads() {
        if (!(nav instanceof Element)) return;

        const collapsedIds = new Set(Array.from(nav.querySelectorAll('[data-omo-pv-group].is-collapsed')).map(function (node) {
            return Number(node.getAttribute('data-omo-pv-group') || 0);
        }));
        const focusedRow = nav.querySelector('[data-omo-pv-point-nav-row].is-focused');
        const focusedPointId = focusedRow ? Number(focusedRow.getAttribute('data-omo-pv-point-nav-row') || 0) : 0;
        const activeInput = document.activeElement instanceof HTMLInputElement && document.activeElement.matches('[data-omo-pv-group-title]')
            ? document.activeElement
            : null;
        const activeGroupId = activeInput ? Number(activeInput.getAttribute('data-omo-pv-group-title') || 0) : 0;
        const activeValue = activeInput ? activeInput.value : '';
        const selectionStart = activeInput ? activeInput.selectionStart : null;
        const selectionEnd = activeInput ? activeInput.selectionEnd : null;

        const items = Object.keys(currentPointPayloads).map(function (key) { return currentPointPayloads[key]; }).filter(function (item) {
            return item && Number(item.id || 0) > 0;
        });
        if (items.length === 0) {
            nav.innerHTML = '<div class="omo-empty-state omo-pv-editor__empty">' + escapeDocumentEmbedHtml(<?= json_encode(omoDocumentsPvEditorT('documents.pv_editor.nav.empty'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>) + '</div>';
            return;
        }

        const childrenByParent = {};
        const itemsById = {};
        items.forEach(function (item) { itemsById[Number(item.id || 0)] = item; });
        items.forEach(function (item) {
            const requestedParentId = Math.max(0, Number(item.parentId || 0));
            const parentItem = itemsById[requestedParentId];
            const parentId = requestedParentId > 0 && parentItem && parentItem.isGroup === true && requestedParentId !== Number(item.id || 0)
                ? requestedParentId
                : 0;
            if (!childrenByParent[parentId]) childrenByParent[parentId] = [];
            childrenByParent[parentId].push(item);
        });
        Object.keys(childrenByParent).forEach(function (parentId) {
            childrenByParent[parentId].sort(function (left, right) {
                return Number(left.position || 0) - Number(right.position || 0) || Number(left.id || 0) - Number(right.id || 0);
            });
        });

        const rootNode = document.createElement('div');
        rootNode.className = 'omo-pv-editor__nav-root generic-stack generic-stack--compact';
        rootNode.setAttribute('data-omo-pv-nav-children', '0');
        const visited = new Set();
        const appendChildren = function (parentId, container) {
            (childrenByParent[parentId] || []).forEach(function (item) {
                const id = Number(item.id || 0);
                if (visited.has(id)) return;
                visited.add(id);
                const temp = document.createElement('div');
                temp.innerHTML = String(item.navHtml || '').trim();
                const node = temp.firstElementChild;
                if (!(node instanceof Element)) return;
                container.appendChild(node);
                if (item.isGroup === true) {
                    if (collapsedIds.has(id)) {
                        node.classList.add('is-collapsed');
                        const toggle = node.querySelector(':scope > .omo-pv-editor__group-head [data-omo-pv-group-toggle]');
                        if (toggle) toggle.setAttribute('aria-expanded', 'false');
                    }
                    const childContainer = node.querySelector('[data-omo-pv-nav-children="' + id + '"]');
                    if (childContainer) appendChildren(id, childContainer);
                }
            });
        };
        appendChildren(0, rootNode);
        nav.replaceChildren(rootNode);
        updateGroupSummaryLabels();

        if (focusedPointId > 0) {
            const nextFocusedRow = nav.querySelector('[data-omo-pv-point-nav-row="' + focusedPointId + '"]');
            if (nextFocusedRow) {
                nextFocusedRow.classList.add('is-focused');
            }
        }

        if (activeGroupId > 0) {
            const nextInput = nav.querySelector('[data-omo-pv-group-title="' + activeGroupId + '"]');
            if (nextInput instanceof HTMLInputElement) {
                nextInput.value = activeValue;
                nextInput.focus({preventScroll: true});
                if (Number.isInteger(selectionStart) && Number.isInteger(selectionEnd)) nextInput.setSelectionRange(selectionStart, selectionEnd);
            }
        }
    }

    function updateGroupSummaryLabels() {
        if (!nav) {
            return;
        }

        const itemsById = {};
        const childrenByParent = {};
        Object.keys(currentPointPayloads).forEach(function (key) {
            const item = currentPointPayloads[key];
            const itemId = Number(item && item.id || 0);
            if (itemId <= 0) {
                return;
            }
            const parentId = Math.max(0, Number(item.parentId || 0));
            itemsById[itemId] = item;
            if (!childrenByParent[parentId]) {
                childrenByParent[parentId] = [];
            }
            childrenByParent[parentId].push(itemId);
        });

        const summaries = {};
        const buildSummary = function (groupId, trail) {
            if (summaries[groupId]) {
                return summaries[groupId];
            }
            trail = trail || {};
            if (trail[groupId]) {
                return { pointCount: 0, durationMinutes: 0 };
            }
            trail[groupId] = true;
            const summary = { pointCount: 0, durationMinutes: 0 };
            (childrenByParent[groupId] || []).forEach(function (childId) {
                const child = itemsById[childId];
                if (!child) {
                    return;
                }
                if (child.isGroup === true) {
                    const childSummary = buildSummary(childId, Object.assign({}, trail));
                    summary.pointCount += childSummary.pointCount;
                    summary.durationMinutes += childSummary.durationMinutes;
                } else {
                    summary.pointCount += 1;
                    summary.durationMinutes += Math.max(0, Number(child.desiredDurationMinutes || 0));
                }
            });
            summaries[groupId] = summary;
            return summary;
        };

        Object.keys(itemsById).forEach(function (itemId) {
            if (itemsById[itemId].isGroup === true) {
                const summary = buildSummary(Number(itemId));
                const summaryNode = nav.querySelector('[data-omo-pv-group="' + itemId + '"] .omo-pv-editor__group-summary');
                if (summaryNode) {
                    summaryNode.textContent = summary.pointCount + ' ' + groupPointsLabel + ' | ' + summary.durationMinutes + ' ' + groupMinutesLabel;
                }
            }
        });
    }

    function isPointLockTakenOverRemotely(card, pointPayload) {
        if (!(card instanceof Element) || !pointPayload || !pointPayload.lock) {
            return false;
        }

        const pointId = Number(card.getAttribute('data-omo-pv-point-card') || 0);
        return Number.isInteger(pointId)
            && pointId > 0
            && activeLockPointIds.has(pointId)
            && pointPayload.lock.isLockedByOther === true;
    }

    function isPointCardProtectedFromRemoteRefresh(card, pointPayload) {
        if (!(card instanceof Element)) {
            return false;
        }

        return card.getAttribute('data-omo-pv-point-dirty') === '1'
            || card.getAttribute('data-omo-pv-point-saving') === '1'
            || (card.contains(document.activeElement) && !isPointLockTakenOverRemotely(card, pointPayload));
    }

    function pointHasRemoteChange(pointPayload) {
        if (!pointPayload || !pointPayload.id) {
            return false;
        }

        const signatures = buildPointSignatureMap([pointPayload]);
        const pointId = String(pointPayload.id);
        return String(signatures[pointId] || '') !== String(knownPointSignatures[pointId] || '');
    }

    function captureMainScrollAnchor() {
        if (!(mainPanel instanceof Element)) {
            return null;
        }

        const mainRect = mainPanel.getBoundingClientRect();
        const activeCard = document.activeElement instanceof Element
            ? document.activeElement.closest('[data-omo-pv-point-card]')
            : null;
        let anchorCard = activeCard instanceof Element && pointsContainer.contains(activeCard)
            ? activeCard
            : null;

        if (!(anchorCard instanceof Element)) {
            const centerY = mainRect.top + (mainRect.height / 2);
            let bestDistance = Number.POSITIVE_INFINITY;
            pointsContainer.querySelectorAll('[data-omo-pv-point-card]').forEach(function (card) {
                const rect = card.getBoundingClientRect();
                if (rect.bottom < mainRect.top || rect.top > mainRect.bottom) {
                    return;
                }

                const distance = Math.abs((rect.top + (rect.height / 2)) - centerY);
                if (distance < bestDistance) {
                    anchorCard = card;
                    bestDistance = distance;
                }
            });
        }

        if (!(anchorCard instanceof Element)) {
            return { scrollTop: mainPanel.scrollTop };
        }

        return {
            pointId: Number(anchorCard.getAttribute('data-omo-pv-point-card') || 0),
            offsetTop: anchorCard.getBoundingClientRect().top - mainRect.top,
            scrollTop: mainPanel.scrollTop,
        };
    }

    function captureFocusedEditor() {
        const activeElement = document.activeElement;
        const isDocumentMetadataEditor = activeElement === documentTitleInput || activeElement === documentDescriptionInput;
        if (
            !(activeElement instanceof Element)
            || (!isDocumentMetadataEditor && !pointsContainer.contains(activeElement))
        ) {
            return null;
        }

        const pointCard = isDocumentMetadataEditor
            ? null
            : activeElement.closest('[data-omo-pv-point-card]');
        if (!isDocumentMetadataEditor && !(pointCard instanceof Element)) {
            return null;
        }

        const state = { element: activeElement };
        if (activeElement instanceof HTMLInputElement || activeElement instanceof HTMLTextAreaElement) {
            state.selectionStart = activeElement.selectionStart;
            state.selectionEnd = activeElement.selectionEnd;
            return state;
        }

        const selection = window.getSelection();
        if (selection && selection.rangeCount > 0 && activeElement.contains(selection.anchorNode)) {
            state.range = selection.getRangeAt(0).cloneRange();
        }
        return state;
    }

    function restoreFocusedEditor(state) {
        if (!state || !(state.element instanceof Element) || !state.element.isConnected) {
            return;
        }

        window.requestAnimationFrame(function () {
            try {
                state.element.focus({ preventScroll: true });
            } catch (error) {
                state.element.focus();
            }

            if (state.element instanceof HTMLInputElement || state.element instanceof HTMLTextAreaElement) {
                if (Number.isInteger(state.selectionStart) && Number.isInteger(state.selectionEnd)) {
                    state.element.setSelectionRange(state.selectionStart, state.selectionEnd);
                }
                return;
            }

            if (state.range) {
                const selection = window.getSelection();
                if (selection) {
                    selection.removeAllRanges();
                    selection.addRange(state.range);
                }
            }
        });
    }

    function restoreMainScrollAnchor(anchor) {
        if (!(mainPanel instanceof Element) || !anchor) {
            return;
        }

        window.requestAnimationFrame(function () {
            const pointId = Number(anchor.pointId || 0);
            const anchorCard = pointId > 0
                ? pointsContainer.querySelector('[data-omo-pv-point-card="' + pointId + '"]')
                : null;
            if (anchorCard instanceof Element && Number.isFinite(Number(anchor.offsetTop))) {
                const nextOffsetTop = anchorCard.getBoundingClientRect().top - mainPanel.getBoundingClientRect().top;
                mainPanel.scrollTop += nextOffsetTop - Number(anchor.offsetTop);
                return;
            }

            mainPanel.scrollTop = Number(anchor.scrollTop || 0);
        });
    }

    function syncPointHandledBadge(pointId, pointPayload) {
        const currentCard = root.querySelector('[data-omo-pv-point-card="' + pointId + '"]');
        if (!(currentCard instanceof Element)) {
            return;
        }

        const topline = currentCard.querySelector('.omo-document-pv__point-topline');
        if (!(topline instanceof Element)) {
            return;
        }

        const temp = document.createElement('div');
        temp.innerHTML = String(pointPayload.cardHtml || '').trim();
        const nextCard = temp.firstElementChild;
        if (!(nextCard instanceof Element)) {
            return;
        }

        const currentBadge = topline.querySelector('.omo-pv-editor__point-ownership');
        if (currentBadge) {
            currentBadge.remove();
        }

        const nextBadge = nextCard.querySelector('.omo-pv-editor__point-ownership');
        if (nextBadge) {
            topline.appendChild(nextBadge.cloneNode(true));
        }
    }

    function applyPointOrderToCards(pointIds) {
        if (!Array.isArray(pointIds) || !pointsContainer) {
            return;
        }

        const currentPointIds = Array.from(pointsContainer.querySelectorAll('[data-omo-pv-point-card]')).map(function (card) {
            return Number(card.getAttribute('data-omo-pv-point-card') || 0);
        });
        if (currentPointIds.join('|') === pointIds.join('|')) {
            return;
        }

        pointIds.forEach(function (pointId) {
            const card = pointsContainer.querySelector('[data-omo-pv-point-card="' + pointId + '"]');
            if (card) {
                pointsContainer.appendChild(card);
            }
        });
    }

    function applyPointOrderToNav(pointIds) {
        renderNavTreeFromPayloads();
    }

    function collectNavPointIds() {
        if (!nav) {
            return [];
        }

        return Array.from(nav.querySelectorAll('[data-omo-pv-point-nav-row]')).map(function (row) {
            return Number(row.getAttribute('data-omo-pv-point-nav-row') || 0);
        }).filter(function (pointId) {
            return Number.isInteger(pointId) && pointId > 0;
        });
    }

    function movePointByDirection(pointId, direction) {
        if (!nav) {
            return false;
        }

        const row = pointId > 0 ? nav.querySelector('[data-omo-pv-nav-node="' + pointId + '"]') : null;
        if (!(row instanceof Element)) {
            return false;
        }

        const sibling = direction === 'up'
            ? row.previousElementSibling
            : row.nextElementSibling;
        if (sibling instanceof Element && sibling.matches('[data-omo-pv-nav-node]')) {
            if (direction === 'up') {
                sibling.insertAdjacentElement('beforebegin', row);
            } else {
                sibling.insertAdjacentElement('afterend', row);
            }
        } else {
            const parentGroup = row.parentElement instanceof Element
                ? row.parentElement.closest('[data-omo-pv-group]')
                : null;
            if (!(parentGroup instanceof Element)) {
                return false;
            }

            const adjacentGroup = direction === 'up'
                ? parentGroup.previousElementSibling
                : parentGroup.nextElementSibling;
            if (adjacentGroup instanceof Element && adjacentGroup.matches('[data-omo-pv-group]')) {
                const targetContainer = adjacentGroup.querySelector(':scope > [data-omo-pv-nav-children]');
                if (!(targetContainer instanceof Element)) {
                    return false;
                }
                if (direction === 'up') {
                    targetContainer.appendChild(row);
                } else {
                    targetContainer.insertAdjacentElement('afterbegin', row);
                }
                adjacentGroup.classList.remove('is-collapsed');
                const toggle = adjacentGroup.querySelector(':scope > .omo-pv-editor__group-head [data-omo-pv-group-toggle]');
                if (toggle) toggle.setAttribute('aria-expanded', 'true');
            } else if (direction === 'up') {
                parentGroup.insertAdjacentElement('beforebegin', row);
            } else {
                parentGroup.insertAdjacentElement('afterend', row);
            }
        }

        const layout = collectNavLayout();
        applyPointOrderToCards(layout.filter(function (item) {
            const payload = currentPointPayloads[String(item.id)] || {};
            return payload.isGroup !== true;
        }).map(function (item) { return item.id; }));
        persistPointOrder(layout);
        return true;
    }

    function collectNavLayout() {
        const layout = [];
        const walk = function (container, parentId) {
            Array.from(container.children || []).forEach(function (node) {
                if (!(node instanceof Element) || !node.matches('[data-omo-pv-nav-node]')) return;
                const id = Number(node.getAttribute('data-omo-pv-nav-node') || 0);
                if (!Number.isInteger(id) || id <= 0) return;
                layout.push({id: id, parentId: parentId});
                const childContainer = node.querySelector(':scope > [data-omo-pv-nav-children]');
                if (childContainer instanceof Element) walk(childContainer, id);
            });
        };
        const rootContainer = nav.querySelector(':scope > [data-omo-pv-nav-children="0"]');
        if (rootContainer instanceof Element) walk(rootContainer, 0);
        return layout;
    }

    function postPointAction(action, pointId, extraFields) {
        const formData = new FormData();
        formData.append('action', String(action || ''));
        formData.append('document_id', String(documentId));
        formData.append('oid', String(organizationId));
        formData.append('editor_token', editorToken);
        if (Number(pointId || 0) > 0) {
            formData.append('point_id', String(pointId));
        }

        if (extraFields && typeof extraFields === 'object') {
            Object.keys(extraFields).forEach(function (key) {
                if (extraFields[key] === undefined || extraFields[key] === null) {
                    return;
                }

                formData.append(String(key), String(extraFields[key]));
            });
        }

        return fetch(actionUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        }).then(function (response) {
            return response.json().then(function (payload) {
                if (!response.ok || !payload || payload.status !== true) {
                    throw payload || new Error('request_failed');
                }

                return payload;
            });
        });
    }

    function renderPointCollection(pointPayloads, forceRefresh) {
        if (!Array.isArray(pointPayloads)) {
            return;
        }

        forceRefresh = forceRefresh === true;

        const scrollAnchor = captureMainScrollAnchor();
        const focusedEditor = captureFocusedEditor();
        const nextPointIds = [];
        pointPayloads.forEach(function (pointPayload) {
            const pointId = Number(pointPayload && pointPayload.id ? pointPayload.id : 0);
            if (!Number.isInteger(pointId) || pointId <= 0) {
                return;
            }

            nextPointIds.push(pointId);
            const currentCard = pointsContainer.querySelector('[data-omo-pv-point-card="' + pointId + '"]');
            if (!currentCard) {
                replacePointHtml(pointPayload);
                return;
            }

            if (!forceRefresh && !pointHasRemoteChange(pointPayload)) {
                return;
            }

            if (isPointCardProtectedFromRemoteRefresh(currentCard, pointPayload)) {
                // Keep the local draft DOM, but still accept its server-side position.
                // Otherwise a reordered point in edit mode is rebuilt from its old
                // position until the point itself is saved.
                mergeKnownPointSignature(pointPayload);
                return;
            }

            if (isPointLockTakenOverRemotely(currentCard, pointPayload)) {
                activeLockPointIds.delete(pointId);
                pendingLockPointIds.delete(pointId);
                pendingUnlockPointIds.delete(pointId);
            }

            replacePointHtml(pointPayload);
        });

        const nextPointIdSet = new Set(nextPointIds.map(String));
        pointsContainer.querySelectorAll('[data-omo-pv-point-card]').forEach(function (card) {
            const pointId = String(card.getAttribute('data-omo-pv-point-card') || '');
            if (!nextPointIdSet.has(pointId) && !isPointCardProtectedFromRemoteRefresh(card)) {
                const navRow = nav.querySelector('[data-omo-pv-point-nav-row="' + pointId + '"]');
                if (navRow) {
                    navRow.remove();
                }
                card.remove();
                delete knownPointSignatures[pointId];
                delete currentPointPayloads[pointId];
            }
        });

        Object.keys(currentPointPayloads).forEach(function (pointId) {
            if (!nextPointIdSet.has(String(pointId))) {
                delete knownPointSignatures[String(pointId)];
                delete currentPointPayloads[String(pointId)];
            }
        });

        applyPointOrderToNav(nextPointIds);
        applyPointOrderToCards(getOrderedPointIdsFromPayloads());
        syncEmptyNavState();
        renderTimingSummary();
        restoreMainScrollAnchor(scrollAnchor);
        restoreFocusedEditor(focusedEditor);
    }

    function ensurePointLock(pointId) {
        if (!Number.isInteger(pointId) || pointId <= 0 || !editorToken || !pointWantsLock(pointId)) {
            return Promise.resolve(null);
        }

        if (activeLockPointIds.has(pointId) || pendingLockPointIds.has(pointId) || pendingUnlockPointIds.has(pointId)) {
            return Promise.resolve(null);
        }

        pendingLockPointIds.add(pointId);
        return postPointAction('lock_point', pointId)
            .then(function (payload) {
                pendingLockPointIds.delete(pointId);
                activeLockPointIds.add(pointId);
                if (payload && payload.point) {
                    mergeKnownPointSignature(payload.point);
                } else if (payload && payload.lock && currentPointPayloads[String(pointId)]) {
                    const pointPayload = Object.assign({}, currentPointPayloads[String(pointId)], {
                        lock: Object.assign({}, currentPointPayloads[String(pointId)].lock || {}, payload.lock)
                    });
                    mergeKnownPointSignature(pointPayload);
                }
                if (!pointWantsLock(pointId)) {
                    return releasePointLock(pointId);
                }
                return payload;
            })
            .catch(function (payload) {
                pendingLockPointIds.delete(pointId);
                if (payload && payload.point) {
                    replacePointHtml(payload.point);
                }
                return null;
            });
    }

    function releasePointLock(pointId) {
        if (!Number.isInteger(pointId) || pointId <= 0 || pendingUnlockPointIds.has(pointId)) {
            return Promise.resolve(null);
        }

        if (!activeLockPointIds.has(pointId)) {
            return Promise.resolve(null);
        }

        activeLockPointIds.delete(pointId);
        pendingUnlockPointIds.add(pointId);
        return postPointAction('unlock_point', pointId)
            .then(function (payload) {
                if (payload && payload.point && !pointWantsLock(pointId)) {
                    replacePointHtml(payload.point);
                }
                return payload;
            })
            .catch(function () {
                return null;
            })
            .finally(function () {
                pendingUnlockPointIds.delete(pointId);
                if (pointWantsLock(pointId)) {
                    ensurePointLock(pointId);
                }
            });
    }

    function takeOverPointLock(pointId, triggerButton) {
        if (!Number.isInteger(pointId) || pointId <= 0) {
            return;
        }

        if (triggerButton instanceof HTMLButtonElement) {
            triggerButton.disabled = true;
        }

        postPointAction('take_over_point_lock', pointId)
            .then(function (payload) {
                if (!payload || !payload.point) {
                    throw new Error('take_over_lock_failed');
                }

                activeLockPointIds.add(pointId);
                const nextCard = replacePointHtml(payload.point);
                const titleField = nextCard
                    ? nextCard.querySelector('[data-omo-pv-point-title="' + pointId + '"]')
                    : null;
                if (titleField instanceof HTMLInputElement) {
                    setFocusedPoint(pointId);
                    titleField.focus();
                }
            })
            .catch(function (error) {
                if (error && error.point) {
                    replacePointHtml(error.point);
                }
                const message = String(error && (error.message || error.text) || 'Impossible de reprendre le verrou.');
                window.alert(message);
            })
            .finally(function () {
                const currentButton = root.querySelector('[data-omo-pv-point-take-over-lock="' + pointId + '"]');
                if (currentButton instanceof HTMLButtonElement) {
                    currentButton.disabled = false;
                }
            });
    }

    function savePoint(pointId, preserveEditor) {
        preserveEditor = preserveEditor === true;
        const card = root.querySelector('[data-omo-pv-point-card="' + pointId + '"]');
        if (
            !card
            || card.getAttribute('data-omo-pv-point-dirty') !== '1'
            || card.getAttribute('data-omo-pv-point-saving') === '1'
        ) {
            return;
        }

        clearPointAutoSave(pointId);
        const savedChangeVersion = pointChangeVersions.get(pointId) || 0;

        const titleField = card.querySelector('[data-omo-pv-point-title="' + pointId + '"]');
        const typeField = card.querySelector('[data-omo-pv-point-type="' + pointId + '"]');
        const durationField = card.querySelector('[data-omo-pv-point-duration="' + pointId + '"]');
        const authorField = card.querySelector('[data-omo-pv-point-author="' + pointId + '"]');
        const concernedHolonField = card.querySelector('[data-omo-pv-point-concerned-holon="' + pointId + '"]');
        const confidentialField = card.querySelector('[data-omo-pv-point-confidential="' + pointId + '"]');
        const editorHost = card.querySelector('[data-omo-pv-point-editor-host="' + pointId + '"]');
        const statusNode = card.querySelector('[data-omo-pv-point-status="' + pointId + '"]');
        const htmlField = editorHost && editorHost.__omoPvPointField ? editorHost.__omoPvPointField : null;

        const formData = new FormData();
        formData.append('action', 'save_point');
        formData.append('document_id', String(documentId));
        formData.append('oid', String(organizationId));
        formData.append('editor_token', editorToken);
        formData.append('point_id', String(pointId));
        formData.append('title', titleField ? String(titleField.value || '') : '');
        formData.append('pointtype', typeField ? String(typeField.value || '') : 'information');
        formData.append('desired_duration_minutes', durationField ? String(durationField.value || '') : '');
        formData.append('author', authorField ? String(authorField.value || '') : '');
        formData.append('concerned_holon_id', concernedHolonField ? String(concernedHolonField.value || '0') : '0');
        formData.append('is_confidential', confidentialField && confidentialField.checked ? '1' : '0');
        formData.append('content', htmlField && typeof htmlField.getValue === 'function' ? String(htmlField.getValue() || '') : '');

        card.setAttribute('data-omo-pv-point-saving', '1');
        syncPointDirtyUi(pointId);
        if (statusNode) {
            statusNode.textContent = savingLabel;
        }

        fetch(actionUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        })
            .then(function (response) {
                return response.json().then(function (payload) {
                    if (!response.ok || !payload || payload.status !== true || (!payload.point && !payload.hiddenPointId)) {
                        throw payload || new Error('save_failed');
                    }

                    return payload;
                });
            })
            .then(function (payload) {
                const hasChangesAfterSaveStarted = (pointChangeVersions.get(pointId) || 0) !== savedChangeVersion;
                const drafts = hasChangesAfterSaveStarted ? captureDraftState() : null;
                if (payload.hiddenPointId) {
                    activeLockPointIds.delete(pointId);
                    pendingLockPointIds.delete(pointId);
                    pendingUnlockPointIds.delete(pointId);
                    renderPointCollection(Array.isArray(payload.points) ? payload.points : [], true);
                    return;
                }
                if (preserveEditor) {
                    mergeKnownPointSignature(payload.point);
                    if (hasChangesAfterSaveStarted) {
                        schedulePointAutoSave(pointId);
                        return;
                    }
                    markPointDirty(pointId, false);
                    const currentStatus = card.querySelector('[data-omo-pv-point-status="' + pointId + '"]');
                    if (currentStatus) {
                        currentStatus.textContent = payload.message || savedLabel;
                    }
                    syncPointLockState(pointId);
                    return;
                }
                const nextCard = replacePointHtml(payload.point);
                if (hasChangesAfterSaveStarted && drafts && drafts[pointId]) {
                    restoreDraftState({ [pointId]: drafts[pointId] });
                } else {
                    markPointDirty(pointId, false);
                }
                const nextStatus = nextCard ? nextCard.querySelector('[data-omo-pv-point-status="' + pointId + '"]') : null;
                if (nextStatus) {
                    nextStatus.textContent = payload.message || savedLabel;
                }
                syncPointLockState(pointId);
            })
            .catch(function (error) {
                if (!preserveEditor && error && error.point) {
                    replacePointHtml(error.point);
                }
                markPointDirty(pointId, true, false);
                if (statusNode) {
                    statusNode.textContent = error && error.message ? String(error.message) : (error && error.text ? String(error.text) : 'Erreur');
                }
            })
            .finally(function () {
                const currentCard = root.querySelector('[data-omo-pv-point-card="' + pointId + '"]');
                if (currentCard) {
                    currentCard.removeAttribute('data-omo-pv-point-saving');
                    syncPointDirtyUi(pointId);
                    if (preserveEditor && currentCard.getAttribute('data-omo-pv-point-dirty') === '1') {
                        schedulePointAutoSave(pointId);
                    }
                }
            });
    }

    function removePointFromEditor(pointId) {
        const card = root.querySelector('[data-omo-pv-point-card="' + pointId + '"]');
        const navRow = nav.querySelector('[data-omo-pv-point-nav-row="' + pointId + '"]');
        if (card) {
            card.remove();
        }
        if (navRow) {
            navRow.remove();
        }
        activeLockPointIds.delete(pointId);
        pendingLockPointIds.delete(pointId);
        pendingUnlockPointIds.delete(pointId);
        delete knownPointSignatures[String(pointId)];
        delete currentPointPayloads[String(pointId)];
        syncEmptyNavState();
    }

    function deletePoint(pointId, triggerButton, confirmationMessage) {
        if (!Number.isInteger(pointId) || pointId <= 0 || (triggerButton && triggerButton.disabled)) {
            return;
        }

        if (!window.confirm(confirmationMessage || deletePointMessage)) {
            return;
        }

        if (triggerButton) {
            triggerButton.disabled = true;
        }

        postPointAction('delete_point', pointId)
            .then(function (payload) {
                removePointFromEditor(pointId);
                renderPointCollection(payload && Array.isArray(payload.points) ? payload.points : [], true);
            })
            .catch(function (error) {
                if (triggerButton) {
                    triggerButton.disabled = false;
                }
                const card = root.querySelector('[data-omo-pv-point-card="' + pointId + '"]');
                const status = card ? card.querySelector('[data-omo-pv-point-status="' + pointId + '"]') : null;
                if (status) {
                    status.textContent = error && (error.message || error.text) ? String(error.message || error.text) : 'Erreur';
                }
            });
    }

    function togglePointHandled(pointId, isHandled, input) {
        if (input) {
            input.disabled = true;
        }

        postPointAction('toggle_handled', pointId, { is_handled: isHandled ? '1' : '0' })
            .then(function (payload) {
                if (payload && payload.point) {
                    replacePointHtml(payload.point);
                }
            })
            .catch(function () {
                if (input) {
                    input.checked = !isHandled;
                }
                renderTimingSummary();
            })
            .finally(function () {
                if (input) {
                    input.disabled = false;
                }
            });
    }

    function toggleAttendance(identityKey, isPresent, input) {
        if (!identityKey) {
            return;
        }

        if (input) {
            input.disabled = true;
        }

        postPointAction('toggle_attendance', 0, {
            identity_key: identityKey,
            is_present: isPresent ? '1' : '0'
        })
            .then(function (payload) {
                if (attendanceEnabled) {
                    renderAttendancePayload(payload && payload.attendance ? payload.attendance : null);
                }
            })
            .catch(function (payload) {
                if (input) {
                    input.checked = !isPresent;
                }
                if (attendanceEnabled && payload && payload.attendance) {
                    renderAttendancePayload(payload.attendance);
                }
            })
            .finally(function () {
                if (input instanceof HTMLInputElement && input.isConnected) {
                    input.disabled = false;
                }
            });
    }

    function persistPointOrder(layout) {
        const formData = new FormData();
        formData.append('action', 'reorder_points');
        formData.append('document_id', String(documentId));
        formData.append('oid', String(organizationId));
        formData.append('editor_token', editorToken);
        formData.append('layout', JSON.stringify(Array.isArray(layout) ? layout : []));

        fetch(actionUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (payload) {
                if (!payload || payload.status !== true || !Array.isArray(payload.points)) {
                    throw new Error(payload && payload.message ? payload.message : 'reorder_failed');
                }

                renderPointCollection(payload.points);
            })
            .catch(function () {
                // Keep the local order even if the save fails; the user can retry by dragging again.
            });
    }

    function addPoint() {
        if (!addButton) {
            return;
        }

        addButton.disabled = true;

        const formData = new FormData();
        formData.append('action', 'add_point');
        formData.append('document_id', String(documentId));
        formData.append('oid', String(organizationId));
        formData.append('editor_token', editorToken);

        fetch(actionUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (payload) {
                if (!payload || payload.status !== true || !payload.point) {
                    throw new Error(payload && payload.message ? payload.message : 'add_failed');
                }

                const nextCard = replacePointHtml(payload.point);
                if (nextCard) {
                    nextCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    const titleField = nextCard.querySelector('[data-omo-pv-point-title="' + payload.point.id + '"]');
                    if (titleField) {
                        titleField.focus();
                        titleField.select();
                    }
                }
            })
            .finally(function () {
                addButton.disabled = false;
            });
    }

    function addGroup() {
        if (!(addGroupButton instanceof HTMLButtonElement)) return;
        addGroupButton.disabled = true;
        postPointAction('add_group', 0)
            .then(function (payload) {
                if (!payload || !payload.point) throw new Error('add_group_failed');
                mergeKnownPointSignature(payload.point);
                mergeCurrentPointPayload(payload.point);
                renderNavTreeFromPayloads();
                const input = nav.querySelector('[data-omo-pv-group-title="' + Number(payload.point.id || 0) + '"]');
                if (input instanceof HTMLInputElement) {
                    input.scrollIntoView({behavior: 'smooth', block: 'nearest'});
                    input.focus();
                    input.select();
                }
            })
            .finally(function () {
                addGroupButton.disabled = false;
            });
    }

    function saveGroupTitle(input) {
        if (!(input instanceof HTMLInputElement)) return;
        const groupId = Number(input.getAttribute('data-omo-pv-group-title') || 0);
        const title = input.value.trim();
        if (groupId <= 0 || title === '') return;
        input.disabled = true;
        postPointAction('update_group', groupId, {title: title})
            .then(function (payload) {
                if (payload && payload.point) {
                    mergeKnownPointSignature(payload.point);
                    mergeCurrentPointPayload(payload.point);
                    renderNavTreeFromPayloads();
                }
            })
            .finally(function () {
                if (input.isConnected) input.disabled = false;
            });
    }

    function saveDocumentMetadata() {
        if (
            !(documentTitleInput instanceof HTMLInputElement)
            || !(documentDescriptionInput instanceof HTMLTextAreaElement)
            || !(documentVisibilitySelect instanceof Element)
            || documentMetadataSaving
            || !documentMetadataIsDirty()
        ) {
            return;
        }

        clearDocumentMetadataAutoSave();
        const savedChangeVersion = documentMetadataChangeVersion;
        let hasChangesAfterSaveStarted = false;
        documentMetadataSaving = true;
        syncDocumentMetadataUi();
        if (documentMetaStatus instanceof Element) {
            documentMetaStatus.textContent = savingLabel;
        }

        postPointAction('update_document_metadata', 0, {
            title: documentTitleInput.value.trim(),
            description: documentDescriptionInput.value.trim(),
            visibility_type: getDocumentVisibilityValue()
            })
            .then(function (payload) {
                hasChangesAfterSaveStarted = documentMetadataChangeVersion !== savedChangeVersion;
                mergeCurrentDocumentPayload(payload && payload.document ? payload.document : {});
                if (documentMetaStatus instanceof Element) {
                    documentMetaStatus.textContent = savedLabel;
                }
            })
            .catch(function (payload) {
                if (documentMetaStatus instanceof Element) {
                    documentMetaStatus.textContent = String(payload && payload.message ? payload.message : 'Impossible d’enregistrer le PV.');
                }
            })
            .finally(function () {
                documentMetadataSaving = false;
                syncDocumentMetadataUi();
                if (hasChangesAfterSaveStarted) {
                    scheduleDocumentMetadataAutoSave();
                }
        });
    }

    function generateDocumentAutoSummary() {
        if (
            autoSummaryPending
            || !(documentAutoSummaryButton instanceof HTMLButtonElement)
            || !(documentDescriptionInput instanceof HTMLTextAreaElement)
            || currentDocumentPayload.pvStage !== 'review'
        ) {
            return;
        }

        autoSummaryPending = true;
        documentAutoSummaryButton.disabled = true;
        if (documentMetaStatus instanceof Element) {
            documentMetaStatus.textContent = autoSummaryLoadingLabel;
        }

        const formData = new FormData();
        formData.append('oid', String(organizationId));
        formData.append('document_id', String(documentId));
        fetch('/omo/api/documents/pv/summarize.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            body: formData
        })
            .then(function (response) {
                return response.json().catch(function () { return null; }).then(function (payload) {
                    if (!response.ok || !payload || payload.status !== true) {
                        throw new Error(payload && payload.message ? payload.message : 'Impossible de générer le résumé.');
                    }
                    return payload;
                });
            })
            .then(function (payload) {
                const summary = String(payload.text || '').trim();
                if (summary === '') {
                    throw new Error('Le résumé généré est vide.');
                }
                documentDescriptionInput.value = summary;
                resizeDocumentDescriptionInput();
                markDocumentMetadataDirty();
                if (documentMetaStatus instanceof Element) {
                    documentMetaStatus.textContent = autoSummaryReadyLabel;
                }
            })
            .catch(function (error) {
                if (documentMetaStatus instanceof Element) {
                    documentMetaStatus.textContent = error && error.message ? error.message : 'Impossible de générer le résumé.';
                }
            })
            .finally(function () {
                autoSummaryPending = false;
                syncDocumentMetadataUi();
            });
    }

    function openValidatedPvViewer() {
        const routeToken = 'documents-d' + String(documentId);
        releaseActiveLocksWithBeacon();

        if (typeof window.omoCloseExternalPanelDrawer === 'function') {
            window.omoCloseExternalPanelDrawer({
                force: true,
                forceReset: true,
                skipCloseGuard: true
            });
        }

        window.setTimeout(function () {
            if (typeof window.omoOpenDrawerHashState === 'function') {
                window.omoOpenDrawerHashState(routeToken);
                return;
            }

            window.location.hash = routeToken;
        }, 210);
    }

    root.addEventListener('click', function (event) {
        const templateToggle = event.target.closest('[data-omo-pv-template-toggle]');
        if (templateToggle instanceof HTMLButtonElement && root.contains(templateToggle)) {
            event.preventDefault();
            const nextState = templateToggle.dataset.omoPvTemplateState === '1' ? 0 : 1;
            templateToggle.disabled = true;
            postPointAction('set_pv_template', 0, { is_template: nextState })
                .then(function (payload) {
                    mergeCurrentDocumentPayload(payload && payload.document ? payload.document : {});
                    const menu = templateToggle.closest('[data-omo-pv-more-actions]');
                    if (menu instanceof HTMLDetailsElement) {
                        menu.open = false;
                    }
                })
                .finally(function () {
                    templateToggle.disabled = currentDocumentPayload.canManagePvTemplate !== true;
                });
            return;
        }

        const documentMetaSave = event.target.closest('[data-omo-pv-document-meta-save]');
        if (documentMetaSave && root.contains(documentMetaSave)) {
            event.preventDefault();
            saveDocumentMetadata();
            return;
        }

        const documentAutoSummary = event.target.closest('[data-omo-pv-document-auto-summary]');
        if (documentAutoSummary && root.contains(documentAutoSummary)) {
            event.preventDefault();
            generateDocumentAutoSummary();
            return;
        }

        const stageButton = event.target.closest('[data-omo-pv-stage-option]');
        if (stageButton instanceof HTMLButtonElement && root.contains(stageButton) && !stageButton.disabled) {
            const previousStage = String(currentDocumentPayload && currentDocumentPayload.pvStage ? currentDocumentPayload.pvStage : '').trim();
            const nextStage = String(stageButton.getAttribute('data-omo-pv-stage-value') || '').trim();
            if (nextStage === '' || nextStage === previousStage) {
                return;
            }

            if (nextStage === 'validated') {
                if (hasUnsavedPointChanges() && !window.confirm(unsavedCloseMessage)) {
                    return;
                }

                if (!window.confirm(validateIrreversibleMessage)) {
                    return;
                }
            }

            stageButtons.forEach(function (button) {
                button.disabled = true;
            });

            postPointAction('update_stage', 0, { pv_stage: nextStage })
                .then(function (payload) {
                    const nextDocumentPayload = payload && payload.document ? payload.document : { pvStage: nextStage };
                    mergeCurrentDocumentPayload(nextDocumentPayload);
                    if (nextStage === 'review') {
                        window.location.reload();
                        return;
                    }
                    if (nextStage === 'validated' && currentDocumentPayload.isPvValidated === true) {
                        openValidatedPvViewer();
                    }
                })
                .finally(function () {
                    syncDocumentStageUi(currentDocumentPayload);
                });
            return;
        }

        const typeButton = event.target.closest('[data-omo-pv-point-type-option]');
        if (typeButton instanceof HTMLButtonElement && root.contains(typeButton)) {
            event.preventDefault();
            event.stopPropagation();
            selectPointTypeOption(typeButton, false);
            return;
        }

        const moveButton = event.target.closest('[data-omo-pv-point-move]');
        if (moveButton && root.contains(moveButton)) {
            event.preventDefault();
            event.stopPropagation();

            const pointId = Number(moveButton.getAttribute('data-omo-pv-point-move') || 0);
            const direction = String(moveButton.getAttribute('data-omo-pv-point-move-direction') || '').trim().toLowerCase();
            if (pointId > 0 && (direction === 'up' || direction === 'down')) {
                movePointByDirection(pointId, direction);
            }
            return;
        }

        const deleteButton = event.target.closest('[data-omo-pv-point-delete]');
        if (deleteButton && root.contains(deleteButton)) {
            event.preventDefault();
            event.stopPropagation();
            const pointId = Number(deleteButton.getAttribute('data-omo-pv-point-delete') || 0);
            if (pointId > 0) {
                deletePoint(pointId, deleteButton);
            }
            return;
        }

        const navButton = event.target.closest('[data-omo-pv-point-nav-target]');
        if (navButton && root.contains(navButton)) {
            const pointId = Number(navButton.getAttribute('data-omo-pv-point-nav-target') || 0);
            const target = pointId > 0 ? root.querySelector('#omo-pv-editor-point-' + pointId) : null;
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            return;
        }

        const takeOverLockButton = event.target.closest('[data-omo-pv-point-take-over-lock]');
        if (takeOverLockButton && root.contains(takeOverLockButton)) {
            event.preventDefault();
            const pointId = Number(takeOverLockButton.getAttribute('data-omo-pv-point-take-over-lock') || 0);
            if (pointId > 0) {
                takeOverPointLock(pointId, takeOverLockButton);
            }
            return;
        }

        const saveButton = event.target.closest('[data-omo-pv-point-save]');
        if (saveButton && root.contains(saveButton)) {
            const pointId = Number(saveButton.getAttribute('data-omo-pv-point-save') || 0);
            if (pointId > 0) {
                savePoint(pointId);
            }
            return;
        }

        const addPointButton = event.target.closest('[data-omo-pv-editor-add-point]');
        if (addPointButton && root.contains(addPointButton)) {
            addPoint();
            return;
        }

        const addGroupTrigger = event.target.closest('[data-omo-pv-editor-add-group]');
        if (addGroupTrigger && root.contains(addGroupTrigger)) {
            addGroup();
            return;
        }

        const groupToggle = event.target.closest('[data-omo-pv-group-toggle]');
        if (groupToggle && nav.contains(groupToggle)) {
            const groupNode = groupToggle.closest('[data-omo-pv-group]');
            if (groupNode) {
                const collapsed = groupNode.classList.toggle('is-collapsed');
                groupToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            }
            return;
        }

        const claimSecretaryButton = event.target.closest('[data-omo-pv-claim-secretary]');
        if (claimSecretaryButton && root.contains(claimSecretaryButton)) {
            const secretaryAction = String(claimSecretaryButton.dataset.omoPvSecretaryAction || 'claim_pv_editor');
            if (secretaryAction === 'pass_pv_editor' && hasUnsavedPointChanges()) {
                window.alert(unsavedHandoverMessage);
                return;
            }
            claimSecretaryButton.disabled = true;
            postPointAction(secretaryAction, 0, {})
                .then(function (payload) {
                    mergeCurrentDocumentPayload(payload && payload.document ? payload.document : {});
                    if (payload && Array.isArray(payload.points)) {
                        renderPointCollection(payload.points, true);
                    }
                })
                .finally(function () {
                    syncPvEditorUi(currentDocumentPayload);
                });
        }
    });

    root.addEventListener('change', function (event) {
        const groupTitleInput = event.target.closest('[data-omo-pv-group-title]');
        if (groupTitleInput && nav.contains(groupTitleInput)) {
            saveGroupTitle(groupTitleInput);
            return;
        }

        const attendanceInput = event.target.closest('[data-omo-pv-attendance-toggle]');
        if (attendanceInput && root.contains(attendanceInput)) {
            toggleAttendance(
                String(attendanceInput.getAttribute('data-omo-pv-attendance-toggle') || '').trim(),
                !!attendanceInput.checked,
                attendanceInput
            );
            return;
        }

        const handledInput = event.target.closest('[data-omo-pv-point-handled]');
        if (!handledInput || !root.contains(handledInput)) {
            return;
        }

        const pointId = Number(handledInput.getAttribute('data-omo-pv-point-handled') || 0);
        if (pointId > 0) {
            renderTimingSummary();
            togglePointHandled(pointId, !!handledInput.checked, handledInput);
        }
    });

    root.addEventListener('keydown', function (event) {
        const typeButton = event.target.closest('[data-omo-pv-point-type-option]');
        if (!(typeButton instanceof HTMLButtonElement) || !root.contains(typeButton)) {
            return;
        }

        if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
            return;
        }

        const pointId = Number(typeButton.getAttribute('data-omo-pv-point-type-option') || 0);
        const card = pointId > 0 ? root.querySelector('[data-omo-pv-point-card="' + pointId + '"]') : null;
        const buttons = card instanceof Element
            ? Array.from(card.querySelectorAll('[data-omo-pv-point-type-option="' + pointId + '"]'))
            : [];
        const currentIndex = buttons.indexOf(typeButton);
        if (currentIndex < 0 || buttons.length < 2) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        const direction = event.key === 'ArrowRight' ? 1 : -1;
        const nextIndex = (currentIndex + direction + buttons.length) % buttons.length;
        selectPointTypeOption(buttons[nextIndex], true);
    });

    (function initPointReorder() {
        if (!nav) {
            return;
        }

        let draggedPointId = 0;
        let dropIntent = null;
        const dropInsideLabel = <?= json_encode(omoDocumentsPvEditorT('documents.pv_editor.group.drop_inside'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const dropIndicator = document.createElement('div');
        dropIndicator.className = 'omo-pv-editor__drop-indicator';
        dropIndicator.setAttribute('aria-hidden', 'true');

        const showDropIndicator = function (rect, mode) {
            if (!(rect instanceof DOMRect) && (!rect || typeof rect.left !== 'number')) return;
            if (!dropIndicator.isConnected) document.body.appendChild(dropIndicator);

            if (mode === 'inside') {
                dropIndicator.className = 'omo-pv-editor__drop-indicator omo-pv-editor__drop-indicator--inside';
                dropIndicator.textContent = String(dropInsideLabel || '');
                dropIndicator.style.left = Math.round(rect.left) + 'px';
                dropIndicator.style.top = Math.round(rect.top) + 'px';
                dropIndicator.style.width = Math.max(24, Math.round(rect.width)) + 'px';
                dropIndicator.style.height = Math.max(38, Math.round(rect.height)) + 'px';
                return;
            }

            dropIndicator.className = 'omo-pv-editor__drop-indicator omo-pv-editor__drop-indicator--line';
            dropIndicator.textContent = '';
            dropIndicator.style.left = Math.round(rect.left + 8) + 'px';
            dropIndicator.style.top = Math.round((mode === 'after' ? rect.bottom : rect.top) - 3) + 'px';
            dropIndicator.style.width = Math.max(24, Math.round(rect.width - 16)) + 'px';
            dropIndicator.style.height = '6px';
        };

        const clearDragState = function () {
            nav.querySelectorAll('.is-drop-before, .is-drop-after, .is-drop-inside, .is-dragging').forEach(function (node) {
                node.classList.remove('is-drop-before', 'is-drop-after', 'is-drop-inside', 'is-dragging');
            });
            if (dropIndicator.isConnected) dropIndicator.remove();
            if (deleteDropzone) {
                deleteDropzone.classList.remove('is-visible', 'is-active');
            }
            dropIntent = null;
        };

        nav.addEventListener('dragstart', function (event) {
            const handle = event.target.closest('[data-omo-pv-point-drag-handle]');
            if (!handle) {
                event.preventDefault();
                return;
            }

            draggedPointId = Number(handle.getAttribute('data-omo-pv-point-drag-handle') || 0);
            const row = draggedPointId > 0 ? nav.querySelector('[data-omo-pv-nav-node="' + draggedPointId + '"]') : null;
            if (!(row instanceof Element)) {
                draggedPointId = 0;
                event.preventDefault();
                return;
            }

            row.classList.add('is-dragging');
            if (deleteDropzone && row.getAttribute('data-omo-pv-can-delete') === '1') {
                deleteDropzone.classList.add('is-visible');
            }
            if (event.dataTransfer) {
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', String(draggedPointId));
            }
        });

        nav.addEventListener('dragover', function (event) {
            if (!draggedPointId) {
                return;
            }

            const sourceNode = nav.querySelector('[data-omo-pv-nav-node="' + draggedPointId + '"]');
            const targetNode = event.target.closest('[data-omo-pv-nav-node]');
            if (!(sourceNode instanceof Element)) return;

            if (!targetNode) {
                const targetContainer = event.target.closest('[data-omo-pv-nav-children]');
                if (!(targetContainer instanceof Element) || sourceNode.contains(targetContainer)) return;
                event.preventDefault();
                nav.querySelectorAll('.is-drop-before, .is-drop-after, .is-drop-inside').forEach(function (node) {
                    node.classList.remove('is-drop-before', 'is-drop-after', 'is-drop-inside');
                });
                dropIntent = { mode: 'inside', container: targetContainer };
                const containerGroup = targetContainer.closest('[data-omo-pv-group]');
                if (containerGroup instanceof Element) {
                    containerGroup.classList.add('is-drop-inside');
                    const groupHead = containerGroup.querySelector(':scope > .omo-pv-editor__group-head');
                    if (groupHead instanceof Element) showDropIndicator(groupHead.getBoundingClientRect(), 'inside');
                } else {
                    const lastNode = targetContainer.querySelector(':scope > [data-omo-pv-nav-node]:last-child');
                    showDropIndicator((lastNode || targetContainer).getBoundingClientRect(), 'after');
                }
                if (event.dataTransfer) event.dataTransfer.dropEffect = 'move';
                return;
            }

            if (targetNode === sourceNode || sourceNode.contains(targetNode)) return;

            event.preventDefault();
            if (event.dataTransfer) {
                event.dataTransfer.dropEffect = 'move';
            }
            nav.querySelectorAll('.is-drop-before, .is-drop-after, .is-drop-inside').forEach(function (node) {
                node.classList.remove('is-drop-before', 'is-drop-after', 'is-drop-inside');
            });

            const isGroup = targetNode.matches('[data-omo-pv-group]');
            const targetHitbox = isGroup
                ? targetNode.querySelector(':scope > .omo-pv-editor__group-head')
                : targetNode;
            const targetRect = (targetHitbox || targetNode).getBoundingClientRect();
            const relativeY = Math.max(0, Math.min(targetRect.height, event.clientY - targetRect.top));
            const dropMode = isGroup && relativeY >= targetRect.height * 0.28 && relativeY <= targetRect.height * 0.72
                ? 'inside'
                : (relativeY > targetRect.height / 2 ? 'after' : 'before');
            targetNode.classList.add(dropMode === 'inside' ? 'is-drop-inside' : (dropMode === 'after' ? 'is-drop-after' : 'is-drop-before'));
            showDropIndicator(targetRect, dropMode);
            if (dropMode === 'inside') {
                targetNode.classList.remove('is-collapsed');
                const toggle = targetNode.querySelector(':scope > .omo-pv-editor__group-head [data-omo-pv-group-toggle]');
                if (toggle) toggle.setAttribute('aria-expanded', 'true');
                const childContainer = targetNode.querySelector(':scope > [data-omo-pv-nav-children]');
                if (!(childContainer instanceof Element)) return;
                dropIntent = { mode: 'inside', container: childContainer };
            } else {
                dropIntent = { mode: dropMode, reference: targetNode };
            }
        });

        nav.addEventListener('drop', function (event) {
            const sourceRow = draggedPointId > 0 ? nav.querySelector('[data-omo-pv-nav-node="' + draggedPointId + '"]') : null;
            if (!(sourceRow instanceof Element) || !dropIntent) {
                clearDragState();
                draggedPointId = 0;
                return;
            }

            event.preventDefault();
            if (dropIntent.mode === 'inside' && dropIntent.container instanceof Element) {
                dropIntent.container.appendChild(sourceRow);
            } else if (dropIntent.reference instanceof Element) {
                if (dropIntent.mode === 'after') {
                    dropIntent.reference.insertAdjacentElement('afterend', sourceRow);
                } else {
                    dropIntent.reference.insertAdjacentElement('beforebegin', sourceRow);
                }
            } else {
                clearDragState();
                draggedPointId = 0;
                return;
            }

            const layout = collectNavLayout();
            applyPointOrderToCards(layout.filter(function (item) {
                const payload = currentPointPayloads[String(item.id)] || {};
                return payload.isGroup !== true;
            }).map(function (item) { return item.id; }));
            persistPointOrder(layout);
            clearDragState();
            draggedPointId = 0;
        });

        if (deleteDropzone) {
            deleteDropzone.addEventListener('dragenter', function (event) {
                if (!draggedPointId) {
                    return;
                }
                const sourceRow = nav.querySelector('[data-omo-pv-nav-node="' + draggedPointId + '"]');
                if (!(sourceRow instanceof Element) || sourceRow.getAttribute('data-omo-pv-can-delete') !== '1') {
                    return;
                }
                event.preventDefault();
                deleteDropzone.classList.add('is-active');
            });

            deleteDropzone.addEventListener('dragover', function (event) {
                if (!draggedPointId) {
                    return;
                }
                const sourceRow = nav.querySelector('[data-omo-pv-nav-node="' + draggedPointId + '"]');
                if (!(sourceRow instanceof Element) || sourceRow.getAttribute('data-omo-pv-can-delete') !== '1') {
                    return;
                }
                event.preventDefault();
                deleteDropzone.classList.add('is-active');
                if (event.dataTransfer) {
                    event.dataTransfer.dropEffect = 'move';
                }
            });

            deleteDropzone.addEventListener('dragleave', function (event) {
                if (event.relatedTarget instanceof Node && deleteDropzone.contains(event.relatedTarget)) {
                    return;
                }
                deleteDropzone.classList.remove('is-active');
            });

            deleteDropzone.addEventListener('drop', function (event) {
                const pointId = draggedPointId;
                const sourceRow = pointId > 0 ? nav.querySelector('[data-omo-pv-nav-node="' + pointId + '"]') : null;
                if (!(sourceRow instanceof Element) || sourceRow.getAttribute('data-omo-pv-can-delete') !== '1') {
                    clearDragState();
                    draggedPointId = 0;
                    return;
                }
                event.preventDefault();
                clearDragState();
                draggedPointId = 0;
                deletePoint(pointId, null, deleteItemMessage);
            });
        }

        nav.addEventListener('dragend', function () {
            clearDragState();
            draggedPointId = 0;
        });

        nav.addEventListener('dragleave', function (event) {
            const relatedTarget = event.relatedTarget;
            if (relatedTarget instanceof Node && nav.contains(relatedTarget)) {
                return;
            }

            nav.querySelectorAll('.is-drop-before, .is-drop-after, .is-drop-inside').forEach(function (node) {
                node.classList.remove('is-drop-before', 'is-drop-after', 'is-drop-inside');
            });
            if (dropIndicator.isConnected) dropIndicator.remove();
            dropIntent = null;
        });
    })();

    function isPvEditorSessionActive() {
        if (!root.isConnected || document.hidden) {
            return false;
        }

        const externalDrawer = root.closest('[data-omo-external-panel-drawer="1"]');
        if (externalDrawer instanceof HTMLElement) {
            return !externalDrawer.hidden && externalDrawer.classList.contains('is-open');
        }

        return root.getClientRects().length > 0;
    }

    function isPvEditorPollingActive() {
        if (!isPvEditorSessionActive()) {
            return false;
        }

        const externalDrawer = root.closest('[data-omo-external-panel-drawer="1"]');
        return !(externalDrawer instanceof HTMLElement && externalDrawer.classList.contains('is-peek'));
    }

    function getNextSyncPollDelay() {
        return String(currentDocumentPayload.pvStage || '') === 'meeting'
            || activeLockPointIds.size > 0
            || hasUnsavedPointChanges()
            ? activeSyncPollDelayMs
            : idleSyncPollDelayMs;
    }

    function scheduleNextSyncPoll(delayMs) {
        if (syncPollTimer !== null) {
            window.clearTimeout(syncPollTimer);
            syncPollTimer = null;
        }
        if (currentDocumentPayload.isPvValidated === true || !root.isConnected) {
            return;
        }

        const nextDelay = Number.isFinite(delayMs) ? Math.max(0, delayMs) : getNextSyncPollDelay();
        syncPollTimer = window.setTimeout(function () {
            syncPollTimer = null;
            renderTimingSummary();
            if (!root.isConnected) {
                stopPvEditorBackgroundWork();
                return;
            }
            if (!isPvEditorPollingActive()) {
                scheduleNextSyncPoll(idleSyncPollDelayMs);
                return;
            }

            syncEditorFromServer().then(function () {
                scheduleNextSyncPoll();
            });
        }, nextDelay);
    }

    function startSyncPolling() {
        scheduleNextSyncPoll(activeSyncPollDelayMs);
    }

    function startEditorLifecycleObserver() {
        const externalDrawer = root.closest('[data-omo-external-panel-drawer="1"]');
        editorWasPollingActive = isPvEditorPollingActive();
        if (!(externalDrawer instanceof HTMLElement) || typeof MutationObserver !== 'function') {
            return;
        }

        editorLifecycleObserver = new MutationObserver(function () {
            const pollingActive = isPvEditorPollingActive();
            if (pollingActive && !editorWasPollingActive) {
                scheduleNextSyncPoll(0);
            }
            editorWasPollingActive = pollingActive;
        });
        editorLifecycleObserver.observe(externalDrawer, {
            attributes: true,
            attributeFilter: ['class', 'hidden']
        });
    }

    function syncEditorFromServer() {
        if (currentDocumentPayload.isPvValidated === true) {
            return Promise.resolve(null);
        }
        if (syncPollPending instanceof Promise) {
            return syncPollPending;
        }

        syncPollPending = postPointAction('poll_updates', 0, {
            poll_revision: knownPollingRevision
        })
            .then(function (payload) {
                if (payload && payload.pollRevision) {
                    knownPollingRevision = String(payload.pollRevision);
                }
                if (payload && payload.unchanged === true) {
                    return payload;
                }

                const remoteDocumentPayload = payload && payload.document ? payload.document : null;
                if (documentPayloadHasRemoteChanges(remoteDocumentPayload)) {
                    mergeCurrentDocumentPayload(remoteDocumentPayload);
                }
                if (attendanceEnabled) {
                    renderAttendancePayload(payload && payload.attendance ? payload.attendance : null);
                }
                if (payload && Array.isArray(payload.points) && pointCollectionHasRemoteChanges(payload.points)) {
                    renderPointCollection(payload.points);
                }
                return payload;
            })
            .catch(function () {
                // Silent polling failure: keep the editor usable locally.
                return null;
            })
            .finally(function () {
                syncPollPending = null;
            });
        return syncPollPending;
    }

    function startLockHeartbeat() {
        if (lockHeartbeatTimer !== null) {
            window.clearInterval(lockHeartbeatTimer);
        }
        if (currentDocumentPayload.isPvValidated === true) {
            lockHeartbeatTimer = null;
            return;
        }

        lockHeartbeatTimer = window.setInterval(function () {
            if (currentDocumentPayload.isPvValidated === true || !isPvEditorSessionActive() || activeLockPointIds.size === 0) {
                return;
            }

            const pointIds = Array.from(activeLockPointIds).filter(function (pointId) {
                return Number.isInteger(pointId) && pointId > 0;
            });
            if (pointIds.length === 0) {
                return;
            }

            postPointAction('heartbeat_locks', 0, {
                point_ids: pointIds.join(',')
            }).catch(function () {
                // Polling will reconcile visible lock state if needed.
            });
        }, 30000);
    }

    window.addEventListener('beforeunload', function (event) {
        if (hasUnsavedPointChanges()) {
            event.preventDefault();
            event.returnValue = '';
            return '';
        }

        releaseActiveLocksWithBeacon();
    });

    window.addEventListener('pagehide', function (event) {
        if (!event.persisted) {
            releaseActiveLocksWithBeacon();
        }
    });

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden && isPvEditorPollingActive()) {
            scheduleNextSyncPoll(0);
        }
    });

    if (root.querySelector('.omo-simple-html-render')) {
        ensureHtmlFieldLibrary(function () {});
    }

    mountEditableCards(root);
    if (autoSaveToggle instanceof HTMLInputElement) {
        autoSaveToggle.addEventListener('change', function () {
            getDirtyPointIds().forEach(function (pointId) {
                if (autoSaveToggle.checked) {
                    schedulePointAutoSave(pointId);
                } else {
                    clearPointAutoSave(pointId);
                }
            });
            if (autoSaveToggle.checked) {
                scheduleDocumentMetadataAutoSave();
            } else {
                clearDocumentMetadataAutoSave();
            }
        });
    }
    [documentTitleInput, documentDescriptionInput].forEach(function (input) {
        if (!(input instanceof Element)) {
            return;
        }
        input.addEventListener('input', function () {
            resizeDocumentDescriptionInput();
            markDocumentMetadataDirty();
        });
    });
    if (documentVisibilitySelect instanceof Element) {
        documentVisibilitySelect.addEventListener('change', function () {
            markDocumentMetadataDirty();
        });
    }
    if (invitationsButton instanceof HTMLButtonElement) {
        invitationsButton.addEventListener('click', function () {
            const invitationUrl = String(invitationsButton.getAttribute('data-omo-pv-invitations-url') || '').trim();
            const invitationTitle = String(invitationsButton.getAttribute('data-omo-pv-invitations-title') || '').trim();
            if (invitationUrl && typeof window.commonTopbarOpenModal === 'function') {
                window.commonTopbarOpenModal(invitationTitle || 'Invités', invitationUrl, 'fetch');
            }
        });
    }
    window.addEventListener('omo:pv-invitations-updated', function (event) {
        const updatedDocumentId = Number(event && event.detail ? event.detail.documentId : 0);
        if (updatedDocumentId === documentId) {
            syncEditorFromServer();
        }
    });
    resizeDocumentDescriptionInput();
    syncEmptyNavState();
    mergeCurrentDocumentPayload(initialDocumentPayload);
    if (attendanceEnabled) {
        renderAttendancePayload(initialAttendancePayload);
    }
    updateKnownPointSignatures(initialPointPayloads);
    syncCurrentPointPayloads(initialPointPayloads);
    renderNavTreeFromPayloads();
    applyPointOrderToCards(getOrderedPointIdsFromPayloads());
    renderTimingSummary();
    startEditorLifecycleObserver();
    startSyncPolling();
    startLockHeartbeat();
})();
</script>
