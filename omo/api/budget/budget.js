(function () {
    'use strict';

    function bindBudgetForm(form) {
        if (!(form instanceof HTMLFormElement) || form.dataset.omoBudgetBound === '1') {
            return;
        }
        form.dataset.omoBudgetBound = '1';

        var feedback = form.querySelector('[data-omo-budget-feedback]');
        var submitButton = form.querySelector('button[type="submit"]');
        var fallbackError = String(form.dataset.omoBudgetError || '');
        var budgetPairs = [
            ['time_budget_hours', 'time_budget_recurrence'],
            ['money_budget', 'money_budget_recurrence']
        ];

        budgetPairs.forEach(function (names) {
            var amount = form.elements.namedItem(names[0]);
            var recurrence = form.elements.namedItem(names[1]);
            if (!(amount instanceof HTMLInputElement) || !(recurrence instanceof HTMLSelectElement)) {
                return;
            }
            amount.addEventListener('input', function () {
                if (String(amount.value || '').trim() !== '' && String(recurrence.value || '') === '') {
                    recurrence.value = 'month';
                }
            });
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            if (submitButton) {
                submitButton.disabled = true;
            }
            if (feedback) {
                feedback.textContent = '';
                feedback.classList.remove('is-error', 'is-success');
            }

            fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                credentials: 'same-origin',
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            }).then(function (response) {
                return response.json().catch(function () {
                    return {status: false, message: ''};
                }).then(function (payload) {
                    if (!response.ok || !payload || !payload.status) {
                        throw new Error(payload && payload.message ? payload.message : '');
                    }
                    return payload;
                });
            }).then(function (payload) {
                var budgets = payload && payload.budgets && typeof payload.budgets === 'object'
                    ? payload.budgets
                    : {};
                budgetPairs.forEach(function (names) {
                    var recurrence = form.elements.namedItem(names[1]);
                    if (recurrence instanceof HTMLSelectElement && Object.prototype.hasOwnProperty.call(budgets, names[1])) {
                        recurrence.value = String(budgets[names[1]] || '');
                    }
                });
                if (feedback) {
                    feedback.textContent = String(payload.message || '');
                    feedback.classList.add('is-success');
                }
            }).catch(function (error) {
                if (feedback) {
                    feedback.textContent = String(error && error.message ? error.message : fallbackError);
                    feedback.classList.add('is-error');
                }
            }).finally(function () {
                if (submitButton) {
                    submitButton.disabled = false;
                }
            });
        });
    }

    document.querySelectorAll('[data-omo-budget-form]').forEach(bindBudgetForm);
}());
