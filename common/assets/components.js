(function () {
    var tabContainerCount = 0;

    function toArray(items) {
        return Array.prototype.slice.call(items || []);
    }

    function ensureId(element, prefix) {
        if (!element) {
            return '';
        }

        if (!element.id) {
            element.id = prefix + '-' + Math.random().toString(36).slice(2, 10);
        }

        return element.id;
    }

    function findClosestByAttribute(startNode, attributeName, stopNode) {
        var node = startNode;

        while (node && node !== stopNode) {
            if (node.nodeType === 1 && node.hasAttribute(attributeName)) {
                return node;
            }

            node = node.parentNode;
        }

        return null;
    }

    function buildTabState(container) {
        var sourceTabs;
        var sourcePanels;
        var tabs;
        var panels;
        var list;
        var containerId;

        if (!container) {
            return null;
        }

        sourceTabs = toArray(container.querySelectorAll('[data-generic-tab]'));
        sourcePanels = toArray(container.querySelectorAll('[data-generic-tab-panel]'));
        tabs = [];
        panels = [];

        if (!sourceTabs.length || !sourcePanels.length) {
            return null;
        }

        if (container.id) {
            containerId = container.id;
        } else {
            containerId = 'generic-tabs-' + tabContainerCount;
            container.id = containerId;
            tabContainerCount += 1;
        }

        list = container.querySelector('.generic-tabs__list');
        if (list) {
            list.setAttribute('role', 'tablist');
        }

        sourceTabs.forEach(function (tab, index) {
            var targetPanelId = tab.getAttribute('data-generic-tab-target') || tab.getAttribute('aria-controls') || '';
            var panel = targetPanelId ? document.getElementById(targetPanelId) : sourcePanels[index];

            if (!panel) {
                return;
            }

            ensureId(tab, containerId + '-tab-' + index);
            ensureId(panel, containerId + '-panel-' + index);

            if (tab.tagName === 'BUTTON') {
                tab.type = 'button';
            }

            tab.setAttribute('role', 'tab');
            tab.setAttribute('aria-controls', panel.id);
            panel.setAttribute('role', 'tabpanel');
            panel.setAttribute('aria-labelledby', tab.id);

            tabs.push(tab);
            panels.push(panel);
        });

        if (!tabs.length || !panels.length) {
            return null;
        }

        return {
            tabs: tabs,
            panels: panels
        };
    }

    function activateTab(container, nextTab, moveFocus) {
        var state = buildTabState(container);
        var tabs;
        var panels;
        var targetTab;
        var targetPanelId;

        if (!state) {
            return;
        }

        tabs = state.tabs;
        panels = state.panels;
        targetTab = tabs.indexOf(nextTab) >= 0 ? nextTab : getFirstActiveTab(tabs);
        if (!targetTab) {
            return;
        }

        targetPanelId = targetTab.getAttribute('aria-controls') || '';

        tabs.forEach(function (tab) {
            var isActive = tab === targetTab;

            tab.classList.toggle('is-active', isActive);
            tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
            tab.setAttribute('tabindex', isActive ? '0' : '-1');
        });

        panels.forEach(function (panel) {
            panel.hidden = panel.id !== targetPanelId;
        });

        if (moveFocus) {
            targetTab.focus();
        }
    }

    function getFirstActiveTab(tabs) {
        var index;

        for (index = 0; index < tabs.length; index += 1) {
            if (tabs[index].classList.contains('is-active') || tabs[index].getAttribute('aria-selected') === 'true') {
                return tabs[index];
            }
        }

        return tabs[0] || null;
    }

    function findClosestTabContainer(startNode) {
        return findClosestByAttribute(startNode, 'data-generic-tabs', document);
    }

    function initTabs(container) {
        var state;

        if (!container) {
            return;
        }

        state = buildTabState(container);
        if (!state) {
            return;
        }

        activateTab(container, getFirstActiveTab(state.tabs), false);
        container.dataset.genericTabsReady = '1';
    }

    function initAccordion(accordion) {
        if (!accordion || accordion.dataset.genericAccordionReady === '1') {
            return;
        }

        var toggle = accordion.querySelector('[data-generic-accordion-toggle]');
        if (!toggle) {
            return;
        }

        accordion.dataset.genericAccordionReady = '1';
        toggle.addEventListener('click', function (event) {
            var interactiveTarget = event.target.closest('a, button, input, select, textarea, label, [data-generic-accordion-ignore-toggle]');

            if (interactiveTarget && interactiveTarget !== toggle && toggle.contains(interactiveTarget)) {
                return;
            }

            accordion.classList.toggle('is-collapsed');
        });
    }

    function syncFileList(root) {
        var firstHeader;
        var firstGroupTitle;
        var groupTitleHeight;
        var headerHeight;
        var groups;
        var groupCount;

        if (!root || root.nodeType !== 1) {
            return;
        }

        if (!root.classList.contains('generic-file-list--stacked-sticky')) {
            root.style.setProperty('--generic-file-list-header-offset', '0px');
            root.style.setProperty('--generic-file-list-folder-offset', '0px');
            return;
        }

        firstHeader = root.querySelector('.generic-file-list__header');
        firstGroupTitle = root.querySelector('.generic-file-list__group-title');
        groupTitleHeight = firstGroupTitle ? Math.ceil(firstGroupTitle.getBoundingClientRect().height) : 0;
        headerHeight = firstHeader ? Math.ceil(firstHeader.getBoundingClientRect().height) : 0;

        root.style.setProperty('--generic-file-list-header-offset', String(Math.max(0, groupTitleHeight)) + 'px');
        root.style.setProperty('--generic-file-list-folder-offset', String(Math.max(0, groupTitleHeight + headerHeight)) + 'px');

        groups = toArray(root.querySelectorAll('.generic-file-list__group'));
        groupCount = groups.length;

        groups.forEach(function (group, index) {
            var layerBase = Math.max(0, (groupCount - index) * 10);
            group.style.setProperty('--generic-file-list-group-title-z', String(layerBase + 3));
            group.style.setProperty('--generic-file-list-group-header-z', String(layerBase + 2));
            group.style.setProperty('--generic-file-list-group-folder-z', String(layerBase + 1));
        });
    }

    function initFileList(root) {
        var resizeHandler;

        if (!root) {
            return;
        }

        if (root.dataset.genericFileListReady === '1') {
            syncFileList(root);
            return;
        }

        root.dataset.genericFileListReady = '1';
        resizeHandler = function () {
            syncFileList(root);
        };

        root.__genericFileListResizeHandler = resizeHandler;
        window.addEventListener('resize', resizeHandler);
        window.requestAnimationFrame(function () {
            syncFileList(root);
        });
    }

    function collectFileLists(root) {
        var scope = root || document;
        var lists = toArray(scope.querySelectorAll('[data-generic-file-list]'));

        if (scope.nodeType === 1 && scope.hasAttribute('data-generic-file-list')) {
            lists.unshift(scope);
        }

        return lists;
    }

    function initFileLists(root) {
        collectFileLists(root).forEach(initFileList);
    }

    function collectEditableSelects(root) {
        var scope = root || document;
        var editableSelects = toArray(scope.querySelectorAll('[data-generic-editable-select]'));

        if (scope.nodeType === 1 && scope.hasAttribute('data-generic-editable-select')) {
            editableSelects.unshift(scope);
        }

        return editableSelects;
    }

    function getEditableSelectParts(component) {
        if (!component) {
            return null;
        }

        return {
            component: component,
            input: component.querySelector('[data-generic-editable-select-input]'),
            toggle: component.querySelector('[data-generic-editable-select-toggle]'),
            panel: component.querySelector('[data-generic-editable-select-panel]'),
            options: toArray(component.querySelectorAll('[data-generic-editable-select-option]')),
            empty: component.querySelector('[data-generic-editable-select-empty]')
        };
    }

    function filterEditableSelectOptions(component) {
        var parts = getEditableSelectParts(component);
        var query;
        var hasVisibleOptions = false;

        if (!parts || !parts.input) {
            return;
        }

        query = String(parts.input.value || '').toLowerCase().trim();

        parts.options.forEach(function (option) {
            var optionValue = String(option.getAttribute('data-generic-editable-select-option') || option.textContent || '').toLowerCase();
            var isVisible = query === '' || optionValue.indexOf(query) !== -1;

            option.hidden = !isVisible;
            if (isVisible) {
                hasVisibleOptions = true;
            }
        });

        if (parts.empty) {
            parts.empty.hidden = hasVisibleOptions;
        }
    }

    function closeEditableSelect(component) {
        var parts = getEditableSelectParts(component);

        if (!parts || !parts.panel || !parts.toggle) {
            return;
        }

        parts.panel.hidden = true;
        parts.toggle.setAttribute('aria-expanded', 'false');
        parts.component.classList.remove('is-open');
    }

    function closeAllEditableSelects(exceptComponent) {
        collectEditableSelects(document).forEach(function (component) {
            if (component !== exceptComponent) {
                closeEditableSelect(component);
            }
        });
    }

    function openEditableSelect(component) {
        var parts = getEditableSelectParts(component);

        if (!parts || !parts.panel || !parts.toggle) {
            return;
        }

        filterEditableSelectOptions(component);
        closeAllEditableSelects(component);
        parts.panel.hidden = false;
        parts.toggle.setAttribute('aria-expanded', 'true');
        parts.component.classList.add('is-open');
    }

    function toggleEditableSelect(component) {
        var parts = getEditableSelectParts(component);

        if (!parts || !parts.panel) {
            return;
        }

        if (parts.panel.hidden) {
            openEditableSelect(component);
            return;
        }

        closeEditableSelect(component);
    }

    function initEditableSelect(component) {
        var parts = getEditableSelectParts(component);
        var panelId;

        if (!parts || !parts.input || !parts.toggle || !parts.panel) {
            return;
        }

        if (!component.id) {
            component.id = 'generic-editable-select-' + Math.random().toString(36).slice(2, 10);
        }

        panelId = ensureId(parts.panel, component.id + '-panel');
        parts.input.setAttribute('autocomplete', parts.input.getAttribute('autocomplete') || 'off');
        parts.toggle.setAttribute('aria-controls', panelId);
        parts.toggle.setAttribute('aria-expanded', parts.panel.hidden ? 'false' : 'true');
        parts.toggle.setAttribute('aria-haspopup', 'listbox');
        parts.panel.setAttribute('role', 'listbox');
        parts.options.forEach(function (option, index) {
            ensureId(option, component.id + '-option-' + index);
            option.setAttribute('role', 'option');
            if (option.tagName === 'BUTTON') {
                option.type = 'button';
            }
        });

        filterEditableSelectOptions(component);
        component.dataset.genericEditableSelectReady = '1';
    }

    function initEditableSelects(root) {
        collectEditableSelects(root).forEach(initEditableSelect);
    }

    function createVerticalSortableList(options) {
        var settings = options || {};
        var list = settings.list || null;
        var itemSelector = settings.itemSelector || '[draggable="true"]';
        var handleSelector = settings.handleSelector || '';
        var draggingClass = settings.draggingClass || 'is-dragging';
        var dropTargetClass = settings.dropTargetClass || 'is-drop-target';
        var placeholderClass = settings.placeholderClass || '';
        var draggedItem = null;
        var placeholderItem = null;

        if (!list || list.nodeType !== 1) {
            return null;
        }

        function getItems() {
            return toArray(list.querySelectorAll(itemSelector)).filter(function (item) {
                return item && item !== placeholderItem;
            });
        }

        function clearDropTargets() {
            if (!dropTargetClass) {
                return;
            }

            getItems().forEach(function (item) {
                item.classList.remove(dropTargetClass);
            });
        }

        function removePlaceholder() {
            if (placeholderItem && placeholderItem.parentNode) {
                placeholderItem.parentNode.removeChild(placeholderItem);
            }

            placeholderItem = null;
        }

        function buildPlaceholder(item) {
            var placeholder = null;

            if (typeof settings.createPlaceholder === 'function') {
                placeholder = settings.createPlaceholder(item);
            }

            if (!placeholder) {
                placeholder = document.createElement('div');
            }

            if (placeholderClass) {
                placeholder.classList.add(placeholderClass);
            }

            if (!placeholder.style.height) {
                placeholder.style.height = Math.max(item.getBoundingClientRect().height, 1) + 'px';
            }

            return placeholder;
        }

        function getInsertionTarget(clientY) {
            var items = getItems().filter(function (item) {
                return item !== draggedItem;
            });
            var index;
            var item;
            var bounds;
            var centerY;

            if (!items.length) {
                return null;
            }

            for (index = 0; index < items.length; index += 1) {
                item = items[index];
                bounds = item.getBoundingClientRect();
                centerY = bounds.top + (bounds.height / 2);

                if (clientY < centerY) {
                    return {
                        item: item,
                        placeAfter: false
                    };
                }
            }

            return {
                item: items[items.length - 1],
                placeAfter: true
            };
        }

        function bindHandle(item) {
            if (!handleSelector) {
                return;
            }

            toArray(item.querySelectorAll(handleSelector)).forEach(function (handle) {
                ['mousedown', 'touchstart'].forEach(function (eventName) {
                    handle.addEventListener(eventName, function () {
                        item.setAttribute('data-generic-sortable-drag-ready', '1');
                    }, { passive: true });
                });

                ['mouseup', 'mouseleave', 'touchend', 'touchcancel'].forEach(function (eventName) {
                    handle.addEventListener(eventName, function () {
                        item.removeAttribute('data-generic-sortable-drag-ready');
                    }, { passive: true });
                });
            });
        }

        function bindItem(item) {
            if (!item || item.nodeType !== 1 || item.dataset.genericSortableReady === '1') {
                return item;
            }

            bindHandle(item);

            item.addEventListener('dragstart', function (event) {
                if (handleSelector && item.getAttribute('data-generic-sortable-drag-ready') !== '1') {
                    event.preventDefault();
                    return;
                }

                draggedItem = item;
                item.classList.add(draggingClass);

                if (typeof settings.onDragStart === 'function') {
                    settings.onDragStart(item, list);
                }

                placeholderItem = buildPlaceholder(item);
                list.insertBefore(placeholderItem, item.nextSibling);

                if (event.dataTransfer) {
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', item.getAttribute('data-sortable-id') || '');
                }

                window.setTimeout(function () {
                    if (draggedItem === item) {
                        item.style.display = 'none';
                    }
                }, 0);
            });

            item.addEventListener('dragend', function () {
                item.classList.remove(draggingClass);
                item.removeAttribute('data-generic-sortable-drag-ready');
                item.style.display = '';

                if (placeholderItem && placeholderItem.parentNode) {
                    placeholderItem.parentNode.insertBefore(item, placeholderItem);
                }

                removePlaceholder();
                draggedItem = null;
                clearDropTargets();

                if (typeof settings.onDragEnd === 'function') {
                    settings.onDragEnd(item, list);
                }
            });

            item.dataset.genericSortableReady = '1';
            return item;
        }

        function handleDragOver(event) {
            var target;

            if (!draggedItem) {
                return;
            }

            event.preventDefault();
            clearDropTargets();

            if (!placeholderItem) {
                return;
            }

            target = getInsertionTarget(event.clientY);
            if (!target || !target.item) {
                list.appendChild(placeholderItem);
                return;
            }

            if (dropTargetClass) {
                target.item.classList.add(dropTargetClass);
            }

            if (target.placeAfter) {
                list.insertBefore(placeholderItem, target.item.nextSibling);
                return;
            }

            list.insertBefore(placeholderItem, target.item);
        }

        function handleDrop(event) {
            if (!draggedItem) {
                return;
            }

            event.preventDefault();
            clearDropTargets();

            if (placeholderItem && placeholderItem.parentNode) {
                placeholderItem.parentNode.insertBefore(draggedItem, placeholderItem);
            }

            if (typeof settings.onDrop === 'function') {
                settings.onDrop(draggedItem, list);
            }
        }

        getItems().forEach(bindItem);
        list.addEventListener('dragover', handleDragOver);
        list.addEventListener('drop', handleDrop);

        return {
            bindItem: bindItem,
            getItems: getItems,
            destroy: function () {
                removePlaceholder();
                clearDropTargets();
                list.removeEventListener('dragover', handleDragOver);
                list.removeEventListener('drop', handleDrop);
            }
        };
    }

    function initGenericComponents(root) {
        var scope = root || document;

        initFileLists(scope);
        initEditableSelects(scope);
        toArray(scope.querySelectorAll('[data-generic-tabs]')).forEach(initTabs);
        toArray(scope.querySelectorAll('[data-generic-accordion]')).forEach(initAccordion);
    }

    function collectPendingActionControls(root) {
        var selector = 'button, input[type="submit"], input[type="button"]';
        var controls = [];

        if (!root || root.nodeType !== 1) {
            return controls;
        }

        controls = toArray(root.querySelectorAll(selector));

        if (typeof root.matches === 'function' && root.matches(selector)) {
            controls.unshift(root);
        }

        return controls;
    }

    function setPendingActionState(root, isPending) {
        if (!root || root.nodeType !== 1) {
            return;
        }

        collectPendingActionControls(root).forEach(function (control) {
            var originalDisabled;

            if (!control || typeof control.disabled === 'undefined') {
                return;
            }

            if (isPending) {
                if (!control.hasAttribute('data-generic-pending-original-disabled')) {
                    control.setAttribute('data-generic-pending-original-disabled', control.disabled ? '1' : '0');
                }

                control.disabled = true;
                control.setAttribute('aria-disabled', 'true');
                return;
            }

            originalDisabled = control.getAttribute('data-generic-pending-original-disabled');
            if (originalDisabled !== null) {
                control.disabled = originalDisabled === '1';
                control.removeAttribute('data-generic-pending-original-disabled');
            } else {
                control.disabled = false;
            }

            if (control.disabled) {
                control.setAttribute('aria-disabled', 'true');
            } else {
                control.removeAttribute('aria-disabled');
            }
        });

        if (isPending) {
            root.dataset.genericAjaxPending = '1';
            root.setAttribute('aria-busy', 'true');
            return;
        }

        delete root.dataset.genericAjaxPending;
        root.removeAttribute('aria-busy');
    }

    function beginPendingAction(root) {
        if (!root || root.nodeType !== 1) {
            return true;
        }

        if (root.dataset.genericAjaxPending === '1') {
            return false;
        }

        setPendingActionState(root, true);
        return true;
    }

    function endPendingAction(root) {
        if (!root || root.nodeType !== 1) {
            return;
        }

        setPendingActionState(root, false);
    }

    function normalizeLocalePreference(locale) {
        var normalized = String(locale || '').trim().toLowerCase().replace(/_/g, '-');

        if (normalized === 'system') {
            return 'system';
        }

        if (/^[a-z]{2,3}(?:-[a-z0-9]{2,8})*$/.test(normalized)) {
            return normalized;
        }

        return '';
    }

    function setLanguagePreference(locale, reloadPage) {
        var normalized = normalizeLocalePreference(locale);
        var persistUrl = '/common/language_preference.php';
        var persistBody;
        var persistRequest;

        if (!normalized) {
            return false;
        }

        if (normalized === 'system') {
            document.cookie = [
                'lang=',
                'path=/',
                'expires=Thu, 01 Jan 1970 00:00:00 GMT',
                'SameSite=Lax'
            ].join('; ');
        } else {
            document.cookie = [
                'lang=' + encodeURIComponent(normalized),
                'path=/',
                'max-age=' + String(365 * 24 * 60 * 60),
                'SameSite=Lax'
            ].join('; ');
        }

        persistBody = 'locale=' + encodeURIComponent(normalized);
        persistRequest = null;

        if (typeof window.fetch === 'function') {
            persistRequest = window.fetch(persistUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: persistBody,
                keepalive: reloadPage === true
            }).catch(function () {
                return null;
            });
        }

        if (reloadPage) {
            if (persistRequest && typeof persistRequest.finally === 'function') {
                persistRequest.finally(function () {
                    window.location.reload();
                });
            } else {
                window.location.reload();
            }
        }

        return true;
    }

    function handleGenericTabClick(event) {
        var nextTab = findClosestByAttribute(event.target, 'data-generic-tab', document);
        var container;

        if (!nextTab) {
            return;
        }

        container = findClosestTabContainer(nextTab);
        if (!container) {
            return;
        }

        initTabs(container);
        activateTab(container, nextTab, false);
    }

    function handleGenericTabKeydown(event) {
        var currentTab = findClosestByAttribute(event.target, 'data-generic-tab', document);
        var container;
        var state;
        var currentIndex;
        var nextIndex;

        if (!currentTab) {
            return;
        }

        if (event.key !== 'ArrowRight' && event.key !== 'ArrowLeft' && event.key !== 'Home' && event.key !== 'End') {
            return;
        }

        container = findClosestTabContainer(currentTab);
        if (!container) {
            return;
        }

        state = buildTabState(container);
        if (!state) {
            return;
        }

        currentIndex = state.tabs.indexOf(currentTab);
        if (currentIndex < 0) {
            return;
        }

        nextIndex = currentIndex;
        if (event.key === 'ArrowRight') {
            nextIndex = (currentIndex + 1) % state.tabs.length;
        } else if (event.key === 'ArrowLeft') {
            nextIndex = (currentIndex - 1 + state.tabs.length) % state.tabs.length;
        } else if (event.key === 'Home') {
            nextIndex = 0;
        } else if (event.key === 'End') {
            nextIndex = state.tabs.length - 1;
        }

        event.preventDefault();
        activateTab(container, state.tabs[nextIndex], true);
    }

    function handleEditableSelectClick(event) {
        var option = findClosestByAttribute(event.target, 'data-generic-editable-select-option', document);
        var toggle = findClosestByAttribute(event.target, 'data-generic-editable-select-toggle', document);
        var component = findClosestByAttribute(event.target, 'data-generic-editable-select', document);
        var parts;
        var value;

        if (option) {
            component = findClosestByAttribute(option, 'data-generic-editable-select', document);
            parts = getEditableSelectParts(component);
            if (!parts || !parts.input) {
                return;
            }

            value = option.getAttribute('data-generic-editable-select-option') || option.textContent || '';
            parts.input.value = value;
            parts.input.dispatchEvent(new Event('input', { bubbles: true }));
            parts.input.dispatchEvent(new Event('change', { bubbles: true }));
            closeEditableSelect(component);
            parts.input.focus();
            return;
        }

        if (toggle) {
            component = findClosestByAttribute(toggle, 'data-generic-editable-select', document);
            if (!component) {
                return;
            }

            initEditableSelect(component);
            toggleEditableSelect(component);
            return;
        }

        if (!component) {
            closeAllEditableSelects(null);
        }
    }

    function handleEditableSelectInput(event) {
        var input = findClosestByAttribute(event.target, 'data-generic-editable-select-input', document);
        var component;

        if (!input) {
            return;
        }

        component = findClosestByAttribute(input, 'data-generic-editable-select', document);
        if (!component) {
            return;
        }

        initEditableSelect(component);
        openEditableSelect(component);
    }

    function handleEditableSelectKeydown(event) {
        var component = findClosestByAttribute(event.target, 'data-generic-editable-select', document);
        var parts;

        if (!component) {
            return;
        }

        parts = getEditableSelectParts(component);
        if (!parts || !parts.panel) {
            return;
        }

        if (event.key === 'Escape') {
            closeEditableSelect(component);
            return;
        }

        if (event.key === 'ArrowDown' && parts.panel.hidden) {
            openEditableSelect(component);
            event.preventDefault();
        }
    }

    document.addEventListener('click', handleEditableSelectClick);
    document.addEventListener('input', handleEditableSelectInput);
    document.addEventListener('keydown', handleEditableSelectKeydown);
    document.addEventListener('click', handleGenericTabClick);
    document.addEventListener('keydown', handleGenericTabKeydown);

    window.initGenericTabs = initTabs;
    window.initGenericComponents = initGenericComponents;
    window.initGenericEditableSelects = initEditableSelects;
    window.initGenericFileLists = initFileLists;
    window.syncGenericFileLists = function (root) {
        collectFileLists(root).forEach(syncFileList);
    };
    window.commonCreateVerticalSortableList = createVerticalSortableList;
    window.omoBeginPendingAction = beginPendingAction;
    window.omoEndPendingAction = endPendingAction;
    window.sharedNormalizeLocalePreference = normalizeLocalePreference;
    window.sharedSetLanguagePreference = setLanguagePreference;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initGenericComponents(document);
        });
    } else {
        initGenericComponents(document);
    }
})();
