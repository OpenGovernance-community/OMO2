(function (window, document) {
    'use strict';
    if (window.commonPanelViewActions) return;

    var texts = {more: 'Autres options', fullscreen: 'Plein \u00e9cran', exitFullscreen: 'Quitter le plein \u00e9cran'};
    var active = null;
    var ownsNativeFullscreen = false;
    var nativeRequestPending = false;
    var observer = null;
    var sequence = 0;
    var translationsRequested = false;
    var options = {};

    function updateLabels() {
        document.querySelectorAll('[data-common-panel-fullscreen]').forEach(function (button) {
            var label = active && button.closest('[data-common-panel-view]') === active.root ? texts.exitFullscreen : texts.fullscreen;
            if (button.hasAttribute('data-common-panel-fullscreen-icon')) {
                button.setAttribute('aria-label', label);
                button.title = label;
            } else if (button.textContent !== label) button.textContent = label;
        });
        document.querySelectorAll('[data-common-panel-menu-toggle]').forEach(function (button) {
            button.setAttribute('aria-label', texts.more);
            button.title = texts.more;
        });
    }

    function loadTranslations() {
        if (translationsRequested || typeof window.fetch !== 'function') return;
        translationsRequested = true;
        var locale = options.locale || document.documentElement.lang || 'fr';
        window.fetch('/common/jstranslation/panel_view_actions.php?lang=' + encodeURIComponent(locale), {credentials: 'same-origin'})
            .then(function (response) { if (!response.ok) throw new Error('translation_unavailable'); return response.json(); })
            .then(function (payload) { Object.keys(texts).forEach(function (key) { if (typeof payload[key] === 'string') texts[key] = payload[key]; }); updateLabels(); })
            .catch(function () {});
    }

    function closeMenus(root, preserveHeader) {
        root.querySelectorAll('.generic-menu, [data-common-panel-actions-menu]').forEach(function (menu) {
            if (menu.classList.contains('generic-menu--expanded-mobile') && typeof window.resetGenericExpandedMenu === 'function') {
                window.resetGenericExpandedMenu(menu);
                return;
            }
            menu.classList.remove('is-open');
            var panel = menu.querySelector('.generic-menu-panel, [data-common-panel-actions-panel]');
            var toggle = menu.querySelector('.generic-menu-toggle, [data-common-panel-actions-toggle]');
            if (panel && panel.classList.contains('generic-menu-panel')) panel.hidden = true;
            if (toggle) toggle.setAttribute('aria-expanded', 'false');
        });
        if (!preserveHeader && typeof options.closeMenus === 'function') options.closeMenus();
    }

    function resize() {
        window.dispatchEvent(new Event('resize'));
    }

    function exit() {
        if (!active) return;
        var previous = active;
        active = null;
        if (observer) { observer.disconnect(); observer = null; }
        previous.host.classList.remove('generic-panel-fullscreen-host');
        previous.root.classList.remove('generic-panel-fullscreen-content');
        previous.hiddenSiblings.forEach(function (item) { item.element.inert = item.inert; item.element.classList.remove('generic-panel-fullscreen-hidden'); });
        closeMenus(previous.root);
        updateLabels();
        if (ownsNativeFullscreen) {
            ownsNativeFullscreen = false;
            if (document.fullscreenElement === document.documentElement && document.exitFullscreen) {
                Promise.resolve(document.exitFullscreen()).catch(function () {});
            }
        }
        resize();
        if (previous.trigger && previous.trigger.isConnected) previous.trigger.focus({preventScroll: true});
        previous.scrollPositions.forEach(function (item) { item.element.scrollTop = item.top; item.element.scrollLeft = item.left; });
    }

    function checkActiveView() {
        if (!active) return;
        if (!active.host.isConnected || (active.host.matches('.drawer') && !active.host.classList.contains('open'))) {
            exit();
            return;
        }
        if (!active.root.isConnected) {
            if (active.root.hasAttribute('data-common-panel-fullscreen-isolate')) { exit(); return; }
            // Filter reloads replace the root inside a stable panel host.
            var replacement = active.root.id ? document.getElementById(active.root.id) : null;
            if (!replacement || !active.host.contains(replacement)) { exit(); return; }
            active.root = replacement;
            mount(replacement);
        }
    }

    function toggle(root, trigger) {
        if (active && active.root === root) { exit(); return; }
        exit();
        var hostSelector = root.getAttribute('data-common-panel-fullscreen-isolate');
        var host = hostSelector ? root.closest(hostSelector) : (typeof options.getHost === 'function' ? options.getHost(root) : root);
        if (!host || !host.isConnected) return;
        var triggerMenu = trigger && trigger.closest('.generic-menu');
        var focusTrigger = triggerMenu ? triggerMenu.querySelector('.generic-menu-toggle') : trigger;
        active = {root: root, host: host, trigger: focusTrigger, hiddenSiblings: [], scrollPositions: []};
        if (hostSelector) {
            for (var ancestor = root.parentElement; ancestor; ancestor = ancestor.parentElement) {
                active.scrollPositions.push({element: ancestor, top: ancestor.scrollTop, left: ancestor.scrollLeft});
                if (ancestor === host) break;
            }
            root.classList.add('generic-panel-fullscreen-content');
            for (var branch = root; branch && branch !== host; branch = branch.parentElement) {
                Array.from(branch.parentElement.children).forEach(function (sibling) {
                    if (sibling === branch) return;
                    active.hiddenSiblings.push({element: sibling, inert: sibling.inert});
                    sibling.inert = true;
                    sibling.classList.add('generic-panel-fullscreen-hidden');
                });
            }
        }
        closeMenus(root);
        host.classList.add('generic-panel-fullscreen-host');
        updateLabels();
        observer = new MutationObserver(checkActiveView);
        observer.observe(document.body, {childList: true, subtree: true});
        observer.observe(host, {attributes: true, attributeFilter: ['class']});
        resize();
        var closeButton = Array.from(root.querySelectorAll('[data-common-panel-fullscreen-close]')).find(function (button) {
            return button.closest('[data-common-panel-view]') === root;
        });
        if (closeButton) closeButton.focus({preventScroll: true});
        // Keep shared modals, notifications and subdrawers in the fullscreen document.
        // If native fullscreen is unavailable, the panel still fills the viewport.
        if (!document.fullscreenElement && !nativeRequestPending && document.documentElement.requestFullscreen) {
            nativeRequestPending = true;
            try {
                Promise.resolve(document.documentElement.requestFullscreen()).then(function () {
                    if (document.fullscreenElement !== document.documentElement) return;
                    ownsNativeFullscreen = true;
                    if (!active) {
                        ownsNativeFullscreen = false;
                        return document.exitFullscreen();
                    }
                }).catch(function () {}).finally(function () { nativeRequestPending = false; });
            } catch (error) {
                nativeRequestPending = false;
            }
        }
    }

    function addFullscreenButton(panel) {
        var button = panel.querySelector('[data-common-panel-fullscreen]');
        if (!button) {
            button = document.createElement('button');
            button.type = 'button';
            button.className = 'generic-menu-item';
            button.setAttribute('data-common-panel-fullscreen', '');
            button.setAttribute('role', 'menuitem');
            panel.insertBefore(button, panel.firstChild);
        }
    }

    function mountRoot(root) {
        root.setAttribute('data-common-panel-view', '');
        if (root.hasAttribute('data-common-panel-fullscreen-isolate')) return;
        var panel = root.querySelector('[data-common-panel-actions-panel]');
        if (!panel) {
            var header = root.querySelector(options.headerSelector || '[data-common-panel-header]');
            if (!header) return;
            var actions = header.querySelector(options.actionsSelector || '[data-common-panel-actions]');
            if (!actions) {
                actions = document.createElement('div');
                actions.setAttribute('data-common-panel-actions', '');
                if (options.actionsAttribute) actions.setAttribute(options.actionsAttribute, '');
                (header.querySelector(options.headerMainSelector || '[data-common-panel-header-main]') || header).appendChild(actions);
            }
            actions.classList.add('generic-panel-actions-host');
            // Reuse the application's existing menu and leave its own actions intact.
            panel = actions.querySelector('.generic-menu > .generic-menu-panel');
            if (!panel) {
                var menu = document.createElement('div');
                menu.className = 'generic-menu generic-menu--expanded-mobile generic-panel-actions';
                var toggleButton = document.createElement('button');
                toggleButton.type = 'button';
                toggleButton.className = 'generic-menu-toggle';
                toggleButton.textContent = '\u22ee';
                toggleButton.setAttribute('data-common-panel-menu-toggle', '');
                toggleButton.setAttribute('aria-haspopup', 'menu');
                toggleButton.setAttribute('aria-expanded', 'false');
                panel = document.createElement('div');
                panel.className = 'generic-menu-panel generic-menu-panel--wide generic-menu-panel--anchored';
                panel.setAttribute('role', 'menu');
                panel.id = 'common-panel-actions-' + String(++sequence);
                panel.hidden = true;
                toggleButton.setAttribute('aria-controls', panel.id);
                menu.appendChild(toggleButton);
                menu.appendChild(panel);
                actions.appendChild(menu);
                if (typeof window.resetGenericExpandedMenu === 'function') window.resetGenericExpandedMenu(menu);
            }
            var actionGroup = actions.querySelector(options.actionsGroupSelector || '[data-common-panel-actions-group]') || actions;
            var actionMenu = panel.parentElement;
            if (actionGroup.lastElementChild !== actionMenu) actionGroup.appendChild(actionMenu);
        }
        addFullscreenButton(panel);
        if (panel.parentElement.classList.contains('generic-menu--expanded-mobile') && typeof window.resetGenericExpandedMenu === 'function') {
            window.resetGenericExpandedMenu(panel.parentElement);
        }
    }

    function mount(container, configuration) {
        if (!container || !container.querySelectorAll) return;
        if (configuration) options = Object.assign(options, configuration);
        var selector = options.rootSelector || '[data-common-panel-view]';
        if (container.matches && container.matches(selector)) mountRoot(container);
        container.querySelectorAll(selector).forEach(mountRoot);
        container.querySelectorAll('.generic-menu-toggle').forEach(function (button) {
            if (button.textContent.trim() === '...') button.textContent = '\u22ee';
        });
        checkActiveView();
        updateLabels();
        loadTranslations();
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-common-panel-fullscreen]');
        if (button) {
            var root = button.closest('[data-common-panel-view]');
            if (!root) return;
            event.preventDefault();
            event.stopPropagation();
            toggle(root, button);
            return;
        }
        var menuToggle = event.target.closest('[data-common-panel-menu-toggle]');
        if (menuToggle) {
            event.preventDefault();
            event.stopPropagation();
            var menu = menuToggle.closest('.generic-menu');
            var panel = menu.querySelector('.generic-menu-panel');
            var open = panel.hidden;
            closeMenus(menuToggle.closest('[data-common-panel-view]'), true);
            panel.hidden = !open;
            menu.classList.toggle('is-open', open);
            menuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
    }, true);

    document.addEventListener('click', function (event) {
        document.querySelectorAll('.generic-menu.is-open > [data-common-panel-menu-toggle]').forEach(function (toggleButton) {
            var menu = toggleButton.closest('.generic-menu');
            if (!menu.contains(event.target)) closeMenus(toggleButton.closest('[data-common-panel-view]'), true);
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        if (active) {
            var root = active.root;
            event.preventDefault();
            event.stopPropagation();
            exit();
            var toggleButton = root.hasAttribute('data-common-panel-fullscreen-isolate') ? null : root.querySelector(options.focusSelector || '[data-common-panel-actions-toggle], .generic-menu-toggle');
            if (toggleButton) toggleButton.focus();
        } else {
            document.querySelectorAll('.generic-menu.is-open > [data-common-panel-menu-toggle]').forEach(function (button) {
                closeMenus(button.closest('[data-common-panel-view]'));
            });
        }
    }, true);

    document.addEventListener('fullscreenchange', function () {
        if (!document.fullscreenElement && ownsNativeFullscreen) { ownsNativeFullscreen = false; exit(); }
    });

    window.commonPanelViewActions = {mount: mount, exit: exit};
})(window, document);
