(function () {
    var root = typeof window.omoFindApplicationRoot === 'function'
        ? window.omoFindApplicationRoot('omo-policy-root')
        : document.getElementById('omo-policy-root');
    if (!root || root.dataset.ready === '1') return;
    root.dataset.ready = '1';
    var drawer = root.querySelector('[data-policy-drawer]');
    var body = root.querySelector('[data-policy-drawer-body]');
    var drawerTitle = root.querySelector('.omo-overlay-drawer__title');
    var drawerDescription = root.querySelector('.omo-overlay-drawer__description');
    var filterControl = root.querySelector('[data-policy-filter-control]');
    var filterPanel = root.querySelector('[data-policy-filter-panel]');
    var quickSearchInput = root.querySelector('[data-policy-quick-search]');
    var quickSearchEmpty = root.querySelector('[data-policy-search-empty]');
    var pendingView = null;
    var filterPanelIsOpen = false;
    var savedViewsStorageKey = 'omo.policy.saved-views.v2';
    var legacySavedViewsStorageKey = 'omo.policy.saved-views.v1';
    var temporaryViewsStorageKey = 'omo.policy.session-views.v1';
    var currentQuickSearch = '';
    var refreshRoot = function (url) {
        if (typeof window.omoReplaceFetchedPanelRoot !== 'function') {
            window.commonNotify(root.dataset.policyLoadError, 'error');
            return;
        }
        window.omoReplaceFetchedPanelRoot({
            rootSelector: '#omo-policy-root',
            currentRoot: root,
            url: url,
            setLoadingState: function (isLoading) {
                root.classList.toggle('is-loading', Boolean(isLoading));
                if (typeof window.omoSetPanelResultsLoadingSkeleton === 'function') {
                    window.omoSetPanelResultsLoadingSkeleton(root, isLoading, {
                        contentSelector: '.omo-policy__body'
                    });
                }
            }
        }).catch(function () {
            window.commonNotify(root.dataset.policyLoadError, 'error');
        });
    };
    var policyViewUrl = function (view) {
        var url = root.dataset.policyIndexUrl;
        url += '&policy_scope=' + encodeURIComponent(view.scope);
        if (view.sort !== 'alpha') url += '&policy_sort=' + encodeURIComponent(view.sort);
        if (view.group !== 'holon') url += '&policy_group=' + encodeURIComponent(view.group);
        return url;
    };
    var policyPreferenceKey = function () {
        var regularKey = String(root.dataset.policyOid || '0') + ':' + String(root.dataset.policyCid || '0');
        return typeof window.omoApplicationViewPreferencesGetStorageContextKey === 'function'
            ? window.omoApplicationViewPreferencesGetStorageContextKey(root, regularKey)
            : regularKey;
    };
    var normalizeView = function (view) {
        view = view && typeof view === 'object' ? view : {};
        var scope = view.scope === 'children' || view.scope === 'descendants' ? view.scope : 'contextual';
        if (!filterPanel || !filterPanel.querySelector('[data-policy-scope-choice="' + scope + '"]')) scope = root.dataset.policyScope || 'contextual';
        var sort = view.sort === 'created' || view.sort === 'updated' ? view.sort : 'alpha';
        var group = view.group === 'authority' ? 'authority' : (view.group === 'none' ? 'none' : 'holon');
        return {scope: scope, sort: sort, group: group};
    };
    var currentView = function () {
        return normalizeView({
            scope: root.dataset.policyScope || 'contextual',
            sort: root.dataset.policySort || 'alpha',
            group: root.dataset.policyGroup || 'holon'
        });
    };
    var viewsMatch = function (left, right) {
        return left.scope === right.scope && left.sort === right.sort && left.group === right.group;
    };
    var getSavedViewsStore = function () {
        try {
            var storedValue = window.localStorage.getItem(savedViewsStorageKey);
            var savedViews = storedValue ? JSON.parse(storedValue) : null;
            if (savedViews && typeof savedViews === 'object' && savedViews.contexts && typeof savedViews.contexts === 'object') {
                return {
                    defaultView: savedViews.defaultView && typeof savedViews.defaultView === 'object'
                        ? savedViews.defaultView
                        : null,
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
    };
    var saveSavedViewsStore = function (store) {
        try {
            window.localStorage.setItem(savedViewsStorageKey, JSON.stringify({
                defaultView: store.defaultView && typeof store.defaultView === 'object'
                    ? store.defaultView
                    : null,
                contexts: store.contexts && typeof store.contexts === 'object' ? store.contexts : {}
            }));
        } catch (error) {
            // Storage can be unavailable in private or restricted browsing contexts.
        }
    };
    var getStoredPreference = function () {
        var preference = getSavedViewsStore().contexts[policyPreferenceKey()];
        return preference && typeof preference === 'object' ? preference : null;
    };
    var getDefaultPreference = function () {
        return getSavedViewsStore().defaultView;
    };
    var storePreference = function (view) {
        var store = getSavedViewsStore();
        store.contexts[policyPreferenceKey()] = normalizeView(view);
        saveSavedViewsStore(store);
    };
    var storeDefaultPreference = function (view) {
        var store = getSavedViewsStore();
        store.defaultView = normalizeView(view);
        saveSavedViewsStore(store);
    };
    var clearStoredPreference = function () {
        var store = getSavedViewsStore();
        delete store.contexts[policyPreferenceKey()];
        saveSavedViewsStore(store);
    };
    var readPreference = function (storage) {
        try {
            var value = storage.getItem(storage === window.localStorage ? savedViewsStorageKey : temporaryViewsStorageKey);
            var preferences = value ? JSON.parse(value) : null;
            return preferences && typeof preferences === 'object' ? preferences[policyPreferenceKey()] : null;
        } catch (error) {
            return null;
        }
    };
    var writePreference = function (storage, view) {
        try {
            var key = storage === window.localStorage ? savedViewsStorageKey : temporaryViewsStorageKey;
            var value = storage.getItem(key);
            var preferences = value ? JSON.parse(value) : {};
            if (!preferences || typeof preferences !== 'object') preferences = {};
            preferences[policyPreferenceKey()] = normalizeView(view);
            storage.setItem(key, JSON.stringify(preferences));
        } catch (error) {
            // Storage can be unavailable in private or restricted browsing contexts.
        }
    };
    var clearTemporaryPreference = function () {
        try {
            var value = window.sessionStorage.getItem(temporaryViewsStorageKey);
            var preferences = value ? JSON.parse(value) : null;
            if (!preferences || typeof preferences !== 'object') return;
            delete preferences[policyPreferenceKey()];
            window.sessionStorage.setItem(temporaryViewsStorageKey, JSON.stringify(preferences));
        } catch (error) {
            // Storage can be unavailable in private or restricted browsing contexts.
        }
    };
    var clearAllTemporaryPreferences = function () {
        try {
            window.sessionStorage.removeItem(temporaryViewsStorageKey);
        } catch (error) {
            // Storage can be unavailable in private or restricted browsing contexts.
        }
    };
    var syncPolicyGroupStickyOffsets = function () {
        var titles = root.querySelectorAll('.omo-policy__rule-group-title');
        titles.forEach(function (title) {
            var offset = 0;
            var depth = 0;
            var group = title.closest('[data-policy-rule-group]');
            var parent = group ? group.parentElement : null;
            while (parent) {
                var ancestorGroup = parent.closest('[data-policy-rule-group]');
                if (!ancestorGroup) break;
                var ancestorTitle = ancestorGroup.querySelector(':scope > .omo-policy__rule-group-title');
                if (ancestorTitle && !ancestorGroup.classList.contains('is-filter-hidden')) {
                    offset += Math.ceil(ancestorTitle.getBoundingClientRect().height);
                    depth++;
                }
                parent = ancestorGroup.parentElement;
            }
            title.style.setProperty('--omo-policy-group-top', offset + 'px');
            title.style.setProperty('--omo-policy-group-z', String(40 - depth));
        });
    };
    var syncViewChoices = function () {
        if (!filterPanel || pendingView === null) return;
        pendingView = normalizeView(pendingView);
        filterPanel.querySelectorAll('[data-policy-scope-choice]').forEach(function (button) {
            var active = button.getAttribute('data-policy-scope-choice') === pendingView.scope;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
        filterPanel.querySelectorAll('[data-policy-sort-choice]').forEach(function (button) {
            var active = button.getAttribute('data-policy-sort-choice') === pendingView.sort;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
        filterPanel.querySelectorAll('[data-policy-group-choice]').forEach(function (button) {
            var active = button.getAttribute('data-policy-group-choice') === pendingView.group;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    };
    var removeFilterOutsideHandler = function () {
        document.removeEventListener('pointerdown', handleFilterOutsidePointerDown, true);
    };
    var applyView = function (view) {
        var nextView = normalizeView(view);
        if (!viewsMatch(nextView, currentView())) refreshRoot(policyViewUrl(nextView));
    };
    var closeFilterMoreMenu = function () {
        root.querySelectorAll('[data-policy-filter-more-menu]').forEach(function (menu) {
            var panel = menu.querySelector('[data-policy-filter-more-panel]');
            var toggle = menu.querySelector('[data-policy-filter-more-toggle]');
            if (panel) panel.hidden = true;
            menu.classList.remove('is-open');
            if (toggle) toggle.setAttribute('aria-expanded', 'false');
        });
    };
    var closeFilterPanel = function (applyChanges, saveView) {
        if (!filterPanelIsOpen) return;
        filterPanelIsOpen = false;
        filterPanel.classList.add('is-filter-hidden');
        root.querySelectorAll('[data-policy-filter-toggle]').forEach(function (button) { button.setAttribute('aria-expanded', 'false'); });
        removeFilterOutsideHandler();
        closeFilterMoreMenu();
        if (!applyChanges || pendingView === null) {
            pendingView = null;
            return;
        }
        var nextView = normalizeView(pendingView);
        pendingView = null;
        if (saveView) {
            storePreference(nextView);
            clearTemporaryPreference();
        } else {
            writePreference(window.sessionStorage, nextView);
        }
        applyView(nextView);
    };
    var applyFilterMoreAction = function (action) {
        if (!filterPanelIsOpen || pendingView === null) return;
        var nextView = normalizeView(pendingView);
        closeFilterPanel(false, false);

        if (action === 'set-default') {
            clearStoredPreference();
            clearTemporaryPreference();
            storeDefaultPreference(nextView);
            applyView(nextView);
            return;
        }
        if (action === 'apply-everywhere') {
            var store = getSavedViewsStore();
            store.defaultView = normalizeView(nextView);
            store.contexts = {};
            saveSavedViewsStore(store);
            clearAllTemporaryPreferences();
            applyView(nextView);
            return;
        }
        if (action === 'restore-default') {
            clearStoredPreference();
            clearTemporaryPreference();
            var store = getSavedViewsStore();
            store.defaultView = null;
            saveSavedViewsStore(store);
            var serverDefault = typeof window.omoApplicationViewPreferencesGetDefault === 'function'
                ? window.omoApplicationViewPreferencesGetDefault(root)
                : null;
            applyView(serverDefault || {
                scope: 'contextual',
                sort: 'alpha',
                group: 'holon'
            });
        }
    };
    var handleFilterOutsidePointerDown = function (event) {
        if (!filterControl || filterControl.contains(event.target)) return;
        closeFilterPanel(true, false);
    };
    var openFilterPanel = function () {
        if (!filterControl || !filterPanel || filterPanelIsOpen) return;
        pendingView = currentView();
        closeFilterMoreMenu();
        syncViewChoices();
        filterPanel.classList.remove('is-filter-hidden');
        filterPanelIsOpen = true;
        root.querySelectorAll('[data-policy-filter-toggle]').forEach(function (button) { button.setAttribute('aria-expanded', 'true'); });
        document.addEventListener('pointerdown', handleFilterOutsidePointerDown, true);
    };
    var normalizeQuickSearch = function (value) {
        return String(value || '').toLocaleLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
    };
    var applyQuickSearch = function () {
        var query = normalizeQuickSearch(currentQuickSearch);
        var visibleCount = 0;
        root.querySelectorAll('[data-policy-rule-card]').forEach(function (ruleCard) {
            var searchableText = ruleCard.getAttribute('data-policy-rule-search') || ruleCard.textContent || '';
            var matches = query === '' || normalizeQuickSearch(searchableText).indexOf(query) !== -1;
            ruleCard.classList.toggle('is-filter-hidden', !matches);
            if (matches) visibleCount++;
        });
        root.querySelectorAll('[data-policy-rule-group]').forEach(function (group) {
            group.classList.toggle('is-filter-hidden', !group.querySelector('[data-policy-rule-card]:not(.is-filter-hidden)'));
        });
        syncPolicyGroupStickyOffsets();
        if (quickSearchEmpty) quickSearchEmpty.classList.toggle('is-filter-hidden', query === '' || visibleCount > 0);
    };
    root.querySelectorAll('[data-policy-filter-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            if (filterPanelIsOpen) closeFilterPanel(true, false); else openFilterPanel();
        });
    });
    if (filterPanel) {
        filterPanel.addEventListener('click', function (event) {
            var moreToggle = event.target.closest('[data-policy-filter-more-toggle]');
            if (moreToggle) {
                event.preventDefault();
                event.stopPropagation();
                var moreMenu = moreToggle.closest('[data-policy-filter-more-menu]');
                var morePanel = moreMenu ? moreMenu.querySelector('[data-policy-filter-more-panel]') : null;
                var isMoreMenuOpen = !!morePanel && !morePanel.hidden;
                closeFilterMoreMenu();
                if (!isMoreMenuOpen && morePanel) {
                    morePanel.hidden = false;
                    moreMenu.classList.add('is-open');
                    moreToggle.setAttribute('aria-expanded', 'true');
                }
                return;
            }
            var moreAction = event.target.closest('[data-policy-filter-more-action]');
            if (moreAction) {
                event.preventDefault();
                event.stopPropagation();
                applyFilterMoreAction(moreAction.getAttribute('data-policy-filter-more-action') || '');
                return;
            }
            if (event.target.closest('[data-policy-filter-apply]')) {
                closeFilterPanel(true, false);
                return;
            }
            if (event.target.closest('[data-policy-filter-save]')) {
                closeFilterPanel(true, true);
                return;
            }
            var scopeButton = event.target.closest('[data-policy-scope-choice]');
            if (scopeButton) {
                pendingView.scope = scopeButton.getAttribute('data-policy-scope-choice') || 'contextual';
                syncViewChoices();
                return;
            }
            var sortButton = event.target.closest('[data-policy-sort-choice]');
            if (sortButton) {
                pendingView.sort = sortButton.getAttribute('data-policy-sort-choice') || 'alpha';
                syncViewChoices();
                return;
            }
            var groupButton = event.target.closest('[data-policy-group-choice]');
            if (groupButton) {
                pendingView.group = groupButton.getAttribute('data-policy-group-choice') || 'holon';
                syncViewChoices();
            }
        });
        var serverDefault = typeof window.omoApplicationViewPreferencesGetDefault === 'function'
            ? window.omoApplicationViewPreferencesGetDefault(root)
            : null;
        var personalView = typeof window.omoApplicationViewPreferencesGetPersonal === 'function'
            ? window.omoApplicationViewPreferencesGetPersonal(root)
            : null;
        var canUseLegacyPersonal = typeof window.omoApplicationViewPreferencesCanUseLegacyPersonal === 'function'
            && window.omoApplicationViewPreferencesCanUseLegacyPersonal(root);
        var preferredView = readPreference(window.sessionStorage)
            || personalView
            || serverDefault
            || (canUseLegacyPersonal ? getStoredPreference() : null)
            || (canUseLegacyPersonal ? getDefaultPreference() : null);
        if (preferredView && !viewsMatch(normalizeView(preferredView), currentView())) {
            refreshRoot(policyViewUrl(normalizeView(preferredView)));
        }
    }
    if (quickSearchInput) {
        quickSearchInput.addEventListener('input', function () {
            currentQuickSearch = quickSearchInput.value || '';
            applyQuickSearch();
        });
        quickSearchInput.addEventListener('search', function () {
            currentQuickSearch = quickSearchInput.value || '';
            applyQuickSearch();
        });
    }
    window.requestAnimationFrame(syncPolicyGroupStickyOffsets);
    window.addEventListener('resize', syncPolicyGroupStickyOffsets);
    var closeRuleMenus = function (exceptMenu) {
        root.querySelectorAll('[data-policy-rule-menu]').forEach(function (menu) {
            if (menu === exceptMenu) return;
            var panel = menu.querySelector('[data-policy-rule-menu-panel]');
            var toggle = menu.querySelector('[data-policy-rule-menu-toggle]');
            if (panel) panel.hidden = true;
            menu.classList.remove('is-open');
            var card = menu.closest('[data-policy-rule-card]');
            if (card) card.classList.remove('is-menu-open');
            if (toggle) toggle.setAttribute('aria-expanded', 'false');
        });
    };
    root.addEventListener('click', function (event) {
        var toggle = event.target.closest('[data-policy-rule-menu-toggle]');
        if (toggle && root.contains(toggle)) {
            var menu = toggle.closest('[data-policy-rule-menu]');
            var panel = menu ? menu.querySelector('[data-policy-rule-menu-panel]') : null;
            var isOpen = !!panel && !panel.hidden;
            event.preventDefault();
            event.stopPropagation();
            closeRuleMenus(menu);
            if (panel) panel.hidden = isOpen;
            if (menu) menu.classList.toggle('is-open', !isOpen);
            var card = menu ? menu.closest('[data-policy-rule-card]') : null;
            if (card) card.classList.toggle('is-menu-open', !isOpen);
            toggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
            return;
        }

        var editButton = event.target.closest('[data-policy-rule-edit]');
        if (editButton && root.contains(editButton)) {
            event.preventDefault();
            event.stopPropagation();
            closeRuleMenus();
            openPolicyDrawer(editButton.getAttribute('data-policy-edit-url') || '');
            return;
        }

        var deleteButton = event.target.closest('[data-policy-rule-delete]');
        if (deleteButton && root.contains(deleteButton)) {
            event.preventDefault();
            event.stopPropagation();
            closeRuleMenus();

            var ruleId = deleteButton.getAttribute('data-policy-rule-id') || '';
            var confirmation = root.dataset.policyDeleteConfirm || '';
            if (!ruleId || (confirmation !== '' && !window.confirm(confirmation))) return;

            deleteButton.disabled = true;
            var payload = new FormData();
            payload.append('oid', root.dataset.policyOid || '0');
            payload.append('cid', root.dataset.policyCid || '0');
            payload.append('rule_id', ruleId);
            payload.append('action', 'delete');

            fetch('/omo/api/policy/action.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'X-Requested-With': 'XMLHttpRequest'},
                body: payload
            }).then(function (response) {
                return response.json().then(function (data) {
                    return {ok: response.ok, data: data};
                });
            }).then(function (result) {
                if (!result.ok || !result.data || !result.data.success) {
                    throw new Error(result.data && result.data.message ? result.data.message : (root.dataset.policyDeleteError || ''));
                }
                afterChange(result.data.message);
            }).catch(function (error) {
                deleteButton.disabled = false;
                window.commonNotify(error && error.message ? error.message : root.dataset.policyDeleteError, 'error');
            });
            return;
        }

        if (!event.target.closest('[data-policy-rule-menu]')) closeRuleMenus();
    });
    var close = function () { drawer.classList.remove('is-open'); window.setTimeout(function () { if (!drawer.classList.contains('is-open')) { drawer.hidden = true; body.innerHTML = ''; } }, 220); };
    var refreshRulesList = function () {
        var url = policyViewUrl(currentView());
        if (typeof window.omoResolveAppUrl === 'function') url = window.omoResolveAppUrl(url);
        return fetch(url, {
            credentials: 'same-origin',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            cache: 'no-store'
        }).then(function (response) {
            if (!response.ok) throw new Error(root.dataset.policyLoadError);
            return response.text();
        }).then(function (html) {
            var fragment = document.createElement('div');
            fragment.innerHTML = html;
            var nextRoot = fragment.querySelector('#omo-policy-root');
            var nextBody = nextRoot && nextRoot.querySelector('.omo-policy__body');
            var nextCount = nextRoot && nextRoot.querySelector('.omo-panel-view__count');
            var listBody = root.querySelector('.omo-policy__body');
            var count = root.querySelector('.omo-panel-view__count');
            if (!nextBody || !nextCount || !listBody || !count) throw new Error(root.dataset.policyLoadError);
            if (!root.isConnected) return;
            listBody.innerHTML = nextBody.innerHTML;
            count.textContent = nextCount.textContent;
            quickSearchEmpty = root.querySelector('[data-policy-search-empty]');
            applyQuickSearch();
        });
    };
    var afterChange = function (message) {
        window.commonNotify(message, 'success');
        close();
        refreshRulesList().catch(function (error) {
            window.commonNotify(error && error.message ? error.message : root.dataset.policyLoadError, 'error');
        });
    };
    var mountPolicyHtmlFields = function (scope) {
        if (!scope || !window.omoSimpleHtmlField || typeof window.omoSimpleHtmlField.mount !== 'function') return;
        scope.querySelectorAll('[data-policy-html-field]').forEach(function (host) {
            var input = host.parentElement ? host.parentElement.querySelector('[data-policy-html-input]') : null;
            if (!input) return;
            window.omoSimpleHtmlField.mount(host, {
                value: input.value || '',
                placeholder: 'Saisissez le contenu de la regle...',
                simpleOnly: true,
                onChange: function (value) { input.value = String(value || ''); }
            });
        });
    };
    var openPolicyDrawer = function (url) {
        if (!url) return;
        closeRuleMenus();
        drawer.hidden = false;
        window.requestAnimationFrame(function () { drawer.classList.add('is-open'); });
        body.textContent = '...';
        fetch(url, {credentials: 'same-origin'}).then(function (response) {
            if (!response.ok) throw new Error('load_failed');
            return response.text();
        }).then(function (html) {
            body.innerHTML = html;
            var form = body.querySelector('[data-policy-form]');
            if (!form) throw new Error(root.dataset.policyLoadError);
            if (drawerTitle) drawerTitle.textContent = form.getAttribute('data-policy-form-title') || omoPolicyDefaultDrawerTitle;
            if (drawerDescription) drawerDescription.textContent = form.getAttribute('data-policy-form-description') || omoPolicyDefaultDrawerDescription;
            mountPolicyHtmlFields(form);
            if (typeof window.initGenericComponents === 'function') window.initGenericComponents(body);
        }).catch(function () { window.commonNotify(root.dataset.policyLoadError, 'error'); close(); });
    };
    var omoPolicyDefaultDrawerTitle = drawerTitle ? drawerTitle.textContent : '';
    var omoPolicyDefaultDrawerDescription = drawerDescription ? drawerDescription.textContent : '';
    root.querySelectorAll('[data-policy-close]').forEach(function (button) { button.addEventListener('click', close); });
    var create = root.querySelector('[data-policy-new]');
    if (create) create.addEventListener('click', function () { openPolicyDrawer(root.dataset.policyCreateUrl || ''); });
    body.addEventListener('submit', function (event) {
        var form = event.target.closest('[data-policy-form]');
        if (!form) return;
        event.preventDefault();
        if (!form.reportValidity()) return;
        var formData = new FormData(form);
        var usesSharedPendingState = typeof window.omoBeginPendingAction === 'function';
        var submitButton = form.querySelector('[type="submit"]');
        if (usesSharedPendingState && !window.omoBeginPendingAction(form)) return;
        if (!usesSharedPendingState && submitButton) submitButton.disabled = true;
        fetch(form.action, {method: 'POST', credentials: 'same-origin', headers: {'X-Requested-With': 'XMLHttpRequest'}, body: formData})
            .then(function (response) { return response.json().then(function (payload) { return {ok: response.ok, payload: payload}; }); })
            .then(function (result) {
                if (!result.ok || !result.payload || !result.payload.success) throw new Error(result.payload && result.payload.message ? result.payload.message : root.dataset.policySaveError);
                afterChange(result.payload.message);
            })
            .catch(function (error) {
                window.commonNotify(error && error.message ? error.message : root.dataset.policySaveError, 'error');
            })
            .finally(function () {
                if (usesSharedPendingState && typeof window.omoEndPendingAction === 'function') window.omoEndPendingAction(form);
                else if (submitButton) submitButton.disabled = false;
            });
    });
})();
