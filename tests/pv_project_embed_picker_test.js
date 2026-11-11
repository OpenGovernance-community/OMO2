const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

// Exercise the actual PV popup, async loader and shared project controller together.
const source = fs.readFileSync(path.join(__dirname, '../omo/api/documents/pv/editor.js'), 'utf8');
const section = (start, end) => {
    const offset = source.indexOf(start);
    assert(offset >= 0);
    const limit = source.indexOf(end, offset);
    assert(limit > offset);
    return source.slice(offset, limit);
};
class Element {
    constructor() {
        this.isConnected = true;
        this.children = [];
        this.attributes = {};
        this.handlers = {};
        this.nodes = {};
        this.value = '';
        this.classList = {add() {}};
    }
    querySelector(selector) { return this.nodes[selector] || null; }
    querySelectorAll(selector) { return this.nodes[selector] || []; }
    setAttribute(name, value) { this.attributes[name] = value; }
    getAttribute(name) { return this.attributes[name]; }
    removeAttribute(name) { delete this.attributes[name]; }
    addEventListener(name, handler) { this.handlers[name] = handler; }
    removeEventListener(name) { delete this.handlers[name]; }
    fire(name) { if (this.handlers[name]) this.handlers[name](); }
    appendChild(child) { this.children.push(child); }
    prepend(child) { this.children.unshift(child); child.parent = this; }
    remove() { if (this.parent) this.parent.children = this.parent.children.filter(child => child !== this); }
    set innerHTML(value) { this.html = value; this.children = []; }
}
class Select extends Element {}
class Form extends Element {}
const requests = [], errors = [], inserted = [];
let body, select, search, preview, insert, modalEvents = {};
const window = {
    commonTopbarOpenModal() {
        body = new Element();
        const picker = new Element();
        select = new Select(); search = new Element(); preview = new Element(); insert = new Element();
        body.nodes = {
            '.generic-tabs.omo-document-embed-picker': picker,
            '.omo-document-embed-picker': picker,
            '[data-omo-pv-project-embed-select]': select,
            '[data-omo-pv-project-embed-search]': search,
            '[data-omo-pv-project-embed-preview]': preview,
            '[data-omo-pv-project-embed-insert]': insert
        };
    },
    commonTopbarCloseModal() {
        body.isConnected = false;
        body.nodes['.omo-document-embed-picker'].isConnected = false;
        if (modalEvents['common-topbar-modal-close']) modalEvents['common-topbar-modal-close']();
        modalEvents = {};
    },
    addEventListener(name, handler) { modalEvents[name] = handler; },
    omoNotify: (message, type) => errors.push([message, type]),
    setTimeout: handler => handler()
};
const context = vm.createContext({
    window, Element, HTMLSelectElement: Select, HTMLFormElement: Form,
    document: {createElement: () => new Element(), getElementById: () => body},
    root: new Element(),
    canEmbedProjects: true,
    projectEmbedCreateContext: {holonId: 10}, projectEmbedUi: {none: 'Empty'},
    resourcePickerOrganizationId: 1, resourcePickerInitialHolonId: 10, resourcePickerScopeUi: {},
    embeddableProjects: [],
    pageConfig: {resourceCatalogUrl: '/resources.php?oid=1&id=2', resourceCatalogUi: {loading: 'Loading', error: 'Failed'}},
    escapeDocumentEmbedHtml: value => String(value),
    buildPvProjectEmbedHtml: project => 'Project ' + project.id,
    insertPvEmbedIntoField: (field, target, marker, html) => { inserted.push(html); return true; },
    refreshPvProjectEmbedReviews() {},
    fetch: (url, options) => new Promise(resolve => requests.push({url, options, resolve}))
});
vm.runInContext(fs.readFileSync(path.join(__dirname, '../common/project-picker/project-picker.js'), 'utf8'), context);
vm.runInContext(section('    const resourceCatalogRequests = new Map();', '    function openPvDocumentEmbedPicker(')
    + section('    function openPvProjectEmbedPicker(', '    function buildPvEventEmbedHtml('), context);
const field = {createTemporaryCursorMarker: () => new Element(), removeTemporaryMarker() {}};
const open = target => context.openPvProjectEmbedPicker(field, target);
const settle = () => new Promise(resolve => setImmediate(resolve));
const respond = (index, items, ok = true) => requests[index].resolve({ok, json: async () => ({status: true, items})});

(async () => {
    open(null);
    assert.equal(requests.length, 1, 'Opening the Summernote project popup must start loading the catalogue.');
    assert.match(requests[0].url, /&type=projects$/);
    assert.equal(select.children.length, 0);
    assert.equal(insert.disabled, true, 'Insertion stays disabled while loading.');
    respond(0, [{id: 1, title: 'Alpha'}, {id: 2, title: 'Beta'}]);
    await settle();
    assert.equal(select.children.length, 2, 'Loaded projects must populate the real popup.');
    assert.equal(insert.disabled, false);
    assert.equal(preview.html, 'Project 1');
    search.value = 'Beta'; search.fire('input');
    assert.equal(select.children.length, 1, 'Search still filters asynchronously loaded projects.');
    assert.equal(preview.html, 'Project 2');
    insert.fire('click');
    assert.deepEqual(inserted, ['Project 2'], 'Insert uses the selected project.');

    const target = new Element(); target.setAttribute('data-omo-project-id', '2');
    open(target);
    assert.equal(insert.disabled, true, 'Reopening clears stale projects.');
    respond(1, [{id: 1, title: 'Alpha'}, {id: 2, title: 'Beta'}]);
    await settle();
    assert.equal(select.value, '2', 'Editing an existing embed restores its project.');
    assert.equal(preview.html, 'Project 2');
    window.commonTopbarCloseModal();

    open(null); respond(2, [], false); await settle();
    assert.equal(select.children.length, 0);
    assert.equal(insert.disabled, true);
    assert.deepEqual(errors, [['Failed', 'error']]);
    window.commonTopbarCloseModal();
    open(null); respond(3, [{id: 3, title: 'Retry'}]); await settle();
    assert.equal(select.children.length, 1, 'Reopening retries a failed catalogue.');
    assert.equal(preview.html, 'Project 3');
    console.log('pv_project_embed_picker_test: OK');
})().catch(error => { console.error(error); process.exitCode = 1; });
