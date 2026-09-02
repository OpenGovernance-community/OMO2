(function (window, document) {
    'use strict';

    var root = document.getElementById('omo-activities-root');
    if (!root || root.dataset.activitiesReady === '1') {
        return;
    }
    root.dataset.activitiesReady = '1';

    var drawer = root.querySelector('[data-activity-drawer]');
    var body = root.querySelector('[data-activity-drawer-body]');
    var drawerController = drawer && typeof window.omoCreateSubdrawerController === 'function'
        ? window.omoCreateSubdrawerController({drawer: drawer})
        : null;
    var requestToken = 0;
    var rootNeedsRefresh = false;
    var filterPanelOpen = false;
    var pendingFilters = null;
    var currentScope = normalizeScope(root.getAttribute('data-activity-scope'));
    var currentState = 'all';
    var currentSearch = '';
    var currentUrl = root.getAttribute('data-activity-current-url') || '';
    var baseUrl = root.getAttribute('data-activity-base-url') || currentUrl;
    var savedViewsStorageKey = 'omo.activities.saved-views.v1';
    var sessionViewsStorageKey = 'omo.activities.session-views.v1';
    var searchStorageKey = 'omo.activities.quick-search.v1';
    var texts = {
        loading: 'Chargement de l’activité...',
        loadingError: 'Impossible de charger cette activité.',
        actionError: 'Action impossible.'
    };

    try {
        texts = Object.assign(texts, JSON.parse(root.getAttribute('data-activity-texts') || '{}'));
    } catch (error) {
        // Keep the local fallbacks.
    }

    function resolveUrl(url) {
        return typeof window.omoResolveAppUrl === 'function' ? window.omoResolveAppUrl(url) : url;
    }

    function normalizeScope(value) {
        value = String(value || '').trim().toLowerCase();
        return value === 'children' || value === 'descendants' ? value : 'contextual';
    }

    function normalizeState(value) {
        value = String(value || '').trim().toLowerCase();
        if (value === 'overdue') {
            value = 'missed';
        }
        return ['all', 'attention', 'missed', 'checked', 'upcoming'].indexOf(value) !== -1 ? value : 'all';
    }

    function normalizeSearch(value) {
        return String(value || '').toLocaleLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
    }

    function getPreferencesContextKey() {
        return String(root.getAttribute('data-activity-oid') || '0')
            + ':' + String(root.getAttribute('data-activity-cid') || '0');
    }

    function readStoredValue(storage, storageKey) {
        try {
            var values = JSON.parse(storage.getItem(storageKey) || '{}');
            return values && typeof values === 'object' ? values[getPreferencesContextKey()] || null : null;
        } catch (error) {
            return null;
        }
    }

    function writeStoredValue(storage, storageKey, value) {
        try {
            var values = JSON.parse(storage.getItem(storageKey) || '{}');
            if (!values || typeof values !== 'object') {
                values = {};
            }
            values[getPreferencesContextKey()] = value;
            storage.setItem(storageKey, JSON.stringify(values));
        } catch (error) {
            // Storage can be disabled without blocking the application.
        }
    }

    function clearTemporaryFilters() {
        clearStoredValue(window.sessionStorage, sessionViewsStorageKey);
    }

    function clearStoredValue(storage, storageKey) {
        try {
            var values = JSON.parse(storage.getItem(storageKey) || '{}');
            if (!values || typeof values !== 'object') {
                return;
            }
            delete values[getPreferencesContextKey()];
            storage.setItem(storageKey, JSON.stringify(values));
        } catch (error) {
            // Storage can be disabled without blocking the application.
        }
    }

    function setRootLoading(loading) {
        root.classList.toggle('is-loading', Boolean(loading));
        root.querySelectorAll('[data-activity-filter-toggle], [data-activity-filter-apply], [data-activity-filter-save], [data-activity-scope-option], [data-activity-state-option]').forEach(function (button) {
            button.disabled = Boolean(loading);
        });
    }

    function refreshRoot(url) {
        var targetUrl = url || currentUrl;
        if (!targetUrl) {
            return Promise.resolve(null);
        }
        if (typeof window.omoReplaceFetchedPanelRoot !== 'function') {
            window.location.href = resolveUrl(targetUrl);
            return Promise.resolve(null);
        }
        return window.omoReplaceFetchedPanelRoot({
            rootSelector: '#omo-activities-root',
            currentRoot: root,
            url: resolveUrl(targetUrl),
            setLoadingState: setRootLoading,
            beforeReplace: function () {
                document.removeEventListener('pointerdown', handleFilterOutsidePointerDown, true);
            }
        });
    }

    function buildScopeUrl(scope) {
        var normalized = normalizeScope(scope);
        return baseUrl + (normalized !== 'contextual'
            ? (baseUrl.indexOf('?') === -1 ? '?' : '&') + 'activity_scope=' + encodeURIComponent(normalized)
            : '');
    }

    function stateMatches(itemState) {
        if (currentState === 'all') {
            return true;
        }
        if (currentState === 'attention') {
            return itemState === 'due' || itemState === 'missed';
        }
        if (currentState === 'checked') {
            return itemState === 'checked' || itemState === 'late';
        }
        return itemState === currentState;
    }

    function applyListFilters() {
        var query = normalizeSearch(currentSearch);
        var visibleCount = 0;

        root.querySelectorAll('[data-activity-search-item]').forEach(function (item) {
            var itemState = String(item.getAttribute('data-activity-state') || '');
            var matchesSearch = query === '' || normalizeSearch(item.textContent || '').indexOf(query) !== -1;
            var matches = matchesSearch && stateMatches(itemState);
            item.hidden = !matches;
            if (matches) {
                visibleCount++;
            }
        });

        root.querySelectorAll('[data-activity-group]').forEach(function (group) {
            var groupVisibleCount = Array.prototype.filter.call(group.querySelectorAll('[data-activity-search-item]'), function (item) {
                return !item.hidden;
            }).length;
            group.hidden = groupVisibleCount === 0;
            var count = group.querySelector('[data-activity-group-count]');
            if (count) {
                count.textContent = String(groupVisibleCount);
            }
        });

        var headerCount = root.querySelector('[data-activity-header-count]');
        if (headerCount) {
            headerCount.textContent = String(visibleCount);
        }
        var defaultEmpty = root.querySelector('[data-activity-default-empty]');
        if (defaultEmpty) {
            defaultEmpty.hidden = query !== '' || currentState !== 'all';
        }
        var searchEmpty = root.querySelector('[data-activity-search-empty]');
        if (searchEmpty) {
            searchEmpty.hidden = visibleCount > 0 || (query === '' && currentState === 'all');
        }
    }

    function syncFilterChoices() {
        if (!pendingFilters) {
            return;
        }
        pendingFilters.scope = normalizeScope(pendingFilters.scope);
        pendingFilters.state = normalizeState(pendingFilters.state);
        if (!root.querySelector('[data-activity-scope-option="' + pendingFilters.scope + '"]')) {
            pendingFilters.scope = currentScope;
        }
        root.querySelectorAll('[data-activity-scope-option]').forEach(function (button) {
            var active = normalizeScope(button.getAttribute('data-activity-scope-option')) === pendingFilters.scope;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
        root.querySelectorAll('[data-activity-state-option]').forEach(function (button) {
            var active = normalizeState(button.getAttribute('data-activity-state-option')) === pendingFilters.state;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    }

    function syncFilterChips() {
        var scopeChip = root.querySelector('[data-activity-scope-chip]');
        var scopeButton = root.querySelector('[data-activity-scope-option="' + currentScope + '"]');
        var stateChip = root.querySelector('[data-activity-state-chip]');
        var stateButton = root.querySelector('[data-activity-state-option="' + currentState + '"]');
        if (scopeChip && scopeButton) {
            scopeChip.textContent = scopeButton.textContent.trim();
        }
        if (stateChip && stateButton) {
            stateChip.textContent = stateButton.textContent.trim();
        }
    }

    function handleFilterOutsidePointerDown(event) {
        var control = root.querySelector('[data-activity-filter-control]');
        if (!control || !control.contains(event.target)) {
            closeFilterPanel(true, false);
        }
    }

    function openFilterPanel() {
        var panel = root.querySelector('[data-activity-filter-panel]');
        if (!panel || filterPanelOpen) {
            return;
        }
        pendingFilters = {scope: currentScope, state: currentState};
        syncFilterChoices();
        panel.hidden = false;
        filterPanelOpen = true;
        root.querySelectorAll('[data-activity-filter-toggle]').forEach(function (button) {
            button.setAttribute('aria-expanded', 'true');
        });
        document.addEventListener('pointerdown', handleFilterOutsidePointerDown, true);
    }

    function closeFilterPanel(applyChanges, saveView) {
        var panel = root.querySelector('[data-activity-filter-panel]');
        if (!filterPanelOpen) {
            return;
        }
        filterPanelOpen = false;
        if (panel) {
            panel.hidden = true;
        }
        root.querySelectorAll('[data-activity-filter-toggle]').forEach(function (button) {
            button.setAttribute('aria-expanded', 'false');
        });
        document.removeEventListener('pointerdown', handleFilterOutsidePointerDown, true);

        if (!applyChanges || !pendingFilters) {
            pendingFilters = null;
            return;
        }
        var nextScope = normalizeScope(pendingFilters.scope);
        var nextState = normalizeState(pendingFilters.state);
        pendingFilters = null;
        if (saveView) {
            writeStoredValue(window.localStorage, savedViewsStorageKey, {scope: nextScope, state: nextState});
            clearTemporaryFilters();
        } else {
            writeStoredValue(window.sessionStorage, sessionViewsStorageKey, {scope: nextScope, state: nextState});
        }
        currentState = nextState;
        if (nextScope !== currentScope) {
            currentScope = nextScope;
            refreshRoot(buildScopeUrl(nextScope));
            return;
        }
        syncFilterChips();
        applyListFilters();
    }

    function setDrawerMessage(message, isError) {
        if (!body) {
            return;
        }
        if (drawerController) {
            drawerController.resetHeader();
        }
        body.innerHTML = '<div class="generic-section omo-activity-feedback' + (isError ? ' is-error' : '') + '"></div>';
        body.firstElementChild.textContent = String(message || '');
    }

    function openDrawer(url) {
        if (!url || !drawer || !body) {
            return Promise.resolve(false);
        }
        var localToken = ++requestToken;
        setDrawerMessage(texts.loading, false);
        drawer.hidden = false;
        window.requestAnimationFrame(function () {
            drawer.classList.add('is-open');
        });
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
            body.innerHTML = html;
            if (drawerController) {
                drawerController.applyContentHeader(body);
            }
            if (typeof window.initGenericComponents === 'function') {
                window.initGenericComponents(body);
            }
            body.querySelectorAll('[data-activity-task-form]').forEach(updateSchedule);
            return true;
        }).catch(function () {
            if (localToken === requestToken) {
                setDrawerMessage(texts.loadingError, true);
            }
            return false;
        });
    }

    function closeDrawer(refreshAfterClose) {
        if (!drawer) {
            return;
        }
        requestToken++;
        drawer.classList.remove('is-open');
        window.setTimeout(function () {
            if (drawer.classList.contains('is-open')) {
                return;
            }
            drawer.hidden = true;
            if (body) {
                body.innerHTML = '';
            }
            if (drawerController) {
                drawerController.resetHeader();
            }
            if (refreshAfterClose || rootNeedsRefresh) {
                rootNeedsRefresh = false;
                refreshRoot(currentUrl);
            }
        }, 180);
    }

    function updateSchedule(form) {
        var frequency = form.querySelector('[data-activity-frequency]');
        var schedule = form.querySelector('[data-activity-schedule]');
        var options;
        var selected;
        if (!frequency || !schedule) {
            return;
        }
        try {
            options = JSON.parse(form.getAttribute('data-activity-schedule-options') || '{}');
        } catch (error) {
            options = {};
        }
        selected = schedule.getAttribute('data-selected-value') || schedule.value;
        schedule.innerHTML = '';
        (options[frequency.value] || []).forEach(function (entry) {
            var option = document.createElement('option');
            option.value = entry.value;
            option.textContent = entry.label;
            schedule.appendChild(option);
        });
        if (Array.prototype.some.call(schedule.options, function (option) { return option.value === selected; })) {
            schedule.value = selected;
        }
        schedule.removeAttribute('data-selected-value');
    }

    function postAction(action, id, element) {
        if (element.getAttribute('data-activity-confirm') && !window.confirm(element.getAttribute('data-activity-confirm'))) {
            return;
        }
        var listCheck = element.hasAttribute('data-activity-list-check');
        var data = new FormData();
        data.append('activity_action', action);
        data.append('id', id);
        data.append('oid', root.getAttribute('data-activity-oid'));
        data.append('cid', root.getAttribute('data-activity-cid'));
        element.disabled = true;
        fetch('/omo/api/activities/action.php', {
            method: 'POST',
            body: data,
            credentials: 'same-origin'
        }).then(function (response) {
            return response.json();
        }).then(function (result) {
            if (!result.status) {
                throw new Error(result.message || texts.actionError);
            }
            if (listCheck || action === 'delete_activity') {
                refreshRoot(currentUrl);
                return;
            }
            rootNeedsRefresh = true;
            if (result.detailUrl) {
                openDrawer(result.detailUrl);
            }
        }).catch(function (error) {
            element.disabled = false;
            window.alert(error && error.message ? error.message : texts.actionError);
        });
    }

    function initializeViewFilter() {
        var temporary = readStoredValue(window.sessionStorage, sessionViewsStorageKey);
        var saved = readStoredValue(window.localStorage, savedViewsStorageKey);
        var serverDefault = typeof window.omoApplicationViewPreferencesGetDefault === 'function'
            ? window.omoApplicationViewPreferencesGetDefault(root)
            : null;
        var personalView = typeof window.omoApplicationViewPreferencesGetPersonal === 'function'
            ? window.omoApplicationViewPreferencesGetPersonal(root)
            : null;
        var preferences = temporary || personalView || serverDefault || saved || {};
        var preferredScope = normalizeScope(preferences.scope || currentScope);
        currentState = normalizeState(preferences.state || 'all');
        currentSearch = String(readStoredValue(window.sessionStorage, searchStorageKey) || '');
        var search = root.querySelector('[data-activity-quick-search]');
        if (search) {
            search.value = currentSearch;
        }
        if (!root.querySelector('[data-activity-scope-option="' + preferredScope + '"]')) {
            preferredScope = currentScope;
        }
        if (preferredScope !== currentScope) {
            currentScope = preferredScope;
            refreshRoot(buildScopeUrl(preferredScope));
            return;
        }
        syncFilterChips();
        applyListFilters();
        root.removeAttribute('data-omo-view-filter-pending');
        root.removeAttribute('aria-busy');
    }

    root.addEventListener('click', function (event) {
        var closeButton = event.target.closest('[data-activity-close]');
        if (closeButton) {
            event.preventDefault();
            closeDrawer(false);
            return;
        }
        var cancelButton = event.target.closest('[data-activity-editor-cancel]');
        if (cancelButton && !cancelButton.hasAttribute('data-activity-open-url')) {
            event.preventDefault();
            closeDrawer(false);
            return;
        }
        var actionButton = event.target.closest('[data-activity-post-action]');
        if (actionButton) {
            event.preventDefault();
            event.stopPropagation();
            postAction(actionButton.getAttribute('data-activity-post-action'), actionButton.getAttribute('data-activity-id'), actionButton);
            return;
        }
        var openButton = event.target.closest('[data-activity-open-url]');
        if (openButton) {
            event.preventDefault();
            openDrawer(openButton.getAttribute('data-activity-open-url'));
            return;
        }
        var filterToggle = event.target.closest('[data-activity-filter-toggle]');
        if (filterToggle) {
            event.preventDefault();
            if (filterPanelOpen) {
                closeFilterPanel(true, false);
            } else {
                openFilterPanel();
            }
            return;
        }
        var scopeOption = event.target.closest('[data-activity-scope-option]');
        if (scopeOption && pendingFilters) {
            pendingFilters.scope = normalizeScope(scopeOption.getAttribute('data-activity-scope-option'));
            syncFilterChoices();
            return;
        }
        var stateOption = event.target.closest('[data-activity-state-option]');
        if (stateOption && pendingFilters) {
            pendingFilters.state = normalizeState(stateOption.getAttribute('data-activity-state-option'));
            syncFilterChoices();
            return;
        }
        if (event.target.closest('[data-activity-filter-apply]')) {
            event.preventDefault();
            closeFilterPanel(true, false);
            return;
        }
        if (event.target.closest('[data-activity-filter-save]')) {
            event.preventDefault();
            closeFilterPanel(true, true);
            return;
        }
        if (event.target.closest('[data-activity-filter-restore]')) {
            event.preventDefault();
            closeFilterPanel(false, false);
            clearStoredValue(window.localStorage, savedViewsStorageKey);
            clearTemporaryFilters();
            var serverDefault = typeof window.omoApplicationViewPreferencesGetDefault === 'function'
                ? window.omoApplicationViewPreferencesGetDefault(root)
                : null;
            var nextScope = normalizeScope((serverDefault && serverDefault.scope) || currentScope);
            currentState = normalizeState((serverDefault && serverDefault.state) || 'all');
            if (!root.querySelector('[data-activity-scope-option="' + nextScope + '"]')) {
                nextScope = currentScope;
            }
            if (nextScope !== currentScope) {
                currentScope = nextScope;
                refreshRoot(buildScopeUrl(nextScope));
                return;
            }
            syncFilterChips();
            applyListFilters();
        }
    });

    root.addEventListener('change', function (event) {
        if (event.target.matches('[data-activity-frequency]')) {
            updateSchedule(event.target.closest('[data-activity-task-form]'));
        }
    });

    root.addEventListener('input', function (event) {
        if (!event.target.matches('[data-activity-quick-search]')) {
            return;
        }
        currentSearch = event.target.value || '';
        writeStoredValue(window.sessionStorage, searchStorageKey, currentSearch);
        applyListFilters();
    });

    root.addEventListener('submit', function (event) {
        var form = event.target.closest('[data-activity-form]');
        if (!form) {
            return;
        }
        event.preventDefault();
        if (!form.reportValidity()) {
            return;
        }
        var feedback = form.querySelector('[data-activity-feedback]');
        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            credentials: 'same-origin'
        }).then(function (response) {
            return response.json();
        }).then(function (result) {
            if (feedback) {
                feedback.textContent = result.message || '';
                feedback.classList.toggle('is-error', !result.status);
            }
            if (result.status && result.detailUrl) {
                rootNeedsRefresh = true;
                openDrawer(result.detailUrl);
            }
        }).catch(function () {
            if (feedback) {
                feedback.textContent = texts.actionError;
                feedback.classList.add('is-error');
            }
        });
    });

    root.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && filterPanelOpen) {
            closeFilterPanel(false, false);
            return;
        }
        var row = event.target.closest('[data-activity-open-url]');
        if (row && !event.target.closest('button, a, input, select, textarea') && (event.key === 'Enter' || event.key === ' ')) {
            event.preventDefault();
            openDrawer(row.getAttribute('data-activity-open-url'));
        }
    });

    initializeViewFilter();
}(window, document));
