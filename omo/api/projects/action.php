<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\Project;
use dbObject\ArrayProject;
use dbObject\Holon;
use dbObject\Document;
use dbObject\ArrayProjectDocument;
use dbObject\ProjectDocument;
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

function omoProjectsSaveFailureMessage($saveResult)
{
    $errorCode = is_array($saveResult) ? (string)($saveResult['errorCode'] ?? '') : '';
    if ($errorCode === Project::SAVE_ERROR_PARENT_SOMEDAY) {
        return omoProjectsT('projects.error.parent_someday');
    }
    if ($errorCode === Project::SAVE_ERROR_PARENT_END_DATE) {
        return omoProjectsT('projects.error.parent_end_date');
    }

    return omoProjectsT('projects.error.save');
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
$bulkProjectIds = [];
foreach ((array)($_POST['project_ids'] ?? []) as $bulkProjectId) {
    if (is_numeric($bulkProjectId) && (int)$bulkProjectId > 0) {
        $bulkProjectIds[(int)$bulkProjectId] = true;
    }
}
$bulkProjectIds = array_keys($bulkProjectIds);

if (in_array($action, ['bulk_archive_projects', 'bulk_delete_projects'], true)) {
    if ($currentUserId <= 0 || count($bulkProjectIds) === 0) {
        omoProjectsActionRespond(false, omoProjectsT('projects.error.action'), [], 422);
    }

    $includeInactive = $action === 'bulk_delete_projects';
    $projectsToProcess = [];
    foreach ($bulkProjectIds as $bulkProjectId) {
        $bulkProject = new Project();
        if (
            !$bulkProject->load($bulkProjectId)
            || (int)$bulkProject->get('IDorganization') !== $organizationId
            || (int)$bulkProject->get('active') !== 1
        ) {
            omoProjectsActionRespond(false, omoProjectsT('projects.error.not_found'), [], 404);
        }

        foreach (omoProjectsGetProjectTree($bulkProject, $organizationId, $includeInactive) as $treeProject) {
            if (!($treeProject instanceof Project)) {
                continue;
            }
            $treeProjectId = (int)$treeProject->getId();
            if ($treeProjectId > 0) {
                $projectsToProcess[$treeProjectId] = $treeProject;
            }
        }
    }

    foreach ($projectsToProcess as $treeProject) {
        if (
            !omoProjectsCanManageProject($treeProject, $context)
            || ($action === 'bulk_delete_projects' && !omoProjectsCanDeleteProject($treeProject, $context))
        ) {
            omoProjectsActionRespond(false, omoProjectsT('projects.error.forbidden'), [], 403);
        }
    }

    foreach ($projectsToProcess as $treeProject) {
        if ($action === 'bulk_archive_projects') {
            $treeProject->set('active', 0);
            $saveResult = $treeProject->save();
            if (!is_array($saveResult) || empty($saveResult['status'])) {
                omoProjectsActionRespond(false, omoProjectsT('projects.error.save'), [], 422);
            }
        } elseif (!$treeProject->delete()) {
            omoProjectsActionRespond(false, omoProjectsT('projects.error.save'), [], 422);
        }
    }

    omoProjectsActionRespond(true, omoProjectsT('projects.success.save'), [
        'selectedCount' => count($bulkProjectIds),
        'affectedCount' => count($projectsToProcess),
    ]);
}

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
$canCreateProject = omoProjectsCanCreateContext($context);
if (
    $currentUserId <= 0
    || ($existingProject instanceof Project
        ? (in_array($action, ['attach_document', 'remove_document'], true)
            ? !omoProjectsCanCreateDocument($existingProject, $currentUserId)
            : !omoProjectsCanManageProject($existingProject, $context))
        : !$canCreateProject)
) {
    omoProjectsActionRespond(false, omoProjectsT('projects.error.forbidden'), [], 403);
}

if ($action === 'attach_document') {
    if (!($existingProject instanceof Project)) {
        omoProjectsActionRespond(false, omoProjectsT('projects.error.not_found'), [], 404);
    }

    $documentId = isset($_POST['document_id']) && is_numeric($_POST['document_id'])
        ? (int)$_POST['document_id']
        : 0;
    $projectHolon = $existingProject->getHolon();
    $document = new Document();
    if (
        $documentId <= 0
        || !($projectHolon instanceof Holon)
        || !$document->load($documentId)
        || (int)$document->get('IDorganization') !== $organizationId
        || (int)$document->get('active') !== 1
        || !$document->canBeEmbedded()
        || !(
            $document->canViewInOrganizationContext($organizationId, (int)$document->get('IDholon'), $currentUserId)
            || $document->canViewDirectlyInOrganization($organizationId)
        )
    ) {
        omoProjectsActionRespond(false, omoProjectsT('projects.error.forbidden'), [], 403);
    }

    $projectDocument = new ProjectDocument();
    if (!$projectDocument->load([['IDproject', $projectId], ['IDdocument', $documentId]])) {
        $projectDocument->set('IDproject', $projectId);
        $projectDocument->set('IDdocument', $documentId);
        $saveResult = $projectDocument->save();
        if (!is_array($saveResult) || empty($saveResult['status'])) {
            omoProjectsActionRespond(false, omoProjectsT('projects.error.save'), [], 422);
        }
    }

    omoProjectsActionRespond(true, omoProjectsT('projects.success.save'), [
        'projectId' => $projectId,
        'documentId' => $documentId,
    ]);
}

if ($action === 'remove_document') {
    if (!($existingProject instanceof Project)) {
        omoProjectsActionRespond(false, omoProjectsT('projects.error.not_found'), [], 404);
    }

    $documentId = isset($_POST['document_id']) && is_numeric($_POST['document_id'])
        ? (int)$_POST['document_id']
        : 0;
    $document = new Document();
    $projectDocument = new ProjectDocument();
    if (
        $documentId <= 0
        || !$document->load($documentId)
        || (int)$document->get('IDorganization') !== $organizationId
        || !$projectDocument->load([['IDproject', $projectId], ['IDdocument', $documentId]])
    ) {
        omoProjectsActionRespond(false, omoProjectsT('projects.error.not_found'), [], 404);
    }

    $documentProjects = new ArrayProjectDocument();
    $documentProjects->loadForDocument($documentId);
    $hasOtherProject = false;
    foreach ($documentProjects as $documentProject) {
        if (
            $documentProject instanceof ProjectDocument
            && (int)$documentProject->get('IDproject') !== $projectId
        ) {
            $hasOtherProject = true;
            break;
        }
    }

    $shouldDetach = $hasOtherProject
        || $document->isVisibleInHolonWhenProjectDocument()
        || !$document->canDeleteDocument()
        || !$document->canManageLifecycle($organizationId, $currentUserId);
    if ($shouldDetach) {
        if (!$projectDocument->delete()) {
            omoProjectsActionRespond(false, omoProjectsT('projects.error.save'), [], 422);
        }
    } elseif (!$document->delete()) {
        omoProjectsActionRespond(false, omoProjectsT('projects.error.save'), [], 422);
    }

    omoProjectsActionRespond(true, omoProjectsT('projects.success.save'), [
        'projectId' => $projectId,
        'documentId' => $documentId,
        'mode' => $shouldDetach ? 'detach' : 'delete',
    ]);
}

if ($action === 'update_kanban_position') {
    if (!($existingProject instanceof Project) || $projectId <= 0) {
        omoProjectsActionRespond(false, omoProjectsT('projects.error.not_found'), [], 404);
    }

    $status = trim((string)($_POST['status'] ?? ''));
    if (!in_array($status, Project::statuses(), true)) {
        omoProjectsActionRespond(false, omoProjectsT('projects.error.status'), [], 422);
    }

    $groupKind = strtolower(trim((string)($_POST['group_kind'] ?? '')));
    if ($groupKind === 'holon') {
        $targetHolonId = isset($_POST['target_holon_id']) && is_numeric($_POST['target_holon_id'])
            ? (int)$_POST['target_holon_id']
            : 0;
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
    } elseif ($groupKind === 'priority') {
        $targetPriorityRaw = trim((string)($_POST['target_priority'] ?? ''));
        if ($targetPriorityRaw !== '' && (!is_numeric($targetPriorityRaw) || (int)$targetPriorityRaw < 0 || (int)$targetPriorityRaw > 5)) {
            omoProjectsActionRespond(false, omoProjectsT('projects.error.action'), [], 422);
        }
        $existingProject->set('priority', Project::normalizeLevel($targetPriorityRaw));
    } else {
        omoProjectsActionRespond(false, omoProjectsT('projects.error.action'), [], 422);
    }

    $existingProject->set('status', $status);
    $saveResult = $existingProject->save();
    if (!is_array($saveResult) || empty($saveResult['status'])) {
        omoProjectsActionRespond(false, omoProjectsSaveFailureMessage($saveResult), [], 422);
    }

    omoProjectsActionRespond(true, omoProjectsT('projects.success.save'), [
        'id' => (int)$existingProject->getId(),
        'status' => Project::normalizeStatus($existingProject->get('status')),
    ]);
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
        omoProjectsActionRespond(false, omoProjectsSaveFailureMessage($saveResult), [], 422);
    }

    omoProjectsActionRespond(true, omoProjectsT('projects.success.status'), [
        'id' => (int)$project->getId(),
        'status' => Project::normalizeStatus($project->get('status')),
    ]);
}

if ($action === 'attach_subproject') {
    if (
        !($existingProject instanceof Project)
        || Project::normalizeKind($existingProject->get('project_kind')) !== Project::KIND_STANDARD
    ) {
        omoProjectsActionRespond(false, omoProjectsT('projects.error.not_found'), [], 404);
    }

    $childId = isset($_POST['child_id']) && is_numeric($_POST['child_id']) ? (int)$_POST['child_id'] : 0;
    $childProject = new Project();
    if (
        $childId <= 0
        || !$childProject->load($childId)
        || (int)$childProject->get('IDorganization') !== $organizationId
        || (int)$childProject->get('active') !== 1
        || (int)$childProject->get('IDproject_parent') > 0
        || Project::normalizeKind($childProject->get('project_kind')) !== Project::KIND_STANDARD
        || !omoProjectsCanManageProject($childProject, $context)
        || !$childProject->canUseAsParent($existingProject)
    ) {
        omoProjectsActionRespond(false, omoProjectsT('projects.error.forbidden'), [], 403);
    }

    $childProject->set('IDproject_parent', (int)$existingProject->getId());
    $saveResult = $childProject->save();
    if (!is_array($saveResult) || empty($saveResult['status'])) {
        omoProjectsActionRespond(false, omoProjectsSaveFailureMessage($saveResult), [], 422);
    }

    omoProjectsActionRespond(true, omoProjectsT('projects.success.save'), [
        'id' => (int)$childProject->getId(),
        'parentId' => (int)$existingProject->getId(),
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
        if (
            !omoProjectsCanManageProject($treeProject, $context)
            || !omoProjectsCanDeleteProject($treeProject, $context)
        ) {
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
$targetHolonId = isset($_POST['IDholon']) && is_numeric($_POST['IDholon'])
    ? (int)$_POST['IDholon']
    : (int)($project->get('IDholon') ?: ($context['currentHolon'] instanceof Holon ? $context['currentHolon']->getId() : 0));
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
if ($projectId <= 0 && !$targetHolon->isAllowed('CAN_CREATE_PROJECT', false, $currentUserId)) {
    omoProjectsActionRespond(false, omoProjectsT('projects.error.forbidden'), [], 403);
}
if ($projectId <= 0) {
    $project->set('IDorganization', $organizationId);
}
$project->set('IDholon', $targetHolonId);
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
if ($plannedStartDate instanceof \DateTimeInterface && $plannedEndDate instanceof \DateTimeInterface && $plannedEndDate < $plannedStartDate) {
    omoProjectsActionRespond(false, omoProjectsT('projects.error.dates'), [], 422);
}
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
    $projectHolon = $targetHolon;
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
        || (int)$parent->get('active') !== 1
        || Project::normalizeKind($parent->get('project_kind')) !== Project::KIND_STANDARD
        || !$project->canUseAsParent($parent)
    ) {
        $parentId = 0;
    }
}
$project->set('IDproject_parent', $parentId > 0 ? $parentId : null);
$project->set('active', 1);

$saveResult = $project->save();
if (!is_array($saveResult) || empty($saveResult['status']) || (int)$project->getId() <= 0) {
    omoProjectsActionRespond(false, omoProjectsSaveFailureMessage($saveResult), [], 422);
}

omoProjectsActionRespond(true, omoProjectsT('projects.success.save'), [
    'id' => (int)$project->getId(),
]);
