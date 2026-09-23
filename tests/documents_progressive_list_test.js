'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

function environment(withObserver = true) {
    const intersections = [];
    const mutations = [];
    const document = {createElement: () => new Element()};
    class Element {
        constructor() { this.children = []; this.listeners = {}; this.ownerDocument = document; this.parent = null; }
        get isConnected() { return this === document.documentElement || !!this.parent?.isConnected; }
        appendChild(node) { node.remove(); this.children.push(node); node.parent = this; }
        insertBefore(node, before) { node.remove(); this.children.splice(this.children.indexOf(before), 0, node); node.parent = this; }
        remove() { if (this.parent) this.parent.children.splice(this.parent.children.indexOf(this), 1); this.parent = null; }
        setAttribute() {}
        addEventListener(name, fn) { this.listeners[name] = fn; }
        removeEventListener(name) { delete this.listeners[name]; }
    }
    document.documentElement = new Element();
    const window = {
        MutationObserver: class {
            constructor(callback) { this.callback = callback; mutations.push(this); }
            observe() {}
            disconnect() { this.disconnected = true; }
        }
    };
    if (withObserver) window.IntersectionObserver = class {
        constructor(callback) { this.callback = callback; intersections.push(this); }
        observe(target) { this.target = target; }
        unobserve() {}
        disconnect() { this.disconnected = true; }
    };
    vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../omo/api/documents/progressive-list.js'), 'utf8'), {window});
    function mount(items, initialCount) {
        const container = new Element();
        document.documentElement.appendChild(container);
        const rendered = [];
        const counts = [];
        const controller = window.omoCreateDocumentProgressiveList({
            container, items, initialCount, moreLabel: 'More',
            appendItem(item, sentinel) {
                const node = new Element(); node.item = item;
                container.insertBefore(node, sentinel); rendered.push(node);
            },
            afterAppend(count) { counts.push(count); }
        });
        return {container, controller, rendered, counts};
    }
    return {mount, intersections, mutations};
}

const items = Array.from({length: 400}, (_, index) => index + 1);
const env = environment();
const list = env.mount(items);
assert.equal(list.rendered.length, 30, 'Only 30 of 400 rows are constructed initially.');
const first = list.rendered[0];
const observer = env.intersections[0];
observer.callback([{isIntersecting: false}]);
assert.equal(list.rendered.length, 30);
observer.callback([{isIntersecting: true}]);
assert.equal(list.rendered.length, 60);
assert.equal(list.rendered[0], first, 'Existing DOM nodes are retained.');
observer.target.listeners.click({type: 'click'});
assert.equal(list.rendered.length, 90, 'Keyboard/click fallback appends a batch.');
while (list.rendered.length < 400) observer.callback([{isIntersecting: true}]);
assert.deepEqual(list.rendered.map(node => node.item), items, 'Stable order, no duplicates or omissions.');
assert.equal(list.container.children.length, 400, 'Sentinel removed at completion.');
assert.equal(observer.disconnected, true);
assert.deepEqual(list.counts, [30, 60, 90, 120, 150, 180, 210, 240, 270, 300, 330, 360, 390, 400]);

const preserved = env.mount(items, 90);
assert.equal(preserved.rendered.length, 90, 'Folder refresh may preserve the visible range.');
preserved.controller.destroy();
env.intersections.at(-1).callback([{isIntersecting: true}]);
assert.equal(preserved.rendered.length, 90, 'Old observer callbacks cannot append after rerender.');
const filtered = env.mount(items.filter(id => id === 399));
assert.equal(filtered.rendered[0].item, 399, 'Filtering before batching finds off-screen data.');
assert.equal(filtered.container.children.length, 1);
const detached = env.mount(items);
detached.container.remove();
env.mutations.at(-1).callback([{removedNodes: [detached.container]}]);
assert.equal(env.intersections.at(-1).disconnected, true, 'Detached lists release their observers.');
const fallback = environment(false).mount(items);
fallback.container.children.at(-1).listeners.click({type: 'click'});
assert.equal(fallback.rendered.length, 60, 'Works without IntersectionObserver.');

// A cold drawer reload must not depend on an asynchronously inserted external script.
const page = fs.readFileSync(path.join(__dirname, '../omo/api/documents/index.php'), 'utf8');
const helper = fs.readFileSync(path.join(__dirname, '../omo/api/documents/progressive-list.js'), 'utf8');
const inlineHelper = /<\?php[^?]*readfile\(__DIR__ \. '\/progressive-list\.js'\); \?>/;
assert(inlineHelper.test(page), 'The helper is embedded synchronously in the drawer bootstrap.');
const renderedPage = page.replace(inlineHelper, () => helper);
const bootstrap = Array.from(renderedPage.matchAll(/<script\b([^>]*)>([\s\S]*?)<\/script>/g))
    .find(match => match[2].includes('window.omoDocumentsFindRoot = function'))[2];
const bootstrapStart = bootstrap.indexOf('            (function () {');
assert(bootstrapStart > 0);
const coldWindow = {};
vm.runInNewContext(bootstrap.slice(0, bootstrapStart), {window: coldWindow});
assert.equal(typeof coldWindow.omoCreateDocumentProgressiveList, 'function', 'Helper exists before cold panel initialization.');

// Parse the PHP-rendered inline JavaScript, replacing the remaining PHP expressions.
for (const match of renderedPage.matchAll(/<script\b([^>]*)>([\s\S]*?)<\/script>/g)) {
    if (!match[1].includes('application/json')) new vm.Script(match[2].replace(/<\?[\s\S]*?\?>/g, 'null'));
}
assert(page.includes('foreach (array_slice($documentEntries, 0, 30) as $entry)'), 'Server HTML is bounded too.');
console.log('documents_progressive_list_test: OK (400 rows, batches, fallback, reset, cleanup, cold bootstrap, inline syntax)');
