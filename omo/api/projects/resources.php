<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';
require_once dirname(__DIR__) . '/activities/shared.php';
require_once dirname(__DIR__) . '/stats/shared.php';

use dbObject\ArrayControlActivity;
use dbObject\ArrayProjectIndicator;
use dbObject\ArrayProjectRecurringTask;
use dbObject\ArrayStatIndicator;
use dbObject\ControlActivity;
use dbObject\ControlTaskCheck;
use dbObject\Holon;
use dbObject\Project;
use dbObject\RecurrenceSchedule;
use dbObject\StatIndicator;
use dbObject\StatIndicatorReferencePoint;
use dbObject\StatIndicatorValue;

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$projectId = (int)($_GET['id'] ?? 0);
$type = (string)($_GET['type'] ?? '');
$isIndicator = $type === 'indicator';
$isTask = $type === 'recurring_task';
$context = omoProjectsResolveContext($organizationId, (int)($_GET['cid'] ?? 0));
$project = new Project();
$organization = $context['organization'] ?? null;
$app = $isIndicator ? 'stats' : 'activities';
if ((!$isIndicator && !$isTask) || empty($context['status']) || $projectId <= 0
    || !$project->load($projectId) || (int)$project->get('IDorganization') !== $organizationId
    || !omoProjectsCanViewProject($project, $context)
    || !$organization->isApplicationEnabled($app)) {
    http_response_code(404);
    echo '<div class="omo-empty-state">' . omoApiEscape(omoProjectsT('projects.error.not_found')) . '</div>';
    exit;
}
$projectHolon = $project->getHolon();
$rootHolon = $context['rootHolon'] ?? null;
if ($projectHolon instanceof Holon && (!($rootHolon instanceof Holon)
    || !$projectHolon->isDescendantOf((int)$rootHolon->getId(), true)
    || !$projectHolon->canViewDetail())) {
    http_response_code(404);
    exit;
}

$links = $isIndicator ? new ArrayProjectIndicator() : new ArrayProjectRecurringTask();
$links->loadForProject($projectId);
$field = $isIndicator ? 'IDstatindicator' : 'IDrecurringtask';
$attachedIds = [];
foreach ($links as $link) {
    $attachedIds[(int)$link->get($field)] = true;
}

if ($isIndicator) {
    $resources = new ArrayStatIndicator();
    $resources->loadForOrganization($organizationId);
} else {
    $resources = new ArrayControlActivity();
    $resources->load([
        'where' => [['field' => 'IDorganization', 'value' => $organizationId], ['field' => 'active', 'value' => 1]],
        'orderBy' => [['field' => 'title', 'dir' => 'ASC'], ['field' => 'id', 'dir' => 'ASC']],
    ]);
}
$visible = [];
foreach ($resources as $resource) {
    if ($isIndicator && !($resource instanceof StatIndicator && $resource->canView())) {
        continue;
    }
    if ($isTask && !($resource instanceof ControlActivity && omoActivityCanView($resource))) {
        continue;
    }
    $visible[(int)$resource->getId()] = $resource;
}
$visibleAttachedIds = array_intersect_key($attachedIds, $visible);
$now = $isTask ? new DateTimeImmutable('now') : null;
$canManage = (int)$project->get('active') === 1 && !$project->isPendingProposal()
    && omoProjectsCanManageProject($project, $context);
$resourceHolon = $projectHolon instanceof Holon ? $projectHolon : ($rootHolon instanceof Holon ? $rootHolon : null);
$canCreate = false;
if ($canManage) {
    if ($isIndicator) {
        $canCreate = omoStatsCanCreateContext(array_replace($context, ['currentHolon' => $resourceHolon]));
    } else {
        $canCreate = omoActivityCanUsePermission($resourceHolon, 'CAN_CREATE_RECURRING_TASK', $organizationId);
    }
}
$cid = $resourceHolon instanceof Holon ? (int)$resourceHolon->getId() : 0;
$baseUrl = '/omo/api/projects/resources.php?oid=' . $organizationId . '&id=' . $projectId
    . '&type=' . rawurlencode($type) . '&cid=' . $cid;
if (isset($_GET['picker'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $items = [];
    foreach ($visible as $resourceId => $resource) {
        if (isset($attachedIds[$resourceId]) || ($isIndicator && $resource->isHiddenFromCatalog())) {
            continue;
        }
        $itemHolon = $resource->getHolon();
        $items[] = [
            'id' => $resourceId,
            'title' => trim((string)$resource->get($isIndicator ? 'name' : 'title')),
            'contextHolonId' => (int)$resource->get('IDholon'),
            'contextLabel' => $itemHolon instanceof Holon
                ? trim((string)$itemHolon->getDisplayName())
                : trim((string)$organization->get('name')),
        ];
    }
    echo json_encode([
        'success' => true, 'organizationId' => $organizationId, 'projectId' => $projectId, 'type' => $type,
        'projectHolonId' => $cid, 'items' => $items,
        'scopeLabels' => [
            'local' => omoProjectsT('projects.scope.contextual'),
            'children' => omoProjectsT('projects.scope.children'),
            'descendants' => omoProjectsT('projects.scope.descendants'),
        ],
        'labels' => [
            'title' => omoProjectsT($isIndicator ? 'projects.resources.import_indicator' : 'projects.resources.import_recurring_task'),
            'existing' => omoProjectsT('projects.resources.existing'),
            'search' => omoProjectsT('projects.resources.search'),
            'none' => omoProjectsT('projects.resources.none'),
            'attach' => omoProjectsT('projects.resources.attach'),
            'error' => omoProjectsT('projects.resources.error'),
            'cancel' => omoProjectsT('projects.action.cancel'),
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
?>
<?php if ($canManage): ?>
<div class="omo-project-detail__documents-actions">
        <?php if ($canCreate): ?>
        <div class="generic-menu generic-menu--split" data-omo-project-detail-document-menu>
            <button type="button" class="generic-action-button generic-action-button--main" <?= $isIndicator ? 'data-omo-project-indicator-editor-url' : 'data-omo-project-recurring-task-editor-url' ?>="<?= omoApiEscape('/omo/api/' . ($isIndicator ? 'stats' : 'activities') . '/edit.php?oid=' . $organizationId . '&cid=' . $cid . '&project_id=' . $projectId) ?>"><?= omoApiEscape(omoProjectsT($isIndicator ? 'projects.resources.create_indicator' : 'projects.resources.create_recurring_task')) ?></button>
            <button type="button" class="generic-menu-toggle" data-omo-project-detail-document-menu-toggle aria-expanded="false" aria-label="<?= omoApiEscape(omoProjectsT($isIndicator ? 'projects.resources.indicator_menu' : 'projects.resources.recurring_task_menu')) ?>">&#9662;</button>
            <div class="generic-menu-panel" data-omo-project-detail-document-menu-panel hidden>
        <?php endif; ?>
            <button type="button" class="<?= $canCreate ? 'generic-menu-item' : 'generic-action-button generic-action-button--main' ?>" data-omo-project-resource-add data-resource-url="<?= omoApiEscape($baseUrl . '&picker=1') ?>" data-resource-title="<?= omoApiEscape(omoProjectsT($isIndicator ? 'projects.resources.import_indicator' : 'projects.resources.import_recurring_task')) ?>"><?= omoApiEscape(omoProjectsT($isIndicator ? 'projects.resources.import_indicator' : 'projects.resources.import_recurring_task')) ?></button>
        <?php if ($canCreate): ?></div></div><?php endif; ?>
</div>
<?php endif; ?>
<?php if (!$visibleAttachedIds): ?>
<div class="omo-project-detail__documents-empty"><h3 class="generic-card-title generic-card-title--medium"><?= omoApiEscape(omoProjectsT('projects.resources.empty')) ?></h3></div>
<?php else: ?>
<div class="generic-file-list generic-file-list--structured generic-file-list--embedded omo-project-detail__resource-list<?= $isIndicator ? ' omo-stats' : '' ?>">
    <div class="generic-file-list__table">
    <?php foreach ($links as $link): ?>
        <?php $resourceId = (int)$link->get($field); $resource = $visible[$resourceId] ?? null;
        if (!$resource) { continue; }
        $title = trim((string)$resource->get($isIndicator ? 'name' : 'title'));
        $resourceUrl = '#' . ($isIndicator ? 'stats-i' : 'activities-d') . $resourceId;
        if ($isIndicator) {
            $values = omoStatsCollectionItems($resource->getMeasurements(), StatIndicatorValue::class);
            $referencePoints = omoStatsCollectionItems($resource->getReferencePoints(), StatIndicatorReferencePoint::class);
            $latestValue = $values ? $values[count($values) - 1] : null;
            $referencePercentage = omoStatsGetIndicatorReferencePercentage($resource, $latestValue, $referencePoints);
            $overdueSeverity = (string)omoStatsGetIndicatorOverdueInfo($resource)['severity'];
        } else {
            $state = $resource->getOccurrenceState($now);
            $itemHolon = $resource->getHolon();
            $stateKey = (string)($state['state'] ?? 'upcoming');
            $occurrenceAt = $state['occurrenceAt'] ?? null;
            $check = $state['check'] ?? null;
            $checkedAt = $check instanceof ControlTaskCheck ? $check->get('checked_at') : null;
            if (in_array($stateKey, ['checked', 'late'], true) && $checkedAt instanceof DateTimeInterface) {
                $dateLabel = omoActivityT($stateKey === 'late' ? 'activity.checked.late_on' : 'activity.checked.on', ['date' => $checkedAt->format('d.m.Y à H:i')]);
            } elseif ($stateKey === 'missed') {
                $dateLabel = omoActivityOverdueLabel($state, $now);
            } else {
                $dateLabel = $occurrenceAt instanceof DateTimeInterface ? $occurrenceAt->format('d.m.Y à H:i') : '';
            }
            $frequency = RecurrenceSchedule::normalizeFrequency($resource->get('frequency'));
        }
        ?>
        <div class="generic-file-list__item-shell omo-project-detail__resource-shell<?= $isIndicator ? '' : ' omo-activity-row-shell omo-activity-row-shell--' . omoApiEscape($stateKey) ?>">
            <a class="generic-file-list__row omo-project-detail__resource-row<?= $isIndicator ? ' omo-stats-compact__row' . ($overdueSeverity === 'error' ? ' omo-stats-compact__row--overdue' : ($overdueSeverity === 'warning' ? ' omo-stats-compact__row--warning' : '')) : ' omo-activity-row' ?>" href="<?= omoApiEscape($resourceUrl) ?>" data-omo-project-resource-link>
                <span class="generic-file-list__cell generic-file-list__cell--name">
                    <span class="generic-file-list__name-main">
                        <?php if ($isIndicator): ?><span class="omo-stats-compact__dot" aria-hidden="true"></span><?php else: ?><span class="generic-file-list__icon-box omo-activity-row__icon" aria-hidden="true"><img src="/omo/images/tools/control-list.png" alt=""></span><?php endif; ?>
                        <span class="generic-file-list__title-block">
                            <strong class="generic-file-list__title"><?= omoApiEscape($title) ?></strong>
                            <span class="generic-file-list__meta-line"><?= omoApiEscape($isIndicator
                                ? omoStatsT('stats.card.value_count', ['count' => count($values)]) . ' · ' . omoStatsContextLabel($resource) . ' · ' . omoStatsResponsibleAssignmentLabel($resource)
                                : omoActivityFrequencyLabel($frequency) . ' · ' . omoActivityScheduleLabel($frequency, $resource->get('schedule')) . ' · ' . ($itemHolon instanceof Holon ? $itemHolon->getDisplayName() : (string)$organization->get('name'))) ?></span>
                        </span>
                    </span>
                </span>
                <?php if ($isIndicator): ?>
                    <span class="generic-file-list__cell omo-stats-compact__latest" data-label="<?= omoApiEscape(omoStatsT('stats.column.latest')) ?>">
                        <?php if ($latestValue instanceof StatIndicatorValue): ?>
                            <span class="omo-stats-compact__latest-value"><strong><?= omoApiEscape(omoStatsFormatNumber($latestValue->get('value'))) ?></strong><?php if (is_numeric($referencePercentage)): ?> <span class="omo-stats-reference-percentage">(<?= omoApiEscape(omoStatsFormatNumber($referencePercentage)) ?>%)</span><?php endif; ?></span>
                            <time><?= omoApiEscape(omoStatsFormatDateTime($latestValue->get('measured_at'), false)) ?></time>
                        <?php else: ?><span>—</span><?php endif; ?>
                    </span>
                    <div class="generic-file-list__cell omo-stats-compact__chart" data-label="<?= omoApiEscape(omoStatsT('stats.column.history')) ?>"><?= omoStatsRenderChart($resource, $values, $referencePoints, 'compact', $overdueSeverity) ?></div>
                <?php else: ?>
                    <span class="generic-file-list__cell" data-label="<?= omoApiEscape(omoActivityT('activity.column.next')) ?>"><time class="omo-activity-row__date<?= $stateKey === 'missed' ? ' is-missed' : '' ?>"><?= omoApiEscape($dateLabel) ?></time></span>
                    <span class="generic-file-list__cell" data-label="<?= omoApiEscape(omoActivityT('activity.column.status')) ?>"><span class="omo-activity-badge omo-activity-badge--<?= omoApiEscape($stateKey) ?>"><?= omoApiEscape(omoActivityStateLabel($state, $now)) ?></span></span>
                <?php endif; ?>
            </a>
            <?php if ($canManage): ?>
            <div class="generic-menu omo-project-detail__document-menu" data-omo-project-detail-document-menu>
                <button type="button" class="generic-menu-toggle omo-project-detail__document-menu-toggle" data-omo-project-detail-document-menu-toggle aria-label="<?= omoApiEscape(omoProjectsT('projects.resources.detach')) ?>" aria-expanded="false">&#8230;</button>
                <div class="generic-menu-panel omo-project-detail__document-menu-panel" data-omo-project-detail-document-menu-panel hidden>
                    <button type="button" class="generic-menu-item" data-omo-project-resource-detach data-project-id="<?= $projectId ?>" data-resource-type="<?= omoApiEscape($type) ?>" data-resource-id="<?= $resourceId ?>"><?= omoApiEscape(omoProjectsT('projects.resources.detach')) ?></button>
                </div>
            </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
