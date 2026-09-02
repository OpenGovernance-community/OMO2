<?php
use dbObject\ArrayUserOrganization;
use dbObject\Holon;

$memberships = new ArrayUserOrganization();
$allowedTeamUserIds = null;
if ($dashboardModuleScope === 'contextual' && $scopeReferenceHolon instanceof Holon) {
    $allowedTeamUserIds = $scopeReferenceHolon->getAssociatedMemberUserIds(array(
        'organizationId' => $currentOrganizationId,
    ));
} elseif ($dashboardModuleScope !== 'contextual' && count($dashboardModuleScopeHolonIds) > 0) {
    $allowedTeamUserIdMap = [];
    foreach ($dashboardModuleScopeHolonIds as $scopeHolonId) {
        $scopeHolon = new Holon();
        if (!$scopeHolon->load((int)$scopeHolonId)) {
            continue;
        }
        foreach ($scopeHolon->getAssociatedMemberUserIds(array(
            'organizationId' => $currentOrganizationId,
            'includeDescendants' => false,
        )) as $userId) {
            $allowedTeamUserIdMap[(int)$userId] = (int)$userId;
        }
    }
    $allowedTeamUserIds = array_values($allowedTeamUserIdMap);
}
$teamEvents = !empty($enabledAppHashes['team']) && $currentUserId > 0
    ? $memberships->buildUpcomingCelebrations($currentOrganizationId, 6, null, array(
        'proNew' => t('personal_space.team.pro.new', [], $lang, $sourceLang),
        'proNewDetailPrefix' => t('personal_space.team.pro.new_detail_prefix', [], $lang, $sourceLang),
        'proToday' => t('personal_space.team.pro.today', [], $lang, $sourceLang),
        'proSoonPrefix' => t('personal_space.team.pro.soon_prefix', [], $lang, $sourceLang),
    ), $allowedTeamUserIds)
    : array();
