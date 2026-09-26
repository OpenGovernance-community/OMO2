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
// Simulate server rendering and script initialization after every panel refresh.
// Missing URL fields fall back to saved server preferences, as in GetInitialValue.
for (const savedSort of ['created', 'updated']) {
    for (const target of [
        {scope: 'contextual', sort: 'alpha', group: 'authority'},
        {scope: 'contextual', sort: savedSort, group: 'holon'},
        {scope: 'global', sort: 'alpha', group: 'holon'},
        {scope: 'local', sort: 'updated', group: 'none'}
    ]) {
        for (const restore of [false, true]) {
            const dom = new JSDOM('', {url: 'https://omo.test/', runScripts: 'outside-only', pretendToBeVisual: true});
            const {window} = dom;
            const {document} = window;
            const savedView = {scope: 'contextual', sort: savedSort, group: 'authority'};
            const requests = [];
            const pending = [];
            window.omoApplicationViewPreferencesGetPersonal = () => savedView;
            window.omoReplaceFetchedPanelRoot = options => {
                requests.push(options.url);
                pending.push(options.url);
                return Promise.resolve();
            };
            const render = url => {
                const params = new URL(url, window.location.origin).searchParams;
                const view = Object.fromEntries(Object.entries(savedView).map(([key, value]) => [key, params.get('policy_' + key) ?? value]));
                document.body.innerHTML = `<div id="omo-policy-root" data-policy-oid="33" data-policy-cid="989"
                    data-policy-index-url="/omo/api/policy/index.php?oid=33&cid=989&lang=fr"
                    data-policy-scope="${view.scope}" data-policy-sort="${view.sort}" data-policy-group="${view.group}">
                    <div data-policy-filter-control><button data-policy-filter-toggle>Filters</button>
                        <div data-policy-filter-panel class="is-filter-hidden">
                            ${Object.entries({scope: ['local', 'contextual', 'global'], sort: ['alpha', 'created', 'updated'], group: ['holon', 'authority', 'none']})
                                .map(([key, values]) => values.map(value => `<button data-policy-${key}-choice="${value}">${value}</button>`).join('')).join('')}
                            <button data-policy-filter-apply>Apply</button>
                        </div>
                    </div><div data-policy-drawer hidden><div data-policy-drawer-body></div></div>
                </div>`;
                window.eval(source);
            };
            if (restore) {
                window.sessionStorage.setItem('omo.policy.session-views.v1', JSON.stringify({'33:989': target}));
            }
            try {
                render('/omo/api/policy/index.php?oid=33&cid=989&lang=fr');
                if (!restore) {
                    assert.equal(requests.length, 0, 'Saved server view is already applied');
                    document.querySelector('[data-policy-filter-toggle]').click();
                    for (const [key, value] of Object.entries(target)) {
                        document.querySelector(`[data-policy-${key}-choice="${value}"]`).click();
                    }
                    document.querySelector('[data-policy-filter-apply]').click();
                }
                for (let count = 0; pending.length && count < 3; count++) render(pending.shift());
                assert.equal(requests.length, 1, `View must settle after one refresh: ${JSON.stringify({savedSort, target, restore})}`);
                const root = document.getElementById('omo-policy-root');
                for (const [key, value] of Object.entries(target)) {
                    assert.equal(root.getAttribute('data-policy-' + key), value, 'Rendered view matches the requested filter');
                }
            } finally {
                dom.window.close();
            }
        }
    }
}
console.log('policy_view_filters_test: OK');
