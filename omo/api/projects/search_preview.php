<?php
require_once __DIR__ . '/shared.php';
return static function (\dbObject\Project $project, \dbObject\Organization $organization, int $currentHolonId): array {
    $organizationId = (int)$organization->getId();
    $context = omoProjectsResolveContext($organizationId, $currentHolonId);
    if (!omoProjectsCanViewProject($project, $context)) {
        throw new RuntimeException('Preview unavailable');
    }
    return [
        'title' => $project->get('title'),
        'fields' => [
            'context' => $project->getHolon()?->getFullDisplayName(),
            'status' => \dbObject\Project::getOrganizationStatusLabel($organizationId, $project->get('status')),
            'responsible' => $project->getResponsible()?->getScopedDisplayName($organizationId),
            'start' => $project->get('planned_start_date'),
            'end' => $project->get('planned_end_date'),
        ],
        'sections' => [omoSearchPreviewSection('summary', $project->get('description'))],
    ];
};
