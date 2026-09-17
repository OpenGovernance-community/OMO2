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
        $dashboardModuleScopeHolonIds
    );

    foreach ($checklists as $checklist) {
        if (!($checklist instanceof Checklist) || !omoChecklistCanView($checklist)) {
            continue;
        }

        $templateRoot = $checklist->getTemplateRoot();
        $checklistHolon = $templateRoot instanceof Project ? $templateRoot->getHolon() : null;
        $isMine = $currentUserId > 0
            && (
                (int)$checklist->get('IDuser_responsible') === $currentUserId
                || ((int)$checklist->get('IDuser_responsible') <= 0
                    && $checklistHolon instanceof Holon
                    && omoDashboardUserIsAssociatedWithHolon($currentUserId, $currentOrganizationId, $checklistHolon))
            );
        if ($dashboardModuleAudience === 'mine' && !$isMine) {
            continue;
        }

        $dashboardChecklistItems[] = array(
            'id' => (int)$checklist->getId(),
            'title' => $templateRoot instanceof Project ? trim((string)$templateRoot->get('title')) : '',
            'holonId' => $checklistHolon instanceof Holon ? (int)$checklistHolon->getId() : 0,
            'holonLabel' => $checklistHolon instanceof Holon ? trim((string)$checklistHolon->getDisplayName()) : '',
            'responsibilityLabel' => omoChecklistResponsibleAssignmentLabel($checklist),
        );
    }

    usort($dashboardChecklistItems, static function (array $left, array $right): int {
        return strcasecmp((string)$left['title'], (string)$right['title']);
    });
}
