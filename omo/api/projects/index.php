<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\ArrayProject;
use dbObject\Holon;
use dbObject\Project;

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$currentHolonId = isset($_GET['cid']) && is_numeric($_GET['cid']) ? (int)$_GET['cid'] : 0;
$context = omoProjectsResolveContext($organizationId, $currentHolonId);

if (empty($context['status'])) {
    http_response_code(403);
    ?>
    <div class="omo-projects omo-panel-view">
        <div class="omo-panel-view__body"><div class="omo-panel-view__body_content"><div class="omo-empty-state"><?= omoApiEscape((string)($context['message'] ?? omoProjectsT('projects.error.context'))) ?></div></div></div>
    </div>
    <?php
    exit;
}

$organization = $context['organization'];
$rootHolon = $context['rootHolon'];
$currentHolon = $context['currentHolon'];
$canToggleScope = $currentHolon instanceof Holon;
$availableScopes = omoApiGetAvailableContextScopes($canToggleScope, $currentHolon, $rootHolon);
$projectScope = omoApiNormalizeContextScope($_GET['project_scope'] ?? 'contextual', $availableScopes);
$projectAssignment = strtolower(trim((string)($_GET['project_assignment'] ?? 'all')));
$projectAssignment = $projectAssignment === 'mine' ? 'mine' : 'all';
$currentUserId = function_exists('commonGetCurrentUserId') ? (int)commonGetCurrentUserId() : 0;
$projectQuickSearch = trim((string)($_GET['project_query'] ?? ''));
$projectView = strtolower(trim((string)($_GET['project_view'] ?? 'kanban')));
$projectView = in_array($projectView, ['list', 'gantt'], true) ? $projectView : 'kanban';
$projectListSort = strtolower(trim((string)($_GET['project_sort'] ?? 'planned')));
$projectListSort = in_array($projectListSort, ['priority', 'importance', 'holon'], true) ? $projectListSort : 'planned';
if ($projectListSort === 'holon' && !in_array($projectScope, ['children', 'descendants'], true)) {
    $projectListSort = 'planned';
}
$canUseHolonSort = in_array($projectScope, ['children', 'descendants'], true);
$openProjectTargetId = isset($_GET['open_project_id']) && is_numeric($_GET['open_project_id']) ? (int)$_GET['open_project_id'] : 0;
$openProjectMode = strtolower(trim((string)($_GET['open_project_mode'] ?? '')));
if (!in_array($openProjectMode, ['detail', 'edit', 'create'], true)) {
    $openProjectMode = '';
}
if ($openProjectMode === 'create') {
    $openProjectTargetId = 0;
} elseif ($openProjectTargetId <= 0) {
    $openProjectMode = '';
}
$descendantHolonIds = $projectScope === 'descendants' && $currentHolon instanceof Holon
    ? omoApiGetDescendantHolonIds($currentHolon)
    : [];
$scopeHolonIds = $projectScope === 'children'
    ? omoApiGetDirectChildScopeHolonIds($currentHolon)
    : $descendantHolonIds;

$allProjects = new ArrayProject();
$allProjects->loadForOrganization($organizationId);
$projects = new ArrayProject();
$scopeCurrentHolonId = $currentHolon instanceof Holon ? (int)$currentHolon->getId() : 0;
foreach ($allProjects as $allProject) {
    if (
        !($allProject instanceof Project)
        || !omoProjectsScopeContainsProject($allProject, $projectScope, $scopeCurrentHolonId, $scopeHolonIds)
        || ($projectAssignment === 'mine' && (int)$allProject->get('IDuser') !== $currentUserId)
    ) {
        continue;
    }
    $projects[] = $allProject;
}
$projectsById = [];
$projectsByParent = [];
foreach ($allProjects as $allProject) {
    if (!($allProject instanceof Project) || (int)$allProject->getId() <= 0) {
        continue;
    }
    $projectsById[(int)$allProject->getId()] = $allProject;
    $parentId = (int)$allProject->get('IDproject_parent');
    if ($parentId > 0) {
        $projectsByParent[$parentId][] = $allProject;
    }
}

$columns = [
    Project::STATUS_READY,
    Project::STATUS_IN_PROGRESS,
    Project::STATUS_BLOCKED,
    Project::STATUS_REVIEW,
    Project::STATUS_DONE,
    Project::STATUS_SOMEDAY,
];
$projectCount = 0;
$projectsByStatus = array_fill_keys($columns, []);
$subprojectSummaryMemo = [];
$visibleProjectIds = [];
foreach ($projects as $project) {
    if ($project instanceof Project && (int)$project->getId() > 0) {
        $visibleProjectIds[(int)$project->getId()] = true;
    }
}

foreach ($projects as $project) {
    if (!($project instanceof Project)) {
        continue;
    }
    if (!omoProjectsIsKanbanVisible($project, $projectsById, $projectsByParent, $visibleProjectIds)) {
        continue;
    }

    $status = Project::normalizeStatus($project->get('status'));
    if (!isset($projectsByStatus[$status])) {
        $status = Project::STATUS_SOMEDAY;
    }

    $projectHolon = $project->getHolon();
    $responsible = $project->getResponsible();
    $contextLabel = $projectHolon instanceof Holon
        ? trim((string)$projectHolon->getDisplayName())
        : trim((string)$organization->get('name'));
    $responsibleLabel = omoProjectsGetUserLabel($responsible);
    $plannedEnd = $project->get('planned_end_date');
    $subprojectSummary = omoProjectsBuildStatusBar($project, $projectsByParent, $subprojectSummaryMemo);
    $projectSize = Project::normalizeSize($project->get('project_size'));
    $projectsByStatus[$status][] = [
        'project' => $project,
        'contextLabel' => $contextLabel,
        'responsibleLabel' => $responsibleLabel,
        'plannedEnd' => omoProjectsFormatDate($plannedEnd),
        'holonLabel' => $contextLabel,
        'startSort' => $project->get('planned_start_date') instanceof \DateTimeInterface
            ? $project->get('planned_start_date')->format('Y-m-d')
            : '',
        'endSort' => $project->get('planned_end_date') instanceof \DateTimeInterface
            ? $project->get('planned_end_date')->format('Y-m-d')
            : '',
        'priority' => Project::normalizeLevel($project->get('priority')),
        'calculatedImportance' => max(0.0, min(1.0, (float)$project->get('calculated_importance'))),
        'subprojectSummary' => $subprojectSummary,
        'projectSize' => $projectSize,
    ];
    $projectCount++;
}

$listProjectItems = [];
foreach ($projects as $project) {
    if (!($project instanceof Project) || (int)$project->getId() <= 0) {
        continue;
    }

    $projectHolon = $project->getHolon();
    $listProjectItems[] = [
        'project' => $project,
        'holonLabel' => $projectHolon instanceof Holon
            ? trim((string)$projectHolon->getDisplayName())
            : trim((string)$organization->get('name')),
        'holonId' => $projectHolon instanceof Holon ? (int)$projectHolon->getId() : 0,
        'responsibleLabel' => omoProjectsGetUserLabel($project->getResponsible()),
        'status' => Project::normalizeStatus($project->get('status')),
        'startDate' => omoProjectsFormatDate($project->get('planned_start_date')),
        'endDate' => omoProjectsFormatDate($project->get('planned_end_date')),
        'startSort' => $project->get('planned_start_date') instanceof \DateTimeInterface
            ? $project->get('planned_start_date')->format('Y-m-d')
            : '',
        'endSort' => $project->get('planned_end_date') instanceof \DateTimeInterface
            ? $project->get('planned_end_date')->format('Y-m-d')
            : '',
        'priority' => Project::normalizeLevel($project->get('priority')),
        'calculatedImportance' => max(0.0, min(1.0, (float)$project->get('calculated_importance'))),
        'projectSize' => Project::normalizeSize($project->get('project_size')),
    ];
}
$compareProjectItems = static function (array $left, array $right) use ($projectListSort) {
    $comparePlanning = static function (array $first, array $second) {
        $firstStart = (string)$first['startSort'];
        $secondStart = (string)$second['startSort'];
        if ($firstStart === '' && $secondStart !== '') {
            return 1;
        }
        if ($firstStart !== '' && $secondStart === '') {
            return -1;
        }
        if ($firstStart !== $secondStart) {
            return strcmp($firstStart, $secondStart);
        }
        return strcmp((string)$first['endSort'], (string)$second['endSort']);
    };

    if ($projectListSort === 'importance') {
        $importanceComparison = (float)($right['calculatedImportance'] ?? 0.0) <=> (float)($left['calculatedImportance'] ?? 0.0);
        if ($importanceComparison !== 0) {
            return $importanceComparison;
        }
    } elseif ($projectListSort === 'holon') {
        $holonComparison = strcasecmp((string)$left['holonLabel'], (string)$right['holonLabel']);
        if ($holonComparison !== 0) {
            return $holonComparison;
        }
    }

    if ($projectListSort === 'priority') {
        $leftPriority = $left['priority'] === null ? PHP_INT_MAX : (int)$left['priority'];
        $rightPriority = $right['priority'] === null ? PHP_INT_MAX : (int)$right['priority'];
        if ($leftPriority !== $rightPriority) {
            return $leftPriority <=> $rightPriority;
        }
    }

    $planningComparison = $comparePlanning($left, $right);
    if ($planningComparison !== 0) {
        return $planningComparison;
    }
    return strcasecmp((string)$left['project']->get('title'), (string)$right['project']->get('title'));
};
usort($listProjectItems, $compareProjectItems);
$activeListProjectItems = [];
$completedListProjectItems = [];
foreach ($listProjectItems as $item) {
    if ($item['status'] === Project::STATUS_DONE) {
        $completedListProjectItems[] = $item;
    } else {
        $activeListProjectItems[] = $item;
    }
}
foreach ($projectsByStatus as &$columnItems) {
    usort($columnItems, $compareProjectItems);
}
unset($columnItems);

$listProjectGroups = [];
if ($projectListSort === 'importance') {
    $listProjectGroups['importance'] = [
        'label' => omoProjectsT('projects.sort.importance'),
        'items' => $listProjectItems,
    ];
} elseif ($projectListSort === 'priority') {
    foreach ([1, 2, 3, 4, 5] as $priority) {
        $listProjectGroups['priority-' . $priority] = [
            'label' => 'P' . $priority,
            'items' => [],
        ];
    }
    $listProjectGroups['priority-none'] = [
        'label' => omoProjectsT('projects.list.priority.none'),
        'items' => [],
    ];

    foreach ($listProjectItems as $item) {
        $priority = $item['priority'];
        $groupKey = $priority === null ? 'priority-none' : 'priority-' . (int)$priority;
        $listProjectGroups[$groupKey]['items'][] = $item;
    }
} elseif ($projectListSort === 'holon') {
    foreach ($listProjectItems as $item) {
        $groupKey = 'holon-' . (int)$item['holonId'];
        if (!isset($listProjectGroups[$groupKey])) {
            $listProjectGroups[$groupKey] = [
                'label' => $item['holonLabel'],
                'items' => [],
            ];
        }
        $listProjectGroups[$groupKey]['items'][] = $item;
    }
} else {
    $today = new \DateTimeImmutable('today');
    $tomorrow = $today->modify('+1 day');
    $afterTomorrow = $today->modify('+2 days');
    $nextWeekStart = $today->modify('monday next week');
    $nextWeekEnd = $nextWeekStart->modify('+6 days');
    $listProjectGroups = [
        'overdue' => ['label' => omoProjectsT('projects.list.planned.overdue'), 'items' => []],
        'in-progress' => ['label' => omoProjectsT('projects.list.planned.in_progress'), 'items' => []],
        'tomorrow' => ['label' => omoProjectsT('projects.list.planned.tomorrow'), 'items' => []],
        'after-tomorrow' => ['label' => omoProjectsT('projects.list.planned.after_tomorrow'), 'items' => []],
        'this-week' => ['label' => omoProjectsT('projects.list.planned.this_week'), 'items' => []],
        'next-week' => ['label' => omoProjectsT('projects.list.planned.next_week'), 'items' => []],
        'later' => ['label' => omoProjectsT('projects.list.planned.later'), 'items' => []],
        'none' => ['label' => omoProjectsT('projects.list.planned.none'), 'items' => []],
    ];

    foreach ($activeListProjectItems as $item) {
        $startDate = $item['startSort'] !== '' ? new \DateTimeImmutable($item['startSort']) : null;
        $endDate = $item['endSort'] !== '' ? new \DateTimeImmutable($item['endSort']) : null;
        if ($endDate instanceof \DateTimeImmutable && $endDate < $today) {
            $groupKey = 'overdue';
        } elseif ($startDate instanceof \DateTimeImmutable && $startDate <= $today) {
            $groupKey = 'in-progress';
        } elseif (!($startDate instanceof \DateTimeImmutable)) {
            $groupKey = 'none';
        } elseif ($startDate == $tomorrow) {
            $groupKey = 'tomorrow';
        } elseif ($startDate == $afterTomorrow) {
            $groupKey = 'after-tomorrow';
        } elseif ($startDate < $nextWeekStart) {
            $groupKey = 'this-week';
        } elseif ($startDate <= $nextWeekEnd) {
            $groupKey = 'next-week';
        } else {
            $groupKey = 'later';
        }
        $listProjectGroups[$groupKey]['items'][] = $item;
    }
}
if ($projectListSort === 'planned') {
    $listProjectGroups['done'] = [
        'label' => omoProjectsT('projects.list.done'),
        'items' => $completedListProjectItems,
    ];
}
$listProjectGroups = array_filter($listProjectGroups, static fn (array $group): bool => count($group['items']) > 0);

$kanbanItemsById = [];
foreach ($projectsByStatus as $statusItems) {
    foreach ($statusItems as $statusItem) {
        $kanbanItemsById[(int)$statusItem['project']->getId()] = $statusItem;
    }
}

$kanbanGroups = [];
if ($projectListSort === 'holon') {
    foreach ($listProjectGroups as $groupKey => $group) {
        $kanbanGroups[$groupKey] = [
            'label' => (string)$group['label'],
            'kind' => 'holon',
            'value' => (string)(int)str_replace('holon-', '', (string)$groupKey),
            'importanceLevel' => null,
            'items' => array_fill_keys($columns, []),
        ];
        foreach ($group['items'] as $listItem) {
            $projectId = (int)$listItem['project']->getId();
            if (!isset($kanbanItemsById[$projectId])) {
                continue;
            }
            $status = Project::normalizeStatus($kanbanItemsById[$projectId]['project']->get('status'));
            $kanbanGroups[$groupKey]['items'][$status][] = $kanbanItemsById[$projectId];
        }
    }
} elseif ($projectListSort === 'priority') {
    foreach ([1, 2, 3, 4, 5, 0] as $priorityLevel) {
        $kanbanGroups['priority-' . $priorityLevel] = [
            'label' => $priorityLevel > 0 ? ('P' . $priorityLevel) : omoProjectsT('projects.list.priority.none'),
            'kind' => 'priority',
            'value' => (string)$priorityLevel,
            'importanceLevel' => null,
            'items' => array_fill_keys($columns, []),
        ];
    }
    foreach ($kanbanItemsById as $kanbanItem) {
        $priorityLevel = Project::normalizeLevel($kanbanItem['priority'] ?? null) ?? 0;
        $status = Project::normalizeStatus($kanbanItem['project']->get('status'));
        $kanbanGroups['priority-' . $priorityLevel]['items'][$status][] = $kanbanItem;
    }
} elseif ($projectListSort === 'importance') {
    foreach ([5, 4, 3, 2, 1, 0] as $importanceLevel) {
        $kanbanGroups['importance-' . $importanceLevel] = [
            'label' => $importanceLevel > 0 ? ($importanceLevel . '/5') : omoProjectsT('projects.importance.none'),
            'kind' => 'importance',
            'value' => (string)$importanceLevel,
            'importanceLevel' => $importanceLevel > 0 ? $importanceLevel : null,
            'items' => array_fill_keys($columns, []),
        ];
    }
    foreach ($kanbanItemsById as $projectId => $kanbanItem) {
        $importanceValue = (float)($kanbanItem['calculatedImportance'] ?? 0.0);
        $importanceLevel = $importanceValue > 0
            ? max(1, min(5, (int)round($importanceValue * 5, 0, PHP_ROUND_HALF_UP)))
            : 0;
        $kanbanGroups['importance-' . $importanceLevel]['items'][Project::normalizeStatus($kanbanItem['project']->get('status'))][] = $kanbanItem;
    }
}
$hasKanbanProjects = count($kanbanItemsById) > 0;
$kanbanGroups = array_values(array_filter($kanbanGroups, static function (array $group) use ($hasKanbanProjects): bool {
    if (($group['kind'] ?? '') === 'priority') {
        return $hasKanbanProjects;
    }
    foreach ($group['items'] as $items) {
        if (count($items) > 0) {
            return true;
        }
    }
    return false;
}));

$renderKanbanCard = static function (array $item, string $status) use ($context, $projectsByParent, $columns): string {
    $project = $item['project'];
    $projectTitle = trim((string)$project->get('title'));
    $responsibleLabel = $item['responsibleLabel'];
    $plannedEnd = $item['plannedEnd'];
    $subprojectSummary = $item['subprojectSummary'];
    $projectSize = $item['projectSize'];
    $canManageProject = omoProjectsCanManageProject($project, $context);
    $subprojectCount = omoProjectsCountDescendants((int)$project->getId(), $projectsByParent);
    ob_start();
    ?>
    <article
        class="omo-project-card omo-project-card--<?= omoApiEscape($status) ?> generic-section generic-section--stack"
        draggable="<?= $canManageProject ? 'true' : 'false' ?>"
        data-omo-project-card
        data-project-id="<?= (int)$project->getId() ?>"
        data-project-parent-id="<?= (int)$project->get('IDproject_parent') ?>"
        data-project-status="<?= omoApiEscape($status) ?>"
        data-project-title="<?= omoApiEscape($projectTitle) ?>"
        data-project-search="<?= omoApiEscape(trim($projectTitle . ' ' . $item['contextLabel'] . ' ' . $responsibleLabel . ' ' . omoProjectsStatusLabel($status))) ?>"
        data-project-holon-id="<?= (int)$project->get('IDholon') ?>"
        data-project-subproject-count="<?= (int)$subprojectCount ?>"
        tabindex="0"
        role="button"
        aria-label="<?= omoApiEscape($projectTitle) ?>"
    >
        <div class="omo-project-card__topline">
            <?php if ($canManageProject): ?>
                <label class="omo-project-selection-control">
                    <input type="checkbox" data-omo-project-select value="<?= (int)$project->getId() ?>" aria-label="<?= omoApiEscape(omoProjectsT('projects.selection.toggle')) ?>">
                </label>
            <?php endif; ?>
            <span class="omo-project-card__context generic-meta generic-meta--compact"><?= omoApiEscape($item['contextLabel']) ?></span>
            <span class="omo-project-card__topline-actions">
                <span class="omo-project-card__size" title="<?= omoApiEscape(omoProjectsT('projects.detail.size')) ?>"><?= omoApiEscape($projectSize) ?></span>
                <?php if ($item['priority'] !== null): ?><span class="generic-project-priority generic-project-priority--p<?= (int)$item['priority'] ?>" title="<?= omoApiEscape(omoProjectsT('projects.detail.priority')) ?>">P<?= (int)$item['priority'] ?></span><?php endif; ?>
                <?php if ($canManageProject): ?>
                    <div class="generic-menu omo-project-card__menu" data-omo-project-menu>
                        <button type="button" class="generic-menu-toggle omo-project-card__menu-toggle" data-omo-project-menu-toggle aria-expanded="false" aria-label="<?= omoApiEscape($projectTitle) ?>">&#8942;</button>
                        <div class="generic-menu-panel omo-project-card__menu-panel" data-omo-project-menu-panel role="menu" hidden>
                            <button type="button" class="generic-menu-item" data-omo-project-action="edit" role="menuitem"><?= omoApiEscape(omoProjectsT('projects.action.edit')) ?></button>
                            <button type="button" class="generic-menu-item" data-omo-project-action="move" role="menuitem"><?= omoApiEscape(omoProjectsT('projects.action.move')) ?></button>
                            <button type="button" class="generic-menu-item" data-omo-project-action="archive" role="menuitem"><?= omoApiEscape(omoProjectsT('projects.action.archive')) ?></button>
                            <button type="button" class="generic-menu-item generic-menu-item--danger" data-omo-project-action="delete" role="menuitem"><?= omoApiEscape(omoProjectsT('projects.action.delete')) ?></button>
                        </div>
                    </div>
                <?php endif; ?>
            </span>
        </div>
        <h4 class="omo-project-card__title generic-title generic-title--item"><?= omoApiEscape($projectTitle) ?></h4>
        <div class="omo-project-card__meta generic-meta generic-meta--compact">
            <span><?= omoApiEscape($responsibleLabel) ?></span>
            <?php if ($plannedEnd !== ''): ?><time datetime="<?= omoApiEscape((string)$plannedEnd) ?>"><?= omoApiEscape($plannedEnd) ?></time><?php endif; ?>
        </div>
        <?= omoProjectsRenderStatusBar($subprojectSummary, 'omo-project-card__subprojects') ?>
        <?php if ($canManageProject): ?>
            <label class="omo-project-card__status-control">
                <span class="sr-only"><?= omoApiEscape(omoProjectsT('projects.status_move')) ?></span>
                <select class="generic-form-control" data-omo-project-status-select data-project-id="<?= (int)$project->getId() ?>" aria-label="<?= omoApiEscape(omoProjectsT('projects.status_move')) ?>">
                    <?php foreach ($columns as $statusOption): ?>
                        <option value="<?= omoApiEscape($statusOption) ?>"<?= $statusOption === $status ? ' selected' : '' ?>><?= omoApiEscape(omoProjectsStatusLabel($statusOption)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>
    </article>
    <?php
    return (string)ob_get_clean();
};

$ganttDateMemo = [];
$ganttItemsById = [];
$ganttChildrenByParent = [];
foreach ($projects as $project) {
    if (!($project instanceof Project) || (int)$project->getId() <= 0) {
        continue;
    }

    $projectId = (int)$project->getId();
    $resolvedDates = omoProjectsResolveGanttDates($project, $projectsById, $ganttDateMemo);
    $effectiveStart = $resolvedDates['start'] instanceof \DateTimeImmutable ? $resolvedDates['start'] : $resolvedDates['end'];
    $effectiveEnd = $resolvedDates['end'] instanceof \DateTimeImmutable ? $resolvedDates['end'] : $resolvedDates['start'];
    if ($effectiveStart instanceof \DateTimeImmutable && $effectiveEnd instanceof \DateTimeImmutable && $effectiveEnd < $effectiveStart) {
        $effectiveEnd = $effectiveStart;
    }
    $projectHolon = $project->getHolon();
    $ganttItemsById[$projectId] = [
        'project' => $project,
        'status' => Project::normalizeStatus($project->get('status')),
        'holonLabel' => $projectHolon instanceof Holon
            ? trim((string)$projectHolon->getDisplayName())
            : trim((string)$organization->get('name')),
        'responsibleLabel' => omoProjectsGetUserLabel($project->getResponsible()),
        'priority' => Project::normalizeLevel($project->get('priority')),
        'calculatedImportance' => max(0.0, min(1.0, (float)$project->get('calculated_importance'))),
        'plannedEnd' => $resolvedDates['end'] instanceof \DateTimeImmutable ? $resolvedDates['end'] : null,
        'effectiveStart' => $effectiveStart,
        'effectiveEnd' => $effectiveEnd,
        'inheritedStart' => (bool)$resolvedDates['inheritedStart'],
        'inheritedEnd' => (bool)$resolvedDates['inheritedEnd'],
    ];
}

foreach ($ganttItemsById as $projectId => $item) {
    $parentId = (int)$item['project']->get('IDproject_parent');
    if ($parentId > 0 && isset($ganttItemsById[$parentId])) {
        $ganttChildrenByParent[$parentId][] = $projectId;
    }
}
$compareGanttProjectIds = static function (int $leftId, int $rightId) use ($ganttItemsById, $projectListSort): int {
    $left = $ganttItemsById[$leftId];
    $right = $ganttItemsById[$rightId];
    if ($projectListSort === 'importance') {
        $importanceComparison = (float)$right['calculatedImportance'] <=> (float)$left['calculatedImportance'];
        if ($importanceComparison !== 0) {
            return $importanceComparison;
        }
    } elseif ($projectListSort === 'priority') {
        $leftPriority = $left['priority'] === null ? PHP_INT_MAX : (int)$left['priority'];
        $rightPriority = $right['priority'] === null ? PHP_INT_MAX : (int)$right['priority'];
        if ($leftPriority !== $rightPriority) {
            return $leftPriority <=> $rightPriority;
        }
    } elseif ($projectListSort === 'holon') {
        $holonComparison = strcasecmp((string)$left['holonLabel'], (string)$right['holonLabel']);
        if ($holonComparison !== 0) {
            return $holonComparison;
        }
    }
    $leftStart = $left['effectiveStart'] instanceof \DateTimeImmutable ? $left['effectiveStart']->format('Y-m-d') : '';
    $rightStart = $right['effectiveStart'] instanceof \DateTimeImmutable ? $right['effectiveStart']->format('Y-m-d') : '';
    if ($leftStart === '' && $rightStart !== '') {
        return 1;
    }
    if ($leftStart !== '' && $rightStart === '') {
        return -1;
    }
    if ($leftStart !== $rightStart) {
        return strcmp($leftStart, $rightStart);
    }
    return strcasecmp((string)$left['project']->get('title'), (string)$right['project']->get('title'));
};
foreach ($ganttChildrenByParent as &$childIds) {
    usort($childIds, $compareGanttProjectIds);
}
unset($childIds);
$ganttRootIds = [];
foreach ($ganttItemsById as $projectId => $item) {
    if (!isset($ganttItemsById[(int)$item['project']->get('IDproject_parent')])) {
        $ganttRootIds[] = $projectId;
    }
}
usort($ganttRootIds, $compareGanttProjectIds);
$ganttRows = [];
$ganttVisited = [];
$appendGanttBranch = static function (int $projectId, int $depth) use (&$appendGanttBranch, &$ganttRows, &$ganttVisited, $ganttItemsById, $ganttChildrenByParent): void {
    if (isset($ganttVisited[$projectId]) || !isset($ganttItemsById[$projectId])) {
        return;
    }
    $ganttVisited[$projectId] = true;
    $ganttRows[] = $ganttItemsById[$projectId] + ['depth' => $depth];
    foreach ($ganttChildrenByParent[$projectId] ?? [] as $childId) {
        $appendGanttBranch($childId, $depth + 1);
    }
};
foreach ($ganttRootIds as $projectId) {
    $appendGanttBranch($projectId, 0);
}
foreach (array_keys($ganttItemsById) as $projectId) {
    $appendGanttBranch($projectId, 0);
}

$ganttDatedRows = array_values(array_filter($ganttRows, static fn (array $item): bool => $item['effectiveStart'] instanceof \DateTimeImmutable && $item['effectiveEnd'] instanceof \DateTimeImmutable));
$ganttToday = new \DateTimeImmutable('today');
if (count($ganttDatedRows) > 0) {
    $ganttRangeStart = $ganttDatedRows[0]['effectiveStart'];
    $ganttRangeEnd = $ganttDatedRows[0]['effectiveEnd'];
    foreach ($ganttDatedRows as $item) {
        if ($item['effectiveStart'] < $ganttRangeStart) {
            $ganttRangeStart = $item['effectiveStart'];
        }
        if ($item['effectiveEnd'] > $ganttRangeEnd) {
            $ganttRangeEnd = $item['effectiveEnd'];
        }
    }
    $ganttRangeStart = $ganttRangeStart->modify('-7 days');
    $ganttRangeEnd = $ganttRangeEnd->modify('+14 days');
} else {
    $ganttRangeStart = $ganttToday->modify('first day of this month');
    $ganttRangeEnd = $ganttRangeStart->modify('+2 months')->modify('-1 day');
}
$ganttRangeDays = max(1, (int)$ganttRangeStart->diff($ganttRangeEnd)->format('%a') + 1);
$ganttTimelineWidth = max(720, $ganttRangeDays * 16);
$ganttDateOffset = static function (\DateTimeImmutable $date) use ($ganttRangeStart, $ganttRangeDays): float {
    return max(0.0, min(100.0, ((int)$ganttRangeStart->diff($date)->format('%r%a') / $ganttRangeDays) * 100));
};
$ganttMonthHeaders = [];
for ($month = $ganttRangeStart->modify('first day of this month'); $month <= $ganttRangeEnd; $month = $month->modify('first day of next month')) {
    $monthEnd = $month->modify('last day of this month');
    $segmentStart = $month < $ganttRangeStart ? $ganttRangeStart : $month;
    $segmentEnd = $monthEnd > $ganttRangeEnd ? $ganttRangeEnd : $monthEnd;
    $ganttMonthHeaders[] = [
        'label' => $month->format('m.Y'),
        'left' => $ganttDateOffset($segmentStart),
        'width' => max(0.8, (((int)$segmentStart->diff($segmentEnd)->format('%a') + 1) / $ganttRangeDays) * 100),
    ];
}
$ganttTodayOffset = $ganttToday >= $ganttRangeStart && $ganttToday <= $ganttRangeEnd ? $ganttDateOffset($ganttToday) : null;

$currentUrl = '/omo/api/projects/index.php?oid=' . rawurlencode((string)$organizationId);
if ($currentHolonId > 0) {
    $currentUrl .= '&cid=' . rawurlencode((string)$currentHolonId);
}
if ($projectScope !== 'contextual') {
    $currentUrl .= '&project_scope=' . rawurlencode($projectScope);
}
if ($projectView !== 'kanban') {
    $currentUrl .= '&project_view=' . rawurlencode($projectView);
}
if ($projectListSort !== 'planned') {
    $currentUrl .= '&project_sort=' . rawurlencode($projectListSort);
}
if ($projectAssignment === 'mine') {
    $currentUrl .= '&project_assignment=mine';
}
if ($projectQuickSearch !== '') {
    $currentUrl .= '&project_query=' . rawurlencode($projectQuickSearch);
}

$createUrl = '/omo/api/projects/create.php?oid=' . rawurlencode((string)$organizationId);
if ($currentHolonId > 0) {
    $createUrl .= '&cid=' . rawurlencode((string)$currentHolonId);
}

$detailUrl = '/omo/api/projects/detail.php?oid=' . rawurlencode((string)$organizationId);
$actionUrl = '/omo/api/projects/action.php';
$canManage = omoProjectsCanManageContext($context);
$canCreate = omoProjectsCanCreateContext($context);
$emptyKey = 'projects.empty.' . $projectScope;
if ($projectAssignment === 'mine') {
    $emptyKey = 'projects.empty.mine';
}
$projectTexts = [
    'loading' => omoProjectsT('projects.loading'),
    'loadingError' => omoProjectsT('projects.loading_error'),
    'emptyColumn' => omoProjectsT('projects.empty.column'),
    'statusUpdateError' => omoProjectsT('projects.status_update_error'),
    'actionError' => omoProjectsT('projects.action_error'),
    'deleteConfirm' => omoProjectsT('projects.delete.confirm'),
    'archiveConfirm' => omoProjectsT('projects.archive.confirm'),
    'archiveSelectedConfirm' => omoProjectsT('projects.archive.confirm_selected'),
    'deleteSelectedConfirm' => omoProjectsT('projects.delete.confirm_selected'),
    'selectionCount' => omoProjectsT('projects.selection.count'),
    'moveTitle' => omoProjectsT('projects.move.title'),
    'moveHint' => omoProjectsT('projects.move.hint'),
    'moveSubmit' => omoProjectsT('projects.move.submit'),
    'moveSelectRequired' => omoProjectsT('projects.move.select_required'),
    'attachTitle' => omoProjectsT('projects.attach.title'),
    'attachHint' => omoProjectsT('projects.attach.hint'),
    'attachSearch' => omoProjectsT('projects.attach.search'),
    'attachEmpty' => omoProjectsT('projects.attach.empty'),
    'attachSubmit' => omoProjectsT('projects.attach.submit'),
    'attachSelectRequired' => omoProjectsT('projects.attach.select_required'),
    'cancel' => omoProjectsT('projects.action.cancel'),
    'documentsLoading' => omoProjectsT('projects.detail.documents.loading'),
    'documentsError' => omoProjectsT('projects.detail.documents.error'),
    'eventsLoading' => omoProjectsT('projects.detail.events.loading'),
    'eventsError' => omoProjectsT('projects.detail.events.error'),
    'documentsAdd' => omoProjectsT('projects.detail.documents.add'),
    'taskDeleteConfirm' => omoProjectsT('projects.detail.task.delete_confirm'),
    'archivesTitle' => omoProjectsT('projects.detail.archives.title'),
];
?>
<link rel="stylesheet" href="/common/view-filter/view-filter.css?v=20260801-view-preferences-actions-height">
<link rel="stylesheet" href="/omo/api/projects/projects.css?v=20260807-project-bulk-actions">
<div
    class="omo-projects omo-panel-view"
    id="omo-projects-root"
    data-omo-projects-oid="<?= (int)$organizationId ?>"
    data-omo-projects-cid="<?= $currentHolon instanceof Holon ? (int)$currentHolon->getId() : 0 ?>"
    data-omo-projects-scope="<?= omoApiEscape($projectScope) ?>"
    data-omo-projects-assignment="<?= omoApiEscape($projectAssignment) ?>"
    data-omo-projects-query="<?= omoApiEscape($projectQuickSearch) ?>"
    data-omo-projects-view="<?= omoApiEscape($projectView) ?>"
    data-omo-projects-list-sort="<?= omoApiEscape($projectListSort) ?>"
    data-omo-projects-current-url="<?= omoApiEscape($currentUrl) ?>"
    data-omo-projects-create-url="<?= omoApiEscape($createUrl) ?>"
    data-omo-projects-detail-url="<?= omoApiEscape($detailUrl) ?>"
    data-omo-projects-action-url="<?= omoApiEscape($actionUrl) ?>"
    data-omo-projects-can-manage="<?= $canManage ? '1' : '0' ?>"
    data-omo-projects-columns="<?= omoApiEscape(json_encode($columns, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
    data-omo-projects-texts="<?= omoApiEscape(json_encode($projectTexts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
    data-omo-projects-open-project-id="<?= (int)$openProjectTargetId ?>"
    data-omo-projects-open-project-mode="<?= omoApiEscape($openProjectMode) ?>"
    data-omo-projects-preferences-pending="1"
    aria-busy="true"
>
    <header class="omo-projects__header omo-panel-view__header omo-panel-view__header--stacked">
        <div class="omo-panel-view__header-main">
            <div class="omo-panel-view__title-cluster">
                <span class="omo-panel-view__app-icon omo-projects__app-icon" aria-hidden="true">
                    <img src="images/tools/product.png" alt="">
                </span>
                <div class="omo-panel-view__header-copy">
                    <div class="omo-projects__title-row generic-title-row generic-title-row--center">
                        <h2 class="omo-panel-view__title"><?= omoApiEscape(omoProjectsT('projects.title')) ?></h2>
                        <span class="omo-panel-view__count"><?= (int)($projectView === 'list' ? count($listProjectItems) : ($projectView === 'gantt' ? count($ganttRows) : $projectCount)) ?></span>
                    </div>
                </div>
            </div>
            <div class="omo-projects__header-actions" data-omo-header-actions>
                <div class="omo-projects__bulk-actions" data-omo-projects-bulk-actions hidden>
                    <span class="omo-projects__bulk-count" data-omo-projects-bulk-count></span>
                    <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-projects-bulk-action="archive"><?= omoApiEscape(omoProjectsT('projects.action.archive_selected')) ?></button>
                    <button type="button" class="generic-action-button generic-action-button--danger" data-omo-projects-bulk-action="delete"><?= omoApiEscape(omoProjectsT('projects.action.delete_selected')) ?></button>
                </div>
                <?php if ($canCreate): ?><button type="button" class="generic-action-button generic-action-button--main omo-mobile-corner-action" data-omo-projects-open-create><?= omoApiEscape(omoProjectsT('projects.action.new')) ?></button><?php endif; ?>
            </div>
        </div>
        <div class="omo-panel-view__header-secondary">
            <div class="omo-projects__filter-toolbar" data-omo-projects-filter-control role="group" aria-label="<?= omoApiEscape(omoProjectsT('projects.filters.aria')) ?>">
                <div class="omo-projects__filter-input" aria-label="<?= omoApiEscape(omoProjectsT('projects.filters.aria')) ?>">
                    <div class="omo-projects__filter-chips">
                        <button type="button" class="omo-projects__filter-chip" data-omo-projects-filter-toggle aria-expanded="false" aria-controls="omo-projects-filter-panel"><?= omoApiEscape(omoProjectsT('projects.scope.' . $projectScope)) ?></button>
                        <button type="button" class="omo-projects__filter-chip" data-omo-projects-filter-toggle aria-expanded="false" aria-controls="omo-projects-filter-panel"><?= omoApiEscape(omoProjectsT('projects.assignment.' . ($projectAssignment === 'mine' ? 'mine' : 'everyone'))) ?></button>
                        <button type="button" class="omo-projects__filter-chip" data-omo-projects-filter-toggle aria-expanded="false" aria-controls="omo-projects-filter-panel"><?= omoApiEscape(omoProjectsT('projects.sort.' . $projectListSort)) ?></button>
                        <button type="button" class="omo-projects__filter-chip" data-omo-projects-filter-toggle aria-expanded="false" aria-controls="omo-projects-filter-panel"><?= omoApiEscape(omoProjectsT('projects.view.' . $projectView)) ?></button>
                    </div>
                    <label class="omo-projects__filter-search">
                        <input type="search" class="generic-form-control" data-omo-projects-quick-search value="<?= omoApiEscape($projectQuickSearch) ?>" placeholder="<?= omoApiEscape(omoProjectsT('projects.search.placeholder')) ?>" aria-label="<?= omoApiEscape(omoProjectsT('projects.search.aria')) ?>" autocomplete="off">
                    </label>
                </div>
                <section id="omo-projects-filter-panel" class="omo-projects__filter-panel generic-soft-panel generic-soft-panel--stack" data-omo-projects-filter-panel hidden>
                    <div class="omo-projects__filter-panel-grid">
                        <div class="omo-projects__filter-group">
                            <span class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoProjectsT('projects.filters.scope')) ?></span>
                            <div class="omo-segmented" role="group" aria-label="<?= omoApiEscape(omoProjectsT('projects.filters.scope')) ?>">
                                <?php foreach ($availableScopes as $scopeKey): ?>
                                    <button type="button" class="omo-segmented__button<?= $projectScope === $scopeKey ? ' is-active' : '' ?>" data-omo-projects-scope="<?= omoApiEscape($scopeKey) ?>" aria-pressed="<?= $projectScope === $scopeKey ? 'true' : 'false' ?>"><?= omoApiEscape(omoProjectsT('projects.scope.' . $scopeKey)) ?></button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="omo-projects__filter-group">
                            <span class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoProjectsT('projects.filters.assignment')) ?></span>
                            <div class="omo-segmented" role="group" aria-label="<?= omoApiEscape(omoProjectsT('projects.assignment.aria')) ?>">
                                <button type="button" class="omo-segmented__button<?= $projectAssignment === 'mine' ? ' is-active' : '' ?>" data-omo-projects-assignment="mine" aria-pressed="<?= $projectAssignment === 'mine' ? 'true' : 'false' ?>"><?= omoApiEscape(omoProjectsT('projects.assignment.mine')) ?></button>
                                <button type="button" class="omo-segmented__button<?= $projectAssignment === 'all' ? ' is-active' : '' ?>" data-omo-projects-assignment="all" aria-pressed="<?= $projectAssignment === 'all' ? 'true' : 'false' ?>"><?= omoApiEscape(omoProjectsT('projects.assignment.everyone')) ?></button>
                            </div>
                        </div>
                        <div class="omo-projects__filter-group">
                            <span class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoProjectsT('projects.filters.sort')) ?></span>
                            <div class="omo-segmented" role="group" aria-label="<?= omoApiEscape(omoProjectsT('projects.sort.aria')) ?>">
                                <button type="button" class="omo-segmented__button<?= $projectListSort === 'planned' ? ' is-active' : '' ?>" data-omo-projects-sort="planned" aria-pressed="<?= $projectListSort === 'planned' ? 'true' : 'false' ?>"><?= omoApiEscape(omoProjectsT('projects.sort.planned')) ?></button>
                                <button type="button" class="omo-segmented__button<?= $projectListSort === 'priority' ? ' is-active' : '' ?>" data-omo-projects-sort="priority" aria-pressed="<?= $projectListSort === 'priority' ? 'true' : 'false' ?>"><?= omoApiEscape(omoProjectsT('projects.sort.priority')) ?></button>
                                <button type="button" class="omo-segmented__button<?= $projectListSort === 'importance' ? ' is-active' : '' ?>" data-omo-projects-sort="importance" aria-pressed="<?= $projectListSort === 'importance' ? 'true' : 'false' ?>"><?= omoApiEscape(omoProjectsT('projects.sort.importance')) ?></button>
                                <?php if (in_array('children', $availableScopes, true) || in_array('descendants', $availableScopes, true)): ?>
                                    <button type="button" class="omo-segmented__button<?= $projectListSort === 'holon' ? ' is-active' : '' ?>" data-omo-projects-sort="holon" aria-pressed="<?= $projectListSort === 'holon' ? 'true' : 'false' ?>"<?= $canUseHolonSort ? '' : ' disabled aria-disabled="true"' ?>><?= omoApiEscape(omoProjectsT('projects.sort.holon')) ?></button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="omo-projects__filter-group">
                            <span class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoProjectsT('projects.filters.view')) ?></span>
                            <div class="omo-segmented" role="group" aria-label="<?= omoApiEscape(omoProjectsT('projects.view.aria')) ?>">
                                <button type="button" class="omo-segmented__button<?= $projectView === 'kanban' ? ' is-active' : '' ?>" data-omo-projects-view="kanban" aria-pressed="<?= $projectView === 'kanban' ? 'true' : 'false' ?>"><?= omoApiEscape(omoProjectsT('projects.view.kanban')) ?></button>
                                <button type="button" class="omo-segmented__button<?= $projectView === 'list' ? ' is-active' : '' ?>" data-omo-projects-view="list" aria-pressed="<?= $projectView === 'list' ? 'true' : 'false' ?>"><?= omoApiEscape(omoProjectsT('projects.view.list')) ?></button>
                                <button type="button" class="omo-segmented__button<?= $projectView === 'gantt' ? ' is-active' : '' ?>" data-omo-projects-view="gantt" aria-pressed="<?= $projectView === 'gantt' ? 'true' : 'false' ?>"><?= omoApiEscape(omoProjectsT('projects.view.gantt')) ?></button>
                            </div>
                        </div>
                    </div>
                    <div class="omo-projects__filter-panel-actions">
                        <button type="button" class="generic-action-button generic-action-button--main" data-omo-projects-filter-apply><?= omoApiEscape(omoProjectsT('projects.filters.apply')) ?></button>
                        <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-projects-filter-save><?= omoApiEscape(omoProjectsT('projects.filters.save_view')) ?></button>
                        <div class="generic-menu omo-projects__filter-panel-actions-more" data-omo-projects-filter-more-menu>
                            <button type="button" class="generic-menu-toggle" data-omo-projects-filter-more-toggle aria-expanded="false" aria-label="<?= omoApiEscape(omoProjectsT('projects.filters.more_actions')) ?>">&#8942;</button>
                            <div class="generic-menu-panel" data-omo-projects-filter-more-panel role="menu" hidden>
                                <button type="button" class="generic-menu-item" data-omo-projects-filter-more-action="apply-everywhere" role="menuitem"><?= omoApiEscape(omoProjectsT('projects.filters.apply_everywhere')) ?></button>
                                <button type="button" class="generic-menu-item" data-omo-projects-filter-more-action="set-default" role="menuitem"><?= omoApiEscape(omoProjectsT('projects.filters.set_default')) ?></button>
                                <button type="button" class="generic-menu-item" data-omo-projects-filter-more-action="restore-default" role="menuitem"><?= omoApiEscape(omoProjectsT('projects.filters.restore_default')) ?></button>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
            <div class="omo-projects__mobile-column-nav" aria-label="<?= omoApiEscape(omoProjectsT('projects.title')) ?>"<?= $projectView === 'kanban' ? '' : ' hidden' ?>>
                <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-projects-column-prev aria-label="<?= omoApiEscape(omoProjectsT('projects.column.previous')) ?>">&lsaquo;</button>
                <span data-omo-projects-column-label></span>
                <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-projects-column-next aria-label="<?= omoApiEscape(omoProjectsT('projects.column.next')) ?>">&rsaquo;</button>
            </div>
        </div>
    </header>

    <div class="omo-panel-view__body">
        <div class="omo-panel-view__body_content omo-projects__body">
            <?php if ($projectView === 'kanban'): ?>
            <?php if (count($kanbanGroups) > 0): ?>
            <div class="omo-projects__board omo-projects__board--grouped" data-omo-projects-board>
                <div class="omo-projects__kanban-grid-header">
                    <?php foreach ($columns as $status): ?>
                        <?php $columnItems = $projectsByStatus[$status] ?? []; ?>
                        <section class="omo-projects__kanban-grid-header-cell" data-omo-projects-column="<?= omoApiEscape($status) ?>">
                            <div class="omo-projects__kanban-grid-header-title">
                                <h3><?= omoApiEscape(omoProjectsStatusLabel($status)) ?></h3>
                                <span class="omo-projects__column-count omo-projects__column-count--<?= omoApiEscape($status) ?>" data-omo-projects-column-count><?= count($columnItems) ?></span>
                            </div>
                        </section>
                    <?php endforeach; ?>
                </div>
                <div class="omo-projects__kanban-rows">
                    <?php foreach ($kanbanGroups as $kanbanGroup): ?>
                        <section
                            class="omo-projects__kanban-row"
                            data-omo-projects-kanban-row
                            data-omo-projects-kanban-group-kind="<?= omoApiEscape($kanbanGroup['kind']) ?>"
                            data-omo-projects-kanban-group-value="<?= omoApiEscape($kanbanGroup['value']) ?>"
                        >
                            <h3 class="generic-file-list__group-title omo-projects__kanban-row-title">
                                <?php if ($kanbanGroup['importanceLevel'] !== null): ?>
                                    <span class="omo-projects__importance-stars" role="img" aria-label="<?= omoApiEscape(omoProjectsT('projects.sort.importance') . ': ' . $kanbanGroup['importanceLevel'] . '/5') ?>">
                                        <?php foreach ([1, 2, 3, 4, 5] as $starLevel): ?><span class="omo-projects__importance-star<?= $starLevel <= $kanbanGroup['importanceLevel'] ? ' is-active' : '' ?>" aria-hidden="true">&#9733;</span><?php endforeach; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="omo-projects__kanban-row-title-label"><?= omoApiEscape($kanbanGroup['label']) ?></span>
                                <?php endif; ?>
                            </h3>
                            <div class="omo-projects__kanban-row-grid">
                                <?php foreach ($columns as $status): ?>
                                    <div class="omo-projects__kanban-cell" data-omo-projects-kanban-cell data-omo-projects-cards="<?= omoApiEscape($status) ?>" data-status="<?= omoApiEscape($status) ?>">
                                        <?php foreach ($kanbanGroup['items'][$status] as $item): ?>
                                            <?= $renderKanbanCard($item, $status) ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="omo-projects__board" data-omo-projects-board>
                <?php foreach ($columns as $status): ?>
                    <?php $columnItems = $projectsByStatus[$status] ?? []; ?>
                    <section class="omo-projects__column" data-omo-projects-column="<?= omoApiEscape($status) ?>">
                        <header class="omo-projects__column-header">
                            <div>
                                <h3><?= omoApiEscape(omoProjectsStatusLabel($status)) ?></h3>
                            </div>
                            <span class="omo-projects__column-count omo-projects__column-count--<?= omoApiEscape($status) ?>" data-omo-projects-column-count><?= count($columnItems) ?></span>
                        </header>
                        <div class="omo-projects__column-cards" data-omo-projects-cards="<?= omoApiEscape($status) ?>" data-status="<?= omoApiEscape($status) ?>">
                            <?php
                            $columnGroups = [];
                            if (count($kanbanGroups) > 0) {
                                foreach ($kanbanGroups as $kanbanGroup) {
                                    $groupItems = $kanbanGroup['items'][$status] ?? [];
                                    $columnGroups[] = [
                                        'label' => $kanbanGroup['label'],
                                        'items' => $groupItems,
                                    ];
                                }
                            } else {
                                $columnGroups[] = ['label' => '', 'items' => $columnItems];
                            }
                            ?>
                            <?php foreach ($columnGroups as $columnGroup): ?>
                                <?php if ($columnGroup['label'] !== ''): ?>
                                    <div class="omo-projects__kanban-group" data-omo-projects-kanban-group>
                                        <h4 class="omo-projects__kanban-group-title"><?= omoApiEscape($columnGroup['label']) ?></h4>
                                <?php endif; ?>
                                <?php foreach ($columnGroup['items'] as $item): ?>
                                <?php
                                $project = $item['project'];
                                $projectTitle = trim((string)$project->get('title'));
                                $responsibleLabel = $item['responsibleLabel'];
                                $plannedEnd = $item['plannedEnd'];
                                $subprojectSummary = $item['subprojectSummary'];
                                $projectSize = $item['projectSize'];
                                $canManageProject = omoProjectsCanManageProject($project, $context);
                                $subprojectCount = omoProjectsCountDescendants((int)$project->getId(), $projectsByParent);
                                ?>
                                <article
                                    class="omo-project-card omo-project-card--<?= omoApiEscape($status) ?> generic-section generic-section--stack"
                                    draggable="<?= $canManageProject ? 'true' : 'false' ?>"
                                    data-omo-project-card
                                    data-project-id="<?= (int)$project->getId() ?>"
                                    data-project-parent-id="<?= (int)$project->get('IDproject_parent') ?>"
                                    data-project-status="<?= omoApiEscape($status) ?>"
                                    data-project-title="<?= omoApiEscape($projectTitle) ?>"
                                    data-project-search="<?= omoApiEscape(trim($projectTitle . ' ' . $item['contextLabel'] . ' ' . $responsibleLabel . ' ' . omoProjectsStatusLabel($status))) ?>"
                                    data-project-holon-id="<?= (int)$project->get('IDholon') ?>"
                                    data-project-subproject-count="<?= (int)$subprojectCount ?>"
                                    tabindex="0"
                                    role="button"
                                    aria-label="<?= omoApiEscape($projectTitle) ?>"
                                >
                                    <div class="omo-project-card__topline">
                                        <?php if ($canManageProject): ?>
                                            <label class="omo-project-selection-control">
                                                <input type="checkbox" data-omo-project-select value="<?= (int)$project->getId() ?>" aria-label="<?= omoApiEscape(omoProjectsT('projects.selection.toggle')) ?>">
                                            </label>
                                        <?php endif; ?>
                                        <span class="omo-project-card__context generic-meta generic-meta--compact"><?= omoApiEscape($item['contextLabel']) ?></span>
                                        <span class="omo-project-card__topline-actions">
                                            <span class="omo-project-card__size" title="<?= omoApiEscape(omoProjectsT('projects.detail.size')) ?>"><?= omoApiEscape($projectSize) ?></span>
                                            <?php if ($item['priority'] !== null): ?><span class="generic-project-priority generic-project-priority--p<?= (int)$item['priority'] ?>" title="<?= omoApiEscape(omoProjectsT('projects.detail.priority')) ?>">P<?= (int)$item['priority'] ?></span><?php endif; ?>
                                            <?php if ($canManageProject): ?>
                                                <div class="generic-menu omo-project-card__menu" data-omo-project-menu>
                                                    <button type="button" class="generic-menu-toggle omo-project-card__menu-toggle" data-omo-project-menu-toggle aria-expanded="false" aria-label="<?= omoApiEscape($projectTitle) ?>">&#8942;</button>
                                                    <div class="generic-menu-panel omo-project-card__menu-panel" data-omo-project-menu-panel role="menu" hidden>
                                                        <button type="button" class="generic-menu-item" data-omo-project-action="edit" role="menuitem"><?= omoApiEscape(omoProjectsT('projects.action.edit')) ?></button>
                                                        <button type="button" class="generic-menu-item" data-omo-project-action="move" role="menuitem"><?= omoApiEscape(omoProjectsT('projects.action.move')) ?></button>
                                                        <button type="button" class="generic-menu-item" data-omo-project-action="archive" role="menuitem"><?= omoApiEscape(omoProjectsT('projects.action.archive')) ?></button>
                                                        <button type="button" class="generic-menu-item generic-menu-item--danger" data-omo-project-action="delete" role="menuitem"><?= omoApiEscape(omoProjectsT('projects.action.delete')) ?></button>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <h4 class="omo-project-card__title generic-title generic-title--item"><?= omoApiEscape($projectTitle) ?></h4>
                                    <div class="omo-project-card__meta generic-meta generic-meta--compact">
                                        <span><?= omoApiEscape($responsibleLabel) ?></span>
                                        <?php if ($plannedEnd !== ''): ?><time datetime="<?= omoApiEscape((string)$plannedEnd) ?>"><?= omoApiEscape($plannedEnd) ?></time><?php endif; ?>
                                    </div>
                                    <?= omoProjectsRenderStatusBar($subprojectSummary, 'omo-project-card__subprojects') ?>
                                    <?php if ($canManageProject): ?>
                                        <label class="omo-project-card__status-control">
                                            <span class="sr-only"><?= omoApiEscape(omoProjectsT('projects.status_move')) ?></span>
                                            <select class="generic-form-control" data-omo-project-status-select data-project-id="<?= (int)$project->getId() ?>" aria-label="<?= omoApiEscape(omoProjectsT('projects.status_move')) ?>">
                                                <?php foreach ($columns as $statusOption): ?>
                                                    <option value="<?= omoApiEscape($statusOption) ?>"<?= $statusOption === $status ? ' selected' : '' ?>><?= omoApiEscape(omoProjectsStatusLabel($statusOption)) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                    <?php endif; ?>
                                </article>
                                <?php endforeach; ?>
                                <?php if ($columnGroup['label'] !== ''): ?>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <?php if (count($columnItems) === 0 && count($kanbanGroups) === 0): ?>
                                <div class="omo-projects__column-empty" data-omo-projects-column-empty><?= omoApiEscape(omoProjectsT('projects.empty.column')) ?></div>
                            <?php endif; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if ($projectCount === 0): ?>
                <div class="omo-projects__board-empty omo-empty-state"><?= omoApiEscape(omoProjectsT($emptyKey)) ?></div>
            <?php endif; ?>
            <?php elseif ($projectView === 'list'): ?>
                <?php if (count($listProjectItems) === 0): ?>
                    <div class="omo-projects__board-empty omo-empty-state"><?= omoApiEscape(omoProjectsT($emptyKey)) ?></div>
                <?php else: ?>
                    <div class="generic-file-list generic-file-list--structured omo-projects__list" data-omo-projects-list>
                        <?php foreach ($listProjectGroups as $group): ?>
                            <section class="generic-file-list__group omo-projects__list-group" data-omo-projects-list-group>
                                <h3 class="generic-card-title generic-card-title--small generic-file-list__group-title"><?= omoApiEscape($group['label']) ?></h3>
                                <div class="omo-projects__list-group-items">
                                    <?php foreach ($group['items'] as $item): ?>
                                        <?php $project = $item['project']; ?>
                                        <article class="omo-project-list-item omo-project-list-item--<?= omoApiEscape($item['status']) ?>" data-omo-project-list-item data-project-id="<?= (int)$project->getId() ?>" data-project-parent-id="<?= (int)$project->get('IDproject_parent') ?>" data-project-search="<?= omoApiEscape(trim((string)$project->get('title') . ' ' . $item['holonLabel'] . ' ' . $item['responsibleLabel'] . ' ' . omoProjectsStatusLabel($item['status']))) ?>" tabindex="0" role="button" aria-label="<?= omoApiEscape((string)$project->get('title')) ?>">
                                            <?php if (omoProjectsCanManageProject($project, $context)): ?><label class="omo-project-selection-control"><input type="checkbox" data-omo-project-select value="<?= (int)$project->getId() ?>" aria-label="<?= omoApiEscape(omoProjectsT('projects.selection.toggle')) ?>"></label><?php endif; ?>
                                            <div class="omo-project-list-item__main">
                                                <strong><?= omoApiEscape((string)$project->get('title')) ?></strong>
                                                <div class="omo-project-list-item__meta">
                                                    <span><?= omoApiEscape($item['holonLabel']) ?></span>
                                                    <span><?= omoApiEscape($item['responsibleLabel']) ?></span>
                                                    <span class="omo-project-status omo-project-status--<?= omoApiEscape($item['status']) ?>"><?= omoApiEscape(omoProjectsStatusLabel($item['status'])) ?></span>
                                                    <span class="omo-project-detail__subproject-size"><?= omoApiEscape($item['projectSize']) ?></span>
                                                    <?php if ($item['priority'] !== null): ?><span class="generic-project-priority generic-project-priority--p<?= (int)$item['priority'] ?>" title="<?= omoApiEscape(omoProjectsT('projects.detail.priority')) ?>">P<?= (int)$item['priority'] ?></span><?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="omo-project-list-item__planning">
                                                <?php if ($item['startDate'] !== '' || $item['endDate'] !== ''): ?>
                                                    <span><?= omoApiEscape($item['startDate'] !== '' ? $item['startDate'] : omoProjectsT('projects.detail.none')) ?> - <?= omoApiEscape($item['endDate'] !== '' ? $item['endDate'] : omoProjectsT('projects.detail.none')) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <?php if (count($ganttRows) === 0): ?>
                    <div class="omo-projects__board-empty omo-empty-state"><?= omoApiEscape(omoProjectsT($emptyKey)) ?></div>
                <?php else: ?>
                    <div class="omo-projects__gantt" data-omo-projects-gantt>
                        <div class="omo-projects__gantt-scroll" data-omo-projects-gantt-scroll>
                            <div class="omo-projects__gantt-table" style="--omo-projects-gantt-timeline-width: <?= (int)$ganttTimelineWidth ?>px;">
                                <div class="omo-projects__gantt-header">
                                    <div class="omo-projects__gantt-project-header"><?= omoApiEscape(omoProjectsT('projects.title')) ?></div>
                                    <div class="omo-projects__gantt-timeline omo-projects__gantt-timeline--header">
                                        <?php foreach ($ganttMonthHeaders as $month): ?>
                                            <span class="omo-projects__gantt-month" style="--omo-project-gantt-left: <?= number_format($month['left'], 4, '.', '') ?>%; --omo-project-gantt-width: <?= number_format($month['width'], 4, '.', '') ?>%;"><?= omoApiEscape($month['label']) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php foreach ($ganttRows as $item): ?>
                                    <?php
                                    $project = $item['project'];
                                    $projectTitle = trim((string)$project->get('title'));
                                    $hasDates = $item['effectiveStart'] instanceof \DateTimeImmutable && $item['effectiveEnd'] instanceof \DateTimeImmutable;
                                    $dateLabel = '';
                                    if ($hasDates) {
                                        $dateLabel = omoProjectsFormatGanttDateRange($item['effectiveStart'], $item['effectiveEnd']);
                                        if ($item['inheritedStart'] || $item['inheritedEnd']) {
                                            $dateLabel .= ' (' . omoProjectsT('projects.gantt.inherited') . ')';
                                        }
                                    }
                                    $barLeft = $hasDates ? $ganttDateOffset($item['effectiveStart']) : 0.0;
                                    $barWidth = $hasDates ? (((int)$item['effectiveStart']->diff($item['effectiveEnd'])->format('%a') + 1) / $ganttRangeDays) * 100 : 0.0;
                                    $isOverdue = $item['status'] !== Project::STATUS_DONE
                                        && $item['plannedEnd'] instanceof \DateTimeImmutable
                                        && $item['plannedEnd'] < $ganttToday;
                                    $projectAriaLabel = trim($projectTitle . ' ' . $dateLabel . ($isOverdue ? ' ' . omoProjectsT('projects.gantt.overdue') : ''));
                                    ?>
                                    <article class="omo-project-gantt-row omo-project-gantt-row--<?= omoApiEscape($item['status']) ?><?= $isOverdue ? ' omo-project-gantt-row--overdue' : '' ?>" data-omo-project-gantt-item data-project-id="<?= (int)$project->getId() ?>" data-project-parent-id="<?= (int)$project->get('IDproject_parent') ?>" data-project-search="<?= omoApiEscape(trim($projectTitle . ' ' . $item['holonLabel'] . ' ' . $item['responsibleLabel'] . ' ' . omoProjectsStatusLabel($item['status']) . ($isOverdue ? ' ' . omoProjectsT('projects.gantt.overdue') : ''))) ?>" style="--omo-project-gantt-depth: <?= (int)$item['depth'] ?>;" tabindex="0" role="button" aria-label="<?= omoApiEscape($projectAriaLabel) ?>">
                                    <div class="omo-project-gantt-row__project" data-omo-project-gantt-project<?= $isOverdue ? ' title="' . omoApiEscape(omoProjectsT('projects.gantt.overdue')) . '"' : '' ?>>
                                        <?php if (omoProjectsCanManageProject($project, $context)): ?><label class="omo-project-selection-control"><input type="checkbox" data-omo-project-select value="<?= (int)$project->getId() ?>" aria-label="<?= omoApiEscape(omoProjectsT('projects.selection.toggle')) ?>"></label><?php endif; ?>
                                            <strong><?= omoApiEscape($projectTitle) ?></strong>
                                            <span><?= omoApiEscape($item['holonLabel']) ?> · <?= omoApiEscape($item['responsibleLabel']) ?></span>
                                        </div>
                                        <div class="omo-projects__gantt-timeline omo-project-gantt-row__timeline" data-omo-project-gantt-timeline>
                                            <?php if ($ganttTodayOffset !== null): ?><span class="omo-projects__gantt-today" style="--omo-project-gantt-left: <?= number_format($ganttTodayOffset, 4, '.', '') ?>%;" aria-hidden="true"></span><?php endif; ?>
                                            <?php if ($hasDates): ?>
                                                <span class="omo-project-gantt-row__bar" data-omo-project-gantt-bar style="--omo-project-gantt-left: <?= number_format($barLeft, 4, '.', '') ?>%; --omo-project-gantt-width: <?= number_format($barWidth, 4, '.', '') ?>%;" title="<?= omoApiEscape($dateLabel) ?>"><span><?= omoApiEscape($dateLabel) ?></span></span>
                                            <?php else: ?>
                                                <span class="omo-project-gantt-row__no-dates"><?= omoApiEscape(omoProjectsT('projects.gantt.no_dates')) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            <div class="omo-projects__search-empty omo-empty-state" data-omo-projects-search-empty hidden><?= omoApiEscape(omoProjectsT('projects.search.empty')) ?></div>
        </div>
    </div>

    <div class="omo-overlay-drawer omo-projects__drawer" data-omo-projects-drawer hidden>
        <div class="omo-overlay-drawer__backdrop" data-omo-projects-drawer-close></div>
        <div class="omo-overlay-drawer__panel">
            <div class="omo-overlay-drawer__header generic-drawer-header generic-drawer-header--sticky">
                <div class="omo-overlay-drawer__header-copy generic-drawer-header__copy">
                    <h3 class="omo-overlay-drawer__title" data-omo-subdrawer-title><?= omoApiEscape(omoProjectsT('projects.drawer.title')) ?></h3>
                    <p class="omo-overlay-drawer__description" data-omo-subdrawer-description><?= omoApiEscape(omoProjectsT('projects.drawer.description')) ?></p>
                </div>
                <div class="generic-drawer-header__actions">
                    <div data-omo-subdrawer-actions></div>
                    <button type="button" class="omo-overlay-drawer__close generic-action-button generic-action-button--secondary" data-omo-projects-drawer-close><?= omoApiEscape(omoProjectsT('projects.action.close')) ?></button>
                </div>
            </div>
            <div class="omo-overlay-drawer__body" data-omo-projects-drawer-body></div>
        </div>
    </div>
    <div class="omo-overlay-drawer omo-projects__document-drawer" data-omo-projects-document-drawer hidden>
        <div class="omo-overlay-drawer__backdrop" data-omo-projects-document-drawer-close></div>
        <div class="omo-overlay-drawer__panel">
            <div class="omo-overlay-drawer__header generic-drawer-header generic-drawer-header--sticky">
                <div class="omo-overlay-drawer__header-copy generic-drawer-header__copy">
                    <h3 class="omo-overlay-drawer__title" data-omo-subdrawer-title><?= omoApiEscape(omoProjectsT('projects.detail.documents.drawer_title')) ?></h3>
                    <p class="omo-overlay-drawer__description" data-omo-subdrawer-description><?= omoApiEscape(omoProjectsT('projects.detail.documents.drawer_description')) ?></p>
                </div>
                <div class="generic-drawer-header__actions">
                    <div data-omo-subdrawer-actions></div>
                    <button type="button" class="omo-overlay-drawer__close generic-action-button generic-action-button--secondary" data-omo-projects-document-drawer-close><?= omoApiEscape(omoProjectsT('projects.action.close')) ?></button>
                </div>
            </div>
            <div class="omo-overlay-drawer__body" data-omo-projects-document-drawer-body></div>
        </div>
    </div>
</div>
<script src="/common/drawer/subdrawer.js"></script>
<script src="/common/calendar/event-editor.js?v=20260804-project-events"></script>
<script src="/omo/api/projects/projects.js?v=20260807-project-bulk-actions"></script>
