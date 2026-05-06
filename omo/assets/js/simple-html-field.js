(function (window, document) {
    'use strict';

    if (window.omoSimpleHtmlField) {
        return;
    }

    const SUMMERNOTE_VERSION = '0.8.18';
    const SUMMERNOTE_CSS_URL = 'https://cdnjs.cloudflare.com/ajax/libs/summernote/' + SUMMERNOTE_VERSION + '/summernote-lite.min.css';
    const SUMMERNOTE_JS_URL = 'https://cdnjs.cloudflare.com/ajax/libs/summernote/' + SUMMERNOTE_VERSION + '/summernote-lite.min.js';
    const SUMMERNOTE_LANG_URL = 'https://cdnjs.cloudflare.com/ajax/libs/summernote/' + SUMMERNOTE_VERSION + '/lang/summernote-fr-FR.min.js';

    let stylesInjected = false;
    let dependencyPromise = null;

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function ensureLocalStyles() {
        if (stylesInjected) {
            return;
        }

        const style = document.createElement('style');
        style.textContent = ''
            + '.omo-simple-html-field{display:grid;gap:10px;}'
            + '.omo-simple-html-field .note-editor.note-frame{border:1px solid var(--color-border,#d1d5db);border-radius:14px;background:var(--color-surface,#fff);}'
            + '.omo-simple-html-field .note-toolbar{border-bottom:1px solid var(--color-border,#d1d5db);background:color-mix(in srgb,var(--color-surface-alt,#f8fafc) 70%,white);border-top-left-radius:14px;border-top-right-radius:14px;padding:8px;}'
            + '.omo-simple-html-field .note-btn{border-radius:10px;border-color:var(--color-border,#d1d5db);}'
            + '.omo-simple-html-field .note-editing-area .note-editable{min-height:140px;padding:14px;line-height:1.55;color:var(--color-text,#1f2937);}'
            + '.omo-simple-html-field .note-placeholder{color:var(--color-text-light,#6b7280);}'
            + '.omo-simple-html-field .note-statusbar{display:none;}'
            + '.omo-simple-html-field__meta{font-size:12px;line-height:1.45;color:var(--color-text-light,#6b7280);}'
            + '.omo-simple-html-render{line-height:1.55;word-break:break-word;white-space:normal;}'
            + '.omo-simple-html-render > :first-child{margin-top:0;}'
            + '.omo-simple-html-render > :last-child{margin-bottom:0;}'
            + '.omo-simple-html-render p{margin:0 0 .85em;}'
            + '.omo-simple-html-render ul,.omo-simple-html-render ol{margin:.2em 0;padding-left:1.35em;}'
            + '.omo-simple-html-render li + li{margin-top:.28em;}'
            + '.omo-simple-html-render a{color:var(--color-primary,#2563eb);text-decoration:underline;}';
        document.head.appendChild(style);
        stylesInjected = true;
    }

    function ensureStylesheet(url) {
        return new Promise(function (resolve, reject) {
            const existing = document.querySelector('link[data-omo-summernote-href="' + url + '"]');
            if (existing) {
                resolve();
                return;
            }

            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = url;
            link.setAttribute('data-omo-summernote-href', url);
            link.onload = function () { resolve(); };
            link.onerror = function () { reject(new Error('Impossible de charger la feuille de style Summernote.')); };
            document.head.appendChild(link);
        });
    }

    function ensureScript(url) {
        return new Promise(function (resolve, reject) {
            const existing = document.querySelector('script[data-omo-summernote-src="' + url + '"]');
            if (existing) {
                if (existing.getAttribute('data-loaded') === '1') {
                    resolve();
                    return;
                }

                existing.addEventListener('load', function () { resolve(); }, { once: true });
                existing.addEventListener('error', function () { reject(new Error('Impossible de charger Summernote.')); }, { once: true });
                return;
            }

            const script = document.createElement('script');
            script.src = url;
            script.async = false;
            script.setAttribute('data-omo-summernote-src', url);
            script.onload = function () {
                script.setAttribute('data-loaded', '1');
                resolve();
            };
            script.onerror = function () { reject(new Error('Impossible de charger Summernote.')); };
            document.head.appendChild(script);
        });
    }

    function ensureDependencies() {
        if (dependencyPromise) {
            return dependencyPromise;
        }

        dependencyPromise = Promise.resolve()
            .then(function () {
                if (!window.jQuery) {
                    throw new Error('jQuery est requis pour Summernote.');
                }

                ensureLocalStyles();
                return ensureStylesheet(SUMMERNOTE_CSS_URL);
            })
            .then(function () {
                return ensureScript(SUMMERNOTE_JS_URL);
            })
            .then(function () {
                return ensureScript(SUMMERNOTE_LANG_URL);
            })
            .then(function () {
                if (!window.jQuery || !window.jQuery.fn || typeof window.jQuery.fn.summernote !== 'function') {
                    throw new Error('Summernote n est pas disponible apres chargement.');
                }
            });

        return dependencyPromise;
    }

    function sanitizeUrl(url) {
        const value = String(url || '').trim();
        if (!value) {
            return '';
        }

        if (/^(#|\/)/.test(value)) {
            return value;
        }

        if (!/^[a-z][a-z0-9+.-]*:/i.test(value)) {
            return value;
        }

        return /^(https?:|mailto:|tel:)/i.test(value) ? value : '';
    }

    function appendSanitizedChild(parentNode, childNode) {
        if (childNode && childNode.nodeType === 11 && !childNode.hasChildNodes()) {
            return;
        }

        parentNode.appendChild(childNode);
    }

    function buildSanitizedNode(sourceNode, ownerDocument) {
        if (!sourceNode) {
            return ownerDocument.createDocumentFragment();
        }

        if (sourceNode.nodeType === 3) {
            return ownerDocument.createTextNode(sourceNode.nodeValue || '');
        }

        if (sourceNode.nodeType !== 1) {
            return ownerDocument.createDocumentFragment();
        }

        const sourceTagName = String(sourceNode.tagName || '').toUpperCase();
        if (!sourceTagName) {
            return ownerDocument.createDocumentFragment();
        }

        if (['SCRIPT', 'STYLE', 'IFRAME', 'OBJECT', 'EMBED', 'META', 'LINK'].indexOf(sourceTagName) >= 0) {
            return ownerDocument.createDocumentFragment();
        }

        const normalizedTagName = sourceTagName === 'DIV' ? 'P' : sourceTagName;
        const allowedTags = {
            P: true,
            BR: true,
            STRONG: true,
            B: true,
            EM: true,
            I: true,
            U: true,
            UL: true,
            OL: true,
            LI: true,
            A: true
        };

        if (!allowedTags[normalizedTagName]) {
            const fragment = ownerDocument.createDocumentFragment();
            Array.from(sourceNode.childNodes || []).forEach(function (childNode) {
                appendSanitizedChild(fragment, buildSanitizedNode(childNode, ownerDocument));
            });
            return fragment;
        }

        if (normalizedTagName === 'A') {
            const href = sanitizeUrl(sourceNode.getAttribute('href') || '');
            if (!href) {
                const anchorFragment = ownerDocument.createDocumentFragment();
                Array.from(sourceNode.childNodes || []).forEach(function (childNode) {
                    appendSanitizedChild(anchorFragment, buildSanitizedNode(childNode, ownerDocument));
                });
                return anchorFragment;
            }

            const anchorNode = ownerDocument.createElement('a');
            anchorNode.setAttribute('href', href);

            const target = String(sourceNode.getAttribute('target') || '').trim().toLowerCase();
            if (target === '_blank') {
                anchorNode.setAttribute('target', '_blank');
                anchorNode.setAttribute('rel', 'noopener noreferrer');
            }

            Array.from(sourceNode.childNodes || []).forEach(function (childNode) {
                appendSanitizedChild(anchorNode, buildSanitizedNode(childNode, ownerDocument));
            });

            return anchorNode;
        }

        const elementNode = ownerDocument.createElement(normalizedTagName.toLowerCase());
        Array.from(sourceNode.childNodes || []).forEach(function (childNode) {
            appendSanitizedChild(elementNode, buildSanitizedNode(childNode, ownerDocument));
        });

        return elementNode;
    }

    function normalizeHtmlValue(html) {
        const rawHtml = String(html || '').trim();
        if (!rawHtml) {
            return '';
        }

        const textValue = rawHtml
            .replace(/<br\s*\/?>/gi, ' ')
            .replace(/<\/(p|li)>/gi, ' ')
            .replace(/<[^>]+>/g, ' ')
            .replace(/&nbsp;|&#160;/gi, ' ')
            .replace(/\s+/g, ' ')
            .trim();

        return textValue ? rawHtml : '';
    }

    function sanitizeHtml(html) {
        const parser = new window.DOMParser();
        const parsed = parser.parseFromString('<div>' + String(html || '') + '</div>', 'text/html');
        const sourceRoot = parsed.body && parsed.body.firstElementChild ? parsed.body.firstElementChild : parsed.body;
        const cleanDocument = document.implementation.createHTMLDocument('');
        const wrapper = cleanDocument.createElement('div');

        Array.from(sourceRoot.childNodes || []).forEach(function (childNode) {
            appendSanitizedChild(wrapper, buildSanitizedNode(childNode, cleanDocument));
        });

        const sanitized = wrapper.innerHTML
            .replace(/<p>(?:\s|&nbsp;|&#160;|<br\s*\/?>)*<\/p>/gi, '')
            .trim();

        return normalizeHtmlValue(sanitized);
    }

    function mount(container, options) {
        if (!container) {
            return null;
        }

        if (typeof container.__omoSimpleHtmlFieldDestroy === 'function') {
            container.__omoSimpleHtmlFieldDestroy();
        }

        const state = Object.assign({
            value: '',
            placeholder: 'Saisissez du contenu HTML simple.',
            disabled: false,
            height: 180
        }, options || {});

        const safeInitialValue = sanitizeHtml(state.value);
        const editorId = 'omo-html-field-' + Math.random().toString(36).slice(2);
        const textareaId = editorId + '-textarea';
        let destroyed = false;
        let initialized = false;
        let $editor = null;

        container.setAttribute('data-omo-html-field', '1');
        container.innerHTML = ''
            + '<div class="omo-simple-html-field">'
            + '  <textarea id="' + escapeHtml(textareaId) + '"></textarea>'
            + '  <div class="omo-simple-html-field__meta">Edition HTML via Summernote: gras, italic, listes et liens.</div>'
            + '</div>';

        const textarea = container.querySelector('textarea');
        if (textarea) {
            textarea.value = safeInitialValue;
        }

        function setRawValue(nextValue) {
            state.value = sanitizeHtml(nextValue);
            if (textarea) {
                textarea.value = state.value;
            }
        }

        function getValue() {
            if (initialized && $editor) {
                return sanitizeHtml($editor.summernote('code'));
            }

            return sanitizeHtml(state.value);
        }

        function setValue(nextValue) {
            setRawValue(nextValue);

            if (initialized && $editor) {
                $editor.summernote('code', state.value);
            }
        }

        function destroy() {
            destroyed = true;

            if (initialized && $editor) {
                try {
                    setRawValue($editor.summernote('code'));
                    $editor.summernote('destroy');
                } catch (error) {
                    // ignore cleanup issues
                }
            }

            initialized = false;
            $editor = null;
            delete container.__omoSimpleHtmlField;
            delete container.__omoSimpleHtmlFieldDestroy;
        }

        ensureDependencies()
            .then(function () {
                if (destroyed || !textarea) {
                    return;
                }

                $editor = window.jQuery(textarea);
                $editor.summernote({
                    lang: 'fr-FR',
                    placeholder: state.placeholder,
                    height: Number(state.height || 180),
                    dialogsInBody: true,
                    disableDragAndDrop: true,
                    toolbar: [
                        ['font', ['bold', 'italic', 'underline', 'clear']],
                        ['para', ['ul', 'ol']],
                        ['insert', ['link']]
                    ],
                    callbacks: {
                        onChange: function (contents) {
                            setRawValue(contents);
                        }
                    }
                });

                $editor.summernote('code', state.value);
                if (state.disabled) {
                    $editor.summernote('disable');
                }

                initialized = true;
            })
            .catch(function (error) {
                if (destroyed) {
                    return;
                }

                container.innerHTML = '<div class="omo-simple-html-field__meta">Impossible de charger l editeur HTML.</div>';
                if (window.console && typeof window.console.error === 'function') {
                    window.console.error(error);
                }
            });

        container.__omoSimpleHtmlField = {
            getValue: getValue,
            setValue: setValue,
            focus: function () {
                if (initialized && $editor) {
                    $editor.summernote('focus');
                }
            },
            destroy: destroy
        };
        container.__omoSimpleHtmlFieldDestroy = destroy;

        return container.__omoSimpleHtmlField;
    }

    function renderPreviewHtml(value, className) {
        ensureLocalStyles();

        const safeValue = sanitizeHtml(value);
        if (!safeValue) {
            return '';
        }

        const classes = ['omo-simple-html-render'];
        if (String(className || '').trim() !== '') {
            classes.push(String(className).trim());
        }

        return '<div class="' + escapeHtml(classes.join(' ')) + '">' + safeValue + '</div>';
    }

    window.omoSimpleHtmlField = {
        mount: mount,
        sanitizeHtml: sanitizeHtml,
        renderPreviewHtml: renderPreviewHtml
    };
})(window, document);
