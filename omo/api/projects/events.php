<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';
require_once dirname(__DIR__, 3) . '/common/calendar/upcoming_sections.php';

use dbObject\Event;
use dbObject\Holon;
use dbObject\Project;

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
$canCreateEvent = !$isArchivedProject && $currentUserId > 0
    && (
        $createPermissionHolon instanceof Holon
            ? $createPermissionHolon->isAllowed('CAN_CREATE_EVENT', true, $currentUserId)
            : commonCurrentUserHasOrganizationAccess($organizationId)
    );
$events = $project->getEvents();
$todayStart = new \DateTimeImmutable('today 00:00:00');
$sectionLabels = [
    'today' => omoProjectsT('projects.detail.events.section.today'),
    'tomorrow' => omoProjectsT('projects.detail.events.section.tomorrow'),
    'this_week' => omoProjectsT('projects.detail.events.section.this_week'),
    'next_week' => omoProjectsT('projects.detail.events.section.next_week'),
    'this_month' => omoProjectsT('projects.detail.events.section.this_month'),
    'next_month' => omoProjectsT('projects.detail.events.section.next_month'),
];
$eventSections = [];

foreach ($events as $event) {
    if (!($event instanceof Event)) {
        continue;
    }

    $startAt = $event->get('start_at');
    $endAt = $event->get('end_at');
    if (!($startAt instanceof \DateTimeInterface) || !($endAt instanceof \DateTimeInterface) || $endAt < $todayStart) {
        continue;
    }

    $anchorDate = \DateTimeImmutable::createFromInterface($startAt);
    if ($anchorDate < $todayStart) {
        $anchorDate = $todayStart;
    }

    $section = omoCalendarGetUpcomingSectionMetadata($anchorDate, $todayStart);
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

foreach ($eventSections as &$eventSection) {
    usort($eventSection['items'], static function (array $left, array $right): int {
        $leftSort = (int)($left['sort'] ?? 0);
        $rightSort = (int)($right['sort'] ?? 0);
        if ($leftSort !== $rightSort) {
            return $leftSort <=> $rightSort;
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

if (count($eventSections) === 0) {
    echo '<div class="omo-project-detail__events-empty">'
        . '<h3 class="generic-card-title generic-card-title--medium">' . omoApiEscape(omoProjectsT('projects.detail.events.empty')) . '</h3>';
    if ($canCreateEvent) {
        echo '<p class="generic-description generic-description--small">' . omoApiEscape(omoProjectsT('projects.detail.events.empty_hint')) . '</p>'
            . $createEventButton;
    }
    echo '</div>';
    exit;
}
?>
<?php if ($canCreateEvent): ?>
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
                    $status = Event::normalizeStatus($event->get('status'));
                    $statusCatalog = Event::getStatusCatalog();
                    $statusLabel = trim((string)($statusCatalog[$status]['label'] ?? ''));
                    ?>
                    <a
                        class="omo-project-detail__event-item"
                        href="#calendar-e<?= (int)$event->getId() ?>"
                        data-omo-project-detail-event-link
                        data-event-id="<?= (int)$event->getId() ?>"
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
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
</div>
