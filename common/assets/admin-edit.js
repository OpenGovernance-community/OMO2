window.adminEditHtmlFieldPromise = window.adminEditHtmlFieldPromise || null;

function adminEditEnsureHtmlField() {
    if (window.omoSimpleHtmlField && window.omoSimpleHtmlField.version === '20261005-html-editor-gaps') {
        return Promise.resolve(window.omoSimpleHtmlField);
    }
    if (!window.adminEditHtmlFieldPromise) {
        window.adminEditHtmlFieldPromise = new Promise(function (resolve, reject) {
            var script = document.createElement('script');
            script.src = '/omo/assets/js/simple-html-field.js?v=20261005-html-editor-gaps';
            script.onload = function () { resolve(window.omoSimpleHtmlField); };
            script.onerror = function () { reject(new Error('html_field_load_failed')); };
            document.head.appendChild(script);
        }).catch(function (error) {
            window.adminEditHtmlFieldPromise = null;
            throw error;
        });
    }
    return window.adminEditHtmlFieldPromise;
}

function adminEditSyncHtmlFields(scope) {
    var root = scope || document;
    root.querySelectorAll('textarea.summernote').forEach(function (field) {
        if (field.__adminEditHtmlHost) {
            field.value = field.__adminEditHtmlHost.__omoSimpleHtmlField.getValue();
        }
    });
}

function adminEditDestroyHtmlFields(scope) {
    var root = scope || document;
    adminEditSyncHtmlFields(root);
    root.querySelectorAll('textarea.summernote').forEach(function (field) {
        var host = field.__adminEditHtmlHost;
        if (host) {
            host.__omoSimpleHtmlField.destroy();
            host.remove();
            delete field.__adminEditHtmlHost;
            field.hidden = false;
            (field.__adminEditHtmlLabels || []).forEach(function (binding) {
                binding.label.removeEventListener('click', binding.focus);
                binding.label.htmlFor = field.id;
            });
            delete field.__adminEditHtmlLabels;
        }
    });
}

function adminEditSetHtmlFieldValue(field, value) {
    field.value = String(value || '');
    var host = field.__adminEditHtmlHost;
    if (host) host.__omoSimpleHtmlField.setValue(field.value);
}

function adminEditInitHtmlFields(scope) {
    var root = scope || document;
    var fields = root.querySelectorAll('textarea.summernote');
    if (!fields.length) return Promise.resolve();
    return adminEditEnsureHtmlField().then(function (htmlField) {
        fields.forEach(function (field) {
            if (!field.isConnected || field.__adminEditHtmlHost) return;
            var host = document.createElement('div');
            host.className = 'admin-edit__html-field';
            field.after(host);
            field.hidden = true;
            field.__adminEditHtmlHost = host;
            var labels = Array.from(document.querySelectorAll('label[for]')).filter(function (label) {
                return field.id && label.htmlFor === field.id;
            });
            htmlField.mount(host, {
                value: field.value,
                disabled: field.disabled || field.readOnly,
                placeholder: field.getAttribute('placeholder') || (labels[0] ? labels[0].textContent.trim() : ''),
                surfaceId: field.id ? field.id + '-html' : '',
                editorProfile: field.getAttribute('data-editor-profile') === 'simple' ? 'simple' : 'admin',
                customButtons: [{
                    name: 'omoHighlight', group: 'color', label: 'Surlignage', title: 'Modifier le surlignage',
                    contents: '<img src="/omo/images/tools/surligneur.png" alt="" class="omo-simple-html-highlight-icon">',
                    onClick: function (context) {
                        if (!window.omoHighlightPalette) return;
                        context.api.saveRange();
                        window.omoHighlightPalette.open({
                            anchor: context.event.currentTarget,
                            onSelect: function (color) { context.api.applyBackgroundColor(color); }
                        });
                    }
                }],
                onChange: function (value) {
                    field.value = value;
                    field.dispatchEvent(new Event('input', {bubbles: true}));
                    if (window.jQuery) window.jQuery(field).triggerHandler('summernote.change', [value]);
                }
            });
            var surface = host.querySelector('[data-html-editor-surface]');
            // Keep existing labels pointing to the focusable HTML surface.
            field.__adminEditHtmlLabels = labels.map(function (label) {
                var focus = function (event) {
                    event.preventDefault();
                    host.__omoSimpleHtmlField.focus();
                };
                label.htmlFor = surface.id;
                label.addEventListener('click', focus);
                return {label: label, focus: focus};
            });
        });
    }).catch(function (error) {
        console.warn('Impossible de charger l editeur HTML adminEdit.', error);
    });
}
