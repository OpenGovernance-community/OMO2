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
const nodes = Object.fromEntries(['list', 'scope', 'members', 'period', 'status', 'add', 'retry', 'cancel'].map(name => [name, new Node()]));
const scopeButtons = ['local', 'global'].map(value => {
    const button = new Node('button');
    button.setAttribute('data-import-scope-choice', value);
    button.setAttribute('aria-pressed', value === 'local' ? 'true' : 'false');
    return button;
});
const memberButtons = ['mine', 'all'].map(value => {
    const button = new Node('button');
    button.setAttribute('data-import-members-choice', value);
    button.setAttribute('aria-pressed', value === 'mine' ? 'true' : 'false');
    return button;
});
const periodButtons = ['before', 'after'].map(value => {
    const button = new Node('button');
    button.setAttribute('data-import-period-choice', value);
    button.setAttribute('aria-pressed', value === 'before' ? 'true' : 'false');
    return button;
});
const picker = {
    querySelector(selector) { return nodes[selector.match(/data-import-(\w+)/)[1]]; },
    querySelectorAll(selector) { return selector.includes('period') ? periodButtons : (selector.includes('members') ? memberButtons : scopeButtons); }
};
const requests = [], replaced = [], events = {};
let syncs = 0, closes = 0, modalHtml = '';
const context = {
    addMenuToggle: null, addMenuPanel: null,
    pageConfig: {editorClientUi: {genericError: 'Error', pointImport: {
        title: 'Import', help: '<safe>', scope: 'Scope', local: 'Local', global: 'Global', cancel: 'Cancel',
        retry: 'Retry', loading: 'Loading', empty: 'Empty', saving: 'Saving', add: 'Add ({count})', locked: 'Locked',
        members: 'Authors', mine: 'Me', all: 'All members', canImportAllMembers: true,
        period: 'Meeting dates', before: 'Before', after: 'After',
        types: {decision: 'Decision'}, icons: {decision: '/decision.png', information: '/information.png'}
    }}},
    document: {createElement: tag => new Node(tag), querySelector: () => picker},
    window: {
        commonTopbarOpenModal(title, html) { modalHtml = html; nodes.list.replaceChildren(); },
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
    priority: 1, pointType: 'decision', duration: 12, author: 'Author', isLocked: id === 8,
    contentPreview: '<safe> first line'});

(async function () {
    assert.match(modalHtml, /&lt;safe&gt;/);
    assert.match(modalHtml, /omo-scope-toggle/);
    assert.doesNotMatch(modalHtml, /<select/);
    assert.match(modalHtml, /generic-drawer-footer--sticky/);
    assert.equal(requests[0].fields.scope, 'local');
    assert.equal(requests[0].fields.members, 'mine');
    assert.equal(requests[0].fields.period, 'before');
    assert.match(modalHtml, /data-import-period-choice="after">After/);
    assert.match(modalHtml, /data-import-members-choice="all">All members/);
    assert.equal(requests[0].fields.limit, 8);
    requests[0].resolve({items: Array.from({length: 8}, (_, index) => point(index + 1)), cursor: {date: '2026-09-01', id: 1}, hasMore: true});
    await settle();
    assert.equal(requests.length, 1, 'Do not load another page before scrolling a filled list.');
    assert.equal(nodes.list.children.length, 8);
    assert.equal(nodes.list.children[7].disabled, true, 'Locked points are not selectable.');
    assert.equal(nodes.list.children[0].tag, 'button', 'Cards are keyboard accessible buttons without checkboxes.');
    assert.match(nodes.list.children[0].className, /generic-choice-card/);
    const firstRow = nodes.list.children[0];
    assert.match(firstRow.className, /omo-pv-editor__nav-row--priority-p1/);
    assert.equal(firstRow.children[0].children[0].children[0].src, '/decision.png');
    assert.equal(firstRow.children[0].children[0].children[0].alt, 'Decision');
    assert.match(firstRow.children[0].children[2].textContent, /P1.*12 min/);
    assert.equal(firstRow.children[0].children[3].textContent, '<safe> first line', 'Preview stays plain text.');
    assert.equal(firstRow.children[0].children[3].className, 'omo-pv-import__preview');
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
    memberButtons[1].fire('click');
    assert.equal(requests[3].fields.members, 'all');
    assert.equal(requests[3].fields.scope, 'global', 'The author filter preserves the chosen scope.');
    assert.equal(requests[3].fields.cursor, 'null');
    assert.equal(memberButtons[1].getAttribute('aria-pressed'), 'true');
    assert.equal(nodes.members.getAttribute('--omo-scope-active-index'), '1');
    requests[2].resolve({items: [point(98)], hasMore: false});
    await settle();
    assert.equal(nodes.list.children.length, 0, 'An old author-filter response must be ignored.');
    requests[3].resolve({items: [point(20), point(21)], hasMore: false});
    await settle();
    for (const row of nodes.list.children) row.fire('click');
    memberButtons[1].fire('click');
    assert.equal(requests.length, 4, 'Choosing the active author filter preserves selections without reloading.');
    assert.equal(nodes.add.textContent, 'Add (2)');
    nodes.add.fire('click');
    assert.equal(requests[4].action, 'import_points');
    assert.equal(requests[4].fields.point_ids, '20,21');
    assert.equal(requests[4].fields.members, 'all');
    assert.equal(requests[4].fields.period, 'before');
    assert.equal(scopeButtons[0].disabled, true);
    assert.equal(scopeButtons[1].disabled, true);
    assert.equal(memberButtons[1].disabled, true);
    memberButtons[0].fire('click');
    assert.equal(requests.length, 5, 'Author changes are blocked while the transfer is in progress.');
    assert.equal(nodes.list.children[0].disabled, true);
    requests[4].reject({message: 'Point changed'});
    await settle();
    assert.equal(nodes.status.textContent, 'Point changed');
    assert.equal(nodes.retry.hidden, false);
    assert.equal(requests.length, 5, 'Failed transfers must never retry themselves.');
    nodes.retry.fire('click');
    requests[5].resolve({items: [point(21)], hasMore: false});
    await settle();
    nodes.list.children[0].fire('click');
    nodes.add.fire('click');
    requests[6].resolve({points: [{id: 100}]});
    await settle();
    assert.deepEqual(replaced, [{id: 100}]);
    assert.equal(syncs, 1);
    assert.equal(closes, 1);
    context.pageConfig.editorClientUi.pointImport.canImportAllMembers = false;
    memberButtons[0].setAttribute('aria-pressed', 'true');
    memberButtons[1].setAttribute('aria-pressed', 'false');
    memberButtons[1].disabled = true;
    vm.runInContext('openPointImport();', context);
    assert.match(modalHtml, /data-import-members-choice="all" disabled>/);
    assert.equal(requests[7].fields.members, 'mine');
    memberButtons[1].fire('click');
    assert.equal(requests.length, 8, 'A non-editor cannot activate the all-members filter.');
    requests[7].resolve({items: [point(30)], hasMore: false});
    await settle();
    nodes.list.children[0].fire('click');
    nodes.add.fire('click');
    requests[8].reject({message: 'Point changed'});
    await settle();
    assert.equal(memberButtons[0].disabled, false);
    assert.equal(memberButtons[1].disabled, true, 'Transfer errors must preserve the editor-only restriction.');
    periodButtons[1].fire('click');
    assert.equal(requests[9].fields.period, 'after', 'Non-editors can choose future meetings for their own points.');
    assert.equal(requests[9].fields.scope, 'local');
    assert.equal(requests[9].fields.members, 'mine');
    assert.equal(nodes.period.getAttribute('--omo-scope-active-index'), '1');
    periodButtons[0].fire('click');
    requests[9].resolve({items: [point(99)], hasMore: false});
    await settle();
    assert.equal(nodes.list.children.length, 0, 'A stale future-meeting response must be ignored after switching back.');
    requests[10].resolve({items: [point(40)], hasMore: false});
    await settle();
    nodes.list.children[0].fire('click');
    periodButtons[1].fire('click');
    assert.equal(nodes.add.disabled, true, 'Period changes clear the old selection.');
    assert.equal(requests[11].fields.cursor, 'null');
    requests[11].resolve({items: [point(41)], hasMore: false});
    await settle();
    nodes.list.children[0].fire('click');
    nodes.add.fire('click');
    assert.equal(requests[12].fields.period, 'after', 'The transfer includes the chosen period.');
    assert.equal(requests[12].fields.point_ids, '41');
    assert.equal(periodButtons[0].disabled, true);
    periodButtons[0].fire('click');
    assert.equal(requests.length, 13, 'Period changes are blocked while saving.');
    requests[12].reject({message: 'Point changed'});
    await settle();
    assert.equal(periodButtons[0].disabled, false);
    assert.equal(periodButtons[1].disabled, false);
    console.log('pv_point_import_picker_test: OK');
})().catch(error => { console.error(error); process.exitCode = 1; });
