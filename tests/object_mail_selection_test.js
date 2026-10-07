'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {JSDOM} = require('jsdom');
const dom = new JSDOM(`<body>
<summary data-object-mail-count data-one="1 recipient" data-other="{count} recipients"></summary>
<button data-object-mail-select="all">All</button><button data-object-mail-select="none">None</button>
<input type="checkbox" form="mail" name="recipient_ids[]" value="user:1" data-object-mail-recipient checked>
<input type="checkbox" form="mail" name="recipient_ids[]" value="user:2" data-object-mail-recipient checked>
<form id="mail" data-object-mail-form data-object-mail-max="500" data-object-mail-sending="Sending">
<p data-object-mail-selection-error data-empty="Choose a recipient" data-too-many="Too many" hidden></p>
<span data-object-mail-progress hidden></span><button type="submit">Send</button></form></body>`, {runScripts: 'outside-only'});
const {window} = dom; const {document} = window;
window.eval(fs.readFileSync(path.join(__dirname, '../common/object_mail/ui.js'), 'utf8'));
const form = document.querySelector('form'); const send = form.querySelector('button');
const count = document.querySelector('summary'); const choices = document.querySelectorAll('input');
assert.equal(count.textContent, '2 recipients');
document.querySelector('[data-object-mail-select="none"]').click();
assert.equal(count.textContent, '0 recipients'); assert.equal(send.disabled, true);
assert.equal(document.querySelector('[data-object-mail-selection-error]').hidden, false);
assert.equal(form.dispatchEvent(new window.Event('submit', {bubbles: true, cancelable: true})), false);
assert.equal(form.hasAttribute('aria-busy'), false, 'An empty audience does not start sending');
choices[0].checked = true; choices[0].dispatchEvent(new window.Event('change', {bubbles: true}));
assert.equal(count.textContent, '1 recipient'); assert.equal(send.disabled, false);
assert.deepEqual(new window.FormData(form).getAll('recipient_ids[]'), ['user:1'], 'External form-associated checkboxes are submitted');
document.querySelector('[data-object-mail-select="all"]').click();
assert.equal(count.textContent, '2 recipients');
form.setAttribute('data-object-mail-max', '1'); choices[0].dispatchEvent(new window.Event('change', {bubbles: true}));
assert.equal(send.disabled, true, 'The selection limit is checked');
form.setAttribute('data-object-mail-max', '500'); choices[0].dispatchEvent(new window.Event('change', {bubbles: true}));
assert.equal(form.dispatchEvent(new window.Event('submit', {bubbles: true, cancelable: true})), true);
assert.equal(send.disabled, true); assert.equal(form.getAttribute('aria-busy'), 'true');
assert.equal(form.dispatchEvent(new window.Event('submit', {bubbles: true, cancelable: true})), false, 'A pending send cannot be submitted twice');
console.log('object_mail_selection_test: OK');
