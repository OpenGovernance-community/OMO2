(function () {
    function getConfig() {
        return window.commonTopbarConfig || {};
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
        } else {
            body.innerHTML = content || '';
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
        titleNode.textContent = title || 'Panneau';
        if (mode === 'iframe') {
            body.innerHTML = '<iframe class="common-topbar-modal__iframe" src="' + content + '"></iframe>';
        } else {
            body.innerHTML = content || '';
        }

        modal.hidden = false;
        document.body.classList.add('common-topbar-modal-open');
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
        if (!modal) {
            return;
        }
        modal.hidden = true;
        if (body) {
            body.innerHTML = '';
        }
        document.body.classList.remove('common-topbar-modal-open');
    }

    function callNamedFunction(name) {
        if (!name || typeof window[name] !== 'function') {
            return false;
        }

        var args = Array.prototype.slice.call(arguments, 1);
        window[name].apply(window, args);
        return true;
    }

    function handleSearchSubmit(event) {
        event.preventDefault();

        var config = getConfig();
        var input = document.querySelector('[data-topbar-search-input]');
        var query = input ? input.value.trim() : '';

        if (callNamedFunction(config.search && config.search.callback, query, config)) {
            return;
        }

        window.dispatchEvent(new CustomEvent('common-topbar-search', {
            detail: { query: query, config: config }
        }));
    }

    function handleHelpItemClick(item) {
        closeMenus();

        if (callNamedFunction(item.callback, item, getConfig())) {
            return;
        }

        if (item.url) {
            if (item.mode === 'drawer') {
                openDrawer(item.title || item.label || 'Aide', item.url, item.contentMode === 'html' ? 'html' : 'iframe');
                return;
            }
            openModal(item.title || item.label || 'Aide', item.url, item.mode === 'iframe' ? 'iframe' : 'html');
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
            openModal(profile.editTitle || 'Profil', profile.editUrl, profile.editMode === 'html' ? 'html' : 'iframe');
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
})();
