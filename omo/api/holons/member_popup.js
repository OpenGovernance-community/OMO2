window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/omo/api/holons/member_popup.js"] = function (pageConfig, pageScript) {
    (function () {
        var form = document.getElementById('omoHolonMemberPopupForm');
        var feedback = document.getElementById('omoHolonMemberPopupFeedback');
        var submitButton = document.getElementById('omoHolonMemberPopupSubmit');
        var select = document.getElementById('omoHolonMemberExistingUser');
        var emailInput = document.getElementById('omoHolonMemberEmail');
        var holonId = pageConfig.holonId;
        var rootHolonId = pageConfig.rootHolonId;
        var organizationId = pageConfig.organizationId;

        if (!form || !feedback || !submitButton || !select || !emailInput) {
            return;
        }

        select.addEventListener('change', function () {
            if (select.value) {
                emailInput.value = '';
            }
        });

        emailInput.addEventListener('input', function () {
            if (emailInput.value.trim() !== '') {
                select.value = '';
            }
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            feedback.textContent = '';
            feedback.classList.remove('is-success');

            if (!select.value && !emailInput.value.trim()) {
                feedback.textContent = 'Choisissez une personne ou saisissez une adresse e-mail.';
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
                        feedback.textContent = result.data && result.data.message ? result.data.message : 'Une erreur est survenue.';
                        submitButton.disabled = false;
                        return;
                    }

                    feedback.textContent = result.data.message || 'Membre ajouté.';
                    feedback.classList.add('is-success');

                    if (typeof refreshDrawer === 'function') {
                        var drawerUrl = '/omo/api/team/index.php?oid=' + organizationId;
                        if (holonId > 0 && holonId !== rootHolonId) {
                            drawerUrl += '&cid=' + holonId;
                        }
                        refreshDrawer('drawer_team', drawerUrl);
                    }

                    if (typeof loadContent === 'function') {
                        var leftUrl = 'api/getOrg.php?oid=' + organizationId;
                        if (holonId > 0 && holonId !== rootHolonId) {
                            leftUrl += '&cid=' + holonId;
                        }
                        loadContent(typeof omoGetLeftPanelContentSelector === 'function' ? omoGetLeftPanelContentSelector() : '#panel-left', leftUrl);
                    }

                    if (typeof window.omoReloadStructureAndFocus === 'function') {
                        window.omoReloadStructureAndFocus(holonId > 0 ? holonId : null, {
                            quickZoom: true
                        });
                    } else {
                        window.dispatchEvent(new CustomEvent('omo-structure-refresh', {
                            detail: {
                                cid: holonId > 0 ? holonId : null
                            }
                        }));
                    }

                    window.setTimeout(function () {
                        if (typeof window.commonTopbarCloseModal === 'function') {
                            window.commonTopbarCloseModal();
                        }
                    }, 250);
                })
                .catch(function () {
                    feedback.textContent = "Impossible d'ajouter ce membre pour le moment.";
                    submitButton.disabled = false;
                });
        });
    })();
};
