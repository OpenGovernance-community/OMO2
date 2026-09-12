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

shareLinkProjectsAccessAssert(
    is_string($projectsShared)
        && str_contains($projectsShared, 'function omoProjectsAreAvailableForCurrentRequest')
        && str_contains($projectsShared, 'return !($shareLink instanceof \\dbObject\\HolonShareLink);'),
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
        && str_contains($structureData, 'const OMO_STRUCTURE_CACHE_VERSION = 3;')
        && str_contains($structureData, '$shareLink instanceof \\dbObject\\HolonShareLink')
        && str_contains($structureData, "? array()\n    : ArrayProject::fetchTitlesForOrganization"),
    'Project titles must not be sent in a structural share representation.'
);

echo "share_link_projects_access_test: OK\n";
