'use strict';
// Run with jsdom on NODE_PATH. No server or database writes.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {JSDOM} = require('jsdom');

const dom = new JSDOM(`<form><div data-rule-scope-fields><input type="hidden" name="scope" value="local">
    ${['local', 'circle', 'descendants', 'global'].map(value => `<button type="button" data-rule-scope-choice="${value}">${value}</button>`).join('')}
    <label data-rule-authority-field><select name="IDauthority"><option value="">None</option></select>
    <small data-rule-authority-required hidden>Required</small><small data-rule-authority-unavailable hidden>Unavailable</small></label>
</div></form>`, {runScripts: 'outside-only'});
const {window} = dom;
const form = window.document.querySelector('form');
window.eval(fs.readFileSync(path.join(__dirname, '../common/choice/rule-scope-fields.js'), 'utf8'));
const authority = form.elements.namedItem('IDauthority');
const scope = form.elements.namedItem('scope');
const select = value => form.querySelector(`[data-rule-scope-choice="${value}"]`).click();
const init = (context, state) => window.omoInitRuleScopeFields(form, context, state);
init({usesAuthorities: false, isRole: true, authorities: []});
for (const value of ['local', 'circle', 'descendants', 'global']) {
    select(value);
    assert.equal(scope.value, value);
    assert(form.checkValidity(), 'All scopes are available without authority management');
    assert.equal(form.querySelectorAll('[aria-pressed="true"]').length, 1);
}
assert(form.querySelector('[data-rule-authority-field]').hidden);
const context = {usesAuthorities: true, isRole: false, authorities: [{id: 10, label: '<Domain & authority>'}]};
init(context, {scope: 'local'});
for (const value of ['local', 'circle']) {
    select(value);
    assert(form.checkValidity(), 'A circle can define local and circle rules without a domain');
}
for (const value of ['descendants', 'global']) {
    select(value);
    assert(!form.checkValidity(), 'Wider scopes require a domain');
    assert(authority.required);
}
authority.value = '10';
assert(form.checkValidity());
assert.equal(new window.FormData(form).get('scope'), 'global');
assert.equal(new window.FormData(form).get('IDauthority'), '10');
assert.equal(authority.options[1].textContent, '<Domain & authority>');
assert.equal(form.querySelector('domain'), null);
init({...context, isRole: true}, {scope: 'circle', IDauthority: null});
assert(!form.checkValidity(), 'Role circle scope requires an authority');
init({...context, isRole: true}, {scope: 'circle', IDauthority: 10});
assert(form.checkValidity(), 'Reopened proposal retains scope and authority');
assert.equal(form.querySelector('[data-rule-scope-choice="circle"]').getAttribute('aria-pressed'), 'true');
init({usesAuthorities: true, isRole: true, authorities: []});
assert(!form.checkValidity(), 'Changing context removes an unavailable authority');
assert(!form.querySelector('[data-rule-authority-unavailable]').hidden);
select('local');
assert(form.checkValidity());
assert(form.querySelector('[data-rule-authority-required]').hidden);
dom.window.close();
console.log('rule_scope_fields_test: OK');
