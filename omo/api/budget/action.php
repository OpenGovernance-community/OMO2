<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\Holon;

header('Content-Type: application/json; charset=UTF-8');

$respond = static function (int $statusCode, bool $status, string $message, array $extra = array()): void {
    http_response_code($statusCode);
    echo json_encode(array_merge(array(
        'status' => $status,
        'message' => $message,
    ), $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
};

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    $respond(405, false, omoBudgetT('budget.holon_budget.error.method'));
}

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_POST['oid'] ?? 0));
$holonId = (int)($_POST['cid'] ?? 0);
$context = omoBudgetResolveContext($organizationId, $holonId);
if (empty($context['status']) || !(($context['currentHolon'] ?? null) instanceof Holon)) {
    $respond(404, false, omoBudgetT('budget.holon_budget.error.context'));
}

/** @var Holon $holon */
$holon = $context['currentHolon'];
if (!$holon->isAllowed('CAN_EDIT_HOLON_BUDGET')) {
    $respond(403, false, omoBudgetT('budget.holon_budget.error.forbidden'));
}

$result = $holon->updateBudgetDetails(array(
    'time_budget_hours' => $_POST['time_budget_hours'] ?? '',
    'time_budget_recurrence' => $_POST['time_budget_recurrence'] ?? '',
    'money_budget' => $_POST['money_budget'] ?? '',
    'money_budget_recurrence' => $_POST['money_budget_recurrence'] ?? '',
));

if (empty($result['status'])) {
    $reason = (string)($result['reason'] ?? '');
    $messageKey = match ($reason) {
        'invalid_time_budget', 'invalid_money_budget' => 'budget.holon_budget.error.invalid_budget',
        'invalid_time_recurrence', 'invalid_money_recurrence' => 'budget.holon_budget.error.invalid_recurrence',
        default => 'budget.holon_budget.error.save',
    };
    $respond(422, false, omoBudgetT($messageKey));
}

$respond(200, true, omoBudgetT('budget.holon_budget.saved'), array(
    'budgets' => $result['values'] ?? array(),
));
