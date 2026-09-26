window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/omo/assets/js/organization-directory.js"] = function (pageConfig, pageScript) {
        (function () {
            var createButton = document.getElementById('omoCreateOrganizationCard');
            var importButton = document.getElementById('omoImportOrganizationCard');
            var modelButton = document.getElementById('omoCreateFromModelCard');
            var patreonConnectButton = document.getElementById('omoPatreonConnectCard');
            var organizationActionUrl = '/omo/api/organizations/card_action.php';
            var organizationCreateUrl = pageConfig.organizationCreateUrl;
            var organizationCreateTopbarRoute = pageConfig.organizationCreateTopbarRoute;
            var organizationCreateModalTitle = pageConfig.organizationCreateModalTitle;
            var organizationImportUrl = '/omo/api/organizations/create_import_popup.php';
            var organizationImportModalTitle = pageConfig.organizationImportModalTitle;
            var organizationModelUrl = '/omo/api/organizations/model_popup.php';
            var organizationModelModalTitle = pageConfig.organizationModelModalTitle;
            var shouldAutoOpenOrganizationCreateModal = pageConfig.shouldAutoOpenOrganizationCreateModal;
            var patreonConnectUrl = '/common/patreon_connect.php';
            var patreonConnectOrigin = pageConfig.patreonConnectOrigin;

            function interpolateTemplate(template, variables) {
                return String(template || '').replace(/\{(\w+)\}/g, function (match, key) {
                    return Object.prototype.hasOwnProperty.call(variables, key) ? String(variables[key]) : match;
                });
            }

            function getMenuPanel(menu) {
                return menu._omoPanel || menu.querySelector('[data-omo-org-menu-panel]');
            }

            function restoreMenuPanel(menu) {
                var panel = getMenuPanel(menu);
                var placeholder = panel ? panel._omoOriginPlaceholder : null;

                if (!panel) {
                    return;
                }

                if (placeholder && placeholder.parentNode) {
                    placeholder.parentNode.insertBefore(panel, placeholder);
                    placeholder.parentNode.removeChild(placeholder);
                }

                panel._omoOriginPlaceholder = null;
                panel.classList.remove('is-portal', 'is-above');
                panel.style.left = '';
                panel.style.top = '';
                panel.style.maxHeight = '';
            }

            function closeMenu(menu) {
                var trigger = menu.querySelector('[data-omo-org-menu-trigger]');
                menu.classList.remove('is-open');
                restoreMenuPanel(menu);

                if (trigger) {
                    trigger.setAttribute('aria-expanded', 'false');
                }
            }

            function closeMenus() {
                document.querySelectorAll('[data-omo-org-card-menu].is-open').forEach(closeMenu);
            }

            function positionMenu(menu) {
                var trigger = menu.querySelector('[data-omo-org-menu-trigger]');
                var panel = getMenuPanel(menu);

                if (!trigger || !panel || !panel.classList.contains('is-portal')) {
                    return;
                }

                var triggerRect = trigger.getBoundingClientRect();
                var viewportWidth = document.documentElement.clientWidth || window.innerWidth;
                var viewportHeight = document.documentElement.clientHeight || window.innerHeight;
                var viewportPadding = 10;
                var gap = 8;
                var spaceAbove = Math.max(0, triggerRect.top - viewportPadding - gap);
                var spaceBelow = Math.max(0, viewportHeight - triggerRect.bottom - viewportPadding - gap);
                var openAbove = spaceAbove >= spaceBelow;

                panel.style.maxHeight = Math.min(360, Math.max(80, openAbove ? spaceAbove : spaceBelow)) + 'px';
                var panelRect = panel.getBoundingClientRect();
                var left = Math.min(
                    Math.max(viewportPadding, triggerRect.right - panelRect.width),
                    Math.max(viewportPadding, viewportWidth - panelRect.width - viewportPadding)
                );
                var top = openAbove
                    ? triggerRect.top - panelRect.height - gap
                    : triggerRect.bottom + gap;

                panel.classList.toggle('is-above', openAbove);
                panel.style.left = Math.round(left) + 'px';
                panel.style.top = Math.round(Math.max(viewportPadding, top)) + 'px';
            }

            function openMenu(menu) {
                var panel = getMenuPanel(menu);

                if (!panel) {
                    return;
                }

                menu._omoPanel = panel;
                var placeholder = document.createElement('span');
                placeholder.hidden = true;
                placeholder.setAttribute('aria-hidden', 'true');
                panel.parentNode.insertBefore(placeholder, panel);
                panel._omoOriginPlaceholder = placeholder;
                document.body.appendChild(panel);
                panel.classList.add('is-portal');
                menu.classList.add('is-open');
                positionMenu(menu);
            }

            function openCreateModal() {
                if (typeof window.commonTopbarOpenModal === 'function') {
                    window.commonTopbarOpenModal(organizationCreateModalTitle, organizationCreateUrl, 'fetch');
                    return;
                }

                if (organizationCreateTopbarRoute && window.location.href.indexOf('modal=organization-create') === -1) {
                    window.location.href = organizationCreateTopbarRoute;
                    return;
                }

                window.location.href = organizationCreateUrl;
            }

            function openImportModal() {
                if (typeof window.commonTopbarOpenModal === 'function') {
                    window.commonTopbarOpenModal(organizationImportModalTitle, organizationImportUrl, 'fetch');
                    return;
                }

                window.location.href = organizationImportUrl;
            }

            function openModelModal() {
                if (typeof window.commonTopbarOpenModal === 'function') {
                    window.commonTopbarOpenModal(organizationModelModalTitle, organizationModelUrl, 'fetch');
                    return;
                }
                window.location.href = organizationModelUrl;
            }

            function consumeOrganizationCreateTopbarRoute() {
                if (!shouldAutoOpenOrganizationCreateModal || typeof window.history.replaceState !== 'function') {
                    return;
                }

                try {
                    var currentUrl = new URL(window.location.href);
                    if (currentUrl.searchParams.get('modal') !== 'organization-create') {
                        return;
                    }

                    currentUrl.searchParams.delete('modal');
                    window.history.replaceState({}, document.title, currentUrl.pathname + currentUrl.search + currentUrl.hash);
                } catch (error) {
                    // Keep the page usable even if URL cleanup fails.
                }
            }

            function openPatreonConnect() {
                var width = 720;
                var height = 860;
                var left = Math.max(0, (window.screen.width - width) / 2);
                var top = Math.max(0, (window.screen.height - height) / 2);
                var popup = window.open(
                    patreonConnectUrl,
                    'patreon_connect',
                    'width=' + width + ',height=' + height + ',left=' + left + ',top=' + top + ',resizable=yes,scrollbars=yes'
                );

                if (!popup) {
                    window.location.href = patreonConnectUrl;
                }
            }

            function handlePatreonMessage(event) {
                if (patreonConnectOrigin === '' || event.origin !== patreonConnectOrigin) {
                    return;
                }

                if (!event.data || event.data.type !== 'patreon-connected') {
                    return;
                }

                window.location.reload();
            }

            if (createButton) {
                createButton.addEventListener('click', openCreateModal);
            }

            if (importButton) {
                importButton.addEventListener('click', openImportModal);
            }

            if (modelButton) {
                modelButton.addEventListener('click', openModelModal);
            }

            if (patreonConnectButton) {
                patreonConnectButton.addEventListener('click', openPatreonConnect);
            }

            if (shouldAutoOpenOrganizationCreateModal && typeof window.commonTopbarOpenModal === 'function') {
                window.setTimeout(function () {
                    openCreateModal();
                    consumeOrganizationCreateTopbarRoute();
                }, 0);
            }

            window.addEventListener('message', handlePatreonMessage);

            document.addEventListener('click', function (event) {
                var trigger = event.target.closest('[data-omo-org-menu-trigger]');
                if (trigger) {
                    event.preventDefault();
                    event.stopPropagation();

                    var menu = trigger.closest('[data-omo-org-card-menu]');
                    var shouldOpen = !menu.classList.contains('is-open');
                    closeMenus();

                    if (shouldOpen) {
                        trigger.setAttribute('aria-expanded', 'true');
                        openMenu(menu);
                    }

                    return;
                }

                var actionButton = event.target.closest('[data-omo-org-action]');
                if (actionButton) {
                    event.preventDefault();
                    event.stopPropagation();

                    var card = actionButton.closest('[data-organization-id]');
                    var menuPanel = actionButton.closest('[data-omo-org-menu-panel]');
                    if (!card && !menuPanel) {
                        return;
                    }

                    var action = actionButton.getAttribute('data-omo-org-action') || '';
                    var organizationId = (card || menuPanel).getAttribute('data-organization-id') || '';
                    var organizationName = (card || menuPanel).getAttribute('data-organization-name') || window.omoDirectoryTranslations.defaultOrganizationName;
                    var confirmMessage = '';

                    if (action === 'leave') {
                        confirmMessage = interpolateTemplate(window.omoDirectoryTranslations.leaveConfirm, {
                            organizationName: organizationName
                        });
                    } else if (action === 'delete') {
                        confirmMessage = interpolateTemplate(window.omoDirectoryTranslations.deleteConfirm, {
                            organizationName: organizationName
                        });
                    }

                    if ((confirmMessage !== '' && !window.confirm(confirmMessage)) || (confirmMessage === '' && action !== 'toggle-model')) {
                        closeMenus();
                        return;
                    }

                    actionButton.disabled = true;

                    var payload = new FormData();
                    payload.append('oid', organizationId);
                    payload.append('action', action);

                    fetch(organizationActionUrl, {
                        method: 'POST',
                        body: payload,
                        credentials: 'same-origin'
                    })
                        .then(function (response) {
                            return response.text().then(function (text) {
                                var data = null;

                                try {
                                    data = JSON.parse(text);
                                } catch (error) {
                                    data = null;
                                }

                                return {
                                    ok: response.ok,
                                    data: data
                                };
                            });
                        })
                        .then(function (result) {
                            if (!result.ok || !result.data || result.data.status !== true) {
                                throw new Error(result.data && result.data.message ? result.data.message : window.omoDirectoryTranslations.actionError);
                            }

                            closeMenus();

                            if (result.data.redirect) {
                                window.location.href = result.data.redirect;
                                return;
                            }

                            window.location.reload();
                        })
                        .catch(function (error) {
                            actionButton.disabled = false;
                            closeMenus();
                            window.alert(error && error.message ? error.message : window.omoDirectoryTranslations.actionError);
                        });

                    return;
                }

                if (!event.target.closest('[data-omo-org-card-menu]')) {
                    closeMenus();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeMenus();
                }
            });

            window.addEventListener('resize', function () {
                document.querySelectorAll('[data-omo-org-card-menu].is-open').forEach(positionMenu);
            });

            window.addEventListener('scroll', function () {
                document.querySelectorAll('[data-omo-org-card-menu].is-open').forEach(positionMenu);
            }, true);
        })();
};
