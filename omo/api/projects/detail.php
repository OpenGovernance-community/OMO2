<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\Holon;
use dbObject\ArrayProject;
use dbObject\Project;
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
    || (int)$project->get('active') !== 1
) {
    http_response_code(404);
    echo '<div class="omo-empty-state">' . omoApiEscape(omoProjectsT('projects.error.not_found')) . '</div>';
    exit;
}

$organization = $context['organization'];
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
$canEdit = omoProjectsCanManageProject($project, $context);
$currentUserId = function_exists('commonGetCurrentUserId') ? (int)commonGetCurrentUserId() : 0;
$canCreateSubproject = $projectHolon instanceof Holon
    ? $projectHolon->isAllowed('CAN_CREATE_PROJECT', true, $currentUserId)
    : omoProjectsCanCreateContext($context);
$editUrl = '/omo/api/projects/create.php?oid=' . rawurlencode((string)$organizationId) . '&id=' . rawurlencode((string)$projectId);
if ((int)($_GET['cid'] ?? 0) > 0) {
    $editUrl .= '&cid=' . rawurlencode((string)(int)$_GET['cid']);
}
$description = PropertyFormat::sanitizeHtml((string)$project->get('description'));
$status = Project::normalizeStatus($project->get('status'));
$projectSize = Project::normalizeSize($project->get('project_size'));
$contextLabel = $projectHolon instanceof Holon
    ? trim((string)$projectHolon->getDisplayName())
    : trim((string)$organization->get('name'));
$startDate = omoProjectsFormatDate($project->get('planned_start_date'));
$endDate = omoProjectsFormatDate($project->get('planned_end_date'));
$createdAt = $project->get('created_at');
$allProjects = new ArrayProject();
$allProjects->loadForOrganization($organizationId);
$projectsByParent = [];
$attachableProjects = [];
foreach ($allProjects as $allProject) {
    if (!($allProject instanceof Project) || (int)$allProject->getId() <= 0) {
        continue;
    }
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
$newSubprojectUrl = '/omo/api/projects/create.php?oid=' . rawurlencode((string)$organizationId)
    . '&parent_id=' . rawurlencode((string)$projectId);
if ($projectHolon instanceof Holon) {
    $newSubprojectUrl .= '&cid=' . rawurlencode((string)$projectHolon->getId());
}
$hasSubprojectActions = $canCreateSubproject || count($attachableProjects) > 0;
$subprojects = $projectsByParent[(int)$project->getId()] ?? [];
$statusSummaryMemo = [];
$projectStatusSummary = omoProjectsBuildStatusBar($project, $projectsByParent, $statusSummaryMemo);
$detailStatusOptions = [
    Project::STATUS_READY,
    Project::STATUS_IN_PROGRESS,
    Project::STATUS_BLOCKED,
    Project::STATUS_REVIEW,
    Project::STATUS_DONE,
    Project::STATUS_SOMEDAY,
];
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
    <section class="generic-hero-panel accent omo-project-detail__hero">
        <span class="generic-card-title generic-card-title--eyebrow"><?= omoApiEscape(omoProjectsT('projects.detail.badge')) ?></span>
        <h2 class="generic-card-title generic-card-title--large"><?= omoApiEscape((string)$project->get('title')) ?></h2>
        <div class="omo-project-detail__hero-meta">
            <span class="omo-project-status omo-project-status--<?= omoApiEscape($status) ?>"><?= omoApiEscape(omoProjectsStatusLabel($status)) ?></span>
            <span><?= omoApiEscape($contextLabel) ?></span>
        </div>
    </section>

    <section class="generic-section omo-project-detail__section">
        <h3 class="generic-card-title generic-card-title--big"><?= omoApiEscape(omoProjectsT('projects.detail.description')) ?></h3>
        <?php if ($description !== ''): ?>
            <div class="omo-simple-html-render omo-project-detail__description"><?= $description ?></div>
        <?php else: ?>
            <p class="omo-project-detail__muted"><?= omoApiEscape(omoProjectsT('projects.detail.empty_description')) ?></p>
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
                    $subprojectCanEdit = omoProjectsCanManageProject($subproject, $context);
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
                                <span class="omo-project-detail__subproject-size"><?= omoApiEscape(Project::normalizeSize($subproject->get('project_size'))) ?></span>
                            </div>
                        </div>
                        <?php if ($subprojectIsProject): ?>
                            <?= omoProjectsRenderStatusBar($subprojectSummary, 'omo-project-detail__subproject-bar') ?>
                        <?php elseif ($subprojectCanEdit): ?>
                            <select class="generic-form-control omo-project-detail__subproject-status-select" data-omo-project-detail-status-select data-project-id="<?= (int)$subproject->getId() ?>" data-previous-status="<?= omoApiEscape($subprojectStatus) ?>" aria-label="<?= omoApiEscape(omoProjectsT('projects.status_move')) ?>">
                                <?php foreach ($detailStatusOptions as $statusOption): ?>
                                    <option value="<?= omoApiEscape($statusOption) ?>"<?= $statusOption === $subprojectStatus ? ' selected' : '' ?>><?= omoApiEscape(omoProjectsStatusLabel($statusOption)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="omo-project-detail__muted"><?= omoApiEscape(omoProjectsT('projects.detail.subprojects_empty')) ?></p>
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
                <div><dt><?= omoApiEscape(omoProjectsT('projects.detail.priority')) ?></dt><dd><?= $project->get('priority') !== null && $project->get('priority') !== '' ? omoApiEscape((string)$project->get('priority') . '/5') : omoApiEscape(omoProjectsT('projects.detail.none')) ?></dd></div>
                <div><dt><?= omoApiEscape(omoProjectsT('projects.detail.importance')) ?></dt><dd><?= $project->get('importance') !== null && $project->get('importance') !== '' ? omoApiEscape((string)$project->get('importance') . '/5') : omoApiEscape(omoProjectsT('projects.detail.none')) ?></dd></div>
                <div><dt><?= omoApiEscape(omoProjectsT('projects.detail.size')) ?></dt><dd><?= omoApiEscape($projectSize) ?></dd></div>
            </dl>
        </section>
        <section class="generic-soft-panel omo-project-detail__section">
            <h3 class="generic-card-title generic-card-title--big"><?= omoApiEscape(omoProjectsT('projects.detail.parent')) ?></h3>
            <p class="omo-project-detail__value"><?= omoApiEscape($parent instanceof Project ? (string)$parent->get('title') : omoProjectsT('projects.detail.none')) ?></p>
            <p class="omo-project-detail__created"><?= omoApiEscape(omoProjectsT('projects.detail.created')) ?> <?= omoApiEscape(omoProjectsFormatDate($createdAt)) ?></p>
        </section>
    </div>
</div>
