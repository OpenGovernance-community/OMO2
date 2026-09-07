<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\Holon;
use dbObject\ArrayProject;
use dbObject\Project;
use dbObject\ProjectImportanceCalculator;
use dbObject\PropertyFormat;

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$projectId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

if ($organizationId <= 0 || $projectId <= 0) {
    http_response_code(404);
    echo '<div class="omo-empty-state">' . omoApiEscape(omoProjectsT('projects.error.not_found')) . '</div>';
    exit;
}

$context = omoProjectsResolveContext($organizationId, isset($_GET['cid']) ? (int)$_GET['cid'] : 0);
if (empty($context['status'])) {
    http_response_code(403);
    echo '<div class="omo-empty-state">' . omoApiEscape((string)$context['message']) . '</div>';
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

$organization = $context['organization'];
$projectDisplayConfig = omoProjectsGetDisplayConfig($organizationId);
$enabledStatuses = $projectDisplayConfig['enabledStatuses'];
$usesPriority = !empty($projectDisplayConfig['usePriority']);
$usesImportance = !empty($projectDisplayConfig['useImportance']);
$usesSize = !empty($projectDisplayConfig['useSize']);
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

$responsible = $project->getResponsible();
$parent = $project->getParent();
$canEdit = !$isArchivedProject && omoProjectsCanManageProject($project, $context);
$currentUserId = function_exists('commonGetCurrentUserId') ? (int)commonGetCurrentUserId() : 0;
$canCreateSubproject = !$isArchivedProject && ($projectHolon instanceof Holon
    ? $projectHolon->isAllowed('CAN_CREATE_PROJECT', true, $currentUserId)
    : omoProjectsCanCreateContext($context));
$editUrl = '/omo/api/projects/create.php?oid=' . rawurlencode((string)$organizationId) . '&id=' . rawurlencode((string)$projectId);
if ((int)($_GET['cid'] ?? 0) > 0) {
    $editUrl .= '&cid=' . rawurlencode((string)(int)$_GET['cid']);
}
$description = PropertyFormat::sanitizeHtml((string)$project->get('description'));
$projectSize = Project::normalizeSize($project->get('project_size'));
$calculatedImportance = ProjectImportanceCalculator::toBusinessScale($project->get('calculated_importance'));
$contextLabel = $projectHolon instanceof Holon
    ? trim((string)$projectHolon->getDisplayName())
    : trim((string)$organization->get('name'));
$startDate = omoProjectsFormatDate($project->get('planned_start_date'));
$endDate = omoProjectsFormatDate($project->get('planned_end_date'));
$createdAt = $project->get('created_at');
$allProjects = new ArrayProject();
$allProjects->loadForOrganization($organizationId, !$isArchivedProject);
$projectsByParent = [];
$projectsById = [];
$attachableProjects = [];
foreach ($allProjects as $allProject) {
    if (!($allProject instanceof Project) || (int)$allProject->getId() <= 0) {
        continue;
    }
    $projectsById[(int)$allProject->getId()] = $allProject;
    $parentId = (int)$allProject->get('IDproject_parent');
    if ($parentId > 0) {
        $projectsByParent[$parentId][] = $allProject;
    }

    $candidateHolon = $allProject->getHolon();
    if (
        !$canEdit
        || (int)$allProject->getId() === (int)$project->getId()
        || (int)$allProject->get('active') !== 1
        || (int)$allProject->get('IDproject_parent') > 0
        || Project::normalizeKind($allProject->get('project_kind')) !== Project::KIND_STANDARD
        || !$allProject->canUseAsParent($project)
        || !omoProjectsCanManageProject($allProject, $context)
        || ($candidateHolon instanceof Holon && (
            !($rootHolon instanceof Holon)
            || !$candidateHolon->isDescendantOf((int)$rootHolon->getId(), true)
            || !$candidateHolon->canViewDetail()
        ))
    ) {
        continue;
    }

    $attachableProjects[] = [
        'id' => (int)$allProject->getId(),
        'title' => trim((string)$allProject->get('title')),
        'holonId' => $candidateHolon instanceof Holon ? (int)$candidateHolon->getId() : 0,
        'context' => $candidateHolon instanceof Holon
            ? trim((string)$candidateHolon->getDisplayName())
            : trim((string)$organization->get('name')),
    ];
}
$projectBreadcrumb = [];
$breadcrumbVisited = [(int)$project->getId() => true];
$breadcrumbParentId = (int)$project->get('IDproject_parent');
while ($breadcrumbParentId > 0 && !isset($breadcrumbVisited[$breadcrumbParentId])) {
    $breadcrumbVisited[$breadcrumbParentId] = true;
    $breadcrumbProject = $projectsById[$breadcrumbParentId] ?? null;
    if (!($breadcrumbProject instanceof Project) || (int)$breadcrumbProject->get('active') !== 1) {
        break;
    }

    $projectBreadcrumb[] = [
        'id' => (int)$breadcrumbProject->getId(),
        'title' => trim((string)$breadcrumbProject->get('title')),
    ];
    $breadcrumbParentId = (int)$breadcrumbProject->get('IDproject_parent');
}
$projectBreadcrumb = array_reverse($projectBreadcrumb);
$newSubprojectUrl = '/omo/api/projects/create.php?oid=' . rawurlencode((string)$organizationId)
    . '&parent_id=' . rawurlencode((string)$projectId);
if ($projectHolon instanceof Holon) {
    $newSubprojectUrl .= '&cid=' . rawurlencode((string)$projectHolon->getId());
}
$hasSubprojectActions = $canCreateSubproject || count($attachableProjects) > 0;
$subprojects = $projectsByParent[(int)$project->getId()] ?? [];
$archivedSubprojects = new ArrayProject();
$archivedSubprojects->loadForParent((int)$project->getId(), false);
$archivedSubprojectCount = 0;
foreach ($archivedSubprojects as $archivedSubproject) {
    if ($archivedSubproject instanceof Project && (int)$archivedSubproject->get('active') !== 1) {
        $archivedSubprojectCount++;
    }
}
$archivedProjectsUrl = '/omo/api/projects/archives.php?oid=' . rawurlencode((string)$organizationId)
    . '&id=' . rawurlencode((string)$projectId);
if ((int)($_GET['cid'] ?? 0) > 0) {
    $archivedProjectsUrl .= '&cid=' . rawurlencode((string)(int)$_GET['cid']);
}
$statusSummaryMemo = [];
$projectStatusSummary = omoProjectsBuildStatusBar($project, $projectsByParent, $statusSummaryMemo);
$detailStatusOptions = $enabledStatuses;
$documentsUrl = '/omo/api/projects/documents.php?oid=' . rawurlencode((string)$organizationId)
    . '&id=' . rawurlencode((string)$projectId);
$eventsUrl = '/omo/api/projects/events.php?oid=' . rawurlencode((string)$organizationId)
    . '&id=' . rawurlencode((string)$projectId);
if ((int)($_GET['cid'] ?? 0) > 0) {
    $documentsUrl .= '&cid=' . rawurlencode((string)(int)$_GET['cid']);
    $eventsUrl .= '&cid=' . rawurlencode((string)(int)$_GET['cid']);
}
?>
<div
    class="omo-project-detail"
    data-omo-project-detail="<?= (int)$project->getId() ?>"
    data-omo-project-holon-id="<?= $projectHolon instanceof Holon ? (int)$projectHolon->getId() : 0 ?>"
    data-omo-project-attach-candidates="<?= omoApiEscape(json_encode($attachableProjects, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
>
    <div
        hidden
        data-omo-subdrawer-header
        data-omo-subdrawer-title="<?= omoApiEscape((string)$project->get('title')) ?>"
        data-omo-subdrawer-description="<?= omoApiEscape($contextLabel) ?>"
    >
        <?php if ($canEdit): ?>
            <button
                type="button"
                class="generic-action-button generic-action-button--main"
                data-omo-subdrawer-action
                data-omo-projects-open-edit-url="<?= omoApiEscape($editUrl) ?>"
                data-omo-projects-edit-project-id="<?= (int)$project->getId() ?>"
            ><?= omoApiEscape(omoProjectsT('projects.action.edit')) ?></button>
        <?php endif; ?>
    </div>
    <?php if (count($projectBreadcrumb) > 0): ?>
        <nav class="omo-project-detail__breadcrumb" aria-label="<?= omoApiEscape(omoProjectsT('projects.detail.breadcrumb')) ?>">
            <?php $breadcrumbCount = count($projectBreadcrumb); ?>
            <button type="button" class="omo-project-detail__breadcrumb-link" data-omo-project-breadcrumb data-project-id="<?= (int)$projectBreadcrumb[0]['id'] ?>"><?= omoApiEscape($projectBreadcrumb[0]['title']) ?></button>
            <?php if ($breadcrumbCount > 2): ?>
                <span class="omo-project-detail__breadcrumb-collapse" data-omo-project-breadcrumb-collapse>
                    <span class="omo-project-detail__breadcrumb-separator" aria-hidden="true">&#8250;</span>
                    <button type="button" class="omo-project-detail__breadcrumb-expand" data-omo-project-breadcrumb-expand aria-expanded="false" aria-controls="omo-project-breadcrumb-extra-<?= (int)$project->getId() ?>" title="<?= omoApiEscape(omoProjectsT('projects.detail.breadcrumb.expand')) ?>">&hellip;</button>
                </span>
                <span id="omo-project-breadcrumb-extra-<?= (int)$project->getId() ?>" class="omo-project-detail__breadcrumb-extra" data-omo-project-breadcrumb-extra hidden>
                    <?php foreach (array_slice($projectBreadcrumb, 1, -1) as $breadcrumbItem): ?>
                        <span class="omo-project-detail__breadcrumb-separator" aria-hidden="true">&#8250;</span>
                        <button type="button" class="omo-project-detail__breadcrumb-link" data-omo-project-breadcrumb data-project-id="<?= (int)$breadcrumbItem['id'] ?>"><?= omoApiEscape($breadcrumbItem['title']) ?></button>
                    <?php endforeach; ?>
                </span>
            <?php endif; ?>
            <?php if ($breadcrumbCount > 1): ?><span class="omo-project-detail__breadcrumb-separator" aria-hidden="true">&#8250;</span><?php endif; ?>
            <?php if ($breadcrumbCount > 1): ?><button type="button" class="omo-project-detail__breadcrumb-link" data-omo-project-breadcrumb data-project-id="<?= (int)$projectBreadcrumb[$breadcrumbCount - 1]['id'] ?>"><?= omoApiEscape($projectBreadcrumb[$breadcrumbCount - 1]['title']) ?></button><?php endif; ?>
        </nav>
    <?php endif; ?>

    <div class="generic-tabs omo-project-detail__tabs" data-generic-tabs>
        <div class="generic-tabs__list" aria-label="<?= omoApiEscape(omoProjectsT('projects.detail.tabs.label')) ?>">
            <button type="button" class="generic-tabs__tab is-active" data-generic-tab data-generic-tab-target="omo-project-detail-information-<?= (int)$project->getId() ?>"><?= omoApiEscape(omoProjectsT('projects.detail.tabs.information')) ?></button>
            <button type="button" class="generic-tabs__tab" data-generic-tab data-generic-tab-target="omo-project-detail-documents-<?= (int)$project->getId() ?>" data-omo-project-detail-documents-tab><?= omoApiEscape(omoProjectsT('projects.detail.tabs.documents')) ?></button>
            <button type="button" class="generic-tabs__tab" data-generic-tab data-generic-tab-target="omo-project-detail-events-<?= (int)$project->getId() ?>" data-omo-project-detail-events-tab><?= omoApiEscape(omoProjectsT('projects.detail.tabs.events')) ?></button>
        </div>
        <div class="generic-tabs__panels">
            <div id="omo-project-detail-information-<?= (int)$project->getId() ?>" class="generic-tabs__panel omo-project-detail__tab-panel" data-generic-tab-panel>
                <div class="omo-project-detail__tab-content generic-drawer-content">
    <section class="generic-section omo-project-detail__section">
        <h3 class="generic-card-title generic-card-title--big"><?= omoApiEscape(omoProjectsT('projects.detail.description')) ?></h3>
        <?php if ($description !== ''): ?>
            <div class="omo-simple-html-render omo-project-detail__description generic-description generic-description--small generic-description--relaxed"><?= $description ?></div>
        <?php else: ?>
            <p class="omo-project-detail__muted generic-description generic-description--small"><?= omoApiEscape(omoProjectsT('projects.detail.empty_description')) ?></p>
        <?php endif; ?>
    </section>

    <?php if (count($subprojects) > 0 || $hasSubprojectActions): ?>
        <section class="generic-section omo-project-detail__section omo-project-detail__subprojects">
            <div class="omo-project-detail__subprojects-heading">
                <h3 class="generic-card-title generic-card-title--big"><?= omoApiEscape(omoProjectsT('projects.detail.subprojects')) ?></h3>
                <?php if ($hasSubprojectActions): ?>
                    <div class="omo-project-detail__subprojects-actions">
                        <?php if ($canCreateSubproject): ?>
                            <button type="button" class="generic-action-button generic-action-button--main" data-omo-project-detail-new-subproject-url="<?= omoApiEscape($newSubprojectUrl) ?>"><?= omoApiEscape(omoProjectsT('projects.detail.subprojects_new')) ?></button>
                        <?php endif; ?>
                        <?php if (count($attachableProjects) > 0): ?>
                            <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-project-detail-attach-subproject><?= omoApiEscape(omoProjectsT('projects.action.attach')) ?></button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php if (count($subprojects) > 0): ?>
                <?= omoProjectsRenderStatusBar($projectStatusSummary, 'omo-project-detail__subprojects-bar') ?>
                <div class="omo-project-detail__subprojects-list">
                    <?php foreach ($subprojects as $subproject): ?>
                    <?php
                    $subprojectStatus = Project::normalizeStatus($subproject->get('status'));
                    $subprojectSummary = omoProjectsBuildStatusBar($subproject, $projectsByParent, $statusSummaryMemo, true);
                    $subprojectIsProject = count($projectsByParent[(int)$subproject->getId()] ?? []) > 0;
                    $subprojectHolon = $subproject->getHolon();
                    $subprojectHolonLabel = $subprojectHolon instanceof Holon
                        ? trim((string)$subprojectHolon->getDisplayName())
                        : omoProjectsT('projects.detail.none');
                    $subprojectResponsible = omoProjectsGetUserLabel($subproject->getResponsible());
                    $subprojectCanEdit = !$isArchivedProject
                        && (int)$subproject->get('active') === 1
                        && omoProjectsCanManageProject($subproject, $context);
                    $subprojectCanDelete = omoProjectsCanDeleteProject($subproject, $context);
                    ?>
                    <article
                        class="omo-project-detail__subproject-item omo-project-detail__subproject-item--<?= omoApiEscape($subprojectStatus) ?><?= $subprojectIsProject ? '' : ' omo-project-detail__subproject-item--task' ?>"
                        data-omo-project-subproject
                        data-project-id="<?= (int)$subproject->getId() ?>"
                        tabindex="0"
                        role="button"
                    >
                        <span class="omo-project-detail__subproject-icon" aria-hidden="true">
                            <?php if ($subprojectIsProject): ?>
                                <img src="/omo/images/tools/product.png" alt="">
                            <?php else: ?>
                                <img src="/omo/assets/images/projects/task-complete.png" alt="">
                            <?php endif; ?>
                        </span>
                        <div class="omo-project-detail__subproject-copy">
                            <strong><?= omoApiEscape((string)$subproject->get('title')) ?></strong>
                            <div class="omo-project-detail__subproject-meta">
                                <span><?= omoApiEscape($subprojectHolonLabel) ?></span>
                                <span><?= omoApiEscape($subprojectResponsible) ?></span>
                                <span class="omo-project-status omo-project-status--<?= omoApiEscape($subprojectStatus) ?>"><?= omoApiEscape(omoProjectsStatusLabel($subprojectStatus)) ?></span>
                                <?php if ($usesSize): ?><span class="omo-project-detail__subproject-size"><?= omoApiEscape(Project::normalizeSize($subproject->get('project_size'))) ?></span><?php endif; ?>
                            </div>
                        </div>
                        <?php if ($subprojectIsProject): ?>
                            <?= omoProjectsRenderStatusBar($subprojectSummary, 'omo-project-detail__subproject-bar') ?>
                        <?php elseif ($subprojectCanEdit && in_array($subprojectStatus, $detailStatusOptions, true)): ?>
                            <select class="generic-form-control omo-project-detail__subproject-status-select" data-omo-project-detail-status-select data-project-id="<?= (int)$subproject->getId() ?>" data-previous-status="<?= omoApiEscape($subprojectStatus) ?>" aria-label="<?= omoApiEscape(omoProjectsT('projects.status_move')) ?>">
                                <?php foreach ($detailStatusOptions as $statusOption): ?>
                                    <option value="<?= omoApiEscape($statusOption) ?>"<?= $statusOption === $subprojectStatus ? ' selected' : '' ?>><?= omoApiEscape(omoProjectsStatusLabel($statusOption)) ?></option>
                                <?php endforeach; ?>
                                <option disabled>──────────</option>
                                <option value="__archive__"><?= omoApiEscape(omoProjectsT('projects.detail.task.archive')) ?></option>
                                <?php if ($subprojectCanDelete): ?><option value="__delete__"><?= omoApiEscape(omoProjectsT('projects.detail.task.delete')) ?></option><?php endif; ?>
                            </select>
                        <?php endif; ?>
                    </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="omo-project-detail__muted generic-description generic-description--small"><?= omoApiEscape(omoProjectsT('projects.detail.subprojects_empty')) ?></p>
            <?php endif; ?>
            <?php if ($archivedSubprojectCount > 0): ?>
                <a
                    href="#"
                    class="omo-project-detail__archives-link"
                    data-omo-project-detail-archives-link
                    data-omo-project-detail-archives-url="<?= omoApiEscape($archivedProjectsUrl) ?>"
                >
                    <span><?= omoApiEscape(omoProjectsT('projects.detail.archives.link')) ?></span>
                    <span class="omo-project-detail__archives-count"><?= (int)$archivedSubprojectCount ?></span>
                </a>
            <?php endif; ?>
        </section>
    <?php endif; ?>
    <div class="omo-project-detail__grid">
        <section class="generic-soft-panel omo-project-detail__section">
            <h3 class="generic-card-title generic-card-title--big"><?= omoApiEscape(omoProjectsT('projects.detail.schedule')) ?></h3>
            <dl class="omo-project-detail__facts">
                <div><dt><?= omoApiEscape(omoProjectsT('projects.detail.date_start')) ?></dt><dd><?= omoApiEscape($startDate !== '' ? $startDate : omoProjectsT('projects.detail.none')) ?></dd></div>
                <div><dt><?= omoApiEscape(omoProjectsT('projects.detail.date_end')) ?></dt><dd><?= omoApiEscape($endDate !== '' ? $endDate : omoProjectsT('projects.detail.none')) ?></dd></div>
            </dl>
        </section>
        <section class="generic-soft-panel omo-project-detail__section">
            <h3 class="generic-card-title generic-card-title--big"><?= omoApiEscape(omoProjectsT('projects.detail.context')) ?></h3>
            <dl class="omo-project-detail__facts">
                <div><dt><?= omoApiEscape(omoProjectsT('projects.detail.organisation')) ?></dt><dd><?= omoApiEscape((string)$organization->get('name')) ?></dd></div>
                <div><dt><?= omoApiEscape(omoProjectsT('projects.detail.context')) ?></dt><dd><?= omoApiEscape($contextLabel) ?></dd></div>
                <div><dt><?= omoApiEscape(omoProjectsT('projects.detail.responsible')) ?></dt><dd><?= omoApiEscape(omoProjectsGetUserLabel($responsible)) ?></dd></div>
            </dl>
        </section>
        <section class="generic-soft-panel omo-project-detail__section">
            <h3 class="generic-card-title generic-card-title--big"><?= omoApiEscape(omoProjectsT('projects.detail.status')) ?></h3>
            <dl class="omo-project-detail__facts">
                <?php if ($usesPriority): ?><div><dt><?= omoApiEscape(omoProjectsT('projects.detail.priority')) ?></dt><dd><?= $project->get('priority') !== null && $project->get('priority') !== '' ? omoApiEscape('P' . (string)$project->get('priority')) : omoApiEscape(omoProjectsT('projects.detail.none')) ?></dd></div><?php endif; ?>
                <?php if ($usesImportance): ?>
                    <div><dt><?= omoApiEscape(omoProjectsT('projects.detail.importance')) ?></dt><dd><?= $project->get('importance') !== null && $project->get('importance') !== '' ? omoApiEscape((string)$project->get('importance') . '/5') : omoApiEscape(omoProjectsT('projects.detail.none')) ?></dd></div>
                    <div title="<?= omoApiEscape(omoProjectsT('projects.detail.calculated_importance_help')) ?>"><dt><?= omoApiEscape(omoProjectsT('projects.detail.calculated_importance')) ?></dt><dd><?= omoApiEscape(number_format($calculatedImportance, 2, '.', '')) ?>/5</dd></div>
                <?php endif; ?>
                <?php if ($usesSize): ?><div><dt><?= omoApiEscape(omoProjectsT('projects.detail.size')) ?></dt><dd><?= omoApiEscape($projectSize) ?></dd></div><?php endif; ?>
            </dl>
        </section>
        <section class="generic-soft-panel omo-project-detail__section">
            <h3 class="generic-card-title generic-card-title--big"><?= omoApiEscape(omoProjectsT('projects.detail.parent')) ?></h3>
            <p class="omo-project-detail__value"><?= omoApiEscape($parent instanceof Project ? (string)$parent->get('title') : omoProjectsT('projects.detail.none')) ?></p>
            <p class="omo-project-detail__created generic-meta"><?= omoApiEscape(omoProjectsT('projects.detail.created')) ?> <?= omoApiEscape(omoProjectsFormatDate($createdAt)) ?></p>
        </section>
    </div>
                </div>
            </div>
            <div id="omo-project-detail-documents-<?= (int)$project->getId() ?>" class="generic-tabs__panel omo-project-detail__tab-panel" data-generic-tab-panel data-omo-project-detail-documents-panel data-omo-project-detail-documents-url="<?= omoApiEscape($documentsUrl) ?>" data-omo-project-detail-documents-loaded="0" hidden>
                <div class="omo-project-detail__tab-content omo-project-detail__documents-content generic-drawer-content" data-omo-project-detail-documents-content></div>
            </div>
            <div id="omo-project-detail-events-<?= (int)$project->getId() ?>" class="generic-tabs__panel omo-project-detail__tab-panel" data-generic-tab-panel data-omo-project-detail-events-panel data-omo-project-detail-events-url="<?= omoApiEscape($eventsUrl) ?>" data-omo-project-detail-events-loaded="0" hidden>
                <div class="omo-project-detail__tab-content omo-project-detail__events-content generic-drawer-content" data-omo-project-detail-events-content></div>
            </div>
        </div>
    </div>
</div>
