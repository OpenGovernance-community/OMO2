(() => {
    'use strict';
    const root = document.querySelector('[data-pad-reader]');
    if (!root) return;
    const content = root.querySelector('[data-pad-reader-content]');
    const status = root.querySelector('[data-pad-reader-status]');
    const url = new URL(window.location.href);
    url.searchParams.set('content', '1');
    let stopped = false;
    const refresh = async () => {
        if (document.hidden) {
            window.setTimeout(refresh, 10000);
            return;
        }
        try {
            const response = await fetch(url, {credentials: 'same-origin', cache: 'no-store', signal: AbortSignal.timeout(15000)});
            if ([401, 403, 404].includes(response.status) || response.redirected) {
                content.replaceChildren();
                status.textContent = status.dataset.denied;
                status.hidden = false;
                stopped = true;
                return;
            }
            if (!response.ok) throw new Error('refresh_failed');
            const payload = await response.json();
            if (!payload.status || typeof payload.html !== 'string') throw new Error('invalid_content');
            // HTML is sanitized server-side, as in the shared document snapshots.
            if (content.innerHTML !== payload.html) content.innerHTML = payload.html;
            status.textContent = '';
            status.hidden = true;
        } catch (error) {
            status.textContent = status.dataset.error;
            status.hidden = false;
        } finally {
            if (!stopped) window.setTimeout(refresh, 10000);
        }
    };
    window.setTimeout(refresh, 10000);
})();
