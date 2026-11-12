(function () {
    'use strict';

    function millisecondsAt(state, now) {
        const elapsed = state.status === 'playing' ? Math.max(0, now - state.anchor) : 0;
        return Math.max(0, state.milliseconds + (state.mode === 'countdown' ? -elapsed : elapsed));
    }

    function format(milliseconds, countdown) {
        const seconds = Math.min(359999, countdown ? Math.ceil(milliseconds / 1000) : Math.floor(milliseconds / 1000));
        return [Math.floor(seconds / 3600), Math.floor(seconds / 60) % 60, seconds % 60]
            .map(value => String(value).padStart(2, '0')).join(':');
    }

    function parse(value) {
        const parts = /^(\d{2}):([0-5]\d):([0-5]\d)$/.exec(value.trim());
        return parts ? Number(parts[1]) * 3600 + Number(parts[2]) * 60 + Number(parts[3]) : null;
    }

    function mount(options) {
        const root = options.root;
        const host = root.querySelector('[data-omo-pv-timer]');
        const topbar = root.querySelector('[data-omo-pv-topbar]');
        const input = host.querySelector('[data-omo-pv-timer-time]');
        const controls = host.querySelector('[data-omo-pv-timer-controls]');
        const buttons = Array.from(controls.querySelectorAll('button'));
        let state = options.state;
        let rights = false;
        let pending = false;
        let draft = null;
        let editing = false;
        let selectedPointId = null;
        let offset = Number(options.serverNow) - Date.now();
        let interval = null;
        let pollInterval = null;
        let polling = false;
        let audio = null;
        let destroyed = false;
        const soundedRuns = new Set();
        if (!Number.isFinite(offset)) offset = 0;

        // Browsers require a user gesture before Web Audio can make a sound.
        function unlockAudio() {
            if (destroyed) return;
            try {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                if (!AudioContext) return;
                if (!audio) audio = new AudioContext();
                if (audio.state === 'suspended') audio.resume().catch(function () {});
            } catch (error) { /* Audio may be unavailable on this device. */ }
        }

        function ring() {
            if (!audio || audio.state !== 'running') return;
            for (let index = 0; index < 3; index++) {
                const start = audio.currentTime + index * 0.3;
                const oscillator = audio.createOscillator();
                const gain = audio.createGain();
                oscillator.frequency.value = 880;
                gain.gain.setValueAtTime(0, start);
                gain.gain.linearRampToValueAtTime(0.18, start + 0.01);
                gain.gain.linearRampToValueAtTime(0, start + 0.2);
                oscillator.connect(gain);
                gain.connect(audio.destination);
                oscillator.start(start);
                oscillator.stop(start + 0.21);
                oscillator.onended = function () { oscillator.disconnect(); gain.disconnect(); };
            }
        }

        function render() {
            if (destroyed) return;
            if (!root.isConnected) { destroy(); return; }
            const countdown = draft ? draft.mode === 'countdown' : state.mode === 'countdown';
            const milliseconds = draft ? draft.seconds * 1000 : millisecondsAt(state, Date.now() + offset);
            host.hidden = !rights && state.status === 'stopped';
            controls.hidden = !rights;
            input.readOnly = !rights;
            input.disabled = pending;
            if (!editing || !rights) input.value = format(milliseconds, countdown);
            host.classList.toggle('is-urgent', countdown && milliseconds < 60000);
            buttons.forEach(function (button) {
                const command = button.getAttribute('data-omo-pv-timer-command');
                button.disabled = pending || (command === 'play' && state.status === 'playing')
                    || (command === 'pause' && state.status !== 'playing');
            });
            const tabs = root.querySelector('[data-omo-pv-application-tabs]');
            topbar.hidden = host.hidden && (!tabs || tabs.hidden);
            root.classList.toggle('omo-pv-editor--has-toolbar', !topbar.hidden);
            if (state.status === 'playing' && state.mode === 'countdown'
                && milliseconds === 0 && !soundedRuns.has(state.runId)) {
                soundedRuns.add(state.runId);
                ring();
            }
            if (state.status === 'playing' && interval === null) interval = window.setInterval(render, 100);
            if (state.status !== 'playing' && interval !== null) {
                window.clearInterval(interval);
                interval = null;
            }
        }

        function sync(documentPayload, timer, serverNow) {
            const nextRights = documentPayload.canControlTimer === true;
            if (!nextRights) { draft = null; editing = false; input.setCustomValidity(''); }
            rights = nextRights;
            if (timer && Number(timer.anchor) >= Number(state.anchor)) {
                if (timer.revision !== state.revision) { draft = null; editing = false; }
                state = timer;
            }
            if (Number.isFinite(serverNow)) offset = serverNow - Date.now();
            render();
        }

        function send(command, fields) {
            if (!rights || pending) return Promise.resolve();
            pending = true;
            editing = false;
            render();
            return options.send(Object.assign({command: command, revision: state.revision}, fields || {}))
                .then(function (payload) {
                    options.onDocument(payload.document);
                    sync(payload.document, payload.timer, payload.serverNow);
                    draft = null;
                    return true;
                })
                .catch(function (error) {
                    if (error.document) options.onDocument(error.document);
                    sync(error.document || {canControlTimer: rights}, error.timer, error.serverNow);
                    window.omoNotify(error.message || options.ui.timerError, 'error');
                    return false;
                })
                .finally(function () { pending = false; render(); });
        }

        function commitInput() {
            if (!editing || !rights) return Promise.resolve(true);
            const seconds = parse(input.value);
            if (seconds === null) {
                input.setCustomValidity(options.ui.timerInvalid);
                input.reportValidity();
                return Promise.resolve(false);
            }
            input.setCustomValidity('');
            editing = false;
            if (state.status === 'stopped') {
                draft = {seconds: seconds, mode: 'countdown'};
                render();
            } else {
                return send('edit', {seconds: seconds});
            }
            return Promise.resolve(true);
        }

        function onClick(event) {
            const button = event.target.closest('[data-omo-pv-timer-command]');
            if (!button || !rights || pending) return;
            const command = button.getAttribute('data-omo-pv-timer-command');
            if (command === 'stop') {
                editing = false;
                input.setCustomValidity('');
                send(command);
                return;
            }
            commitInput().then(function (valid) {
                if (!valid) return;
                send(command, command === 'play' && state.status === 'stopped'
                    ? (draft || {seconds: 0, mode: 'stopwatch'}) : {});
            });
        }

        function selectPoint(minutes, pointId) {
            if (!rights || pending || state.status !== 'stopped' || !(minutes > 0)) return;
            if (pointId && pointId === selectedPointId) return;
            if (pointId) selectedPointId = pointId;
            draft = {seconds: Math.min(359999, Math.round(minutes * 60)), mode: 'countdown'};
            editing = false;
            input.setCustomValidity('');
            render();
        }

        function destroy() {
            if (destroyed) return;
            destroyed = true;
            if (interval !== null) window.clearInterval(interval);
            if (pollInterval !== null) window.clearInterval(pollInterval);
            if (audio) audio.close().catch(function () {});
            document.removeEventListener('pointerdown', unlockAudio);
            document.removeEventListener('keydown', unlockAudio);
            host.removeEventListener('click', onClick);
        }

        input.addEventListener('input', function () { editing = true; input.setCustomValidity(''); });
        input.addEventListener('change', commitInput);
        input.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') { event.preventDefault(); commitInput(); }
            if (event.key === 'Escape') {
                editing = false;
                input.setCustomValidity('');
                render();
                input.blur();
            }
        });
        document.addEventListener('pointerdown', unlockAudio);
        document.addEventListener('keydown', unlockAudio);
        // Keep an unfinished duration focused until the chosen command has
        // committed it, so clicking Pause/Play only needs a single click.
        controls.addEventListener('pointerdown', function (event) { event.preventDefault(); });
        host.addEventListener('click', onClick);
        if (options.poll) {
            // Visible projection windows also need commands and handovers while
            // unfocused. Avoid the much heavier full-editor background poll.
            pollInterval = window.setInterval(function () {
                if (!root.isConnected) { destroy(); return; }
                if (document.hidden || root.getClientRects().length === 0 || polling || pending) return;
                polling = true;
                options.poll().then(function (payload) {
                    sync(payload.timerPermissions, payload.timer, payload.serverNow);
                }).catch(function () {
                    // Keep the last known clock running during a network outage.
                }).finally(function () { polling = false; });
            }, 2000);
        }
        return {sync: sync, selectPoint: selectPoint, destroy: destroy};
    }

    window.omoPvTimer = {mount: mount};
})();
