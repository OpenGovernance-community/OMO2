(function (window, document) {
    'use strict';
    if (window.omoCalendarShowAvailability) { return; }

    function dateLabel(value) {
        var parts = String(value || '').match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (!parts) { return value; }
        // These are server-local wall-clock dates, not instants to convert to the visitor's zone.
        return new Intl.DateTimeFormat(document.documentElement.lang || 'fr', {
            day: '2-digit', month: '2-digit', year: 'numeric', timeZone: 'UTC'
        }).format(new Date(Date.UTC(Number(parts[1]), Number(parts[2]) - 1, Number(parts[3]))));
    }

    function appendText(parent, className, value) {
        var element = document.createElement('span');
        element.className = className;
        element.textContent = value;
        parent.appendChild(element);
    }

    window.omoCalendarSetAvailabilityPending = function (form, pending) {
        var loading = form.querySelector('[data-calendar-availability-loading]');
        var panel = form.querySelector('[data-calendar-availability]');
        if (loading) { loading.hidden = !pending; }
        if (pending && panel) {
            panel.hidden = true;
            delete panel.dataset.acknowledgement;
        }
        form.setAttribute('aria-busy', pending ? 'true' : 'false');
    };

    window.omoCalendarShowAvailability = function (form, payload) {
        var panel = form.querySelector('[data-calendar-availability]');
        if (!panel || !payload || !payload.availability) { return false; }
        var report = payload.availability;
        var list = panel.querySelector('[data-calendar-availability-messages]');
        list.replaceChildren();
        (report.items || (report.messages || []).map(function (message) { return {detail: message}; })).forEach(function (item) {
            var row = document.createElement('div');
            row.className = 'generic-soft-panel calendar-availability__person' + (item.kind === 'conflict' ? ' is-conflict' : '');
            row.setAttribute('role', 'listitem');
            if (item.kind === 'conflict') {
                var warning = document.createElement('span');
                warning.className = 'calendar-availability__conflict';
                var icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                icon.setAttribute('viewBox', '0 0 24 24');
                icon.setAttribute('aria-hidden', 'true');
                icon.setAttribute('focusable', 'false');
                var path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                path.setAttribute('d', 'M12 3 2 21h20L12 3Z M12 9v5 M12 17v.5');
                icon.appendChild(path);
                warning.appendChild(icon);
                appendText(warning, '', item.label);
                row.appendChild(warning);
            }
            if (item.name) { appendText(row, 'calendar-availability__name', item.name); }
            if (item.label && item.kind !== 'conflict') { appendText(row, 'calendar-availability__label', item.label); }
            if (item.kind === 'conflict') {
                if (item.context) { appendText(row, 'calendar-availability__context', item.context); }
                var sameDay = item.start.slice(0, 10) === item.end.slice(0, 10);
                appendText(row, 'calendar-availability__time', sameDay
                    ? dateLabel(item.start) + ' \u00b7 ' + item.start.slice(11, 16) + ' \u2013 ' + item.end.slice(11, 16)
                    : dateLabel(item.start) + ' ' + item.start.slice(11, 16) + ' \u2192 ' + dateLabel(item.end) + ' ' + item.end.slice(11, 16));
            } else if (item.detail) {
                appendText(row, 'calendar-availability__detail', item.detail);
            }
            list.appendChild(row);
        });
        // Only the explicit override button sends the acknowledgement.
        panel.dataset.acknowledgement = report.acknowledgement || '';
        panel.hidden = false;
        panel.focus();
        panel.scrollIntoView({block: 'nearest'});
        return true;
    };

    document.addEventListener('click', function (event) {
        var adjust = event.target.closest('[data-calendar-availability-adjust]');
        if (adjust) {
            var editor = adjust.closest('form');
            var startField = editor.querySelector('[name="start_at"]');
            var tabPanel = startField && startField.closest('[data-generic-tab-panel]');
            if (tabPanel && tabPanel.id) {
                var toggle = Array.from(editor.querySelectorAll('[data-generic-tab-target]')).find(function (node) {
                    return node.getAttribute('data-generic-tab-target') === tabPanel.id;
                });
                if (toggle) { toggle.click(); }
            }
            adjust.closest('[data-calendar-availability]').hidden = true;
            editor.elements.availability_ack.value = '';
            if (startField) { startField.focus(); startField.scrollIntoView({block: 'center'}); }
            return;
        }
        var button = event.target.closest('[data-calendar-availability-confirm]');
        if (!button) { return; }
        var form = button.closest('form');
        if (!form || form.dataset.omoCalendarSubmitPending === '1') { return; }
        form.elements.availability_ack.value = button.closest('[data-calendar-availability]').dataset.acknowledgement || '';
        form.requestSubmit();
    });

    function invalidate(event) {
        var form = event.target.closest('[data-omo-calendar-create-form]');
        if (!form || !form.elements.availability_ack) { return; }
        form.elements.availability_ack.value = '';
        var panel = form.querySelector('[data-calendar-availability]');
        if (panel) { panel.hidden = true; delete panel.dataset.acknowledgement; }
    }
    document.addEventListener('input', invalidate);
    document.addEventListener('change', invalidate);
})(window, document);
