<?php
use dbObject\ArrayControlActivity;
use dbObject\ControlActivity;
use dbObject\Holon;

$dashboardActivityItems = array();
$dashboardActivityCounts = array('soon' => 0, 'due' => 0, 'overdue' => 0);

if (!empty($enabledAppHashes['activities']) && $scopeReferenceHolon instanceof Holon) {
    $activityScopeHolonIds = $dashboardModuleScope === 'contextual'
        ? array((int)$scopeReferenceHolon->getId())
        : $dashboardModuleScopeHolonIds;
    $activities = new ArrayControlActivity();
    $includeOrganizationTasks = $organizationRootHolon instanceof Holon
        && (int)$scopeReferenceHolon->getId() === (int)$organizationRootHolon->getId();
    $activities->loadForContext($currentOrganizationId, $activityScopeHolonIds, true, $includeOrganizationTasks);
    $now = new DateTimeImmutable('now');

    foreach ($activities as $activity) {
        if (!($activity instanceof ControlActivity)) {
            continue;
        }

        $activityHolon = $activity->getHolon();
        if (($activityHolon instanceof Holon && !$activityHolon->canViewDetail())
            || (!($activityHolon instanceof Holon) && (int)$activity->get('IDholon') !== 0)) {
            continue;
        }
        $responsibleUserId = (int)$activity->get('IDuser_responsible');
        $isAssociated = $activityHolon instanceof Holon
            ? omoDashboardUserIsAssociatedWithHolon($currentUserId, $currentOrganizationId, $activityHolon)
            : \dbObject\UserOrganization::hasActiveMembership($currentUserId, $currentOrganizationId);
        if ($dashboardModuleAudience === 'mine'
            && $responsibleUserId !== $currentUserId
            && ($responsibleUserId > 0 || !$isAssociated)) {
            continue;
        }
        if ($dashboardModuleAudience === 'roles'
            && !$isAssociated) {
            continue;
        }

        $state = $activity->getOccurrenceState($now);
        $stateKey = (string)($state['state'] ?? 'invalid');
        if (in_array($stateKey, array('invalid', 'checked', 'late'), true)) {
            continue;
        }

        $occurrenceAt = $state['occurrenceAt'] ?? null;
        $deadlineAt = $state['deadlineAt'] ?? null;
        $missedOccurrenceAt = $state['missedOccurrenceAt'] ?? null;
        $isMissed = $stateKey === 'missed';
        $effectiveOccurrenceAt = $isMissed && $missedOccurrenceAt instanceof DateTimeInterface
            ? DateTimeImmutable::createFromInterface($missedOccurrenceAt)
            : ($occurrenceAt instanceof DateTimeInterface ? DateTimeImmutable::createFromInterface($occurrenceAt) : null);
        $effectiveDeadlineAt = $isMissed && $effectiveOccurrenceAt instanceof DateTimeImmutable
            ? $activity->getDeadlineAt($effectiveOccurrenceAt)
            : ($deadlineAt instanceof DateTimeInterface ? DateTimeImmutable::createFromInterface($deadlineAt) : null);
        $isOverdue = $isMissed;
        $metricKey = $isOverdue
            ? 'overdue'
            : ($effectiveOccurrenceAt instanceof DateTimeImmutable && $effectiveOccurrenceAt > $now ? 'soon' : 'due');

        $dashboardActivityCounts[$metricKey]++;
        $dashboardActivityItems[] = array(
            'id' => (int)$activity->getId(),
            'title' => trim((string)$activity->get('title')) !== ''
                ? trim((string)$activity->get('title'))
                : 'Tâche récurrente #' . (int)$activity->getId(),
            'holonId' => $activityHolon instanceof Holon ? (int)$activityHolon->getId() : 0,
            'holonLabel' => $activityHolon instanceof Holon
                ? trim((string)$activityHolon->getDisplayName())
                : trim((string)$organization->get('name')),
            'responsibilityLabel' => omoActivityResponsibleAssignmentLabel($activity),
            'metric' => $metricKey,
            'occurrenceAt' => $effectiveOccurrenceAt,
            'deadlineAt' => $effectiveDeadlineAt,
        );
    }

    $activitySortOrder = array('overdue' => 0, 'due' => 1, 'soon' => 2);
    usort($dashboardActivityItems, static function (array $left, array $right) use ($activitySortOrder): int {
        $priorityComparison = (int)($activitySortOrder[$left['metric']] ?? 9) <=> (int)($activitySortOrder[$right['metric']] ?? 9);
        if ($priorityComparison !== 0) {
            return $priorityComparison;
        }
        $leftDate = $left['metric'] === 'overdue' ? ($left['deadlineAt'] ?? null) : ($left['occurrenceAt'] ?? null);
        $rightDate = $right['metric'] === 'overdue' ? ($right['deadlineAt'] ?? null) : ($right['occurrenceAt'] ?? null);
        $leftTimestamp = $leftDate instanceof DateTimeInterface ? $leftDate->getTimestamp() : PHP_INT_MAX;
        $rightTimestamp = $rightDate instanceof DateTimeInterface ? $rightDate->getTimestamp() : PHP_INT_MAX;
        return $leftTimestamp !== $rightTimestamp
            ? $leftTimestamp <=> $rightTimestamp
            : strcasecmp((string)$left['title'], (string)$right['title']);
    });
}
