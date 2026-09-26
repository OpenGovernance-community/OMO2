window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/omo/api/organization/member_popup.js"] = function (pageConfig, pageScript) {
    (function () {
        var form = document.getElementById('omoOrganizationMemberPopupForm');
        var feedback = document.getElementById('omoOrganizationMemberPopupFeedback');
        var submitButton = document.getElementById('omoOrganizationMemberPopupSubmit');
        var emailInput = document.getElementById('omoOrganizationMemberEmail');
        var organizationId = pageConfig.organizationId;

        if (!form || !feedback || !submitButton || !emailInput) {
            return;
        }

        function refreshParentPanel() {
            if (typeof window.omoRefreshOrganizationInfoPanel === 'function') {
                window.omoRefreshOrganizationInfoPanel(organizationId);
                return;
            }

            if (window.parent && window.parent !== window && typeof window.parent.omoRefreshOrganizationInfoPanel === 'function') {
                window.parent.omoRefreshOrganizationInfoPanel(organizationId);
            }
        }

        function closeModal() {
            if (typeof window.commonTopbarCloseModal === 'function') {
                window.commonTopbarCloseModal();
                return;
            }

            if (window.parent && window.parent !== window && typeof window.parent.commonTopbarCloseModal === 'function') {
                window.parent.commonTopbarCloseModal();
            }
        }

        function showFeedback(message, isError) {
            if (typeof window.commonNotify === 'function') {
                window.commonNotify(String(message || ''), isError ? 'error' : 'success');
                feedback.textContent = '';
                feedback.classList.remove('is-success');
                return;
            }

            feedback.textContent = String(message || '');
            feedback.classList.toggle('is-success', !isError && String(message || '') !== '');
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            feedback.textContent = '';
            feedback.classList.remove('is-success');

            if (!emailInput.value.trim()) {
                showFeedback('Saisissez une adresse e-mail.', true);
                return;
            }

            submitButton.disabled = true;

            fetch(form.getAttribute('action'), {
                method: 'POST',
                body: new FormData(form),
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function (response) {
                return response.json().catch(function () {
                    return null;
                }).then(function (data) {
                    return {
                        ok: response.ok,
                        data: data
                    };
                });
            })
            .then(function (result) {
                if (!result.ok || !result.data || !result.data.status) {
                    showFeedback(result.data && result.data.message ? result.data.message : 'Une erreur est survenue.', true);
                    submitButton.disabled = false;
                    return;
                }

                showFeedback(result.data.message || 'Invitation envoyee.', false);
                refreshParentPanel();

                window.setTimeout(function () {
                    closeModal();
                }, 250);
            })
            .catch(function () {
                showFeedback('Une erreur est survenue.', true);
                submitButton.disabled = false;
            });
        });
    })();
};
