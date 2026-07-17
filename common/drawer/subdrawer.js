(function (window) {
    'use strict';

    function asElement(value) {
        return value instanceof Element ? value : null;
    }

    function getElement(drawer, value) {
        if (value instanceof Element) {
            return value;
        }
        return drawer && typeof value === 'string' ? drawer.querySelector(value) : null;
    }

    function createSubdrawerController(options) {
        var settings = options && typeof options === 'object' ? options : {};
        var drawer = getElement(document, settings.drawer) || asElement(settings.drawer);
        var title = getElement(drawer, settings.title || '[data-omo-subdrawer-title]');
        var description = getElement(drawer, settings.description || '[data-omo-subdrawer-description]');
        var actions = getElement(drawer, settings.actions || '[data-omo-subdrawer-actions]');
        var defaultTitle = title ? title.textContent : '';
        var defaultDescription = description ? description.textContent : '';

        function setHeader(headerOptions) {
            var headerSettings = headerOptions && typeof headerOptions === 'object' ? headerOptions : {};
            var hasTitle = Object.prototype.hasOwnProperty.call(headerSettings, 'title');
            var hasDescription = Object.prototype.hasOwnProperty.call(headerSettings, 'description')
                || Object.prototype.hasOwnProperty.call(headerSettings, 'subtitle');
            var hasActions = Object.prototype.hasOwnProperty.call(headerSettings, 'actions');
            var nextDescription = Object.prototype.hasOwnProperty.call(headerSettings, 'subtitle')
                ? headerSettings.subtitle
                : headerSettings.description;

            if (title && hasTitle) {
                title.textContent = headerSettings.title || '';
            }

            if (description && hasDescription) {
                description.textContent = nextDescription || '';
                description.hidden = !nextDescription;
            }

            if (!actions || !hasActions) {
                return;
            }

            actions.innerHTML = '';
            (Array.isArray(headerSettings.actions) ? headerSettings.actions : []).forEach(function (action) {
                var button;
                if (action instanceof HTMLElement) {
                    actions.appendChild(action);
                    return;
                }

                if (!action || typeof action !== 'object' || !action.label) {
                    return;
                }

                button = document.createElement('button');
                button.type = action.type || 'button';
                button.className = action.className || 'generic-action-button';
                button.textContent = action.label;
                if (action.attributes && typeof action.attributes === 'object') {
                    Object.keys(action.attributes).forEach(function (name) {
                        button.setAttribute(name, String(action.attributes[name]));
                    });
                }
                if (typeof action.onClick === 'function') {
                    button.addEventListener('click', action.onClick);
                }
                actions.appendChild(button);
            });
        }

        function resetHeader() {
            setHeader({
                title: defaultTitle,
                description: defaultDescription,
                actions: []
            });
        }

        function applyContentHeader(content) {
            var contentRoot = asElement(content);
            var header = contentRoot ? contentRoot.querySelector('[data-omo-subdrawer-header]') : null;
            if (!header) {
                return false;
            }

            setHeader({
                title: header.getAttribute('data-omo-subdrawer-title') || defaultTitle,
                description: header.getAttribute('data-omo-subdrawer-description') || '',
                actions: Array.prototype.slice.call(header.querySelectorAll('[data-omo-subdrawer-action]'))
            });
            return true;
        }

        return {
            drawer: drawer,
            setHeader: setHeader,
            setTitle: function (nextTitle) {
                setHeader({ title: nextTitle });
            },
            setSubtitle: function (nextSubtitle) {
                setHeader({ subtitle: nextSubtitle });
            },
            addButton: function (button) {
                if (!actions) {
                    return;
                }
                setHeader({
                    actions: Array.prototype.slice.call(actions.children).concat([button])
                });
            },
            resetHeader: resetHeader,
            applyContentHeader: applyContentHeader
        };
    }

    window.omoCreateSubdrawerController = createSubdrawerController;
})(window);
