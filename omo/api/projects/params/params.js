(function () {
    'use strict';

    if (window.omoProjectsParamsBound) return;
    window.omoProjectsParamsBound = true;

    function refreshPreview(event) {
        var input = event.target;
        if (!input.matches('[data-omo-projects-parent-weight], [data-omo-projects-depth-penalty]')) return;
        var form = input.closest('[data-omo-projects-params-form]');
        if (!form) return;
        var value = input.valueAsNumber;
        var valid = input.value !== '' && Number.isFinite(value) && input.validity.valid;

        if (input.hasAttribute('data-omo-projects-parent-weight')) {
            var local = form.querySelector('[data-omo-projects-local-weight]');
            var parent = form.querySelector('[data-omo-projects-parent-share]');
            if (local) local.textContent = valid ? String(Math.round((1 - value) * 100)) + ' %' : '--';
            if (parent) parent.textContent = valid ? parent.dataset.template.replace('{percent}', String(Math.round(value * 100))) : '--';
        } else {
            var example = form.querySelector('[data-omo-projects-depth-example]');
            if (example) {
                example.textContent = valid
                    ? example.dataset.template
                        .replace('{level1}', String(Math.round(Math.exp(-value) * 100)))
                        .replace('{level2}', String(Math.round(Math.exp(-value * 2) * 100)))
                        .replace('{level3}', String(Math.round(Math.exp(-value * 3) * 100)))
                    : '--';
            }
        }
    }

    // Delegation also covers forms inserted or reopened in the settings drawer.
    document.addEventListener('input', refreshPreview);
    document.addEventListener('change', refreshPreview);
    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form.matches('[data-omo-projects-params-form]') || event.defaultPrevented) return;
        event.preventDefault();
        if (form.dataset.saving === '1' || !form.reportValidity()) return;

        var submit = form.querySelector('[data-omo-projects-params-submit]');
        var feedback = form.querySelector('[data-omo-projects-params-feedback]');
        var saveLabel = submit.textContent;
        var errorMessage = form.getAttribute('data-omo-projects-params-error') || '';
        function showFeedback(message, isError) {
            feedback.hidden = false;
            feedback.textContent = message;
            feedback.classList.toggle('is-error', isError);
            feedback.classList.toggle('is-success', !isError);
            feedback.setAttribute('role', isError ? 'alert' : 'status');
        }

        form.dataset.saving = '1';
        submit.disabled = true;
        submit.textContent = form.getAttribute('data-omo-projects-params-saving-label') || saveLabel;
        feedback.hidden = true;
        window.fetch(form.action, {method: 'POST', body: new FormData(form), credentials: 'same-origin'})
            .then(function (response) {
                return response.json().then(function (payload) {
                    showFeedback(String(payload.message || errorMessage), !response.ok || !payload.status);
                });
            })
            .catch(function () { showFeedback(errorMessage, true); })
            .finally(function () {
                delete form.dataset.saving;
                submit.disabled = false;
                submit.textContent = saveLabel;
            });
    });
})();
