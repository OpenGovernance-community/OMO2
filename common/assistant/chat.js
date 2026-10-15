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
    const setOpen = (open) => {
        panel.hidden = !open;
        toggle.setAttribute('aria-expanded', String(open));
        if (open) input.focus(); else toggle.focus();
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
    };
    find('new').addEventListener('click', () => { if (!busy) { reset(); input.focus(); } });
    root.querySelectorAll('[data-assistant-example]').forEach((button) => button.addEventListener('click', () => {
        input.value = button.textContent.trim(); input.focus();
    }));
    const scroll = () => { conversation.scrollTop = conversation.scrollHeight; };
    const notify = (message) => {
        if (typeof window.commonNotify === 'function') window.commonNotify(message, 'error');
        else { progress.textContent = message; progress.hidden = false; }
    };
    const safeUrl = (value) => {
        if (typeof value !== 'string' || !value.startsWith(`/omo/o/${config.organizationId}`)) return null;
        const url = new URL(value, location.origin);
        if (url.origin !== location.origin || !new RegExp(`^/omo/o/${config.organizationId}(?:/c/[1-9][0-9]*)?/?$`).test(url.pathname)
            || url.search || !/^#[a-z0-9-]*$|^$/.test(url.hash)) return null;
        return url.pathname + url.hash;
    };
    const appendMessage = (author, text, links = []) => {
        const article = document.createElement('article');
        article.className = 'generic-soft-panel generic-soft-panel--stack';
        const title = document.createElement('strong');
        title.className = 'generic-meta-text'; title.textContent = author;
        const body = document.createElement('div'); body.className = 'organization-assistant__message';
        const allowed = new Map(links.map((link) => [safeUrl(link.url), link.label]).filter(([url]) => url));
        const appendText = (value) => {
            let start = 0;
            for (const match of value.matchAll(/\*\*([^*\n]+)\*\*/g)) {
                body.append(document.createTextNode(value.slice(start, match.index)));
                const strong = document.createElement('strong'); strong.textContent = match[1]; body.append(strong);
                start = match.index + match[0].length;
            }
            body.append(document.createTextNode(value.slice(start)));
        };
        // Build nodes only: provider text can never inject HTML, images, scripts or action URLs.
        const pattern = /\[([^\]\n]+)\]\(\s*([^\s)]+)\s*\)/g;
        let offset = 0;
        for (const match of text.matchAll(pattern)) {
            appendText(text.slice(offset, match.index));
            const url = safeUrl(match[2]);
            if (url && allowed.has(url)) {
                const link = document.createElement('a'); link.href = url; link.textContent = match[1]; body.append(link);
            } else body.append(document.createTextNode(match[1]));
            offset = match.index + match[0].length;
        }
        appendText(text.slice(offset));
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
        if (busy || !input.value.trim()) return;
        const route = typeof window.parseUrl === 'function' ? window.parseUrl() : { oid: config.organizationId };
        if (Number(route.oid) !== config.organizationId) { reset(); notify(config.text.context); return; }
        const question = input.value.trim();
        const questionNode = appendMessage(config.text.you, question);
        find('welcome').hidden = true;
        input.value = ''; busy = true;
        find('send').disabled = true; find('new').disabled = true; input.disabled = true;
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
        } catch (error) {
            progress.hidden = true; progress.textContent = '';
            questionNode.remove(); input.value = question;
            find('welcome').hidden = log.childElementCount > 0;
            notify(error instanceof SyntaxError || error.name === 'AbortError' || error instanceof TypeError ? config.text.error : error.message);
        } finally {
            clearTimeout(timeout); busy = false;
            find('send').disabled = false; find('new').disabled = false; input.disabled = false;
            if (!panel.hidden) input.focus();
        }
    });
})();
