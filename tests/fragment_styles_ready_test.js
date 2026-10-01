'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

class Element extends EventTarget {
    constructor() {
        super();
        this.attrs = new Map();
        this.classes = new Set();
        this.classList = {
            add: name => this.classes.add(name),
            remove: name => this.classes.delete(name),
            contains: name => this.classes.has(name)
        };
        this.links = [];
        this.scripts = [];
        this.isConnected = true;
    }
    setAttribute(name, value) { this.attrs.set(name, value); }
    getAttribute(name) { return this.attrs.has(name) ? this.attrs.get(name) : null; }
    removeAttribute(name) { this.attrs.delete(name); }
    get attributes() { return [...this.attrs].map(([name, value]) => ({ name, value })); }
    get src() { return this.getAttribute('src'); }
    querySelectorAll(selector) { return selector === 'script' ? this.scripts : this.links; }
    replaceChild(replacement, original) {
        this.replacement = replacement;
        if (original.onRun) original.onRun();
    }
}

function environment() {
    const timers = new Map();
    let timerId = 0;
    const document = {
        head: new Element(),
        readyState: 'loading',
        addEventListener() {},
        createElement() { return new Element(); }
    };
    const window = {
        addEventListener() {},
        matchMedia: media => ({ matches: media !== 'print', addEventListener() {} }),
        setTimeout(callback) { timers.set(++timerId, callback); return timerId; },
        clearTimeout(id) { timers.delete(id); }
    };
    const source = fs.readFileSync(path.join(__dirname, '../common/assets/components.js'), 'utf8');
    vm.runInNewContext(source, { document, window, console });
    return { document, window, timers };
}

function stylesheet() {
    const link = new Element();
    link.href = '/screen.css';
    return link;
}

function script(host, onRun, src) {
    const node = new Element();
    node.parentNode = host;
    node.onRun = onRun;
    if (src) node.setAttribute('src', src);
    host.scripts.push(node);
    return node;
}

function pending(host) { return host.classList.contains('generic-fragment-pending'); }
function flush() { return new Promise(resolve => setImmediate(resolve)); }

async function run() {
    {
        const { window, document, timers } = environment();
        const host = new Element();
        const css = stylesheet();
        const widgetCss = stylesheet();
        host.links.push(css);
        let initialized = false;
        script(host, () => {
            initialized = true;
            document.head.links.push(widgetCss);
        });
        const ready = window.commonExecuteFragmentScripts(host);
        assert(pending(host), 'A first visit must conceal the fragment synchronously.');
        assert.equal(host.getAttribute('aria-busy'), 'true');
        await flush();
        assert.equal(initialized, false, 'Initialization must wait for the screen CSS.');
        css.sheet = {};
        css.dispatchEvent(new Event('load'));
        await flush();
        assert(initialized);
        assert(pending(host), 'Widget CSS added during initialization must also finish.');
        widgetCss.sheet = {};
        widgetCss.dispatchEvent(new Event('load'));
        await ready;
        assert.equal(pending(host), false);
        assert.equal(host.getAttribute('aria-busy'), null);
        assert.equal(timers.size, 0, 'Completed loads must release timers.');
    }
    {
        const { window, timers } = environment();
        const host = new Element();
        const cached = stylesheet();
        cached.sheet = {};
        const print = stylesheet();
        print.media = 'print';
        host.links.push(cached, print);
        await window.commonExecuteFragmentScripts(host);
        assert.equal(pending(host), false, 'Cached and irrelevant CSS must not add a delay.');
        assert.equal(timers.size, 0);
    }
    {
        const { window, timers } = environment();
        const host = new Element();
        const failed = stylesheet();
        host.links.push(failed);
        const ready = window.genericAwaitStylesheets(host);
        failed.dispatchEvent(new Event('error'));
        await ready;
        await window.commonExecuteFragmentScripts(host);
        assert.equal(pending(host), false, 'An error must not leave the screen concealed.');
        assert.equal(timers.size, 0, 'A known failure must not wait for another timeout.');
        failed.href = '/replacement.css';
        const replacementReady = window.genericAwaitStylesheets(host);
        await flush();
        assert(pending(host), 'Changing a link URL must wait for the new stylesheet.');
        failed.dispatchEvent(new Event('load'));
        await replacementReady;
        assert.equal(pending(host), false);
    }
    {
        const { window } = environment();
        const host = new Element();
        host.setAttribute('aria-busy', 'false');
        const oldCss = stylesheet();
        host.links = [oldCss];
        const older = window.genericAwaitStylesheets(host);
        const newCss = stylesheet();
        host.links = [newCss];
        const newer = window.genericAwaitStylesheets(host);
        oldCss.dispatchEvent(new Event('load'));
        await older;
        assert(pending(host), 'A stale request must not reveal the newer content.');
        newCss.dispatchEvent(new Event('load'));
        await newer;
        assert.equal(pending(host), false);
        assert.equal(host.getAttribute('aria-busy'), 'false', 'The original busy state must be restored.');
    }
    {
        const { window } = environment();
        const host = new Element();
        script(host, null, '/missing.js');
        const ready = window.commonExecuteFragmentScripts(host);
        await flush();
        assert(pending(host));
        host.replacement.dispatchEvent(new Event('error'));
        await assert.rejects(ready, /Unable to load page script/);
        assert.equal(pending(host), false, 'A script error must release the display gate.');
    }
    {
        const { window, timers } = environment();
        const host = new Element();
        host.links.push(stylesheet());
        const ready = window.genericAwaitStylesheets(host);
        [...timers.values()].forEach(callback => callback());
        await ready;
        assert.equal(pending(host), false, 'An unresponsive resource must reach the fallback.');
        assert.equal(timers.size, 0);
    }
    console.log('Fragment style readiness: 6 scenarios passed.');
}

run().catch(error => { console.error(error); process.exitCode = 1; });
