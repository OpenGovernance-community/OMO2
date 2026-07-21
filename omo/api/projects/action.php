<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\Project;
use dbObject\ArrayProject;
use dbObject\Holon;
use dbObject\PropertyFormat;
use dbObject\UserOrganization;

header('Content-Type: text/plain; charset=UTF-8');

function omoProjectsActionRespond($success, $message = '', array $extra = [], $statusCode = 200)
{
    http_response_code((int)$statusCode);
    echo json_encode(array_merge([
        'success' => (bool)$success,
        'status' => (bool)$success,
        'message' => (string)$message,
    ], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function omoProjectsGetProjectTree(Project $project, $organizationId, $includeInactive = false)
{
    $projects = new ArrayProject();
    $projects->loadForOrganization((int)$organizationId, !$includeInactive);
    $childrenByParent = [];
    $projectsById = [(int)$project->getId() => $project];
    foreach ($projects as $candidate) {
        if (!($candidate instanceof Project) || (int)$candidate->getId() <= 0) {
            continue;
        }
        $candidateId = (int)$candidate->getId();
        $projectsById[$candidateId] = $candidate;
        $parentId = (int)$candidate->get('IDproject_parent');
        if ($parentId > 0) {
            $childrenByParent[$parentId][] = $candidate;
        }
    }

    $ordered = [];
    $visited = [];
    $collect = static function ($projectId) use (&$collect, &$ordered, &$visited, $childrenByParent, $projectsById) {
        $projectId = (int)$projectId;
        if ($projectId <= 0 || isset($visited[$projectId])) {
            return;
        }
        $visited[$projectId] = true;
        foreach ($childrenByParent[$projectId] ?? [] as $child) {
            if ($child instanceof Project) {
                $collect((int)$child->getId());
            }
        }
        if (isset($projectsById[$projectId]) && $projectsById[$projectId] instanceof Project) {
            $ordered[] = $projectsById[$projectId];
        }
    };
    $collect((int)$project->getId());
    return $ordered;
}

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    omoProjectsActionRespond(false, omoProjectsT('projects.error.method'), [], 405);
}

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_POST['oid'] ?? 0));
$currentHolonId = isset($_POST['cid']) && is_numeric($_POST['cid']) ? (int)$_POST['cid'] : 0;
$currentUserId = function_exists('commonGetCurrentUserId') ? (int)commonGetCurrentUserId() : 0;
$context = omoProjectsResolveContext($organizationId, $currentHolonId);
if (empty($context['status'])) {
    omoProjectsActionRespond(false, (string)$context['message'], [], 403);
}
$action = trim((string)($_POST['project_action'] ?? $_POST['action'] ?? ''));
$projectId = isset($_POST['id']) && is_numeric($_POST['id']) ? (int)$_POST['id'] : 0;
$existingProject = null;
if ($projectId > 0) {
    $existingProject = new Project();
    if (
        !$existingProject->load($projectId)
        || (int)$existingProject->get('IDorganization') !== $organizationId
        || (int)$existingProject->get('active') !== 1
    ) {
        omoProjectsActionRespond(false, omoProjectsT('projects.error.not_found'), [], 404);
    }
}

$canManageContext = omoProjectsCanManageContext($context);
if (
    $currentUserId <= 0
    || ($existingProject instanceof Project
        ? !omoProjectsCanManageProject($existingProject, $context)
        : !$canManageContext)
) {
    omoProjectsActionRespond(false, omoProjectsT('projects.error.forbidden'), [], 403);
}

if ($action === 'update_status') {
    $status = trim((string)($_POST['status'] ?? ''));
    if ($projectId <= 0 || !in_array($status, Project::statuses(), true)) {
        omoProjectsActionRespond(false, omoProjectsT('projects.error.status'), [], 422);
    }

    $project = $existingProject instanceof Project ? $existingProject : new Project();
    if (!($project instanceof Project) || $projectId <= 0) {
        omoProjectsActionRespond(false, omoProjectsT('projects.error.not_found'), [], 404);
    }
    if (!omoProjectsCanManageProject($project, $context)) {
        omoProjectsActionRespond(false, omoProjectsT('projects.error.forbidden'), [], 403);
    }

    $project->set('status', $status);
    $saveResult = $project->save();
    if (!is_array($saveResult) || empty($saveResult['status'])) {
        omoProjectsActionRespond(false, omoProjectsT('projects.error.save'), [], 422);
    }

    omoProjectsActionRespond(true, omoProjectsT('projects.success.status'), [
        'id' => (int)$project->getId(),
        'status' => Project::normalizeStatus($project->get('status')),
    ]);
}

if ($action === 'move_project') {
    if (!($existingProject instanceof Project)) {
        omoProjectsActionRespond(false, omoProjectsT('projects.error.not_found'), [], 404);
    }
    $targetHolonId = isset($_POST['IDholon']) && is_numeric($_POST['IDholon']) ? (int)$_POST['IDholon'] : 0;
    $targetHolon = new Holon();
    $rootHolon = $context['rootHolon'] ?? null;
    if (
        $targetHolonId <= 0
        || !$targetHolon->load($targetHolonId)
        || !($rootHolon instanceof Holon)
        || !$targetHolon->isDescendantOf((int)$rootHolon->getId(), true)
        || !$targetHolon->canEdit()
    ) {
        omoProjectsActionRespond(false, omoProjectsT('projects.error.holon'), [], 422);
    }

    $existingProject->set('IDholon', $targetHolonId);
    $saveResult = $existingProject->save();
    if (!is_array($saveResult) || empty($saveResult['status'])) {
        omoProjectsActionRespond(false, omoProjectsT('projects.error.save'), [], 422);
    }

    omoProjectsActionRespond(true, omoProjectsT('projects.success.save'), [
        'id' => (int)$existingProject->getId(),
    ]);
}

if ($action === 'archive_project') {
    if (!($existingProject instanceof Project)) {
        omoProjectsActionRespond(false, omoProjectsT('projects.error.not_found'), [], 404);
    }
    $projectTree = omoProjectsGetProjectTree($existingProject, $organizationId);
    foreach ($projectTree as $treeProject) {
        if (!omoProjectsCanManageProject($treeProject, $context)) {
            omoProjectsActionRespond(false, omoProjectsT('projects.error.forbidden'), [], 403);
        }
    }
    foreach ($projectTree as $treeProject) {
        $treeProject->set('active', 0);
        $saveResult = $treeProject->save();
        if (!is_array($saveResult) || empty($saveResult['status'])) {
            omoProjectsActionRespond(false, omoProjectsT('projects.error.save'), [], 422);
        }
    }

    omoProjectsActionRespond(true, omoProjectsT('projects.success.save'), [
        'id' => (int)$existingProject->getId(),
        'affectedCount' => count($projectTree),
    ]);
}

if ($action === 'delete_project') {
    if (!($existingProject instanceof Project)) {
        omoProjectsActionRespond(false, omoProjectsT('projects.error.not_found'), [], 404);
    }
    $projectTree = omoProjectsGetProjectTree($existingProject, $organizationId, true);
    foreach ($projectTree as $treeProject) {
        if (!omoProjectsCanManageProject($treeProject, $context)) {
            omoProjectsActionRespond(false, omoProjectsT('projects.error.forbidden'), [], 403);
        }
    }
    foreach ($projectTree as $treeProject) {
        if (!$treeProject->delete()) {
            omoProjectsActionRespond(false, omoProjectsT('projects.error.save'), [], 422);
        }
    }

    omoProjectsActionRespond(true, omoProjectsT('projects.success.save'), [
        'id' => $projectId,
        'affectedCount' => count($projectTree),
    ]);
}

if ($action !== 'save_project') {
    omoProjectsActionRespond(false, omoProjectsT('projects.error.action'), [], 422);
}

$title = trim((string)($_POST['title'] ?? ''));
if ($title === '') {
    omoProjectsActionRespond(false, omoProjectsT('projects.error.title'), [], 422);
}

$project = $existingProject instanceof Project ? $existingProject : new Project();
if ($projectId <= 0) {
    $project->set('IDorganization', $organizationId);
    $project->set('IDholon', $context['currentHolon'] instanceof \dbObject\Holon ? (int)$context['currentHolon']->getId() : null);
}
$project->set('title', mb_substr($title, 0, 255, 'UTF-8'));
$project->set('description', PropertyFormat::sanitizeHtml((string)($_POST['description'] ?? '')));
$project->set('status', Project::normalizeStatus($_POST['status'] ?? Project::STATUS_SOMEDAY));
$project->set('capture_mode', Project::normalizeCaptureMode($_POST['capture_mode'] ?? Project::CAPTURE_MULTIPLE_DOCUMENTS));
$project->set('project_size', Project::normalizeSize($_POST['project_size'] ?? Project::SIZE_M));
$project->set('priority', Project::normalizeLevel($_POST['priority'] ?? null));
$project->set('importance', Project::normalizeLevel($_POST['importance'] ?? null));

$parseDate = static function ($value) {
    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }

    $date = \DateTime::createFromFormat('!Y-m-d', $value);
    $errors = \DateTime::getLastErrors();
    return $date instanceof \DateTime && ($errors === false || ((int)$errors['warning_count'] === 0 && (int)$errors['error_count'] === 0))
        ? $date
        : null;
};

$plannedStartDate = $parseDate($_POST['planned_start_date'] ?? null);
$plannedEndDate = $parseDate($_POST['planned_end_date'] ?? null);
$project->set('planned_start_date', $plannedStartDate);
$project->set('planned_end_date', $plannedEndDate);

$responsibleId = isset($_POST['IDuser']) && is_numeric($_POST['IDuser']) ? (int)$_POST['IDuser'] : 0;
if ($responsibleId > 0) {
    $membership = new UserOrganization();
    if (!$membership->load([
        ['IDuser', $responsibleId],
        ['IDorganization', $organizationId],
    ]) || !(bool)$membership->get('active')) {
        $responsibleId = 0;
    }
}
if ($responsibleId > 0 && (string)($_POST['enforce_holon_member'] ?? '') === '1') {
    $projectHolon = $context['currentHolon'] ?? null;
    $rootHolon = $context['rootHolon'] ?? null;
    $isOrganizationHolon = $projectHolon instanceof Holon
        && $rootHolon instanceof Holon
        && (int)$projectHolon->getId() === (int)$rootHolon->getId();
    if ($projectHolon instanceof Holon && !$isOrganizationHolon) {
        $holonMembership = new \dbObject\UserHolon();
        if (!$holonMembership->load([
            ['IDuser', $responsibleId],
            ['IDholon', (int)$projectHolon->getId()],
        ]) || !(bool)$holonMembership->get('active')) {
            $responsibleId = 0;
        }
    }
}
$project->set('IDuser', $responsibleId > 0 ? $responsibleId : null);

$parentId = isset($_POST['IDproject_parent']) && is_numeric($_POST['IDproject_parent']) ? (int)$_POST['IDproject_parent'] : 0;
if ($parentId > 0) {
    $parent = new Project();
    if (
        !$parent->load($parentId)
        || (int)$parent->get('IDorganization') !== $organizationId
        || !$project->canUseAsParent($parent)
    ) {
        $parentId = 0;
    }
}
$project->set('IDproject_parent', $parentId > 0 ? $parentId : null);
$project->set('active', 1);

$saveResult = $project->save();
if (!is_array($saveResult) || empty($saveResult['status']) || (int)$project->getId() <= 0) {
    omoProjectsActionRespond(false, omoProjectsT('projects.error.save'), [], 422);
}

omoProjectsActionRespond(true, omoProjectsT('projects.success.save'), [
    'id' => (int)$project->getId(),
]);
