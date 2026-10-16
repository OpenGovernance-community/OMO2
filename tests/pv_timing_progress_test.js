'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const source = fs.readFileSync(path.join(__dirname, '../omo/api/documents/pv/editor.js'), 'utf8');
function extract(startName, nextName) {
    const start = source.indexOf('    function ' + startName + '(');
    const end = source.indexOf('    function ' + nextName + '(', start);
    assert.ok(start >= 0 && end > start);
    return source.slice(start, end);
}
const node = () => ({textContent: '', attributes: {},
    setAttribute(name, value) { this.attributes[name] = value; },
    getAttribute(name) { return this.attributes[name]; }});
const center = node(), count = node(), chart = node();
const checkbox = {checked: true};
const payloads = {};
for (let id = 1; id <= 20; id++) {
    payloads[id] = {isHandled: id === 2, desiredDurationMinutes: id === 1 ? 5 : 0};
}
payloads[21] = {isGroup: true};
payloads[22] = {isMoved: true};
payloads[23] = {isAbandoned: true};
const context = vm.createContext({
    currentPointPayloads: payloads,
    // Most points have no visible card or checkbox, including completed point 2.
    root: {querySelector(selector) {
        if (selector === '[data-omo-pv-timing-center]') return center;
        if (selector === '[data-omo-pv-timing-point-count]') return count;
        if (selector === '[data-omo-pv-timing-chart="1"]') return chart;
        if (selector === '[data-omo-pv-point-handled="1"]') return checkbox;
        return null;
    }},
    pageConfig: {pointProgressLabel: '{handled}/{total} points traites'},
    eventStartAtIso: '',
    getMeetingDurationMinutes: () => 60, getTimerRemainingMeetingMinutes: () => 45,
    formatMinutesLabel: minutes => minutes + ' min', notStartedValue: '--',
    marginLegendLabel: 'Marge', overrunLegendLabel: 'Depassement'
});
vm.runInContext(extract('collectTimingPointState', 'polarPoint')
    + extract('renderTimingSummary', 'mountEditableCard'), context);
context.renderTimingSummary();
assert.equal(count.textContent, '2/20', 'Includes offscreen and zero-duration points, excludes groups and transferred/abandoned points.');
assert.equal(center.textContent, '45 min', 'Remaining time is retained alongside the count.');
assert.equal(count.getAttribute('aria-label'), '2/20 points traites');
assert.equal(chart.getAttribute('title'), '45 min | 2/20 points traites');
checkbox.checked = false;
context.renderTimingSummary();
assert.equal(count.textContent, '1/20', 'Unchecking immediately updates progress.');
payloads[3].isHandled = true;
context.renderTimingSummary();
assert.equal(count.textContent, '2/20', 'Remote updates to offscreen points update progress.');
context.currentPointPayloads = {};
context.getMeetingDurationMinutes = () => 0;
context.renderTimingSummary();
assert.equal(count.textContent, '0/0', 'Empty agenda has a defined counter.');
assert.equal(center.textContent, '--', 'Missing duration does not suppress the point counter.');
console.log('pv_timing_progress_test: OK');
