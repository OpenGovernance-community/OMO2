'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const source = fs.readFileSync(path.join(__dirname, '../omo/api/calendar/calendar.js'), 'utf8');
const context = {window: {}};
vm.runInNewContext(source, context);
const panels = [];
const root = {
    querySelector: () => ({appendChild: node => panels.push(node)}),
    ownerDocument: {createElement: () => ({attributes: {}, setAttribute(key, value) { this.attributes[key] = value; }})}
};
const event = {id: 10, title: '<script>alert(1)</script>', status: 'confirmed', timeLabel: '10:00 - 11:00',
    documentUrl: '/document?id=10&x="', documentTitle: 'PV', documentPvEditorUrl: '/editor?id=10',
    holonLabel: 'Team', description: 'Description', weekdayLabel: 'Mer', dateLabel: '23', statusLabel: '',
    canEdit: true, canDelete: true, editUrl: '/edit', deleteUrl: '/delete', hasAssociatedDocuments: true,
    startMinute: 600, endMinute: 660, column: 1, columnCount: 2};
const external = {...event, id: -1, isExternal: true, externalColor: '#abcdef', externalDrawerData: {title: '<unsafe " & text>'}};
const other = {...event, id: 11, isOtherOrganization: true, isFaded: true, documentUrl: '', title: 'Other organization'};
const day = {dayKey: '2026-09-23', label: 'Mer 23', isToday: true, count: 3, countLabel: '3 events', allDay: [1], timed: [0, 2]};
const views = {
    month: {title: 'September', subtitle: '4 events', prevUrl: '/previous', nextUrl: '/next', days: [{...day, items: [0, 1, 0, 1], more: '+1 more'}]},
    week: {title: 'Week', subtitle: '3 events', count: 3, columnCount: 1, days: [day]},
    day: {title: 'Day', subtitle: '3 events', count: 3, columnCount: 1, days: [day]},
    list: {sections: [{label: 'Today', items: [0, 1]}]}
};
const payload = {views: {contextual: views, children: {...views, list: {sections: []}}}, items: [event, external, other], labels: {}, weekdays: ['Mon'], hours: ['00:00', '01:00']};
const ensure = context.omoCreateCalendarViews(root, payload);
assert.equal(panels.length, 0, 'No eager view construction.');
const month = ensure('month', 'contextual');
assert.equal(panels.length, 1, 'Only the requested view is built.');
assert(month.innerHTML.includes('data-omo-calendar-overflow-event'));
assert(month.innerHTML.includes('data-omo-calendar-more'));
assert(!month.innerHTML.includes('<script>'));
assert(month.innerHTML.includes('&lt;script&gt;'));
assert(month.innerHTML.includes('&quot;'));
assert.equal(ensure('month', 'contextual'), null, 'Reusing a view never reconstructs its DOM or listeners.');
assert.equal(panels.length, 1);
const week = ensure('week', 'contextual');
assert.equal(panels.length, 2);
assert(week.innerHTML.includes('data-omo-calendar-now-indicator'));
assert(week.innerHTML.includes('data-omo-calendar-other-organization'));
assert(week.innerHTML.includes('data-omo-calendar-external-event-data'));
assert(week.innerHTML.includes('left:calc(50% + 4px)'));
assert(week.innerHTML.indexOf('omo-calendar__time-event-title') < week.innerHTML.indexOf('omo-calendar__time-event-time-row'));
assert.equal(ensure('day', 'contextual').attributes['data-omo-calendar-timeline-panel'], 'day');
const list = ensure('list', 'contextual');
assert(list.innerHTML.includes('data-omo-calendar-open-pv-editor-url'));
assert(list.innerHTML.includes('data-omo-calendar-open-edit-url'));
assert(list.innerHTML.includes('data-omo-calendar-delete-has-documents="1"'));
assert(ensure('list', 'children').innerHTML.includes('data-omo-calendar-default-empty'));
assert.equal(ensure('month', 'forbidden'), null, 'No unprovided scope can be rendered.');
const php = fs.readFileSync(path.join(__dirname, '../omo/api/calendar/index.php'), 'utf8');
assert(php.includes("hash_file('sha256', __DIR__ . '/calendar.js')"), 'Asset version changes with file content.');
assert(php.includes('JSON_HEX_TAG'), 'Inline JSON cannot terminate its script element.');
assert(!php.includes('data-omo-calendar-view-panel='), 'PHP no longer builds hidden interfaces.');
assert(source.includes('if (newPanel) bindCalendarView(newPanel)'), 'New views receive their interaction handlers.');
console.log('calendar_lazy_views_test: OK (lazy rendering, cache, four views, permissions, escaping, versioning)');
