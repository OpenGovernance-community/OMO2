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

    var previewStates = new WeakMap();

    function previewState(form) {
        var state = previewStates.get(form);
        if (!state) {
            state = {dirty: true, month: '', date: '', anchor: '', request: 0, controller: null};
            previewStates.set(form, state);
        }
        return state;
    }

    function previewHost(form) {
        return form.querySelector('[data-omo-calendar-preview-host]');
    }

    function markPreviewDirty(event) {
        var form = event.target.closest('[data-omo-calendar-create-form]');
        if (!form || !previewHost(form)) { return; }
        var field = event.target;
        if (!field.closest('[data-omo-calendar-invitations-editor]')
            && !field.matches('[name="IDholon"], [name="start_at"], [name="end_at"]')) { return; }
        if (field.closest('[data-omo-calendar-invitations-editor]')
            && !field.matches('[name^="invitation_"]')) { return; }
        var state = previewState(form);
        state.dirty = true;
        state.request++;
        if (state.controller) { state.controller.abort(); state.controller = null; }
        if (field.matches('[name="start_at"]')) {
            state.date = String(field.value || '').slice(0, 10);
            state.month = state.date.slice(0, 7);
            state.anchor = '';
        }
    }

    function syncPreviewSelectedSlots(form, state) {
        var host = previewHost(form);
        if (!host) { return; }
        var startField = form.querySelector('[name="start_at"]');
        var endField = form.querySelector('[name="end_at"]');
        var start = startField ? String(startField.value || '').slice(0, 16) : '';
        var end = endField ? String(endField.value || '').slice(0, 16) : '';
        host.querySelectorAll('button[data-omo-calendar-preview-slot-start]').forEach(function (slot) {
            var slotStart = slot.dataset.omoCalendarPreviewSlotStart;
            var slotEnd = slot.dataset.omoCalendarPreviewSlotEnd;
            var selected = start && end && slotStart >= start && slotEnd <= end;
            slot.setAttribute('aria-pressed', selected ? 'true' : 'false');
            slot.classList.toggle('is-anchor', slotStart === state.anchor);
        });
    }

    function setPreviewSelectionFeedback(host, message, isError) {
        var feedback = host.querySelector('[data-omo-calendar-preview-selection-feedback]');
        if (!feedback) { return; }
        feedback.textContent = message;
        feedback.classList.toggle('is-error', !!isError);
    }

    function choosePreviewSlot(form, slot, extend) {
        var host = previewHost(form);
        var state = previewState(form);
        var slots = Array.from(host.querySelectorAll('[data-omo-calendar-preview-slot-start]'));
        var targetIndex = slots.indexOf(slot);
        var anchorIndex = extend ? slots.findIndex(function (item) {
            return item.dataset.omoCalendarPreviewSlotStart === state.anchor;
        }) : -1;
        if (targetIndex < 0) { return; }
        if (anchorIndex < 0) { anchorIndex = targetIndex; }
        var first = Math.min(anchorIndex, targetIndex);
        var last = Math.max(anchorIndex, targetIndex);
        for (var index = first; index <= last; index++) {
            if (slots[index].dataset.state !== 'free'
                || (index > first && slots[index - 1].dataset.omoCalendarPreviewSlotEnd !== slots[index].dataset.omoCalendarPreviewSlotStart)) {
                var feedback = host.querySelector('[data-omo-calendar-preview-selection-feedback]');
                setPreviewSelectionFeedback(host, feedback ? feedback.dataset.rangeBlocked || '' : '', true);
                return;
            }
        }
        var startValue = slots[first].dataset.omoCalendarPreviewSlotStart;
        var endValue = slots[last].dataset.omoCalendarPreviewSlotEnd;
        var startField = form.querySelector('[name="start_at"]');
        var endField = form.querySelector('[name="end_at"]');
        if (!startField || !endField) { return; }
        startField.value = startValue;
        endField.value = endValue;
        var allDay = form.querySelector('[name="is_all_day"]');
        if (allDay && allDay.checked) {
            allDay.checked = false;
            allDay.dispatchEvent(new Event('change', {bubbles: true}));
        }
        form.dataset.omoCalendarLastStart = startValue;
        form.dataset.omoCalendarLastEnd = endValue;
        startField.dispatchEvent(new Event('input', {bubbles: true}));
        endField.dispatchEvent(new Event('input', {bubbles: true}));
        state.anchor = anchorIndex === targetIndex ? slot.dataset.omoCalendarPreviewSlotStart : slots[anchorIndex].dataset.omoCalendarPreviewSlotStart;
        state.date = startValue.slice(0, 10);
        state.month = state.date.slice(0, 7);
        state.dirty = false;
        syncPreviewSelectedSlots(form, state);
        var success = host.querySelector('[data-omo-calendar-preview-selection-feedback]');
        setPreviewSelectionFeedback(host, success ? success.dataset.rangeSelected || '' : '', false);
    }

    function loadPreview(form, force) {
        var host = previewHost(form);
        if (!host) { return; }
        var state = previewState(form);
        if (!force && !state.dirty && host.dataset.loaded === '1') { return; }
        if (state.controller) { state.controller.abort(); }
        state.controller = new AbortController();
        var request = ++state.request;
        var start = form.querySelector('[name="start_at"]');
        var initialDate = start ? String(start.value || '').slice(0, 10) : '';
        state.date = state.date || initialDate;
        if (!state.month) {
            var now = new Date();
            state.month = state.date.slice(0, 7) || (now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0'));
        }
        var data = new FormData();
        data.set('availability_preview', '1');
        data.set('month', state.month);
        data.set('date', state.date);
        var context = form.querySelector('[name="IDholon"]');
        if (context) { data.set('IDholon', context.value); }
        form.querySelectorAll('[data-omo-calendar-invitations-editor] input:checked').forEach(function (field) {
            if (field.name === 'invitation_holon_ids[]' || field.name === 'invitation_user_ids[]') {
                data.append(field.name, field.value);
            }
        });
        var emails = form.querySelector('[name="invitation_emails"]');
        if (emails) { data.set('invitation_emails', emails.value); }
        host.replaceChildren();
        var loading = document.createElement('div');
        loading.className = 'omo-calendar-create__preview-loading';
        loading.setAttribute('role', 'status');
        loading.textContent = host.dataset.loadingLabel || '';
        host.appendChild(loading);
        host.setAttribute('aria-busy', 'true');
        fetch(form.action, {
            method: 'POST', credentials: 'same-origin', body: data,
            headers: {'X-Requested-With': 'XMLHttpRequest'}, signal: state.controller.signal
        }).then(function (response) {
            return response.text().then(function (html) {
                if (!response.ok) { throw new Error(response.status === 422 ? html : ''); }
                return html;
            });
        }).then(function (html) {
            if (request !== state.request || !form.isConnected) { return; }
            host.innerHTML = html;
            host.dataset.loaded = '1';
            state.dirty = false;
            var startField = form.querySelector('[name="start_at"]');
            var startValue = startField ? String(startField.value || '').slice(0, 16) : '';
            state.anchor = host.querySelector('button[data-omo-calendar-preview-slot-start="' + startValue + '"]') ? startValue : '';
            syncPreviewSelectedSlots(form, state);
        }).catch(function (error) {
            if (error.name === 'AbortError' || request !== state.request) { return; }
            host.innerHTML = error.message || '';
            if (!host.textContent.trim()) { host.textContent = host.dataset.errorLabel || ''; }
            host.dataset.loaded = '0';
            state.dirty = true;
        }).finally(function () {
            if (request === state.request) {
                state.controller = null;
                host.removeAttribute('aria-busy');
            }
        });
    }

    document.addEventListener('input', markPreviewDirty);
    document.addEventListener('change', markPreviewDirty);
    document.addEventListener('click', function (event) {
        var slot = event.target.closest('button[data-omo-calendar-preview-slot-start]');
        if (slot) {
            var slotForm = slot.closest('[data-omo-calendar-create-form]');
            if (slotForm) { choosePreviewSlot(slotForm, slot, event.shiftKey); }
            return;
        }
        var tab = event.target.closest('[data-omo-calendar-preview-tab]');
        if (tab) {
            var form = tab.closest('[data-omo-calendar-create-form]');
            if (form) { loadPreview(form, false); }
            return;
        }
        var control = event.target.closest('[data-omo-calendar-preview-target]');
        if (!control) { return; }
        var editor = control.closest('[data-omo-calendar-create-form]');
        if (!editor) { return; }
        event.preventDefault();
        var target = new URLSearchParams(control.getAttribute('data-omo-calendar-preview-target') || '');
        var state = previewState(editor);
        state.month = target.get('month') || state.month;
        state.date = target.get('date') || '';
        loadPreview(editor, true);
    });
})(window, document);
