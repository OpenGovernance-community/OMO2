<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/pv_editor_helpers.php';

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

if (!$accessGranted) {
    http_response_code(403);
    ?>
    <div class="omo-empty-state"><?= $escape(omoDocumentsPvEditorT('documents.pv_editor.error.forbidden')) ?></div>
    <?php
    exit;
}

$event = $document->getAssociatedEvent();
$hasAssociatedEvent = $event instanceof \dbObject\Event;
$organization = new \dbObject\Organization();
$hasOrganization = $organizationId > 0 && $organization->load($organizationId);
$hasTeamApplication = $hasOrganization && $organization->isApplicationEnabled('team', $currentUserId);
$hasStructureApplication = $hasOrganization && $organization->isStructureApplicationEnabled($currentUserId);
$hasUpcomingAssociatedEvent = $hasAssociatedEvent && $event->isUpcoming();
$canManagePvStage = $document->canManagePvStage($organizationId, $currentUserId);
$pvEditorUserId = $document->getPvEditorUserId();
$pvEditorLabel = $pvEditorUserId > 0
    ? \dbObject\DocumentPvPoint::getUserDisplayNameForOrganization($pvEditorUserId, $organizationId)
    : '';
$isPvEditor = $document->isPvEditor($currentUserId);
$canManagePvDocument = $document->canUserManagePvDocument($currentUserId);
$canClaimPvEditor = $document->canUserClaimPvEditor($organizationId, $currentUserId);
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
    $documentTitle,
    $documentDescription,
    $documentVisibilityType,
]));
$eventTitle = $hasAssociatedEvent
    ? (trim((string)$event->get('title')) !== ''
        ? trim((string)$event->get('title'))
        : ('Evenement #' . (int)$event->getId()))
    : '';
$eventSchedule = trim(
    ($eventStartAt instanceof DateTimeInterface ? $formatDateTime($eventStartAt) : '')
    . ($eventEndAt instanceof DateTimeInterface ? ' - ' . $formatDateTime($eventEndAt) : '')
);
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
$hasPvInvitationEditor = $hasAssociatedEvent;
$canManagePvInvitations = $hasPvInvitationEditor
    && $pvStage === \dbObject\Document::PV_STAGE_PREPARATION
    && $canManagePvDocument;
$pvInvitationPopupUrl = $hasPvInvitationEditor
    ? '/omo/api/calendar/invitations_popup.php?oid=' . rawurlencode((string)$organizationId)
        . '&id=' . rawurlencode((string)(int)$event->getId())
        . '&pv_editor=1'
    : '';

$points = new \dbObject\ArrayDocumentPvPoint();
$points->loadForDocument((int)$document->getId(), true);
$authorOptions = $document->getPvPointAuthorOptions($organizationId);
$pointCards = [];
$pointNavItems = [];
$pointPayloads = [];
$attendancePayload = $hasTeamApplication
    ? omoDocumentsPvEditorBuildAttendancePayloadFromDocument($document, $organizationId)
    : null;

foreach ($points as $point) {
    if (!($point instanceof \dbObject\DocumentPvPoint) || (int)$point->getId() <= 0) {
        continue;
    }

    $pointData = $point->buildEditorData($organizationId, $currentUserId, $editorToken);
    $pointData['isEditable'] = $document->canUserEditPvPoint($point, $currentUserId);
    $pointData['canEditNow'] = !empty($pointData['isEditable']) && empty($pointData['lock']['isLockedByOther']);
    $pointData['canReorder'] = $document->canUserReorderPvPoints($currentUserId);
    $pointData['isPvEditor'] = $isPvEditor;
    $pointData['canToggleHandled'] = $canManagePvDocument;
    $pointData['canAssignAuthor'] = $canManagePvDocument;
    $pointData['hasStructureApplication'] = $hasStructureApplication;
    $pointData['authorOptions'] = $authorOptions;
    $pointData['authorHolonOptions'] = [];
    foreach ($authorOptions as $authorOption) {
		$authorValue = trim((string)($authorOption['value'] ?? ''));
        $authorUserId = (int)($authorOption['userId'] ?? 0);
        if ($authorValue !== '') {
            $pointData['authorHolonOptions'][$authorValue] = $hasStructureApplication && $authorUserId > 0
                ? \dbObject\DocumentPvPoint::buildConcernedHolonOptionsForDocument($document, $authorUserId)
                : [];
        }
    }
    $pointData = omoDocumentsPvEditorAttachConcernedHolonOptions(
        $pointData,
        $hasStructureApplication
            ? \dbObject\DocumentPvPoint::buildConcernedHolonOptionsForDocument($document, (int)$point->get('IDuser_author'))
            : []
    );
    $payload = omoDocumentsPvEditorBuildPointPayload($pointData, $uiText);
    $pointCards[] = $payload['cardHtml'];
    $pointNavItems[] = $payload['navHtml'];
    $pointPayloads[] = $payload;
}
?>
<div
    class="omo-pv-editor"
    data-omo-pv-editor-root="1"
    data-omo-pv-editor-document-id="<?= (int)$document->getId() ?>"
    data-omo-pv-editor-oid="<?= (int)$organizationId ?>"
    data-omo-pv-editor-user-id="<?= (int)$currentUserId ?>"
    data-omo-pv-editor-token="<?= $escape($editorToken) ?>"
    data-omo-pv-editor-action-url="<?= $escape('/omo/api/documents/pv_editor_action.php') ?>"
>
    <style>
    .omo-pv-editor {
        --omo-pv-editor-sidebar-width: 280px;
        display: grid;
        grid-template-columns: minmax(220px, var(--omo-pv-editor-sidebar-width)) 10px minmax(0, 1fr);
        grid-template-rows: minmax(0, 1fr);
        gap: 0 18px;
        min-height: 100%;
        height: 100%;
        max-height: 100%;
        padding: 18px;
        background: linear-gradient(180deg, color-mix(in srgb, var(--color-surface-alt, #f8fafc) 84%, white) 0%, var(--color-bg, #eef2ff) 100%);
        overflow: hidden;
    }

    .omo-pv-editor__page-head {
        display: grid;
        gap: 12px;
        align-items: end;
        grid-template-columns: minmax(0, 1.25fr) minmax(260px, 0.95fr);
    }

    .omo-pv-editor__page-title {
        display: grid;
        gap: 6px;
        min-width: 0;
    }

    .omo-pv-editor__page-title h2,
    .omo-pv-editor__page-title p {
        margin: 0;
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
        font-size: 1.45rem;
        font-weight: 750;
    }

    .omo-pv-editor__document-description-input {
        min-height: 24px;
        resize: none;
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
        margin-top: 3px;
    }

    .omo-pv-editor__document-visibility-select {
        width: auto;
        min-width: 132px;
        padding: 5px 26px 5px 8px;
        font-size: 0.82rem;
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

    .omo-pv-editor__page-side {
        display: grid;
        gap: 10px;
        justify-items: stretch;
        align-content: start;
        min-width: 0;
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
    }

    .omo-pv-editor__stage-option {
        --omo-pv-stage-color: #64748b;
        position: relative;
        flex: 1 0 104px;
        min-width: 0;
        height: 30px;
        /* The extra 2px removes the anti-aliased seam between each interlocking pair. */
        margin-left: -24px;
        padding: 0 15px 0 30px;
        border: 0;
        /* The left V receives the previous step point; the right V enters the next step. */
        clip-path: polygon(11px 0, calc(100% - 11px) 0, 100% 50%, calc(100% - 11px) 100%, 11px 100%, 0 50%);
        background: color-mix(in srgb, var(--omo-pv-stage-color) 43%, white 57%);
        color: color-mix(in srgb, var(--omo-pv-stage-color) 78%, #102a3c 22%);
        font: inherit;
        font-size: 0.75rem;
        font-weight: 800;
        line-height: 1;
        letter-spacing: 0.01em;
        text-align: center;
        white-space: nowrap;
        cursor: pointer;
        transition: background 160ms ease, color 160ms ease, filter 160ms ease;
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

    .omo-pv-editor__attendance {
        display: grid;
        gap: 8px;
        width: 100%;
        grid-column: 1 / -1;
        padding: 10px 12px;
        border: 1px solid color-mix(in srgb, var(--color-border, #d1d5db) 78%, white 22%);
        border-radius: 16px;
        background: color-mix(in srgb, var(--color-surface-alt, #f8fafc) 62%, white 38%);
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

    .omo-pv-editor__attendance-name,
    .omo-pv-editor__attendance-meta {
        min-width: 0;
    }

    .omo-pv-editor__attendance-name {
        font-size: 0.86rem;
        font-weight: 600;
        color: var(--color-text, #0f172a);
    }

    .omo-pv-editor__attendance-meta {
        color: var(--color-text-light, #64748b);
        font-size: 0.8rem;
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
        padding: 14px;
        border: 1px solid color-mix(in srgb, var(--color-border, #d1d5db) 84%, white 16%);
        border-radius: 20px;
        background: color-mix(in srgb, var(--color-surface, #ffffff) 92%, white 8%);
        box-shadow: 0 18px 40px -32px rgba(15, 23, 42, 0.35);
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
        display: grid;
        gap: 6px;
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
        border-radius: 14px;
        background: color-mix(in srgb, var(--color-surface, #ffffff) 94%, transparent);
        transition: transform 140ms ease, margin 140ms ease;
    }

    .omo-pv-editor__nav-row.is-handled {
        opacity: 0.8;
    }

    .omo-pv-editor__nav-row.is-dragging {
        opacity: 0.45;
        transform: scale(0.985);
    }

    .omo-pv-editor__nav-drop-marker {
        min-height: 42px;
        border: 2px dashed color-mix(in srgb, var(--color-primary, #2563eb) 72%, white 28%);
        border-radius: 14px;
        background:
            linear-gradient(
                135deg,
                color-mix(in srgb, var(--color-primary, #2563eb) 12%, transparent),
                color-mix(in srgb, var(--color-primary, #2563eb) 4%, transparent)
            );
        box-shadow:
            inset 0 0 0 1px color-mix(in srgb, var(--color-primary, #2563eb) 14%, transparent),
            0 8px 20px color-mix(in srgb, var(--color-primary, #2563eb) 12%, transparent);
        pointer-events: none;
        animation: omo-pv-drop-marker-pulse 900ms ease-in-out infinite alternate;
    }

    @keyframes omo-pv-drop-marker-pulse {
        from {
            opacity: 0.78;
        }
        to {
            opacity: 1;
        }
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
        display: grid;
        gap: 12px;
        grid-auto-rows: max-content;
        align-content: start;
        min-height: 0;
        overflow: auto;
        padding-right: 4px;
    }

    .omo-pv-editor__toolbar {
        display: flex;
        justify-content: flex-end;
    }

    .omo-pv-editor__points {
        display: grid;
        gap: 12px;
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

    .omo-pv-editor__field {
        display: grid;
        gap: 6px;
    }

    .omo-pv-editor__field--compact .generic-form-control {
        text-align: right;
    }

    .omo-pv-editor__editor-block {
        display: grid;
        gap: 8px;
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

    .omo-pv-editor__point-author-select-shell {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        min-width: 0;
    }

    .omo-pv-editor__secretary {
        grid-column: 1 / -1;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 7px 10px;
        padding-top: 2px;
    }

    .omo-pv-editor__secretary-name {
        font-weight: 750;
        color: var(--color-text, #183247);
    }

    .omo-pv-editor__secretary-state {
        color: var(--color-success, #15803d);
        font-size: 0.84rem;
    }

    .omo-pv-editor__secretary-claim {
        margin-left: auto;
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

    .omo-pv-editor__save-button:disabled {
        cursor: not-allowed;
    }

    .omo-pv-editor__save-button.is-saving:disabled {
        cursor: wait;
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

        .omo-pv-editor__page-head {
            grid-template-columns: 1fr;
        }

        .omo-pv-editor__page-side,
        .omo-pv-editor__page-meta {
            justify-items: start;
            justify-content: flex-start;
        }

        .omo-pv-editor__stage-help {
            text-align: left;
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
    </style>

    <aside class="omo-pv-editor__sidebar">
        <section class="omo-pv-editor__panel omo-pv-editor__agenda-panel">
            <div class="omo-pv-editor__toolbar">
                <button type="button" class="generic-action-button generic-action-button--main" data-omo-pv-editor-add-point<?= $isPvValidated ? ' disabled' : '' ?>><?= $escape(omoDocumentsPvEditorT('documents.pv_editor.action.add_point')) ?></button>
            </div>
            <div class="omo-pv-editor__nav" data-omo-pv-editor-nav>
                <?php if (count($pointNavItems) === 0): ?>
                    <div class="omo-empty-state omo-pv-editor__empty"><?= $escape(omoDocumentsPvEditorT('documents.pv_editor.nav.empty')) ?></div>
                <?php else: ?>
                    <?= implode('', $pointNavItems) ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="omo-pv-editor__panel omo-pv-editor__timing" data-omo-pv-timing-panel>
            <div class="omo-pv-editor__timing-chart-shell">
                <div class="omo-pv-editor__timing-chart" data-omo-pv-timing-chart="1">
                    <svg viewBox="0 0 100 100" aria-hidden="true" focusable="false">
                        <circle cx="50" cy="50" r="50" fill="#e2e8f0"></circle>
                        <path data-omo-pv-timing-outer-handled fill="#22c55e"></path>
                        <path data-omo-pv-timing-outer-remaining fill="#f59e0b"></path>
                        <path data-omo-pv-timing-outer-buffer fill="#cbd5e1"></path>
                        <circle cx="50" cy="50" r="34" fill="#e2e8f0"></circle>
                        <path data-omo-pv-timing-sector fill="#ef4444"></path>
                    </svg>
                    <div class="omo-pv-editor__timing-center">
                        <strong data-omo-pv-timing-center><?= $escape((string)$uiText['notStartedValue']) ?></strong>
                        <span><?= $escape((string)$uiText['remainingTime']) ?></span>
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
                    <div class="omo-pv-editor__timing-legend-item">
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

    <section class="omo-pv-editor__main">
        <section class="omo-pv-editor__panel omo-pv-editor__page-head">
            <div class="omo-pv-editor__page-title">
                <?php if ($canManagePvDocument): ?>
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
                            <select
                                class="generic-form-control omo-pv-editor__document-visibility-select"
                                aria-label="<?= $escape(omoDocumentsPvEditorT('documents.pv_editor.field.document_visibility')) ?>"
                                data-omo-pv-document-visibility
                            >
                                <?php foreach ($documentVisibilityOptions as $visibilityValue => $visibilityLabel): ?>
                                    <option value="<?= $escape($visibilityValue) ?>" <?= $documentVisibilityType === $visibilityValue ? ' selected' : '' ?>><?= $escape($visibilityLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="generic-action-button" data-omo-pv-document-meta-save disabled><?= $escape(omoDocumentsPvEditorT('documents.pv_editor.action.save')) ?></button>
                            <span class="omo-pv-editor__document-meta-status" data-omo-pv-document-meta-status></span>
                        </div>
                    </div>
                    <?php if ($eventTitle !== ''): ?>
                        <p><?= $escape($eventTitle) ?></p>
                    <?php endif; ?>
                <?php else: ?>
                    <h2 data-omo-pv-document-title-display><?= $escape($documentTitle) ?></h2>
                    <p data-omo-pv-document-description-display<?= $documentDescription === '' ? ' hidden' : '' ?>><?= $escape($documentDescription) ?></p>
                    <?php if ($eventTitle !== ''): ?>
                        <p><?= $escape($eventTitle) ?></p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="omo-pv-editor__page-side">
                <?php if ($eventSchedule !== '' || $eventLocation !== ''): ?>
                    <div class="omo-pv-editor__page-meta">
                        <?php if ($eventSchedule !== ''): ?>
                            <span class="omo-pv-editor__meta-pill"><?= $escape($eventSchedule) ?></span>
                        <?php endif; ?>
                        <?php if ($eventLocation !== ''): ?>
                            <span class="omo-pv-editor__meta-pill"><?= $escape($eventLocation) ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <div class="omo-pv-editor__stage-field">
                    <div class="omo-pv-editor__stage-flow" role="group" aria-label="<?= $escape((string)$uiText['stage']) ?>">
                        <?php foreach ($pvStageOptions as $stageValue => $stageLabel): ?>
                            <button
                                type="button"
                                class="omo-pv-editor__stage-option omo-pv-editor__stage-option--<?= $escape($stageValue) ?><?= $pvStage === $stageValue ? ' is-active' : '' ?>"
                                data-omo-pv-stage-option
                                data-omo-pv-stage-value="<?= $escape($stageValue) ?>"
                                aria-pressed="<?= $pvStage === $stageValue ? 'true' : 'false' ?>"
                                <?= $canManagePvStage ? '' : ' disabled' ?>
                            ><?= $escape($stageLabel) ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php if (!$canManagePvStage): ?>
                    <div class="omo-pv-editor__stage-help"><?= $escape((string)$uiText['stageReadonly']) ?></div>
                <?php endif; ?>
            </div>
            <div class="omo-pv-editor__secretary" data-omo-pv-secretary>
                <span class="omo-pv-editor__field-label"><?= $escape((string)$uiText['pvEditor']) ?></span>
                <span class="omo-pv-editor__secretary-name" data-omo-pv-secretary-name><?= $escape($pvEditorLabel !== '' ? $pvEditorLabel : (string)$uiText['pvEditorEmpty']) ?></span>
                <?php if ($isPvEditor): ?>
                    <span class="omo-pv-editor__secretary-state" data-omo-pv-secretary-state><?= $escape((string)$uiText['pvEditorActive']) ?></span>
                <?php endif; ?>
                <button type="button" class="generic-action-button generic-action-button--main omo-pv-editor__secretary-claim" data-omo-pv-claim-secretary<?= $canClaimPvEditor ? '' : ' hidden' ?>><?= $escape((string)$uiText['claimPvEditor']) ?></button>
                <?php if ($pvInvitationPopupUrl !== ''): ?>
                <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-pv-invitations-url="<?= $escape($pvInvitationPopupUrl) ?>" data-omo-pv-invitations-title="<?= $escape((string)$uiText['inviteTitle']) ?>"<?= $canManagePvInvitations ? '' : ' hidden' ?>><?= $escape((string)$uiText['invite']) ?></button>
                <?php endif; ?>
            </div>
            <?php if ($hasTeamApplication): ?>
            <div class="omo-pv-editor__attendance" data-omo-pv-attendance-root<?= is_array($attendancePayload) ? '' : ' hidden' ?>>
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
                            <label class="omo-pv-editor__attendance-item">
                                <input
                                    type="checkbox"
                                    data-omo-pv-attendance-toggle="<?= $escape((string)($attendanceEntry['identityKey'] ?? '')) ?>"
                                    <?= !$canManagePvDocument ? 'disabled' : '' ?>
                                    <?= !empty($attendanceEntry['isPresent']) ? 'checked' : '' ?>
                                >
                                <span class="omo-pv-editor__attendance-copy">
                                    <span class="omo-pv-editor__attendance-name"><?= $escape((string)($attendanceEntry['displayLabel'] ?? '')) ?></span>
                                    <?php if (trim((string)($attendanceEntry['secondaryLabel'] ?? '')) !== ''): ?>
                                        <span class="omo-pv-editor__attendance-meta"><?= $escape(trim((string)$attendanceEntry['secondaryLabel'])) ?></span>
                                    <?php endif; ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <p class="omo-pv-editor__attendance-empty" data-omo-pv-attendance-empty<?= is_array($attendancePayload) && count((array)($attendancePayload['entries'] ?? [])) > 0 ? ' hidden' : '' ?>>
                    <?= $escape((string)$uiText['attendanceEmpty']) ?>
                </p>
            </div>
            <?php endif; ?>
        </section>
        <div class="omo-pv-editor__points" data-omo-pv-editor-points>
            <?= count($pointCards) > 0 ? implode('', $pointCards) : '' ?>
        </div>
    </section>
</div>

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
    const resizer = root.querySelector('[data-omo-pv-editor-resizer]');
    const stageButtons = Array.from(root.querySelectorAll('[data-omo-pv-stage-option]'));
    const attendanceRoot = root.querySelector('[data-omo-pv-attendance-root]');
    const attendanceList = root.querySelector('[data-omo-pv-attendance-list]');
    const attendanceCount = root.querySelector('[data-omo-pv-attendance-count]');
    const attendanceEmpty = root.querySelector('[data-omo-pv-attendance-empty]');
    const secretaryName = root.querySelector('[data-omo-pv-secretary-name]');
    const claimSecretaryButton = root.querySelector('[data-omo-pv-claim-secretary]');
    const invitationsButton = root.querySelector('[data-omo-pv-invitations-url]');
    const documentTitleInput = root.querySelector('[data-omo-pv-document-title]');
    const documentDescriptionInput = root.querySelector('[data-omo-pv-document-description]');
    const documentVisibilitySelect = root.querySelector('[data-omo-pv-document-visibility]');
    const documentMetaSaveButton = root.querySelector('[data-omo-pv-document-meta-save]');
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
        'isPvEditor' => $isPvEditor,
        'canManagePvDocument' => $canManagePvDocument,
        'canClaimPvEditor' => $canClaimPvEditor,
        'isPvValidated' => $isPvValidated,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const initialAttendancePayload = <?= json_encode($attendancePayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const attendanceEnabled = <?= json_encode($hasTeamApplication) ?>;
    const eventStartAtIso = <?= json_encode($eventStartAtIso, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const eventEndAtIso = <?= json_encode($eventEndAtIso, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const saveLabel = <?= json_encode(omoDocumentsPvEditorT('documents.pv_editor.action.save'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const savingLabel = <?= json_encode(omoDocumentsPvEditorT('documents.pv_editor.action.saving'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const savedLabel = <?= json_encode(omoDocumentsPvEditorT('documents.pv_editor.state.saved'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const dirtyLabel = <?= json_encode(omoDocumentsPvEditorT('documents.pv_editor.state.dirty'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const unsavedCloseMessage = <?= json_encode(omoDocumentsPvEditorT('documents.pv_editor.warning.unsaved_close'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const validateIrreversibleMessage = <?= json_encode(omoDocumentsPvEditorT('documents.pv_editor.warning.validate_irreversible'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const notStartedValue = <?= json_encode((string)$uiText['notStartedValue'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const marginLegendLabel = <?= json_encode((string)$uiText['marginLegend'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const overrunLegendLabel = <?= json_encode((string)$uiText['overrunLegend'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const activeLockPointIds = new Set();
    const pendingLockPointIds = new Set();
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
    let syncPollTimer = null;
    let lockHeartbeatTimer = null;
    let allowNextExternalClose = false;

    function resizeDocumentDescriptionInput() {
        if (!(documentDescriptionInput instanceof HTMLTextAreaElement)) {
            return;
        }

        documentDescriptionInput.style.height = 'auto';
        documentDescriptionInput.style.height = Math.max(24, documentDescriptionInput.scrollHeight) + 'px';
    }

    function documentMetadataIsDirty() {
        if (
            !(documentTitleInput instanceof HTMLInputElement)
            || !(documentDescriptionInput instanceof HTMLTextAreaElement)
            || !(documentVisibilitySelect instanceof HTMLSelectElement)
        ) {
            return false;
        }

        return documentTitleInput.value.trim() !== String(currentDocumentPayload.title || '').trim()
            || documentDescriptionInput.value.trim() !== String(currentDocumentPayload.description || '').trim()
            || documentVisibilitySelect.value !== String(currentDocumentPayload.visibilityType || '');
    }

    function syncDocumentMetadataUi() {
        const isDirty = documentMetadataIsDirty();
        if (documentMetaSaveButton instanceof HTMLButtonElement) {
            documentMetaSaveButton.disabled = !isDirty;
        }
        if (documentMetaStatus instanceof Element) {
            documentMetaStatus.textContent = isDirty ? <?= json_encode(omoDocumentsPvEditorT('documents.pv_editor.state.metadata_dirty'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?> : '';
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
        const htmlFieldVersion = '20260711-sticky-toolbar';
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

        const hasItems = nav.querySelector('[data-omo-pv-point-nav-row]') !== null;
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

        const status = root.querySelector('[data-omo-pv-point-status="' + pointId + '"]');
        if (status) {
            if (isDirty) {
                status.textContent = dirtyLabel;
            } else if (String(status.textContent || '') === dirtyLabel) {
                status.textContent = '';
            }
        }

        const saveButton = root.querySelector('[data-omo-pv-point-save="' + pointId + '"]');
        if (saveButton) {
            saveButton.disabled = !isDirty || isSaving;
            saveButton.setAttribute('aria-disabled', (!isDirty || isSaving) ? 'true' : 'false');
            saveButton.classList.toggle('generic-action-button--main', isDirty);
            saveButton.classList.toggle('is-saving', isSaving);
            saveButton.textContent = isSaving ? savingLabel : saveLabel;
        }
    }

    function syncDirtyUi() {
        root.querySelectorAll('[data-omo-pv-point-card]').forEach(function (card) {
            const pointId = Number(card.getAttribute('data-omo-pv-point-card') || 0);
            if (Number.isInteger(pointId) && pointId > 0) {
                syncPointDirtyUi(pointId);
            }
        });
    }

    function markPointDirty(pointId, isDirty) {
        if (isDirty && isPointDirtySuppressed(pointId)) {
            return;
        }

        const card = root.querySelector('[data-omo-pv-point-card="' + pointId + '"]');
        if (card) {
            card.setAttribute('data-omo-pv-point-dirty', isDirty ? '1' : '0');
            if (!isDirty) {
                card.removeAttribute('data-omo-pv-point-saving');
            }
        }

        syncPointDirtyUi(pointId);
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
                Number(lock.userId || 0)
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
        stageButtons.forEach(function (button) {
            const isActive = String(button.getAttribute('data-omo-pv-stage-value') || '') === nextStage;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            button.disabled = !canManageStage;
        });
    }

    function syncPvEditorUi(documentPayload) {
        if (!documentPayload || typeof documentPayload !== 'object') {
            return;
        }

        if (secretaryName instanceof Element) {
            const label = String(documentPayload.pvEditorLabel || '').trim();
            secretaryName.textContent = label !== '' ? label : <?= json_encode((string)$uiText['pvEditorEmpty'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        }

        if (claimSecretaryButton instanceof HTMLButtonElement) {
            claimSecretaryButton.hidden = documentPayload.canClaimPvEditor !== true;
            claimSecretaryButton.disabled = documentPayload.canClaimPvEditor !== true;
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
            if (documentVisibilitySelect instanceof HTMLSelectElement && documentPayload.visibilityType) {
                documentVisibilitySelect.value = String(documentPayload.visibilityType);
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
        syncDocumentStageUi(currentDocumentPayload);
        syncPvEditorUi(currentDocumentPayload);
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
        const focusedEditor = captureFocusedPointEditor();
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
            restoreFocusedPointEditor(focusedEditor);
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
            checkbox.disabled = !(currentDocumentPayload && currentDocumentPayload.canManagePvDocument === true);
            checkbox.setAttribute('data-omo-pv-attendance-toggle', String(entry.identityKey));

            const copy = document.createElement('span');
            copy.className = 'omo-pv-editor__attendance-copy';

            const name = document.createElement('span');
            name.className = 'omo-pv-editor__attendance-name';
            name.textContent = String(entry.displayLabel || '');
            copy.appendChild(name);

            const secondaryLabel = String(entry.secondaryLabel || '').trim();
            if (secondaryLabel !== '') {
                const meta = document.createElement('span');
                meta.className = 'omo-pv-editor__attendance-meta';
                meta.textContent = secondaryLabel;
                copy.appendChild(meta);
            }

            label.appendChild(checkbox);
            label.appendChild(copy);
            attendanceList.appendChild(label);
        });

        restoreMainScrollAnchor(scrollAnchor);
        restoreFocusedPointEditor(focusedEditor);
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
        return Object.keys(currentPointPayloads).map(function (pointId) {
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
                field = window.omoSimpleHtmlField.mount(editorHost, {
                    value: String(sourceField.value || ''),
                    placeholder: <?= json_encode(omoDocumentsPvEditorT('documents.pv_editor.field.content'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                    onChange: function () {
                        if (isPointDirtySuppressed(pointId)) {
                            return;
                        }

                        ensurePointLock(pointId);
                        markPointDirty(pointId, true);
                    }
                });
            }, 220);

            editorHost.__omoPvPointMounted = true;
            editorHost.__omoPvPointField = field;

            if (titleField) {
                titleField.addEventListener('focus', function () {
                    ensurePointLock(pointId);
                });
                titleField.addEventListener('input', function () {
                    ensurePointLock(pointId);
                    markPointDirty(pointId, true);
                });
            }

            if (typeField) {
                typeField.addEventListener('focus', function () {
                    ensurePointLock(pointId);
                });
                typeField.addEventListener('change', function () {
                    ensurePointLock(pointId);
                    markPointDirty(pointId, true);
                });
            }

            if (durationField) {
                durationField.addEventListener('focus', function () {
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
                    ensurePointLock(pointId);
                });
                concernedHolonField.addEventListener('change', function () {
                    ensurePointLock(pointId);
                    markPointDirty(pointId, true);
                });
            }

            editorHost.addEventListener('focusin', function () {
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

        return root.querySelector('[data-omo-pv-point-save]:not(:disabled), [data-omo-pv-document-meta-save]:not(:disabled)') !== null;
    }

    function releaseActiveLocksWithBeacon() {
        if (activeLockPointIds.size === 0) {
            return;
        }

        Array.from(activeLockPointIds).forEach(function (pointId) {
            const formData = new FormData();
            formData.append('action', 'unlock_point');
            formData.append('document_id', String(documentId));
            formData.append('oid', String(organizationId));
            formData.append('editor_token', editorToken);
            formData.append('point_id', String(pointId));

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
        });
        activeLockPointIds.clear();
        pendingLockPointIds.clear();
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
        const temp = document.createElement('div');
        temp.innerHTML = String(pointPayload.cardHtml || '').trim();
        const nextCard = temp.firstElementChild;
        if (!(nextCard instanceof Element)) {
            return null;
        }

        const currentCard = root.querySelector('[data-omo-pv-point-card="' + pointId + '"]');
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

        const pointId = Number(pointPayload.id || 0);
        const navTemp = document.createElement('div');
        navTemp.innerHTML = String(pointPayload.navHtml || '').trim();
        const nextNav = navTemp.firstElementChild;
        if (!(nextNav instanceof Element)) {
            return null;
        }

        const currentNav = root.querySelector('[data-omo-pv-point-nav-row="' + pointId + '"]');
        if (currentNav && currentNav.parentNode) {
            currentNav.parentNode.replaceChild(nextNav, currentNav);
        } else {
            nav.appendChild(nextNav);
        }

        mergeKnownPointSignature(pointPayload);
        mergeCurrentPointPayload(pointPayload);
        renderTimingSummary();
        return nextNav;
    }

    function isPointCardProtectedFromRemoteRefresh(card) {
        if (!(card instanceof Element)) {
            return false;
        }

        return card.getAttribute('data-omo-pv-point-dirty') === '1'
            || card.getAttribute('data-omo-pv-point-saving') === '1'
            || card.contains(document.activeElement);
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

    function captureFocusedPointEditor() {
        const activeElement = document.activeElement;
        if (!(activeElement instanceof Element) || !pointsContainer.contains(activeElement)) {
            return null;
        }

        const pointCard = activeElement.closest('[data-omo-pv-point-card]');
        if (!(pointCard instanceof Element)) {
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

    function restoreFocusedPointEditor(state) {
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
        if (!Array.isArray(pointIds) || !nav) {
            return;
        }

        const currentPointIds = collectNavPointIds();
        if (currentPointIds.join('|') === pointIds.join('|')) {
            return;
        }

        pointIds.forEach(function (pointId) {
            const row = nav.querySelector('[data-omo-pv-point-nav-row="' + pointId + '"]');
            if (row) {
                nav.appendChild(row);
            }
        });
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

        const row = pointId > 0 ? nav.querySelector('[data-omo-pv-point-nav-row="' + pointId + '"]') : null;
        if (!(row instanceof Element)) {
            return false;
        }

        const sibling = direction === 'up'
            ? row.previousElementSibling
            : row.nextElementSibling;
        if (!(sibling instanceof Element) || !sibling.matches('[data-omo-pv-point-nav-row]')) {
            return false;
        }

        if (direction === 'up') {
            sibling.insertAdjacentElement('beforebegin', row);
        } else {
            sibling.insertAdjacentElement('afterend', row);
        }

        const orderedPointIds = collectNavPointIds();
        applyPointOrderToCards(orderedPointIds);
        persistPointOrder(orderedPointIds);
        return true;
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

    function renderPointCollection(pointPayloads) {
        if (!Array.isArray(pointPayloads)) {
            return;
        }

        const scrollAnchor = captureMainScrollAnchor();
        const focusedEditor = captureFocusedPointEditor();
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

            if (!pointHasRemoteChange(pointPayload)) {
                return;
            }

            if (isPointCardProtectedFromRemoteRefresh(currentCard)) {
                return;
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

        applyPointOrderToNav(nextPointIds);
        applyPointOrderToCards(nextPointIds);
        syncEmptyNavState();
        renderTimingSummary();
        restoreMainScrollAnchor(scrollAnchor);
        restoreFocusedPointEditor(focusedEditor);
    }

    function ensurePointLock(pointId) {
        if (!Number.isInteger(pointId) || pointId <= 0 || !editorToken) {
            return Promise.resolve(null);
        }

        if (activeLockPointIds.has(pointId) || pendingLockPointIds.has(pointId)) {
            return Promise.resolve(null);
        }

        pendingLockPointIds.add(pointId);
        return postPointAction('lock_point', pointId)
            .then(function (payload) {
                pendingLockPointIds.delete(pointId);
                activeLockPointIds.add(pointId);
                if (payload && payload.point) {
                    mergeKnownPointSignature(payload.point);
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
        if (!Number.isInteger(pointId) || pointId <= 0 || !activeLockPointIds.has(pointId)) {
            return Promise.resolve(null);
        }

        activeLockPointIds.delete(pointId);
        return postPointAction('unlock_point', pointId)
            .then(function (payload) {
                if (payload && payload.point) {
                    replacePointHtml(payload.point);
                }
                return payload;
            })
            .catch(function () {
                return null;
            });
    }

    function savePoint(pointId, triggerButton) {
        const card = root.querySelector('[data-omo-pv-point-card="' + pointId + '"]');
        if (!card) {
            return;
        }

        const titleField = card.querySelector('[data-omo-pv-point-title="' + pointId + '"]');
        const typeField = card.querySelector('[data-omo-pv-point-type="' + pointId + '"]');
        const durationField = card.querySelector('[data-omo-pv-point-duration="' + pointId + '"]');
        const authorField = card.querySelector('[data-omo-pv-point-author="' + pointId + '"]');
        const concernedHolonField = card.querySelector('[data-omo-pv-point-concerned-holon="' + pointId + '"]');
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
        formData.append('content', htmlField && typeof htmlField.getValue === 'function' ? String(htmlField.getValue() || '') : '');

        if (triggerButton) {
            card.setAttribute('data-omo-pv-point-saving', '1');
            syncPointDirtyUi(pointId);
        }
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
                    if (!response.ok || !payload || payload.status !== true || !payload.point) {
                        throw payload || new Error('save_failed');
                    }

                    return payload;
                });
            })
            .then(function (payload) {

                const nextCard = replacePointHtml(payload.point);
                activeLockPointIds.delete(pointId);
                markPointDirty(pointId, false);
                const nextStatus = nextCard ? nextCard.querySelector('[data-omo-pv-point-status="' + pointId + '"]') : null;
                if (nextStatus) {
                    nextStatus.textContent = payload.message || savedLabel;
                }
            })
            .catch(function (error) {
                if (error && error.point) {
                    replacePointHtml(error.point);
                }
                markPointDirty(pointId, true);
                if (statusNode) {
                    statusNode.textContent = error && error.message ? String(error.message) : (error && error.text ? String(error.text) : 'Erreur');
                }
            })
            .finally(function () {
                const currentCard = root.querySelector('[data-omo-pv-point-card="' + pointId + '"]');
                if (currentCard) {
                    currentCard.removeAttribute('data-omo-pv-point-saving');
                    syncPointDirtyUi(pointId);
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

    function persistPointOrder(pointIds) {
        const formData = new FormData();
        formData.append('action', 'reorder_points');
        formData.append('document_id', String(documentId));
        formData.append('oid', String(organizationId));
        formData.append('editor_token', editorToken);
        pointIds.forEach(function (pointId) {
            formData.append('point_ids[]', String(pointId));
        });

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

    function saveDocumentMetadata() {
        if (
            !(documentTitleInput instanceof HTMLInputElement)
            || !(documentDescriptionInput instanceof HTMLTextAreaElement)
            || !(documentVisibilitySelect instanceof HTMLSelectElement)
            || !(documentMetaSaveButton instanceof HTMLButtonElement)
            || documentMetaSaveButton.disabled
        ) {
            return;
        }

        documentMetaSaveButton.disabled = true;
        if (documentMetaStatus instanceof Element) {
            documentMetaStatus.textContent = savingLabel;
        }

        postPointAction('update_document_metadata', 0, {
            title: documentTitleInput.value.trim(),
            description: documentDescriptionInput.value.trim(),
            visibility_type: documentVisibilitySelect.value
        })
            .then(function (payload) {
                mergeCurrentDocumentPayload(payload && payload.document ? payload.document : {});
                if (documentMetaStatus instanceof Element) {
                    documentMetaStatus.textContent = savedLabel;
                }
            })
            .catch(function (payload) {
                if (documentMetaStatus instanceof Element) {
                    documentMetaStatus.textContent = String(payload && payload.message ? payload.message : 'Impossible de sauver le PV.');
                }
            })
            .finally(function () {
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
        const documentMetaSave = event.target.closest('[data-omo-pv-document-meta-save]');
        if (documentMetaSave && root.contains(documentMetaSave)) {
            event.preventDefault();
            saveDocumentMetadata();
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

        const navButton = event.target.closest('[data-omo-pv-point-nav-target]');
        if (navButton && root.contains(navButton)) {
            const pointId = Number(navButton.getAttribute('data-omo-pv-point-nav-target') || 0);
            const target = pointId > 0 ? root.querySelector('#omo-pv-editor-point-' + pointId) : null;
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            return;
        }

        const saveButton = event.target.closest('[data-omo-pv-point-save]');
        if (saveButton && root.contains(saveButton)) {
            const pointId = Number(saveButton.getAttribute('data-omo-pv-point-save') || 0);
            if (pointId > 0) {
                savePoint(pointId, saveButton);
            }
            return;
        }

        const addPointButton = event.target.closest('[data-omo-pv-editor-add-point]');
        if (addPointButton && root.contains(addPointButton)) {
            addPoint();
            return;
        }

        const claimSecretaryButton = event.target.closest('[data-omo-pv-claim-secretary]');
        if (claimSecretaryButton && root.contains(claimSecretaryButton)) {
            claimSecretaryButton.disabled = true;
            postPointAction('claim_pv_editor', 0, {})
                .then(function (payload) {
                    mergeCurrentDocumentPayload(payload && payload.document ? payload.document : {});
                    if (payload && Array.isArray(payload.points)) {
                        renderPointCollection(payload.points);
                    }
                })
                .finally(function () {
                    if (claimSecretaryButton.hidden === false) {
                        claimSecretaryButton.disabled = false;
                    }
                });
        }
    });

    root.addEventListener('change', function (event) {
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
        const dropMarker = document.createElement('div');
        dropMarker.className = 'omo-pv-editor__nav-drop-marker';
        dropMarker.setAttribute('data-omo-pv-drop-marker', '1');
        dropMarker.setAttribute('aria-hidden', 'true');

        const clearDragState = function () {
            nav.querySelectorAll('.is-drop-before, .is-drop-after, .is-dragging').forEach(function (node) {
                node.classList.remove('is-drop-before', 'is-drop-after', 'is-dragging');
            });
            if (dropMarker.parentNode) {
                dropMarker.remove();
            }
        };

        nav.addEventListener('dragstart', function (event) {
            const handle = event.target.closest('[data-omo-pv-point-drag-handle]');
            if (!handle) {
                event.preventDefault();
                return;
            }

            draggedPointId = Number(handle.getAttribute('data-omo-pv-point-drag-handle') || 0);
            const row = draggedPointId > 0 ? nav.querySelector('[data-omo-pv-point-nav-row="' + draggedPointId + '"]') : null;
            if (!(row instanceof Element)) {
                draggedPointId = 0;
                event.preventDefault();
                return;
            }

            row.classList.add('is-dragging');
            if (event.dataTransfer) {
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', String(draggedPointId));
            }
        });

        nav.addEventListener('dragover', function (event) {
            const targetRow = event.target.closest('[data-omo-pv-point-nav-row]');
            if (!targetRow || !draggedPointId) {
                if (draggedPointId && dropMarker.parentNode) {
                    event.preventDefault();
                    if (event.dataTransfer) {
                        event.dataTransfer.dropEffect = 'move';
                    }
                }
                return;
            }

            event.preventDefault();
            if (event.dataTransfer) {
                event.dataTransfer.dropEffect = 'move';
            }
            nav.querySelectorAll('.is-drop-before, .is-drop-after').forEach(function (node) {
                node.classList.remove('is-drop-before', 'is-drop-after');
            });

            const targetRect = targetRow.getBoundingClientRect();
            const dropMode = event.clientY > (targetRect.top + targetRect.height / 2) ? 'after' : 'before';
            targetRow.classList.add(dropMode === 'after' ? 'is-drop-after' : 'is-drop-before');
            if (dropMode === 'after') {
                targetRow.insertAdjacentElement('afterend', dropMarker);
            } else {
                targetRow.insertAdjacentElement('beforebegin', dropMarker);
            }
        });

        nav.addEventListener('drop', function (event) {
            const sourceRow = draggedPointId > 0 ? nav.querySelector('[data-omo-pv-point-nav-row="' + draggedPointId + '"]') : null;
            if (!(sourceRow instanceof Element) || !dropMarker.parentNode) {
                clearDragState();
                draggedPointId = 0;
                return;
            }

            event.preventDefault();

            dropMarker.insertAdjacentElement('beforebegin', sourceRow);
            dropMarker.remove();

            const orderedPointIds = collectNavPointIds();
            applyPointOrderToCards(orderedPointIds);
            persistPointOrder(orderedPointIds);
            clearDragState();
            draggedPointId = 0;
        });

        nav.addEventListener('dragend', function () {
            clearDragState();
            draggedPointId = 0;
        });

        nav.addEventListener('dragleave', function (event) {
            const relatedTarget = event.relatedTarget;
            if (relatedTarget instanceof Node && nav.contains(relatedTarget)) {
                return;
            }

            nav.querySelectorAll('.is-drop-before, .is-drop-after').forEach(function (node) {
                node.classList.remove('is-drop-before', 'is-drop-after');
            });
            if (dropMarker.parentNode) {
                dropMarker.remove();
            }
        });
    })();

    function startSyncPolling() {
        if (syncPollTimer !== null) {
            window.clearInterval(syncPollTimer);
        }

        syncPollTimer = window.setInterval(function () {
            renderTimingSummary();
            if (!document.hidden) {
                syncEditorFromServer();
            }
        }, 5000);
    }

    function syncEditorFromServer() {
        return postPointAction('poll_updates', 0)
            .then(function (payload) {
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
            });
    }

    function startLockHeartbeat() {
        if (lockHeartbeatTimer !== null) {
            window.clearInterval(lockHeartbeatTimer);
        }

        lockHeartbeatTimer = window.setInterval(function () {
            if (document.hidden || activeLockPointIds.size === 0) {
                return;
            }

            Array.from(activeLockPointIds).forEach(function (pointId) {
                postPointAction('lock_point', pointId).catch(function () {
                    // Polling will reconcile visible lock state if needed.
                });
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

    mountEditableCards(root);
    [documentTitleInput, documentDescriptionInput].forEach(function (input) {
        if (!(input instanceof Element)) {
            return;
        }
        input.addEventListener('input', function () {
            resizeDocumentDescriptionInput();
            syncDocumentMetadataUi();
        });
    });
    if (documentVisibilitySelect instanceof HTMLSelectElement) {
        documentVisibilitySelect.addEventListener('change', syncDocumentMetadataUi);
    }
    if (invitationsButton instanceof HTMLButtonElement) {
        invitationsButton.addEventListener('click', function () {
            const invitationUrl = String(invitationsButton.getAttribute('data-omo-pv-invitations-url') || '').trim();
            const invitationTitle = String(invitationsButton.getAttribute('data-omo-pv-invitations-title') || '').trim();
            if (invitationUrl && typeof window.commonTopbarOpenModal === 'function') {
                window.commonTopbarOpenModal(invitationTitle || 'Invites', invitationUrl, 'fetch');
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
    renderTimingSummary();
    startSyncPolling();
    startLockHeartbeat();
})();
</script>
