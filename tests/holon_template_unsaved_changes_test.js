'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const { JSDOM } = require('jsdom');

async function main() {
    const source = fs.readFileSync('omo/api/parameters/holon-templates/templates.js', 'utf8').replace(/\r\n/g, '\n');
    const dom = new JSDOM('<div id="omo-holon-template-page"><div id="omo-holon-template-editor"></div></div>', {
        url: 'https://omo.localtest.me/omo#parameters', runScripts: 'outside-only', pretendToBeVisual: true
    });
    const { window } = dom;
    const root = window.document.getElementById('omo-holon-template-editor');
    const selectIds = ['type', 'parent', 'definition-holon'];
    for (const match of source.matchAll(/querySelector\('#(omo-template-[^']+)'\)/g)) {
        if (window.document.getElementById(match[1])) continue;
        const suffix = match[1].replace('omo-template-', '');
        const tag = suffix === 'form' ? 'form' : selectIds.includes(suffix) ? 'select'
            : /^(name|color|unassigned-color|mandatory|locked|unique|link|admin)/.test(suffix) ? 'input' : 'div';
        const element = window.document.createElement(tag);
        element.id = match[1];
        root.append(element);
    }
    const newChild = window.document.createElement('button');
    newChild.dataset.templateAction = 'new-child';
    root.append(newChild);
    const newTemplate = window.document.createElement('button');
    newTemplate.dataset.templateAction = 'new';
    root.append(newTemplate);
    window.omoSizedImageField = { mount: (host, config) => ({ getValue: () => config.value, appendToFormData() {} }) };
    window.omoSimpleHtmlField = {};
    window.fetch = () => Promise.resolve({ ok: false });
    for (const file of ['common/assets/property-types.js', 'common/assets/property-list-conversion.js', 'common/permissions/editor.js']) {
        window.eval(fs.readFileSync(file, 'utf8'));
    }
    let accept = false;
    let prompts = 0;
    window.confirm = message => { assert(message); prompts++; return accept; };
    const templates = [1, 2].map(id => ({ id, name: 'Template ' + id, typeId: 1, properties: [], children: [] }));
    window.eval(source.replace('omoHolonTemplateBootstrapInitialRender();\n});',
        'omoHolonTemplateBootstrapInitialRender(); window.testReady = true;\n});'));
    const editorData = { templates, rootHolonId: 10, types: [{ id: 1, name: 'Role' }], formats: [{ id: 1, name: 'Text' }], propertyTypes: [{ key: 'type1', label: 'Type 1', canCreate: true }] };
    window.commonPageScripts['/omo/api/parameters/holon-templates/templates.js']({
        data: editorData,
        selectedId: 1, compactMode: false, omoHolonTemplateTexts: { confirmDiscardChanges: 'Discard unsaved changes?' }
    });
    await new Promise(resolve => setImmediate(resolve));
    assert(window.testReady, 'Editor must initialize');
    const name = window.document.getElementById('omo-template-name');
    const select = id => root.querySelector('[data-template-select="' + id + '"]').click();
    assert.equal(root.omoHasUnsavedChanges(), false);
    name.value = 'Changed';
    select(1);
    assert.equal(name.value, 'Changed', 'Selecting the same template must not reset it');
    assert.equal(prompts, 0);
    select(2);
    assert.equal(prompts, 1);
    assert.equal(name.value, 'Changed', 'Cancel must keep the draft');
    assert.equal(root.querySelector('form').dataset.templateId, '1');
    newTemplate.click();
    assert.equal(prompts, 2);
    assert.equal(name.value, 'Changed', 'Cancel new template must keep the draft');
    name.value = 'Template 1';
    assert.equal(root.omoHasUnsavedChanges(), false, 'Reverting changes should restore clean state');
    name.value = 'Changed';
    accept = true;
    select(2);
    assert.equal(name.value, 'Template 2');
    assert.equal(root.omoHasUnsavedChanges(), false);

    // The application guard and native unload use the actual registered editor.
    const app = fs.readFileSync('omo/assets/js/app.js', 'utf8');
    window.eval(app.slice(app.indexOf('function omoConfirmDiscardChanges('), app.indexOf('function loadContent(')));
    name.value = 'Unsaved';
    accept = false;
    assert.equal(window.omoConfirmDiscardChanges(), false);
    const unload = new window.Event('beforeunload', { cancelable: true });
    window.dispatchEvent(unload);
    assert(unload.defaultPrevented, 'Reload/close must warn');
    assert(root.omoHasUnsavedChanges(), 'The native unload warning must not clear the draft');

    // Exercise the real navigation entry point: cancellation precedes history changes.
    window.parseUrl = () => ({ oid: 1, cid: null, hash: 'parameters' });
    window.omoParseHashState = hash => ({ routeToken: (hash || '').split('|')[0] });
    window.buildOmoUrl = (oid, cid, hash) => '/omo#' + hash;
    let routed = 0;
    window.handleRoute = () => { routed++; };
    window.eval(app.slice(app.indexOf('function navigate('), app.indexOf('function parseUrl(')));
    window.navigate(1, null, 'structure');
    assert.equal(routed, 0);
    assert.equal(window.location.hash, '#parameters');
    assert.equal(name.value, 'Unsaved');
    accept = true;
    window.navigate(1, null, 'structure');
    assert.equal(routed, 1);
    assert.equal(window.location.hash, '#structure');
    assert.equal(root.omoHasUnsavedChanges(), false);

    select(1);
    const settle = () => new Promise(resolve => setImmediate(resolve));
    const submit = () => root.querySelector('form').dispatchEvent(new window.Event('submit', { cancelable: true }));
    name.value = 'Save failure';
    window.fetch = () => Promise.reject(new Error('Offline'));
    submit();
    await settle();
    assert(root.omoHasUnsavedChanges(), 'Failed saves must leave the warning active');
    assert.equal(name.value, 'Save failure');
    window.fetch = (url, options) => {
        const payload = JSON.parse(options.body.get('payload'));
        const template = { ...templates[0], name: payload.name };
        return Promise.resolve({ ok: true, json: () => Promise.resolve({ status: 'ok', template, data: { ...editorData, templates: [template, templates[1]] } }) });
    };
    submit();
    await settle();
    assert.equal(root.omoHasUnsavedChanges(), false, 'Successful saves reset the baseline');
    const cleanUnload = new window.Event('beforeunload', { cancelable: true });
    window.dispatchEvent(cleanUnload);
    assert.equal(cleanUnload.defaultPrevented, false);

    let finishSave;
    window.fetch = () => new Promise(resolve => { finishSave = resolve; });
    name.value = 'Submitted';
    submit();
    await settle();
    name.value = 'Edited during save';
    const savedTemplate = { ...templates[0], name: 'Submitted' };
    finishSave({ ok: true, json: () => Promise.resolve({ status: 'ok', template: savedTemplate, data: { ...editorData, templates: [savedTemplate, templates[1]] } }) });
    await settle();
    assert.equal(name.value, 'Edited during save');
    assert(root.omoHasUnsavedChanges(), 'Later edits remain unsaved');

    // A late save response must not switch back to the previously selected template.
    submit();
    await settle();
    select(2);
    finishSave({ ok: true, json: () => Promise.resolve({ status: 'ok', template: savedTemplate, data: editorData }) });
    await settle();
    assert.equal(name.value, 'Template 2');

    name.value = 'Back navigation draft';
    accept = false;
    window.currentState = { oid: 1, cid: null, hash: 'parameters', routeToken: 'parameters' };
    window.parseUrl = () => ({ oid: 1, cid: null, hash: 'structure' });
    window.eval(app.slice(app.indexOf('function handleRoute('), app.indexOf('function activateMenu(')));
    window.handleRoute();
    assert.equal(window.location.hash, '#parameters', 'Cancelled browser navigation restores the editor URL');
    assert.equal(name.value, 'Back navigation draft');

    const settings = window.document.createElement('div');
    settings.className = 'omo-settings';
    settings.innerHTML = '<div data-omo-settings-nested-drawer class="is-open"><button data-omo-settings-nested-close></button><div data-omo-settings-nested-body></div></div>';
    window.document.body.append(settings);
    settings.querySelector('[data-omo-settings-nested-body]').append(root);
    window.eval(fs.readFileSync('omo/api/parameters/index.js', 'utf8'));
    window.commonPageScripts['/omo/api/parameters/index.js']({ settingsTexts: {} });
    settings.querySelector('button').click();
    assert(settings.querySelector('[data-omo-settings-nested-drawer]').classList.contains('is-open'));
    assert.equal(name.value, 'Back navigation draft', 'Cancelling the settings drawer close preserves the editor');

    // A nameless property is still an unfinished draft and must not be ignored.
    const properties = window.document.getElementById('omo-template-properties');
    properties.innerHTML = '<div class="omo-template-property"><input class="omo-template-property__name" value=""><select class="omo-template-property__format"><option value="1">Text</option></select><input class="omo-template-property__value" value="Draft"></div>';
    assert(root.omoHasUnsavedChanges());
    accept = false;
    assert.equal(window.omoConfirmDiscardChanges(), false);
    root.remove();
    assert.equal(window.omoConfirmDiscardChanges(), true, 'Detached editors must not block later pages');
    dom.window.close();
    console.log('holon_template_unsaved_changes_test: OK');
}
main().catch(error => { console.error(error); process.exitCode = 1; });
