'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '../omo/assets/js/app.js'), 'utf8');
const modeStart = source.indexOf('function omoIsShareMode()');
const modeEnd = source.indexOf('function omoGetShareToken()', modeStart);
const start = source.indexOf('const OMO_RUNTIME_MAINTENANCE_MIN_INTERVAL_MS');
const end = source.indexOf('window.omoRefreshSidebar =', start);
assert(modeStart >= 0 && modeEnd > modeStart && start >= 0 && end > start);

function createPage(mode, status = 401, readyState = 'complete') {
    const timers = [];
    const listeners = {};
    const requests = [];
    const events = [];
    let reloads = 0;
    const context = vm.createContext({
        console,
        navigator: { onLine: true },
        document: {
            readyState,
            visibilityState: 'visible',
            addEventListener: (name, callback) => { listeners[name] = callback; }
        },
        window: {
            omoConfig: { mode },
            location: { reload: () => { reloads++; } },
            setTimeout: (callback, delay) => { timers.push({ callback, delay }); },
            addEventListener: (name, callback) => { listeners[name] = callback; },
            dispatchEvent: event => { events.push(event); }
        },
        CustomEvent: function (type, options) { this.type = type; this.detail = options.detail; },
        fetch: async (url, options) => {
            requests.push({ url, options });
            return { status, ok: status === 200, json: async () => ({ status: true }) };
        }
    });
    vm.runInContext(source.slice(modeStart, modeEnd) + source.slice(start, end), context);
    return { context, timers, listeners, requests, events, reloads: () => reloads };
}

async function flushRequests() {
    await new Promise(resolve => setImmediate(resolve));
}

async function main() {
    for (const readyState of ['loading', 'complete']) {
        const share = createPage('share', 401, readyState);
        assert.equal(share.timers.length, 0, 'Public shares must not schedule authenticated maintenance.');
        assert.deepEqual(Object.keys(share.listeners), [], 'Public shares must not install maintenance listeners.');
        // Even a direct forced invocation must stay inactive on a public share.
        vm.runInContext('omoRuntimeMaintenanceReady = true;', share.context);
        await share.context.omoRunRuntimeMaintenance({ force: true });
        assert.equal(share.requests.length, 0);
        assert.equal(share.reloads(), 0);
    }

    const signedIn = createPage(undefined, 200, 'loading');
    assert.equal(signedIn.timers.length, 0, 'Maintenance must wait for page load.');
    signedIn.listeners.load();
    assert.equal(signedIn.timers[0].delay, 2000);
    signedIn.timers[0].callback();
    await flushRequests();
    assert.equal(signedIn.requests.length, 1);
    assert.equal(signedIn.requests[0].url, '/omo/api/runtime_maintenance.php');
    assert.equal(signedIn.requests[0].options.method, 'POST');
    assert.equal(signedIn.events[0].type, 'omo-runtime-maintenance');
    signedIn.listeners.focus();
    signedIn.listeners.visibilitychange();
    await flushRequests();
    assert.equal(signedIn.requests.length, 1, 'Focus and visibility must respect the cooldown.');
    signedIn.listeners.online();
    await flushRequests();
    signedIn.listeners.pageshow({ persisted: true });
    await flushRequests();
    assert.equal(signedIn.requests.length, 3, 'Online and restored pages must still trigger maintenance.');
    assert.equal(signedIn.reloads(), 0);

    const expired = createPage(undefined);
    expired.timers[0].callback();
    await flushRequests();
    assert.equal(expired.reloads(), 1, 'Expired authenticated sessions must still reload.');
    await expired.context.omoRunRuntimeMaintenance({ force: true });
    assert.equal(expired.reloads(), 1, 'Concurrent session expiry must not reload twice.');
    console.log('runtime_maintenance_share_test: OK');
}

main().catch(error => { console.error(error); process.exitCode = 1; });
