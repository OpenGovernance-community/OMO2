window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/omo/api/holons/delete_popup.js"] = function (pageConfig, pageScript) {
    (function () {
        var form = document.getElementById('omoHolonDeletePopupForm');
        var feedback = document.getElementById('omoHolonDeletePopupFeedback');
        var submitButton = document.getElementById('omoHolonDeletePopupSubmit');
        var cancelButton = document.getElementById('omoHolonDeletePopupCancel');
        var parentId = pageConfig.parentId;
        var parentIsRoot = pageConfig.parentIsRoot;
        var organizationId = pageConfig.organizationId;

        if (!form || !feedback || !submitButton || !cancelButton) {
            return;
        }

        function closePopup() {
            if (typeof window.omoSetPopupHashState === 'function') {
                window.omoSetPopupHashState({
                    open: false
                });
                return;
            }

            if (typeof window.commonTopbarCloseModal === 'function') {
                window.commonTopbarCloseModal();
            }
        }

        cancelButton.addEventListener('click', function () {
            closePopup();
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            feedback.textContent = '';
            feedback.classList.remove('is-success');
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
                        feedback.textContent = result.data && result.data.message ? result.data.message : 'Une erreur est survenue.';
                        submitButton.disabled = false;
                        return;
                    }

                    closePopup();

                    var targetCid = parentId > 0 && !parentIsRoot ? parentId : null;
                    var route = typeof parseUrl === 'function'
                        ? parseUrl()
                        : { oid: organizationId, cid: null, hash: null };

                    if (typeof navigate === 'function') {
                        navigate(route.oid, targetCid, route.hash || null);
                    } else if (typeof loadContent === 'function') {
                        var leftUrl = 'api/getOrg.php?oid=' + Number(route.oid || organizationId || 0);
                        if (targetCid) {
                            leftUrl += '&cid=' + targetCid;
                        }
                        loadContent(typeof omoGetLeftPanelContentSelector === 'function' ? omoGetLeftPanelContentSelector() : '#panel-left', leftUrl);
                    }

                    if (typeof window.omoReloadStructureAndFocus === 'function') {
                        window.omoReloadStructureAndFocus(targetCid, {
                            quickZoom: true
                        });
                    } else {
                        window.dispatchEvent(new CustomEvent('omo-structure-refresh', {
                            detail: {
                                cid: targetCid
                            }
                        }));
                    }
                })
                .catch(function () {
feedback.textContent = 'Impossible de supprimer cet espace pour le moment.';
                    submitButton.disabled = false;
                });
        });
    })();
};
