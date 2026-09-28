const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const source = fs.readFileSync(path.join(__dirname, '../omo/api/lms/catalog.js'), 'utf8');

async function run({ create = false, edit = false, remove = false, manageable = true, confirm = true }) {
    const requests = [];
    const confirmations = [];
    let reloaded = false;
    const context = vm.createContext({
        FormData,
        document: {
            querySelectorAll: () => [],
            getElementById: () => null,
            addEventListener() {},
            querySelector: () => ({ getAttribute: key => key === 'data-can-manage' ? (manageable ? '1' : '0') : 'Test' })
        },
        window: {
            omoLmsCatalogConfig: {
                canCreateParcours: create, canEditParcours: edit, canDeleteParcours: remove,
                lmsIndexText: {}, lmsParcoursDeletePreviewPath: '/preview', lmsParcoursDeletePath: '/delete'
            },
            confirm: text => { confirmations.push(text); return confirm; },
            alert() {},
            location: { reload: () => { reloaded = true; } }
        },
        fetch: async (url, options) => {
            requests.push([url, options.body.get('id')]);
            return { ok: true, json: async () => ({ status: true, action: 'archive', confirmMessage: 'Utilise.\n\nConserver le contenu ?' }) };
        }
    });
    vm.runInContext(source, context);
    await context.deleteParcoursFromCard({ preventDefault() {}, stopPropagation() {} }, 42);
    return { requests, confirmations, reloaded };
}

(async () => {
    const deleteOnly = await run({ remove: true });
    assert.deepEqual(deleteOnly.requests, [['/preview', '42'], ['/delete', '42']], 'Delete alone must work without create/edit');
    assert.equal(deleteOnly.reloaded, true);
    assert.match(deleteOnly.confirmations[0], /Utilise\.\n\nConserver/);
    assert.deepEqual((await run({ create: true, manageable: false })).requests, [], 'Create cannot delete an owned card');
    assert.deepEqual((await run({ edit: true })).requests, [], 'Edit cannot delete');
    assert.deepEqual((await run({ remove: true, manageable: false })).requests, [], 'Archived cards cannot be deleted');
    assert.equal((await run({ create: true })).requests.length, 2, 'Create still allows detaching an imported card');
    const cancelled = await run({ remove: true, confirm: false });
    assert.deepEqual(cancelled.requests, [['/preview', '42']], 'Cancel must leave the parcours untouched');
    assert.equal(cancelled.reloaded, false);
    console.log('parcours_delete_menu_test: OK');
})().catch(error => { console.error(error); process.exitCode = 1; });
