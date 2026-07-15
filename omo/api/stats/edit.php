<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\StatIndicator;
use dbObject\StatIndicatorReferencePoint;

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$currentHolonId = isset($_GET['cid']) && is_numeric($_GET['cid']) ? (int)$_GET['cid'] : 0;
$indicatorId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$context = omoStatsResolveContext($organizationId, $currentHolonId);

if (empty($context['status'])) {
    http_response_code(403);
    echo '<div class="omo-empty-state">' . omoApiEscape((string)($context['message'] ?? omoStatsT('stats.error.context'))) . '</div>';
    exit;
}

$indicator = $indicatorId > 0 ? omoStatsLoadIndicator($indicatorId, $organizationId) : new StatIndicator();
if ($indicatorId > 0 && !($indicator instanceof StatIndicator)) {
    http_response_code(404);
    echo '<div class="omo-empty-state">' . omoApiEscape(omoStatsT('stats.error.not_found')) . '</div>';
    exit;
}
if ($indicatorId > 0 && !$indicator->canEdit()) {
    http_response_code(403);
    echo '<div class="omo-empty-state">' . omoApiEscape(omoStatsT('stats.error.forbidden')) . '</div>';
    exit;
}
if ($indicatorId <= 0 && !omoStatsCanManageContext($context)) {
    http_response_code(403);
    echo '<div class="omo-empty-state">' . omoApiEscape(omoStatsT('stats.error.forbidden')) . '</div>';
    exit;
}

if ($indicatorId <= 0) {
    $indicator->set('reference_type', StatIndicator::REFERENCE_NONE);
}

if (!function_exists('fct_reference_type')) {
    function fct_reference_type($object, $field, $default = null)
    {
        $selected = StatIndicator::normalizeReferenceType($object->get($field));
        $options = [
            StatIndicator::REFERENCE_NONE => omoStatsT('stats.form.reference_none'),
            StatIndicator::REFERENCE_CEILING => omoStatsT('stats.form.reference_ceiling'),
            StatIndicator::REFERENCE_OBJECTIVE => omoStatsT('stats.form.reference_objective'),
        ];
        $html = '<select class="admin-edit__control generic-form-control" name="reference_type" id="reference_type" data-omo-stats-reference-type>';
        foreach ($options as $value => $label) {
            $html .= '<option value="' . omoApiEscape($value) . '"' . ($selected === $value ? ' selected' : '') . '>' . omoApiEscape($label) . '</option>';
        }
        $html .= '</select>';
        return $html;
    }
}

if (!function_exists('omoStatsEditInputNumber')) {
    function omoStatsEditInputNumber($value)
    {
        if (!is_numeric($value)) {
            return '';
        }
        return rtrim(rtrim(number_format((float)$value, 6, '.', ''), '0'), '.');
    }
}

$referencePoints = $indicatorId > 0
    ? omoStatsCollectionItems($indicator->getReferencePoints(), StatIndicatorReferencePoint::class)
    : [];
$measurementFrequency = StatIndicator::normalizeMeasurementFrequency($indicator->get('measurement_frequency'));
$measurementSchedule = StatIndicator::normalizeMeasurementSchedule($measurementFrequency, $indicator->get('measurement_schedule'));
$measurementFrequencyOptions = [['value' => '', 'label' => omoStatsT('stats.frequency.none')]];
foreach (StatIndicator::getMeasurementFrequencyCatalog() as $frequency) {
    $measurementFrequencyOptions[] = [
        'value' => $frequency,
        'label' => omoStatsMeasurementFrequencyLabel($frequency),
    ];
}
$measurementScheduleOptions = [];
foreach (StatIndicator::getMeasurementFrequencyCatalog() as $frequency) {
    $measurementScheduleOptions[$frequency] = omoStatsMeasurementScheduleOptions($frequency);
}

if (count($referencePoints) === 0) {
    $startPoint = new StatIndicatorReferencePoint();
    $startPoint->set('position_percent', 0);
    $startPoint->set('value', 0);
    $startPoint->set('point_at', new DateTime());
    $endPoint = new StatIndicatorReferencePoint();
    $endPoint->set('position_percent', 100);
    $endPoint->set('value', 100);
    $endPoint->set('point_at', new DateTime('+1 month'));
    $referencePoints = [$startPoint, $endPoint];
}

usort($referencePoints, static function (StatIndicatorReferencePoint $left, StatIndicatorReferencePoint $right) {
    return (float)$left->get('position_percent') <=> (float)$right->get('position_percent');
});

ob_start();
?>
<section class="generic-soft-panel omo-stats-schedule" data-omo-stats-schedule>
    <div class="omo-stats-schedule__heading">
        <div>
            <h3 class="generic-card-title generic-card-title--big"><?= omoApiEscape(omoStatsT('stats.form.schedule_title')) ?></h3>
            <p><?= omoApiEscape(omoStatsT('stats.form.schedule_help')) ?></p>
        </div>
    </div>
    <div class="omo-stats-schedule__fields">
        <label class="omo-stats-field">
            <span><?= omoApiEscape(omoStatsT('stats.form.frequency')) ?></span>
            <select class="generic-form-control" name="measurement_frequency" data-omo-stats-measurement-frequency>
                <?php foreach ($measurementFrequencyOptions as $option): ?>
                    <option value="<?= omoApiEscape((string)$option['value']) ?>"<?= (string)$option['value'] === (string)$measurementFrequency ? ' selected' : '' ?>><?= omoApiEscape((string)$option['label']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="omo-stats-field" data-omo-stats-measurement-schedule-field>
            <span><?= omoApiEscape(omoStatsT('stats.form.schedule')) ?></span>
            <select class="generic-form-control" name="measurement_schedule" data-omo-stats-measurement-schedule data-selected-schedule="<?= omoApiEscape((string)$measurementSchedule) ?>"></select>
        </label>
    </div>
</section>
<div class="omo-stats-reference-editor" data-omo-stats-reference-editor>
    <div class="omo-stats-reference-editor__heading">
        <div>
            <h3 class="generic-card-title generic-card-title--big"><?= omoApiEscape(omoStatsT('stats.form.reference_title')) ?></h3>
            <p><?= omoApiEscape(omoStatsT('stats.form.reference_help')) ?></p>
        </div>
        <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-stats-add-reference-point><?= omoApiEscape(omoStatsT('stats.form.add_point')) ?></button>
    </div>
    <div class="omo-stats-reference-editor__rail" data-omo-stats-reference-rail aria-hidden="true"></div>
    <div class="omo-stats-reference-editor__points" data-omo-stats-reference-points>
        <?php foreach ($referencePoints as $pointIndex => $point): ?>
            <?php
            $position = (float)$point->get('position_percent');
            $isEndpoint = abs($position) < 0.0001 || abs($position - 100.0) < 0.0001;
            $pointAt = $point->get('point_at');
            ?>
            <div class="omo-stats-reference-point generic-soft-panel" data-omo-stats-reference-point data-endpoint="<?= $isEndpoint ? '1' : '0' ?>">
                <div class="omo-stats-reference-point__badge"><?= omoApiEscape(omoStatsT($isEndpoint ? 'stats.form.endpoint' : 'stats.form.intermediate')) ?></div>
                <label class="omo-stats-field">
                    <span><?= omoApiEscape(omoStatsT('stats.form.position')) ?></span>
                    <input
                        type="number"
                        class="generic-form-control"
                        name="reference_points[<?= (int)$pointIndex ?>][position_percent]"
                        value="<?= omoApiEscape(omoStatsEditInputNumber($position)) ?>"
                        min="0"
                        max="100"
                        step="0.0001"
                        data-omo-stats-point-position
                        <?= $isEndpoint ? 'readonly' : '' ?>
                        required
                    >
                </label>
                <label class="omo-stats-field omo-stats-field--date"<?= $isEndpoint ? '' : ' hidden' ?>>
                    <span><?= omoApiEscape(omoStatsT('stats.form.point_date')) ?></span>
                    <input
                        type="datetime-local"
                        class="generic-form-control"
                        name="reference_points[<?= (int)$pointIndex ?>][point_at]"
                        value="<?= $pointAt instanceof DateTimeInterface ? omoApiEscape($pointAt->format('Y-m-d\TH:i')) : '' ?>"
                        data-omo-stats-point-date
                        <?= $isEndpoint ? 'required' : '' ?>
                    >
                </label>
                <label class="omo-stats-field">
                    <span><?= omoApiEscape(omoStatsT('stats.form.point_value')) ?></span>
                    <input
                        type="number"
                        class="generic-form-control"
                        name="reference_points[<?= (int)$pointIndex ?>][value]"
                        value="<?= omoApiEscape(omoStatsEditInputNumber($point->get('value'))) ?>"
                        step="any"
                        data-omo-stats-point-value
                        required
                    >
                </label>
                <?php if (!$isEndpoint): ?>
                    <button type="button" class="generic-action-button generic-action-button--danger omo-stats-reference-point__remove" data-omo-stats-remove-reference-point><?= omoApiEscape(omoStatsT('stats.form.remove_point')) ?></button>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<input type="hidden" name="stats_action" value="save_indicator">
<input type="hidden" name="oid" value="<?= (int)$organizationId ?>">
<input type="hidden" name="cid" value="<?= (int)$currentHolonId ?>">
<div class="omo-stats-editor__actions">
    <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-stats-cancel-editor data-indicator-id="<?= (int)$indicatorId ?>"><?= omoApiEscape(omoStatsT('stats.action.cancel')) ?></button>
    <button type="submit" class="generic-action-button generic-action-button--main"><?= omoApiEscape(omoStatsT('stats.action.save')) ?></button>
</div>
<?php
$afterTableHtml = ob_get_clean();
$params = [
    'fields' => ['name', 'description', 'source_url', 'reference_type'],
    'buttons' => false,
    'action' => '/omo/api/stats/action.php',
    'success' => 'omoStatsAfterIndicatorSave()',
    'afterTableHtml' => $afterTableHtml,
];
?>
<div class="omo-stats-editor" data-omo-stats-editor data-indicator-id="<?= (int)$indicatorId ?>">
    <section class="generic-hero-panel accent omo-stats-editor__intro">
        <h2 class="generic-card-title generic-card-title--large"><?= omoApiEscape(omoStatsT($indicatorId > 0 ? 'stats.form.edit_title' : 'stats.form.create_title')) ?></h2>
        <p><?= omoApiEscape(omoStatsT('stats.form.intro')) ?></p>
    </section>
    <?php $indicator->display('adminEdit.php', $params); ?>
</div>
<script>
(function () {
    var editor = document.querySelector('[data-omo-stats-editor]');
    if (!editor || editor.dataset.omoStatsEditorReady === '1') {
        return;
    }
    editor.dataset.omoStatsEditorReady = '1';

    var typeField = editor.querySelector('[data-omo-stats-reference-type]');
    var measurementFrequencyField = editor.querySelector('[data-omo-stats-measurement-frequency]');
    var measurementScheduleField = editor.querySelector('[data-omo-stats-measurement-schedule]');
    var measurementScheduleWrapper = editor.querySelector('[data-omo-stats-measurement-schedule-field]');
    var referenceEditor = editor.querySelector('[data-omo-stats-reference-editor]');
    var referenceRail = editor.querySelector('[data-omo-stats-reference-rail]');
    var pointList = editor.querySelector('[data-omo-stats-reference-points]');
    var addButton = editor.querySelector('[data-omo-stats-add-reference-point]');
    var labels = <?= json_encode([
        'intermediate' => omoStatsT('stats.form.intermediate'),
        'position' => omoStatsT('stats.form.position'),
        'value' => omoStatsT('stats.form.point_value'),
        'remove' => omoStatsT('stats.form.remove_point'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    var measurementScheduleOptions = <?= json_encode($measurementScheduleOptions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    function syncMeasurementSchedule(resetSelection) {
        if (!measurementScheduleField || !measurementFrequencyField) {
            return;
        }
        var frequency = measurementFrequencyField.value;
        var options = measurementScheduleOptions[frequency] || [];
        var selectedValue = resetSelection ? '' : (measurementScheduleField.dataset.selectedSchedule || measurementScheduleField.value || '');
        measurementScheduleField.innerHTML = '';
        options.forEach(function (option) {
            var optionElement = document.createElement('option');
            optionElement.value = option.value;
            optionElement.textContent = option.label;
            optionElement.selected = option.value === selectedValue;
            measurementScheduleField.appendChild(optionElement);
        });
        if (measurementScheduleWrapper) {
            measurementScheduleWrapper.hidden = options.length === 0;
        }
        measurementScheduleField.disabled = options.length === 0;
        measurementScheduleField.dataset.selectedSchedule = '';
    }

    function rows() {
        return Array.prototype.slice.call(editor.querySelectorAll('[data-omo-stats-reference-point]'));
    }

    function reindexRows() {
        rows().forEach(function (row, index) {
            Array.prototype.forEach.call(row.querySelectorAll('[name]'), function (field) {
                field.name = field.name.replace(/reference_points\[\d+\]/, 'reference_points[' + String(index) + ']');
            });
        });
    }

    function sortPointRows() {
        if (!pointList) {
            return;
        }
        rows().sort(function (left, right) {
            var leftField = left.querySelector('[data-omo-stats-point-position]');
            var rightField = right.querySelector('[data-omo-stats-point-position]');
            return Number(leftField ? leftField.value : 0) - Number(rightField ? rightField.value : 0);
        }).forEach(function (row) {
            pointList.appendChild(row);
        });
        reindexRows();
    }

    function renderReferenceRail() {
        if (!referenceRail) {
            return;
        }
        referenceRail.innerHTML = '';
        rows().forEach(function (row) {
            var positionField = row.querySelector('[data-omo-stats-point-position]');
            var valueField = row.querySelector('[data-omo-stats-point-value]');
            var position = positionField ? Number(positionField.value || 0) : 0;
            if (!Number.isFinite(position)) {
                position = 0;
            }
            position = Math.max(0, Math.min(100, position));
            var stop = document.createElement('span');
            stop.className = 'omo-stats-reference-editor__stop' + (row.getAttribute('data-endpoint') === '1' ? ' is-endpoint' : '');
            stop.style.left = String(position) + '%';
            stop.title = String(position) + ' % · ' + String(valueField ? valueField.value : '');
            referenceRail.appendChild(stop);
        });
    }

    function syncCeilingValues(sourceField) {
        if (!typeField || typeField.value !== 'ceiling') {
            return;
        }
        var pointRows = rows();
        var firstValue = pointRows.length > 0 ? pointRows[0].querySelector('[data-omo-stats-point-value]') : null;
        if (!firstValue) {
            return;
        }
        var sharedValue = sourceField ? sourceField.value : firstValue.value;
        pointRows.forEach(function (row) {
            var valueField = row.querySelector('[data-omo-stats-point-value]');
            if (valueField) {
                valueField.value = sharedValue;
            }
        });
    }

    function syncReferenceType() {
        var type = typeField ? typeField.value : 'none';
        if (referenceEditor) {
            referenceEditor.hidden = type === 'none';
            Array.prototype.forEach.call(referenceEditor.querySelectorAll('input, button'), function (field) {
                field.disabled = type === 'none';
            });
        }
        if (addButton) {
            addButton.hidden = type !== 'objective';
            addButton.disabled = type !== 'objective';
        }
        syncCeilingValues();
        renderReferenceRail();
    }

    function suggestPosition() {
        var positions = rows().map(function (row) {
            var field = row.querySelector('[data-omo-stats-point-position]');
            return field ? Number(field.value || 0) : 0;
        }).filter(function (value) {
            return Number.isFinite(value);
        }).sort(function (left, right) {
            return left - right;
        });
        if (positions.length < 2) {
            return 50;
        }
        var bestStart = positions[0];
        var bestGap = 0;
        for (var index = 1; index < positions.length; index += 1) {
            var gap = positions[index] - positions[index - 1];
            if (gap > bestGap) {
                bestGap = gap;
                bestStart = positions[index - 1];
            }
        }
        return Math.round((bestStart + (bestGap / 2)) * 10000) / 10000;
    }

    function addIntermediatePoint() {
        if (!pointList) {
            return;
        }
        var row = document.createElement('div');
        row.className = 'omo-stats-reference-point generic-soft-panel';
        row.setAttribute('data-omo-stats-reference-point', '');
        row.setAttribute('data-endpoint', '0');
        row.innerHTML = ''
            + '<div class="omo-stats-reference-point__badge"></div>'
            + '<label class="omo-stats-field"><span></span><input type="number" class="generic-form-control" name="reference_points[0][position_percent]" min="0" max="100" step="0.0001" data-omo-stats-point-position required></label>'
            + '<label class="omo-stats-field"><span></span><input type="number" class="generic-form-control" name="reference_points[0][value]" step="any" data-omo-stats-point-value required></label>'
            + '<button type="button" class="generic-action-button generic-action-button--danger omo-stats-reference-point__remove" data-omo-stats-remove-reference-point></button>';
        row.querySelector('.omo-stats-reference-point__badge').textContent = labels.intermediate;
        row.querySelectorAll('.omo-stats-field span')[0].textContent = labels.position;
        row.querySelectorAll('.omo-stats-field span')[1].textContent = labels.value;
        row.querySelector('[data-omo-stats-remove-reference-point]').textContent = labels.remove;
        row.querySelector('[data-omo-stats-point-position]').value = String(suggestPosition());
        row.querySelector('[data-omo-stats-point-value]').value = '';
        pointList.appendChild(row);
        sortPointRows();
        renderReferenceRail();
        row.querySelector('[data-omo-stats-point-value]').focus();
    }

    if (typeField) {
        typeField.addEventListener('change', syncReferenceType);
    }
    if (measurementFrequencyField) {
        measurementFrequencyField.addEventListener('change', function () {
            syncMeasurementSchedule(true);
        });
    }
    if (addButton) {
        addButton.addEventListener('click', addIntermediatePoint);
    }
    if (pointList) {
        pointList.addEventListener('click', function (event) {
            var removeButton = event.target.closest('[data-omo-stats-remove-reference-point]');
            if (!removeButton) {
                return;
            }
            var row = removeButton.closest('[data-omo-stats-reference-point]');
            if (row && row.getAttribute('data-endpoint') !== '1') {
                row.remove();
                reindexRows();
                renderReferenceRail();
            }
        });
        pointList.addEventListener('input', function (event) {
            if (event.target.matches('[data-omo-stats-point-value]')) {
                syncCeilingValues(event.target);
            }
            renderReferenceRail();
        });
        pointList.addEventListener('change', function (event) {
            if (event.target.matches('[data-omo-stats-point-position]')) {
                sortPointRows();
                renderReferenceRail();
            }
        });
    }

    var saveButton = document.getElementById('btn_submit');
    if (saveButton) {
        saveButton.addEventListener('click', function (event) {
            saveButton.disabled = true;
            saveButton.setAttribute('aria-busy', 'true');
        }, true);
    }

    reindexRows();
    syncMeasurementSchedule(false);
    syncReferenceType();
    renderReferenceRail();
})();
</script>
