<?php
use dbObject\ArrayProject;
use dbObject\Holon;
use dbObject\Project;

$overdueProjects = [];
$dashboardProjectItems = [];
$dashboardProjectCounts = array('total' => 0, 'in_progress' => 0, 'late' => 0);
if (!empty($enabledAppHashes['projects'])) {
    $allProjects = new ArrayProject();
    $allProjects->loadForOrganization($currentOrganizationId);
    $today = new DateTimeImmutable('today');
    foreach ($allProjects as $project) {
        if (
            !($project instanceof Project)
            || Project::normalizeStatus($project->get('status')) === Project::STATUS_DONE
            || !omoProjectsScopeContainsProject(
                $project,
                $dashboardModuleScope,
                $dashboardModuleContextHolonId,
                $dashboardModuleScopeHolonIds
            )
        ) {
            continue;
        }

        $status = Project::normalizeStatus($project->get('status'));
        $plannedEnd = $project->get('planned_end_date');
        $plannedEndDate = $plannedEnd instanceof DateTimeInterface
            ? DateTimeImmutable::createFromInterface($plannedEnd)
            : null;
        $isLate = $plannedEndDate instanceof DateTimeImmutable && $plannedEndDate < $today;
        $projectHolon = $project->getHolon();
        $projectItem = [
            'id' => (int)$project->getId(),
            'title' => trim((string)$project->get('title')) !== ''
                ? trim((string)$project->get('title'))
                : 'Projet #' . (int)$project->getId(),
            'holonId' => (int)$project->get('IDholon'),
            'holonLabel' => $projectHolon instanceof Holon
                ? trim((string)$projectHolon->getDisplayName())
                : trim((string)$organization->get('name')),
            'plannedEnd' => $plannedEndDate,
            'overdueDays' => $isLate ? max(1, (int)$today->diff($plannedEndDate)->days) : 0,
            'priority' => Project::normalizeLevel($project->get('priority')),
            'status' => $status,
            'filters' => array_values(array_filter(array(
                'total',
                $status === Project::STATUS_IN_PROGRESS ? 'in_progress' : null,
                $isLate ? 'late' : null,
            ))),
        ];
        $dashboardProjectCounts['total']++;
        if ($status === Project::STATUS_IN_PROGRESS) {
            $dashboardProjectCounts['in_progress']++;
        }
        if ($isLate) {
            $dashboardProjectCounts['late']++;
            $overdueProjects[] = $projectItem;
        }
        $dashboardProjectItems[] = $projectItem;
    }
    usort($overdueProjects, static function (array $left, array $right): int {
        $dateComparison = $left['plannedEnd']->getTimestamp() <=> $right['plannedEnd']->getTimestamp();
        return $dateComparison !== 0
            ? $dateComparison
            : strcasecmp((string)$left['title'], (string)$right['title']);
    });
    usort($dashboardProjectItems, static function (array $left, array $right): int {
        $leftPriority = $left['priority'] === null ? PHP_INT_MAX : (int)$left['priority'];
        $rightPriority = $right['priority'] === null ? PHP_INT_MAX : (int)$right['priority'];
        return ($leftPriority <=> $rightPriority)
            ?: ((int)($right['overdueDays'] ?? 0) <=> (int)($left['overdueDays'] ?? 0))
            ?: strcasecmp((string)$left['title'], (string)$right['title']);
    });
}
