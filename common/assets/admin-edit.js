
    window.adminEditSummernoteInitPromise = window.adminEditSummernoteInitPromise || null;

    function adminEditLoadStyleOnce(href, dataAttribute) {
        return new Promise(function (resolve, reject) {
            if (!href) {
                resolve();
                return;
            }

            var existingLink = document.querySelector('link[' + dataAttribute + '="' + href + '"]');
            if (existingLink) {
                resolve();
                return;
            }

            var link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = href;
            link.setAttribute(dataAttribute, href);
            link.onload = function () {
                resolve();
            };
            link.onerror = function () {
                reject(new Error('summernote_css_load_failed'));
            };
            document.head.appendChild(link);
        });
    }

    function adminEditLoadScriptOnce(src, dataAttribute) {
        return new Promise(function (resolve, reject) {
            if (!src) {
                resolve();
                return;
            }

            var existingScript = document.querySelector('script[' + dataAttribute + '="' + src + '"]');
            if (existingScript) {
                if (existingScript.getAttribute('data-admin-edit-loaded') === '1') {
                    resolve();
                    return;
                }

                existingScript.addEventListener('load', function () {
                    existingScript.setAttribute('data-admin-edit-loaded', '1');
                    resolve();
                }, { once: true });
                existingScript.addEventListener('error', function () {
                    reject(new Error('summernote_js_load_failed'));
                }, { once: true });
                return;
            }

            var script = document.createElement('script');
            script.src = src;
            script.async = false;
            script.setAttribute(dataAttribute, src);
            script.onload = function () {
                script.setAttribute('data-admin-edit-loaded', '1');
                resolve();
            };
            script.onerror = function () {
                reject(new Error('summernote_js_load_failed'));
            };
            document.head.appendChild(script);
        });
    }

    function adminEditEnsureSummernoteAssets() {
        if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.summernote === 'function') {
            return Promise.resolve(window.jQuery);
        }

        if (window.adminEditSummernoteInitPromise) {
            return window.adminEditSummernoteInitPromise;
        }

        var summernoteVersion = '0.8.18';
        var summernoteCssUrl = 'https://cdnjs.cloudflare.com/ajax/libs/summernote/' + summernoteVersion + '/summernote-lite.min.css';
        var summernoteJsUrl = 'https://cdnjs.cloudflare.com/ajax/libs/summernote/' + summernoteVersion + '/summernote-lite.min.js';
        var summernoteLangUrl = 'https://cdnjs.cloudflare.com/ajax/libs/summernote/' + summernoteVersion + '/lang/summernote-fr-FR.min.js';

        window.adminEditSummernoteInitPromise = adminEditLoadStyleOnce(summernoteCssUrl, 'data-admin-edit-summernote-css')
            .then(function () {
                return adminEditLoadScriptOnce(summernoteJsUrl, 'data-admin-edit-summernote-js');
            })
            .then(function () {
                return adminEditLoadScriptOnce(summernoteLangUrl, 'data-admin-edit-summernote-lang');
            })
            .then(function () {
                if (!window.jQuery || !window.jQuery.fn || typeof window.jQuery.fn.summernote !== 'function') {
                    throw new Error('summernote_not_available');
                }

                return window.jQuery;
            })
            .catch(function (error) {
                window.adminEditSummernoteInitPromise = null;
                throw error;
            });

        return window.adminEditSummernoteInitPromise;
    }

    function adminEditSyncHtmlFields(scope) {
        if (!window.jQuery || !window.jQuery.fn) {
            return;
        }

        var root = scope || document;
        window.jQuery(root).find('textarea.summernote').each(function () {
            var field = window.jQuery(this);
            if (field.data('adminEditSummernoteBound') === true && typeof field.summernote === 'function') {
                try {
                    field.val(field.summernote('code'));
                } catch (error) {
                }
            }
        });
    }

    function adminEditDestroyHtmlFields(scope) {
        if (!window.jQuery || !window.jQuery.fn) {
            return;
        }

        var root = scope || document;
        window.jQuery(root).find('textarea.summernote').each(function () {
            var field = window.jQuery(this);
            var isBound = field.data('adminEditSummernoteBound') === true
                || field.next('.note-editor').length > 0;

            if (isBound && typeof field.summernote === 'function') {
                try {
                    field.val(field.summernote('code'));
                    field.summernote('destroy');
                } catch (error) {
                }
            }

            field.removeData('adminEditSummernoteBound');
        });
    }

    function adminEditInitHtmlFields(scope) {
        var root = scope || document;
        var textareas = root.querySelectorAll ? root.querySelectorAll('textarea.summernote') : [];
        if (!textareas || textareas.length === 0) {
            return Promise.resolve();
        }

        function adminEditGetHtmlEditorOptions(field) {
            var profile = (field.data('editorProfile') || '').toString();
            var options = {
                lang: 'fr-FR',
                height: 240,
                disableResizeEditor: true,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'italic', 'underline', 'clear']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['insert', ['link', 'table', 'hr']],
                    ['view', ['codeview']]
                ]
            };

            if (profile === 'simple') {
                options.toolbar = [
                    ['font', ['bold', 'italic', 'underline', 'clear']],
                    ['para', ['ul', 'ol', 'paragraph']]
                ];
            }

            options.buttons = {
                omoHighlight: function () {
                    return $.summernote.ui.button({
                        contents: '<img src="/omo/images/tools/surligneur.png" alt="" style="display:block;width:18px;height:18px;object-fit:contain;">',
                        tooltip: 'Modifier le surlignage',
                        click: function (event) {
                            field.summernote('saveRange');
                            if (window.omoHighlightPalette) {
                                window.omoHighlightPalette.open({
                                    anchor: event && event.currentTarget,
                                    onSelect: function (color) {
                                        field.summernote('restoreRange');
                                        field.summernote('backColor', color || 'transparent');
                                    }
                                });
                            }
                        }
                    }).render();
                }
            };
            var colorGroupIndex = options.toolbar.findIndex(function (group) {
                return group[0] === 'para';
            });
            options.toolbar.splice(colorGroupIndex >= 0 ? colorGroupIndex : options.toolbar.length, 0, ['color', ['omoHighlight']]);

            options.callbacks = {
                onChange: function (contents) {
                    field.val(contents);
                }
            };

            return options;
        }

        return adminEditEnsureSummernoteAssets()
            .then(function ($) {
                Array.prototype.forEach.call(textareas, function (textarea) {
                    if (!document.documentElement.contains(textarea)) {
                        return;
                    }

                    var field = $(textarea);
                    if (field.data('adminEditSummernoteBound') === true) {
                        return;
                    }

                    field.data('adminEditSummernoteBound', true);
                    field.summernote(adminEditGetHtmlEditorOptions(field));

                    field.val(field.summernote('code'));
                });
            })
            .catch(function () {
                console.warn('Impossible de charger l editeur HTML adminEdit.');
            });
    }

