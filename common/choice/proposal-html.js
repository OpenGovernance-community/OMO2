(function (window, document) {
    'use strict';

    if (window.omoProposalHtml) {
        return;
    }

    function getValueField(host) {
        var field = host && host.closest
            ? host.closest('[data-omo-proposal-html-field]')
            : null;
        return field ? field.querySelector('[data-omo-proposal-html-value]') : null;
    }

    function openBackgroundColorPalette(api, anchor) {
        if (!api || typeof api.applyBackgroundColor !== 'function' || !window.omoHighlightPalette) {
            return;
        }

        if (typeof api.saveRange === 'function') {
            api.saveRange();
        }

        window.omoHighlightPalette.open({
            anchor: anchor,
            onSelect: function (color) {
                api.applyBackgroundColor(color);
            }
        });
    }

    function mount(host, options) {
        options = options || {};
        if (!host) {
            return Promise.resolve(null);
        }

        if (host.__omoProposalHtmlReady) {
            return host.__omoProposalHtmlReady;
        }

        var valueField = options.valueField || getValueField(host);
        var disabled = !!options.disabled || host.getAttribute('data-omo-proposal-html-disabled') === '1';
        var initialValue = Object.prototype.hasOwnProperty.call(options, 'value')
            ? String(options.value || '')
            : (valueField ? String(valueField.value || '') : String(host.getAttribute('data-omo-proposal-html-initial') || ''));
        host.setAttribute('data-omo-proposal-html-initial', initialValue);

        var ready = new Promise(function (resolve, reject) {
            var start = function () {
                if (!window.omoSimpleHtmlField || typeof window.omoSimpleHtmlField.mount !== 'function') {
                    reject(new Error('proposal_html_editor_unavailable'));
                    return;
                }

                var api = window.omoSimpleHtmlField.mount(host, {
                    value: initialValue,
                    disabled: disabled,
                    placeholder: String(options.placeholder || 'Ajoutez une description mise en forme...'),
                    minHeight: Number(options.minHeight || 170),
                    customButtons: [{
                        name: 'omoProposalHighlight',
                        group: 'color',
                        label: 'Surlignage',
                        contents: '<img src="/omo/images/tools/surligneur.png" alt="" class="omo-simple-html-highlight-icon">',
                        title: 'Modifier le surlignage',
                        onClick: function (context) {
                            openBackgroundColorPalette(context.api, context.event && context.event.currentTarget);
                        }
                    }],
                    onChange: function (value) {
                        if (valueField) {
                            valueField.value = String(value || '');
                        }
                        if (typeof options.onChange === 'function') {
                            options.onChange(String(value || ''), api);
                        }
                    }
                });

                host.__omoProposalHtmlApi = api;
                host.setAttribute('data-omo-proposal-html-ready', '1');
                if (valueField && api && typeof api.getValue === 'function') {
                    valueField.value = String(api.getValue() || '');
                }
                resolve(api);
            };

            if (window.omoSimpleHtmlField && typeof window.omoSimpleHtmlField.mount === 'function') {
                start();
                return;
            }

            var existingScript = document.querySelector('script[data-omo-simple-html-field-loader]');
            if (existingScript) {
                existingScript.addEventListener('load', start, {once: true});
                existingScript.addEventListener('error', function () {
                    reject(new Error('proposal_html_editor_load_failed'));
                }, {once: true});
                return;
            }

            var script = document.createElement('script');
            script.src = '/omo/assets/js/simple-html-field.js?v=20260903-toolbar-insert-focus';
            script.defer = true;
            script.setAttribute('data-omo-simple-html-field-loader', '1');
            script.addEventListener('load', start, {once: true});
            script.addEventListener('error', function () {
                reject(new Error('proposal_html_editor_load_failed'));
            }, {once: true});
            document.head.appendChild(script);
        });

        host.__omoProposalHtmlReady = ready;
        ready.catch(function () {
            delete host.__omoProposalHtmlReady;
        });
        return ready;
    }

    function getValue(host) {
        if (!host) {
            return '';
        }

        var api = host.__omoProposalHtmlApi;
        if (api && typeof api.getValue === 'function') {
            return String(api.getValue() || '');
        }

        var valueField = getValueField(host);
        return valueField
            ? String(valueField.value || '')
            : String(host.getAttribute('data-omo-proposal-html-initial') || '');
    }

    function setValue(host, value) {
        if (!host) {
            return Promise.resolve();
        }

        value = String(value || '');
        var valueField = getValueField(host);
        if (valueField) {
            valueField.value = value;
        }
        host.setAttribute('data-omo-proposal-html-initial', value);

        var ready = host.__omoProposalHtmlReady || Promise.resolve(null);
        return ready.then(function (api) {
            if (api && typeof api.setValue === 'function') {
                api.setValue(value);
            }
        });
    }

    function mountAll(root) {
        root = root || document;
        var hosts = root.querySelectorAll ? root.querySelectorAll('[data-omo-proposal-html-editor]') : [];
        Array.prototype.forEach.call(hosts, function (host) {
            mount(host).catch(function () {
                host.setAttribute('data-omo-proposal-html-error', '1');
            });
        });
    }

    function getPlainText(value) {
        var temporary = document.createElement('div');
        temporary.innerHTML = String(value || '');
        return String(temporary.textContent || temporary.innerText || '').replace(/\s+/g, ' ').trim();
    }

    function refreshDecisionProposalCards(root, content, options) {
        if (!root || !root.querySelectorAll) {
            return;
        }

        content = content || {};
        options = options || {};
        var descriptionSelector = String(options.descriptionSelector || '[data-omo-decision-proposal-description]');
        var detailsSelector = String(options.detailsSelector || '');
        var canEdit = options.canEdit !== false;
        var cards = root.querySelectorAll('.omo-decision-proposal-card');

        Array.prototype.forEach.call(cards, function (card) {
            var titleInput = card.querySelector('input[name="proposals[]"]');
            var descriptionInput = card.querySelector(descriptionSelector);
            var detailsButton = detailsSelector !== '' ? card.querySelector(detailsSelector) : null;
            if (!titleInput || !descriptionInput) {
                return;
            }

            if (!content.url && detailsButton) {
                detailsButton.remove();
            }

            var field = descriptionInput.closest ? descriptionInput.closest('[data-omo-proposal-html-field]') : null;
            var editor = field ? field.querySelector('[data-omo-proposal-html-editor]') : null;

            if (content.title) {
                if (titleInput.type === 'hidden') {
                    var derivedTitle = getPlainText(descriptionInput.value);
                    titleInput.type = 'text';
                    titleInput.className = 'generic-form-control';
                    if (String(titleInput.value || '').trim() === '' && derivedTitle !== '') {
                        titleInput.value = derivedTitle;
                    }
                }
                if (field) {
                    field.hidden = true;
                }
                descriptionInput.hidden = true;
                return;
            }

            titleInput.type = 'hidden';
            if (!content.description) {
                if (field) {
                    field.hidden = true;
                }
                descriptionInput.hidden = true;
                return;
            }

            if (!field) {
                field = document.createElement('div');
                field.setAttribute('data-omo-proposal-html-field', '');
                editor = document.createElement('div');
                editor.className = 'omo-proposal-html-editor';
                editor.setAttribute('data-omo-proposal-html-editor', '');
                if (!canEdit) {
                    editor.setAttribute('data-omo-proposal-html-disabled', '1');
                }
                descriptionInput.parentNode.insertBefore(field, descriptionInput);
                field.appendChild(editor);
                field.appendChild(descriptionInput);
            }

            field.hidden = false;
            descriptionInput.hidden = true;
            if (editor) {
                if (!canEdit) {
                    editor.setAttribute('data-omo-proposal-html-disabled', '1');
                } else {
                    editor.removeAttribute('data-omo-proposal-html-disabled');
                }
                mount(editor, {
                    value: String(descriptionInput.value || ''),
                    disabled: !canEdit
                }).catch(function () {
                    editor.setAttribute('data-omo-proposal-html-error', '1');
                });
            }
        });
    }

    window.omoProposalHtml = {
        mount: mount,
        mountAll: mountAll,
        getValue: getValue,
        setValue: setValue,
        refreshDecisionProposalCards: refreshDecisionProposalCards
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            mountAll(document);
        }, {once: true});
    } else {
        mountAll(document);
    }

    if (window.MutationObserver && document.documentElement) {
        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                Array.prototype.forEach.call(mutation.addedNodes || [], function (node) {
                    if (node.nodeType === 1) {
                        mountAll(node);
                    }
                });
            });
        });
        observer.observe(document.documentElement, {childList: true, subtree: true});
    }
})(window, document);
