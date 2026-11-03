'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const source = fs.readFileSync(path.join(__dirname, '../omo/api/documents/pv/editor.js'), 'utf8');

function eventTarget() {
    const listeners = new Map();
    return {
        addEventListener(name, callback) {
            if (!listeners.has(name)) listeners.set(name, new Set());
            listeners.get(name).add(callback);
        },
        removeEventListener(name, callback) { listeners.get(name)?.delete(callback); },
        dispatch(name) { listeners.get(name)?.forEach(callback => callback()); }
    };
}

function harness(withDrawer = true) {
    let now = 0, nextTimer = 0, focused = true;
    const timers = new Map(), requests = [];
    class Element {}
    const drawer = new Element();
    drawer.classes = new Set(['is-open']);
    drawer.hidden = false;
    drawer.classList = {contains: name => drawer.classes.has(name)};
    const root = {isConnected: true, closest: () => withDrawer ? drawer : null, getClientRects: () => [1]};
    const window = eventTarget();
    window.setTimeout = (callback, delay) => {
        const id = ++nextTimer;
        timers.set(id, {callback, at: now + delay});
        return id;
    };
    window.clearTimeout = id => timers.delete(id);
    window.setInterval = callback => { window.heartbeat = callback; return 1; };
    const document = Object.assign(eventTarget(), {hidden: false, hasFocus: () => focused});
    class MutationObserver {
        constructor(callback) { this.callback = callback; }
        observe() {}
    }
    const context = {
        window, document, root, HTMLElement: Element, MutationObserver, Promise,
        currentDocumentPayload: {pvStage: 'meeting'}, activeLockPointIds: new Set([1]),
        activeSyncPollDelayMs: 5000, idleSyncPollDelayMs: 15000,
        syncPollTimer: null, syncPollPending: null, lockHeartbeatTimer: null,
        editorLifecycleObserver: null, editorWasPollingActive: false,
        knownPollingRevision: 'original', attendanceEnabled: false,
        hasUnsavedPointChanges: () => false, renderTimingSummary() {},
        stopPvEditorBackgroundWork() { throw new Error('Unexpected editor teardown'); },
        documentPayloadHasRemoteChanges: payload => !!payload,
        mergeCurrentDocumentPayload: payload => { context.currentDocumentPayload = payload; },
        pointCollectionHasRemoteChanges: () => false,
        postPointAction(action, id, data) {
            return new Promise(resolve => requests.push({action, data, resolve}));
        }
    };
    vm.createContext(context);
    const start = source.indexOf('    function isPvEditorSessionActive()');
    const end = source.indexOf("    window.addEventListener('beforeunload'", start);
    assert(start >= 0 && end > start);
    vm.runInContext(source.slice(start, end), context);
    context.startEditorLifecycleObserver();
    context.startSyncPolling();
    context.startLockHeartbeat();
    async function advance(ms) {
        const until = now + ms;
        while (true) {
            const entry = [...timers].sort((a, b) => a[1].at - b[1].at)[0];
            if (!entry || entry[1].at > until) break;
            timers.delete(entry[0]);
            now = entry[1].at;
            entry[1].callback();
            await settle();
        }
        now = until;
    }
    function focus(value) { focused = value; window.dispatch(value ? 'focus' : 'blur'); }
    function hidden(value) { document.hidden = value; document.dispatch('visibilitychange'); }
    async function respond(payload = {unchanged: true, pollRevision: 'latest'}) {
        requests.at(-1).resolve(payload);
        await settle();
    }
    return {context, window, document, drawer, requests, timers, advance, focus, hidden, respond};
}

const settle = () => new Promise(resolve => setImmediate(resolve));

(async function () {
    for (const withDrawer of [true, false]) {
        const h = harness(withDrawer), c = h.context;
        await h.advance(5000);
        assert.equal(h.requests.length, 1, 'The active editor polls normally.');
        h.hidden(true); // Hide while the request is in flight.
        await h.respond();
        assert.equal(h.timers.size, 0, 'An in-flight response cannot restart hidden polling.');
        await h.advance(8 * 60 * 60 * 1000);
        h.window.heartbeat();
        await c.syncEditorFromServer();
        assert.equal(h.requests.length, 1, 'Hidden editors make no poll or heartbeat requests, even on a refresh event.');

        h.focus(false);
        h.hidden(false);
        await h.advance(0);
        assert.equal(h.requests.length, 1, 'A visible window must also be focused before polling resumes.');
        h.focus(true);
        h.document.dispatch('visibilitychange');
        await h.advance(0);
        assert.equal(h.requests.length, 2, 'Returning triggers one immediate poll despite multiple browser events.');
        assert.equal(h.requests[1].data.poll_revision, 'latest');
        await h.respond({pollRevision: 'changed', document: {pvStage: 'review'}});
        assert.equal(c.currentDocumentPayload.pvStage, 'review', 'Resume applies remote changes.');

        h.focus(false); // Switching OS application need not hide the browser.
        assert.equal(h.document.hidden, false);
        assert.equal(h.timers.size, 0, 'Blur cancels the next poll immediately.');
        await h.advance(60000);
        h.window.heartbeat();
        assert.equal(h.requests.length, 2, 'An unfocused visible window makes no background requests.');
        h.focus(true);
        await h.advance(0);
        assert.equal(h.requests.length, 3);
        h.window.dispatch('blur'); // Focusing an embedded iframe can blur the window while its document retains focus.
        h.window.dispatch('focus');
        await h.advance(0);
        assert.equal(h.requests.length, 3, 'Focus inside the page does not duplicate polls.');
        await h.respond();
        await h.advance(4999);
        assert.equal(h.requests.length, 3);
        await h.advance(1);
        assert.equal(h.requests.length, 4, 'Resume keeps the normal polling cadence.');
        await h.respond();

        if (withDrawer) {
            h.drawer.classes.add('is-peek');
            c.editorLifecycleObserver.callback();
            assert.equal(h.timers.size, 0, 'Switching away from the PV inside OMO suspends polling.');
            await h.advance(60000);
            assert.equal(h.requests.length, 4);
            h.drawer.classes.delete('is-peek');
            c.editorLifecycleObserver.callback();
            await h.advance(0);
            assert.equal(h.requests.length, 5, 'Reopening the PV refreshes immediately.');
            await h.respond();
        }
        c.currentDocumentPayload.isPvValidated = true;
        h.focus(false);
        h.focus(true);
        assert.equal(h.timers.size, 0, 'A validated PV does not restart polling on focus.');
    }
    console.log('pv_editor_polling_activity_test: OK');
})().catch(error => { console.error(error); process.exitCode = 1; });
