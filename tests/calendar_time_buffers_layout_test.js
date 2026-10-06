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
const short = {...parent, id: 11, title: 'A very long appointment title that wraps onto several lines',
    startMinute: 720, endMinute: 750, timeLabel: '12:00 - 12:30', columnCount: 4,
    documentUrl: '/document?id=11', documentTitle: 'Document', canEdit: true, editUrl: '/edit'};
const allDay = {...short, id: 12};
const day = {dayKey: '2030-01-07', label: 'Lun 7', count: 3, allDay: [4], timed: [0, 1, 2, 3], items: [3]};
const timeline = {title: 'Calendrier', count: 1, columnCount: 1, days: [day]};
const ensure = context.omoCreateCalendarViews(host, {items: [parent, before, after, short, allDay],
    views: {contextual: {week: timeline, day: timeline, month: timeline, list: {sections: [{label: 'Day', items: [3]}]}}},
    labels: {}, hours: {}, weekdays: []});

(async () => {
    const browser = await chromium.launch({headless: true, ...(process.argv[3] ? {executablePath: process.argv[3]} : {})});
    try {
        const page = await browser.newPage({viewport: {width: 1000, height: 1100}});
        await page.route('**/*', route => route.abort());
        for (const mode of ['week', 'day']) {
            const panel = ensure(mode, 'contextual');
            await page.setContent('<style>:root{--radius-md:6px}</style>' + panel.innerHTML);
            await page.addStyleTag({content: fs.readFileSync(path.join(root, 'common/assets/components.css'), 'utf8')});
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
            const event = page.locator('.omo-calendar__time-event[data-omo-calendar-event-id="11"]');
            await event.scrollIntoViewIfNeeded();
            const shortcut = await event.evaluate(node => {
                const button = node.querySelector('[data-omo-calendar-open-url]');
                const title = node.querySelector('.omo-calendar__time-event-title').getBoundingClientRect();
                const r = button.getBoundingClientRect(); const card = node.getBoundingClientRect();
                const hit = document.elementFromPoint(r.x + r.width / 2, r.y + r.height / 2);
                return {visible: r.top >= card.top && r.bottom <= card.bottom, rightInset: card.right - r.right,
                    topInset: r.top - card.top, overlapsTitle: title.right > r.left, clickable: hit === button || button.contains(hit)};
            });
            assert(shortcut.visible && shortcut.clickable, mode + ': document remains visible and clickable on a 30-minute event.');
            assert(shortcut.topInset < 8 && shortcut.rightInset < 10, mode + ': shortcut sits at the top right.');
            assert(!shortcut.overlapsTitle, mode + ': a wrapped title cannot cover the shortcut.');
        }
        for (const mode of ['month', 'list']) {
            await page.setContent(ensure(mode, 'contextual').innerHTML);
            await page.addStyleTag({content: fs.readFileSync(path.join(root, 'common/assets/components.css'), 'utf8')});
            await page.addStyleTag({content: fs.readFileSync(path.join(root, 'omo/api/calendar/calendar.css'), 'utf8')});
            const position = await page.locator('[data-omo-calendar-event-id="11"]').evaluate(node => {
                const button = node.querySelector('[data-omo-calendar-open-url]').getBoundingClientRect();
                const card = node.getBoundingClientRect(); const menu = node.querySelector('[data-omo-calendar-event-menu-toggle]')?.getBoundingClientRect();
                return {topInset: button.top - card.top, rightInset: card.right - button.right,
                    menuOverlap: menu && menu.right > button.left && menu.bottom > button.top && menu.top < button.bottom};
            });
            assert(position.topInset < 8 && position.rightInset < 10, mode + ': same top-right shortcut.');
            assert(!position.menuOverlap, mode + ': shortcut does not cover the event menu.');
        }
        console.log('calendar_time_buffers_layout_test: OK (buffers, corners, 30-minute document shortcut, all four views)');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
