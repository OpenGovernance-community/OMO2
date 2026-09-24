window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/omo/api/stats/stats.js"] = function (pageConfig, pageScript) {
(function () {
    var root = typeof window.omoFindApplicationRoot === 'function'
        ? window.omoFindApplicationRoot('omo-stats-root')
        : document.getElementById('omo-stats-root');
    if (!root || root.dataset.omoStatsReady === '1') {
        return;
    }
    root.dataset.omoStatsReady = '1';

    var useLocalDrawerNavigation = typeof window.omoIsPvApplicationTabContext === 'function'
        && window.omoIsPvApplicationTabContext(root);

    var drawer = root.querySelector('[data-omo-stats-drawer]');
    var drawerBody = root.querySelector('[data-omo-stats-drawer-body]');
    var drawerController = drawer && typeof window.omoCreateSubdrawerController === 'function'
        ? window.omoCreateSubdrawerController({ drawer: drawer })
        : null;
    if (drawerController) {
        drawer.__omoSubdrawerController = drawerController;
        window.omoStatsDrawer = drawerController;
    }
    var currentUrl = root.getAttribute('data-omo-stats-current-url') || '';
    var createUrl = root.getAttribute('data-omo-stats-create-url') || '';
    var detailBaseUrl = root.getAttribute('data-omo-stats-detail-url') || '';
    var groupDetailBaseUrl = root.getAttribute('data-omo-stats-group-detail-url') || '';
    var currentScope = root.getAttribute('data-omo-stats-current-scope') || 'contextual';
    var currentAssignment = root.getAttribute('data-omo-stats-current-assignment') || 'all';
    var routeCid = Number(root.getAttribute('data-omo-stats-route-cid') || 0);
    var initialIndicatorId = Number(root.getAttribute('data-omo-stats-open-indicator-id') || 0);
    var initialGroupId = Number(root.getAttribute('data-omo-stats-open-group-id') || 0);
    var requestToken = 0;
    var listNeedsRefresh = false;
    var savedViewsStorageKey = 'omo.stats.saved-views.v2';
    var legacySavedViewsStorageKey = 'omo.stats.saved-views.v1';
    var sessionViewsStorageKey = 'omo.stats.session-views.v1';
    var searchStorageKey = 'omo.stats.quick-search.v1';
    var currentSort = root.getAttribute('data-omo-stats-current-sort') === 'alpha' ? 'alpha' : 'temporal';
    var currentView = 'cards';
    var currentSearch = '';
    var pendingFilters = null;
    var filterPanelOpen = false;
    var texts = pageConfig.texts;

    var statsMenuOwnerDocument = root.ownerDocument || document;
    var floatingStatsMenu = statsMenuOwnerDocument.querySelector('[data-omo-stats-floating-menu="1"]');
    if (!floatingStatsMenu) {
        floatingStatsMenu = statsMenuOwnerDocument.createElement('div');
        floatingStatsMenu.className = 'omo-stats-item-menu__panel generic-menu-panel generic-menu-panel--wide generic-menu-panel--floating omo-stats-item-menu__panel--floating';
        floatingStatsMenu.setAttribute('data-omo-stats-floating-menu', '1');
        floatingStatsMenu.setAttribute('role', 'menu');
        floatingStatsMenu.hidden = true;
        statsMenuOwnerDocument.body.appendChild(floatingStatsMenu);
    }
    var activeStatsMenuToggle = null;
    var floatingStatsMenuActions = new WeakMap();

    function positionStatsFloatingMenu(toggle) {
        if (!(toggle instanceof Element) || !toggle.isConnected) {
            closeStatsItemMenus();
            return;
        }

        floatingStatsMenu.hidden = false;
        floatingStatsMenu.style.visibility = 'hidden';
        floatingStatsMenu.style.top = '0px';
        floatingStatsMenu.style.left = '0px';

        var toggleRect = toggle.getBoundingClientRect();
        var menuRect = floatingStatsMenu.getBoundingClientRect();
        var viewportPadding = 12;
        var gap = 8;
        var top = toggleRect.bottom + gap;
        var left = toggleRect.right - menuRect.width;

        if (top + menuRect.height > window.innerHeight - viewportPadding) {
            top = Math.max(viewportPadding, toggleRect.top - menuRect.height - gap);
        }
        if (left + menuRect.width > window.innerWidth - viewportPadding) {
            left = Math.max(viewportPadding, window.innerWidth - menuRect.width - viewportPadding);
        }
        if (left < viewportPadding) {
            left = viewportPadding;
        }

        floatingStatsMenu.style.top = String(Math.round(top)) + 'px';
        floatingStatsMenu.style.left = String(Math.round(left)) + 'px';
        floatingStatsMenu.style.visibility = '';
    }

    function closeStatsItemMenus() {
        root.querySelectorAll('[data-omo-stats-item-menu]').forEach(function (menu) {
            menu.classList.remove('is-open');
        });
        root.querySelectorAll('[data-omo-stats-item-menu-panel]').forEach(function (panel) {
            panel.hidden = true;
        });
        root.querySelectorAll('[data-omo-stats-item-menu-toggle]').forEach(function (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
        });
        activeStatsMenuToggle = null;
        floatingStatsMenu.hidden = true;
        floatingStatsMenu.style.visibility = '';
        floatingStatsMenu.replaceChildren();
    }

    function openStatsItemMenu(toggle) {
        var menu = toggle ? toggle.closest('[data-omo-stats-item-menu]') : null;
        var panel = menu ? menu.querySelector('[data-omo-stats-item-menu-panel]') : null;
        var shouldOpen = !!toggle && !!panel && (activeStatsMenuToggle !== toggle || floatingStatsMenu.hidden);
        closeStatsItemMenus();

        if (!shouldOpen) {
            return;
        }

        var fragment = statsMenuOwnerDocument.createDocumentFragment();
        Array.prototype.forEach.call(panel.children, function (originalAction) {
            if (!(originalAction instanceof Element)) {
                return;
            }
            var floatingAction = originalAction.cloneNode(true);
            floatingAction.setAttribute('data-omo-stats-floating-menu-action', '1');
            floatingAction.setAttribute('role', 'menuitem');
            floatingStatsMenuActions.set(floatingAction, originalAction);
            fragment.appendChild(floatingAction);
        });
        floatingStatsMenu.replaceChildren(fragment);
        if (!floatingStatsMenu.childElementCount) {
            return;
        }

        activeStatsMenuToggle = toggle;
        menu.classList.add('is-open');
        toggle.setAttribute('aria-expanded', 'true');
        positionStatsFloatingMenu(toggle);
    }

    window.addEventListener('resize', function () {
        if (activeStatsMenuToggle) {
            positionStatsFloatingMenu(activeStatsMenuToggle);
        }
    });
    statsMenuOwnerDocument.addEventListener('scroll', function () {
        if (activeStatsMenuToggle) {
            positionStatsFloatingMenu(activeStatsMenuToggle);
        }
    }, true);

    function resolveUrl(url) {
        return typeof window.omoResolveAppUrl === 'function' ? window.omoResolveAppUrl(url) : url;
    }

    function normalizeScope(scope) {
        if (scope === 'global') {
            return 'descendants';
        }
        return scope === 'children' || scope === 'descendants' ? scope : 'contextual';
    }

    function normalizeSort(sortName) {
        return sortName === 'alpha' ? 'alpha' : 'temporal';
    }

    function normalizeAssignment(assignment) {
        return assignment === 'mine' || assignment === 'roles' ? assignment : 'all';
    }

    function normalizeView(viewName) {
        return viewName === 'compact' ? 'compact' : 'cards';
    }

    function getPreferencesContextKey() {
        var regularKey = String(root.getAttribute('data-omo-stats-oid') || '0')
            + ':' + String(root.getAttribute('data-omo-stats-cid') || '0');
        return typeof window.omoApplicationViewPreferencesGetStorageContextKey === 'function'
            ? window.omoApplicationViewPreferencesGetStorageContextKey(root, regularKey)
            : regularKey;
    }

    function createStoredFilters(filters) {
        return {
            scope: normalizeScope(filters && filters.scope),
            assignment: normalizeAssignment(filters && filters.assignment),
            sort: normalizeSort(filters && filters.sort),
            view: normalizeView(filters && filters.view)
        };
    }

    function getStoredFiltersStore() {
        try {
            var storedValue = window.localStorage.getItem(savedViewsStorageKey);
            var savedViews = storedValue ? JSON.parse(storedValue) : null;
            if (savedViews && typeof savedViews === 'object' && savedViews.contexts && typeof savedViews.contexts === 'object') {
                return {
                    defaultView: savedViews.defaultView && typeof savedViews.defaultView === 'object' ? savedViews.defaultView : null,
                    contexts: savedViews.contexts
                };
            }

            var legacyValue = window.localStorage.getItem(legacySavedViewsStorageKey);
            var legacyViews = legacyValue ? JSON.parse(legacyValue) : null;
            return {
                defaultView: null,
                contexts: legacyViews && typeof legacyViews === 'object' ? legacyViews : {}
            };
        } catch (error) {
            return {defaultView: null, contexts: {}};
        }
    }

    function saveStoredFiltersStore(store) {
        try {
            window.localStorage.setItem(savedViewsStorageKey, JSON.stringify({
                defaultView: store.defaultView && typeof store.defaultView === 'object' ? store.defaultView : null,
                contexts: store.contexts && typeof store.contexts === 'object' ? store.contexts : {}
            }));
        } catch (error) {
        }
    }

    function getStoredFilters() {
        var filters = getStoredFiltersStore().contexts[getPreferencesContextKey()];
        return filters && typeof filters === 'object' ? filters : null;
    }

    function getDefaultStoredFilters() {
        return getStoredFiltersStore().defaultView;
    }

    function storeFilters(filters) {
        var store = getStoredFiltersStore();
        store.contexts[getPreferencesContextKey()] = createStoredFilters(filters);
        saveStoredFiltersStore(store);
    }

    function storeDefaultFilters(filters) {
        var store = getStoredFiltersStore();
        store.defaultView = createStoredFilters(filters);
        saveStoredFiltersStore(store);
    }

    function clearStoredFilters() {
        var store = getStoredFiltersStore();
        delete store.contexts[getPreferencesContextKey()];
        saveStoredFiltersStore(store);
    }

    function readStoredValue(storage, storageKey) {
        try {
            var rawValue = storage.getItem(storageKey);
            var values = rawValue ? JSON.parse(rawValue) : null;
            return values && typeof values === 'object'
                ? values[getPreferencesContextKey()] || null
                : null;
        } catch (error) {
            return null;
        }
    }

    function writeStoredFilters(storage, storageKey, filters) {
        try {
            var rawValue = storage.getItem(storageKey);
            var values = rawValue ? JSON.parse(rawValue) : {};
            if (!values || typeof values !== 'object') {
                values = {};
            }
            values[getPreferencesContextKey()] = createStoredFilters(filters);
            storage.setItem(storageKey, JSON.stringify(values));
        } catch (error) {
        }
    }

    function clearTemporaryFilters() {
        try {
            var rawValue = window.sessionStorage.getItem(sessionViewsStorageKey);
            var values = rawValue ? JSON.parse(rawValue) : {};
            if (!values || typeof values !== 'object') {
                return;
            }
            delete values[getPreferencesContextKey()];
            window.sessionStorage.setItem(sessionViewsStorageKey, JSON.stringify(values));
        } catch (error) {
        }
    }

    function clearAllTemporaryFilters() {
        try {
            window.sessionStorage.removeItem(sessionViewsStorageKey);
        } catch (error) {
        }
    }

    function readStoredSearch() {
        var value = readStoredValue(window.sessionStorage, searchStorageKey);
        return typeof value === 'string' ? value : '';
    }

    function writeStoredSearch(value) {
        try {
            var rawValue = window.sessionStorage.getItem(searchStorageKey);
            var values = rawValue ? JSON.parse(rawValue) : {};
            if (!values || typeof values !== 'object') {
                values = {};
            }
            values[getPreferencesContextKey()] = String(value || '');
            window.sessionStorage.setItem(searchStorageKey, JSON.stringify(values));
        } catch (error) {
        }
    }

    function buildScopeUrl(scope, assignment, sortName) {
        var organizationId = Number(root.getAttribute('data-omo-stats-oid') || 0);
        var query = ['oid=' + encodeURIComponent(String(organizationId))];
        var nextScope = normalizeScope(scope);
        var nextAssignment = normalizeAssignment(assignment);
        var nextSort = normalizeSort(sortName);
        if (routeCid > 0) {
            query.push('cid=' + encodeURIComponent(String(routeCid)));
        }
        query.push('stats_scope=' + encodeURIComponent(nextScope));
        query.push('stats_assignment=' + encodeURIComponent(nextAssignment));
        query.push('stats_sort=' + encodeURIComponent(nextSort));
        return '/omo/api/stats/index.php?' + query.join('&');
    }

    function setLoading(isLoading) {
        root.classList.toggle('is-loading', Boolean(isLoading));
        if (typeof window.omoSetPanelResultsLoadingSkeleton === 'function') {
            window.omoSetPanelResultsLoadingSkeleton(root, isLoading, {
                contentSelector: '.omo-stats__body'
            });
        }
        Array.prototype.forEach.call(root.querySelectorAll(
            '[data-omo-stats-filter-toggle], [data-omo-stats-scope], [data-omo-stats-assignment], [data-omo-stats-sort], '
            + '[data-omo-stats-view], [data-omo-stats-filter-apply], [data-omo-stats-filter-save], '
            + '[data-omo-stats-filter-more-toggle], [data-omo-stats-filter-more-action]'
        ), function (button) {
            button.disabled = Boolean(isLoading);
        });
    }

    function getRootScrollPosition() {
        var scrollBody = root.querySelector('.omo-panel-view__body');
        if (!scrollBody) {
            return null;
        }
        return {
            left: scrollBody.scrollLeft,
            top: scrollBody.scrollTop
        };
    }

    function restoreRootScrollPosition(nextRoot, position) {
        if (!nextRoot || !position) {
            return;
        }
        var scrollBody = nextRoot.querySelector('.omo-panel-view__body');
        if (!scrollBody) {
            return;
        }
        scrollBody.scrollLeft = position.left || 0;
        scrollBody.scrollTop = position.top || 0;
        window.requestAnimationFrame(function () {
            scrollBody.scrollLeft = position.left || 0;
            scrollBody.scrollTop = position.top || 0;
        });
    }

    function refreshRoot(url, options) {
        var settings = options && typeof options === 'object' ? options : {};
        var targetUrl = url || currentUrl;
        var scrollPosition = settings.preserveScroll === false ? null : getRootScrollPosition();
        if (!targetUrl) {
            return Promise.resolve(null);
        }
        if (typeof window.omoReplaceFetchedPanelRoot !== 'function') {
            window.location.href = resolveUrl(targetUrl);
            return Promise.resolve(null);
        }
        return window.omoReplaceFetchedPanelRoot({
            rootSelector: '#omo-stats-root',
            currentRoot: root,
            url: resolveUrl(targetUrl),
            setLoadingState: setLoading
        }).then(function (nextRoot) {
            restoreRootScrollPosition(nextRoot, scrollPosition);
            return nextRoot;
        });
    }

    function applyView(viewName) {
        var view = normalizeView(viewName);
        currentView = view;
        root.setAttribute('data-omo-stats-current-view', view);
        Array.prototype.forEach.call(root.querySelectorAll('[data-omo-stats-view]'), function (button) {
            var active = button.getAttribute('data-omo-stats-view') === view;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
        Array.prototype.forEach.call(root.querySelectorAll('[data-omo-stats-view-panel]'), function (panel) {
            panel.hidden = panel.getAttribute('data-omo-stats-view-panel') !== view;
        });
        var activeButton = root.querySelector('[data-omo-stats-view="' + view + '"]');
        var viewChip = root.querySelector('[data-omo-stats-view-chip]');
        if (activeButton && viewChip) {
            viewChip.textContent = activeButton.textContent.trim();
        }
        applyQuickSearch();
    }

    function normalizeSearch(value) {
        return String(value || '')
            .toLocaleLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();
    }

    function applyQuickSearch() {
        var query = normalizeSearch(currentSearch);
        var activePanel = root.querySelector('[data-omo-stats-view-panel="' + currentView + '"]');
        Array.prototype.forEach.call(root.querySelectorAll('[data-omo-stats-search-item]'), function (item) {
            item.hidden = query !== '' && normalizeSearch(item.textContent || '').indexOf(query) === -1;
        });
        Array.prototype.forEach.call(root.querySelectorAll('[data-omo-stats-search-group]'), function (group) {
            group.hidden = query !== '' && !group.querySelector('[data-omo-stats-search-item]:not([hidden])');
        });
        Array.prototype.forEach.call(root.querySelectorAll('[data-omo-stats-default-empty]'), function (empty) {
            empty.hidden = query !== '';
        });

        var visibleCount = activePanel
            ? activePanel.querySelectorAll('[data-omo-stats-search-item]:not([hidden])').length
            : 0;
        var empty = root.querySelector('[data-omo-stats-search-empty]');
        if (empty) {
            empty.hidden = query === '' || visibleCount > 0;
        }
        var headerCount = root.querySelector('[data-omo-stats-header-count]');
        if (headerCount) {
            headerCount.textContent = query === ''
                ? String(pageConfig.displayItemCount)
                : String(visibleCount);
        }
    }

    function getActiveFilters() {
        return {
            scope: normalizeScope(currentScope),
            assignment: normalizeAssignment(currentAssignment),
            sort: normalizeSort(currentSort),
            view: normalizeView(currentView)
        };
    }

    function normalizeFilters(filters) {
        var active = getActiveFilters();
        var scope = normalizeScope(filters && filters.scope);
        if (!root.querySelector('[data-omo-stats-scope="' + scope + '"]')) {
            scope = active.scope;
        }
        return {
            scope: scope,
            assignment: normalizeAssignment(filters && filters.assignment),
            sort: normalizeSort(filters && filters.sort),
            view: normalizeView(filters && filters.view)
        };
    }

    function syncFilterChoices() {
        if (!pendingFilters) {
            return;
        }
        pendingFilters = normalizeFilters(pendingFilters);
        [
            {selector: '[data-omo-stats-scope]', attribute: 'data-omo-stats-scope', value: pendingFilters.scope},
            {selector: '[data-omo-stats-assignment]', attribute: 'data-omo-stats-assignment', value: pendingFilters.assignment},
            {selector: '[data-omo-stats-sort]', attribute: 'data-omo-stats-sort', value: pendingFilters.sort},
            {selector: '[data-omo-stats-view]', attribute: 'data-omo-stats-view', value: pendingFilters.view}
        ].forEach(function (choice) {
            Array.prototype.forEach.call(root.querySelectorAll(choice.selector), function (button) {
                var active = button.getAttribute(choice.attribute) === choice.value;
                button.classList.toggle('is-active', active);
                button.setAttribute('aria-pressed', active ? 'true' : 'false');
            });
        });
    }

    function applyFilters(filters, active) {
        var next = normalizeFilters(filters);
        var previous = active || getActiveFilters();
        if (next.scope !== previous.scope || next.assignment !== previous.assignment || next.sort !== previous.sort) {
            currentScope = next.scope;
            currentAssignment = next.assignment;
            currentSort = next.sort;
            refreshRoot(buildScopeUrl(next.scope, next.assignment, next.sort), { preserveScroll: false });
            return;
        }
        applyView(next.view);
        syncFilterChips();
    }

    function closeFilterMoreMenu() {
        Array.prototype.forEach.call(root.querySelectorAll('[data-omo-stats-filter-more-menu]'), function (menu) {
            var panel = menu.querySelector('[data-omo-stats-filter-more-panel]');
            var toggle = menu.querySelector('[data-omo-stats-filter-more-toggle]');
            if (panel) {
                panel.hidden = true;
            }
            menu.classList.remove('is-open');
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    function handleFilterOutsidePointerDown(event) {
        var control = root.querySelector('[data-omo-stats-filter-control]');
        if (control && control.contains(event.target)) {
            return;
        }
        closeFilterPanel(true, false);
    }

    function openFilterPanel() {
        var panel = root.querySelector('[data-omo-stats-filter-panel]');
        if (!panel || filterPanelOpen) {
            return;
        }
        pendingFilters = getActiveFilters();
        closeFilterMoreMenu();
        syncFilterChoices();
        panel.hidden = false;
        filterPanelOpen = true;
        Array.prototype.forEach.call(root.querySelectorAll('[data-omo-stats-filter-toggle]'), function (button) {
            button.setAttribute('aria-expanded', 'true');
        });
        document.addEventListener('pointerdown', handleFilterOutsidePointerDown, true);
    }

    function closeFilterPanel(applyChanges, saveView) {
        var panel = root.querySelector('[data-omo-stats-filter-panel]');
        if (!filterPanelOpen) {
            return;
        }
        filterPanelOpen = false;
        if (panel) {
            panel.hidden = true;
        }
        Array.prototype.forEach.call(root.querySelectorAll('[data-omo-stats-filter-toggle]'), function (button) {
            button.setAttribute('aria-expanded', 'false');
        });
        document.removeEventListener('pointerdown', handleFilterOutsidePointerDown, true);
        closeFilterMoreMenu();

        if (!applyChanges || !pendingFilters) {
            pendingFilters = null;
            return;
        }

        var active = getActiveFilters();
        var next = normalizeFilters(pendingFilters);
        pendingFilters = null;
        if (saveView) {
            storeFilters(next);
            clearTemporaryFilters();
        } else {
            writeStoredFilters(window.sessionStorage, sessionViewsStorageKey, next);
        }

        applyFilters(next, active);
    }

    function applyFilterMoreAction(action) {
        if (!filterPanelOpen || !pendingFilters) {
            return;
        }

        var active = getActiveFilters();
        var next = normalizeFilters(pendingFilters);
        closeFilterPanel(false, false);

        if (action === 'set-default') {
            clearStoredFilters();
            clearTemporaryFilters();
            storeDefaultFilters(next);
            applyFilters(next, active);
            return;
        }

        if (action === 'apply-everywhere') {
            var store = getStoredFiltersStore();
            store.defaultView = createStoredFilters(next);
            store.contexts = {};
            saveStoredFiltersStore(store);
            clearAllTemporaryFilters();
            applyFilters(next, active);
            return;
        }

        if (action === 'restore-default') {
            clearStoredFilters();
            clearTemporaryFilters();
            var store = getStoredFiltersStore();
            store.defaultView = null;
            saveStoredFiltersStore(store);
            var serverDefault = typeof window.omoApplicationViewPreferencesGetDefault === 'function'
                ? window.omoApplicationViewPreferencesGetDefault(root)
                : null;
            applyFilters(serverDefault || {
                scope: 'contextual',
                assignment: 'all',
                sort: 'temporal',
                view: 'cards'
            }, active);
        }
    }

    function syncFilterChips() {
        [
            {button: '[data-omo-stats-scope="' + currentScope + '"]', chip: '[data-omo-stats-scope-chip]'},
            {button: '[data-omo-stats-assignment="' + currentAssignment + '"]', chip: '[data-omo-stats-assignment-chip]'},
            {button: '[data-omo-stats-sort="' + currentSort + '"]', chip: '[data-omo-stats-sort-chip]'},
            {button: '[data-omo-stats-view="' + currentView + '"]', chip: '[data-omo-stats-view-chip]'}
        ].forEach(function (entry) {
            var button = root.querySelector(entry.button);
            var chip = root.querySelector(entry.chip);
            if (button && chip) {
                chip.textContent = button.textContent.trim();
            }
        });
    }

    function initializeViewFilter() {
        currentSearch = readStoredSearch();
        var quickSearch = root.querySelector('[data-omo-stats-quick-search]');
        if (quickSearch) {
            quickSearch.value = currentSearch;
        }
        var temporary = readStoredValue(window.sessionStorage, sessionViewsStorageKey);
        var canUseLegacyPersonal = typeof window.omoApplicationViewPreferencesCanUseLegacyPersonal === 'function'
            && window.omoApplicationViewPreferencesCanUseLegacyPersonal(root);
        var saved = canUseLegacyPersonal ? getStoredFilters() : null;
        var defaultFilters = canUseLegacyPersonal ? getDefaultStoredFilters() : null;
        var serverDefault = typeof window.omoApplicationViewPreferencesGetDefault === 'function'
            ? window.omoApplicationViewPreferencesGetDefault(root)
            : null;
        var personalView = typeof window.omoApplicationViewPreferencesGetPersonal === 'function'
            ? window.omoApplicationViewPreferencesGetPersonal(root)
            : null;
        var preferences = normalizeFilters(temporary || personalView || serverDefault || saved || defaultFilters || getActiveFilters());
        if ((Number.isInteger(initialIndicatorId) && initialIndicatorId > 0) || (Number.isInteger(initialGroupId) && initialGroupId > 0)) {
            preferences.scope = currentScope;
            preferences.assignment = currentAssignment;
            preferences.sort = currentSort;
        }
        if (preferences.scope !== currentScope || preferences.assignment !== currentAssignment || preferences.sort !== currentSort) {
            currentScope = preferences.scope;
            currentAssignment = preferences.assignment;
            currentSort = preferences.sort;
            refreshRoot(buildScopeUrl(preferences.scope, preferences.assignment, preferences.sort), { preserveScroll: false }).catch(function () {
                root.removeAttribute('data-omo-view-filter-pending');
                root.removeAttribute('aria-busy');
            });
            return;
        }
        applyView(preferences.view);
        syncFilterChips();
        root.removeAttribute('data-omo-view-filter-pending');
        root.removeAttribute('aria-busy');
    }

    function executeFetchedScripts(container) {
        return window.commonExecuteFragmentScripts(container);
    }

    function buildDetailUrl(indicatorId) {
        return detailBaseUrl + (detailBaseUrl.indexOf('?') === -1 ? '?' : '&') + 'id=' + encodeURIComponent(String(indicatorId));
    }

    function buildGroupDetailUrl(groupId) {
        return groupDetailBaseUrl + (groupDetailBaseUrl.indexOf('?') === -1 ? '?' : '&') + 'id=' + encodeURIComponent(String(groupId));
    }

    function setDrawerMessage(message, isError) {
        if (!drawerBody) {
            return;
        }
        if (drawerController) {
            drawerController.resetHeader();
        }
        drawerBody.innerHTML = '<div class="generic-section' + (isError ? ' omo-stats-feedback is-error' : '') + '"></div>';
        drawerBody.firstElementChild.textContent = message;
    }

    function openDrawerWithUrl(url) {
        if (!drawer || !drawerBody || !url) {
            return Promise.resolve(false);
        }
        setDrawerMessage(texts.loading, false);
        if (drawerController && typeof drawerController.open === 'function') {
            drawerController.open();
        } else {
            drawer.hidden = false;
            void drawer.offsetWidth;
            window.requestAnimationFrame(function () {
                drawer.classList.add('is-open');
            });
        }
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
            if (localToken !== requestToken || !drawerBody) {
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
                if (typeof window.omoStatsInitInteractiveCharts === 'function') {
                    window.omoStatsInitInteractiveCharts(drawerBody);
                }
                return true;
            });
        }).catch(function () {
            if (localToken === requestToken) {
                setDrawerMessage(texts.loadError, true);
            }
            return false;
        });
    }

    function getCurrentRouteToken() {
        if (useLocalDrawerNavigation) {
            return '';
        }
        if (typeof window.omoParsePopupHashState !== 'function') {
            return '';
        }
        var state = window.omoParsePopupHashState();
        return state && state.routeToken ? String(state.routeToken) : '';
    }

    function closeDrawer(options) {
        var settings = options && typeof options === 'object' ? options : {};
        if (
            settings.force !== true
            && /^stats-(?:i|g|indicator-|group-)(\d+)$/i.test(getCurrentRouteToken())
            && typeof window.omoOpenDrawerHashState === 'function'
        ) {
            window.omoOpenDrawerHashState('stats');
            return;
        }
        if (!drawer) {
            return;
        }
        drawer.classList.remove('is-open');
        window.setTimeout(function () {
            if (!drawer.classList.contains('is-open')) {
                drawer.hidden = true;
                if (drawerBody) {
                    drawerBody.innerHTML = '';
                }
                if (listNeedsRefresh) {
                    listNeedsRefresh = false;
                    refreshRoot(currentUrl);
                }
            }
        }, 180);
    }

    function openIndicator(indicatorId) {
        var resolvedId = Number(indicatorId || 0);
        if (!Number.isInteger(resolvedId) || resolvedId <= 0) {
            return;
        }
        var routeToken = typeof window.omoBuildStatsIndicatorRouteToken === 'function'
            ? window.omoBuildStatsIndicatorRouteToken(resolvedId)
            : 'stats-i' + String(resolvedId);
        if (!useLocalDrawerNavigation && typeof window.omoOpenDrawerHashState === 'function' && routeToken !== getCurrentRouteToken()) {
            window.omoOpenDrawerHashState(routeToken);
            return;
        }
        openDrawerWithUrl(buildDetailUrl(resolvedId));
    }

    function openGroup(groupId) {
        var resolvedId = Number(groupId || 0);
        if (!Number.isInteger(resolvedId) || resolvedId <= 0) {
            return;
        }
        var routeToken = typeof window.omoBuildStatsGroupRouteToken === 'function'
            ? window.omoBuildStatsGroupRouteToken(resolvedId)
            : 'stats-g' + String(resolvedId);
        if (!useLocalDrawerNavigation && typeof window.omoOpenDrawerHashState === 'function' && routeToken !== getCurrentRouteToken()) {
            window.omoOpenDrawerHashState(routeToken);
            return;
        }
        openDrawerWithUrl(buildGroupDetailUrl(resolvedId));
    }

    function postFormData(formData) {
        try {
            var meetingUrl = new URL(currentUrl, window.location.origin);
            var meetingDocumentId = meetingUrl.searchParams.get('pv_meeting_document_id') || '';
            var meetingEditorToken = meetingUrl.searchParams.get('pv_meeting_editor_token') || '';
            if (meetingDocumentId !== '' && meetingEditorToken !== '') {
                formData.set('pv_meeting_document_id', meetingDocumentId);
                formData.set('pv_meeting_editor_token', meetingEditorToken);
            }
        } catch (error) {
        }
        return fetch(resolveUrl('/omo/api/stats/action.php'), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            body: formData
        }).then(function (response) {
            return response.json().catch(function () {
                return null;
            }).then(function (payload) {
                if (!response.ok || !payload || payload.success !== true) {
                    throw new Error(payload && payload.message ? payload.message : texts.loadError);
                }
                return payload;
            });
        });
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (character) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[character];
        });
    }

    function contextHelp(label, text) {
        return '<details class="generic-context-help generic-context-help--compact" data-generic-context-help-hover>'
            + '<summary aria-label="' + escapeHtml(label) + '">?</summary>'
            + '<div class="generic-context-help__content">' + escapeHtml(text) + '</div></details>';
    }

    function getPickerItems() {
        try {
            var raw = root.getAttribute('data-omo-stats-picker') || '[]';
            var items = JSON.parse(raw);
            return Array.isArray(items) ? items : [];
        } catch (error) {
            return [];
        }
    }

    function openGroupDrawerEditor(editData) {
        if (!drawer || !drawerBody) {
            return;
        }

        var isEditing = editData && typeof editData === 'object';
        var groupId = isEditing ? Number(editData.id || 0) : 0;
        var selectedIds = isEditing && Array.isArray(editData.indicatorIds)
            ? editData.indicatorIds.map(function (id) { return String(id); })
            : [];
        var formId = 'omoStatsGroupEditorForm';
        var items = getPickerItems();
        var formHtml = '<form id="' + formId + '" class="omo-stats-picker omo-stats-group-editor generic-form-stack generic-form-stack--compact" data-omo-stats-group-editor-form>'
            + '<div class="generic-form-grid generic-form-grid--pair">'
            + '<div class="generic-form-field"><div class="generic-inline-help"><label class="generic-form-label" for="omo-stats-group-name">' + escapeHtml(texts.groupName) + '</label>' + contextHelp(texts.groupName, texts.groupNameHelp) + '</div><input id="omo-stats-group-name" type="text" class="generic-form-control generic-form-control--compact" data-omo-stats-group-editor-name required></div>'
            + '<div class="generic-form-field"><div class="generic-inline-help"><label class="generic-form-label" for="omo-stats-group-mode">' + escapeHtml(texts.groupMode) + '</label>' + contextHelp(texts.groupMode, texts.groupModeHelp) + '</div><select id="omo-stats-group-mode" class="generic-form-control generic-form-control--compact" data-omo-stats-group-editor-mode><option value="overlay">' + escapeHtml(texts.overlay) + '</option><option value="sum">' + escapeHtml(texts.sum) + '</option></select></div>'
            + '</div>'
            + '<section class="generic-section generic-section--stack generic-form-section generic-form-section--divided generic-form-section--compact omo-stats-group-editor__sources">'
            + '<div class="generic-heading-with-help"><h3 class="generic-card-title generic-card-title--small">' + escapeHtml(texts.groupIndicators) + '</h3>' + contextHelp(texts.groupIndicators, texts.groupIndicatorsHelp) + '</div>'
            + '<div class="generic-form-grid generic-form-grid--pair"><div class="generic-form-field"><label class="generic-form-label" for="omo-stats-group-search">' + escapeHtml(texts.search) + '</label><input id="omo-stats-group-search" type="search" class="generic-form-control generic-form-control--compact" data-omo-stats-group-editor-search placeholder="' + escapeHtml(texts.searchPlaceholder) + '"></div>'
            + '<div class="generic-inline-help omo-stats-group-editor__source-option"><label class="generic-checkbox"><input type="checkbox" data-omo-stats-group-editor-hide-sources><span>' + escapeHtml(texts.groupHideSameHolonSources) + '</span></label>' + contextHelp(texts.groupHideSameHolonSources, texts.groupHideSameHolonSourcesHelp) + '</div></div>'
            + '<div class="generic-form-field"><label class="generic-form-label" for="omo-stats-group-select">' + escapeHtml(texts.visible) + '</label><select id="omo-stats-group-select" class="generic-form-control generic-form-control--compact omo-stats-picker__select" data-omo-stats-group-editor-select size="7" multiple></select></div>'
            + '</section>'
            + '<section class="generic-section generic-section--stack generic-form-section generic-form-section--divided generic-form-section--compact">'
            + '<div class="generic-heading-with-help"><h3 class="generic-card-title generic-card-title--small">' + escapeHtml(texts.groupChart) + '</h3></div>'
            + '<div class="generic-form-grid">'
            + '<div class="generic-form-field"><div class="generic-inline-help"><label class="generic-form-label" for="omo-stats-group-chart-min">' + escapeHtml(texts.chartMinValue) + '</label>' + contextHelp(texts.chartMinValue, texts.groupChartMinValueHelp) + '</div><input id="omo-stats-group-chart-min" type="number" class="generic-form-control generic-form-control--compact" data-omo-stats-group-editor-chart-min-value step="any"></div>'
            + '<div class="generic-form-field"><div class="generic-inline-help"><label class="generic-form-label" for="omo-stats-group-reference-type">' + escapeHtml(texts.groupReferenceType) + '</label>' + contextHelp(texts.groupReferenceType, texts.groupReferenceTypeHelp) + '</div><select id="omo-stats-group-reference-type" class="generic-form-control generic-form-control--compact" name="reference_type" data-omo-stats-reference-type><option value="none">' + escapeHtml(texts.referenceNone) + '</option><option value="ceiling">' + escapeHtml(texts.referenceCeiling) + '</option><option value="objective">' + escapeHtml(texts.referenceObjective) + '</option></select></div>'
            + '<div class="generic-form-field" data-omo-stats-ceiling-editor hidden><div class="generic-inline-help"><label class="generic-form-label" for="omo-stats-group-ceiling">' + escapeHtml(texts.ceilingValue) + '</label>' + contextHelp(texts.ceilingValue, texts.ceilingHelp) + '</div><input id="omo-stats-group-ceiling" type="number" class="generic-form-control generic-form-control--compact" name="ceiling_value" data-omo-stats-ceiling-value step="any"></div>'
            + '</div>'
            + '<section class="omo-stats-reference-editor generic-form-stack generic-form-stack--compact" data-omo-stats-reference-editor data-omo-stats-reference-panel>'
            + '<div class="omo-stats-reference-editor__heading generic-form-section__heading"><div class="generic-heading-with-help"><h3 class="generic-card-title generic-card-title--small">' + escapeHtml(texts.referenceTitle) + '</h3>' + contextHelp(texts.referenceTitle, texts.referenceHelp) + '</div><button type="button" class="generic-action-button generic-action-button--secondary" data-omo-stats-add-reference-point>' + escapeHtml(texts.addReferencePoint) + '</button></div>'
            + '<div class="omo-stats-reference-editor__rail" data-omo-stats-reference-rail></div>'
            + '<div class="omo-stats-reference-editor__points" data-omo-stats-reference-points></div>'
            + '</section>'
            + '</section>'
            + '<div class="omo-stats-feedback" data-omo-stats-group-editor-feedback role="status"></div>'
            + '</form>';

        drawerBody.innerHTML = formHtml;
        if (drawerController && typeof drawerController.open === 'function') {
            drawerController.open();
        } else {
            drawer.hidden = false;
            void drawer.offsetWidth;
            window.requestAnimationFrame(function () {
                drawer.classList.add('is-open');
            });
        }

        var form = drawerBody.querySelector('[data-omo-stats-group-editor-form]');
        var nameInput = drawerBody.querySelector('[data-omo-stats-group-editor-name]');
        var searchInput = drawerBody.querySelector('[data-omo-stats-group-editor-search]');
        var select = drawerBody.querySelector('[data-omo-stats-group-editor-select]');
        var modeInput = drawerBody.querySelector('[data-omo-stats-group-editor-mode]');
        var hideSourcesInput = drawerBody.querySelector('[data-omo-stats-group-editor-hide-sources]');
        var chartMinValueInput = drawerBody.querySelector('[data-omo-stats-group-editor-chart-min-value]');
        var referenceTypeInput = drawerBody.querySelector('[data-omo-stats-reference-type]');
        var ceilingValueInput = drawerBody.querySelector('[data-omo-stats-ceiling-value]');
        var feedback = drawerBody.querySelector('[data-omo-stats-group-editor-feedback]');
        var cancelButton = document.createElement('button');
        var saveButton = document.createElement('button');

        if (!form || !nameInput || !select || !modeInput) {
            setDrawerMessage(texts.loadError, true);
            return;
        }

        nameInput.value = isEditing ? String(editData.name || '') : '';
        modeInput.value = isEditing ? String(editData.displayMode || 'overlay') : 'overlay';
        if (hideSourcesInput) {
            hideSourcesInput.checked = isEditing ? editData.hideSameHolonSources === true : false;
        }
        if (chartMinValueInput) {
            chartMinValueInput.value = isEditing && editData.chartMinValue !== null && editData.chartMinValue !== undefined
                ? String(editData.chartMinValue)
                : '';
        }
        if (referenceTypeInput) {
            referenceTypeInput.value = isEditing ? String(editData.referenceType || 'none') : 'none';
        }
        if (ceilingValueInput) {
            ceilingValueInput.value = isEditing && editData.ceilingValue !== null && editData.ceilingValue !== undefined
                ? String(editData.ceilingValue)
                : '';
        }
        if (typeof window.omoStatsInitReferenceEditor === 'function') {
            window.omoStatsInitReferenceEditor(drawerBody, {
                points: isEditing && Array.isArray(editData.referencePoints) ? editData.referencePoints : [],
                labels: {
                    endpoint: texts.referenceEndpoint,
                    intermediate: texts.referenceIntermediate,
                    position: texts.referencePosition,
                    date: texts.referenceDate,
                    dateAuto: texts.referenceDateAuto,
                    value: texts.referenceValue,
                    remove: texts.removeReferencePoint
                }
            });
        }

        function retainVisibleSelection() {
            selectedIds = Array.prototype.map.call(select.selectedOptions, function (option) {
                return option.value;
            }).concat(selectedIds.filter(function (id) {
                return !Array.prototype.some.call(select.options, function (option) {
                    return option.value === id;
                });
            }));
            selectedIds = selectedIds.filter(function (id, index, values) {
                return values.indexOf(id) === index;
            });
        }

        function renderOptions() {
            retainVisibleSelection();

            var query = String(searchInput ? searchInput.value : '').trim().toLocaleLowerCase();
            select.innerHTML = '';
            items.forEach(function (item) {
                var haystack = [item.name, item.context, item.description].join(' ').toLocaleLowerCase();
                if (query && haystack.indexOf(query) === -1) {
                    return;
                }
                var option = document.createElement('option');
                option.value = String(item.id || '');
                option.textContent = String(item.name || '') + (item.context ? ' - ' + String(item.context) : '');
                option.selected = selectedIds.indexOf(option.value) !== -1;
                select.appendChild(option);
            });
        }

        cancelButton.type = 'button';
        cancelButton.className = 'generic-action-button generic-action-button--secondary';
        cancelButton.textContent = texts.cancel;
        cancelButton.addEventListener('click', function () {
            if (Number.isInteger(groupId) && groupId > 0) {
                openDrawerWithUrl(buildGroupDetailUrl(groupId));
                return;
            }
            closeDrawer({ force: true });
        });

        saveButton.type = 'submit';
        saveButton.setAttribute('form', formId);
        saveButton.className = 'generic-action-button generic-action-button--main';
        saveButton.textContent = isEditing ? texts.update : texts.createGroup;

        if (drawerController) {
            drawerController.setHeader({
                title: isEditing ? texts.editGroupTitle : texts.groupTitle,
                description: '',
                actions: [cancelButton, saveButton]
            });
        }

        renderOptions();
        nameInput.focus();
        if (searchInput) {
            searchInput.addEventListener('input', renderOptions);
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            retainVisibleSelection();
            var selectedIndicatorIds = selectedIds.filter(Boolean);
            var formData = new FormData();
            formData.append('stats_action', isEditing ? 'update_group' : 'create_group');
            formData.append('oid', root.getAttribute('data-omo-stats-oid') || '');
            formData.append('cid', root.getAttribute('data-omo-stats-cid') || '');
            if (isEditing) {
                formData.append('group_id', String(groupId));
            }
            formData.append('name', nameInput.value || '');
            formData.append('display_mode', modeInput.value || 'overlay');
            formData.append('hide_same_holon_sources', hideSourcesInput && hideSourcesInput.checked ? '1' : '0');
            formData.append('chart_min_value', chartMinValueInput ? chartMinValueInput.value || '' : '');
            formData.append('reference_type', referenceTypeInput ? referenceTypeInput.value : 'none');
            if (ceilingValueInput && !ceilingValueInput.disabled) {
                formData.append('ceiling_value', ceilingValueInput.value || '');
            }
            Array.prototype.forEach.call(form.querySelectorAll('[name^="reference_points["]'), function (field) {
                if (!field.disabled) {
                    formData.append(field.name, field.value || '');
                }
            });
            selectedIndicatorIds.forEach(function (id) {
                formData.append('indicator_ids[]', id);
            });

            var usesSharedPendingState = typeof window.omoBeginPendingAction === 'function';
            if (usesSharedPendingState && !window.omoBeginPendingAction(form)) {
                return;
            }
            if (!usesSharedPendingState) {
                saveButton.disabled = true;
                cancelButton.disabled = true;
            }
            if (feedback) {
                feedback.textContent = '';
                feedback.className = 'omo-stats-feedback';
            }

            postFormData(formData).then(function (payload) {
                listNeedsRefresh = true;
                var savedGroupId = Number(payload && payload.id ? payload.id : groupId);
                if (Number.isInteger(savedGroupId) && savedGroupId > 0) {
                    openDrawerWithUrl(buildGroupDetailUrl(savedGroupId));
                    return;
                }
                closeDrawer({ force: true });
            }).catch(function (error) {
                var message = error.message || texts.loadError;
                if (typeof window.omoNotify === 'function') {
                    window.omoNotify(message, 'error');
                } else if (feedback) {
                    feedback.textContent = message;
                    feedback.className = 'omo-stats-feedback is-error';
                }
            }).finally(function () {
                if (usesSharedPendingState && typeof window.omoEndPendingAction === 'function') {
                    window.omoEndPendingAction(form);
                } else {
                    saveButton.disabled = false;
                    cancelButton.disabled = false;
                }
            });
        });
    }

    function openContextPicker(mode, editData) {
        if (mode === 'group') {
            openGroupDrawerEditor(editData);
            return;
        }

        if (typeof window.commonTopbarOpenModal !== 'function') {
            return;
        }
        var isGroup = mode === 'group';
        var isEditing = editData && typeof editData === 'object';
        var title = isGroup
            ? (isEditing ? texts.editGroupTitle : texts.groupTitle)
            : (isEditing ? texts.editImportTitle : texts.importTitle);
        var multiple = isGroup ? ' multiple' : '';
        var selectedIds = isEditing && Array.isArray(editData.indicatorIds)
            ? editData.indicatorIds.map(function (id) { return String(id); })
            : [];
        var firstRender = true;
        var ethercalcAvailable = false;
        var pickerFieldsHtml = (isGroup ? '<label class="omo-stats-picker__field"><span>' + escapeHtml(texts.groupName) + '</span><input type="text" class="generic-form-control" data-omo-stats-picker-name></label>' : '')
            + '<label class="omo-stats-picker__field"><span>' + escapeHtml(texts.search) + '</span><input type="search" class="generic-form-control" data-omo-stats-picker-search placeholder="' + escapeHtml(texts.searchPlaceholder) + '"></label>'
            + '<label class="omo-stats-picker__field"><span>' + escapeHtml(texts.visible) + '</span><select class="generic-form-control omo-stats-picker__select" data-omo-stats-picker-select size="10"' + multiple + '></select></label>'
            + (isGroup ? '<label class="omo-stats-picker__field"><span>' + escapeHtml(texts.groupMode) + '</span><select class="generic-form-control" data-omo-stats-picker-mode><option value="overlay">' + escapeHtml(texts.overlay) + '</option><option value="sum">' + escapeHtml(texts.sum) + '</option></select></label>' : '');
        var spreadsheetAvailable = false;
        var ethercalcFieldsHtml = '';
        if (ethercalcAvailable || spreadsheetAvailable) {
            ethercalcFieldsHtml = '<div class="generic-tabs omo-stats-picker__tabs" data-generic-tabs>'
                + '<div class="generic-tabs__list" aria-label="' + escapeHtml(texts.importTitle) + '">'
                + '<button type="button" class="generic-tabs__tab is-active" data-generic-tab data-generic-tab-target="omo-stats-import-indicators">' + escapeHtml(texts.importSourceIndicators) + '</button>'
                + (ethercalcAvailable ? '<button type="button" class="generic-tabs__tab" data-generic-tab data-generic-tab-target="omo-stats-import-ethercalc">' + escapeHtml(texts.importSourceEthercalc) + '</button>' : '')
                + (spreadsheetAvailable ? '<button type="button" class="generic-tabs__tab" data-generic-tab data-generic-tab-target="omo-stats-import-spreadsheet">' + escapeHtml(texts.importSourceSpreadsheet) + '</button>' : '')
                + '</div>'
                + '<div class="generic-tabs__panels">'
                + '<section id="omo-stats-import-indicators" class="generic-tabs__panel omo-stats-picker" data-generic-tab-panel>' + pickerFieldsHtml + '</section>'
                + (ethercalcAvailable ? '<section id="omo-stats-import-ethercalc" class="generic-tabs__panel omo-stats-picker" data-generic-tab-panel hidden>'
                    + '<label class="omo-stats-picker__field"><span>' + escapeHtml(texts.ethercalcDocument) + '</span><select class="generic-form-control" data-omo-stats-ethercalc-document></select></label>'
                    + '<div class="generic-soft-panel" data-omo-stats-ethercalc-empty hidden>' + escapeHtml(texts.ethercalcNoDocuments) + '</div>'
                    + '<label class="omo-stats-picker__field"><span>' + escapeHtml(texts.ethercalcName) + '</span><input type="text" class="generic-form-control" data-omo-stats-ethercalc-name></label>'
                    + '<label class="omo-stats-picker__field"><span>' + escapeHtml(texts.ethercalcMode) + '</span><select class="generic-form-control" data-omo-stats-ethercalc-mode><option value="cell">' + escapeHtml(texts.ethercalcModeCell) + '</option><option value="table">' + escapeHtml(texts.ethercalcModeTable) + '</option></select></label>'
                    + '<section class="generic-soft-panel generic-soft-panel--stack" data-omo-stats-ethercalc-cell-fields>'
                    + '<label class="omo-stats-picker__field"><span>' + escapeHtml(texts.ethercalcCell) + '</span><input type="text" class="generic-form-control" data-omo-stats-ethercalc-cell value="A1" placeholder="A1"></label>'
                    + '<label class="omo-stats-picker__field"><span>' + escapeHtml(texts.ethercalcMeasurementFrequency) + '</span><select class="generic-form-control" data-omo-stats-ethercalc-frequency><option value="hourly">' + escapeHtml(texts.ethercalcFrequencyHourly) + '</option><option value="daily">' + escapeHtml(texts.ethercalcFrequencyDaily) + '</option><option value="weekly">' + escapeHtml(texts.ethercalcFrequencyWeekly) + '</option><option value="monthly">' + escapeHtml(texts.ethercalcFrequencyMonthly) + '</option><option value="quarterly">' + escapeHtml(texts.ethercalcFrequencyQuarterly) + '</option><option value="semiannual">' + escapeHtml(texts.ethercalcFrequencySemiannual) + '</option><option value="yearly">' + escapeHtml(texts.ethercalcFrequencyYearly) + '</option></select></label>'
                    + '</section>'
                    + '<section class="generic-soft-panel generic-soft-panel--stack" data-omo-stats-ethercalc-table-fields hidden>'
                    + '<label class="omo-stats-picker__field"><span>' + escapeHtml(texts.ethercalcRange) + '</span><input type="text" class="generic-form-control" data-omo-stats-ethercalc-range value="A1:C100" placeholder="A1:C100"></label>'
                    + '<label class="omo-stats-picker__field"><span>' + escapeHtml(texts.ethercalcDateColumn) + '</span><input type="text" class="generic-form-control" data-omo-stats-ethercalc-date-column value="A" placeholder="A"></label>'
                    + '<label class="omo-stats-picker__field"><span>' + escapeHtml(texts.ethercalcValueColumns) + '</span><input type="text" class="generic-form-control" data-omo-stats-ethercalc-value-columns value="B,C" placeholder="B,C"></label>'
                    + '<label class="omo-stats-picker__field"><span>' + escapeHtml(texts.ethercalcSyncFrequency) + '</span><select class="generic-form-control" data-omo-stats-ethercalc-table-frequency><option value="hourly">' + escapeHtml(texts.ethercalcFrequencyHourly) + '</option><option value="daily" selected>' + escapeHtml(texts.ethercalcFrequencyDaily) + '</option><option value="weekly">' + escapeHtml(texts.ethercalcFrequencyWeekly) + '</option><option value="monthly">' + escapeHtml(texts.ethercalcFrequencyMonthly) + '</option><option value="quarterly">' + escapeHtml(texts.ethercalcFrequencyQuarterly) + '</option><option value="semiannual">' + escapeHtml(texts.ethercalcFrequencySemiannual) + '</option><option value="yearly">' + escapeHtml(texts.ethercalcFrequencyYearly) + '</option></select></label>'
                    + '<p class="generic-description">' + escapeHtml(texts.ethercalcTableHelp) + '</p>'
                    + '</section>'
                    + '</section>' : '')
                + (spreadsheetAvailable ? '<section id="omo-stats-import-spreadsheet" class="generic-tabs__panel omo-stats-picker" data-generic-tab-panel hidden>'
                    + '<label class="omo-stats-picker__field"><span>' + escapeHtml(texts.spreadsheetDocument) + '</span><select class="generic-form-control" data-omo-stats-spreadsheet-document></select></label>'
                    + '<div class="generic-soft-panel" data-omo-stats-spreadsheet-empty hidden>' + escapeHtml(texts.spreadsheetNoDocuments) + '</div>'
                    + '<label class="omo-stats-picker__field"><span>' + escapeHtml(texts.spreadsheetName) + '</span><input type="text" class="generic-form-control" data-omo-stats-spreadsheet-name></label>'
                    + '<label class="omo-stats-picker__field"><span>' + escapeHtml(texts.spreadsheetSheet) + '</span><input type="text" class="generic-form-control" data-omo-stats-spreadsheet-sheet placeholder="Feuille1"></label>'
                    + '<label class="omo-stats-picker__field"><span>' + escapeHtml(texts.spreadsheetMode) + '</span><select class="generic-form-control" data-omo-stats-spreadsheet-mode><option value="cell">' + escapeHtml(texts.spreadsheetModeCell) + '</option><option value="table">' + escapeHtml(texts.spreadsheetModeTable) + '</option></select></label>'
                    + '<section class="generic-soft-panel generic-soft-panel--stack" data-omo-stats-spreadsheet-cell-fields>'
                    + '<label class="omo-stats-picker__field"><span>' + escapeHtml(texts.spreadsheetCell) + '</span><input type="text" class="generic-form-control" data-omo-stats-spreadsheet-cell value="A1" placeholder="A1"></label>'
                    + '<label class="omo-stats-picker__field"><span>' + escapeHtml(texts.spreadsheetMeasurementFrequency) + '</span><select class="generic-form-control" data-omo-stats-spreadsheet-frequency><option value="hourly">' + escapeHtml(texts.spreadsheetFrequencyHourly) + '</option><option value="daily" selected>' + escapeHtml(texts.spreadsheetFrequencyDaily) + '</option><option value="weekly">' + escapeHtml(texts.spreadsheetFrequencyWeekly) + '</option><option value="monthly">' + escapeHtml(texts.spreadsheetFrequencyMonthly) + '</option><option value="quarterly">' + escapeHtml(texts.spreadsheetFrequencyQuarterly) + '</option><option value="semiannual">' + escapeHtml(texts.spreadsheetFrequencySemiannual) + '</option><option value="yearly">' + escapeHtml(texts.spreadsheetFrequencyYearly) + '</option></select></label>'
                    + '</section>'
                    + '<section class="generic-soft-panel generic-soft-panel--stack" data-omo-stats-spreadsheet-table-fields hidden>'
                    + '<label class="omo-stats-picker__field"><span>' + escapeHtml(texts.spreadsheetRange) + '</span><input type="text" class="generic-form-control" data-omo-stats-spreadsheet-range value="A1:C100" placeholder="A1:C100"></label>'
                    + '<label class="omo-stats-picker__field"><span>' + escapeHtml(texts.spreadsheetDateColumn) + '</span><input type="text" class="generic-form-control" data-omo-stats-spreadsheet-date-column value="A" placeholder="A"></label>'
                    + '<label class="omo-stats-picker__field"><span>' + escapeHtml(texts.spreadsheetValueColumns) + '</span><input type="text" class="generic-form-control" data-omo-stats-spreadsheet-value-columns value="B,C" placeholder="B,C"></label>'
                    + '<label class="omo-stats-picker__field"><span>' + escapeHtml(texts.spreadsheetSyncFrequency) + '</span><select class="generic-form-control" data-omo-stats-spreadsheet-table-frequency><option value="hourly">' + escapeHtml(texts.spreadsheetFrequencyHourly) + '</option><option value="daily" selected>' + escapeHtml(texts.spreadsheetFrequencyDaily) + '</option><option value="weekly">' + escapeHtml(texts.spreadsheetFrequencyWeekly) + '</option><option value="monthly">' + escapeHtml(texts.spreadsheetFrequencyMonthly) + '</option><option value="quarterly">' + escapeHtml(texts.spreadsheetFrequencyQuarterly) + '</option><option value="semiannual">' + escapeHtml(texts.spreadsheetFrequencySemiannual) + '</option><option value="yearly">' + escapeHtml(texts.spreadsheetFrequencyYearly) + '</option></select></label>'
                    + '<p class="generic-description">' + escapeHtml(texts.spreadsheetTableHelp) + '</p>'
                    + '</section>'
                    + '</section>' : '')
                + '</div>'
                + '</div>';
        }
        var html = '<div class="omo-stats-picker">'
            + (ethercalcFieldsHtml || pickerFieldsHtml)
            + '<div class="omo-stats-picker__actions"><button type="button" class="generic-action-button generic-action-button--secondary" data-omo-stats-picker-cancel>' + escapeHtml(texts.cancel) + '</button><button type="button" class="generic-action-button generic-action-button--main" data-omo-stats-picker-apply>' + escapeHtml(isEditing ? texts.update : (isGroup ? texts.createGroup : texts.add)) + '</button></div>'
            + '</div>';
        window.commonTopbarOpenModal(title, html, 'html');
        var modalBody = document.getElementById('commonTopbarModalBody');
        if (!modalBody) {
            return;
        }
        var items = getPickerItems();
        var searchInput = modalBody.querySelector('[data-omo-stats-picker-search]');
        var select = modalBody.querySelector('[data-omo-stats-picker-select]');
        var nameInput = modalBody.querySelector('[data-omo-stats-picker-name]');
        var modeInput = modalBody.querySelector('[data-omo-stats-picker-mode]');
        var applyButton = modalBody.querySelector('[data-omo-stats-picker-apply]');
        var ethercalcTab = modalBody.querySelector('[data-generic-tab-target="omo-stats-import-ethercalc"]');
        var ethercalcDocumentSelect = modalBody.querySelector('[data-omo-stats-ethercalc-document]');
        var ethercalcEmpty = modalBody.querySelector('[data-omo-stats-ethercalc-empty]');
        var ethercalcNameInput = modalBody.querySelector('[data-omo-stats-ethercalc-name]');
        var ethercalcModeSelect = modalBody.querySelector('[data-omo-stats-ethercalc-mode]');
        var ethercalcCellFields = modalBody.querySelector('[data-omo-stats-ethercalc-cell-fields]');
        var ethercalcTableFields = modalBody.querySelector('[data-omo-stats-ethercalc-table-fields]');
        var spreadsheetTab = modalBody.querySelector('[data-generic-tab-target="omo-stats-import-spreadsheet"]');
        var spreadsheetDocumentSelect = modalBody.querySelector('[data-omo-stats-spreadsheet-document]');
        var spreadsheetEmpty = modalBody.querySelector('[data-omo-stats-spreadsheet-empty]');
        var spreadsheetNameInput = modalBody.querySelector('[data-omo-stats-spreadsheet-name]');
        var spreadsheetModeSelect = modalBody.querySelector('[data-omo-stats-spreadsheet-mode]');
        var spreadsheetCellFields = modalBody.querySelector('[data-omo-stats-spreadsheet-cell-fields]');
        var spreadsheetTableFields = modalBody.querySelector('[data-omo-stats-spreadsheet-table-fields]');
        if (nameInput && isEditing) {
            nameInput.value = String(editData.name || '');
        }
        if (modeInput && isEditing) {
            modeInput.value = String(editData.displayMode || 'overlay');
        }
        if (typeof window.initGenericTabs === 'function') {
            window.initGenericTabs(modalBody);
        }
        function renderEthercalcDocuments() {
            if (!ethercalcDocumentSelect) {
                return;
            }
            var documents = getEthercalcPickerItems();
            ethercalcDocumentSelect.innerHTML = '';
            documents.forEach(function (item) {
                var option = document.createElement('option');
                option.value = String(item.id || '');
                option.textContent = String(item.name || '');
                ethercalcDocumentSelect.appendChild(option);
            });
            ethercalcDocumentSelect.disabled = documents.length === 0;
            if (ethercalcEmpty) {
                ethercalcEmpty.hidden = documents.length > 0;
            }
            if (ethercalcNameInput && !ethercalcNameInput.value && documents.length > 0) {
                ethercalcNameInput.value = String(documents[0].name || '');
            }
        }
        function syncEthercalcMode() {
            var isTableMode = ethercalcModeSelect && ethercalcModeSelect.value === 'table';
            if (ethercalcCellFields) {
                ethercalcCellFields.hidden = !!isTableMode;
            }
            if (ethercalcTableFields) {
                ethercalcTableFields.hidden = !isTableMode;
            }
        }
        function renderSpreadsheetDocuments() {
            if (!spreadsheetDocumentSelect) {
                return;
            }
            var documents = getSpreadsheetPickerItems();
            spreadsheetDocumentSelect.innerHTML = '';
            documents.forEach(function (item) {
                var option = document.createElement('option');
                option.value = String(item.id || '');
                option.textContent = String(item.name || '') + (item.filename ? ' (' + String(item.filename) + ')' : '');
                spreadsheetDocumentSelect.appendChild(option);
            });
            spreadsheetDocumentSelect.disabled = documents.length === 0;
            if (spreadsheetEmpty) {
                spreadsheetEmpty.hidden = documents.length > 0;
            }
            if (spreadsheetNameInput && !spreadsheetNameInput.value && documents.length > 0) {
                spreadsheetNameInput.value = String(documents[0].name || '');
            }
        }
        function syncSpreadsheetMode() {
            var isTableMode = spreadsheetModeSelect && spreadsheetModeSelect.value === 'table';
            if (spreadsheetCellFields) {
                spreadsheetCellFields.hidden = !!isTableMode;
            }
            if (spreadsheetTableFields) {
                spreadsheetTableFields.hidden = !isTableMode;
            }
        }
        function isEthercalcTabActive() {
            return !!ethercalcTab && ethercalcTab.classList.contains('is-active');
        }
        function isSpreadsheetTabActive() {
            return !!spreadsheetTab && spreadsheetTab.classList.contains('is-active');
        }
        function syncApplyButton() {
            if (!applyButton || isGroup) {
                return;
            }
            applyButton.textContent = isEthercalcTabActive()
                ? texts.ethercalcCreateAction
                : (isSpreadsheetTabActive() ? texts.spreadsheetCreateAction : (isEditing ? texts.update : texts.add));
        }
        renderEthercalcDocuments();
        renderSpreadsheetDocuments();
        syncEthercalcMode();
        syncSpreadsheetMode();
        syncApplyButton();
        if (ethercalcModeSelect) {
            ethercalcModeSelect.addEventListener('change', syncEthercalcMode);
        }
        if (spreadsheetModeSelect) {
            spreadsheetModeSelect.addEventListener('change', syncSpreadsheetMode);
        }
        if (ethercalcTab) {
            ethercalcTab.closest('[data-generic-tabs]').addEventListener('click', function () {
                window.setTimeout(syncApplyButton, 0);
            });
        }
        if (spreadsheetTab) {
            spreadsheetTab.closest('[data-generic-tabs]').addEventListener('click', function () {
                window.setTimeout(syncApplyButton, 0);
            });
        }
        function renderOptions() {
            if (!select) {
                return;
            }
            if (!firstRender) {
                selectedIds = Array.prototype.map.call(select.selectedOptions, function (option) {
                    return option.value;
                });
            }
            var query = String(searchInput ? searchInput.value : '').trim().toLocaleLowerCase();
            select.innerHTML = '';
            items.forEach(function (item) {
                var haystack = [item.name, item.context, item.description].join(' ').toLocaleLowerCase();
                if (query && haystack.indexOf(query) === -1) {
                    return;
                }
                var option = document.createElement('option');
                option.value = String(item.id || '');
                option.textContent = String(item.name || '') + (item.context ? ' - ' + String(item.context) : '');
                option.selected = selectedIds.indexOf(option.value) !== -1;
                select.appendChild(option);
            });
            firstRender = false;
        }
        renderOptions();
        if (searchInput) {
            searchInput.focus();
            searchInput.addEventListener('input', renderOptions);
        }
        var cancelButton = modalBody.querySelector('[data-omo-stats-picker-cancel]');
        if (cancelButton) {
            cancelButton.addEventListener('click', function () {
                if (typeof window.commonTopbarCloseModal === 'function') {
                    window.commonTopbarCloseModal();
                }
            });
        }
        if (applyButton) {
            applyButton.addEventListener('click', function () {
                if (isEthercalcTabActive()) {
                    if (!ethercalcDocumentSelect || !ethercalcDocumentSelect.value) {
                        window.omoNotify(texts.ethercalcNoDocuments, 'error');
                        return;
                    }
                    var ethercalcFormData = new FormData();
                    ethercalcFormData.append('stats_action', 'create_ethercalc_indicator');
                    ethercalcFormData.append('oid', root.getAttribute('data-omo-stats-oid') || '');
                    ethercalcFormData.append('cid', root.getAttribute('data-omo-stats-cid') || '');
                    ethercalcFormData.append('ethercalc_document_id', ethercalcDocumentSelect.value);
                    ethercalcFormData.append('ethercalc_name', ethercalcNameInput ? ethercalcNameInput.value : '');
                    ethercalcFormData.append('ethercalc_mode', ethercalcModeSelect ? ethercalcModeSelect.value : 'cell');
                    ethercalcFormData.append('ethercalc_cell', (modalBody.querySelector('[data-omo-stats-ethercalc-cell]') || {}).value || '');
                    var ethercalcFrequencyField = ethercalcModeSelect && ethercalcModeSelect.value === 'table'
                        ? modalBody.querySelector('[data-omo-stats-ethercalc-table-frequency]')
                        : modalBody.querySelector('[data-omo-stats-ethercalc-frequency]');
                    ethercalcFormData.append('ethercalc_frequency', ethercalcFrequencyField ? ethercalcFrequencyField.value : '');
                    ethercalcFormData.append('ethercalc_range', (modalBody.querySelector('[data-omo-stats-ethercalc-range]') || {}).value || '');
                    ethercalcFormData.append('ethercalc_date_column', (modalBody.querySelector('[data-omo-stats-ethercalc-date-column]') || {}).value || '');
                    ethercalcFormData.append('ethercalc_value_columns', (modalBody.querySelector('[data-omo-stats-ethercalc-value-columns]') || {}).value || '');
                    applyButton.disabled = true;
                    postFormData(ethercalcFormData).then(function () {
                        if (typeof window.commonTopbarCloseModal === 'function') {
                            window.commonTopbarCloseModal();
                        }
                        return refreshRoot(currentUrl);
                    }).catch(function (error) {
                        window.omoNotify(error.message || texts.loadError, 'error');
                        applyButton.disabled = false;
                    });
                    return;
                }
                if (isSpreadsheetTabActive()) {
                    if (!spreadsheetDocumentSelect || !spreadsheetDocumentSelect.value) {
                        window.omoNotify(texts.spreadsheetNoDocuments, 'error');
                        return;
                    }
                    var spreadsheetFormData = new FormData();
                    spreadsheetFormData.append('stats_action', 'create_spreadsheet_indicator');
                    spreadsheetFormData.append('oid', root.getAttribute('data-omo-stats-oid') || '');
                    spreadsheetFormData.append('cid', root.getAttribute('data-omo-stats-cid') || '');
                    spreadsheetFormData.append('spreadsheet_document_id', spreadsheetDocumentSelect.value);
                    spreadsheetFormData.append('spreadsheet_name', spreadsheetNameInput ? spreadsheetNameInput.value : '');
                    spreadsheetFormData.append('spreadsheet_sheet', (modalBody.querySelector('[data-omo-stats-spreadsheet-sheet]') || {}).value || '');
                    spreadsheetFormData.append('spreadsheet_mode', spreadsheetModeSelect ? spreadsheetModeSelect.value : 'cell');
                    spreadsheetFormData.append('spreadsheet_cell', (modalBody.querySelector('[data-omo-stats-spreadsheet-cell]') || {}).value || '');
                    var spreadsheetFrequencyField = spreadsheetModeSelect && spreadsheetModeSelect.value === 'table'
                        ? modalBody.querySelector('[data-omo-stats-spreadsheet-table-frequency]')
                        : modalBody.querySelector('[data-omo-stats-spreadsheet-frequency]');
                    spreadsheetFormData.append('spreadsheet_frequency', spreadsheetFrequencyField ? spreadsheetFrequencyField.value : '');
                    spreadsheetFormData.append('spreadsheet_range', (modalBody.querySelector('[data-omo-stats-spreadsheet-range]') || {}).value || '');
                    spreadsheetFormData.append('spreadsheet_date_column', (modalBody.querySelector('[data-omo-stats-spreadsheet-date-column]') || {}).value || '');
                    spreadsheetFormData.append('spreadsheet_value_columns', (modalBody.querySelector('[data-omo-stats-spreadsheet-value-columns]') || {}).value || '');
                    applyButton.disabled = true;
                    postFormData(spreadsheetFormData).then(function () {
                        if (typeof window.commonTopbarCloseModal === 'function') {
                            window.commonTopbarCloseModal();
                        }
                        return refreshRoot(currentUrl);
                    }).catch(function (error) {
                        window.omoNotify(error.message || texts.loadError, 'error');
                        applyButton.disabled = false;
                    });
                    return;
                }
                var selectedIds = Array.prototype.map.call(select ? select.selectedOptions : [], function (option) {
                    return option.value;
                }).filter(Boolean);
                var formData = new FormData();
                formData.append('stats_action', isEditing ? (isGroup ? 'update_group' : 'update_import') : (isGroup ? 'create_group' : 'import_indicator'));
                formData.append('oid', root.getAttribute('data-omo-stats-oid') || '');
                formData.append('cid', root.getAttribute('data-omo-stats-cid') || '');
                if (isGroup) {
                    if (isEditing) {
                        formData.append('group_id', String(editData.id || ''));
                    }
                    formData.append('name', nameInput ? nameInput.value : '');
                    formData.append('display_mode', modeInput ? modeInput.value : 'overlay');
                    selectedIds.forEach(function (id) {
                        formData.append('indicator_ids[]', id);
                    });
                } else {
                    if (isEditing) {
                        formData.append('import_id', String(editData.id || ''));
                    }
                    formData.append('indicator_id', selectedIds[0] || '');
                }
                applyButton.disabled = true;
                postFormData(formData).then(function () {
                    if (typeof window.commonTopbarCloseModal === 'function') {
                        window.commonTopbarCloseModal();
                    }
                    return refreshRoot(currentUrl);
                }).catch(function (error) {
                    window.omoNotify(error.message || texts.loadError, 'error');
                    applyButton.disabled = false;
                });
            });
        }
    }

    Array.prototype.forEach.call(root.querySelectorAll('[data-omo-stats-filter-toggle]'), function (button) {
        button.addEventListener('click', function () {
            if (filterPanelOpen) {
                closeFilterPanel(true, false);
            } else {
                openFilterPanel();
            }
        });
    });

    var filterPanel = root.querySelector('[data-omo-stats-filter-panel]');
    if (filterPanel) {
        filterPanel.addEventListener('click', function (event) {
            var moreToggle = event.target.closest('[data-omo-stats-filter-more-toggle]');
            if (moreToggle) {
                event.preventDefault();
                event.stopPropagation();
                var moreMenu = moreToggle.closest('[data-omo-stats-filter-more-menu]');
                var morePanel = moreMenu ? moreMenu.querySelector('[data-omo-stats-filter-more-panel]') : null;
                var isMoreMenuOpen = !!morePanel && !morePanel.hidden;
                closeFilterMoreMenu();
                if (!isMoreMenuOpen && morePanel) {
                    morePanel.hidden = false;
                    moreMenu.classList.add('is-open');
                    moreToggle.setAttribute('aria-expanded', 'true');
                }
                return;
            }
            var moreAction = event.target.closest('[data-omo-stats-filter-more-action]');
            if (moreAction) {
                event.preventDefault();
                event.stopPropagation();
                applyFilterMoreAction(moreAction.getAttribute('data-omo-stats-filter-more-action') || '');
                return;
            }
            var applyButton = event.target.closest('[data-omo-stats-filter-apply]');
            if (applyButton) {
                event.preventDefault();
                closeFilterPanel(true, false);
                return;
            }
            var saveButton = event.target.closest('[data-omo-stats-filter-save]');
            if (saveButton) {
                event.preventDefault();
                closeFilterPanel(true, true);
                return;
            }
            var scopeButton = event.target.closest('[data-omo-stats-scope]');
            if (scopeButton && pendingFilters) {
                pendingFilters.scope = normalizeScope(scopeButton.getAttribute('data-omo-stats-scope') || '');
                syncFilterChoices();
                return;
            }
            var assignmentButton = event.target.closest('[data-omo-stats-assignment]');
            if (assignmentButton && pendingFilters) {
                pendingFilters.assignment = normalizeAssignment(assignmentButton.getAttribute('data-omo-stats-assignment') || '');
                syncFilterChoices();
                return;
            }
            var sortButton = event.target.closest('[data-omo-stats-sort]');
            if (sortButton && pendingFilters) {
                pendingFilters.sort = normalizeSort(sortButton.getAttribute('data-omo-stats-sort') || '');
                syncFilterChoices();
                return;
            }
            var viewButton = event.target.closest('[data-omo-stats-view]');
            if (viewButton && pendingFilters) {
                pendingFilters.view = normalizeView(viewButton.getAttribute('data-omo-stats-view') || '');
                syncFilterChoices();
            }
        });
    }

    var quickSearch = root.querySelector('[data-omo-stats-quick-search]');
    if (quickSearch) {
        quickSearch.addEventListener('input', function () {
            currentSearch = quickSearch.value || '';
            writeStoredSearch(currentSearch);
            applyQuickSearch();
        });
        quickSearch.addEventListener('search', function () {
            currentSearch = quickSearch.value || '';
            writeStoredSearch(currentSearch);
            applyQuickSearch();
        });
    }

    root.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && filterPanelOpen) {
            closeFilterPanel(false, false);
        }
    });

    Array.prototype.forEach.call(root.querySelectorAll('[data-omo-stats-indicator-id]'), function (item) {
        function activate(event) {
            if (event.target.closest('[data-omo-stats-item-menu], [data-omo-stats-open-editor-url]')) {
                return;
            }
            if (event.type === 'keydown' && event.key !== 'Enter' && event.key !== ' ') {
                return;
            }
            event.preventDefault();
            openIndicator(item.getAttribute('data-omo-stats-indicator-id'));
        }
        item.addEventListener('click', activate);
        item.addEventListener('keydown', activate);
    });

    Array.prototype.forEach.call(root.querySelectorAll('[data-omo-stats-group-id]'), function (item) {
        function activate(event) {
            if (event.target.closest('[data-omo-stats-item-menu], [data-omo-stats-edit-group], [data-omo-stats-delete-group]')) {
                return;
            }
            if (event.type === 'keydown' && event.key !== 'Enter' && event.key !== ' ') {
                return;
            }
            event.preventDefault();
            openGroup(item.getAttribute('data-omo-stats-group-id'));
        }
        item.addEventListener('click', activate);
        item.addEventListener('keydown', activate);
    });

    root.addEventListener('click', function (event) {
        var indicatorLink = event.target.closest('[data-omo-stats-open-indicator]');
        if (indicatorLink) {
            event.preventDefault();
            event.stopPropagation();
            openIndicator(indicatorLink.getAttribute('data-omo-stats-open-indicator'));
            return;
        }

        var toggle = event.target.closest('[data-omo-stats-item-menu-toggle]');
        if (toggle) {
            event.preventDefault();
            event.stopPropagation();
            openStatsItemMenu(toggle);
            return;
        }

        var editorLink = event.target.closest('[data-omo-stats-open-editor-url]');
        if (editorLink) {
            event.preventDefault();
            event.stopPropagation();
            openDrawerWithUrl(editorLink.getAttribute('data-omo-stats-open-editor-url') || '');
            return;
        }

        var editImportButton = event.target.closest('[data-omo-stats-edit-import]');
        if (editImportButton) {
            event.preventDefault();
            event.stopPropagation();
            openContextPicker('import', {
                id: editImportButton.getAttribute('data-omo-stats-edit-import') || '',
                indicatorIds: [editImportButton.getAttribute('data-omo-stats-indicator-id') || '']
            });
            return;
        }

        var editGroupButton = event.target.closest('[data-omo-stats-edit-group]');
        if (editGroupButton) {
            event.preventDefault();
            event.stopPropagation();
            var groupIndicatorIds = [];
            try {
                groupIndicatorIds = JSON.parse(editGroupButton.getAttribute('data-omo-stats-group-indicators') || '[]');
            } catch (error) {
                groupIndicatorIds = [];
            }
            openContextPicker('group', {
                id: editGroupButton.getAttribute('data-omo-stats-edit-group') || '',
                name: editGroupButton.getAttribute('data-omo-stats-group-name') || '',
                displayMode: editGroupButton.getAttribute('data-omo-stats-group-mode') || 'overlay',
                hideSameHolonSources: editGroupButton.getAttribute('data-omo-stats-group-hide-same-holon-sources') === '1',
                referenceType: editGroupButton.getAttribute('data-omo-stats-group-reference-type') || 'none',
                ceilingValue: editGroupButton.getAttribute('data-omo-stats-group-ceiling-value') || '',
                chartMinValue: editGroupButton.getAttribute('data-omo-stats-group-chart-min-value') || '',
                referencePoints: (function () {
                    try {
                        var points = JSON.parse(editGroupButton.getAttribute('data-omo-stats-group-reference-points') || '[]');
                        return Array.isArray(points) ? points : [];
                    } catch (error) {
                        return [];
                    }
                })(),
                indicatorIds: Array.isArray(groupIndicatorIds) ? groupIndicatorIds : []
            });
            return;
        }

        var deleteImportButton = event.target.closest('[data-omo-stats-delete-import]');
        if (deleteImportButton) {
            event.preventDefault();
            event.stopPropagation();
            if (!window.confirm(texts.confirmDeleteImport)) {
                return;
            }
            var deleteImportData = new FormData();
            deleteImportData.append('stats_action', 'delete_import');
            deleteImportData.append('import_id', deleteImportButton.getAttribute('data-omo-stats-delete-import') || '');
            deleteImportData.append('oid', root.getAttribute('data-omo-stats-oid') || '');
            deleteImportButton.disabled = true;
            postFormData(deleteImportData).then(function () {
                return refreshRoot(currentUrl);
            }).catch(function (error) {
                deleteImportButton.disabled = false;
                window.omoNotify(error.message || texts.loadError, 'error');
            });
            return;
        }

        var deleteGroupButton = event.target.closest('[data-omo-stats-delete-group]');
        if (deleteGroupButton) {
            event.preventDefault();
            event.stopPropagation();
            if (!window.confirm(texts.confirmDeleteGroup)) {
                return;
            }
            var deleteGroupData = new FormData();
            deleteGroupData.append('stats_action', 'delete_group');
            deleteGroupData.append('group_id', deleteGroupButton.getAttribute('data-omo-stats-delete-group') || '');
            deleteGroupData.append('oid', root.getAttribute('data-omo-stats-oid') || '');
            deleteGroupButton.disabled = true;
            postFormData(deleteGroupData).then(function () {
                return refreshRoot(currentUrl);
            }).catch(function (error) {
                deleteGroupButton.disabled = false;
                window.omoNotify(error.message || texts.loadError, 'error');
            });
            return;
        }

        var deleteButton = event.target.closest('[data-omo-stats-delete-indicator]');
        if (!deleteButton) {
            return;
        }
        event.preventDefault();
        event.stopPropagation();
        if (!window.confirm(texts.confirmDeleteIndicator)) {
            return;
        }
        var formData = new FormData();
        formData.append('stats_action', 'delete_indicator');
        formData.append('indicator_id', deleteButton.getAttribute('data-omo-stats-delete-indicator') || '');
        formData.append('oid', root.getAttribute('data-omo-stats-oid') || '');
        deleteButton.disabled = true;
        postFormData(formData).then(function () {
            closeDrawer({force: true});
            return refreshRoot(currentUrl);
        }).catch(function (error) {
            deleteButton.disabled = false;
            window.omoNotify(error.message || texts.loadError, 'error');
        });
    });

    statsMenuOwnerDocument.addEventListener('click', function (event) {
        var floatingAction = event.target.closest('[data-omo-stats-floating-menu-action]');
        if (floatingAction) {
            event.preventDefault();
            event.stopPropagation();
            var originalAction = floatingStatsMenuActions.get(floatingAction);
            closeStatsItemMenus();
            if (originalAction && originalAction.isConnected) {
                originalAction.click();
            }
            return;
        }
        if (event.target.closest('[data-omo-stats-floating-menu]')) {
            return;
        }
        if (event.target.closest('[data-omo-stats-item-menu]')) {
            return;
        }
        closeStatsItemMenus();
    });

    var createButton = root.querySelector('[data-omo-stats-open-create]');
    if (createButton) {
        createButton.addEventListener('click', function () {
            openDrawerWithUrl(createUrl);
        });
    }

    var moreMenu = root.querySelector('[data-omo-stats-more-menu]');
    var moreToggle = root.querySelector('[data-omo-stats-more-toggle]');
    var morePanel = root.querySelector('[data-omo-stats-more-panel]');
    if (moreToggle && morePanel) {
        moreToggle.addEventListener('click', function (event) {
            event.preventDefault();
            var isOpen = !morePanel.hidden;
            morePanel.hidden = isOpen;
            moreToggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
            if (moreMenu) {
                moreMenu.classList.toggle('is-open', !isOpen);
            }
        });
        document.addEventListener('click', function (event) {
            if (moreMenu && !moreMenu.contains(event.target)) {
                morePanel.hidden = true;
                moreToggle.setAttribute('aria-expanded', 'false');
                if (moreMenu) {
                    moreMenu.classList.remove('is-open');
                }
            }
        });
    }
    Array.prototype.forEach.call(root.querySelectorAll('[data-omo-stats-open-import], [data-omo-stats-open-group]'), function (button) {
        button.addEventListener('click', function () {
            if (morePanel) {
                morePanel.hidden = true;
            }
            if (moreToggle) {
                moreToggle.setAttribute('aria-expanded', 'false');
            }
            if (moreMenu) {
                moreMenu.classList.remove('is-open');
            }
            openContextPicker(button.hasAttribute('data-omo-stats-open-group') ? 'group' : 'import');
        });
    });

    Array.prototype.forEach.call(root.querySelectorAll('[data-omo-stats-drawer-close]'), function (button) {
        button.addEventListener('click', closeDrawer);
    });

    if (drawer) {
        drawer.addEventListener('click', function (event) {
            var editButton = event.target.closest('[data-omo-stats-open-editor-url]');
            if (editButton) {
                event.preventDefault();
                openDrawerWithUrl(editButton.getAttribute('data-omo-stats-open-editor-url') || '');
                return;
            }

            var cancelButton = event.target.closest('[data-omo-stats-cancel-editor]');
            if (cancelButton) {
                event.preventDefault();
                var indicatorId = Number(cancelButton.getAttribute('data-indicator-id') || 0);
                if (indicatorId > 0) {
                    openDrawerWithUrl(buildDetailUrl(indicatorId));
                } else {
                    closeDrawer({force: true});
                }
                return;
            }

            var deleteButton = event.target.closest('[data-omo-stats-delete-value]');
            if (!deleteButton) {
                return;
            }
            event.preventDefault();
            if (!window.confirm(texts.confirmDelete)) {
                return;
            }
            var detail = deleteButton.closest('[data-omo-stats-detail]');
            var formData = new FormData();
            formData.append('stats_action', 'delete_value');
            formData.append('value_id', deleteButton.getAttribute('data-omo-stats-delete-value') || '');
            formData.append('oid', root.getAttribute('data-omo-stats-oid') || '');
            deleteButton.disabled = true;
            postFormData(formData).then(function () {
                listNeedsRefresh = true;
                return openDrawerWithUrl(detail ? detail.getAttribute('data-detail-url') : '');
            }).catch(function (error) {
                deleteButton.disabled = false;
                window.omoNotify(error.message || texts.loadError, 'error');
            });
        });

    }

    if (drawerBody) {
        drawerBody.addEventListener('submit', function (event) {
            var form = event.target.closest('[data-omo-stats-add-value-form]');
            if (!form) {
                return;
            }
            event.preventDefault();
            var submitButton = form.querySelector('button[type="submit"]');
            var feedback = form.querySelector('[data-omo-stats-value-feedback]');
            var formData = new FormData(form);
            var usesSharedPendingState = typeof window.omoBeginPendingAction === 'function';
            if (usesSharedPendingState && !window.omoBeginPendingAction(form)) {
                return;
            }
            if (!usesSharedPendingState && submitButton) {
                submitButton.disabled = true;
            }
            if (feedback) {
                feedback.textContent = '';
                feedback.className = 'omo-stats-feedback';
            }
            postFormData(formData).then(function () {
                var detail = form.closest('[data-omo-stats-detail]');
                listNeedsRefresh = true;
                return openDrawerWithUrl(detail ? detail.getAttribute('data-detail-url') : '');
            }).catch(function (error) {
                var message = error.message || texts.loadError;
                if (typeof window.omoNotify === 'function') {
                    window.omoNotify(message, 'error');
                } else if (feedback) {
                    feedback.textContent = message;
                    feedback.className = 'omo-stats-feedback is-error';
                }
            }).finally(function () {
                if (usesSharedPendingState && typeof window.omoEndPendingAction === 'function') {
                    window.omoEndPendingAction(form);
                } else if (submitButton) {
                    submitButton.disabled = false;
                }
            });
        });
    }

    window.omoStatsAfterIndicatorSave = function () {
        var editor = drawerBody ? drawerBody.querySelector('[data-omo-stats-editor]') : null;
        var indicatorId = Number(editor ? (editor.getAttribute('data-indicator-id') || 0) : 0);
        if (indicatorId > 0) {
            listNeedsRefresh = true;
            openDrawerWithUrl(buildDetailUrl(indicatorId));
            return;
        }
        listNeedsRefresh = false;
        closeDrawer({force: true});
        refreshRoot(currentUrl);
    };
    window.omoStatsRefreshCurrentView = function () {
        return refreshRoot(currentUrl);
    };

    if (!root.__omoStatsRouteHandler) {
        root.__omoStatsRouteHandler = function (routeEvent) {
            if (!document.body.contains(root)) {
                return;
            }
            var detail = routeEvent && routeEvent.detail ? routeEvent.detail : {};
            var indicatorId = Number(detail.indicatorId || 0);
            var groupId = Number(detail.groupId || 0);
            if (groupId > 0) {
                openDrawerWithUrl(buildGroupDetailUrl(groupId));
            } else if (indicatorId > 0) {
                openDrawerWithUrl(buildDetailUrl(indicatorId));
            } else {
                closeDrawer({force: true});
            }
        };
        window.addEventListener('omo-stats-route-change', root.__omoStatsRouteHandler);
    }

    initializeViewFilter();

    if (Number.isInteger(initialGroupId) && initialGroupId > 0) {
        window.setTimeout(function () {
            openDrawerWithUrl(buildGroupDetailUrl(initialGroupId));
        }, 40);
    } else if (Number.isInteger(initialIndicatorId) && initialIndicatorId > 0) {
        window.setTimeout(function () {
            openDrawerWithUrl(buildDetailUrl(initialIndicatorId));
        }, 40);
    }
})();
};
