<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\Holon;
use dbObject\UserHolon;
use dbObject\WorkTime;

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$currentHolonId = isset($_GET['cid']) && is_numeric($_GET['cid']) ? (int)$_GET['cid'] : 0;
$context = omoBudgetResolveContext($organizationId, $currentHolonId);

if (empty($context['status'])) {
    http_response_code(403);
    echo '<div class="omo-empty-state">' . omoApiEscape($context['message'] ?? omoBudgetT('budget.error.context')) . '</div>';
    exit;
}

/** @var Holon $currentHolon */
$currentHolon = $context['currentHolon'];
$currentHolonId = (int)$currentHolon->getId();
$isRole = (int)$currentHolon->get('IDtypeholon') === 1;
$holonIds = $isRole
    ? [$currentHolonId]
    : $currentHolon->getVisibleDescendantIds(true);

$timezone = new DateTimeZone(date_default_timezone_get());
$today = new DateTimeImmutable('today', $timezone);
$rangeStart = $today->modify('-29 days');
$rangeEnd = $today->modify('+1 day');
$dailySeconds = WorkTime::getDailyMeasuredSecondsForHolons($organizationId, $holonIds, $rangeStart, $rangeEnd);
$totalSeconds = array_sum($dailySeconds);
$timeBudgets = UserHolon::getActiveTimeBudgetsForHolons($holonIds);
$budgetReferenceSeries = omoBudgetBuildTimeBudgetReferenceSeries($timeBudgets, array_keys($dailySeconds), $timezone);
$holonTimeBudgetHours = is_numeric($currentHolon->get('time_budget_hours'))
    ? max(0.0, (float)$currentHolon->get('time_budget_hours'))
    : 0.0;
$holonTimeBudgetRecurrence = UserHolon::normalizeBudgetRecurrence($currentHolon->get('time_budget_recurrence'));
$hasHolonTimeBudget = $holonTimeBudgetHours > 0 && $holonTimeBudgetRecurrence !== '';
$holonBudgetReferenceSeries = $hasHolonTimeBudget
    ? omoBudgetBuildTimeBudgetReferenceSeries(array(array(
        'hours' => $holonTimeBudgetHours,
        'recurrence' => $holonTimeBudgetRecurrence,
    )), array_keys($dailySeconds), $timezone)
    : array();
$initialCumulativeSeconds = 0;
if ($hasHolonTimeBudget) {
    $initialPeriodBounds = omoBudgetResolveRecurrenceBounds($rangeStart, $holonTimeBudgetRecurrence);
    if (is_array($initialPeriodBounds) && $initialPeriodBounds['start'] < $rangeStart) {
        $initialDailySeconds = WorkTime::getDailyMeasuredSecondsForHolons(
            $organizationId,
            $holonIds,
            $initialPeriodBounds['start'],
            $rangeStart
        );
        $initialCumulativeSeconds = array_sum($initialDailySeconds);
    }
}
$hasBudgetReference = count($budgetReferenceSeries) > 1 || count($holonBudgetReferenceSeries) > 1;
$canEditBudget = $currentHolon->isAllowed('CAN_EDIT_HOLON_BUDGET');
$budgetRecurrences = array(
    UserHolon::BUDGET_RECURRENCE_DAY => omoBudgetT('budget.holon_budget.recurrence.day'),
    UserHolon::BUDGET_RECURRENCE_WEEK => omoBudgetT('budget.holon_budget.recurrence.week'),
    UserHolon::BUDGET_RECURRENCE_MONTH => omoBudgetT('budget.holon_budget.recurrence.month'),
    UserHolon::BUDGET_RECURRENCE_YEAR => omoBudgetT('budget.holon_budget.recurrence.year'),
);
$formatBudgetValue = static function ($value): string {
    if (!is_numeric($value)) {
        return '';
    }

    return rtrim(rtrim(number_format((float)$value, 2, '.', ''), '0'), '.');
};
$timeBudgetRecurrence = UserHolon::normalizeBudgetRecurrence($currentHolon->get('time_budget_recurrence'));
$moneyBudgetRecurrence = UserHolon::normalizeBudgetRecurrence($currentHolon->get('money_budget_recurrence'));
?>
<link rel="stylesheet" href="/omo/api/stats/stats.css?v=20260824-source-fields">
<link rel="stylesheet" href="/omo/api/budget/budget.css?v=20260907-3">
<div
    class="omo-budget omo-panel-view"
    id="omo-budget-root"
    data-omo-budget-oid="<?= (int)$organizationId ?>"
    data-omo-budget-cid="<?= (int)$currentHolonId ?>"
>
    <header class="omo-budget__header omo-panel-view__header omo-panel-view__header--stacked">
        <div class="omo-panel-view__header-main">
            <div class="omo-panel-view__title-cluster">
                <span class="omo-panel-view__app-icon" aria-hidden="true"><img src="/omo/images/tools/budget.png" alt=""></span>
                <div class="omo-panel-view__header-copy">
                    <h2 class="omo-panel-view__title"><?= omoApiEscape(omoBudgetT('budget.title')) ?></h2>
                    <p class="omo-panel-view__description"><?= omoApiEscape($currentHolon->getDisplayName()) ?></p>
                </div>
            </div>
        </div>
    </header>

    <div class="omo-panel-view__body">
        <div class="omo-panel-view__body_content omo-budget__body">
            <section class="omo-budget__settings generic-soft-panel" aria-labelledby="omo-budget-settings-title">
                <div class="omo-budget__settings-heading">
                    <div>
                        <h3 id="omo-budget-settings-title" class="generic-card-title"><?= omoApiEscape(omoBudgetT('budget.holon_budget.title')) ?></h3>
                        <p class="generic-description generic-description--small"><?= omoApiEscape(omoBudgetT('budget.holon_budget.description')) ?></p>
                    </div>
                </div>

                <form class="omo-budget__settings-form" action="/omo/api/budget/action.php" method="post" data-omo-budget-form data-omo-budget-error="<?= omoApiEscape(omoBudgetT('budget.holon_budget.error.save')) ?>">
                    <input type="hidden" name="oid" value="<?= (int)$organizationId ?>">
                    <input type="hidden" name="cid" value="<?= (int)$currentHolonId ?>">
                    <div class="omo-budget__settings-grid generic-form-grid">
                        <label class="generic-form-field">
                            <span class="generic-form-label"><?= omoApiEscape(omoBudgetT('budget.holon_budget.time')) ?></span>
                            <input type="number" name="time_budget_hours" class="generic-form-control" min="0" max="9999999999.99" step="0.01" inputmode="decimal" value="<?= omoApiEscape($formatBudgetValue($currentHolon->get('time_budget_hours'))) ?>"<?= $canEditBudget ? '' : ' disabled' ?>>
                            <small class="generic-help-text"><?= omoApiEscape(omoBudgetT('budget.holon_budget.time_help')) ?></small>
                        </label>
                        <label class="generic-form-field">
                            <span class="generic-form-label"><?= omoApiEscape(omoBudgetT('budget.holon_budget.recurrence')) ?></span>
                            <select name="time_budget_recurrence" class="generic-form-control"<?= $canEditBudget ? '' : ' disabled' ?>>
                                <option value=""><?= omoApiEscape(omoBudgetT('budget.holon_budget.recurrence.choose')) ?></option>
                                <?php foreach ($budgetRecurrences as $value => $label): ?>
                                    <option value="<?= omoApiEscape($value) ?>"<?= $timeBudgetRecurrence === $value ? ' selected' : '' ?>><?= omoApiEscape($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="generic-form-field">
                            <span class="generic-form-label"><?= omoApiEscape(omoBudgetT('budget.holon_budget.money')) ?></span>
                            <input type="number" name="money_budget" class="generic-form-control" min="0" max="9999999999.99" step="0.01" inputmode="decimal" value="<?= omoApiEscape($formatBudgetValue($currentHolon->get('money_budget'))) ?>"<?= $canEditBudget ? '' : ' disabled' ?>>
                        </label>
                        <label class="generic-form-field">
                            <span class="generic-form-label"><?= omoApiEscape(omoBudgetT('budget.holon_budget.recurrence')) ?></span>
                            <select name="money_budget_recurrence" class="generic-form-control"<?= $canEditBudget ? '' : ' disabled' ?>>
                                <option value=""><?= omoApiEscape(omoBudgetT('budget.holon_budget.recurrence.choose')) ?></option>
                                <?php foreach ($budgetRecurrences as $value => $label): ?>
                                    <option value="<?= omoApiEscape($value) ?>"<?= $moneyBudgetRecurrence === $value ? ' selected' : '' ?>><?= omoApiEscape($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>
                    <?php if ($canEditBudget): ?>
                        <div class="omo-budget__settings-actions generic-form-actions">
                            <button type="submit" class="generic-action-button generic-action-button--main"><?= omoApiEscape(omoBudgetT('budget.holon_budget.save')) ?></button>
                            <span class="omo-budget__settings-feedback" data-omo-budget-feedback aria-live="polite"></span>
                        </div>
                    <?php endif; ?>
                </form>
            </section>

            <section class="omo-budget__summary generic-soft-panel" aria-labelledby="omo-budget-summary-title">
                <div class="omo-budget__summary-copy">
                    <span id="omo-budget-summary-title" class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoBudgetT('budget.measured_time')) ?></span>
                    <span class="omo-budget__scope"><?= omoApiEscape(omoBudgetT($isRole ? 'budget.scope.direct' : 'budget.scope.descendants')) ?></span>
                </div>
                <div class="omo-budget__total">
                    <strong><?= omoApiEscape(omoBudgetFormatDuration((int)$totalSeconds)) ?></strong>
                    <span><?= omoApiEscape(omoBudgetT('budget.cumulative_time')) ?> · <?= omoApiEscape(omoBudgetT('budget.period')) ?></span>
                </div>
            </section>

            <section class="omo-budget__chart" aria-labelledby="omo-budget-chart-title">
                <div class="omo-budget__chart-heading">
                    <h3 id="omo-budget-chart-title" class="generic-card-title"><?= omoApiEscape(omoBudgetT('budget.daily_time')) ?></h3>
                    <span><?= omoApiEscape(omoBudgetT('budget.period')) ?></span>
                </div>

                <?php if ($totalSeconds <= 0 && !$hasBudgetReference): ?>
                    <div class="omo-empty-state"><?= omoApiEscape(omoBudgetT('budget.empty')) ?></div>
                <?php else: ?>
                    <?= omoBudgetRenderMeasuredTimeChart(
                        $dailySeconds,
                        $timezone,
                        $budgetReferenceSeries,
                        $holonBudgetReferenceSeries,
                        $hasHolonTimeBudget ? $holonTimeBudgetRecurrence : '',
                        $initialCumulativeSeconds
                    ) ?>
                <?php endif; ?>
            </section>
        </div>
    </div>
</div>
<script src="/omo/api/stats/chart.js?v=20260807-range-handles"></script>
<script src="/omo/api/budget/budget.js?v=20260907-holon-budgets"></script>
