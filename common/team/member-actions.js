window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/common/team/member-actions.js"] = function (pageConfig, pageScript) {
(function () {
    const memberActionsText = pageConfig.memberActionsText;
    const root = document.getElementById('omoMemberActionsPopup');
    const feedback = document.getElementById('omoMemberActionsFeedback');

    if (!root || !feedback) {
        return;
    }

    const userId = Number(root.getAttribute('data-user-id') || 0);
    const organizationId = Number(root.getAttribute('data-oid') || 0);
    const currentHolonId = Number(root.getAttribute('data-hid') || 0);
    const rootHolonId = Number(root.getAttribute('data-root-hid') || 0);
    const contextLabel = String(root.getAttribute('data-context-label') || '').trim() || memberActionsText.defaultContextLabel;
    const displayName = String(root.getAttribute('data-display-name') || '').trim() || memberActionsText.defaultMemberLabel;

    function formatText(template, variables) {
        let text = String(template || '');

        Object.keys(variables || {}).forEach(function (key) {
            text = text.split('{' + key + '}').join(String(variables[key] == null ? '' : variables[key]));
        });

        return text;
    }

    function setFeedback(message, isError) {
        feedback.textContent = message || '';
        feedback.classList.toggle('is-error', !!isError);
        feedback.classList.toggle('is-success', !isError && !!message);
    }

    function refreshContext() {
        if (typeof refreshDrawer === 'function') {
            let drawerUrl = '/omo/api/team/index.php?oid=' + organizationId;
            if (currentHolonId > 0 && currentHolonId !== rootHolonId) {
                drawerUrl += '&cid=' + currentHolonId;
            }
            refreshDrawer('drawer_team', drawerUrl);
        }

        if (typeof loadContent === 'function') {
            let leftUrl = 'api/getOrg.php?oid=' + organizationId;
            if (currentHolonId > 0 && currentHolonId !== rootHolonId) {
                leftUrl += '&cid=' + currentHolonId;
            }
            loadContent('#panel-left', leftUrl);
        }

        if (typeof window.omoReloadStructureAndFocus === 'function') {
            window.omoReloadStructureAndFocus(currentHolonId > 0 && currentHolonId !== rootHolonId ? currentHolonId : null, {
                quickZoom: true
            });
        }
    }

    root.querySelectorAll('[data-member-popup-action]').forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();

            const action = String(button.getAttribute('data-member-popup-action') || '').trim();
            let confirmationMessage = '';

            if (!action || !userId || !organizationId || !currentHolonId) {
                return;
            }

            if (action === 'remove') {
                confirmationMessage = formatText(memberActionsText.confirmRemove, {name: displayName, context: contextLabel});
            } else if (action === 'grant_admin') {
                confirmationMessage = formatText(memberActionsText.confirmGrantAdmin, {name: displayName, context: contextLabel});
            } else if (action === 'revoke_admin') {
                confirmationMessage = formatText(memberActionsText.confirmRevokeAdmin, {name: displayName, context: contextLabel});
            } else {
                return;
            }

            if (!window.confirm(confirmationMessage)) {
                return;
            }

            setFeedback('', false);
            button.disabled = true;

            const formData = new FormData();
            formData.append('hid', String(currentHolonId));
            formData.append('oid', String(organizationId));
            formData.append('user_id', String(userId));
            formData.append('action', action);

            const memberActionUrl = typeof window.omoResolveAppUrl === 'function'
                ? window.omoResolveAppUrl('/omo/api/team/member_action.php')
                : '/omo/api/team/member_action.php';

            fetch(memberActionUrl, {
                method: 'POST',
                body: formData,
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
                    button.disabled = false;

                    if (!result.ok || !result.data || !result.data.status) {
                        setFeedback(result.data && result.data.message ? result.data.message : memberActionsText.updateFailed, true);
                        return;
                    }

                    setFeedback(result.data.message || memberActionsText.updateSuccess, false);
                    refreshContext();

                    window.setTimeout(function () {
                        if (typeof window.omoSetPopupHashState === 'function') {
                            window.omoSetPopupHashState({ open: false });
                        } else if (typeof window.commonTopbarCloseModal === 'function') {
                            window.commonTopbarCloseModal();
                        }
                    }, 220);
                })
                .catch(function () {
                    button.disabled = false;
                    setFeedback(memberActionsText.updateFailedLater, true);
                });
        });
    });
})();
};
