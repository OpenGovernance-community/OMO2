'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const source = fs.readFileSync(path.join(__dirname, '../omo/api/documents/pv/editor.js'), 'utf8');
const start = source.indexOf('    function replacePointProposalsHtml(');
const end = source.indexOf('\n    function renderPointCollection(', start);
assert.ok(start >= 0 && end > start);

// Model only the DOM boundary touched by the real refresh controller.
class Element {
    constructor(kind, html = '') {
        this.kind = kind;
        this.html = html;
        this.children = [];
        this.parentNode = null;
    }
    appendChild(child) { this.insertBefore(child, null); }
    insertBefore(child, reference) {
        if (child.parentNode) child.remove();
        const index = reference ? this.children.indexOf(reference) : this.children.length;
        assert.ok(index >= 0);
        this.children.splice(index, 0, child);
        child.parentNode = this;
    }
    replaceWith(next) {
        const parent = this.parentNode;
        parent.insertBefore(next, this);
        this.remove();
    }
    remove() {
        this.parentNode.children.splice(this.parentNode.children.indexOf(this), 1);
        this.parentNode = null;
    }
    querySelector(selector) {
        const kind = selector.includes('point-card') ? 'card'
            : selector.includes('point-proposals') ? 'proposals' : 'footer';
        return this.children.find(child => child.kind === kind) || null;
    }
    set innerHTML(html) {
        this.children = [];
        if (!html.includes('data-omo-pv-point-card="1"')) return;
        const card = new Element('card');
        this.appendChild(card);
        if (html.includes('data-omo-pv-point-proposals')) card.appendChild(new Element('proposals', html));
    }
}

const root = new Element('root');
const card = new Element('card');
root.appendChild(card);
const editor = new Element('summernote');
editor.content = '<p>Unsaved text typed before opening the proposal modal</p>';
editor.undoHistory = ['old text', editor.content];
card.dirty = true;
const title = new Element('input');
title.value = 'Unsaved title';
const footer = new Element('footer');
card.appendChild(title);
card.appendChild(editor);
card.appendChild(footer);
const initialChildren = card.children.slice();
const hydrated = [];
const handlers = {};
const requests = [];
let response;
let failure;
const alerts = [];
const context = {
    Element, root, document: {createElement: () => new Element('temp')},
    hydrateDeferredProposalDetails: node => hydrated.push(node),
    window: {addEventListener: (name, handler) => { handlers[name] = handler; }, alert: message => alerts.push(message)},
    editorClientUi: {genericError: 'Refresh failed'},
    postPointAction: (action, id) => {
        requests.push({action, id});
        return failure ? Promise.reject(failure) : Promise.resolve({point: response});
    }
};
vm.createContext(context);
vm.runInContext(source.slice(start, end), context);
const payload = text => ({id: 1, cardHtml: '<article data-omo-pv-point-card="1">'
    + (text === null ? '' : '<section data-omo-pv-point-proposals>' + text + '</section>') + '</article>'});
const settle = () => new Promise(resolve => setImmediate(resolve));
function assertDraft() {
    assert.equal(root.children[0], card, 'Keep the same point card.');
    assert.equal(editor.parentNode, card, 'Keep the mounted Summernote instance.');
    assert.equal(editor.content, '<p>Unsaved text typed before opening the proposal modal</p>');
    assert.deepEqual(editor.undoHistory, ['old text', editor.content], 'Keep editor undo history.');
    assert.equal(title.value, 'Unsaved title');
    assert.equal(card.dirty, true, 'Keep the pending manual save state.');
}

(async function () {
    // First proposal, including a point that had no proposal section before.
    response = payload('First proposal');
    handlers['omo-deferred-proposal-saved']({detail: {pointId: 1}});
    await settle();
    assert.equal(requests.length, 1);
    assert.deepEqual(requests[0], {action: 'refresh_point', id: 1});
    assert.equal(card.children[2].kind, 'proposals', 'Insert proposals before the footer.');
    assert.ok(card.children[2].html.includes('First proposal'));
    assert.equal(hydrated[0], card.children[2]);
    assertDraft();

    const oldProposals = card.children[2];
    response = payload('Edited proposal');
    handlers['omo-deferred-proposal-saved']({detail: {pointId: 1}});
    await settle();
    assert.equal(oldProposals.parentNode, null);
    assert.ok(card.children[2].html.includes('Edited proposal'));
    assertDraft();

    // Run the actual deletion success callback as well.
    const deletionStart = source.indexOf("            postPointAction('remove_deferred_proposal'");
    const deletionEnd = source.indexOf('\n            return;', deletionStart);
    context.pointId = 1;
    context.proposalId = 7;
    response = payload(null);
    vm.runInContext(source.slice(deletionStart, deletionEnd), context);
    await settle();
    assert.equal(requests.at(-1).action, 'remove_deferred_proposal');
    assert.deepEqual(card.children, initialChildren, 'Removing the last section keeps all point fields.');
    assertDraft();

    // Malformed responses and events for other documents must leave the editor alone.
    context.replacePointProposalsHtml({id: 1, cardHtml: ''});
    assert.deepEqual(card.children, initialChildren);
    root.children = [];
    const previousRequests = requests.length;
    handlers['omo-deferred-proposal-saved']({detail: {pointId: 1}});
    handlers['omo-deferred-proposal-saved']({detail: {pointId: 0}});
    assert.equal(requests.length, previousRequests);
    root.children = [card];
    failure = {};
    handlers['omo-deferred-proposal-saved']({detail: {pointId: 1}});
    await settle();
    assert.deepEqual(alerts, ['Refresh failed']);
    assertDraft();
    console.log('pv_point_proposals_refresh_test: OK');
})().catch(error => { console.error(error); process.exitCode = 1; });
