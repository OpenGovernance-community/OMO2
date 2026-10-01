'use strict';

// Run with jsdom on NODE_PATH. No application server or database writes.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {JSDOM} = require('jsdom');

const source = fs.readFileSync(path.join(__dirname, '../omo/api/stats/stats.js'), 'utf8');
const subdrawer = fs.readFileSync(path.join(__dirname, '../common/drawer/subdrawer.js'), 'utf8');

async function checkImportNavigation(localNavigation, cid) {
    const base = '/omo/api/stats/index.php?oid=1&cid=' + cid;
    const dom = new JSDOM(`<!doctype html><body>
        <div id="omo-stats-root" data-omo-stats-oid="1" data-omo-stats-route-cid="${cid}"
            data-omo-stats-current-url="${base}"
            data-omo-stats-detail-url="/omo/api/stats/detail.php?oid=1&cid=${cid}">
            <article id="card" data-omo-stats-indicator-id="10" data-omo-stats-import-id="20" tabindex="0"></article>
            <div id="row" data-omo-stats-indicator-id="10" data-omo-stats-import-id="20" tabindex="0"></div>
            <article id="original" data-omo-stats-indicator-id="11" data-omo-stats-import-id="0"></article>
            <div data-omo-stats-drawer hidden>
                <h3 data-omo-subdrawer-title></h3><p data-omo-subdrawer-description></p>
                <div data-omo-subdrawer-actions></div>
                <button data-omo-stats-drawer-close>Close</button>
                <div data-omo-stats-drawer-body></div>
            </div>
        </div>
    </body>`, {url: 'https://omo.test/', runScripts: 'outside-only', pretendToBeVisual: true});
    const {window} = dom;
    const {document} = window;
    const requests = [];
    const routes = [];
    const refreshed = [];
    let confirmed = true;
    let failDelete = false;
    let notification = '';
    window.confirm = () => confirmed;
    window.omoNotify = message => { notification = message; };
    window.omoIsPvApplicationTabContext = () => localNavigation;
    window.omoOpenDrawerHashState = token => routes.push(token);
    window.commonExecuteFragmentScripts = () => Promise.resolve();
    window.omoReplaceFetchedPanelRoot = options => {
        refreshed.push(options.url);
        return Promise.resolve(document.getElementById('omo-stats-root'));
    };
    window.fetch = async (url, options) => {
        requests.push({url: new URL(url, window.location.origin), options});
        if (options.method === 'POST') {
            return {ok: !failDelete, json: async () => ({success: !failDelete, message: 'Denied'})};
        }
        return {ok: true, text: async () => `<article data-omo-stats-detail data-indicator-id="10" data-import-id="20">
            <div hidden data-omo-subdrawer-header data-omo-subdrawer-title="Imported indicator">
                <button data-omo-subdrawer-action data-omo-stats-delete-import="20">Detach</button>
            </div><div>Source values</div></article>`};
    };
    const settle = () => new Promise(resolve => window.setTimeout(resolve, 25));
    window.eval(subdrawer);
    window.eval(source);
    window.commonPageScripts['/omo/api/stats/stats.js']({texts: {loading: 'Loading', loadError: 'Error', confirmDeleteImport: 'Detach?'}});

    document.getElementById('card').click();
    await settle();
    assert.equal(routes.length, 0, 'An import must not navigate to the original route');
    assert.equal(requests.at(-1).url.searchParams.get('import_id'), '20');
    assert.equal(requests.at(-1).url.searchParams.get('cid'), String(cid));
    assert.equal(requests.at(-1).url.searchParams.has('id'), false, 'The import selects its source server-side');

    document.getElementById('row').dispatchEvent(new window.KeyboardEvent('keydown', {key: 'Enter', bubbles: true}));
    await settle();
    assert.equal(requests.at(-1).url.searchParams.get('import_id'), '20', 'Compact keyboard navigation preserves the import');
    assert.equal(routes.length, 0);

    const detach = document.querySelector('[data-omo-subdrawer-actions] [data-omo-stats-delete-import]');
    assert(detach, 'The shared drawer must promote the detach action to its header');
    const requestCount = requests.length;
    confirmed = false;
    detach.click();
    await settle();
    assert.equal(requests.length, requestCount, 'Cancellation must not send any mutation');
    confirmed = true;
    failDelete = true;
    detach.click();
    await settle();
    assert.equal(refreshed.length, 0, 'A rejected detach must leave the list intact');
    assert.equal(detach.disabled, false);
    assert.equal(notification, 'Denied');
    failDelete = false;
    detach.click();
    await settle();
    const form = requests.at(-1).options.body;
    assert.equal(form.get('stats_action'), 'delete_import');
    assert.equal(form.get('import_id'), '20');
    assert.equal(form.get('cid'), String(cid), 'Permissions must be checked in the destination context');
    assert.equal(form.get('oid'), '1');
    assert.equal(form.has('indicator_id'), false, 'Detaching must never submit the original for deletion');
    assert.deepEqual(refreshed, [base]);
    assert(!document.querySelector('[data-omo-stats-drawer]').classList.contains('is-open'));

    document.getElementById('original').click();
    await settle();
    if (localNavigation) {
        assert.equal(requests.at(-1).url.searchParams.get('id'), '11');
        assert.equal(requests.at(-1).url.searchParams.has('import_id'), false);
    } else {
        assert.deepEqual(routes, ['stats-i11'], 'Original indicators keep their existing route');
    }
    window.close();
}

(async () => {
    for (const localNavigation of [false, true]) {
        for (const cid of [0, 7]) {
            await checkImportNavigation(localNavigation, cid);
        }
    }
    console.log('Indicator import drawer tests passed.');
})().catch(error => { console.error(error); process.exitCode = 1; });
