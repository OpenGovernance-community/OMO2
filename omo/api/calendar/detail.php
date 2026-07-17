<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/invitations_shared.php';

use dbObject\Event;
use dbObject\Holon;
use dbObject\Organization;

$sourceLang = array_merge([
    'calendar.detail.badge' => [
        'text' => 'Evenement',
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
        'text' => 'Supprimer cet evenement ?',
        'context' => 'Confirmation message shown before deleting an event.',
    ],
    'calendar.detail.delete_error' => [
        'text' => 'Impossible de supprimer cet evenement.',
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
    'calendar.detail.header.subtitle' => [
        'text' => 'Consultez les details puis modifiez si besoin.',
        'context' => 'Short explanatory subtitle below the event detail heading.',
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
        'text' => 'Document associe',
        'context' => 'Label of the linked document card inside the event detail view.',
    ],
    'calendar.detail.section.invites' => [
        'text' => 'Invites',
        'context' => 'Label of the invitation summary card inside the event detail view.',
    ],
    'calendar.detail.section.description' => [
        'text' => 'Description',
        'context' => 'Label of the description section inside the event detail view.',
    ],
    'calendar.detail.section.quick_info' => [
        'text' => 'Infos rapides',
        'context' => 'Label of the quick event information card.',
    ],
    'calendar.detail.quick_info.created_at' => [
        'text' => 'Cree le',
        'context' => 'Label shown before the event creation date in the quick information card.',
    ],
    'calendar.detail.empty.description' => [
        'text' => 'Aucune description pour cet evenement.',
        'context' => 'Fallback text shown when the event has no description.',
    ],
    'calendar.detail.empty.location' => [
        'text' => 'Aucun lieu precise.',
        'context' => 'Fallback text shown when the event has no location yet.',
    ],
    'calendar.detail.empty.document' => [
        'text' => 'Aucun document lie a cet evenement.',
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
    'calendar.detail.schedule.same_day' => [
        'text' => '{date} de {start} a {end}',
        'context' => 'Schedule string used for an event starting and ending the same day.',
    ],
    'calendar.detail.schedule.range' => [
        'text' => 'Du {start} au {end}',
        'context' => 'Schedule string used for an event spanning multiple days.',
    ],
    'calendar.detail.schedule.all_day_single' => [
        'text' => 'Toute la journee du {date}',
        'context' => 'Schedule string used for a one-day all-day event.',
    ],
    'calendar.detail.schedule.all_day_range' => [
        'text' => 'Journees completes du {start} au {end}',
        'context' => 'Schedule string used for a multi-day all-day event.',
    ],
    'calendar.detail.not_found' => [
        'text' => 'Evenement introuvable.',
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
    return \DateTimeImmutable::createFromInterface($date)->format('d.m.Y');
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

    if ($isAllDay) {
        if ($sameDay) {
            return omoCalendarDetailT('calendar.detail.schedule.all_day_single', [
                'date' => omoCalendarDetailFormatDay($startAt),
            ]);
        }

        return omoCalendarDetailT('calendar.detail.schedule.all_day_range', [
            'start' => omoCalendarDetailFormatDay($startAt),
            'end' => omoCalendarDetailFormatDay($endAt),
        ]);
    }

    if ($sameDay) {
        return omoCalendarDetailT('calendar.detail.schedule.same_day', [
            'date' => omoCalendarDetailFormatDay($startAt),
            'start' => omoCalendarDetailFormatTime($startAt),
            'end' => omoCalendarDetailFormatTime($endAt),
        ]);
    }

    return omoCalendarDetailT('calendar.detail.schedule.range', [
        'start' => omoCalendarDetailFormatDateTime($startAt),
        'end' => omoCalendarDetailFormatDateTime($endAt),
    ]);
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
$canEdit = $currentUserId > 0 && (int)$event->get('IDuser') === $currentUserId;
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
$title = trim((string)$event->get('title'));
$eventTitle = $title !== '' ? $title : ('Evenement #' . (int)$event->getId());
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
$createdAt = $event->get('created_at');
$associatedDocument = $event->getAssociatedDocument();
$canOpenAssociatedDocument = $associatedDocument instanceof \dbObject\Document
    && (
        $associatedDocument->isPvDocument() && !$associatedDocument->isPvValidated()
            ? $associatedDocument->canUserAccessPvBeforeValidation($currentUserId, $organizationId)
            : $associatedDocument->canViewDirectlyInOrganization($organizationId)
    );
$associatedDocumentUrl = $canOpenAssociatedDocument
    ? $event->buildAssociatedDocumentDetailUrl($eventHolonId > 0 ? $eventHolonId : $currentHolonId)
    : '';
$associatedDocumentPvPreparationUrl = $associatedDocument instanceof \dbObject\Document
    && $associatedDocument->canUserOpenPvEditor($currentUserId, $organizationId)
    ? $associatedDocument->buildPvEditorUrl($organizationId)
    : '';
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
        data-omo-calendar-drawer-description="<?= omoApiEscape(omoCalendarDetailT('calendar.detail.header.subtitle')) ?>"
    >
        <?php if ($canEdit || $canDelete): ?>
            <?php if ($canEdit): ?>
                <button
                    type="button"
                    class="generic-action-button generic-action-button--main"
                    data-omo-calendar-drawer-action
                    data-omo-calendar-open-edit-url="<?= omoApiEscape($editUrl) ?>"
                ><?= omoApiEscape(omoCalendarDetailT('calendar.detail.action.edit')) ?></button>
            <?php endif; ?>
            <?php if ($canDelete): ?>
                <button
                    type="button"
                    class="generic-action-button generic-action-button--danger generic-action-button--icon-only"
                    data-omo-calendar-drawer-action
                    data-omo-calendar-delete-url="<?= omoApiEscape($deleteUrl) ?>"
                    data-omo-calendar-delete-confirm="<?= omoApiEscape(omoCalendarDetailT('calendar.detail.confirm_delete')) ?>"
                    data-omo-calendar-delete-error="<?= omoApiEscape(omoCalendarDetailT('calendar.detail.delete_error')) ?>"
                    data-omo-calendar-delete-has-documents="<?= $associatedDocument instanceof \dbObject\Document ? '1' : '0' ?>"
                    data-omo-calendar-delete-documents-title="<?= omoApiEscape(omoCalendarDetailT('calendar.detail.delete_documents_title')) ?>"
                    data-omo-calendar-delete-documents-question="<?= omoApiEscape(omoCalendarDetailT('calendar.detail.delete_documents_question')) ?>"
                    data-omo-calendar-delete-documents-yes="<?= omoApiEscape(omoCalendarDetailT('calendar.detail.delete_documents_yes')) ?>"
                    data-omo-calendar-delete-documents-no="<?= omoApiEscape(omoCalendarDetailT('calendar.detail.delete_documents_no')) ?>"
                    title="<?= omoApiEscape(omoCalendarDetailT('calendar.detail.action.delete')) ?>"
                    aria-label="<?= omoApiEscape(omoCalendarDetailT('calendar.detail.action.delete')) ?>"
                >
                    <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
                        <path d="M5 7h14M10 11v6M14 11v6M9 7V5h6v2m-9 0 1 13h10l1-13"></path>
                    </svg>
                </button>
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
                        <span class="omo-calendar-detail__meta-label"><?= omoApiEscape(omoCalendarDetailT('calendar.detail.section.schedule')) ?></span>
                        <strong class="omo-calendar-detail__meta-value"><?= omoApiEscape($scheduleLabel) ?></strong>
                    </div>
                </div>
                <div class="omo-calendar-detail__meta-card">
                    <span class="omo-calendar-detail__meta-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false"><circle cx="12" cy="6" r="3"></circle><circle cx="5" cy="17" r="3"></circle><circle cx="19" cy="17" r="3"></circle></svg>
                    </span>
                    <div>
                        <span class="omo-calendar-detail__meta-label"><?= omoApiEscape(omoCalendarDetailT('calendar.detail.section.context')) ?></span>
                        <strong class="omo-calendar-detail__meta-value"><?= omoApiEscape($contextLabel !== '' ? $contextLabel : trim((string)$organization->get('name'))) ?></strong>
                    </div>
                </div>
                <div class="omo-calendar-detail__meta-card">
                    <span class="omo-calendar-detail__meta-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false"><circle cx="12" cy="12" r="8"></circle><path d="m8.5 12 2.3 2.3 4.8-5"></path></svg>
                    </span>
                    <div>
                        <span class="omo-calendar-detail__meta-label"><?= omoApiEscape(omoCalendarDetailT('calendar.detail.section.status')) ?></span>
                        <strong class="omo-calendar-detail__meta-value omo-calendar-detail__status-value<?= $normalizedStatus === Event::STATUS_CONFIRMED ? ' is-confirmed' : '' ?>"><?= omoApiEscape($statusLabel) ?></strong>
                    </div>
                </div>
                <div class="omo-calendar-detail__meta-card">
                    <span class="omo-calendar-detail__meta-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false"><path d="M12 21s6-5.1 6-11a6 6 0 1 0-12 0c0 5.9 6 11 6 11Z"></path><circle cx="12" cy="10" r="2"></circle></svg>
                    </span>
                    <div>
                        <span class="omo-calendar-detail__meta-label"><?= omoApiEscape(omoCalendarDetailT('calendar.detail.section.location')) ?></span>
                        <strong class="omo-calendar-detail__meta-value"><?= omoApiEscape($locationSummary !== '' ? $locationSummary : omoCalendarDetailT('calendar.detail.empty.location')) ?></strong>
                    </div>
                </div>
            </div>
        </section>

        <div class="omo-calendar-detail__content-grid">
            <div class="omo-calendar-detail__primary-column">
                <section class="generic-section generic-section--stack omo-calendar-detail__content">
                    <h3 class="generic-card-title generic-card-title--medium"><?= omoApiEscape(omoCalendarDetailT('calendar.detail.section.description')) ?></h3>
                    <?php if ($description !== ''): ?>
                        <div class="omo-calendar-detail__description"><?= nl2br(omoApiEscape($description)) ?></div>
                    <?php else: ?>
                        <p class="omo-calendar-detail__empty"><?= omoApiEscape(omoCalendarDetailT('calendar.detail.empty.description')) ?></p>
                    <?php endif; ?>
                </section>

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
                        <p class="omo-calendar-detail__empty"><?= omoApiEscape(omoCalendarDetailT('calendar.detail.empty.location')) ?></p>
                    <?php endif; ?>
                </section>

                <?= omoCalendarRenderInvitationSummarySection($event, $invitationContext, $lang, $sourceLang, 'omoApiEscape') ?>
            </div>

            <aside class="omo-calendar-detail__secondary-column">
                <section class="generic-section generic-section--stack omo-calendar-detail__content">
                    <h3 class="generic-card-title generic-card-title--medium"><?= omoApiEscape(omoCalendarDetailT('calendar.detail.section.document')) ?></h3>
                    <?php if ($associatedDocument instanceof \dbObject\Document): ?>
                        <div class="omo-calendar-detail__document-head">
                            <strong class="omo-calendar-detail__meta-value"><?= omoApiEscape(trim((string)$associatedDocument->get('title')) !== '' ? trim((string)$associatedDocument->get('title')) : ('Document #' . (int)$associatedDocument->getId())) ?></strong>
                            <span class="omo-calendar-detail__document-type"><?= omoApiEscape($associatedDocument->getDocumentTypeLabel()) ?></span>
                        </div>
                        <?php if ($associatedDocumentUrl !== ''): ?>
                            <button
                                type="button"
                                class="generic-action-button generic-action-button--secondary"
                                data-omo-calendar-open-url="<?= omoApiEscape($associatedDocumentUrl) ?>"
                                data-omo-calendar-open-url-title="<?= omoApiEscape(trim((string)$associatedDocument->get('title')) !== '' ? trim((string)$associatedDocument->get('title')) : ('Document #' . (int)$associatedDocument->getId())) ?>"
                                data-omo-calendar-open-pv-editor-url="<?= omoApiEscape($associatedDocumentPvPreparationUrl) ?>"
                            ><?= omoApiEscape(omoCalendarDetailT('calendar.detail.action.open_document')) ?></button>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="omo-calendar-detail__empty"><?= omoApiEscape(omoCalendarDetailT('calendar.detail.empty.document')) ?></p>
                    <?php endif; ?>
                </section>

                <section class="generic-section generic-section--stack omo-calendar-detail__content omo-calendar-detail__quick-info">
                    <h3 class="generic-card-title generic-card-title--medium"><?= omoApiEscape(omoCalendarDetailT('calendar.detail.section.quick_info')) ?></h3>
                    <dl class="omo-calendar-detail__quick-info-list">
                        <div>
                            <dt><?= omoApiEscape(omoCalendarDetailT('calendar.detail.section.status')) ?></dt>
                            <dd class="omo-calendar-detail__status-value<?= $normalizedStatus === Event::STATUS_CONFIRMED ? ' is-confirmed' : '' ?>"><?= omoApiEscape($statusLabel) ?></dd>
                        </div>
                        <?php if ($createdAt instanceof \DateTimeInterface): ?>
                            <div>
                                <dt><?= omoApiEscape(omoCalendarDetailT('calendar.detail.quick_info.created_at')) ?></dt>
                                <dd><?= omoApiEscape(omoCalendarDetailFormatDay($createdAt)) ?></dd>
                            </div>
                        <?php endif; ?>
                    </dl>
                </section>
            </aside>
        </div>
    </article>

    <style>
    .omo-calendar-detail {
        display: grid;
        gap: 0;
    }

    .omo-calendar-detail__shell {
        gap: 18px;
    }

    .omo-calendar-detail__overview,
    .omo-calendar-detail__content {
        --generic-section-shadow: var(--shadow-sm, 0 4px 14px rgba(15, 23, 42, 0.08));
    }

    .omo-calendar-detail__overview {
        gap: 20px;
    }

    .omo-calendar-detail__meta-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0;
    }

    .omo-calendar-detail__meta-card {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
        padding: 4px 16px;
        border-left: 1px solid var(--color-border, #e5e7eb);
    }

    .omo-calendar-detail__meta-card:first-child {
        padding-left: 0;
        border-left: 0;
    }

    .omo-calendar-detail__meta-card > div {
        display: grid;
        gap: 4px;
        min-width: 0;
    }

    .omo-calendar-detail__meta-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: color-mix(in srgb, var(--color-primary, #2563eb) 10%, var(--color-surface, #ffffff));
        color: var(--color-primary, #2563eb);
    }

    .omo-calendar-detail__meta-icon svg {
        width: 22px;
        height: 22px;
        fill: none;
        stroke: currentColor;
        stroke-linecap: round;
        stroke-linejoin: round;
        stroke-width: 1.8;
    }

    .omo-calendar-detail__meta-label {
        display: block;
        color: var(--color-text-light, #64748b);
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 0.03em;
        text-transform: uppercase;
    }

    .omo-calendar-detail__meta-value {
        display: block;
        color: var(--color-text, #1f2937);
        line-height: 1.5;
        overflow-wrap: anywhere;
    }

    .omo-calendar-detail__status-value.is-confirmed {
        color: var(--color-success, #15803d);
    }

    .omo-calendar-detail__content-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.55fr) minmax(260px, 0.95fr);
        align-items: start;
        gap: 18px;
    }

    .omo-calendar-detail__primary-column,
    .omo-calendar-detail__secondary-column {
        display: grid;
        gap: 14px;
        min-width: 0;
    }

    .omo-calendar-detail__content {
        gap: 12px;
        min-width: 0;
    }

    .omo-calendar-detail__description,
    .omo-calendar-detail__empty {
        margin: 0;
        color: var(--color-text, #1f2937);
        line-height: 1.65;
        white-space: normal;
    }

    .omo-calendar-detail__empty {
        color: var(--color-text-light, #64748b);
    }

    .omo-calendar-detail__location-mode,
    .omo-calendar-detail__document-type {
        display: inline-flex;
        align-items: center;
        width: fit-content;
        min-height: 28px;
        padding: 0 10px;
        border-radius: 999px;
        background: color-mix(in srgb, var(--color-primary, #2563eb) 10%, var(--color-surface, #ffffff));
        color: var(--color-text, #1f2937);
        font-size: 0.85rem;
        font-weight: 700;
    }

    .omo-calendar-detail__location-line {
        display: grid;
        gap: 4px;
        color: var(--color-text, #1f2937);
        line-height: 1.6;
    }

    .omo-calendar-detail__link {
        color: var(--color-primary, #2563eb);
        text-decoration: none;
        word-break: break-word;
    }

    .omo-calendar-detail__link:hover {
        text-decoration: underline;
    }

    .omo-calendar-detail__document-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    .omo-calendar-detail__quick-info-list {
        display: grid;
        gap: 0;
        margin: 0;
    }

    .omo-calendar-detail__quick-info-list > div {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 12px;
        padding: 10px 0;
        border-top: 1px solid var(--color-border, #e5e7eb);
    }

    .omo-calendar-detail__quick-info-list > div:first-child {
        padding-top: 0;
        border-top: 0;
    }

    .omo-calendar-detail__quick-info-list dt,
    .omo-calendar-detail__quick-info-list dd {
        margin: 0;
    }

    .omo-calendar-detail__quick-info-list dt {
        color: var(--color-text-light, #64748b);
    }

    .omo-calendar-detail__quick-info-list dd {
        color: var(--color-text, #1f2937);
        font-weight: 700;
        text-align: right;
    }

    .omo-calendar-detail__summary-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    .omo-calendar-detail__summary-copy {
        margin: 0;
        color: var(--color-text-light, #64748b);
        line-height: 1.6;
    }

    @media (max-width: 980px) {
        .omo-calendar-detail__meta-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px 0;
        }

        .omo-calendar-detail__meta-card:nth-child(odd) {
            padding-left: 0;
            border-left: 0;
        }

        .omo-calendar-detail__content-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 620px) {
        .omo-calendar-detail__meta-grid {
            grid-template-columns: 1fr;
            gap: 0;
        }

        .omo-calendar-detail__meta-card,
        .omo-calendar-detail__meta-card:nth-child(odd) {
            padding: 12px 0;
            border-top: 1px solid var(--color-border, #e5e7eb);
            border-left: 0;
        }

        .omo-calendar-detail__meta-card:first-child {
            padding-top: 0;
            border-top: 0;
        }
    }
    </style>
</div>
