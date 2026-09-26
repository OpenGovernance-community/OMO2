
var omoTeamSavedViewsStorageKey = 'omo.team.saved-views.v2';
var omoTeamLegacySavedViewsStorageKey = 'omo.team.saved-views.v1';
var omoTeamSessionViewsStorageKey = 'omo.team.session-views.v1';
var omoTeamMapEnabled = window.omoTeamPageConfig.omoTeamMapEnabled;
var omoTeamMapMembers = window.omoTeamPageConfig.omoTeamMapMembers;
var omoTeamInitialScope = window.omoTeamPageConfig.omoTeamInitialScope;
var omoTeamText = window.omoTeamPageConfig.omoTeamText;
var omoTeamLeafletMap = null;
var omoTeamLeafletLayer = null;
var omoTeamLeafletTileState = {layer: null, theme: null};
var omoTeamCurrentView = 'cards';
var omoTeamCurrentSearch = '';
var omoTeamPendingFilters = null;
var omoTeamFilterPanelOpen = false;

function omoTeamGetRoot() {
    return typeof window.omoFindApplicationRoot === 'function'
        ? window.omoFindApplicationRoot('omo-team-root')
        : document.getElementById('omo-team-root');
}

function omoTeamEscapeHtml(value) {
    return String(value == null ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function omoTeamFormatText(template, variables) {
    let text = String(template == null ? '' : template);
    if (!variables || typeof variables !== 'object') {
        return text;
    }

    Object.keys(variables).forEach(function (key) {
        const value = variables[key] == null ? '' : String(variables[key]);
        text = text.split('{' + key + '}').join(value);
    });

    return text;
}

function omoTeamNormalizeScope(scopeValue) {
    const normalizedScope = String(scopeValue || '').trim().toLowerCase();
    if (normalizedScope === 'global') {
        return 'descendants';
    }
    return normalizedScope === 'children' || normalizedScope === 'descendants' ? normalizedScope : 'contextual';
}

function omoTeamNormalizeView(viewValue) {
    const normalizedView = String(viewValue || '').trim().toLowerCase();
    if (normalizedView === 'map' && omoTeamMapEnabled) {
        return 'map';
    }
    return normalizedView === 'compact' ? 'compact' : 'cards';
}

function omoTeamGetContextKey() {
    const root = omoTeamGetRoot();
    const regularKey = String(root ? (root.getAttribute('data-team-oid') || '0') : '0')
        + ':' + String(root ? (root.getAttribute('data-team-cid') || '0') : '0');
    return typeof window.omoApplicationViewPreferencesGetStorageContextKey === 'function'
        ? window.omoApplicationViewPreferencesGetStorageContextKey(root, regularKey)
        : regularKey;
}

function omoTeamCreateViewPreferences(filters) {
    return {
        scope: omoTeamNormalizeScope(filters && filters.scope),
        view: omoTeamNormalizeView(filters && filters.view)
    };
}

function omoTeamGetSavedViewsStore() {
    try {
        const storedValue = window.localStorage.getItem(omoTeamSavedViewsStorageKey);
        const savedViews = storedValue ? JSON.parse(storedValue) : null;
        if (savedViews && typeof savedViews === 'object' && savedViews.contexts && typeof savedViews.contexts === 'object') {
            return {
                defaultView: savedViews.defaultView && typeof savedViews.defaultView === 'object'
                    ? savedViews.defaultView
                    : null,
                contexts: savedViews.contexts
            };
        }

        const legacyValue = window.localStorage.getItem(omoTeamLegacySavedViewsStorageKey);
        const legacyViews = legacyValue ? JSON.parse(legacyValue) : null;
        return {
            defaultView: null,
            contexts: legacyViews && typeof legacyViews === 'object' ? legacyViews : {}
        };
    } catch (error) {
        return {defaultView: null, contexts: {}};
    }
}

function omoTeamSaveViewsStore(store) {
    try {
        window.localStorage.setItem(omoTeamSavedViewsStorageKey, JSON.stringify({
            defaultView: store.defaultView && typeof store.defaultView === 'object'
                ? store.defaultView
                : null,
            contexts: store.contexts && typeof store.contexts === 'object' ? store.contexts : {}
        }));
    } catch (error) {
    }
}

function omoTeamGetStoredViewPreferences() {
    const preferences = omoTeamGetSavedViewsStore().contexts[omoTeamGetContextKey()];
    return preferences && typeof preferences === 'object' ? preferences : null;
}

function omoTeamGetDefaultViewPreferences() {
    return omoTeamGetSavedViewsStore().defaultView;
}

function omoTeamStoreViewPreferences(filters) {
    const store = omoTeamGetSavedViewsStore();
    store.contexts[omoTeamGetContextKey()] = omoTeamCreateViewPreferences(filters);
    omoTeamSaveViewsStore(store);
}

function omoTeamStoreDefaultViewPreferences(filters) {
    const store = omoTeamGetSavedViewsStore();
    store.defaultView = omoTeamCreateViewPreferences(filters);
    omoTeamSaveViewsStore(store);
}

function omoTeamClearStoredViewPreferences() {
    const store = omoTeamGetSavedViewsStore();
    delete store.contexts[omoTeamGetContextKey()];
    omoTeamSaveViewsStore(store);
}

function omoTeamReadViewPreferences(storage, storageKey) {
    try {
        const storedValue = storage.getItem(storageKey);
        const views = storedValue ? JSON.parse(storedValue) : null;
        if (!views || typeof views !== 'object') {
            return null;
        }
        const preferences = views[omoTeamGetContextKey()];
        return preferences && typeof preferences === 'object' ? preferences : null;
    } catch (error) {
        return null;
    }
}

function omoTeamWriteViewPreferences(storage, storageKey, filters) {
    try {
        const storedValue = storage.getItem(storageKey);
        let views = storedValue ? JSON.parse(storedValue) : {};
        if (!views || typeof views !== 'object') {
            views = {};
        }
        views[omoTeamGetContextKey()] = omoTeamCreateViewPreferences(filters);
        storage.setItem(storageKey, JSON.stringify(views));
    } catch (error) {
    }
}

function omoTeamClearSessionViewPreferences() {
    try {
        const storedValue = window.sessionStorage.getItem(omoTeamSessionViewsStorageKey);
        const views = storedValue ? JSON.parse(storedValue) : {};
        if (!views || typeof views !== 'object') {
            return;
        }
        delete views[omoTeamGetContextKey()];
        window.sessionStorage.setItem(omoTeamSessionViewsStorageKey, JSON.stringify(views));
    } catch (error) {
    }
}

function omoTeamClearAllSessionViewPreferences() {
    try {
        window.sessionStorage.removeItem(omoTeamSessionViewsStorageKey);
    } catch (error) {
    }
}

function omoTeamBuildScopeUrl(scopeValue) {
    const root = omoTeamGetRoot();
    const organizationId = Number(root ? (root.getAttribute('data-team-oid') || 0) : 0);
    const holonId = Number(root ? (root.getAttribute('data-team-cid') || 0) : 0);
    const rootHolonId = Number(root ? (root.getAttribute('data-team-root-hid') || 0) : 0);
    const resolvedScope = omoTeamNormalizeScope(scopeValue);
    const query = [];

    if (organizationId > 0) {
        query.push('oid=' + encodeURIComponent(String(organizationId)));
    }

    if (holonId > 0 && holonId !== rootHolonId) {
        query.push('cid=' + encodeURIComponent(String(holonId)));
    }

    query.push('team_scope=' + encodeURIComponent(resolvedScope));
    const quickSearch = root ? String(root.getAttribute('data-team-query') || '').trim() : '';
    if (quickSearch !== '') {
        query.push('team_query=' + encodeURIComponent(quickSearch));
    }

    return '/omo/api/team/index.php' + (query.length > 0 ? '?' + query.join('&') : '');
}

function omoTeamSetScopeLoadingState(targetScope, isLoading) {
    const root = omoTeamGetRoot();
    if (!root) {
        return;
    }

    const resolvedScope = omoTeamNormalizeScope(targetScope);
    let activeScopeIndex = 0;

    root.classList.toggle('is-loading', Boolean(isLoading));
    root.setAttribute('data-team-scope', resolvedScope);
    if (typeof window.omoSetPanelResultsLoadingSkeleton === 'function') {
        window.omoSetPanelResultsLoadingSkeleton(root, isLoading, {
            contentSelector: '.omo-panel-view__body_content'
        });
    }

    root.querySelectorAll('[data-team-scope-toggle]').forEach(function (scopeButton) {
        const buttonScope = omoTeamNormalizeScope(scopeButton.getAttribute('data-team-scope-toggle') || '');
        const isActive = buttonScope === resolvedScope;

        scopeButton.disabled = Boolean(isLoading);
        scopeButton.classList.toggle('is-active', isActive);
        scopeButton.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        if (isActive) {
            activeScopeIndex = parseInt(scopeButton.getAttribute('data-omo-scope-index') || '0', 10) || 0;
        }
    });

    const scopeSwitch = root.querySelector('[data-omo-scope-switch]');
    if (scopeSwitch) {
        scopeSwitch.setAttribute('data-omo-scope-switch', resolvedScope);
        scopeSwitch.style.setProperty('--omo-scope-active-index', String(activeScopeIndex));
    }
}

function omoTeamChangeScope(scopeValue) {
    const root = omoTeamGetRoot();
    const currentScope = omoTeamNormalizeScope(root ? root.getAttribute('data-team-scope') : omoTeamInitialScope);
    const targetScope = omoTeamNormalizeScope(scopeValue);

    if (targetScope === currentScope) {
        return false;
    }

    const nextUrl = omoTeamBuildScopeUrl(targetScope);
    omoTeamSetScopeLoadingState(targetScope, true);

    if (root && typeof window.omoReplaceFetchedPanelRoot === 'function') {
        if (typeof window.requestAnimationFrame === 'function') {
            window.requestAnimationFrame(function () {
                window.omoReplaceFetchedPanelRoot({
                    rootSelector: '#omo-team-root',
                    currentRoot: root,
                    url: nextUrl,
                    setLoadingState: function (isLoading) {
                        omoTeamSetScopeLoadingState(targetScope, isLoading);
                    }
                }).catch(function () {
                    window.location.href = nextUrl;
                });
            });
        } else {
            window.omoReplaceFetchedPanelRoot({
                rootSelector: '#omo-team-root',
                currentRoot: root,
                url: nextUrl,
                setLoadingState: function (isLoading) {
                    omoTeamSetScopeLoadingState(targetScope, isLoading);
                }
            }).catch(function () {
                window.location.href = nextUrl;
            });
        }
        return false;
    }

    window.location.href = nextUrl;
    return false;
}

window.omoToggleTeamScope = function (button, event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    return omoTeamChangeScope(button ? button.getAttribute('data-team-scope-toggle') : '');
};

function omoTeamApplyView(viewName) {
    const normalizedView = omoTeamNormalizeView(viewName);
    const root = omoTeamGetRoot();
    omoTeamCurrentView = normalizedView;
    if (root) {
        root.setAttribute('data-team-view', normalizedView);
    }

    $('[data-team-view-panel]').each(function () {
        const shouldShow = $(this).data('team-view-panel') === normalizedView;
        $(this).prop('hidden', !shouldShow);
    });

    const viewButton = root ? root.querySelector('[data-team-filter-view="' + normalizedView + '"]') : null;
    const viewChip = root ? root.querySelector('[data-team-filter-view-chip]') : null;
    if (viewButton && viewChip) {
        viewChip.textContent = viewButton.textContent.trim();
    }

    if (normalizedView === 'map' && omoTeamMapEnabled) {
        if (typeof window.commonWhenLeafletReady === 'function') {
            window.commonWhenLeafletReady(function () {
                omoTeamEnsureMapReady();
            });
        } else {
            omoTeamEnsureMapReady();
        }
    }
    omoTeamApplyQuickSearch();
}

function omoTeamNormalizeSearch(value) {
    return String(value || '')
        .toLocaleLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .trim();
}

function omoTeamMemberMatchesSearch(member, query) {
    if (query === '') {
        return true;
    }
    return omoTeamNormalizeSearch(member && member.searchText ? member.searchText : '').indexOf(query) !== -1;
}

function omoTeamUpdateMapSummary(count) {
    const root = omoTeamGetRoot();
    const summary = root ? root.querySelector('[data-team-map-summary]') : null;
    if (!summary) {
        return;
    }
    const template = count === 1 ? omoTeamText.mapSummaryOne : omoTeamText.mapSummaryOther;
    summary.textContent = omoTeamFormatText(template, {count: String(count)});
}

function omoTeamApplyQuickSearch() {
    const root = omoTeamGetRoot();
    if (!root) {
        return;
    }
    const query = omoTeamNormalizeSearch(omoTeamCurrentSearch);
    root.querySelectorAll('[data-team-member-item]').forEach(function (memberNode) {
        const searchableText = memberNode.getAttribute('data-team-member-search') || memberNode.textContent || '';
        memberNode.hidden = query !== '' && omoTeamNormalizeSearch(searchableText).indexOf(query) === -1;
    });
    root.querySelectorAll('[data-team-default-empty]').forEach(function (emptyNode) {
        emptyNode.hidden = query !== '';
    });

    let visibleCount = 0;
    if (omoTeamCurrentView === 'map') {
        visibleCount = omoTeamMapMembers.filter(function (member) {
            return omoTeamMemberMatchesSearch(member, query);
        }).length;
        const mapShell = root.querySelector('.omo-team__map-shell');
        if (mapShell) {
            mapShell.hidden = query !== '' && visibleCount === 0;
        }
        omoTeamUpdateMapSummary(visibleCount);
        if (omoTeamLeafletMap) {
            omoTeamEnsureMapReady();
        }
    } else {
        const activePanel = root.querySelector('[data-team-view-panel="' + omoTeamCurrentView + '"]');
        visibleCount = activePanel
            ? activePanel.querySelectorAll('[data-team-member-item]:not([hidden])').length
            : 0;
        const itemsContainer = root.querySelector('[data-team-items-container="' + omoTeamCurrentView + '"]');
        if (itemsContainer) {
            itemsContainer.hidden = query !== '' && visibleCount === 0;
        }
    }

    const empty = root.querySelector('[data-team-search-empty]');
    if (empty) {
        empty.hidden = query === '' || visibleCount > 0;
    }
}

function omoTeamGetActiveFilters() {
    const root = omoTeamGetRoot();
    return {
        scope: omoTeamNormalizeScope(root ? root.getAttribute('data-team-scope') : omoTeamInitialScope),
        view: omoTeamNormalizeView(omoTeamCurrentView)
    };
}

function omoTeamNormalizeFilters(filters) {
    const root = omoTeamGetRoot();
    const active = omoTeamGetActiveFilters();
    let scope = omoTeamNormalizeScope(filters && filters.scope);
    if (!root || !root.querySelector('[data-team-filter-scope="' + scope + '"]')) {
        scope = active.scope;
    }
    return {
        scope: scope,
        view: omoTeamNormalizeView(filters && filters.view)
    };
}

function omoTeamSyncFilterChoices() {
    const root = omoTeamGetRoot();
    const panel = root ? root.querySelector('[data-team-filter-panel]') : null;
    if (!panel || !omoTeamPendingFilters) {
        return;
    }
    omoTeamPendingFilters = omoTeamNormalizeFilters(omoTeamPendingFilters);
    panel.querySelectorAll('[data-team-filter-scope]').forEach(function (button) {
        const active = button.getAttribute('data-team-filter-scope') === omoTeamPendingFilters.scope;
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
    panel.querySelectorAll('[data-team-filter-view]').forEach(function (button) {
        const active = button.getAttribute('data-team-filter-view') === omoTeamPendingFilters.view;
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
}

function omoTeamHandleFilterOutsidePointerDown(event) {
    const root = omoTeamGetRoot();
    const control = root ? root.querySelector('[data-team-filter-control]') : null;
    if (control && control.contains(event.target)) {
        return;
    }
    omoTeamCloseFilterPanel(true, false);
}

function omoTeamApplyFilters(filters, active) {
    const next = omoTeamNormalizeFilters(filters);
    const previous = active || omoTeamGetActiveFilters();
    if (next.scope !== previous.scope) {
        omoTeamChangeScope(next.scope);
        return;
    }
    omoTeamApplyView(next.view);
}

function omoTeamCloseFilterMoreMenu() {
    const root = omoTeamGetRoot();
    if (!root) {
        return;
    }
    root.querySelectorAll('[data-team-filter-more-menu]').forEach(function (menu) {
        const panel = menu.querySelector('[data-team-filter-more-panel]');
        const toggle = menu.querySelector('[data-team-filter-more-toggle]');
        if (panel) {
            panel.hidden = true;
        }
        menu.classList.remove('is-open');
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
        }
    });
}

function omoTeamOpenFilterPanel() {
    const root = omoTeamGetRoot();
    const panel = root ? root.querySelector('[data-team-filter-panel]') : null;
    if (!root || !panel || omoTeamFilterPanelOpen) {
        return;
    }
    omoTeamPendingFilters = omoTeamGetActiveFilters();
    omoTeamCloseFilterMoreMenu();
    omoTeamSyncFilterChoices();
    panel.hidden = false;
    omoTeamFilterPanelOpen = true;
    root.querySelectorAll('[data-team-filter-toggle]').forEach(function (button) {
        button.setAttribute('aria-expanded', 'true');
    });
    document.addEventListener('pointerdown', omoTeamHandleFilterOutsidePointerDown, true);
}

function omoTeamCloseFilterPanel(applyChanges, saveView) {
    const root = omoTeamGetRoot();
    const panel = root ? root.querySelector('[data-team-filter-panel]') : null;
    if (!root || !omoTeamFilterPanelOpen) {
        return;
    }
    omoTeamFilterPanelOpen = false;
    if (panel) {
        panel.hidden = true;
    }
    root.querySelectorAll('[data-team-filter-toggle]').forEach(function (button) {
        button.setAttribute('aria-expanded', 'false');
    });
    document.removeEventListener('pointerdown', omoTeamHandleFilterOutsidePointerDown, true);
    omoTeamCloseFilterMoreMenu();

    if (!applyChanges || !omoTeamPendingFilters) {
        omoTeamPendingFilters = null;
        return;
    }

    const active = omoTeamGetActiveFilters();
    const next = omoTeamNormalizeFilters(omoTeamPendingFilters);
    omoTeamPendingFilters = null;
    if (saveView) {
        omoTeamStoreViewPreferences(next);
        omoTeamClearSessionViewPreferences();
    } else {
        omoTeamWriteViewPreferences(window.sessionStorage, omoTeamSessionViewsStorageKey, next);
    }

    omoTeamApplyFilters(next, active);
}

function omoTeamApplyFilterMoreAction(action) {
    if (!omoTeamFilterPanelOpen || !omoTeamPendingFilters) {
        return;
    }

    const active = omoTeamGetActiveFilters();
    const next = omoTeamNormalizeFilters(omoTeamPendingFilters);
    omoTeamCloseFilterPanel(false, false);

    if (action === 'set-default') {
        omoTeamClearStoredViewPreferences();
        omoTeamClearSessionViewPreferences();
        omoTeamStoreDefaultViewPreferences(next);
        omoTeamApplyFilters(next, active);
        return;
    }

    if (action === 'apply-everywhere') {
        const store = omoTeamGetSavedViewsStore();
        store.defaultView = omoTeamCreateViewPreferences(next);
        store.contexts = {};
        omoTeamSaveViewsStore(store);
        omoTeamClearAllSessionViewPreferences();
        omoTeamApplyFilters(next, active);
        return;
    }

    if (action === 'restore-default') {
        omoTeamClearStoredViewPreferences();
        omoTeamClearSessionViewPreferences();
        const store = omoTeamGetSavedViewsStore();
        store.defaultView = null;
        omoTeamSaveViewsStore(store);
        const serverDefault = typeof window.omoApplicationViewPreferencesGetDefault === 'function'
            ? window.omoApplicationViewPreferencesGetDefault(root)
            : null;
        omoTeamApplyFilters(serverDefault || {
            scope: 'contextual',
            view: 'cards'
        }, active);
    }
}

function omoTeamRevealRoot() {
    const root = omoTeamGetRoot();
    if (!root) {
        return;
    }
    root.removeAttribute('data-team-preferences-pending');
    root.removeAttribute('aria-busy');
}

function omoTeamInitializeFilters() {
    const root = omoTeamGetRoot();
    if (!root) {
        return;
    }
    const temporary = omoTeamReadViewPreferences(window.sessionStorage, omoTeamSessionViewsStorageKey);
    const canUseLegacyPersonal = typeof window.omoApplicationViewPreferencesCanUseLegacyPersonal === 'function'
        && window.omoApplicationViewPreferencesCanUseLegacyPersonal(root);
    const saved = canUseLegacyPersonal ? omoTeamGetStoredViewPreferences() : null;
    const defaultView = canUseLegacyPersonal ? omoTeamGetDefaultViewPreferences() : null;
    const serverDefault = typeof window.omoApplicationViewPreferencesGetDefault === 'function'
        ? window.omoApplicationViewPreferencesGetDefault(root)
        : null;
    const personalView = typeof window.omoApplicationViewPreferencesGetPersonal === 'function'
        ? window.omoApplicationViewPreferencesGetPersonal(root)
        : null;
    const preferences = omoTeamNormalizeFilters(temporary || personalView || serverDefault || saved || defaultView || {
        scope: root.getAttribute('data-team-scope') || omoTeamInitialScope,
        view: 'cards'
    });
    const currentScope = omoTeamNormalizeScope(root.getAttribute('data-team-scope') || omoTeamInitialScope);
    omoTeamCurrentSearch = root.getAttribute('data-team-query') || '';
    if (preferences.scope !== currentScope) {
        omoTeamChangeScope(preferences.scope);
        return;
    }

    omoTeamApplyView(preferences.view);
    const scopeButton = root.querySelector('[data-team-filter-scope="' + currentScope + '"]');
    const scopeChip = root.querySelector('[data-team-filter-scope-chip]');
    if (scopeButton && scopeChip) {
        scopeChip.textContent = scopeButton.textContent.trim();
    }
    omoTeamRevealRoot();
}

function omoTeamEnsureMapReady() {
    if (typeof L === 'undefined' || !Array.isArray(omoTeamMapMembers) || omoTeamMapMembers.length === 0) {
        return;
    }

    const mapElement = document.getElementById('omo-team-map');
    if (!mapElement) {
        return;
    }

    if (!omoTeamLeafletMap) {
        omoTeamLeafletMap = L.map(mapElement, {
            zoomControl: true,
            scrollWheelZoom: true
        });

        if (typeof window.commonBindLeafletTheme === 'function') {
            window.commonBindLeafletTheme(omoTeamLeafletMap, omoTeamLeafletTileState);
        }

        omoTeamLeafletLayer = L.layerGroup().addTo(omoTeamLeafletMap);
    }

    if (!omoTeamLeafletLayer) {
        omoTeamLeafletLayer = L.layerGroup().addTo(omoTeamLeafletMap);
    }
    omoTeamLeafletLayer.clearLayers();
    const query = omoTeamNormalizeSearch(omoTeamCurrentSearch);
    const visibleMapMembers = omoTeamMapMembers.filter(function (member) {
        return omoTeamMemberMatchesSearch(member, query);
    });
    const bounds = [];

    visibleMapMembers.forEach(function (member) {
            const lat = Number(member.lat);
            const lng = Number(member.long);
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                return;
            }

            const popupBits = ['<div class="omo-team__map-popup">'];

            popupBits.push('<div class="omo-team__map-popup-head">');
            if (member.photoUrl) {
                popupBits.push('<img class="omo-team__map-popup-photo" src="' + omoTeamEscapeHtml(member.photoUrl) + '" alt="' + omoTeamEscapeHtml(member.displayName || omoTeamFormatText(omoTeamText.userFallback, {userId: member.userId})) + '" width="44" height="44" decoding="async">');
            } else {
                popupBits.push('<div class="omo-team__map-popup-photo-placeholder">' + omoTeamEscapeHtml(member.initials || 'P') + '</div>');
            }

            popupBits.push('<div class="omo-team__map-popup-identity">');
            popupBits.push('<div class="omo-team__map-popup-name">' + omoTeamEscapeHtml(member.displayName || omoTeamFormatText(omoTeamText.userFallback, {userId: member.userId})) + '</div>');
            if (member.secondary) {
                popupBits.push('<div class="omo-team__map-popup-secondary">' + omoTeamEscapeHtml(member.secondary) + '</div>');
            } else if (member.email) {
                popupBits.push('<div class="omo-team__map-popup-secondary">' + omoTeamEscapeHtml(member.email) + '</div>');
            }
            popupBits.push('</div>');
            popupBits.push('</div>');

            popupBits.push('<div class="omo-team__map-popup-badges">');
            if (member.isPending) {
                popupBits.push('<span class="omo-team__map-popup-badge omo-team__map-popup-badge--pending">' + omoTeamEscapeHtml(omoTeamText.pending) + '</span>');
            }
            if (member.isContextAdmin) {
                popupBits.push('<span class="omo-team__map-popup-badge omo-team__map-popup-badge--admin">' + omoTeamEscapeHtml(omoTeamText.adminContext) + '</span>');
            }
            if (member.isOrganizationAdmin) {
                popupBits.push('<span class="omo-team__map-popup-badge">' + omoTeamEscapeHtml(omoTeamText.adminOrganization) + '</span>');
            }
            popupBits.push('</div>');

            popupBits.push('<div class="omo-team__map-popup-meta">');
            const phoneIcon = '<svg class="omo-team__map-popup-contact-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M6.6 10.8c1.4 2.8 3.7 5.1 6.5 6.5l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.5.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C10.5 21 3 13.5 3 4.2c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.2.2 2.4.6 3.5.1.3.1.7-.2 1L6.6 10.8z" fill="currentColor"></path></svg>';
            const emailIcon = '<svg class="omo-team__map-popup-contact-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6.5A2.5 2.5 0 0 1 5.5 4h13A2.5 2.5 0 0 1 21 6.5v11a2.5 2.5 0 0 1-2.5 2.5h-13A2.5 2.5 0 0 1 3 17.5v-11zm2 .2v.3l7 4.7 7-4.7v-.3c0-.4-.3-.7-.7-.7H5.7c-.4 0-.7.3-.7.7zm14 2.7-6.4 4.3a1 1 0 0 1-1.1 0L5 9.4v8.1c0 .4.3.7.7.7h12.6c.4 0 .7-.3.7-.7V9.4z" fill="currentColor"></path></svg>';
            const contactLines = [];
            const phoneValue = member.phoneHref
                ? '<a class="omo-team__map-popup-contact-link" href="' + omoTeamEscapeHtml(member.phoneHref) + '" data-map-popup-contact-link>' + omoTeamEscapeHtml(member.phone) + '</a>'
                : omoTeamEscapeHtml(member.phone || omoTeamText.notProvided);
            const emailValue = member.emailHref
                ? '<a class="omo-team__map-popup-contact-link" href="' + omoTeamEscapeHtml(member.emailHref) + '" data-map-popup-contact-link>' + omoTeamEscapeHtml(member.email) + '</a>'
                : omoTeamEscapeHtml(member.email || omoTeamText.notProvided);
            contactLines.push('<div class="omo-team__map-popup-contact-line">' + phoneIcon + '<span>' + phoneValue + '</span></div>');
            contactLines.push('<div class="omo-team__map-popup-contact-line">' + emailIcon + '<span>' + emailValue + '</span></div>');
            popupBits.push('<div class="omo-team__map-popup-meta-row"><div class="omo-team__map-popup-meta-label">' + omoTeamEscapeHtml(omoTeamText.contact) + '</div><div class="omo-team__map-popup-meta-value omo-team__map-popup-contact-value">' + contactLines.join('') + '</div></div>');
            popupBits.push('<div class="omo-team__map-popup-meta-row"><div class="omo-team__map-popup-meta-label">' + omoTeamEscapeHtml(omoTeamText.added) + '</div><div class="omo-team__map-popup-meta-value">' + omoTeamEscapeHtml(member.joinedAtLabel || 'N/A') + '</div></div>');
            popupBits.push('<div class="omo-team__map-popup-meta-row"><div class="omo-team__map-popup-meta-label">' + omoTeamEscapeHtml(omoTeamText.lastConnection) + '</div><div class="omo-team__map-popup-meta-value">' + omoTeamEscapeHtml(member.lastSeenLabel || omoTeamText.never) + '</div></div>');
            popupBits.push('</div>');

            if (member.canViewDetail) {
                popupBits.push('<button type="button" class="omo-team__map-popup-action" data-map-popup-open-user="' + Number(member.userId) + '">' + omoTeamEscapeHtml(omoTeamText.openProfile) + '</button>');
            }

            popupBits.push('</div>');

            const marker = L.circleMarker([lat, lng], {
                radius: member.isContextAdmin ? 9 : 7,
                color: member.isContextAdmin ? '#b45309' : '#0f766e',
                weight: 2,
                fillColor: member.isContextAdmin ? '#f59e0b' : '#14b8a6',
                fillOpacity: 0.88
            });

            marker.bindPopup(popupBits.join(''));
            if (member.canViewDetail) {
                marker.on('dblclick', function () {
                    if (typeof window.omoOpenUserContextPopup === 'function') {
                        window.omoOpenUserContextPopup(Number(member.userId));
                    }
                });
            }

            marker.addTo(omoTeamLeafletLayer);
            bounds.push([lat, lng]);
    });

    if (bounds.length === 1) {
        omoTeamLeafletMap.setView(bounds[0], 13);
    } else if (bounds.length > 1) {
        omoTeamLeafletMap.fitBounds(bounds, {padding: [28, 28]});
    } else {
        omoTeamLeafletMap.setView([46.8182, 8.2275], 7);
    }

    window.setTimeout(function () {
        if (omoTeamLeafletMap) {
            omoTeamLeafletMap.invalidateSize();
        }
    }, 0);
    window.setTimeout(function () {
        if (omoTeamLeafletMap) {
            omoTeamLeafletMap.invalidateSize();
        }
    }, 250);
}

$(function () {
    const root = omoTeamGetRoot();
    if (!root) {
        return;
    }

    root.querySelectorAll('[data-team-filter-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            if (omoTeamFilterPanelOpen) {
                omoTeamCloseFilterPanel(true, false);
            } else {
                omoTeamOpenFilterPanel();
            }
        });
    });

    const panel = root.querySelector('[data-team-filter-panel]');
    if (panel) {
        panel.addEventListener('click', function (event) {
            const moreToggle = event.target.closest('[data-team-filter-more-toggle]');
            if (moreToggle) {
                event.preventDefault();
                event.stopPropagation();
                const moreMenu = moreToggle.closest('[data-team-filter-more-menu]');
                const morePanel = moreMenu ? moreMenu.querySelector('[data-team-filter-more-panel]') : null;
                const isMoreMenuOpen = !!morePanel && !morePanel.hidden;
                omoTeamCloseFilterMoreMenu();
                if (!isMoreMenuOpen && morePanel) {
                    morePanel.hidden = false;
                    moreMenu.classList.add('is-open');
                    moreToggle.setAttribute('aria-expanded', 'true');
                }
                return;
            }
            const moreAction = event.target.closest('[data-team-filter-more-action]');
            if (moreAction) {
                event.preventDefault();
                event.stopPropagation();
                omoTeamApplyFilterMoreAction(
                    moreAction.getAttribute('data-team-filter-more-action') || ''
                );
                return;
            }
            const applyButton = event.target.closest('[data-team-filter-apply]');
            if (applyButton) {
                event.preventDefault();
                omoTeamCloseFilterPanel(true, false);
                return;
            }
            const saveButton = event.target.closest('[data-team-filter-save]');
            if (saveButton) {
                event.preventDefault();
                omoTeamCloseFilterPanel(true, true);
                return;
            }
            const scopeButton = event.target.closest('[data-team-filter-scope]');
            if (scopeButton && omoTeamPendingFilters) {
                omoTeamPendingFilters.scope = omoTeamNormalizeScope(scopeButton.getAttribute('data-team-filter-scope'));
                omoTeamSyncFilterChoices();
                return;
            }
            const viewButton = event.target.closest('[data-team-filter-view]');
            if (viewButton && omoTeamPendingFilters) {
                omoTeamPendingFilters.view = omoTeamNormalizeView(viewButton.getAttribute('data-team-filter-view'));
                omoTeamSyncFilterChoices();
            }
        });
    }

    const quickSearch = root.querySelector('[data-team-quick-search]');
    if (quickSearch) {
        quickSearch.addEventListener('input', function () {
            omoTeamCurrentSearch = quickSearch.value || '';
            root.setAttribute('data-team-query', omoTeamCurrentSearch);
            omoTeamApplyQuickSearch();
        });
        quickSearch.addEventListener('search', function () {
            omoTeamCurrentSearch = quickSearch.value || '';
            root.setAttribute('data-team-query', omoTeamCurrentSearch);
            omoTeamApplyQuickSearch();
        });
    }

    root.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && omoTeamFilterPanelOpen) {
            omoTeamCloseFilterPanel(false, false);
        }
    });

    omoTeamInitializeFilters();
});

function omoCloseTeamMemberMenus() {
    $('[data-team-member-menu="1"]').each(function () {
        $(this).removeClass('is-open');
        $(this).find('[data-team-member-menu-panel="1"]').prop('hidden', true);
        $(this).find('[data-team-member-menu-toggle="1"]').attr('aria-expanded', 'false');
    });
}

$(document)
  .off('click.omoTeamMapPopupAction', '[data-map-popup-open-user]')
  .on('click.omoTeamMapPopupAction', '[data-map-popup-open-user]', function (event) {
    event.preventDefault();
    const userId = Number($(this).data('map-popup-open-user') || 0);
    if (!userId || typeof window.omoOpenUserContextPopup !== 'function') {
        return;
    }

    window.omoOpenUserContextPopup(userId);
  });

$(document)
  .off('click.omoTeamMapPopupContact', '[data-map-popup-contact-link]')
  .on('click.omoTeamMapPopupContact', '[data-map-popup-contact-link]', function (event) {
    event.stopPropagation();
  });

$(document)
  .off('click.omoTeamUserContext', '[data-open-user-context="1"]')
  .on('click.omoTeamUserContext', '[data-open-user-context="1"]', function (event) {
    if ($(event.target).closest('[data-team-member-menu="1"], [data-team-phone-link], [data-team-email-link]').length) {
        return;
    }

    const userId = Number($(this).data('user-id'));

    if (typeof window.omoOpenUserContextPopup !== 'function') {
        return;
    }

    window.omoOpenUserContextPopup(userId);
  });

$(document)
  .off('keydown.omoTeamUserContext', '[data-open-user-context="1"]')
  .on('keydown.omoTeamUserContext', '[data-open-user-context="1"]', function (event) {
    if ($(event.target).closest('[data-team-member-menu="1"], [data-team-phone-link], [data-team-email-link]').length) {
        return;
    }

    if (event.key !== 'Enter' && event.key !== ' ') {
        return;
    }

    event.preventDefault();
    $(this).trigger('click');
  });

$(document)
  .off('click.omoTeamMenuSurface', '.omo-team-card__menu')
  .on('click.omoTeamMenuSurface', '.omo-team-card__menu', function (event) {
    event.stopPropagation();
  });

function omoTeamShowEmailCopied(button) {
    if (!button) {
        return;
    }

    if (!button.dataset.teamCopyEmailOriginalHtml) {
        button.dataset.teamCopyEmailOriginalHtml = button.innerHTML;
        button.dataset.teamCopyEmailOriginalLabel = button.getAttribute('aria-label') || '';
        button.dataset.teamCopyEmailOriginalTitle = button.getAttribute('title') || '';
    }

    if (button.omoTeamCopyRestoreTimer) {
        window.clearTimeout(button.omoTeamCopyRestoreTimer);
    }

    button.textContent = omoTeamText.emailCopied;
    button.classList.add('is-copied');
    button.setAttribute('aria-label', omoTeamText.emailCopied);
    button.setAttribute('title', omoTeamText.emailCopied);
    button.omoTeamCopyRestoreTimer = window.setTimeout(function () {
        button.innerHTML = button.dataset.teamCopyEmailOriginalHtml;
        button.classList.remove('is-copied');
        button.setAttribute('aria-label', button.dataset.teamCopyEmailOriginalLabel);
        button.setAttribute('title', button.dataset.teamCopyEmailOriginalTitle);
        button.omoTeamCopyRestoreTimer = null;
    }, 2500);
}

$(document)
  .off('click.omoTeamCopyEmail', '[data-team-copy-email]')
  .on('click.omoTeamCopyEmail', '[data-team-copy-email]', function (event) {
    event.preventDefault();
    event.stopPropagation();

    const email = String($(this).attr('data-team-copy-email') || '').trim();
    if (email === '') {
        return;
    }

    const fallbackCopy = function () {
        const input = document.createElement('textarea');
        input.value = email;
        input.setAttribute('readonly', '');
        input.style.position = 'fixed';
        input.style.opacity = '0';
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        document.body.removeChild(input);
    };

    if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
        navigator.clipboard.writeText(email).catch(fallbackCopy);
    } else {
        fallbackCopy();
    }

    omoTeamShowEmailCopied(this);
  });

$(document)
  .off('click.omoTeamMenuToggle', '[data-team-member-menu-toggle="1"]')
  .on('click.omoTeamMenuToggle', '[data-team-member-menu-toggle="1"]', function (event) {
    event.preventDefault();
    event.stopPropagation();

    const menu = $(this).closest('[data-team-member-menu="1"]');
    const willOpen = !menu.hasClass('is-open');
    omoCloseTeamMemberMenus();

    if (!willOpen) {
        return;
    }

    menu.addClass('is-open');
    menu.find('[data-team-member-menu-panel="1"]').prop('hidden', false);
    menu.find('[data-team-member-menu-toggle="1"]').attr('aria-expanded', 'true');
  });

$(document)
  .off('click.omoTeamMenuOutside')
  .on('click.omoTeamMenuOutside', function (event) {
    if ($(event.target).closest('[data-team-member-menu="1"]').length) {
        return;
    }

    omoCloseTeamMemberMenus();
  });

$(document)
  .off('click.omoTeamOpenMemberPopup', '[data-team-open-member-popup="1"]')
  .on('click.omoTeamOpenMemberPopup', '[data-team-open-member-popup="1"]', function (event) {
    event.preventDefault();

    const holonId = Number($(this).data('hid') || 0);

    if (!holonId || typeof window.commonTopbarOpenModal !== 'function') {
        return;
    }

    window.commonTopbarOpenModal(
        omoTeamText.addMemberTitle,
        'api/holons/member_popup.php?hid=' + holonId,
        'fetch'
    );
  });

$(document)
  .off('click.omoTeamEditAssignment', '[data-team-edit-assignment="1"]')
  .on('click.omoTeamEditAssignment', '[data-team-edit-assignment="1"]', function (event) {
    event.preventDefault();
    event.stopPropagation();

    const userId = Number($(this).data('user-id') || 0);
    const root = omoTeamGetRoot();
    const holonId = Number(root ? root.getAttribute('data-team-cid') : 0);
    if (!userId || !holonId || typeof window.commonTopbarOpenModal !== 'function') {
        return;
    }

    const teamScope = root ? String(root.getAttribute('data-team-scope') || 'contextual') : 'contextual';
    const teamQuery = root ? String(root.getAttribute('data-team-query') || '') : '';
    const popupUrl = 'api/team/member_assignment_popup.php?hid=' + encodeURIComponent(String(holonId))
        + '&user_id=' + encodeURIComponent(String(userId))
        + '&team_scope=' + encodeURIComponent(teamScope)
        + '&team_query=' + encodeURIComponent(teamQuery);

    omoCloseTeamMemberMenus();
    window.commonTopbarOpenModal(
        window.omoTeamPageConfig.teamAssignmentPopupTitle,
        popupUrl,
        'fetch'
    );
  });

$(document)
  .off('click.omoTeamMemberAction', '[data-member-action]')
  .on('click.omoTeamMemberAction', '[data-member-action]', function (event) {
    event.preventDefault();
    event.stopPropagation();

    const button = $(this);
    const card = button.closest('.omo-team-card');
    const action = String(button.data('member-action') || '');
    const userId = Number(button.data('user-id') || card.data('user-id') || 0);
    const organizationId = window.omoTeamPageConfig.organizationId;
    const currentHolonId = window.omoTeamPageConfig.currentHolonId;
    const rootHolonId = window.omoTeamPageConfig.rootHolonId;
    const teamRoot = omoTeamGetRoot();
    const currentTeamScope = omoTeamNormalizeScope(teamRoot ? teamRoot.getAttribute('data-team-scope') : omoTeamInitialScope);
    const contextLabel = window.omoTeamPageConfig.contextLabel;
    const displayName = $.trim(card.find('.omo-team-card__identity h3').first().text()) || omoTeamText.thisMember;
    let confirmationMessage = '';

    if (!action || !userId) {
        return;
    }

    const memberActionUrl = typeof window.omoResolveAppUrl === 'function'
        ? window.omoResolveAppUrl('/omo/api/team/member_action.php')
        : '/omo/api/team/member_action.php';
    const requestMemberAction = function (requestedAction) {
        const formData = new FormData();
        formData.append('hid', String(currentHolonId));
        formData.append('oid', String(organizationId));
        formData.append('user_id', String(userId));
        formData.append('action', requestedAction);

        return fetch(memberActionUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(function (response) {
            return response.json().catch(function () {
                return null;
            }).then(function (data) {
                return {
                    ok: response.ok,
                    data: data
                };
            });
        });
    };
    const handleMemberActionResult = function (result) {
        button.prop('disabled', false);

        if (!result.ok || !result.data || !result.data.status) {
            window.omoNotify(result.data && result.data.message ? result.data.message : omoTeamText.updateFailed, 'error');
            return;
        }

        omoCloseTeamMemberMenus();

        if (typeof refreshDrawer === 'function') {
            let drawerUrl = '/omo/api/team/index.php?oid=' + organizationId;
            if (currentHolonId > 0 && currentHolonId !== rootHolonId) {
                drawerUrl += '&cid=' + currentHolonId;
            }
            drawerUrl += '&team_scope=' + encodeURIComponent(currentTeamScope);
            const currentTeamQuery = teamRoot ? String(teamRoot.getAttribute('data-team-query') || '').trim() : '';
            if (currentTeamQuery !== '') {
                drawerUrl += '&team_query=' + encodeURIComponent(currentTeamQuery);
            }
            refreshDrawer('drawer_team', drawerUrl);
        }

        if (typeof loadContent === 'function') {
            let leftUrl = 'api/getOrg.php?oid=' + organizationId;
            if (currentHolonId > 0 && currentHolonId !== rootHolonId) {
                leftUrl += '&cid=' + currentHolonId;
            }
            loadContent(typeof omoGetLeftPanelContentSelector === 'function' ? omoGetLeftPanelContentSelector() : '#panel-left', leftUrl);
        }

        if (rootHolonId > 0 && typeof window.omoReloadStructureAndFocus === 'function') {
            window.omoReloadStructureAndFocus(currentHolonId > 0 && currentHolonId !== rootHolonId ? currentHolonId : null, {
                quickZoom: true
            });
        }
    };
    const submitMemberAction = function () {
        button.prop('disabled', true);
        requestMemberAction(action)
            .then(handleMemberActionResult)
            .catch(function () {
                button.prop('disabled', false);
                window.omoNotify(omoTeamText.updateFailedLater, 'error');
            });
    };

    if (action === 'cancel_invitation') {
        confirmationMessage = omoTeamFormatText(omoTeamText.confirmCancelInvitation, {name: displayName});
    } else if (action === 'remove') {
        button.prop('disabled', true);
        requestMemberAction('remove_preview')
            .then(function (result) {
                button.prop('disabled', false);

                if (!result.ok || !result.data || !result.data.status) {
                    window.omoNotify(result.data && result.data.message ? result.data.message : omoTeamText.updateFailed, 'error');
                    return;
                }

                const removalSummary = result.data.removalSummary || {};
                const roleCount = Number(removalSummary.roleCount || 0);
                if (roleCount === 1) {
                    confirmationMessage = omoTeamFormatText(omoTeamText.confirmRemoveWithOneRole, {
                        name: displayName,
                        context: contextLabel
                    });
                } else if (roleCount > 1) {
                    confirmationMessage = omoTeamFormatText(omoTeamText.confirmRemoveWithRoles, {
                        name: displayName,
                        context: contextLabel,
                        roleCount: roleCount
                    });
                } else {
                    confirmationMessage = omoTeamFormatText(omoTeamText.confirmRemove, {name: displayName, context: contextLabel});
                }

                if (window.confirm(confirmationMessage)) {
                    submitMemberAction();
                }
            })
            .catch(function () {
                button.prop('disabled', false);
                window.omoNotify(omoTeamText.updateFailedLater, 'error');
            });
        return;
    } else if (action === 'grant_admin') {
        confirmationMessage = omoTeamFormatText(omoTeamText.confirmGrantAdmin, {name: displayName, context: contextLabel});
    } else if (action === 'revoke_admin') {
        confirmationMessage = omoTeamFormatText(omoTeamText.confirmRevokeAdmin, {name: displayName, context: contextLabel});
    } else {
        return;
    }

    if (!window.confirm(confirmationMessage)) {
        return;
    }

    submitMemberAction();
  });

