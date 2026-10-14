const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

async function run() {
    const handlers = {};
    const button = {disabled: false};
    const form = {getAttribute: name => name === 'action' ? '/archive' : null, querySelector: () => button, addEventListener: (name, fn) => { handlers[name] = fn; }};
    const panel = {querySelector: () => form, addEventListener: (name, fn) => { handlers['panel-' + name] = fn; }};
    const notices = [], navigations = [], reloads = [], modals = [];
    let calls = 0, finish;
    const window = {
        commonPageScripts: {}, omoNotify: (...args) => notices.push(args),
        commonTopbarCloseModal: () => modals.push('closed'),
        commonTopbarOpenModal: (...args) => modals.push(args),
        omoReloadStructureAndFocus: (...args) => reloads.push(args),
    };
    const context = {window, document: {querySelector: () => panel}, FormData: class {},
        fetch: () => { calls++; return new Promise(resolve => { finish = resolve; }); },
        navigate: (...args) => navigations.push(args)};
    vm.runInNewContext(fs.readFileSync('omo/api/holons/archive.js', 'utf8'), context);
    window.commonPageScripts['/omo/api/holons/archive.js']({organizationId: 12, rootId: 42, title: 'Archives', error: 'Failed'});
    const event = {preventDefault() {}};
    const first = handlers.submit(event);
    await handlers.submit(event);
    assert.equal(calls, 1, 'Double submissions must not start a second mutation');
    finish({ok: true, json: async () => ({status: true, message: 'Archived', targetId: 42})});
    await first;
    assert.deepEqual(notices, [['Archived', 'success']]);
    assert.deepEqual(navigations, [[12, null, null]], 'Root parent must use the organization route');
    assert.equal(reloads[0][0], null, 'Structure visualization must also return to the root');
    assert.deepEqual(modals, ['closed']);

    button.disabled = false;
    const failed = handlers.submit(event);
    finish({ok: false, json: async () => ({status: false, message: 'Permission revoked'})});
    await failed;
    assert.equal(button.disabled, false, 'Failure must allow retry');
    assert.deepEqual(notices.at(-1), ['Permission revoked', 'error']);
    assert.equal(navigations.length, 1, 'Failure must not navigate or discard the archive preview');
    handlers['panel-click']({target: {closest: () => ({dataset: {archiveOpen: '/archive?hid=7'}})}});
    assert.deepEqual(modals.at(-1), ['Archives', '/archive?hid=7', 'fetch']);
    console.log('holon_archive_test: OK');
}
run().catch(error => { console.error(error); process.exitCode = 1; });
