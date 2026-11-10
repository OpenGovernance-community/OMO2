(function (window) {
    'use strict';

    window.omoDecisionNotifyError = function (message) {
        const text = String(message || '').trim();
        const notify = window.commonNotify || window.omoNotify;
        if (text === '' || typeof notify !== 'function') {
            return false;
        }

        notify(text, 'error');
        return true;
    };

    window.omoDecisionKeepResponseInPlace = function (form, result, payload, submitButton, setFeedback) {
        if (!form.closest('[data-decision-public-group]')) {
            return false;
        }

        form.dataset.omoDecisionResponseSubmitted = '1';
        form.querySelectorAll('[data-omo-decision-one-at-a-time-draft-url]').forEach(function (root) {
            root.removeAttribute('data-omo-decision-one-at-a-time-draft-url');
        });
        if (submitButton && payload.texts && payload.texts.update) {
            submitButton.textContent = payload.texts.update;
            submitButton.dataset.originalText = payload.texts.update;
            payload.texts.save = payload.texts.update;
        }
        if (payload.responsesEditable === false) {
            form.dataset.omoDecisionResponseLocked = '1';
            form.querySelectorAll('input:not([type="hidden"]), select, textarea').forEach(function (field) {
                field.disabled = true;
            });
            if (submitButton) submitButton.disabled = true;
        }

        const message = result.message || (payload.texts && payload.texts.success) || '';
        const notify = window.commonNotify || window.omoNotify;
        if (typeof notify === 'function') {
            setFeedback('', false);
            notify(message, 'success');
        } else {
            setFeedback(message, false);
        }
        return true;
    };
})(window);
