<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/invitations_shared.php';
require_once __DIR__ . '/permissions_shared.php';
require_once dirname(__DIR__, 3) . '/common/calendar/recurrence.php';

use dbObject\Event;
use dbObject\Holon;
use dbObject\Organization;

$sourceLang = array_merge(commonMeetingRecurrenceSourceLang(), [
    'calendar.detail.recurrence.previous' => ['text' => 'Réunion précédente', 'context' => 'Navigate to the previous visible meeting in this recurring series.'],
    'calendar.detail.recurrence.next' => ['text' => 'Réunion suivante', 'context' => 'Navigate to the next visible meeting in this recurring series.'],
    'calendar.detail.badge' => [
        'text' => 'Événement',
        'context' => 'Small eyebrow label shown above the event detail title.',
    ],
    'calendar.detail.action.edit' => [
        'text' => 'Modifier',
        'context' => 'Button used to open the event edit form from the event detail view.',
    ],
    'calendar.detail.action.delete' => [
        'text' => 'Supprimer',
        'context' => 'Accessible label of the icon button used to delete an event from its detail view.',
    ],
    'calendar.detail.confirm_delete' => [
        'text' => 'Supprimer cet événement ?',
        'context' => 'Confirmation message shown before deleting an event.',
    ],
    'calendar.detail.delete_error' => [
        'text' => 'Impossible de supprimer cet événement.',
        'context' => 'Fallback error shown when deleting an event fails.',
    ],
    'calendar.detail.delete_documents_title' => [
        'text' => 'Documents associés',
        'context' => 'Title of the choice dialog shown before deleting documents linked to an event.',
    ],
    'calendar.detail.delete_documents_question' => [
        'text' => 'Voulez-vous supprimer les documents associés ?',
        'context' => 'Question shown before deleting documents linked to an event.',
    ],
    'calendar.detail.delete_documents_yes' => [
        'text' => 'Oui',
        'context' => 'Choice that deletes documents linked to the event.',
    ],
    'calendar.detail.delete_documents_no' => [
        'text' => 'Non',
        'context' => 'Choice that keeps documents linked to the event.',
    ],
    'calendar.detail.action.open_document' => [
        'text' => 'Consulter le document',
        'context' => 'Button used to open the linked document from the event detail view.',
    ],
    'calendar.detail.action.delete_document' => [
        'text' => 'Supprimer le document',
        'context' => 'Icon-only button used to delete the linked document from the event detail view.',
    ],
    'calendar.detail.confirm_delete_document' => [
        'text' => 'Supprimer définitivement ce document ?',
        'context' => 'Confirmation message shown before deleting the linked document from an event.',
    ],
    'calendar.detail.delete_document_error' => [
        'text' => 'Impossible de supprimer le document.',
        'context' => 'Fallback error shown when deleting the linked document from an event fails.',
    ],
    'calendar.detail.section.schedule' => [
        'text' => 'Horaire',
        'context' => 'Label of the schedule card inside the event detail view.',
    ],
    'calendar.detail.section.context' => [
        'text' => 'Contexte',
        'context' => 'Label of the context card inside the event detail view.',
    ],
    'calendar.detail.section.status' => [
        'text' => 'Statut',
        'context' => 'Label of the status card inside the event detail view.',
    ],
    'calendar.detail.section.location' => [
        'text' => 'Lieu',
        'context' => 'Label of the location card inside the event detail view.',
    ],
    'calendar.detail.section.document' => [
        'text' => 'Document associé',
        'context' => 'Label of the linked document card inside the event detail view.',
    ],
    'calendar.detail.section.invites' => [
        'text' => 'Invités',
        'context' => 'Label of the invitation summary card inside the event detail view.',
    ],
    'calendar.detail.section.description' => [
        'text' => 'Description',
        'context' => 'Label of the description section inside the event detail view.',
    ],
    'calendar.detail.reference.created_at' => [
        'text' => 'Créé le {date}',
        'context' => 'Creation timestamp in the discreet footer of the event detail.',
    ],
    'calendar.detail.reference.updated_at' => [
        'text' => 'Modifié le {date}',
        'context' => 'Last modification timestamp in the discreet footer of the event detail.',
    ],
    'calendar.detail.reference.created_at_by' => [
        'text' => 'Créé le {date} par {name}',
        'context' => 'Creation timestamp and creator in the discreet footer of the event detail.',
    ],
    'calendar.detail.empty.description' => [
        'text' => 'Aucune description pour cet événement.',
        'context' => 'Fallback text shown when the event has no description.',
    ],
    'calendar.detail.empty.location' => [
        'text' => 'Aucun lieu précisé.',
        'context' => 'Fallback text shown when the event has no location yet.',
    ],
    'calendar.detail.empty.document' => [
        'text' => 'Aucun document lié à cet événement.',
        'context' => 'Fallback text shown when the event has no linked document.',
    ],
    'calendar.detail.location.address' => [
        'text' => 'Adresse',
        'context' => 'Label shown before the physical address.',
    ],
    'calendar.detail.location.visio' => [
        'text' => 'Visio',
        'context' => 'Label shown before the virtual meeting URL.',
    ],
    'calendar.detail.schedule.hours' => [
        'text' => 'De {start} à {end}',
        'context' => 'Start and end times displayed below the event dates.',
    ],
    'calendar.detail.schedule.range' => [
        'text' => 'Du {start} au {end}',
        'context' => 'Schedule string used for an event spanning multiple days.',
    ],
    'calendar.detail.schedule.all_day' => [
        'text' => 'Toute la journée',
        'context' => 'All-day indication displayed below the event dates.',
    ],
    'calendar.detail.not_found' => [
        'text' => 'Événement introuvable.',
        'context' => 'Error shown when the requested event cannot be found.',
    ],
    'calendar.detail.organization_invalid' => [
        'text' => 'Organisation invalide.',
        'context' => 'Error shown when the organization is missing or inaccessible.',
    ],
], omoCalendarInvitationSourceLang());

$lang = omoLoadTranslationBundle('omo_calendar_detail', $sourceLang);

function omoCalendarDetailT($key, array $replace = [])
{
    global $lang, $sourceLang;
    return t($key, $replace, $lang, $sourceLang);
}

function omoCalendarDetailFormatDay(\DateTimeInterface $date)
{
    $formatter = new \IntlDateFormatter(
        omoGetTranslationLocale(),
        \IntlDateFormatter::FULL,
        \IntlDateFormatter::NONE,
        $date->getTimezone()->getName(),
        \IntlDateFormatter::GREGORIAN,
        'EEEE dd.MM.yyyy'
    );
    return mb_ucfirst((string)$formatter->format($date), 'UTF-8');
}

function omoCalendarDetailFormatDateTime(\DateTimeInterface $date)
{
    return \DateTimeImmutable::createFromInterface($date)->format('d.m.Y H:i');
}

function omoCalendarDetailFormatTime(\DateTimeInterface $date)
{
    return \DateTimeImmutable::createFromInterface($date)->format('H:i');
}

function omoCalendarDetailFormatSchedule(Event $event)
{
    $startAt = $event->get('start_at');
    $endAt = $event->get('end_at');
    if (!($startAt instanceof \DateTimeInterface) || !($endAt instanceof \DateTimeInterface)) {
        return '';
    }

    $isAllDay = (bool)$event->get('is_all_day');
    $sameDay = $startAt->format('Y-m-d') === $endAt->format('Y-m-d');

    $dateLabel = $sameDay
        ? omoCalendarDetailFormatDay($startAt)
        : omoCalendarDetailT('calendar.detail.schedule.range', [
            'start' => omoCalendarDetailFormatDay($startAt),
            'end' => omoCalendarDetailFormatDay($endAt),
        ]);
    $timeLabel = $isAllDay
        ? omoCalendarDetailT('calendar.detail.schedule.all_day')
        : omoCalendarDetailT('calendar.detail.schedule.hours', [
            'start' => omoCalendarDetailFormatTime($startAt),
            'end' => omoCalendarDetailFormatTime($endAt),
        ]);

    return $dateLabel . "\n" . $timeLabel;
}

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$currentHolonId = isset($_GET['cid']) && is_numeric($_GET['cid']) ? (int)$_GET['cid'] : 0;
$eventId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$currentUserId = function_exists('commonGetCurrentUserId') ? (int)commonGetCurrentUserId() : 0;

if ($organizationId <= 0 || $eventId <= 0) {
    http_response_code(404);
    echo '<div class="omo-empty-state">' . omoApiEscape(omoCalendarDetailT('calendar.detail.not_found')) . '</div>';
    exit;
}

$organization = new Organization();
if (!$organization->load($organizationId) || !$organization->canViewDetail()) {
    http_response_code(403);
    echo '<div class="omo-empty-state">' . omoApiEscape(omoCalendarDetailT('calendar.detail.organization_invalid')) . '</div>';
    exit;
}

$event = new Event();
if (
    !$event->load($eventId)
    || (int)$event->get('IDorganization') !== $organizationId
    || (int)$event->get('active') !== 1
    || Event::normalizeStatus($event->get('status')) === Event::STATUS_CANCELLED
    || !$event->isDraftVisibleToViewer($currentUserId)
) {
    http_response_code(404);
    echo '<div class="omo-empty-state">' . omoApiEscape(omoCalendarDetailT('calendar.detail.not_found')) . '</div>';
    exit;
}

$rootHolon = $organization->getEnabledStructuralRootHolon();
$eventHolonId = (int)$event->get('IDholon');
$contextLabel = trim((string)$organization->get('name'));

if ($eventHolonId > 0) {
    $eventHolon = new Holon();
    if (
        !$eventHolon->load($eventHolonId)
        || !($rootHolon instanceof Holon)
        || !$eventHolon->isDescendantOf((int)$rootHolon->getId(), true)
        || !$eventHolon->canViewDetail()
    ) {
        http_response_code(404);
        echo '<div class="omo-empty-state">' . omoApiEscape(omoCalendarDetailT('calendar.detail.not_found')) . '</div>';
        exit;
    }

    $contextLabel = trim((string)$eventHolon->getDisplayName());
}

$deletePermissionHolon = $eventHolonId > 0 && isset($eventHolon) && $eventHolon instanceof Holon
    ? $eventHolon
    : $rootHolon;
$canDelete = $currentUserId > 0
    && (
        $deletePermissionHolon instanceof Holon
            ? $deletePermissionHolon->isAllowed('CAN_DELETE_EVENT', false, $currentUserId)
            : commonCurrentUserHasOrganizationAccess($organizationId)
    );
$canDelete = $canDelete && !$event->isPastRecurringMeeting();
$canEdit = omoCalendarCanEditEvent($event, $organizationId, $currentUserId, $rootHolon, false);
$series = $event->getRecurrence();
$recurrenceSummary = commonMeetingRecurrenceSummary($event, 'omoCalendarDetailT');
$recurrenceNeighbours = $series ? $series->getAdjacentEvents($event, static function (Event $candidate) use ($rootHolon, $currentUserId): bool {
    if (!$candidate->isDraftVisibleToViewer($currentUserId)) { return false; }
    if ((int)$candidate->get('IDholon') <= 0) { return true; }
    $holon = omoCalendarResolveEventPermissionHolon($candidate, $rootHolon);
    return $holon instanceof Holon && $holon->canViewDetail();
}) : [];
$_SESSION['omo_event_recurrence_csrf'] ??= bin2hex(random_bytes(32));
$canPlanNext = $series && $series->get('active') && $series->get('frequency') === 'on_close'
    && !$event->get('recurrence_exception') && !$event->getParameter('next_meeting_id') && $series->canManage($organizationId, $currentUserId);
$canOpenEditor = $canEdit || $event->canEditTimeBuffers($currentUserId);
$editContextHolonId = $currentHolonId > 0 ? $currentHolonId : $eventHolonId;
$editUrl = '/omo/api/calendar/create.php?oid=' . rawurlencode((string)$organizationId);
if ($editContextHolonId > 0) {
    $editUrl .= '&cid=' . rawurlencode((string)$editContextHolonId);
}
$editUrl .= '&id=' . rawurlencode((string)$eventId);
$deleteUrl = '/omo/api/calendar/delete.php?oid=' . rawurlencode((string)$organizationId)
    . '&id=' . rawurlencode((string)$eventId);

$statusCatalog = Event::getStatusCatalog();
$normalizedStatus = Event::normalizeStatus($event->get('status'));
$statusLabel = trim((string)($statusCatalog[$normalizedStatus]['label'] ?? $normalizedStatus));
$statusClass = 'is-' . $normalizedStatus;
$title = trim((string)$event->get('title'));
$eventTitle = $title !== '' ? $title : ('Événement #' . (int)$event->getId());
$description = trim((string)$event->get('description'));
$scheduleLabel = omoCalendarDetailFormatSchedule($event);
$locationData = $event->getLocationDisplayData();
$locationSummary = trim((string)($locationData['modeLabel'] ?? ''));
if ($locationSummary === '') {
    $locationSummary = trim((string)($locationData['address'] ?? ''));
}
if ($locationSummary === '') {
    $locationSummary = trim((string)($locationData['videoUrl'] ?? ''));
}
$referenceDates = [];
$createdBy = $event->getCreatedByDisplayName();
foreach (['created_at', 'updated_at'] as $dateField) {
    $referenceDate = $event->get($dateField);
    if ($referenceDate instanceof \DateTimeInterface) {
        $referenceKey = 'calendar.detail.reference.' . $dateField;
        if ($dateField === 'created_at' && $createdBy !== '') {
            $referenceKey .= '_by';
        }
        $referenceDates[] = omoCalendarDetailT($referenceKey, [
            'date' => omoCalendarDetailFormatDateTime($referenceDate),
            'name' => $createdBy,
        ]);
    }
}
$associatedDocument = $event->getAssociatedDocument();
$canOpenAssociatedDocument = $associatedDocument instanceof \dbObject\Document
    && (
        $associatedDocument->isPvDocument() && !$associatedDocument->isPvValidated()
            ? (
                $associatedDocument->canUserAccessPvBeforeValidation($currentUserId, $organizationId)
                || ($associatedDocument->getPvStage() === \dbObject\Document::PV_STAGE_REVIEW
                    && $associatedDocument->canUserViewPvReadOnly($currentUserId, $organizationId, $eventHolonId > 0 ? $eventHolonId : null))
            )
            : $associatedDocument->canViewDirectlyInOrganization($organizationId)
    );
$associatedDocumentUrl = $canOpenAssociatedDocument
    ? $event->buildAssociatedDocumentDetailUrl($eventHolonId > 0 ? $eventHolonId : $currentHolonId)
    : '';
$associatedDocumentPvPreparationUrl = $associatedDocument instanceof \dbObject\Document
    && $associatedDocument->canUserOpenPvEditor($currentUserId, $organizationId)
    ? $associatedDocument->buildPvEditorUrl($organizationId)
    : '';
$canDeleteAssociatedDocument = $associatedDocument instanceof \dbObject\Document
    && $associatedDocument->canDeleteInOrganizationContext($organizationId, $currentUserId)
    && $associatedDocument->canDeleteDocument(true);
$detailRefreshUrl = '/omo/api/calendar/detail.php?oid=' . rawurlencode((string)$organizationId)
    . '&id=' . rawurlencode((string)$eventId);
if ($editContextHolonId > 0) {
    $detailRefreshUrl .= '&cid=' . rawurlencode((string)$editContextHolonId);
}
$invitationContext = [
    'organizationId' => $organizationId,
    'targetHolonId' => $editContextHolonId,
    'effectiveHolon' => isset($eventHolon) && $eventHolon instanceof Holon ? $eventHolon : null,
    'canEditInvitations' => $canEdit,
];
?>
<div class="omo-calendar-detail">
    <div
        hidden
        data-omo-calendar-drawer-header
        data-omo-calendar-drawer-title="<?= omoApiEscape(omoCalendarDetailT('calendar.detail.badge')) ?>"
        data-omo-calendar-drawer-description=""
    >
        <?php require_once dirname(__DIR__, 3) . '/common/object_mail/ui.php'; omoObjectMailButton($organizationId, 'event', (int)$event->getId(), 'data-omo-calendar-drawer-action'); ?>
        <?php if ($canPlanNext): ?>
        <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-calendar-drawer-action
            data-meeting-plan-next="<?= omoApiEscape(json_encode(['id' => (int)$event->getId(), 'csrf' => $_SESSION['omo_event_recurrence_csrf'],
                'url' => '/omo/api/calendar/recurrence_next.php', 'availability' => commonMeetingNextDateConfig($event), 'ui' => commonMeetingRecurrenceUi('omoCalendarDetailT')], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)) ?>"
        ><?= omoApiEscape(omoCalendarDetailT('calendar.recurrence.plan')) ?></button>
        <?php endif; ?>
        <?php if ($canOpenEditor || $canDelete): ?>
            <?php if ($canOpenEditor): ?>
                <button
                    type="button"
                    class="generic-action-button generic-action-button--main"
                    data-omo-calendar-drawer-action
                    data-omo-calendar-open-edit-url="<?= omoApiEscape($editUrl) ?>"
                ><?= omoApiEscape(omoCalendarDetailT('calendar.detail.action.edit')) ?></button>
            <?php endif; ?>
            <?php if ($canDelete): ?>
                <?php if ($series): ?>
                <div class="generic-menu generic-menu--split" data-meeting-delete-menu data-meeting-action-menu data-omo-calendar-drawer-action>
                <?php endif; ?>
                <button
                    type="button"
                    class="generic-action-button generic-action-button--danger generic-action-button--icon-only"
                    <?= $series ? '' : 'data-omo-calendar-drawer-action' ?>
                    data-omo-calendar-delete-url="<?= omoApiEscape($deleteUrl) ?>"
                    <?php if ($series): ?>
                    data-meeting-delete-scope="<?= omoApiEscape(json_encode(commonMeetingRecurrenceUi('omoCalendarDetailT'))) ?>"
                    data-meeting-delete-csrf="<?= omoApiEscape($_SESSION['omo_event_recurrence_csrf']) ?>"
                    <?php endif; ?>
                    data-omo-calendar-delete-confirm="<?= omoApiEscape(omoCalendarDetailT('calendar.detail.confirm_delete')) ?>"
                    data-omo-calendar-delete-error="<?= omoApiEscape(omoCalendarDetailT('calendar.detail.delete_error')) ?>"
                    data-omo-calendar-delete-has-documents="<?= $associatedDocument instanceof \dbObject\Document ? '1' : '0' ?>"
                    data-omo-calendar-delete-documents-title="<?= omoApiEscape(omoCalendarDetailT('calendar.detail.delete_documents_title')) ?>"
                    data-omo-calendar-delete-documents-question="<?= omoApiEscape(omoCalendarDetailT('calendar.detail.delete_documents_question')) ?>"
                    data-omo-calendar-delete-documents-yes="<?= omoApiEscape(omoCalendarDetailT('calendar.detail.delete_documents_yes')) ?>"
                    data-omo-calendar-delete-documents-no="<?= omoApiEscape(omoCalendarDetailT('calendar.detail.delete_documents_no')) ?>"
                    title="<?= omoApiEscape(omoCalendarDetailT($series ? 'calendar.recurrence.delete_single' : 'calendar.detail.action.delete')) ?>"
                    aria-label="<?= omoApiEscape(omoCalendarDetailT($series ? 'calendar.recurrence.delete_single' : 'calendar.detail.action.delete')) ?>"
                >
                    <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
                        <path d="M5 7h14M10 11v6M14 11v6M9 7V5h6v2m-9 0 1 13h10l1-13"></path>
                    </svg>
                </button>
                <?php if ($series): ?>
                    <button type="button" class="generic-menu-toggle" data-meeting-delete-toggle data-meeting-action-toggle aria-haspopup="menu" aria-expanded="false"
                        aria-label="<?= omoApiEscape(omoCalendarDetailT('calendar.recurrence.delete_options')) ?>" title="<?= omoApiEscape(omoCalendarDetailT('calendar.recurrence.delete_options')) ?>">&#9662;</button>
                    <div class="generic-menu-panel generic-menu-panel--descriptive" role="menu" hidden>
                        <button type="button" class="generic-menu-item generic-menu-item--descriptive generic-menu-item--danger" role="menuitem" data-meeting-delete-choice="following">
                            <strong><?= omoApiEscape(omoCalendarDetailT('calendar.recurrence.' . ($series->hasFollowing($event) ? 'delete_following' : 'delete_last'))) ?></strong>
                            <span class="generic-menu-item__description"><?= omoApiEscape(omoCalendarDetailT('calendar.recurrence.scope_delete_help')) ?></span>
                        </button>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <article class="generic-section generic-section--plain generic-section--stack omo-calendar-detail__shell">
        <section class="generic-section generic-section--stack omo-calendar-detail__overview">
            <h3 class="generic-card-title generic-card-title--large"><?= omoApiEscape($eventTitle) ?></h3>
            <div class="omo-calendar-detail__meta-grid">
                <div class="omo-calendar-detail__meta-card">
                    <span class="omo-calendar-detail__meta-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false"><rect x="4" y="5" width="16" height="15" rx="2"></rect><path d="M8 3v4M16 3v4M4 10h16"></path></svg>
                    </span>
                    <div>
                        <span class="omo-calendar-detail__meta-label generic-meta-label"><?= omoApiEscape(omoCalendarDetailT('calendar.detail.section.schedule')) ?></span>
                        <strong class="omo-calendar-detail__meta-value generic-meta-value"><?= nl2br(omoApiEscape($scheduleLabel)) ?></strong>
                    </div>
                </div>
                <?php if ($recurrenceSummary !== ''): ?>
                    <p class="generic-meta generic-action-row generic-action-row--start omo-calendar-detail__recurrence" data-meeting-recurrence-summary>
                        <span class="generic-action-row" data-meeting-recurrence-navigation>
                        <?php foreach (['previous' => '&lt;', 'next' => '&gt;'] as $direction => $symbol): ?>
                            <?php
                            $neighbour = $recurrenceNeighbours[$direction] ?? null;
                            $navigationLabel = omoCalendarDetailT('calendar.detail.recurrence.' . $direction);
                            if ($neighbour) {
                                $navigationLabel .= ' - ' . $neighbour->get('start_at')->format('d.m.Y H:i');
                                $navigationUrl = '/omo/api/calendar/detail.php?oid=' . $organizationId . '&id=' . (int)$neighbour->getId()
                                    . ($currentHolonId > 0 ? '&cid=' . $currentHolonId : '');
                                $navigationRoute = '/omo/o/' . $organizationId . ($currentHolonId > 0 ? '/c/' . $currentHolonId : '') . '#calendar-e' . (int)$neighbour->getId();
                            }
                            ?>
                            <?php if ($neighbour): ?>
                            <a class="generic-action-button generic-action-button--secondary generic-action-button--icon-only"
                                href="<?= omoApiEscape($navigationRoute) ?>" data-omo-calendar-open-detail-url="<?= omoApiEscape($navigationUrl) ?>"
                                data-meeting-recurrence-direction="<?= $direction ?>" aria-label="<?= omoApiEscape($navigationLabel) ?>" title="<?= omoApiEscape($navigationLabel) ?>"><?= $symbol ?></a>
                            <?php else: ?>
                            <button type="button" class="generic-action-button generic-action-button--secondary generic-action-button--icon-only" disabled
                                data-meeting-recurrence-direction="<?= $direction ?>" aria-label="<?= omoApiEscape($navigationLabel) ?>" title="<?= omoApiEscape($navigationLabel) ?>"><?= $symbol ?></button>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        </span>
                        <span class="generic-meta-label"><?= omoApiEscape(omoCalendarDetailT('calendar.recurrence.frequency')) ?> :</span>
                        <span class="generic-meta-value generic-meta-value--compact"><?= omoApiEscape($recurrenceSummary) ?></span>
                    </p>
                <?php endif; ?>
                <div class="omo-calendar-detail__meta-card">
                    <span class="omo-calendar-detail__meta-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false"><circle cx="12" cy="6" r="3"></circle><circle cx="5" cy="17" r="3"></circle><circle cx="19" cy="17" r="3"></circle></svg>
                    </span>
                    <div>
                        <span class="omo-calendar-detail__meta-label generic-meta-label"><?= omoApiEscape(omoCalendarDetailT('calendar.detail.section.context')) ?></span>
                        <strong class="omo-calendar-detail__meta-value generic-meta-value"><?= omoApiEscape($contextLabel !== '' ? $contextLabel : trim((string)$organization->get('name'))) ?></strong>
                    </div>
                </div>
                <div class="omo-calendar-detail__meta-card">
                    <span class="omo-calendar-detail__meta-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false"><circle cx="12" cy="12" r="8"></circle><path d="m8.5 12 2.3 2.3 4.8-5"></path></svg>
                    </span>
                    <div>
                        <span class="omo-calendar-detail__meta-label generic-meta-label"><?= omoApiEscape(omoCalendarDetailT('calendar.detail.section.status')) ?></span>
                        <strong class="omo-calendar-detail__meta-value generic-meta-value omo-calendar-detail__status-value <?= omoApiEscape($statusClass) ?>"><?= omoApiEscape($statusLabel) ?></strong>
                    </div>
                </div>
                <div class="omo-calendar-detail__meta-card">
                    <span class="omo-calendar-detail__meta-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false"><path d="M12 21s6-5.1 6-11a6 6 0 1 0-12 0c0 5.9 6 11 6 11Z"></path><circle cx="12" cy="10" r="2"></circle></svg>
                    </span>
                    <div>
                        <span class="omo-calendar-detail__meta-label generic-meta-label"><?= omoApiEscape(omoCalendarDetailT('calendar.detail.section.location')) ?></span>
                        <strong class="omo-calendar-detail__meta-value generic-meta-value"><?= omoApiEscape($locationSummary !== '' ? $locationSummary : omoCalendarDetailT('calendar.detail.empty.location')) ?></strong>
                    </div>
                </div>
            </div>
        </section>

        <div class="omo-calendar-detail__content-grid">
            <div class="omo-calendar-detail__primary-column">
                <section class="generic-section generic-section--stack omo-calendar-detail__content">
                    <h3 class="generic-card-title generic-card-title--medium"><?= omoApiEscape(omoCalendarDetailT('calendar.detail.section.document')) ?></h3>
                    <?php if ($associatedDocument instanceof \dbObject\Document): ?>
                        <div class="omo-calendar-detail__document-head">
                            <strong class="omo-calendar-detail__meta-value"><?= omoApiEscape(trim((string)$associatedDocument->get('title')) !== '' ? trim((string)$associatedDocument->get('title')) : ('Document #' . (int)$associatedDocument->getId())) ?></strong>
                            <span class="omo-calendar-detail__document-type"><?= omoApiEscape($associatedDocument->getDocumentTypeLabel()) ?></span>
                        </div>
                        <?php if ($associatedDocumentUrl !== '' || $canDeleteAssociatedDocument): ?>
                            <div class="omo-calendar-detail__document-actions">
                                <?php if ($associatedDocumentUrl !== ''): ?>
                                    <button
                                        type="button"
                                        class="generic-action-button generic-action-button--secondary"
                                        data-omo-calendar-open-url="<?= omoApiEscape($associatedDocumentUrl) ?>"
                                        data-omo-calendar-open-url-title="<?= omoApiEscape(trim((string)$associatedDocument->get('title')) !== '' ? trim((string)$associatedDocument->get('title')) : ('Document #' . (int)$associatedDocument->getId())) ?>"
                                        data-omo-calendar-open-pv-editor-url="<?= omoApiEscape($associatedDocumentPvPreparationUrl) ?>"
                                    ><?= omoApiEscape(omoCalendarDetailT('calendar.detail.action.open_document')) ?></button>
                                <?php endif; ?>
                                <?php if ($canDeleteAssociatedDocument): ?>
                                    <button
                                        type="button"
                                        class="generic-action-button generic-action-button--danger generic-action-button--icon-only"
                                        data-omo-calendar-document-delete-id="<?= (int)$associatedDocument->getId() ?>"
                                        data-omo-calendar-document-delete-refresh-url="<?= omoApiEscape($detailRefreshUrl) ?>"
                                        data-omo-calendar-document-delete-confirm="<?= omoApiEscape(omoCalendarDetailT('calendar.detail.confirm_delete_document')) ?>"
                                        data-omo-calendar-document-delete-error="<?= omoApiEscape(omoCalendarDetailT('calendar.detail.delete_document_error')) ?>"
                                        title="<?= omoApiEscape(omoCalendarDetailT('calendar.detail.action.delete_document')) ?>"
                                        aria-label="<?= omoApiEscape(omoCalendarDetailT('calendar.detail.action.delete_document')) ?>"
                                    >
                                        <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
                                            <path d="M5 7h14M10 11v6M14 11v6M9 7V5h6v2m-9 0 1 13h10l1-13"></path>
                                        </svg>
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="omo-calendar-detail__empty generic-description generic-description--relaxed"><?= omoApiEscape(omoCalendarDetailT('calendar.detail.empty.document')) ?></p>
                    <?php endif; ?>
                </section>

                <section class="generic-section generic-section--stack omo-calendar-detail__content">
                    <h3 class="generic-card-title generic-card-title--medium"><?= omoApiEscape(omoCalendarDetailT('calendar.detail.section.description')) ?></h3>
                    <?php if ($description !== ''): ?>
                        <div class="omo-calendar-detail__description generic-description generic-description--primary generic-description--relaxed"><?= nl2br(omoApiEscape($description)) ?></div>
                    <?php else: ?>
                        <p class="omo-calendar-detail__empty generic-description generic-description--relaxed"><?= omoApiEscape(omoCalendarDetailT('calendar.detail.empty.description')) ?></p>
                    <?php endif; ?>
                </section>
            </div>

            <aside class="omo-calendar-detail__secondary-column">
                <section class="generic-section generic-section--stack omo-calendar-detail__content">
                    <h3 class="generic-card-title generic-card-title--medium"><?= omoApiEscape(omoCalendarDetailT('calendar.detail.section.location')) ?></h3>
                    <?php if (($locationData['mode'] ?? '') !== '' || ($locationData['address'] ?? '') !== '' || ($locationData['videoUrl'] ?? '') !== ''): ?>
                        <?php if (trim((string)($locationData['modeLabel'] ?? '')) !== ''): ?>
                            <div class="omo-calendar-detail__location-mode"><?= omoApiEscape((string)$locationData['modeLabel']) ?></div>
                        <?php endif; ?>
                        <?php if (trim((string)($locationData['address'] ?? '')) !== ''): ?>
                            <div class="omo-calendar-detail__location-line">
                                <strong><?= omoApiEscape(omoCalendarDetailT('calendar.detail.location.address')) ?></strong>
                                <span><?= nl2br(omoApiEscape((string)$locationData['address'])) ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (trim((string)($locationData['videoUrl'] ?? '')) !== ''): ?>
                            <div class="omo-calendar-detail__location-line">
                                <strong><?= omoApiEscape(omoCalendarDetailT('calendar.detail.location.visio')) ?></strong>
                                <a href="<?= omoApiEscape((string)$locationData['videoUrl']) ?>" target="_blank" rel="noopener noreferrer" class="omo-calendar-detail__link">
                                    <?= omoApiEscape((string)$locationData['videoUrl']) ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="omo-calendar-detail__empty generic-description generic-description--relaxed"><?= omoApiEscape(omoCalendarDetailT('calendar.detail.empty.location')) ?></p>
                    <?php endif; ?>
                </section>

                <?= omoCalendarRenderInvitationSummarySection($event, $invitationContext, $lang, $sourceLang, 'omoApiEscape') ?>
            </aside>
        </div>
        <?php if ($referenceDates !== []): ?>
            <p class="generic-help-text generic-help-text--centered"><em><?= omoApiEscape(implode(' · ', $referenceDates)) ?></em></p>
        <?php endif; ?>
    </article>

    <link rel="stylesheet" href="<?= commonAssetUrl('/omo/api/calendar/detail.css') ?>">
    <?php if ($canPlanNext) { commonMeetingAvailabilityAssets(); } ?>
    <script src="<?= commonAssetUrl('/common/calendar/recurrence.js') ?>"></script>
</div>
