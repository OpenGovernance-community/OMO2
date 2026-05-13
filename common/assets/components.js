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
        toggle.addEventListener('click', function () {
            accordion.classList.toggle('is-collapsed');
        });
    }

    function initGenericComponents(root) {
        var scope = root || document;

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

    document.addEventListener('click', handleGenericTabClick);
    document.addEventListener('keydown', handleGenericTabKeydown);

    window.initGenericTabs = initTabs;
    window.initGenericComponents = initGenericComponents;
    window.omoBeginPendingAction = beginPendingAction;
    window.omoEndPendingAction = endPendingAction;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initGenericComponents(document);
        });
    } else {
        initGenericComponents(document);
    }
})();
