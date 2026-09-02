<?php
use dbObject\History;

$structureHistory = array('items' => array());
if (!empty($enabledAppHashes['structure'])) {
    if ($dashboardModuleScope === 'contextual') {
        $structureHistory = History::fetchHolonFeedPage(
            $currentOrganizationId,
            $dashboardModuleContextHolonId,
            5,
            0,
            $dashboardModuleContextHolonId <= 0
        );
    } else {
        $historyById = [];
        foreach ($dashboardModuleScopeHolonIds as $scopeHolonId) {
            $scopeHistory = History::fetchHolonFeedPage(
                $currentOrganizationId,
                (int)$scopeHolonId,
                5,
                0,
                false
            );
            foreach ((array)($scopeHistory['items'] ?? []) as $historyItem) {
                $historyId = (int)($historyItem['id'] ?? 0);
                if ($historyId > 0) {
                    $historyById[$historyId] = $historyItem;
                }
            }
        }
        $historyItems = array_values($historyById);
        usort($historyItems, static function (array $left, array $right): int {
            $dateComparison = strcmp(
                (string)($right['datecreation'] ?? ''),
                (string)($left['datecreation'] ?? '')
            );
            return $dateComparison !== 0
                ? $dateComparison
                : ((int)($right['id'] ?? 0) <=> (int)($left['id'] ?? 0));
        });
        $structureHistory['items'] = array_slice($historyItems, 0, 5);
    }
}
$historyItems = is_array($structureHistory['items'] ?? null) ? $structureHistory['items'] : array();
