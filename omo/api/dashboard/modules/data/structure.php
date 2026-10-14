<?php
use dbObject\History;

$structureHistory = array('items' => array());
if (!empty($enabledAppHashes['structure'])) {
    $structureHistory = History::fetchHolonFeedPage(
        $currentOrganizationId,
        $dashboardModuleContextHolonId,
        5,
        0,
        $dashboardModuleIsOrganizationHolon,
        $dashboardModuleScopeHolonIds
    );
}
$historyItems = is_array($structureHistory['items'] ?? null) ? $structureHistory['items'] : array();
