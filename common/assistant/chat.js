(() => {
    'use strict';
    const root = document.querySelector('[data-organization-assistant]');
    if (!root || root.dataset.mounted) return;
    root.dataset.mounted = '1';
    const config = JSON.parse(root.dataset.config);
    const find = (name) => root.querySelector(`[data-assistant-${name}]`);
    const panel = document.getElementById('organization-assistant-panel');
    const input = document.getElementById('organization-assistant-question');
    const toggle = find('toggle');
    const log = find('messages');
    const progress = find('progress');
    const conversation = root.querySelector('.organization-assistant__conversation');
    const newId = () => Array.from(crypto.getRandomValues(new Uint8Array(16)), (v) => v.toString(16).padStart(2, '0')).join('');
    let conversationId = newId();
    let busy = false;
    const stateKey = 'common_assistant_chat_' + config.organizationId;
    let savedState = null;
    try {
        const saved = JSON.parse(sessionStorage.getItem(stateKey));
        if (saved?.scope === config.storageScope && /^[a-f0-9]{32}$/.test(saved.conversationId)) savedState = saved;
        else sessionStorage.removeItem(stateKey);
    } catch (_) { /* Storage can be disabled or contain an obsolete value. */ }
    if (savedState) conversationId = savedState.conversationId;
    let pendingQuestion = typeof savedState?.pendingQuestion === 'string' ? savedState.pendingQuestion.slice(0, 4000) : '';
    let scrollPosition = Number.isFinite(savedState?.scroll) ? Math.max(0, savedState.scroll) : 0;
    let persistEnabled = !savedState;
    let restoreBlocked = Boolean(savedState);
    let restoreTimer;
    const saveState = () => {
        if (!persistEnabled) return;
        try {
            sessionStorage.setItem(stateKey, JSON.stringify({scope: config.storageScope, conversationId,
                draft: input.value, open: !panel.hidden, scroll: scrollPosition, pendingQuestion,
                sources: Array.from(log.querySelectorAll('article > details'), node => node.open)}));
        } catch (_) { /* The live chat still works without browser storage. */ }
    };
    input.addEventListener('input', saveState);
    conversation.addEventListener('scroll', () => {
        if (!panel.hidden) { scrollPosition = conversation.scrollTop; saveState(); }
    }, {passive: true});
    log.addEventListener('toggle', saveState, true);
    window.addEventListener('pagehide', () => { saveState(); clearTimeout(restoreTimer); });
    const setBusy = (value) => {
        busy = value;
        find('send').disabled = value; find('new').disabled = value; input.disabled = value;
    };
    const sessionAction = async (action) => {
        const response = await fetch(config.endpoint, {method: 'POST', credentials: 'same-origin',
            headers: {'Content-Type': 'application/json'}, signal: AbortSignal.timeout(15000),
            body: JSON.stringify({action, organizationId: config.organizationId, csrf: config.csrf, conversationId})});
        const result = await response.json();
        if (!response.ok || !result.status || result.organizationId !== config.organizationId || result.conversationId !== conversationId)
            throw new Error(result.message || config.text.context);
        return result;
    };
    const resize = find('resize');
    let preferredWidth = 440;
    let drag = null;
    const resizeShield = document.createElement('div');
    resizeShield.className = 'generic-resize-shield';
    resizeShield.setAttribute('aria-hidden', 'true');
    try {
        const saved = Number(localStorage.getItem('common_assistant_width'));
        if (Number.isFinite(saved) && saved >= 360) preferredWidth = Math.min(1100, saved);
    } catch (_) { /* Storage can be disabled. */ }
    const resizeBounds = () => {
        const max = Math.max(0, Math.min(1100, window.innerWidth - 40));
        return {min: Math.min(360, max), max};
    };
    const applyWidth = () => {
        const {min, max} = resizeBounds();
        const width = Math.round(Math.max(min, Math.min(max, preferredWidth)));
        panel.style.setProperty('--param-floating-panel-width', width + 'px');
        resize.setAttribute('aria-valuemin', min);
        resize.setAttribute('aria-valuemax', max);
        resize.setAttribute('aria-valuenow', width);
        resize.hidden = min === max;
    };
    const saveWidth = () => {
        try { localStorage.setItem('common_assistant_width', String(preferredWidth)); } catch (_) { /* Optional preference. */ }
    };
    resize.addEventListener('pointerdown', (event) => {
        if (event.button !== 0 || drag) return;
        event.preventDefault();
        drag = {id: event.pointerId, x: event.clientX, width: panel.getBoundingClientRect().width};
        // Keep the pointer in this document when dragging across embedded video/documents.
        document.body.append(resizeShield);
        resize.setPointerCapture(event.pointerId);
        resize.classList.add('is-resizing');
        resize.focus();
    });
    resize.addEventListener('pointermove', (event) => {
        if (!drag || drag.id !== event.pointerId) return;
        const {min, max} = resizeBounds();
        preferredWidth = Math.max(min, Math.min(max, drag.width + drag.x - event.clientX));
        applyWidth();
    });
    const finishResize = (event) => {
        if (!drag || drag.id !== event.pointerId) return;
        drag = null;
        resizeShield.remove();
        resize.classList.remove('is-resizing');
        if (resize.hasPointerCapture(event.pointerId)) resize.releasePointerCapture(event.pointerId);
        saveWidth();
    };
    ['pointerup', 'pointercancel', 'lostpointercapture'].forEach(type => resize.addEventListener(type, finishResize));
    resize.addEventListener('keydown', (event) => {
        if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
        event.preventDefault();
        const {min, max} = resizeBounds();
        const width = panel.getBoundingClientRect().width;
        preferredWidth = event.key === 'Home' ? min : event.key === 'End' ? max
            : Math.max(min, Math.min(max, width + (event.key === 'ArrowLeft' ? 40 : -40)));
        applyWidth(); saveWidth();
    });
    window.addEventListener('resize', applyWidth);
    window.addEventListener('blur', () => { if (drag) finishResize({pointerId: drag.id}); });
    applyWidth();
    const setOpen = (open, focus = true) => {
        if (!open && drag) finishResize({pointerId: drag.id});
        if (!panel.hidden) scrollPosition = conversation.scrollTop;
        panel.hidden = !open;
        toggle.setAttribute('aria-expanded', String(open));
        if (open) conversation.scrollTop = scrollPosition;
        if (focus) { if (open) input.focus(); else toggle.focus(); }
        saveState();
    };
    toggle.addEventListener('click', () => setOpen(panel.hidden));
    find('close').addEventListener('click', () => setOpen(false));
    root.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !panel.hidden) { event.stopPropagation(); setOpen(false); }
    });
    const reset = () => {
        conversationId = newId();
        log.replaceChildren();
        find('welcome').hidden = false;
        input.value = '';
        pendingQuestion = ''; scrollPosition = 0; restoreBlocked = false;
        persistEnabled = true; saveState();
    };
    find('new').addEventListener('click', async () => {
        if (busy) return;
        setBusy(true); clearTimeout(restoreTimer);
        try {
            await sessionAction('reset');
            reset(); progress.hidden = true; progress.textContent = '';
        } catch (error) { notify(error.message || config.text.error); }
        finally { setBusy(false); find('send').disabled = restoreBlocked; input.focus(); }
    });
    root.querySelectorAll('[data-assistant-example]').forEach((button) => button.addEventListener('click', () => {
        if (busy) return;
        input.value = button.textContent.trim(); input.focus(); saveState();
    }));
    const scroll = () => {
        conversation.scrollTop = conversation.scrollHeight;
        scrollPosition = panel.hidden ? Number.MAX_SAFE_INTEGER : conversation.scrollTop;
    };
    const notify = (message, type = 'error') => {
        if (typeof window.commonNotify === 'function') window.commonNotify(message, type);
        else { progress.textContent = message; progress.hidden = false; }
    };
    const safeUrl = (value) => {
        if (typeof value !== 'string' || !value.startsWith(`/omo/o/${config.organizationId}`)) return null;
        const url = new URL(value, location.origin);
        if (url.origin !== location.origin || !new RegExp(`^/omo/o/${config.organizationId}(?:/c/[1-9][0-9]*)?/?$`).test(url.pathname)
            || url.search || !/^#[a-z0-9-]*$|^$/.test(url.hash)) return null;
        return url.pathname + url.hash;
    };
    const appendMessage = (author, text, links = [], outgoing = false) => {
        const article = document.createElement('article');
        article.className = 'generic-message-bubble' + (outgoing ? ' generic-message-bubble--outgoing' : '');
        const title = document.createElement('strong');
        title.className = 'generic-meta-text'; title.textContent = author;
        const body = document.createElement('div'); body.className = 'organization-assistant__message';
        const allowed = new Map(links.map((link) => [safeUrl(link.url), link.label]).filter(([url]) => url));
        const appendText = (target, value) => {
            let start = 0;
            for (const match of value.matchAll(/\*\*([^*\n]+)\*\*/g)) {
                target.append(document.createTextNode(value.slice(start, match.index)));
                const strong = document.createElement('strong'); strong.textContent = match[1]; target.append(strong);
                start = match.index + match[0].length;
            }
            target.append(document.createTextNode(value.slice(start)));
        };
        // Build nodes only: provider text can never inject HTML, images, scripts or action URLs.
        const inline = (target, value) => {
            const pattern = /\[([^\]\n]+)\]\(\s*([^\s)]+)\s*\)/g;
            let offset = 0;
            for (const match of value.matchAll(pattern)) {
                appendText(target, value.slice(offset, match.index));
                const url = safeUrl(match[2]);
                if (url && allowed.has(url)) {
                    const link = document.createElement('a'); link.href = url; link.textContent = match[1]; target.append(link);
                } else target.append(document.createTextNode(match[1]));
                offset = match.index + match[0].length;
            }
            appendText(target, value.slice(offset));
        };
        const cells = (line) => {
            // Escaped pipes stay inside their cell; optional leading/trailing pipes are structural.
            const parts = line.trim().split(/(?<!\\)\|/).map(cell => cell.trim().replace(/\\\|/g, '|'));
            if (parts[0] === '') parts.shift();
            if (parts[parts.length - 1] === '') parts.pop();
            return parts;
        };
        const lines = text.replace(/\r\n?/g, '\n').split('\n');
        const tableHeader = (index) => {
            if (!lines[index]?.includes('|') || !lines[index + 1]?.includes('|')) return null;
            const headers = cells(lines[index]), separators = cells(lines[index + 1]);
            return headers.length >= 2 && headers.length <= 8 && headers.length === separators.length
                && separators.every(cell => /^:?-{3,}:?$/.test(cell)) ? headers : null;
        };
        const listItem = line => line.match(/^\s*(?:([-+*])|(\d+)\.)\s+(.+)$/);
        const heading = line => line.match(/^ {0,3}(#{1,6})[\t ]+(.+?)[\t ]*$/);
        if (outgoing) {
            const paragraph = document.createElement('p'); paragraph.textContent = text; body.append(paragraph);
        } else for (let i = 0; i < lines.length;) {
            if (!lines[i].trim()) { i++; continue; }
            const headingMatch = heading(lines[i]);
            if (headingMatch) {
                // A semantic heading without inheriting the site's h1-h6 presentation.
                const title = document.createElement('div');
                title.className = 'organization-assistant__heading';
                title.setAttribute('role', 'heading');
                title.setAttribute('aria-level', Math.min(6, headingMatch[1].length + 2));
                inline(title, headingMatch[2].replace(/[\t ]+#+[\t ]*$/, ''));
                body.append(title); i++; continue;
            }
            const headers = tableHeader(i);
            if (headers) {
                const wrapper = document.createElement('div'); wrapper.className = 'generic-data-table-scroll';
                // Focusable scroll region lets keyboard users reach columns on narrow screens.
                wrapper.tabIndex = 0;
                const table = document.createElement('table'); table.className = 'generic-data-table';
                const head = document.createElement('thead'), row = document.createElement('tr');
                for (const value of headers) {
                    const cell = document.createElement('th'); cell.scope = 'col'; inline(cell, value); row.append(cell);
                }
                head.append(row); table.append(head);
                const rows = document.createElement('tbody'); i += 2;
                while (i < lines.length && lines[i].includes('|') && cells(lines[i]).length === headers.length) {
                    const row = document.createElement('tr');
                    for (const value of cells(lines[i++])) {
                        const cell = document.createElement('td'); inline(cell, value); row.append(cell);
                    }
                    rows.append(row);
                }
                table.append(rows); wrapper.append(table);
                const frame = document.createElement('div'); frame.className = 'generic-data-table-frame';
                const copy = document.createElement('button');
                copy.type = 'button';
                copy.className = 'generic-action-button generic-action-button--secondary generic-action-button--icon-only generic-data-table-copy';
                copy.title = config.text.copy_csv; copy.setAttribute('aria-label', config.text.copy_csv);
                const icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                icon.setAttribute('viewBox', '0 0 24 24'); icon.setAttribute('aria-hidden', 'true');
                const outline = document.createElementNS(icon.namespaceURI, 'path');
                outline.setAttribute('d', 'M8 8h12v13H8z M16 8V3H3v13h5');
                icon.append(outline); copy.append(icon);
                copy.addEventListener('click', async () => {
                    // Copy displayed cell text (including headers), never markup or the action button.
                    // CSV quoting preserves commas, quotes and embedded line breaks.
                    const csv = Array.from(table.rows, row => Array.from(row.cells, cell =>
                        '"' + cell.textContent.replace(/"/g, '""') + '"').join(',')).join('\r\n');
                    copy.disabled = true;
                    try {
                        await navigator.clipboard.writeText(csv);
                        notify(config.text.copied_csv, 'success');
                    } catch (_) { notify(config.text.copy_error); }
                    finally { copy.disabled = false; }
                });
                frame.append(wrapper, copy); body.append(frame);
                continue;
            }
            const item = listItem(lines[i]);
            if (item) {
                const ordered = Boolean(item[2]);
                const list = document.createElement(ordered ? 'ol' : 'ul');
                if (ordered) list.start = Number(item[2]);
                while (i < lines.length) {
                    const entry = listItem(lines[i]);
                    if (!entry || Boolean(entry[2]) !== ordered) break;
                    const li = document.createElement('li'); inline(li, entry[3]); list.append(li); i++;
                }
                body.append(list); continue;
            }
            const paragraph = document.createElement('p'), content = [lines[i++]];
            while (i < lines.length && lines[i].trim() && !tableHeader(i) && !listItem(lines[i]) && !heading(lines[i])) content.push(lines[i++]);
            inline(paragraph, content.join('\n')); body.append(paragraph);
        }
        article.append(title, body);
        if (allowed.size) {
            const details = document.createElement('details');
            const summary = document.createElement('summary'); summary.textContent = config.text.sources;
            const sources = document.createElement('div'); sources.className = 'organization-assistant__sources';
            for (const [url, label] of allowed) {
                const link = document.createElement('a'); link.className = 'generic-action-button generic-action-button--compact';
                link.href = url; link.textContent = label; sources.append(link);
            }
            details.append(summary, sources); article.append(details);
        }
        log.append(article); scroll(); return article;
    };
    log.addEventListener('click', (event) => {
        const link = event.target.closest('a');
        if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return;
        const url = new URL(link.href);
        const match = url.pathname.match(/^\/omo\/o\/(\d+)(?:\/c\/(\d+))?\/?$/);
        if (match && Number(match[1]) === config.organizationId && typeof window.navigate === 'function') {
            event.preventDefault(); setOpen(false);
            window.navigate(config.organizationId, match[2] ? Number(match[2]) : null, url.hash.slice(1) || null);
        }
    });
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
            event.preventDefault(); if (!busy) find('form').requestSubmit();
        }
    });
    find('form').addEventListener('submit', async (event) => {
        event.preventDefault();
        if (busy || restoreBlocked || !input.value.trim()) return;
        const route = typeof window.parseUrl === 'function' ? window.parseUrl() : { oid: config.organizationId };
        if (Number(route.oid) !== config.organizationId) { reset(); notify(config.text.context); return; }
        const question = input.value.trim();
        const questionNode = appendMessage(config.text.you, question, [], true);
        find('welcome').hidden = true;
        input.value = ''; pendingQuestion = question; setBusy(true); saveState();
        progress.textContent = config.text.working; progress.hidden = false; scroll();
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 125000);
        try {
            const response = await fetch(config.endpoint, { method: 'POST', credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' }, signal: controller.signal,
                body: JSON.stringify({ organizationId: config.organizationId, csrf: config.csrf, conversationId,
                    holonId: Number(route.cid) > 0 ? Number(route.cid) : null, question }) });
            const result = await response.json();
            if (!response.ok || !result.status) throw new Error(result.message || config.text.error);
            if (typeof window.parseUrl === 'function' && Number(window.parseUrl().oid) !== config.organizationId) {
                reset(); setOpen(false); throw new Error(config.text.context);
            }
            if (result.organizationId !== config.organizationId || result.conversationId !== conversationId) throw new Error(config.text.context);
            progress.hidden = true; progress.textContent = '';
            appendMessage(config.text.title, result.answer, result.links);
            pendingQuestion = '';
        } catch (error) {
            progress.hidden = true; progress.textContent = '';
            questionNode.remove(); input.value = question;
            pendingQuestion = '';
            find('welcome').hidden = log.childElementCount > 0;
            notify(error instanceof SyntaxError || error.name === 'AbortError' || error instanceof TypeError ? config.text.error : error.message);
        } finally {
            clearTimeout(timeout); setBusy(false); saveState();
            if (!panel.hidden) input.focus();
        }
    });
    const restore = async () => {
        setBusy(true);
        progress.textContent = config.text.restoring; progress.hidden = false;
        try {
            const state = await sessionAction('state');
            if (!Array.isArray(state.messages)) throw new Error(config.text.restore_error);
            const position = scrollPosition;
            const sourceStates = log.childElementCount
                ? Array.from(log.querySelectorAll('article > details'), details => details.open) : savedState?.sources;
            persistEnabled = false;
            log.replaceChildren();
            for (const message of state.messages) {
                appendMessage(message.role === 'user' ? config.text.you : config.text.title,
                    message.text, message.links || [], message.role === 'user');
            }
            if (state.busy && state.pendingQuestion) appendMessage(config.text.you, state.pendingQuestion, [], true);
            find('welcome').hidden = log.childElementCount > 0;
            log.querySelectorAll('article > details').forEach((details, index) => {
                details.open = sourceStates?.[index] === true;
            });
            const lastQuestion = state.messages.filter(message => message.role === 'user').at(-1)?.text;
            if (!state.busy && (state.failed || (pendingQuestion && lastQuestion !== pendingQuestion))) {
                if (!input.value) input.value = state.pendingQuestion || pendingQuestion;
                notify(config.text.interrupted);
            }
            pendingQuestion = state.busy ? state.pendingQuestion : '';
            scrollPosition = position;
            if (!panel.hidden) conversation.scrollTop = position;
            restoreBlocked = false; persistEnabled = true;
            setBusy(Boolean(state.busy));
            progress.hidden = !state.busy;
            progress.textContent = state.busy ? config.text.working : '';
            saveState();
            if (state.busy) restoreTimer = setTimeout(restore, 1500);
        } catch (_) {
            setBusy(false); restoreBlocked = true; persistEnabled = true;
            find('send').disabled = true;
            progress.hidden = true; progress.textContent = '';
            notify(config.text.restore_error);
        }
    };
    if (savedState) {
        input.value = typeof savedState.draft === 'string' ? savedState.draft.slice(0, 4000) : '';
        setOpen(savedState.open === true, false);
        restore();
    }
})();
