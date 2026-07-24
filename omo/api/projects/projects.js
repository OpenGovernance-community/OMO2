(function (window, document) {
    'use strict';

    var root = document.getElementById('omo-projects-root');
    if (!root || root.dataset.omoProjectsReady === '1') {
        return;
    }
    root.dataset.omoProjectsReady = '1';

    var drawer = root.querySelector('[data-omo-projects-drawer]');
    var drawerBody = root.querySelector('[data-omo-projects-drawer-body]');
    var drawerController = drawer && typeof window.omoCreateSubdrawerController === 'function'
        ? window.omoCreateSubdrawerController({ drawer: drawer })
        : null;
    var currentUrl = root.getAttribute('data-omo-projects-current-url') || '';
    var createUrl = root.getAttribute('data-omo-projects-create-url') || '';
    var detailUrl = root.getAttribute('data-omo-projects-detail-url') || '';
    var actionUrl = root.getAttribute('data-omo-projects-action-url') || '';
    var routeCid = Number(root.getAttribute('data-omo-projects-cid') || 0);
    var currentView = root.getAttribute('data-omo-projects-view') === 'list' ? 'list' : 'kanban';
    var currentListSort = ['priority', 'holon'].indexOf(root.getAttribute('data-omo-projects-list-sort')) !== -1
        ? root.getAttribute('data-omo-projects-list-sort')
        : 'planned';
    var displayPreferencesStorageKey = 'omo.projects.display-preferences.v1';
    var columns = [];
    var texts = {
        loading: 'Chargement du projet...',
        loadingError: 'Impossible de charger ce projet.',
        emptyColumn: 'Aucun projet dans cette colonne.',
        statusUpdateError: 'Impossible de changer le statut.',
        actionError: 'Impossible de mettre a jour le projet.',
        deleteConfirm: 'Supprimer definitivement ce projet et ses {count} sous-projets ? Cette action est irreversible.',
        archiveConfirm: 'Ce projet n est pas termine. L archiver quand meme ?',
        moveTitle: 'Deplacer le projet',
        moveHint: 'Choisissez le holon de destination dans la structure.',
        moveSubmit: 'Deplacer ici',
        moveSelectRequired: 'Choisissez un holon de destination.',
        attachTitle: 'Attacher un projet',
        attachHint: 'Choisissez un projet sans parent dans la structure.',
        attachSearch: 'Rechercher un projet',
        attachEmpty: 'Aucun projet sans parent ne correspond a la recherche.',
        attachSubmit: 'Attacher',
        attachSelectRequired: 'Choisissez un projet a attacher.',
        cancel: 'Annuler'
    };
    var mobileColumnIndex = 0;
    var requestToken = 0;
    var currentDrawerUrl = '';
    var rootNeedsRefresh = false;
    var initialOpenProjectId = Number(root.getAttribute('data-omo-projects-open-project-id') || 0);
    var initialOpenProjectMode = root.getAttribute('data-omo-projects-open-project-mode') || '';
    var pendingCreateStatus = '';
    var pendingCreateUrl = '';

    try {
        columns = JSON.parse(root.getAttribute('data-omo-projects-columns') || '[]');
    } catch (error) {
        columns = [];
    }
    try {
        texts = Object.assign(texts, JSON.parse(root.getAttribute('data-omo-projects-texts') || '{}'));
    } catch (error) {
        // Keep the local fallback strings when the server payload is unavailable.
    }

    function resolveUrl(url) {
        return typeof window.omoResolveAppUrl === 'function' ? window.omoResolveAppUrl(url) : url;
    }

    function setLoading(isLoading) {
        root.classList.toggle('is-loading', Boolean(isLoading));
        root.querySelectorAll('button, select').forEach(function (element) {
            if (element.hasAttribute('data-omo-projects-drawer-close')) {
                return;
            }
            element.disabled = Boolean(isLoading);
        });
    }

    function revealRoot() {
        root.removeAttribute('data-omo-projects-preferences-pending');
        root.removeAttribute('aria-busy');
    }

    function captureContentScrollPosition() {
        if (currentView === 'list') {
            var list = root.querySelector('[data-omo-projects-list]');
            return list ? {view: 'list', top: list.scrollTop} : null;
        }

        var board = root.querySelector('[data-omo-projects-board]');
        return board ? {view: 'kanban', left: board.scrollLeft} : null;
    }

    function restoreContentScrollPosition(nextRoot, position) {
        if (!nextRoot || !position) {
            return;
        }

        window.requestAnimationFrame(function () {
            if (position.view === 'list') {
                var list = nextRoot.querySelector('[data-omo-projects-list]');
                if (list) {
                    list.scrollTop = position.top;
                }
                return;
            }

            var board = nextRoot.querySelector('[data-omo-projects-board]');
            if (board) {
                board.scrollLeft = position.left;
            }
        });
    }

    function waitForRootInitialization(nextRoot) {
        if (!nextRoot || nextRoot.dataset.omoProjectsReady === '1') {
            return Promise.resolve();
        }

        return new Promise(function (resolve) {
            var deadline = Date.now() + 1000;
            function checkReady() {
                if (nextRoot.dataset.omoProjectsReady === '1' || Date.now() >= deadline) {
                    resolve();
                    return;
                }
                window.requestAnimationFrame(checkReady);
            }
            checkReady();
        });
    }

    function refreshRoot(url, options) {
        var targetUrl = url || currentUrl;
        var preserveScroll = Boolean(options && options.preserveScroll);
        var scrollPosition = preserveScroll ? captureContentScrollPosition() : null;
        if (!targetUrl) {
            return Promise.resolve(null);
        }
        if (typeof window.omoReplaceFetchedPanelRoot !== 'function') {
            window.location.href = resolveUrl(targetUrl);
            return Promise.resolve(null);
        }
        return window.omoReplaceFetchedPanelRoot({
            rootSelector: '#omo-projects-root',
            currentRoot: root,
            url: resolveUrl(targetUrl),
            setLoadingState: setLoading,
            beforeReplace: function () {
                window.removeEventListener('omo-projects-route-change', handleProjectRouteChange);
                window.removeEventListener('omo-runtime-maintenance', handleRuntimeMaintenance);
            }
        }).then(function (nextRoot) {
            restoreContentScrollPosition(nextRoot, scrollPosition);
            return nextRoot;
        });
    }

    function setDrawerMessage(message, isError) {
        if (!drawerBody) {
            return;
        }
        if (drawerController) {
            drawerController.resetHeader();
        }
        drawerBody.innerHTML = '<div class="generic-section omo-projects-feedback' + (isError ? ' is-error' : '') + '"></div>';
        drawerBody.firstElementChild.textContent = String(message || '');
    }

    function handleRuntimeMaintenance(event) {
        var result = event && event.detail ? event.detail : {};
        var createdCount = Number(result.checklistProjectsCreated || 0);
        if (!Number.isInteger(createdCount) || createdCount <= 0) {
            return;
        }
        if (drawer && drawer.classList.contains('is-open')) {
            rootNeedsRefresh = true;
            return;
        }
        refreshRoot(currentUrl, {preserveScroll: true});
    }

    function executeFetchedScripts(container) {
        var scripts = Array.prototype.slice.call(container.querySelectorAll('script'));
        return scripts.reduce(function (promise, script) {
            return promise.then(function () {
                if (script.src) {
                    return new Promise(function (resolve, reject) {
                        var loadedScript = document.createElement('script');
                        loadedScript.src = script.src;
                        loadedScript.onload = resolve;
                        loadedScript.onerror = reject;
                        document.head.appendChild(loadedScript);
                    });
                }

                try {
                    window.eval(script.textContent || '');
                    return Promise.resolve();
                } catch (error) {
                    return Promise.reject(error);
                }
            });
        }, Promise.resolve());
    }

    function openDrawerWithUrl(url) {
        if (!drawer || !drawerBody || !url) {
            return Promise.resolve(false);
        }

        currentDrawerUrl = url;
        setDrawerMessage(texts.loading, false);
        drawer.hidden = false;
        window.requestAnimationFrame(function () {
            drawer.classList.add('is-open');
        });

        var localToken = ++requestToken;
        return fetch(resolveUrl(url), {
            credentials: 'same-origin',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            cache: 'no-store'
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('load_failed');
            }
            return response.text();
        }).then(function (html) {
            if (localToken !== requestToken) {
                return false;
            }
            drawerBody.innerHTML = html;
            if (drawerController) {
                drawerController.applyContentHeader(drawerBody);
            }
            if (typeof window.initGenericComponents === 'function') {
                window.initGenericComponents(drawerBody);
            }
            return executeFetchedScripts(drawerBody).then(function () {
                if (typeof window.initGenericComponents === 'function') {
                    window.initGenericComponents(drawerBody);
                }
                return true;
            });
        }).catch(function () {
            if (localToken === requestToken) {
                setDrawerMessage(texts.loadingError, true);
            }
            return false;
        });
    }

    function closeDrawer() {
        if (!drawer) {
            return;
        }
        drawer.classList.remove('is-open');
        window.setTimeout(function () {
            if (!drawer.classList.contains('is-open')) {
                drawer.hidden = true;
                drawerBody.innerHTML = '';
                if (rootNeedsRefresh) {
                    rootNeedsRefresh = false;
                    refreshRoot(currentUrl, {preserveScroll: true});
                }
                if (drawerController) {
                    drawerController.resetHeader();
                }
            }
        }, 180);
    }

    function buildProjectsUrl(scope, view, listSort) {
        var organizationId = Number(root.getAttribute('data-omo-projects-oid') || 0);
        var query = ['oid=' + encodeURIComponent(String(organizationId))];
        var nextScope = scope === 'global' ? 'descendants' : (scope === 'descendants' || scope === 'children' ? scope : 'contextual');
        var nextView = view === 'list' ? 'list' : 'kanban';
        var nextListSort = listSort === 'priority' || listSort === 'holon' ? listSort : 'planned';
        if (routeCid > 0) {
            query.push('cid=' + encodeURIComponent(String(routeCid)));
        }
        if (nextScope !== 'contextual') {
            query.push('project_scope=' + encodeURIComponent(nextScope));
        }
        if (nextView === 'list') {
            query.push('project_view=list');
        }
        if (nextListSort !== 'planned') {
            query.push('project_sort=' + encodeURIComponent(nextListSort));
        }
        return '/omo/api/projects/index.php?' + query.join('&');
    }

    function buildScopeUrl(scope) {
        return buildProjectsUrl(scope, currentView, currentListSort);
    }

    function getStoredDisplayPreferences() {
        try {
            var storedValue = window.localStorage.getItem(displayPreferencesStorageKey);
            var preferences = storedValue ? JSON.parse(storedValue) : null;
            if (!preferences || typeof preferences !== 'object') {
                return null;
            }
            return preferences;
        } catch (error) {
            return null;
        }
    }

    function storeDisplayPreferences(scope, view, listSort) {
        try {
            window.localStorage.setItem(displayPreferencesStorageKey, JSON.stringify({
                scope: scope === 'global' ? 'descendants' : (scope === 'descendants' || scope === 'children' ? scope : 'contextual'),
                view: view === 'list' ? 'list' : 'kanban',
                sort: listSort === 'priority' || listSort === 'holon' ? listSort : 'planned'
            }));
        } catch (error) {
            // Local storage can be unavailable in private or restricted browsing contexts.
        }
    }

    function applyStoredDisplayPreferences() {
        var preferences = getStoredDisplayPreferences();
        if (!preferences) {
            storeDisplayPreferences(root.getAttribute('data-omo-projects-scope') || 'contextual', currentView, currentListSort);
            return false;
        }

        var currentScope = root.getAttribute('data-omo-projects-scope') || 'contextual';
        var rawPreferredScope = String(preferences.scope || '');
        var preferredScope = ['contextual', 'children', 'descendants', 'global'].indexOf(rawPreferredScope) !== -1
            ? (rawPreferredScope === 'global' ? 'descendants' : rawPreferredScope)
            : '';
        var scopeButton = preferredScope
            ? root.querySelector('[data-omo-projects-scope="' + preferredScope + '"]')
            : null;
        var nextScope = scopeButton ? preferredScope : currentScope;
        if (!scopeButton && (preferredScope === 'children' || preferredScope === 'descendants')) {
            nextScope = 'contextual';
        }
        var nextView = preferences.view === 'list' ? 'list' : currentView;
        var preferredSort = String(preferences.sort || '');
        var canUseHolonSort = nextScope === 'descendants' || nextScope === 'children';
        var nextSort = preferredSort === 'priority' || preferredSort === 'planned'
            ? preferredSort
            : (preferredSort === 'holon' && canUseHolonSort ? 'holon' : currentListSort);

        if (nextScope === currentScope && nextView === currentView && nextSort === currentListSort) {
            return false;
        }

        var refreshUrl = buildProjectsUrl(nextScope, nextView, nextSort);
        if (initialOpenProjectMode !== '') {
            refreshUrl += '&open_project_mode=' + encodeURIComponent(initialOpenProjectMode);
            if (initialOpenProjectId > 0) {
                refreshUrl += '&open_project_id=' + encodeURIComponent(String(initialOpenProjectId));
            }
        }

        refreshRoot(refreshUrl).catch(function () {
            revealRoot();
        });
        return true;
    }

    function buildProjectRouteToken(projectId, mode) {
        if (typeof window.omoBuildProjectRouteToken === 'function') {
            return window.omoBuildProjectRouteToken(projectId, mode);
        }

        var resolvedProjectId = Number(projectId || 0);
        if (mode === 'create') {
            return 'projects-new';
        }
        if (!Number.isInteger(resolvedProjectId) || resolvedProjectId <= 0) {
            return '';
        }
        return mode === 'edit' ? 'projects-e' + String(resolvedProjectId) : 'projects-d' + String(resolvedProjectId);
    }

    function getCurrentRouteToken() {
        if (typeof window.omoParsePopupHashState !== 'function') {
            return '';
        }
        var state = window.omoParsePopupHashState();
        return state && state.routeToken ? String(state.routeToken) : '';
    }

    function isProjectDrawerRoute(routeToken) {
        return /^projects-(?:new|d\d+|e\d+)$/i.test(String(routeToken || ''));
    }

    function buildDetailUrl(projectId) {
        return detailUrl + '&id=' + encodeURIComponent(String(projectId)) + '&cid=' + encodeURIComponent(String(routeCid));
    }

    function buildEditUrl(projectId) {
        return createUrl + '&id=' + encodeURIComponent(String(projectId));
    }

    function buildCreateUrl(status) {
        if (pendingCreateUrl !== '') {
            return pendingCreateUrl;
        }
        var normalizedStatus = String(status || '').trim().toLowerCase();
        if (columns.indexOf(normalizedStatus) === -1) {
            normalizedStatus = '';
        }
        return createUrl + (normalizedStatus !== '' ? '&status=' + encodeURIComponent(normalizedStatus) : '');
    }

    function navigateProject(projectId, mode, fallbackUrl) {
        var routeToken = buildProjectRouteToken(projectId, mode);
        if (routeToken && typeof window.omoOpenDrawerHashState === 'function' && routeToken !== getCurrentRouteToken()) {
            window.omoOpenDrawerHashState(routeToken);
            return;
        }

        openDrawerWithUrl(fallbackUrl || (mode === 'create'
            ? buildCreateUrl(pendingCreateStatus)
            : (mode === 'edit' ? buildEditUrl(projectId) : buildDetailUrl(projectId))));
    }

    function updateColumnCounts() {
        root.querySelectorAll('[data-omo-projects-column]').forEach(function (column) {
            var cards = column.querySelector('[data-omo-projects-cards]');
            var count = cards ? cards.querySelectorAll('[data-omo-project-card]').length : 0;
            var countNode = column.querySelector('[data-omo-projects-column-count]');
            var emptyNode = column.querySelector('[data-omo-projects-column-empty]');
            if (countNode) {
                countNode.textContent = String(count);
            }
            if (count === 0 && !emptyNode && cards) {
                emptyNode = document.createElement('div');
                emptyNode.className = 'omo-projects__column-empty';
                emptyNode.setAttribute('data-omo-projects-column-empty', '');
                emptyNode.textContent = texts.emptyColumn;
                cards.appendChild(emptyNode);
            } else if (count > 0 && emptyNode) {
                emptyNode.remove();
            }
        });
    }

    function clearRelatedProjectHighlights() {
        root.querySelectorAll('.is-showing-children, .is-related-child').forEach(function (card) {
            card.classList.remove('is-showing-children', 'is-related-child');
        });
    }

    function highlightProjectChildren(projectNode) {
        var projectId = Number(projectNode && projectNode.getAttribute('data-project-id') || 0);
        clearRelatedProjectHighlights();
        if (projectId <= 0 || !projectNode) {
            return;
        }
        projectNode.classList.add('is-showing-children');
        root.querySelectorAll('[data-omo-project-card][data-project-parent-id="' + String(projectId) + '"], [data-omo-project-list-item][data-project-parent-id="' + String(projectId) + '"]').forEach(function (childNode) {
            childNode.classList.add('is-related-child');
        });
    }

    function updateMobileColumn() {
        var columnNodes = Array.prototype.slice.call(root.querySelectorAll('[data-omo-projects-column]'));
        if (!columnNodes.length) {
            return;
        }
        mobileColumnIndex = Math.max(0, Math.min(mobileColumnIndex, columnNodes.length - 1));
        columnNodes.forEach(function (column, index) {
            column.classList.toggle('is-mobile-active', index === mobileColumnIndex);
        });
        var currentColumn = columnNodes[mobileColumnIndex];
        var labelNode = root.querySelector('[data-omo-projects-column-label]');
        var previousButton = root.querySelector('[data-omo-projects-column-prev]');
        var nextButton = root.querySelector('[data-omo-projects-column-next]');
        var titleNode = currentColumn ? currentColumn.querySelector('h3') : null;
        if (labelNode) {
            labelNode.textContent = titleNode ? titleNode.textContent : '';
        }
        if (previousButton) {
            previousButton.disabled = mobileColumnIndex <= 0;
        }
        if (nextButton) {
            nextButton.disabled = mobileColumnIndex >= columnNodes.length - 1;
        }
    }

    function moveCard(card, nextStatus) {
        var target = root.querySelector('[data-omo-projects-cards="' + nextStatus + '"]');
        if (!card || !target) {
            return;
        }
        var currentStatus = card.getAttribute('data-project-status') || '';
        if (currentStatus === nextStatus) {
            updateColumnCounts();
            return;
        }
        card.classList.add('is-pending-status');
        postStatus(card.getAttribute('data-project-id'), nextStatus).then(function () {
            refreshRoot(currentUrl, {preserveScroll: true});
        }).catch(function (error) {
            card.classList.remove('is-pending-status');
            if (select) {
                select.value = currentStatus;
            }
            window.alert(error.message || texts.statusUpdateError);
        });
    }

    function postProjectAction(projectId, action, fields) {
        var payload = new FormData();
        payload.append('project_action', String(action || ''));
        payload.append('oid', root.getAttribute('data-omo-projects-oid') || '0');
        payload.append('cid', String(routeCid));
        payload.append('id', String(projectId || '0'));
        Object.keys(fields || {}).forEach(function (name) {
            payload.append(name, String(fields[name] || ''));
        });
        return fetch(resolveUrl(actionUrl), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            body: payload
        }).then(function (response) {
            return response.text().then(function (text) {
                var data;
                try {
                    data = JSON.parse(text);
                } catch (error) {
                    data = null;
                }
                if (!response.ok || !data || !data.success) {
                    throw new Error(data && data.message ? data.message : texts.actionError);
                }
                return data;
            });
        });
    }

    function postStatus(projectId, status) {
        return postProjectAction(projectId, 'update_status', {status: status});
    }

    function formatText(template, values) {
        return String(template || '').replace(/\{([a-z_]+)\}/gi, function (match, key) {
            return values && values[key] !== undefined ? String(values[key]) : match;
        });
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function closeProjectMenus(exceptMenu) {
        root.querySelectorAll('[data-omo-project-menu]').forEach(function (menu) {
            if (menu === exceptMenu) {
                return;
            }
            var panel = menu.querySelector('[data-omo-project-menu-panel]');
            var toggle = menu.querySelector('[data-omo-project-menu-toggle]');
            if (panel) {
                panel.hidden = true;
            }
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    function openMoveDialog(card) {
        if (!card || typeof window.commonTopbarOpenModal !== 'function' || typeof window.omoMountHolonScopePicker !== 'function') {
            window.alert(texts.actionError);
            return;
        }

        var projectId = Number(card.getAttribute('data-project-id') || 0);
        var currentHolonId = Number(card.getAttribute('data-project-holon-id') || 0);
        var organizationId = Number(root.getAttribute('data-omo-projects-oid') || 0);
        var html = '<div class="omo-project-move-dialog">'
            + '<p class="omo-project-move-dialog__hint">' + escapeHtml(texts.moveHint) + '</p>'
            + '<div data-omo-project-move-picker></div>'
            + '<p class="omo-project-move-dialog__error" data-omo-project-move-error hidden></p>'
            + '<div class="omo-project-move-dialog__actions">'
            + '<button type="button" class="generic-action-button generic-action-button--secondary" data-omo-project-move-cancel>' + escapeHtml(texts.cancel) + '</button>'
            + '<button type="button" class="generic-action-button generic-action-button--main" data-omo-project-move-submit disabled>' + escapeHtml(texts.moveSubmit) + '</button>'
            + '</div></div>';

        window.commonTopbarOpenModal(texts.moveTitle, html, 'html');
        window.setTimeout(function () {
            var modal = document.getElementById('commonTopbarModalBody');
            var dialog = modal ? modal.querySelector('.omo-project-move-dialog') : null;
            if (!dialog) {
                return;
            }
            var submit = dialog.querySelector('[data-omo-project-move-submit]');
            var error = dialog.querySelector('[data-omo-project-move-error]');
            var selectedHolonId = 0;
            window.omoMountHolonScopePicker({
                host: dialog.querySelector('[data-omo-project-move-picker]'),
                organizationId: organizationId,
                initialHolonId: currentHolonId,
                showModes: false,
                onChange: function (holonId) {
                    selectedHolonId = Number(holonId || 0);
                    if (submit) {
                        submit.disabled = selectedHolonId <= 0 || selectedHolonId === currentHolonId;
                    }
                }
            });
            dialog.addEventListener('click', function (event) {
                if (event.target.closest('[data-omo-project-move-cancel]')) {
                    window.commonTopbarCloseModal();
                    return;
                }
                if (!event.target.closest('[data-omo-project-move-submit]')) {
                    return;
                }
                if (selectedHolonId <= 0 || selectedHolonId === currentHolonId) {
                    if (error) {
                        error.textContent = texts.moveSelectRequired;
                        error.hidden = false;
                    }
                    return;
                }
                submit.disabled = true;
                postProjectAction(projectId, 'move_project', {IDholon: selectedHolonId}).then(function () {
                    window.commonTopbarCloseModal();
                    refreshRoot(currentUrl);
                }).catch(function (actionError) {
                    submit.disabled = false;
                    if (error) {
                        error.textContent = actionError.message || texts.actionError;
                        error.hidden = false;
                    }
                });
            });
        }, 0);
    }

    function openAttachSubprojectDialog(detail) {
        if (!detail || typeof window.commonTopbarOpenModal !== 'function' || typeof window.omoMountHolonScopePicker !== 'function') {
            window.alert(texts.actionError);
            return;
        }

        var parentId = Number(detail.getAttribute('data-omo-project-detail') || 0);
        var parentHolonId = Number(detail.getAttribute('data-omo-project-holon-id') || 0);
        var organizationId = Number(root.getAttribute('data-omo-projects-oid') || 0);
        var candidates = [];
        try {
            candidates = JSON.parse(detail.getAttribute('data-omo-project-attach-candidates') || '[]');
        } catch (error) {
            candidates = [];
        }
        if (parentId <= 0 || !Array.isArray(candidates) || candidates.length === 0) {
            return;
        }

        var html = '<div class="omo-project-attach-dialog omo-resource-picker">'
            + '<aside class="omo-resource-picker__navigation" data-omo-project-attach-scope></aside>'
            + '<div class="omo-resource-picker__content">'
            + '<p class="omo-project-move-dialog__hint">' + escapeHtml(texts.attachHint) + '</p>'
            + '<label class="omo-resource-picker__quick-search"><input class="generic-form-control" type="search" data-omo-project-attach-search aria-label="' + escapeHtml(texts.attachSearch) + '" placeholder="' + escapeHtml(texts.attachSearch) + '"></label>'
            + '<select class="generic-form-control omo-project-parent-picker__select" size="10" data-omo-project-attach-select></select>'
            + '<p class="omo-project-parent-picker__empty" data-omo-project-attach-empty hidden></p>'
            + '<p class="omo-project-move-dialog__error" data-omo-project-attach-error hidden></p>'
            + '<div class="omo-project-parent-picker__actions">'
            + '<button type="button" class="generic-action-button generic-action-button--secondary" data-omo-project-attach-cancel>' + escapeHtml(texts.cancel) + '</button>'
            + '<button type="button" class="generic-action-button generic-action-button--main" data-omo-project-attach-submit disabled>' + escapeHtml(texts.attachSubmit) + '</button>'
            + '</div></div></div>';

        window.commonTopbarOpenModal(texts.attachTitle, html, 'html');
        window.setTimeout(function () {
            var modal = document.getElementById('commonTopbarModalBody');
            var dialog = modal ? modal.querySelector('.omo-project-attach-dialog') : null;
            if (!dialog) {
                return;
            }
            var search = dialog.querySelector('[data-omo-project-attach-search]');
            var select = dialog.querySelector('[data-omo-project-attach-select]');
            var empty = dialog.querySelector('[data-omo-project-attach-empty]');
            var error = dialog.querySelector('[data-omo-project-attach-error]');
            var submit = dialog.querySelector('[data-omo-project-attach-submit]');
            var selectedId = 0;
            var scopePicker = null;

            function updateSubmit() {
                if (submit) {
                    submit.disabled = selectedId <= 0;
                }
            }

            function render() {
                if (!select || !empty) {
                    return;
                }
                var query = String(search && search.value || '').trim().toLowerCase();
                var selectedHolonId = scopePicker && typeof scopePicker.getSelectedHolonId === 'function'
                    ? Number(scopePicker.getSelectedHolonId() || 0)
                    : 0;
                var matches = candidates.filter(function (candidate) {
                    var candidateHolonId = Number(candidate && candidate.holonId || 0);
                    var matchesScope = !scopePicker
                        || candidateHolonId <= 0
                        || scopePicker.matches(candidateHolonId)
                        || (selectedHolonId > 0 && candidateHolonId === selectedHolonId);
                    var candidateText = [candidate && candidate.title, candidate && candidate.context].join(' ').toLowerCase();
                    return matchesScope && (query === '' || candidateText.indexOf(query) !== -1);
                });
                select.innerHTML = '<option value="0">' + escapeHtml(texts.attachSelectRequired) + '</option>';
                matches.forEach(function (candidate) {
                    var option = document.createElement('option');
                    option.value = String(candidate.id || 0);
                    option.textContent = String(candidate.title || '') + (candidate.context ? ' - ' + String(candidate.context) : '');
                    select.appendChild(option);
                });
                select.value = String(selectedId);
                if (select.value !== String(selectedId)) {
                    selectedId = 0;
                    select.value = '0';
                }
                empty.textContent = texts.attachEmpty;
                empty.hidden = matches.length > 0;
                updateSubmit();
            }

            scopePicker = window.omoMountHolonScopePicker({
                host: dialog.querySelector('[data-omo-project-attach-scope]'),
                organizationId: organizationId,
                initialHolonId: parentHolonId,
                onChange: render
            });
            if (search) {
                search.addEventListener('input', render);
                search.focus();
            }
            if (select) {
                select.addEventListener('change', function () {
                    selectedId = Number(select.value || 0);
                    if (error) {
                        error.hidden = true;
                    }
                    updateSubmit();
                });
            }
            dialog.addEventListener('click', function (event) {
                if (event.target.closest('[data-omo-project-attach-cancel]')) {
                    window.commonTopbarCloseModal();
                    return;
                }
                if (!event.target.closest('[data-omo-project-attach-submit]')) {
                    return;
                }
                if (selectedId <= 0) {
                    if (error) {
                        error.textContent = texts.attachSelectRequired;
                        error.hidden = false;
                    }
                    return;
                }
                submit.disabled = true;
                postProjectAction(parentId, 'attach_subproject', {child_id: selectedId}).then(function () {
                    rootNeedsRefresh = true;
                    window.commonTopbarCloseModal();
                    return currentDrawerUrl ? openDrawerWithUrl(currentDrawerUrl) : null;
                }).catch(function (actionError) {
                    submit.disabled = false;
                    if (error) {
                        error.textContent = actionError.message || texts.actionError;
                        error.hidden = false;
                    }
                });
            });
            render();
        }, 0);
    }

    function runProjectContextAction(action, card) {
        if (!card) {
            return;
        }
        var projectId = Number(card.getAttribute('data-project-id') || 0);
        var subprojectCount = Number(card.getAttribute('data-project-subproject-count') || 0);
        var status = card.getAttribute('data-project-status') || '';
        if (action === 'move') {
            openMoveDialog(card);
            return;
        }
        if (action === 'delete' && !window.confirm(formatText(texts.deleteConfirm, {count: subprojectCount}))) {
            return;
        }
        if (action === 'archive' && status !== 'done' && !window.confirm(texts.archiveConfirm)) {
            return;
        }
        var actionName = action === 'delete' ? 'delete_project' : 'archive_project';
        postProjectAction(projectId, actionName).then(function () {
            refreshRoot(currentUrl);
        }).catch(function (actionError) {
            window.alert(actionError.message || texts.actionError);
        });
    }

    root.querySelectorAll('[data-omo-projects-scope]').forEach(function (button) {
        button.addEventListener('click', function () {
            var nextScope = button.getAttribute('data-omo-projects-scope') || 'contextual';
            storeDisplayPreferences(nextScope, currentView, currentListSort);
            refreshRoot(buildScopeUrl(nextScope));
        });
    });

    root.querySelectorAll('[data-omo-projects-view]').forEach(function (button) {
        button.addEventListener('click', function () {
            var nextView = button.getAttribute('data-omo-projects-view') || 'kanban';
            if (nextView !== currentView) {
                storeDisplayPreferences(root.getAttribute('data-omo-projects-scope') || 'contextual', nextView, currentListSort);
                refreshRoot(buildProjectsUrl(root.getAttribute('data-omo-projects-scope') || 'contextual', nextView, currentListSort));
            }
        });
    });

    root.querySelectorAll('[data-omo-projects-sort]').forEach(function (button) {
        button.addEventListener('click', function () {
            var nextSort = button.getAttribute('data-omo-projects-sort') || 'planned';
            if (nextSort !== currentListSort) {
                storeDisplayPreferences(root.getAttribute('data-omo-projects-scope') || 'contextual', currentView, nextSort);
                refreshRoot(buildProjectsUrl(root.getAttribute('data-omo-projects-scope') || 'contextual', currentView, nextSort));
            }
        });
    });

    var previousColumnButton = root.querySelector('[data-omo-projects-column-prev]');
    var nextColumnButton = root.querySelector('[data-omo-projects-column-next]');
    if (previousColumnButton) {
        previousColumnButton.addEventListener('click', function () {
            mobileColumnIndex -= 1;
            updateMobileColumn();
        });
    }
    if (nextColumnButton) {
        nextColumnButton.addEventListener('click', function () {
            mobileColumnIndex += 1;
            updateMobileColumn();
        });
    }

    root.querySelectorAll('[data-omo-projects-open-create]').forEach(function (button) {
        button.addEventListener('click', function () {
            pendingCreateStatus = '';
            pendingCreateUrl = '';
            navigateProject(0, 'create', buildCreateUrl(''));
        });
    });

    root.querySelectorAll('[data-omo-projects-column]').forEach(function (column) {
        column.addEventListener('dblclick', function (event) {
            if (event.target.closest('[data-omo-project-card], button, select, input, textarea, a, [contenteditable="true"]')) {
                return;
            }

            pendingCreateStatus = column.getAttribute('data-omo-projects-column') || '';
            pendingCreateUrl = '';
            navigateProject(0, 'create', buildCreateUrl(pendingCreateStatus));
        });
    });

    root.addEventListener('click', function (event) {
        var menuToggle = event.target.closest('[data-omo-project-menu-toggle]');
        if (menuToggle) {
            event.preventDefault();
            event.stopPropagation();
            var menu = menuToggle.closest('[data-omo-project-menu]');
            var menuPanel = menu ? menu.querySelector('[data-omo-project-menu-panel]') : null;
            var isOpen = !!menuPanel && !menuPanel.hidden;
            closeProjectMenus(menu);
            if (menuPanel) {
                menuPanel.hidden = isOpen;
            }
            menuToggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
            return;
        }

        var actionButton = event.target.closest('[data-omo-project-action]');
        if (actionButton) {
            event.preventDefault();
            event.stopPropagation();
            var actionCard = actionButton.closest('[data-omo-project-card]');
            closeProjectMenus();
            runProjectContextAction(actionButton.getAttribute('data-omo-project-action') || '', actionCard);
            return;
        }

        closeProjectMenus();
        var closeButton = event.target.closest('[data-omo-projects-drawer-close], [data-omo-projects-cancel-create]');
        if (closeButton) {
            event.preventDefault();
            if (isProjectDrawerRoute(getCurrentRouteToken()) && typeof window.omoOpenDrawerHashState === 'function') {
                window.omoOpenDrawerHashState('projects');
                return;
            }
            closeDrawer();
            return;
        }

        var editButton = event.target.closest('[data-omo-projects-open-edit-url]');
        if (editButton) {
            event.preventDefault();
            event.stopPropagation();
            var editProjectId = Number(editButton.getAttribute('data-omo-projects-edit-project-id') || 0);
            navigateProject(editProjectId, 'edit', editButton.getAttribute('data-omo-projects-open-edit-url') || '');
            return;
        }

        var newSubprojectButton = event.target.closest('[data-omo-project-detail-new-subproject-url]');
        if (newSubprojectButton) {
            event.preventDefault();
            event.stopPropagation();
            pendingCreateStatus = '';
            pendingCreateUrl = newSubprojectButton.getAttribute('data-omo-project-detail-new-subproject-url') || '';
            navigateProject(0, 'create', pendingCreateUrl);
            return;
        }

        var attachSubprojectButton = event.target.closest('[data-omo-project-detail-attach-subproject]');
        if (attachSubprojectButton) {
            event.preventDefault();
            event.stopPropagation();
            openAttachSubprojectDialog(attachSubprojectButton.closest('[data-omo-project-detail]'));
            return;
        }

        var subproject = event.target.closest('[data-omo-project-subproject]');
        if (subproject && !event.target.closest('select, option, button, a')) {
            event.preventDefault();
            var subprojectId = Number(subproject.getAttribute('data-project-id') || 0);
            if (subprojectId > 0) {
                navigateProject(subprojectId, 'detail', buildDetailUrl(subprojectId));
            }
            return;
        }

        var listItem = event.target.closest('[data-omo-project-list-item]');
        if (listItem && !event.target.closest('select, option, button, a')) {
            event.preventDefault();
            var listProjectId = Number(listItem.getAttribute('data-project-id') || 0);
            if (listProjectId > 0) {
                navigateProject(listProjectId, 'detail', buildDetailUrl(listProjectId));
            }
            return;
        }

        var card = event.target.closest('[data-omo-project-card]');
        if (!card || event.target.closest('select, option, button, a')) {
            return;
        }
        var projectId = Number(card.getAttribute('data-project-id') || 0);
        if (projectId > 0) {
            navigateProject(projectId, 'detail', buildDetailUrl(projectId));
        }
    });

    root.addEventListener('pointerover', function (event) {
        var projectNode = event.target.closest('[data-omo-project-card], [data-omo-project-list-item]');
        if (projectNode && !projectNode.contains(event.relatedTarget)) {
            highlightProjectChildren(projectNode);
        }
    });

    root.addEventListener('pointerout', function (event) {
        var projectNode = event.target.closest('[data-omo-project-card], [data-omo-project-list-item]');
        if (projectNode && !projectNode.contains(event.relatedTarget)) {
            clearRelatedProjectHighlights();
        }
    });

    root.addEventListener('focusin', function (event) {
        var projectNode = event.target.closest('[data-omo-project-card], [data-omo-project-list-item]');
        if (projectNode) {
            highlightProjectChildren(projectNode);
        }
    });

    root.addEventListener('focusout', function (event) {
        if (!root.contains(event.relatedTarget)) {
            clearRelatedProjectHighlights();
        }
    });

    root.addEventListener('keydown', function (event) {
        if (event.key !== 'Enter' && event.key !== ' ') {
            return;
        }
        var card = event.target.closest('[data-omo-project-card]');
        if (card && event.target === card) {
            event.preventDefault();
            card.click();
            return;
        }

        var subproject = event.target.closest('[data-omo-project-subproject]');
        if (subproject && event.target === subproject) {
            event.preventDefault();
            subproject.click();
            return;
        }

        var listItem = event.target.closest('[data-omo-project-list-item]');
        if (listItem && event.target === listItem) {
            event.preventDefault();
            listItem.click();
        }
    });

    root.addEventListener('change', function (event) {
        var select = event.target.closest('[data-omo-project-detail-status-select]');
        if (!select) {
            return;
        }
        event.stopPropagation();
        var item = select.closest('[data-omo-project-subproject]');
        var projectId = select.getAttribute('data-project-id') || (item ? item.getAttribute('data-project-id') : '0');
        var previousStatus = select.getAttribute('data-previous-status') || select.value;
        select.disabled = true;
        postStatus(projectId, select.value).then(function () {
            if (item) {
                columns.forEach(function (status) {
                    item.classList.remove('omo-project-detail__subproject-item--' + status);
                });
                item.classList.add('omo-project-detail__subproject-item--' + select.value);
            }
            select.setAttribute('data-previous-status', select.value);
            select.disabled = false;
            rootNeedsRefresh = true;
            if (currentDrawerUrl) {
                return openDrawerWithUrl(currentDrawerUrl);
            }
        }).catch(function (error) {
            select.value = previousStatus;
            select.disabled = false;
            window.alert(error.message || texts.statusUpdateError);
        });
    });

    root.querySelectorAll('[data-omo-project-status-select]').forEach(function (select) {
        select.addEventListener('click', function (event) {
            event.stopPropagation();
        });
        select.addEventListener('change', function () {
            var card = select.closest('[data-omo-project-card]');
            moveCard(card, select.value);
        });
    });

    root.querySelectorAll('[data-omo-project-card][draggable="true"]').forEach(function (card) {
        card.addEventListener('dragstart', function (event) {
            card.classList.add('dragging');
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', card.getAttribute('data-project-id') || '');
        });
        card.addEventListener('dragend', function () {
            card.classList.remove('dragging');
            root.querySelectorAll('.omo-projects__column-cards.is-drag-over').forEach(function (container) {
                container.classList.remove('is-drag-over');
            });
        });
    });

    root.querySelectorAll('[data-omo-projects-cards]').forEach(function (container) {
        container.addEventListener('dragover', function (event) {
            event.preventDefault();
            container.classList.add('is-drag-over');
        });
        container.addEventListener('dragleave', function () {
            container.classList.remove('is-drag-over');
        });
        container.addEventListener('drop', function (event) {
            event.preventDefault();
            container.classList.remove('is-drag-over');
            var projectId = event.dataTransfer.getData('text/plain');
            var card = root.querySelector('[data-omo-project-card][data-project-id="' + projectId + '"]');
            moveCard(card, container.getAttribute('data-status') || '');
        });
    });

    window.omoProjectsAfterSave = function () {
        var hashState = typeof window.omoParsePopupHashState === 'function'
            ? window.omoParsePopupHashState()
            : null;
        var routeToken = hashState && hashState.routeToken ? String(hashState.routeToken) : '';
        var editMatch = routeToken.match(/^projects-e(\d+)$/i);
        return refreshRoot(currentUrl, {preserveScroll: true}).then(function (nextRoot) {
            return waitForRootInitialization(nextRoot);
        }).then(function () {
            if (editMatch && typeof window.omoOpenDrawerHashState === 'function') {
                window.omoOpenDrawerHashState(buildProjectRouteToken(Number(editMatch[1]), 'detail'), {replace: true});
            } else if (routeToken === 'projects-new' && typeof window.omoOpenDrawerHashState === 'function') {
                window.omoOpenDrawerHashState('projects', {replace: true});
            }
        });
    };

    function maybeOpenInitialProject() {
        var projectId = initialOpenProjectId;
        var mode = initialOpenProjectMode;
        if (typeof window.omoParsePopupHashState === 'function') {
            var hashState = window.omoParsePopupHashState();
            var routeToken = hashState && hashState.routeToken ? String(hashState.routeToken) : '';
            if (routeToken === 'projects-new') {
                projectId = 0;
                mode = 'create';
            } else {
                var editMatch = routeToken.match(/^projects-e(\d+)$/i);
                var detailMatch = routeToken.match(/^projects-d(\d+)$/i);
                if (editMatch) {
                    projectId = Number(editMatch[1]);
                    mode = 'edit';
                } else if (detailMatch) {
                    projectId = Number(detailMatch[1]);
                    mode = 'detail';
                }
            }
        }

        if (mode === 'create') {
            openDrawerWithUrl(createUrl);
            return;
        }
        if (!Number.isInteger(projectId) || projectId <= 0) {
            return;
        }
        openDrawerWithUrl(mode === 'edit'
            ? buildEditUrl(projectId)
            : buildDetailUrl(projectId));
    }

    window.omoOpenProjectRoute = function (routeToken) {
        if (!root.isConnected) {
            return false;
        }

        var normalizedRouteToken = String(routeToken || '').replace(/^#/, '').trim();
        var mode = '';
        var projectId = 0;
        if (normalizedRouteToken === 'projects-new') {
            mode = 'create';
        } else {
            var editMatch = normalizedRouteToken.match(/^projects-e(\d+)$/i);
            var detailMatch = normalizedRouteToken.match(/^projects-d(\d+)$/i);
            if (editMatch) {
                mode = 'edit';
                projectId = Number(editMatch[1]);
            } else if (detailMatch) {
                mode = 'detail';
                projectId = Number(detailMatch[1]);
            }
        }

        if (mode === '') {
            return false;
        }

        var targetUrl = mode === 'create'
            ? buildCreateUrl(pendingCreateStatus)
            : (mode === 'edit' ? buildEditUrl(projectId) : buildDetailUrl(projectId));
        if (!targetUrl) {
            return false;
        }

        if (
            currentDrawerUrl !== ''
            && resolveUrl(currentDrawerUrl) === resolveUrl(targetUrl)
            && drawer
            && !drawer.hidden
        ) {
            return true;
        }

        openDrawerWithUrl(targetUrl);
        return true;
    };

    function handleProjectRouteChange(event) {
        var route = event && event.detail ? event.detail : {};
        var projectId = Number(route.projectId || 0);
        var mode = String(route.mode || '').toLowerCase();
        var routeToken = '';
        if (mode === 'create') {
            routeToken = 'projects-new';
        } else if (projectId > 0 && mode === 'edit') {
            routeToken = 'projects-e' + String(projectId);
        } else if (projectId > 0) {
            routeToken = 'projects-d' + String(projectId);
        }

        if (routeToken !== '' && window.omoOpenProjectRoute(routeToken)) {
            return;
        }
        closeDrawer();
    }

    window.addEventListener('omo-projects-route-change', handleProjectRouteChange);
    window.addEventListener('omo-runtime-maintenance', handleRuntimeMaintenance);

    updateColumnCounts();
    updateMobileColumn();
    var isApplyingStoredDisplayPreferences = applyStoredDisplayPreferences();
    if (!isApplyingStoredDisplayPreferences) {
        revealRoot();
    }
    if (!isApplyingStoredDisplayPreferences) {
        window.setTimeout(maybeOpenInitialProject, 40);
    }
})(window, document);
