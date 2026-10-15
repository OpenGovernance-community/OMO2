(function () {
    document.querySelectorAll('[data-patreon-organization]').forEach(function (panel) {
        if (panel.dataset.initialized) return;
        panel.dataset.initialized = '1';
        const config = JSON.parse(panel.dataset.config);
        let popup = null;
        const notify = (text, type) => window.commonNotify?.(text, type);
        async function action(name) {
            const buttons = panel.querySelectorAll('button');
            buttons.forEach(button => { button.disabled = true; });
            try {
                const response = await fetch('/common/patreon/organization-action.php', {
                    method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ organizationId: config.organizationId, csrf: config.csrf, action: name })
                });
                const data = await response.json();
                if (!response.ok || !data.status) throw new Error();
                panel.querySelector('[data-patreon-status]').textContent = config.text[data.linked ? (data.active ? 'active' : 'inactive') : 'unlinked'];
                panel.querySelector('[data-patreon-account]').hidden = !data.linked;
                panel.querySelector('[data-patreon-account] span').textContent = data.account || '';
                panel.querySelectorAll('[data-patreon-action]').forEach(button => { button.hidden = !data.linked; });
                notify(config.text.saved, 'success');
            } catch (_) { notify(config.text.error, 'error'); }
            finally { buttons.forEach(button => { button.disabled = false; }); }
        }
        panel.querySelector('[data-patreon-connect]').addEventListener('click', function () {
            popup = window.open(config.connectUrl, 'organization-patreon-' + config.organizationId, 'width=640,height=760');
        });
        panel.querySelectorAll('[data-patreon-action]').forEach(button => button.addEventListener('click', function () {
            if (button.dataset.patreonAction === 'disconnect' && !window.confirm(config.text.confirm)) return;
            action(button.dataset.patreonAction);
        }));
        window.addEventListener('message', function (event) {
            if (!panel.isConnected || event.source !== popup || event.origin !== config.hubOrigin || event.data?.type !== 'patreon-connected') return;
            action('status');
        });
    });
})();
