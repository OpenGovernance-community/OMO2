'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {JSDOM} = require('jsdom');
const read = file => fs.readFileSync(path.join(__dirname, '..', file), 'utf8');
const settle = () => new Promise(resolve => setTimeout(resolve, 0));

(async function () {
    const dom = new JSDOM('<!doctype html><body><div id="commonTopbarModalBody"></div></body>', {runScripts:'outside-only'});
    const {window} = dom, {document} = window;
    await new Promise(resolve => window.addEventListener('load', resolve, {once:true}));
    window.TextEncoder = TextEncoder;
    const root = document.createElement('section');
    root.dataset.governanceEditor = '';
    root.innerHTML = '<form data-governance-form><input data-governance-blueprint><div data-governance-proposals></div><p data-governance-feedback hidden></p></form>'
        + '<script type="application/json" data-governance-data></script>'
        + '<template data-governance-fields="holon_move"><form data-editor><select name="parent_id" required><option value=""></option></select><button type="submit">Save</button><button type="button" data-cancel>Cancel</button></form></template>';
    const before = {name:'Role', parent_id:3, parent_label:'Source', template_id:0};
    root.querySelector('[data-governance-data]').textContent = JSON.stringify({editable:true, organizationId:42, contextHolonId:2,
        contextLabels:{3:'Source'}, contextPermissions:{holon:{create:[], update:[], delete:[], move:[3]}},
        texts:{roleMove:'Deplacer', move:'Deplacement'}, blueprint:[{id:0, title:'Test', actions:[]}]});
    document.body.appendChild(root);
    window.commonTopbarOpenModal = (title, html) => { document.getElementById('commonTopbarModalBody').innerHTML = html; };
    window.commonTopbarCloseModal = () => {};
    window.commonNotify = message => { throw new Error(message); };
    window.fetch = async url => ({ok:true, json:async () => String(url).includes('moving_holon_id=5')
        ? {status:true, before, destinations:[{id:3, pathLabel:'Source', isCurrentParent:true}, {id:4, pathLabel:'Destination'}]}
        : {status:true, context:{id:3,label:'Source'}, objects:[{id:5,label:'Role',state:before}]}});
    window.eval(read('common/choice/governance-actions.js'));
    root.querySelector('[data-add-action]').click();
    const modal = document.getElementById('commonTopbarModalBody');
    const operation = modal.querySelector('[data-operation]');
    assert(operation.querySelector('[value="move"]').disabled, 'Move is unavailable for rules');
    modal.querySelector('[data-type="holon"]').click();
    assert(!operation.querySelector('[value="move"]').disabled, 'Move is available for holons');
    operation.value = 'move'; operation.dispatchEvent(new window.Event('change'));
    await settle();
    const object = modal.querySelector('[data-object]');
    object.value = '5'; object.dispatchEvent(new window.Event('change'));
    modal.querySelector('[data-object-row] [data-open-editor]').click();
    await settle();
    let editor = modal.querySelector('[data-editor]');
    assert.deepEqual(Array.from(editor.elements.parent_id.options, option => option.value), ['', '4']);
    editor.elements.parent_id.value = '4';
    editor.dispatchEvent(new window.Event('submit', {bubbles:true,cancelable:true}));
    let actions = JSON.parse(root.querySelector('[data-governance-blueprint]').value)[0].actions;
    assert.equal(actions[0].type, 'holon.move');
    assert.equal(actions[0].before.parent_id, 3);
    assert.equal(actions[0].after.parent_id, 4);
    root.querySelector('[data-edit]').click();
    await settle();
    assert.equal(modal.querySelector('[name="parent_id"]').value, '4', 'Reopening retains destination');

    window.eval(read('common/choice/change-details.js'));
    const changes = window.omoChoiceChangeDetails.governanceChanges(actions[0], [], {});
    assert.equal(changes.length, 1);
    assert.equal(changes[0].before, 'Source');
    assert.equal(changes[0].after, 'Destination');

    modal.innerHTML = '<form data-deferred-holon-move><select name="parent_id" required><option value=""></option><option value="4">Destination</option></select><p data-feedback hidden></p><button type="submit">Save</button><button type="button" data-cancel>Cancel</button></form>';
    let posted, saved = null;
    window.fetch = async (url, options) => { posted = {url, operation:options.body.get('operation'), payload:JSON.parse(options.body.get('payload'))}; return {ok:true, json:async () => ({status:true, id:19, pointId:11})}; };
    window.addEventListener('omo-deferred-proposal-saved', event => { saved = event.detail; });
    window.eval(read('omo/api/deferred_proposals/pv_holon_move_editor.js'));
    const initialize = window.commonPageScripts['/omo/api/deferred_proposals/pv_holon_move_editor.js'];
    initialize({organizationId:42,pointId:11,proposalId:0,holonId:5,saveError:'Error'});
    editor = modal.querySelector('form');
    editor.elements.parent_id.value = '4';
    editor.dispatchEvent(new window.Event('submit', {bubbles:true,cancelable:true}));
    await settle();
    assert.equal(posted.url, '/omo/api/deferred_proposals/pv_holon_save.php');
    assert.equal(posted.operation, 'move');
    assert.equal(posted.payload.parent_id, 4);
    assert.equal(saved.pointId, 11);
    assert.equal(saved.proposalId, 19);
    dom.window.close();
    console.log('holon_move_editor_test: OK');
})().catch(error => { console.error(error); process.exitCode = 1; });
