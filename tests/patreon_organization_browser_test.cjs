const assert = require('node:assert/strict');
const {spawn} = require('node:child_process');
const {createInterface} = require('node:readline');
const {chromium} = require(process.argv[2] || 'playwright');
(async () => {
    const fixture = spawn('docker', ['compose', 'exec', '-T', 'app', 'php', 'tests/patreon_organization_browser_fixture.php'], {stdio: ['pipe', 'pipe', 'inherit']});
    const closed = new Promise(resolve => fixture.on('close', resolve));
    const lines = createInterface({input: fixture.stdout});
    let browser;
    try {
        const first = await lines[Symbol.asyncIterator]().next();
        assert(!first.done); const config = JSON.parse(first.value);
        browser = await chromium.launch({headless: true, channel: 'chrome'});
        const context = await browser.newContext({ignoreHTTPSErrors: true, locale: 'fr-CH', extraHTTPHeaders: {Origin: config.origin}, viewport: {width: 1200, height: 1000}});
        const login = await context.request.post(config.origin + '/common/login_password.php', {form: {email: config.email, password: config.password}, headers: {'X-Requested-With': 'XMLHttpRequest'}});
        assert.equal((await login.json()).status, 'ok');
        await context.request.post(config.origin + '/common/admin_mode.php', {form: {enabled: '1', organization_id: config.oid}, headers: {'X-Requested-With': 'XMLHttpRequest'}});
        const page = await context.newPage(); const errors = []; page.on('pageerror', error => errors.push(error.message));
        await page.goto(config.origin + '/popup/organization_create.php?oid=' + config.oid);
        const panel = page.locator('[data-patreon-organization]'); await panel.waitFor();
        require('node:fs').mkdirSync('tmp', {recursive: true});
        await panel.screenshot({path: 'tmp/patreon-settings-check.png'});
        assert(await panel.locator('[data-patreon-connect]').isVisible());
        assert.equal(await panel.locator('.generic-help-bubble').count(), 0);
        const data = JSON.parse(await panel.getAttribute('data-config'));
        assert(!JSON.stringify(data).includes('fixture-not-a-token'), 'No token in DOM');
        const endpoint = config.origin + '/common/patreon/organization-action.php';
        const base = {organizationId: config.oid, csrf: data.csrf, action: 'status'};
        assert.equal((await context.request.post(endpoint, {data: {...base, csrf: 'wrong'}})).status(), 403);
        assert.equal((await context.request.post(endpoint, {data: {...base, organizationId: config.otherOid}})).status(), 403);
        assert.equal((await context.request.get(endpoint)).status(), 405);
        assert.equal((await (await context.request.post(endpoint, {data: base})).json()).linked, true);
        assert.equal(await panel.locator('table').count(), 0, 'No usage counts in organization settings');
        page.on('dialog', dialog => dialog.accept());
        await panel.locator('[data-patreon-action="disconnect"]').click();
        await panel.locator('[data-patreon-action="disconnect"]').waitFor({state: 'hidden'});
        assert.equal((await (await context.request.post(endpoint, {data: base})).json()).linked, false);
        assert.equal(await page.locator('#commonNotifications').count(), 1, 'Shared notification renders');
        for (const width of [390, 1200]) {
            await page.setViewportSize({width, height: 1000});
            assert(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), 'No horizontal page overflow');
        }
        await page.goto(config.origin + '/common/admin/stats.php?organization=' + config.oid);
        const usage = page.locator('#ai-usage'); await usage.waitFor();
        assert.equal(await usage.locator('tbody tr').count(), 3, 'Global stats separate three origins');
        assert((await usage.innerText()).includes('42'));
        await usage.screenshot({path: 'tmp/patreon-usage-check.png'});
        await page.goto(config.origin + '/common/admin/stats.php?organization=' + config.oid + '&ai_origin=mcp');
        assert.equal(await page.locator('#ai-usage-table tbody tr').count(), 1, 'Origin filter scoped server-side');
        const guest = await browser.newContext({ignoreHTTPSErrors: true});
        const guestResponse = await guest.request.get(config.origin + '/common/admin/stats.php');
        assert(!(await guestResponse.text()).includes('id="ai-usage-table"'), 'Usage is restricted to site administrators');
        await guest.close();
        assert.deepEqual(errors, []);
        console.log('PASS organization Patreon UI, usage breakdown, CSRF, cross-org denial, disconnection and mobile layout');
    } finally {
        if (browser) await browser.close(); fixture.stdin.write('\n'); fixture.stdin.end(); await closed; lines.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
