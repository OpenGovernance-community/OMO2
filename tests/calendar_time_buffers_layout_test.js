'use strict';
// Run with Playwright and an optional browser executable: node tests/calendar_time_buffers_layout_test.js /path/to/playwright /path/to/chrome
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const {chromium} = require(process.argv[2] || 'playwright');
const root = path.resolve(__dirname, '..');
const context = {window: {}};
vm.runInNewContext(fs.readFileSync(path.join(root, 'omo/api/calendar/calendar.js'), 'utf8'), context);
const panels = [];
const host = {
    querySelector: () => ({appendChild: panel => panels.push(panel)}),
    ownerDocument: {createElement: () => ({setAttribute() {}})}
};
const parent = {id: 10, title: 'Rendez-vous', timeLabel: '10:00 - 11:00', status: 'confirmed',
    startMinute: 600, endMinute: 660, hasTimeBuffers: true, column: 0, columnCount: 1};
const before = {...parent, title: 'Preparation', bufferKind: 'before', startMinute: 585, endMinute: 600};
const after = {...parent, title: 'Cloture', bufferKind: 'after', startMinute: 660, endMinute: 675};
const day = {dayKey: '2030-01-07', label: 'Lun 7', count: 1, allDay: [], timed: [0, 1, 2]};
const timeline = {title: 'Calendrier', count: 1, columnCount: 1, days: [day]};
const ensure = context.omoCreateCalendarViews(host, {items: [parent, before, after],
    views: {contextual: {week: timeline, day: timeline}}, labels: {}, hours: {}, weekdays: []});

(async () => {
    const browser = await chromium.launch({headless: true, ...(process.argv[3] ? {executablePath: process.argv[3]} : {})});
    try {
        const page = await browser.newPage({viewport: {width: 1000, height: 1100}});
        await page.route('**/*', route => route.abort());
        for (const mode of ['week', 'day']) {
            const panel = ensure(mode, 'contextual');
            await page.setContent('<style>:root{--radius-md:6px}</style>' + panel.innerHTML);
            await page.addStyleTag({content: fs.readFileSync(path.join(root, 'omo/api/calendar/calendar.css'), 'utf8')});
            const sizes = await page.locator('.omo-calendar__time-column').evaluate(column => {
                const rect = node => {
                    const r = node.getBoundingClientRect();
                    const css = getComputedStyle(node);
                    return {top: r.top, bottom: r.bottom, height: r.height, width: r.width, text: node.textContent,
                        topRadius: css.borderTopLeftRadius, bottomRadius: css.borderBottomLeftRadius};
                };
                return {height: column.getBoundingClientRect().height,
                    before: rect(column.querySelector('[data-omo-calendar-time-buffer="before"]')),
                    parent: rect(column.querySelector('.omo-calendar__time-event:not([data-omo-calendar-time-buffer])')),
                    after: rect(column.querySelector('[data-omo-calendar-time-buffer="after"]'))};
            });
            const close = (a, b) => Math.abs(a - b) < 0.1;
            assert(close(sizes.before.height, sizes.height * 15 / 1440), mode + ': 15 minutes must render at actual height.');
            assert(close(sizes.after.height, sizes.before.height), mode + ': closing uses the same precise height.');
            assert(close(sizes.before.bottom, sizes.parent.top), mode + ': preparation cannot cover appointment text.');
            assert(close(sizes.parent.bottom, sizes.after.top), mode + ': closing begins after the appointment.');
            assert(close(sizes.parent.width, sizes.before.width), mode + ': attached blocks line up.');
            assert.equal(sizes.before.text + sizes.after.text, '', 'Buffers have no printed legends.');
            assert.equal(sizes.parent.topRadius, '0px');
            assert.equal(sizes.parent.bottomRadius, '0px');
            assert.equal(sizes.before.topRadius, '6px');
            assert.equal(sizes.before.bottomRadius, '0px');
            assert.equal(sizes.after.topRadius, '0px');
            assert.equal(sizes.after.bottomRadius, '6px');
        }
        console.log('calendar_time_buffers_layout_test: OK (actual browser geometry, week/day, 15-minute buffers, corners, no overlap)');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
