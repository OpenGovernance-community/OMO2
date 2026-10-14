'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {JSDOM} = require('jsdom');
const root = path.resolve(__dirname, '..');
const types = ['everyone', 'organization', 'circle', 'role', 'self'];
const dom = new JSDOM(`<div data-omo-project-form-root><form id="editor">
    <button type="button" data-omo-project-parent-picker>Parent</button>
    <input type="hidden" data-omo-project-holon-id value="12">
    <div data-omo-visibility-choice>${types.map(type => `<input type="radio" class="omo-visibility-choice__input"
        name="visibility_type" id="visibility-${type}" value="${type}" ${type === 'role' ? 'checked' : ''}>
        <label for="visibility-${type}">${type}</label>`).join('')}</div></form></div>`,
    {url: 'https://omo.test/', runScripts: 'outside-only'});
try {
    const {window} = dom;
    const {document, Event} = window;
    const selectorSource = fs.readFileSync(path.join(root, 'common/object_visibility_selector.php'), 'utf8').match(/<script>([\s\S]*?)<\/script>/)[1];
    window.eval(selectorSource);
    window.eval(fs.readFileSync(path.join(root, 'omo/api/projects/create.js'), 'utf8'));
    window.commonPageScripts['/omo/api/projects/create.js']({formId: 'editor', texts: {}, visibilityHolons: {12: true, 13: false}});
    const form = document.querySelector('form');
    const radio = type => form.querySelector(`[value="${type}"]`);
    const choice = form.querySelector('[data-omo-visibility-choice]');
    const holon = form.querySelector('[data-omo-project-holon-id]');
    assert.equal(radio('role').disabled, false);
    assert.equal(choice.style.getPropertyValue('--omo-visibility-active-index'), '3');
    radio('self').click();
    assert.equal(choice.style.getPropertyValue('--omo-visibility-active-index'), '4', 'Shared slider follows the selected scope.');
    assert.equal(new window.FormData(form).get('visibility_type'), 'self');
    radio('circle').click();
    holon.value = '13'; holon.dispatchEvent(new Event('change'));
    assert.equal(radio('circle').disabled, true, 'A holon without a containing circle cannot use circle visibility.');
    assert.equal(radio('organization').checked, true, 'Moving to an incompatible space chooses a valid scope.');
    assert.equal(choice.style.getPropertyValue('--omo-visibility-active-index'), '1');
    holon.value = ''; holon.dispatchEvent(new Event('change'));
    assert.equal(radio('role').disabled, true);
    assert.equal(form.querySelector('label[for="visibility-role"]').classList.contains('is-disabled'), true);
    radio('self').click();
    assert.equal(new window.FormData(form).get('visibility_type'), 'self', 'Personal visibility remains available in organization space.');
    holon.value = '12'; holon.dispatchEvent(new Event('change'));
    assert.equal(radio('circle').disabled, false);
    assert.equal(radio('role').disabled, false);
    console.log('project_visibility_form_test: OK');
} finally { dom.window.close(); }
