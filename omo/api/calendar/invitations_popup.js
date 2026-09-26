window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/omo/api/calendar/invitations_popup.js"] = function (pageConfig, pageScript) {
(function () {
    var form = document.getElementById('omoCalendarInvitationsPopupForm');
    var feedback = document.getElementById('omoCalendarInvitationsPopupFeedback');
    var submitButton = document.getElementById('omoCalendarInvitationsPopupSubmit');

    function initInvitationEditor(scope) {
        if (!scope) {
            return;
        }

        if (typeof window.omoCalendarInitInvitationEditors === 'function') {
            window.omoCalendarInitInvitationEditors(scope);
            return;
        }

        if (typeof window.initGenericComponents === 'function') {
            window.initGenericComponents(scope);
        }

        Array.prototype.forEach.call(scope.querySelectorAll('[data-omo-calendar-holon-toggle]'), function (toggle) {
            if (toggle.dataset.omoCalendarBound === '1') {
                return;
            }

            toggle.dataset.omoCalendarBound = '1';
            toggle.addEventListener('click', function (event) {
                var node;
                var children;
                var isExpanded;

                event.preventDefault();
                event.stopPropagation();

                node = toggle.closest('[data-omo-calendar-holon-node]');
                children = node ? node.querySelector('[data-omo-calendar-holon-children]') : null;
                if (!children) {
                    return;
                }

                isExpanded = toggle.getAttribute('aria-expanded') === 'true';
                toggle.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
                children.hidden = isExpanded;
            });
        });
    }

    if (!form || !feedback || !submitButton) {
        return;
    }

    initInvitationEditor(form);

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        var formData = new FormData(form);
        var usesSharedPendingState = typeof window.omoBeginPendingAction === 'function';

        if (usesSharedPendingState && !window.omoBeginPendingAction(form)) {
            return;
        }
        feedback.textContent = '';
        feedback.classList.remove('is-success');
        if (!usesSharedPendingState) {
            submitButton.disabled = true;
        }

        fetch(form.getAttribute('action'), {
            method: 'POST',
            body: formData,
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
                    feedback.textContent = result.data && result.data.message ? result.data.message : pageConfig.message;
                    return;
                }

                feedback.textContent = result.data.message || pageConfig.calendarInvitationsUpdated;
                feedback.classList.add('is-success');

                if (typeof window.commonTopbarCloseModal === 'function') {
                    window.commonTopbarCloseModal();
                }

                if (result.data.pvEditorContext && typeof window.CustomEvent === 'function') {
                    window.dispatchEvent(new CustomEvent('omo:pv-invitations-updated', {
                        detail: {
                            documentId: Number(result.data.pvDocumentId || 0)
                        }
                    }));
                }

                if (!result.data.pvEditorContext && result.data.detailUrl && typeof window.omoCalendarOpenEventDrawer === 'function') {
                    window.omoCalendarOpenEventDrawer(result.data.detailUrl);
                }
                if (typeof window.omoCalendarRefreshCurrentView === 'function') {
                    window.omoCalendarRefreshCurrentView();
                }
            })
            .catch(function () {
                feedback.textContent = pageConfig.calendarInvitationsJsRequestError;
            })
            .finally(function () {
                if (usesSharedPendingState && typeof window.omoEndPendingAction === 'function') {
                    window.omoEndPendingAction(form);
                } else {
                    submitButton.disabled = false;
                }
            });
    });
})();
};
