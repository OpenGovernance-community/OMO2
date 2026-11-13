'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const source = fs.readFileSync('omo/api/parameters/holon-templates/templates.js', 'utf8');
const deletion = source.slice(source.indexOf('async function omoHolonTemplateDelete()'), source.indexOf('function omoHolonTemplateSave('));

async function runScenario({ count = 3, entered = '3', previewOk = true, deleteOk = true, navigate = false, duplicate = false } = {}) {
    const requests = [];
    const notifications = [];
    let prompts = 0;
    let ended = 0;
    let welcomed = 0;
    const state = { data: { contextHolonId: 10 }, formRevision: 1, deleting: false };
    const context = {
        FormData,
        omoHolonTemplateState: state,
        omoHolonTemplateElements: { form: { dataset: { templateId: '12' } } },
        omoHolonTemplateRoot: { isConnected: true },
        omoHolonTemplateTexts: { confirmDeleteModel: 'Delete {templateName}, {deletedCount} removed, {count} impacted. Type {count}.', deleteConfirmationRequired: 'Wrong count', deleteErrorModel: 'Error' },
        omoHolonTemplateFind: () => ({ id: 12, name: 'Old name' }),
        omoHolonTemplateIsHolonDefinitionMode: () => false,
        omoHolonTemplateGetScope: () => 'descendants',
        omoHolonTemplateClearStatus() {},
        omoHolonTemplateShowWelcome() { welcomed++; },
        omoHolonTemplateShowStatus(message, type) { notifications.push({ message, type }); },
        window: {
            omoBeginPendingAction: () => true,
            omoEndPendingAction() { ended++; },
            prompt(message, initial) {
                prompts++;
                assert.equal(initial, '', 'The input must start empty, including when count is zero');
                assert.equal(message, `Delete Current name, 2 removed, ${count} impacted. Type ${count}.`);
                return entered;
            }
        },
        async fetch(url, options) {
            requests.push({ url, body: options.body });
            assert(url.includes('cid=10') && url.includes('template_scope=descendants'));
            assert.equal(options.body.get('template_id'), '12');
            const preview = options.body.get('action') === 'preview';
            if (navigate && preview) state.formRevision++;
            return { ok: preview ? previewOk : deleteOk, json: async () => preview
                ? { status: previewOk ? 'ok' : 'error', impact: { count, deletedCount: 2, signature: 'current-scope' }, template: { name: 'Current name' } }
                : { status: deleteOk ? 'ok' : 'error', message: deleteOk ? 'Deleted' : 'Changed scope', data: state.data } };
        }
    };
    vm.createContext(context);
    vm.runInContext(deletion, context);
    const first = context.omoHolonTemplateDelete();
    if (duplicate) await context.omoHolonTemplateDelete();
    await first;
    assert.equal(state.deleting, false, 'Every exit must release the pending guard');
    assert.equal(ended, 1);
    return { requests, notifications, prompts, welcomed };
}

async function main() {
    for (const entered of [null, '', '2', '03', '3.0', ' 3']) {
        const result = await runScenario({ entered });
        assert.equal(result.requests.length, 1, 'Cancel or a wrong count must never submit deletion');
        assert.equal(result.welcomed, 0);
    }
    const success = await runScenario({ duplicate: true });
    assert.equal(success.requests.length, 2, 'One preview and one confirmed deletion');
    assert.equal(success.requests[1].body.get('confirmation_count'), '3');
    assert.equal(success.requests[1].body.get('impact_signature'), 'current-scope');
    assert.equal(success.prompts, 1);
    assert.equal(success.welcomed, 1);
    const zero = await runScenario({ count: 0, entered: '0' });
    assert.equal(zero.requests.length, 2);
    assert.equal((await runScenario({ count: 0, entered: '' })).requests.length, 1);
    for (const settings of [{ previewOk: false }, { count: null }, { navigate: true }]) {
        const result = await runScenario(settings);
        assert.equal(result.requests.length, 1);
        assert.equal(result.prompts, 0, 'An invalid or obsolete preview must not open confirmation');
    }
    const refused = await runScenario({ deleteOk: false });
    assert.equal(refused.welcomed, 0, 'Server refusal must preserve the editor');
    assert.equal(refused.notifications.at(-1).message, 'Changed scope');
    console.log('holon_template_delete_test: OK');
}
main().catch(error => { console.error(error); process.exitCode = 1; });
