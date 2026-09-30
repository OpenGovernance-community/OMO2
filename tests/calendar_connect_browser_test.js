'use strict';
// Render the fixture with external_calendar_edit_test.php render, then pass its HTML path and a test-only Playwright module.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require(process.argv[3] || 'playwright');
const root = path.resolve(__dirname, '..');
const html = fs.readFileSync(process.argv[2], 'utf8').replace(/<link[^>]+>/g, '');
const source = fs.readFileSync(path.join(root, 'omo/api/calendar/calendar.js'), 'utf8');
const start = source.indexOf('        function initCalendarConnectControls(container) {');
const end = source.indexOf("        root.querySelectorAll('[data-omo-calendar-filter-toggle]')", start);

(async () => {
    const browser = await chromium.launch({headless: true, channel: process.env.CALENDAR_BROWSER_CHANNEL || undefined});
    try {
        const page = await browser.newPage({viewport: {width: 760, height: 1000}});
        await page.setContent(html);
        for (const file of ['common/assets/theme.css', 'common/assets/components.css', 'omo/api/calendar/popups.css']) {
            await page.addStyleTag({path: path.join(root, file)});
        }
        await page.addScriptTag({content: `
            window.calls = []; window.refreshes = 0; window.notices = [];
            window.omoNotify = (message, type) => notices.push({message, type});
            function resolveUrl(url) { return url; }
            var currentUrl = '/calendar';
            function refreshCalendar() { refreshes++; return Promise.resolve(); }
            function openCalendarConnectPopup() {}
            window.fetch = async (url, options) => {
                var values = Object.fromEntries(options.body.entries()); calls.push(values);
                return {json: async () => values.action === 'discover'
                    ? {status: true, discoveryToken: 'fixture-token', message: 'Choose', calendars: [{index: 0, title: 'Discovered', color: '#112233', connected: false}]}
                    : {status: true, message: 'Saved'}};
            };
            ${source.slice(start, end)}
            initCalendarConnectControls(document.body);
            document.querySelector('#omoCalendarConnectOmo').hidden = true;
            document.querySelector('#omoCalendarConnectExternal').hidden = false;
        `});
        const ics = page.locator('[data-omo-external-calendar-provider="ics"]');
        const caldav = page.locator('[data-omo-external-calendar-provider="caldav"]');
        assert(await ics.isVisible());
        assert(!await caldav.isVisible());
        const listTop = await page.locator('[data-omo-external-calendar-item]').first().boundingBox();
        const addTop = await page.locator('[data-omo-external-calendar-add]').boundingBox();
        assert(listTop.y < addTop.y, 'Connected calendars precede creation.');
        await page.locator('[data-omo-external-calendar-type][value="caldav"]').check();
        assert(await caldav.isVisible() && !await ics.isVisible());
        await caldav.locator('[name="server_url"]').fill('https://example.com/calendar');
        await caldav.locator('[name="username"]').fill('user');
        await caldav.locator('[name="password"]').fill('password');
        await caldav.locator('[data-omo-external-calendar-form] button[type="submit"]').click();
        await page.waitForFunction(() => document.querySelector('[data-omo-external-calendar-selection]').hidden === false);
        await page.locator('[data-omo-external-calendar-type][value="ics"]').check();
        assert(!await page.locator('[data-omo-external-calendar-selection]').isVisible(), 'Discovery results belong to CalDAV only.');
        await ics.locator('summary').click();
        assert(await ics.locator('.generic-context-help__content').isVisible());
        await ics.locator('summary').click();
        await ics.locator('[name="ics_url"]').fill('https://example.com/calendar.ics');
        await ics.locator('[name="title"]').fill('New ICS');
        await ics.locator('button[type="submit"]').click();
        await page.waitForFunction(() => calls.some(call => call.action === 'save_ics'));
        const editButtons = page.locator('[data-omo-external-calendar-edit]');
        const editor = page.locator('[data-omo-external-calendar-edit-form]');
        await editButtons.first().click();
        assert(await editor.isVisible() && !await page.locator('[data-omo-external-calendar-add]').isVisible());
        assert.equal(await editor.locator('[name="title"]').inputValue(), 'Original');
        assert.equal(await editor.locator('[name="ics_url"]').inputValue(), '');
        assert(!await editor.locator('[data-omo-external-calendar-edit-caldav]').isVisible());
        await editor.locator('[name="title"]').fill('Renamed');
        await editor.locator('[name="color"]').fill('#aabbcc');
        await editor.locator('button[type="submit"]').click();
        await page.waitForFunction(() => calls.some(call => call.action === 'update'));
        const submitted = await page.evaluate(() => calls.find(call => call.action === 'update'));
        assert.equal(submitted.title, 'Renamed');
        assert.equal(submitted.color, '#aabbcc');
        assert.equal(submitted.csrf_token, 'test-csrf');
        assert(submitted.calendar_id && submitted.ics_url === '', 'Submit keeps ID and blank credential while busy.');
        await editor.locator('[data-omo-external-calendar-edit-cancel]').click();
        assert(await page.locator('[data-omo-external-calendar-add]').isVisible());
        await editButtons.nth(1).click();
        assert.equal(await editor.locator('[name="username"]').inputValue(), 'test-user');
        assert.equal(await editor.locator('[name="calendar_url"]').inputValue(), 'https://127.0.0.1:1/original');
        assert.equal(await editor.locator('[name="password"]').inputValue(), '');
        assert(!await editor.locator('[data-omo-external-calendar-edit-ics]').isVisible());
        for (const width of [760, 375]) {
            await page.setViewportSize({width, height: 1000});
            assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), 'Editor must fit the viewport.');
            await editor.locator('[data-omo-external-calendar-edit-cancel]').click();
            assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), 'Creation form must fit the viewport.');
            if (process.env.CALENDAR_SCREENSHOT_DIR) { await page.screenshot({path: path.join(process.env.CALENDAR_SCREENSHOT_DIR, `calendar-connect-${width}.png`), fullPage: true}); }
            await editButtons.nth(1).click();
        }
        console.log('calendar_connect_browser_test: OK (switching, help, discovery, creation, editing, cancellation, mobile)');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
