window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/omo/api/decision/invitations_popup.js"] = function (pageConfig, pageScript) {
(function () {
    var form = document.getElementById('omoDecisionInvitationsPopupForm');
    var feedback = document.getElementById('omoDecisionInvitationsPopupFeedback');
    var submitButton = document.getElementById('omoDecisionInvitationsPopupSubmit');
    var isDraft = form ? form.getAttribute('data-omo-decision-invitations-draft') === '1' : false;
    var draftFormId = form ? String(form.getAttribute('data-omo-decision-invitations-draft-form-id') || '') : '';
    var draftTargetForm = isDraft && window.omoDecisionInvitationDraftTargetForm instanceof HTMLFormElement
        ? window.omoDecisionInvitationDraftTargetForm
        : (draftFormId !== '' ? document.getElementById(draftFormId) : null);

    if (!form || !feedback || !submitButton) {
        return;
    }

    if (typeof window.initGenericComponents === 'function') {
        window.initGenericComponents(form);
    }

    function getDraftValues(name) {
        if (!draftTargetForm) {
            return [];
        }

        return Array.prototype.map.call(draftTargetForm.querySelectorAll('[name="' + name + '"]'), function (input) {
            return String(input.value || '');
        }).filter(function (value) {
            return value !== '';
        });
    }

    function synchronizeDraftSelection() {
        var holonIds;
        var userIds;
        var emailInput;
        var publicInput;

        if (!draftTargetForm || !draftTargetForm.querySelector('[name="invitation_inline_enabled"]')) {
            return;
        }

        holonIds = getDraftValues('invitation_holon_ids[]');
        userIds = getDraftValues('invitation_user_ids[]');
        Array.prototype.forEach.call(form.querySelectorAll('[name="holon_ids[]"]'), function (input) {
            input.checked = holonIds.indexOf(String(input.value || '')) !== -1;
        });
        Array.prototype.forEach.call(form.querySelectorAll('[name="user_ids[]"]'), function (input) {
            input.checked = userIds.indexOf(String(input.value || '')) !== -1;
        });

        emailInput = form.querySelector('[name="emails"]');
        if (emailInput) {
            emailInput.value = getDraftValues('invitation_emails').join('\n');
        }

        publicInput = form.querySelector('[name="allow_public_self_registration"]');
        if (publicInput) {
            publicInput.checked = !!draftTargetForm.querySelector('[name="allow_public_self_registration"][value="1"]');
        }
    }

    function appendDraftField(container, name, value) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        container.appendChild(input);
    }

    function getSelectedLabels(name) {
        return Array.prototype.map.call(form.querySelectorAll('[name="' + name + '"]:checked'), function (input) {
            var label = input.closest('label');
            var title = label ? label.querySelector('strong') : null;
            return title ? String(title.textContent || '').trim() : '';
        }).filter(function (value) {
            return value !== '';
        });
    }

    function applyDraftSelection() {
        var fields = draftTargetForm ? draftTargetForm.querySelector('[data-omo-decision-invitations-draft-fields]') : null;
        var formData = new FormData(form);
        var emails = String(formData.get('emails') || '').split(/[\r\n,;]+/).map(function (email) {
            return email.trim();
        }).filter(function (email, index, values) {
            return email !== '' && values.indexOf(email) === index;
        });
        var summary;
        var summaryStrong;
        var summaryParts = getSelectedLabels('holon_ids[]');
        var userCount = formData.getAll('user_ids[]').length;

        if (!fields) {
            return false;
        }

        fields.replaceChildren();
        appendDraftField(fields, 'invitation_inline_enabled', '1');
        formData.getAll('holon_ids[]').forEach(function (value) {
            appendDraftField(fields, 'invitation_holon_ids[]', String(value));
        });
        formData.getAll('user_ids[]').forEach(function (value) {
            appendDraftField(fields, 'invitation_user_ids[]', String(value));
        });
        appendDraftField(fields, 'invitation_emails', emails.join('\n'));
        if (formData.has('allow_public_self_registration')) {
            appendDraftField(fields, 'allow_public_self_registration', '1');
        }

        if (userCount > 0) {
            summaryParts.push(userCount + (userCount > 1 ? ' membres' : ' membre'));
        }
        if (emails.length > 0) {
            summaryParts.push(emails.length + (emails.length > 1 ? ' invités' : ' invité'));
        }
        if (formData.has('allow_public_self_registration')) {
            summaryParts.push('Participation publique ouverte');
        }

        summary = draftTargetForm.querySelector('[data-omo-decision-invitations-summary]');
        summaryStrong = summary ? summary.querySelector('strong') : null;
        if (summaryStrong) {
            summaryStrong.textContent = summaryParts.length > 0
                ? summaryParts.join(', ')
                : 'Aucune invitation explicite';
        }

        return true;
    }

    synchronizeDraftSelection();

    Array.prototype.forEach.call(form.querySelectorAll('[data-omo-decision-holon-toggle]'), function (toggle) {
        toggle.addEventListener('click', function (event) {
            var node;
            var children;
            var isExpanded;

            event.preventDefault();
            event.stopPropagation();

            node = toggle.closest('[data-omo-decision-holon-node]');
            children = node ? node.querySelector('[data-omo-decision-holon-children]') : null;
            if (!children) {
                return;
            }

            isExpanded = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
            children.hidden = isExpanded;
        });
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        feedback.textContent = '';
        feedback.classList.remove('is-success');

        if (isDraft) {
            if (!draftTargetForm || !applyDraftSelection()) {
                feedback.textContent = 'Impossible de retrouver le formulaire de création.';
                return;
            }
            window.omoDecisionInvitationDraftTargetForm = null;
            if (typeof window.commonTopbarCloseModal === 'function') {
                window.commonTopbarCloseModal();
            }
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
                    submitButton.disabled = false;
                    return;
                }

                feedback.textContent = result.data.message || pageConfig.decisionsInvitationsPopupUpdated;
                feedback.classList.add('is-success');

                if (typeof window.commonTopbarCloseModal === 'function') {
                    window.commonTopbarCloseModal();
                }

                if (result.data.redirectUrl && typeof window.omoDecisionOpenNestedDrawer === 'function') {
                    window.omoDecisionOpenNestedDrawer(result.data.drawerTitle || pageConfig.decisionsInvitationsPopupDrawerTitle, result.data.redirectUrl, '');
                    return;
                }

                if (result.data.redirectUrl && typeof window.commonTopbarOpenDrawer === 'function') {
                    window.commonTopbarOpenDrawer(result.data.drawerTitle || pageConfig.decisionsInvitationsPopupDrawerTitle, result.data.redirectUrl, 'fetch');
                }
            })
            .catch(function () {
                feedback.textContent = pageConfig.decisionsInvitationsPopupJsRequestError;
                submitButton.disabled = false;
            });
    });
})();
};
