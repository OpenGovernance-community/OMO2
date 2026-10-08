'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const source = fs.readFileSync(path.join(__dirname, '../omo/api/documents/pv/editor.js'), 'utf8');
function productionFunction(name) {
    const start = source.indexOf('    function ' + name + '(');
    const end = source.indexOf('\n    function ', start + 1);
    assert(start >= 0 && end > start);
    return source.slice(start, end);
}
class Element {
    constructor() {
        this.hidden = false;
        this.classes = new Set();
        this.attributes = {};
        this.classList = {toggle: (name, enabled) => enabled ? this.classes.add(name) : this.classes.delete(name)};
    }
    getAttribute(name) { return this.attributes[name]; }
    setAttribute(name, value) { this.attributes[name] = value; }
}
const context = {Element, HTMLElement: Element, HTMLButtonElement: Element, HTMLDetailsElement: Element,
    HTMLInputElement: Element, HTMLTextAreaElement: Element,
    currentUserId: 7, pageConfig: {}, syncEventExtensionUi() {}, syncDocumentMetadataUi() {},
    documentMetadataIsDirty: () => true, closeAddMenu() {}, canManageApplicationTabs: true,
    activeApplicationTabId: 5, applicationTabsById: new Map([[5, {tabId: 5}]]),
    currentDocumentPayload: {pvStage: 'meeting', canManagePvStructure: true}};
// Optional controls are absent, as in a minimal embedded PV editor.
for (const [, name] of source.matchAll(/const (\w+) = root\.querySelector\(/g)) context[name] = null;
const add = new Element(), nav = new Element(), workspace = new Element(), panel = new Element(), main = new Element();
const pvTab = new Element(), appTab = new Element();
pvTab.attributes['data-omo-pv-application-tab'] = '0';
appTab.attributes['data-omo-pv-application-tab'] = '5';
panel.attributes['data-omo-pv-application-panel'] = '5';
nav.querySelector = () => add;
nav.querySelectorAll = () => [pvTab, appTab];
workspace.querySelectorAll = () => [panel];
main.hidden = true;
Object.assign(context, {root: new Element(), applicationTabsNav: nav, applicationWorkspace: workspace,
    pvEditorSwitchableSurfaces: [main], addMenu: new Element(), addButton: new Element()});
vm.createContext(context);
vm.runInContext(productionFunction('setActiveApplicationTab') + productionFunction('syncPvEditorUi'), context);
context.syncPvEditorUi(context.currentDocumentPayload);
assert.equal(nav.hidden, false);
assert.equal(add.hidden, false);
context.currentDocumentPayload = {pvStage: 'review', canManagePvStructure: true};
context.syncPvEditorUi(context.currentDocumentPayload);
assert.equal(nav.hidden, true, 'Polling hides all application tabs in review.');
assert.equal(add.hidden, true, 'The picker cannot remain available.');
assert.equal(context.canManageApplicationTabs, false);
assert.equal(context.root.classes.has('omo-pv-editor--has-application-tabs'), false, 'No empty tab row remains.');
assert.equal(main.hidden, false, 'An active app returns to the PV.');
assert.equal(workspace.hidden, true);
assert.equal(panel.hidden, true);
assert.equal(context.addMenu.hidden, true);
context.setActiveApplicationTab(5);
assert.equal(context.activeApplicationTabId, 0, 'A late application callback cannot reopen an app in review.');
console.log('pv_review_tabs_test: OK');
