'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {JSDOM} = require('jsdom');

(async () => {
    const dom = new JSDOM('<main id="drawer"></main>', {runScripts: 'outside-only'});
    const {window} = dom;
    const {document} = window;
    window.matchMedia = () => ({matches: false, addEventListener() {}});
    await new Promise(resolve => window.addEventListener('load', resolve, {once: true}));
    window.eval(fs.readFileSync(path.join(__dirname, '../common/assets/components.js'), 'utf8'));
    const drawer = document.getElementById('drawer');
    // Reproduce an editor arriving after the shared page scripts have initialized.
    drawer.innerHTML = '<form><section class="generic-accordion--collapsible is-collapsed" data-generic-accordion>'
        + '<label><input type="checkbox" data-generic-accordion-toggle>Planification</label>'
        + '<div class="generic-accordion__content"><input name="evaluation_start_at" type="datetime-local"></div></section></form>'
        + '<section class="generic-accordion--collapsible is-collapsed" data-generic-accordion><button data-generic-accordion-toggle>Legacy toggle</button></section>';
    await window.commonExecuteFragmentScripts(drawer);
    const section = drawer.querySelector('section');
    const toggle = section.querySelector('[data-generic-accordion-toggle]');
    const input = section.querySelector('[name="evaluation_start_at"]');
    assert.equal(section.dataset.genericAccordionReady, '1', 'Fragment loading initializes shared controls');
    assert.equal(toggle.getAttribute('aria-expanded'), 'false');
    toggle.click();
    assert(!section.classList.contains('is-collapsed'), 'Checking the schedule opens the date fields');
    input.value = '2026-11-10T09:30';
    toggle.click();
    assert(section.classList.contains('is-collapsed'), 'Unchecking closes the date fields');
    toggle.click();
    assert.equal(input.value, '2026-11-10T09:30', 'Reopening preserves the entered schedule');
    assert.equal(new window.FormData(drawer.querySelector('form')).get('evaluation_start_at'), input.value, 'Schedule still uses the existing save fields');
    window.initGenericComponents(drawer);
    toggle.click();
    assert(section.classList.contains('is-collapsed'), 'Repeated initialization does not double-toggle');
    const secondPhase = section.cloneNode(true);
    secondPhase.removeAttribute('data-generic-accordion-ready');
    secondPhase.querySelector('input[type="datetime-local"]').name = 'consultation_start_at';
    drawer.querySelector('form').appendChild(secondPhase);
    await window.commonExecuteFragmentScripts(drawer);
    secondPhase.querySelector('[data-generic-accordion-toggle]').click();
    assert(!secondPhase.classList.contains('is-collapsed'), 'Each phase opens independently');
    assert(section.classList.contains('is-collapsed'), 'Opening elaboration leaves evaluation unchanged');
    const legacy = drawer.querySelector('button[data-generic-accordion-toggle]').closest('section');
    legacy.querySelector('button').click();
    assert(!legacy.classList.contains('is-collapsed'), 'Button accordions also work in loaded fragments');
    const root = document.createElement('section');
    root.setAttribute('data-generic-accordion', '');
    root.innerHTML = '<input type="checkbox" data-generic-accordion-toggle checked>';
    drawer.appendChild(root);
    await window.commonExecuteFragmentScripts(root);
    assert.equal(root.dataset.genericAccordionReady, '1', 'An accordion at the fragment root is initialized');
    assert.equal(root.querySelector('input').getAttribute('aria-expanded'), 'true');
    dom.window.close();
    console.log('Decision schedule toggle: passed.');
})().catch(error => { console.error(error); process.exitCode = 1; });
