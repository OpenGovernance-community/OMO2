window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/omo/api/stats/edit.js"] = function (pageConfig, pageScript) {
(function () {
    var editor = document.querySelector('[data-omo-stats-editor]');
    if (!editor || editor.dataset.omoStatsEditorReady === '1') {
        return;
    }
    editor.dataset.omoStatsEditorReady = '1';

    var sourceTypeField = editor.querySelector('[data-omo-stats-source-type]');
    var selectedSourceType = pageConfig.selectedSourceType;
    var sourcePanels = Array.prototype.slice.call(editor.querySelectorAll('[data-omo-stats-source-panel]'));
    var sourceModeFields = Array.prototype.slice.call(editor.querySelectorAll('[data-omo-stats-source-mode-field]'));

    function setSourceFieldsDisabled(container, disabled) {
        Array.prototype.forEach.call(container.querySelectorAll('input, select, textarea'), function (field) {
            field.disabled = disabled;
        });
    }

    function syncSourceType() {
        var sourceType = sourceTypeField ? sourceTypeField.value : selectedSourceType;
        sourceType = sourceType || 'manual';

        sourcePanels.forEach(function (panel) {
            var panelTypes = String(panel.getAttribute('data-omo-stats-source-panel') || '').trim().split(/\s+/);
            var isActive = panelTypes.indexOf(sourceType) !== -1;
            panel.hidden = !isActive;
            setSourceFieldsDisabled(panel, !isActive);
        });

        sourceModeFields.forEach(function (field) {
            var isActive = String(field.getAttribute('data-omo-stats-source-mode-field') || '') === sourceType;
            field.hidden = !isActive;
            setSourceFieldsDisabled(field, !isActive);
        });

        if (sourceType === 'manual' && typeof syncMeasurementSchedule === 'function') {
            syncMeasurementSchedule(false);
        }
    }

    if (sourceTypeField) {
        sourceTypeField.addEventListener('change', syncSourceType);
    }

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
            title: pageConfig.title,
            description: pageConfig.description,
            actions: [cancelEditorButton, saveEditorButton].filter(function (button) {
                return button instanceof HTMLElement;
            })
        });
    }


    var feedback = editor.querySelector('[data-omo-stats-editor-feedback]');
    var saving = false;
    var saveLabel = saveEditorButton.textContent;
    var saveError = pageConfig.saveError;
    editorForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        if (saving || !editorForm.reportValidity()) {
            return;
        }
        var formData = new FormData(editorForm);
        saving = true;
        editorForm.setAttribute('aria-busy', 'true');
        saveEditorButton.disabled = true;
        cancelEditorButton.disabled = true;
        saveEditorButton.textContent = pageConfig.editorSaving;
        feedback.textContent = '';
        feedback.classList.remove('is-success');
        try {
            var response = await fetch(editorForm.action, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            });
            var result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.message || saveError);
            }
            editorForm.elements.namedItem('id').value = String(result.id);
            if (typeof window.omoStatsAfterIndicatorSave === 'function') {
                window.omoStatsAfterIndicatorSave();
            } else {
                feedback.classList.add('is-success');
                feedback.textContent = pageConfig.editorSaved;
            }
        } catch (error) {
            var message = error.message || saveError;
            if (typeof window.omoNotify === 'function') {
                window.omoNotify(message, 'error');
            } else {
                feedback.textContent = message;
                feedback.scrollIntoView({block: 'nearest', behavior: 'smooth'});
            }
        } finally {
            saving = false;
            editorForm.removeAttribute('aria-busy');
            saveEditorButton.disabled = false;
            cancelEditorButton.disabled = false;
            saveEditorButton.textContent = saveLabel;
        }
    });

    var typeField = editor.querySelector('[data-omo-stats-reference-type]');
    var showCumulativeField = editor.querySelector('input[type="checkbox"][name="show_cumulative"]');
    var referenceScaleRow = editor.querySelector('[data-omo-stats-reference-scale]');
    var measurementFrequencyField = editor.querySelector('[data-omo-stats-measurement-frequency]');
    var measurementScheduleField = editor.querySelector('[data-omo-stats-measurement-schedule]');
    var measurementScheduleWrapper = editor.querySelector('[data-omo-stats-measurement-schedule-field]');
    var labels = pageConfig.labels;
    var measurementScheduleOptions = pageConfig.measurementScheduleOptions;

    function syncReferenceScale() {
        if (!referenceScaleRow) {
            return;
        }
        var isVisible = Boolean(showCumulativeField && showCumulativeField.checked)
            && Boolean(typeField && typeField.value !== 'none');
        referenceScaleRow.hidden = !isVisible;
    }

    if (showCumulativeField) {
        showCumulativeField.addEventListener('change', syncReferenceScale);
    }
    if (typeField) {
        typeField.addEventListener('change', syncReferenceScale);
    }

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

    syncSourceType();
    syncReferenceScale();
})();
};
