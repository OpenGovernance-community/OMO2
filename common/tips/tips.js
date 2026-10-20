(function () {
    'use strict';
    let slot = null;
    let payload = null;
    let notificationId = '';
    let busy = false;
    let revealTimer = null;
    let autoTimer = null;
    let seenDay = '';
    let region = null;
    let texts = {title: 'Trucs et astuces', next: 'Suivant', read: 'En savoir plus', close: 'Fermer', error: 'Impossible de charger cette astuce.'};

    function url(path) {
        return typeof window.omoResolveAppUrl === 'function' ? window.omoResolveAppUrl(path) : path;
    }
    function readCookie() {
        if (!payload) return null;
        const prefix = payload.cookieName + '=';
        try {
            const value = document.cookie.split('; ').find(value => value.startsWith(prefix));
            return value ? JSON.parse(decodeURIComponent(value.slice(prefix.length))) : null;
        } catch (_) { return null; }
    }
    function remember(seen) {
        if (!payload || !payload.tip) return;
        if (seen) seenDay = payload.cookieName + ':' + payload.day;
        const value = {day: payload.day, id: payload.tip.id, seen: seen};
        try {
            document.cookie = payload.cookieName + '=' + encodeURIComponent(JSON.stringify(value))
                + '; Path=/; SameSite=Lax; Expires=' + payload.expires + (location.protocol === 'https:' ? '; Secure' : '');
        } catch (_) { /* In-memory state also prevents repeats when cookies are unavailable. */ }
    }
    function currentPayload(value = payload) {
        return !!value && Date.parse(value.expires) > Date.now();
    }
    function alreadySeen() {
        const cookie = readCookie();
        return seenDay === payload.cookieName + ':' + payload.day
            || !!cookie && cookie.day === payload.day && cookie.seen;
    }
    function visibleSlot() {
        if (!slot) return false;
        if (window.matchMedia('(max-width: 768px)').matches && !document.body.classList.contains('view-menu')) return false;
        const bounds = slot.getBoundingClientRect();
        return bounds.width > 0 && bounds.height > 0 && bounds.right > 1 && bounds.left >= -1 && bounds.top < innerHeight;
    }
    function position() {
        if (!region || !notificationId || !visibleSlot()) return;
        const bounds = slot.getBoundingClientRect();
        const width = region.getBoundingClientRect().width;
        region.style.left = Math.max(12, Math.min(bounds.right + 10, innerWidth - width - 12)) + 'px';
        region.style.top = Math.max(12, Math.min(bounds.top, innerHeight - region.offsetHeight - 12)) + 'px';
    }
    function reveal() {
        clearTimeout(revealTimer);
        if (payload && payload.tip && slot) slot.classList.add('is-revealed');
    }
    function hideButton() {
        clearTimeout(revealTimer);
        revealTimer = setTimeout(function () {
            if (!notificationId && slot && !slot.matches(':hover') && !slot.contains(document.activeElement)) {
                slot.classList.remove('is-revealed');
            }
        }, 300);
    }
    function dismiss() {
        if (notificationId) window.commonDismissNotification(notificationId);
    }
    function safeLink(value) {
        if (typeof value !== 'string' || /[\x00-\x20\x7f\\]/.test(value)) return false;
        return value.startsWith('#') || (value.startsWith('/') && !value.startsWith('//')) || /^https?:\/\//i.test(value);
    }
    function show() {
        if (!visibleSlot() || !payload || !payload.tip || notificationId || typeof window.commonNotify !== 'function') return;
        if (!currentPayload()) {
            load(false).then(function (loaded) { if (loaded) show(); });
            return;
        }
        clearTimeout(autoTimer);
        autoTimer = null;
        reveal();
        const tip = payload.tip;
        const content = document.createElement('div');
        content.className = 'omo-tips-content';
        const title = document.createElement('strong');
        title.className = 'generic-card-title generic-card-title--small';
        title.textContent = texts.title;
        const text = document.createElement('p');
        text.textContent = tip.text;
        content.append(title, text);
        if (tip.image && tip.image.startsWith('/img/') && safeLink(tip.image)) {
            const image = document.createElement('img');
            image.src = tip.image;
            image.alt = '';
            image.addEventListener('load', position);
            image.addEventListener('error', function () { image.remove(); position(); });
            content.append(image);
        }
        const actions = document.createElement('div');
        actions.className = 'generic-action-row';
        if (safeLink(tip.link)) {
            const link = document.createElement('a');
            link.className = 'generic-action-button generic-action-button--compact generic-action-button--main';
            link.textContent = texts.read;
            if (tip.link.startsWith('#')) {
                const target = new URL(location.href);
                target.hash = tip.link;
                link.href = target.href;
            } else link.href = tip.link;
            if (/^https?:\/\//i.test(tip.link) && new URL(tip.link).origin !== location.origin) {
                link.target = '_blank';
                link.rel = 'noopener noreferrer';
            }
            link.addEventListener('click', dismiss);
            actions.append(link);
        }
        const next = document.createElement('button');
        next.type = 'button';
        next.className = 'generic-action-button generic-action-button--compact generic-action-button--secondary';
        next.textContent = texts.next;
        next.addEventListener('click', function () { load(true, next); });
        actions.append(next);
        content.append(actions);
        const button = slot.querySelector('button');
        button.setAttribute('aria-expanded', 'true');
        notificationId = window.commonNotify('', 'info', {
            duration: 10000, content: content, container: region, closeLabel: texts.close,
            onDismiss: function () {
                // An older notification can finish fading after the next tip opens.
                if (region.querySelector('[data-common-notification-state="visible"]')) { position(); return; }
                notificationId = '';
                if (slot) slot.querySelector('button').setAttribute('aria-expanded', 'false');
                hideButton();
            }
        });
        remember(true);
        requestAnimationFrame(position);
    }
    function maybeAutoShow() {
        if (!payload || !payload.tip || !visibleSlot() || document.hidden) return;
        if (!currentPayload()) { load(false); return; }
        if (!alreadySeen() && !autoTimer && !notificationId) {
            reveal();
            autoTimer = setTimeout(function () {
                autoTimer = null;
                if (!document.hidden && !alreadySeen()) show();
            }, 320);
        }
    }
    async function load(next, nextButton) {
        if (busy) return false;
        busy = true;
        if (nextButton) nextButton.disabled = true;
        try {
            const oid = Number((window.omoConfig || {}).oid) || 0;
            const response = await fetch(url('/omo/api/tips.php?oid=' + oid + (next ? '&next=1' : '')), {credentials: 'same-origin', cache: 'no-store'});
            if (!response.ok) throw new Error('Tip request failed');
            const received = await response.json();
            if (!currentPayload(received)) throw new Error('Expired tip response');
            payload = received;
            if (next) {
                dismiss();
                notificationId = '';
                show();
            } else {
                maybeAutoShow();
            }
            if (!payload.tip && slot) slot.classList.remove('is-revealed');
            return true;
        } catch (_) {
            if (next && typeof window.commonNotify === 'function') window.commonNotify(texts.error, 'error');
            return false;
        } finally {
            busy = false;
            if (nextButton) nextButton.disabled = false;
        }
    }
    function bind() {
        const found = document.querySelector('[data-omo-tips-slot]');
        if (found === slot) return;
        slot = found;
        if (!slot) { dismiss(); return; }
        const button = slot.querySelector('button');
        button.title = texts.title;
        button.setAttribute('aria-label', texts.title);
        button.addEventListener('click', function () {
            reveal();
            if (notificationId) dismiss();
            else if (payload && payload.tip) show();
            else load(false).then(function (loaded) { if (loaded) show(); });
        });
        slot.addEventListener('pointerdown', function (event) {
            if (event.pointerType === 'touch' && !slot.classList.contains('is-revealed')) reveal();
        });
        slot.addEventListener('mouseenter', reveal);
        slot.addEventListener('mouseleave', hideButton);
        slot.addEventListener('focusin', reveal);
        slot.addEventListener('focusout', hideButton);
        if (notificationId) { reveal(); button.setAttribute('aria-expanded', 'true'); position(); }
        else if (payload) maybeAutoShow();
        else load(false);
    }
    async function init() {
        if (!document.getElementById('sidebar') || !Number((window.omoConfig || {}).currentUserId)) return;
        try {
            const response = await fetch(url('/common/jstranslation/tips.php'), {credentials: 'same-origin'});
            if (response.ok) texts = Object.assign(texts, await response.json());
        } catch (_) { /* The local copy remains available offline. */ }
        region = document.createElement('div');
        region.id = 'omo-tips-region';
        region.className = 'omo-tips-region';
        region.setAttribute('aria-live', 'polite');
        document.body.append(region);
        new MutationObserver(bind).observe(document.getElementById('menu_sidebar'), {childList: true, subtree: true});
        new ResizeObserver(function () { maybeAutoShow(); position(); }).observe(document.getElementById('sidebar'));
        new MutationObserver(function () { maybeAutoShow(); position(); }).observe(document.body, {attributes: true, attributeFilter: ['class']});
        document.getElementById('sidebar').addEventListener('transitionend', function () { maybeAutoShow(); position(); });
        window.addEventListener('resize', function () { maybeAutoShow(); position(); });
        document.addEventListener('visibilitychange', maybeAutoShow);
        document.addEventListener('keydown', function (event) { if (event.key === 'Escape' && notificationId) dismiss(); });
        bind();
    }
    if (document.readyState === 'complete') init();
    else window.addEventListener('load', init, {once: true});
}());
