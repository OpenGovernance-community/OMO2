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
        if (type === 'project') {
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
        if (type === 'project') {
            await openAndChoose(fixture.proposeOnly);
            assert.equal(form.querySelector('[type="submit"]').textContent, 'Proposer le projet',
                'A propose-only collective destination must use the proposal label.');
        }
        await openAndChoose(fixture.destination);
        if (type === 'project') assert.equal(form.querySelector('[type="submit"]').textContent, 'Enregistrer la modification');
        form.reportValidity = () => true;
        form.dispatchEvent(new window.Event('submit', {bubbles: true, cancelable: true}));
        await tick();
        assert.equal(requests.length, 1);
        assert.equal(requests[0].options.body.get('holon_id'), String(fixture.destination));
        assert.equal(requests[0].options.body.getAll('holon_id').length, 1, 'No stale hidden destination may override the picker.');
        assert.deepEqual(notices, []);
    } finally { window.close(); }
}

(async () => {
    for (const type of ['project', 'indicator', 'recurring_task']) {
        for (const reopened of [false, true]) await check(type, reopened);
    }
    console.log('pv_proposal_creation_space_browser_test: OK');
})().catch(error => { console.error(error); process.exitCode = 1; });
