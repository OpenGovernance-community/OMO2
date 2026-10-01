<?php
declare(strict_types=1);

function assertBudgetApplicationOptIn(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$root = dirname(__DIR__);
$budgetMigration = (string)file_get_contents($root . '/sql/2026-09-07-01-budget-application.sql');
$optInMigration = (string)file_get_contents($root . '/sql/2026-09-07-05-budget-application-opt-in.sql');
$seed = (string)file_get_contents($root . '/docker/db/init/00-base.seed.sql');
$applicationPopup = (string)file_get_contents($root . '/omo/api/organization_applications_popup.php');

assertBudgetApplicationOptIn(
    str_contains($budgetMigration, "VALUES ('Budget', 'budget'")
        && !str_contains($budgetMigration, 'organization_application'),
    'The Budget migration must only register the application and not activate it for every organization.'
);
assertBudgetApplicationOptIn(
    str_contains($optInMigration, 'DELETE `oa`')
        && str_contains($optInMigration, "WHERE `a`.`hash` = 'budget'"),
    'Existing automatically-created Budget links must be removed by a follow-up migration.'
);
assertBudgetApplicationOptIn(
    !str_contains($seed, "(24,1,11,55,1,NULL)")
        && !str_contains($seed, "(25,2,11,55,1,NULL)"),
    'The Docker seed must not activate Budget automatically for its organizations.'
);
assertBudgetApplicationOptIn(
    str_contains($applicationPopup, 'new \\dbObject\\OrganizationApplication()')
        && str_contains($applicationPopup, "->set('active', \$shouldBeActive ? 1 : 0)"),
    'The organization application picker must remain able to activate an optional application explicitly.'
);

echo "budget_application_opt_in_test: OK\n";
