'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const { JSDOM } = require('jsdom');

const source = fs.readFileSync('omo/api/parameters/holon-templates/templates.js', 'utf8');
const start = source.indexOf('function omoHolonTemplateBuildParentOptions(');
const end = source.indexOf('\nfunction ', start + 1);
const dom = new JSDOM('<select id="parent"></select>', { runScripts: 'outside-only' });
const { window } = dom;
const select = window.document.getElementById('parent');
const catalog = [
    { id: 8, name: 'Secretaire', inheritsFromId: 5, lockedName: true },
    { id: 6, name: 'Organisation', lockedName: true },
    { id: 4, name: 'Projet', inheritsFromId: 1, lockedName: false },
    { id: 2, name: 'Role', inheritsFromId: 0, lockedName: false },
    { id: 7, name: 'Facilitation', inheritsFromId: 5, lockedName: true },
    { id: 5, name: 'Gouvernance', inheritsFromId: 0, lockedName: true },
    { id: 3, name: '\u00c9quipe', inheritsFromId: 1, lockedName: false, definedInId: 20, definedInName: 'Equipe locale' },
    { id: 1, name: 'cercle', inheritsFromId: 0, lockedName: false, definedInId: 10, definedInName: 'Organisation' },
    { id: 9, name: 'Current', inheritsFromId: 1, lockedName: true }
];
const originalCatalog = JSON.stringify(catalog);
window.omoHolonTemplateState = { data: { templateCatalog: catalog, contextHolonId: 10 } };
window.omoHolonTemplateElements = { parent: select };
window.omoUiText = (key, fallback) => fallback;
window.eval(fs.readFileSync('common/assets/holon-template-options.js', 'utf8'));
window.eval(source.slice(start, end));
window.omoHolonTemplateBuildParentOptions(7, 9);

const groups = Array.from(select.querySelectorAll('optgroup'));
assert.deepEqual(groups.map(group => group.label), [
    'Modèles de base · nom libre', 'Modèles dérivés · nom libre',
    'Modèles de base · nom fixe', 'Modèles dérivés · nom fixe'
]);
assert.deepEqual(groups.map(group => Array.from(group.children, option => option.value)), [
    ['1', '2'], ['3', '4'], ['5', '6'], ['7', '8']
], 'Each group must be alphabetized, including accented names');
assert.equal(select.firstElementChild.value, '0', 'No inheritance remains the first option');
assert.equal(select.value, '7', 'The inherited template remains selected inside its group');
assert.equal(select.querySelector('option[value="3"]').textContent, '\u00c9quipe > Equipe locale');
assert.equal(select.querySelector('option[value="1"]').textContent, 'cercle');
assert.equal(select.querySelector('option[value="9"]'), null, 'The current template must be excluded');
assert.equal(JSON.stringify(catalog), originalCatalog, 'Rendering must preserve the source catalog');

window.omoHolonTemplateBuildParentOptions(9, 9);
assert.equal(select.value, '0', 'An unavailable selection falls back to no inheritance');
window.omoHolonTemplateState.data.templateCatalog = [catalog[0]];
window.omoHolonTemplateBuildParentOptions(8, 9);
assert.equal(select.querySelectorAll('optgroup').length, 1, 'Empty groups are omitted');
assert.equal(select.value, '8');
window.omoHolonTemplateState.data.templateCatalog = [];
window.omoHolonTemplateBuildParentOptions(8, 9);
assert.equal(select.options.length, 1);
assert.equal(select.value, '0');

const holonSource = fs.readFileSync('omo/api/holons/editor.js', 'utf8');
const holonStart = holonSource.indexOf('function renderTemplateOptions(');
const holonEnd = holonSource.indexOf('\nfunction ', holonStart + 1);
window.elements = { template: select };
window.state = { data: { contextHolonId: 10 } };
window.getTemplates = () => catalog;
window.eval(holonSource.slice(holonStart, holonEnd));
window.renderTemplateOptions(8);
assert.equal(select.querySelectorAll('optgroup').length, 4);
assert.deepEqual(Array.from(select.options, option => option.value), ['1', '2', '3', '4', '5', '6', '9', '7', '8']);
assert.equal(select.value, '8', 'The holon editor must retain its selected model');
assert.equal(select.required, true);
window.renderTemplateOptions();
assert.equal(select.value, '1', 'A new holon defaults to the first alphabetized base model');
window.renderTemplateOptions(99);
assert.equal(select.value, '1', 'An unavailable model falls back to the first visible option');
window.getTemplates = () => [];
window.renderTemplateOptions();
assert.equal(select.options.length, 0);
window.getTemplates = () => catalog;
window.omoUiText = key => 'Translated ' + key;
window.renderTemplateOptions(7);
assert.equal(select.querySelector('optgroup').label, 'Translated parameters.holon_templates.parent_group.base');
assert.equal(select.value, '7');
dom.window.close();
console.log('holon_template_parent_options_test: OK');
