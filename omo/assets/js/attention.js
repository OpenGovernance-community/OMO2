(function (window, document) {
    'use strict';
    const config = window.omoConfig || {};
    if (!config.currentUserId || config.isDemo || config.shareMode) return;
    const menu = document.getElementById('menu_sidebar');
    if (!menu) return;
    const boundBadges = new WeakSet();
    const tooltip = document.createElement('div');
    tooltip.id = 'omo-attention-tooltip';
    tooltip.className = 'omo-attention-tooltip generic-soft-panel generic-description generic-description--small';
    tooltip.setAttribute('role', 'tooltip');
    tooltip.hidden = true;
    document.body.appendChild(tooltip);
    let signals = [];
    let inFlight = false;
    let refreshAgain = false;
    let timer = null;
    let started = false;
    let activeBadge = null;

    function hideTooltip() {
        tooltip.hidden = true;
        activeBadge = null;
    }

    function showTooltip(badge) {
        if (badge.hidden) return;
        activeBadge = badge;
        tooltip.textContent = badge.getAttribute('aria-label') || '';
        tooltip.hidden = false;
        const rect = badge.getBoundingClientRect();
        const size = tooltip.getBoundingClientRect();
        tooltip.style.left = Math.max(12, Math.min(rect.right + 8, window.innerWidth - size.width - 12)) + 'px';
        const top = rect.bottom + size.height + 8 > window.innerHeight - 12
            ? rect.top - size.height - 8 : rect.bottom + 8;
        tooltip.style.top = Math.max(12, top) + 'px';
    }

    function syncBadges() {
        if (activeBadge && !activeBadge.isConnected) hideTooltip();
        const badges = Array.from(menu.querySelectorAll('[data-omo-attention-badge]'));
        badges.forEach(function (badge) {
            if (!boundBadges.has(badge)) {
                bindBadge(badge);
                boundBadges.add(badge);
            }
            const target = getBadgeTarget(badge);
            badge.hidden = !target;
            if (target) {
                badge.href = window.buildOmoUrl(target.oid, target.cid || null, target.routeToken);
                badge.setAttribute('aria-label', target.message || '');
                badge.setAttribute('aria-describedby', tooltip.id);
            } else {
                badge.removeAttribute('href');
                badge.removeAttribute('aria-label');
                badge.removeAttribute('aria-describedby');
            }
        });
        if (activeBadge && activeBadge.hidden) hideTooltip();
        else if (activeBadge) showTooltip(activeBadge);
    }

    function getBadgeTarget(badge) {
        return signals.find(function (signal) { return signal.type === badge.getAttribute('data-omo-attention-badge'); }) || null;
    }

    function render(payload) {
        signals = Array.isArray(payload.signals) ? payload.signals : [];
        syncBadges();
    }

    async function refresh() {
        if (!started || document.hidden) return;
        if (inFlight) {
            refreshAgain = true;
            return;
        }
        window.clearTimeout(timer);
        inFlight = true;
        const controller = new AbortController();
        const timeout = window.setTimeout(function () { controller.abort(); }, 15000);
        try {
            const url = window.omoResolveAppUrl('/omo/api/attention.php?oid=' + encodeURIComponent(config.oid));
            const response = await window.fetch(url, {credentials: 'same-origin', cache: 'no-store', signal: controller.signal});
            if (response.status === 401 || response.status === 403) {
                render({signals: []});
            } else if (response.ok) {
                render(await response.json());
            }
        } catch (error) {
            // Keep the last known signal during a temporary network failure; retry later.
        } finally {
            window.clearTimeout(timeout);
            inFlight = false;
            timer = window.setTimeout(refresh, refreshAgain ? 0 : 15 * 60 * 1000);
            refreshAgain = false;
        }
    }

    function bindBadge(badge) {
        badge.addEventListener('mouseenter', function () { showTooltip(badge); });
        badge.addEventListener('mouseleave', hideTooltip);
        badge.addEventListener('focus', function () { showTooltip(badge); });
        badge.addEventListener('blur', hideTooltip);
        badge.addEventListener('click', function (event) {
            event.stopPropagation();
            const target = getBadgeTarget(badge);
            if (!target || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
            event.preventDefault();
            hideTooltip();
            window.omoNavigate(target.oid, target.cid || null, target.routeToken);
        });
    }
    // The sidebar loads asynchronously and can be replaced when its applications change.
    new MutationObserver(syncBadges).observe(menu, {childList: true, subtree: true});
    syncBadges();
    menu.addEventListener('scroll', hideTooltip, true);
    window.addEventListener('resize', hideTooltip);
    document.addEventListener('keydown', function (event) { if (event.key === 'Escape') hideTooltip(); });
    window.addEventListener('omo-decision-response-saved', refresh);
    window.addEventListener('omo-decision-moved', refresh);
    window.addEventListener('omo-activities-changed', refresh);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) refresh(); });

    function start() {
        // The signal calculation is never part of the initial page or navigation requests.
        window.setTimeout(function () { started = true; refresh(); }, 0);
    }
    if (document.readyState === 'complete') start();
    else window.addEventListener('load', start, {once: true});
})(window, document);
