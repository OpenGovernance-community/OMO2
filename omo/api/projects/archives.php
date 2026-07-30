<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\ArrayProject;
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
    || (int)$project->get('active') !== 1
) {
    http_response_code(404);
    echo '<div class="omo-empty-state">' . omoApiEscape(omoProjectsT('projects.error.not_found')) . '</div>';
    exit;
}

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

$archivedProjects = new ArrayProject();
$archivedProjects->loadForParent($projectId, false);
$hasArchivedProject = false;
?>
<div class="omo-project-archives__list">
    <?php foreach ($archivedProjects as $archivedProject): ?>
        <?php if (!($archivedProject instanceof Project) || (int)$archivedProject->get('active') === 1): continue; endif; ?>
        <?php $hasArchivedProject = true; ?>
        <div class="generic-soft-panel omo-project-archives__item">
            <strong><?= omoApiEscape((string)$archivedProject->get('title')) ?></strong>
            <span><?= omoApiEscape(omoProjectsStatusLabel($archivedProject->get('status'))) ?><?php if ($archivedProject->get('planned_start_date') instanceof DateTimeInterface): ?> · <?= omoApiEscape($archivedProject->get('planned_start_date')->format('d.m.Y')) ?><?php endif; ?></span>
        </div>
    <?php endforeach; ?>
</div>
<?php if (!$hasArchivedProject): ?>
    <p class="omo-project-detail__muted generic-description generic-description--small"><?= omoApiEscape(omoProjectsT('projects.detail.archives.empty')) ?></p>
<?php endif; ?>
