window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/omo/api/decision/send_invitations_popup.js"] = function (pageConfig, pageScript) {
(function () {
    var form = document.getElementById('omoDecisionSendInvitationsPopupForm');
    var feedback = document.getElementById('omoDecisionSendInvitationsPopupFeedback');
    var submitButton = document.getElementById('omoDecisionSendInvitationsPopupSubmit');
    var toggleButton = document.getElementById('omoDecisionSendInvitationsPopupToggle');
    var menu = document.getElementById('omoDecisionSendInvitationsPopupMenu');
    var scopeField = document.getElementById('omoDecisionSendInvitationsScope');
    var canSendPending = pageConfig.canSendPending;
    var canSendAll = pageConfig.canSendAll;

    if (!form || !feedback || !submitButton || !toggleButton || !menu || !scopeField) {
        return;
    }

    function closeMenu() {
        menu.hidden = true;
        toggleButton.setAttribute('aria-expanded', 'false');
    }

    function openMenu() {
        if (toggleButton.disabled) {
            return;
        }
        menu.hidden = false;
        toggleButton.setAttribute('aria-expanded', 'true');
    }

    function applyAvailabilityState(isBusy) {
        submitButton.disabled = isBusy || !canSendPending;
        toggleButton.disabled = isBusy || !canSendAll;
    }

    submitButton.addEventListener('click', function () {
        scopeField.value = 'pending';
    });

    toggleButton.addEventListener('click', function () {
        if (menu.hidden) {
            openMenu();
            return;
        }

        closeMenu();
    });

    document.addEventListener('click', function (event) {
        if (!menu.hidden && !form.contains(event.target)) {
            closeMenu();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeMenu();
        }
    });

    menu.addEventListener('click', function (event) {
        var actionButton = event.target.closest('[data-send-scope]');
        if (!actionButton) {
            return;
        }

        scopeField.value = actionButton.getAttribute('data-send-scope') || 'pending';
        closeMenu();
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
            return;
        }

        form.dispatchEvent(new Event('submit', { cancelable: true }));
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        closeMenu();
        feedback.textContent = '';
        feedback.classList.remove('is-success');
        applyAvailabilityState(true);

        fetch(form.getAttribute('action'), {
            method: 'POST',
            body: new FormData(form),
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (response) {
                return response.json().then(function (data) {
                    return {
                        ok: response.ok,
                        data: data
                    };
                });
            })
            .then(function (result) {
                if (!result.ok || !result.data || !result.data.status) {
                    feedback.textContent = result.data && result.data.message ? result.data.message : 'Une erreur est survenue.';
                    applyAvailabilityState(false);
                    return;
                }

                feedback.textContent = result.data.message || 'Invitations envoyees.';
                feedback.classList.add('is-success');

                if (typeof window.commonTopbarCloseModal === 'function') {
                    window.commonTopbarCloseModal();
                }

                if (result.data.redirectUrl && typeof window.omoDecisionOpenNestedDrawer === 'function') {
                    window.omoDecisionOpenNestedDrawer(result.data.drawerTitle || 'Prises de decision', result.data.redirectUrl, '');
                    return;
                }

                if (result.data.redirectUrl && typeof window.commonTopbarOpenDrawer === 'function') {
                    window.commonTopbarOpenDrawer(result.data.drawerTitle || 'Prises de decision', result.data.redirectUrl, 'fetch');
                }
            })
            .catch(function () {
                feedback.textContent = 'Impossible d envoyer ces invitations pour le moment.';
                applyAvailabilityState(false);
            });
    });

    applyAvailabilityState(false);
})();
};
