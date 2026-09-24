(function () {
    var root = document.querySelector('.omo-user-context');
    var modalBody = document.getElementById('commonTopbarModalBody');
    var fragmentLoadingMessage = 'Chargement...';
    var fragmentErrorMessage = 'Impossible de charger cet onglet pour le moment.';

    if (!root) {
        return;
    }

    if (modalBody) {
        modalBody.setAttribute('data-omo-popup-live-sync', '1');
    }

    var initialTab = String(root.getAttribute('data-user-initial-tab') || '').trim();
    if (initialTab !== '') {
        window.setTimeout(function () {
            root.querySelectorAll('[data-generic-tab]').forEach(function (tab) {
                var target = String(tab.getAttribute('data-generic-tab-target') || '');
                if (target === 'omo-user-context-panel-' + initialTab) {
                    tab.click();
                }
            });
        }, 0);
    }

    function loadFragmentHost(host) {
        var fragmentUrl;

        if (!host) {
            return;
        }

        fragmentUrl = String(host.getAttribute('data-user-fragment-url') || '').trim();
        if (fragmentUrl === '') {
            return;
        }

        if (host.getAttribute('data-user-fragment-loaded') === '1') {
            return;
        }

        host.innerHTML = '<div class="omo-user-context__fragment-feedback">' + fragmentLoadingMessage + '</div>';
        fetch(fragmentUrl, {
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('load');
                }

                return response.text();
            })
            .then(function (html) {
                host.innerHTML = html;
                host.setAttribute('data-user-fragment-loaded', '1');
            })
            .catch(function () {
                host.innerHTML = '<div class="omo-user-context__fragment-feedback is-error">' + fragmentErrorMessage + '</div>';
            });
    }

    root.querySelectorAll('[data-user-fragment-panel]').forEach(function (button) {
        button.addEventListener('click', function () {
            var panelId = String(button.getAttribute('data-user-fragment-panel') || '').trim();
            var panel = panelId !== '' ? document.getElementById(panelId) : null;
            var host = panel ? panel.querySelector('[data-user-fragment-host="1"]') : null;
            loadFragmentHost(host);
        });
    });

    root.querySelectorAll('[data-user-role-cid]').forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            var cid = Number(button.getAttribute('data-user-role-cid') || '0');
            if (!Number.isInteger(cid) || cid <= 0) {
                return;
            }

            if (typeof navigate !== 'function' || typeof parseUrl !== 'function') {
                return;
            }

            var route = parseUrl();
            navigate(route.oid, cid, route.hash || null);

            if (typeof omoFocusStructureNode === 'function') {
                window.setTimeout(function () {
                    omoFocusStructureNode(cid, { quickZoom: true });
                }, 140);
            }
        });
    });

    var openRoleMenu = null;

    function closeRoleMenu(menu) {
        if (!menu) {
            return;
        }

        var panel = menu.querySelector('[data-user-role-menu-panel="1"]');
        var toggle = menu.querySelector('[data-user-role-menu-toggle="1"]');
        if (panel) {
            panel.hidden = true;
        }
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
        }
        var role = menu.closest('.omo-user-context__role');
        if (role) {
            role.classList.remove('is-menu-open');
        }
        if (openRoleMenu === menu) {
            openRoleMenu = null;
        }
    }

    root.querySelectorAll('[data-user-role-menu-toggle="1"]').forEach(function (toggle) {
        toggle.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            var menu = toggle.closest('[data-user-role-menu="1"]');
            var panel = menu ? menu.querySelector('[data-user-role-menu-panel="1"]') : null;
            if (!menu || !panel) {
                return;
            }

            if (openRoleMenu === menu) {
                closeRoleMenu(menu);
                return;
            }

            closeRoleMenu(openRoleMenu);
            panel.hidden = false;
            toggle.setAttribute('aria-expanded', 'true');
            var role = menu.closest('.omo-user-context__role');
            if (role) {
                role.classList.add('is-menu-open');
            }
            openRoleMenu = menu;
        });
    });

    root.querySelectorAll('[data-user-role-edit-url]').forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            var editorUrl = String(button.getAttribute('data-user-role-edit-url') || '').trim();
            if (editorUrl === '') {
                return;
            }

            closeRoleMenu(openRoleMenu);
            if (typeof window.commonTopbarRefreshModalContent === 'function') {
                window.commonTopbarRefreshModalContent(editorUrl);
                return;
            }

            if (typeof window.commonTopbarOpenModal === 'function') {
                window.commonTopbarOpenModal('Editer l affectation', editorUrl, 'fetch');
            }
        });
    });

    document.addEventListener('click', function (event) {
        if (openRoleMenu && !openRoleMenu.contains(event.target)) {
            closeRoleMenu(openRoleMenu);
        }
    });

    root.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeRoleMenu(openRoleMenu);
        }
    });
})();
