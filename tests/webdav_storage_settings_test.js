'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

function element(values = {}) {
    return Object.assign({hidden: false, disabled: false, checked: false, textContent: '', dataset: {},
        listeners: {}, classList: {add() {}, remove() {}, toggle() {}},
        addEventListener(event, callback) { this.listeners[event] = callback; }}, values);
}

(async () => {
    const names = ['form', 'submit', 'feedback', 'storage-toggle', 'storage-choice', 'nextcloud-fields', 'kdrive-fields',
        'webdav-fields', 'nextcloud-test', 'nextcloud-test-feedback', 'storage-test', 'storage-test-feedback',
        'webdav-test', 'webdav-test-feedback', 'collabora-toggle', 'collabora-fields'];
    const controls = Object.fromEntries(names.map(name => [name, element()]));
    controls['storage-toggle'].checked = true;
    const providers = ['nextcloud', 'kdrive', 'webdav'].map(value => element({value, checked: value === 'webdav'}));
    const selectors = {
        '[data-omo-documents-params-form]': controls.form,
        '[data-omo-documents-params-submit]': controls.submit,
        '[data-omo-documents-params-feedback]': controls.feedback,
    };
    for (const name of names) selectors['[data-omo-documents-' + name + ']'] = controls[name];
    const root = element({
        querySelector(selector) {
            return selector.endsWith(':checked') ? providers.find(provider => provider.checked) : selectors[selector] || null;
        },
        querySelectorAll() { return providers; }
    });
    let response = {status: true, message: 'Connected'};
    const notifications = [], requests = [];
    const window = {commonPageScripts: {}, commonNotify: (...args) => notifications.push(args),
        fetch: async (url, options) => {
            requests.push({url, options});
            return {json: async () => response};
        }};
    const context = vm.createContext({window, document: {querySelectorAll: () => [root]}, FormData: class {}});
    vm.runInContext(fs.readFileSync(path.join(__dirname, '../omo/api/documents/params/index.js'), 'utf8'), context);
    window.commonPageScripts['/omo/api/documents/params/index.js']({storageTestLabel: 'Test', storageTestingLabel: 'Testing', message: 'Failed'});
    assert.equal(controls['webdav-fields'].hidden, false, 'Saved WebDAV settings are visible on initialization');
    assert.equal(controls['nextcloud-fields'].hidden, true);
    assert.equal(controls['kdrive-fields'].hidden, true);
    providers.forEach(provider => { provider.checked = provider.value === 'kdrive'; });
    providers[1].listeners.change();
    assert.equal(controls['kdrive-fields'].hidden, false);
    assert.equal(controls['webdav-fields'].hidden, true);
    providers.forEach(provider => { provider.checked = provider.value === 'webdav'; });
    providers[2].listeners.change();
    controls['storage-toggle'].checked = false;
    controls['storage-toggle'].listeners.change();
    assert.equal(controls['webdav-fields'].hidden, true);
    assert.equal(controls['collabora-toggle'].disabled, true);
    controls['storage-toggle'].checked = true;
    controls['storage-toggle'].listeners.change();
    assert.equal(controls['webdav-fields'].hidden, false);

    controls['webdav-test'].listeners.click();
    assert.equal(controls['webdav-test'].disabled, true, 'Prevent repeated connection tests while pending');
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(requests[0].url, '/omo/api/documents/params/test_storage.php');
    assert.equal(requests[0].options.method, 'POST');
    assert.deepEqual(notifications[0], ['Connected', 'success']);
    assert.equal(controls['webdav-test-feedback'].textContent, '', 'Do not duplicate notifications inline');
    assert.equal(controls['webdav-test'].disabled, false);
    response = {status: false, message: 'Rejected'};
    controls['webdav-test'].listeners.click();
    await new Promise(resolve => setImmediate(resolve));
    assert.deepEqual(notifications[1], ['Rejected', 'error']);
    console.log('webdav_storage_settings_test: OK (provider selection, activation, connection test, notifications)');
})().catch(error => { console.error(error); process.exitCode = 1; });
