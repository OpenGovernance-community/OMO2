var omoUiText = window.omoUiText || function (key, fallback) { return fallback; };
var omoUiHtml = window.omoUiHtml || function (key, fallback) { return fallback; };
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
            return new Intl.DateTimeFormat((window.omoConfig && window.omoConfig.translationLocale) || document.documentElement.lang || 'fr-CH', {
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
            ("    <div class=\"omo-site-update-banner__eyebrow\">" + omoUiHtml("admin_du_site", "Admin du site") + "</div>"),
            '    <strong class="omo-site-update-banner__title"></strong>',
            '    <p class="omo-site-update-banner__message"></p>',
            '    <div class="omo-site-update-banner__meta"></div>',
            '    <div class="omo-site-update-banner__local-changes" hidden></div>',
            '  </div>',
            '  <div class="omo-site-update-banner__actions">',
            ("    <button type=\"button\" class=\"omo-site-update-banner__button omo-site-update-banner__button--secondary\" data-omo-site-update-dismiss>" + omoUiHtml("ignorer", "Ignorer") + "</button>"),
            ("    <button type=\"button\" class=\"omo-site-update-banner__button omo-site-update-banner__button--main\" data-omo-site-update-run>" + omoUiHtml("installer", "Installer") + "</button>"),
            ("    <button type=\"button\" class=\"omo-site-update-banner__button omo-site-update-banner__button--force\" data-omo-site-update-force hidden>" + omoUiHtml("forcer_la_mise_a_jour", "Forcer la mise à jour") + "</button>"),
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
            '    border-radius: var(--radius-md);',
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
            '.omo-site-update-banner__local-changes {',
            '    color: #7f1d1d;',
            '    font-size: 12px;',
            '    overflow-wrap: anywhere;',
            '}',
            '.omo-site-update-banner__local-changes summary {',
            '    cursor: pointer;',
            '    font-weight: 700;',
            '}',
            '.omo-site-update-banner__local-changes ul {',
            '    max-height: 180px;',
            '    margin: 8px 0 0;',
            '    overflow: auto;',
            '    padding-left: 18px;',
            '}',
            '.omo-site-update-banner__local-changes li {',
            '    margin: 3px 0;',
            '}',
            '.omo-site-update-banner__local-changes li.is-overlapping {',
            '    font-weight: 700;',
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
            '.omo-site-update-banner__button--force {',
            '    background: #b91c1c;',
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
            pills.push('<span class="omo-site-update-banner__pill">' + escapeHtml(behindCount + (behindCount > 1 ? ' versions' : omoUiText("version", " version"))) + '</span>');
        }

        if (remoteDate) {
            pills.push('<span class="omo-site-update-banner__pill">' + escapeHtml(omoUiText("derniere_version", "Dernière version: ") + remoteDate) + '</span>');
        } else if (localDate) {
            pills.push('<span class="omo-site-update-banner__pill">' + escapeHtml(omoUiText("version_installee", "Version installée: ") + localDate) + '</span>');
        } else if (localCommit) {
            pills.push('<span class="omo-site-update-banner__pill">' + escapeHtml(localCommit) + '</span>');
        }

        return pills.join('');
    }

    function formatLocalChangeStates(states) {
        var labels = [];
        var source = Array.isArray(states) ? states : [];

        source.forEach(function (state) {
            if (state === 'modified') {
                labels.push('modifie');
            } else if (state === 'indexed') {
                labels.push('indexe');
            } else if (state === 'untracked') {
                labels.push('non suivi');
            }
        });

        return labels.length > 0 ? labels.join(', ') : 'modifie';
    }

    function renderLocalChanges(payload) {
        var changes = payload && Array.isArray(payload.localChanges) ? payload.localChanges : [];
        changes = changes.filter(function (change) {
            var states = change && Array.isArray(change.states) ? change.states : [];
            return change && (change.overlapsRemoteUpdate === true || states.includes('modified') || states.includes('indexed'));
        });
        var overlapCount = changes.filter(function (change) {
            return change.overlapsRemoteUpdate === true;
        }).length;

        if (changes.length === 0) {
            return '';
        }

        var title = changes.length + (changes.length > 1 ? ' fichiers locaux detectes' : omoUiText("fichier_local_detecte", " fichier local detecte"));
        if (overlapCount > 0) {
            title += '. ' + overlapCount + (overlapCount > 1 ? ' sont aussi modifies par le patch distant.' : ' est aussi modifie par le patch distant.');
        }

        var items = changes.map(function (change) {
            var path = change && change.path ? String(change.path) : '';
            var states = formatLocalChangeStates(change && change.states);
            var overlapping = change && change.overlapsRemoteUpdate === true;
            return '<li' + (overlapping ? ' class="is-overlapping"' : '') + '><code>' + escapeHtml(path) + '</code> (' + escapeHtml(states) + (overlapping ? ', aussi dans le patch' : '') + ')</li>';
        });

        return '<details open><summary>' + escapeHtml(title) + '</summary><ul>' + items.join('') + '</ul></details>';
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
        var localChangesNode = root.querySelector('.omo-site-update-banner__local-changes');
        var runButton = root.querySelector('[data-omo-site-update-run]');
        var forceButton = root.querySelector('[data-omo-site-update-force]');
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

        if (localChangesNode) {
            localChangesNode.hidden = !options.localChangesHtml;
            localChangesNode.innerHTML = options.localChangesHtml || '';
        }

        if (runButton) {
            runButton.hidden = options.showRun !== true;
            runButton.disabled = options.runDisabled === true;
            runButton.textContent = options.runLabel || 'Installer';
        }

        if (forceButton) {
            forceButton.hidden = options.showForce !== true;
            forceButton.disabled = options.forceDisabled === true;
            forceButton.textContent = options.forceLabel || "Forcer la mise à jour";
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
        var forceButton = root.querySelector('[data-omo-site-update-force]');
        var dismissButton = root.querySelector('[data-omo-site-update-dismiss]');
        var lastPayload = null;
        var completeOnly = new URLSearchParams(window.location.search).get('site-update-complete') === '1';

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
                        title: omoUiText("mise_a_jour_en_cours", "Mise à jour en cours"),
                        message: payload.message || omoUiText("un_autre_admin_est_en_train_de_synchroniser_le_site", "Un autre admin est en train de synchroniser le site."),
                        metaHtml: renderMeta(payload),
                        showRun: false,
                        showDismiss: true
                    });
                    return;
                }

                if (payload.requiresCompletion === true || completeOnly) {
                    completeOnly = true;
                    setBannerState(root, {
                        type: 'error',
                        title: omoUiText("finaliser_la_mise_a_jour", "Finaliser la mise à jour"),
                        message: omoUiText("installer_les_dependances_php_et_executer_les_migrations_pour_le_code_deja_present_sur_le", "Installer les dépendances PHP et executer les migrations pour le code déjà présent sur le serveur."),
                        metaHtml: renderMeta(payload),
                        showRun: true,
                        runLabel: omoUiText("finaliser", "Finaliser"),
                        showDismiss: true
                    });
                    return;
                }

                if (payload.available === true) {
                    var behindCount = payload.behindCount ? Number(payload.behindCount) : 0;
                    var hasTrackedChanges = payload.trackedLocalChangeCount ? Number(payload.trackedLocalChangeCount) > 0 : false;
                    var hasUntrackedOverlap = payload.untrackedOverlappingLocalChangeCount ? Number(payload.untrackedOverlappingLocalChangeCount) > 0 : false;

                    if (hasUntrackedOverlap) {
                        setBannerState(root, {
                            type: 'error',
                            title: omoUiText("fichiers_locaux_en_conflit", "Fichiers locaux en conflit"),
                            message: omoUiText("vous_pouvez_forcer_la_mise_a_jour_les_fichiers_identiques_a_la_version_distante_ne_seront", "Vous pouvez forcer la mise à jour. Les fichiers identiques à la version distante ne seront pas sauvegardés ; seules les différences seront sauvegardées avant remplacement."),
                            metaHtml: renderMeta(payload),
                            localChangesHtml: renderLocalChanges(payload),
                            showRun: false,
                            showForce: true,
                            forceDisabled: false,
                            showDismiss: true
                        });
                        return;
                    }

                    if (hasTrackedChanges) {
                        setBannerState(root, {
                            type: 'error',
                            title: omoUiText("modifications_locales_detectees", "Modifications locales détectées"),
                            message: omoUiText("la_mise_a_jour_est_bloquee_pour_proteger_ces_modifications_verifiez_la_liste_avant_de_forc", "La mise à jour est bloquée pour protéger ces modifications. Vérifiez la liste avant de forcer la synchronisation."),
                            metaHtml: renderMeta(payload),
                            localChangesHtml: renderLocalChanges(payload),
                            showRun: false,
                            showForce: true,
                            forceDisabled: false,
                            showDismiss: true
                        });
                        return;
                    }

                    setBannerState(root, {
                        type: '',
                        title: behindCount > 1 ? (behindCount + omoUiText("mises_a_jour_disponibles", " mises à jour disponibles")) : omoUiText("nouvelle_version_disponible", "Nouvelle version disponible"),
                        message: payload.message || payload.remoteHeadline || omoUiText("une_version_plus_recente_du_site_peut_etre_installee_maintenant", "Une version plus récente du site peut être installée maintenant."),
                        metaHtml: renderMeta(payload),
                        localChangesHtml: renderLocalChanges(payload),
                        showRun: true,
                        runDisabled: false,
                        runLabel: omoUiText("installer", "Installer"),
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

        function runUpdate(force) {
            if (!completeOnly && force !== true && (!lastPayload || lastPayload.available !== true)) {
                hideBanner();
                return;
            }

            if (force === true) {
                var trackedCount = lastPayload && lastPayload.trackedLocalChangeCount ? Number(lastPayload.trackedLocalChangeCount) : 0;
                var untrackedCount = lastPayload && lastPayload.untrackedOverlappingLocalChangeCount ? Number(lastPayload.untrackedOverlappingLocalChangeCount) : 0;
                var warning = omoUiText("cette_action_synchronisera_le_site_avec_la_version_distante", "Cette action synchronisera le site avec la version distante : ") + trackedCount + ' fichiers suivis modifies et ' + untrackedCount + omoUiText("fichiers_non_suivis_en_conflit_les_contenus_identiques_ne_seront_pas_sauvegardes_les_conte", " fichiers non suivis en conflit. Les contenus identiques ne seront pas sauvegardés. Les contenus différents seront sauvegardes hors du dossier public avant remplacement. Les fichiers non suivis sans conflit seront conservés. Continuer ?");
                if (!window.confirm(warning)) {
                    return;
                }
            } else if (!window.confirm(completeOnly ? omoUiText("installer_les_dependances_et_appliquer_les_migrations_du_code_actuel", "Installer les dépendances et appliquer les migrations du code actuel ?") : omoUiText("installer_la_nouvelle_version_du_site_maintenant", "Installer la nouvelle version du site maintenant ?"))) {
                return;
            }

            setBannerState(root, {
                type: '',
                title: omoUiText("mise_a_jour_en_cours", "Mise à jour en cours"),
                message: completeOnly ? omoUiText("installation_des_dependances_et_execution_des_migrations_sql", "Installation des dépendances et exécution des migrations SQL...") : omoUiText("synchronisation_du_code_et_execution_des_migrations_sql", "Synchronisation du code et exécution des migrations SQL..."),
                metaHtml: renderMeta(lastPayload || {}),
                localChangesHtml: renderLocalChanges(lastPayload || {}),
                showRun: !force,
                runDisabled: true,
                runLabel: omoUiText("installation", "Installation..."),
                showForce: force,
                forceDisabled: true,
                forceLabel: omoUiText("installation", "Installation..."),
                showDismiss: false
            });

            var requestOptions = {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            };

            if (force === true || completeOnly) {
                requestOptions.headers['Content-Type'] = 'application/x-www-form-urlencoded;charset=UTF-8';
                requestOptions.body = completeOnly ? 'complete=1' : 'force=1';
            }

            fetch(config.runUrl, requestOptions)
            .then(function (response) {
                return response.json().catch(function () {
                    return {
                        status: false,
                        message: omoUiText("reponse_invalide", "Réponse invalide.")
                    };
                });
            })
            .then(function (payload) {
                if (payload && payload.requiresForce === true) {
                    lastPayload = payload;
                    setBannerState(root, {
                        type: 'error',
                        title: omoUiText("modifications_locales_detectees", "Modifications locales détectées"),
                        message: payload.message || omoUiText("la_mise_a_jour_est_bloquee_pour_proteger_les_modifications_locales", "La mise à jour est bloquée pour protéger les modifications locales."),
                        metaHtml: renderMeta(payload),
                        localChangesHtml: renderLocalChanges(payload),
                        showRun: false,
                        showForce: true,
                        forceDisabled: false,
                        showDismiss: true
                    });
                    return;
                }

                if (payload && payload.requiresManualLocalCleanup === true) {
                    lastPayload = payload;
                    setBannerState(root, {
                        type: 'error',
                        title: omoUiText("fichiers_non_suivis_a_proteger", "Fichiers non suivis a protéger"),
                        message: payload.message || omoUiText("des_fichiers_non_suivis_doivent_etre_deplaces_ou_supprimes_avant_la_mise_a_jour", "Des fichiers non suivis doivent être déplacés ou supprimés avant la mise à jour."),
                        metaHtml: renderMeta(payload),
                        localChangesHtml: renderLocalChanges(payload),
                        showRun: false,
                        showForce: false,
                        showDismiss: true
                    });
                    return;
                }

                if (!payload || payload.status !== true) {
                    throw new Error(payload && payload.message ? payload.message : omoUiText("la_mise_a_jour_a_echoue", "La mise à jour a échoué."));
                }

                setBannerState(root, {
                    type: 'success',
                    title: payload.updated === false ? omoUiText("site_deja_a_jour", "Site déjà à jour") : omoUiText("mise_a_jour_terminee", "Mise à jour terminée"),
                    message: payload.message || payload.localHeadline || omoUiText("la_mise_a_jour_du_site_est_terminee", "La mise à jour du site est terminée."),
                    metaHtml: renderMeta(payload),
                    showRun: false,
                    showForce: false,
                    showDismiss: true
                });

                window.setTimeout(function () {
                    if (completeOnly) {
                        var url = new URL(window.location.href);
                        url.searchParams.delete('site-update-complete');
                        window.location.replace(url.toString());
                        return;
                    }
                    window.location.reload();
                }, payload.backupPath ? 15000 : (payload.updated === false ? 1000 : 1800));
            })
            .catch(function (error) {
                setBannerState(root, {
                    type: 'error',
                    title: omoUiText("mise_a_jour_impossible", "Mise à jour impossible"),
                    message: error && error.message ? error.message : omoUiText("la_mise_a_jour_du_site_a_echoue", "La mise à jour du site a échoué."),
                    metaHtml: renderMeta(lastPayload || {}),
                    localChangesHtml: renderLocalChanges(lastPayload || {}),
                    showRun: !force,
                    runDisabled: false,
                    runLabel: omoUiText("reessayer", "Réessayer"),
                    showForce: force,
                    forceDisabled: false,
                    forceLabel: omoUiText("forcer_la_mise_a_jour", "Forcer la mise à jour"),
                    showDismiss: true
                });
            });
        }

        if (runButton) {
            runButton.addEventListener('click', function () {
                runUpdate(false);
            });
        }

        if (forceButton) {
            forceButton.addEventListener('click', function () {
                runUpdate(true);
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
