(() => {
    'use strict';
    const root = document.querySelector('[data-visio-health]');
    if (!root) return;
    const button = root.querySelector('[data-health-refresh]');
    const summary = root.querySelector('[data-health-summary]');
    const results = root.querySelector('[data-health-results]');
    const checks = [...root.querySelectorAll('[data-health-check]')];
    const time = root.querySelector('[data-health-time]');
    const endpoint = root.querySelector('[data-health-url]');
    endpoint.querySelector('code').textContent = endpoint.href;

    async function refresh() {
        if (button.disabled) return;
        button.disabled = true;
        results.setAttribute('aria-busy', 'true');
        summary.textContent = root.dataset.pending;
        time.hidden = true;
        checks.forEach(cell => { cell.textContent = root.dataset.unknown; });
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 20000);
        try {
            const response = await fetch(endpoint.href, {cache: 'no-store', credentials: 'omit', signal: controller.signal});
            const data = await response.json();
            const healthy = response.status === 200 && data.status === 'ok'
                && checks.every(cell => data.checks?.[cell.dataset.healthCheck] === 'ok');
            summary.textContent = healthy ? root.dataset.ok : root.dataset.error;
            checks.forEach(cell => {
                const state = data.checks?.[cell.dataset.healthCheck];
                cell.textContent = state === 'ok' ? root.dataset.available
                    : (state === 'unavailable' ? root.dataset.unavailable : root.dataset.unknown);
            });
        } catch {
            summary.textContent = root.dataset.error;
        } finally {
            clearTimeout(timeout);
            results.setAttribute('aria-busy', 'false');
            button.disabled = false;
            const now = new Date();
            time.querySelector('time').dateTime = now.toISOString();
            time.querySelector('time').textContent = now.toLocaleString(document.documentElement.lang);
            time.hidden = false;
        }
    }
    button.addEventListener('click', refresh);
    refresh();
})();
