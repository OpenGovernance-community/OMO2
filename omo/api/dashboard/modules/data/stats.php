<?php
use dbObject\ArrayStatIndicator;
use dbObject\ArrayStatIndicatorImport;
use dbObject\StatIndicator;

$overdueIndicators = [];
if (!empty($enabledAppHashes['stats'])) {
    $statsIndicators = new ArrayStatIndicator();
    $statsContextHolonId = $dashboardModuleContextHolonId;
    $statsIndicators->loadForContext(
        $currentOrganizationId,
        $statsContextHolonId,
        $dashboardModuleScope,
        $dashboardModuleScopeHolonIds
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
        $dashboardModuleScopeHolonIds
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
        $overdueInfo = omoStatsGetIndicatorOverdueInfo($indicator);
        if (empty($overdueInfo['is_overdue'])) {
            continue;
        }
        $overdueIndicators[] = [
            'id' => (int)$indicator->getId(),
            'title' => trim((string)$indicator->get('name')) !== ''
                ? trim((string)$indicator->get('name'))
                : 'Indicateur #' . (int)$indicator->getId(),
            'contextLabel' => omoStatsContextLabel($indicator),
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
