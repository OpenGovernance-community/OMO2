(function (window, document) {
    'use strict';
    if (window.omoMeetingRecurrence) { window.omoMeetingRecurrence.init(document); return; }
    function sync(section) {
        var enabled = section.querySelector('[data-meeting-recurrence-enabled]');
        var frequency = section.querySelector('[data-meeting-recurrence-frequency]');
        var editing = section.querySelector('[data-meeting-recurrence-editing]');
        var strategy = section.querySelector('[data-meeting-recurrence-strategy]');
        if (editing) {
            section.querySelector('[data-meeting-recurrence-readonly]').hidden = editing.value === '1';
            section.querySelector('[data-meeting-recurrence-editor]').hidden = editing.value !== '1';
            section.querySelectorAll('[data-meeting-strategy-help]').forEach(function (help) {
                help.hidden = help.getAttribute('data-meeting-strategy-help') !== strategy.value;
            });
        }
        var stopped = strategy && strategy.value.indexOf('stop_') === 0;
        section.querySelector('[data-meeting-recurrence-fields]').hidden = editing ? stopped : !enabled.checked;
        section.querySelectorAll('[data-meeting-recurrence-fields] input, [data-meeting-recurrence-fields] select').forEach(function (input) {
            input.disabled = editing ? editing.value !== '1' || stopped : !enabled.checked;
        });
        section.querySelectorAll('[data-meeting-recurrence-for]').forEach(function (field) {
            field.hidden = field.getAttribute('data-meeting-recurrence-for').split(' ').indexOf(frequency.value) < 0;
        });
        var intervalLabel = section.querySelector('[data-meeting-interval-label]');
        if (intervalLabel) { intervalLabel.textContent = frequency.value === 'on_close' ? intervalLabel.dataset.onClose : intervalLabel.dataset.days; }
        var form = section.closest('form');
        document.querySelectorAll('[data-meeting-save-menu]').forEach(function (menu) {
            if (menu.querySelector('[data-meeting-save-scope]').form === form) { syncSaveMenu(menu); }
        });
    }
    function init(scope) {
        if (scope.matches && scope.matches('[data-meeting-recurrence]')) { sync(scope); }
        if (scope.querySelectorAll) { scope.querySelectorAll('[data-meeting-recurrence]').forEach(sync); }
        if (scope.matches && scope.matches('[data-meeting-save-menu]')) { syncSaveMenu(scope); }
        if (scope.querySelectorAll) { scope.querySelectorAll('[data-meeting-save-menu]').forEach(syncSaveMenu); }
    }
    function syncSaveMenu(menu) {
        var form = menu.querySelector('[data-meeting-save-scope]').form;
        var enabled = form && form.querySelector('[data-meeting-recurrence-enabled]');
        if (!enabled) { return; }
        var editing = form.querySelector('[data-meeting-recurrence-editing]');
        var editingRule = editing && editing.value === '1';
        menu.querySelectorAll('[data-meeting-save-label]').forEach(function (label) {
            var text = label.getAttribute(editingRule ? 'data-rule' : (editing || enabled.checked ? 'data-enabled' : 'data-disabled'));
            if (label.textContent !== text) { label.textContent = text; }
        });
    }
    function closeActionMenus(except) {
        document.querySelectorAll('[data-meeting-action-menu]').forEach(function (menu) {
            if (menu === except) { return; }
            menu.querySelector('[role="menu"]').hidden = true;
            menu.querySelector('[data-meeting-action-toggle]').setAttribute('aria-expanded', 'false');
        });
    }
    function escape(value) { var node = document.createElement('span'); node.textContent = value; return node.innerHTML; }
    function confirmDeletion(ui, scope) {
        return Promise.resolve(window.confirm(scope === 'following' ? ui.scope_delete_help : ui.delete_single_help));
    }
    // Capture the selected save action before the drawer serializes the form.
    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form.matches('[data-omo-calendar-create-form]')) { return; }
        var scopeField = form.querySelector('[data-meeting-edit-scope]');
        if (!scopeField) { return; }
        var editing = form.querySelector('[data-meeting-recurrence-editing]');
        var strategy = form.querySelector('[data-meeting-recurrence-strategy]');
        scopeField.value = event.submitter && event.submitter.getAttribute('data-meeting-save-scope') === 'following' ? '1' : '0';
        if (editing && editing.value === '1' && scopeField.value === '0' && !form.dataset.meetingIgnoreRuleConfirmed) {
            if (!window.confirm(form.querySelector('[data-meeting-ignore-rule-confirm]').textContent)) {
                event.preventDefault(); event.stopImmediatePropagation(); return;
            }
            form.dataset.meetingIgnoreRuleConfirmed = '1';
        }
        if (editing && editing.value === '1' && scopeField.value === '1' && strategy.value === 'stop_delete' && !form.dataset.meetingStopConfirmed) {
            if (!window.confirm(form.querySelector('[data-meeting-stop-confirm]').textContent)) {
                event.preventDefault(); event.stopImmediatePropagation(); return;
            }
            form.querySelector('[data-meeting-recurrence-delete-documents]').value = window.confirm(form.querySelector('[data-meeting-documents-confirm]').textContent) ? '1' : '0';
            // Keep the choice through an availability acknowledgement/retry.
            form.dataset.meetingStopConfirmed = '1';
        }
        closeActionMenus();
    }, true);
    document.addEventListener('click', function (event) {
        var editRule = event.target.closest('[data-meeting-recurrence-edit], [data-meeting-recurrence-cancel]');
        if (editRule) {
            var section = editRule.closest('[data-meeting-recurrence]');
            section.querySelector('[data-meeting-recurrence-editing]').value = editRule.hasAttribute('data-meeting-recurrence-edit') ? '1' : '0';
            delete section.closest('form').dataset.meetingStopConfirmed;
            delete section.closest('form').dataset.meetingIgnoreRuleConfirmed;
            sync(section); return;
        }
        var deleteChoice = event.target.closest('[data-meeting-delete-choice]');
        if (deleteChoice) {
            var deleteMenu = deleteChoice.closest('[data-meeting-delete-menu]');
            var primary = deleteMenu.querySelector('[data-omo-calendar-delete-url]');
            closeActionMenus();
            primary.setAttribute('data-meeting-delete-selection', deleteChoice.getAttribute('data-meeting-delete-choice'));
            primary.click(); primary.removeAttribute('data-meeting-delete-selection');
            return;
        }
        var toggle = event.target.closest('[data-meeting-action-toggle]');
        if (toggle) {
            var menu = toggle.closest('[data-meeting-action-menu]');
            var panel = menu.querySelector('[role="menu"]');
            closeActionMenus(menu); panel.hidden = !panel.hidden;
            toggle.setAttribute('aria-expanded', panel.hidden ? 'false' : 'true');
            if (!panel.hidden) { panel.querySelector('button').focus(); }
        } else if (!event.target.closest('[data-meeting-action-menu]')) { closeActionMenus(); }
    });
    document.addEventListener('keydown', function (event) {
        var menu = event.target.closest('[data-meeting-action-menu]');
        if (!menu) { return; }
        if (event.key === 'Escape') {
            closeActionMenus(); menu.querySelector('[data-meeting-action-toggle]').focus(); event.preventDefault();
        } else if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            var panel = menu.querySelector('[role="menu"]');
            panel.hidden = false; menu.querySelector('[data-meeting-action-toggle]').setAttribute('aria-expanded', 'true');
            var buttons = Array.from(panel.querySelectorAll('button'));
            var index = buttons.indexOf(document.activeElement);
            var next = index < 0 ? (event.key === 'ArrowDown' ? 0 : buttons.length - 1)
                : (index + (event.key === 'ArrowDown' ? 1 : buttons.length - 1)) % buttons.length;
            buttons[next].focus();
            event.preventDefault();
        }
    });
    function chooseDate(ui, closing, suggested, availability) {
        return new Promise(function (resolve) {
            if (typeof window.commonTopbarOpenModal !== 'function') { resolve(null); return; }
            var html = '<form data-meeting-next-form class="generic-section generic-section--plain generic-form-stack">'
                + '<div class="generic-heading-with-help"><label class="generic-form-label" for="meeting-next-start">' + escape(ui.next_date) + '</label>'
                + (availability && availability.url ? '<details class="generic-context-help generic-context-help--compact" data-generic-context-help-hover>'
                    + '<summary aria-label="' + escape(ui.next_date) + '">?</summary><div class="generic-context-help__content">'
                    + escape(ui.availability_hint) + '</div></details>' : '') + '</div>'
                + '<input id="meeting-next-start" class="generic-form-control" type="datetime-local" name="next_meeting_start" required>'
                + (availability && availability.url ? '<input type="hidden" name="start_at"><input type="hidden" name="end_at">'
                    + '<div data-omo-calendar-preview-host aria-live="polite" data-loading-label="' + escape(ui.availability_loading)
                    + '" data-error-label="' + escape(ui.availability_error) + '"></div>' : '')
                + '<div class="generic-action-row"><button class="generic-action-button generic-action-button--main" type="submit">'
                + escape(closing ? ui.close_plan : ui.plan) + '</button>'
                + (closing ? '<button class="generic-action-button generic-action-button--secondary" type="button" data-meeting-skip>' + escape(ui.close_skip) + '</button>' : '')
                + '<button class="generic-action-button generic-action-button--secondary" type="button" data-meeting-cancel>' + escape(ui.cancel) + '</button></div></form>';
            window.commonTopbarOpenModal(ui.next_title, html, 'html');
            var form = document.querySelector('[data-meeting-next-form]');
            if (!form) { resolve(null); return; }
            var input = form.elements.next_meeting_start;
            var now = new Date(); now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            input.min = now.toISOString().slice(0, 16);
            if (availability && availability.url) {
                form.setAttribute('data-omo-calendar-availability-form', '');
                form.action = availability.url;
                form.dataset.calendarFixedDuration = String(availability.duration);
                form.dataset.calendarMinimum = availability.minimum;
                input.min = availability.minimum;
                suggested = suggested || availability.suggested;
            }
            if (suggested) { input.value = suggested.slice(0, 16); }
            if (availability && availability.url) {
                var preview = window.omoCalendarAvailabilityPreview;
                function updateSchedule() {
                    form.elements.start_at.value = input.value;
                    var time = Date.parse(input.value + 'Z');
                    form.elements.end_at.value = Number.isFinite(time)
                        ? new Date(time + Number(availability.duration) * 1000).toISOString().slice(0, 16) : '';
                    form.elements.start_at.dispatchEvent(new Event('input', {bubbles: true}));
                }
                updateSchedule();
                input.addEventListener('change', function () { updateSchedule(); if (preview) { preview.load(form, false); } });
                form.elements.start_at.addEventListener('input', function () { input.value = form.elements.start_at.value; });
                if (preview) { preview.load(form, false); }
                else { form.querySelector('[data-omo-calendar-preview-host]').textContent = ui.availability_error; }
            }
            var settled = false;
            function finish(value) {
                if (settled) { return; } settled = true;
                if (window.omoCalendarAvailabilityPreview) { window.omoCalendarAvailabilityPreview.dispose(form); }
                window.removeEventListener('common-topbar-modal-close', onClose);
                window.commonTopbarCloseModal(); resolve(value);
            }
            function onClose() {
                if (window.omoCalendarAvailabilityPreview) { window.omoCalendarAvailabilityPreview.dispose(form); }
                if (!settled) { settled = true; resolve(null); }
            }
            window.addEventListener('common-topbar-modal-close', onClose, {once: true});
            form.addEventListener('submit', function (event) { event.preventDefault(); if (form.reportValidity()) { finish(input.value); } });
            form.querySelector('[data-meeting-cancel]').addEventListener('click', function () { finish(null); });
            var skip = form.querySelector('[data-meeting-skip]');
            if (skip) { skip.addEventListener('click', function () { finish(''); }); }
        });
    }
    document.addEventListener('change', function (event) {
        var section = event.target.closest('[data-meeting-recurrence]');
        if (!section) { return; }
        delete section.closest('form').dataset.meetingStopConfirmed;
        delete section.closest('form').dataset.meetingIgnoreRuleConfirmed;
        sync(section);
    });
    function refreshAfterPlanning() {
        if (typeof window.omoInvalidateMainRightPanel === 'function') { window.omoInvalidateMainRightPanel(); }
        if (document.querySelector('#omo-calendar-root') && typeof window.omoCalendarRefreshCurrentView === 'function') {
            window.omoCalendarRefreshCurrentView(true);
        }
    }
    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-meeting-plan-next]');
        if (!button || button.disabled) { return; }
        var config = JSON.parse(button.getAttribute('data-meeting-plan-next'));
        chooseDate(config.ui, false, config.suggested, config.availability).then(function (date) {
            if (!date) { return; } button.disabled = true;
            var body = new FormData(); body.set('id', config.id); body.set('csrf', config.csrf); body.set('next_meeting_start', date);
            return fetch(config.url, {method: 'POST', credentials: 'same-origin', body: body, headers: {'Accept': 'application/json'}})
                .then(function (response) { return response.json(); }).then(function (payload) {
                    if (!payload.status) { throw new Error(payload.message || config.ui.error); }
                    if (window.commonNotify) { window.commonNotify(config.ui.saved, 'success'); }
                    button.remove();
                    refreshAfterPlanning();
                }).catch(function (error) {
                    if (window.commonNotify) { window.commonNotify(error.message || config.ui.error, 'error'); }
                    button.disabled = false;
                });
        });
    });
    window.omoMeetingRecurrence = {init: init, chooseDate: chooseDate, confirmDeletion: confirmDeletion, refreshAfterPlanning: refreshAfterPlanning};
    new MutationObserver(function (records) {
        records.forEach(function (record) { record.addedNodes.forEach(function (node) { if (node.nodeType === 1) { init(node); } }); });
    }).observe(document.documentElement, {childList: true, subtree: true});
    init(document);
})(window, document);
