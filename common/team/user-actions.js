(function () {
    var root = document.querySelector('.omo-user-context[data-user-competence-popup-url]');
    if (!root) {
        return;
    }

    var feedback = root.querySelector('[data-user-competence-feedback="1"]');
    var openMenu = null;

    function setFeedback(message, type) {
        if (!feedback) {
            return;
        }

        feedback.textContent = message || '';
        feedback.className = 'omo-user-context__competence-feedback';
        if (type === 'success') {
            feedback.classList.add('is-success');
        } else if (type === 'error') {
            feedback.classList.add('is-error');
        }
    }

    function parseResponse(response) {
        return response.text().then(function (text) {
            try {
                return JSON.parse(text);
            } catch (error) {
                return {
                    status: false,
                    message: 'Reponse serveur invalide.'
                };
            }
        });
    }

    function reloadPopup() {
        var popupUrl = root.getAttribute('data-user-competence-popup-url') || '';
        if (popupUrl === '') {
            return;
        }

        if (window.jQuery && document.getElementById('popup_content')) {
            window.jQuery('#popup_content').load(popupUrl);
            return;
        }

        if (window.commonTopbarRefreshModalContent) {
            window.commonTopbarRefreshModalContent(popupUrl);
        }
    }

    function closeMenu(menu) {
        if (!menu) {
            return;
        }

        menu.hidden = true;
        var toggle = menu.parentNode ? menu.parentNode.querySelector('[data-user-competence-menu-toggle="1"]') : null;
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
        }
        if (openMenu === menu) {
            openMenu = null;
        }
    }

    function openMenuFor(toggle) {
        var action = toggle.parentNode;
        var menu = action ? action.querySelector('[data-user-competence-menu="1"]') : null;
        if (!menu) {
            return;
        }

        if (openMenu && openMenu !== menu) {
            closeMenu(openMenu);
        }

        menu.hidden = false;
        toggle.setAttribute('aria-expanded', 'true');
        openMenu = menu;
    }

    root.querySelectorAll('[data-user-competence-menu-toggle="1"]').forEach(function (toggle) {
        toggle.addEventListener('click', function (event) {
            event.preventDefault();
            var action = toggle.parentNode;
            var menu = action ? action.querySelector('[data-user-competence-menu="1"]') : null;
            if (!menu) {
                return;
            }

            if (!menu.hidden) {
                closeMenu(menu);
                return;
            }

            openMenuFor(toggle);
        });
    });

    document.addEventListener('click', function (event) {
        if (!openMenu) {
            return;
        }

        if (openMenu.contains(event.target)) {
            return;
        }

        var toggle = openMenu.parentNode ? openMenu.parentNode.querySelector('[data-user-competence-menu-toggle="1"]') : null;
        if (toggle && toggle.contains(event.target)) {
            return;
        }

        closeMenu(openMenu);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && openMenu) {
            closeMenu(openMenu);
        }
    });

    root.querySelectorAll('[data-user-competence-validate-form="1"]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();

            if (typeof window.omoBeginPendingAction === 'function' && !window.omoBeginPendingAction(form)) {
                return;
            }

            setFeedback('', '');

            fetch('/omo/api/user_competence_validate.php', {
                method: 'POST',
                credentials: 'same-origin',
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(parseResponse)
                .then(function (result) {
                    if (!result || !result.status) {
                        setFeedback(result && result.message ? result.message : "Impossible d'enregistrer cette validation.", 'error');
                        return;
                    }

                    setFeedback(result.message || 'Validation enregistree.', 'success');
                    closeMenu(form.closest('[data-user-competence-menu="1"]'));
                    reloadPopup();
                })
                .catch(function () {
                    setFeedback("Impossible d'enregistrer cette validation.", 'error');
                })
                .finally(function () {
                    if (typeof window.omoEndPendingAction === 'function') {
                        window.omoEndPendingAction(form);
                    }
                });
        });
    });
})();
