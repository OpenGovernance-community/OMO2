(function (window) {
    'use strict';

    // Fragments can load this script again. Keep one guard and one set of snapshots.
    if (!window.commonDrawerConfirmDiscard) {
        var formStates = new WeakMap();
        var navigationApprovals = new WeakSet();
        var pendingReveal = 0;
        var nativeRevealSync = null;
        var hostSelector = '.omo-overlay-drawer, #commonTopbarDrawer, #commonTopbarModal';
        var dismissSelector = [
            '.omo-overlay-drawer__backdrop', '.omo-overlay-drawer__close',
            '.generic-action-button--close', '[data-topbar-drawer-close]', '[data-topbar-modal-close]',
            '[data-common-drawer-dismiss]',
            '[data-omo-subdrawer-cancel]', '[data-omo-document-editor-cancel]',
            '[data-omo-projects-cancel-create]', '[data-omo-stats-cancel-editor]',
            '[data-activity-editor-cancel]', '[data-activity-close]',
            '[data-checklist-editor-cancel]', '[data-checklist-back-detail]',
            '[data-deferred-object-cancel]', '[data-deferred-cancel]', '[data-deferred-project-cancel]',
            '[data-cancel]'
        ].join(', ');
        var discardMessage = "Terminer l'edition en cours ? Les donnees non enregistrees seront perdues.";

        function guardedForms(container) {
            var forms = Array.prototype.slice.call(container.querySelectorAll('form'));
            if (container.matches && container.matches('form')) forms.unshift(container);
            return forms.filter(function (form) {
                var host = form.closest(hostSelector);
                var meeting = form.closest('[data-omo-pv-editor-root]');
                if (host && host.id === 'commonTopbarModal' && typeof window.commonTopbarModalCanClose === 'function') return false;
                // The meeting and template editors already own their save/close lifecycle.
                return host && !form.closest('[data-omo-unsaved-changes], [data-common-drawer-editor]')
                    && (!meeting || meeting.closest(hostSelector) !== host);
            });
        }

        function isOpenHost(host) {
            // A main app drawer can be hidden while retaining an active editor.
            // Explicitly closed subdrawers and their descendants no longer count.
            return host && !host.hidden
                && (!host.classList.contains('omo-overlay-drawer') || host.classList.contains('is-open'))
                && !host.parentElement.closest('.omo-overlay-drawer[hidden], .omo-overlay-drawer:not(.is-open), #commonTopbarDrawer[hidden], #commonTopbarModal[hidden]');
        }

        function formSignature(form) {
            var values = Array.prototype.filter.call(form.elements, function (field) {
                return field.name && !/^(submit|button|reset|image)$/i.test(field.type);
            }).map(function (field) {
                var value = field.value;
                if (field.type === 'checkbox' || field.type === 'radio') value = field.checked;
                else if (field.type === 'file') {
                    value = Array.prototype.map.call(field.files || [], function (file) {
                        return [file.name, file.size, file.lastModified];
                    });
                } else if (field.multiple && field.options) {
                    value = Array.prototype.filter.call(field.options, function (option) { return option.selected; })
                        .map(function (option) { return option.value; });
                }
                return [field.name, value];
            });
            // Read rich fields through their stable host, even while Summernote has focus.
            Array.prototype.forEach.call(form.querySelectorAll('*'), function (node) {
                if (node.__omoSimpleHtmlField && typeof node.__omoSimpleHtmlField.getValue === 'function') {
                    values.push(['html', node.__omoSimpleHtmlField.getValue()]);
                } else if (node.isContentEditable && !node.parentElement.isContentEditable
                    && !node.closest('.generic-html-editor')) {
                    values.push(['contenteditable', node.innerHTML]);
                }
            });
            return JSON.stringify(values);
        }

        function rememberInteraction(event) {
            var target = event.target instanceof Element ? event.target : null;
            var host = target ? target.closest(hostSelector) : null;
            if (!host) return;
            guardedForms(host).forEach(function (form) {
                // Do not mark another nested drawer when interacting with its parent.
                if (form.closest(hostSelector) !== host) return;
                var state = formStates.get(form);
                if (!state) {
                    state = {initial: formSignature(form), touched: false};
                    formStates.set(form, state);
                }
                if (!state.touched && !event.isTrusted && (event.type === 'input' || event.type === 'change')) return;
                if (!state.touched && event.type !== 'input' && event.type !== 'change') {
                    // Async selects and HTML adapters may have initialized since insertion.
                    state.initial = formSignature(form);
                }
                state.touched = true;
            });
        }

        function customEditors(container) {
            var editors = Array.prototype.slice.call(container.querySelectorAll('[data-common-drawer-editor]'));
            if (container.matches && container.matches('[data-common-drawer-editor]')) editors.unshift(container);
            return editors;
        }

        function dirtyEditors(container) {
            return guardedForms(container).filter(function (form) {
                var state = formStates.get(form);
                return isOpenHost(form.closest(hostSelector)) && state && state.touched && state.initial !== formSignature(form);
            }).concat(customEditors(container).filter(function (editor) {
                return isOpenHost(editor.closest(hostSelector))
                    && typeof editor.commonDrawerHasUnsavedChanges === 'function' && editor.commonDrawerHasUnsavedChanges();
            }));
        }
        window.commonDrawerHasUnsavedChanges = function (container) {
            return dirtyEditors(container).length > 0;
        };
        window.commonDrawerMarkSaved = function (container) {
            var forms = container.matches && container.matches('form') ? [container] : guardedForms(container);
            forms.forEach(function (form) {
                formStates.set(form, {initial: formSignature(form), touched: false});
            });
        };
        window.commonDrawerCancelReveal = function () {
            window.cancelAnimationFrame(pendingReveal);
            pendingReveal = 0;
            if (nativeRevealSync) {
                document.removeEventListener('pointerdown', nativeRevealSync, true);
                document.removeEventListener('keydown', nativeRevealSync, true);
                nativeRevealSync = null;
            }
        };
        window.commonDrawerRevealEditor = function (editor, options) {
            window.commonDrawerCancelReveal();
            // Let the blocked caller finish restoring its previous URL first.
            pendingReveal = window.requestAnimationFrame(function () {
                pendingReveal = 0;
                if (!(editor instanceof Element) || !editor.isConnected) return;
                var owner = editor.closest(hostSelector);
                if (owner && !isOpenHost(owner)) return;
                var state = formStates.get(editor);
                if (state && (!state.touched || state.initial === formSignature(editor))) return;
                if (typeof editor.commonDrawerHasUnsavedChanges === 'function' && !editor.commonDrawerHasUnsavedChanges()) return;
                if (typeof editor.omoHasUnsavedChanges === 'function' && !editor.omoHasUnsavedChanges()) return;
                if (typeof window.omoRevealUnsavedEditor === 'function') window.omoRevealUnsavedEditor(editor, options || {});
                var hosts = [];
                for (var node = editor; node; node = node.parentElement) {
                    if (node.matches(hostSelector)) hosts.unshift(node);
                }
                hosts.forEach(function (host) {
                    host.hidden = false;
                    if (host.classList.contains('omo-overlay-drawer')) host.classList.add('is-open');
                });
                var panel = editor.closest('[data-omo-pv-application-panel]');
                var meeting = panel ? panel.closest('[data-omo-pv-editor-root]') : null;
                if (meeting && panel.hidden && panel.getAttribute('data-omo-pv-application-loaded') === '1') {
                    var tab = Array.prototype.find.call(meeting.querySelectorAll('[data-omo-pv-application-tab]'), function (button) {
                        return button.getAttribute('data-omo-pv-application-tab') === panel.getAttribute('data-omo-pv-application-panel');
                    });
                    if (tab) tab.click();
                }
                var host = editor.closest(hostSelector) || editor;
                if (!host.hasAttribute('tabindex')) host.setAttribute('tabindex', '-1');
                var outerScroll = [];
                for (var ancestor = host.parentElement; ancestor; ancestor = ancestor.parentElement) {
                    outerScroll.push({element: ancestor, left: ancestor.scrollLeft, top: ancestor.scrollTop});
                }
                host.focus({preventScroll: true});
                editor.scrollIntoView({block: 'nearest', inline: 'nearest', behavior: 'instant'});
                // During the drawer's slide-in, scrollIntoView can pan even overflow:hidden
                // ancestors. Keep the shell fixed and scroll only inside the subdrawer.
                outerScroll.forEach(function (position) {
                    position.element.scrollTo({left: position.left, top: position.top, behavior: 'instant'});
                });
                if (options && options.syncRoute === false && typeof window.omoRevealUnsavedEditor === 'function') {
                    // Native unload gives no cancellation callback. A subsequent user
                    // interaction means the editor is being resumed, so history is safe to sync.
                    nativeRevealSync = function (event) {
                        if (!event.isTrusted) return;
                        window.commonDrawerCancelReveal();
                        if (editor.isConnected) window.omoRevealUnsavedEditor(editor, {syncRoute: true});
                    };
                    document.addEventListener('pointerdown', nativeRevealSync, true);
                    document.addEventListener('keydown', nativeRevealSync, true);
                }
            });
        };
        window.commonDrawerConfirmDiscard = function (container, options) {
            var dirty = dirtyEditors(container).filter(function (editor) { return !navigationApprovals.has(editor); });
            if (dirty.length && !window.confirm(discardMessage)) {
                window.commonDrawerRevealEditor(dirty[0], {syncRoute: true});
                return false;
            }
            if (dirty.length && options && options.navigation === true) {
                // A route can check the document, drawer and fragment in the same turn.
                // Approve that chain once, without treating discarded data as saved.
                dirty.forEach(function (editor) { navigationApprovals.add(editor); });
                window.setTimeout(function () {
                    dirty.forEach(function (editor) { navigationApprovals.delete(editor); });
                }, 0);
            }
            return true;
        };

        window.addEventListener('beforeunload', function (event) {
            var dirty = dirtyEditors(document);
            if (dirty.length) {
                event.preventDefault();
                event.returnValue = '';
                // A surviving document gets a frame after cancelling the native warning.
                // Do not change history while the browser is navigating away.
                window.commonDrawerRevealEditor(dirty[0], {syncRoute: false});
            }
        });

        // Capture before module handlers can clear the fragment, release locks or change routes.
        document.addEventListener('click', function (event) {
            var target = event.target instanceof Element ? event.target : null;
            var dismiss = target ? target.closest(dismissSelector) : null;
            var host = dismiss ? dismiss.closest(hostSelector) : null;
            if (!host) return;
            if (host.id === 'commonTopbarModal' && typeof window.commonTopbarModalCanClose === 'function') return;
            event.commonDrawerDiscardChecked = true;
            if (!window.commonDrawerConfirmDiscard(host, {navigation: true})) {
                event.preventDefault();
                event.stopImmediatePropagation();
            }
        }, true);
        ['pointerdown', 'focusin', 'input', 'change'].forEach(function (name) {
            document.addEventListener(name, rememberInteraction, true);
        });

        // Also retain a baseline for file drops and controls changed without receiving focus.
        function rememberForms(container) {
            guardedForms(container).forEach(function (form) {
                if (!formStates.has(form)) formStates.set(form, {initial: formSignature(form), touched: false});
            });
        }
        rememberForms(document);
        new MutationObserver(function (records) {
            records.forEach(function (record) {
                Array.prototype.forEach.call(record.addedNodes, function (node) {
                    if (node instanceof Element) rememberForms(node);
                });
            });
        }).observe(document.documentElement, {childList: true, subtree: true});

        var locale = document.documentElement.lang || 'fr';
        window.fetch('/common/jstranslation/drawer.php?lang=' + encodeURIComponent(locale), {credentials: 'same-origin'})
            .then(function (response) { return response.ok ? response.json() : null; })
            .then(function (texts) { if (texts && texts.discard) discardMessage = texts.discard; })
            .catch(function () { /* Keep the local fallback when translations cannot load. */ });
    }

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
        var dismissAction = getElement(drawer, settings.dismissAction);
        if (dismissAction) dismissAction.setAttribute('data-common-drawer-dismiss', '');
        var cancelAction = null;
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

            if (cancelAction) { cancelAction.remove(); cancelAction = null; }
            if (dismissAction) { dismissAction.hidden = false; }
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
            // A form's cancel action occupies the permanent close button's position.
            if (dismissAction) {
                cancelAction = actions.querySelector('[data-omo-subdrawer-cancel]');
                if (cancelAction) {
                    dismissAction.hidden = true;
                    dismissAction.parentNode.insertBefore(cancelAction, dismissAction);
                }
            }
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

        function open() {
            if (!drawer || (!drawer.hidden && drawer.classList.contains('is-open'))) {
                return;
            }

            drawer.classList.remove('is-open');
            drawer.hidden = false;
            void drawer.offsetWidth;
            window.requestAnimationFrame(function () {
                drawer.classList.add('is-open');
            });
        }

        return {
            drawer: drawer,
            open: open,
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
