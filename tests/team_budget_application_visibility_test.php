<?php
declare(strict_types=1);

function assertTeamBudgetVisibility(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$root = dirname(__DIR__);
$teamIndex = (string)file_get_contents($root . '/omo/api/team/index.php');
$assignmentPopup = (string)file_get_contents($root . '/omo/api/team/member_assignment_popup.php');
$timerIndex = (string)file_get_contents($root . '/timer/index.php');

assertTeamBudgetVisibility(
    str_contains($teamIndex, "isApplicationEnabled('budget', \$currentUserId)"),
    'Team must resolve assignment budget visibility from the Budget application state.'
);
assertTeamBudgetVisibility(
    str_contains($teamIndex, '$hasBudgetApplication && $contextTimeBudgetAmount')
        && str_contains($teamIndex, '$hasBudgetApplication && $contextMoneyBudgetAmount'),
    'Team cards must hide both assignment budget labels when Budget is disabled.'
);
assertTeamBudgetVisibility(
    str_contains($assignmentPopup, "\$canEditAssignmentBudget = \$hasBudgetApplication && \$holon->isAllowed('CAN_EDIT_AFFECTATION_BUDGET');")
        && str_contains($assignmentPopup, '<?php if ($canEditAssignmentBudget): ?>')
        && str_contains($assignmentPopup, "\$assignment->get('time_budget_hours')")
        && str_contains($assignmentPopup, "\$assignment->get('money_budget')"),
    'The assignment editor must hide its budget controls and preserve stored values when Budget is disabled or not allowed.'
);
assertTeamBudgetVisibility(
    !str_contains($timerIndex, "isApplicationEnabled('budget'"),
    'Timer must remain independent from the Budget application state.'
);

echo "team_budget_application_visibility_test: OK\n";
