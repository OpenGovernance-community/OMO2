'use strict';

// Run with jsdom on NODE_PATH. No server or database writes.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {JSDOM} = require('jsdom');

const card = (id, title, content) => `<article id="rule-${id}" class="generic-accordion--collapsible is-collapsed" data-generic-accordion data-policy-rule-card>
    <h3><button data-generic-accordion-toggle aria-expanded="false" aria-controls="content-${id}"><span data-policy-search-text>${title}</span></button></h3>
    <div data-policy-rule-menu><button data-policy-rule-menu-toggle aria-expanded="false">...</button><div data-policy-rule-menu-panel hidden><button data-policy-rule-delete data-policy-rule-id="${id}">Delete</button></div></div>
    <div class="generic-accordion__content" id="content-${id}"><div data-policy-search-text>${content}</div>
    <details><summary>Documentation</summary><div data-policy-search-text>Intention confidentielle</div></details></div>
</article>`;

(async () => {
    const cards = card(1, 'R\u00e8gle &amp; s\u00e9curit\u00e9', '<p>Une <strong>r\u00e9union</strong> utile &amp; claire. Re\u0301union utile.</p><a href="/test?q=reunion">Lien</a>')
        + card(2, 'Autre sujet', '<p>Aucun resultat.</p>');
    const list = `<div class="omo-policy__body"><section data-policy-rule-group>${cards}</section><div data-policy-search-empty class="is-filter-hidden">Vide</div></div>`;
    const dom = new JSDOM(`<div id="omo-policy-root" data-policy-index-url="/policy" data-policy-oid="1"><input type="search" data-policy-quick-search><span class="omo-panel-view__count">2</span>${list}<div data-policy-drawer hidden><div data-policy-drawer-body></div></div></div>`, {
        url: 'https://omo.test/', runScripts: 'outside-only', pretendToBeVisual: true
    });
    const {window} = dom;
    const {document} = window;
    window.matchMedia = () => ({matches: false, addEventListener() {}, addListener() {}});
    window.commonNotify = () => {};
    window.confirm = () => true;
    window.fetch = async (url, options) => options.method === 'POST'
        ? {ok: true, json: async () => ({success: true})}
        : {ok: true, text: async () => `<div id="omo-policy-root"><span class="omo-panel-view__count">2</span>${list}</div>`};
    ['common/assets/components.js', 'common/search_text.js', 'omo/api/policy/index.js'].forEach(file => {
        window.eval(fs.readFileSync(path.join(__dirname, '..', file), 'utf8'));
    });
    const first = () => document.querySelector('#rule-1');
    const toggle = () => first().querySelector('[data-generic-accordion-toggle]');
    const search = value => {
        const input = document.querySelector('input');
        input.value = value;
        input.dispatchEvent(new window.Event('input', {bubbles: true}));
    };
    const closed = () => assert(first().classList.contains('is-collapsed'));
    closed();
    search('SECURITE');
    closed();
    assert.equal(first().querySelector('mark').textContent, 's\u00e9curit\u00e9');
    assert(document.querySelector('#rule-2').classList.contains('is-filter-hidden'));
    search('reunion utile');
    closed();
    assert.equal(first().querySelectorAll('mark').length, 3, 'Both occurrences, including inline markup and decomposed accents');
    assert.equal(first().querySelector('strong mark').textContent, 'r\u00e9union');
    assert.equal(first().querySelector('a').getAttribute('href'), '/test?q=reunion');
    assert.equal(first().querySelector('a').textContent, 'Lien');
    toggle().click();
    assert.equal(toggle().getAttribute('aria-expanded'), 'true');
    assert(!first().classList.contains('is-collapsed'));
    search('intention');
    assert(!first().classList.contains('is-collapsed'), 'Search preserves manual opening');
    assert.equal(first().querySelector('details').open, false);
    assert.equal(first().querySelector('details mark').textContent, 'Intention');
    toggle().click();
    closed();
    assert.equal(toggle().getAttribute('aria-expanded'), 'false');
    first().querySelector('[data-policy-rule-menu-toggle]').click();
    closed();
    assert.equal(first().querySelector('[data-policy-rule-menu-panel]').hidden, false);
    search('[.*');
    assert(!document.querySelector('[data-policy-search-empty]').classList.contains('is-filter-hidden'));
    search('');
    assert.equal(document.querySelectorAll('mark').length, 0);
    assert.equal(first().querySelector('strong').textContent, 'r\u00e9union');
    assert(!document.querySelector('#rule-2').classList.contains('is-filter-hidden'));
    search('reunion');
    first().querySelector('[data-policy-rule-delete]').click();
    await new Promise(resolve => window.setTimeout(resolve, 30));
    closed();
    assert.equal(first().querySelectorAll('mark').length, 2, 'List refresh reapplies highlighting');
    toggle().click();
    assert.equal(toggle().getAttribute('aria-expanded'), 'true', 'Refreshed accordions are initialized');
    dom.window.close();
    console.log('Policy accordion and search tests passed.');
})().catch(error => { console.error(error); process.exitCode = 1; });
