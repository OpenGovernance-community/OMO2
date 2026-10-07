'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const source = fs.readFileSync(path.join(__dirname, '../omo/assets/js/organization-directory.js'), 'utf8');

async function runAction(response, action = 'leave') {
    let click;
    const notices = [];
    const requests = [];
    let reloads = 0;
    const card = { getAttribute: key => ({ 'data-organization-id': '828', 'data-organization-name': 'La Passerelle' }[key]) };
    const button = {
        disabled: false,
        getAttribute: () => action,
        closest: selector => selector === '[data-organization-id]' ? card : null
    };
    const window = {
        commonNotify: (message, type) => notices.push({ message, type }),
        // The directory does not load app.js and has no omoNotify wrapper.
        confirm: () => true,
        addEventListener() {},
        history: {},
        location: { href: 'https://localtest.me/omo/', reload: () => reloads++ },
        omoDirectoryTranslations: {
            leaveConfirm: 'Quitter {organizationName} ?', deleteConfirm: 'Supprimer {organizationName} ?',
            defaultOrganizationName: 'Organisation', actionError: 'Action impossible.'
        }
    };
    const document = {
        getElementById: () => null,
        querySelectorAll: () => [],
        addEventListener: (name, handler) => { if (name === 'click') click = handler; }
    };
    const context = {
        window, document, FormData,
        fetch: async (url, options) => {
            requests.push({ url, action: options.body.get('action'), oid: options.body.get('oid') });
            if (response instanceof Error) throw response;
            return { ok: response.ok, text: async () => response.body };
        }
    };
    vm.runInNewContext(source, context);
    window.commonPageScripts['/omo/assets/js/organization-directory.js']({});
    click({
        preventDefault() {}, stopPropagation() {},
        target: { closest: selector => selector === '[data-omo-org-action]' ? button : null }
    });
    assert.equal(button.disabled, true, 'Action must be disabled while pending');
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(requests[0].oid, '828');
    assert.equal(requests[0].action, action);
    return { notices, button, window, reloads };
}

async function main() {
    const lastAdmin = "Le dernier admin ne peut pas quitter l'organisation. Nommez un autre admin ou supprimez l'organisation.";
    for (const action of ['leave', 'delete', 'toggle-model']) {
        const result = await runAction({ ok: false, body: JSON.stringify({ status: false, message: lastAdmin }) }, action);
        assert.deepEqual(result.notices, [{ message: lastAdmin, type: 'error' }], 'HTTP errors must display the server message');
        assert.equal(result.button.disabled, false, 'Rejected actions must be usable again');
        assert.equal(result.window.location.href, 'https://localtest.me/omo/');
        assert.equal(result.reloads, 0, 'Rejected actions must keep the directory visible');
    }
    const denied = await runAction({ ok: true, body: JSON.stringify({ status: false, message: lastAdmin }) });
    assert.equal(denied.notices[0].message, lastAdmin, 'Application errors must also be visible');
    const invalid = await runAction({ ok: false, body: '<html>Server error</html>' });
    assert.deepEqual(invalid.notices, [{ message: 'Action impossible.', type: 'error' }]);
    const offline = await runAction(new Error('Network unavailable'));
    assert.deepEqual(offline.notices, [{ message: 'Network unavailable', type: 'error' }]);
    const success = await runAction({ ok: true, body: JSON.stringify({ status: true, redirect: '/omo/?left=1' }) });
    assert.equal(success.window.location.href, '/omo/?left=1', 'Successful departure must still redirect');
    assert.equal(success.notices.length, 0);
    console.log('organization_directory_actions_test: OK');
}
main().catch(error => { console.error(error); process.exitCode = 1; });
