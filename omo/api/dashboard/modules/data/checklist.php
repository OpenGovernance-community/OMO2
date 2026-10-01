<?php
use dbObject\ArrayChecklist;
use dbObject\Checklist;
use dbObject\Holon;
use dbObject\Project;

$dashboardChecklistItems = array();
if (!empty($enabledAppHashes['checklist']) && $scopeReferenceHolon instanceof Holon) {
    $checklists = new ArrayChecklist();
    $checklists->loadForContext(
        $currentOrganizationId,
        $dashboardModuleContextHolonId,
        $dashboardModuleScope,
        $dashboardModuleScopeHolonIds,
        $organizationRootHolon instanceof Holon && $dashboardModuleContextHolonId === (int)$organizationRootHolon->getId()
    );

    foreach ($checklists as $checklist) {
        if (!($checklist instanceof Checklist) || !omoChecklistCanView($checklist)) {
            continue;
        }

        $templateRoot = $checklist->getTemplateRoot();
        $checklistHolon = $templateRoot instanceof Project ? $templateRoot->getHolon() : null;
        if (!omoDashboardMatchesResponsibleAudience(
            $dashboardModuleAudience,
            $checklist->get('IDuser_responsible'),
            $checklistHolon instanceof Holon ? $checklistHolon : null,
            $currentUserId,
            $currentOrganizationId
        )) {
            continue;
        }

        $dashboardChecklistItems[] = array(
            'id' => (int)$checklist->getId(),
            'title' => $templateRoot instanceof Project ? trim((string)$templateRoot->get('title')) : '',
            'holonId' => $checklistHolon instanceof Holon ? (int)$checklistHolon->getId() : 0,
            'holonLabel' => $checklistHolon instanceof Holon
                ? trim((string)$checklistHolon->getDisplayName())
                : trim((string)$organization->get('name')),
            'responsibilityLabel' => omoChecklistResponsibleAssignmentLabel($checklist),
        );
    }

    usort($dashboardChecklistItems, static function (array $left, array $right): int {
        return strcasecmp((string)$left['title'], (string)$right['title']);
    });
}
