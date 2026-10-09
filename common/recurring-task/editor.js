(function (window, document) {
    'use strict';

    function updateSchedule(form) {
        var frequency = form.querySelector('[data-activity-frequency]');
        var schedule = form.querySelector('[data-activity-schedule]');
        var options;
        var selected;
        if (!frequency || !schedule) {
            return;
        }
        try {
            options = JSON.parse(form.getAttribute('data-activity-schedule-options') || '{}');
        } catch (error) {
            options = {};
        }
        selected = schedule.getAttribute('data-selected-value') || schedule.value;
        schedule.innerHTML = '';
        (options[frequency.value] || []).forEach(function (entry) {
            var option = document.createElement('option');
            option.value = entry.value;
            option.textContent = entry.label;
            schedule.appendChild(option);
        });
        if (Array.prototype.some.call(schedule.options, function (option) { return option.value === selected; })) {
            schedule.value = selected;
        }
        schedule.removeAttribute('data-selected-value');
    }

    function initializeHtmlEditors(container) {
        if (!window.omoSimpleHtmlField || typeof window.omoSimpleHtmlField.mount !== 'function') {
            return;
        }

        container.querySelectorAll('[data-activity-html-editor]').forEach(function (editorHost) {
            if (editorHost.dataset.activityHtmlEditorReady === '1') {
                return;
            }

            var fieldContainer = editorHost.closest('[data-activity-html-editor-container]');
            var valueField = fieldContainer
                ? fieldContainer.querySelector('[data-activity-html-value]')
                : null;
            if (!valueField) {
                return;
            }

            editorHost.dataset.activityHtmlEditorReady = '1';
            window.omoSimpleHtmlField.mount(editorHost, {
                value: valueField.value || '',
                placeholder: '',
                simpleOnly: true,
                onChange: function (value) {
                    valueField.value = String(value || '');
                },
                onReady: function (api) {
                    if (api && typeof api.getValue === 'function') {
                        valueField.value = String(api.getValue() || '');
                    }
                }
            });
        });
    }

    function syncHtmlEditors(form) {
        form.querySelectorAll('[data-activity-html-editor]').forEach(function (editorHost) {
            var fieldContainer = editorHost.closest('[data-activity-html-editor-container]');
            var valueField = fieldContainer
                ? fieldContainer.querySelector('[data-activity-html-value]')
                : null;
            var api = editorHost.__omoSimpleHtmlField;

            if (valueField && api && typeof api.getValue === 'function') {
                valueField.value = String(api.getValue() || '');
            }
        });
    }

    function mount(form, settings) {
        if (!form || form.dataset.activityEditorReady === '1') return;
        form.dataset.activityEditorReady = '1';
        settings = settings || {};
        updateSchedule(form);
        initializeHtmlEditors(form);
        var frequency = form.querySelector('[data-activity-frequency]');
        if (frequency) frequency.addEventListener('change', function () { updateSchedule(form); });
        if (typeof settings.onSave !== 'function') return;
        var buttons = Array.from(document.querySelectorAll('button')).filter(function (button) { return button.form === form; });
        buttons.filter(function (button) { return button.hasAttribute('data-activity-editor-cancel'); }).forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                if (settings.onCancel) settings.onCancel();
            });
        });
        var saving = false;
        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            event.stopPropagation();
            if (saving || !form.reportValidity()) return;
            var sharedPending = typeof window.omoBeginPendingAction === 'function';
            if (sharedPending && !window.omoBeginPendingAction(form)) return;
            syncHtmlEditors(form);
            saving = true;
            form.setAttribute('aria-busy', 'true');
            var disabledStates = buttons.map(function (button) { return button.disabled; });
            if (!sharedPending) buttons.forEach(function (button) { button.disabled = true; });
            try {
                var response = await fetch(form.action, {method: 'POST', body: new FormData(form), credentials: 'same-origin',
                    headers: {'X-Requested-With': 'XMLHttpRequest'}});
                var result = await response.json();
                if (!response.ok || !result.status) throw new Error(result.message || settings.saveError);
                await settings.onSave(result);
                if (result.message && typeof window.commonNotify === 'function') window.commonNotify(result.message, 'success');
            } catch (error) {
                if (typeof window.commonNotify === 'function') window.commonNotify(error.message || settings.saveError, 'error');
            } finally {
                saving = false;
                form.removeAttribute('aria-busy');
                if (sharedPending && typeof window.omoEndPendingAction === 'function') window.omoEndPendingAction(form);
                else buttons.forEach(function (button, index) { button.disabled = disabledStates[index]; });
            }
        });
    }

    window.omoRecurringTaskEditor = {mount: mount, syncHtml: syncHtmlEditors};
    window.commonPageScripts = window.commonPageScripts || {};
    window.commonPageScripts['/common/recurring-task/editor.js'] = function (config) {
        mount(document.getElementById(config.formId), {
            saveError: config.saveError,
            onCancel: function () { window.omoCloseProjectDocumentEditorDrawer(); },
            onSave: function (result) {
                window.dispatchEvent(new CustomEvent('omo-project-resource-saved', {
                    detail: {projectId: Number(config.projectId), resourceType: 'recurring_task', resourceId: Number(result.id)}
                }));
                window.dispatchEvent(new CustomEvent('omo-activities-changed'));
                window.omoCloseProjectDocumentEditorDrawer();
            }
        });
    };
})(window, document);

