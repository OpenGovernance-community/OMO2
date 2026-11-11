'use strict';
// Generate the isolated form first: php tests/project_responsible_test.php --fixture
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {JSDOM} = require('jsdom');
const read = file => fs.readFileSync(path.join(__dirname, '..', file), 'utf8');
const fields = read('tmp/project-responsible-fields.html');
const tick = () => new Promise(resolve => setTimeout(resolve, 0));
const groups = {role: {12: 'Alice', 11: 'Zoe'}, circle: {11: 'Zoe', 13: 'Bob'}, organization: {13: 'Bob', 14: 'Claire'}};

async function pvForm() {
    const dom = new JSDOM(`<form data-deferred-project-context-url="/context?holon=">
        <input name="holon_id" value="100">${fields}</form>`, {runScripts: 'outside-only'});
    const {window} = dom;
    try {
        const form = window.document.querySelector('form');
        const select = form.elements.namedItem('IDuser');
        const status = form.elements.namedItem('status');
        const row = form.querySelector('[data-deferred-project-status-row]');
        assert.equal(row.children[0], select.closest('label'), 'Assignee must be just before status in the same row.');
        assert.equal(row.children[1], status.closest('label'));
        assert.equal(select.value, '12');
        const requests = [];
        const notices = [];
        window.commonNotify = (...args) => notices.push(args);
        window.fetch = url => new Promise(resolve => requests.push({url, resolve}));
        window.eval(read('common/choice/deferred-project-fields.js'));
        window.omoInitDeferredProjectFields(window.document);
        window.omoInitDeferredProjectFields(window.document);
        const blocked = form.querySelector('[data-deferred-project-blocked-row]');
        assert.equal(blocked.hidden, true);
        status.value = 'blocked';
        status.dispatchEvent(new window.Event('change'));
        assert.equal(blocked.hidden, false);
        assert.ok(Array.from(blocked.children).every(field => !field.hidden));
        status.value = 'ready';
        status.dispatchEvent(new window.Event('change'));
        assert.equal(blocked.hidden, true);
        const holon = form.elements.namedItem('holon_id');
        for (const id of [200, 300]) {
            holon.value = String(id);
            holon.dispatchEvent(new window.Event('change'));
        }
        assert.equal(requests.length, 2, 'Repeated initialization must not duplicate listeners.');
        requests[1].resolve({ok: true, json: async () => ({status: true, projectResponsibleGroups: groups})});
        await tick();
        assert.equal(select.value, '12', 'Changing space must retain the chosen person.');
        assert.deepEqual(Array.from(select.options, option => option.value), ['', '12', '11', '13', '14'], 'Groups must preserve priority, alphabetical order and unique people.');
        requests[0].resolve({ok: true, json: async () => ({status: true, projectResponsibleGroups: {organization: {99: 'Stale'}}})});
        await tick();
        assert.equal(select.options.length, 5, 'A stale context response must be ignored.');
        select.value = '14';
        assert.equal(new window.FormData(form).get('IDuser'), '14', 'The chosen person must be submitted.');
        select.value = '';
        window.omoSetDeferredProjectResponsibleOptions(form, groups);
        assert.equal(select.value, '', 'An empty assignment must stay empty.');
        window.omoSetDeferredProjectResponsibleOptions(form, groups, 99);
        assert.equal(select.value, '99', 'Historical assignments must not silently disappear.');
        holon.dispatchEvent(new window.Event('change'));
        requests[2].resolve({ok: false, json: async () => ({status: false})});
        await tick();
        assert.equal(notices.length, 1);
        assert.equal(notices[0][1], 'error');
        assert.equal(select.value, '99', 'Refresh failure must leave the selection intact.');
    } finally { window.close(); }
}

async function decisionForm() {
    const action = {id: 1, type: 'project.propose', holonId: 100, before: {}, after: {title: 'Draft', IDuser: 12, IDuser_proposed: 14}, status: 'pending'};
    const payload = {organizationId: 7, contextHolonId: 200, currentUserId: 11, texts: {}, blueprint: [{title: 'Proposal', actions: [action]}]};
    const dom = new JSDOM(`<div data-governance-editor><script type="application/json" data-governance-data>${JSON.stringify(payload)}</script>
        <form data-governance-form><input data-governance-blueprint><div data-governance-proposals></div><p data-governance-feedback></p></form>
        <template data-governance-fields="project"><form data-editor>${fields}<button type="submit">Save</button><button type="button" data-cancel>Cancel</button></form></template>
        </div><div id="commonTopbarModalBody"></div>`, {runScripts: 'outside-only'});
    const {window} = dom;
    try {
        window.TextEncoder = TextEncoder;
        window.omoProposalHtml = {mount: async () => {}, setValue() {}, getValue: () => ''};
        window.commonTopbarOpenModal = (title, html) => { window.document.getElementById('commonTopbarModalBody').innerHTML = html; };
        window.commonTopbarCloseModal = () => { window.document.getElementById('commonTopbarModalBody').replaceChildren(); };
        window.fetch = async url => {
            assert.ok(url.includes('target_type=project') && url.includes('context_holon_id=100'));
            return {ok: true, json: async () => ({status: true, projectResponsibleGroups: groups})};
        };
        window.eval(read('common/choice/deferred-project-fields.js'));
        window.eval(read('common/choice/governance-actions.js'));
        window.omoGovernanceEditorInit(window.document);
        async function open() {
            window.document.querySelector('[data-edit]').click();
            await tick();
            return window.document.querySelector('[data-editor]');
        }
        let editor = await open();
        assert.equal(editor.elements.namedItem('IDuser').value, '12');
        editor.elements.namedItem('IDuser').value = '13';
        editor.dispatchEvent(new window.Event('submit', {cancelable: true}));
        const saved = JSON.parse(window.document.querySelector('[data-governance-blueprint]').value)[0].actions[0];
        assert.equal(saved.after.IDuser, '13');
        assert.equal(saved.after.IDuser_proposed, '14', 'Changing the assignee must not change the requester.');
        editor = await open();
        assert.equal(editor.elements.namedItem('IDuser').value, '13', 'Reopening a decision proposal must retain its assignee.');
    } finally { window.close(); }
}

(async () => {
    await pvForm();
    await decisionForm();
    console.log('project_responsible_fields_test: OK');
})().catch(error => { console.error(error); process.exitCode = 1; });
