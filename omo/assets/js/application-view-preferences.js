(function (window, document) {
    'use strict';

    if (window.omoApplicationViewPreferencesReady) {
        return;
    }
    window.omoApplicationViewPreferencesReady = true;

    var viewFieldsByApplication = {
        activities: ['scope', 'state'],
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

    function readContext(root) {
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
        return context && context.personalView && typeof context.personalView === 'object' ? context.personalView : null;
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
        save(root, scope, readActiveView(root, context ? context.application : ''), operation).catch(function (error) {
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
