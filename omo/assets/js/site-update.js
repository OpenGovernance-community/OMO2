(function () {
    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function shortenCommit(commit) {
        var value = String(commit || '').trim();
        return value ? value.slice(0, 7) : '';
    }

    function formatDate(value) {
        var raw = String(value || '').trim();
        if (!raw) {
            return '';
        }

        var parsed = new Date(raw);
        if (Number.isNaN(parsed.getTime())) {
            return '';
        }

        try {
            return new Intl.DateTimeFormat('fr-CH', {
                dateStyle: 'medium',
                timeStyle: 'short'
            }).format(parsed);
        } catch (error) {
            return parsed.toLocaleString();
        }
    }

    function ensureBannerRoot() {
        var existing = document.getElementById('omoSiteUpdateBanner');
        if (existing) {
            return existing;
        }

        var root = document.createElement('div');
        root.id = 'omoSiteUpdateBanner';
        root.className = 'omo-site-update-banner';
        root.hidden = true;
        root.innerHTML = [
            '<div class="omo-site-update-banner__card">',
            '  <div class="omo-site-update-banner__copy">',
            '    <div class="omo-site-update-banner__eyebrow">Admin du site</div>',
            '    <strong class="omo-site-update-banner__title"></strong>',
            '    <p class="omo-site-update-banner__message"></p>',
            '    <div class="omo-site-update-banner__meta"></div>',
            '  </div>',
            '  <div class="omo-site-update-banner__actions">',
            '    <button type="button" class="omo-site-update-banner__button omo-site-update-banner__button--secondary" data-omo-site-update-dismiss>Ignorer</button>',
            '    <button type="button" class="omo-site-update-banner__button omo-site-update-banner__button--main" data-omo-site-update-run>Installer</button>',
            '  </div>',
            '</div>'
        ].join('');

        var style = document.createElement('style');
        style.textContent = [
            '.omo-site-update-banner {',
            '    position: fixed;',
            '    top: calc(var(--topbar-height, 48px) + 16px);',
            '    right: 16px;',
            '    z-index: 80;',
            '    width: min(420px, calc(100vw - 24px));',
            '}',
            '.omo-site-update-banner[hidden] {',
            '    display: none;',
            '}',
            '.omo-site-update-banner__card {',
            '    display: grid;',
            '    gap: 14px;',
            '    padding: 18px;',
            '    border-radius: 18px;',
            '    border: 1px solid rgba(37, 99, 235, 0.18);',
            '    background: rgba(255, 255, 255, 0.98);',
            '    box-shadow: 0 22px 48px rgba(15, 23, 42, 0.18);',
            '    color: #0f172a;',
            '}',
            '.omo-site-update-banner__eyebrow {',
            '    font-size: 12px;',
            '    font-weight: 800;',
            '    letter-spacing: 0.08em;',
            '    text-transform: uppercase;',
            '    color: #2563eb;',
            '}',
            '.omo-site-update-banner__copy {',
            '    display: grid;',
            '    gap: 8px;',
            '}',
            '.omo-site-update-banner__title {',
            '    font-size: 16px;',
            '    line-height: 1.35;',
            '}',
            '.omo-site-update-banner__message {',
            '    margin: 0;',
            '    color: #475569;',
            '    line-height: 1.5;',
            '}',
            '.omo-site-update-banner__meta {',
            '    display: flex;',
            '    flex-wrap: wrap;',
            '    gap: 8px;',
            '}',
            '.omo-site-update-banner__pill {',
            '    display: inline-flex;',
            '    align-items: center;',
            '    min-height: 26px;',
            '    padding: 0 10px;',
            '    border-radius: 999px;',
            '    background: #eff6ff;',
            '    color: #1d4ed8;',
            '    font-size: 12px;',
            '    font-weight: 700;',
            '}',
            '.omo-site-update-banner__actions {',
            '    display: flex;',
            '    justify-content: flex-end;',
            '    gap: 10px;',
            '}',
            '.omo-site-update-banner__button {',
            '    min-height: 40px;',
            '    padding: 10px 14px;',
            '    border-radius: 999px;',
            '    border: 0;',
            '    font: inherit;',
            '    font-weight: 700;',
            '    cursor: pointer;',
            '}',
            '.omo-site-update-banner__button:disabled {',
            '    opacity: 0.6;',
            '    cursor: wait;',
            '}',
            '.omo-site-update-banner__button--secondary {',
            '    background: #e2e8f0;',
            '    color: #0f172a;',
            '}',
            '.omo-site-update-banner__button--main {',
            '    background: #2563eb;',
            '    color: #ffffff;',
            '}',
            '.omo-site-update-banner.is-success .omo-site-update-banner__card {',
            '    border-color: rgba(22, 163, 74, 0.22);',
            '    background: rgba(240, 253, 244, 0.98);',
            '}',
            '.omo-site-update-banner.is-success .omo-site-update-banner__eyebrow {',
            '    color: #166534;',
            '}',
            '.omo-site-update-banner.is-success .omo-site-update-banner__pill {',
            '    background: #dcfce7;',
            '    color: #166534;',
            '}',
            '.omo-site-update-banner.is-error .omo-site-update-banner__card {',
            '    border-color: rgba(220, 38, 38, 0.2);',
            '    background: rgba(254, 242, 242, 0.98);',
            '}',
            '.omo-site-update-banner.is-error .omo-site-update-banner__eyebrow {',
            '    color: #b91c1c;',
            '}',
            '.omo-site-update-banner.is-error .omo-site-update-banner__pill {',
            '    background: #fee2e2;',
            '    color: #b91c1c;',
            '}',
            '@media (max-width: 720px) {',
            '    .omo-site-update-banner {',
            '        top: calc(var(--topbar-height, 48px) + 12px);',
            '        left: 12px;',
            '        right: 12px;',
            '        width: auto;',
            '    }',
            '    .omo-site-update-banner__actions {',
            '        justify-content: stretch;',
            '    }',
            '    .omo-site-update-banner__button {',
            '        flex: 1 1 0;',
            '    }',
            '}'
        ].join('\n');

        document.head.appendChild(style);
        document.body.appendChild(root);
        return root;
    }

    function renderMeta(payload) {
        var pills = [];
        var branch = payload && payload.branch ? String(payload.branch) : '';
        var behindCount = payload && payload.behindCount ? Number(payload.behindCount) : 0;
        var localDate = formatDate(payload && payload.localDate);
        var remoteDate = formatDate(payload && payload.remoteDate);
        var localCommit = shortenCommit(payload && payload.localCommit);

        if (branch) {
            pills.push('<span class="omo-site-update-banner__pill">' + escapeHtml(branch) + '</span>');
        }

        if (behindCount > 0) {
            pills.push('<span class="omo-site-update-banner__pill">' + escapeHtml(behindCount + (behindCount > 1 ? ' versions' : ' version')) + '</span>');
        }

        if (remoteDate) {
            pills.push('<span class="omo-site-update-banner__pill">' + escapeHtml('Derniere version: ' + remoteDate) + '</span>');
        } else if (localDate) {
            pills.push('<span class="omo-site-update-banner__pill">' + escapeHtml('Version installee: ' + localDate) + '</span>');
        } else if (localCommit) {
            pills.push('<span class="omo-site-update-banner__pill">' + escapeHtml(localCommit) + '</span>');
        }

        return pills.join('');
    }

    function setBannerState(root, options) {
        if (!root) {
            return;
        }

        root.hidden = false;
        root.classList.toggle('is-success', options.type === 'success');
        root.classList.toggle('is-error', options.type === 'error');

        var titleNode = root.querySelector('.omo-site-update-banner__title');
        var messageNode = root.querySelector('.omo-site-update-banner__message');
        var metaNode = root.querySelector('.omo-site-update-banner__meta');
        var runButton = root.querySelector('[data-omo-site-update-run]');
        var dismissButton = root.querySelector('[data-omo-site-update-dismiss]');

        if (titleNode) {
            titleNode.textContent = options.title || '';
        }

        if (messageNode) {
            messageNode.textContent = options.message || '';
        }

        if (metaNode) {
            metaNode.innerHTML = options.metaHtml || '';
        }

        if (runButton) {
            runButton.hidden = options.showRun !== true;
            runButton.disabled = options.runDisabled === true;
            runButton.textContent = options.runLabel || 'Installer';
        }

        if (dismissButton) {
            dismissButton.hidden = options.showDismiss === false;
        }
    }

    function initSiteUpdateCheck(config) {
        if (!config || config.enabled !== true || !config.statusUrl || !config.runUrl) {
            return;
        }

        var root = ensureBannerRoot();
        var runButton = root.querySelector('[data-omo-site-update-run]');
        var dismissButton = root.querySelector('[data-omo-site-update-dismiss]');
        var lastPayload = null;

        function hideBanner() {
            root.hidden = true;
        }

        function loadStatus() {
            fetch(config.statusUrl, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function (response) {
                return response.json().catch(function () {
                    return null;
                });
            })
            .then(function (payload) {
                lastPayload = payload;

                if (!payload || payload.status !== true || payload.supported !== true) {
                    hideBanner();
                    return;
                }

                if (payload.updating === true) {
                    setBannerState(root, {
                        type: '',
                        title: 'Mise a jour en cours',
                        message: payload.message || 'Un autre admin est en train de synchroniser le site.',
                        metaHtml: renderMeta(payload),
                        showRun: false,
                        showDismiss: true
                    });
                    return;
                }

                if (payload.available === true) {
                    var behindCount = payload.behindCount ? Number(payload.behindCount) : 0;
                    setBannerState(root, {
                        type: '',
                        title: behindCount > 1 ? (behindCount + ' mises a jour disponibles') : 'Nouvelle version disponible',
                        message: payload.message || payload.remoteHeadline || 'Une version plus recente du site peut etre installee maintenant.',
                        metaHtml: renderMeta(payload),
                        showRun: true,
                        runDisabled: false,
                        runLabel: 'Installer',
                        showDismiss: true
                    });
                    return;
                }

                hideBanner();
            })
            .catch(function () {
                hideBanner();
            });
        }

        if (dismissButton) {
            dismissButton.addEventListener('click', hideBanner);
        }

        if (runButton) {
            runButton.addEventListener('click', function () {
                if (!lastPayload || lastPayload.available !== true) {
                    hideBanner();
                    return;
                }

                if (!window.confirm('Installer la nouvelle version du site maintenant ?')) {
                    return;
                }

                setBannerState(root, {
                    type: '',
                    title: 'Mise a jour en cours',
                    message: 'Synchronisation du code et execution des migrations SQL...',
                    metaHtml: renderMeta(lastPayload),
                    showRun: true,
                    runDisabled: true,
                    runLabel: 'Installation...',
                    showDismiss: false
                });

                fetch(config.runUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(function (response) {
                    return response.json().catch(function () {
                        return {
                            status: false,
                            message: 'Reponse invalide.'
                        };
                    });
                })
                .then(function (payload) {
                    if (!payload || payload.status !== true) {
                        throw new Error(payload && payload.message ? payload.message : 'La mise a jour a echoue.');
                    }

                    setBannerState(root, {
                        type: 'success',
                        title: payload.updated === false ? 'Site deja a jour' : 'Mise a jour terminee',
                        message: payload.message || payload.localHeadline || 'La mise a jour du site est terminee.',
                        metaHtml: renderMeta(payload),
                        showRun: false,
                        showDismiss: true
                    });

                    window.setTimeout(function () {
                        window.location.reload();
                    }, payload.updated === false ? 1000 : 1800);
                })
                .catch(function (error) {
                    setBannerState(root, {
                        type: 'error',
                        title: 'Mise a jour impossible',
                        message: error && error.message ? error.message : 'La mise a jour du site a echoue.',
                        metaHtml: renderMeta(lastPayload || {}),
                        showRun: true,
                        runDisabled: false,
                        runLabel: 'Reessayer',
                        showDismiss: true
                    });
                });
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', loadStatus, { once: true });
            return;
        }

        loadStatus();
    }

    window.omoInitSiteUpdateCheck = initSiteUpdateCheck;
})();
