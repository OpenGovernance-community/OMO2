'use strict';
// Generate the isolated PHP fixture with pv_proposal_creation_space_test.php first.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {JSDOM} = require('jsdom');
const fixture = JSON.parse(fs.readFileSync(path.join(__dirname, '../tmp/pv-proposal-creation-space.json'), 'utf8'));
const read = file => fs.readFileSync(path.join(__dirname, '..', file), 'utf8');
const tick = () => new Promise(resolve => setTimeout(resolve, 0));

async function check(type, reopened) {
    const isProject = type.startsWith('project');
    const isProposal = type === 'project_propose';
    const html = fixture[reopened ? 'reopened' : 'html'][type];
    const dom = new JSDOM('<div id="commonTopbarModal"><div id="commonTopbarModalBody">' + html + '</div></div>',
        {url: 'https://omo.test/omo/', runScripts: 'outside-only'});
    const {window} = dom;
    const {document} = window;
    const requests = [];
    const stack = [];
    const notices = [];
    let mountedPicker;
    window.commonNotify = message => notices.push(message);
    window.commonTopbarCloseModal = () => {};
    window.commonTopbarPushModal = (title, content) => {
        const body = document.getElementById('commonTopbarModalBody');
        stack.push(Array.from(body.childNodes));
        body.replaceChildren();
        body.innerHTML = content;
    };
    window.commonTopbarPopModal = () => document.getElementById('commonTopbarModalBody').replaceChildren(...stack.pop());
    window.commonTopbarOpenModal = window.commonTopbarPushModal;
    window.fetch = async (url, options) => {
        requests.push({url, options});
        return {ok: true, json: async () => ({status: true, id: 1, pointId: 1})};
    };
    try {
        window.eval(read('common/holon_scope_picker.js'));
        window.omoMountHolonScopePicker = config => {
            mountedPicker = config;
            let selected = config.initialHolonId;
            const picker = {
                getSelectedHolonLabel: () => 'Chosen space ' + selected,
                setSelectedHolonId: id => { selected = id; config.onChange(id); },
                destroy: () => {}
            };
            window.setTimeout(() => config.onReady(selected), 0);
            mountedPicker.api = picker;
            return picker;
        };
        const form = document.querySelector('form');
        const field = form.elements.namedItem('holon_id');
        const label = form.querySelector('[data-holon-target-label]');
        const settings = JSON.parse(form.querySelector('[data-holon-target-selector]').getAttribute('data-holon-target-selector'));
        assert.equal(label.readOnly, true);
        const initial = field.value;
        if (isProject) {
            const dateRow = field.closest('.generic-form-grid');
            assert.ok(dateRow.classList.contains('generic-form-grid--trio'));
            assert.ok(dateRow.children[0].contains(field), 'Space must be the first field on the date row.');
            assert.equal(dateRow.querySelector('[name="planned_start_date"]').closest('.generic-form-field'), dateRow.children[1]);
            assert.equal(dateRow.querySelector('[name="planned_end_date"]').closest('.generic-form-field'), dateRow.children[2]);
            // Run the actual PHP-rendered controller, including its creation-mode labels.
            for (const script of document.querySelectorAll('script:not([src])')) {
                if (!script.type || script.type === 'text/javascript') window.eval(script.textContent);
            }
        } else {
            window.eval(read('common/choice/deferred-object-editor.js'));
            window.omoDeferredObjectEditorInit(document.querySelector('[data-deferred-object-editor]'), {
                origin: 'pv', targetType: type, holonId: Number(initial), pointId: 1
            });
        }
        async function openAndChoose(id) {
            form.querySelector('[data-holon-target-selector]').click();
            await tick();
            assert.deepEqual(Array.from(mountedPicker.selectableHolonIds), settings.selectableHolonIds,
                'The shared dialog must use the server-filtered destinations.');
            const apply = document.querySelector('[data-holon-target-apply]');
            mountedPicker.api.setSelectedHolonId(999999999);
            assert.equal(apply.disabled, true, 'An unavailable space cannot be selected.');
            mountedPicker.api.setSelectedHolonId(id);
            assert.equal(apply.disabled, false);
            apply.click();
            assert.equal(stack.length, 0, 'Choosing a space restores the existing proposal form.');
            assert.equal(form.isConnected, true);
            assert.equal(field.value, String(id));
            assert.equal(label.value, 'Chosen space ' + id);
        }
        const destination = isProposal ? fixture.proposeOnly : fixture.destination;
        if (isProject) assert.equal(settings.selectableHolonIds.includes(isProposal ? fixture.destination : fixture.proposeOnly), false,
            'Create and propose must expose their own collective ranges.');
        await openAndChoose(destination);
        if (isProject) assert.equal(form.querySelector('[type="submit"]').textContent, isProposal ? 'Proposer le projet' : 'Creer le projet');
        form.reportValidity = () => true;
        form.dispatchEvent(new window.Event('submit', {bubbles: true, cancelable: true}));
        await tick();
        assert.equal(requests.length, 1);
        assert.equal(requests[0].options.body.get('holon_id'), String(destination));
        if (isProject) assert.equal(requests[0].options.body.get('operation'), isProposal ? 'propose' : 'create');
        assert.equal(requests[0].options.body.getAll('holon_id').length, 1, 'No stale hidden destination may override the picker.');
        assert.deepEqual(notices, []);
    } finally { window.close(); }
}

async function checkGovernanceChooser() {
    const payload = {organizationId: 1, contextHolonId: fixture.destination, currentUserId: 1,
        contextLabels: {[fixture.destination]: 'Create space', [fixture.proposeOnly]: 'Propose space'},
        contextPermissions: {project: {create: [fixture.destination], propose: [fixture.proposeOnly]}},
        blueprint: [{title: 'Collective choice', description: '', actions: []}],
        texts: {projectCreate: 'Creer le projet', projectPropose: 'Proposer le projet', propose: 'Proposer'}};
    const dom = new JSDOM(`<section data-governance-editor><script type="application/json" data-governance-data>${JSON.stringify(payload)}</script>
        <form data-governance-form><input data-governance-blueprint><div data-governance-proposals></div><p data-governance-feedback></p></form>
        <template data-governance-fields="project"><form data-editor><input name="title"><button type="submit">Save</button><button type="button" data-cancel>Cancel</button></form></template>
        </section><div id="commonTopbarModalBody"></div>`, {url: 'https://omo.test/omo/', runScripts: 'outside-only'});
    const {window} = dom;
    const {document} = window;
    window.TextEncoder = TextEncoder;
    window.omoProposalHtml = {mount: () => Promise.resolve()};
    window.commonTopbarOpenModal = (title, content) => { document.getElementById('commonTopbarModalBody').innerHTML = content; };
    window.commonTopbarCloseModal = () => {};
    try {
        window.eval(read('common/choice/governance-actions.js'));
        window.omoGovernanceEditorInit(document);
        document.querySelector('[data-add-action]').click();
        document.querySelector('[data-type="project"]').click();
        const operation = document.querySelector('[data-operation]');
        operation.value = 'propose'; operation.dispatchEvent(new window.Event('change'));
        assert.equal(document.querySelector('[data-context-label]').value, 'Propose space');
        document.querySelector('[data-create-row] [data-open-editor]').click();
        const editor = document.querySelector('[data-editor]');
        assert.equal(editor.querySelector('[type="submit"]').textContent, 'Proposer le projet');
        editor.elements.namedItem('title').value = 'Project submitted by collective';
        editor.dispatchEvent(new window.Event('submit', {cancelable: true}));
        const action = JSON.parse(document.querySelector('[data-governance-blueprint]').value)[0].actions[0];
        assert.equal(action.type, 'project.propose');
        assert.equal(action.holonId, fixture.proposeOnly);
        assert.equal(action.targetId, 0);
        assert.deepEqual(action.before, {});
    } finally { window.close(); }
}

async function checkProjectCancel(type, reopened, stacked, dirty) {
    const dom = new JSDOM('<div id="commonTopbarModal" hidden><div class="common-topbar-modal__panel">'
        + '<span id="commonTopbarModalTitle"></span><button data-topbar-modal-close>Close</button>'
        + '<div id="commonTopbarModalBody"></div></div></div>',
    {url: 'https://omo.test/omo/', runScripts: 'outside-only', pretendToBeVisual: true});
    const {window} = dom;
    const {document} = window;
    let confirmations = 0;
    let approved = false;
    let closures = 0;
    window.fetch = async () => ({ok: true, json: async () => ({})});
    window.HTMLElement.prototype.scrollIntoView = () => {};
    window.confirm = () => { confirmations++; return approved; };
    window.addEventListener('common-topbar-modal-close', () => closures++);
    try {
        window.eval(read('common/assets/topbar.js'));
        window.eval(read('common/drawer/subdrawer.js'));
        if (stacked) window.commonTopbarOpenModal('Parent', '<div data-parent>Parent</div>');
        const open = stacked ? window.commonTopbarPushModal : window.commonTopbarOpenModal;
        open('Project', fixture[reopened ? 'reopened' : 'html'][type]);
        for (const script of document.querySelectorAll('script:not([src])')) {
            if (!script.type || script.type === 'text/javascript') window.eval(script.textContent);
        }
        await tick();
        const form = document.querySelector('[data-deferred-project-form]');
        const cancel = form.querySelector('[data-deferred-project-cancel]');
        const field = form.elements.namedItem('title');
        if (dirty) {
            field.dispatchEvent(new window.Event('focusin', {bubbles: true}));
            field.value = 'Unsaved project';
            field.dispatchEvent(new window.Event('input', {bubbles: true}));
            cancel.click();
            assert.equal(confirmations, 1, 'Cancel must ask before discarding changes.');
            assert.equal(form.isConnected, true, 'Declining must preserve the form.');
            assert.equal(field.value, 'Unsaved project');
            assert.equal(document.getElementById('commonTopbarModal').hidden, false);
            assert.equal(closures, 0);
            approved = true;
        }
        cancel.click();
        assert.equal(confirmations, dirty ? 2 : 0, 'Each cancellation asks once, only for modified forms.');
        assert.equal(form.isConnected, false, 'Confirmed cancel must remove the project form.');
        assert.equal(document.getElementById('commonTopbarModal').hidden, !stacked);
        assert.equal(closures, stacked ? 0 : 1);
        if (stacked) assert.ok(document.querySelector('[data-parent]'), 'Cancel must restore the previous popup.');
    } finally { window.close(); }
}

async function checkChooser() {
    const dom = new JSDOM(fixture.picker, {url: 'https://omo.test/omo/', runScripts: 'outside-only'});
    const {window} = dom;
    const {document} = window;
    const opened = [];
    let pickerConfig;
    window.commonTopbarRefreshModalContent = url => opened.push(new URL(url, window.location.origin));
    window.omoMountHolonScopePicker = config => {
        pickerConfig = config;
        return {destroy() {}, getSelectedHolonLabel: () => 'Destination'};
    };
    try {
        window.eval(read('omo/api/deferred_proposals/pv_proposal_picker.js'));
        const inline = Array.from(document.querySelectorAll('script:not([src])')).find(script => script.textContent.includes('contextPermissions'));
        assert.ok(inline, 'The PHP chooser must supply its permission ranges.');
        window.eval(inline.textContent.replace('document.currentScript.previousElementSibling', 'null'));
        const operation = document.querySelector('[data-deferred-operation]');
        document.querySelector('[data-deferred-target-type="project"]').click();
        assert.equal(operation.querySelector('[value="propose"]').hidden, false);
        document.querySelector('[data-deferred-context-open]').click();
        assert.ok(pickerConfig.selectableHolonIds.includes(fixture.destination));
        assert.equal(pickerConfig.selectableHolonIds.includes(fixture.proposeOnly), false);
        document.querySelector('[data-deferred-context-close]').click();
        operation.value = 'propose';
        operation.dispatchEvent(new window.Event('change'));
        document.querySelector('[data-deferred-context-open]').click();
        assert.deepEqual(Array.from(pickerConfig.selectableHolonIds), [fixture.proposeOnly]);
        pickerConfig.onChange(fixture.destination);
        assert.equal(document.querySelector('[data-deferred-context-choose]').disabled, true);
        pickerConfig.onChange(fixture.proposeOnly);
        document.querySelector('[data-deferred-context-choose]').click();
        document.querySelector('[data-deferred-create-row] [data-deferred-open-editor]').click();
        assert.equal(opened.at(-1).searchParams.get('operation'), 'propose');
        assert.equal(opened.at(-1).searchParams.get('holon_id'), String(fixture.proposeOnly));
        assert.equal(document.querySelector('[data-deferred-object-row]').hidden, true, 'Propose captures a new project.');
        document.querySelector('[data-deferred-target-type="indicator"]').click();
        assert.equal(operation.value, 'create');
        assert.equal(operation.querySelector('[value="propose"]').disabled, true, 'The project proposal action cannot leak to other object types.');
    } finally { window.close(); }
}

(async () => {
    for (const type of ['project', 'project_propose', 'indicator', 'recurring_task']) {
        for (const reopened of [false, true]) await check(type, reopened);
    }
    await checkChooser();
    await checkGovernanceChooser();
    for (const type of ['project', 'project_propose']) {
        for (const reopened of [false, true]) {
            for (const stacked of [false, true]) {
                for (const dirty of [false, true]) await checkProjectCancel(type, reopened, stacked, dirty);
            }
        }
    }
    console.log('pv_proposal_creation_space_browser_test: OK');
})().catch(error => { console.error(error); process.exitCode = 1; });
