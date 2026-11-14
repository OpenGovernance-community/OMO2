(() => {
    'use strict';
    const root = document.querySelector('[data-share-live-url]');
    if (!root) return;
    let state = '';
    let stopped = false;
    const refresh = async () => {
        if (document.hidden) { window.setTimeout(refresh, 5000); return; }
        try {
            const url = new URL(root.dataset.shareLiveUrl, location.href);
            if (state) url.searchParams.set('known_state_hash', state);
            const response = await fetch(url, {credentials: 'same-origin', cache: 'no-store', signal: AbortSignal.timeout(15000)});
            if ([401, 403, 404].includes(response.status)) {
                stopped = true;
                root.replaceChildren();
                location.reload();
                return;
            }
            if (!response.ok) return;
            const payload = await response.json();
            if (!payload.status) return;
            state = payload.stateHash || state;
            if (payload.changed === false) return;
            const content = root.querySelector('.prose');
            if (content && typeof payload.content === 'string' && content.innerHTML !== payload.content) content.innerHTML = payload.content;
            if (payload.title) {
                document.title = payload.title;
                const title = document.querySelector('.common-topbar__brand-name');
                if (title) title.textContent = payload.title;
            }
        } catch { /* Keep the last readable snapshot during a temporary network failure. */ }
        finally { if (!stopped) window.setTimeout(refresh, 5000); }
    };
    window.setTimeout(refresh, 5000);
})();
