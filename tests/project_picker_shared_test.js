'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {JSDOM} = require('jsdom');
const read = file => fs.readFileSync(path.join(__dirname, '..', file), 'utf8');
const dom = new JSDOM('<div id="commonTopbarModalBody"></div>', {url: 'https://omo.test/', runScripts: 'outside-only'});
const w = dom.window, body = w.document.getElementById('commonTopbarModalBody');
const labels = {assignmentAria: 'Projects', assignmentMine: 'Moi', assignmentFollowed: 'Suivi', assignmentEveryone: 'Tout le monde', quickSearchPlaceholder: 'Search <projects>'};
const projects = [{id: 1, title: 'Parent', contextHolonId: 1, isMine: true}, {id: 2, title: 'Child', contextHolonId: 2, isFollowed: true}];
let scope = 0, scopeChanged, inserted = null, closed = 0;
w.omoMountHolonScopePicker = options => { scopeChanged = options.onChange; return {matches: id => !scope || id === scope, getSelectedHolonId: () => scope, destroy: () => {}}; };
w.eval(read('common/project-picker/project-picker.js'));
Object.assign(w, {
    canEmbedProjects: true, projectEmbedUi: labels, projectEmbedCreateContext: {}, embeddableProjects: projects,
    resourcePickerOrganizationId: 1, resourcePickerInitialHolonId: 0, resourcePickerScopeUi: {},
    escapeDocumentEmbedHtml: value => String(value || '').replace(/</g, '&lt;'),
    buildPvProjectEmbedHtml: project => '<span>' + project.title + '</span>',
    insertPvEmbedIntoField: (field, target, marker, html) => { inserted = html; return true; },
    refreshPvProjectEmbedReviews: () => {},
    loadPvResourceCatalog: (type, items, root, callback) => callback(),
    commonTopbarOpenModal: (title, html) => { body.innerHTML = html; },
    commonTopbarCloseModal: () => { closed++; w.dispatchEvent(new w.Event('common-topbar-modal-close')); }
});
const source = read('omo/api/documents/pv/editor.js');
w.eval(source.slice(source.indexOf('    function openPvProjectEmbedPicker('), source.indexOf('    function buildPvEventEmbedHtml(')));
w.openPvProjectEmbedPicker({createTemporaryCursorMarker: () => ({}), removeTemporaryMarker: () => {}}, null);
assert.ok(body.querySelector('.generic-tabs.common-project-picker > .omo-resource-picker__navigation'), 'PV tabs share their scope navigation with creation');
assert.equal(body.querySelectorAll('[data-omo-pv-project-embed-select] option').length, 2);
assert.ok(body.querySelector('[data-omo-pv-project-create-form]'), 'PV creation tab remains available');
body.querySelector('[data-omo-pv-project-embed-assignment="followed"]').click();
assert.equal(body.querySelector('[data-omo-pv-project-embed-select]').value, '2');
body.querySelector('[data-omo-pv-project-embed-insert]').click();
assert.equal(inserted, '<span>Child</span>');
assert.equal(closed, 1);
body.innerHTML = w.commonProjectPickerMarkup({labels, prefix: 'data-test', multiple: true});
const controller = w.commonMountProjectPicker({root: body, scopeHost: '[data-test-scope]', searchInput: '[data-test-search]', selectElement: '[data-test-select]', assignmentAttribute: 'data-test-assignment', projects, multiple: true});
const select = body.querySelector('[data-test-select]');
select.value = '1'; select.dispatchEvent(new w.Event('change'));
scope = 2; scopeChanged(2);
select.value = '2'; select.dispatchEvent(new w.Event('change'));
assert.deepEqual(Array.from(controller.getSelectedIds()), [1, 2], 'Selections survive circle changes');
const search = body.querySelector('[data-test-search]');
search.value = 'missing'; search.dispatchEvent(new w.Event('input'));
assert.equal(select.options.length, 0);
assert.deepEqual(Array.from(controller.getSelectedIds()), [1, 2], 'Search never drops hidden selections');
assert.equal(search.placeholder, labels.quickSearchPlaceholder, 'Labels are escaped without changing their text');
controller.destroy();
dom.window.close();
console.log('Shared project picker: PV insertion/creation, filters, scopes and multi-selection passed.');
