<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';
require_once dirname(__DIR__) . '/class/dbobject/userholon.class.php';
require_once dirname(__DIR__) . '/class/dbobject/holon.class.php';

use dbObject\Holon;

function assertHolonBudget(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$rules = Holon::rules();
$floatFields = array();
$stringFields = array();
foreach ($rules as $rule) {
    if (($rule[1] ?? '') === 'float') {
        $floatFields = array_merge($floatFields, $rule[0] ?? array());
    } elseif (($rule[1] ?? '') === 'string') {
        $stringFields = array_merge($stringFields, $rule[0] ?? array());
    }
}

assertHolonBudget(in_array('time_budget_hours', $floatFields, true), 'The holon time budget must be a dedicated numeric field.');
assertHolonBudget(in_array('money_budget', $floatFields, true), 'The holon money budget must be a dedicated numeric field.');
assertHolonBudget(in_array('time_budget_recurrence', $stringFields, true), 'The holon time recurrence must be a dedicated field.');
assertHolonBudget(in_array('money_budget_recurrence', $stringFields, true), 'The holon money recurrence must be a dedicated field.');

$normalized = Holon::normalizeBudgetDetails(array(
    'time_budget_hours' => '4.5',
    'time_budget_recurrence' => 'month',
    'money_budget' => '1200',
    'money_budget_recurrence' => 'year',
));
assertHolonBudget(!empty($normalized['status']), 'Valid direct holon budgets must be accepted.');
assertHolonBudget(($normalized['values']['time_budget_hours'] ?? null) === '4.50', 'Time budgets must retain decimal precision.');
assertHolonBudget(($normalized['values']['time_budget_recurrence'] ?? null) === 'month', 'Time recurrence must be normalized.');
assertHolonBudget(($normalized['values']['money_budget'] ?? null) === '1200.00', 'Money budgets must retain decimal precision.');
assertHolonBudget(($normalized['values']['money_budget_recurrence'] ?? null) === 'year', 'Money recurrence must be normalized.');

$missingRecurrence = Holon::normalizeBudgetDetails(array(
    'time_budget_hours' => '4',
    'time_budget_recurrence' => '',
));
assertHolonBudget(empty($missingRecurrence['status']) && ($missingRecurrence['reason'] ?? '') === 'invalid_time_recurrence', 'A populated budget must require a recurrence.');

$empty = Holon::normalizeBudgetDetails(array(
    'time_budget_hours' => '',
    'time_budget_recurrence' => 'month',
    'money_budget' => '',
    'money_budget_recurrence' => 'year',
));
assertHolonBudget(!empty($empty['status']), 'Empty budget amounts must be accepted.');
assertHolonBudget(array_key_exists('time_budget_recurrence', $empty['values']) && $empty['values']['time_budget_recurrence'] === null, 'An empty time budget must clear its recurrence.');
assertHolonBudget(array_key_exists('money_budget_recurrence', $empty['values']) && $empty['values']['money_budget_recurrence'] === null, 'An empty money budget must clear its recurrence.');

echo "holon_budget_test: OK\n";
