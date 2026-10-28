'use strict';
const assert = require('node:assert/strict');
const {spawn} = require('node:child_process');
const {createInterface} = require('node:readline');
const {chromium} = require(process.argv[2] || 'playwright');

(async () => {
    const fixture = spawn('docker', ['compose', 'exec', '-T', 'app', 'php', '-d', 'display_errors=1', 'tests/calendar_organization_hours_browser_fixture.php'], {stdio: ['pipe', 'pipe', 'inherit']});
    const closed = new Promise(resolve => fixture.on('close', resolve));
    const lines = createInterface({input: fixture.stdout});
    let browser;
    try {
        const first = await lines[Symbol.asyncIterator]().next();
        assert(!first.done);
        const config = JSON.parse(first.value);
        browser = await chromium.launch({headless: true, channel: 'chrome'});
        const context = await browser.newContext({ignoreHTTPSErrors: true, locale: 'fr-CH', viewport: {width: 1200, height: 900}, extraHTTPHeaders: {Origin: config.origin}});
        const login = await context.request.post(config.origin + '/common/login_password.php', {form: {email: config.email, password: config.password}, headers: {'X-Requested-With': 'XMLHttpRequest'}});
        assert.equal((await login.json()).status, 'ok');
        await context.request.post(config.origin + '/common/admin_mode.php', {form: {enabled: '1', organization_id: config.oid}, headers: {'X-Requested-With': 'XMLHttpRequest'}});
        const settingsUrl = config.origin + '/omo/api/parameters/calendar/index.php';
        const endpoint = config.origin + '/omo/api/parameters/calendar/save.php';
        const page = await context.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.goto(config.origin + '/omo/');
        // Load the same settings fragment as the drawer, keeping the normal shared assets.
        await page.evaluate(async url => {
            const host = document.createElement('div');
            host.id = 'calendar-settings-test-host';
            document.body.replaceChildren(host);
            host.innerHTML = await (await fetch(url)).text();
            await window.commonExecuteFragmentScripts(host);
        }, settingsUrl);
        const form = page.locator('[data-omo-calendar-settings]');
        await form.waitFor();
        assert.equal(await form.locator('[name="dayStart"]').inputValue(), '09:00');
        assert.equal(await form.locator('[name="dayEnd"]').inputValue(), '17:00');
        const csrf = await form.locator('[name="csrf"]').inputValue();
        const data = {csrf, organization_id: config.oid, dayStart: '08:00', dayEnd: '18:00'};
        assert.equal((await context.request.get(endpoint)).status(), 405);
        for (const values of [{...data, csrf: 'wrong'}, {...data, organization_id: config.otherOid}]) {
            assert.equal((await context.request.post(endpoint, {form: values})).status(), 403);
        }
        for (const values of [{...data, dayEnd: '08:00'}, {...data, dayStart: '18:30'}, {...data, dayStart: '08:15'}]) {
            assert.equal((await context.request.post(endpoint, {form: values})).status(), 422);
        }
        await form.locator('[name="dayStart"]').selectOption('08:00');
        await form.locator('[name="dayEnd"]').selectOption('18:00');
        const saved = page.waitForResponse(response => response.url() === endpoint && response.request().method() === 'POST');
        await form.locator('[type="submit"]').click();
        assert.equal((await (await saved).json()).status, 'ok');
        await page.waitForFunction(() => !document.querySelector('[data-omo-calendar-settings] [type="submit"]').disabled);
        assert.equal(await page.locator('#commonNotifications').count(), 1, 'Uses shared notifications.');
        assert.equal(await form.locator('[data-calendar-settings-feedback]').textContent(), '', 'No duplicate inline success.');
        const reloaded = await (await context.request.get(settingsUrl)).text();
        assert.match(reloaded, /value="08:00" selected/);
        assert.match(reloaded, /value="18:00" selected/);
        const profile = await (await context.request.get(config.origin + '/popup/user.php?section=availability&id=' + config.uid + '&oid=' + config.oid + '&month=2030-01&date=2030-01-07')).text();
        const payload = JSON.parse(profile.match(/data-user-availability-data>([\s\S]*?)<\/script>/)[1]);
        assert.equal(payload.people[0].days['2030-01-07'], '0'.repeat(16) + '2'.repeat(20) + '0'.repeat(12), 'Saved organization hours bound an occupied day.');
        for (const width of [390, 1200]) {
            await page.setViewportSize({width, height: 900});
            assert(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), 'No horizontal overflow.');
        }
        await page.screenshot({path: 'tmp/calendar-organization-hours.png'});
        await context.request.post(config.origin + '/common/admin_mode.php', {form: {enabled: '0', organization_id: config.oid}, headers: {'X-Requested-With': 'XMLHttpRequest'}});
        assert.equal((await context.request.get(settingsUrl)).status(), 403);
        assert.equal((await context.request.post(endpoint, {form: data})).status(), 403);
        assert.deepEqual(errors, []);
        console.log('PASS calendar settings UI, persistence, bounded profile availability, authorization, CSRF and mobile layout');
    } finally {
        if (browser) { await browser.close(); }
        fixture.stdin.write('\n');
        fixture.stdin.end();
        await closed;
        lines.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
