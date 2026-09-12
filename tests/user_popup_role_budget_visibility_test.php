<?php
declare(strict_types=1);

function assertUserPopupRoleBudgetVisibility(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$root = dirname(__DIR__);
$popup = (string)file_get_contents($root . '/popup/user.php');
$holon = (string)file_get_contents($root . '/class/dbobject/holon.class.php');

assertUserPopupRoleBudgetVisibility(
    str_contains($popup, "isApplicationEnabled('budget', \$currentViewerUserId)"),
    'The user popup must resolve role budget visibility from the Budget application state.'
);
assertUserPopupRoleBudgetVisibility(
    str_contains($popup, 'omoUserContextBuildAssignmentBudgetLabels($assignment)')
        && str_contains($popup, "\$assignment['budgetLabels'] ?? []"),
    'The user popup must build and render budget labels for role assignments.'
);
assertUserPopupRoleBudgetVisibility(
    str_contains($popup, "\$formatted . 'h'")
        && str_contains($popup, "\$formatted . '.-'"),
    'Role budget labels must use hour and Swiss money suffixes.'
);
assertUserPopupRoleBudgetVisibility(
    str_contains($holon, "'timeBudgetHours' => \$row['holon_time_budget_hours'] ?? null")
        && str_contains($holon, "'moneyBudget' => \$row['holon_money_budget'] ?? null"),
    'Visible role assignments must expose their assignment budget values.'
);
assertUserPopupRoleBudgetVisibility(
    str_contains($popup, 'data-user-role-edit-url')
        && str_contains($popup, 'omoUserContextRenderRoleAssignment')
        && str_contains($popup, 'commonTopbarRefreshModalContent(editorUrl)')
        && str_contains($popup, "&tab=current-roles")
        && str_contains($popup, "&tab=organization-roles"),
    'Editable role assignments must expose a menu action opening the Team assignment editor in the current popup.'
);

echo "user_popup_role_budget_visibility_test: OK\n";
