'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const source = fs.readFileSync(path.join(__dirname, '../omo/api/holons/editor.js'), 'utf8');

// Exercise the real initial merge before mounting the form, without external DOM dependencies.
for (const typeId of [1, 2, 3]) {
    for (const mode of ['create', 'edit']) {
        const existing = {name: 'Existing name', fullName: 'Existing full name', templateId: 5,
            properties: [{id: 8, value: 'Existing value', canEditValue: true, canEditDefinition: false, canDelete: false}]};
        const proposed = {name: 'Proposed name', fullName: 'Proposed full name', templateId: 5,
            color: '', icon: '', adminMin: 0, adminMax: null, permissions: {},
            properties: [{id: 8, type: 'type1', value: ''}]};
        const config = {governanceCapture: true, data: {mode, holon: mode === 'edit' ? existing : null,
            types: [{id: typeId}], templateCatalog: [{id: 5, properties: existing.properties}], propertyTypes: []}};
        const window = {omoHolonGovernanceInitialPayload: proposed};
        vm.runInNewContext(source, {window, document: {getElementById: () => null}});
        window.commonPageScripts['/omo/api/holons/editor.js'](config);
        assert.equal(config.data.holon.name, proposed.name);
        assert.equal(config.data.holon.fullName, proposed.fullName);
        assert.equal(config.data.holon.properties[0].value, '', 'Intentionally cleared values must remain empty.');
        assert.equal(config.data.holon.properties[0].canEditValue, true, 'Existing field permissions must be retained.');
    }
}
console.log('holon_proposal_initial_snapshot_test: OK');
