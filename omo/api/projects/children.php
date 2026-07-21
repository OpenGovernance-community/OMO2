<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\ArrayProject;
use dbObject\Holon;
use dbObject\Project;

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$projectId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

if ($organizationId <= 0 || $projectId <= 0) {
    http_response_code(404);
    exit;
}

$context = omoProjectsResolveContext($organizationId);
if (empty($context['status'])) {
    http_response_code(403);
    exit;
}

$project = new Project();
if (
    !$project->load($projectId)
    || (int)$project->get('IDorganization') !== $organizationId
    || (int)$project->get('active') !== 1
) {
    http_response_code(404);
    exit;
}

$rootHolon = $context['rootHolon'];
$projectHolon = $project->getHolon();
if (
    $projectHolon instanceof Holon
    && (
        !($rootHolon instanceof Holon)
        || !$projectHolon->isDescendantOf((int)$rootHolon->getId(), true)
        || !$projectHolon->canViewDetail()
    )
) {
    http_response_code(404);
    exit;
}

$projects = new ArrayProject();
$projects->loadForOrganization($organizationId);
$childrenByParent = array();
foreach ($projects as $candidate) {
    if (!($candidate instanceof Project) || (int)$candidate->getId() <= 0) {
        continue;
    }

    $parentId = (int)$candidate->get('IDproject_parent');
    if ($parentId > 0) {
        $childrenByParent[$parentId][] = $candidate;
    }
}

$children = $childrenByParent[$projectId] ?? array();
$statusSummaryMemo = array();
$html = '<ul class="section-project-reference__children-list">';
$renderedChildren = 0;
foreach ($children as $child) {
    $childHolon = $child->getHolon();
    if (
        $childHolon instanceof Holon
        && (
            !($rootHolon instanceof Holon)
            || !$childHolon->isDescendantOf((int)$rootHolon->getId(), true)
            || !$childHolon->canViewDetail()
        )
    ) {
        continue;
    }

    $childId = (int)$child->getId();
    $title = trim((string)$child->get('title'));
    if ($childId <= 0 || $title === '') {
        continue;
    }

    $priority = Project::normalizeLevel($child->get('priority'));
    $summary = omoProjectsBuildStatusBar($child, $childrenByParent, $statusSummaryMemo, true);
    $renderedChildren++;
    $html .= '<li class="section-project-reference__child">';
    $html .= '<div class="section-project-reference__child-head">';
    $html .= '<a class="section-project-reference__child-title" href="#projects-d' . $childId . '">' . omoApiEscape($title) . '</a>';
    if ($priority !== null) {
        $html .= '<span class="section-project-reference__priority section-project-reference__priority--p' . (int)$priority . '">P' . (int)$priority . '</span>';
    }
    $html .= '</div>';
    $html .= omoProjectsRenderStatusBar($summary, 'section-project-reference__status-bar');
    $html .= '</li>';
}
$html .= '</ul>';

echo $renderedChildren > 0 ? $html : '';
