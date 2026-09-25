'use strict';
// Run with jsdom on NODE_PATH. No server or database writes.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {JSDOM} = require('jsdom');
const source = fs.readFileSync(path.join(__dirname, '../omo/api/policy/index.js'), 'utf8');

for (const storedScope of ['local', 'global', 'contextual', 'children', 'descendants']) {
    const dom = new JSDOM(`<div id="omo-policy-root" data-policy-oid="1" data-policy-cid="2" data-policy-index-url="/policy?oid=1&cid=2" data-policy-scope="contextual">
        <div data-policy-filter-control><button data-policy-filter-toggle>Filters</button><div data-policy-filter-panel class="is-filter-hidden">
            ${['local', 'contextual', 'global'].map(scope => `<button data-policy-scope-choice="${scope}">${scope}</button>`).join('')}
            <button data-policy-filter-apply>Apply</button><button data-policy-filter-save>Save</button>
        </div></div><div data-policy-drawer hidden><div data-policy-drawer-body></div></div>
    </div>`, {url: 'https://omo.test/', runScripts: 'outside-only', pretendToBeVisual: true});
    const {window} = dom;
    const {document} = window;
    const requests = [];
    window.omoReplaceFetchedPanelRoot = options => { requests.push(options.url); return Promise.resolve(); };
    window.sessionStorage.setItem('omo.policy.session-views.v1', JSON.stringify({'1:2': {scope: storedScope}}));
    window.eval(source);
    if (['local', 'global'].includes(storedScope)) {
        assert.equal(new URL(requests[0], window.location.origin).searchParams.get('policy_scope'), storedScope, 'Saved new filters must restore');
    } else {
        assert.equal(requests.length, 0, 'Retired filters normalize to contextual without refresh loops');
    }
    document.querySelector('[data-policy-filter-toggle]').click();
    document.querySelector('[data-policy-scope-choice="global"]').click();
    assert.equal(document.querySelector('[data-policy-scope-choice="global"]').getAttribute('aria-pressed'), 'true');
    document.querySelector('[data-policy-filter-save]').click();
    const saved = JSON.parse(window.localStorage.getItem('omo.policy.saved-views.v2'));
    assert.equal(saved.contexts['1:2'].scope, 'global');
    assert.equal(new URL(requests.at(-1), window.location.origin).searchParams.get('policy_scope'), 'global');
    document.querySelector('[data-policy-filter-toggle]').click();
    document.querySelector('[data-policy-scope-choice="local"]').click();
    document.querySelector('[data-policy-filter-apply]').click();
    assert.equal(new URL(requests.at(-1), window.location.origin).searchParams.get('policy_scope'), 'local');
    const temporary = JSON.parse(window.sessionStorage.getItem('omo.policy.session-views.v1'));
    assert.equal(temporary['1:2'].scope, 'local');
    dom.window.close();
}
console.log('policy_view_filters_test: OK');
