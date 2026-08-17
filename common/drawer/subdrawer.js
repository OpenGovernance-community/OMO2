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
        var help = getElement(drawer, settings.help || '[data-omo-subdrawer-help-panel]');
        var defaultTitle = title ? title.textContent : '';
        var defaultDescription = description ? description.textContent : '';
        var defaultHelp = help ? String((help.querySelector('[data-omo-subdrawer-help-content]') || {}).textContent || '') : '';

        function ensureHelp() {
            var heading;
            var summary;
            var content;

            if (help || !title || !title.parentNode) {
                return help;
            }

            heading = title.parentNode.querySelector('[data-omo-subdrawer-title-help]');
            if (!heading) {
                heading = document.createElement('div');
                heading.className = 'generic-heading-with-help';
                heading.setAttribute('data-omo-subdrawer-title-help', '1');
                title.parentNode.insertBefore(heading, title);
                heading.appendChild(title);
            }

            help = document.createElement('details');
            help.className = 'generic-context-help';
            help.setAttribute('data-omo-subdrawer-help-panel', '1');
            summary = document.createElement('summary');
            summary.textContent = '?';
            content = document.createElement('div');
            content.className = 'generic-context-help__content';
            content.setAttribute('data-omo-subdrawer-help-content', '1');
            help.appendChild(summary);
            help.appendChild(content);
            heading.appendChild(help);
            return help;
        }

        function setHelp(helpText) {
            var nextHelp = String(helpText || '').trim();
            var helpPanel = ensureHelp();
            var content;
            var summary;

            if (!helpPanel) {
                return;
            }

            content = helpPanel.querySelector('[data-omo-subdrawer-help-content]');
            summary = helpPanel.querySelector('summary');
            if (content) {
                content.textContent = nextHelp;
            }
            if (summary) {
                summary.setAttribute('aria-label', nextHelp || 'Aide');
            }
            helpPanel.hidden = nextHelp === '';
            if (nextHelp === '') {
                helpPanel.open = false;
            }
        }

        function setHeader(headerOptions) {
            var headerSettings = headerOptions && typeof headerOptions === 'object' ? headerOptions : {};
            var hasTitle = Object.prototype.hasOwnProperty.call(headerSettings, 'title');
            var hasDescription = Object.prototype.hasOwnProperty.call(headerSettings, 'description')
                || Object.prototype.hasOwnProperty.call(headerSettings, 'subtitle');
            var hasActions = Object.prototype.hasOwnProperty.call(headerSettings, 'actions');
            var hasHelp = Object.prototype.hasOwnProperty.call(headerSettings, 'help')
                || Object.prototype.hasOwnProperty.call(headerSettings, 'info');
            var nextDescription = Object.prototype.hasOwnProperty.call(headerSettings, 'subtitle')
                ? headerSettings.subtitle
                : headerSettings.description;
            var nextHelp = Object.prototype.hasOwnProperty.call(headerSettings, 'info')
                ? headerSettings.info
                : headerSettings.help;

            if (title && hasTitle) {
                title.textContent = headerSettings.title || '';
            }

            if (description && hasDescription) {
                description.textContent = nextDescription || '';
                description.hidden = !nextDescription;
            }

            if (hasHelp) {
                setHelp(nextHelp);
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
                help: defaultHelp,
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
                help: header.getAttribute('data-omo-subdrawer-help') || '',
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
            setHelp: function (nextHelp) {
                setHeader({ help: nextHelp });
            },
            setActions: function (nextActions) {
                setHeader({ actions: Array.isArray(nextActions) ? nextActions : [] });
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
