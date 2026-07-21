(function () {
    'use strict';

    var groupColors = ['#2563eb', '#db2777', '#059669', '#d97706', '#7c3aed', '#0891b2'];
    var chartSequence = 0;

    function escapeXml(value) {
        return String(value === undefined || value === null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function pad(value) {
        return String(value).padStart(2, '0');
    }

    function formatDay(day) {
        var date = new Date(Number(day) * 86400000);
        return pad(date.getUTCDate()) + '.' + pad(date.getUTCMonth() + 1) + '.' + date.getUTCFullYear();
    }

    function formatTimestamp(timestamp) {
        var date = new Date(Number(timestamp) * 1000);
        return pad(date.getDate()) + '.' + pad(date.getMonth() + 1) + '.' + date.getFullYear()
            + ' ' + pad(date.getHours()) + ':' + pad(date.getMinutes());
    }

    function formatNumber(value) {
        var rounded = Math.round(Number(value) * 1000000) / 1000000;
        var fixed = Math.abs(rounded - Math.round(rounded)) < 0.000001
            ? String(Math.round(rounded))
            : rounded.toFixed(6).replace(/0+$/, '').replace(/\.$/, '');
        var parts = fixed.split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
        return parts.join(',');
    }

    function buildPointTooltipText(data, point) {
        var labels = data.tooltip || {value: 'Valeur', date: 'Date'};
        return String(labels.value || 'Valeur') + ' : ' + formatNumber(point.value)
            + '\n' + String(labels.date || 'Date') + ' : ' + formatTimestamp(point.timestamp);
    }

    function buildPointTooltipAttributes(data, point) {
        var text = buildPointTooltipText(data, point);
        return ' data-omo-stats-chart-tooltip="' + escapeXml(text) + '" tabindex="0" aria-label="' + escapeXml(text) + '"';
    }

    function resolveScale(points) {
        var values = points.map(function (point) { return Number(point.value); });
        if (!values.length) {
            return null;
        }
        var minValue = Math.min.apply(Math, values);
        var maxValue = Math.max.apply(Math, values);
        var valueRange = maxValue - minValue;
        if (valueRange < 0.000000001) {
            valueRange = Math.max(1, Math.abs(maxValue) * 0.2);
            minValue -= valueRange / 2;
            maxValue += valueRange / 2;
        }
        var rawStep = valueRange / 4;
        var power = Math.pow(10, Math.floor(Math.log(rawStep) / Math.LN10));
        var normalizedStep = rawStep / power;
        var niceStep = normalizedStep < 1.5 ? 1 : (normalizedStep < 3 ? 2 : (normalizedStep < 7 ? 5 : 10));
        var step = niceStep * power;
        var scaleMin = Math.floor(minValue / step) * step;
        var scaleMax = Math.ceil(maxValue / step) * step;
        if (Math.abs(scaleMin) < step * 0.000000001) {
            scaleMin = 0;
        }
        if (Math.abs(scaleMax) < step * 0.000000001) {
            scaleMax = 0;
        }
        return {
            min: scaleMin,
            max: scaleMax,
            step: step,
            intervals: Math.max(1, Math.round((scaleMax - scaleMin) / step))
        };
    }

    function interpolatePoint(points, timestamp) {
        for (var index = 1; index < points.length; index += 1) {
            var left = points[index - 1];
            var right = points[index];
            var leftTimestamp = Number(left.timestamp);
            var rightTimestamp = Number(right.timestamp);
            if (timestamp === leftTimestamp) {
                return {timestamp: timestamp, value: Number(left.value)};
            }
            if (timestamp === rightTimestamp) {
                return {timestamp: timestamp, value: Number(right.value)};
            }
            if (leftTimestamp < timestamp && timestamp < rightTimestamp) {
                var ratio = (timestamp - leftTimestamp) / (rightTimestamp - leftTimestamp);
                return {
                    timestamp: timestamp,
                    value: Number(left.value) + ((Number(right.value) - Number(left.value)) * ratio)
                };
            }
        }
        return null;
    }

    function addUniquePoint(points, point) {
        if (!point) {
            return;
        }
        var existingIndex = points.findIndex(function (candidate) {
            return Number(candidate.timestamp) === Number(point.timestamp);
        });
        if (existingIndex >= 0) {
            points[existingIndex] = point;
        } else {
            points.push(point);
        }
    }

    function clipPoints(points, startTimestamp, endTimestamp) {
        var sorted = (points || []).filter(function (point) {
            return Number.isFinite(Number(point.timestamp)) && Number.isFinite(Number(point.value));
        }).map(function (point) {
            return {timestamp: Number(point.timestamp), value: Number(point.value)};
        }).sort(function (left, right) {
            return left.timestamp - right.timestamp;
        });
        var clipped = sorted.filter(function (point) {
            return point.timestamp >= startTimestamp && point.timestamp <= endTimestamp;
        });
        addUniquePoint(clipped, interpolatePoint(sorted, startTimestamp));
        addUniquePoint(clipped, interpolatePoint(sorted, endTimestamp));
        return clipped.sort(function (left, right) {
            return left.timestamp - right.timestamp;
        });
    }

    function coordinateString(points) {
        return points.map(function (point) {
            return point[0] + ',' + point[1];
        }).join(' ');
    }

    function buildAxis(scale, paddingTop, plotHeight, paddingLeft, width, paddingRight) {
        var output = [];
        for (var index = 0; index <= scale.intervals; index += 1) {
            var ratio = index / scale.intervals;
            var gridY = Math.round((paddingTop + plotHeight * ratio) * 100) / 100;
            var gridValue = scale.max - scale.step * index;
            output.push('<line class="omo-stats-chart__grid" x1="' + paddingLeft + '" y1="' + gridY + '" x2="' + (width - paddingRight) + '" y2="' + gridY + '"/>');
            output.push('<text class="omo-stats-chart__axis-label" x="' + (paddingLeft - 10) + '" y="' + (gridY + 4) + '" text-anchor="end">' + escapeXml(formatNumber(gridValue)) + '</text>');
        }
        return output.join('');
    }

    function renderIndicator(data, startDay, endDay) {
        var width = 900;
        var height = 340;
        var paddingLeft = 64;
        var paddingRight = 24;
        var paddingTop = 24;
        var paddingBottom = 42;
        var plotWidth = width - paddingLeft - paddingRight;
        var plotHeight = height - paddingTop - paddingBottom;
        var startTimestamp = startDay * 86400;
        var endTimestamp = ((endDay + 1) * 86400) - 1;
        var measure = clipPoints(data.measure, startTimestamp, endTimestamp);
        var reference = clipPoints(data.reference, startTimestamp, endTimestamp);
        var allPoints = measure.concat(reference);
        if (!allPoints.length) {
            return '<div class="omo-stats-chart-empty">Pas encore de donnees a representer.</div>';
        }
        var scale = resolveScale(allPoints);
        var mapPoint = function (point) {
            var x = paddingLeft + ((Number(point.timestamp) - startTimestamp) / (endTimestamp - startTimestamp)) * plotWidth;
            var y = paddingTop + (1 - ((Number(point.value) - scale.min) / (scale.max - scale.min))) * plotHeight;
            return [Math.round(x * 100) / 100, Math.round(y * 100) / 100];
        };
        var measureCoordinates = measure.map(mapPoint);
        var referenceCoordinates = reference.map(mapPoint);
        var chartId = 'omo-stats-interactive-' + (++chartSequence);
        var overdueClass = data.overdueSeverity === 'warning'
            ? ' omo-stats-chart--warning'
            : (data.overdue ? ' omo-stats-chart--overdue' : '');
        var classes = 'omo-stats-chart omo-stats-chart--large' + overdueClass;
        var svg = '<svg class="' + classes + '" viewBox="0 0 ' + width + ' ' + height + '" role="img" aria-label="' + escapeXml(data.label) + '">';
        svg += '<defs><linearGradient id="' + chartId + '-area" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="currentColor" stop-opacity="0.24"/><stop offset="1" stop-color="currentColor" stop-opacity="0.02"/></linearGradient></defs>';
        svg += buildAxis(scale, paddingTop, plotHeight, paddingLeft, width, paddingRight);
        svg += '<text class="omo-stats-chart__axis-label" x="' + paddingLeft + '" y="' + (height - 12) + '">' + formatDay(startDay) + '</text>';
        svg += '<text class="omo-stats-chart__axis-label" x="' + (width - paddingRight) + '" y="' + (height - 12) + '" text-anchor="end">' + formatDay(endDay) + '</text>';
        if (measureCoordinates.length) {
            var areaPoints = coordinateString(measureCoordinates)
                + ' ' + measureCoordinates[measureCoordinates.length - 1][0] + ',' + (paddingTop + plotHeight)
                + ' ' + measureCoordinates[0][0] + ',' + (paddingTop + plotHeight);
            svg += '<polygon class="omo-stats-chart__area" points="' + areaPoints + '" fill="url(#' + chartId + '-area)"/>';
            if (measureCoordinates.length > 1) {
                svg += '<polyline class="omo-stats-chart__line" points="' + coordinateString(measureCoordinates) + '"/>';
            }
            measureCoordinates.forEach(function (point, pointIndex) {
                var sourcePoint = measure[pointIndex];
                svg += '<circle class="omo-stats-chart__point" cx="' + point[0] + '" cy="' + point[1] + '" r="4"' + buildPointTooltipAttributes(data, sourcePoint) + '/>';
            });
        }
        if (referenceCoordinates.length > 1) {
            svg += '<polyline class="omo-stats-chart__reference" points="' + coordinateString(referenceCoordinates) + '"/>';
        }
        return svg + '</svg>';
    }

    function renderGroup(data, startDay, endDay) {
        var width = 900;
        var height = 340;
        var paddingLeft = 64;
        var paddingRight = 24;
        var paddingTop = 24;
        var paddingBottom = 42;
        var plotWidth = width - paddingLeft - paddingRight;
        var plotHeight = height - paddingTop - paddingBottom;
        var startTimestamp = startDay * 86400;
        var endTimestamp = ((endDay + 1) * 86400) - 1;
        var series = (data.series || []).map(function (seriesItem) {
            return {
                points: clipPoints(seriesItem.points, startTimestamp, endTimestamp),
                background: Boolean(seriesItem.background),
                sum: Boolean(seriesItem.sum),
                sourceIndex: Number(seriesItem.sourceIndex || 0)
            };
        }).filter(function (seriesItem) { return seriesItem.points.length > 0; });
        var reference = clipPoints(data.reference || [], startTimestamp, endTimestamp);
        var allPoints = [];
        series.forEach(function (seriesItem) { allPoints = allPoints.concat(seriesItem.points); });
        allPoints = allPoints.concat(reference);
        if (!allPoints.length) {
            return '<div class="omo-stats-chart-empty">Pas encore de donnees a representer.</div>';
        }
        var scale = resolveScale(allPoints);
        var mapPoint = function (point) {
            var x = paddingLeft + ((Number(point.timestamp) - startTimestamp) / (endTimestamp - startTimestamp)) * plotWidth;
            var y = paddingTop + (1 - ((Number(point.value) - scale.min) / (scale.max - scale.min))) * plotHeight;
            return [Math.round(x * 100) / 100, Math.round(y * 100) / 100];
        };
        var chartId = 'omo-stats-interactive-' + (++chartSequence);
        var overdueClass = data.overdueSeverity === 'warning'
            ? ' omo-stats-chart--warning'
            : (data.overdue ? ' omo-stats-chart--overdue' : '');
        var classes = 'omo-stats-chart omo-stats-chart--large omo-stats-chart--group' + overdueClass;
        var svg = '<svg class="' + classes + '" viewBox="0 0 ' + width + ' ' + height + '" role="img" aria-label="' + escapeXml(data.label) + '">';
        svg += buildAxis(scale, paddingTop, plotHeight, paddingLeft, width, paddingRight);
        svg += '<text class="omo-stats-chart__axis-label" x="' + paddingLeft + '" y="' + (height - 12) + '">' + formatDay(startDay) + '</text>';
        svg += '<text class="omo-stats-chart__axis-label" x="' + (width - paddingRight) + '" y="' + (height - 12) + '" text-anchor="end">' + formatDay(endDay) + '</text>';
        series.forEach(function (seriesItem) {
            var coordinates = seriesItem.points.map(mapPoint);
            var color = seriesItem.sum ? groupColors[0] : groupColors[seriesItem.sourceIndex % groupColors.length];
            var lineClass = 'omo-stats-chart__line' + (seriesItem.background ? ' omo-stats-chart__line--background' : '') + (seriesItem.sum ? ' omo-stats-chart__line--sum' : '');
            if (coordinates.length > 1) {
                svg += '<polyline class="' + lineClass + '" style="stroke:' + color + '" points="' + coordinateString(coordinates) + '"/>';
            }
            if (!seriesItem.background) {
                coordinates.forEach(function (point, pointIndex) {
                    svg += '<circle class="omo-stats-chart__point" style="stroke:' + color + '" cx="' + point[0] + '" cy="' + point[1] + '" r="4"' + buildPointTooltipAttributes(data, seriesItem.points[pointIndex]) + '/>';
                });
            }
        });
        var referenceCoordinates = reference.map(mapPoint);
        if (referenceCoordinates.length > 1) {
            svg += '<polyline class="omo-stats-chart__reference" points="' + coordinateString(referenceCoordinates) + '"/>';
        }
        return svg + '</svg>';
    }

    function render(container, data, startDay, endDay) {
        var rendered = data.type === 'group'
            ? renderGroup(data, startDay, endDay)
            : renderIndicator(data, startDay, endDay);
        var currentChart = container.querySelector('svg.omo-stats-chart, .omo-stats-chart-empty');
        if (currentChart) {
            currentChart.outerHTML = rendered;
        }
        bindPointTooltips(container);
    }

    var activeTooltipPoint = null;

    function getChartTooltip() {
        var tooltip = document.getElementById('tooltip');
        if (!tooltip) {
            tooltip = document.createElement('div');
            tooltip.id = 'omo-stats-chart-tooltip';
            document.body.appendChild(tooltip);
        }
        tooltip.classList.add('omo-stats-chart-tooltip');
        return tooltip;
    }

    function positionChartTooltip(tooltip, point, event) {
        var pointRect = point.getBoundingClientRect();
        var clientX = Number(event && event.clientX) || (pointRect.left + pointRect.width / 2);
        var clientY = Number(event && event.clientY) || (pointRect.top + pointRect.height / 2);
        var tooltipRect = tooltip.getBoundingClientRect();
        var left = clientX + 12;
        var top = clientY + 12;
        if (left + tooltipRect.width + 8 > window.innerWidth) {
            left = clientX - tooltipRect.width - 12;
        }
        if (top + tooltipRect.height + 8 > window.innerHeight) {
            top = clientY - tooltipRect.height - 12;
        }
        tooltip.style.left = Math.max(8, Math.round(left)) + 'px';
        tooltip.style.top = Math.max(8, Math.round(top)) + 'px';
    }

    function showChartTooltip(point, event) {
        var tooltip = getChartTooltip();
        var text = point.getAttribute('data-omo-stats-chart-tooltip') || '';
        if (!text) {
            return;
        }
        activeTooltipPoint = point;
        tooltip.textContent = text;
        tooltip.classList.add('visible', 'is-visible');
        positionChartTooltip(tooltip, point, event);
    }

    function hideChartTooltip(point) {
        if (activeTooltipPoint !== point) {
            return;
        }
        var tooltip = document.getElementById('tooltip') || document.getElementById('omo-stats-chart-tooltip');
        if (tooltip) {
            tooltip.classList.remove('visible', 'is-visible');
        }
        activeTooltipPoint = null;
    }

    function bindPointTooltips(container) {
        Array.prototype.forEach.call(container.querySelectorAll('.omo-stats-chart__point[data-omo-stats-chart-tooltip]'), function (point) {
            if (point.__omoStatsTooltipReady) {
                return;
            }
            point.__omoStatsTooltipReady = true;
            point.removeAttribute('title');
            point.addEventListener('pointerenter', function (event) {
                showChartTooltip(point, event);
            });
            point.addEventListener('pointermove', function (event) {
                if (activeTooltipPoint === point) {
                    positionChartTooltip(getChartTooltip(), point, event);
                }
            });
            point.addEventListener('pointerleave', function () {
                hideChartTooltip(point);
            });
            point.addEventListener('focus', function (event) {
                showChartTooltip(point, event);
            });
            point.addEventListener('blur', function () {
                hideChartTooltip(point);
            });
        });
    }

    function initializeRange(range) {
        var container = range.closest('[data-omo-stats-interactive-chart]');
        if (!container || range.__omoStatsChartReady) {
            return;
        }
        var data;
        try {
            data = JSON.parse(range.getAttribute('data-omo-stats-chart-data') || '{}');
        } catch (error) {
            return;
        }
        var startInput = range.querySelector('[data-omo-stats-chart-range-start]');
        var endInput = range.querySelector('[data-omo-stats-chart-range-end]');
        var output = range.querySelector('[data-omo-stats-chart-range-output]');
        var minLabel = range.querySelector('[data-omo-stats-chart-range-min]');
        var maxLabel = range.querySelector('[data-omo-stats-chart-range-max]');
        var selection = range.querySelector('[data-omo-stats-chart-range-selection]');
        if (!startInput || !endInput) {
            return;
        }
        range.__omoStatsChartReady = true;
        var minDay = Number(range.getAttribute('data-start-day') || startInput.min);
        var maxDay = Number(range.getAttribute('data-end-day') || endInput.max);
        var initialStartDay = Number(range.getAttribute('data-initial-start-day'));
        var initialEndDay = Number(range.getAttribute('data-initial-end-day'));

        if (Number.isFinite(initialStartDay) && Number.isFinite(initialEndDay)) {
            initialStartDay = Math.max(minDay, Math.min(maxDay, initialStartDay));
            initialEndDay = Math.max(initialStartDay, Math.min(maxDay, initialEndDay));
            startInput.value = String(initialStartDay);
            endInput.value = String(initialEndDay);
        }

        function update(activeInput) {
            if (Number(startInput.value) > Number(endInput.value)) {
                if (activeInput === startInput) {
                    startInput.value = endInput.value;
                } else {
                    endInput.value = startInput.value;
                }
            }
            var startDay = Number(startInput.value);
            var endDay = Number(endInput.value);
            startInput.setAttribute('aria-valuetext', formatDay(startDay));
            endInput.setAttribute('aria-valuetext', formatDay(endDay));
            if (output) {
                output.textContent = formatDay(startDay) + ' - ' + formatDay(endDay);
            }
            if (minLabel) {
                minLabel.textContent = formatDay(minDay);
            }
            if (maxLabel) {
                maxLabel.textContent = formatDay(maxDay);
            }
            if (selection) {
                var total = Math.max(1, maxDay - minDay);
                selection.style.left = (((startDay - minDay) / total) * 100) + '%';
                selection.style.right = (((maxDay - endDay) / total) * 100) + '%';
            }
            render(container, data, startDay, endDay);
        }

        startInput.addEventListener('input', function () { update(startInput); });
        endInput.addEventListener('input', function () { update(endInput); });
        update(null);
    }

    function initialize(root) {
        var scope = root && root.querySelectorAll ? root : document;
        Array.prototype.forEach.call(scope.querySelectorAll('[data-omo-stats-interactive-chart]'), function (container) {
            var range = container.querySelector('[data-omo-stats-chart-range]');
            if (range) {
                initializeRange(range);
            }
            bindPointTooltips(container);
        });
    }

    window.omoStatsInitInteractiveCharts = initialize;
    initialize(document);
}());
