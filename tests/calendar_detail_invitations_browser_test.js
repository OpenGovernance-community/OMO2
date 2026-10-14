'use strict';
// Generate the real endpoint fixtures with calendar_personal_time_buffers_test.php first.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require(process.argv[2] || 'playwright');
const root = path.resolve(__dirname, '..');

(async () => {
    const browser = await chromium.launch({headless: true, channel: 'msedge'});
    try {
        const parent = fs.readFileSync(path.join(root, 'omo/api/calendar/index.php'), 'utf8');
        const scripts = [...parent.matchAll(/<script src="<\?= commonAssetUrl\('([^']+)'\)/g)].map(match => match[1]);
        assert(scripts.includes('/common/calendar/invitations.js'), 'Calendar must load invitations before inserting event details');
        const detail = fs.readFileSync(path.join(root, 'tmp/calendar-invitations-detail.html'), 'utf8');
        const summary = fs.readFileSync(path.join(root, 'tmp/calendar-invitations-summary.html'), 'utf8');
        for (const width of [1280, 390]) {
            const page = await browser.newPage({viewport: {width, height: 1000}});
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.route('https://calendar.example.invalid/**', route => route.fulfill({contentType: 'text/html', body: '<html></html>'}));
            await page.goto('https://calendar.example.invalid/');
            const config = {labels: {}, weekdays: [], items: [{id: 42, title: 'Meeting', status: 'confirmed', timeLabel: '10:00'}],
                views: {contextual: {month: {title: 'October', days: [{dayKey: '2030-10-01', label: '1', items: [0]}]}}}};
            await page.setContent('<main id="omo-calendar-root" data-omo-calendar-view="month" data-omo-calendar-detail-url="/omo/api/calendar/detail.php?oid=1">'
                + '<script type="application/json" data-omo-calendar-data>' + JSON.stringify(config) + '</script><div data-omo-calendar-views></div>'
                + '<aside hidden data-omo-calendar-editor-drawer><h2 data-omo-calendar-editor-title></h2><p data-omo-calendar-editor-description></p>'
                + '<div data-omo-calendar-editor-actions></div><div data-omo-calendar-editor-body></div></aside></main>');
            await page.evaluate(({detail, summary}) => {
                window.summaryRequests = 0;
                window.failSummary = false;
                window.fetch = async url => {
                    if (String(url).includes('invitation_summary=1')) {
                        window.summaryRequests++;
                        return {ok: !window.failSummary, text: async () => summary};
                    }
                    return {ok: true, text: async () => detail};
                };
            }, {detail, summary});
            for (const file of ['common/assets/components.css', 'omo/api/calendar/calendar.css', 'omo/api/calendar/detail.css']) {
                await page.addStyleTag({path: path.join(root, file)});
            }
            for (const file of ['common/assets/components.js', 'common/calendar/availability-model.js', 'common/calendar/availability-view.js', 'common/calendar/availability.js', 'omo/api/calendar/calendar.js']) {
                await page.addScriptTag({path: path.join(root, file)});
            }
            // Only load this dependency when the real parent declares it; fragment scripts remain inert.
            for (const file of scripts.filter(file => file === '/common/calendar/invitations.js')) {
                await page.addScriptTag({path: path.join(root, file.slice(1))});
            }
            await page.evaluate(() => window.omoInitCalendar(document.querySelector('#omo-calendar-root')));
            const event = page.locator('[data-omo-calendar-event-id="42"]');
            await event.dblclick();
            const host = page.locator('[data-calendar-invitations-lazy]');
            await host.waitFor({state: 'attached'});
            assert.equal(await page.evaluate(() => window.summaryRequests), 0, 'Closed accordion stays lazy');
            const toggle = host.locator('..').locator(':scope > summary');
            await toggle.click();
            await host.locator('.omo-calendar-detail__summary-copy').waitFor();
            assert.match(await host.innerText(), /Invited group/);
            assert.equal(await page.evaluate(() => window.summaryRequests), 1);
            await toggle.click();
            await toggle.click();
            assert.equal(await page.evaluate(() => window.summaryRequests), 1, 'Reopening does not duplicate the fetch');
            await host.screenshot({path: path.join(root, `tmp/calendar-invitations-${width}.png`)});
            await page.evaluate(() => { window.failSummary = true; });
            await event.dblclick();
            await page.waitForFunction(() => !document.querySelector('[data-calendar-invitations-lazy]').dataset.loadState);
            await toggle.click();
            const retry = host.locator('[data-calendar-invitations-feedback] button');
            await retry.waitFor();
            await page.evaluate(() => { window.failSummary = false; });
            await retry.click();
            await host.locator('.omo-calendar-detail__summary-copy').waitFor();
            assert.match(await host.innerText(), /Invited group/);
            assert.equal(await page.evaluate(() => window.summaryRequests), 3, 'Failed requests can be retried');
            assert.deepEqual(errors, []);
            await page.close();
        }
        console.log('calendar_detail_invitations_browser_test: OK (desktop/mobile, lazy load, reopen, retry)');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
