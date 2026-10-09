'use strict';
// Same Playwright/browser arguments as calendar_time_buffers_layout_test.js.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require(process.argv[2] || 'playwright');
const root = path.resolve(__dirname, '..');
const personal = process.argv[4] === 'personal';
(async () => {
    const browser = await chromium.launch({headless: true, ...(process.argv[3] ? {executablePath: process.argv[3]} : {})});
    try {
        const page = await browser.newPage();
        await page.route('https://calendar.example.invalid/**', route => route.fulfill({contentType: 'text/html', body: '<html></html>'}));
        await page.goto('https://calendar.example.invalid/');
        const errors = []; page.on('pageerror', error => errors.push(error.stack));
        const editUrl = personal ? '/omo/api/calendar/create.php?oid=1&id=42' : '/omo/api/calendar/external_event.php?oid=1&id=42';
        const month = {title: 'October', days: [{dayKey: '2030-10-01', label: '1', items: [0]}]};
        const config = {labels: {'calendar.action.edit': 'Editer'}, weekdays: [], items: [{id: 1000000000, title: 'Source title',
            timeLabel: '10:00 - 11:00', isExternal: true, status: 'confirmed', externalDrawerData: {
                title: 'Source title', schedule: '10:00 - 11:00', calendar: 'Imported calendar', editUrl}}],
            externalEventDrawerText: {title: 'Evenement importe', description: 'Source', schedule: 'Date', calendar: 'Agenda'},
            views: {contextual: {month}}};
        await page.setContent('<main id="omo-calendar-root" data-omo-calendar-view="month" data-omo-calendar-current-url="/omo/api/calendar/index.php?oid=1">'
            + (personal ? '<button data-omo-calendar-floating-menu-action data-omo-calendar-open-edit-url="' + editUrl + '">Editer</button>' : '')
            + '<script type="application/json" data-omo-calendar-data>' + JSON.stringify(config) + '</script><div data-omo-calendar-views></div>'
            + '<aside hidden data-omo-calendar-editor-drawer><h2 data-omo-calendar-editor-title></h2><p data-omo-calendar-editor-description></p>'
            + '<div data-omo-calendar-editor-actions></div><div data-omo-calendar-editor-body></div></aside></main>');
        await page.evaluate(({editUrl, personal}) => {
            window.testRequests = []; window.testNotifications = []; window.testRefreshes = [];
            window.commonNotify = (message, type) => window.testNotifications.push({message, type});
            window.omoReplaceFetchedPanelRoot = options => { window.testRefreshes.push(options.url); return Promise.resolve(); };
            window.fetch = async (url, options = {}) => {
                window.testRequests.push({url, method: options.method || 'GET', values: options.body ? Object.fromEntries(options.body.entries()) : {}});
                if (options.method === 'POST') return {json: async () => ({status: true, message: 'Saved'})};
                return {ok: true, text: async () => '<div hidden data-omo-calendar-drawer-header data-omo-calendar-drawer-title="Temps avant / apres">'
                    + '<button type="submit" form="externalForm" data-omo-calendar-drawer-action data-omo-calendar-create-submit>Enregistrer</button></div>'
                    + '<form id="externalForm" action="' + editUrl + '" data-omo-calendar-create-form ' + (personal ? 'data-omo-calendar-personal-time-buffers-form' : 'data-omo-calendar-external-event-form') + '>'
                    + '<input type="hidden" name="csrf" value="token"><label><input type="checkbox" name="time_buffers_enabled" value="1" data-omo-calendar-buffers-toggle>Temps</label>'
                    + '<div hidden data-omo-calendar-buffers-fields><select disabled name="preparation_minutes"><option value="0">Aucun</option><option value="30">30 minutes</option></select>'
                    + '<select disabled name="closing_minutes"><option value="0">Aucun</option><option value="45">45 minutes</option></select></div>'
                    + '<p data-omo-calendar-create-feedback></p></form>'};
            };
        }, {editUrl, personal});
        for (const file of ['common/assets/components.js', 'common/calendar/availability-model.js', 'common/calendar/availability-view.js', 'common/calendar/availability.js', 'omo/api/calendar/calendar.js']) {
            await page.addScriptTag({content: fs.readFileSync(path.join(root, file), 'utf8')});
        }
        await page.evaluate(() => window.omoInitCalendar(document.querySelector('#omo-calendar-root')));
        if (!personal) await page.locator('[data-omo-calendar-external-event]').dblclick();
        await page.locator('[data-omo-calendar-open-edit-url]').click();
        await page.locator('[data-omo-calendar-buffers-toggle]').check();
        await page.locator('[name="preparation_minutes"]').selectOption('30');
        await page.locator('[name="closing_minutes"]').selectOption('45');
        await page.locator('[data-omo-calendar-editor-actions] [data-omo-calendar-create-submit]').click();
        await page.waitForFunction(() => window.testNotifications.length > 0);
        await page.locator('[data-omo-calendar-editor-drawer]').waitFor({state: 'hidden'});
        const result = await page.evaluate(() => ({requests: window.testRequests, notices: window.testNotifications,
            refreshes: window.testRefreshes, closed: document.querySelector('[data-omo-calendar-editor-drawer]').hidden}));
        assert.equal(errors.length, 0, errors.join('\n'));
        assert(result.requests[0].url.endsWith(editUrl), 'Edit uses the cached event ID, not its virtual display ID.');
        assert.deepEqual(result.requests[1].values, {csrf: 'token', time_buffers_enabled: '1', preparation_minutes: '30', closing_minutes: '45'});
        assert.deepEqual(result.notices, [{message: 'Saved', type: 'success'}]);
        assert.equal(result.refreshes.length, 1, 'Saving refreshes the displayed calendar.');
        assert(result.closed, 'Drawer closes after successful save.');
        console.log('calendar_external_event_editor_test: OK (' + (personal ? 'personal' : 'external') + ': open, edit, enable, select, save, notify, refresh)');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
