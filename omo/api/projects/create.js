window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/omo/api/projects/create.js"] = function (pageConfig, pageScript) {
(function (window, document) {
    'use strict';
    var root = document.querySelector('[data-omo-project-form-root]');
    if (!root || root.dataset.omoProjectFormReady === '1') return;
    root.dataset.omoProjectFormReady = '1';
    var form = root.querySelector(("#" + pageConfig.formId + ""));
    if (!(form instanceof HTMLFormElement)) return;
    var parentCandidates = pageConfig.parentCandidates;
    var texts = pageConfig.texts;
    var parentId = form.querySelector('[data-omo-project-parent-id]');
    var parentLabel = document.getElementById('omo-project-parent-label');
    var holonId = form.querySelector('[data-omo-project-holon-id]');
    var holonLabel = document.getElementById('omo-project-holon-label');
    var feedback = form.querySelector('[data-omo-project-form-feedback]');
    var statusControl = form.querySelector('[name="status"]');
    var blockedSection = form.querySelector('[data-omo-project-blocked-section]');
    var blockedReason = form.querySelector('[data-omo-project-blocked-reason]');
    var blockedUntil = form.querySelector('[data-omo-project-blocked-until]');
    var blockedAuto = form.querySelector('[data-omo-project-blocked-auto]');
    var blockedTarget = form.querySelector('[data-omo-project-blocked-target]');
    var blockedTargetField = form.querySelector('[data-omo-project-blocked-target-field]');

    function escapeHtml(value) {
        return String(value || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function setFeedback(message) {
        if (!feedback) return;
        feedback.textContent = String(message || '');
        feedback.hidden = feedback.textContent === '';
    }

    root.querySelectorAll('[data-omo-project-level-input]').forEach(function (input) {
        var field = input.closest('.omo-project-form__level-field');
        var output = field.querySelector('[data-omo-project-level-output]');
        var kind = input.getAttribute('data-omo-project-level-kind');
        var priorityValue = field.querySelector('[data-omo-project-priority-value]');
        var updateLevel = function () {
            var value = Number(input.value || 0);
            var actualValue = kind === 'priority' && value > 0 ? 6 - value : value;
            if (priorityValue) priorityValue.value = String(actualValue);
            if (output) output.textContent = actualValue > 0
                ? (kind === 'priority' ? 'P' + String(actualValue) : String(actualValue) + '/5')
                : ("" + pageConfig.projectsLevelNone + "");
            input.setAttribute('aria-valuetext', actualValue > 0
                ? (kind === 'priority' ? 'P' + String(actualValue) : String(actualValue) + '/5')
                : ("" + pageConfig.projectsLevelNone + ""));
        };
        input.addEventListener('input', updateLevel);
        updateLevel();
    });

    root.querySelector('[data-omo-project-parent-picker]').addEventListener('click', function () {
        if (typeof window.commonTopbarOpenModal !== 'function') return;
        var html = '<div class="omo-project-parent-picker omo-resource-picker generic-drawer-content">'
            + '<aside class="omo-resource-picker__navigation" data-omo-project-parent-scope></aside>'
            + '<div class="omo-resource-picker__content">'
            + '<label class="omo-resource-picker__quick-search"><input class="generic-form-control" type="search" data-omo-project-parent-search aria-label="' + escapeHtml(texts.parentPickerSearch) + '" placeholder="' + escapeHtml(texts.parentPickerSearch) + '"></label>'
            + '<select class="generic-form-control omo-project-parent-picker__select" size="10" data-omo-project-parent-select></select>'
            + '<p class="omo-project-parent-picker__empty generic-description generic-description--small" data-omo-project-parent-empty hidden></p>'
            + '<div class="omo-project-parent-picker__actions"><button type="button" class="generic-action-button generic-action-button--secondary" data-omo-project-parent-cancel>' + escapeHtml(texts.cancel) + '</button><button type="button" class="generic-action-button generic-action-button--main" data-omo-project-parent-confirm>' + escapeHtml(texts.parentPickerChoose) + '</button></div></div></div>';
        window.commonTopbarOpenModal(texts.parentPickerTitle, html, 'html');
        var modal = document.getElementById('commonTopbarModalBody');
        if (!(modal instanceof Element)) return;
        var search = modal.querySelector('[data-omo-project-parent-search]');
        var select = modal.querySelector('[data-omo-project-parent-select]');
        var empty = modal.querySelector('[data-omo-project-parent-empty]');
        var currentId = Number(parentId && parentId.value || 0);
        var scopePicker = null;
        var render = function () {
            var query = String(search && search.value || '').trim().toLowerCase();
            var selectedHolonId = scopePicker && typeof scopePicker.getSelectedHolonId === 'function'
                ? Number(scopePicker.getSelectedHolonId() || 0)
                : 0;
            var matches = parentCandidates.filter(function (candidate) {
                var matchesScope = !scopePicker
                    || scopePicker.matches(Number(candidate.holonId || 0))
                    || (Number(candidate.holonId || 0) === 0 && selectedHolonId === pageConfig.rootHolon)
                    || (selectedHolonId > 0 && Number(candidate.holonId || 0) === selectedHolonId)
                    || Number(candidate.id || 0) === currentId;
                return matchesScope && (query === '' || [candidate.title, candidate.context].join(' ').toLowerCase().indexOf(query) !== -1);
            });
            select.innerHTML = '<option value="0">' + escapeHtml(texts.parentPickerNone) + '</option>';
            matches.forEach(function (candidate) {
                var option = document.createElement('option');
                option.value = String(candidate.id);
                option.textContent = String(candidate.title || '') + (candidate.context ? ' - ' + String(candidate.context) : '');
                select.appendChild(option);
            });
            select.value = String(currentId);
            if (select.value !== String(currentId)) select.value = '0';
            empty.textContent = texts.parentPickerEmpty;
            empty.hidden = matches.length > 0;
        };
        modal.addEventListener('click', function (event) {
            if (event.target.closest('[data-omo-project-parent-cancel]')) {
                window.commonTopbarCloseModal();
                return;
            }
            if (!event.target.closest('[data-omo-project-parent-confirm]')) return;
            var selectedId = Number(select.value || 0);
            var selected = parentCandidates.find(function (candidate) { return Number(candidate.id) === selectedId; });
            if (parentId) parentId.value = selected ? String(selected.id) : '';
            if (parentLabel) parentLabel.value = selected ? String(selected.title || '') : '';
            window.commonTopbarCloseModal();
        });
        if (search) {
            search.addEventListener('input', render);
            search.focus();
        }
        if (pageConfig.rootHolon2 && typeof window.omoMountHolonScopePicker === 'function') {
            scopePicker = window.omoMountHolonScopePicker({
                host: modal.querySelector('[data-omo-project-parent-scope]'),
                organizationId: pageConfig.organizationId,
                initialHolonId: pageConfig.initialHolonId,
                labels: texts.parentPickerScope || {},
                onChange: render
            });
        }
        render();
    });

    var holonPickerButton = root.querySelector('[data-omo-project-holon-picker]');
    if (holonPickerButton) holonPickerButton.addEventListener('click', function () {
        if (typeof window.commonTopbarOpenModal !== 'function' || typeof window.omoMountHolonScopePicker !== 'function') return;
        var html = '<div class="omo-project-holon-picker generic-drawer-content">'
            + '<p class="omo-project-move-dialog__hint generic-help-text">' + escapeHtml(texts.holonPickerHint) + '</p>'
            + '<div data-omo-project-holon-scope></div>'
            + '<div class="omo-project-parent-picker__actions"><button type="button" class="generic-action-button generic-action-button--secondary" data-omo-project-holon-cancel>' + escapeHtml(texts.cancel) + '</button><button type="button" class="generic-action-button generic-action-button--main" data-omo-project-holon-confirm>' + escapeHtml(texts.holonPickerConfirm) + '</button></div></div>';
        window.commonTopbarOpenModal(texts.holonPickerTitle, html, 'html');
        var modal = document.getElementById('commonTopbarModalBody');
        var dialog = modal ? modal.querySelector('.omo-project-holon-picker') : null;
        if (!(dialog instanceof Element)) return;
        var selectedHolonId = Number(holonId && holonId.value || pageConfig.assignedHolonId);
        var selectedHolonLabel = holonLabel ? String(holonLabel.value || '') : '';
        var picker = window.omoMountHolonScopePicker({
            host: dialog.querySelector('[data-omo-project-holon-scope]'),
            organizationId: pageConfig.organizationId,
            initialHolonId: selectedHolonId,
            showModes: false,
            onChange: function (nextHolonId) {
                selectedHolonId = Number(nextHolonId || 0);
                if (picker && typeof picker.getSelectedHolonLabel === 'function') {
                    selectedHolonLabel = picker.getSelectedHolonLabel() || selectedHolonLabel;
                }
            }
        });
        dialog.addEventListener('click', function (event) {
            if (event.target.closest('[data-omo-project-holon-cancel]')) {
                window.commonTopbarCloseModal();
                return;
            }
            if (!event.target.closest('[data-omo-project-holon-confirm]')) return;
            if (holonId) holonId.value = selectedHolonId > 0 ? String(selectedHolonId) : '';
            if (holonLabel && selectedHolonLabel !== '') holonLabel.value = selectedHolonLabel;
            window.commonTopbarCloseModal();
        });
    });

    var startDate = form.querySelector('[data-omo-project-start-date]');
    var endDate = form.querySelector('[data-omo-project-end-date]');
    var syncDateConstraint = function () {
        if (!(startDate instanceof HTMLInputElement) || !(endDate instanceof HTMLInputElement)) return;
        endDate.min = String(startDate.value || '');
        endDate.setCustomValidity(startDate.value && endDate.value && endDate.value < startDate.value ? texts.dateError : '');
    };
    if (startDate) startDate.addEventListener('change', syncDateConstraint);
    if (endDate) endDate.addEventListener('change', syncDateConstraint);
    syncDateConstraint();

    var syncBlockedFields = function () {
        var isBlocked = statusControl && String(statusControl.value || '') === 'blocked';
        if (blockedSection) blockedSection.hidden = !isBlocked;
        if (blockedReason) blockedReason.required = isBlocked;
        if (blockedUntil) blockedUntil.required = isBlocked;
        if (blockedTargetField) blockedTargetField.hidden = !isBlocked || !(blockedAuto && blockedAuto.checked);
        if (blockedTarget) blockedTarget.disabled = !isBlocked || !(blockedAuto && blockedAuto.checked);
    };
    if (statusControl) statusControl.addEventListener('change', syncBlockedFields);
    if (blockedAuto) blockedAuto.addEventListener('change', syncBlockedFields);
    syncBlockedFields();

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        setFeedback('');
        if (!form.reportValidity()) return;
        var title = form.elements.namedItem('title');
        if (title && String(title.value || '').trim() === '') {
            title.setCustomValidity(("" + pageConfig.projectsErrorTitle + ""));
            form.reportValidity();
            title.setCustomValidity('');
            return;
        }
        var submitters = document.querySelectorAll('[data-omo-project-form-submit], button[type="submit"][form="' + form.id + '"]');
        submitters.forEach(function (button) { button.disabled = true; });
        fetch(form.action, {method: 'POST', credentials: 'same-origin', headers: {'X-Requested-With': 'XMLHttpRequest'}, body: new FormData(form)})
            .then(function (response) {
                return response.json().catch(function () { return null; }).then(function (payload) {
                    return {ok: response.ok, payload: payload};
                });
            })
            .then(function (result) {
                if (!result.ok || !result.payload || !result.payload.success) throw new Error(result.payload && result.payload.message ? result.payload.message : texts.saveError);
                return typeof window.omoProjectsAfterSave === 'function' ? window.omoProjectsAfterSave() : null;
            })
            .catch(function (error) {
                var message = error && error.message ? error.message : texts.saveError;
                if (typeof window.omoNotify === 'function') {
                    window.omoNotify(message, 'error');
                } else {
                    setFeedback(message);
                }
                submitters.forEach(function (button) { button.disabled = false; });
            });
    });
})(window, document);
};
