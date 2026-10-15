// Run with Playwright available locally, or set PLAYWRIGHT_MODULE to its module directory.
const assert = require('node:assert/strict');
const {spawn} = require('node:child_process');
const {createInterface} = require('node:readline');
const {chromium} = require(process.env.PLAYWRIGHT_MODULE || 'playwright');

(async () => {
    const fixture = spawn('docker', ['compose', 'exec', '-T', 'app', 'php', '-d', 'display_errors=1', 'tests/meeting_visitor_test.php', '--browser'], {stdio: ['pipe', 'pipe', 'inherit']});
    const closed = new Promise(resolve => fixture.on('close', resolve));
    const lines = createInterface({input: fixture.stdout});
    let browser;
    try {
        const first = await lines[Symbol.asyncIterator]().next();
        assert(!first.done, 'Fixture must start');
        const config = JSON.parse(first.value);
        browser = await chromium.launch({headless: true, channel: 'chrome'});
        const context = await browser.newContext({ignoreHTTPSErrors: true, locale: 'fr-CH', viewport: {width: 1360, height: 1000}});
        const login = async email => {
            const response = await context.request.post(config.origin + '/common/login_password.php', {
                form: {email, password: config.password, return_to: '/omo/o/' + config.oid}, headers: {'X-Requested-With': 'XMLHttpRequest', Origin: config.origin, Referer: config.origin + config.path}
            });
            assert.equal((await response.json()).status, 'ok');
        };
        await login(config.email);
        const page = await context.newPage();
        const errors = []; page.on('pageerror', error => errors.push(error.message));
        const url = config.origin + config.path + '?date=' + config.date + '&time=10:00&duration=30';
        await page.goto(url);
        const form = page.locator('form').filter({has: page.locator('textarea[name=reason]')});
        await page.locator('[data-meeting-identity]').waitFor();
        assert((await page.locator('[data-meeting-identity]').innerText()).includes(config.email));
        assert.equal(await form.locator('input[name=name], input[name=email]').count(), 0);
        const select = form.locator('[name=guest_calendar_id]');
        assert.equal(await select.inputValue(), String(config.calendarId), 'Configured calendar is selected');
        assert.equal(await select.locator('option').count(), 3, 'None + two own CalDAV calendars');
        assert.equal(await select.locator(`option[value="${config.hostCalendarId}"], option[value="${config.icsCalendarId}"]`).count(), 0);
        const csrf = await form.locator('[name=csrf]').inputValue();
        const postForm = {csrf, date: config.date, time: '10:00', duration: '30', reason: 'Identity check', identity_user_id: String(config.visitorId), guest_calendar_id: String(config.alternateId), name: 'Injected name', email: 'injected@example.invalid'};
        let response = await context.request.post(config.origin + config.path, {headers: {Origin: config.origin, Referer: url}, form: {...postForm, guest_calendar_id: String(config.hostCalendarId)}});
        let html = await response.text();
        assert(html.includes('Choisissez un agenda CalDAV actif'), 'Foreign calendar rejected: ' + response.status() + ' ' + response.url() + ' ' + (html.match(/data-meeting-error[^>]*>([^<]*)/)?.[1] || html.slice(0, 1200)));
        response = await context.request.post(config.origin + config.path, {headers: {Origin: config.origin, Referer: url}, form: {...postForm, identity_user_id: String(config.hostId)}});
        assert((await response.text()).includes('Le compte connecté a changé'), 'Forged identity rejected');
        response = await context.request.post(config.origin + config.path, {headers: {Origin: config.origin, Referer: url}, form: {...postForm, csrf: 'wrong', action: 'manual_identity'}});
        assert.equal(response.status(), 403, 'Identity opt-out requires CSRF');
        await page.setViewportSize({width: 1366, height: 768});
        await page.evaluate(() => window.scrollTo(0, 0));
        assert((await form.locator('button.generic-action-button--main').boundingBox()).y < 710, 'Booking form fits a laptop screen');
        await page.screenshot({path: 'tmp/meeting-visitor-desktop.png'});
        await page.setViewportSize({width: 390, height: 844});
        await form.scrollIntoViewIfNeeded();
        assert(await select.isVisible());
        assert((await page.locator('[data-meeting-identity]').boundingBox()).height < 30, 'Identity stays on one compact line');
        const dateBox = await page.locator('#meeting-day').boundingBox();
        const timeBox = await page.locator('.meeting-selection__time').boundingBox();
        assert(Math.abs(dateBox.y - timeBox.y) < 8, 'Date and time share one row');
        assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), 'No mobile horizontal overflow');
        await page.screenshot({path: 'tmp/meeting-visitor-mobile.png'});
        await select.selectOption(String(config.alternateId));
        await form.locator('[name=reason]').fill('Browser review');
        await form.locator('button.generic-action-button--main').click();
        await page.locator('[data-meeting-confirm]').waitFor();
        assert((await page.locator('.meeting-summary').innerText()).includes('Other visitor calendar'));
        const token = await page.locator('[data-meeting-confirm] [name=token]').inputValue();
        await login(config.hostEmail);
        response = await context.request.post(config.origin + config.path, {headers: {Origin: config.origin, Referer: url}, form: {csrf, action: 'confirm', token}});
        html = await response.text();
        assert(html.includes('Le compte connecté a changé'), 'Account switch cannot confirm another visitor draft: ' + (html.match(/data-meeting-error[^>]*>([^<]*)/)?.[1] || html.slice(0, 200)));
        await login(config.email);
        await page.goto(url);
        await form.locator('[name=reason]').fill('Keep this reason');
        const identityRequests = [];
        const recordIdentityRequest = request => { if (request.isNavigationRequest() || request.method() === 'POST') identityRequests.push(request.url()); };
        page.on('request', recordIdentityRequest);
        await form.locator('button[name=action][value=manual_identity]').click();
        await form.locator('input[name=name]').waitFor();
        page.off('request', recordIdentityRequest);
        assert.deepEqual(identityRequests, [], 'Not me switches locally without submission or reload');
        assert(await form.locator('input[name=name]').evaluate(input => document.activeElement === input), 'Focus moves to the name field');
        assert.equal(await form.locator('[name=reason]').inputValue(), 'Keep this reason');
        assert.equal(await form.locator('input[name=name]').inputValue(), '');
        assert.equal(await form.locator('input[name=email]').inputValue(), '');
        assert.equal(await form.locator('[name=guest_calendar_id]').count(), 0, 'Manual booking has no access to logged-in calendar');
        await page.reload();
        assert.equal(await page.locator('[data-meeting-identity]').count(), 0, 'Opt-out persists for this host');
        response = await context.request.get(config.origin + '/omo/api/calendar/meeting_settings.php?oid=' + config.oid);
        assert.equal(response.status(), 200);
        assert((await response.text()).includes('name="slug"'), 'Not me does not log out the account');

        const anonymous = await browser.newContext({ignoreHTTPSErrors: true, locale: 'fr-CH'});
        const anonymousPage = await anonymous.newPage(); await anonymousPage.goto(url);
        assert.equal(await anonymousPage.locator('input[name=name], input[name=email]').count(), 2, 'Anonymous visitor keeps standard form');
        assert.equal(await anonymousPage.locator('[data-meeting-identity], [name=guest_calendar_id]').count(), 0);
        assert.deepEqual(errors, []);
        console.log('Meeting visitor UI passed: identity, calendar selection, authorization, CSRF, manual form, desktop and mobile.');
    } finally {
        if (browser) await browser.close();
        fixture.stdin.end('\n');
        assert.equal(await closed, 0, 'Fixture cleanup completed');
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
