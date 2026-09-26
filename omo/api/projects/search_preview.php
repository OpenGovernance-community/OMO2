<?php
require_once __DIR__ . '/shared.php';
return static function (\dbObject\Project $project, \dbObject\Organization $organization, int $currentHolonId): array {
    $organizationId = (int)$organization->getId();
    $context = omoProjectsResolveContext($organizationId, $currentHolonId);
    if (!omoProjectsCanViewProject($project, $context)) {
        throw new RuntimeException('Preview unavailable');
    }
    $viewerContext = \dbObject\SearchJob::buildViewerContextFromGlobals($organizationId, $currentHolonId);
    $children = [];
    foreach ($project->getChildren() as $child) {
        if (!$organization->loadTopbarSearchPreviewObject('projects', (int)$child->getId(), $viewerContext)
            || !omoProjectsCanViewProject($child, $context)) { continue; }
        $children[] = [
            'title' => $child->get('title'), 'text' => $child->get('description'),
            'meta' => implode(' | ', array_filter([
                \dbObject\Project::getOrganizationStatusLabel($organizationId, $child->get('status')),
                omoSearchPreviewText($child->get('planned_end_date')),
            ])),
        ];
    }
    $documents = [];
    foreach ($project->getDocuments() as $link) {
        $document = $organization->loadTopbarSearchPreviewObject('documents', (int)$link->get('IDdocument'), $viewerContext);
        if (!$document || $document->isArchived()) { continue; }
        $documents[] = ['title' => $document->get('title'), 'meta' => $document->getDocumentTypeLabel()];
    }
    $events = [];
    foreach ($project->getEvents() as $event) {
        if (!$organization->loadTopbarSearchPreviewObject('calendar', (int)$event->getId(), $viewerContext)) { continue; }
        $events[] = [
            'title' => $event->get('title'),
            'meta' => implode(' - ', array_filter([omoSearchPreviewText($event->get('start_at')), omoSearchPreviewText($event->get('end_at'))])),
        ];
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
        'metrics' => ['children' => count($children), 'documents' => count($documents), 'events' => count($events)],
        'collections' => [
            ['title' => omoSearchPreviewT('children'), 'items' => $children],
            ['title' => omoSearchPreviewT('documents'), 'items' => $documents],
            ['title' => omoSearchPreviewT('events'), 'items' => $events],
        ],
    ];
};
