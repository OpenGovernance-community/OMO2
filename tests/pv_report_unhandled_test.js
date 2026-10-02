'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const source = fs.readFileSync(path.join(__dirname, '../omo/api/documents/pv/editor.js'), 'utf8');
function editorFunction(name) {
    const start = source.indexOf('    function ' + name + '(');
    assert.ok(start >= 0, 'Missing editor function ' + name);
    const end = source.indexOf('\n    function ', start + 1);
    return source.slice(start, end);
}

const cards = new Map();
class Element {
    constructor(id = 0) { this.id = id; this.className = 'point'; this.parentNode = null; this.html = ''; }
    set innerHTML(value) {
        this.html = value;
        const match = /data-omo-pv-point-card="(\d+)"/.exec(value);
        this.firstElementChild = match ? new Element(Number(match[1])) : null;
    }
    get innerHTML() { return this.html; }
    querySelector() { return null; }
    querySelectorAll() { return []; }
    getAttribute(name) { return name === 'data-omo-pv-point-card' ? String(this.id) : null; }
    remove() { cards.delete(this.id); this.parentNode = null; }
}
const summary = new Element();
const pointsContainer = {
    querySelector(selector) { const match = /="(\d+)"/.exec(selector); return match ? cards.get(Number(match[1])) || null : null; },
    querySelectorAll() { return Array.from(cards.values()); },
    appendChild(card) { cards.set(card.id, card); card.parentNode = this; },
    replaceChild(next, previous) { previous.remove(); this.appendChild(next); }
};
const currentPointPayloads = {};
const knownPointSignatures = {};
const locallyEngagedPointIds = new Set([1]);
let backgroundStopped = false;
const context = {
    Element, HTMLElement: Element, document: { activeElement: null, createElement: () => new Element() },
    root: { querySelector: selector => selector === '[data-omo-pv-unhandled-points]' ? summary : pointsContainer.querySelector(selector) },
    pointsContainer, nav: { querySelector: () => null }, currentPointPayloads, knownPointSignatures,
    locallyEngagedPointIds, preMountEditorDrafts: new Map(), preMountEditorFocusPointIds: new Set(),
    activeLockPointIds: new Set(), pendingLockPointIds: new Set(), pendingUnlockPointIds: new Set(), yieldingTakeoverPointIds: new Set(),
    currentDocumentPayload: { pvStage: 'meeting' }, knownDocumentSyncVersion: '', applicationWorkspace: null,
    activeApplicationTabId: 0, attendanceEnabled: false,
    mergeKnownPointSignature: payload => { knownPointSignatures[payload.id] = payload.syncVersion; },
    mergeCurrentPointPayload: payload => { currentPointPayloads[payload.id] = payload; },
    replacePointNavHtml: payload => { currentPointPayloads[payload.id] = payload; },
    getOrderedPointIdsFromPayloads: () => Object.keys(currentPointPayloads).map(Number),
    pointHasRemoteChange: () => true,
    // A focused/dirty card would ordinarily be protected. Report omission must still remove it.
    isPointCardProtectedFromRemoteRefresh: () => true,
    isPointLockTakenOverRemotely: () => false,
    pvApplicationMeetingContextKey: () => '',
    stopPvEditorBackgroundWork: () => { backgroundStopped = true; }
};
for (const name of ['renderNavTreeFromPayloads', 'syncEmptyNavState', 'renderTimingSummary',
    'syncPendingTakeoverUi', 'mountEditableCard', 'hydrateDeferredProposalDetails',
    'captureMainScrollAnchor', 'captureFocusedEditor', 'applyPointOrderToNav', 'applyPointOrderToCards',
    'restoreMainScrollAnchor', 'restoreFocusedEditor', 'focusPointMoveButton',
    'applyAssociatedEventSchedule', 'syncDocumentStageUi', 'syncPvEditorUi', 'syncDocumentMetadataUi']) context[name] = () => {};
vm.createContext(context);
vm.runInContext(['replacePointHtml', 'renderPointCollection', 'mergeCurrentDocumentPayload'].map(editorFunction).join('\n'), context);

pointsContainer.appendChild(new Element(1));
currentPointPayloads[1] = { id: 1, isReportOmitted: false };
const omitted = { id: 1, isReportOmitted: true, cardHtml: '', navHtml: '', syncVersion: 'review' };
context.mergeCurrentDocumentPayload({ pvStage: 'review', unhandledPointsHtml: '<section><ul><li>Unfinished title</li></ul></section>' });
context.renderPointCollection([omitted], true);
assert.equal(cards.size, 0, 'Review removes an existing focused unfinished card.');
assert.equal(currentPointPayloads[1].isReportOmitted, true, 'Point identity remains tracked for later stage changes.');
assert.ok(summary.innerHTML.includes('Unfinished title'), 'Review renders the final title list.');
assert.equal(locallyEngagedPointIds.has(1), false, 'A hidden card is no longer locally engaged.');

context.mergeCurrentDocumentPayload({ pvStage: 'meeting', unhandledPointsHtml: '' });
context.renderPointCollection([{ id: 1, isReportOmitted: false, cardHtml: '<article data-omo-pv-point-card="1">Draft</article>', navHtml: 'Point', syncVersion: 'meeting' }], true);
assert.equal(cards.size, 1, 'Returning to meeting restores the unfinished agenda card.');
assert.equal(summary.innerHTML, '', 'Returning to meeting removes the report-only list.');

context.mergeCurrentDocumentPayload({ pvStage: 'validated', isPvValidated: true, unhandledPointsHtml: '<ul><li>Updated title</li></ul>' });
context.renderPointCollection([omitted], true);
assert.equal(cards.size, 0, 'Validation never restores an unfinished card.');
assert.ok(summary.innerHTML.includes('Updated title'), 'The title list follows server updates.');
assert.equal(backgroundStopped, true, 'Validation still stops editor background work.');
console.log('pv_report_unhandled_test: OK');
