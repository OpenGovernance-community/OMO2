const assert = require('node:assert/strict');
const {spawn} = require('node:child_process');
const {createInterface} = require('node:readline');
const {chromium} = require(process.argv[2] || 'playwright');

// Requires a local HTTP instance with Patreon client credentials configured (fixture values suffice).
// Optional arguments: Playwright module path, then the test HTTP origin.
(async () => {
    const fixture = spawn('docker', ['compose', 'exec', '-T', 'app', 'php', 'tests/patreon_organization_browser_fixture.php', '--routing'], {stdio: ['pipe', 'pipe', 'inherit']});
    const closed = new Promise(resolve => fixture.on('close', resolve));
    const lines = createInterface({input: fixture.stdout});
    let browser;
    try {
        const first = await lines[Symbol.asyncIterator]().next();
        assert(!first.done);
        const config = JSON.parse(first.value);
        if (process.argv[3]) config.origin = process.argv[3];
        browser = await chromium.launch({headless: true, channel: 'chrome'});
        const context = await browser.newContext({ignoreHTTPSErrors: true, locale: 'fr-CH', extraHTTPHeaders: {Origin: config.origin}});
        const headers = {'X-Requested-With': 'XMLHttpRequest'};
        const login = await context.request.post(config.origin + '/common/login_password.php', {form: {email: config.email, password: config.password}, headers});
        assert.equal((await login.json()).status, 'ok');
        const admin = await context.request.post(config.origin + '/common/admin_mode.php', {form: {enabled: '1', organization_id: config.oid}, headers});
        const adminResult = await admin.json();
        assert.equal(adminResult.active, true, JSON.stringify(adminResult));
        const page = await context.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        const editorUrl = config.origin + '/popup/organization_create.php?oid=' + config.oid;
        await page.goto(editorUrl);
        const shortname = page.locator('#shortname');
        const domain = page.locator('#domain');
        await shortname.waitFor();
        assert(await shortname.isEnabled() && await domain.isEnabled(), 'Subscribed organization unlocks both fields for its ordinary admin');

        const nonce = Date.now();
        const savedShortname = 'routing-' + nonce;
        const savedDomain = 'routing-' + nonce + '.example.invalid';
        const saveUrl = config.origin + '/ajax/saveorganization.php?oid=' + config.oid;
        const saved = await context.request.post(saveUrl, {form: {shortname: savedShortname, domain: savedDomain}, headers});
        assert.equal((await saved.json()).success, true, 'Subscribed organization can save routing');
        await page.goto(editorUrl);
        assert.equal(await shortname.inputValue(), savedShortname);
        assert.equal(await domain.inputValue(), savedDomain);

        // The same UI path runs after OAuth completion and after synchronization.
        const panel = page.locator('[data-patreon-organization]');
        const panelConfig = JSON.parse(await panel.getAttribute('data-config'));
        await page.evaluate(oid => {
            document.getElementById('organizationCreateRoot').dispatchEvent(new CustomEvent('patreon-organization-updated', {detail: {organizationId: oid, canManageOrganizationRouting: false}}));
        }, config.oid);
        assert(await shortname.isDisabled() && await domain.isDisabled());
        // Fetch real server status through the shared panel to verify unlocking without reloading.
        await page.route('**/common/patreon/organization-action.php', async route => {
            const input = route.request().postDataJSON();
            await route.continue({postData: JSON.stringify({...input, action: 'status'})});
        });
        await panel.locator('[data-patreon-action="sync"]').click();
        await page.waitForFunction(() => !document.getElementById('shortname').disabled);
        assert(await domain.isEnabled(), 'Patreon status update immediately unlocks both fields');
        await page.unroute('**/common/patreon/organization-action.php');

        page.on('dialog', dialog => dialog.accept());
        await panel.locator('[data-patreon-action="disconnect"]').click();
        await page.waitForFunction(() => document.getElementById('shortname').disabled);
        assert(await domain.isDisabled(), 'Disconnect immediately locks both fields');
        const status = await context.request.post(config.origin + '/common/patreon/organization-action.php', {data: {organizationId: config.oid, csrf: panelConfig.csrf, action: 'status'}});
        assert.equal((await status.json()).canManageOrganizationRouting, false);
        const denied = await context.request.post(saveUrl, {form: {shortname: 'forbidden-' + nonce, domain: 'forbidden.example.invalid', color: '#123456'}, headers});
        assert.equal((await denied.json()).success, true, 'Other organization settings remain editable');
        await page.goto(editorUrl);
        assert(await shortname.isDisabled() && await domain.isDisabled(), 'A personal 300 EUR subscription never unlocks organization routing');
        assert.equal(await shortname.inputValue(), savedShortname, 'Unauthorized POST preserves the existing URL identifier');
        assert.equal(await domain.inputValue(), savedDomain, 'Unauthorized POST preserves the existing domain');
        assert.equal(await page.locator('#color').inputValue(), '#123456');
        await page.goto(config.origin + '/popup/organization_create.php');
        assert(await shortname.isDisabled() && await domain.isDisabled(), 'A new organization cannot use the current organization or personal subscription');
        assert.deepEqual(errors, []);
        console.log('PASS organization routing: subscribed admin, persistence, live unlock/lock, personal subscription isolation and forged POST preservation');
    } finally {
        if (browser) await browser.close();
        fixture.stdin.write('\n'); fixture.stdin.end(); await closed; lines.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
