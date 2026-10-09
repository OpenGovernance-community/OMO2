'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const source = fs.readFileSync(path.join(__dirname, '../omo/assets/js/install.js'), 'utf8');

function createPage(getInstalledRelatedApps, standalone = false) {
    const events = new Map();
    let banner = null;
    let cookieWrites = 0;
    const host = {appendChild(element) { banner = element; }};
    const document = {
        body: host,
        querySelector() { return null; },
        createElement() {
            const attributes = new Set();
            return {
                classList: {add() {}, remove() {}},
                setAttribute(name) { attributes.add(name); },
                removeAttribute(name) { attributes.delete(name); },
                hasAttribute(name) { return attributes.has(name); },
                querySelector() { return {addEventListener() {}}; },
            };
        },
        get cookie() { return ''; },
        set cookie(value) { cookieWrites += 1; },
    };
    const navigator = getInstalledRelatedApps ? {getInstalledRelatedApps} : {};
    const window = {
        navigator,
        matchMedia() { return {matches: standalone}; },
        addEventListener(name, callback) { events.set(name, callback); },
    };
    vm.runInNewContext(source, {window, document, navigator, console: {warn() {}, error() {}}});
    return {
        prompt() {
            let prevented = false;
            events.get('beforeinstallprompt')({preventDefault() { prevented = true; }});
            assert(prevented, 'The native install promotion is deferred');
        },
        installed() { events.get('appinstalled')(); },
        visible() { return banner !== null && !banner.hasAttribute('hidden'); },
        cookieWrites() { return cookieWrites; },
    };
}

async function flush() {
    await new Promise(resolve => setImmediate(resolve));
}

(async () => {
    let apps = [{platform: 'webapp', id: 'https://omo.test/omo/o/1'}];
    let calls = 0;
    const query = async () => { calls += 1; return apps; };
    const installedPage = createPage(query);
    installedPage.prompt();
    await flush();
    assert.equal(calls, 1, 'The browser is queried before deciding whether to display the banner');
    assert.equal(installedPage.visible(), false, 'An installed related PWA suppresses the banner');
    assert.equal(installedPage.cookieWrites(), 0, 'Detection does not persist installation in cookies');

    apps = [];
    const afterRemoval = createPage(query);
    afterRemoval.prompt();
    await flush();
    assert.equal(afterRemoval.visible(), true, 'A new visit after uninstalling offers installation again');
    assert.equal(afterRemoval.cookieWrites(), 0, 'An empty result also writes no cookies');

    const unsupported = createPage();
    unsupported.prompt();
    assert.equal(unsupported.visible(), true, 'Browsers without the API keep the existing banner');

    const failing = createPage(async () => { throw new Error('API unavailable'); });
    failing.prompt();
    await flush();
    assert.equal(failing.visible(), true, 'A rejected browser query keeps the installation offer available');

    const nativeOnly = createPage(async () => [{platform: 'play', id: 'other.app'}]);
    nativeOnly.prompt();
    await flush();
    assert.equal(nativeOnly.visible(), true, 'Only related webapps suppress the PWA offer');

    let finishQuery;
    const pending = createPage(() => new Promise(resolve => { finishQuery = resolve; }));
    pending.prompt();
    assert.equal(pending.visible(), false, 'The banner stays hidden while detection is pending');
    pending.installed();
    finishQuery([]);
    await flush();
    assert.equal(pending.visible(), false, 'Installation during the query cannot reopen the banner');

    let standaloneCalls = 0;
    const standalonePage = createPage(async () => { standaloneCalls += 1; return []; }, true);
    standalonePage.prompt();
    await flush();
    assert.equal(standalonePage.visible(), false, 'The standalone application never offers installation');
    assert.equal(standaloneCalls, 0, 'Standalone mode needs no browser query');

    console.log('OMO installation detection: passed.');
})().catch(error => { console.error(error); process.exitCode = 1; });
