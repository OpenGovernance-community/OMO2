'use strict';
// node tests/calendar_save_warning_test.js /path/to/playwright /path/to/chrome
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require(process.argv[2] || 'playwright');

(async () => {
    const browser = await chromium.launch({headless: true, ...(process.argv[3] ? {executablePath: process.argv[3]} : {})});
    try {
        for (const host of ['project', 'calendar']) {
            const page = await browser.newPage();
            await page.route('http://calendar.test/', route => route.fulfill({contentType: 'text/html', body: '<html><body></body></html>'}));
            await page.goto('http://calendar.test/');
            const form = `<form action="/save" data-omo-calendar-create-form ${host === 'project' ? 'data-omo-calendar-editor-host="project"' : ''}>
                <input name="title" value="Event"><input name="start_at" value="2030-01-07T10:00">
                <input name="end_at" value="2030-01-07T11:00"><input name="availability_ack" value="">
                <div data-omo-calendar-create-feedback></div><button data-omo-calendar-create-submit>Save</button></form>`;
            await page.setContent(host === 'project' ? form : `<div id="omo-calendar-root">
                <script data-omo-calendar-data type="application/json">{"views":{},"labels":{}}</script>
                <div data-omo-calendar-views></div><div data-omo-calendar-editor-drawer>
                    <div data-omo-calendar-editor-body>${form}</div></div></div>`);
            await page.evaluate(() => {
                window.notices = []; window.saveCalls = 0;
                window.commonNotify = (message, type) => window.notices.push({message, type});
                window.resetGenericExpandedMenu = () => {};
                window.fetch = async () => {
                    window.saveCalls++;
                    return {ok: true, json: async () => ({status: true, message: 'Saved', warning: 'Cached data used'})};
                };
            });
            const source = host === 'project' ? '../common/calendar/event-editor.js' : '../omo/api/calendar/calendar.js';
            await page.addScriptTag({content: fs.readFileSync(path.join(__dirname, source), 'utf8')});
            await page.evaluate(host => {
                if (host === 'project') window.omoInitCalendarEventEditor(document);
                else window.omoInitCalendar(document.getElementById('omo-calendar-root'));
            }, host);
            await page.locator('[data-omo-calendar-create-submit]').click();
            await page.waitForFunction(() => window.notices.length > 0 && !document.querySelector('form').dataset.omoCalendarSubmitPending);
            assert.deepEqual(await page.evaluate(() => window.notices), [{message: 'Cached data used', type: 'warning'}],
                host + ' shows exactly one topbar warning without turning a successful save into an error.');
            assert.equal(await page.evaluate(() => window.saveCalls), 1, 'No confirmation or second save is required.');
            assert.equal(await page.locator('[data-omo-calendar-create-submit]').isEnabled(), true);
            await page.close();
        }
        console.log('calendar_save_warning_test: OK');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
