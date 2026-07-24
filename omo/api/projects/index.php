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
$projectView = strtolower(trim((string)($_GET['project_view'] ?? 'kanban')));
$projectView = $projectView === 'list' ? 'list' : 'kanban';
$projectListSort = strtolower(trim((string)($_GET['project_sort'] ?? 'planned')));
$projectListSort = in_array($projectListSort, ['priority', 'importance', 'holon'], true) ? $projectListSort : 'planned';
if ($projectListSort === 'holon' && !in_array($projectScope, ['children', 'descendants'], true)) {
    $projectListSort = 'planned';
}
$canSortProjectsByHolon = in_array($projectScope, ['children', 'descendants'], true);
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
$scopeActiveIndex = omoApiResolveContextScopeIndex($projectScope, $availableScopes);
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
    if (!($allProject instanceof Project) || !omoProjectsScopeContainsProject($allProject, $projectScope, $scopeCurrentHolonId, $scopeHolonIds)) {
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

    foreach ($listProjectItems as $item) {
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
$listProjectGroups = array_filter($listProjectGroups, static fn (array $group): bool => count($group['items']) > 0);

$currentUrl = '/omo/api/projects/index.php?oid=' . rawurlencode((string)$organizationId);
if ($currentHolonId > 0) {
    $currentUrl .= '&cid=' . rawurlencode((string)$currentHolonId);
}
if ($projectScope !== 'contextual') {
    $currentUrl .= '&project_scope=' . rawurlencode($projectScope);
}
if ($projectView === 'list') {
    $currentUrl .= '&project_view=list';
}
if ($projectListSort !== 'planned') {
    $currentUrl .= '&project_sort=' . rawurlencode($projectListSort);
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
$projectTexts = [
    'loading' => omoProjectsT('projects.loading'),
    'loadingError' => omoProjectsT('projects.loading_error'),
    'emptyColumn' => omoProjectsT('projects.empty.column'),
    'statusUpdateError' => omoProjectsT('projects.status_update_error'),
    'actionError' => omoProjectsT('projects.action_error'),
    'deleteConfirm' => omoProjectsT('projects.delete.confirm'),
    'archiveConfirm' => omoProjectsT('projects.archive.confirm'),
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
];
?>
<link rel="stylesheet" href="/omo/api/projects/projects.css?v=20260724-subproject-actions">
<div
    class="omo-projects omo-panel-view"
    id="omo-projects-root"
    data-omo-projects-oid="<?= (int)$organizationId ?>"
    data-omo-projects-cid="<?= $currentHolon instanceof Holon ? (int)$currentHolon->getId() : 0 ?>"
    data-omo-projects-scope="<?= omoApiEscape($projectScope) ?>"
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
                    <svg viewBox="0 0 24 24"><path d="M4 5.5h6l1.8 2H20v11H4z"/><path d="M4 8h16"/></svg>
                </span>
                <div class="omo-panel-view__header-copy">
                    <div class="omo-projects__title-row">
                        <h2 class="omo-panel-view__title"><?= omoApiEscape(omoProjectsT('projects.title')) ?></h2>
                        <span class="omo-panel-view__count"><?= (int)($projectView === 'list' ? count($listProjectItems) : $projectCount) ?></span>
                    </div>
                </div>
            </div>
            <?php if ($canCreate): ?>
                <div class="omo-projects__header-actions">
                    <button type="button" class="generic-action-button generic-action-button--main omo-mobile-corner-action" data-omo-projects-open-create><?= omoApiEscape(omoProjectsT('projects.action.new')) ?></button>
                </div>
            <?php endif; ?>
        </div>
        <div class="omo-panel-view__header-secondary">
            <div class="omo-scope-toolbar__main">
                <?php if (count($availableScopes) > 1): ?>
                    <div
                        class="omo-scope-toggle"
                        data-omo-scope-switch="<?= omoApiEscape($projectScope) ?>"
                        style="--omo-scope-option-count: <?= count($availableScopes) ?>; --omo-scope-active-index: <?= (int)$scopeActiveIndex ?>;"
                    >
                        <?php foreach ($availableScopes as $scopeIndex => $scopeKey): ?>
                            <button
                                type="button"
                                class="omo-scope-toggle__button<?= $projectScope === $scopeKey ? ' is-active' : '' ?>"
                                data-omo-projects-scope="<?= omoApiEscape($scopeKey) ?>"
                                data-omo-scope-option="<?= omoApiEscape($scopeKey) ?>"
                                data-omo-scope-index="<?= (int)$scopeIndex ?>"
                                aria-pressed="<?= $projectScope === $scopeKey ? 'true' : 'false' ?>"
                            ><span class="omo-scope-toggle__text"><?= omoApiEscape(omoProjectsT('projects.scope.' . $scopeKey)) ?></span></button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="omo-projects__header-view-controls">
                <div class="omo-segmented" role="group" aria-label="<?= omoApiEscape(omoProjectsT('projects.sort.aria')) ?>">
                    <button type="button" class="omo-segmented__button<?= $projectListSort === 'planned' ? ' is-active' : '' ?>" data-omo-projects-sort="planned" aria-pressed="<?= $projectListSort === 'planned' ? 'true' : 'false' ?>"><?= omoApiEscape(omoProjectsT('projects.sort.planned')) ?></button>
                    <button type="button" class="omo-segmented__button<?= $projectListSort === 'priority' ? ' is-active' : '' ?>" data-omo-projects-sort="priority" aria-pressed="<?= $projectListSort === 'priority' ? 'true' : 'false' ?>"><?= omoApiEscape(omoProjectsT('projects.sort.priority')) ?></button>
                    <button type="button" class="omo-segmented__button<?= $projectListSort === 'importance' ? ' is-active' : '' ?>" data-omo-projects-sort="importance" aria-pressed="<?= $projectListSort === 'importance' ? 'true' : 'false' ?>"><?= omoApiEscape(omoProjectsT('projects.sort.importance')) ?></button>
                    <?php if ($canSortProjectsByHolon): ?>
                        <button type="button" class="omo-segmented__button<?= $projectListSort === 'holon' ? ' is-active' : '' ?>" data-omo-projects-sort="holon" aria-pressed="<?= $projectListSort === 'holon' ? 'true' : 'false' ?>"><?= omoApiEscape(omoProjectsT('projects.sort.holon')) ?></button>
                    <?php endif; ?>
                </div>
                <div class="omo-segmented" role="group" aria-label="<?= omoApiEscape(omoProjectsT('projects.view.aria')) ?>">
                    <button type="button" class="omo-segmented__button<?= $projectView === 'kanban' ? ' is-active' : '' ?>" data-omo-projects-view="kanban" aria-pressed="<?= $projectView === 'kanban' ? 'true' : 'false' ?>"><?= omoApiEscape(omoProjectsT('projects.view.kanban')) ?></button>
                    <button type="button" class="omo-segmented__button<?= $projectView === 'list' ? ' is-active' : '' ?>" data-omo-projects-view="list" aria-pressed="<?= $projectView === 'list' ? 'true' : 'false' ?>"><?= omoApiEscape(omoProjectsT('projects.view.list')) ?></button>
                </div>
                <div class="omo-projects__mobile-column-nav" aria-label="<?= omoApiEscape(omoProjectsT('projects.title')) ?>"<?= $projectView === 'kanban' ? '' : ' hidden' ?>>
                    <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-projects-column-prev aria-label="<?= omoApiEscape(omoProjectsT('projects.column.previous')) ?>">&lsaquo;</button>
                    <span data-omo-projects-column-label></span>
                    <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-projects-column-next aria-label="<?= omoApiEscape(omoProjectsT('projects.column.next')) ?>">&rsaquo;</button>
                </div>
            </div>
        </div>
    </header>

    <div class="omo-panel-view__body">
        <div class="omo-panel-view__body_content omo-projects__body">
            <?php if ($projectView === 'kanban'): ?>
            <div class="omo-projects__board" data-omo-projects-board>
                <?php foreach ($columns as $status): ?>
                    <?php $columnItems = $projectsByStatus[$status] ?? []; ?>
                    <section class="omo-projects__column" data-omo-projects-column="<?= omoApiEscape($status) ?>">
                        <header class="omo-projects__column-header">
                            <div>
                                <span class="omo-projects__column-kicker"><?= omoApiEscape(omoProjectsT('projects.title')) ?></span>
                                <h3><?= omoApiEscape(omoProjectsStatusLabel($status)) ?></h3>
                            </div>
                            <span class="omo-projects__column-count omo-projects__column-count--<?= omoApiEscape($status) ?>" data-omo-projects-column-count><?= count($columnItems) ?></span>
                        </header>
                        <div class="omo-projects__column-cards" data-omo-projects-cards="<?= omoApiEscape($status) ?>" data-status="<?= omoApiEscape($status) ?>">
                            <?php foreach ($columnItems as $item): ?>
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
                                    data-project-holon-id="<?= (int)$project->get('IDholon') ?>"
                                    data-project-subproject-count="<?= (int)$subprojectCount ?>"
                                    tabindex="0"
                                    role="button"
                                    aria-label="<?= omoApiEscape($projectTitle) ?>"
                                >
                                    <div class="omo-project-card__topline">
                                        <span class="omo-project-card__context"><?= omoApiEscape($item['contextLabel']) ?></span>
                                        <span class="omo-project-card__topline-actions">
                                            <span class="omo-project-card__size" title="<?= omoApiEscape(omoProjectsT('projects.detail.size')) ?>"><?= omoApiEscape($projectSize) ?></span>
                                            <?php if ($item['priority'] !== null): ?><span class="generic-project-priority generic-project-priority--p<?= (int)$item['priority'] ?>" title="<?= omoApiEscape(omoProjectsT('projects.detail.priority')) ?>">P<?= (int)$item['priority'] ?></span><?php endif; ?>
                                            <?php if ($canManageProject): ?>
                                                <div class="generic-menu omo-project-card__menu" data-omo-project-menu>
                                                    <button type="button" class="generic-menu-toggle omo-project-card__menu-toggle" data-omo-project-menu-toggle aria-expanded="false" aria-label="<?= omoApiEscape($projectTitle) ?>">&#8942;</button>
                                                    <div class="generic-menu-panel omo-project-card__menu-panel" data-omo-project-menu-panel role="menu" hidden>
                                                        <button type="button" class="generic-menu-item" data-omo-project-action="move" role="menuitem"><?= omoApiEscape(omoProjectsT('projects.action.move')) ?></button>
                                                        <button type="button" class="generic-menu-item" data-omo-project-action="archive" role="menuitem"><?= omoApiEscape(omoProjectsT('projects.action.archive')) ?></button>
                                                        <button type="button" class="generic-menu-item generic-menu-item--danger" data-omo-project-action="delete" role="menuitem"><?= omoApiEscape(omoProjectsT('projects.action.delete')) ?></button>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <h4 class="omo-project-card__title"><?= omoApiEscape($projectTitle) ?></h4>
                                    <div class="omo-project-card__meta">
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
                            <?php if (count($columnItems) === 0): ?>
                                <div class="omo-projects__column-empty" data-omo-projects-column-empty><?= omoApiEscape(omoProjectsT('projects.empty.column')) ?></div>
                            <?php endif; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
            <?php if ($projectCount === 0): ?>
                <div class="omo-projects__board-empty omo-empty-state"><?= omoApiEscape(omoProjectsT($emptyKey)) ?></div>
            <?php endif; ?>
            <?php else: ?>
                <?php if (count($listProjectItems) === 0): ?>
                    <div class="omo-projects__board-empty omo-empty-state"><?= omoApiEscape(omoProjectsT($emptyKey)) ?></div>
                <?php else: ?>
                    <div class="generic-file-list generic-file-list--structured omo-projects__list" data-omo-projects-list>
                        <?php foreach ($listProjectGroups as $group): ?>
                            <section class="generic-file-list__group omo-projects__list-group">
                                <h3 class="generic-card-title generic-card-title--small generic-file-list__group-title"><?= omoApiEscape($group['label']) ?></h3>
                                <div class="omo-projects__list-group-items">
                                    <?php foreach ($group['items'] as $item): ?>
                                        <?php $project = $item['project']; ?>
                                        <article class="omo-project-list-item omo-project-list-item--<?= omoApiEscape($item['status']) ?>" data-omo-project-list-item data-project-id="<?= (int)$project->getId() ?>" data-project-parent-id="<?= (int)$project->get('IDproject_parent') ?>" tabindex="0" role="button" aria-label="<?= omoApiEscape((string)$project->get('title')) ?>">
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
            <?php endif; ?>
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
</div>
<script src="/common/drawer/subdrawer.js"></script>
<script src="/omo/api/projects/projects.js?v=20260724-project-breadcrumb-collapse-visibility"></script>
