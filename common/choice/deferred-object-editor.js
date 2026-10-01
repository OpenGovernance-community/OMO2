(function (window) {
    'use strict';

    function stateFromForm(form, holonId) {
        var state = {IDholon: Number(holonId || 0), show_cumulative: 0};
        new FormData(form).forEach(function (value, name) {
            var pointMatch = /^reference_points\[(\d+)\]\[(position_percent|point_at|value)\]$/.exec(name);
            if (pointMatch) {
                var index = Number(pointMatch[1]);
                if (!state.reference_points) state.reference_points = [];
                if (!state.reference_points[index]) state.reference_points[index] = {};
                state.reference_points[index][pointMatch[2]] = String(value);
            } else if (!['id', 'oid', 'cid', 'activity_action', 'stats_action', 'point_id', 'proposal_id', 'target_type', 'operation', 'holon_id', 'object_id'].includes(name)) {
                state[name] = String(value);
            }
        });
        if (state.reference_points) state.reference_points = state.reference_points.filter(Boolean);
        return state;
    }

    function initActivity(root, form) {
        var frequency = form.querySelector('[data-activity-frequency]');
        var schedule = form.querySelector('[data-activity-schedule]');
        var options = {};
        try { options = JSON.parse(form.getAttribute('data-activity-schedule-options') || '{}'); } catch (error) {}
        function syncSchedule(reset) {
            if (!frequency || !schedule) return;
            var selected = reset ? '' : (schedule.getAttribute('data-selected-value') || schedule.value);
            schedule.innerHTML = '';
            (options[frequency.value] || []).forEach(function (entry) {
                var option = document.createElement('option');
                option.value = entry.value;
                option.textContent = entry.label;
                schedule.appendChild(option);
            });
            if (selected && Array.prototype.some.call(schedule.options, function (option) { return option.value === selected; })) schedule.value = selected;
            schedule.removeAttribute('data-selected-value');
        }
        if (frequency) frequency.addEventListener('change', function () { syncSchedule(true); });
        syncSchedule(false);
        var host = form.querySelector('[data-activity-html-editor]');
        var valueField = form.querySelector('[data-activity-html-value]');
        if (host && valueField && window.omoSimpleHtmlField && typeof window.omoSimpleHtmlField.mount === 'function') {
            window.omoSimpleHtmlField.mount(host, {
                value: valueField.value || '', placeholder: '', minHeight: 120, simpleOnly: true,
                onChange: function (value) { valueField.value = String(value || ''); },
                onReady: function (api) { if (api && api.getValue) valueField.value = String(api.getValue() || ''); }
            });
        } else if (valueField) {
            valueField.hidden = false;
            valueField.removeAttribute('aria-hidden');
        }
    }

    function init(root, config) {
        if (!root || root.dataset.deferredObjectReady === '1') return;
        root.dataset.deferredObjectReady = '1';
        var form = root.querySelector('[data-deferred-object-form]');
        if (!form) return;
        if (config.targetType === 'recurring_task') initActivity(root, form);
        var cancel = root.querySelector('[data-deferred-object-cancel]');
        if (cancel) cancel.addEventListener('click', function () {
            if (config.origin === 'pv') window.dispatchEvent(new CustomEvent('omo-deferred-proposal-editor-back'));
            if (window.commonTopbarCloseModal) window.commonTopbarCloseModal();
        });
        var saving = false;
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            event.stopImmediatePropagation();
            if (saving || !form.reportValidity()) return;
            if (config.targetType === 'indicator') {
                var sourceType = form.querySelector('[name="source_type"]')?.value || 'manual';
                var columnsField = sourceType === 'ethercalc_table' ? form.querySelector('[name="ethercalc_value_columns"]')
                    : (sourceType === 'spreadsheet_table' ? form.querySelector('[name="spreadsheet_value_columns"]') : null);
                if (columnsField && columnsField.value.split(',').filter(function (column) { return column.trim() !== ''; }).length !== 1) {
                    columnsField.setCustomValidity('Une modification différée peut porter sur une seule colonne de valeurs.');
                    columnsField.reportValidity();
                    columnsField.addEventListener('input', function () { columnsField.setCustomValidity(''); }, {once: true});
                    return;
                }
            }
            var htmlHost = form.querySelector('[data-activity-html-editor]');
            var htmlField = form.querySelector('[data-activity-html-value]');
            if (htmlHost && htmlField && htmlHost.__omoSimpleHtmlField && htmlHost.__omoSimpleHtmlField.getValue) htmlField.value = String(htmlHost.__omoSimpleHtmlField.getValue() || '');
            var state = stateFromForm(form, config.holonId);
            if (config.origin === 'governance') {
                window.dispatchEvent(new CustomEvent('omo-deferred-object-capture', {detail: {state: state, targetType: config.targetType}}));
                return;
            }
            var data = new FormData(form);
            if (config.targetType === 'indicator') data.set('show_cumulative', form.querySelector('[name="show_cumulative"]')?.checked ? '1' : '0');
            saving = true;
            fetch(form.action, {method: 'POST', body: data, credentials: 'same-origin'}).then(function (response) {
                return response.json().then(function (result) { return {ok: response.ok, result: result}; });
            }).then(function (reply) {
                if (!reply.ok || !reply.result.status) throw new Error(reply.result.message || 'Enregistrement impossible.');
                window.dispatchEvent(new CustomEvent('omo-deferred-proposal-saved', {detail: {pointId: Number(reply.result.pointId || config.pointId), proposalId: Number(reply.result.id || 0)}}));
                if (window.commonTopbarCloseModal) window.commonTopbarCloseModal();
            }).catch(function (error) {
                if (window.commonNotify) window.commonNotify(error.message || 'Enregistrement impossible.', 'error');
                else {
                    var feedback = root.querySelector('[data-activity-feedback], [data-omo-stats-editor-feedback]');
                    if (feedback) feedback.textContent = error.message || 'Enregistrement impossible.';
                }
            }).finally(function () { saving = false; });
        }, true);
    }

    window.omoDeferredObjectEditorInit = init;
})(window);
