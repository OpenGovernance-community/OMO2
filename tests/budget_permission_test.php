<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';
require_once dirname(__DIR__) . '/class/dbobject/permission.class.php';

use dbObject\Permission;

function assertBudgetPermission(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$catalog = Permission::getBuiltInCatalog();
foreach (array('CAN_EDIT_HOLON_BUDGET', 'CAN_EDIT_AFFECTATION_BUDGET') as $permissionKey) {
    assertBudgetPermission(isset($catalog[$permissionKey]), $permissionKey . ' must be registered in the permission catalog.');
    assertBudgetPermission(($catalog[$permissionKey]['iscontextual'] ?? false) === true, $permissionKey . ' must be contextual.');
    assertBudgetPermission(($catalog[$permissionKey]['group'] ?? '') === 'budget', $permissionKey . ' must be shown in the Budget group.');
}

$root = dirname(__DIR__);
$migration = (string)file_get_contents($root . '/sql/2026-09-07-04-budget-permissions.sql');
$seed = (string)file_get_contents($root . '/docker/db/init/00-base.seed.sql');
$budgetAction = (string)file_get_contents($root . '/omo/api/budget/action.php');
$budgetIndex = (string)file_get_contents($root . '/omo/api/budget/index.php');
$assignmentPopup = (string)file_get_contents($root . '/omo/api/team/member_assignment_popup.php');

foreach (array('CAN_EDIT_HOLON_BUDGET', 'CAN_EDIT_AFFECTATION_BUDGET') as $permissionKey) {
    assertBudgetPermission(str_contains($migration, $permissionKey), $permissionKey . ' must be added by the SQL migration.');
    assertBudgetPermission(str_contains($seed, $permissionKey), $permissionKey . ' must be present in the Docker seed.');
}

assertBudgetPermission(
    str_contains($budgetAction, "isAllowed('CAN_EDIT_HOLON_BUDGET')")
        && str_contains($budgetIndex, "isAllowed('CAN_EDIT_HOLON_BUDGET')"),
    'Direct holon budgets must require CAN_EDIT_HOLON_BUDGET in both UI and action.'
);
assertBudgetPermission(
    str_contains($assignmentPopup, "isAllowed('CAN_EDIT_AFFECTATION_BUDGET')")
        && str_contains($assignmentPopup, '$canEditAssignmentBudget ? ($_POST'),
    'Assignment budgets must require CAN_EDIT_AFFECTATION_BUDGET and preserve values otherwise.'
);

echo "budget_permission_test: OK\n";
