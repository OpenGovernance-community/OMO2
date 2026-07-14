(function (window, document) {
    'use strict';

    const OMO_SIMPLE_HTML_FIELD_VERSION = '20260714-resource-embeds';

    if (
        window.omoSimpleHtmlField
        && String(window.omoSimpleHtmlField.version || '') === OMO_SIMPLE_HTML_FIELD_VERSION
    ) {
        return;
    }

    const SUMMERNOTE_VERSION = '0.8.18';
    const SUMMERNOTE_CSS_URL = 'https://cdnjs.cloudflare.com/ajax/libs/summernote/' + SUMMERNOTE_VERSION + '/summernote-lite.min.css';
    const SUMMERNOTE_JS_URL = 'https://cdnjs.cloudflare.com/ajax/libs/summernote/' + SUMMERNOTE_VERSION + '/summernote-lite.min.js';
    const SUMMERNOTE_LANG_URL = 'https://cdnjs.cloudflare.com/ajax/libs/summernote/' + SUMMERNOTE_VERSION + '/lang/summernote-fr-FR.min.js';

    let stylesInjected = false;
    let dependencyPromise = null;
    let cursorMarkerCounter = 0;

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
            + '.omo-simple-html-field .note-toolbar{position:sticky;top:0;z-index:6;border-bottom:1px solid var(--color-border,#d1d5db);background:color-mix(in srgb,var(--color-surface-alt,#f8fafc) 88%,white);border-top-left-radius:14px;border-top-right-radius:14px;padding:8px;box-shadow:0 8px 18px -18px rgba(15,23,42,.45);}'
            + '.omo-simple-html-field .note-btn{border-radius:10px;border-color:var(--color-border,#d1d5db);}'
            + '.omo-simple-html-field .note-editing-area{overflow:visible;}'
            + '.omo-simple-html-field .note-editing-area .note-editable{min-height:140px;height:auto!important;overflow-y:hidden!important;padding:14px;line-height:1.55;color:var(--color-text,#1f2937);}'
            + '.omo-simple-html-field .note-placeholder{color:var(--color-text-light,#6b7280);}'
            + '.omo-simple-html-field .note-statusbar{display:none;}'
            + '.omo-simple-html-field .note-editable h1,.omo-simple-html-render h1{margin:0 0 .6em;font-size:1.8rem;line-height:1.15;font-weight:850;color:var(--color-text,#1f2937);}'
            + '.omo-simple-html-field .note-editable h2,.omo-simple-html-render h2{margin:0 0 .55em;font-size:1.45rem;line-height:1.2;font-weight:800;color:var(--color-text,#1f2937);}'
            + '.omo-simple-html-field .note-editable h3,.omo-simple-html-render h3{margin:0 0 .5em;font-size:1.16rem;line-height:1.25;font-weight:750;color:var(--color-text,#1f2937);}'
            + '.omo-simple-html-field .note-editable blockquote,.omo-simple-html-render blockquote{margin:0 0 1em;padding:.2em 0 .2em 1em;border-left:3px solid color-mix(in srgb,var(--color-primary,#2563eb) 38%,var(--color-border,#d1d5db));color:var(--color-text-light,#6b7280);font-style:italic;}'
            + '.omo-simple-html-field .note-editable table,.omo-simple-html-render table{width:100%;max-width:100%;margin:0 0 1em;border-collapse:collapse;border-spacing:0;font-size:.97em;}'
            + '.omo-simple-html-field .note-editable th,.omo-simple-html-field .note-editable td,.omo-simple-html-render th,.omo-simple-html-render td{padding:9px 12px;border:1px solid color-mix(in srgb,var(--color-border,#d1d5db) 88%,var(--color-text,#1f2937) 12%);text-align:left;vertical-align:top;}'
            + '.omo-simple-html-field .note-editable th,.omo-simple-html-render th{background:color-mix(in srgb,var(--color-surface-alt,#f8fafc) 82%,var(--color-text,#1f2937) 18%);font-weight:700;}'
            + '.omo-simple-html-field .note-editable tbody tr:nth-child(even),.omo-simple-html-render tbody tr:nth-child(even){background:color-mix(in srgb,var(--color-surface,#fff) 92%,var(--color-surface-alt,#f8fafc) 8%);}'
            + '.omo-simple-html-field .note-editable .omo-document-embed,.omo-simple-html-render .omo-document-embed{display:block;margin:0 0 1em;padding:12px 14px;border:1px solid color-mix(in srgb,var(--color-border,#d1d5db) 88%,#2563eb 12%);border-radius:14px;background:color-mix(in srgb,var(--color-surface,#fff) 90%,#eff6ff 10%);box-shadow:0 10px 24px -20px rgba(37,99,235,.45);cursor:pointer;white-space:normal;line-height:1.5;}'
            + '.omo-simple-html-field .note-editable .omo-document-embed:last-child,.omo-simple-html-render .omo-document-embed:last-child{margin-bottom:0;}'
            + '.omo-simple-html-field .note-editable .omo-document-embed__label,.omo-simple-html-render .omo-document-embed__label{display:block;margin:0 0 6px;color:var(--color-text-light,#6b7280);font-size:12px;font-weight:600;letter-spacing:.02em;text-transform:uppercase;}'
            + '.omo-simple-html-field .note-editable .omo-document-embed__title,.omo-simple-html-render .omo-document-embed__title{display:block;margin:0;color:var(--color-text,#1f2937);font-weight:700;}'
            + '.omo-simple-html-field .note-editable .omo-document-embed__description,.omo-simple-html-render .omo-document-embed__description{display:block;margin:6px 0 0;color:var(--color-text-light,#6b7280);font-size:13px;line-height:1.5;}'
            + '.omo-simple-html-field .note-editable .omo-document-embed > p:first-child,.omo-simple-html-render .omo-document-embed > p:first-child{margin:0 0 6px;color:var(--color-text-light,#6b7280);font-size:12px;font-weight:600;letter-spacing:.02em;text-transform:uppercase;}'
            + '.omo-simple-html-field .note-editable .omo-document-embed > p:nth-child(2),.omo-simple-html-render .omo-document-embed > p:nth-child(2){margin:0;color:var(--color-text,#1f2937);font-weight:700;}'
            + '.omo-simple-html-field .note-editable .omo-document-embed > p:nth-child(3),.omo-simple-html-render .omo-document-embed > p:nth-child(3){margin:6px 0 0;color:var(--color-text-light,#6b7280);font-size:13px;line-height:1.5;}'
            + '.omo-simple-html-field .note-editable .omo-document-embed > strong:first-child,.omo-simple-html-render .omo-document-embed > strong:first-child{display:block;margin:0 0 6px;color:var(--color-text-light,#6b7280);font-size:12px;font-weight:600;letter-spacing:.02em;text-transform:uppercase;}'
            + '.omo-simple-html-field .note-editable .omo-document-embed > strong:nth-of-type(2),.omo-simple-html-render .omo-document-embed > strong:nth-of-type(2){display:block;margin:0;color:var(--color-text,#1f2937);font-weight:700;}'
            + '.omo-pv-editor .omo-simple-html-field .note-editable .omo-document-embed,.omo-pv-editor .omo-simple-html-field .note-editable .omo-decision-embed,.omo-pv-editor .omo-simple-html-field .note-editable .omo-event-embed,.omo-pv-editor .omo-simple-html-render .omo-document-embed,.omo-pv-editor .omo-simple-html-render .omo-decision-embed,.omo-pv-editor .omo-simple-html-render .omo-event-embed{position:relative;min-height:54px;margin-bottom:10px;padding:9px 12px 9px 58px;}'
            + '.omo-pv-editor .omo-simple-html-field .note-editable .omo-document-embed:before,.omo-pv-editor .omo-simple-html-field .note-editable .omo-decision-embed:before,.omo-pv-editor .omo-simple-html-field .note-editable .omo-event-embed:before,.omo-pv-editor .omo-simple-html-render .omo-document-embed:before,.omo-pv-editor .omo-simple-html-render .omo-decision-embed:before,.omo-pv-editor .omo-simple-html-render .omo-event-embed:before{content:"";position:absolute;top:50%;left:14px;width:30px;height:30px;transform:translateY(-50%);background:var(--color-primary,#2563eb);-webkit-mask:url("/omo/images/tools/documents-folder.png") center/contain no-repeat;mask:url("/omo/images/tools/documents-folder.png") center/contain no-repeat;}'
            + '.omo-pv-editor .omo-simple-html-field .note-editable .omo-decision-embed:before,.omo-pv-editor .omo-simple-html-render .omo-decision-embed:before{background:#7c3aed;-webkit-mask-image:url("/omo/images/tools/decision.png");mask-image:url("/omo/images/tools/decision.png");}'
            + '.omo-pv-editor .omo-simple-html-field .note-editable .omo-event-embed:before,.omo-pv-editor .omo-simple-html-render .omo-event-embed:before{background:#0f766e;-webkit-mask-image:url("/omo/images/tools/calendar.png");mask-image:url("/omo/images/tools/calendar.png");}'
            + '.omo-pv-editor .omo-simple-html-field .note-editable .omo-document-embed>strong:first-of-type:not(:only-of-type),.omo-pv-editor .omo-simple-html-field .note-editable .omo-decision-embed>strong:first-of-type:not(:only-of-type),.omo-pv-editor .omo-simple-html-render .omo-document-embed__label,.omo-pv-editor .omo-simple-html-render .omo-decision-embed__label{display:none;}'
            + '.omo-pv-editor .omo-simple-html-field .note-editable .omo-document-embed>strong:last-of-type,.omo-pv-editor .omo-simple-html-field .note-editable .omo-decision-embed>strong:last-of-type,.omo-pv-editor .omo-simple-html-field .note-editable .omo-event-embed>strong:last-of-type,.omo-pv-editor .omo-simple-html-render .omo-document-embed__title,.omo-pv-editor .omo-simple-html-render .omo-decision-embed__title,.omo-pv-editor .omo-simple-html-render .omo-event-embed__title{display:block;overflow:hidden;-webkit-box-orient:vertical;-webkit-line-clamp:1;line-clamp:1;text-overflow:ellipsis;}'
            + '.omo-pv-editor .omo-simple-html-field .note-editable .omo-document-embed>em,.omo-pv-editor .omo-simple-html-field .note-editable .omo-event-embed>em,.omo-pv-editor .omo-simple-html-render .omo-document-embed__description,.omo-pv-editor .omo-simple-html-render .omo-event-embed__summary{display:-webkit-box;overflow:hidden;margin-top:3px;color:var(--color-text-light,#6b7280);font-size:12px;font-style:normal;line-height:1.3;-webkit-box-orient:vertical;-webkit-line-clamp:2;line-clamp:2;}'
            + '.omo-pv-editor .omo-simple-html-field .note-editable p:has(>.omo-document-embed),.omo-pv-editor .omo-simple-html-field .note-editable p:has(>.omo-decision-embed),.omo-pv-editor .omo-simple-html-field .note-editable p:has(>.omo-event-embed){margin:0 0 10px;}'
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

    function getElementAttributeValue(element, attributeName) {
        if (!element || element.nodeType !== 1 || !element.hasAttribute(attributeName)) {
            return '';
        }

        return String(element.getAttribute(attributeName) || '');
    }

    function getDocumentEmbedElementId(element) {
        const rawValue = getElementAttributeValue(element, 'data-omo-document-id').trim();
        const parsed = Number.parseInt(rawValue, 10);
        return Number.isInteger(parsed) && parsed > 0 ? parsed : 0;
    }

    function getDecisionEmbedElementId(element) {
        const rawValue = getElementAttributeValue(element, 'data-omo-decision-id').trim();
        const parsed = Number.parseInt(rawValue, 10);
        return Number.isInteger(parsed) && parsed > 0 ? parsed : 0;
    }

    function getEventEmbedElementId(element) {
        const rawValue = getElementAttributeValue(element, 'data-omo-event-id').trim();
        const parsed = Number.parseInt(rawValue, 10);
        return Number.isInteger(parsed) && parsed > 0 ? parsed : 0;
    }

    function isTemporaryCursorMarkerElement(element) {
        return !!(element && element.nodeType === 1 && getElementAttributeValue(element, 'data-omo-cursor-marker').trim() !== '');
    }

    function isAllowedDocumentEmbedElement(element) {
        if (!element || element.nodeType !== 1) {
            return false;
        }

        return getElementAttributeValue(element, 'data-omo-embed-type').trim() === 'document'
            && getDocumentEmbedElementId(element) > 0;
    }

    function isAllowedDecisionEmbedElement(element) {
        if (!element || element.nodeType !== 1) {
            return false;
        }

        return getElementAttributeValue(element, 'data-omo-embed-type').trim() === 'decision'
            && getDecisionEmbedElementId(element) > 0;
    }

    function isAllowedEventEmbedElement(element) {
        if (!element || element.nodeType !== 1) {
            return false;
        }

        return getElementAttributeValue(element, 'data-omo-embed-type').trim() === 'event'
            && getEventEmbedElementId(element) > 0;
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

        if (isTemporaryCursorMarkerElement(sourceNode)) {
            return ownerDocument.createDocumentFragment();
        }

        if (isAllowedDocumentEmbedElement(sourceNode)) {
            const embedNode = ownerDocument.createElement('span');
            embedNode.setAttribute('class', 'omo-document-embed');
            embedNode.setAttribute('contenteditable', 'false');
            embedNode.setAttribute('data-omo-embed-type', 'document');
            embedNode.setAttribute('data-omo-document-id', String(getDocumentEmbedElementId(sourceNode)));

            const title = getElementAttributeValue(sourceNode, 'data-omo-document-title').trim();
            if (title) {
                embedNode.setAttribute('data-omo-document-title', title);
            }

            const description = getElementAttributeValue(sourceNode, 'data-omo-document-description').trim();
            if (description) {
                embedNode.setAttribute('data-omo-document-description', description);
            }

            Array.from(sourceNode.childNodes || []).forEach(function (childNode) {
                appendSanitizedChild(embedNode, buildSanitizedNode(childNode, ownerDocument));
            });

            return embedNode;
        }

        if (isAllowedDecisionEmbedElement(sourceNode)) {
            const embedNode = ownerDocument.createElement('span');
            embedNode.setAttribute('class', 'omo-decision-embed');
            embedNode.setAttribute('contenteditable', 'false');
            embedNode.setAttribute('data-omo-embed-type', 'decision');
            embedNode.setAttribute('data-omo-decision-id', String(getDecisionEmbedElementId(sourceNode)));

            const title = getElementAttributeValue(sourceNode, 'data-omo-decision-title').trim();
            if (title) {
                embedNode.setAttribute('data-omo-decision-title', title);
            }

            const type = getElementAttributeValue(sourceNode, 'data-omo-decision-type').trim();
            if (type) {
                embedNode.setAttribute('data-omo-decision-type', type);
            }

            Array.from(sourceNode.childNodes || []).forEach(function (childNode) {
                appendSanitizedChild(embedNode, buildSanitizedNode(childNode, ownerDocument));
            });

            return embedNode;
        }

        if (isAllowedEventEmbedElement(sourceNode)) {
            const embedNode = ownerDocument.createElement('span');
            embedNode.setAttribute('class', 'omo-event-embed');
            embedNode.setAttribute('contenteditable', 'false');
            embedNode.setAttribute('data-omo-embed-type', 'event');
            embedNode.setAttribute('data-omo-event-id', String(getEventEmbedElementId(sourceNode)));

            ['title', 'schedule', 'description'].forEach(function (attributeName) {
                const value = getElementAttributeValue(sourceNode, 'data-omo-event-' + attributeName).trim();
                if (value) {
                    embedNode.setAttribute('data-omo-event-' + attributeName, value);
                }
            });

            Array.from(sourceNode.childNodes || []).forEach(function (childNode) {
                appendSanitizedChild(embedNode, buildSanitizedNode(childNode, ownerDocument));
            });

            return embedNode;
        }

        const normalizedTagName = sourceTagName === 'DIV' ? 'P' : sourceTagName;
        const allowedTags = {
            P: true,
            H1: true,
            H2: true,
            H3: true,
            BLOCKQUOTE: true,
            TABLE: true,
            THEAD: true,
            TBODY: true,
            TR: true,
            TH: true,
            TD: true,
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
        if (normalizedTagName === 'TH' || normalizedTagName === 'TD') {
            const colspan = Number.parseInt(sourceNode.getAttribute('colspan') || '', 10);
            const rowspan = Number.parseInt(sourceNode.getAttribute('rowspan') || '', 10);

            if (Number.isInteger(colspan) && colspan > 1) {
                elementNode.setAttribute('colspan', String(colspan));
            }

            if (Number.isInteger(rowspan) && rowspan > 1) {
                elementNode.setAttribute('rowspan', String(rowspan));
            }
        }

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

    function buildTextInsertionHtml(text) {
        const normalizedText = String(text || '')
            .replace(/\r\n?/g, '\n')
            .trim();

        if (!normalizedText) {
            return '';
        }

        return escapeHtml(normalizedText).replace(/\n/g, '<br>');
    }

    function normalizePlainText(text) {
        return String(text || '')
            .replace(/\r\n?/g, '\n')
            .replace(/\u00a0/g, ' ')
            .replace(/[ \t]+\n/g, '\n')
            .replace(/\n{3,}/g, '\n\n')
            .replace(/[ \t]{2,}/g, ' ')
            .trim();
    }

    function htmlToPlainText(html) {
        const temp = document.createElement('div');
        temp.innerHTML = sanitizeHtml(html);
        return normalizePlainText(temp.innerText || temp.textContent || '');
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
            height: 180,
            minHeight: null,
            customButtons: [],
            onChange: null,
            onDoubleClick: null
        }, options || {});

        const safeInitialValue = sanitizeHtml(state.value);
        const editorId = 'omo-html-field-' + Math.random().toString(36).slice(2);
        const textareaId = editorId + '-textarea';
        let destroyed = false;
        let initialized = false;
        let $editor = null;
        let nativeSavedRange = null;
        const toolbarButtons = {};
        const toolbarButtonState = {};
        const customToolbarButtons = Array.isArray(state.customButtons)
            ? state.customButtons.filter(function (buttonConfig) {
                return buttonConfig && String(buttonConfig.name || '').trim() !== '';
            }).map(function (buttonConfig) {
                const normalizedConfig = Object.assign({}, buttonConfig);
                normalizedConfig.name = String(buttonConfig.name || '').trim();
                normalizedConfig.group = String(buttonConfig.group || 'custom').trim() || 'custom';
                normalizedConfig.label = String(buttonConfig.label || buttonConfig.name || '').trim() || normalizedConfig.name;
                normalizedConfig.title = String(buttonConfig.title || buttonConfig.label || buttonConfig.name || '').trim() || normalizedConfig.label;
                normalizedConfig.className = String(buttonConfig.className || '').trim();
                return normalizedConfig;
            })
            : [];

        container.setAttribute('data-omo-html-field', '1');
        container.innerHTML = ''
            + '<div class="omo-simple-html-field">'
            + '  <textarea id="' + escapeHtml(textareaId) + '"></textarea>'
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

        function getEditableElement() {
            return container.querySelector('.note-editable');
        }

        function resizeEditableToContent() {
            const editable = getEditableElement();
            if (!editable) {
                return;
            }

            const minimumHeight = Math.max(80, Number(state.minHeight || state.height || 180));
            editable.style.minHeight = minimumHeight + 'px';
            editable.style.height = 'auto';
            editable.style.overflowY = 'hidden';
            editable.style.height = Math.max(minimumHeight, editable.scrollHeight) + 'px';
        }

        function scheduleResizeEditableToContent() {
            const schedule = typeof window.requestAnimationFrame === 'function'
                ? window.requestAnimationFrame.bind(window)
                : function (callback) { window.setTimeout(callback, 16); };

            schedule(function () {
                if (!destroyed) {
                    resizeEditableToContent();
                }
            });
        }

        function cloneRange(range) {
            if (!range || typeof range.cloneRange !== 'function') {
                return null;
            }

            try {
                return range.cloneRange();
            } catch (error) {
                return null;
            }
        }

        function captureCurrentSelectionRange() {
            const selection = window.getSelection ? window.getSelection() : null;
            const editable = getEditableElement();

            if (!selection || selection.rangeCount === 0 || !editable) {
                return null;
            }

            const range = selection.getRangeAt(0);
            const commonNode = range.commonAncestorContainer;
            if (commonNode !== editable && !editable.contains(commonNode)) {
                return null;
            }

            return cloneRange(range);
        }

        function restoreNativeSavedRange() {
            const editable = getEditableElement();
            const selection = window.getSelection ? window.getSelection() : null;
            if (!editable || !selection || !nativeSavedRange) {
                return false;
            }

            try {
                const commonNode = nativeSavedRange.commonAncestorContainer;
                if (commonNode !== editable && !editable.contains(commonNode)) {
                    return false;
                }

                selection.removeAllRanges();
                selection.addRange(nativeSavedRange);
                return true;
            } catch (error) {
                return false;
            }
        }

        function restoreRange() {
            if (initialized && $editor) {
                try {
                    $editor.summernote('restoreRange');
                } catch (error) {
                    // ignore selection restore issues
                }
            }

            restoreNativeSavedRange();
        }

        function getSelectionRange() {
            restoreRange();
            const selection = window.getSelection ? window.getSelection() : null;
            const editable = getEditableElement();

            if (!selection || selection.rangeCount === 0) {
                return null;
            }

            const range = selection.getRangeAt(0);
            if (!editable) {
                return range;
            }

            const commonNode = range.commonAncestorContainer;
            if (commonNode === editable || editable.contains(commonNode)) {
                return range;
            }

            return null;
        }

        function setValue(nextValue) {
            setRawValue(nextValue);

            if (initialized && $editor) {
                $editor.summernote('code', state.value);
                saveRange();
                scheduleResizeEditableToContent();
            }

            if (typeof state.onChange === 'function') {
                try {
                    state.onChange(getValue(), container.__omoSimpleHtmlField || null);
                } catch (error) {
                }
            }
        }

        function saveRange() {
            if (initialized && $editor) {
                try {
                    $editor.summernote('saveRange');
                } catch (error) {
                    // ignore selection save issues
                }
            }

            nativeSavedRange = captureCurrentSelectionRange();
        }

        function emitChange() {
            if (typeof state.onChange === 'function') {
                try {
                    state.onChange(getValue(), container.__omoSimpleHtmlField || null);
                } catch (error) {
                }
            }
        }

        function emitDoubleClick(targetNode, event) {
            if (typeof state.onDoubleClick === 'function') {
                try {
                    state.onDoubleClick({
                        target: targetNode || null,
                        event: event || null,
                        api: container.__omoSimpleHtmlField || null
                    });
                } catch (error) {
                }
            }
        }

        function applyToolbarButtonState(name, nextState) {
            const buttonName = String(name || '').trim();
            if (!buttonName) {
                return;
            }

            toolbarButtonState[buttonName] = Object.assign({}, toolbarButtonState[buttonName] || {}, nextState || {});
            const $button = toolbarButtons[buttonName];
            if (!$button || !$button.length) {
                return;
            }

            const resolvedState = toolbarButtonState[buttonName];
            const label = resolvedState && resolvedState.label !== undefined
                ? String(resolvedState.label || '')
                : null;
            const title = resolvedState && resolvedState.title !== undefined
                ? String(resolvedState.title || '')
                : null;
            const isDisabled = !!(resolvedState && resolvedState.disabled);
            const isHidden = !!(resolvedState && resolvedState.hidden);
            const isActive = !!(resolvedState && resolvedState.active);

            if (label !== null) {
                $button.html(escapeHtml(label));
            }

            if (title !== null) {
                $button.attr('title', title);
                $button.attr('aria-label', title);
            }

            $button.prop('disabled', isDisabled);
            $button.toggleClass('disabled', isDisabled);
            $button.attr('aria-disabled', isDisabled ? 'true' : 'false');
            $button.toggleClass('is-active', isActive);
            $button.attr('aria-hidden', isHidden ? 'true' : 'false');
            $button.css('display', isHidden ? 'none' : '');

            const $group = $button.closest('.note-btn-group');
            if ($group && $group.length) {
                const hasVisibleButtons = $group.find('button').toArray().some(function (groupButton) {
                    return window.getComputedStyle(groupButton).display !== 'none';
                });
                $group.css('display', hasVisibleButtons ? '' : 'none');
            }
        }

        function insertHtmlAtCursor(nextHtml) {
            const safeHtml = sanitizeHtml(nextHtml);
            if (!safeHtml) {
                return '';
            }

            if (initialized && $editor) {
                try {
                    $editor.summernote('focus');
                    restoreRange();

                    const selectionRange = getSelectionRange();
                    if (selectionRange) {
                        const temp = document.createElement('div');
                        const selection = window.getSelection ? window.getSelection() : null;
                        temp.innerHTML = safeHtml;

                        const nodes = Array.from(temp.childNodes || []);
                        let lastInsertedNode = null;
                        const fragment = document.createDocumentFragment();

                        nodes.forEach(function (node) {
                            lastInsertedNode = node;
                            fragment.appendChild(node);
                        });

                        selectionRange.deleteContents();
                        selectionRange.insertNode(fragment);

                        if (selection && lastInsertedNode) {
                            const collapsedRange = document.createRange();
                            collapsedRange.setStartAfter(lastInsertedNode);
                            collapsedRange.collapse(true);
                            selection.removeAllRanges();
                            selection.addRange(collapsedRange);
                        }
                    } else {
                        $editor.summernote('pasteHTML', safeHtml);
                    }

                    saveRange();
                    setRawValue($editor.summernote('code'));
                } catch (error) {
                    setRawValue((state.value || '') + safeHtml);
                    $editor.summernote('code', state.value);
                    saveRange();
                }

                return safeHtml;
            }

            setRawValue((state.value || '') + safeHtml);
            return safeHtml;
        }

        function insertTextAtCursor(text) {
            return insertHtmlAtCursor(buildTextInsertionHtml(text));
        }

        function replaceNodeWithHtml(targetNode, nextHtml) {
            const safeHtml = sanitizeHtml(nextHtml);
            const editable = getEditableElement();

            if (!safeHtml || !editable || !targetNode || !editable.contains(targetNode)) {
                return insertHtmlAtCursor(safeHtml);
            }

            const temp = document.createElement('div');
            temp.innerHTML = safeHtml;

            const nodes = Array.from(temp.childNodes || []);
            if (!nodes.length) {
                return '';
            }

            const fragment = document.createDocumentFragment();
            let lastInsertedNode = null;
            nodes.forEach(function (node) {
                lastInsertedNode = node;
                fragment.appendChild(node);
            });

            targetNode.replaceWith(fragment);

            if (window.getSelection && lastInsertedNode) {
                const selection = window.getSelection();
                const range = document.createRange();
                range.setStartAfter(lastInsertedNode);
                range.collapse(true);
                selection.removeAllRanges();
                selection.addRange(range);
            }

            saveRange();

            if (initialized && $editor) {
                setRawValue($editor.summernote('code'));
            } else {
                setRawValue(editable.innerHTML);
            }

            emitChange();
            return safeHtml;
        }

        function buildCursorMarkerNode() {
            const markerNode = document.createElement('span');
            cursorMarkerCounter += 1;
            markerNode.setAttribute('data-omo-cursor-marker', 'omo-cursor-marker-' + String(cursorMarkerCounter));
            markerNode.setAttribute('contenteditable', 'false');
            markerNode.className = 'omo-html-cursor-marker';
            return markerNode;
        }

        function createTemporaryCursorMarker() {
            const editable = getEditableElement();
            if (!editable) {
                return null;
            }

            restoreRange();
            let range = getSelectionRange();
            if (!range) {
                range = document.createRange();
                range.selectNodeContents(editable);
                range.collapse(false);
            } else {
                range = cloneRange(range) || range;
            }

            const markerNode = buildCursorMarkerNode();

            try {
                range.deleteContents();
                range.insertNode(markerNode);
            } catch (error) {
                editable.appendChild(markerNode);
            }

            if (window.getSelection) {
                const selection = window.getSelection();
                const caretRange = document.createRange();
                caretRange.setStartAfter(markerNode);
                caretRange.collapse(true);
                selection.removeAllRanges();
                selection.addRange(caretRange);
            }

            saveRange();
            return markerNode;
        }

        function replaceMarkerWithHtml(markerNode, nextHtml) {
            const editable = getEditableElement();
            const safeHtml = sanitizeHtml(nextHtml);

            if (!editable || !markerNode || !editable.contains(markerNode)) {
                return insertHtmlAtCursor(safeHtml);
            }

            const temp = document.createElement('div');
            temp.innerHTML = safeHtml;
            const nodes = Array.from(temp.childNodes || []);
            const fragment = document.createDocumentFragment();
            let lastInsertedNode = null;

            nodes.forEach(function (node) {
                lastInsertedNode = node;
                fragment.appendChild(node);
            });

            markerNode.replaceWith(fragment);

            if (window.getSelection) {
                const selection = window.getSelection();
                const range = document.createRange();
                if (lastInsertedNode) {
                    range.setStartAfter(lastInsertedNode);
                } else {
                    range.selectNodeContents(editable);
                    range.collapse(false);
                }
                range.collapse(true);
                selection.removeAllRanges();
                selection.addRange(range);
            }

            saveRange();

            if (initialized && $editor) {
                setRawValue($editor.summernote('code'));
            } else {
                setRawValue(editable.innerHTML);
            }

            emitChange();
            return safeHtml;
        }

        function removeTemporaryMarker(markerNode) {
            const editable = getEditableElement();
            if (!editable || !markerNode || !editable.contains(markerNode)) {
                return false;
            }

            if (window.getSelection) {
                const selection = window.getSelection();
                const range = document.createRange();
                range.setStartBefore(markerNode);
                range.collapse(true);
                selection.removeAllRanges();
                selection.addRange(range);
            }

            markerNode.remove();
            saveRange();
            return true;
        }

        function removeNode(targetNode) {
            const editable = getEditableElement();
            if (!editable || !targetNode || !editable.contains(targetNode)) {
                return false;
            }

            const nextSibling = targetNode.nextSibling;
            const previousSibling = targetNode.previousSibling;
            targetNode.remove();

            if (window.getSelection) {
                const selection = window.getSelection();
                const range = document.createRange();

                if (nextSibling && editable.contains(nextSibling)) {
                    range.setStartBefore(nextSibling);
                } else if (previousSibling && editable.contains(previousSibling)) {
                    range.setStartAfter(previousSibling);
                } else {
                    range.selectNodeContents(editable);
                    range.collapse(false);
                }

                range.collapse(true);
                selection.removeAllRanges();
                selection.addRange(range);
            }

            saveRange();

            if (initialized && $editor) {
                setRawValue($editor.summernote('code'));
            } else {
                setRawValue(editable.innerHTML);
            }

            emitChange();
            return true;
        }

        function getSelectedText() {
            const range = getSelectionRange();
            if (!range) {
                return '';
            }

            const fragment = range.cloneContents();
            const temp = document.createElement('div');
            temp.appendChild(fragment);

            return normalizePlainText(temp.innerText || temp.textContent || '');
        }

        function hasSelection() {
            const range = getSelectionRange();
            return !!(range && !range.collapsed && normalizePlainText(range.toString()) !== '');
        }

        function getPlainText() {
            return htmlToPlainText(getValue());
        }

        function replaceSelectionWithText(text) {
            return insertTextAtCursor(text);
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

                const toolbar = [
                    ['style', ['style']],
                    ['font', ['bold', 'italic', 'underline', 'clear']],
                    ['para', ['ul', 'ol']],
                    ['insert', ['link', 'table']]
                ];
                const toolbarGroups = {};
                const buttonsConfig = {};

                customToolbarButtons.forEach(function (buttonConfig) {
                    if (!toolbarGroups[buttonConfig.group]) {
                        toolbarGroups[buttonConfig.group] = [];
                    }

                    toolbarGroups[buttonConfig.group].push(buttonConfig.name);
                    buttonsConfig[buttonConfig.name] = function () {
                        const ui = window.jQuery && window.jQuery.summernote
                            ? window.jQuery.summernote.ui
                            : null;

                        if (!ui) {
                            return window.jQuery('<button type="button"></button>').text(buttonConfig.label);
                        }

                        const $button = ui.button({
                            contents: escapeHtml(buttonConfig.label),
                            tooltip: buttonConfig.title,
                            className: buttonConfig.className,
                            click: function (event) {
                                if (typeof buttonConfig.onClick === 'function') {
                                    buttonConfig.onClick({
                                        event: event,
                                        name: buttonConfig.name,
                                        api: container.__omoSimpleHtmlField || null
                                    });
                                }
                            }
                        }).render();

                        toolbarButtons[buttonConfig.name] = $button;
                        if ($button && $button.length) {
                            const buttonNode = $button.get(0);
                            if (buttonNode) {
                                buttonNode.addEventListener('mousedown', saveRange);
                                buttonNode.addEventListener('pointerdown', saveRange);
                            }
                        }
                        $button.attr('data-omo-toolbar-button-name', buttonConfig.name);
                        applyToolbarButtonState(buttonConfig.name, {
                            label: buttonConfig.label,
                            title: buttonConfig.title,
                            hidden: !!buttonConfig.hidden,
                            disabled: !!buttonConfig.disabled,
                            active: !!buttonConfig.active
                        });

                        return $button;
                    };
                });

                Object.keys(toolbarGroups).forEach(function (groupName) {
                    toolbar.push([groupName, toolbarGroups[groupName]]);
                });

                $editor = window.jQuery(textarea);
                $editor.summernote({
                    lang: 'fr-FR',
                    placeholder: state.placeholder,
                    minHeight: Math.max(80, Number(state.minHeight || state.height || 180)),
                    maxHeight: null,
                    dialogsInBody: true,
                    disableDragAndDrop: true,
                    styleTags: [
                        'p',
                        { title: 'Titre 1', tag: 'h1', className: '', value: 'h1' },
                        { title: 'Titre 2', tag: 'h2', className: '', value: 'h2' },
                        { title: 'Titre 3', tag: 'h3', className: '', value: 'h3' },
                        { title: 'Citation', tag: 'blockquote', className: '', value: 'blockquote' }
                    ],
                    toolbar: toolbar,
                    buttons: buttonsConfig,
                    callbacks: {
                        onChange: function (contents) {
                            setRawValue(contents);
                            saveRange();
                            scheduleResizeEditableToContent();
                            emitChange();
                        },
                        onFocus: function () {
                            saveRange();
                        },
                        onBlur: function () {
                            saveRange();
                        },
                        onKeyup: function () {
                            saveRange();
                            scheduleResizeEditableToContent();
                        },
                        onMouseup: function () {
                            saveRange();
                        }
                    }
                });

                $editor.summernote('code', state.value);
                if (state.disabled) {
                    $editor.summernote('disable');
                }

                initialized = true;
                saveRange();
                scheduleResizeEditableToContent();

                const editable = getEditableElement();
                if (editable) {
                    editable.addEventListener('input', scheduleResizeEditableToContent);
                    editable.addEventListener('dblclick', function (event) {
                        saveRange();
                        emitDoubleClick(event.target || null, event);
                    });
                }
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
            version: OMO_SIMPLE_HTML_FIELD_VERSION,
            getValue: getValue,
            setValue: setValue,
            focus: function () {
                if (initialized && $editor) {
                    $editor.summernote('focus');
                }
            },
            saveRange: saveRange,
            restoreRange: restoreRange,
            insertHtmlAtCursor: insertHtmlAtCursor,
            createTemporaryCursorMarker: createTemporaryCursorMarker,
            replaceMarkerWithHtml: replaceMarkerWithHtml,
            removeTemporaryMarker: removeTemporaryMarker,
            insertTextAtCursor: insertTextAtCursor,
            replaceNodeWithHtml: replaceNodeWithHtml,
            removeNode: removeNode,
            replaceSelectionWithText: replaceSelectionWithText,
            getSelectedText: getSelectedText,
            hasSelection: hasSelection,
            getPlainText: getPlainText,
            getEditableElement: getEditableElement,
            setToolbarButtonState: applyToolbarButtonState,
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
        version: OMO_SIMPLE_HTML_FIELD_VERSION,
        mount: mount,
        sanitizeHtml: sanitizeHtml,
        renderPreviewHtml: renderPreviewHtml
    };
})(window, document);
