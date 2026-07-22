<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\Holon;
use dbObject\Project;

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$currentHolonId = isset($_GET['cid']) && is_numeric($_GET['cid']) ? (int)$_GET['cid'] : 0;
$projectId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$context = omoProjectsResolveContext($organizationId, $currentHolonId);

if (empty($context['status'])) {
    http_response_code(403);
    echo '<div class="omo-empty-state">' . omoApiEscape(omoProjectsT('projects.error.forbidden')) . '</div>';
    exit;
}

$isEdit = $projectId > 0;
$project = new Project();
if ($isEdit) {
    if (
        !$project->load($projectId)
        || (int)$project->get('IDorganization') !== $organizationId
        || (int)$project->get('active') !== 1
        || !omoProjectsCanManageProject($project, $context)
    ) {
        http_response_code(403);
        echo '<div class="omo-empty-state">' . omoApiEscape(omoProjectsT('projects.error.forbidden')) . '</div>';
        exit;
    }
} else {
    if (!omoProjectsCanCreateContext($context)) {
        http_response_code(403);
        echo '<div class="omo-empty-state">' . omoApiEscape(omoProjectsT('projects.error.forbidden')) . '</div>';
        exit;
    }
    $project->set('IDorganization', $organizationId);
    $project->set('IDholon', $context['currentHolon'] instanceof Holon ? (int)$context['currentHolon']->getId() : null);
    $project->set('IDuser', function_exists('commonGetCurrentUserId') ? (int)commonGetCurrentUserId() : null);
    $requestedStatus = isset($_GET['status']) ? (string)$_GET['status'] : Project::STATUS_SOMEDAY;
    $project->set('status', Project::normalizeStatus($requestedStatus));
    $project->set('capture_mode', Project::CAPTURE_MULTIPLE_DOCUMENTS);
    $project->set('project_size', Project::SIZE_M);
}

$afterTableHtml = '<input type="hidden" name="project_action" value="save_project">'
    . '<input type="hidden" name="oid" value="' . (int)$organizationId . '">'
    . '<input type="hidden" name="cid" value="' . (int)$currentHolonId . '">'
    . ($isEdit ? '<input type="hidden" name="id" value="' . (int)$projectId . '">' : '');

$params = [
    'fields' => [
        'title',
        'description',
        'status',
        'planned_start_date',
        'planned_end_date',
        'priority',
        'importance',
        'project_size',
        'IDuser',
        'IDproject_parent',
        'capture_mode',
    ],
    'buttons' => false,
    'includeComponentAssets' => false,
    'action' => '/omo/api/projects/action.php',
    'success' => 'omoProjectsAfterSave()',
    'afterTableHtml' => $afterTableHtml,
];
?>
<div class="omo-project-form">
    <div
        hidden
        data-omo-subdrawer-header
        data-omo-subdrawer-title="<?= omoApiEscape(omoProjectsT($isEdit ? 'projects.form.edit_title' : 'projects.form.title')) ?>"
        data-omo-subdrawer-description="<?= omoApiEscape(omoProjectsT($isEdit ? 'projects.form.edit_description' : 'projects.form.description')) ?>"
    >
        <button
            type="submit"
            form="formulaire-edit"
            class="generic-action-button generic-action-button--main"
            data-omo-subdrawer-action
        ><?= omoApiEscape(omoProjectsT($isEdit ? 'projects.form.edit_submit' : 'projects.form.submit')) ?></button>
        <button
            type="button"
            form="formulaire-edit"
            class="generic-action-button generic-action-button--secondary"
            data-omo-subdrawer-action
            data-omo-projects-cancel-create
        ><?= omoApiEscape(omoProjectsT('projects.action.cancel')) ?></button>
    </div>
    <?php $project->display('adminEdit.php', $params); ?>
</div>
