<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\ArrayProject;
use dbObject\Holon;
use dbObject\Project;

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$projectId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$renderForProjectEmbed = trim((string)($_GET['embed'] ?? '')) === 'pv';
$renderJson = $renderForProjectEmbed && trim((string)($_GET['format'] ?? '')) === 'json';
$renderInline = $renderForProjectEmbed && trim((string)($_GET['inline'] ?? '')) === '1';

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
$listTag = $renderForProjectEmbed && ($renderJson || $renderInline) ? 'span' : 'ul';
$childTag = $renderForProjectEmbed && ($renderJson || $renderInline) ? 'span' : 'li';
$html = '<' . $listTag . ' class="' . ($renderForProjectEmbed ? 'omo-project-embed__children-list' : 'section-project-reference__children-list') . '">';
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
    if ($renderForProjectEmbed) {
        $hasDirectChildren = !empty($childrenByParent[$childId]);
        $contextLabel = $childHolon instanceof Holon ? trim((string)$childHolon->getDisplayName()) : '';
        $responsible = $child->getResponsible();
        $responsibleLabel = is_object($responsible) ? trim((string)omoProjectsGetUserLabel($responsible)) : '';
        $metaParts = array_filter([$contextLabel, $responsibleLabel]);
        $html .= '<' . $childTag . ' class="omo-project-embed__child" data-omo-project-node data-omo-project-id="' . $childId . '">';
        $html .= '<span class="omo-project-embed__child-head"><span class="omo-project-embed__child-copy"><a class="omo-project-embed__child-title" href="#projects-d' . $childId . '">' . omoApiEscape($title) . '</a>';
        if (count($metaParts) > 0) {
            $html .= '<span class="omo-project-embed__child-meta">' . omoApiEscape(implode(' - ', $metaParts)) . '</span>';
        }
        $html .= '</span>';
        if ($hasDirectChildren) {
            $html .= '<button type="button" class="omo-project-embed__child-toggle" data-omo-project-embed-toggle aria-expanded="false" aria-label="Afficher les sous-projets de ' . omoApiEscape($title) . '">';
            $html .= omoProjectsRenderStatusBar($summary, 'omo-project-embed__child-status-bar', ($renderJson || $renderInline) ? 'span' : 'div');
            $html .= '</button>';
        } else {
            $html .= omoProjectsRenderStatusBar($summary, 'omo-project-embed__child-status-bar', ($renderJson || $renderInline) ? 'span' : 'div');
        }
        $html .= '</span>';
        if ($hasDirectChildren) {
            $html .= '<' . ($renderInline ? 'span' : 'div') . ' class="omo-project-embed__children" data-omo-project-embed-children hidden></' . ($renderInline ? 'span' : 'div') . '>';
        }
        $html .= '</' . $childTag . '>';
        continue;
    }

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
$html .= '</' . $listTag . '>';

if ($renderJson) {
    $parentSummary = omoProjectsBuildStatusBar($project, $childrenByParent, $statusSummaryMemo);
    $projectStatus = Project::normalizeStatus($project->get('status'));
    $statusCatalog = Project::getStatusCatalog();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => true,
        'hasChildren' => $renderedChildren > 0,
        'statusBarHtml' => omoProjectsRenderStatusBar($parentSummary, 'omo-pv-editor__project-review-status-bar', 'span'),
        'status' => $projectStatus,
        'statusLabel' => (string)($statusCatalog[$projectStatus]['label'] ?? $projectStatus),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

echo $renderedChildren > 0 ? $html : '';
