const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

// Exercise the real popup controller with a small DOM boundary and delayed requests.
const source = fs.readFileSync(require('node:path').join(__dirname, '../omo/api/documents/pv/editor.js'), 'utf8');
const controller = source.slice(source.indexOf('    function closeAddMenu()'), source.indexOf('    if (addMenuToggle && addMenuPanel)'));
class Node {
    constructor(tag = 'div') {
        this.tag = tag;
        this.children = [];
        this.handlers = {};
        this.hidden = false;
        this.disabled = false;
        this.clientHeight = 480;
        this.scrollTop = 0;
        this.value = '';
        this.attributes = {};
        this.style = {setProperty: (name, value) => { this.attributes[name] = value; }};
        this.classList = {toggle: () => {}};
    }
    get innerHTML() { return String(this.textContent || '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;'); }
    get scrollHeight() { return this.children.length * 90; }
    append(...children) { this.children.push(...children); }
    replaceChildren() { this.children = []; }
    addEventListener(name, handler) { this.handlers[name] = handler; }
    fire(name) { if (this.handlers[name]) this.handlers[name](); }
    focus() { this.focused = true; }
    querySelectorAll() { return this.children; }
    setAttribute(name, value) { this.attributes[name] = value; }
    getAttribute(name) { return this.attributes[name]; }
}
const nodes = Object.fromEntries(['list', 'scope', 'status', 'add', 'retry', 'cancel'].map(name => [name, new Node()]));
const scopeButtons = ['local', 'global'].map(value => {
    const button = new Node('button');
    button.setAttribute('data-import-scope-choice', value);
    return button;
});
const picker = {
    querySelector(selector) { return nodes[selector.match(/data-import-(\w+)/)[1]]; },
    querySelectorAll() { return scopeButtons; }
};
const requests = [], replaced = [], events = {};
let syncs = 0, closes = 0, modalHtml = '';
const context = {
    addMenuToggle: null, addMenuPanel: null,
    pageConfig: {editorClientUi: {genericError: 'Error', pointImport: {
        title: 'Import', help: '<safe>', scope: 'Scope', local: 'Local', global: 'Global', cancel: 'Cancel',
        retry: 'Retry', loading: 'Loading', empty: 'Empty', saving: 'Saving', add: 'Add ({count})', locked: 'Locked',
        types: {decision: 'Decision'}
    }}},
    document: {createElement: tag => new Node(tag), querySelector: () => picker},
    window: {
        commonTopbarOpenModal(title, html) { modalHtml = html; },
        commonTopbarCloseModal() { closes++; if (events['common-topbar-modal-close']) events['common-topbar-modal-close'](); },
        addEventListener(name, handler) { events[name] = handler; }
    },
    postPointAction(action, id, fields) { return new Promise((resolve, reject) => requests.push({action, fields, resolve, reject})); },
    replacePointHtml: point => replaced.push(point), syncEditorFromServer: () => syncs++
};
vm.createContext(context);
vm.runInContext(controller + '\nopenPointImport();', context);
const settle = () => new Promise(resolve => setImmediate(resolve));
const point = id => ({id, title: 'Point ' + id, meetingDate: '01.09.2026', meetingTitle: 'Meeting',
    priority: 1, pointType: 'decision', duration: 12, author: 'Author', isLocked: id === 8});

(async function () {
    assert.match(modalHtml, /&lt;safe&gt;/);
    assert.match(modalHtml, /omo-scope-toggle/);
    assert.doesNotMatch(modalHtml, /<select/);
    assert.match(modalHtml, /generic-drawer-footer--sticky/);
    assert.equal(requests[0].fields.scope, 'local');
    assert.equal(requests[0].fields.limit, 8);
    requests[0].resolve({items: Array.from({length: 8}, (_, index) => point(index + 1)), cursor: {date: '2026-09-01', id: 1}, hasMore: true});
    await settle();
    assert.equal(requests.length, 1, 'Do not load another page before scrolling a filled list.');
    assert.equal(nodes.list.children.length, 8);
    assert.equal(nodes.list.children[7].disabled, true, 'Locked points are not selectable.');
    assert.equal(nodes.list.children[0].tag, 'button', 'Cards are keyboard accessible buttons without checkboxes.');
    assert.match(nodes.list.children[0].className, /generic-choice-card/);
    assert.match(nodes.list.children[0].children[0].children[2].textContent, /P1.*Decision.*12 min/);
    for (const index of [0, 1]) {
        nodes.list.children[index].fire('click');
        assert.equal(nodes.list.children[index].getAttribute('aria-pressed'), 'true');
    }
    assert.equal(nodes.add.textContent, 'Add (2)');
    nodes.list.children[7].fire('click');
    assert.equal(nodes.add.textContent, 'Add (2)', 'A locked card cannot join the selection.');
    nodes.list.children[0].fire('click');
    assert.equal(nodes.list.children[0].getAttribute('aria-pressed'), 'false');
    assert.equal(nodes.add.textContent, 'Add (1)');
    nodes.list.children[0].fire('click');
    scopeButtons[0].fire('click');
    assert.equal(requests.length, 1, 'Choosing the active scope must preserve selections and avoid a reload.');
    nodes.list.scrollTop = 300;
    nodes.list.fire('scroll');
    assert.equal(requests.length, 2);
    scopeButtons[1].fire('click');
    assert.equal(requests.length, 3);
    assert.equal(nodes.add.disabled, true, 'Scope changes clear selections.');
    assert.equal(scopeButtons[1].getAttribute('aria-pressed'), 'true');
    assert.equal(nodes.scope.getAttribute('--omo-scope-active-index'), '1');
    assert.equal(requests[2].fields.scope, 'global');
    requests[1].resolve({items: [point(99)], hasMore: false});
    await settle();
    assert.equal(nodes.list.children.length, 0, 'An old scope response must not repopulate the popup.');
    requests[2].resolve({items: [point(20), point(21)], hasMore: false});
    await settle();
    for (const row of nodes.list.children) row.fire('click');
    nodes.add.fire('click');
    assert.equal(requests[3].action, 'import_points');
    assert.equal(requests[3].fields.point_ids, '20,21');
    assert.equal(scopeButtons[0].disabled, true);
    assert.equal(scopeButtons[1].disabled, true);
    assert.equal(nodes.list.children[0].disabled, true);
    requests[3].reject({message: 'Point changed'});
    await settle();
    assert.equal(nodes.status.textContent, 'Point changed');
    assert.equal(nodes.retry.hidden, false);
    assert.equal(requests.length, 4, 'Failed transfers must never retry themselves.');
    nodes.retry.fire('click');
    requests[4].resolve({items: [point(21)], hasMore: false});
    await settle();
    nodes.list.children[0].fire('click');
    nodes.add.fire('click');
    requests[5].resolve({points: [{id: 100}]});
    await settle();
    assert.deepEqual(replaced, [{id: 100}]);
    assert.equal(syncs, 1);
    assert.equal(closes, 1);
    console.log('pv_point_import_picker_test: OK');
})().catch(error => { console.error(error); process.exitCode = 1; });
