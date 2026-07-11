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
$hasUpcomingAssociatedEvent = $hasAssociatedEvent && $event->isUpcoming();
$canManagePvStage = $document->canManagePvStage($organizationId, $currentUserId);
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

$points = new \dbObject\ArrayDocumentPvPoint();
$points->loadForDocument((int)$document->getId(), true);
$concernedHolonOptions = \dbObject\DocumentPvPoint::buildConcernedHolonOptionsForDocument($document, $currentUserId);
$pointCards = [];
$pointNavItems = [];
$pointPayloads = [];

foreach ($points as $point) {
    if (!($point instanceof \dbObject\DocumentPvPoint) || (int)$point->getId() <= 0) {
        continue;
    }

    $pointData = omoDocumentsPvEditorAttachConcernedHolonOptions(
        $point->buildEditorData($organizationId, $currentUserId, $editorToken),
        $concernedHolonOptions
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

    .omo-pv-editor__page-meta {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 8px;
    }

    .omo-pv-editor__page-side {
        display: grid;
        gap: 10px;
        justify-items: end;
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
        width: min(100%, 260px);
        justify-items: stretch;
    }

    .omo-pv-editor__stage-field .generic-form-control {
        min-width: 0;
    }

    .omo-pv-editor__stage-help {
        color: var(--color-text-light, #64748b);
        font-size: 0.8rem;
        text-align: right;
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
    .omo-pv-editor__type-switch-button[aria-pressed="true"] .omo-pv-editor__point-type-icon,
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
                <button type="button" class="generic-action-button generic-action-button--main" data-omo-pv-editor-add-point><?= $escape(omoDocumentsPvEditorT('documents.pv_editor.action.add_point')) ?></button>
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
                <h2><?= $escape($documentTitle) ?></h2>
                <?php if ($eventTitle !== ''): ?>
                    <p><?= $escape($eventTitle) ?></p>
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
                <label class="omo-pv-editor__stage-field">
                    <span class="omo-pv-editor__field-label"><?= $escape((string)$uiText['stage']) ?></span>
                    <select
                        class="generic-form-control"
                        data-omo-pv-stage-select
                        <?= $canManagePvStage ? '' : 'disabled' ?>
                    >
                        <?php foreach ($pvStageOptions as $stageValue => $stageLabel): ?>
                            <option value="<?= $escape($stageValue) ?>" <?= $pvStage === $stageValue ? ' selected' : '' ?>><?= $escape($stageLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <?php if (!$canManagePvStage): ?>
                    <div class="omo-pv-editor__stage-help"><?= $escape((string)$uiText['stageReadonly']) ?></div>
                <?php endif; ?>
            </div>
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
    const addButton = root.querySelector('[data-omo-pv-editor-add-point]');
    const resizer = root.querySelector('[data-omo-pv-editor-resizer]');
    const stageSelect = root.querySelector('[data-omo-pv-stage-select]');
    const initialPointPayloads = <?= json_encode($pointPayloads, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const initialDocumentPayload = <?= json_encode([
        'pvStage' => $pvStage,
        'pvStageLabel' => (string)($pvStageOptions[$pvStage] ?? ''),
        'canManagePvStage' => $canManagePvStage,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const eventStartAtIso = <?= json_encode($eventStartAtIso, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const eventEndAtIso = <?= json_encode($eventEndAtIso, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const saveLabel = <?= json_encode(omoDocumentsPvEditorT('documents.pv_editor.action.save'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const savingLabel = <?= json_encode(omoDocumentsPvEditorT('documents.pv_editor.action.saving'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const savedLabel = <?= json_encode(omoDocumentsPvEditorT('documents.pv_editor.state.saved'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const dirtyLabel = <?= json_encode(omoDocumentsPvEditorT('documents.pv_editor.state.dirty'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const unsavedCloseMessage = <?= json_encode(omoDocumentsPvEditorT('documents.pv_editor.warning.unsaved_close'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
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
    let syncPollTimer = null;
    let lockHeartbeatTimer = null;
    let allowNextExternalClose = false;

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

        pointPayloads.forEach(function (pointPayload, index) {
            if (!pointPayload || !pointPayload.id) {
                return;
            }

            const lock = pointPayload.lock && typeof pointPayload.lock === 'object' ? pointPayload.lock : {};
            const isLocalLock = !!lock.isOwnedByCurrentSession;
            signatures[String(pointPayload.id)] = [
                Number(pointPayload.position || index + 1),
                Number(pointPayload.lastModifiedAtTimestamp || 0),
                Number(pointPayload.lastModifiedByUserId || 0),
                lock.isActive ? 1 : 0,
                Number(lock.userId || 0),
                isLocalLock ? 0 : Number(lock.timestamp || 0),
                isLocalLock ? '' : String(lock.dateIso || '')
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
        if (!(stageSelect instanceof HTMLSelectElement) || !documentPayload || typeof documentPayload !== 'object') {
            return;
        }

        const nextStage = String(documentPayload.pvStage || '').trim();
        if (nextStage !== '' && stageSelect.value !== nextStage) {
            stageSelect.value = nextStage;
        }
    }

    function mergeCurrentDocumentPayload(documentPayload) {
        if (!documentPayload || typeof documentPayload !== 'object') {
            return;
        }

        currentDocumentPayload = Object.assign({}, currentDocumentPayload, documentPayload);
        syncDocumentStageUi(currentDocumentPayload);
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

        return root.querySelector('[data-omo-pv-point-save]:not(:disabled)') !== null;
    }

    function releaseActiveLocksWithBeacon() {
        if (!navigator.sendBeacon || activeLockPointIds.size === 0) {
            return;
        }

        Array.from(activeLockPointIds).forEach(function (pointId) {
            const formData = new FormData();
            formData.append('action', 'unlock_point');
            formData.append('document_id', String(documentId));
            formData.append('oid', String(organizationId));
            formData.append('editor_token', editorToken);
            formData.append('point_id', String(pointId));
            navigator.sendBeacon(actionUrl, formData);
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
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
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

        pointIds.forEach(function (pointId) {
            const card = pointsContainer.querySelector('[data-omo-pv-point-card="' + pointId + '"]');
            if (card) {
                pointsContainer.appendChild(card);
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

    function renderPointCollection(pointPayloads, drafts) {
        if (!Array.isArray(pointPayloads)) {
            return;
        }

        nav.innerHTML = '';
        pointsContainer.innerHTML = '';

        pointPayloads.forEach(function (pointPayload) {
            const navTemp = document.createElement('div');
            navTemp.innerHTML = String(pointPayload.navHtml || '').trim();
            if (navTemp.firstElementChild instanceof Element) {
                nav.appendChild(navTemp.firstElementChild);
            }

            const cardTemp = document.createElement('div');
            cardTemp.innerHTML = String(pointPayload.cardHtml || '').trim();
            if (cardTemp.firstElementChild instanceof Element) {
                pointsContainer.appendChild(cardTemp.firstElementChild);
            }
        });

        mountEditableCards(root);
        restoreDraftState(drafts);
        syncEmptyNavState();
        updateKnownPointSignatures(pointPayloads);
        syncCurrentPointPayloads(pointPayloads);
        renderTimingSummary();
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
                    replacePointNavHtml(payload.point);
                    syncPointHandledBadge(pointId, payload.point);
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

    function persistPointOrder(pointIds) {
        const drafts = captureDraftState();
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

                renderPointCollection(payload.points, drafts);
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

    root.addEventListener('click', function (event) {
        const typeButton = event.target.closest('[data-omo-pv-point-type-option]');
        if (typeButton && root.contains(typeButton)) {
            event.preventDefault();
            event.stopPropagation();

            const pointId = Number(typeButton.getAttribute('data-omo-pv-point-type-option') || 0);
            const nextType = String(typeButton.getAttribute('data-omo-pv-point-type-value') || '').trim();
            const card = pointId > 0 ? root.querySelector('[data-omo-pv-point-card="' + pointId + '"]') : null;
            const typeField = card ? card.querySelector('[data-omo-pv-point-type="' + pointId + '"]') : null;
            if (card && typeField && nextType !== '') {
                typeField.value = nextType;
                card.querySelectorAll('[data-omo-pv-point-type-option="' + pointId + '"]').forEach(function (button) {
                    const isActive = String(button.getAttribute('data-omo-pv-point-type-value') || '') === nextType;
                    button.classList.toggle('is-active', isActive);
                    button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                });
                typeField.dispatchEvent(new Event('change', { bubbles: true }));
            }
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
        }
    });

    root.addEventListener('change', function (event) {
        if (stageSelect && event.target === stageSelect) {
            const previousStage = String(currentDocumentPayload && currentDocumentPayload.pvStage ? currentDocumentPayload.pvStage : '').trim() || stageSelect.value;
            const nextStage = String(stageSelect.value || '').trim();
            stageSelect.disabled = true;

            postPointAction('update_stage', 0, { pv_stage: nextStage })
                .then(function (payload) {
                    mergeCurrentDocumentPayload(payload && payload.document ? payload.document : { pvStage: nextStage });
                })
                .catch(function () {
                    stageSelect.value = previousStage;
                })
                .finally(function () {
                    if (currentDocumentPayload && currentDocumentPayload.canManagePvStage === false) {
                        stageSelect.disabled = true;
                        return;
                    }

                    stageSelect.disabled = false;
                });
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
            if (document.hidden) {
                return;
            }

            postPointAction('poll_updates', 0)
                .then(function (payload) {
                    mergeCurrentDocumentPayload(payload && payload.document ? payload.document : null);
                    if (!payload || !Array.isArray(payload.points) || !pointCollectionHasRemoteChanges(payload.points)) {
                        return;
                    }

                    const drafts = captureDraftState();
                    renderPointCollection(payload.points, drafts);
                })
                .catch(function () {
                    // Silent polling failure: keep the editor usable locally.
                });
        }, 5000);
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

    mountEditableCards(root);
    syncEmptyNavState();
    mergeCurrentDocumentPayload(initialDocumentPayload);
    updateKnownPointSignatures(initialPointPayloads);
    syncCurrentPointPayloads(initialPointPayloads);
    renderTimingSummary();
    startSyncPolling();
    startLockHeartbeat();
})();
</script>
