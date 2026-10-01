'use strict';

// Run with jsdom on NODE_PATH. No server or database writes are needed.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {JSDOM} = require('jsdom');

const tabs = (id, nested = '') => `<div id="${id}" class="generic-tabs" data-generic-tabs>
  <div class="generic-tabs__list" aria-label="Sections ${id}">
    <button data-generic-tab data-generic-tab-target="${id}-info" class="is-active">Informations</button>
    <button data-generic-tab data-generic-tab-target="${id}-docs" aria-label="Documents complets"><img alt="">Docs</button>
    <button data-generic-tab data-generic-tab-target="${id}-locked" disabled>Indisponible</button>
    <button data-generic-tab data-generic-tab-target="${id}-hidden" hidden>Masque</button>
  </div>
  <div id="${id}-info" data-generic-tab-panel>${nested}</div>
  <div id="${id}-docs" data-generic-tab-panel hidden>Documents</div>
  <div id="${id}-locked" data-generic-tab-panel hidden></div>
  <div id="${id}-hidden" data-generic-tab-panel hidden></div>
</div>`;

(async () => {
    const dom = new JSDOM(tabs('outer', tabs('inner')), {runScripts: 'outside-only'});
    const {window} = dom;
    const {document} = window;
    window.matchMedia = () => ({matches: true, addEventListener() {}});
    await new Promise(resolve => window.addEventListener('load', resolve, {once: true}));
    window.eval(fs.readFileSync(path.join(__dirname, '../common/assets/components.js'), 'utf8'));
    const settle = () => new Promise(resolve => window.setTimeout(resolve, 0));
    const outer = document.getElementById('outer');
    const select = outer.querySelector('.generic-tabs__select');
    const buttons = outer.querySelector('.generic-tabs__list').querySelectorAll('button');
    const innerSelect = document.querySelector('#inner .generic-tabs__select');

    assert.equal(select.getAttribute('aria-label'), 'Sections outer');
    assert.equal(select.options.length, 3, 'Hidden tabs must not be offered');
    assert.equal(select.options[1].textContent, 'Documents complets', 'Keep icon tab accessible labels');
    assert.equal(select.options[2].disabled, true);
    assert.equal(select.value, buttons[0].id);
    assert.equal(innerSelect.options.length, 3, 'Nested tabs have their own selector');

    let loads = 0;
    buttons[1].addEventListener('click', () => { loads += 1; });
    select.value = buttons[1].id;
    select.dispatchEvent(new window.Event('change', {bubbles: true}));
    await settle();
    assert.equal(loads, 1, 'Selection must run existing module loading handlers exactly once');
    assert.equal(document.getElementById('outer-docs').hidden, false);
    assert.equal(document.getElementById('outer-info').hidden, true);
    assert.equal(select.getAttribute('aria-controls'), 'outer-docs');
    assert.equal(innerSelect.selectedIndex, 0, 'Parent selection must not activate a nested tab');

    buttons[0].click();
    await settle();
    assert.equal(select.value, buttons[0].id, 'Programmatic and desktop clicks stay in sync');
    buttons[0].dispatchEvent(new window.KeyboardEvent('keydown', {key: 'ArrowRight', bubbles: true}));
    await settle();
    assert.equal(select.value, buttons[1].id, 'Desktop arrow navigation stays in sync');

    buttons[1].setAttribute('aria-label', 'Documents (5)');
    buttons[2].disabled = false;
    buttons[3].hidden = false;
    await settle();
    assert.equal(select.options[1].textContent, 'Documents (5)');
    assert.equal(select.options[2].disabled, false);
    assert.equal(select.options.length, 4);
    window.initGenericComponents(document);
    window.initGenericTabs(outer);
    assert.equal(outer.querySelectorAll(':scope > .generic-tabs__mobile').length, 1, 'Repeated AJAX init must not duplicate controls');

    buttons[2].disabled = true;
    select.value = buttons[2].id;
    select.dispatchEvent(new window.Event('change', {bubbles: true}));
    assert.equal(document.getElementById('outer-locked').hidden, true, 'Disabled options cannot activate panels');
    assert.equal(select.value, buttons[1].id);

    const host = document.createElement('section');
    host.innerHTML = tabs('ajax');
    document.body.appendChild(host);
    window.initGenericComponents(host);
    assert.equal(host.querySelectorAll('.generic-tabs__select').length, 1, 'AJAX fragments receive the mobile selector');

    const singleRoot = document.createElement('div');
    singleRoot.innerHTML = tabs('fragment');
    const fragment = singleRoot.firstElementChild;
    document.body.appendChild(fragment);
    window.initGenericComponents(fragment);
    const fragmentSelect = fragment.querySelector('.generic-tabs__select');
    assert(fragmentSelect, 'An AJAX root that is itself a tabs component must be initialized');
    fragment.querySelector('.generic-tabs__list').insertAdjacentHTML('beforeend', '<button data-generic-tab data-generic-tab-target="late-panel">Historique</button>');
    fragment.insertAdjacentHTML('beforeend', '<div id="late-panel" data-generic-tab-panel hidden>Historique charge</div>');
    await settle();
    fragmentSelect.value = fragmentSelect.options[3].value;
    fragmentSelect.dispatchEvent(new window.Event('change', {bubbles: true}));
    assert.equal(document.getElementById('late-panel').hidden, false, 'Tabs added after initialization must activate normally');
    dom.window.close();
    console.log('Generic mobile tabs: selection, lazy loading, nesting, keyboard, dynamic updates and AJAX init passed.');
})().catch(error => { console.error(error); process.exitCode = 1; });
