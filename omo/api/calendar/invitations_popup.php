<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/invitations_shared.php';
require_once dirname(__DIR__, 3) . '/common/notification_center.php';

use dbObject\DbObject;
use dbObject\Document;
use dbObject\DocumentInvitation;
use dbObject\Event;
use dbObject\EventInvitation;
use dbObject\Holon;
use dbObject\Organization;

$sourceLang = array_merge([
    'calendar.invitations.js_error' => [
        'text' => 'Une erreur est survenue.',
        'context' => 'Fallback error message shown by the event invitation popup JavaScript.',
    ],
    'calendar.invitations.js_request_error' => [
        'text' => "Impossible d'enregistrer ces invités pour le moment.",
        'context' => 'Network error shown by the event invitation popup JavaScript.',
    ],
], omoCalendarInvitationSourceLang());

$lang = omoLoadTranslationBundle('omo_calendar_invitations_popup', $sourceLang);

function omoCalendarInvitationsPopupT($key, array $variables = [])
{
    global $lang, $sourceLang;

    return t($key, $variables, $lang, $sourceLang);
}

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_REQUEST['oid'] ?? 0));
$currentHolonId = isset($_REQUEST['cid']) && is_numeric($_REQUEST['cid']) ? (int)$_REQUEST['cid'] : 0;
$eventId = isset($_REQUEST['id']) && is_numeric($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
$documentId = isset($_REQUEST['document_id']) && is_numeric($_REQUEST['document_id']) ? (int)$_REQUEST['document_id'] : 0;
$currentUserId = function_exists('commonGetCurrentUserId') ? (int)commonGetCurrentUserId() : 0;

if ($organizationId <= 0 || ($eventId <= 0 && $documentId <= 0) || $currentUserId <= 0) {
    http_response_code(403);
    ?>
    <div class="omo-calendar-invitations-popup__empty"><?= omoApiEscape(omoCalendarInvitationsPopupT('calendar.invitations.empty_denied')) ?></div>
    <?php
    exit;
}

$organization = new Organization();
$event = new Event();
$document = new Document();
$resource = null;
$invitationClass = EventInvitation::class;
$resourceField = 'IDevent';
if (!$organization->load($organizationId) || !$organization->canViewDetail()) {
    http_response_code(403);
    ?>
    <div class="omo-calendar-invitations-popup__empty"><?= omoApiEscape(omoCalendarInvitationsPopupT('calendar.invitations.empty_denied')) ?></div>
    <?php
    exit;
}

$pvDocumentId = 0;
$canEditInvitations = false;
if ($documentId > 0) {
    if (
        !$document->load($documentId)
        || (int)$document->get('IDorganization') !== $organizationId
        || !$document->isPvDocument()
        || $document->getPvStage() !== Document::PV_STAGE_PREPARATION
        || !$document->canUserManagePvDocument($currentUserId)
    ) {
        http_response_code(403);
        ?><div class="omo-calendar-invitations-popup__empty"><?= omoApiEscape(omoCalendarInvitationsPopupT('calendar.invitations.empty_denied')) ?></div><?php
        exit;
    }
    $resource = $document;
    $canEditInvitations = true;
    $pvDocumentId = (int)$document->getId();
    $invitationClass = DocumentInvitation::class;
    $resourceField = 'IDdocument';
} else {
    if (!$event->load($eventId) || (int)$event->get('IDorganization') !== $organizationId) {
        http_response_code(403);
        ?><div class="omo-calendar-invitations-popup__empty"><?= omoApiEscape(omoCalendarInvitationsPopupT('calendar.invitations.empty_denied')) ?></div><?php
        exit;
    }
    $resource = $event;
    $canEditInvitations = (int)$event->get('IDuser') === $currentUserId;
    foreach ($event->getAssociatedDocuments() as $associatedDocument) {
        if (!($associatedDocument instanceof Document) || !$associatedDocument->isPvDocument() || $associatedDocument->getPvStage() !== Document::PV_STAGE_PREPARATION || !$associatedDocument->canUserManagePvDocument($currentUserId)) {
            continue;
        }
        $canEditInvitations = true;
        $pvDocumentId = (int)$associatedDocument->getId();
        break;
    }
}

if ($resource === null) {
    http_response_code(403);
    ?><div class="omo-calendar-invitations-popup__empty"><?= omoApiEscape(omoCalendarInvitationsPopupT('calendar.invitations.empty_denied')) ?></div><?php
    exit;
}

if ($documentId > 0) {
    $canEditInvitations = true;
}

if (!$canEditInvitations) {
    http_response_code(403);
    ?>
    <div class="omo-calendar-invitations-popup__empty"><?= omoApiEscape(omoCalendarInvitationsPopupT('calendar.invitations.empty_denied')) ?></div>
    <?php
    exit;
}

$isPvEditorRequest = !empty($_REQUEST['pv_editor']);
if ($isPvEditorRequest && $pvDocumentId <= 0) {
    http_response_code(403);
    ?>
    <div class="omo-calendar-invitations-popup__empty"><?= omoApiEscape(omoCalendarInvitationsPopupT('calendar.invitations.empty_denied')) ?></div>
    <?php
    exit;
}

$isPvEditorContext = $isPvEditorRequest && $pvDocumentId > 0;

$effectiveHolon = null;
if ($currentHolonId > 0) {
    $candidateHolon = new Holon();
    if ($candidateHolon->load($currentHolonId) && $organization->containsHolon($candidateHolon)) {
        $effectiveHolon = $candidateHolon;
    }
}

$eventHolonId = (int)$resource->get('IDholon');
if (!($effectiveHolon instanceof Holon) && $eventHolonId > 0) {
    $candidateHolon = new Holon();
    if ($candidateHolon->load($eventHolonId) && $organization->containsHolon($candidateHolon)) {
        $effectiveHolon = $candidateHolon;
    }
}

$targetHolonId = $effectiveHolon instanceof Holon ? (int)$effectiveHolon->getId() : $eventHolonId;
$editorState = omoCalendarBuildInvitationEditorState(
    $resource,
    $organization,
    $organizationId,
    $effectiveHolon,
    $targetHolonId,
    $targetHolonId,
    true
);

$popupActionQuery = ['oid' => $organizationId];
if ($targetHolonId > 0) {
    $popupActionQuery['cid'] = $targetHolonId;
}
if ($documentId > 0) {
    $popupActionQuery['document_id'] = $documentId;
} else {
    $popupActionQuery['id'] = $eventId;
}
if ($isPvEditorRequest) {
    $popupActionQuery['pv_editor'] = 1;
}
$popupActionUrl = '/omo/api/calendar/invitations_popup.php?' . http_build_query($popupActionQuery);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=UTF-8');

    $selectedHolonIds = array_values(array_unique(array_filter(array_map('intval', $_POST['holon_ids'] ?? []), static function ($holonId) {
        return $holonId > 0;
    })));
    $selectedUserIds = array_values(array_unique(array_filter(array_map('intval', $_POST['user_ids'] ?? []), static function ($userId) {
        return $userId > 0;
    })));
    $selectedEmails = omoCalendarInvitationParseEmails($_POST['emails'] ?? '');

    $pdo = DbObject::getPdo();
    if (!$pdo) {
        echo json_encode([
            'status' => false,
            'message' => omoCalendarInvitationsPopupT('calendar.invitations.db_error'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $applyResult = omoCalendarApplyInvitationSelections(
            $resource,
            $organization,
            $organizationId,
            $selectedHolonIds,
            $selectedUserIds,
            $selectedEmails,
            $invitationClass,
            $resourceField
        );
        if (empty($applyResult['status'])) {
            throw new InvalidArgumentException(trim((string)($applyResult['message'] ?? omoCalendarInvitationsPopupT('calendar.invitations.save_error'))));
        }

        $pdo->commit();
    } catch (InvalidArgumentException $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        echo json_encode([
            'status' => false,
            'message' => trim((string)$exception->getMessage()) !== ''
                ? trim((string)$exception->getMessage())
                : omoCalendarInvitationsPopupT('calendar.invitations.save_error'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        echo json_encode([
            'status' => false,
            'message' => omoCalendarInvitationsPopupT('calendar.invitations.save_error'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($eventId > 0 && $resource instanceof Event) {
        try {
            notificationCenterDispatchEventInvitation($resource, $currentUserId);
        } catch (Throwable $exception) {
            error_log('OMO calendar invitation notification dispatch failed: ' . $exception->getMessage());
        }
    }

    $detailUrl = '';
    if ($eventId > 0) {
        $detailUrl = '/omo/api/calendar/detail.php?oid=' . rawurlencode((string)$organizationId) . '&id=' . rawurlencode((string)$eventId);
        if ($targetHolonId > 0) {
            $detailUrl .= '&cid=' . rawurlencode((string)$targetHolonId);
        }
    }

    echo json_encode([
        'status' => true,
        'message' => omoCalendarInvitationsPopupT('calendar.invitations.updated'),
        'detailUrl' => $detailUrl,
        'eventId' => $eventId,
        'pvEditorContext' => $isPvEditorContext,
        'pvDocumentId' => $pvDocumentId,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
?>
<style>
.omo-calendar-invitations-editor,
.omo-calendar-invitations-editor__tab-panel,
.omo-calendar-invitations-editor__checklist,
.omo-calendar-invitations-editor__member-list,
.omo-calendar-invitations-editor__check-meta,
.omo-calendar-invitations-editor__tree-node,
.omo-calendar-invitations-editor__tree-children {
    display: grid;
    gap: 8px;
}

.omo-calendar-invitations-editor [hidden] {
    display: none !important;
}

.omo-calendar-invitations-editor__tabs {
    --generic-tabs-panel-padding-block: 14px;
    --generic-tabs-panel-padding-inline: 14px;
}

.omo-calendar-invitations-editor__filter {
    width: 100%;
}

.omo-calendar-invitations-editor__empty {
    font-style: italic;
}

.omo-calendar-invitations-editor__tree-row,
.omo-calendar-invitations-editor__check {
    display: flex;
    gap: 10px;
    align-items: flex-start;
}

.omo-calendar-invitations-editor__tree-toggle,
.omo-calendar-invitations-editor__tree-spacer {
    width: 28px;
    min-width: 28px;
    height: 28px;
    margin-top: 2px;
}

.omo-calendar-invitations-editor__tree-toggle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 0;
    border-radius: 999px;
    background: rgba(148, 163, 184, 0.12);
    color: inherit;
    cursor: pointer;
}

.omo-calendar-invitations-editor__tree-toggle span {
    display: inline-block;
    transition: transform 0.18s ease;
}

.omo-calendar-invitations-editor__tree-toggle[aria-expanded="false"] span {
    transform: rotate(-90deg);
}

.omo-calendar-invitations-editor__tree-children {
    margin-left: 18px;
    padding-left: 14px;
    border-left: 1px solid var(--topbar-panel-border, #dbe3ef);
}

.omo-calendar-invitations-editor__check-type,
.omo-calendar-invitations-editor__member-email {
    color: var(--color-text-light, #64748b);
    font-size: 0.9rem;
}

.omo-calendar-invitations-editor__textarea {
    min-height: 120px;
}

.omo-calendar-invitations-popup__empty {
    padding: 18px;
    color: var(--color-text, #1f2937);
}

</style>

<form
    id="omoCalendarInvitationsPopupForm"
    class="omo-calendar-invitations-popup generic-stack generic-stack--flush"
    action="<?= omoApiEscape($popupActionUrl) ?>"
    method="post"
>
    <div class="omo-calendar-invitations-popup__header generic-drawer-header generic-drawer-header--sticky">
        <div class="generic-drawer-header__copy omo-calendar-invitations-popup__header-copy">
            <div class="generic-card-title generic-card-title--eyebrow">Calendrier</div>
            <h3 class="generic-card-title generic-card-title--medium"><?= omoApiEscape(omoCalendarInvitationsPopupT('calendar.invitations.title')) ?></h3>
        </div>
    </div>
    <div class="omo-calendar-invitations-popup__shell generic-drawer-content">
        <?= omoCalendarRenderInvitationEditor($editorState, $lang, $sourceLang, 'omoApiEscape', [
            'instanceId' => 'omoCalendarPopupInvitations',
            'holonFieldName' => 'holon_ids[]',
            'userFieldName' => 'user_ids[]',
            'emailFieldName' => 'emails',
            'showFooterHint' => false,
        ]) ?>

        <div id="omoCalendarInvitationsPopupFeedback" class="omo-calendar-invitations-popup__feedback generic-feedback"></div>

        <div class="omo-calendar-invitations-popup__actions generic-action-row">
            <button type="submit" id="omoCalendarInvitationsPopupSubmit" class="generic-action-button generic-action-button--main">
                <?= omoApiEscape(omoCalendarInvitationsPopupT('calendar.invitations.submit')) ?>
            </button>
        </div>
    </div>
</form>

<script>
(function () {
    var form = document.getElementById('omoCalendarInvitationsPopupForm');
    var feedback = document.getElementById('omoCalendarInvitationsPopupFeedback');
    var submitButton = document.getElementById('omoCalendarInvitationsPopupSubmit');

    function initInvitationEditor(scope) {
        if (!scope) {
            return;
        }

        if (typeof window.omoCalendarInitInvitationEditors === 'function') {
            window.omoCalendarInitInvitationEditors(scope);
            return;
        }

        if (typeof window.initGenericComponents === 'function') {
            window.initGenericComponents(scope);
        }

        Array.prototype.forEach.call(scope.querySelectorAll('[data-omo-calendar-holon-toggle]'), function (toggle) {
            if (toggle.dataset.omoCalendarBound === '1') {
                return;
            }

            toggle.dataset.omoCalendarBound = '1';
            toggle.addEventListener('click', function (event) {
                var node;
                var children;
                var isExpanded;

                event.preventDefault();
                event.stopPropagation();

                node = toggle.closest('[data-omo-calendar-holon-node]');
                children = node ? node.querySelector('[data-omo-calendar-holon-children]') : null;
                if (!children) {
                    return;
                }

                isExpanded = toggle.getAttribute('aria-expanded') === 'true';
                toggle.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
                children.hidden = isExpanded;
            });
        });
    }

    if (!form || !feedback || !submitButton) {
        return;
    }

    initInvitationEditor(form);

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        feedback.textContent = '';
        feedback.classList.remove('is-success');
        submitButton.disabled = true;

        fetch(form.getAttribute('action'), {
            method: 'POST',
            body: new FormData(form),
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (response) {
                return response.json().then(function (data) {
                    return {
                        ok: response.ok,
                        data: data
                    };
                });
            })
            .then(function (result) {
                if (!result.ok || !result.data || !result.data.status) {
                    feedback.textContent = result.data && result.data.message ? result.data.message : <?= json_encode(omoCalendarInvitationsPopupT('calendar.invitations.js_error')) ?>;
                    submitButton.disabled = false;
                    return;
                }

                feedback.textContent = result.data.message || <?= json_encode(omoCalendarInvitationsPopupT('calendar.invitations.updated')) ?>;
                feedback.classList.add('is-success');

                if (typeof window.commonTopbarCloseModal === 'function') {
                    window.commonTopbarCloseModal();
                }

                if (result.data.pvEditorContext && typeof window.CustomEvent === 'function') {
                    window.dispatchEvent(new CustomEvent('omo:pv-invitations-updated', {
                        detail: {
                            documentId: Number(result.data.pvDocumentId || 0)
                        }
                    }));
                }

                if (!result.data.pvEditorContext && result.data.detailUrl && typeof window.omoCalendarOpenEventDrawer === 'function') {
                    window.omoCalendarOpenEventDrawer(result.data.detailUrl);
                }
                if (typeof window.omoCalendarRefreshCurrentView === 'function') {
                    window.omoCalendarRefreshCurrentView();
                }
            })
            .catch(function () {
                feedback.textContent = <?= json_encode(omoCalendarInvitationsPopupT('calendar.invitations.js_request_error')) ?>;
                submitButton.disabled = false;
            });
    });
})();
</script>
