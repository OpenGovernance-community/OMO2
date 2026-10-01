(function () {
    'use strict';

    if (window.omoDecisionEnsureLifecycleStatusConsistency) {
        return;
    }

    function formatDateTimeLocal(date) {
        const pad = function (value) { return String(value).padStart(2, '0'); };
        return date.getFullYear()
            + '-' + pad(date.getMonth() + 1)
            + '-' + pad(date.getDate())
            + 'T' + pad(date.getHours())
            + ':' + pad(date.getMinutes());
    }

    function formatDateLabel(value) {
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return value || '';
        }

        return new Intl.DateTimeFormat(document.documentElement.lang || 'fr-CH', {
            dateStyle: 'short',
            timeStyle: 'short'
        }).format(date);
    }

    function getScope(select) {
        return select.closest('[data-omo-decision-lifecycle-confirm-template]');
    }

    function getInput(scope, name) {
        return scope ? scope.querySelector('input[name="' + name + '"]') : null;
    }

    function adjustDates(scope, now) {
        const nowValue = formatDateTimeLocal(now);
        const consultationStart = getInput(scope, 'consultation_start_at');
        const consultationEnd = getInput(scope, 'consultation_end_at');
        const evaluationStart = getInput(scope, 'evaluation_start_at');

        if (consultationStart && consultationStart.value && new Date(consultationStart.value) > now) {
            consultationStart.value = nowValue;
        }
        if (consultationEnd && (!consultationEnd.value || new Date(consultationEnd.value) > now)) {
            consultationEnd.value = nowValue;
        }
        if (evaluationStart) {
            evaluationStart.value = nowValue;
        }
    }

    function adjustConsultationStart(scope, now) {
        const consultationStart = getInput(scope, 'consultation_start_at');
        if (consultationStart) {
            consultationStart.value = formatDateTimeLocal(now);
        }
    }

    function ensure(select) {
        const scope = getScope(select);
        if (!scope || !['consultation', 'evaluation'].includes(select.value)) {
            select.dataset.omoDecisionLifecyclePreviousStatus = select.value;
            return true;
        }

        const isConsultation = select.value === 'consultation';
        const startInput = getInput(scope, isConsultation ? 'consultation_start_at' : 'evaluation_start_at');
        if (!startInput) {
            return true;
        }

        const now = new Date();
        const startDate = startInput.value ? new Date(startInput.value) : null;
        if (startDate && !Number.isNaN(startDate.getTime()) && startDate <= now) {
            select.dataset.omoDecisionLifecyclePreviousStatus = select.value;
            return true;
        }

        const template = scope.getAttribute(isConsultation
            ? 'data-omo-decision-lifecycle-consultation-confirm-template'
            : 'data-omo-decision-lifecycle-confirm-template') || '';
        const message = template.replace('{date}', formatDateLabel(startInput.value));
        if (window.confirm(message)) {
            if (isConsultation) {
                adjustConsultationStart(scope, now);
            } else {
                adjustDates(scope, now);
            }
            select.dataset.omoDecisionLifecyclePreviousStatus = select.value;
            return true;
        }

        select.value = select.dataset.omoDecisionLifecyclePreviousStatus || 'scheduled';
        return false;
    }

    window.omoDecisionEnsureLifecycleStatusConsistency = function (scope) {
        const select = scope ? scope.querySelector('select[name="status"]') : null;
        return !select || ensure(select);
    };

    document.addEventListener('focusin', function (event) {
        const select = event.target.closest('select[name="status"]');
        if (select && !select.dataset.omoDecisionLifecyclePreviousStatus) {
            select.dataset.omoDecisionLifecyclePreviousStatus = select.value;
        }
    });

    document.addEventListener('change', function (event) {
        const select = event.target.closest('select[name="status"]');
        if (select && getScope(select)) {
            ensure(select);
        }
    });

    document.addEventListener('submit', function (event) {
        const form = event.target.closest('form[data-omo-decision-lifecycle-confirm-template]');
        if (form && !window.omoDecisionEnsureLifecycleStatusConsistency(form)) {
            event.preventDefault();
            event.stopImmediatePropagation();
        }
    }, true);
}());
