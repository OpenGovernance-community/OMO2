const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

const source = fs.readFileSync(path.join(__dirname, '../omo/api/documents/pv/editor.js'), 'utf8');
const start = source.indexOf('    const resourceCatalogRequests = new Map();');
const end = source.indexOf('    function openPvDocumentEmbedPicker(', start);
assert(start >= 0 && end > start);

class Node {
    constructor() { this.isConnected = true; this.attributes = {}; this.children = []; }
    setAttribute(key, value) { this.attributes[key] = value; }
    removeAttribute(key) { delete this.attributes[key]; }
    querySelector() { return this; }
    prepend(child) { this.children.unshift(child); child.parent = this; }
    remove() { if (this.parent) this.parent.children = this.parent.children.filter(child => child !== this); }
}

const requests = [], errors = [];
const context = vm.createContext({
    root: new Node(),
    document: {createElement: () => new Node()},
    pageConfig: {resourceCatalogUrl: '/resources.php?oid=1&id=2', resourceCatalogUi: {loading: 'Loading', error: 'Failed'}},
    indicatorValueUi: {allowedIndicatorIds: [4]},
    window: {omoNotify: (message, type) => errors.push([message, type])},
    fetch: (url, options) => new Promise(resolve => requests.push({url, options, resolve}))
});
vm.runInContext(source.slice(start, end), context);
const load = context.loadPvResourceCatalog;
const respond = (index, items, ok = true) => requests[index].resolve({ok, json: async () => ({status: true, items})});

(async () => {
    assert.equal(requests.length, 0, 'Editor initialization must not fetch a catalogue.');
    const first = new Node(), second = new Node();
    const items = [{id: 999}], otherItems = [];
    let renders = 0;
    const one = load('documents', items, first, () => renders++);
    const two = load('documents', otherItems, second, () => {});
    assert.equal(requests.length, 1, 'Concurrent opens share the in-flight request.');
    assert.equal(items.length, 0, 'Stale results are not selectable while loading.');
    assert.equal(first.attributes['aria-busy'], 'true');
    assert.equal(requests[0].options.credentials, 'same-origin');
    respond(0, [{id: 7, title: 'Visible'}]);
    await Promise.all([one, two]);
    assert.deepEqual(items, [{id: 7, title: 'Visible'}]);
    assert.deepEqual(otherItems, items);
    assert.equal(renders, 2);
    assert.equal(first.children.length, 0, 'Progress is cleared on success.');
    assert.equal(first.attributes['aria-busy'], undefined);

    const reopened = load('documents', items, first, () => {});
    assert.equal(requests.length, 2, 'Reopening revalidates current rights and data.');
    respond(1, [], false);
    await reopened;
    assert.deepEqual(errors, [['Failed', 'error']]);
    assert.equal(items.length, 0);
    assert.equal(first.children.length, 0, 'Progress is cleared on failure.');
    const retry = load('documents', items, first, () => {});
    respond(2, [{id: 8}]);
    await retry;
    assert.equal(items[0].id, 8, 'Failures do not poison subsequent requests.');

    const closed = new Node(), discardedItems = [];
    let discardedRenders = 0;
    const pending = load('events', discardedItems, closed, () => discardedRenders++);
    closed.isConnected = false;
    respond(3, [{id: 88}]);
    await pending;
    assert.equal(discardedRenders, 1, 'A late response cannot modify a closed picker.');
    assert.equal(discardedItems.length, 0);

    const indicators = [];
    const indicatorRequest = load('indicators', indicators, new Node(), () => {});
    respond(4, [{id: 5, canAddValue: true}, {id: 6, canAddValue: false}, {id: 7, kind: 'group', canAddValue: true}]);
    await indicatorRequest;
    assert.deepEqual(context.indicatorValueUi.allowedIndicatorIds, [4, 5]);
    assert.equal(errors.length, 1);

    // The shared project picker copies its input; async replacement must update
    // that copy, restore the edited project and retain filtering.
    class Element extends Node {
        querySelectorAll() { return []; }
        addEventListener() {}
        removeEventListener() {}
        appendChild(child) { this.children.push(child); }
        set innerHTML(value) { this.children = []; }
    }
    class Select extends Element {}
    const projectWindow = {};
    vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../common/project-picker/project-picker.js'), 'utf8'), {
        window: projectWindow, Element, HTMLSelectElement: Select,
        document: {createElement: () => new Element()}
    });
    const select = new Select(), search = new Element();
    const controller = projectWindow.commonMountProjectPicker({root: new Element(), selectElement: select, searchInput: search, projects: [], selectedIds: [2]});
    controller.setProjects([{id: 1, title: 'Alpha'}, {id: 2, title: 'Beta'}], [2]);
    assert.equal(select.value, '2', 'Editing an existing project retains its selection after loading.');
    search.value = 'Alpha';
    controller.refresh();
    assert.equal(select.children.length, 1);
    assert.equal(select.value, '1');
    controller.destroy();
    controller.setProjects([{id: 3, title: 'Late'}]);
    assert.equal(select.value, '1', 'A destroyed picker ignores late updates.');
    console.log('pv_resource_catalog_test: OK');
})().catch(error => { console.error(error); process.exitCode = 1; });
