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
let now = Date.parse('2026-10-16T09:00:00Z');
class ClockDate extends Date {
    constructor(...args) { super(...(args.length ? args : [now])); }
    static now() { return now; }
}
const nodes = {};
for (const name of ['center', 'legend-buffer', 'buffer-label', 'buffer-swatch', 'outer-buffer']) {
    nodes['[data-omo-pv-timing-' + name + ']'] = {
        textContent: '', attributes: {}, style: {},
        setAttribute(key, value) { this.attributes[key] = value; }
    };
}
let points = [{durationMinutes: 40, isHandled: false}, {durationMinutes: 30, isHandled: false}];
const context = vm.createContext({
    Date: ClockDate, eventStartAtIso: '2026-10-16T10:00:00Z', eventEndAtIso: '2026-10-16T11:00:00Z',
    root: {querySelector: selector => nodes[selector] || null},
    collectTimingPointState: () => points,
    formatMinutesLabel: minutes => Math.max(0, Math.round(minutes)) + ' min',
    notStartedValue: '--', marginLegendLabel: 'Marge', overrunLegendLabel: 'Depassement',
    buildDonutSegmentPath: (start, end) => end > start ? 'segment' : ''
});
vm.runInContext(extract('getMeetingDurationMinutes', 'collectTimingPointState')
    + extract('renderTimingSummary', 'mountEditableCard'), context);
function expectBuffer(label, minutes, message) {
    context.renderTimingSummary();
    assert.equal(nodes['[data-omo-pv-timing-buffer-label]'].textContent, label, message);
    assert.equal(nodes['[data-omo-pv-timing-legend-buffer]'].textContent, minutes + ' min', message);
    assert.equal(nodes['[data-omo-pv-timing-buffer-swatch]'].style.background, label === 'Marge' ? '#cbd5e1' : '#ef4444');
    assert.equal(nodes['[data-omo-pv-timing-outer-buffer]'].attributes.fill, label === 'Marge' ? '#cbd5e1' : '#ef4444');
    assert.equal(nodes['[data-omo-pv-timing-outer-buffer]'].attributes.d, minutes > 0 ? 'segment' : '');
}
expectBuffer('Depassement', 10, 'Before the meeting: 70 planned minutes in a 60-minute slot.');
context.eventEndAtIso = '2026-10-16T11:30:00Z';
expectBuffer('Marge', 20, 'Before the meeting: 70 planned minutes in a 90-minute slot.');
context.eventEndAtIso = '2026-10-16T11:00:00Z';
points[0].isHandled = true;
now = Date.parse('2026-10-16T10:00:00Z');
expectBuffer('Marge', 30, 'At the start: only unfinished points consume the remaining budget.');
now = Date.parse('2026-10-16T10:40:00Z');
expectBuffer('Depassement', 10, 'Live clock: 30 minutes of points with 20 minutes left.');
now = Date.parse('2026-10-16T10:50:00Z');
expectBuffer('Depassement', 20, 'Live clock: 30 minutes of points with 10 minutes left.');
assert.equal(nodes['[data-omo-pv-timing-center]'].textContent, '10 min');
points[1].isHandled = true;
expectBuffer('Marge', 10, 'Completing a point immediately updates the live margin.');
points[1].isHandled = false;
context.eventEndAtIso = '2026-10-16T11:20:00Z';
expectBuffer('Marge', 0, 'Extending the meeting restores a balanced live budget.');
context.eventEndAtIso = '2026-10-16T11:30:00Z';
expectBuffer('Marge', 10, 'A further extension creates real reserve time.');
context.eventEndAtIso = '2026-10-16T11:00:00Z';
now = Date.parse('2026-10-16T11:10:00Z');
expectBuffer('Depassement', 40, 'After the end: 10 minutes elapsed overtime plus 30 minutes unfinished.');
assert.equal(nodes['[data-omo-pv-timing-center]'].textContent, '0 min', 'Countdown stays at zero after the end.');
context.eventStartAtIso = '';
context.eventEndAtIso = '';
expectBuffer('Marge', 0, 'Without a schedule, no clock-based overrun is invented.');
context.eventStartAtIso = 'invalid';
context.eventEndAtIso = 'invalid';
expectBuffer('Marge', 0, 'Invalid dates preserve the unscheduled fallback.');
assert.equal(context.getTimerRemainingMeetingMinutes(0, 70), 70);
console.log('pv_timing_margin_test: OK');
