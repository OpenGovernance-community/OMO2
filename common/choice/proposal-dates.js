(function () {
    'use strict';
    if (window.omoProposalDates) return;
    window.omoProposalDates = function (prefix, array, enabled) {
        var template = document.querySelector('template[data-omo-proposal-dates-template]');
        if (!template) return '';
        var wrapper = document.createElement('div');
        wrapper.appendChild(template.content.cloneNode(true));
        wrapper.querySelectorAll('input').forEach(function (input) {
            input.name = (prefix === undefined ? 'proposal_' : prefix) + input.name.replace(/^proposal_/, '').replace(/\[\]$/, '') + (array === false ? '' : '[]');
        });
        window.omoProposalDates.setEnabled(wrapper.querySelector('[data-omo-proposal-dates]'), enabled !== false);
        return wrapper.innerHTML;
    };
    window.omoProposalDates.refresh = function (root) {
        if (!root) return;
        var start = root.querySelector('[data-omo-proposal-date-start]');
        var end = root.querySelector('[data-omo-proposal-date-end]');
        if (!start || !end) return;
        var hasDate = Boolean(start.value || end.value);
        start.required = end.required = hasDate && !root.hidden && !start.disabled;
        end.min = start.value;
        root.querySelector('[data-omo-proposal-date-clear]').hidden = !hasDate;
        root.querySelector('[data-omo-proposal-timezone-label]').textContent = root.querySelector('[data-omo-proposal-timezone]').value;
    };
    window.omoProposalDates.setEnabled = function (root, enabled) {
        if (!root) return;
        root.hidden = !enabled;
        window.omoProposalDates.refresh(root);
    };
    window.omoProposalDates.replaceCalendar = function (panel, html) {
        var template = document.createElement('template');
        template.innerHTML = html;
        var replacement = template.content.querySelector('[data-omo-proposal-calendar]');
        if (replacement) panel.replaceWith(replacement);
        else panel.hidden = true;
    };
    document.querySelectorAll('[data-omo-proposal-dates]').forEach(window.omoProposalDates.refresh);
    document.addEventListener('input', function (event) {
        var root = event.target.closest('[data-omo-proposal-dates]');
        if (root) window.omoProposalDates.refresh(root);
    });
    document.addEventListener('click', function (event) {
        var clear = event.target.closest('[data-omo-proposal-date-clear]');
        if (clear && !clear.disabled) {
            var root = clear.closest('[data-omo-proposal-dates]');
            root.querySelectorAll('input[type="datetime-local"]').forEach(function (input) {
                input.value = '';
                input.dispatchEvent(new Event('input', {bubbles: true}));
                input.dispatchEvent(new Event('change', {bubbles: true}));
            });
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
