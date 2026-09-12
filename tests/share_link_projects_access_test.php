<?php

declare(strict_types=1);

function shareLinkProjectsAccessAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$root = dirname(__DIR__);
$projectsShared = file_get_contents($root . '/omo/api/projects/shared.php');
$sidebar = file_get_contents($root . '/omo/api/getSidebar.php');
$structureData = file_get_contents($root . '/omo/api/getStructureData.php');
$getOrg = file_get_contents($root . '/omo/api/getOrg.php');
$arrayProject = file_get_contents($root . '/class/dbobject/arrayproject.class.php');

shareLinkProjectsAccessAssert(
    is_string($projectsShared)
        && str_contains($projectsShared, 'function omoProjectsIsStructuralShareRequest')
        && str_contains($projectsShared, 'return !omoProjectsIsStructuralShareRequest();'),
    'Projects must be unavailable to structural share links.'
);
shareLinkProjectsAccessAssert(
    is_string($projectsShared)
        && str_contains($projectsShared, 'if (empty($context[\'status\']) || !omoProjectsAreAvailableForCurrentRequest())'),
    'Project visibility must reject an unavailable share context.'
);
shareLinkProjectsAccessAssert(
    is_string($sidebar)
        && str_contains($sidebar, "\$isStructuralShare && \$application->getRouteHash() === 'projects'"),
    'Projects must be hidden from a structural share sidebar.'
);
shareLinkProjectsAccessAssert(
    is_string($structureData)
        && str_contains($structureData, 'const OMO_STRUCTURE_CACHE_VERSION = 4;')
        && str_contains($structureData, '$shareLink instanceof \\dbObject\\HolonShareLink')
        && str_contains($structureData, '? ArrayProject::fetchTitlesForProjectIds($organizationId, array_keys($projectIds))'),
    'A structural share must receive titles only for its displayed project lists.'
);
shareLinkProjectsAccessAssert(
    is_string($getOrg)
        && str_contains($getOrg, "'titleOnly' => true")
        && str_contains($getOrg, "return '<li' . \$className . '>' . omoApiEscape(\$title) . '</li>';"),
    'A structural share project list must render a title without a project link.'
);
shareLinkProjectsAccessAssert(
    is_string($arrayProject)
        && str_contains($arrayProject, 'function fetchTitlesForProjectIds')
        && str_contains($arrayProject, "['field' => 'id', 'op' => 'in', 'value' => \$projectIds]"),
    'Only referenced project titles must be loaded for a structural share.'
);

echo "share_link_projects_access_test: OK\n";
