'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const source = fs.readFileSync(path.join(__dirname, '../omo/api/documents/pv/editor.js'), 'utf8');

function functionSource(name) {
    const start = source.indexOf('    function ' + name + '(');
    const end = source.indexOf('\n    function ', start + 1);
    assert.ok(start >= 0 && end > start, name);
    return source.slice(start, end);
}

// Minimal DOM boundary: run the production listeners, draft restoration and save flow.
class Element {
    constructor(attributes = {}) {
        this.attributes = {...attributes};
        this.children = [];
        this.value = '';
        this.innerHTML = '';
        this.textContent = '';
        this.classList = {toggle() {}};
        this.isConnected = true;
    }
    getAttribute(name) { return this.attributes[name] ?? null; }
    setAttribute(name, value) { this.attributes[name] = String(value); }
    removeAttribute(name) { delete this.attributes[name]; }
    matches(selector) {
        return [...selector.matchAll(/\[([^=\]]+)(?:="([^"]*)")?\]/g)].every(([, name, value]) =>
            name in this.attributes && (value === undefined || this.attributes[name] === value));
    }
    querySelectorAll(selector) {
        return this.children.flatMap(child => [child, ...child.querySelectorAll('*')])
            .filter(child => child.matches(selector));
    }
    querySelector(selector) { return this.querySelectorAll(selector)[0] || null; }
    closest(selector) { return this.matches(selector) ? this : this.parent?.closest(selector) || null; }
    contains(node) { return node === this || this.children.some(child => child.contains(node)); }
    append(child) { child.parent = this; this.children.push(child); return child; }
}
class Input extends Element {}
class Textarea extends Element {}

function harness() {
    const root = new Element();
    const listeners = {};
    const timers = [];
    const requests = [];
    const notices = [];
    root.addEventListener = (name, callback) => { listeners[name] = callback; };
    const context = {
        root, Element, HTMLInputElement: Input, HTMLTextAreaElement: Textarea,
        document: {activeElement: null, body: new Element(), documentElement: new Element()},
        window: {setTimeout: callback => timers.push(callback), omoNotify: (...args) => notices.push(args)},
        pointEditSnapshots: new Map(), saveOnPointLeaveIds: new Set(), activePointId: 0,
        pointChangeVersions: new Map(), locallyEngagedPointIds: new Set(),
        preMountEditorDrafts: new Map(), preMountEditorFocusPointIds: new Set(),
        pendingLockPointIds: new Set(), activeLockPointIds: new Set(), pendingUnlockPointIds: new Set(),
        yieldingTakeoverPointIds: new Set(),
        setFocusedPoint() {}, clearFocusedPoint() {}, syncPointLockState() {}, ensurePointLock() {},
        renderTimingSummary() {}, updatePointTypeSwitch() {}, updatePointPriorityMenu() {},
        isPointLockTakenOverRemotely: () => false,
        savingLabel: 'Saving', saveLabel: 'Save', savedLabel: 'Saved',
        editorClientUi: {genericError: 'Failed'},
        documentId: 42, organizationId: 1, editorToken: 'session', actionUrl: '/save', FormData,
        fetch: (url, options) => new Promise((resolve, reject) => requests.push({url, data: options.body, resolve, reject})),
    };
    vm.createContext(context);
    const functions = ['setPointDirtySuppressed', 'isPointDirtySuppressed', 'syncPointDirtyUi',
        'activatePoint', 'cancelPointEdit', 'markPointDirty', 'getDirtyPointIds', 'captureDraftState',
        'restoreDraftState', 'savePoint'];
    vm.runInContext(functions.map(functionSource).join('\n'), context);
    context.suppressPointDirtyDuring = (id, callback) => {
        context.setPointDirtySuppressed(id, true);
        try { callback(); } finally { context.setPointDirtySuppressed(id, false); }
    };
    const listenerStart = source.indexOf('    if (root instanceof Element) {\n        root.addEventListener(\'focusin\'');
    const listenerEnd = source.indexOf("        root.addEventListener('click'", listenerStart);
    assert.ok(listenerStart >= 0 && listenerEnd > listenerStart);
    vm.runInContext(source.slice(listenerStart, listenerEnd) + '\n}', context);

    function makeCard(id, draft = {}) {
        const card = new Element({'data-omo-pv-point-card': String(id), 'data-omo-pv-point-editable': '1'});
        const fields = {
            title: draft.title || 'Original', type: draft.pointType || 'information',
            duration: draft.desiredDurationMinutes || '15', priority: draft.priority || '3',
            author: draft.authorValue || 'user:1', 'concerned-holon': draft.concernedHolonId || '2',
            confidential: '', 'content-source': draft.content || '<p>Original</p>',
            save: '', cancel: '', status: '', 'editor-host': ''
        };
        Object.entries(fields).forEach(([name, value]) => {
            const field = card.append(new (name === 'content-source' ? Textarea : Input)({['data-omo-pv-point-' + name]: String(id)}));
            field.value = value;
            if (name === 'concerned-holon') field.innerHTML = draft.concernedHolonOptions || '<option value="2">Context 2</option>';
            if (name === 'confidential') field.checked = !!draft.isConfidential;
            if (name === 'save') field.disabled = true;
        });
        const host = card.querySelector('[data-omo-pv-point-editor-host]');
        let html = fields['content-source'];
        host.__omoPvPointField = {
            getValue: () => html,
            setValue: value => { html = value; context.markPointDirty(id, true); }
        };
        return card;
    }
    const card = id => root.querySelector('[data-omo-pv-point-card="' + id + '"]');
    const field = (id, name) => card(id).querySelector('[data-omo-pv-point-' + name + ']');
    function addCard(id, draft) {
        const item = root.append(makeCard(id, draft));
        item.__omoPvPointEditStart = context.captureDraftState(id)[id];
        context.syncPointDirtyUi(id);
        return item;
    }
    context.replacePointHtml = point => {
        root.children = root.children.filter(item => item !== card(point.id));
        return addCard(point.id, point.draft);
    };
    function focus(node) {
        const previous = context.document.activeElement;
        context.document.activeElement = node;
        if (previous) listeners.focusout({target: previous});
        listeners.focusin({target: node});
        while (timers.length) timers.shift()();
    }
    function type(id, title) {
        const input = field(id, 'title');
        listeners.beforeinput({target: input, type: 'beforeinput', isTrusted: true});
        input.value = title;
        context.markPointDirty(id, true);
    }
    function succeed(index, id, draft) {
        requests[index].resolve({ok: true, json: async () => ({status: true, point: {id, draft}})});
    }
    addCard(1); addCard(2); addCard(3);
    return {context, root, listeners, requests, notices, timers, card, field, focus, type, succeed};
}

const settle = () => new Promise(resolve => setImmediate(resolve));

(async function () {
    const h = harness();
    const c = h.context;
    h.focus(h.field(1, 'title'));
    assert.equal(h.field(1, 'save').hidden, true, 'Focus alone is not an unsaved change.');
    const original = JSON.parse(JSON.stringify(c.captureDraftState(1)[1]));
    h.type(1, 'Changed title');
    assert.equal(h.field(1, 'save').hidden, false);
    assert.equal(h.field(1, 'cancel').hidden, false);
    for (const name of ['type', 'duration', 'priority', 'author', 'concerned-holon']) h.field(1, name).value = 'changed';
    h.field(1, 'concerned-holon').innerHTML = '<option value="9">Different author context</option>';
    h.field(1, 'confidential').checked = true;
    h.field(1, 'editor-host').__omoPvPointField.setValue('<p>New <b>HTML</b></p>');
    h.focus(new Element()); // Popup outside the points.
    h.focus(h.field(1, 'cancel'));
    assert.equal(h.requests.length, 0, 'Popup and same-point focus must never save.');
    c.cancelPointEdit(1);
    assert.deepEqual(JSON.parse(JSON.stringify(c.captureDraftState(1)[1])), original, 'Cancel restores all fields and select options.');
    assert.equal(h.field(1, 'cancel').hidden, true);
    assert.equal(h.requests.length, 0, 'Cancel is local.');

    h.type(1, 'First save');
    h.focus(h.field(2, 'title'));
    assert.equal(h.requests.length, 1, 'Entering another point saves the draft.');
    assert.equal(h.requests[0].data.get('title'), 'First save');
    assert.equal(h.field(1, 'cancel').disabled, true);
    c.cancelPointEdit(1);
    assert.equal(h.field(1, 'title').value, 'First save', 'Cannot cancel an in-flight save.');
    h.focus(h.field(3, 'title'));
    assert.equal(h.requests.length, 1, 'Fast transitions must not duplicate the request.');
    h.succeed(0, 1, {...original, title: 'First save'});
    await settle();
    assert.equal(h.card(1).getAttribute('data-omo-pv-point-dirty'), '0');

    h.focus(h.field(1, 'title'));
    h.type(1, 'Next draft');
    c.cancelPointEdit(1);
    assert.equal(h.field(1, 'title').value, 'First save', 'Cancel uses the last saved version for a new edit.');

    h.type(1, 'Manual save');
    const saving = c.savePoint(1);
    h.type(1, 'Typed while saving');
    h.focus(h.field(2, 'title'));
    h.succeed(1, 1, {...original, title: 'Manual save'});
    await settle();
    assert.equal(h.requests.length, 3, 'Leaving during a save flushes subsequent edits after success.');
    assert.equal(h.requests[2].data.get('title'), 'Typed while saving');
    h.succeed(2, 1, {...original, title: 'Typed while saving'});
    await saving;

    h.focus(h.field(1, 'title'));
    h.type(1, 'Keep on failure');
    h.focus(h.field(2, 'title'));
    h.requests[3].resolve({ok: false, json: async () => ({message: 'Network problem', point: {id: 1, draft: original}})});
    await settle();
    assert.equal(h.field(1, 'title').value, 'Keep on failure', 'An error payload must not overwrite the draft.');
    assert.equal(h.card(1).getAttribute('data-omo-pv-point-dirty'), '1');
    assert.equal(h.field(1, 'cancel').disabled, false);
    assert.deepEqual(h.notices, [['Network problem', 'error']]);
    assert.equal(h.requests.length, 4, 'Failure must not start a retry loop.');
    c.cancelPointEdit(1);
    assert.equal(h.field(1, 'title').value, 'Typed while saving');

    const slow = harness();
    slow.focus(slow.field(1, 'title'));
    slow.type(1, 'Saved part');
    const slowSave = slow.context.savePoint(1);
    slow.type(1, 'Still typing here');
    slow.succeed(0, 1, {...original, title: 'Saved part'});
    await slowSave;
    assert.equal(slow.requests.length, 1, 'Staying on a point does not restore continuous autosave.');
    slow.context.cancelPointEdit(1);
    assert.equal(slow.field(1, 'title').value, 'Saved part', 'Cancel during continued editing preserves the completed save.');

    const early = harness();
    delete early.field(1, 'editor-host').__omoPvPointField;
    early.focus(early.field(1, 'content-source'));
    early.listeners.beforeinput({target: early.field(1, 'content-source'), type: 'beforeinput', isTrusted: true});
    early.field(1, 'content-source').value = '<p>Typed before editor initialization</p>';
    early.focus(early.field(2, 'title'));
    assert.equal(early.requests[0].data.get('content'), '<p>Typed before editor initialization</p>');

    const mounting = harness();
    mounting.type(1, 'Draft');
    const api = mounting.field(1, 'editor-host').__omoPvPointField;
    delete mounting.field(1, 'editor-host').__omoPvPointField;
    mounting.context.restoreDraftState({1: {...original, content: '<p>Obsolete delayed draft</p>', isDirty: true}});
    mounting.context.cancelPointEdit(1);
    mounting.field(1, 'editor-host').__omoPvPointField = api;
    while (mounting.timers.length) mounting.timers.shift()();
    assert.equal(api.getValue(), original.content, 'Pending editor initialization cannot resurrect a cancelled draft.');

    console.log('pv_point_edit_session_test: OK');
})().catch(error => { console.error(error); process.exitCode = 1; });
