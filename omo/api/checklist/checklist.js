(function (window, document) {
    'use strict';

    var root = typeof window.omoFindApplicationRoot === 'function'
        ? window.omoFindApplicationRoot('omo-checklist-root')
        : document.getElementById('omo-checklist-root');
    if (!root || root.dataset.checklistReady === '1') {
        return;
    }
    root.dataset.checklistReady = '1';

    var useLocalDrawerNavigation = typeof window.omoIsPvApplicationTabContext === 'function'
        && window.omoIsPvApplicationTabContext(root);

    var drawer = root.querySelector('[data-checklist-drawer]');
    var drawerBody = root.querySelector('[data-checklist-drawer-body]');
    var drawerController = drawer && typeof window.omoCreateSubdrawerController === 'function'
        ? window.omoCreateSubdrawerController({drawer: drawer})
        : null;
    var currentUrl = root.getAttribute('data-checklist-current-url') || '';
    var createUrl = root.getAttribute('data-checklist-create-url') || '';
    var detailUrl = root.getAttribute('data-checklist-detail-url') || '';
    var initialOpenChecklistId = Number(root.getAttribute('data-checklist-open-checklist-id') || 0);
    var requestToken = 0;
    var rootNeedsRefresh = false;
    var currentScope = normalizeScope(root.getAttribute('data-checklist-scope') || 'contextual');
    var currentSearch = '';
    var pendingFilters = null;
    var filterPanelOpen = false;
    var checklistSavedViewsStorageKey = 'omo.checklist.saved-views.v1';
    var checklistSessionViewsStorageKey = 'omo.checklist.session-views.v1';
    var checklistSearchStorageKey = 'omo.checklist.quick-search.v1';
    var checklistListFloatingMenu = null;
    var activeChecklistListMenuToggle = null;
    var texts = {
        loading: 'Chargement de la checklist...',
        loadingError: 'Impossible de charger cette checklist.'
    };

    try {
        texts = Object.assign(texts, JSON.parse(root.getAttribute('data-checklist-texts') || '{}'));
    } catch (error) {
        // Keep local fallbacks when translated texts are unavailable.
    }

    function resolveUrl(url) {
        return typeof window.omoResolveAppUrl === 'function' ? window.omoResolveAppUrl(url) : url;
    }

    function setRootLoading(loading) {
        root.classList.toggle('is-loading', Boolean(loading));
        root.querySelectorAll('[data-checklist-filter-toggle], [data-checklist-scope-option], [data-checklist-filter-apply], [data-checklist-filter-save]').forEach(function (button) {
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
            rootSelector: '#omo-checklist-root',
            currentRoot: root,
            url: resolveUrl(targetUrl),
            setLoadingState: setRootLoading,
            beforeReplace: function () {
                window.removeEventListener('omo-runtime-maintenance', handleRuntimeMaintenance);
                window.removeEventListener('omo-checklist-route-change', handleChecklistRouteChange);
            }
        });
    }

    function setDrawerMessage(message, isError) {
        if (!drawerBody) {
            return;
        }
        if (drawerController) {
            drawerController.resetHeader();
        }
        drawerBody.innerHTML = '<div class="generic-section omo-checklist-feedback' + (isError ? ' is-error' : '') + '"></div>';
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
        refreshRoot(currentUrl);
    }

    function openDrawerWithUrl(url) {
        if (!drawer || !drawerBody || !url) {
            return Promise.resolve(false);
        }
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
            initializeForms(drawerBody);
            initializeChecklistItemMenus(drawerBody);
            return true;
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
        requestToken++;
        drawer.classList.remove('is-open');
        window.setTimeout(function () {
            if (drawer.classList.contains('is-open')) {
                return;
            }
            drawer.hidden = true;
            if (drawerBody) {
                drawerBody.innerHTML = '';
            }
            if (drawerController) {
                drawerController.resetHeader();
            }
            if (rootNeedsRefresh) {
                rootNeedsRefresh = false;
                refreshRoot(currentUrl);
            }
        }, 180);
    }

    function buildScopeUrl(scope) {
        var organizationId = Number(root.getAttribute('data-checklist-oid') || 0);
        var routeCid = Number(root.getAttribute('data-checklist-route-cid') || 0);
        var normalizedScope = ['children', 'descendants'].indexOf(scope) !== -1 ? scope : 'contextual';
        var query = ['oid=' + encodeURIComponent(String(organizationId))];
        if (routeCid > 0) {
            query.push('cid=' + encodeURIComponent(String(routeCid)));
        }
        if (normalizedScope !== 'contextual') {
            query.push('checklist_scope=' + encodeURIComponent(normalizedScope));
        }
        return '/omo/api/checklist/index.php?' + query.join('&');
    }

    function normalizeScope(scope) {
        scope = String(scope || '').trim().toLowerCase();
        return scope === 'children' || scope === 'descendants' ? scope : 'contextual';
    }

    function getPreferencesContextKey() {
        var regularKey = String(root.getAttribute('data-checklist-oid') || '0')
            + ':' + String(root.getAttribute('data-checklist-route-cid') || '0');
        return typeof window.omoApplicationViewPreferencesGetStorageContextKey === 'function'
            ? window.omoApplicationViewPreferencesGetStorageContextKey(root, regularKey)
            : regularKey;
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
        }
    }

    function clearTemporaryFilters() {
        clearStoredValue(window.sessionStorage, checklistSessionViewsStorageKey);
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
        }
    }

    function normalizeSearch(value) {
        return String(value || '').toLocaleLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
    }

    function applyQuickSearch() {
        var query = normalizeSearch(currentSearch);
        var visibleCount = 0;
        root.querySelectorAll('[data-checklist-search-item]').forEach(function (item) {
            var matches = query === '' || normalizeSearch(item.textContent || '').indexOf(query) !== -1;
            item.hidden = !matches;
            if (matches) {
                visibleCount++;
            }
        });
        root.querySelectorAll('[data-checklist-default-empty]').forEach(function (empty) {
            empty.hidden = query !== '';
        });
        var searchEmpty = root.querySelector('[data-checklist-search-empty]');
        if (searchEmpty) {
            searchEmpty.hidden = query === '' || visibleCount > 0;
        }
        var count = root.querySelector('[data-checklist-header-count]');
        if (count) {
            count.textContent = query === ''
                ? String(count.getAttribute('data-checklist-total-count') || '0')
                : String(visibleCount);
        }
    }

    function syncFilterChoices() {
        if (!pendingFilters) {
            return;
        }
        pendingFilters.scope = normalizeScope(pendingFilters.scope);
        if (!root.querySelector('[data-checklist-scope-option="' + pendingFilters.scope + '"]')) {
            pendingFilters.scope = currentScope;
        }
        root.querySelectorAll('[data-checklist-scope-option]').forEach(function (button) {
            var active = normalizeScope(button.getAttribute('data-checklist-scope-option')) === pendingFilters.scope;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    }

    function syncFilterChip() {
        var chip = root.querySelector('[data-checklist-scope-chip]');
        var button = root.querySelector('[data-checklist-scope-option="' + currentScope + '"]');
        if (chip && button) {
            chip.textContent = button.textContent.trim();
        }
    }

    function closeFilterPanel(applyChanges, saveView) {
        var panel = root.querySelector('[data-checklist-filter-panel]');
        if (!filterPanelOpen) {
            return;
        }
        filterPanelOpen = false;
        if (panel) {
            panel.hidden = true;
        }
        root.querySelectorAll('[data-checklist-filter-toggle]').forEach(function (button) {
            button.setAttribute('aria-expanded', 'false');
        });
        document.removeEventListener('pointerdown', handleFilterOutsidePointerDown, true);

        if (!applyChanges || !pendingFilters) {
            pendingFilters = null;
            return;
        }

        var nextScope = normalizeScope(pendingFilters.scope);
        pendingFilters = null;
        if (saveView) {
            writeStoredValue(window.localStorage, checklistSavedViewsStorageKey, {scope: nextScope});
            clearTemporaryFilters();
        } else {
            writeStoredValue(window.sessionStorage, checklistSessionViewsStorageKey, {scope: nextScope});
        }
        if (nextScope !== currentScope) {
            currentScope = nextScope;
            refreshRoot(buildScopeUrl(nextScope)).catch(function () {
                root.removeAttribute('data-omo-view-filter-pending');
                root.removeAttribute('aria-busy');
            });
            return;
        }
        syncFilterChip();
    }

    function handleFilterOutsidePointerDown(event) {
        var control = root.querySelector('[data-checklist-filter-control]');
        if (control && control.contains(event.target)) {
            return;
        }
        closeFilterPanel(true, false);
    }

    function openFilterPanel() {
        var panel = root.querySelector('[data-checklist-filter-panel]');
        if (!panel || filterPanelOpen) {
            return;
        }
        pendingFilters = {scope: currentScope};
        syncFilterChoices();
        panel.hidden = false;
        filterPanelOpen = true;
        root.querySelectorAll('[data-checklist-filter-toggle]').forEach(function (button) {
            button.setAttribute('aria-expanded', 'true');
        });
        document.addEventListener('pointerdown', handleFilterOutsidePointerDown, true);
    }

    function initializeViewFilter() {
        currentSearch = String(readStoredValue(window.sessionStorage, checklistSearchStorageKey) || '');
        var search = root.querySelector('[data-checklist-quick-search]');
        if (search) {
            search.value = currentSearch;
        }
        var temporary = readStoredValue(window.sessionStorage, checklistSessionViewsStorageKey);
        var saved = readStoredValue(window.localStorage, checklistSavedViewsStorageKey);
        var serverDefault = typeof window.omoApplicationViewPreferencesGetDefault === 'function'
            ? window.omoApplicationViewPreferencesGetDefault(root)
            : null;
        var personalView = typeof window.omoApplicationViewPreferencesGetPersonal === 'function'
            ? window.omoApplicationViewPreferencesGetPersonal(root)
            : null;
        var preferredScope = normalizeScope(initialOpenChecklistId > 0
            ? currentScope
            : (temporary && temporary.scope) || (personalView && personalView.scope) || (serverDefault && serverDefault.scope) || (saved && saved.scope) || currentScope);
        if (!root.querySelector('[data-checklist-scope-option="' + preferredScope + '"]')) {
            preferredScope = currentScope;
        }
        if (preferredScope !== currentScope) {
            currentScope = preferredScope;
            refreshRoot(buildScopeUrl(preferredScope)).catch(function () {
                root.removeAttribute('data-omo-view-filter-pending');
                root.removeAttribute('aria-busy');
            });
            return;
        }
        syncFilterChip();
        applyQuickSearch();
        root.removeAttribute('data-omo-view-filter-pending');
        root.removeAttribute('aria-busy');
    }

    function buildChecklistRouteToken(checklistId) {
        if (typeof window.omoBuildChecklistRouteToken === 'function') {
            return window.omoBuildChecklistRouteToken(checklistId);
        }
        var resolvedChecklistId = Number(checklistId || 0);
        return Number.isInteger(resolvedChecklistId) && resolvedChecklistId > 0
            ? 'processus-c' + String(resolvedChecklistId)
            : '';
    }

    function getCurrentRouteToken() {
        if (useLocalDrawerNavigation) {
            return '';
        }
        if (typeof window.omoParsePopupHashState !== 'function') {
            return '';
        }
        var hashState = window.omoParsePopupHashState();
        return hashState && hashState.routeToken ? String(hashState.routeToken) : '';
    }

    function isChecklistDetailRoute(routeToken) {
        return /^(?:processus|checklist)-c\d+$/i.test(String(routeToken || ''));
    }

    function buildDetailUrl(checklistId) {
        var resolvedChecklistId = Number(checklistId || 0);
        if (!Number.isInteger(resolvedChecklistId) || resolvedChecklistId <= 0) {
            return '';
        }
        return detailUrl + '&id=' + encodeURIComponent(String(resolvedChecklistId));
    }

    function navigateChecklistDetail(checklistId, fallbackUrl) {
        var routeToken = buildChecklistRouteToken(checklistId);
        if (!useLocalDrawerNavigation && routeToken && typeof window.omoOpenDrawerHashState === 'function' && routeToken !== getCurrentRouteToken()) {
            window.omoOpenDrawerHashState(routeToken);
            return;
        }
        openDrawerWithUrl(fallbackUrl || buildDetailUrl(checklistId));
    }

    function updateRecurrenceFields(form, keepSelected) {
        var triggerType = form.querySelector('[data-checklist-trigger-type]');
        var frequency = form.querySelector('[data-checklist-frequency]');
        var schedule = form.querySelector('[data-checklist-schedule]');
        var isScheduled = triggerType && triggerType.value === 'scheduled';
        var isContainer = triggerType && triggerType.value === 'container';
        form.querySelectorAll('[data-checklist-recurrence-field]').forEach(function (field) {
            field.hidden = !isScheduled;
        });
        form.querySelectorAll('[data-checklist-overlap-field]').forEach(function (field) {
            field.hidden = Boolean(isContainer);
        });
        if (!schedule || !frequency) {
            return;
        }
        var optionsByFrequency = {};
        try {
            optionsByFrequency = JSON.parse(form.getAttribute('data-schedule-options') || '{}');
        } catch (error) {
            optionsByFrequency = {};
        }
        var selectedValue = keepSelected && schedule.hasAttribute('data-selected-value')
            ? schedule.getAttribute('data-selected-value')
            : schedule.value;
        schedule.innerHTML = '';
        (optionsByFrequency[frequency.value] || []).forEach(function (optionData) {
            var option = document.createElement('option');
            option.value = String(optionData.value || '');
            option.textContent = String(optionData.label || '');
            schedule.appendChild(option);
        });
        if (Array.prototype.some.call(schedule.options, function (option) { return option.value === selectedValue; })) {
            schedule.value = selectedValue;
        }
        schedule.removeAttribute('data-selected-value');
    }

    function updateActivationFields(form) {
        var activation = form.querySelector('[data-checklist-activation-select]');
        if (!activation) {
            return;
        }
        var mode = activation.value;
        var delayInput = form.querySelector('input[name="delay_value"]');
        var dependencyField = form.querySelector('[data-checklist-dependency-field]');
        if (dependencyField) {
            dependencyField.hidden = mode !== 'after_completion';
        }
        form.querySelectorAll('[data-checklist-delay-field]').forEach(function (field) {
            field.hidden = mode === 'immediate';
        });
        if (delayInput) {
            delayInput.min = mode === 'after_completion' ? '0' : '-3650';
            if (mode === 'after_completion' && Number(delayInput.value || 0) < 0) {
                delayInput.value = '0';
            }
        }
    }

    function updateItemRecurrenceFields(form, keepSelected) {
        var frequency = form.querySelector('[data-checklist-item-frequency]');
        var schedule = form.querySelector('[data-checklist-item-schedule]');
        var scheduleField = form.querySelector('[data-checklist-item-schedule-field]');
        if (!frequency || !schedule || !scheduleField) {
            return;
        }
        var scheduleOptions = {};
        try {
            scheduleOptions = JSON.parse(form.getAttribute('data-item-schedule-options') || '{}');
        } catch (error) {
            scheduleOptions = {};
        }
        var selectedValue = keepSelected && schedule.hasAttribute('data-selected-value')
            ? schedule.getAttribute('data-selected-value')
            : schedule.value;
        schedule.innerHTML = '';
        (scheduleOptions[frequency.value] || []).forEach(function (optionData) {
            var option = document.createElement('option');
            option.value = String(optionData.value || '');
            option.textContent = String(optionData.label || '');
            schedule.appendChild(option);
        });
        if (Array.prototype.some.call(schedule.options, function (option) { return option.value === selectedValue; })) {
            schedule.value = selectedValue;
        }
        schedule.removeAttribute('data-selected-value');
        var isRecurring = frequency.value !== '';
        var isContainer = form.getAttribute('data-checklist-container') === '1';
        scheduleField.hidden = !isRecurring;
        form.querySelectorAll('[data-checklist-item-recurrence-option]').forEach(function (field) {
            field.hidden = !isRecurring;
        });
        form.querySelectorAll('[data-checklist-item-parent-field], [data-checklist-item-activation-field]').forEach(function (field) {
            field.hidden = isContainer || isRecurring;
        });
        if (isContainer) {
            form.querySelectorAll('[data-checklist-dependency-field], [data-checklist-delay-field]').forEach(function (field) {
                field.hidden = true;
            });
        }
        if (isRecurring) {
            var parent = form.querySelector('select[name="parent_item_id"]');
            var activation = form.querySelector('[data-checklist-activation-select]');
            if (parent) {
                parent.value = '0';
            }
            if (activation) {
                activation.value = 'immediate';
                updateActivationFields(form);
            }
        }
    }

    function initializeHtmlEditors(container) {
        if (
            !window.omoSimpleHtmlField
            || typeof window.omoSimpleHtmlField.mount !== 'function'
        ) {
            return;
        }

        container.querySelectorAll('[data-checklist-html-editor]').forEach(function (editorHost) {
            var fieldContainer;
            var valueField;
            var api;

            if (editorHost.dataset.checklistHtmlEditorReady === '1') {
                return;
            }

            fieldContainer = editorHost.closest('[data-checklist-html-editor-container]');
            valueField = fieldContainer
                ? fieldContainer.querySelector('[data-checklist-html-value]')
                : null;
            if (!valueField) {
                return;
            }

            editorHost.dataset.checklistHtmlEditorReady = '1';
            api = window.omoSimpleHtmlField.mount(editorHost, {
                value: valueField.value || '',
                placeholder: editorHost.getAttribute('data-checklist-html-editor-placeholder') || '',
                minHeight: 180,
                onChange: function (value) {
                    valueField.value = String(value || '');
                },
                onReady: function (readyApi) {
                    if (readyApi && typeof readyApi.getValue === 'function') {
                        valueField.value = String(readyApi.getValue() || '');
                    }
                }
            });

            if (api && typeof api.getValue === 'function') {
                valueField.value = String(api.getValue() || '');
            }
        });
    }

    function syncHtmlEditors(form) {
        form.querySelectorAll('[data-checklist-html-editor]').forEach(function (editorHost) {
            var fieldContainer = editorHost.closest('[data-checklist-html-editor-container]');
            var valueField = fieldContainer
                ? fieldContainer.querySelector('[data-checklist-html-value]')
                : null;
            var api = editorHost.__omoSimpleHtmlField;

            if (valueField && api && typeof api.getValue === 'function') {
                valueField.value = String(api.getValue() || '');
            }
        });
    }

    function initializeForms(container) {
        initializeHtmlEditors(container);
        container.querySelectorAll('[data-checklist-editor-form]').forEach(function (form) {
            updateRecurrenceFields(form, true);
        });
        container.querySelectorAll('[data-checklist-item-form]').forEach(function (form) {
            updateActivationFields(form);
            updateItemRecurrenceFields(form, true);
        });
    }

    function setFormFeedback(form, message, type) {
        var feedback = form.querySelector('[data-checklist-editor-feedback]');
        if (!feedback) {
            return;
        }
        feedback.textContent = String(message || '');
        feedback.classList.toggle('is-error', type === 'error');
        feedback.classList.toggle('is-success', type === 'success');
    }

    function submitForm(form) {
        var formData;
        if (!form.reportValidity()) {
            return;
        }
        syncHtmlEditors(form);
        formData = new FormData(form);
        if (
            typeof window.omoBeginPendingAction === 'function'
            && !window.omoBeginPendingAction(form)
        ) {
            return;
        }
        var editor = form.closest('[data-checklist-editor]');
        if (editor) {
            editor.classList.add('is-saving');
        }
        setFormFeedback(form, '', '');
        fetch(resolveUrl(form.action), {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            cache: 'no-store'
        }).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (payload) {
                if (!response.ok || !payload.success) {
                    throw new Error(payload.message || texts.loadingError);
                }
                return payload;
            });
        }).then(function (payload) {
            rootNeedsRefresh = true;
            navigateChecklistDetail(Number(payload.id || 0), payload.detailUrl || '');
            return true;
        }).catch(function (error) {
            setFormFeedback(form, error && error.message ? error.message : texts.loadingError, 'error');
        }).finally(function () {
            if (typeof window.omoEndPendingAction === 'function') {
                window.omoEndPendingAction(form);
            }
            if (editor) {
                editor.classList.remove('is-saving');
            }
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

    function closeChecklistItemMenus(exceptMenu) {
        if (!drawer) {
            return;
        }
        drawer.querySelectorAll('[data-checklist-item-menu]').forEach(function (menu) {
            if (menu === exceptMenu) {
                return;
            }
            var panel = menu.querySelector('[data-checklist-item-menu-panel]');
            var toggle = menu.querySelector('[data-checklist-item-menu-toggle]');
            if (panel) {
                panel.hidden = true;
            }
            menu.classList.remove('is-open');
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    function toggleChecklistItemMenu(itemMenuToggle) {
        var itemMenu = itemMenuToggle ? itemMenuToggle.closest('[data-checklist-item-menu]') : null;
        var itemMenuPanel = itemMenu ? itemMenu.querySelector('[data-checklist-item-menu-panel]') : null;
        if (!itemMenu || !itemMenuPanel) {
            return;
        }
        var shouldOpen = itemMenuPanel.hidden;
        closeChecklistItemMenus(itemMenu);
        itemMenuPanel.hidden = !shouldOpen;
        itemMenu.classList.toggle('is-open', shouldOpen);
        itemMenuToggle.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
        if (shouldOpen && itemMenuPanel.classList.contains('generic-menu-panel--floating')) {
            var rect = itemMenuToggle.getBoundingClientRect();
            itemMenuPanel.style.top = Math.min(rect.bottom + 6, window.innerHeight - 12) + 'px';
            itemMenuPanel.style.left = Math.max(12, Math.min(rect.right - itemMenuPanel.offsetWidth, window.innerWidth - itemMenuPanel.offsetWidth - 12)) + 'px';
        } else {
            itemMenuPanel.style.removeProperty('top');
            itemMenuPanel.style.removeProperty('left');
        }
    }

    function initializeChecklistItemMenus(container) {
        if (!container) {
            return;
        }
        container.querySelectorAll('[data-checklist-item-menu-toggle]').forEach(function (itemMenuToggle) {
            if (itemMenuToggle.dataset.checklistItemMenuReady === '1') {
                return;
            }
            itemMenuToggle.dataset.checklistItemMenuReady = '1';
            itemMenuToggle.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                toggleChecklistItemMenu(itemMenuToggle);
            });
        });
    }

    function ensureChecklistListFloatingMenu() {
        if (checklistListFloatingMenu) {
            return checklistListFloatingMenu;
        }
        checklistListFloatingMenu = document.createElement('div');
        checklistListFloatingMenu.className = 'generic-menu-panel generic-menu-panel--floating';
        checklistListFloatingMenu.setAttribute('data-checklist-list-floating-menu', '1');
        checklistListFloatingMenu.setAttribute('role', 'menu');
        checklistListFloatingMenu.hidden = true;
        document.body.appendChild(checklistListFloatingMenu);
        checklistListFloatingMenu.addEventListener('click', function (event) {
            var deleteButton = event.target.closest('[data-checklist-list-delete]');
            if (!deleteButton) {
                return;
            }
            event.preventDefault();
            var deleteConfirm = deleteButton.getAttribute('data-checklist-delete-confirm') || 'Supprimer cette checklist et ses elements ?';
            if (!window.confirm(deleteConfirm)) {
                return;
            }
            closeChecklistListMenus();
            postChecklistDelete(deleteButton).then(function () {
                refreshRoot(currentUrl);
            }).catch(function (actionError) {
                window.omoNotify(actionError && actionError.message ? actionError.message : texts.loadingError, 'error');
            });
        });
        return checklistListFloatingMenu;
    }

    function closeChecklistListMenus() {
        if (activeChecklistListMenuToggle) {
            activeChecklistListMenuToggle.setAttribute('aria-expanded', 'false');
            activeChecklistListMenuToggle = null;
        }
        if (checklistListFloatingMenu) {
            checklistListFloatingMenu.hidden = true;
            checklistListFloatingMenu.replaceChildren();
        }
    }

    function toggleChecklistListMenu(menuToggle) {
        var checklistId = Number(menuToggle && menuToggle.getAttribute('data-checklist-id') || 0);
        if (!menuToggle || checklistId <= 0) {
            return;
        }
        if (activeChecklistListMenuToggle === menuToggle && checklistListFloatingMenu && !checklistListFloatingMenu.hidden) {
            closeChecklistListMenus();
            return;
        }
        closeChecklistListMenus();
        var panel = ensureChecklistListFloatingMenu();
        var deleteButton = document.createElement('button');
        deleteButton.type = 'button';
        deleteButton.className = 'generic-menu-item generic-menu-item--danger';
        deleteButton.setAttribute('data-checklist-list-delete', '1');
        deleteButton.setAttribute('data-checklist-id', String(checklistId));
        deleteButton.setAttribute('data-checklist-delete-confirm', menuToggle.getAttribute('data-checklist-delete-confirm') || '');
        deleteButton.setAttribute('role', 'menuitem');
        deleteButton.textContent = menuToggle.getAttribute('data-checklist-delete-label') || 'Supprimer';
        panel.replaceChildren(deleteButton);
        panel.hidden = false;
        activeChecklistListMenuToggle = menuToggle;
        menuToggle.setAttribute('aria-expanded', 'true');
        var rect = menuToggle.getBoundingClientRect();
        panel.style.top = Math.min(rect.bottom + 6, window.innerHeight - 12) + 'px';
        panel.style.left = Math.max(12, Math.min(rect.right - panel.offsetWidth, window.innerWidth - panel.offsetWidth - 12)) + 'px';
    }

    function postChecklistItemAction(button, action, targetChecklistId) {
        var checklistId = Number(button.getAttribute('data-checklist-id') || 0);
        var itemId = Number(button.getAttribute('data-checklist-item-id') || 0);
        if (checklistId <= 0 || itemId <= 0 || button.disabled) {
            return Promise.reject(new Error(texts.loadingError));
        }
        button.disabled = true;
        var formData = new FormData();
        formData.append('checklist_action', action);
        formData.append('checklist_id', String(checklistId));
        formData.append('id', String(itemId));
        formData.append('oid', String(root.getAttribute('data-checklist-oid') || 0));
        formData.append('cid', String(root.getAttribute('data-checklist-route-cid') || 0));
        if (Number(targetChecklistId || 0) > 0) {
            formData.append('target_checklist_id', String(targetChecklistId));
        }
        return fetch(resolveUrl('/omo/api/checklist/action.php'), {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            cache: 'no-store'
        }).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (payload) {
                if (!response.ok || !payload.success) {
                    throw new Error(payload.message || texts.loadingError);
                }
                return payload;
            });
        }).finally(function () {
            button.disabled = false;
        });
    }

    function postChecklistDelete(button) {
        var checklistId = Number(button.getAttribute('data-checklist-id') || 0);
        if (checklistId <= 0 || button.disabled) {
            return Promise.reject(new Error(texts.loadingError));
        }
        button.disabled = true;
        var formData = new FormData();
        formData.append('checklist_action', 'delete_checklist');
        formData.append('id', String(checklistId));
        formData.append('oid', String(root.getAttribute('data-checklist-oid') || 0));
        formData.append('cid', String(root.getAttribute('data-checklist-route-cid') || 0));
        return fetch(resolveUrl('/omo/api/checklist/action.php'), {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            cache: 'no-store'
        }).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (payload) {
                if (!response.ok || !payload.success) {
                    throw new Error(payload.message || texts.loadingError);
                }
                return payload;
            });
        }).finally(function () {
            button.disabled = false;
        });
    }

    function openChecklistItemMoveDialog(button) {
        if (typeof window.commonTopbarOpenModal !== 'function' || typeof window.omoMountHolonScopePicker !== 'function') {
            window.omoNotify(texts.loadingError, 'error');
            return;
        }
        var detail = button.closest('.omo-checklist-detail');
        var candidates = [];
        var ui = {};
        try {
            candidates = JSON.parse(detail && detail.getAttribute('data-checklist-item-move-targets') || '[]');
            ui = JSON.parse(detail && detail.getAttribute('data-checklist-item-move-ui') || '{}');
        } catch (error) {
            candidates = [];
            ui = {};
        }
        if (!Array.isArray(candidates) || candidates.length === 0) {
            window.omoNotify(ui.empty || texts.loadingError, 'error');
            return;
        }
        var sourceButton = button;
        var sourceHolonId = Number(root.getAttribute('data-checklist-route-cid') || 0);
        var organizationId = Number(root.getAttribute('data-checklist-oid') || 0);
        var html = '<div class="omo-checklist-item-move-dialog omo-resource-picker generic-drawer-content">'
            + '<aside class="omo-resource-picker__navigation" data-checklist-item-move-scope></aside>'
            + '<div class="omo-resource-picker__content">'
            + '<p class="generic-help-text">' + escapeHtml(ui.help || '') + '</p>'
            + '<label class="omo-resource-picker__quick-search"><input class="generic-form-control" type="search" data-checklist-item-move-search aria-label="' + escapeHtml(ui.search || '') + '" placeholder="' + escapeHtml(ui.search || '') + '"></label>'
            + '<select class="generic-form-control omo-project-parent-picker__select" size="10" data-checklist-item-move-select></select>'
            + '<p class="generic-description generic-description--small" data-checklist-item-move-empty hidden></p>'
            + '<p class="omo-project-move-dialog__error" data-checklist-item-move-error hidden></p>'
            + '<div class="omo-project-parent-picker__actions">'
            + '<button type="button" class="generic-action-button generic-action-button--secondary" data-checklist-item-move-cancel>' + escapeHtml(ui.cancel || '') + '</button>'
            + '<button type="button" class="generic-action-button generic-action-button--main" data-checklist-item-move-submit disabled>' + escapeHtml(ui.submit || '') + '</button>'
            + '</div></div></div>';
        window.commonTopbarOpenModal(ui.title || '', html, 'html');
        window.setTimeout(function () {
            var modal = document.getElementById('commonTopbarModalBody');
            var dialog = modal ? modal.querySelector('.omo-checklist-item-move-dialog') : null;
            if (!dialog) {
                return;
            }
            var search = dialog.querySelector('[data-checklist-item-move-search]');
            var select = dialog.querySelector('[data-checklist-item-move-select]');
            var empty = dialog.querySelector('[data-checklist-item-move-empty]');
            var error = dialog.querySelector('[data-checklist-item-move-error]');
            var submit = dialog.querySelector('[data-checklist-item-move-submit]');
            var selectedId = 0;
            var scopePicker = null;
            function render() {
                if (!select || !empty) {
                    return;
                }
                var query = String(search && search.value || '').trim().toLowerCase();
                var selectedHolonId = scopePicker && typeof scopePicker.getSelectedHolonId === 'function'
                    ? Number(scopePicker.getSelectedHolonId() || 0)
                    : 0;
                var matches = candidates.filter(function (candidate) {
                    var holonId = Number(candidate && candidate.holonId || 0);
                    var matchesScope = !scopePicker || holonId <= 0 || scopePicker.matches(holonId) || holonId === selectedHolonId;
                    var candidateText = [candidate && candidate.title, candidate && candidate.context].join(' ').toLowerCase();
                    return matchesScope && (query === '' || candidateText.indexOf(query) !== -1);
                });
                select.innerHTML = '<option value="0">' + escapeHtml(ui.select || '') + '</option>';
                matches.forEach(function (candidate) {
                    var option = document.createElement('option');
                    option.value = String(candidate.id || 0);
                    option.textContent = String(candidate.title || '') + (candidate.context ? ' - ' + String(candidate.context) : '');
                    select.appendChild(option);
                });
                select.value = String(selectedId);
                if (select.value !== String(selectedId)) {
                    selectedId = 0;
                }
                empty.textContent = ui.empty || '';
                empty.hidden = matches.length > 0;
                submit.disabled = selectedId <= 0;
            }
            scopePicker = window.omoMountHolonScopePicker({
                host: dialog.querySelector('[data-checklist-item-move-scope]'),
                organizationId: organizationId,
                initialHolonId: sourceHolonId,
                labels: {local: ui.local || '', children: ui.children || '', descendants: ui.descendants || ''},
                onChange: render
            });
            if (search) {
                search.addEventListener('input', render);
                search.focus();
            }
            if (select) {
                select.addEventListener('change', function () {
                    selectedId = Number(select.value || 0);
                    error.hidden = true;
                    submit.disabled = selectedId <= 0;
                });
            }
            dialog.addEventListener('click', function (event) {
                if (event.target.closest('[data-checklist-item-move-cancel]')) {
                    window.commonTopbarCloseModal();
                    return;
                }
                if (!event.target.closest('[data-checklist-item-move-submit]') || selectedId <= 0) {
                    return;
                }
                submit.disabled = true;
                postChecklistItemAction(sourceButton, 'move_item', selectedId).then(function (payload) {
                    window.commonTopbarCloseModal();
                    rootNeedsRefresh = true;
                    openDrawerWithUrl(payload.detailUrl || buildDetailUrl(Number(payload.id || 0)));
                }).catch(function (actionError) {
                    submit.disabled = false;
                    error.textContent = actionError && actionError.message ? actionError.message : texts.loadingError;
                    error.hidden = false;
                });
            });
            render();
        }, 0);
    }

    root.querySelectorAll('[data-checklist-filter-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            if (filterPanelOpen) {
                closeFilterPanel(true, false);
            } else {
                openFilterPanel();
            }
        });
    });

    var filterPanel = root.querySelector('[data-checklist-filter-panel]');
    if (filterPanel) {
        filterPanel.addEventListener('click', function (event) {
            if (event.target.closest('[data-checklist-filter-apply]')) {
                event.preventDefault();
                closeFilterPanel(true, false);
                return;
            }
            if (event.target.closest('[data-checklist-filter-save]')) {
                event.preventDefault();
                closeFilterPanel(true, true);
                return;
            }
            if (event.target.closest('[data-checklist-filter-restore]')) {
                event.preventDefault();
                closeFilterPanel(false, false);
                clearStoredValue(window.localStorage, checklistSavedViewsStorageKey);
                clearTemporaryFilters();
                var serverDefault = typeof window.omoApplicationViewPreferencesGetDefault === 'function'
                    ? window.omoApplicationViewPreferencesGetDefault(root)
                    : null;
                var nextScope = normalizeScope((serverDefault && serverDefault.scope) || currentScope);
                if (!root.querySelector('[data-checklist-scope-option="' + nextScope + '"]')) {
                    nextScope = currentScope;
                }
                if (nextScope !== currentScope) {
                    currentScope = nextScope;
                    refreshRoot(buildScopeUrl(nextScope)).catch(function () {
                        root.removeAttribute('data-omo-view-filter-pending');
                        root.removeAttribute('aria-busy');
                    });
                    return;
                }
                syncFilterChip();
            }
            var scopeButton = event.target.closest('[data-checklist-scope-option]');
            if (scopeButton && pendingFilters) {
                pendingFilters.scope = normalizeScope(scopeButton.getAttribute('data-checklist-scope-option'));
                syncFilterChoices();
            }
        });
    }

    var quickSearch = root.querySelector('[data-checklist-quick-search]');
    if (quickSearch) {
        var updateQuickSearch = function () {
            currentSearch = quickSearch.value || '';
            writeStoredValue(window.sessionStorage, checklistSearchStorageKey, currentSearch);
            applyQuickSearch();
        };
        quickSearch.addEventListener('input', updateQuickSearch);
        quickSearch.addEventListener('search', updateQuickSearch);
    }

    root.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && filterPanelOpen) {
            closeFilterPanel(false, false);
        }
    });

    root.addEventListener('click', function (event) {
        var scopeButton = event.target.closest('[data-checklist-scope-option]');
        if (scopeButton) {
            if (scopeButton.closest('[data-checklist-filter-panel]')) {
                return;
            }
            refreshRoot(buildScopeUrl(scopeButton.getAttribute('data-checklist-scope-option') || 'contextual'));
            return;
        }
        if (event.target.closest('[data-checklist-open-create]')) {
            openDrawerWithUrl(createUrl);
            return;
        }
        var listMenuToggle = event.target.closest('[data-checklist-list-menu-toggle]');
        if (listMenuToggle) {
            event.preventDefault();
            event.stopPropagation();
            toggleChecklistListMenu(listMenuToggle);
            return;
        }
        var deleteChecklistButton = event.target.closest('[data-checklist-delete]');
        if (deleteChecklistButton) {
            event.preventDefault();
            event.stopPropagation();
            var deleteChecklistConfirm = deleteChecklistButton.getAttribute('data-checklist-delete-confirm') || 'Supprimer cette checklist et ses elements ?';
            if (!window.confirm(deleteChecklistConfirm)) {
                return;
            }
            postChecklistDelete(deleteChecklistButton).then(function () {
                if (drawer && drawer.contains(deleteChecklistButton)) {
                    rootNeedsRefresh = true;
                    if (isChecklistDetailRoute(getCurrentRouteToken()) && typeof window.omoOpenDrawerHashState === 'function') {
                        window.omoOpenDrawerHashState('checklist');
                    } else {
                        closeDrawer();
                    }
                    return;
                }
                refreshRoot(currentUrl);
            }).catch(function (actionError) {
                window.omoNotify(actionError && actionError.message ? actionError.message : texts.loadingError, 'error');
            });
            return;
        }
        var detailTarget = event.target.closest('[data-checklist-open-detail]');
        if (detailTarget) {
            var checklistId = Number(detailTarget.getAttribute('data-checklist-id') || 0);
            if (checklistId > 0) {
                navigateChecklistDetail(checklistId);
            }
        }
    });

    root.addEventListener('keydown', function (event) {
        var detailTarget = event.target.closest('[data-checklist-open-detail]');
        if (detailTarget && (event.key === 'Enter' || event.key === ' ')) {
            event.preventDefault();
            detailTarget.click();
        }
    });

    if (drawer) {
        drawer.addEventListener('click', function (event) {
            var itemMenuToggle = event.target.closest('[data-checklist-item-menu-toggle]');
            if (itemMenuToggle) {
                event.preventDefault();
                toggleChecklistItemMenu(itemMenuToggle);
                return;
            }
            var moveItemButton = event.target.closest('[data-checklist-item-move]');
            if (moveItemButton) {
                event.preventDefault();
                closeChecklistItemMenus();
                openChecklistItemMoveDialog(moveItemButton);
                return;
            }
            var deleteItemButton = event.target.closest('[data-checklist-item-delete]');
            if (deleteItemButton) {
                event.preventDefault();
                closeChecklistItemMenus();
                var deleteDetail = deleteItemButton.closest('.omo-checklist-detail');
                var deleteUi = {};
                try {
                    deleteUi = JSON.parse(deleteDetail && deleteDetail.getAttribute('data-checklist-item-move-ui') || '{}');
                } catch (error) {
                    deleteUi = {};
                }
                if (!window.confirm(deleteUi.deleteConfirm || 'Supprimer cet element de la checklist ?')) {
                    return;
                }
                postChecklistItemAction(deleteItemButton, 'delete_item').then(function (payload) {
                    rootNeedsRefresh = true;
                    openDrawerWithUrl(payload.detailUrl || buildDetailUrl(Number(payload.id || 0)));
                }).catch(function (actionError) {
                    window.omoNotify(actionError && actionError.message ? actionError.message : texts.loadingError, 'error');
                });
                return;
            }
            var extractItemButton = event.target.closest('[data-checklist-item-extract]');
            if (extractItemButton) {
                event.preventDefault();
                closeChecklistItemMenus();
                var extractDetail = extractItemButton.closest('.omo-checklist-detail');
                var extractUi = {};
                try {
                    extractUi = JSON.parse(extractDetail && extractDetail.getAttribute('data-checklist-item-move-ui') || '{}');
                } catch (error) {
                    extractUi = {};
                }
                if (!window.confirm(extractUi.extractConfirm || 'Extraire cet element dans une nouvelle checklist recurrente ?')) {
                    return;
                }
                postChecklistItemAction(extractItemButton, 'extract_item').then(function (payload) {
                    rootNeedsRefresh = true;
                    navigateChecklistDetail(Number(payload.id || 0), payload.detailUrl || '');
                }).catch(function (actionError) {
                    window.omoNotify(actionError && actionError.message ? actionError.message : texts.loadingError, 'error');
                });
                return;
            }
            if (event.target.closest('[data-checklist-drawer-close]')) {
                if (isChecklistDetailRoute(getCurrentRouteToken()) && typeof window.omoOpenDrawerHashState === 'function') {
                    window.omoOpenDrawerHashState('checklist');
                    return;
                }
                closeDrawer();
                return;
            }
            var navigationButton = event.target.closest('[data-checklist-open-edit], [data-checklist-open-item-form], [data-checklist-open-activation], [data-checklist-back-detail], [data-checklist-editor-cancel]');
            if (navigationButton) {
                var targetUrl = navigationButton.getAttribute('data-url') || '';
                if (targetUrl) {
                    openDrawerWithUrl(targetUrl);
                } else {
                    closeDrawer();
                }
            }
        });

        document.addEventListener('pointerdown', function (event) {
            if (!event.target.closest('[data-checklist-item-menu], [data-checklist-list-menu], [data-checklist-list-floating-menu]')) {
                closeChecklistItemMenus();
                closeChecklistListMenus();
            }
        });

        drawer.addEventListener('change', function (event) {
            var form = event.target.closest('form');
            if (!form) {
                return;
            }
            if (event.target.matches('[data-checklist-trigger-type], [data-checklist-frequency]')) {
                updateRecurrenceFields(form, false);
            }
            if (event.target.matches('[data-checklist-activation-select]')) {
                updateActivationFields(form);
            }
            if (event.target.matches('[data-checklist-item-frequency]')) {
                updateItemRecurrenceFields(form, false);
            }
        });

        drawer.addEventListener('submit', function (event) {
            var form = event.target.closest('[data-checklist-editor-form], [data-checklist-item-form], [data-checklist-activation-form]');
            if (!form) {
                return;
            }
            event.preventDefault();
            submitForm(form);
        });
    }

    function maybeOpenInitialChecklist() {
        var checklistId = initialOpenChecklistId;
        var routeToken = getCurrentRouteToken();
        var routeMatch = String(routeToken || '').match(/^(?:processus|checklist)-c(\d+)$/i);
        if (routeMatch) {
            checklistId = Number(routeMatch[1]);
        }
        if (Number.isInteger(checklistId) && checklistId > 0) {
            openDrawerWithUrl(buildDetailUrl(checklistId));
        }
    }

    window.omoOpenChecklistRoute = function (routeToken) {
        if (!root.isConnected) {
            return false;
        }
        var routeMatch = String(routeToken || '').replace(/^#/, '').trim().match(/^(?:processus|checklist)-c(\d+)$/i);
        if (!routeMatch) {
            return false;
        }
        var checklistId = Number(routeMatch[1]);
        var targetUrl = buildDetailUrl(checklistId);
        if (!targetUrl) {
            return false;
        }
        openDrawerWithUrl(targetUrl);
        return true;
    };

    function handleChecklistRouteChange(event) {
        var route = event && event.detail ? event.detail : {};
        var checklistId = Number(route.checklistId || 0);
        if (checklistId > 0 && window.omoOpenChecklistRoute('processus-c' + String(checklistId))) {
            return;
        }
        closeDrawer();
    }

    window.addEventListener('omo-runtime-maintenance', handleRuntimeMaintenance);
    window.addEventListener('omo-checklist-route-change', handleChecklistRouteChange);
    initializeViewFilter();
    window.setTimeout(maybeOpenInitialChecklist, 40);
})(window, document);
