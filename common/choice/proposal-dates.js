(function () {
    'use strict';
    if (window.omoProposalDates) return;
    window.omoProposalDates = function (prefix, array, enabled) {
        var template = document.querySelector('template[data-omo-proposal-dates-template]');
        if (!template) return '';
        var wrapper = document.createElement('div');
        wrapper.appendChild(template.content.cloneNode(true));
        wrapper.querySelectorAll('input[name]').forEach(function (input) {
            input.name = (prefix === undefined ? 'proposal_' : prefix) + input.name.replace(/^proposal_/, '').replace(/\[\]$/, '') + (array === false ? '' : '[]');
        });
        window.omoProposalDates.setEnabled(wrapper.querySelector('[data-omo-proposal-dates]'), enabled === true);
        return wrapper.innerHTML;
    };
    function field(root, key) {
        return root.querySelector('[data-omo-proposal-date-' + key + ']');
    }
    function updateState(root) {
        var multiple = field(root, 'multiple').checked;
        field(root, 'single').hidden = multiple;
        field(root, 'range').hidden = !multiple;
        var active = multiple ? ['start', 'end'] : ['day', 'start_time', 'end_time'];
        var hasDate = active.some(function (key) { return Boolean(field(root, key).value); });
        ['day', 'start_time', 'end_time', 'start', 'end'].forEach(function (key) {
            var input = field(root, key);
            input.disabled = root.hidden || field(root, 'multiple').disabled || active.indexOf(key) < 0;
            input.required = hasDate && !input.disabled;
        });
        field(root, 'end').min = field(root, 'start').value;
        field(root, 'end_time').min = field(root, 'start_time').value;
        field(root, 'clear').hidden = !hasDate;
        root.querySelector('[data-omo-proposal-timezone-label]').textContent = root.querySelector('[data-omo-proposal-timezone]').value;
    }
    // Only the two hidden datetime values and the timezone are submitted, in both modes.
    function syncValues(root) {
        var multiple = field(root, 'multiple').checked;
        ['start', 'end'].forEach(function (key) {
            var day = field(root, 'day').value;
            var time = field(root, key + '_time').value;
            field(root, 'value-' + key).value = multiple ? field(root, key).value : (day && time ? day + 'T' + time : '');
        });
        updateState(root);
    }
    window.omoProposalDates.refresh = function (root) {
        if (!root) return;
        var start = root.querySelector('[data-omo-proposal-date-start]');
        var end = root.querySelector('[data-omo-proposal-date-end]');
        if (!start || !end) return;
        start.value = field(root, 'value-start').value;
        end.value = field(root, 'value-end').value;
        field(root, 'day').value = start.value.slice(0, 10);
        field(root, 'start_time').value = start.value.slice(11, 16);
        field(root, 'end_time').value = end.value.slice(11, 16);
        field(root, 'multiple').checked = Boolean(start.value && end.value && start.value.slice(0, 10) !== end.value.slice(0, 10));
        updateState(root);
    };
    window.omoProposalDates.setEnabled = function (root, enabled) {
        if (!root) return;
        root.hidden = !enabled;
        updateState(root);
    };
    window.omoProposalDates.replaceCalendar = function (panel, html) {
        var template = document.createElement('template');
        template.innerHTML = html;
        var replacement = template.content.querySelector('[data-omo-proposal-calendar]');
        if (replacement) panel.replaceWith(replacement);
        else panel.hidden = true;
    };
    document.querySelectorAll('[data-omo-proposal-dates]').forEach(window.omoProposalDates.refresh);
    function onInput(event) {
        var root = event.target.closest('[data-omo-proposal-dates]');
        if (!root) return;
        if (event.target === field(root, 'multiple')) {
            if (event.type !== 'change') return;
            if (event.target.checked) {
                field(root, 'start').value = field(root, 'value-start').value;
                field(root, 'end').value = field(root, 'value-end').value;
            } else {
                field(root, 'day').value = field(root, 'start').value.slice(0, 10);
                field(root, 'start_time').value = field(root, 'start').value.slice(11, 16);
                field(root, 'end_time').value = field(root, 'end').value.slice(11, 16);
            }
        }
        syncValues(root);
    }
    document.addEventListener('input', onInput);
    document.addEventListener('change', onInput);
    document.addEventListener('click', function (event) {
        var clear = event.target.closest('[data-omo-proposal-date-clear]');
        if (clear && !clear.disabled) {
            var root = clear.closest('[data-omo-proposal-dates]');
            ['day', 'start_time', 'end_time', 'start', 'end', 'value-start', 'value-end'].forEach(function (key) { field(root, key).value = ''; });
            field(root, 'multiple').checked = false;
            updateState(root);
            root.dispatchEvent(new Event('change', {bubbles: true}));
            return;
        }
        var button = event.target.closest('[data-omo-proposal-calendar-action]');
        if (!button || button.disabled) return;
        var body = new FormData();
        var context;
        try { context = JSON.parse(button.getAttribute('data-proposal-context') || '{}'); } catch (error) { return; }
        Object.keys(context).forEach(function (key) { if (typeof context[key] !== 'object') body.append(key, context[key]); });
        body.append('proposal_id', button.getAttribute('data-proposal-id'));
        body.append('calendar_status', button.getAttribute('data-omo-proposal-calendar-action'));
        var buttons = Array.from(document.querySelectorAll('[data-omo-proposal-calendar-action]')).map(function (action) {
            var state = {button: action, disabled: action.disabled};
            action.disabled = true;
            return state;
        });
        fetch('/omo/api/decision/modules/proposals/calendar.php', {method: 'POST', body: body, credentials: 'same-origin'})
            .then(function (response) { return response.json(); })
            .then(function (payload) {
                if (!payload.status) throw new Error(payload.message);
                window.commonNotify(payload.message, 'success');
                document.querySelectorAll('[data-omo-proposal-calendar]').forEach(function (panel) {
                    var id = panel.getAttribute('data-proposal-calendar-id');
                    if (Object.prototype.hasOwnProperty.call(payload.calendars || {}, id)) {
                        window.omoProposalDates.replaceCalendar(panel, payload.calendars[id]);
                    }
                });
            })
            .catch(function (error) { window.commonNotify(error.message, 'error'); })
            .finally(function () { buttons.forEach(function (state) { state.button.disabled = state.disabled; }); });
    });
})();
