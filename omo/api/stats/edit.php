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
if ($indicatorId <= 0 && !omoStatsCanCreateContext($context)) {
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
$referenceType = StatIndicator::normalizeReferenceType($indicator->get('reference_type'));
$ceilingValue = $referenceType === StatIndicator::REFERENCE_CEILING
    ? omoStatsGetCeilingValue($referencePoints)
    : null;
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

if (count($referencePoints) === 0 && $referenceType !== StatIndicator::REFERENCE_CEILING) {
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
<section class="generic-soft-panel omo-stats-ceiling-editor" data-omo-stats-ceiling-editor hidden>
    <div class="omo-stats-ceiling-editor__heading">
        <h3 class="generic-card-title generic-card-title--big"><?= omoApiEscape(omoStatsT('stats.form.ceiling_title')) ?></h3>
        <p><?= omoApiEscape(omoStatsT('stats.form.ceiling_help')) ?></p>
    </div>
    <label class="omo-stats-field">
        <span><?= omoApiEscape(omoStatsT('stats.form.ceiling_value')) ?></span>
        <input
            type="number"
            class="generic-form-control"
            name="ceiling_value"
            value="<?= omoApiEscape(omoStatsEditInputNumber($ceilingValue)) ?>"
            step="any"
            required
            data-omo-stats-ceiling-value
        >
    </label>
</section>
<div class="omo-stats-reference-editor" data-omo-stats-reference-editor>
    <div class="omo-stats-reference-editor__heading">
        <div>
            <h3 class="generic-card-title generic-card-title--big"><?= omoApiEscape(omoStatsT('stats.form.reference_title')) ?></h3>
            <p><?= omoApiEscape(omoStatsT('stats.form.reference_help')) ?></p>
        </div>
        <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-stats-add-reference-point><?= omoApiEscape(omoStatsT('stats.form.add_point')) ?></button>
    </div>
    <div class="omo-stats-reference-editor__rail" data-omo-stats-reference-rail></div>
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
                        step="0.2"
                        data-omo-stats-point-position
                        <?= $isEndpoint ? 'readonly' : '' ?>
                        required
                    >
                </label>
                <label class="omo-stats-field omo-stats-field--date">
                    <span><?= omoApiEscape(omoStatsT($isEndpoint ? 'stats.form.point_date' : 'stats.form.point_date_auto')) ?></span>
                    <input
                        type="datetime-local"
                        class="generic-form-control"
                        name="reference_points[<?= (int)$pointIndex ?>][point_at]"
                        value="<?= $pointAt instanceof DateTimeInterface ? omoApiEscape($pointAt->format('Y-m-d\TH:i')) : '' ?>"
                        data-omo-stats-point-date
                        <?= $isEndpoint ? 'required' : '' ?>
                        <?= $isEndpoint ? '' : 'readonly aria-readonly="true"' ?>
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
    <button type="submit" class="generic-action-button generic-action-button--main" data-omo-stats-save-editor><?= omoApiEscape(omoStatsT('stats.action.save')) ?></button>
</div>
<?php
$afterTableHtml = ob_get_clean();
$params = [
    'fields' => ['name', 'description', 'source_url', 'chart_min_value', 'reference_type'],
    'buttons' => false,
    'action' => '/omo/api/stats/action.php',
    'success' => 'omoStatsAfterIndicatorSave()',
    'afterTableHtml' => $afterTableHtml,
];
?>
<div class="omo-stats-editor" data-omo-stats-editor data-indicator-id="<?= (int)$indicatorId ?>">
    <div
        hidden
        data-omo-subdrawer-header
        data-omo-subdrawer-title="<?= omoApiEscape(omoStatsT($indicatorId > 0 ? 'stats.form.edit_title' : 'stats.form.create_title')) ?>"
        data-omo-subdrawer-description="<?= omoApiEscape(omoStatsT('stats.form.intro')) ?>"
    ></div>
    <section class="generic-hero-panel accent omo-stats-editor__intro">
        <h2 class="generic-card-title generic-card-title--large"><?= omoApiEscape(omoStatsT($indicatorId > 0 ? 'stats.form.edit_title' : 'stats.form.create_title')) ?></h2>
        <p><?= omoApiEscape(omoStatsT('stats.form.intro')) ?></p>
    </section>
    <?php $indicator->display('adminEdit.php', $params); ?>
</div>
<script src="/omo/api/stats/reference-editor.js?v=20260724-ceiling"></script>
<script>
(function () {
    var editor = document.querySelector('[data-omo-stats-editor]');
    if (!editor || editor.dataset.omoStatsEditorReady === '1') {
        return;
    }
    editor.dataset.omoStatsEditorReady = '1';

    var editorForm = editor.querySelector('form');
    var cancelEditorButton = editor.querySelector('[data-omo-stats-cancel-editor]');
    var saveEditorButton = editor.querySelector('[data-omo-stats-save-editor]');
    if (editorForm && window.omoStatsDrawer && typeof window.omoStatsDrawer.setHeader === 'function') {
        if (!editorForm.id) {
            editorForm.id = 'omoStatsIndicatorForm';
        }
        if (cancelEditorButton) {
            cancelEditorButton.setAttribute('form', editorForm.id);
        }
        if (saveEditorButton) {
            saveEditorButton.setAttribute('form', editorForm.id);
            saveEditorButton.type = 'button';
            saveEditorButton.addEventListener('click', function (event) {
                event.preventDefault();
                if (typeof editorForm.requestSubmit === 'function') {
                    editorForm.requestSubmit();
                    return;
                }

                editorForm.dispatchEvent(new Event('submit', {bubbles: true, cancelable: true}));
            });
        }
        window.omoStatsDrawer.setHeader({
            title: <?= json_encode(omoStatsT($indicatorId > 0 ? 'stats.form.edit_title' : 'stats.form.create_title'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            description: <?= json_encode(omoStatsT('stats.form.intro'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            actions: [cancelEditorButton, saveEditorButton].filter(function (button) {
                return button instanceof HTMLElement;
            })
        });
    }

    var typeField = editor.querySelector('[data-omo-stats-reference-type]');
    var measurementFrequencyField = editor.querySelector('[data-omo-stats-measurement-frequency]');
    var measurementScheduleField = editor.querySelector('[data-omo-stats-measurement-schedule]');
    var measurementScheduleWrapper = editor.querySelector('[data-omo-stats-measurement-schedule-field]');
    var referenceEditor = editor.querySelector('[data-omo-stats-reference-editor]');
    var ceilingEditor = editor.querySelector('[data-omo-stats-ceiling-editor]');
    var ceilingValueField = editor.querySelector('[data-omo-stats-ceiling-value]');
    var referenceRail = editor.querySelector('[data-omo-stats-reference-rail]');
    var pointList = editor.querySelector('[data-omo-stats-reference-points]');
    var addButton = editor.querySelector('[data-omo-stats-add-reference-point]');
    var referencePositionStep = 0.2;
    var labels = <?= json_encode([
        'intermediate' => omoStatsT('stats.form.intermediate'),
        'position' => omoStatsT('stats.form.position'),
        'dateAuto' => omoStatsT('stats.form.point_date_auto'),
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

    if (measurementFrequencyField) {
        measurementFrequencyField.addEventListener('change', function () {
            syncMeasurementSchedule(true);
        });
    }

    var useSharedReferenceEditor = typeof window.omoStatsInitReferenceEditor === 'function';
    if (useSharedReferenceEditor) {
        window.omoStatsInitReferenceEditor(editor, {
            labels: labels
        });
    }

    if (!useSharedReferenceEditor) {
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

    function getEndpointDates() {
        var endpointRows = rows().filter(function (row) {
            return row.getAttribute('data-endpoint') === '1';
        }).sort(function (left, right) {
            var leftPosition = left.querySelector('[data-omo-stats-point-position]');
            var rightPosition = right.querySelector('[data-omo-stats-point-position]');
            return Number(leftPosition ? leftPosition.value : 0) - Number(rightPosition ? rightPosition.value : 0);
        });
        if (endpointRows.length < 2) {
            return null;
        }

        var startField = endpointRows[0].querySelector('[data-omo-stats-point-date]');
        var endField = endpointRows[endpointRows.length - 1].querySelector('[data-omo-stats-point-date]');
        var startAt = startField && startField.value ? new Date(startField.value) : null;
        var endAt = endField && endField.value ? new Date(endField.value) : null;
        if (!(startAt instanceof Date) || Number.isNaN(startAt.getTime()) || !(endAt instanceof Date) || Number.isNaN(endAt.getTime()) || endAt <= startAt) {
            return null;
        }
        return {startAt: startAt, endAt: endAt};
    }

    function formatDateTimeLocal(date) {
        function pad(value) {
            return String(value).padStart(2, '0');
        }
        return String(date.getFullYear())
            + '-' + pad(date.getMonth() + 1)
            + '-' + pad(date.getDate())
            + 'T' + pad(date.getHours())
            + ':' + pad(date.getMinutes());
    }

    function syncIntermediateDates() {
        var endpoints = getEndpointDates();
        rows().forEach(function (row) {
            if (row.getAttribute('data-endpoint') === '1') {
                return;
            }
            var positionField = row.querySelector('[data-omo-stats-point-position]');
            var dateField = row.querySelector('[data-omo-stats-point-date]');
            if (!positionField || !dateField || !endpoints) {
                if (dateField) {
                    dateField.value = '';
                }
                return;
            }
            var position = Number(positionField.value || 0);
            if (!Number.isFinite(position)) {
                dateField.value = '';
                return;
            }
            var timestamp = endpoints.startAt.getTime() + ((endpoints.endAt.getTime() - endpoints.startAt.getTime()) * (Math.max(0, Math.min(100, position)) / 100));
            timestamp = Math.round(timestamp / 60000) * 60000;
            dateField.value = formatDateTimeLocal(new Date(timestamp));
        });
    }

    function getSliderBounds(row) {
        var pointRows = rows().sort(function (left, right) {
            var leftPosition = left.querySelector('[data-omo-stats-point-position]');
            var rightPosition = right.querySelector('[data-omo-stats-point-position]');
            return Number(leftPosition ? leftPosition.value : 0) - Number(rightPosition ? rightPosition.value : 0);
        });
        var rowIndex = pointRows.indexOf(row);
        var previousPosition = rowIndex > 0 ? Number(pointRows[rowIndex - 1].querySelector('[data-omo-stats-point-position]').value || 0) : 0;
        var nextPosition = rowIndex >= 0 && rowIndex < pointRows.length - 1 ? Number(pointRows[rowIndex + 1].querySelector('[data-omo-stats-point-position]').value || 100) : 100;
        return {
            min: row.getAttribute('data-endpoint') === '1' ? 0 : previousPosition + referencePositionStep,
            max: row.getAttribute('data-endpoint') === '1' ? 100 : nextPosition - referencePositionStep,
        };
    }

    function setRowPosition(row, position, shouldRenderRail) {
        var positionField = row ? row.querySelector('[data-omo-stats-point-position]') : null;
        if (!positionField || row.getAttribute('data-endpoint') === '1') {
            return;
        }
        var bounds = getSliderBounds(row);
        position = Math.max(bounds.min, Math.min(bounds.max, position));
        position = Math.round(position / referencePositionStep) * referencePositionStep;
        position = Math.max(bounds.min, Math.min(bounds.max, position));
        position = Math.round(position * 10) / 10;
        positionField.value = String(position);
        syncIntermediateDates();
        if (shouldRenderRail !== false) {
            renderReferenceRail();
        }
    }

    function renderReferenceRail() {
        if (!referenceRail) {
            return;
        }
        referenceRail.innerHTML = '';
        rows().forEach(function (row, rowIndex) {
            var positionField = row.querySelector('[data-omo-stats-point-position]');
            var valueField = row.querySelector('[data-omo-stats-point-value]');
            var position = positionField ? Number(positionField.value || 0) : 0;
            if (!Number.isFinite(position)) {
                position = 0;
            }
            position = Math.max(0, Math.min(100, position));
            var isEndpoint = row.getAttribute('data-endpoint') === '1';
            var stop = document.createElement('span');
            stop.className = 'omo-stats-reference-editor__stop' + (isEndpoint ? ' is-endpoint' : '');
            stop.style.left = String(position) + '%';
            stop.title = String(position) + ' % · ' + String(valueField ? valueField.value : '');
            if (!isEndpoint) {
                stop.setAttribute('data-omo-stats-reference-slider', '');
                stop.setAttribute('data-omo-stats-reference-slider-index', String(rowIndex));
                stop.setAttribute('role', 'slider');
                stop.setAttribute('tabindex', '0');
                stop.setAttribute('aria-valuemin', '0');
                stop.setAttribute('aria-valuemax', '100');
                stop.setAttribute('aria-valuenow', String(position));
                stop.setAttribute('aria-label', labels.position + ' ' + String(position) + ' %');
            }
            referenceRail.appendChild(stop);
        });
    }

    function syncCeilingValues(sourceField) {
        if (ceilingEditor || !typeField || typeField.value !== 'ceiling') {
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
            referenceEditor.hidden = ceilingEditor ? type !== 'objective' : type === 'none';
            Array.prototype.forEach.call(referenceEditor.querySelectorAll('input, button'), function (field) {
                field.disabled = ceilingEditor ? type !== 'objective' : type === 'none';
            });
        }
        if (ceilingEditor) {
            ceilingEditor.hidden = type !== 'ceiling';
            Array.prototype.forEach.call(ceilingEditor.querySelectorAll('input, button'), function (field) {
                field.disabled = type !== 'ceiling';
            });
            if (ceilingValueField) {
                ceilingValueField.required = type === 'ceiling';
            }
        }
        if (addButton) {
            addButton.hidden = type !== 'objective';
            addButton.disabled = type !== 'objective';
        }
        if (!ceilingEditor) {
            syncCeilingValues();
        }
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
            + '<label class="omo-stats-field"><span></span><input type="number" class="generic-form-control" name="reference_points[0][position_percent]" min="0" max="100" step="0.2" data-omo-stats-point-position required></label>'
            + '<label class="omo-stats-field omo-stats-field--date"><span></span><input type="datetime-local" class="generic-form-control" name="reference_points[0][point_at]" data-omo-stats-point-date readonly aria-readonly="true"></label>'
            + '<label class="omo-stats-field"><span></span><input type="number" class="generic-form-control" name="reference_points[0][value]" step="any" data-omo-stats-point-value required></label>'
            + '<button type="button" class="generic-action-button generic-action-button--danger omo-stats-reference-point__remove" data-omo-stats-remove-reference-point></button>';
        row.querySelector('.omo-stats-reference-point__badge').textContent = labels.intermediate;
        row.querySelectorAll('.omo-stats-field span')[0].textContent = labels.position;
        row.querySelectorAll('.omo-stats-field span')[1].textContent = labels.dateAuto;
        row.querySelectorAll('.omo-stats-field span')[2].textContent = labels.value;
        row.querySelector('[data-omo-stats-remove-reference-point]').textContent = labels.remove;
        row.querySelector('[data-omo-stats-point-position]').value = String(suggestPosition());
        row.querySelector('[data-omo-stats-point-value]').value = '';
        pointList.appendChild(row);
        sortPointRows();
        syncIntermediateDates();
        renderReferenceRail();
        row.querySelector('[data-omo-stats-point-value]').focus();
    }

    if (typeField) {
        typeField.addEventListener('change', syncReferenceType);
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
                syncIntermediateDates();
                renderReferenceRail();
            }
        });
        pointList.addEventListener('input', function (event) {
            if (event.target.matches('[data-omo-stats-point-value]')) {
                syncCeilingValues(event.target);
            }
            syncIntermediateDates();
            renderReferenceRail();
        });
        pointList.addEventListener('change', function (event) {
            if (event.target.matches('[data-omo-stats-point-position]')) {
                var positionRow = event.target.closest('[data-omo-stats-reference-point]');
                sortPointRows();
                if (positionRow && positionRow.getAttribute('data-endpoint') !== '1') {
                    setRowPosition(positionRow, Number(event.target.value || 0), false);
                }
                syncIntermediateDates();
                renderReferenceRail();
            }
        });
    }

    var activeReferenceSlider = null;
    var activeReferencePointerId = null;

    function getReferenceSliderRow(slider) {
        var rowIndex = Number(slider ? slider.getAttribute('data-omo-stats-reference-slider-index') : -1);
        var pointRows = rows();
        return Number.isInteger(rowIndex) && rowIndex >= 0 && rowIndex < pointRows.length ? pointRows[rowIndex] : null;
    }

    function updateReferenceSliderFromPointer(slider, event) {
        var row = getReferenceSliderRow(slider);
        if (!row) {
            return;
        }
        var railRect = referenceRail.getBoundingClientRect();
        var position = ((event.clientX - railRect.left) / railRect.width) * 100;
        setRowPosition(row, position, false);
        var positionField = row.querySelector('[data-omo-stats-point-position]');
        var valueField = row.querySelector('[data-omo-stats-point-value]');
        var currentPosition = positionField ? positionField.value : '0';
        slider.style.left = currentPosition + '%';
        slider.title = currentPosition + ' % - ' + String(valueField ? valueField.value : '');
        slider.setAttribute('aria-valuenow', currentPosition);
        slider.setAttribute('aria-label', labels.position + ' ' + currentPosition + ' %');
    }

    function stopReferenceSliderDrag() {
        if (!activeReferenceSlider) {
            return;
        }
        if (activeReferencePointerId !== null && activeReferenceSlider.hasPointerCapture(activeReferencePointerId)) {
            activeReferenceSlider.releasePointerCapture(activeReferencePointerId);
        }
        activeReferenceSlider.classList.remove('is-dragging');
        activeReferenceSlider = null;
        activeReferencePointerId = null;
        renderReferenceRail();
    }

    if (referenceRail) {
        referenceRail.addEventListener('pointerdown', function (event) {
            var slider = event.target.closest('[data-omo-stats-reference-slider]');
            if (!slider) {
                return;
            }
            event.preventDefault();
            activeReferenceSlider = slider;
            activeReferencePointerId = event.pointerId;
            slider.classList.add('is-dragging');
            if (typeof slider.setPointerCapture === 'function') {
                slider.setPointerCapture(event.pointerId);
            }
        });
        document.addEventListener('pointermove', function (event) {
            if (!activeReferenceSlider || activeReferencePointerId !== event.pointerId) {
                return;
            }
            updateReferenceSliderFromPointer(activeReferenceSlider, event);
        });
        document.addEventListener('pointerup', function (event) {
            if (activeReferencePointerId === event.pointerId) {
                stopReferenceSliderDrag();
            }
        });
        document.addEventListener('pointercancel', function (event) {
            if (activeReferencePointerId === event.pointerId) {
                stopReferenceSliderDrag();
            }
        });
        referenceRail.addEventListener('keydown', function (event) {
            var slider = event.target.closest('[data-omo-stats-reference-slider]');
            if (!slider) {
                return;
            }
            var row = getReferenceSliderRow(slider);
            var positionField = row ? row.querySelector('[data-omo-stats-point-position]') : null;
            if (!row || !positionField) {
                return;
            }
            var delta = event.shiftKey ? 5 : 1;
            if (event.key === 'ArrowLeft' || event.key === 'ArrowDown') {
                event.preventDefault();
                setRowPosition(row, Number(positionField.value || 0) - delta);
            } else if (event.key === 'ArrowRight' || event.key === 'ArrowUp') {
                event.preventDefault();
                setRowPosition(row, Number(positionField.value || 0) + delta);
            } else if (event.key === 'Home') {
                event.preventDefault();
                setRowPosition(row, 0);
            } else if (event.key === 'End') {
                event.preventDefault();
                setRowPosition(row, 100);
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
    syncReferenceType();
    syncIntermediateDates();
    renderReferenceRail();
    }
    syncMeasurementSchedule(false);
})();
</script>
