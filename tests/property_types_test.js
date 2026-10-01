'use strict';
const fs = require('fs');
const vm = require('vm');
const assert = require('assert');
const context = {window: {}};
vm.createContext(context);
vm.runInContext(fs.readFileSync('common/assets/property-types.js', 'utf8'), context);
const types = context.window.omoPropertyTypes;
const options = [
    {id: 'type1', name: 'Mandat', canCreate: false},
    {id: 'type2', name: 'Strategie', canCreate: false},
    {id: 'type3', name: 'Competences <script>', canCreate: true}
];
assert.strictEqual(types.firstCreatable([]), '');
assert.strictEqual(types.firstCreatable(options), 'type3');
const fresh = types.render({id: 0, type: 'type3'}, options, 'Type', true);
assert(!fresh.includes('value="type1"') && !fresh.includes('value="type2"'));
assert(fresh.includes('value="type3" selected'));
assert(fresh.includes('&lt;script&gt;') && !fresh.includes('<script>'));
const existing = types.render({id: 1, type: 'type1'}, options, 'Type', true);
assert(existing.includes('value="type1" selected') && existing.includes('value="type3"'));
assert(types.render({id: 1, type: 'type1'}, options, 'Type', false).includes(' disabled'));
assert.strictEqual(types.read({dataset: {propertyType: 'type2'}, querySelector: () => null}), 'type2');
assert.strictEqual(types.read({dataset: {}, querySelector: () => ({value: 'type3'})}), 'type3');
console.log('property_types_test: OK');

// Exercise the template editor's actual permission normalization, including stale flags.
const editor = fs.readFileSync('omo/api/parameters/holon-templates/templates.js', 'utf8');
const start = editor.indexOf('function omoHolonTemplateNormalizeProperty(property)');
const end = editor.indexOf('\nfunction ', start + 1);
context.omoHolonTemplateState = {propertyTypes: options, data: {listItemTypes: [{id: 'text'}]}};
vm.runInContext(editor.slice(start, end), context);
let normalized = context.omoHolonTemplateNormalizeProperty({id: 1, type: 'type1', canEditValue: true, canDelete: true});
assert.strictEqual(normalized.canEditValue, false);
assert.strictEqual(normalized.canDelete, false);
normalized = context.omoHolonTemplateNormalizeProperty({id: 0, type: 'type3'});
assert.strictEqual(normalized.canEditValue, true);
assert.strictEqual(normalized.canDelete, true);
normalized = context.omoHolonTemplateNormalizeProperty({id: 1, type: 'type3', inheritedLocked: true});
assert.strictEqual(normalized.canEditValue, false);
console.log('property_types_template_normalization: OK');

// Render the actual template row with each independent permission profile.
const {JSDOM} = require('jsdom');
context.document = new JSDOM('').window.document;
context.omoHolonTemplateTexts = {};
context.omoHolonTemplateIsHolonDefinitionMode = () => false;
context.omoHolonTemplateRenderInheritedValueHtml = () => '';
context.omoHolonTemplateState.data.formats = [{id: 1, name: 'Text'}, {id: 3, name: 'Number'}];
context.window.omoPropertyListConversion = {sourceProperty: value => value, mount: () => {}};
for (const name of ['omoHolonTemplateEscapeHtml', 'omoHolonTemplateGetValueHelpText', 'omoHolonTemplateRenderValueInputHtml', 'omoHolonTemplateRenderListConfigHtml', 'omoHolonTemplateRenderPropertyMetaHtml', 'omoHolonTemplateCreatePropertyRow', 'omoHolonTemplateSerializePropertyValue', 'omoHolonTemplateReadPropertyState']) {
    const from = editor.lastIndexOf('function ' + name + '(');
    assert(from >= 0, name);
    const to = editor.indexOf('\nfunction ', from + 1);
    vm.runInContext(editor.slice(from, to < 0 ? undefined : to), context);
}
const profile = {id: 'type1', name: 'Type 1', canCreate: false, canEdit: false, canDelete: false};
context.omoHolonTemplateState.propertyTypes = [profile];
for (const [create, edit] of [[false, false], [true, false], [false, true], [true, true]]) {
    Object.assign(profile, {canCreate: create, canEdit: edit});
    const row = context.omoHolonTemplateCreatePropertyRow({id: 1, type: 'type1', name: 'Purpose', value: 'Existing value'});
    for (const selector of ['.omo-template-property__name', '.omo-template-property__format', '[data-property-type]', '.omo-template-property__mandatory', '.omo-template-property__locked', '[data-property-move]']) {
        assert.strictEqual(row.querySelector(selector).disabled, !create, selector);
    }
    const field = row.querySelector('.omo-template-property__value');
    assert.strictEqual(field.disabled, !edit, 'Only EDIT enables the value');
    field.value = 'New value';
    assert.strictEqual(context.omoHolonTemplateReadPropertyState(row).value, edit ? 'New value' : 'Existing value');
    if (create && !edit) {
        row.querySelector('.omo-template-property__format').value = '3';
        const replacement = context.omoHolonTemplateCreatePropertyRow(context.omoHolonTemplateReadPropertyState(row));
        assert.strictEqual(context.omoHolonTemplateReadPropertyState(replacement).value, 'Existing value', 'Changing format must preserve read-only content even when a number input cannot display it');
    }
}
console.log('property_types_template_dom: OK');

// Reopening a collective draft must refresh capabilities from server data.
const holonEditor = fs.readFileSync('omo/api/holons/editor.js', 'utf8');
const initialization = holonEditor.slice(holonEditor.indexOf('const state ='), holonEditor.indexOf("const root = document.getElementById"));
const hydration = {
    pageConfig: {
        governanceCapture: true,
        data: {
            propertyTypes: [{id: 'type1', canCreate: true}],
            holon: {properties: [{id: 1, canEditDefinition: false, canEditValue: true, canDelete: false}]}
        }
    },
    window: {omoHolonGovernanceInitialPayload: {properties: [
        {id: 1, value: 'Draft value', canEditDefinition: true, canEditValue: false, canDelete: true},
        {id: 0, type: 'type1', value: 'Initial value'}
    ]}}
};
vm.createContext(hydration);
vm.runInContext(initialization + '\nthis.result = state.data.holon.properties;', hydration);
assert.strictEqual(hydration.result[0].value, 'Draft value');
assert.strictEqual(hydration.result[0].canEditDefinition, false);
assert.strictEqual(hydration.result[0].canEditValue, true);
assert.strictEqual(hydration.result[0].canDelete, false);
assert.strictEqual(hydration.result[1].canEditDefinition, true);
assert.strictEqual(hydration.result[1].canEditValue, true);
console.log('property_types_collective_draft: OK');
