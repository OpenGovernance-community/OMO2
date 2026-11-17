(function (window) {
    'use strict';
    const scriptPath = '/common/document/pv-references.js';
    window.commonPageScripts = window.commonPageScripts || {};
    if (window.commonPageScripts[scriptPath]) return;

    const controllers = new Set();
    const fields = '.omo-document-pv__point-title, .omo-document-pv__point-content, '
        + '.omo-document-pv__group-title, .common-pv-report__title, '
        + '[data-omo-pv-point-editor-host] .omo-simple-html-render, [data-omo-pv-point-editor-host] .note-editable, '
        + '[data-omo-pv-document-title-display], [data-omo-pv-document-description-display], '
        + '[data-common-pv-reference-text]';
    let labels = {
        position: 'Référence {current} sur {total}', previous: 'Référence précédente', next: 'Référence suivante',
        collapse: 'Glisser pour déplacer, cliquer pour réduire', expand: 'Glisser pour déplacer, cliquer pour déployer'
    };
    let translations;
    let lifecycleObserver;

    function updateHighlights() {
        if (!window.CSS || !CSS.highlights || !window.Highlight) return;
        const all = [], current = [];
        controllers.forEach(function (controller) {
            if (controller.panel.hidden) return;
            all.push(...controller.ranges);
            if (controller.ranges[controller.index]) current.push(controller.ranges[controller.index]);
        });
        CSS.highlights.set('pv-references', new Highlight(...all));
        CSS.highlights.set('pv-reference-current', new Highlight(...current));
    }

    function aliasKey(value) {
        return String(value || '').normalize('NFC').toLowerCase().trim().replace(/\s+/gu, ' ');
    }

    function buildMatcher(identities, viewerKey) {
        const aliases = new Map();
        identities.forEach(function (identity) {
            ['username', 'fullName', 'firstname'].forEach(function (field) {
                const key = aliasKey(identity[field]);
                if (!key) return;
                if (!aliases.has(key)) aliases.set(key, {strong: new Set(), firstnames: new Set()});
                aliases.get(key)[field === 'firstname' ? 'firstnames' : 'strong'].add(identity.key);
            });
        });
        const choices = Array.from(aliases.keys()).sort((left, right) => right.length - left.length)
            .flatMap(key => Array.from(new Set([key, key.normalize('NFD')]))).map(function (key) {
                return key.replace(/[.*+?^${}()|[\]\\]/g, '\\$&').replace(/ /g, '\\s+');
            });
        if (!choices.length) return null;
        const pattern = new RegExp('(^|[^\\p{L}\\p{M}\\p{N}_.+@-])@(' + choices.join('|')
            + ')(?![\\p{L}\\p{M}\\p{N}_@-]|\\.[\\p{L}\\p{N}])', 'giu');
        return function (text) {
            pattern.lastIndex = 0;
            const matches = [];
            for (const match of text.matchAll(pattern)) {
                const alias = aliases.get(aliasKey(match[2]));
                if (!alias) continue;
                const recipients = alias.strong.size ? alias.strong : alias.firstnames;
                if (recipients.has(viewerKey)) {
                    matches.push({start: match.index + match[1].length, end: match.index + match[0].length});
                }
            }
            return matches;
        };
    }

    // Keep inline formatting continuous, while separating paragraphs and hidden content.
    function textNodesForField(field) {
        let text = '';
        const nodes = [];
        function visit(node) {
            if (node.nodeType === Node.TEXT_NODE) {
                nodes.push({node: node, start: text.length, end: text.length + node.length});
                text += node.data;
                return;
            }
            if (!(node instanceof Element)) return;
            const style = getComputedStyle(node);
            if (node.matches('script, style, textarea, input, select, [hidden], [aria-hidden="true"]')
                || style.display === 'none' || style.visibility !== 'visible' || style.opacity === '0') {
                text += '\u0000';
                return;
            }
            const block = /^(P|DIV|LI|BR|H[1-6]|TR|TD|TH|SECTION|ARTICLE|BLOCKQUOTE)$/.test(node.tagName);
            if (block) text += '\u0000';
            node.childNodes.forEach(visit);
            if (block) text += '\u0000';
        }
        visit(field);
        return {text: text, nodes: nodes};
    }

    function scrollToRange(range) {
        const element = range.startContainer.parentElement;
        element.scrollIntoView({block: 'center', inline: 'nearest', behavior: 'instant'});
        let scroller = element;
        while (scroller && !(scroller.scrollHeight > scroller.clientHeight
            && /auto|scroll/.test(getComputedStyle(scroller).overflowY))) {
            scroller = scroller.parentElement;
        }
        const rect = range.getBoundingClientRect();
        const center = scroller ? scroller.getBoundingClientRect().top + scroller.clientHeight / 2 : innerHeight / 2;
        (scroller || window).scrollBy({top: rect.top + rect.height / 2 - center, behavior: 'instant'});
    }

    function mountFloatingControls(panel, count, buttons) {
        const handle = document.createElement('button');
        handle.type = 'button';
        handle.className = 'common-pv-references__handle generic-drag-handle';
        handle.textContent = '⠿';
        handle.setAttribute('aria-expanded', 'true');
        panel.append(handle);
        const dragShield = document.createElement('div');
        dragShield.className = 'generic-resize-shield generic-resize-shield--move';
        let collapsed = false;
        let drag = null;
        let suppressClick = false;

        function setPosition(right, top) {
            const rect = panel.getBoundingClientRect();
            const margin = 8;
            right = Math.max(margin, Math.min(right, innerWidth - rect.width - margin));
            top = Math.max(margin, Math.min(top, innerHeight - rect.height - margin));
            panel.style.setProperty('--param-floating-panel-right', right + 'px');
            panel.style.setProperty('--param-floating-panel-top', top + 'px');
            panel.classList.add('generic-floating-panel--positioned');
        }

        function keepInViewport() {
            if (panel.hidden || !panel.classList.contains('generic-floating-panel--positioned')) return;
            const rect = panel.getBoundingClientRect();
            setPosition(innerWidth - rect.right, rect.top);
        }

        function finishDrag(event) {
            if (!drag || (event && event.pointerId !== drag.id)) return;
            const pointerId = drag.id;
            const activate = event && event.type === 'pointerup' && !drag.moved;
            suppressClick = drag.moved || activate;
            drag = null;
            dragShield.remove();
            if (handle.hasPointerCapture(pointerId)) handle.releasePointerCapture(pointerId);
            if (activate) toggleCollapsed();
        }

        function toggleCollapsed() {
            const before = handle.getBoundingClientRect();
            collapsed = !collapsed;
            count.hidden = collapsed;
            buttons.forEach(button => { button.hidden = collapsed; });
            panel.classList.toggle('generic-floating-panel--compact', collapsed);
            panel.classList.toggle('generic-soft-panel--flush', collapsed);
            handle.setAttribute('aria-expanded', String(!collapsed));
            updateLabels();
            const after = handle.getBoundingClientRect();
            const rect = panel.getBoundingClientRect();
            setPosition(innerWidth - rect.right + after.right - before.right, rect.top + before.top - after.top);
        }

        handle.addEventListener('pointerdown', function (event) {
            if (event.button !== 0 || !event.isPrimary || drag) return;
            const rect = panel.getBoundingClientRect();
            drag = {id: event.pointerId, x: event.clientX, y: event.clientY,
                right: innerWidth - rect.right, top: rect.top, moved: false};
            suppressClick = false;
            handle.focus({preventScroll: true});
            handle.setPointerCapture(event.pointerId);
        });
        handle.addEventListener('pointermove', function (event) {
            if (!drag || event.pointerId !== drag.id) return;
            const deltaX = event.clientX - drag.x;
            const deltaY = event.clientY - drag.y;
            if (!drag.moved && Math.hypot(deltaX, deltaY) < 5) return;
            if (!drag.moved) document.body.append(dragShield);
            drag.moved = true;
            setPosition(drag.right - deltaX, drag.top + deltaY);
        });
        ['pointerup', 'pointercancel', 'lostpointercapture'].forEach(type => handle.addEventListener(type, finishDrag));
        handle.addEventListener('click', function (event) {
            if (suppressClick && event.detail > 0) { suppressClick = false; return; }
            toggleCollapsed();
        });
        handle.addEventListener('keydown', function (event) {
            const directions = {ArrowLeft: [20, 0], ArrowRight: [-20, 0], ArrowUp: [0, -20], ArrowDown: [0, 20]};
            const direction = directions[event.key];
            if (!direction) return;
            event.preventDefault();
            const rect = panel.getBoundingClientRect();
            setPosition(innerWidth - rect.right + direction[0], rect.top + direction[1]);
        });
        const cancelDrag = function () { finishDrag(); };
        window.addEventListener('blur', cancelDrag);
        window.addEventListener('resize', keepInViewport);
        function updateLabels() {
            const handleLabel = collapsed ? labels.expand : labels.collapse;
            handle.title = handleLabel;
            handle.setAttribute('aria-label', handleLabel);
        }
        return {
            keepInViewport: keepInViewport,
            cancelDrag: cancelDrag,
            updateLabels: updateLabels,
            destroy: function () {
                finishDrag();
                window.removeEventListener('blur', cancelDrag);
                window.removeEventListener('resize', keepInViewport);
            }
        };
    }

    window.commonPageScripts[scriptPath] = function (config, pageScript) {
        const root = pageScript.closest(config.rootSelector);
        const matchText = buildMatcher(config.identities || [], config.viewerKey);
        if (!root || !matchText) return;
        if (root.pvReferences) root.pvReferences.destroy();
        root.setAttribute('data-common-pv-reference-stage', config.stage);
        const inlineHost = root.querySelector('[data-common-pv-references-host]');
        const panel = document.createElement('nav');
        panel.className = inlineHost ? 'common-pv-references common-pv-references--inline'
            : 'common-pv-references generic-floating-panel generic-soft-panel';
        panel.hidden = true;
        const count = document.createElement('span');
        count.className = 'common-pv-references__count';
        count.setAttribute('aria-live', 'polite');
        panel.append(count);
        const buttons = [-1, 1].map(function (direction) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'generic-action-button generic-action-button--secondary generic-action-button--icon-only';
            if (inlineHost) button.classList.add('generic-action-button--compact');
            button.textContent = direction < 0 ? '↑' : '↓';
            button.addEventListener('click', function () {
                if (!controller.ranges.length) return;
                if (controller.index < 0) {
                    controller.index = direction > 0 ? 0 : controller.ranges.length - 1;
                } else {
                    controller.index = (controller.index + direction + controller.ranges.length) % controller.ranges.length;
                }
                scrollToRange(controller.ranges[controller.index]);
                controller.updateLabels();
                updateHighlights();
            });
            panel.append(button);
            return button;
        });
        (inlineHost || document.body).append(panel);
        const floatingControls = inlineHost ? null : mountFloatingControls(panel, count, buttons);
        let refreshTimeout;
        const controller = {
            root: root, panel: panel, ranges: [], index: -1,
            floatingControls: floatingControls,
            wasAvailable: false,
            setVisible: function (visible) {
                panel.hidden = !visible;
                if (inlineHost && inlineHost.hidden === visible) {
                    inlineHost.hidden = !visible;
                    inlineHost.dispatchEvent(new Event('common:pv-references-updated', {bubbles: true}));
                }
            },
            scheduleRefresh: function () {
                clearTimeout(refreshTimeout);
                refreshTimeout = setTimeout(refresh, 100);
            },
            available: function () {
                const drawer = root.closest('.omo-overlay-drawer');
                return ['review', 'validated'].includes(root.getAttribute('data-common-pv-reference-stage'))
                    && (!drawer || (drawer.classList.contains('is-open') && !drawer.classList.contains('is-peek')))
                    && root.checkVisibility({visibilityProperty: true, opacityProperty: true});
            },
            updateLabels: function () {
                const text = String(labels.position)
                    .replace('{current}', String(controller.index + 1))
                    .replace('{total}', String(controller.ranges.length));
                if (count.textContent !== text) count.textContent = text;
                panel.setAttribute('aria-label', text);
                buttons.forEach(function (button, index) {
                    const label = index === 0 ? labels.previous : labels.next;
                    button.title = label;
                    button.setAttribute('aria-label', label);
                });
                floatingControls?.updateLabels();
            },
            destroy: function () {
                floatingControls?.destroy();
                controller.setVisible(false);
                clearTimeout(refreshTimeout);
                observer.disconnect();
                panel.remove();
                controllers.delete(controller);
                delete root.pvReferences;
                updateHighlights();
                if (!controllers.size && lifecycleObserver) {
                    lifecycleObserver.disconnect();
                    lifecycleObserver = null;
                }
            }
        };
        function refresh() {
            const ranges = [];
            const previousIndex = controller.index;
            const previousRange = controller.ranges[controller.index];
            controller.wasAvailable = controller.available();
            if (controller.wasAvailable) {
                root.querySelectorAll(fields).forEach(function (field) {
                    if (!field.checkVisibility({visibilityProperty: true}) || field.parentElement.closest(fields)) return;
                    const content = textNodesForField(field);
                    matchText(content.text).forEach(function (match) {
                        const start = content.nodes.find(item => item.end > match.start);
                        const end = content.nodes.find(item => item.end >= match.end);
                        if (!start || !end) return;
                        const range = document.createRange();
                        range.setStart(start.node, match.start - start.start);
                        range.setEnd(end.node, match.end - end.start);
                        if (range.getClientRects().length) ranges.push(range);
                    });
                });
            }
            controller.ranges = ranges;
            controller.index = previousRange ? ranges.findIndex(range => range.startContainer === previousRange.startContainer
                && range.startOffset === previousRange.startOffset && range.endContainer === previousRange.endContainer
                && range.endOffset === previousRange.endOffset) : -1;
            if (controller.index < 0 && ranges.length) {
                controller.index = Math.max(0, Math.min(previousIndex, ranges.length - 1));
            }
            controller.updateLabels();
            if (!ranges.length) floatingControls?.cancelDrag();
            controller.setVisible(ranges.length > 0);
            floatingControls?.keepInViewport();
            updateHighlights();
        }
        const observer = new MutationObserver(function (mutations) {
            const relevant = mutations.some(function (mutation) {
                const target = mutation.target instanceof Element ? mutation.target : mutation.target.parentElement;
                if (!target) return false;
                if (target.closest(fields)) return true;
                if (mutation.type === 'attributes') {
                    return mutation.attributeName === 'data-common-pv-reference-stage' || !!target.querySelector(fields);
                }
                return Array.from(mutation.addedNodes).concat(Array.from(mutation.removedNodes))
                    .some(node => node instanceof Element && (node.matches(fields) || node.querySelector(fields)));
            });
            if (relevant) controller.scheduleRefresh();
        });
        observer.observe(root, {subtree: true, childList: true, characterData: true, attributes: true,
            attributeFilter: ['data-common-pv-reference-stage', 'hidden', 'open', 'class', 'style']});
        root.pvReferences = controller;
        controllers.add(controller);
        if (!lifecycleObserver) {
            lifecycleObserver = new MutationObserver(function () {
                controllers.forEach(function (item) {
                    if (!item.root.isConnected) { item.destroy(); return; }
                    if (item.available() !== item.wasAvailable) item.scheduleRefresh();
                    const hidden = !item.ranges.length || !item.available();
                    if (item.panel.hidden !== hidden) {
                        item.setVisible(!hidden);
                        if (hidden) item.floatingControls?.cancelDrag();
                        else item.floatingControls?.keepInViewport();
                        updateHighlights();
                    }
                });
            });
            lifecycleObserver.observe(document.body, {subtree: true, childList: true, attributes: true,
                attributeFilter: ['hidden', 'class', 'style', 'open']});
        }
        refresh();
        if (!translations) {
            translations = fetch('/common/jstranslation/pv_references.php', {credentials: 'same-origin'})
                .then(function (response) { if (!response.ok) throw new Error(); return response.json(); })
                .then(function (payload) {
                    labels = Object.assign({}, labels, payload);
                    controllers.forEach(item => item.updateLabels());
                }).catch(function () {});
        }
    };
})(window);
