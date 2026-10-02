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
    }
    get innerHTML() { return String(this.textContent || '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;'); }
    get scrollHeight() { return this.children.length * 90; }
    append(...children) { this.children.push(...children); }
    replaceChildren() { this.children = []; }
    addEventListener(name, handler) { this.handlers[name] = handler; }
    fire(name) { if (this.handlers[name]) this.handlers[name](); }
    focus() { this.focused = true; }
    querySelectorAll() { return this.children.map(row => row.children[0]); }
}
const nodes = Object.fromEntries(['list', 'scope', 'status', 'add', 'retry', 'cancel'].map(name => [name, new Node()]));
nodes.scope.value = 'local';
const picker = {querySelector(selector) { return nodes[selector.match(/data-import-(\w+)/)[1]]; }};
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
    assert.equal(requests[0].fields.scope, 'local');
    assert.equal(requests[0].fields.limit, 8);
    requests[0].resolve({items: Array.from({length: 8}, (_, index) => point(index + 1)), cursor: {date: '2026-09-01', id: 1}, hasMore: true});
    await settle();
    assert.equal(requests.length, 1, 'Do not load another page before scrolling a filled list.');
    assert.equal(nodes.list.children.length, 8);
    assert.equal(nodes.list.children[7].children[0].disabled, true, 'Locked points are not selectable.');
    assert.match(nodes.list.children[0].children[1].children[2].textContent, /P1.*Decision.*12 min/);
    for (const index of [0, 1]) {
        const checkbox = nodes.list.children[index].children[0];
        checkbox.checked = true;
        checkbox.fire('change');
    }
    assert.equal(nodes.add.textContent, 'Add (2)');
    nodes.list.scrollTop = 300;
    nodes.list.fire('scroll');
    assert.equal(requests.length, 2);
    nodes.scope.value = 'global';
    nodes.scope.fire('change');
    assert.equal(requests.length, 3);
    assert.equal(nodes.add.disabled, true, 'Scope changes clear selections.');
    requests[1].resolve({items: [point(99)], hasMore: false});
    await settle();
    assert.equal(nodes.list.children.length, 0, 'An old scope response must not repopulate the popup.');
    requests[2].resolve({items: [point(20), point(21)], hasMore: false});
    await settle();
    for (const row of nodes.list.children) { row.children[0].checked = true; row.children[0].fire('change'); }
    nodes.add.fire('click');
    assert.equal(requests[3].action, 'import_points');
    assert.equal(requests[3].fields.point_ids, '20,21');
    assert.equal(nodes.scope.disabled, true);
    requests[3].reject({message: 'Point changed'});
    await settle();
    assert.equal(nodes.status.textContent, 'Point changed');
    assert.equal(nodes.retry.hidden, false);
    assert.equal(requests.length, 4, 'Failed transfers must never retry themselves.');
    nodes.retry.fire('click');
    requests[4].resolve({items: [point(21)], hasMore: false});
    await settle();
    nodes.list.children[0].children[0].checked = true;
    nodes.list.children[0].children[0].fire('change');
    nodes.add.fire('click');
    requests[5].resolve({points: [{id: 100}]});
    await settle();
    assert.deepEqual(replaced, [{id: 100}]);
    assert.equal(syncs, 1);
    assert.equal(closes, 1);
    console.log('pv_point_import_picker_test: OK');
})().catch(error => { console.error(error); process.exitCode = 1; });
