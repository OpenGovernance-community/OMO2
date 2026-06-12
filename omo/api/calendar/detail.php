<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\Event;
use dbObject\Holon;
use dbObject\Organization;

$sourceLang = [
    'calendar.detail.badge' => [
        'text' => 'Evenement',
        'context' => 'Small eyebrow label shown above the event detail title.',
    ],
    'calendar.detail.action.edit' => [
        'text' => 'Modifier',
        'context' => 'Button used to open the event edit form from the event detail view.',
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
    'calendar.detail.section.description' => [
        'text' => 'Description',
        'context' => 'Label of the description section inside the event detail view.',
    ],
    'calendar.detail.empty.description' => [
        'text' => 'Aucune description pour cet evenement.',
        'context' => 'Fallback text shown when the event has no description.',
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
];

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

$canEdit = $currentUserId > 0 && (int)$event->get('IDuser') === $currentUserId;
$editContextHolonId = $currentHolonId > 0 ? $currentHolonId : $eventHolonId;
$editUrl = '/omo/api/calendar/create.php?oid=' . rawurlencode((string)$organizationId);
if ($editContextHolonId > 0) {
    $editUrl .= '&cid=' . rawurlencode((string)$editContextHolonId);
}
$editUrl .= '&id=' . rawurlencode((string)$eventId);

$statusCatalog = Event::getStatusCatalog();
$normalizedStatus = Event::normalizeStatus($event->get('status'));
$statusLabel = trim((string)($statusCatalog[$normalizedStatus]['label'] ?? $normalizedStatus));
$title = trim((string)$event->get('title'));
$description = trim((string)$event->get('description'));
$scheduleLabel = omoCalendarDetailFormatSchedule($event);
?>
<div class="omo-calendar-detail">
    <article class="generic-section generic-section--stack omo-calendar-detail__shell">
        <header class="omo-calendar-detail__header">
            <div class="omo-calendar-detail__eyebrow"><?= omoApiEscape(omoCalendarDetailT('calendar.detail.badge')) ?></div>
            <div class="omo-calendar-detail__title-row">
                <h2 class="generic-card-title generic-card-title--medium"><?= omoApiEscape($title !== '' ? $title : ('Evenement #' . (int)$event->getId())) ?></h2>
                <?php if ($canEdit): ?>
                    <button
                        type="button"
                        class="generic-action-button"
                        data-omo-calendar-open-edit-url="<?= omoApiEscape($editUrl) ?>"
                    ><?= omoApiEscape(omoCalendarDetailT('calendar.detail.action.edit')) ?></button>
                <?php endif; ?>
            </div>
        </header>

        <div class="omo-calendar-detail__meta-grid">
            <section class="generic-soft-panel generic-soft-panel--stack omo-calendar-detail__meta-card">
                <span class="omo-calendar-detail__meta-label"><?= omoApiEscape(omoCalendarDetailT('calendar.detail.section.schedule')) ?></span>
                <strong class="omo-calendar-detail__meta-value"><?= omoApiEscape($scheduleLabel) ?></strong>
            </section>

            <section class="generic-soft-panel generic-soft-panel--stack omo-calendar-detail__meta-card">
                <span class="omo-calendar-detail__meta-label"><?= omoApiEscape(omoCalendarDetailT('calendar.detail.section.context')) ?></span>
                <strong class="omo-calendar-detail__meta-value"><?= omoApiEscape($contextLabel !== '' ? $contextLabel : trim((string)$organization->get('name'))) ?></strong>
            </section>

            <section class="generic-soft-panel generic-soft-panel--stack omo-calendar-detail__meta-card">
                <span class="omo-calendar-detail__meta-label"><?= omoApiEscape(omoCalendarDetailT('calendar.detail.section.status')) ?></span>
                <strong class="omo-calendar-detail__meta-value"><?= omoApiEscape($statusLabel) ?></strong>
            </section>
        </div>

        <section class="generic-soft-panel generic-soft-panel--stack omo-calendar-detail__content">
            <h3 class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoCalendarDetailT('calendar.detail.section.description')) ?></h3>
            <?php if ($description !== ''): ?>
                <div class="omo-calendar-detail__description"><?= nl2br(omoApiEscape($description)) ?></div>
            <?php else: ?>
                <p class="omo-calendar-detail__empty"><?= omoApiEscape(omoCalendarDetailT('calendar.detail.empty.description')) ?></p>
            <?php endif; ?>
        </section>
    </article>

    <style>
    .omo-calendar-detail {
        display: grid;
        gap: 0;
    }

    .omo-calendar-detail__shell {
        gap: 18px;
    }

    .omo-calendar-detail__header {
        display: grid;
        gap: 10px;
    }

    .omo-calendar-detail__eyebrow {
        display: inline-flex;
        align-items: center;
        width: fit-content;
        min-height: 28px;
        padding: 0 10px;
        border-radius: 999px;
        background: color-mix(in srgb, var(--color-primary, #2563eb) 12%, var(--color-surface, #ffffff));
        color: var(--color-text-light, #64748b);
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 0.03em;
        text-transform: uppercase;
    }

    .omo-calendar-detail__title-row {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    .omo-calendar-detail__meta-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
    }

    .omo-calendar-detail__meta-card {
        min-width: 0;
    }

    .omo-calendar-detail__meta-label {
        color: var(--color-text-light, #64748b);
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 0.03em;
        text-transform: uppercase;
    }

    .omo-calendar-detail__meta-value {
        color: var(--color-text, #1f2937);
        line-height: 1.5;
    }

    .omo-calendar-detail__content {
        gap: 10px;
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

    @media (max-width: 760px) {
        .omo-calendar-detail__meta-grid {
            grid-template-columns: 1fr;
        }
    }
    </style>
</div>
