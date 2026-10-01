(function (window, document) {
    'use strict';

    if (window.omoApplicationViewPreferencesReady) {
        return;
    }
    window.omoApplicationViewPreferencesReady = true;

    var viewFieldsByApplication = {
        activities: ['scope', 'assignment', 'state'],
        calendar: ['scope', 'view'],
        checklist: ['scope'],
        decision: ['scope', 'status', 'type', 'method', 'sort', 'density'],
        documents: ['scope', 'sort', 'density'],
        policy: ['scope', 'sort', 'group'],
        projects: ['scope', 'assignment', 'view', 'sort'],
        stats: ['scope', 'sort', 'view'],
        team: ['scope', 'view']
    };
    var valueSelectorsByApplication = {
        decision: {
            type: '[data-omo-decisions-type]',
            method: '[data-omo-decisions-method]'
        }
    };

    // Send only display preferences, for the requested context, before its first render.
    // The server still resolves the saved-view hierarchy and all access checks.
    window.omoApplicationViewPreferencesPrepareRequest = function (url) {
        try {
            var target = new URL(url, window.location.href);
            var match = target.pathname.match(/^\/omo\/api\/(documents|calendar)\/index\.php$/);
            if (!match || target.origin !== window.location.origin) return url;
            if (['pv_application_tab_id', 'open_document_id', 'open_event_id', 'document_scope', 'scope', 'view', 'month', 'date', 'restore_view']
                .some(function (key) { return target.searchParams.has(key); })) return url;
            var config = window.omoConfig || {};
            var oid = Number(target.searchParams.get('oid') || config.oid || 0);
            var cid = Number(target.searchParams.get('cid') || (oid === Number(config.oid) ? config.rootHolonId : 0) || 0);
            if (!oid || !cid) return url;
            var application = match[1];
            var contextKey = String(oid) + ':' + String(cid);
            var prefix = 'omo.' + application;
            function read(storage, key) {
                try { return JSON.parse(storage.getItem(key) || 'null'); } catch (error) { return null; }
            }
            function view(value) {
                if (!value || typeof value !== 'object' || Array.isArray(value)) return null;
                var result = {};
                viewFieldsByApplication[application].forEach(function (key) {
                    if (typeof value[key] === 'string' && value[key].length < 32) result[key] = value[key];
                });
                return Object.keys(result).length ? result : null;
            }
            var temporary = read(window.sessionStorage, prefix + '.session-views.v1');
            var saved = read(window.localStorage, prefix + '.saved-views.v2');
            if (!saved || !saved.contexts) saved = {contexts: read(window.localStorage, prefix + '.saved-views.v1') || {}};
            var restore = {
                organizationId: oid, holonId: cid,
                temporary: view(temporary && temporary[contextKey]),
                saved: view(saved.contexts[contextKey]),
                default: view(saved.defaultView)
            };
            if (application === 'calendar') {
                var positions = read(window.sessionStorage, prefix + '.session-position.v1');
                var position = positions && positions[contextKey];
                if (position && typeof position.url === 'string') {
                    var location = new URL(position.url, window.location.href);
                    if (location.origin === target.origin && location.pathname === target.pathname
                        && Number(location.searchParams.get('oid')) === oid && Number(location.searchParams.get('cid')) === cid) {
                        restore.position = {};
                        ['date', 'month', 'view', 'scope'].forEach(function (key) {
                            var value = location.searchParams.get(key);
                            if (value && value.length < 32) restore.position[key] = value;
                        });
                    }
                }
            }
            if (!restore.temporary && !restore.saved && !restore.default && !restore.position) return url;
            target.searchParams.set('restore_view', JSON.stringify(restore));
            return target.pathname + target.search + target.hash;
        } catch (error) {
            return url;
        }
    };

    function readContext(root) {
        if (!root) return null;
        if (!root.hasAttribute || !root.hasAttribute('data-omo-app-view-preferences')) {
            root = (root.closest && root.closest('[data-omo-app-view-preferences]'))
                || (root.querySelector && root.querySelector('[data-omo-app-view-preferences]'));
        }
        if (!root) return null;
        try {
            var value = JSON.parse(root.getAttribute('data-omo-app-view-preferences') || '{}');
            return value && typeof value === 'object' ? value : null;
        } catch (error) {
            return null;
        }
    }

    function closeMenus(exceptMenu) {
        document.querySelectorAll('[data-omo-app-view-save-menu]').forEach(function (menu) {
            if (menu === exceptMenu) return;
            var panel = menu.querySelector('[data-omo-app-view-save-menu-panel]');
            var toggle = menu.querySelector('[data-omo-app-view-save-menu-toggle]');
            if (panel) panel.hidden = true;
            if (toggle) toggle.setAttribute('aria-expanded', 'false');
        });
    }

    function getChoiceKey(attributeName) {
        var name = String(attributeName || '').toLowerCase();
        if (name.indexOf('scope') !== -1) return 'scope';
        if (name.indexOf('sort') !== -1) return 'sort';
        if (name.indexOf('group') !== -1) return 'group';
        if (name.indexOf('density') !== -1) return 'density';
        if (name.indexOf('assignment') !== -1) return 'assignment';
        if (name.indexOf('state') !== -1) return 'state';
        if (name.indexOf('view') !== -1) return 'view';
        return '';
    }

    function readActiveView(root, application) {
        var view = {};
        var allowedFields = viewFieldsByApplication[application] || [];
        if (!root) return view;
        root.querySelectorAll('button.is-active').forEach(function (button) {
            Array.prototype.forEach.call(button.attributes, function (attribute) {
                if (attribute.name.indexOf('data-') !== 0 || attribute.value === '') return;
                var key = getChoiceKey(attribute.name);
                if (key !== '' && allowedFields.indexOf(key) !== -1) view[key] = attribute.value;
            });
        });
        Object.keys(valueSelectorsByApplication[application] || {}).forEach(function (key) {
            var input = root.querySelector(valueSelectorsByApplication[application][key]);
            if (input && allowedFields.indexOf(key) !== -1 && String(input.value || '') !== '') {
                view[key] = String(input.value);
            }
        });
        return view;
    }

    function applyPendingView(root, application) {
        var applySelectors = {
            activities: '[data-activity-filter-apply]',
            calendar: '[data-omo-calendar-filter-apply]',
            checklist: '[data-checklist-filter-apply]',
            decision: '[data-omo-decisions-filter-apply]',
            documents: '[data-omo-documents-filter-apply]',
            policy: '[data-policy-filter-apply]',
            projects: '[data-omo-projects-filter-apply]',
            stats: '[data-omo-stats-filter-apply]',
            team: '[data-team-filter-apply]'
        };
        var selector = applySelectors[application] || '';
        var applyButton = selector && root ? root.querySelector(selector) : null;
        if (applyButton && !applyButton.disabled) {
            applyButton.click();
        }
    }

    function save(root, scope, view, operation) {
        var context = readContext(root);
        if (!context || !context.application || !context.endpoint || !context.csrfToken) {
            return Promise.reject(new Error('missing_context'));
        }
        return window.fetch(context.endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
            body: JSON.stringify({
                application: context.application,
                organizationId: context.organizationId || 0,
                holonId: context.holonId || 0,
                pvApplicationTabId: context.pvApplicationTabId || 0,
                scope: scope,
                operation: operation || 'save',
                csrfToken: context.csrfToken,
                view: view && typeof view === 'object' ? view : {}
            })
        }).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (payload) {
                if (!response.ok || !payload.status) throw new Error(payload.message || 'save_failed');
                return payload;
            });
        });
    }

    window.omoApplicationViewPreferencesGetDefault = function (root) {
        var context = readContext(root);
        return context && context.defaultView && typeof context.defaultView === 'object' ? context.defaultView : null;
    };
    window.omoApplicationViewPreferencesGetPersonal = function (root) {
        var context = readContext(root);
        if (context && context.temporaryView && typeof context.temporaryView === 'object') {
            return context.temporaryView;
        }
        return context && context.personalView && typeof context.personalView === 'object' ? context.personalView : null;
    };
    window.omoApplicationViewPreferencesCanUseLegacyPersonal = function (root) {
        var context = readContext(root);
        return Boolean(context && context.canSavePersonal);
    };
    window.omoApplicationViewPreferencesGetStorageContextKey = function (root, regularKey) {
        var contextRoot = root;
        if (contextRoot && !contextRoot.hasAttribute('data-omo-app-view-preferences')) {
            contextRoot = contextRoot.closest('[data-omo-app-view-preferences]')
                || contextRoot.querySelector('[data-omo-app-view-preferences]');
        }
        var context = readContext(contextRoot);
        var tabId = Number(context && context.pvApplicationTabId || 0);
        var revision = String(context && context.pvApplicationViewRevision || '').trim();
        return Number.isInteger(tabId) && tabId > 0
            ? String(regularKey || '') + ':pv:' + String(tabId) + ':' + revision
            : String(regularKey || '');
    };
    window.omoApplicationViewPreferencesSave = save;

    document.addEventListener('click', function (event) {
        var toggle = event.target.closest('[data-omo-app-view-save-menu-toggle]');
        if (toggle) {
            event.preventDefault();
            event.stopPropagation();
            var menu = toggle.closest('[data-omo-app-view-save-menu]');
            var panel = menu ? menu.querySelector('[data-omo-app-view-save-menu-panel]') : null;
            var isOpen = panel && !panel.hidden;
            closeMenus(menu);
            if (panel) panel.hidden = Boolean(isOpen);
            toggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
            return;
        }

        var saveButton = event.target.closest('[data-omo-app-view-save-scope]');
        if (!saveButton) return;
        event.preventDefault();
        event.stopPropagation();
        var root = saveButton.closest('[data-omo-app-view-preferences]');
        var scope = saveButton.getAttribute('data-omo-app-view-save-scope') || '';
        var operation = saveButton.getAttribute('data-omo-app-view-operation') || 'save';
        saveButton.disabled = true;
        var context = readContext(root);
        var application = context ? context.application : '';
        var view = readActiveView(root, application);
        if (scope !== 'personal' && operation === 'save') {
            // The choices in the filter panel are pending until Apply is pressed.
            // Apply them before saving, without erasing a closer personal scope.
            applyPendingView(root, application);
        }
        save(root, scope, view, operation).catch(function (error) {
            window.alert(error && error.message ? error.message : 'Impossible d enregistrer cette vue par defaut.');
        }).finally(function () {
            saveButton.disabled = false;
            closeMenus();
        });
    });

    document.addEventListener('click', function (event) {
        var resetButton = event.target.closest('[data-omo-app-view-reset-scope]');
        if (!resetButton) return;
        var root = resetButton.closest('[data-omo-app-view-preferences]');
        var scope = resetButton.getAttribute('data-omo-app-view-reset-scope') || '';
        var operation = resetButton.getAttribute('data-omo-app-view-operation') || 'clear';
        save(root, scope, {}, operation).catch(function () {
            // The local reset still restores the currently known fallback view.
        }).finally(function () {
            closeMenus();
        });
    });

    document.addEventListener('pointerdown', function (event) {
        if (!event.target.closest('[data-omo-app-view-save-menu]')) closeMenus();
    }, true);
})(window, document);
