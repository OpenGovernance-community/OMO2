'use strict';
// Run scrum_http_test.php first, then run with jsdom on NODE_PATH.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {JSDOM} = require('jsdom');
const fixture = JSON.parse(fs.readFileSync(path.join(__dirname, '../tmp/scrum-browser-fixture.json'), 'utf8'));
const source = fs.readFileSync(path.join(__dirname, '../omo/api/scrum/scrum.js'), 'utf8');
const pickerSource = fs.readFileSync(path.join(__dirname, '../common/project-picker/project-picker.js'), 'utf8');

async function main() {
    const dom = new JSDOM(fixture.picker, {url: 'https://omo.test/omo/', runScripts: 'outside-only'});
    const {window} = dom; const {document} = window; const requests = []; const navigation = [];
    window.omoNotify = () => {};
    window.omoReplaceFetchedPanelRoot = options => { navigation.push(options); return Promise.resolve(options.currentRoot); };
    window.omoMountHolonScopePicker = () => ({matches: () => true, getSelectedHolonId: () => 0, destroy: () => {}});
    window.fetch = async (url, options) => { requests.push({url, options}); return {json: async () => ({status: true, message: 'Saved', url: '/omo/api/scrum/index.php?id=1'})}; };
    window.eval(pickerSource); window.eval(source);
    const select = document.querySelector('[data-scrum-select]');
    select.value = String(fixture.parent); select.dispatchEvent(new window.Event('change', {bubbles: true}));
    const preview = document.querySelector('[data-scrum-preview]');
    assert.equal(preview.querySelectorAll('select').length, 2, 'Picking a parent includes all children');
    assert.match(document.querySelector('[data-scrum-total]').textContent, /4 points/);
    const parentSize = preview.querySelector('[data-project-id="' + fixture.parent + '"]');
    parentSize.value = 'XXL'; parentSize.dispatchEvent(new window.Event('change', {bubbles: true}));
    assert.match(document.querySelector('[data-scrum-total]').textContent, /256 points/, 'Preview must not double-count child work');
    const changedSize = preview.querySelector('[data-project-id="' + fixture.parent + '"]');
    changedSize.value = 'S'; changedSize.dispatchEvent(new window.Event('change', {bubbles: true}));
    assert.match(document.querySelector('[data-scrum-total]').textContent, /4 points/, 'Underestimated parent uses recursive child total');
    assert.ok(preview.querySelector('small'), 'Underestimation must be explained inline');
    document.querySelector('[data-scrum-import]').click();
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(requests.length, 1);
    assert.equal(requests[0].options.body.get('action'), 'import');
    assert.equal(requests[0].options.body.get('sizes[' + fixture.parent + ']'), 'S');
    assert.equal(requests[0].options.body.get('sizes[' + fixture.child + ']'), 'M');
    assert.equal(navigation[0].rootSelector, '#omo-scrum-root');
    dom.window.close();

    const editorDom = new JSDOM(fixture.editor, {url: 'https://omo.test/omo/', runScripts: 'outside-only'});
    const editorWindow = editorDom.window; let saved;
    editorWindow.omoNotify = () => {};
    editorWindow.omoReplaceFetchedPanelRoot = () => Promise.resolve(null);
    editorWindow.fetch = async (url, options) => { saved = options.body; return {json: async () => ({status: true, url: '/omo/api/scrum/index.php?id=1'})}; };
    editorWindow.eval(source);
    const form = editorWindow.document.querySelector('#formulaire-edit');
    form.querySelector('[name="title"]').value = 'Sprint browser test';
    form.dispatchEvent(new editorWindow.Event('submit', {bubbles: true, cancelable: true}));
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(saved.get('title'), 'Sprint browser test'); assert.equal(saved.get('action'), 'save');
    assert.ok(saved.get('_csrf'), 'Editor must submit its session CSRF token');
    editorDom.window.close();

    const boardDom = new JSDOM(fixture.detail, {url: 'https://omo.test/omo/#scrum', runScripts: 'outside-only', pretendToBeVisual: true});
    const boardWindow = boardDom.window;
    boardWindow.resetGenericExpandedMenu = () => {};
    let reloadOptions;
    boardWindow.omoReplaceFetchedPanelRoot = options => { reloadOptions = options; return Promise.resolve(options.currentRoot); };
    boardWindow.eval(fs.readFileSync(path.join(__dirname, '../omo/api/projects/projects.js'), 'utf8'));
    await boardWindow.omoProjectsAfterSave();
    assert.equal(reloadOptions.rootSelector, '#omo-scrum-root', 'Project saves must refresh the sprint and its chart');
    assert.match(reloadOptions.url, /\/scrum\/index.php/, 'The kanban must stay in Scrum');
    assert.equal(boardWindow.location.hash, '#scrum');
    boardDom.window.close();
    console.log('Scrum browser estimation, import and adminEdit tests passed.');
}
main().catch(error => { console.error(error); process.exitCode = 1; });
