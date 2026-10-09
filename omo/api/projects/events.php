<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';
require_once dirname(__DIR__, 3) . '/common/calendar/upcoming_sections.php';
require_once dirname(__DIR__) . '/calendar/permissions_shared.php';

use dbObject\Event;
use dbObject\Holon;
use dbObject\Project;
use dbObject\ArrayProjectExternalEvent;
use dbObject\ProjectExternalEvent;

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$projectId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$context = omoProjectsResolveContext($organizationId, isset($_GET['cid']) ? (int)$_GET['cid'] : 0);

if (empty($context['status']) || $projectId <= 0) {
    http_response_code(empty($context['status']) ? 403 : 404);
    echo '<div class="omo-empty-state">' . omoApiEscape(omoProjectsT('projects.error.not_found')) . '</div>';
    exit;
}

$project = new Project();
if (
    !$project->load($projectId)
    || (int)$project->get('IDorganization') !== $organizationId
    || !omoProjectsCanViewProject($project, $context)
) {
    http_response_code(404);
    echo '<div class="omo-empty-state">' . omoApiEscape(omoProjectsT('projects.error.not_found')) . '</div>';
    exit;
}

$isArchivedProject = (int)$project->get('active') !== 1;

$projectHolon = $project->getHolon();
$rootHolon = $context['rootHolon'];
if (
    $projectHolon instanceof Holon
    && (
        !($rootHolon instanceof Holon)
        || !$projectHolon->isDescendantOf((int)$rootHolon->getId(), true)
        || !$projectHolon->canViewDetail()
    )
) {
    http_response_code(404);
    echo '<div class="omo-empty-state">' . omoApiEscape(omoProjectsT('projects.error.not_found')) . '</div>';
    exit;
}

$currentUserId = (int)commonGetCurrentUserId();
$createPermissionHolon = $projectHolon instanceof Holon ? $projectHolon : $rootHolon;
$canCreateEvent = !$isArchivedProject && !$project->isPendingProposal() && $currentUserId > 0
    && (
        $createPermissionHolon instanceof Holon
            ? $createPermissionHolon->isAllowed('CAN_CREATE_EVENT', true, $currentUserId)
            : commonCurrentUserHasOrganizationAccess($organizationId)
    );
$canImportEvents = omoProjectsCanImportEvents($project, $context);
$externalEvents = new ArrayProjectExternalEvent();
$externalEvents->loadForProject($projectId);
$events = array_merge($project->getEvents()->getArrayCopy(), $externalEvents->getArrayCopy());
$todayStart = new \DateTimeImmutable('today 00:00:00');
$sectionLabels = [
    'today' => omoProjectsT('projects.detail.events.section.today'),
    'tomorrow' => omoProjectsT('projects.detail.events.section.tomorrow'),
    'this_week' => omoProjectsT('projects.detail.events.section.this_week'),
    'next_week' => omoProjectsT('projects.detail.events.section.next_week'),
    'this_month' => omoProjectsT('projects.detail.events.section.this_month'),
    'next_month' => omoProjectsT('projects.detail.events.section.next_month'),
    'past' => omoProjectsT('projects.detail.events.section.past'),
];
$eventSections = [];

foreach ($events as $event) {
    if ($event instanceof ProjectExternalEvent ? $event->isSourceMissing()
        : (!($event instanceof Event) || !$event->isDraftVisibleToViewer($currentUserId))) {
        continue;
    }

    $startAt = $event->get('start_at');
    $endAt = $event->get('end_at');
    if (!($startAt instanceof \DateTimeInterface) || !($endAt instanceof \DateTimeInterface)) {
        continue;
    }

    $anchorDate = \DateTimeImmutable::createFromInterface($startAt);
    if ($anchorDate < $todayStart) {
        $anchorDate = $todayStart;
    }

    $section = $endAt < $todayStart
        ? ['key' => 'past', 'sort' => PHP_INT_MAX]
        : omoCalendarGetUpcomingSectionMetadata($anchorDate, $todayStart);
    $sectionKey = (string)$section['key'];
    $sectionLabel = isset($section['month']) && $section['month'] instanceof \DateTimeInterface
        ? omoProjectsT('projects.detail.events.month.' . (int)$section['month']->format('n')) . ' ' . $section['month']->format('Y')
        : (string)($sectionLabels[$sectionKey] ?? '');

    if (!isset($eventSections[$sectionKey])) {
        $eventSections[$sectionKey] = [
            'label' => $sectionLabel,
            'sort' => (int)$section['sort'],
            'items' => [],
        ];
    }

    $eventSections[$sectionKey]['items'][] = [
        'event' => $event,
        'sort' => (int)$startAt->format('U'),
    ];
}

foreach ($eventSections as $sectionKey => &$eventSection) {
    usort($eventSection['items'], static function (array $left, array $right) use ($sectionKey): int {
        $leftSort = (int)($left['sort'] ?? 0);
        $rightSort = (int)($right['sort'] ?? 0);
        if ($leftSort !== $rightSort) {
            return $sectionKey === 'past' ? $rightSort <=> $leftSort : $leftSort <=> $rightSort;
        }
        return (int)$left['event']->getId() <=> (int)$right['event']->getId();
    });
}
unset($eventSection);
uasort($eventSections, static function (array $left, array $right): int {
    return (int)($left['sort'] ?? 0) <=> (int)($right['sort'] ?? 0);
});

$createEventUrl = '/omo/api/calendar/create.php?oid=' . rawurlencode((string)$organizationId)
    . '&project_id=' . rawurlencode((string)$projectId)
    . '&editor_host=project';
if ($projectHolon instanceof Holon) {
    $createEventUrl .= '&cid=' . rawurlencode((string)(int)$projectHolon->getId());
}

$createEventButton = '<button type="button" class="generic-action-button generic-action-button--main"'
    . ' data-omo-project-detail-add-event'
    . ' data-omo-project-detail-add-event-url="' . omoApiEscape($createEventUrl) . '">'
    . omoApiEscape(omoProjectsT('projects.detail.events.new'))
    . '</button>';
$importUrl = '/omo/api/projects/event_import.php?oid=' . $organizationId . '&id=' . $projectId
    . '&cid=' . (int)($projectHolon instanceof Holon ? $projectHolon->getId() : 0);
$importButton = '<button type="button" class="generic-menu-item" data-omo-project-import-event-url="'
    . omoApiEscape($importUrl) . '">' . omoApiEscape(omoProjectsT('projects.events.import')) . '</button>';
if ($canCreateEvent && $canImportEvents) {
    $createEventButton = '<div class="generic-menu generic-menu--split" data-omo-project-detail-event-menu>' . $createEventButton
        . '<button type="button" class="generic-menu-toggle" data-omo-project-detail-event-menu-toggle aria-expanded="false"'
        . ' aria-label="' . omoApiEscape(omoProjectsT('projects.detail.events.menu')) . '">&#9662;</button>'
        . '<div class="generic-menu-panel" data-omo-project-detail-event-menu-panel hidden>' . $importButton . '</div></div>';
} elseif (!$canCreateEvent && $canImportEvents) {
    $createEventButton = '<button type="button" class="generic-action-button generic-action-button--main" data-omo-project-import-event-url="'
        . omoApiEscape($importUrl) . '">' . omoApiEscape(omoProjectsT('projects.events.import')) . '</button>';
}

if (count($eventSections) === 0) {
    echo '<div class="omo-project-detail__events-empty">'
        . '<h3 class="generic-card-title generic-card-title--medium">' . omoApiEscape(omoProjectsT('projects.detail.events.empty')) . '</h3>';
    if ($canCreateEvent || $canImportEvents) {
        echo '<p class="generic-description generic-description--small">' . omoApiEscape(omoProjectsT('projects.detail.events.empty_hint')) . '</p>'
            . $createEventButton;
    }
    echo '</div>';
    exit;
}
?>
<?php if ($canCreateEvent || $canImportEvents): ?>
    <div class="omo-project-detail__events-actions">
        <?= $createEventButton ?>
    </div>
<?php endif; ?>
<div class="omo-project-detail__events-list">
    <?php foreach ($eventSections as $eventSection): ?>
        <section class="omo-project-detail__events-group">
            <h3 class="omo-project-detail__events-group-title generic-card-title generic-card-title--small"><?= omoApiEscape((string)$eventSection['label']) ?></h3>
            <div class="omo-project-detail__events-group-items">
                <?php foreach ($eventSection['items'] as $eventItem): ?>
                    <?php
                    $event = $eventItem['event'];
                    $isExternal = $event instanceof ProjectExternalEvent;
                    $startAt = $event->get('start_at');
                    $endAt = $event->get('end_at');
                    $isAllDay = (bool)$event->get('is_all_day');
                    $dateLabel = $startAt instanceof \DateTimeInterface ? $startAt->format('d.m.Y') : '';
                    $timeLabel = '';
                    if ($isAllDay) {
                        $timeLabel = omoProjectsT('projects.detail.events.all_day');
                    } elseif ($startAt instanceof \DateTimeInterface) {
                        $timeLabel = $startAt->format('H:i');
                        if ($endAt instanceof \DateTimeInterface) {
                            $timeLabel .= '–' . $endAt->format('H:i');
                        }
                    }
                    $status = $isExternal ? Event::STATUS_CONFIRMED : Event::normalizeStatus($event->get('status'));
                    $statusCatalog = Event::getStatusCatalog();
                    $statusLabel = $isExternal ? omoProjectsT('projects.events.external', ['calendar' => $event->get('calendar_title')])
                        : trim((string)($statusCatalog[$status]['label'] ?? ''));
                    $eventPermissionHolon = $isExternal ? null : omoCalendarResolveEventPermissionHolon($event, $rootHolon);
                    $canEditEvent = !$isExternal && omoCalendarCanEditEvent($event, $organizationId, $currentUserId, $rootHolon, true);
                    $canDetachEvent = $canImportEvents && ($isExternal || $canEditEvent);
                    $canDeleteEvent = !$isExternal && $currentUserId > 0 && (
                        $eventPermissionHolon instanceof Holon
                            ? $eventPermissionHolon->isAllowed('CAN_DELETE_EVENT', false, $currentUserId)
                            : commonCurrentUserHasOrganizationAccess($organizationId)
                    );
                    $eventEditorUrl = '/omo/api/calendar/create.php?oid=' . rawurlencode((string)$organizationId)
                        . '&project_id=' . rawurlencode((string)$projectId)
                        . '&editor_host=project';
                    if ($projectHolon instanceof Holon) {
                        $eventEditorUrl .= '&cid=' . rawurlencode((string)(int)$projectHolon->getId());
                    }
                    $eventId = (int)$event->getId();
                    $externalDetailUrl = $importUrl . '&action=detail&link_id=' . $eventId;
                    $eventEditUrl = $eventEditorUrl . '&id=' . rawurlencode((string)$eventId);
                    $eventDuplicateUrl = $eventEditorUrl . '&duplicate_id=' . rawurlencode((string)$eventId);
                    $eventDeleteUrl = '/omo/api/calendar/delete.php?oid=' . rawurlencode((string)$organizationId)
                        . '&id=' . rawurlencode((string)$eventId);
                    ?>
                    <div class="omo-project-detail__event-row">
                        <div class="omo-project-detail__event-item is-status-<?= omoApiEscape($status) ?>">
                    <a
                        class="omo-project-detail__event-link"
                        href="<?= omoApiEscape($isExternal ? $externalDetailUrl : '#calendar-e' . $eventId) ?>"
                        <?php if ($isExternal): ?>data-omo-project-external-event-url="<?= omoApiEscape($externalDetailUrl) ?>"<?php else: ?>data-omo-project-detail-event-link<?php endif; ?>
                        data-event-id="<?= $eventId ?>"
                    >
                        <span class="omo-project-detail__event-date" aria-hidden="true">
                            <strong><?= omoApiEscape($startAt instanceof \DateTimeInterface ? $startAt->format('d') : '–') ?></strong>
                            <span><?= omoApiEscape($startAt instanceof \DateTimeInterface ? $startAt->format('m.Y') : '') ?></span>
                        </span>
                        <span class="omo-project-detail__event-copy">
                            <strong><?= omoApiEscape(trim((string)$event->get('title')) !== '' ? trim((string)$event->get('title')) : ('Événement #' . (int)$event->getId())) ?></strong>
                            <span><?= omoApiEscape(implode(' · ', array_values(array_filter([$dateLabel, $timeLabel, $statusLabel], static function ($value) {
                                return trim((string)$value) !== '';
                            })))) ?></span>
                        </span>
                    </a>
                    <?php if ($canEditEvent || $canDeleteEvent || (!$isExternal && $canCreateEvent) || $canDetachEvent): ?>
                        <div class="generic-menu omo-project-detail__event-menu" data-omo-project-detail-event-menu>
                            <button
                                type="button"
                                class="generic-menu-toggle omo-project-detail__event-menu-toggle"
                                data-omo-project-detail-event-menu-toggle
                                aria-label="<?= omoApiEscape(omoProjectsT('projects.detail.events.menu')) ?>"
                                aria-expanded="false"
                            >&#8230;</button>
                            <div class="generic-menu-panel omo-project-detail__event-menu-panel" data-omo-project-detail-event-menu-panel hidden>
                                <?php if ($canEditEvent): ?>
                                    <button type="button" class="generic-menu-item" data-omo-project-detail-event-editor-url="<?= omoApiEscape($eventEditUrl) ?>">
                                        <?= omoApiEscape(omoProjectsT('projects.detail.events.edit')) ?>
                                    </button>
                                <?php endif; ?>
                                <?php if (!$isExternal && $canCreateEvent): ?>
                                    <button type="button" class="generic-menu-item" data-omo-project-detail-event-editor-url="<?= omoApiEscape($eventDuplicateUrl) ?>">
                                        <?= omoApiEscape(omoProjectsT('projects.detail.events.duplicate')) ?>
                                    </button>
                                <?php endif; ?>
                                <?php if ($canDetachEvent): ?>
                                    <button type="button" class="generic-menu-item" data-omo-project-detach-event
                                        data-import-url="<?= omoApiEscape($importUrl) ?>" data-event-id="<?= $eventId ?>"
                                        data-source="<?= $isExternal ? 'external' : 'internal' ?>" data-project-id="<?= $projectId ?>"
                                        data-csrf="<?= omoApiEscape(commonCsrfToken()) ?>"><?= omoApiEscape(omoProjectsT('projects.events.detach')) ?></button>
                                <?php endif; ?>
                                <?php if ($canDeleteEvent): ?>
                                    <button
                                        type="button"
                                        class="generic-menu-item generic-menu-item--danger"
                                        data-omo-project-detail-event-delete-url="<?= omoApiEscape($eventDeleteUrl) ?>"
                                        data-project-id="<?= (int)$projectId ?>"
                                        data-confirm="<?= omoApiEscape(omoProjectsT('projects.detail.events.confirm_delete')) ?>"
                                    ><?= omoApiEscape(omoProjectsT('projects.detail.events.delete')) ?></button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
</div>
