const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

(async function () {
    const handlers = {};
    const toggle = { checked: false, addEventListener: (name, callback) => { handlers[name] = callback; } };
    const form = {
        elements: { email: { value: 'backup@example.invalid' }, frequency: { value: '8m' } },
        querySelector: () => toggle,
        addEventListener: (name, callback) => { handlers[name] = callback; }
    };
    const button = { disabled: false };
    const backupButton = { disabled: false };
    const feedback = {};
    const last = {};
    const next = {};
    const root = {
        dataset: { organizationId: '42', csrf: 'fixture-token' },
        querySelector: (selector) => ({ form, '[data-omo-security-save]': button, '[data-omo-security-backup-now]': backupButton,
            '[data-omo-security-feedback]': feedback, '[data-omo-security-last]': last, '[data-omo-security-next]': next })[selector]
    };
    const requests = [];
    const notifications = [];
    let deliver;
    const context = {
        window: { commonNotify: (message, type, options) => notifications.push({ message, type, duration: options.duration }) },
        document: { querySelector: () => root },
        FormData: class extends Map { constructor() { super(); } },
        fetch: (url, options) => {
            requests.push({ url, options });
            return new Promise((resolve) => { deliver = resolve; });
        }
    };
    vm.runInNewContext(fs.readFileSync('omo/api/parameters/security/index.js', 'utf8'), context);
    const initialize = context.window.commonPageScripts['/omo/api/parameters/security/index.js'];
    initialize({ error: 'Save failed', sending: 'Sending backup' });
    initialize({ error: 'Save failed', sending: 'Sending backup' });
    assert.equal(form.elements.email.disabled, false, 'A manual backup can use an extra email while automatic backups are disabled');
    toggle.checked = true;
    handlers.change();
    assert.equal(form.elements.email.disabled, false);
    toggle.checked = false;
    handlers.change();
    handlers.submit({ preventDefault() {} });
    handlers.submit({ preventDefault() {} });
    assert.equal(requests.length, 1, 'Repeated clicks must not submit while saving');
    const data = requests[0].options.body;
    assert.equal(data.get('enabled'), '0');
    assert.equal(data.get('email'), 'backup@example.invalid', 'Disabling backups preserves the configured email');
    assert.equal(data.get('frequency'), '8m', 'Disabling backups preserves the chosen frequency');
    assert.equal(data.get('organization_id'), '42', 'Saving stays bound to the organization that opened the form');
    assert.equal(data.get('csrf'), 'fixture-token');
    deliver({ ok: false, json: () => Promise.resolve({ status: 'error', message: 'Permission denied' }) });
    await new Promise(setImmediate);
    assert.equal(button.disabled, false, 'Failed requests must release the save button');
    assert.deepEqual(notifications[0], { message: 'Permission denied', type: 'error', duration: 7000 });
    assert.equal(feedback.textContent, '', 'Errors must appear in the topbar notification rather than remain inline');
    handlers.submit({ preventDefault() {}, submitter: backupButton });
    handlers.submit({ preventDefault() {}, submitter: backupButton });
    assert.equal(requests.length, 2, 'The immediate backup button must also prevent duplicate submissions');
    assert.equal(requests[1].options.body.get('backup_now'), '1');
    assert.equal(requests[1].options.body.get('enabled'), '0', 'Sending now must not enable scheduled backups');
    assert.equal(button.disabled, true);
    assert.equal(backupButton.disabled, true);
    assert.equal(feedback.textContent, 'Sending backup', 'Sending progress must remain visible until the request finishes');
    deliver({ ok: true, json: () => Promise.resolve({ status: 'ok', message: 'Backup sent', lastSentLabel: 'Sent now', nextDueLabel: '' }) });
    await new Promise(setImmediate);
    assert.deepEqual(notifications[1], { message: 'Backup sent', type: 'success', duration: 5000 });
    assert.equal(feedback.textContent, '', 'The final notification must clear the sending progress');
    assert.equal(last.textContent, 'Sent now');
    assert.equal(next.hidden, true);
    assert.equal(backupButton.disabled, false);
    handlers.submit({ preventDefault() {}, submitter: button });
    deliver({ ok: true, json: () => Promise.resolve({ status: 'ok', message: 'Settings saved', nextDueLabel: 'Next week' }) });
    await new Promise(setImmediate);
    assert.deepEqual(notifications[2], { message: 'Settings saved', type: 'success', duration: 5000 });
    assert.equal(next.textContent, 'Next week');
    assert.equal(next.hidden, false);
    handlers.submit({ preventDefault() {}, submitter: button });
    deliver({ ok: false, json: () => Promise.reject(new Error('Network error')) });
    await new Promise(setImmediate);
    assert.deepEqual(notifications[3], { message: 'Network error', type: 'error', duration: 7000 });
    assert.equal(button.disabled, false);
    delete context.window.commonNotify;
    handlers.submit({ preventDefault() {}, submitter: button });
    deliver({ ok: false, json: () => Promise.resolve({ status: 'error', message: 'Permission denied' }) });
    await new Promise(setImmediate);
    assert.equal(feedback.textContent, 'Permission denied', 'A missing topbar API must retain readable error feedback');
    console.log('organization_backup_settings_test: OK');
})().catch((error) => { console.error(error); process.exitCode = 1; });
