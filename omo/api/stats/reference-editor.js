(function () {
    'use strict';

    function initReferenceEditor(container, options) {
        options = options || {};
        var editor = container && container.matches && container.matches('[data-omo-stats-reference-editor]')
            ? container
            : container && container.querySelector
                ? container.querySelector('[data-omo-stats-reference-editor]')
                : null;
        if (!editor || editor.dataset.omoStatsReferenceEditorReady === '1') {
            return editor;
        }
        editor.dataset.omoStatsReferenceEditorReady = '1';

        var host = container && container.querySelector ? container : editor;
        var typeField = editor.querySelector('[data-omo-stats-reference-type]') || host.querySelector('[data-omo-stats-reference-type]');
        var nestedReferencePanel = editor.querySelector('[data-omo-stats-reference-panel]');
        var referencePanel = nestedReferencePanel || editor;
        var ceilingEditor = host.querySelector('[data-omo-stats-ceiling-editor]');
        var ceilingValueField = ceilingEditor ? ceilingEditor.querySelector('[data-omo-stats-ceiling-value]') : null;
        var referenceRail = editor.querySelector('[data-omo-stats-reference-rail]');
        var pointList = editor.querySelector('[data-omo-stats-reference-points]');
        var addButton = editor.querySelector('[data-omo-stats-add-reference-point]');
        var labels = options.labels || {};
        var referencePositionStep = 0.2;

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
            return String(date.getFullYear()) + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate())
                + 'T' + pad(date.getHours()) + ':' + pad(date.getMinutes());
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
                dateField.value = formatDateTimeLocal(new Date(Math.round(timestamp / 60000) * 60000));
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
                max: row.getAttribute('data-endpoint') === '1' ? 100 : nextPosition - referencePositionStep
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
            positionField.value = String(Math.round(position * 10) / 10);
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
                position = Number.isFinite(position) ? Math.max(0, Math.min(100, position)) : 0;
                var isEndpoint = row.getAttribute('data-endpoint') === '1';
                var stop = document.createElement('span');
                stop.className = 'omo-stats-reference-editor__stop' + (isEndpoint ? ' is-endpoint' : '');
                stop.style.left = String(position) + '%';
                stop.title = String(position) + ' % - ' + String(valueField ? valueField.value : '');
                if (!isEndpoint) {
                    stop.setAttribute('data-omo-stats-reference-slider', '');
                    stop.setAttribute('data-omo-stats-reference-slider-index', String(rowIndex));
                    stop.setAttribute('role', 'slider');
                    stop.setAttribute('tabindex', '0');
                    stop.setAttribute('aria-valuemin', '0');
                    stop.setAttribute('aria-valuemax', '100');
                    stop.setAttribute('aria-valuenow', String(position));
                    stop.setAttribute('aria-label', (labels.position || 'Position') + ' ' + String(position) + ' %');
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
            var usesSimpleCeiling = Boolean(ceilingEditor);
            if (nestedReferencePanel) {
                referencePanel.hidden = usesSimpleCeiling ? type !== 'objective' : type === 'none';
            } else {
                editor.hidden = usesSimpleCeiling ? type !== 'objective' : type === 'none';
            }
            Array.prototype.forEach.call(referencePanel.querySelectorAll('input, button'), function (field) {
                if (!field.matches('[data-omo-stats-add-reference-point]')) {
                    field.disabled = usesSimpleCeiling ? type !== 'objective' : type === 'none';
                }
            });
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
            if (!usesSimpleCeiling) {
                syncCeilingValues();
            }
            renderReferenceRail();
        }

        function suggestPosition() {
            var positions = rows().map(function (row) {
                var field = row.querySelector('[data-omo-stats-point-position]');
                return field ? Number(field.value || 0) : 0;
            }).filter(Number.isFinite).sort(function (left, right) { return left - right; });
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
            return Math.round((bestStart + bestGap / 2) * 10000) / 10000;
        }

        function createPointRow(point, isEndpoint) {
            var row = document.createElement('div');
            row.className = 'omo-stats-reference-point generic-soft-panel';
            row.setAttribute('data-omo-stats-reference-point', '');
            row.setAttribute('data-endpoint', isEndpoint ? '1' : '0');
            row.innerHTML = '<div class="omo-stats-reference-point__badge"></div>'
                + '<label class="omo-stats-field"><span></span><input type="number" class="generic-form-control" min="0" max="100" step="0.2" data-omo-stats-point-position required></label>'
                + '<label class="omo-stats-field omo-stats-field--date"><span></span><input type="datetime-local" class="generic-form-control" data-omo-stats-point-date readonly aria-readonly="true"></label>'
                + '<label class="omo-stats-field"><span></span><input type="number" class="generic-form-control" step="any" data-omo-stats-point-value required></label>'
                + (isEndpoint ? '' : '<button type="button" class="generic-action-button generic-action-button--danger omo-stats-reference-point__remove" data-omo-stats-remove-reference-point></button>');
            var fields = row.querySelectorAll('.omo-stats-field span');
            row.querySelector('.omo-stats-reference-point__badge').textContent = isEndpoint ? (labels.endpoint || 'Endpoint') : (labels.intermediate || 'Intermediate');
            fields[0].textContent = labels.position || 'Position';
            fields[1].textContent = isEndpoint ? (labels.date || 'Date') : (labels.dateAuto || 'Calculated date');
            fields[2].textContent = labels.value || 'Value';
            if (!isEndpoint) {
                row.querySelector('[data-omo-stats-remove-reference-point]').textContent = labels.remove || 'Remove';
            }
            var positionField = row.querySelector('[data-omo-stats-point-position]');
            var dateField = row.querySelector('[data-omo-stats-point-date]');
            var valueField = row.querySelector('[data-omo-stats-point-value]');
            positionField.name = 'reference_points[0][position_percent]';
            dateField.name = 'reference_points[0][point_at]';
            valueField.name = 'reference_points[0][value]';
            positionField.value = point && point.position_percent != null ? String(point.position_percent) : (isEndpoint ? (point && point.position != null ? String(point.position) : '100') : String(suggestPosition()));
            dateField.value = point && point.point_at ? String(point.point_at).slice(0, 16) : '';
            valueField.value = point && point.value != null ? String(point.value) : '';
            if (isEndpoint) {
                positionField.readOnly = true;
                dateField.readOnly = false;
                dateField.required = true;
            }
            return row;
        }

        function addIntermediatePoint() {
            if (!pointList) {
                return;
            }
            pointList.appendChild(createPointRow({position_percent: suggestPosition()}, false));
            sortPointRows();
            syncIntermediateDates();
            renderReferenceRail();
        }

        function getReferenceSliderRow(slider) {
            var rowIndex = Number(slider ? slider.getAttribute('data-omo-stats-reference-slider-index') : -1);
            var pointRows = rows();
            return Number.isInteger(rowIndex) && rowIndex >= 0 && rowIndex < pointRows.length ? pointRows[rowIndex] : null;
        }

        var activeSlider = null;
        var activePointerId = null;
        function updateSlider(slider, event) {
            var row = getReferenceSliderRow(slider);
            if (!row || !referenceRail) {
                return;
            }
            var railRect = referenceRail.getBoundingClientRect();
            setRowPosition(row, ((event.clientX - railRect.left) / railRect.width) * 100, false);
            var positionField = row.querySelector('[data-omo-stats-point-position]');
            var valueField = row.querySelector('[data-omo-stats-point-value]');
            var position = positionField ? positionField.value : '0';
            slider.style.left = position + '%';
            slider.title = position + ' % - ' + String(valueField ? valueField.value : '');
            slider.setAttribute('aria-valuenow', position);
        }
        function stopSlider() {
            if (!activeSlider) {
                return;
            }
            if (activePointerId !== null && activeSlider.hasPointerCapture(activePointerId)) {
                activeSlider.releasePointerCapture(activePointerId);
            }
            activeSlider.classList.remove('is-dragging');
            activeSlider = null;
            activePointerId = null;
            renderReferenceRail();
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
                var row = removeButton && removeButton.closest('[data-omo-stats-reference-point]');
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
                    var row = event.target.closest('[data-omo-stats-reference-point]');
                    sortPointRows();
                    setRowPosition(row, Number(event.target.value || 0), false);
                    syncIntermediateDates();
                    renderReferenceRail();
                }
            });
        }
        if (referenceRail) {
            referenceRail.addEventListener('pointerdown', function (event) {
                var slider = event.target.closest('[data-omo-stats-reference-slider]');
                if (!slider) {
                    return;
                }
                event.preventDefault();
                activeSlider = slider;
                activePointerId = event.pointerId;
                slider.classList.add('is-dragging');
                if (typeof slider.setPointerCapture === 'function') {
                    slider.setPointerCapture(event.pointerId);
                }
            });
            document.addEventListener('pointermove', function (event) {
                if (activeSlider && activePointerId === event.pointerId) {
                    updateSlider(activeSlider, event);
                }
            });
            document.addEventListener('pointerup', function (event) {
                if (activePointerId === event.pointerId) {
                    stopSlider();
                }
            });
            document.addEventListener('pointercancel', function (event) {
                if (activePointerId === event.pointerId) {
                    stopSlider();
                }
            });
            referenceRail.addEventListener('keydown', function (event) {
                var slider = event.target.closest('[data-omo-stats-reference-slider]');
                var row = getReferenceSliderRow(slider);
                var positionField = row && row.querySelector('[data-omo-stats-point-position]');
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

        if (rows().length === 0 && pointList) {
            var defaultStart = options.defaultStart || formatDateTimeLocal(new Date());
            var defaultEndDate = new Date();
            defaultEndDate.setMonth(defaultEndDate.getMonth() + 1);
            var defaultEnd = options.defaultEnd || formatDateTimeLocal(defaultEndDate);
            var points = Array.isArray(options.points) && options.points.length >= 2 ? options.points : [
                {position_percent: 0, point_at: defaultStart, value: 0},
                {position_percent: 100, point_at: defaultEnd, value: 100}
            ];
            points.forEach(function (point, index) {
                pointList.appendChild(createPointRow(point, index === 0 || index === points.length - 1));
            });
        }
        sortPointRows();
        syncReferenceType();
        syncIntermediateDates();
        renderReferenceRail();
        return editor;
    }

    window.omoStatsInitReferenceEditor = initReferenceEditor;
})();
