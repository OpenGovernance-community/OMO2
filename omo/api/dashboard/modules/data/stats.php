<?php
use dbObject\ArrayStatIndicator;
use dbObject\ArrayStatIndicatorImport;
use dbObject\StatIndicator;

$overdueIndicators = [];
if (!empty($enabledAppHashes['stats'])) {
    $statsIndicators = new ArrayStatIndicator();
    $statsContextHolonId = $dashboardModuleContextHolonId;
    $includeOrganizationItems = $organizationRootHolon instanceof \dbObject\Holon
        && $statsContextHolonId === (int)$organizationRootHolon->getId();
    $statsIndicators->loadForContext(
        $currentOrganizationId,
        $statsContextHolonId,
        $dashboardModuleScope,
        $dashboardModuleScopeHolonIds,
        $includeOrganizationItems
    );
    $indicatorById = [];
    foreach ($statsIndicators as $indicator) {
        if ($indicator instanceof StatIndicator) {
            $indicatorById[(int)$indicator->getId()] = $indicator;
        }
    }

    $indicatorImports = new ArrayStatIndicatorImport();
    $indicatorImports->loadForContext(
        $currentOrganizationId,
        $statsContextHolonId,
        $dashboardModuleScope,
        $dashboardModuleScopeHolonIds,
        $includeOrganizationItems
    );
    foreach ($indicatorImports as $indicatorImport) {
        $indicator = $indicatorImport->getIndicator();
        if (
            !($indicator instanceof StatIndicator)
            || !$indicator->canView()
            || isset($indicatorById[(int)$indicator->getId()])
        ) {
            continue;
        }
        $indicatorById[(int)$indicator->getId()] = $indicator;
    }

    foreach ($indicatorById as $indicator) {
        $indicatorHolon = $indicator->getHolon();
        if (!omoDashboardMatchesResponsibleAudience(
            $dashboardModuleAudience,
            $indicator->get('IDuser_responsible'),
            $indicatorHolon instanceof \dbObject\Holon ? $indicatorHolon : null,
            $currentUserId,
            $currentOrganizationId
        )) {
            continue;
        }
        $overdueInfo = omoStatsGetIndicatorOverdueInfo($indicator);
        if (empty($overdueInfo['is_overdue'])) {
            continue;
        }
        $overdueIndicators[] = [
            'id' => (int)$indicator->getId(),
            'title' => trim((string)$indicator->get('name')) !== ''
                ? trim((string)$indicator->get('name'))
                : 'Indicateur #' . (int)$indicator->getId(),
            'holonId' => $indicatorHolon instanceof \dbObject\Holon ? (int)$indicatorHolon->getId() : 0,
            'contextLabel' => omoStatsContextLabel($indicator),
            'responsibilityLabel' => omoStatsResponsibleAssignmentLabel($indicator),
            'severity' => (string)($overdueInfo['severity'] ?? 'error'),
            'overdueDays' => (int)($overdueInfo['overdue_days'] ?? 0),
        ];
    }
    usort($overdueIndicators, static function (array $left, array $right): int {
        $severityComparison = strcmp((string)$left['severity'], (string)$right['severity']);
        return $severityComparison !== 0
            ? $severityComparison
            : strcasecmp((string)$left['title'], (string)$right['title']);
    });
}
