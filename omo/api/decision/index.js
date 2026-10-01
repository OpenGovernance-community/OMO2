(() => {
const root = typeof window.omoFindApplicationRoot === 'function'
    ? window.omoFindApplicationRoot('omo-decisions-root')
    : document.getElementById('omo-decisions-root');
if (!root) {
    return;
}

if (typeof window.omoDecisionIndexTeardown === 'function') {
    try {
        window.omoDecisionIndexTeardown();
    } catch (error) {
        if (window.console && typeof window.console.warn === 'function') {
            window.console.warn('[OMO][Decisions][panel] previous teardown failed', error);
        }
    }
}

const omoDecisionIndexGlobalCleanupCallbacks = [];
function omoDecisionRegisterGlobalListener(target, eventName, listener, options) {
    if (!target || typeof target.addEventListener !== 'function' || typeof listener !== 'function') {
        return;
    }

    target.addEventListener(eventName, listener, options);
    omoDecisionIndexGlobalCleanupCallbacks.push(function () {
        target.removeEventListener(eventName, listener, options);
    });
}

function omoDecisionParseIndexPayload(node) {
    if (!node) {
        return null;
    }

    try {
        const parsed = JSON.parse(node.textContent || '{}');
        return parsed && typeof parsed === 'object' ? parsed : null;
    } catch (error) {
        return null;
    }
}

const payloadNode = root.querySelector('[data-omo-decisions-payload]');
const payload = omoDecisionParseIndexPayload(payloadNode);
if (!payload) return;
const omoDecisionsSavedViewsStorageKey = 'omo.decisions.saved-views.v2';
const omoDecisionsLegacySavedViewsStorageKey = 'omo.decisions.saved-views.v1';
const omoDecisionsSessionViewsStorageKey = 'omo.decisions.session-views.v1';
const omoDecisionsSearchStorageKey = 'omo.decisions.quick-search.v1';
const elements = {
    newButton: root.querySelector('[data-omo-decisions-new]'),
    count: root.querySelector('[data-omo-decisions-count]'),
    displayControls: root.querySelector('[data-omo-decisions-display-controls]'),
    statusScroll: root.querySelector('[data-omo-decisions-status-scroll]'),
    statusTabs: root.querySelector('[data-omo-decisions-status-tabs]'),
    statusScrollPrev: root.querySelector('[data-omo-decisions-status-scroll-prev]'),
    statusScrollNext: root.querySelector('[data-omo-decisions-status-scroll-next]'),
    filtersToggle: root.querySelector('[data-omo-decisions-filters-toggle]'),
    filtersPanel: root.querySelector('[data-omo-decisions-filters-panel]'),
    search: root.querySelector('[data-omo-decisions-search]'),
    type: root.querySelector('[data-omo-decisions-type]'),
    method: root.querySelector('[data-omo-decisions-method]'),
    typeChoices: root.querySelector('[data-omo-decisions-type-choices]'),
    methodChoices: root.querySelector('[data-omo-decisions-method-choices]'),
    reset: root.querySelector('[data-omo-decisions-reset]'),
    filterControl: root.querySelector('[data-omo-decisions-filter-control]'),
    viewFilterPanel: root.querySelector('[data-omo-decisions-filter-panel]'),
    state: root.querySelector('[data-omo-decisions-state]'),
    list: root.querySelector('[data-omo-decisions-list]'),
    editorDrawer: root.querySelector('[data-omo-decision-editor-drawer]'),
    editorTitle: root.querySelector('[data-omo-decision-editor-title]'),
    editorDescription: root.querySelector('[data-omo-decision-editor-description]'),
    editorBody: root.querySelector('[data-omo-decision-editor-body]')
};
const decisionDrawerController = elements.editorDrawer && typeof window.omoCreateSubdrawerController === 'function'
    ? window.omoCreateSubdrawerController({ drawer: elements.editorDrawer })
    : null;
if (decisionDrawerController) {
    elements.editorDrawer.__omoSubdrawerController = decisionDrawerController;
    window.omoDecisionDrawer = decisionDrawerController;
}
const useLocalDrawerNavigation = typeof window.omoIsPvApplicationTabContext === 'function'
    && window.omoIsPvApplicationTabContext(root);
const collator = typeof Intl !== 'undefined' && typeof Intl.Collator === 'function'
    ? new Intl.Collator('fr', { sensitivity: 'base', numeric: true })
    : null;
const savedPreferences = omoDecisionsReadViewPreferences();
const state = {
    status: savedPreferences.status,
    search: omoDecisionsReadSearch(),
    type: savedPreferences.type,
    method: savedPreferences.method,
    filtersExpanded: false,
    sort: savedPreferences.sort,
    density: savedPreferences.density
};
let omoDecisionIndexRefreshToken = 0;
let pendingViewFilters = null;
let decisionFilterPanelOpen = false;

function omoDecisionDebugLog(stage, details) {
    if (!window.console || typeof window.console.log !== 'function') {
        return;
    }

    if (details && typeof details === 'object') {
        console.log('[OMO][Decisions][panel]', stage, details);
        return;
    }

    console.log('[OMO][Decisions][panel]', stage, details);
}

function omoDecisionDebugError(stage, error, details) {
    if (!window.console || typeof window.console.error !== 'function') {
        return;
    }

    if (details && typeof details === 'object') {
        console.error('[OMO][Decisions][panel]', stage, error, details);
        return;
    }

    console.error('[OMO][Decisions][panel]', stage, error);
}

omoDecisionDebugLog('bootstrap:start', {
    oid: Number(root.getAttribute('data-omo-decision-oid') || 0),
    cid: Number(root.getAttribute('data-omo-decision-cid') || 0),
    scope: String(root.getAttribute('data-omo-decision-scope') || 'contextual'),
    payloadItemCount: Array.isArray(payload.items) ? payload.items.length : null,
    refreshUrl: String(payload.refreshUrl || '')
});

function omoDecisionsNormalizeSortPreference(value) {
    return String(value || '').trim().toLowerCase() === 'alpha'
        ? 'alpha'
        : 'time';
}

function omoDecisionsNormalizeDensityPreference(value) {
    return String(value || '').trim().toLowerCase() === 'compact'
        ? 'compact'
        : 'detail';
}

function omoDecisionsNormalizeScopePreference(value) {
    const normalized = String(value || '').trim().toLowerCase();
    return normalized === 'children' || normalized === 'descendants' ? normalized : 'contextual';
}

function omoDecisionsGetPreferencesContextKey() {
    const regularKey = String(root.getAttribute('data-omo-decision-oid') || '0')
        + ':' + String(root.getAttribute('data-omo-decision-cid') || '0');
    return typeof window.omoApplicationViewPreferencesGetStorageContextKey === 'function'
        ? window.omoApplicationViewPreferencesGetStorageContextKey(root, regularKey)
        : regularKey;
}

function omoDecisionsGetSavedViewsStore() {
    try {
        const rawValue = window.localStorage.getItem(omoDecisionsSavedViewsStorageKey);
        const savedViews = rawValue ? JSON.parse(rawValue) : null;
        if (savedViews && typeof savedViews === 'object' && savedViews.contexts && typeof savedViews.contexts === 'object') {
            return {
                defaultView: savedViews.defaultView && typeof savedViews.defaultView === 'object'
                    ? savedViews.defaultView
                    : null,
                contexts: savedViews.contexts
            };
        }

        const legacyValue = window.localStorage.getItem(omoDecisionsLegacySavedViewsStorageKey);
        const legacyViews = legacyValue ? JSON.parse(legacyValue) : null;
        return {
            defaultView: null,
            contexts: legacyViews && typeof legacyViews === 'object' ? legacyViews : {}
        };
    } catch (error) {
        return {defaultView: null, contexts: {}};
    }
}

function omoDecisionsSaveViewsStore(store) {
    try {
        window.localStorage.setItem(omoDecisionsSavedViewsStorageKey, JSON.stringify({
            defaultView: store.defaultView && typeof store.defaultView === 'object'
                ? store.defaultView
                : null,
            contexts: store.contexts && typeof store.contexts === 'object' ? store.contexts : {}
        }));
    } catch (error) {
    }
}

function omoDecisionsGetStoredViewPreferences() {
    const preferences = omoDecisionsGetSavedViewsStore().contexts[omoDecisionsGetPreferencesContextKey()];
    return preferences && typeof preferences === 'object' ? preferences : null;
}

function omoDecisionsGetDefaultViewPreferences() {
    return omoDecisionsGetSavedViewsStore().defaultView;
}

function omoDecisionsStoreViewPreferences(preferences) {
    const store = omoDecisionsGetSavedViewsStore();
    store.contexts[omoDecisionsGetPreferencesContextKey()] = omoDecisionsNormalizeViewPreferences(preferences);
    omoDecisionsSaveViewsStore(store);
}

function omoDecisionsStoreDefaultViewPreferences(preferences) {
    const store = omoDecisionsGetSavedViewsStore();
    store.defaultView = omoDecisionsNormalizeViewPreferences(preferences);
    omoDecisionsSaveViewsStore(store);
}

function omoDecisionsClearStoredViewPreferences() {
    const store = omoDecisionsGetSavedViewsStore();
    delete store.contexts[omoDecisionsGetPreferencesContextKey()];
    omoDecisionsSaveViewsStore(store);
}

function omoDecisionsReadStoredValue(storage, storageKey) {
    try {
        const values = JSON.parse(storage.getItem(storageKey) || '{}');
        return values && typeof values === 'object' ? values[omoDecisionsGetPreferencesContextKey()] || null : null;
    } catch (error) {
        return null;
    }
}

function omoDecisionsWriteStoredValue(storage, storageKey, value) {
    try {
        const values = JSON.parse(storage.getItem(storageKey) || '{}');
        const map = values && typeof values === 'object' ? values : {};
        map[omoDecisionsGetPreferencesContextKey()] = value;
        storage.setItem(storageKey, JSON.stringify(map));
    } catch (error) {
    }
}

function omoDecisionsNormalizeViewPreferences(preferences) {
    const values = preferences && typeof preferences === 'object' ? preferences : {};
    return {
        scope: omoDecisionsNormalizeScopePreference(values.scope),
        status: String(values.status || 'active'),
        type: String(values.type || 'all'),
        method: String(values.method || 'all'),
        sort: omoDecisionsNormalizeSortPreference(values.sort),
        density: omoDecisionsNormalizeDensityPreference(values.density)
    };
}

function omoDecisionsReadViewPreferences() {
    const temporary = omoDecisionsReadStoredValue(window.sessionStorage, omoDecisionsSessionViewsStorageKey);
    const canUseLegacyPersonal = typeof window.omoApplicationViewPreferencesCanUseLegacyPersonal === 'function'
        && window.omoApplicationViewPreferencesCanUseLegacyPersonal(root);
    const saved = canUseLegacyPersonal ? omoDecisionsGetStoredViewPreferences() : null;
    const defaultView = canUseLegacyPersonal ? omoDecisionsGetDefaultViewPreferences() : null;
    const serverDefault = typeof window.omoApplicationViewPreferencesGetDefault === 'function'
        ? window.omoApplicationViewPreferencesGetDefault(root)
        : null;
    const personalView = typeof window.omoApplicationViewPreferencesGetPersonal === 'function'
        ? window.omoApplicationViewPreferencesGetPersonal(root)
        : null;
    return omoDecisionsNormalizeViewPreferences(temporary || personalView || serverDefault || saved || defaultView);
}

function omoDecisionsReadSearch() {
    const search = omoDecisionsReadStoredValue(window.sessionStorage, omoDecisionsSearchStorageKey);
    return typeof search === 'string' ? search : '';
}

function omoDecisionsWriteSearch(search) {
    omoDecisionsWriteStoredValue(window.sessionStorage, omoDecisionsSearchStorageKey, String(search || ''));
}

function omoDecisionsClearTemporaryViewPreferences() {
    try {
        const values = JSON.parse(window.sessionStorage.getItem(omoDecisionsSessionViewsStorageKey) || '{}');
        if (!values || typeof values !== 'object') {
            return;
        }
        delete values[omoDecisionsGetPreferencesContextKey()];
        window.sessionStorage.setItem(omoDecisionsSessionViewsStorageKey, JSON.stringify(values));
    } catch (error) {
    }
}

function omoDecisionsClearAllTemporaryViewPreferences() {
    try {
        window.sessionStorage.removeItem(omoDecisionsSessionViewsStorageKey);
    } catch (error) {
    }
}

function omoDecisionsWriteViewPreferences(storage, storageKey, preferences) {
    omoDecisionsWriteStoredValue(
        storage,
        storageKey,
        omoDecisionsNormalizeViewPreferences(preferences)
    );
}

function omoDecisionsGetCurrentViewFilters() {
    return {
        scope: omoDecisionGetCurrentScope(),
        status: state.status,
        type: state.type,
        method: state.method,
        sort: state.sort,
        density: state.density
    };
}

function omoDecisionsWritePreferences(preferences) {
    omoDecisionsWriteViewPreferences(window.sessionStorage, omoDecisionsSessionViewsStorageKey, preferences);

    window.dispatchEvent(new CustomEvent('omo-decisions-preferences-change', {
        detail: omoDecisionsNormalizeViewPreferences(preferences)
    }));
}

function omoDecisionGetCurrentScope() {
    const normalizedScope = String(root.getAttribute('data-omo-decision-scope') || 'contextual').trim().toLowerCase();
    if (normalizedScope === 'global') {
        return 'descendants';
    }
    return normalizedScope === 'children' || normalizedScope === 'descendants' ? normalizedScope : 'contextual';
}

function omoDecisionsFindOptionLabel(select, value) {
    if (!select) {
        return '';
    }
    const option = Array.prototype.find.call(select.options || [], function (candidate) {
        return String(candidate.value || '') === String(value || '');
    });
    return option ? String(option.textContent || '').trim() : '';
}

function omoDecisionsFindStatusLabel(value) {
    const filter = (Array.isArray(payload.statusFilters) ? payload.statusFilters : []).find(function (item) {
        return String(item && item.key ? item.key : '') === String(value || '');
    });
    return filter ? String(filter.label || '').trim() : '';
}

function omoDecisionsRenderChoiceButtons(container, options, attribute, selectedValue) {
    if (!container) {
        return;
    }
    container.replaceChildren();
    (Array.isArray(options) ? options : []).forEach(function (option) {
        const value = String(option && option.value !== undefined ? option.value : '');
        const button = document.createElement('button');
        const active = value === String(selectedValue || 'all');
        button.type = 'button';
        button.className = 'omo-segmented__button' + (active ? ' is-active' : '');
        button.setAttribute(attribute, value);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
        button.textContent = String(option && option.label !== undefined ? option.label : value);
        container.appendChild(button);
    });
}

function omoDecisionsSyncFilterChips() {
    const currentScope = omoDecisionGetCurrentScope();
    const entries = [
        {chip: '[data-omo-decisions-scope-chip]', label: function () {
            const button = root.querySelector('[data-omo-decision-scope-toggle="' + currentScope + '"]');
            return button ? String(button.textContent || '').trim() : '';
        }},
        {chip: '[data-omo-decisions-status-chip]', label: function () { return omoDecisionsFindStatusLabel(state.status); }},
        {chip: '[data-omo-decisions-type-chip]', label: function () { return omoDecisionsFindOptionLabel(elements.type, state.type); }},
        {chip: '[data-omo-decisions-method-chip]', label: function () { return omoDecisionsFindOptionLabel(elements.method, state.method); }},
        {chip: '[data-omo-decisions-sort-chip]', label: function () {
            const button = root.querySelector('[data-omo-decisions-sort="' + state.sort + '"]');
            return button ? String(button.textContent || '').trim() : '';
        }},
        {chip: '[data-omo-decisions-density-chip]', label: function () {
            const button = root.querySelector('[data-omo-decisions-density="' + state.density + '"]');
            return button ? String(button.textContent || '').trim() : '';
        }}
    ];
    entries.forEach(function (entry) {
        const chip = root.querySelector(entry.chip);
        const label = entry.label();
        if (chip && label !== '') {
            chip.textContent = label;
        }
    });
}

function omoDecisionsSyncFilterChoices() {
    if (!pendingViewFilters) {
        return;
    }
    const filters = omoDecisionsNormalizeViewPreferences(pendingViewFilters);
    if (!root.querySelector('[data-omo-decision-scope-toggle="' + filters.scope + '"]')) {
        filters.scope = omoDecisionGetCurrentScope();
    }
    filters.status = omoDecisionsFindStatusLabel(filters.status) !== '' ? filters.status : state.status;
    filters.type = omoDecisionsFindOptionLabel(elements.type, filters.type) !== '' ? filters.type : 'all';
    filters.method = omoDecisionsFindOptionLabel(elements.method, filters.method) !== '' ? filters.method : 'all';
    pendingViewFilters = filters;

    root.querySelectorAll('[data-omo-decision-scope-toggle]').forEach(function (button) {
        const active = String(button.getAttribute('data-omo-decision-scope-toggle') || '') === filters.scope;
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
    root.querySelectorAll('[data-status]').forEach(function (button) {
        const active = String(button.getAttribute('data-status') || '') === filters.status;
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
    root.querySelectorAll('[data-omo-decisions-sort]').forEach(function (button) {
        const active = String(button.getAttribute('data-omo-decisions-sort') || '') === filters.sort;
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
    root.querySelectorAll('[data-omo-decisions-density]').forEach(function (button) {
        const active = String(button.getAttribute('data-omo-decisions-density') || '') === filters.density;
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
    root.querySelectorAll('[data-omo-decisions-type-choice]').forEach(function (button) {
        const active = String(button.getAttribute('data-omo-decisions-type-choice') || '') === filters.type;
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
    root.querySelectorAll('[data-omo-decisions-method-choice]').forEach(function (button) {
        const active = String(button.getAttribute('data-omo-decisions-method-choice') || '') === filters.method;
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
    if (elements.type) {
        elements.type.value = filters.type;
    }
    if (elements.method) {
        elements.method.value = filters.method;
    }
}

function omoDecisionsBuildScopeUrl(scope) {
    const query = ['oid=' + encodeURIComponent(String(root.getAttribute('data-omo-decision-oid') || '0'))];
    const holonId = Number(root.getAttribute('data-omo-decision-cid') || 0);
    if (holonId > 0) {
        query.push('cid=' + encodeURIComponent(String(holonId)));
    }
    const normalizedScope = omoDecisionsNormalizeScopePreference(scope);
    if (normalizedScope !== 'contextual') {
        query.push('decision_scope=' + encodeURIComponent(normalizedScope));
    }
    return '/omo/api/decision/index.php?' + query.join('&');
}

function omoDecisionsRefreshScope(scope) {
    if (typeof window.omoReplaceFetchedPanelRoot !== 'function') {
        window.location.href = omoDecisionsBuildScopeUrl(scope);
        return;
    }
    root.classList.add('is-loading');
    window.omoReplaceFetchedPanelRoot({
        rootSelector: '#omo-decisions-root',
        currentRoot: root,
        url: omoDecisionsBuildScopeUrl(scope),
        setLoadingState: function (loading) {
            root.classList.toggle('is-loading', !!loading);
            if (typeof window.omoSetPanelResultsLoadingSkeleton === 'function') {
                window.omoSetPanelResultsLoadingSkeleton(root, loading, {
                    contentSelector: '[data-omo-decisions-list]',
                    reveal: true
                });
            }
        }
    }).catch(function () {
        root.classList.remove('is-loading');
        root.removeAttribute('data-omo-view-filter-pending');
        root.removeAttribute('aria-busy');
    });
}

function omoDecisionsApplyViewFilters(preferences) {
    const next = omoDecisionsNormalizeViewPreferences(preferences);
    if (next.scope !== omoDecisionGetCurrentScope()) {
        omoDecisionsRefreshScope(next.scope);
        return;
    }
    state.status = next.status;
    state.type = restoreSelectValue(elements.type, next.type);
    state.method = restoreSelectValue(elements.method, next.method);
    state.sort = next.sort;
    state.density = next.density;
    renderList();
}

function omoDecisionsCloseFilterMoreMenu() {
    root.querySelectorAll('[data-omo-decisions-filter-more-menu]').forEach(function (menu) {
        const panel = menu.querySelector('[data-omo-decisions-filter-more-panel]');
        const toggle = menu.querySelector('[data-omo-decisions-filter-more-toggle]');
        if (panel) {
            panel.hidden = true;
        }
        menu.classList.remove('is-open');
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
        }
    });
}

function omoDecisionsCloseFilterPanel(applyChanges, saveView) {
    if (!decisionFilterPanelOpen) {
        return;
    }
    decisionFilterPanelOpen = false;
    if (elements.viewFilterPanel) {
        elements.viewFilterPanel.hidden = true;
    }
    root.querySelectorAll('[data-omo-decisions-filter-toggle]').forEach(function (button) {
        button.setAttribute('aria-expanded', 'false');
    });
    document.removeEventListener('pointerdown', omoDecisionsHandleFilterOutsidePointerDown, true);
    omoDecisionsCloseFilterMoreMenu();
    if (!applyChanges || !pendingViewFilters) {
        pendingViewFilters = null;
        return;
    }
    const next = omoDecisionsNormalizeViewPreferences(pendingViewFilters);
    pendingViewFilters = null;
    if (saveView) {
        omoDecisionsStoreViewPreferences(next);
        omoDecisionsClearTemporaryViewPreferences();
    } else {
        omoDecisionsWritePreferences(next);
    }
    omoDecisionsApplyViewFilters(next);
}

function omoDecisionsApplyFilterMoreAction(action) {
    if (!decisionFilterPanelOpen || !pendingViewFilters) {
        return;
    }

    const next = omoDecisionsNormalizeViewPreferences(pendingViewFilters);
    omoDecisionsCloseFilterPanel(false, false);

    if (action === 'set-default') {
        omoDecisionsClearStoredViewPreferences();
        omoDecisionsClearTemporaryViewPreferences();
        omoDecisionsStoreDefaultViewPreferences(next);
        omoDecisionsApplyViewFilters(next);
        return;
    }

    if (action === 'apply-everywhere') {
        const store = omoDecisionsGetSavedViewsStore();
        store.defaultView = omoDecisionsNormalizeViewPreferences(next);
        store.contexts = {};
        omoDecisionsSaveViewsStore(store);
        omoDecisionsClearAllTemporaryViewPreferences();
        omoDecisionsApplyViewFilters(next);
        return;
    }

    if (action === 'restore-default') {
        omoDecisionsClearStoredViewPreferences();
        omoDecisionsClearTemporaryViewPreferences();
        const store = omoDecisionsGetSavedViewsStore();
        store.defaultView = null;
        omoDecisionsSaveViewsStore(store);
        const serverDefault = typeof window.omoApplicationViewPreferencesGetDefault === 'function'
            ? window.omoApplicationViewPreferencesGetDefault(root)
            : null;
        omoDecisionsApplyViewFilters(serverDefault || {
            scope: 'contextual',
            status: 'active',
            type: 'all',
            method: 'all',
            sort: 'time',
            density: 'detail'
        });
    }
}

function omoDecisionsHandleFilterOutsidePointerDown(event) {
    if (elements.filterControl && elements.filterControl.contains(event.target)) {
        return;
    }
    omoDecisionsCloseFilterPanel(true, false);
}

function omoDecisionsOpenFilterPanel() {
    if (!elements.viewFilterPanel || decisionFilterPanelOpen) {
        return;
    }
    pendingViewFilters = omoDecisionsGetCurrentViewFilters();
    omoDecisionsCloseFilterMoreMenu();
    omoDecisionsSyncFilterChoices();
    elements.viewFilterPanel.hidden = false;
    decisionFilterPanelOpen = true;
    root.querySelectorAll('[data-omo-decisions-filter-toggle]').forEach(function (button) {
        button.setAttribute('aria-expanded', 'true');
    });
    document.addEventListener('pointerdown', omoDecisionsHandleFilterOutsidePointerDown, true);
}

function omoDecisionsInitializeViewFilter() {
    const preferredScope = Number(payload.openDecisionId || 0) > 0
        ? omoDecisionGetCurrentScope()
        : savedPreferences.scope;
    if (preferredScope !== omoDecisionGetCurrentScope()) {
        omoDecisionsRefreshScope(preferredScope);
        return true;
    }
    omoDecisionsSyncFilterChips();
    root.removeAttribute('data-omo-view-filter-pending');
    root.removeAttribute('aria-busy');
    return false;
}

window.omoToggleDecisionsScope = function (button, event) {
    if (event) {
        if (typeof event.preventDefault === 'function') {
            event.preventDefault();
        }
        if (typeof event.stopPropagation === 'function') {
            event.stopPropagation();
        }
        if (typeof event.stopImmediatePropagation === 'function') {
            event.stopImmediatePropagation();
        }
    }

    if (!(button instanceof Element)) {
        return false;
    }

    const panel = button.closest('#omo-decisions-root');
    if (!panel) {
        return false;
    }

    const normalizeDecisionScope = function (scopeValue) {
        const normalizedScope = String(scopeValue || '').trim().toLowerCase();
        if (normalizedScope === 'global') {
            return 'descendants';
        }
        return normalizedScope === 'children' || normalizedScope === 'descendants' ? normalizedScope : 'contextual';
    };
    const currentScope = normalizeDecisionScope(panel.getAttribute('data-omo-decision-scope') || 'contextual');
    const targetScope = normalizeDecisionScope(button.getAttribute('data-omo-decision-scope-toggle') || '');

    if (targetScope === currentScope) {
        return false;
    }

    const scopeSwitch = panel.querySelector('[data-omo-scope-switch]');
    if (scopeSwitch) {
        scopeSwitch.setAttribute('data-omo-scope-switch', targetScope);
        scopeSwitch.style.setProperty(
            '--omo-scope-active-index',
            String(parseInt(button.getAttribute('data-omo-scope-index') || '0', 10) || 0)
        );
    }

    const organizationId = Number(panel.getAttribute('data-omo-decision-oid') || 0);
    let holonId = Number(panel.getAttribute('data-omo-decision-cid') || 0);
    const query = [];

    if (typeof window.omoNormalizeRouteCid === 'function') {
        holonId = Number(window.omoNormalizeRouteCid(holonId) || 0);
    }

    if (organizationId > 0) {
        query.push('oid=' + encodeURIComponent(String(organizationId)));
    }

    if (holonId > 0) {
        query.push('cid=' + encodeURIComponent(String(holonId)));
    }

    query.push('decision_scope=' + encodeURIComponent(targetScope));
    query.push('_=' + String(Date.now()));

    const targetUrl = '/omo/api/decision/index.php' + (query.length > 0 ? '?' + query.join('&') : '');

    panel.classList.add('is-loading');

    if (typeof window.omoReplaceFetchedPanelRoot !== 'function') {
        window.location.href = targetUrl;
        return false;
    }

    window.omoReplaceFetchedPanelRoot({
        rootSelector: '#omo-decisions-root',
        currentRoot: panel,
        url: targetUrl,
        setLoadingState: function (isLoading) {
            panel.classList.toggle('is-loading', !!isLoading);
            if (typeof window.omoSetPanelResultsLoadingSkeleton === 'function') {
                window.omoSetPanelResultsLoadingSkeleton(panel, isLoading, {
                    contentSelector: '[data-omo-decisions-list]',
                    reveal: true
                });
            }
        }
    }).catch(function () {
        panel.classList.remove('is-loading');
    });

    return false;
};

function normalizeText(value) {
    return String(value || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, ' ')
        .trim();
}

function escapeHtml(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function formatCountLabel(count) {
    return String(Number(count) || 0);
}

function restoreSelectValue(select, preferredValue) {
    if (!select) {
        return 'all';
    }

    const normalizedPreferredValue = String(preferredValue || 'all');
    const hasPreferredOption = Array.prototype.some.call(select.options || [], function (option) {
        return String(option.value || '') === normalizedPreferredValue;
    });

    select.value = hasPreferredOption ? normalizedPreferredValue : 'all';
    return String(select.value || 'all');
}

function replacePayload(nextPayload) {
    Object.keys(payload).forEach(function (key) {
        delete payload[key];
    });

    if (nextPayload && typeof nextPayload === 'object') {
        Object.keys(nextPayload).forEach(function (key) {
            payload[key] = nextPayload[key];
        });
    }
}

function applyDecisionIndexPayload(nextPayload) {
    if (!nextPayload || typeof nextPayload !== 'object') {
        return;
    }

    omoDecisionDebugLog('applyPayload:start', {
        nextPayloadItemCount: Array.isArray(nextPayload.items) ? nextPayload.items.length : null,
        nextRefreshUrl: String(nextPayload.refreshUrl || '')
    });

    const preservedState = {
        status: state.status,
        search: state.search,
        type: state.type,
        method: state.method,
        filtersExpanded: state.filtersExpanded,
        sort: state.sort,
        density: state.density
    };

    replacePayload(nextPayload);

    populateSelect(elements.type, payload.typeOptions);
    populateSelect(elements.method, payload.methodOptions);
    omoDecisionsRenderChoiceButtons(elements.typeChoices, payload.typeOptions, 'data-omo-decisions-type-choice', state.type);
    omoDecisionsRenderChoiceButtons(elements.methodChoices, payload.methodOptions, 'data-omo-decisions-method-choice', state.method);

    state.status = preservedState.status;
    state.search = preservedState.search;
    state.type = restoreSelectValue(elements.type, preservedState.type);
    state.method = restoreSelectValue(elements.method, preservedState.method);
    state.filtersExpanded = preservedState.filtersExpanded;
    state.sort = omoDecisionsNormalizeSortPreference(preservedState.sort);
    state.density = omoDecisionsNormalizeDensityPreference(preservedState.density);

    if (elements.search) {
        elements.search.value = state.search;
    }

    if (elements.newButton) {
        const hasNewUrl = String(payload.newUrl || '').trim() !== '';
        elements.newButton.hidden = !hasNewUrl;
        elements.newButton.disabled = !hasNewUrl;
    }

    if (elements.displayControls) {
        elements.displayControls.hidden = !Array.isArray(payload.items) || payload.items.length === 0;
    }

    if (payloadNode) {
        payloadNode.textContent = JSON.stringify(payload);
    }

    renderList();

    omoDecisionDebugLog('applyPayload:end', {
        currentStatus: state.status,
        currentSort: state.sort,
        currentDensity: state.density,
        currentItemCount: Array.isArray(payload.items) ? payload.items.length : null
    });
}

function openDecisionEditor(url, title) {
    const resolvedTitle = title || (payload.text && payload.text.drawerTitle ? payload.text.drawerTitle : 'Prises de décision');
    if (typeof window.commonTopbarOpenDrawer === 'function') {
        window.commonTopbarOpenDrawer(resolvedTitle, url, 'fetch');
        return;
    }

    window.location.href = url;
}

let omoDecisionEditorRequestToken = 0;
function omoDecisionRefreshIndex(options) {
    if (!document.body.contains(root)) {
        return Promise.resolve(false);
    }

    const refreshUrl = String(payload.refreshUrl || '').trim();
    if (refreshUrl === '') {
        return Promise.resolve(false);
    }

    const settings = options && typeof options === 'object' ? options : {};
    const requestToken = ++omoDecisionIndexRefreshToken;
    const resolvedUrl = typeof window.omoResolveAppUrl === 'function'
        ? window.omoResolveAppUrl(refreshUrl)
        : refreshUrl;

    omoDecisionDebugLog('refreshIndex:start', {
        requestToken: requestToken,
        resolvedUrl: resolvedUrl,
        silent: settings.silent === true
    });

    return fetch(resolvedUrl, {
        method: 'GET',
        credentials: 'same-origin',
        cache: 'no-store'
    })
        .then(function (response) {
            if (!response.ok) {
                throw new Error('refresh_failed');
            }

            return response.text();
        })
        .then(function (html) {
            if (requestToken !== omoDecisionIndexRefreshToken || !document.body.contains(root)) {
                return false;
            }

            const parser = new DOMParser();
            const doc = parser.parseFromString(String(html || ''), 'text/html');
            const nextPayload = omoDecisionParseIndexPayload(doc.querySelector('[data-omo-decisions-payload]'));
            if (!nextPayload) {
                throw new Error('payload_missing');
            }

            omoDecisionDebugLog('refreshIndex:success', {
                requestToken: requestToken,
                nextPayloadItemCount: Array.isArray(nextPayload.items) ? nextPayload.items.length : null
            });

            applyDecisionIndexPayload(nextPayload);
            return true;
        })
        .catch(function (error) {
            omoDecisionDebugError('refreshIndex:error', error, {
                requestToken: requestToken,
                resolvedUrl: resolvedUrl,
                silent: settings.silent === true
            });

            if (!settings.silent) {
                showStateContainer(
                    '<div class="omo-decisions__state-message">' + escapeHtml(payload.text && payload.text.error ? payload.text.error : 'Impossible de charger la liste pour le moment.') + '</div>',
                    'default'
                );
            }

            return false;
        });
}

function omoDecisionOpenNestedEditorDrawer() {
    if (!elements.editorDrawer) {
        return;
    }

    elements.editorDrawer.hidden = false;
    requestAnimationFrame(function () {
        elements.editorDrawer.classList.add('is-open');
    });
}

function omoDecisionCloseNestedEditorDrawer(options) {
    if (!elements.editorDrawer) {
        return;
    }

    const settings = options && typeof options === 'object' ? options : {};
    const currentHashState = typeof window.omoParsePopupHashState === 'function'
        ? window.omoParsePopupHashState()
        : null;
    const currentRouteToken = currentHashState && currentHashState.routeToken
        ? String(currentHashState.routeToken)
        : '';

    if (
        settings.force !== true
        && !useLocalDrawerNavigation
        && /^decision-(?:(?:d)?\d+|[vgp]\d+)$/i.test(currentRouteToken)
        && typeof window.omoOpenDrawerHashState === 'function'
    ) {
        window.omoOpenDrawerHashState('decision');
        return;
    }

    const wasOpen = !elements.editorDrawer.hidden || elements.editorDrawer.classList.contains('is-open');
    elements.editorDrawer.classList.remove('is-open');
    window.setTimeout(function () {
        if (!elements.editorDrawer.classList.contains('is-open')) {
            elements.editorDrawer.hidden = true;
        }
    }, 200);

    if (wasOpen && settings.refreshIndex !== false) {
        window.setTimeout(function () {
            omoDecisionRefreshIndex({ silent: true });
        }, 0);
    }
}

function omoDecisionRenderNestedEditorLoading() {
    if (!elements.editorBody) {
        return;
    }

    if (typeof window.getSkeleton === 'function') {
        elements.editorBody.innerHTML = window.getSkeleton('panel');
        return;
    }

    elements.editorBody.innerHTML = '<div class="loading">Chargement...</div>';
}

function omoDecisionRenderNestedEditorError() {
    if (!elements.editorBody) {
        return;
    }

    elements.editorBody.innerHTML = '<div class="omo-empty-state">Impossible de charger ce module.</div>';
}

openDecisionEditor = function (url, title, description) {
    if (!url) {
        return;
    }

    const resolvedTitle = title || (payload.text && payload.text.drawerTitle ? payload.text.drawerTitle : 'Prises de décision');

    if (!elements.editorDrawer || !elements.editorBody) {
        if (typeof window.commonTopbarOpenDrawer === 'function') {
            window.commonTopbarOpenDrawer(resolvedTitle, url, 'fetch');
            return;
        }

        window.location.href = url;
        return;
    }

    if (decisionDrawerController) {
        decisionDrawerController.setHeader({
            title: resolvedTitle,
            description: description || '',
            help: '',
            actions: []
        });
    }

    if (elements.editorTitle) {
        elements.editorTitle.textContent = resolvedTitle;
    }
    if (elements.editorDescription) {
        elements.editorDescription.textContent = description || '';
    }

    omoDecisionRenderNestedEditorLoading();
    omoDecisionOpenNestedEditorDrawer();

    const requestToken = ++omoDecisionEditorRequestToken;
    const resolvedUrl = typeof window.omoResolveAppUrl === 'function'
        ? window.omoResolveAppUrl(url)
        : url;

    $.ajax({
        url: resolvedUrl,
        method: 'GET',
        cache: false,
        success: function (data) {
            if (requestToken !== omoDecisionEditorRequestToken || !elements.editorBody) {
                return;
            }

            elements.editorBody.innerHTML = data;
            window.commonExecuteFragmentScripts(elements.editorBody, {
                isCurrent: function () { return requestToken === omoDecisionEditorRequestToken && elements.editorBody.isConnected; }
            }).catch(function (error) { console.error(error); });

            if (decisionDrawerController) {
                decisionDrawerController.applyContentHeader(elements.editorBody);

                const editorHeaderForm = elements.editorBody.querySelector('[data-omo-decision-editor-header-form]');
                if (editorHeaderForm && editorHeaderForm.id) {
                    const submitLabel = String(editorHeaderForm.getAttribute('data-omo-decision-editor-header-submit-label') || '').trim();
                    decisionDrawerController.setHeader({
                        title: String(editorHeaderForm.getAttribute('data-omo-decision-editor-header-title') || resolvedTitle),
                        description: '',
                        actions: submitLabel === '' ? [] : [{
                            label: submitLabel,
                            type: 'button',
                            className: 'generic-action-button generic-action-button--main',
                            attributes: {
                                form: editorHeaderForm.id,
                                'data-omo-decision-editor-submit': ''
                            },
                            onClick: function () {
                                if (!editorHeaderForm.isConnected) {
                                    return;
                                }

                                if (typeof editorHeaderForm.requestSubmit === 'function') {
                                    editorHeaderForm.requestSubmit();
                                    return;
                                }

                                const fallbackSubmit = document.createElement('button');
                                fallbackSubmit.type = 'submit';
                                fallbackSubmit.hidden = true;
                                editorHeaderForm.appendChild(fallbackSubmit);
                                fallbackSubmit.click();
                                fallbackSubmit.remove();
                            }
                        }]
                    });
                }
            }

            if (typeof window.omoDecisionVoteInit === 'function') {
                window.omoDecisionVoteInit(elements.editorBody);
            }

            if (typeof window.omoDecisionMajorityJudgmentInit === 'function') {
                window.omoDecisionMajorityJudgmentInit(elements.editorBody);
            }

            if (typeof window.omoDecisionConsentInit === 'function') {
                window.omoDecisionConsentInit(elements.editorBody);
            }
        },
        error: function () {
            if (requestToken !== omoDecisionEditorRequestToken) {
                return;
            }

            omoDecisionRenderNestedEditorError();
        }
    });
};

function findDecisionItemById(decisionId) {
    const resolvedDecisionId = Number(decisionId);
    if (!Number.isInteger(resolvedDecisionId) || resolvedDecisionId <= 0 || !Array.isArray(payload.items)) {
        return null;
    }

    for (let index = 0; index < payload.items.length; index += 1) {
        const item = payload.items[index];
        if (Number(item && item.id ? item.id : 0) === resolvedDecisionId) {
            return item;
        }
    }

    return null;
}

function getCurrentDecisionRouteToken() {
    if (useLocalDrawerNavigation) {
        return '';
    }
    if (typeof window.omoParsePopupHashState !== 'function') {
        return '';
    }

    const hashState = window.omoParsePopupHashState();
    return hashState && hashState.routeToken
        ? String(hashState.routeToken)
        : '';
}

function buildDecisionRouteToken(decisionId, modeValue) {
    const resolvedDecisionId = Number(decisionId || 0);
    if (!Number.isInteger(resolvedDecisionId) || resolvedDecisionId <= 0) {
        return null;
    }

    if (typeof window.omoBuildDecisionRouteToken === 'function') {
        return window.omoBuildDecisionRouteToken(resolvedDecisionId, normalizeDecisionOpenMode(modeValue));
    }

    const normalizedMode = normalizeDecisionOpenMode(modeValue);
    if (normalizedMode === 'view') {
        return 'decision-v' + String(resolvedDecisionId);
    }

    if (normalizedMode === 'manage') {
        return 'decision-g' + String(resolvedDecisionId);
    }

    if (normalizedMode === 'participate') {
        return 'decision-p' + String(resolvedDecisionId);
    }

    return 'decision-d' + String(resolvedDecisionId);
}

function normalizeDecisionOpenMode(modeValue) {
    const normalizedMode = String(modeValue || '').trim().toLowerCase();
    return normalizedMode === 'view' || normalizedMode === 'manage' || normalizedMode === 'participate'
        ? normalizedMode
        : 'default';
}

function resolveDecisionAutoOpenAction(item) {
    const actions = Array.isArray(item && item.actions) ? item.actions : [];
    let fallbackAction = null;

    for (let index = 0; index < actions.length; index += 1) {
        const action = actions[index];
        const actionUrl = String(action && action.url ? action.url : '').trim();
        if (actionUrl === '') {
            continue;
        }

        if (!fallbackAction) {
            fallbackAction = action;
        }

        if (String(action && action.variant ? action.variant : '').trim() === 'main') {
            return action;
        }
    }

    return fallbackAction;
}

function resolveDecisionActionForMode(item, modeValue) {
    const normalizedMode = normalizeDecisionOpenMode(modeValue);
    if (normalizedMode === 'default') {
        return resolveDecisionAutoOpenAction(item);
    }

    const actions = Array.isArray(item && item.actions) ? item.actions : [];
    for (let index = 0; index < actions.length; index += 1) {
        const action = actions[index];
        if (String(action && action.mode ? action.mode : '').trim().toLowerCase() !== normalizedMode) {
            continue;
        }

        const actionUrl = String(action && action.url ? action.url : '').trim();
        if (actionUrl !== '') {
            return action;
        }
    }

    return null;
}

function openDecisionItemById(decisionId, options) {
    const item = findDecisionItemById(decisionId);
    if (!item) {
        return false;
    }

    const settings = options && typeof options === 'object'
        ? options
        : {};
    const action = resolveDecisionActionForMode(item, settings.mode);
    const actionUrl = String(action && action.url ? action.url : '').trim();
    if (actionUrl === '') {
        return false;
    }

    openDecisionEditor(
        actionUrl,
        String(settings.title || item.title || (payload.text && payload.text.drawerTitle ? payload.text.drawerTitle : 'Prises de decision')),
        String(settings.description || '')
    );

    return true;
}

function openDecisionFromInteraction(decisionId, targetUrl, title, description, modeValue) {
    const resolvedDecisionId = Number(decisionId || 0);
    const resolvedUrl = String(targetUrl || '').trim();
    const normalizedMode = normalizeDecisionOpenMode(modeValue);
    const routeToken = buildDecisionRouteToken(resolvedDecisionId, normalizedMode);
    const currentRouteToken = getCurrentDecisionRouteToken();

    if (!useLocalDrawerNavigation && routeToken && typeof window.omoOpenDrawerHashState === 'function' && routeToken !== currentRouteToken) {
        window.omoOpenDrawerHashState(routeToken);
        return true;
    }

    if (resolvedDecisionId > 0 && openDecisionItemById(resolvedDecisionId, {
        title: title,
        description: description,
        mode: normalizedMode
    })) {
        return true;
    }

    if (resolvedUrl !== '') {
        openDecisionEditor(resolvedUrl, title, description);
        return true;
    }

    return false;
}

function openInitialDecisionFromPayload() {
    const decisionId = Number(payload.openDecisionId || 0);
    if (!Number.isInteger(decisionId) || decisionId <= 0) {
        return false;
    }

    payload.openDecisionId = 0;
    return openDecisionItemById(decisionId, {
        mode: normalizeDecisionOpenMode(payload.openDecisionMode || 'default')
    });
}

function populateSelect(select, options) {
    if (!select) {
        return;
    }

    select.innerHTML = '';
    (Array.isArray(options) ? options : []).forEach(function (option) {
        const node = document.createElement('option');
        node.value = String(option && option.value !== undefined ? option.value : '');
        node.textContent = String(option && option.label !== undefined ? option.label : node.value);
        select.appendChild(node);
    });
}

function renderStatusTabs() {
    if (!elements.statusTabs) {
        return;
    }

    elements.statusTabs.innerHTML = '';
    const statusFilters = Array.isArray(payload.statusFilters) ? payload.statusFilters : [];
    const lastFilterIndex = Math.max(0, statusFilters.length - 1);

    statusFilters.forEach(function (filter, index) {
        const key = String(filter && filter.key ? filter.key : 'all');
        const count = key === 'all'
            ? (Array.isArray(payload.items) ? payload.items.length : 0)
            : (key === 'active'
                ? Number(payload.statusCounts && payload.statusCounts.active ? payload.statusCounts.active : 0)
                : Number(payload.statusCounts && payload.statusCounts[key] ? payload.statusCounts[key] : 0));

        const shouldAlwaysShow = index === 0 || index === lastFilterIndex;
        if (!shouldAlwaysShow && count <= 0 && state.status !== key) {
            return;
        }

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'omo-decisions__status-tab' + (state.status === key ? ' is-active' : '');
        button.setAttribute('data-status', key);
        button.setAttribute('aria-pressed', state.status === key ? 'true' : 'false');
        button.innerHTML = '<span>' + escapeHtml(filter && filter.label ? filter.label : key) + '</span>'
            + '<span class="omo-decisions__status-count">' + String(count) + '</span>';
        elements.statusTabs.appendChild(button);
    });

    window.requestAnimationFrame(syncStatusTabsOverflow);
}

function compareText(left, right) {
    const normalizedLeft = String(left || '');
    const normalizedRight = String(right || '');

    if (collator) {
        return collator.compare(normalizedLeft, normalizedRight);
    }

    return normalizedLeft.localeCompare(normalizedRight);
}

function sortItemsByTime(items) {
    return items.slice().sort(function (left, right) {
        const activityDiff = Number(right && right.lastActivityTimestamp ? right.lastActivityTimestamp : 0)
            - Number(left && left.lastActivityTimestamp ? left.lastActivityTimestamp : 0);

        if (activityDiff !== 0) {
            return activityDiff;
        }

        return compareText(
            left && left.sortTitle ? left.sortTitle : (left && left.title ? left.title : ''),
            right && right.sortTitle ? right.sortTitle : (right && right.title ? right.title : '')
        );
    });
}

function sortItemsByAlpha(items) {
    return items.slice().sort(function (left, right) {
        const titleDiff = compareText(
            left && left.sortTitle ? left.sortTitle : (left && left.title ? left.title : ''),
            right && right.sortTitle ? right.sortTitle : (right && right.title ? right.title : '')
        );

        if (titleDiff !== 0) {
            return titleDiff;
        }

        return Number(right && right.lastActivityTimestamp ? right.lastActivityTimestamp : 0)
            - Number(left && left.lastActivityTimestamp ? left.lastActivityTimestamp : 0);
    });
}

function getSortedItems(items, sortMode) {
    return sortMode === 'alpha'
        ? sortItemsByAlpha(items)
        : sortItemsByTime(items);
}

function getOrderedGroups() {
    return Array.isArray(payload.groups) ? payload.groups : [];
}

function syncDisplayControlsVisibility() {
    if (!elements.displayControls) {
        return;
    }

    elements.displayControls.hidden = !Array.isArray(payload.items) || payload.items.length === 0;
}

function syncDisplayButtons() {
    root.querySelectorAll('[data-omo-decisions-sort]').forEach(function (button) {
        const isActive = String(button.getAttribute('data-omo-decisions-sort') || '') === state.sort;
        button.classList.toggle('is-active', isActive);
        button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
    });

    root.querySelectorAll('[data-omo-decisions-density]').forEach(function (button) {
        const isActive = String(button.getAttribute('data-omo-decisions-density') || '') === state.density;
        button.classList.toggle('is-active', isActive);
        button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
    });
    omoDecisionsSyncFilterChips();
}

function resetListPresentation() {
    if (!elements.list) {
        return;
    }

    elements.list.className = 'omo-decisions__list';
    elements.list.removeAttribute('data-generic-file-list');
}

function syncStatusTabsOverflow() {
    if (!elements.statusTabs || !elements.statusScrollPrev || !elements.statusScrollNext) {
        return;
    }

    const maxScrollLeft = Math.max(0, elements.statusTabs.scrollWidth - elements.statusTabs.clientWidth);
    const hasOverflow = maxScrollLeft > 4;
    const scrollLeft = Math.max(0, elements.statusTabs.scrollLeft);

    elements.statusScrollPrev.hidden = !hasOverflow || scrollLeft <= 4;
    elements.statusScrollNext.hidden = !hasOverflow || scrollLeft >= (maxScrollLeft - 4);
}

function scrollStatusTabs(direction) {
    if (!elements.statusTabs) {
        return;
    }

    const offset = Math.max(120, Math.floor(elements.statusTabs.clientWidth * 0.7));
    elements.statusTabs.scrollBy({
        left: direction < 0 ? -offset : offset,
        behavior: 'smooth'
    });
}

function syncAdvancedFiltersVisibility() {
    const isVisible = !!state.filtersExpanded;

    setVisible(elements.filtersPanel, isVisible);

    if (!elements.filtersToggle) {
        return;
    }

    elements.filtersToggle.textContent = isVisible
        ? String(payload.text && payload.text.filtersToggleHide ? payload.text.filtersToggleHide : 'Masquer les filtres')
        : String(payload.text && payload.text.filtersToggleShow ? payload.text.filtersToggleShow : 'Afficher les filtres');
    elements.filtersToggle.setAttribute('aria-expanded', isVisible ? 'true' : 'false');
    elements.filtersToggle.classList.toggle('is-active', state.search !== '' || state.type !== 'all' || state.method !== 'all');
}

function getFilteredItems() {
    const items = Array.isArray(payload.items) ? payload.items : [];
    const searchNeedle = normalizeText(state.search);

    return items.filter(function (item) {
        if (state.status === 'active') {
            if (!item.isActiveDefault) {
                return false;
            }
        } else if (state.status !== 'all' && String(item.status || '') !== state.status) {
            return false;
        }

        if (state.type !== 'all' && String(item.decisionType || '') !== state.type) {
            return false;
        }

        if (state.method !== 'all' && String(item.evaluationMethod || '') !== state.method) {
            return false;
        }

        if (searchNeedle !== '') {
            const haystack = String(item.searchIndex || '');
            if (haystack.indexOf(searchNeedle) === -1) {
                return false;
            }
        }

        return true;
    });
}

function setVisible(node, isVisible) {
    if (!node) {
        return;
    }

    node.hidden = !isVisible;
}

function resetStateContainer() {
    if (!elements.state) {
        return;
    }

    elements.state.className = 'omo-decisions__state generic-section';
    elements.state.innerHTML = '';
    setVisible(elements.state, false);
}

function showStateContainer(html, variant) {
    if (!elements.state) {
        return;
    }

    elements.state.className = 'omo-decisions__state ' + (variant === 'empty'
        ? 'generic-hero-panel accent'
        : 'generic-section');
    elements.state.innerHTML = String(html || '');
    setVisible(elements.state, true);
}

function buildMetaItem(label, value) {
    const normalizedLabel = String(label || '').trim();
    const normalizedValue = String(value || '').trim();
    if (normalizedLabel === '' || normalizedValue === '') {
        return '';
    }

    return '<span class="omo-decisions-card__meta-item"><strong>'
        + escapeHtml(normalizedLabel)
        + '</strong><span>'
        + escapeHtml(normalizedValue)
        + '</span></span>';
}

function buildCompactAvatar(owner) {
    const resolvedOwner = owner && typeof owner === 'object' ? owner : {};
    const ownerName = String(resolvedOwner.displayName || '').trim();
    const ownerInitials = String(resolvedOwner.initials || 'P').trim() || 'P';
    const ownerPhotoUrl = String(resolvedOwner.photoUrl || '').trim();

    if (ownerPhotoUrl !== '') {
        return '<span class="omo-decisions__compact-avatar"><img src="' + escapeHtml(ownerPhotoUrl) + '" alt="' + escapeHtml(ownerName !== '' ? ownerName : ownerInitials) + '" class="omo-decisions__compact-avatar-photo" width="34" height="34" decoding="async"></span>';
    }

    return '<span class="omo-decisions__compact-avatar"><span class="omo-decisions__compact-avatar-placeholder">' + escapeHtml(ownerInitials) + '</span></span>';
}

function buildDecisionVisibilityIconHtml(item, className) {
    const resolvedClassName = String(className || '').trim();
    const iconUrl = String(item && item.visibilityIconUrl ? item.visibilityIconUrl : '').trim();
    const iconLabel = String(item && item.visibilityLabel ? item.visibilityLabel : '').trim();

    if (iconUrl === '') {
        return '';
    }

    return '<span class="' + escapeHtml(resolvedClassName !== '' ? resolvedClassName : 'omo-decisions__visibility-icon') + '" role="img"'
        + (iconLabel !== '' ? ' aria-label="' + escapeHtml(iconLabel) + '" title="' + escapeHtml(iconLabel) + '"' : '')
        + '><img src="' + escapeHtml(iconUrl) + '" alt=""></span>';
}

function buildCompactMetaLine(item) {
    const metaParts = [];
    const ownerName = String(item && item.owner && item.owner.displayName ? item.owner.displayName : '').trim();
    const typeLabel = String(item && item.decisionTypeLabel ? item.decisionTypeLabel : '').trim();
    const methodLabel = String(item && item.evaluationMethodLabel ? item.evaluationMethodLabel : '').trim();
    const badges = Array.isArray(item && item.badges) ? item.badges : [];

    if (ownerName !== '') {
        metaParts.push(ownerName);
    }
    if (typeLabel !== '') {
        metaParts.push(typeLabel);
    }
    if (methodLabel !== '') {
        metaParts.push(methodLabel);
    }

    badges.forEach(function (badge) {
        const normalizedBadge = String(badge || '').trim();
        if (normalizedBadge !== '') {
            metaParts.push(normalizedBadge);
        }
    });

    return metaParts.map(function (part, index) {
        const separator = index > 0 ? '<span class="omo-decisions__compact-meta-separator">|</span>' : '';
        return separator + '<span>' + escapeHtml(part) + '</span>';
    }).join('');
}

function buildCompactListHeader() {
    const header = document.createElement('div');
    header.className = 'generic-file-list__header';
    header.innerHTML = ''
        + '<div class="generic-file-list__header-cell">' + escapeHtml(payload.text && payload.text.compactHeaderName ? payload.text.compactHeaderName : 'Decision') + '</div>'
        + '<div class="generic-file-list__header-cell">' + escapeHtml(payload.text && payload.text.compactHeaderStatus ? payload.text.compactHeaderStatus : 'Statut') + '</div>'
        + '<div class="generic-file-list__header-cell">' + escapeHtml(payload.text && payload.text.compactHeaderScope ? payload.text.compactHeaderScope : 'Structure') + '</div>'
        + '<div class="generic-file-list__header-cell">' + escapeHtml(payload.text && payload.text.compactHeaderActivity ? payload.text.compactHeaderActivity : 'Activite') + '</div>';
    return header;
}

function isDecisionMenuActionUsable(action) {
    if (!action || typeof action !== 'object') {
        return false;
    }

    const behavior = String(action.behavior || '').trim();
    if (behavior === 'mutation') {
        return String(action.requestUrl || '').trim() !== '';
    }

    if (behavior === 'export') {
        return String(action.exportUrl || '').trim() !== '';
    }

    if (behavior === 'window' || behavior === 'modal') {
        return String(action.url || '').trim() !== '';
    }

    return String(action.url || '').trim() !== '';
}

function getDecisionDetailMenuActions(item) {
    return (Array.isArray(item && item.menuActions) ? item.menuActions : []).filter(isDecisionMenuActionUsable);
}

function getDecisionCompactMenuActions(item) {
    const primaryActions = Array.isArray(item && item.actions) ? item.actions : [];
    return primaryActions.concat(getDecisionDetailMenuActions(item)).filter(isDecisionMenuActionUsable);
}

function buildDecisionActionMenuToggle(item, actions, className) {
    const resolvedActions = Array.isArray(actions) ? actions.filter(isDecisionMenuActionUsable) : [];
    if (resolvedActions.length === 0) {
        return null;
    }

    const toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = (String(className || '').trim() || 'omo-decisions__compact-menu-toggle') + ' generic-menu-toggle';
    toggle.setAttribute('data-omo-decision-compact-menu-toggle', '1');
    toggle.setAttribute('data-omo-decision-compact-menu-actions', JSON.stringify(resolvedActions));
    toggle.setAttribute('data-omo-decision-compact-menu-title', String(item && item.title ? item.title : ''));
    toggle.setAttribute('data-omo-decision-compact-menu-description', String(item && item.description ? item.description : ''));
    toggle.setAttribute('data-omo-decision-id', String(Number(item && item.id ? item.id : 0) || 0));
    toggle.setAttribute('aria-haspopup', 'menu');
    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('aria-label', String(payload.text && payload.text.moreActionAriaLabel ? payload.text.moreActionAriaLabel : 'Plus d actions pour cette prise de decision'));
    toggle.textContent = String(payload.text && payload.text.moreActionLabel ? payload.text.moreActionLabel : '...');
    return toggle;
}

function buildCompactActionMenu(item) {
    const actions = getDecisionCompactMenuActions(item);

    if (actions.length === 0) {
        return null;
    }

    const menu = document.createElement('div');
    menu.className = 'omo-decisions__compact-menu generic-menu generic-file-list__menu';
    menu.setAttribute('data-omo-decision-compact-menu', '1');

    const toggle = buildDecisionActionMenuToggle(item, actions, 'omo-decisions__compact-menu-toggle generic-file-list__menu-toggle');
    if (!toggle) {
        return null;
    }

    menu.appendChild(toggle);

    return menu;
}

function buildDetailedActionMenuButton(item) {
    const actions = getDecisionDetailMenuActions(item);
    if (actions.length === 0) {
        return null;
    }

    const menu = document.createElement('div');
    menu.className = 'omo-decisions__compact-menu generic-menu';
    menu.setAttribute('data-omo-decision-compact-menu', '1');

    const toggle = buildDecisionActionMenuToggle(
        item,
        actions,
        'omo-decisions-card__action omo-decisions-card__menu-toggle'
    );
    if (!toggle) {
        return null;
    }

    menu.appendChild(toggle);
    return menu;
}

function appendDetailedActionMenuButton(card, item) {
    if (!(card instanceof Element)) {
        return;
    }

    const detailMenuButton = buildDetailedActionMenuButton(item);
    const actionsContainer = card.querySelector('.omo-decisions-card__actions');
    if (detailMenuButton && actionsContainer) {
        actionsContainer.appendChild(detailMenuButton);
    }
}

function renderCompactRow(item) {
    const article = document.createElement('article');
    article.className = 'omo-decisions__item-shell generic-file-list__item-shell';
    article.setAttribute('data-omo-decision-id', String(Number(item && item.id ? item.id : 0) || 0));

    const primaryAction = resolveDecisionAutoOpenAction(item);
    const primaryActionUrl = String(primaryAction && primaryAction.url ? primaryAction.url : '').trim();
    const compactMenu = buildCompactActionMenu(item);
    const row = document.createElement(primaryActionUrl !== '' ? 'button' : 'div');
    const activityLabel = String(item && item.lastActivityLabel ? item.lastActivityLabel : (item && item.deadlineLabel ? item.deadlineLabel : '')).trim();
    const scopeLabel = String(item && item.holonLabel ? item.holonLabel : '').trim();

    if (compactMenu) {
        article.classList.add('generic-file-list__item-shell--with-menu');
    }

    row.className = 'omo-decisions__compact-row generic-file-list__row';
    if (primaryActionUrl !== '') {
        row.type = 'button';
        row.setAttribute('data-open-url', primaryActionUrl);
        row.setAttribute('data-open-title', String(item && item.title ? item.title : ''));
        row.setAttribute('data-open-mode', String(primaryAction && primaryAction.mode ? primaryAction.mode : 'default'));
    }

    row.innerHTML = ''
        + '<div class="generic-file-list__cell generic-file-list__cell--name" data-label="' + escapeHtml(payload.text && payload.text.compactHeaderName ? payload.text.compactHeaderName : 'Decision') + '">'
            + '<div class="omo-decisions__compact-name-main generic-file-list__name-main">'
                + buildCompactAvatar(item && item.owner ? item.owner : {})
                + '<div class="omo-decisions__compact-title-block generic-file-list__title-block">'
                    + '<div class="generic-file-list__title-row">'
                        + '<span class="omo-decisions__compact-title-line"><strong class="generic-file-list__title">' + escapeHtml(item && item.title ? item.title : '') + '</strong>' + buildDecisionVisibilityIconHtml(item, 'omo-decisions__visibility-icon omo-decisions__visibility-icon--compact') + '</span>'
                    + '</div>'
                    + '<div class="omo-decisions__compact-meta generic-file-list__meta-line">' + buildCompactMetaLine(item) + '</div>'
                + '</div>'
            + '</div>'
        + '</div>'
        + '<div class="generic-file-list__cell" data-label="' + escapeHtml(payload.text && payload.text.compactHeaderStatus ? payload.text.compactHeaderStatus : 'Statut') + '">'
            + '<span class="omo-decisions__compact-status">' + escapeHtml(item && item.statusLabel ? item.statusLabel : '') + '</span>'
        + '</div>'
        + '<div class="generic-file-list__cell" data-label="' + escapeHtml(payload.text && payload.text.compactHeaderScope ? payload.text.compactHeaderScope : 'Structure') + '">'
            + '<span class="omo-decisions__compact-scope">' + escapeHtml(scopeLabel) + '</span>'
        + '</div>'
        + '<div class="generic-file-list__cell generic-file-list__cell--date" data-label="' + escapeHtml(payload.text && payload.text.compactHeaderActivity ? payload.text.compactHeaderActivity : 'Activite') + '">'
            + '<span class="omo-decisions__compact-activity">' + escapeHtml(activityLabel) + '</span>'
        + '</div>';

    article.appendChild(row);
    if (compactMenu) {
        article.appendChild(compactMenu);
    }
    return article;
}

function renderDetailedList(items, groupMode) {
    const sortedItems = getSortedItems(items, groupMode === 'alpha' ? 'alpha' : 'time');
    const fragment = document.createDocumentFragment();

    if (groupMode === 'alpha') {
        const list = document.createElement('div');
        list.className = 'omo-decisions__card-list';
        sortedItems.forEach(function (item) {
            list.appendChild(renderCard(item));
        });
        fragment.appendChild(list);
        return fragment;
    }

    const itemsByGroupKey = new Map();
    sortedItems.forEach(function (item) {
        const groupKey = String(item && item.activityGroupKey ? item.activityGroupKey : 'too_far');
        if (!itemsByGroupKey.has(groupKey)) {
            itemsByGroupKey.set(groupKey, []);
        }
        itemsByGroupKey.get(groupKey).push(item);
    });

    getOrderedGroups().forEach(function (group) {
        const groupKey = String(group && group.key ? group.key : '');
        const groupItems = itemsByGroupKey.get(groupKey) || [];
        if (groupItems.length === 0) {
            return;
        }

        const section = document.createElement('section');
        section.className = 'omo-decisions__group omo-panel-group';

        const title = document.createElement('h3');
        title.className = 'generic-card-title generic-card-title--small generic-file-list__group-title';
        title.textContent = String(group && group.label ? group.label : '');

        const list = document.createElement('div');
        list.className = 'omo-decisions__card-list';
        groupItems.forEach(function (entry) {
            list.appendChild(renderCard(entry));
        });

        section.appendChild(title);
        section.appendChild(list);
        fragment.appendChild(section);
    });

    return fragment;
}

function renderCompactList(items, groupMode) {
    const sortedItems = getSortedItems(items, groupMode === 'alpha' ? 'alpha' : 'time');
    const fragment = document.createDocumentFragment();

    elements.list.classList.add('generic-file-list', 'generic-file-list--structured');
    elements.list.setAttribute('data-generic-file-list', '1');

    if (groupMode === 'alpha') {
        const section = document.createElement('section');
        section.className = 'omo-decisions__group generic-file-list__group';

        const table = document.createElement('div');
        table.className = 'generic-file-list__table';
        table.appendChild(buildCompactListHeader());
        sortedItems.forEach(function (item) {
            table.appendChild(renderCompactRow(item));
        });

        section.appendChild(table);
        fragment.appendChild(section);
        return fragment;
    }

    elements.list.classList.add('generic-file-list--stacked-sticky');

    const itemsByGroupKey = new Map();
    sortedItems.forEach(function (item) {
        const groupKey = String(item && item.activityGroupKey ? item.activityGroupKey : 'too_far');
        if (!itemsByGroupKey.has(groupKey)) {
            itemsByGroupKey.set(groupKey, []);
        }
        itemsByGroupKey.get(groupKey).push(item);
    });

    getOrderedGroups().forEach(function (group) {
        const groupKey = String(group && group.key ? group.key : '');
        const groupItems = itemsByGroupKey.get(groupKey) || [];
        if (groupItems.length === 0) {
            return;
        }

        const section = document.createElement('section');
        section.className = 'omo-decisions__group generic-file-list__group';

        const title = document.createElement('h3');
        title.className = 'generic-card-title generic-card-title--small generic-file-list__group-title';
        title.textContent = String(group && group.label ? group.label : '');

        const table = document.createElement('div');
        table.className = 'generic-file-list__table';
        table.appendChild(buildCompactListHeader());
        groupItems.forEach(function (entry) {
            table.appendChild(renderCompactRow(entry));
        });

        section.appendChild(title);
        section.appendChild(table);
        fragment.appendChild(section);
    });

    return fragment;
}

function renderCard(item) {
    const sharedCards = window.commonChoiceDecisionCards;
    if (sharedCards && typeof sharedCards.renderCard === 'function') {
        const owner = item && item.owner ? item.owner : {};
        const ownerName = String(owner.displayName || '').trim();
        const metaItems = [
            {
                label: payload.text.typeLabel || 'Type',
                value: String(item.decisionTypeLabel || '')
            },
            {
                label: payload.text.methodLabel || 'Methode',
                value: String(item.evaluationMethodLabel || '')
            },
            {
                label: String(item.scopeTypeLabel || payload.text.scopeLabel || 'Structure'),
                value: String(item.holonLabel || '')
            }
        ];
        const stats = [
            {
                value: String(Number(item.proposalCount || 0)),
                label: payload.text.proposalsLabel || 'Propositions'
            },
            {
                value: String(Number(item.participantCount || 0)),
                label: payload.text.participantsLabel || 'Participants'
            },
            {
                value: String(Number(item.responseCount || 0)),
                label: payload.text.responsesLabel || 'Reponses'
            }
        ];
        const dateItems = [];

        if (ownerName !== '') {
            metaItems.unshift({
                label: payload.text.ownerLabel || 'En charge',
                value: ownerName
            });
        }

        if (item.deadlineLabel) {
            dateItems.push({
                label: payload.text.deadlineLabel || 'Echeance',
                value: item.deadlineLabel
            });
        }

        if (item.lastActivityLabel) {
            dateItems.push({
                label: payload.text.lastActivityLabel || 'Derniere activite',
                value: item.lastActivityLabel
            });
        }

        const card = sharedCards.renderCard({
            title: String(item.title || ''),
            description: String(item.description || '').trim(),
            statusLabel: String(item.statusLabel || ''),
            titleIcon: {
                url: String(item.visibilityIconUrl || ''),
                label: String(item.visibilityLabel || '')
            },
            owner: owner,
            badges: Array.isArray(item.badges) ? item.badges : [],
            actions: Array.isArray(item.actions) ? item.actions : [],
            metaItems: metaItems,
            stats: stats,
            dateItems: dateItems
        }, {
            fallbackTitle: payload.text.drawerTitle || 'Prises de decision',
            openUrlAttribute: 'data-open-url',
            openTitleAttribute: 'data-open-title'
        });

        card.setAttribute('data-omo-decision-id', String(Number(item && item.id ? item.id : 0) || 0));
        card.querySelectorAll('[data-open-url]').forEach(function (button, buttonIndex) {
            const action = Array.isArray(item && item.actions) ? item.actions[buttonIndex] : null;
            button.setAttribute('data-omo-decision-id', String(Number(item && item.id ? item.id : 0) || 0));
            button.setAttribute('data-open-mode', String(action && action.mode ? action.mode : 'default'));
        });
        appendDetailedActionMenuButton(card, item);
        return card;
    }

    const article = document.createElement('article');
    article.className = 'omo-decisions-card generic-section generic-accordion generic-accordion--card generic-accordion--collapsible is-collapsed';
    article.setAttribute('data-generic-accordion', '1');
    article.setAttribute('data-omo-decision-id', String(Number(item && item.id ? item.id : 0) || 0));

    const description = String(item.description || '').trim();
    const badges = Array.isArray(item.badges) ? item.badges : [];
    const actions = Array.isArray(item.actions) ? item.actions : [];
    const owner = item && item.owner ? item.owner : {};
    const ownerName = String(owner.displayName || '').trim();
    const ownerInitials = String(owner.initials || 'P').trim() || 'P';
    const ownerPhotoUrl = String(owner.photoUrl || '').trim();
    const metaBits = [
        buildMetaItem(payload.text.typeLabel || 'Type', String(item.decisionTypeLabel || '')),
        buildMetaItem(payload.text.methodLabel || 'Méthode', String(item.evaluationMethodLabel || '')),
        buildMetaItem(String(item.scopeTypeLabel || payload.text.scopeLabel || 'Structure'), String(item.holonLabel || ''))
    ];

    if (ownerName !== '') {
        metaBits.unshift(buildMetaItem(payload.text.ownerLabel || 'En charge', ownerName));
    }

    let badgesHtml = '';
    if (badges.length > 0) {
        badgesHtml = '<div class="omo-decisions-card__badges">';
        badges.forEach(function (badge) {
            badgesHtml += '<span class="omo-decisions-card__badge">' + escapeHtml(badge || '') + '</span>';
        });
        badgesHtml += '</div>';
    }

    let ownerAvatarHtml = '';
    if (ownerPhotoUrl !== '') {
        ownerAvatarHtml = '<img src="' + escapeHtml(ownerPhotoUrl) + '" alt="' + escapeHtml(ownerName !== '' ? ownerName : ownerInitials) + '" class="omo-decisions-card__owner-photo" width="42" height="42" decoding="async">';
    } else {
        ownerAvatarHtml = '<span class="omo-decisions-card__owner-placeholder">' + escapeHtml(ownerInitials) + '</span>';
    }

    let actionsHtml = '<div class="omo-decisions-card__actions">';
    actions.forEach(function (action) {
        const actionVariant = String(action && action.variant ? action.variant : 'secondary');
        actionsHtml += '<button type="button" class="generic-action-button generic-action-button--' + escapeHtml(actionVariant) + ' omo-decisions-card__action" data-omo-decision-id="' + escapeHtml(String(Number(item && item.id ? item.id : 0) || 0)) + '" data-open-url="' + escapeHtml(action && action.url ? action.url : '') + '" data-open-mode="' + escapeHtml(String(action && action.mode ? action.mode : 'default')) + '">' + escapeHtml(action && action.label ? action.label : '') + '</button>';
    });
    actionsHtml += '</div>';

    article.innerHTML = '<div class="omo-decisions-card__header generic-accordion__header">'
        + '<button type="button" class="omo-decisions-card__summary" data-generic-accordion-toggle aria-expanded="false">'
            + '<span class="omo-decisions-card__owner-avatar">' + ownerAvatarHtml + '</span>'
            + '<span class="omo-decisions-card__summary-copy">'
                + '<span class="omo-decisions-card__summary-top">'
                    + '<span class="omo-decisions-card__title-line"><span class="omo-decisions-card__title generic-title generic-title--item">' + escapeHtml(item.title || '') + '</span>' + buildDecisionVisibilityIconHtml(item, 'omo-decisions-card__title-icon') + '</span>'
                    + '<span class="omo-decisions-card__status">' + escapeHtml(item.statusLabel || '') + '</span>'
                + '</span>'
                + '<span class="omo-decisions-card__summary-bottom">'
                    + (ownerName !== '' ? '<span class="omo-decisions-card__owner-name">' + escapeHtml(ownerName) + '</span>' : '')
                    + badgesHtml
                + '</span>'
            + '</span>'
            + '<span class="generic-accordion__toggle" aria-hidden="true">&#9662;</span>'
        + '</button>'
        + actionsHtml
        + '</div>'
        + '<div class="omo-decisions-card__content generic-accordion__content">'
            + (description !== '' ? '<p class="omo-decisions-card__description generic-description">' + escapeHtml(description) + '</p>' : '')
            + '<div class="omo-decisions-card__meta">' + metaBits.join('') + '</div>'
            + '<div class="omo-decisions-card__stats">'
                + '<div class="omo-decisions-card__stat"><strong>' + String(Number(item.proposalCount || 0)) + '</strong><span>' + escapeHtml(payload.text.proposalsLabel || 'Propositions') + '</span></div>'
                + '<div class="omo-decisions-card__stat"><strong>' + String(Number(item.participantCount || 0)) + '</strong><span>' + escapeHtml(payload.text.participantsLabel || 'Participants') + '</span></div>'
                + '<div class="omo-decisions-card__stat"><strong>' + String(Number(item.responseCount || 0)) + '</strong><span>' + escapeHtml(payload.text.responsesLabel || 'Réponses') + '</span></div>'
            + '</div>'
            + '<div class="omo-decisions-card__dates">'
                + (item.deadlineLabel ? '<div class="omo-decisions-card__date"><strong>' + escapeHtml(payload.text.deadlineLabel || 'Échéance') + '</strong><span>' + escapeHtml(item.deadlineLabel) + '</span></div>' : '')
                + (item.lastActivityLabel ? '<div class="omo-decisions-card__date"><strong>' + escapeHtml(payload.text.lastActivityLabel || 'Dernière activité') + '</strong><span>' + escapeHtml(item.lastActivityLabel) + '</span></div>' : '')
            + '</div>'
        + '</div>';

    appendDetailedActionMenuButton(article, item);

    return article;
}

function buildStateCardHtml(title, text, buttonLabel, buttonUrl) {
    let html = '<div class="omo-decisions__state-content">';
    if (title) {
        html += '<h3 class="omo-decisions__empty-title generic-title generic-title--card">' + escapeHtml(title) + '</h3>';
    }
    if (text) {
        html += '<p class="omo-decisions__empty-text generic-description generic-description--relaxed">' + escapeHtml(text) + '</p>';
    }
    if (buttonLabel && buttonUrl) {
        html += '<button type="button" class="generic-action-button generic-action-button--main" data-open-url="' + escapeHtml(buttonUrl) + '">' + escapeHtml(buttonLabel) + '</button>';
    }
    html += '</div>';
    return html;
}

function renderNoResults() {
    return '<h3 class="omo-decisions__no-results-title generic-title generic-title--card">' + escapeHtml(payload.text.noResultsTitle || '') + '</h3>'
        + '<p class="omo-decisions__no-results-text generic-description generic-description--relaxed">' + escapeHtml(payload.text.noResultsText || '') + '</p>';
}

function renderList() {
    omoDecisionDebugLog('renderList:start', {
        payloadItemCount: Array.isArray(payload.items) ? payload.items.length : null,
        status: state.status,
        search: state.search,
        type: state.type,
        method: state.method,
        sort: state.sort,
        density: state.density
    });

    syncAdvancedFiltersVisibility();
    resetStateContainer();
    setVisible(elements.list, false);
    root.setAttribute('data-omo-decisions-initialized', '0');

    if (elements.list) {
        elements.list.innerHTML = '';
        resetListPresentation();
    }

    if (!Array.isArray(payload.items)) {
        showStateContainer(
            '<div class="omo-decisions__state-message">' + escapeHtml(payload.text.error || 'Impossible de charger la liste pour le moment.') + '</div>',
            'default'
        );
        return;
    }

    const filteredItems = getFilteredItems();
    if (elements.count) {
        elements.count.textContent = formatCountLabel(filteredItems.length);
    }

    renderStatusTabs();
    syncDisplayControlsVisibility();
    syncDisplayButtons();

    if (payload.items.length === 0) {
        showStateContainer(buildStateCardHtml(
            payload.text.emptyTitle || '',
            payload.text.emptyText || '',
            payload.text.emptyCta || '',
            payload.newUrl || ''
        ), 'empty');
        root.setAttribute('data-omo-decisions-initialized', '1');
        omoDecisionDebugLog('renderList:end-empty', {
            initialized: String(root.getAttribute('data-omo-decisions-initialized') || ''),
            filteredItemCount: 0
        });
        return;
    }

    if (filteredItems.length === 0) {
        showStateContainer(renderNoResults(), 'default');
        root.setAttribute('data-omo-decisions-initialized', '1');
        omoDecisionDebugLog('renderList:end-no-results', {
            initialized: String(root.getAttribute('data-omo-decisions-initialized') || ''),
            filteredItemCount: filteredItems.length
        });
        return;
    }

    if (elements.list) {
        const fragment = state.density === 'compact'
            ? renderCompactList(filteredItems, state.sort)
            : renderDetailedList(filteredItems, state.sort);
        elements.list.appendChild(fragment);
        if (typeof window.initGenericComponents === 'function') {
            window.initGenericComponents(elements.list);
        }
        if (typeof window.syncGenericFileLists === 'function') {
            window.syncGenericFileLists(elements.list);
        }
    }
    setVisible(elements.list, true);
    root.setAttribute('data-omo-decisions-initialized', '1');
    omoDecisionDebugLog('renderList:end', {
        filteredItemCount: filteredItems.length,
        listChildCount: elements.list ? elements.list.childElementCount : null,
        initialized: String(root.getAttribute('data-omo-decisions-initialized') || ''),
        listHidden: elements.list ? elements.list.hidden : null,
        stateHidden: elements.state ? elements.state.hidden : null,
        drawerOpen: Boolean(root.closest('.drawer.open')),
        drawerId: root.closest('.drawer') ? String(root.closest('.drawer').id || '') : ''
    });
}

function resetFilters() {
    state.status = 'active';
    state.search = '';
    state.type = 'all';
    state.method = 'all';
    state.filtersExpanded = false;

    if (elements.search) {
        elements.search.value = '';
    }
    if (elements.type) {
        elements.type.value = 'all';
    }
    if (elements.method) {
        elements.method.value = 'all';
    }
}

const ownerDocument = root.ownerDocument || document;
let floatingCompactMenu = ownerDocument.querySelector('[data-omo-decision-floating-menu="1"]');
if (!floatingCompactMenu) {
    floatingCompactMenu = ownerDocument.createElement('div');
    floatingCompactMenu.className = 'omo-decisions__menu-panel generic-menu-panel generic-menu-panel--floating omo-decisions__menu-panel--floating';
    floatingCompactMenu.setAttribute('data-omo-decision-floating-menu', '1');
    floatingCompactMenu.setAttribute('role', 'menu');
    floatingCompactMenu.hidden = true;
    ownerDocument.body.appendChild(floatingCompactMenu);
}

let activeCompactMenuToggle = null;

function buildCompactMenuItem(action, title, description) {
    const button = ownerDocument.createElement('button');
    const behavior = String(action && action.behavior ? action.behavior : '').trim();
    const requestPayload = action && action.requestPayload && typeof action.requestPayload === 'object'
        ? action.requestPayload
        : {};
    button.type = 'button';
    button.className = 'omo-decisions__menu-item generic-menu-item';
    if (String(action && action.variant ? action.variant : '').trim() === 'danger') {
        button.classList.add('omo-decisions__menu-item--danger', 'generic-menu-item--danger');
    }
    button.setAttribute('data-omo-decision-compact-menu-action', '1');
    button.setAttribute('role', 'menuitem');
    button.setAttribute(
        'data-omo-decision-menu-behavior',
        behavior === 'mutation' || behavior === 'export' || behavior === 'window' || behavior === 'modal' || behavior === 'direct' ? behavior : 'open'
    );
    if (behavior === 'mutation') {
        button.setAttribute('data-omo-decision-menu-request-url', String(action && action.requestUrl ? action.requestUrl : ''));
        button.setAttribute('data-omo-decision-menu-request-payload', JSON.stringify(requestPayload));
        button.setAttribute('data-omo-decision-menu-confirm', String(action && action.confirmMessage ? action.confirmMessage : ''));
    } else if (behavior === 'export') {
        button.setAttribute('data-omo-decision-menu-export-url', String(action && action.exportUrl ? action.exportUrl : ''));
        button.setAttribute('data-omo-decision-menu-export-formats', JSON.stringify(Array.isArray(action && action.exportFormats) ? action.exportFormats : []));
        button.setAttribute('data-open-title', String(action && action.title ? action.title : title || ''));
        button.setAttribute('data-open-description', String(description || ''));
    } else {
        button.setAttribute('data-open-url', String(action && action.url ? action.url : ''));
        button.setAttribute('data-open-title', String(action && action.title ? action.title : title || ''));
        button.setAttribute('data-open-description', String(description || ''));
        button.setAttribute('data-open-mode', String(action && action.mode ? action.mode : 'default'));
    }
    button.textContent = String(action && action.label ? action.label : '');
    return button;
}

function parseCompactMenuActions(toggle) {
    if (!(toggle instanceof Element)) {
        return [];
    }

    try {
        const parsed = JSON.parse(String(toggle.getAttribute('data-omo-decision-compact-menu-actions') || '[]'));
        return Array.isArray(parsed)
            ? parsed.filter(isDecisionMenuActionUsable)
            : [];
    } catch (error) {
        return [];
    }
}

function populateCompactMenu(toggle) {
    const actions = parseCompactMenuActions(toggle);
    const title = String(toggle && toggle.getAttribute('data-omo-decision-compact-menu-title') || '').trim();
    const description = String(toggle && toggle.getAttribute('data-omo-decision-compact-menu-description') || '').trim();
    const fragment = ownerDocument.createDocumentFragment();

    actions.forEach(function (action) {
        fragment.appendChild(buildCompactMenuItem(action, title, description));
    });

    floatingCompactMenu.replaceChildren(fragment);
}

function positionCompactMenu(toggle) {
    if (!(toggle instanceof Element) || !toggle.isConnected) {
        closeCompactMenus();
        return;
    }

    floatingCompactMenu.hidden = false;
    floatingCompactMenu.style.visibility = 'hidden';
    floatingCompactMenu.style.top = '0px';
    floatingCompactMenu.style.left = '0px';

    const toggleRect = toggle.getBoundingClientRect();
    const menuRect = floatingCompactMenu.getBoundingClientRect();
    const viewportPadding = 12;
    const gap = 8;
    let top = toggleRect.bottom + gap;
    let left = toggleRect.right - menuRect.width;

    if (top + menuRect.height > window.innerHeight - viewportPadding) {
        top = Math.max(viewportPadding, toggleRect.top - menuRect.height - gap);
    }

    if (left + menuRect.width > window.innerWidth - viewportPadding) {
        left = Math.max(viewportPadding, window.innerWidth - menuRect.width - viewportPadding);
    }

    if (left < viewportPadding) {
        left = viewportPadding;
    }

    floatingCompactMenu.style.top = String(Math.round(top)) + 'px';
    floatingCompactMenu.style.left = String(Math.round(left)) + 'px';
    floatingCompactMenu.style.visibility = '';
}

function closeCompactMenus() {
    root.querySelectorAll('[data-omo-decision-compact-menu="1"]').forEach(function (menu) {
        menu.classList.remove('is-open');
    });

    root.querySelectorAll('[data-omo-decision-compact-menu-toggle="1"]').forEach(function (toggle) {
        toggle.setAttribute('aria-expanded', 'false');
    });

    activeCompactMenuToggle = null;
    floatingCompactMenu.hidden = true;
    floatingCompactMenu.style.visibility = '';
    floatingCompactMenu.replaceChildren();
}

function openCompactMenu(toggle) {
    const parentMenu = toggle ? toggle.closest('[data-omo-decision-compact-menu="1"]') : null;
    const shouldOpen = !!toggle && (!activeCompactMenuToggle || activeCompactMenuToggle !== toggle || floatingCompactMenu.hidden);

    closeCompactMenus();

    if (!toggle || !parentMenu || !shouldOpen) {
        return;
    }

    populateCompactMenu(toggle);
    if (!floatingCompactMenu.childElementCount) {
        return;
    }

    activeCompactMenuToggle = toggle;
    parentMenu.classList.add('is-open');
    toggle.setAttribute('aria-expanded', 'true');
    positionCompactMenu(toggle);
}

function parseDecisionMenuRequestPayload(button) {
    if (!(button instanceof Element)) {
        return {};
    }

    try {
        const parsed = JSON.parse(String(button.getAttribute('data-omo-decision-menu-request-payload') || '{}'));
        return parsed && typeof parsed === 'object' ? parsed : {};
    } catch (error) {
        return {};
    }
}

function parseDecisionMenuExportFormats(button) {
    if (!(button instanceof Element)) {
        return [];
    }

    try {
        const parsed = JSON.parse(String(button.getAttribute('data-omo-decision-menu-export-formats') || '[]'));
        return Array.isArray(parsed) ? parsed : [];
    } catch (error) {
        return [];
    }
}

function buildDecisionExportModalHtml(title, exportUrl, formats) {
    const safeTitle = String(title || '').trim();
    const safeExportUrl = String(exportUrl || '').trim();
    const safeFormats = Array.isArray(formats) ? formats : [];
    let html = '<div class="generic-section generic-section--stack">';

    if (payload.text && payload.text.exportModalIntro) {
        html += '<p>' + escapeHtml(payload.text.exportModalIntro) + '</p>';
    }

    safeFormats.forEach(function (format) {
        const key = String(format && format.key ? format.key : '').trim();
        const label = String(format && format.label ? format.label : key.toUpperCase()).trim();
        const description = String(format && format.description ? format.description : '').trim();
        const available = Boolean(format && format.available);

        html += '<section class="generic-soft-panel generic-soft-panel--stack">';
        html += '<h3 class="generic-card-title generic-card-title--small">' + escapeHtml(label) + '</h3>';
        if (description !== '') {
            html += '<p>' + escapeHtml(description) + '</p>';
        }

        if (available && safeExportUrl !== '' && key !== '') {
            html += '<button type="button" class="generic-action-button generic-action-button--main"'
                + ' data-omo-decision-export-format-button="1"'
                + ' data-omo-decision-export-url="' + escapeHtml(safeExportUrl) + '"'
                + ' data-omo-decision-export-format="' + escapeHtml(key) + '"'
                + ' data-omo-decision-export-title="' + escapeHtml(safeTitle) + '"'
                + '>'
                + escapeHtml(payload.text && payload.text.exportOpenLabel ? payload.text.exportOpenLabel : 'Telecharger')
                + '</button>';
        } else {
            html += '<button type="button" class="generic-action-button generic-action-button--secondary" disabled>'
                + escapeHtml(payload.text && payload.text.exportComingSoonLabel ? payload.text.exportComingSoonLabel : 'Bientot disponible')
                + '</button>';
        }

        html += '</section>';
    });

    html += '</div>';
    return html;
}

function openDecisionExportModal(button) {
    if (!(button instanceof Element) || typeof window.commonTopbarOpenModal !== 'function') {
        return;
    }

    const exportUrl = String(button.getAttribute('data-omo-decision-menu-export-url') || '').trim();
    if (exportUrl === '') {
        return;
    }

    const title = String(button.getAttribute('data-open-title') || '').trim()
        || (payload.text && payload.text.exportModalTitle ? payload.text.exportModalTitle : 'Exporter ce scrutin');
    const formats = parseDecisionMenuExportFormats(button);

    window.commonTopbarOpenModal(
        payload.text && payload.text.exportModalTitle ? payload.text.exportModalTitle : 'Exporter ce scrutin',
        buildDecisionExportModalHtml(title, exportUrl, formats),
        'html'
    );
}

function startDecisionExportDownload(button) {
    if (!(button instanceof Element)) {
        return;
    }

    const exportUrl = String(button.getAttribute('data-omo-decision-export-url') || '').trim();
    const format = String(button.getAttribute('data-omo-decision-export-format') || '').trim();
    if (exportUrl === '' || format === '') {
        return;
    }

    let resolvedUrl = typeof window.omoResolveAppUrl === 'function'
        ? window.omoResolveAppUrl(exportUrl)
        : exportUrl;

    try {
        const url = new URL(resolvedUrl, window.location.origin);
        url.searchParams.set('format', format);
        resolvedUrl = url.toString();
    } catch (error) {
        resolvedUrl += (resolvedUrl.indexOf('?') === -1 ? '?' : '&') + 'format=' + encodeURIComponent(format);
    }

    if (typeof window.commonTopbarCloseModal === 'function') {
        window.commonTopbarCloseModal();
    }

    window.open(resolvedUrl, '_blank', 'noopener');
}

function submitDecisionMenuAction(button) {
    if (!(button instanceof Element)) {
        return Promise.resolve(false);
    }

    const requestUrl = String(button.getAttribute('data-omo-decision-menu-request-url') || '').trim();
    if (requestUrl === '') {
        return Promise.resolve(false);
    }

    const confirmMessage = String(button.getAttribute('data-omo-decision-menu-confirm') || '').trim();
    if (confirmMessage !== '' && !window.confirm(confirmMessage)) {
        return Promise.resolve(false);
    }

    const formData = new FormData();
    const requestPayload = parseDecisionMenuRequestPayload(button);
    Object.keys(requestPayload).forEach(function (key) {
        const value = requestPayload[key];
        if (value === null || typeof value === 'undefined') {
            return;
        }

        formData.append(key, String(value));
    });

    const resolvedUrl = typeof window.omoResolveAppUrl === 'function'
        ? window.omoResolveAppUrl(requestUrl)
        : requestUrl;

    button.disabled = true;
    closeCompactMenus();

    return fetch(resolvedUrl, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: {
            'Accept': 'application/json',
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
                const message = result.data && result.data.message
                    ? String(result.data.message)
                    : String(payload.text && payload.text.actionErrorUpdate ? payload.text.actionErrorUpdate : 'Impossible de mettre a jour cette prise de decision pour le moment.');
                throw new Error(message);
            }

            return omoDecisionRefreshIndex({ silent: false });
        })
        .catch(function (error) {
            window.omoNotify(error && error.message ? error.message : (payload.text && payload.text.actionErrorUpdate ? payload.text.actionErrorUpdate : 'Impossible de mettre a jour cette prise de decision pour le moment.'), 'error');
            return false;
        })
        .finally(function () {
            button.disabled = false;
        });
}

try {
    omoDecisionDebugLog('bootstrap:beforePopulate', {
        typeOptionCount: Array.isArray(payload.typeOptions) ? payload.typeOptions.length : null,
        methodOptionCount: Array.isArray(payload.methodOptions) ? payload.methodOptions.length : null,
    });

    populateSelect(elements.type, payload.typeOptions);
    populateSelect(elements.method, payload.methodOptions);
    state.type = restoreSelectValue(elements.type, state.type);
    state.method = restoreSelectValue(elements.method, state.method);
    omoDecisionsRenderChoiceButtons(elements.typeChoices, payload.typeOptions, 'data-omo-decisions-type-choice', state.type);
    omoDecisionsRenderChoiceButtons(elements.methodChoices, payload.methodOptions, 'data-omo-decisions-method-choice', state.method);
    if (elements.search) {
        elements.search.value = state.search;
    }
    renderList();
    omoDecisionDebugLog('bootstrap:afterRender', {
        initialized: String(root.getAttribute('data-omo-decisions-initialized') || ''),
        listChildCount: elements.list ? elements.list.childElementCount : null
    });
    if (!omoDecisionsInitializeViewFilter()) {
        window.setTimeout(openInitialDecisionFromPayload, 0);
    }
} catch (error) {
    root.setAttribute('data-omo-decisions-initialized', '0');
    omoDecisionDebugError('bootstrap:error', error, {
        initialized: String(root.getAttribute('data-omo-decisions-initialized') || '')
    });
    showStateContainer(
        '<div class="omo-decisions__state-message">' + escapeHtml(payload.text.error || 'Impossible de charger la liste pour le moment.') + '</div>',
        'default'
    );
}

if (elements.newButton) {
    elements.newButton.addEventListener('click', function () {
        openDecisionEditor(payload.newUrl || '', payload.text && payload.text.drawerTitle ? payload.text.drawerTitle : 'Prises de décision');
    });
}

if (elements.editorDrawer) {
    elements.editorDrawer.querySelectorAll('[data-omo-decision-editor-close]').forEach(function (button) {
        button.addEventListener('click', omoDecisionCloseNestedEditorDrawer);
    });
}

if (elements.filtersToggle) {
    elements.filtersToggle.addEventListener('click', function () {
        state.filtersExpanded = !state.filtersExpanded;
        syncAdvancedFiltersVisibility();
    });
}

if (elements.statusTabs) {
    elements.statusTabs.addEventListener('scroll', syncStatusTabsOverflow, { passive: true });
}

if (elements.statusScrollPrev) {
    elements.statusScrollPrev.addEventListener('click', function () {
        scrollStatusTabs(-1);
    });
}

if (elements.statusScrollNext) {
    elements.statusScrollNext.addEventListener('click', function () {
        scrollStatusTabs(1);
    });
}

omoDecisionRegisterGlobalListener(ownerDocument, 'click', function (event) {
    const toggle = event.target.closest('[data-omo-decision-compact-menu-toggle="1"]');
    if (toggle) {
        return;
    }

    const actionButton = event.target.closest('[data-omo-decision-compact-menu-action="1"]');
    if (actionButton && floatingCompactMenu.contains(actionButton)) {
        event.preventDefault();
        event.stopPropagation();

        const behavior = String(actionButton.getAttribute('data-omo-decision-menu-behavior') || 'open').trim();
        if (behavior === 'mutation') {
            submitDecisionMenuAction(actionButton);
            return;
        }

        if (behavior === 'export') {
            openDecisionExportModal(actionButton);
            closeCompactMenus();
            return;
        }

        if (behavior === 'window') {
            const targetUrl = String(actionButton.getAttribute('data-open-url') || '').trim();
            if (targetUrl !== '') {
                const resolvedUrl = typeof window.omoResolveAppUrl === 'function'
                    ? window.omoResolveAppUrl(targetUrl)
                    : targetUrl;
                window.open(resolvedUrl, '_blank', 'noopener');
            }

            closeCompactMenus();
            return;
        }

        if (behavior === 'modal') {
            const targetUrl = String(actionButton.getAttribute('data-open-url') || '').trim();
            if (targetUrl !== '' && typeof window.commonTopbarOpenModal === 'function') {
                window.commonTopbarOpenModal(
                    String(actionButton.getAttribute('data-open-title') || '').trim()
                        || String(payload.text && payload.text.moveModalTitle ? payload.text.moveModalTitle : 'Déplacer la prise de décision'),
                    targetUrl,
                    'fetch'
                );
            }

            closeCompactMenus();
            return;
        }

        if (behavior === 'direct') {
            const targetUrl = String(actionButton.getAttribute('data-open-url') || '').trim();
            if (targetUrl !== '') {
                openDecisionEditor(
                    targetUrl,
                    String(actionButton.getAttribute('data-open-title') || '').trim()
                        || (payload.text && payload.text.drawerTitle ? payload.text.drawerTitle : 'Prises de decision'),
                    String(actionButton.getAttribute('data-open-description') || '').trim()
                );
            }

            closeCompactMenus();
            return;
        }

        const targetUrl = String(actionButton.getAttribute('data-open-url') || '').trim();
        if (targetUrl !== '') {
            openDecisionFromInteraction(
                activeCompactMenuToggle ? Number(activeCompactMenuToggle.getAttribute('data-omo-decision-id') || 0) : 0,
                targetUrl,
                String(actionButton.getAttribute('data-open-title') || '').trim() || (payload.text && payload.text.drawerTitle ? payload.text.drawerTitle : 'Prises de decision'),
                String(actionButton.getAttribute('data-open-description') || '').trim(),
                String(actionButton.getAttribute('data-open-mode') || '').trim()
            );
        }

        closeCompactMenus();
        return;
    }

    const exportButton = event.target.closest('[data-omo-decision-export-format-button="1"]');
    if (exportButton) {
        event.preventDefault();
        startDecisionExportDownload(exportButton);
        return;
    }

    if (!event.target.closest('[data-omo-decision-floating-menu="1"]')) {
        closeCompactMenus();
    }
});

omoDecisionRegisterGlobalListener(ownerDocument, 'keydown', function (event) {
    if (event.key === 'Escape' && !floatingCompactMenu.hidden) {
        closeCompactMenus();
    }
});

omoDecisionRegisterGlobalListener(ownerDocument, 'scroll', function () {
    if (!activeCompactMenuToggle || floatingCompactMenu.hidden) {
        return;
    }

    positionCompactMenu(activeCompactMenuToggle);
}, true);

omoDecisionRegisterGlobalListener(window, 'resize', function () {
    if (!activeCompactMenuToggle || floatingCompactMenu.hidden) {
        return;
    }

    positionCompactMenu(activeCompactMenuToggle);
});

omoDecisionRegisterGlobalListener(window, 'resize', syncStatusTabsOverflow);
omoDecisionRegisterGlobalListener(window, 'omo-decision-moved', function () {
    omoDecisionRefreshIndex({ silent: false });
});

root.querySelectorAll('[data-omo-decisions-filter-toggle]').forEach(function (button) {
    button.addEventListener('click', function () {
        if (decisionFilterPanelOpen) {
            omoDecisionsCloseFilterPanel(true, false);
        } else {
            omoDecisionsOpenFilterPanel();
        }
    });
});

if (elements.viewFilterPanel) {
    elements.viewFilterPanel.addEventListener('click', function (event) {
        const moreToggle = event.target.closest('[data-omo-decisions-filter-more-toggle]');
        if (moreToggle) {
            event.preventDefault();
            event.stopPropagation();
            const moreMenu = moreToggle.closest('[data-omo-decisions-filter-more-menu]');
            const morePanel = moreMenu ? moreMenu.querySelector('[data-omo-decisions-filter-more-panel]') : null;
            const isMoreMenuOpen = !!morePanel && !morePanel.hidden;
            omoDecisionsCloseFilterMoreMenu();
            if (!isMoreMenuOpen && morePanel) {
                morePanel.hidden = false;
                moreMenu.classList.add('is-open');
                moreToggle.setAttribute('aria-expanded', 'true');
            }
            return;
        }
        const moreAction = event.target.closest('[data-omo-decisions-filter-more-action]');
        if (moreAction) {
            event.preventDefault();
            event.stopPropagation();
            omoDecisionsApplyFilterMoreAction(
                moreAction.getAttribute('data-omo-decisions-filter-more-action') || ''
            );
            return;
        }
        if (event.target.closest('[data-omo-decisions-filter-apply]')) {
            event.preventDefault();
            omoDecisionsCloseFilterPanel(true, false);
            return;
        }
        if (event.target.closest('[data-omo-decisions-filter-save]')) {
            event.preventDefault();
            omoDecisionsCloseFilterPanel(true, true);
            return;
        }
        const scopeButton = event.target.closest('[data-omo-decision-scope-toggle]');
        if (scopeButton && pendingViewFilters) {
            pendingViewFilters.scope = omoDecisionsNormalizeScopePreference(
                scopeButton.getAttribute('data-omo-decision-scope-toggle')
            );
            omoDecisionsSyncFilterChoices();
            return;
        }
        const typeButton = event.target.closest('[data-omo-decisions-type-choice]');
        if (typeButton && pendingViewFilters) {
            pendingViewFilters.type = String(typeButton.getAttribute('data-omo-decisions-type-choice') || 'all');
            omoDecisionsSyncFilterChoices();
            return;
        }
        const methodButton = event.target.closest('[data-omo-decisions-method-choice]');
        if (methodButton && pendingViewFilters) {
            pendingViewFilters.method = String(methodButton.getAttribute('data-omo-decisions-method-choice') || 'all');
            omoDecisionsSyncFilterChoices();
        }
    });
}

root.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && decisionFilterPanelOpen) {
        omoDecisionsCloseFilterPanel(false, false);
    }
});

root.querySelectorAll('[data-omo-decisions-sort]').forEach(function (button) {
    button.addEventListener('click', function () {
        const nextSort = omoDecisionsNormalizeSortPreference(
            button.getAttribute('data-omo-decisions-sort')
        );

        if (decisionFilterPanelOpen && pendingViewFilters) {
            pendingViewFilters.sort = nextSort;
            omoDecisionsSyncFilterChoices();
            return;
        }

        if (nextSort === state.sort) {
            return;
        }

        state.sort = nextSort;
        omoDecisionsWritePreferences({
            sort: state.sort,
            density: state.density
        });
        renderList();
    });
});

root.querySelectorAll('[data-omo-decisions-density]').forEach(function (button) {
    button.addEventListener('click', function () {
        const nextDensity = omoDecisionsNormalizeDensityPreference(
            button.getAttribute('data-omo-decisions-density')
        );

        if (decisionFilterPanelOpen && pendingViewFilters) {
            pendingViewFilters.density = nextDensity;
            omoDecisionsSyncFilterChoices();
            return;
        }

        if (nextDensity === state.density) {
            return;
        }

        state.density = nextDensity;
        omoDecisionsWritePreferences({
            sort: state.sort,
            density: state.density
        });
        renderList();
    });
});

window.omoDecisionOpenNestedDrawer = function (title, url, description) {
    openDecisionEditor(url, title, description);
};

window.omoRefreshDecisionIndex = function (options) {
    return omoDecisionRefreshIndex(options);
};

window.omoRefreshDecisionView = function (url, options) {
    const targetUrl = String(url || '').trim();
    if (!targetUrl) {
        return;
    }

    const settings = options && typeof options === 'object' ? options : {};
    const title = String(settings.title || (payload.text && payload.text.drawerTitle ? payload.text.drawerTitle : 'Prises de decision')).trim();
    const description = String(settings.description || '').trim();
    openDecisionEditor(targetUrl, title, description);
};

window.omoDecisionCloseNestedDrawer = function () {
    omoDecisionCloseNestedEditorDrawer();
};

if (!root.__omoDecisionsRouteHandler) {
    root.__omoDecisionsRouteHandler = function (routeEvent) {
        if (!document.body.contains(root)) {
            return;
        }

        const detail = routeEvent && routeEvent.detail
            ? routeEvent.detail
            : {};
        const targetDecisionId = Number(detail.decisionId || 0);
        const targetMode = normalizeDecisionOpenMode(detail.mode || 'default');

        if (targetDecisionId > 0) {
            if (!openDecisionItemById(targetDecisionId, { mode: targetMode })) {
                omoDecisionCloseNestedEditorDrawer({
                    force: true,
                    refreshIndex: false
                });
            }
            return;
        }

        omoDecisionCloseNestedEditorDrawer({
            force: true,
            refreshIndex: false
        });
    };

    window.addEventListener('omo-decisions-route-change', root.__omoDecisionsRouteHandler);
}

if (elements.statusTabs) {
    elements.statusTabs.addEventListener('click', function (event) {
        const button = event.target.closest('[data-status]');
        if (!button) {
            return;
        }

        const nextStatus = String(button.getAttribute('data-status') || 'all');
        if (decisionFilterPanelOpen && pendingViewFilters) {
            pendingViewFilters.status = nextStatus;
            omoDecisionsSyncFilterChoices();
            return;
        }
        state.status = nextStatus;
        renderList();
    });
}

if (elements.search) {
    elements.search.addEventListener('input', function () {
        state.search = String(elements.search.value || '');
        omoDecisionsWriteSearch(state.search);
        renderList();
    });
}

if (elements.type) {
    elements.type.addEventListener('change', function () {
        if (decisionFilterPanelOpen && pendingViewFilters) {
            pendingViewFilters.type = String(elements.type.value || 'all');
            omoDecisionsSyncFilterChoices();
            return;
        }
        state.type = String(elements.type.value || 'all');
        renderList();
    });
}

if (elements.method) {
    elements.method.addEventListener('change', function () {
        if (decisionFilterPanelOpen && pendingViewFilters) {
            pendingViewFilters.method = String(elements.method.value || 'all');
            omoDecisionsSyncFilterChoices();
            return;
        }
        state.method = String(elements.method.value || 'all');
        renderList();
    });
}

if (elements.reset) {
    elements.reset.addEventListener('click', function () {
        resetFilters();
        renderList();
    });
}

root.addEventListener('click', function (event) {
    const accordionToggle = event.target.closest('[data-generic-accordion-toggle]');
    if (accordionToggle) {
        window.setTimeout(function () {
            const accordion = accordionToggle.closest('[data-generic-accordion]');
            if (!accordion) {
                return;
            }

            accordionToggle.setAttribute('aria-expanded', accordion.classList.contains('is-collapsed') ? 'false' : 'true');
        }, 0);
    }

    const compactMenuToggle = event.target.closest('[data-omo-decision-compact-menu-toggle="1"]');
    if (compactMenuToggle) {
        event.preventDefault();
        event.stopPropagation();
        openCompactMenu(compactMenuToggle);
        return;
    }

    const button = event.target.closest('[data-open-url]');
    if (!button) {
        return;
    }

    const targetUrl = String(button.getAttribute('data-open-url') || '').trim();
    if (targetUrl === '') {
        return;
    }

    const targetTitle = String(button.getAttribute('data-open-title') || '').trim();
    const decisionContainer = button.closest('[data-omo-decision-id]');
    const decisionId = decisionContainer
        ? Number(decisionContainer.getAttribute('data-omo-decision-id') || 0)
        : Number(button.getAttribute('data-omo-decision-id') || 0);
    const targetMode = String(button.getAttribute('data-open-mode') || '').trim();

    openDecisionFromInteraction(
        decisionId,
        targetUrl,
        targetTitle !== '' ? targetTitle : (payload.text && payload.text.drawerTitle ? payload.text.drawerTitle : 'Prises de décision'),
        String(button.getAttribute('data-open-description') || '').trim(),
        targetMode
    );
    event.preventDefault();
    return;

    openDecisionEditor(
        targetUrl,
        targetTitle !== '' ? targetTitle : (payload.text && payload.text.drawerTitle ? payload.text.drawerTitle : 'Prises de décision'),
    );
    event.preventDefault();
});

const omoDecisionIndexTeardown = function () {
    closeCompactMenus();

    if (root.__omoDecisionsRouteHandler) {
        window.removeEventListener('omo-decisions-route-change', root.__omoDecisionsRouteHandler);
        root.__omoDecisionsRouteHandler = null;
    }

    while (omoDecisionIndexGlobalCleanupCallbacks.length > 0) {
        const cleanup = omoDecisionIndexGlobalCleanupCallbacks.pop();
        if (typeof cleanup !== 'function') {
            continue;
        }

        try {
            cleanup();
        } catch (error) {
            if (window.console && typeof window.console.warn === 'function') {
                window.console.warn('[OMO][Decisions][panel] cleanup failed', error);
            }
        }
    }

    if (window.omoDecisionIndexTeardown === omoDecisionIndexTeardown) {
        window.omoDecisionIndexTeardown = null;
    }
};

window.omoDecisionIndexTeardown = omoDecisionIndexTeardown;
})();
