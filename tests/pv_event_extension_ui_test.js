'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const source = fs.readFileSync(path.join(__dirname, '../omo/api/documents/pv/editor.js'), 'utf8');
const start = source.indexOf('    function applyAssociatedEventSchedule(');
const end = source.indexOf('    function getTimerRemainingMeetingMinutes(', start);
assert.ok(start >= 0 && end > start, 'Use the actual schedule and extension UI functions.');

class Element {}
class HTMLElement extends Element {}
class HTMLButtonElement extends HTMLElement {}
const menu = new HTMLElement(), toggle = new HTMLButtonElement(), schedule = new Element();
let closeCount = 0;
const context = vm.createContext({
    Element, HTMLElement, HTMLButtonElement,
    eventExtensionMenu: menu, eventExtensionToggle: toggle, eventScheduleValue: schedule,
    eventStartAtIso: '2020-01-01T09:00:00Z', eventEndAtIso: '2020-01-01T10:00:00Z',
    closeEventExtensionMenu() { closeCount++; }
});
vm.runInContext(source.slice(start, end), context);
for (const stage of ['preparation', 'meeting', 'review', 'validated', 'unknown']) {
    for (const canManage of [true, false]) {
        context.syncEventExtensionUi({pvStage: stage, canManagePvDocument: canManage});
        const allowed = canManage && ['preparation', 'meeting'].includes(stage);
        assert.equal(menu.hidden, !allowed, 'Menu availability for ' + stage);
        assert.equal(toggle.disabled, !allowed, 'Button availability for ' + stage);
    }
}
assert.ok(closeCount > 0, 'Leaving an editable stage closes the extension menu.');
context.applyAssociatedEventSchedule({startAt: '2020-01-01T09:00:00Z', endAt: '2020-01-01T10:15:00Z', scheduleLabel: '09:00 - 10:15'});
assert.equal(context.getMeetingDurationMinutes(), 75, 'The timer uses the extended schedule.');
assert.equal(schedule.textContent, '09:00 - 10:15', 'The displayed schedule updates with the server response.');
context.syncEventExtensionUi({pvStage: 'meeting', canManagePvDocument: true});
assert.equal(toggle.disabled, false, 'An elapsed planned end does not disable another extension.');
context.syncEventExtensionUi({pvStage: 'review', canManagePvDocument: true});
assert.equal(toggle.disabled, true, 'A live transition to review immediately disables extensions.');
console.log('pv_event_extension_ui_test: OK');
