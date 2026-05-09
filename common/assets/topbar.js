(function () {
    function getConfig() {
        return window.commonTopbarConfig || {};
    }

    function runContainerCleanup(container) {
        if (!container || container.id !== 'commonTopbarModalBody') {
            return;
        }

        if (typeof window.__omoPopupCleanup === 'function') {
            window.__omoPopupCleanup();
            window.__omoPopupCleanup = null;
        } else if (typeof window.__omoFaqPopupCleanup === 'function') {
            window.__omoFaqPopupCleanup();
            window.__omoFaqPopupCleanup = null;
        }
    }

    function executeEmbeddedScripts(container) {
        if (!container) {
            return;
        }

        Array.prototype.forEach.call(container.querySelectorAll('script'), function (script) {
            var replacement = document.createElement('script');

            Array.prototype.forEach.call(script.attributes, function (attribute) {
                replacement.setAttribute(attribute.name, attribute.value);
            });

            if (!replacement.src) {
                replacement.textContent = script.textContent || '';
            }

            script.parentNode.replaceChild(replacement, script);
        });
    }

    function enhanceScrollablePanel(container) {
        if (!container) {
            return;
        }

        Array.prototype.forEach.call(
            container.querySelectorAll('.common-topbar__sticky-actions'),
            function (node) {
                node.classList.remove('common-topbar__sticky-actions');
            }
        );

        var selectors = [
            '[class*="__actions"]',
            '[class$="__footer"]'
        ];
        var candidates = Array.prototype.filter.call(
            container.querySelectorAll(selectors.join(',')),
            function (node) {
                if (!node || node.offsetParent === null) {
                    return false;
                }

                var buttons = node.querySelectorAll('button, input[type="submit"], a.generic-action-button');
                if (!buttons.length) {
                    return false;
                }

                if (node.querySelector('button[type="submit"], input[type="submit"], .generic-action-button--main')) {
                    return true;
                }

                return buttons.length >= 2;
            }
        );

        if (!candidates.length) {
            return;
        }

        candidates[candidates.length - 1].classList.add('common-topbar__sticky-actions');
    }

    function renderRemoteContent(container, url) {
        if (!container) {
            return;
        }

        runContainerCleanup(container);
        container.innerHTML = '<div class="loading">Chargement...</div>';

        fetch(url, {
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Erreur de chargement');
                }

                return response.text();
            })
            .then(function (html) {
                container.innerHTML = html;
                executeEmbeddedScripts(container);
                enhanceScrollablePanel(container);
                window.setTimeout(function () {
                    enhanceScrollablePanel(container);
                }, 0);
            })
            .catch(function () {
                container.innerHTML = '<div class="loading">Erreur de chargement</div>';
            });
    }

    function closeMenus() {
        document.querySelectorAll('.common-topbar__menu.is-open').forEach(function (menu) {
            menu.classList.remove('is-open');
        });
    }

    function openDrawer(title, content, mode) {
        var drawer = document.getElementById('commonTopbarDrawer');
        var body = document.getElementById('commonTopbarDrawerBody');
        var titleNode = document.getElementById('commonTopbarDrawerTitle');

        if (!drawer || !body || !titleNode) {
            return;
        }

        closeModal();
        closeDrawer();

        titleNode.textContent = title || 'Panneau latéral';
        if (mode === 'iframe') {
            body.innerHTML = '<iframe class="common-topbar-drawer__iframe" src="' + content + '"></iframe>';
        } else if (mode === 'fetch') {
            renderRemoteContent(body, content);
        } else {
            body.innerHTML = content || '';
            enhanceScrollablePanel(body);
        }

        drawer.hidden = false;
        document.body.classList.add('common-topbar-drawer-open');
    }

    function openModal(title, content, mode) {
        var modal = document.getElementById('commonTopbarModal');
        var body = document.getElementById('commonTopbarModalBody');
        var titleNode = document.getElementById('commonTopbarModalTitle');

        if (!modal || !body || !titleNode) {
            return;
        }

        closeDrawer();
        closeModal();
        titleNode.textContent = title || 'Panneau';
        if (mode === 'iframe') {
            body.innerHTML = '<iframe class="common-topbar-modal__iframe" src="' + content + '"></iframe>';
        } else if (mode === 'fetch') {
            renderRemoteContent(body, content);
        } else {
            body.innerHTML = content || '';
            enhanceScrollablePanel(body);
        }

        modal.hidden = false;
        document.body.classList.add('common-topbar-modal-open');
        window.dispatchEvent(new CustomEvent('common-topbar-modal-open', {
            detail: {
                title: title || 'Panneau',
                content: content,
                mode: mode || 'html'
            }
        }));
    }

    function closeDrawer() {
        var drawer = document.getElementById('commonTopbarDrawer');
        var body = document.getElementById('commonTopbarDrawerBody');
        if (!drawer) {
            return;
        }
        drawer.hidden = true;
        if (body) {
            body.innerHTML = '';
        }
        document.body.classList.remove('common-topbar-drawer-open');
    }

    function closeModal() {
        var modal = document.getElementById('commonTopbarModal');
        var body = document.getElementById('commonTopbarModalBody');
        var wasHidden = !modal || modal.hidden;
        if (!modal) {
            return;
        }
        modal.hidden = true;
        if (body) {
            runContainerCleanup(body);
            body.innerHTML = '';
            body.removeAttribute('data-omo-faq-modal');
            body.removeAttribute('data-omo-popup-key');
            body.removeAttribute('data-omo-popup-url');
            body.removeAttribute('data-omo-popup-live-sync');
        }
        document.body.classList.remove('common-topbar-modal-open');
        if (!wasHidden) {
            window.dispatchEvent(new CustomEvent('common-topbar-modal-close'));
        }
    }

    function callNamedFunction(name) {
        if (!name || typeof window[name] !== 'function') {
            return false;
        }

        var args = Array.prototype.slice.call(arguments, 1);
        window[name].apply(window, args);
        return true;
    }

    function renderSearchScopes(menu) {
        if (!menu) {
            return [];
        }

        var config = getConfig();
        var searchConfig = config.search || {};
        var scopeProvider = searchConfig.scopeProvider || '';
        var scopesContainer = menu.querySelector('[data-topbar-search-scopes]');
        var scopeList = menu.querySelector('[data-topbar-search-scope-list]');

        if (!scopesContainer || !scopeList) {
            return [];
        }

        scopeList.innerHTML = '';

        if (!scopeProvider || typeof window[scopeProvider] !== 'function') {
            scopesContainer.hidden = true;
            return [];
        }

        var scopes = window[scopeProvider](config);
        if (!Array.isArray(scopes) || scopes.length === 0) {
            scopesContainer.hidden = true;
            return [];
        }

        scopes.forEach(function (scope, index) {
            if (!scope || !scope.id || !scope.label) {
                return;
            }

            var wrapper = document.createElement('label');
            wrapper.className = 'common-topbar__search-scope';

            var input = document.createElement('input');
            input.type = 'checkbox';
            input.className = 'common-topbar__search-scope-input';
            input.setAttribute('data-topbar-search-scope-input', '1');
            input.value = String(scope.id);
            input.checked = !!scope.checked;

            var label = document.createElement('span');
            label.className = 'common-topbar__search-scope-label';
            label.textContent = String(scope.label);

            wrapper.appendChild(input);
            wrapper.appendChild(label);
            scopeList.appendChild(wrapper);
        });

        scopesContainer.hidden = scopeList.children.length === 0;
        return scopes;
    }

    function readSelectedSearchScopes(form) {
        if (!form) {
            return [];
        }

        return Array.prototype.map.call(
            form.querySelectorAll('[data-topbar-search-scope-input]:checked'),
            function (input, index) {
                var label = input.closest('.common-topbar__search-scope');
                var labelNode = label ? label.querySelector('.common-topbar__search-scope-label') : null;

                return {
                    id: input.value,
                    label: labelNode ? labelNode.textContent : input.value,
                    position: index
                };
            }
        );
    }

    function handleSearchSubmit(event) {
        event.preventDefault();

        var config = getConfig();
        var form = event.target && event.target.matches('[data-topbar-search-form]')
            ? event.target
            : document.querySelector('[data-topbar-search-form]');
        var input = form ? form.querySelector('[data-topbar-search-input]') : document.querySelector('[data-topbar-search-input]');
        var query = input ? input.value.trim() : '';
        var searchState = {
            query: query,
            scopes: readSelectedSearchScopes(form),
            config: config
        };

        closeMenus();

        if (callNamedFunction(config.search && config.search.callback, query, config, searchState)) {
            return;
        }

        window.dispatchEvent(new CustomEvent('common-topbar-search', {
            detail: searchState
        }));
    }

    function handleHelpItemClick(item) {
        closeMenus();

        if (callNamedFunction(item.callback, item, getConfig())) {
            return;
        }

        if (item.url) {
            if (item.mode === 'drawer') {
                openDrawer(
                    item.title || item.label || 'Aide',
                    item.url,
                    item.contentMode === 'html' ? 'html' : (item.contentMode === 'fetch' ? 'fetch' : 'iframe')
                );
                return;
            }
            openModal(
                item.title || item.label || 'Aide',
                item.url,
                item.mode === 'fetch' ? 'fetch' : (item.mode === 'iframe' ? 'iframe' : 'html')
            );
            return;
        }

        openModal(item.title || item.label || 'Aide', item.html || '<p>Contenu à venir.</p>', 'html');
    }

    function handleProfileEdit() {
        var config = getConfig();
        var profile = config.profile || {};
        closeMenus();

        if (callNamedFunction(profile.editCallback, profile, config)) {
            return;
        }

        if (profile.editUrl) {
            openModal(
                profile.editTitle || 'Profil',
                profile.editUrl,
                profile.editMode === 'fetch' ? 'fetch' : (profile.editMode === 'html' ? 'html' : 'iframe')
            );
        }
    }

    function handleLogout() {
        var config = getConfig();
        var target = (config.logoutReturnTo || window.location.pathname || '/');

        fetch((config.logoutPath || '/common/logout.php') + '?return_to=' + encodeURIComponent(target), {
            headers: {
                'Accept': 'application/json'
            }
        })
            .then(function () {
                window.location.href = target;
            })
            .catch(function () {
                window.location.href = target;
            });
    }

    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-topbar-menu-trigger]');
        if (trigger) {
            var name = trigger.getAttribute('data-topbar-menu-trigger');
            var menu = document.querySelector('[data-topbar-menu="' + name + '"]');
            var isOpen = menu && menu.classList.contains('is-open');
            closeMenus();
            if (menu && !isOpen) {
                menu.classList.add('is-open');
                if (name === 'search') {
                    renderSearchScopes(menu);
                }
                var searchInput = menu.querySelector('[data-topbar-search-input]');
                if (searchInput) {
                    setTimeout(function () {
                        searchInput.focus();
                    }, 20);
                }
            }
            return;
        }

        var helpItem = event.target.closest('[data-topbar-help-item]');
        if (helpItem) {
            try {
                handleHelpItemClick(JSON.parse(helpItem.getAttribute('data-topbar-help-item')));
            } catch (e) {
                handleHelpItemClick({ label: 'Aide', html: '<p>Contenu indisponible.</p>' });
            }
            return;
        }

        if (event.target.closest('[data-topbar-profile-edit]')) {
            handleProfileEdit();
            return;
        }

        if (event.target.closest('[data-topbar-logout]')) {
            handleLogout();
            return;
        }

        if (event.target.closest('[data-topbar-modal-close]')) {
            closeModal();
            return;
        }

        if (event.target.closest('[data-topbar-drawer-close]')) {
            closeDrawer();
            return;
        }

        if (!event.target.closest('.common-topbar__menu-wrap')) {
            closeMenus();
        }
    });

    document.addEventListener('submit', function (event) {
        if (event.target.matches('[data-topbar-search-form]')) {
            handleSearchSubmit(event);
        }
    });

    window.commonTopbarOpenModal = openModal;
    window.commonTopbarCloseModal = closeModal;
    window.commonTopbarOpenDrawer = openDrawer;
    window.commonTopbarCloseDrawer = closeDrawer;
    window.commonTopbarRefreshModalContent = function (url) {
        var body = document.getElementById('commonTopbarModalBody');
        if (!body || !url) {
            return;
        }

        renderRemoteContent(body, url);
    };
    window.commonTopbarRefreshDrawerContent = function (url) {
        var body = document.getElementById('commonTopbarDrawerBody');
        if (!body || !url) {
            return;
        }

        renderRemoteContent(body, url);
    };
})();
