(function (window) {
    'use strict';
    if (window.omoHtmlAiTools) return;

    const fallback = {ia: 'IA', dicter: 'Dicter', transcrire: 'Transcrire', annuler: 'Annuler', reecrire: 'Réécrire', resumer: 'Résumer', error: 'Impossible de terminer cette action IA.'};
    let labels = fallback;
    let translations;
    const controllers = new Set();
    let observer;
    function loadTranslations() {
        if (!translations) translations = fetch('/common/jstranslation/html_ai_tools.php', {credentials: 'same-origin', cache: 'no-store'})
            .then(response => { if (!response.ok) throw new Error(); return response.json(); })
            .then(payload => { labels = Object.assign({}, fallback, payload); })
            .catch(function () {});
        return translations;
    }
    const text = key => String(labels[key] || labels.error);

    window.omoHtmlAiTools = {
        create: function (options) {
            let field, mode = 'idle', opened = false, version = 0, request, recorder, stream, marker;
            let destroyed = false;
            const enabled = options.textToolsEnabled || options.transcriptionEnabled;
            const available = () => !destroyed && !!options.host && options.host.isConnected && (!options.isAvailable || options.isAvailable());
            const busy = () => mode !== 'idle';
            function status(message, type) {
                const node = options.statusNode;
                if (node) {
                    node.textContent = type === 'live' ? message : '';
                    node.hidden = type !== 'live' || !message;
                }
                if (message && type !== 'live') {
                    const notify = window.commonNotify || window.omoNotify;
                    if (typeof notify === 'function') notify(message, type);
                }
            }
            function sync() {
                if (!field || !enabled) return;
                const visible = opened || busy();
                const states = {
                    omoDocumentAiToggle: {label: text('ia'), title: text(visible ? 'masquer_les_outils_ia' : 'afficher_les_outils_ia'), active: visible},
                    omoDocumentDictate: {label: text(mode === 'recording' ? 'en_cours' : 'dicter'), title: text('demarrer_une_dictee'), disabled: busy(), hidden: !visible || !options.transcriptionEnabled},
                    omoDocumentTranscript: {label: text(mode === 'transcribing' ? 'transcription' : 'transcrire'), title: text('arreter_l_enregistrement_et_transcrire'), disabled: mode !== 'recording', hidden: !visible || !options.transcriptionEnabled},
                    omoDocumentDictationCancel: {label: text('annuler'), title: text('annuler_action_ia'), disabled: !busy(), hidden: !visible || !busy()},
                    omoDocumentRewrite: {label: text(mode === 'rewrite' ? 'reecriture' : 'reecrire'), title: text('reecrire_la_selection'), disabled: busy(), hidden: !visible || !options.textToolsEnabled},
                    omoDocumentSummarize: {label: text(mode === 'summarize' ? 'resume' : 'resumer'), title: text('resumer_la_selection'), disabled: busy(), hidden: !visible || !options.textToolsEnabled}
                };
                Object.keys(states).forEach(name => field.setToolbarButtonState(name, states[name]));
                if (options.onBusyChange) options.onBusyChange(busy());
            }
            function setMode(value) { mode = value; sync(); }
            function stopTracks() {
                if (stream) stream.getTracks().forEach(track => track.stop());
                stream = null;
            }
            function clearMarker() {
                if (marker && field) field.removeTemporaryMarker(marker);
                marker = null;
            }
            function cancel() {
                version++;
                if (request) request.abort();
                request = null;
                if (recorder && recorder.state !== 'inactive') {
                    try { recorder.stop(); } catch (error) { /* Still release the microphone below. */ }
                }
                recorder = null;
                stopTracks();
                clearMarker();
                status('', 'live');
                setMode('idle');
            }
            function destroy() {
                if (destroyed) return;
                destroyed = true;
                cancel();
                controllers.delete(controller);
                if (!controllers.size && observer) { observer.disconnect(); observer = null; }
            }
            function errorMessage(error) {
                if (error && ['NotAllowedError', 'SecurityError'].includes(error.name)) return text('l_acces_au_micro_a_ete_refuse');
                if (error && ['NotFoundError', 'DevicesNotFoundError'].includes(error.name)) return text('aucun_micro_n_est_disponible_sur_cet_appareil');
                return error && error.message || text('error');
            }
            async function prepare(nextMode) {
                if (busy() || !available() || !field) return 0;
                const operation = ++version;
                field.saveRange();
                // Keep the original cursor/selection even when focus moves during the request.
                marker = field.createTemporaryCursorMarker({preserveSelection: true});
                if (!marker) return 0;
                setMode(nextMode);
                if (options.beforeAction && await options.beforeAction() === false) {
                    if (operation === version) cancel();
                    return 0;
                }
                return operation === version && available() ? operation : 0;
            }
            const current = operation => operation === version && available();
            function finish(operation) {
                if (operation !== version) return;
                request = null;
                recorder = null;
                stopTracks();
                clearMarker();
                status('', 'live');
                setMode('idle');
            }
            function insert(value) {
                const node = document.createElement('div');
                node.textContent = value;
                const html = node.innerHTML.replace(/\r?\n/g, '<br>');
                if (!field.replaceMarkerWithHtml(marker, html)) throw new Error(text('impossible_de_remplacer_la_selection_dans_l_editeur'));
                marker = null;
                field.focus();
            }
            function formData() {
                const context = options.getContext();
                const data = new FormData();
                data.set('oid', String(context.organizationId || 0));
                data.set('cid', String(context.holonId || 0));
                data.set('title', String(context.title || ''));
                return data;
            }
            async function send(endpoint, data) {
                request = new window.AbortController();
                const response = await fetch('/omo/api/documents/html/' + endpoint + '.php', {
                    method: 'POST', body: data, credentials: 'same-origin', cache: 'no-store',
                    headers: {'X-Requested-With': 'XMLHttpRequest'}, signal: request.signal
                });
                const payload = await response.json().catch(() => null);
                if (!response.ok || !payload || payload.status !== true || !String(payload.text || '').trim()) {
                    throw new Error(payload && payload.message || text('error'));
                }
                return String(payload.text).trim();
            }
            async function startDictation() {
                if (!options.transcriptionEnabled || busy()) return;
                if (!window.MediaRecorder || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    status(text('la_dictee_n_est_pas_disponible_sur_ce_navigateur'), 'error'); return;
                }
                let operation = version + 1;
                try {
                    operation = await prepare('requesting');
                    if (!operation) return;
                    status(text('acces_micro'), 'live');
                    const media = await navigator.mediaDevices.getUserMedia({audio: true});
                    if (!current(operation)) { media.getTracks().forEach(track => track.stop()); return; }
                    stream = media;
                    const mimeType = ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4', 'audio/ogg;codecs=opus', 'audio/ogg']
                        .find(type => typeof window.MediaRecorder.isTypeSupported === 'function' && window.MediaRecorder.isTypeSupported(type));
                    recorder = mimeType ? new window.MediaRecorder(stream, {mimeType}) : new window.MediaRecorder(stream);
                    const chunks = [];
                    recorder.addEventListener('dataavailable', event => { if (event.data && event.data.size) chunks.push(event.data); });
                    recorder.audioChunks = chunks;
                    recorder.addEventListener('error', () => {
                        if (current(operation)) { status(text('error'), 'error'); finish(operation); }
                    });
                    recorder.start();
                    setMode('recording');
                    status(text('enregistrement_en_cours_quand_vous_avez_termine_cliquez_sur_transcrire'), 'live');
                } catch (error) {
                    if (operation === version) { status(errorMessage(error), 'error'); finish(operation); }
                }
            }
            async function transcribe() {
                if (mode !== 'recording' || !recorder || !available()) return;
                const operation = version;
                const recording = recorder;
                setMode('transcribing');
                status(text('transcription_en_cours'), 'live');
                try {
                    const audio = await new Promise((resolve, reject) => {
                        recording.addEventListener('stop', () => resolve(new Blob(recording.audioChunks, {type: recording.mimeType || 'audio/webm'})), {once: true});
                        recording.addEventListener('error', reject, {once: true});
                        // stop emits the final data; requestData here leaves WebM duration unfinished.
                        recording.stop();
                    });
                    if (!current(operation)) return;
                    stopTracks();
                    if (!audio.size) throw new Error(text('aucun_son_n_a_ete_enregistre'));
                    const extension = /mp4|m4a/.test(audio.type) ? 'm4a' : (/ogg/.test(audio.type) ? 'ogg' : (/wav/.test(audio.type) ? 'wav' : 'webm'));
                    const data = formData();
                    data.set('audio', audio, 'document-dictation.' + extension);
                    const result = await send('transcribe', data);
                    if (!current(operation)) return;
                    insert(result);
                    status(text('transcription_inseree_dans_le_document'), 'success');
                } catch (error) {
                    if (current(operation)) status(errorMessage(error), 'error');
                } finally { finish(operation); }
            }
            async function transform(action) {
                if (!options.textToolsEnabled || busy() || !available() || !field) return;
                field.saveRange();
                const selected = String(field.getSelectedText() || '').trim();
                const full = String(field.getPlainText() || '').trim();
                if (!selected || !full) { status(text(action === 'rewrite' ? 'selectionnez_un_bloc_de_texte_avant_de_lancer_la_reecriture' : 'selectionnez_un_bloc_de_texte_avant_de_lancer_le_resume'), 'error'); return; }
                let operation = version + 1;
                try {
                    operation = await prepare(action);
                    if (!operation) return;
                    status(text(action === 'rewrite' ? 'reecriture_en_cours_2' : 'resume_en_cours_2'), 'live');
                    const data = formData();
                    data.set('selected_text', selected);
                    data.set('full_text', full);
                    if (action === 'rewrite') data.set('rewrite_full_document', selected.replace(/\s+/g, ' ') === full.replace(/\s+/g, ' ') ? '1' : '0');
                    const result = await send(action, data);
                    if (!current(operation)) return;
                    insert(result);
                    status(text(action === 'rewrite' ? 'selection_reecrite' : 'selection_resumee'), 'success');
                } catch (error) {
                    if (operation === version) status(errorMessage(error), 'error');
                } finally { if (operation) finish(operation); }
            }
            const actions = {
                omoDocumentAiToggle: () => { opened = !opened; sync(); },
                omoDocumentDictate: startDictation,
                omoDocumentTranscript: transcribe,
                omoDocumentDictationCancel: () => { cancel(); status(text('action_ia_annulee'), 'success'); },
                omoDocumentRewrite: () => transform('rewrite'),
                omoDocumentSummarize: () => transform('summarize')
            };
            const buttonLabels = ['ia', 'dicter', 'transcrire', 'annuler', 'reecrire', 'resumer'];
            const controller = {
                buttons: enabled ? Object.keys(actions).map((name, index) => ({
                    name, group: index ? 'omo-ai-tools' : 'omo-ai-toggle', label: text(buttonLabels[index]),
                    className: 'note-btn-light', focusForInsertion: index > 0, hidden: index > 0,
                    onClick: actions[name]
                })) : [],
                bind: function (api) {
                    field = api;
                    sync();
                    if (!enabled) return;
                    controllers.add(controller);
                    if (!observer && typeof MutationObserver === 'function') {
                        observer = new MutationObserver(() => controllers.forEach(item => {
                            if (!item.isAttached()) item.destroy();
                            else if (!item.isAvailable() && item.isBusy()) item.cancel();
                        }));
                        observer.observe(document.body, {childList: true, subtree: true, attributes: true, attributeFilter: ['class', 'hidden', 'data-omo-pv-point-editable']});
                    }
                    loadTranslations().then(() => { if (available()) sync(); });
                },
                isAttached: () => !!options.host && options.host.isConnected,
                isAvailable: available, isBusy: busy, sync, cancel, destroy
            };
            return controller;
        }
    };
    window.addEventListener('pagehide', () => controllers.forEach(controller => controller.destroy()));
})(window);
