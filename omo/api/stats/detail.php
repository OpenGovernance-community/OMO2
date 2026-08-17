<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\StatIndicator;
use dbObject\StatIndicatorReferencePoint;
use dbObject\StatIndicatorValue;

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$currentHolonId = isset($_GET['cid']) && is_numeric($_GET['cid']) ? (int)$_GET['cid'] : 0;
$indicatorId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$indicator = omoStatsLoadIndicator($indicatorId, $organizationId);

if (!($indicator instanceof StatIndicator)) {
    http_response_code(404);
    echo '<div class="omo-empty-state">' . omoApiEscape(omoStatsT('stats.error.not_found')) . '</div>';
    exit;
}

$values = omoStatsCollectionItems($indicator->getMeasurements(), StatIndicatorValue::class);
$referencePoints = omoStatsCollectionItems($indicator->getReferencePoints(), StatIndicatorReferencePoint::class);
$valuesDescending = array_reverse($values);
$latestValue = count($values) > 0 ? $values[count($values) - 1] : null;
$latestReferencePercentage = omoStatsGetIndicatorReferencePercentage($indicator, $latestValue, $referencePoints);
$canEdit = $indicator->canEdit();
$canEditValues = $canEdit && !$indicator->isEthercalcSource();
$sourceUrl = StatIndicator::sanitizeSourceUrl($indicator->get('source_url'));
$contextLabel = omoStatsContextLabel($indicator);
$measurementFrequency = StatIndicator::normalizeMeasurementFrequency($indicator->get('measurement_frequency'));
$measurementSchedule = omoStatsMeasurementScheduleLabel($measurementFrequency, $indicator->get('measurement_schedule'));
$chartMinValue = is_numeric($indicator->get('chart_min_value')) ? (float)$indicator->get('chart_min_value') : null;
$showCumulative = (int)$indicator->get('show_cumulative') > 0;
$overdueInfo = omoStatsGetIndicatorOverdueInfo($indicator);
$overdueSeverity = (string)$overdueInfo['severity'];
$chartData = omoStatsBuildIndicatorChartData($indicator, $values, $referencePoints, $overdueSeverity);
$headerDescriptionParts = [omoStatsReferenceTypeLabel($indicator->get('reference_type'))];
if ($measurementFrequency !== null) {
    $headerDescriptionParts[] = omoStatsMeasurementFrequencyLabel($measurementFrequency);
}
if ($measurementSchedule !== '') {
    $headerDescriptionParts[] = $measurementSchedule;
}
$headerDescription = implode(' · ', $headerDescriptionParts);
$editUrl = '/omo/api/stats/edit.php?oid=' . rawurlencode((string)$organizationId) . '&id=' . rawurlencode((string)$indicatorId);
if ($currentHolonId > 0) {
    $editUrl .= '&cid=' . rawurlencode((string)$currentHolonId);
}
$detailUrl = '/omo/api/stats/detail.php?oid=' . rawurlencode((string)$organizationId) . '&id=' . rawurlencode((string)$indicatorId);
if ($currentHolonId > 0) {
    $detailUrl .= '&cid=' . rawurlencode((string)$currentHolonId);
}
$tabPrefix = 'omo-stats-detail-' . (int)$indicatorId;
?>
<article
    class="omo-stats-detail<?= $overdueSeverity === 'error' ? ' omo-stats-detail--overdue' : ($overdueSeverity === 'warning' ? ' omo-stats-detail--warning' : '') ?>"
    data-omo-stats-detail
    data-indicator-id="<?= (int)$indicatorId ?>"
    data-detail-url="<?= omoApiEscape($detailUrl) ?>"
>
    <div
        hidden
        data-omo-subdrawer-header
        data-omo-subdrawer-title="<?= omoApiEscape((string)$indicator->get('name')) ?>"
        data-omo-subdrawer-description="<?= omoApiEscape($headerDescription) ?>"
    >
        <?php if ($canEdit): ?>
            <button
                type="button"
                class="generic-action-button generic-action-button--main"
                data-omo-subdrawer-action
                data-omo-stats-open-editor-url="<?= omoApiEscape($editUrl) ?>"
            ><?= omoApiEscape(omoStatsT('stats.action.edit')) ?></button>
        <?php endif; ?>
    </div>

    <div class="omo-stats-detail__meta omo-stats-detail__meta--compact generic-meta">
        <span><strong><?= omoApiEscape(omoStatsT('stats.card.context')) ?> :</strong> <?= omoApiEscape($contextLabel) ?></span>
            <?php if ($overdueSeverity === 'warning'): ?>
                <span class="omo-stats-overdue-label omo-stats-overdue-label--warning"><?= omoApiEscape(omoStatsT('stats.card.to_complete')) ?></span>
            <?php elseif ($overdueSeverity === 'error'): ?>
                <span class="omo-stats-overdue-label"><?= omoApiEscape(omoStatsT('stats.card.overdue_days', ['count' => $overdueInfo['overdue_days']])) ?></span>
            <?php endif; ?>
            <?php if ($latestValue instanceof StatIndicatorValue): ?>
                <span class="omo-stats-detail__latest-value"><span><strong><?= omoApiEscape(omoStatsT('stats.detail.latest')) ?> :</strong> <?= omoApiEscape(omoStatsFormatNumber($latestValue->get('value'))) ?><?php if (is_numeric($latestReferencePercentage)): ?> <span class="omo-stats-reference-percentage">(<?= omoApiEscape(omoStatsFormatNumber($latestReferencePercentage)) ?>%)</span><?php endif; ?></span><time>· <?= omoApiEscape(omoStatsFormatDateTime($latestValue->get('measured_at'))) ?></time></span>
            <?php endif; ?>
            <?php if ($chartMinValue !== null): ?>
                <span><strong><?= omoApiEscape(omoStatsT('stats.detail.chart_min_value')) ?> :</strong> <?= omoApiEscape(omoStatsFormatNumber($chartMinValue)) ?></span>
            <?php endif; ?>
            <?php if ($sourceUrl !== ''): ?>
                <a href="<?= omoApiEscape($sourceUrl) ?>" target="_blank" rel="noopener noreferrer"><?= omoApiEscape(omoStatsT('stats.detail.source')) ?></a>
            <?php endif; ?>
        </div>

    <div class="generic-tabs omo-stats-detail__tabs" data-generic-tabs>
        <div class="generic-tabs__list">
            <button type="button" class="generic-tabs__tab is-active" data-generic-tab data-generic-tab-target="<?= omoApiEscape($tabPrefix) ?>-chart"><?= omoApiEscape(omoStatsT('stats.detail.tab.chart')) ?></button>
            <button type="button" class="generic-tabs__tab" data-generic-tab data-generic-tab-target="<?= omoApiEscape($tabPrefix) ?>-values"><?= omoApiEscape(omoStatsT('stats.detail.tab.values')) ?> (<?= count($values) ?>)</button>
        </div>
        <div class="generic-tabs__panels">
            <section id="<?= omoApiEscape($tabPrefix) ?>-chart" class="generic-tabs__panel omo-stats-detail__chart-panel" data-generic-tab-panel>
                <div class="omo-stats-interactive-chart" data-omo-stats-interactive-chart>
                    <?= omoStatsRenderChart($indicator, $values, $referencePoints, 'large', $overdueSeverity, true) ?>
                    <?= omoStatsRenderInteractiveChartRange($chartData) ?>
                </div>
                <div class="omo-stats-detail__legend<?= $showCumulative ? ' omo-stats-detail__legend--cumulative' : '' ?>">
                    <span class="omo-stats-detail__legend-item omo-stats-detail__legend-item--measure"><?= omoApiEscape(omoStatsT('stats.detail.tab.values')) ?></span>
                    <?php if ($showCumulative): ?>
                        <span class="omo-stats-detail__legend-item omo-stats-detail__legend-item--cumulative"><?= omoApiEscape(omoStatsT('stats.detail.cumulative')) ?></span>
                    <?php endif; ?>
                    <?php if (StatIndicator::normalizeReferenceType($indicator->get('reference_type')) !== StatIndicator::REFERENCE_NONE): ?>
                        <span class="omo-stats-detail__legend-item omo-stats-detail__legend-item--reference"><?= omoApiEscape(omoStatsT('stats.detail.reference')) ?></span>
                    <?php endif; ?>
                </div>
            </section>
            <section id="<?= omoApiEscape($tabPrefix) ?>-values" class="generic-tabs__panel" data-generic-tab-panel hidden>
                <?php if (count($valuesDescending) === 0): ?>
                    <div class="omo-empty-state"><?= omoApiEscape(omoStatsT('stats.detail.no_values')) ?></div>
                <?php else: ?>
                    <div class="omo-stats-value-list">
                        <div class="omo-stats-value-list__header">
                            <span><?= omoApiEscape(omoStatsT('stats.detail.value_date')) ?></span>
                            <span><?= omoApiEscape(omoStatsT('stats.detail.value')) ?></span>
                            <span></span>
                        </div>
                        <?php foreach ($valuesDescending as $value): ?>
                            <div class="omo-stats-value-list__row" data-omo-stats-value-row="<?= (int)$value->getId() ?>">
                                <time datetime="<?= $value->get('measured_at') instanceof DateTimeInterface ? omoApiEscape($value->get('measured_at')->format(DateTimeInterface::ATOM)) : '' ?>"><?= omoApiEscape(omoStatsFormatDateTime($value->get('measured_at'))) ?></time>
                                <strong><?= omoApiEscape(omoStatsFormatNumber($value->get('value'))) ?></strong>
                                <?php if ($canEditValues): ?>
                                    <button
                                        type="button"
                                        class="generic-action-button generic-action-button--danger omo-stats-value-list__delete"
                                        data-omo-stats-delete-value="<?= (int)$value->getId() ?>"
                                        aria-label="<?= omoApiEscape(omoStatsT('stats.action.delete')) ?>"
                                    ><?= omoApiEscape(omoStatsT('stats.action.delete')) ?></button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>

    <?php if ($canEditValues): ?>
        <form class="generic-section generic-section--stack omo-stats-quick-value" data-omo-stats-add-value-form>
            <div>
                <h3 class="generic-card-title generic-card-title--big"><?= omoApiEscape(omoStatsT('stats.detail.add_title')) ?></h3>
                <p class="generic-description"><?= omoApiEscape(omoStatsT('stats.detail.add_help')) ?></p>
            </div>
            <input type="hidden" name="stats_action" value="add_value">
            <input type="hidden" name="indicator_id" value="<?= (int)$indicatorId ?>">
            <input type="hidden" name="oid" value="<?= (int)$organizationId ?>">
            <div class="omo-stats-quick-value__fields">
                <label class="omo-stats-field">
                    <span><?= omoApiEscape(omoStatsT('stats.detail.value')) ?></span>
                    <input type="number" class="generic-form-control" name="value" step="any" required inputmode="decimal">
                </label>
                <label class="omo-stats-field">
                    <span><?= omoApiEscape(omoStatsT('stats.detail.value_date')) ?></span>
                    <input type="datetime-local" class="generic-form-control" name="measured_at" value="<?= omoApiEscape(date('Y-m-d\TH:i')) ?>" required>
                </label>
                <button type="submit" class="generic-action-button generic-action-button--main"><?= omoApiEscape(omoStatsT('stats.detail.add')) ?></button>
            </div>
            <div class="omo-stats-feedback" data-omo-stats-value-feedback role="status"></div>
        </form>
    <?php endif; ?>
</article>
<script src="/omo/api/stats/chart.js?v=20260807-range-handles"></script>
