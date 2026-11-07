const assert = require('node:assert/strict');
const {spawn} = require('node:child_process');
const {createInterface} = require('node:readline');
const {chromium} = require(process.argv[2] || 'playwright');
(async () => {
    const fixture = spawn('docker', ['compose', 'exec', '-T', 'app', 'php', 'tests/organization_integration_browser_fixture.php'], {stdio: ['pipe', 'pipe', 'inherit']});
    fixture.stdin.on('error', error => { if (error.code !== 'EPIPE') throw error; });
    const closed = new Promise(resolve => fixture.on('close', resolve));
    let browser;
    try {
        const first = await createInterface({input: fixture.stdout})[Symbol.asyncIterator]().next();
        assert(!first.done, 'Local fixture starts');
        const config = JSON.parse(first.value);
        browser = await chromium.launch({channel: 'chrome', headless: true, args: ['--host-resolver-rules=MAP localtest.me 127.0.0.1, MAP *.localtest.me 127.0.0.1']});
        const context = await browser.newContext({ignoreHTTPSErrors: true, extraHTTPHeaders: {Origin: config.origin}, viewport: {width: 1100, height: 900}});
        await context.addInitScript(() => { window.turnstile = {ready: cb => cb(), render: () => 'fixture', reset() {}, remove() {}}; });
        const endpoint = config.origin + '/omo/api/parameters/security/save.php?oid=' + config.oid;
        const login = await context.request.post(config.origin + '/common/login_password.php', {form: {email: config.email, password: config.password}, headers: {'X-Requested-With': 'XMLHttpRequest'}});
        assert.equal((await login.json()).status, 'ok');
        await context.request.get(config.origin + '/omo/o/' + config.oid);
        assert.equal((await context.request.post(endpoint, {form: {}})).status(), 403, 'Admin mode required');
        const admin = await context.request.post(config.origin + '/common/admin_mode.php', {form: {enabled: '1', organization_id: config.oid}, headers: {'X-Requested-With': 'XMLHttpRequest'}});
        assert.equal((await admin.json()).active, true, 'Organization admin mode available without Patreon');
        const page = await context.newPage();
        const errors = []; page.on('pageerror', error => errors.push(error.message));
        await page.goto(config.origin + '/omo/o/' + config.oid, {waitUntil: 'domcontentloaded'});
        await page.waitForFunction(() => typeof window.commonTopbarOpenModal === 'function');
        const open = async () => {
            await page.evaluate(oid => window.commonTopbarOpenModal('Securite', '/omo/api/parameters/security/index.php?oid=' + oid, 'fetch'), config.oid);
            await page.locator('[data-omo-mcp-settings]').waitFor();
        };
        await open();
        const root = page.locator('[data-omo-security]');
        const csrf = await root.getAttribute('data-csrf');
        assert.equal((await context.request.post(endpoint, {form: {organization_id: config.oid, csrf: 'wrong', api_enabled: '1', mcp_enabled: '1'}})).status(), 403, 'Form CSRF required');
        assert.equal((await context.request.post(endpoint, {form: {organization_id: config.otherOid, csrf, api_enabled: '1', mcp_enabled: '1'}})).status(), 403, 'Another organization cannot be modified');
        const invalid = await context.request.post(endpoint, {form: {organization_id: config.oid, csrf, api_enabled: 'maybe', mcp_enabled: '1', ai_blocked: '0'}});
        assert.equal(invalid.status(), 422, 'Policy values validated: ' + await invalid.text());
        const invalidBackup = await context.request.post(endpoint, {form: {organization_id: config.oid, csrf, api_enabled: '1', mcp_enabled: '1', ai_blocked: '1', enabled: '0', email: 'invalid-email', frequency: '1m'}});
        assert.equal(invalidBackup.status(), 422, 'All preferences validated before any are saved');
        const api = () => page.locator('[name="api_enabled"]');
        const mcp = () => page.locator('[name="mcp_enabled"]');
        const ai = () => page.locator('[name="ai_blocked"]');
        assert.equal(await api().inputValue(), '0'); assert.equal(await mcp().inputValue(), '0');
        assert.equal(await ai().isChecked(), false, 'Internal AI allowed by default');
        const apiInfo = () => page.locator('[data-omo-integration-info="api"]');
        const mcpInfo = () => page.locator('[data-omo-integration-info="mcp"]');
        assert.equal(await apiInfo().isVisible(), false); assert.equal(await mcpInfo().isVisible(), false);
        const help = page.locator('[data-omo-mcp-settings] details.generic-context-help');
        assert.equal(await help.count(), 4, 'Section and all three controls use shared help capsules');
        assert.equal(await help.first().locator('.generic-context-help__content').isVisible(), false, 'Explanations are initially collapsed');
        await help.first().locator('summary').click();
        assert.equal(await help.first().locator('.generic-context-help__content').isVisible(), true, 'Capsule opens on click');
        await help.first().locator('summary').click();
        assert.equal(await root.locator('form').count(), 1, 'One shared security form');
        assert.equal(await page.locator('[data-omo-mcp-settings] button[type="submit"]').count(), 0, 'No separate integration save button');
        await page.screenshot({path: 'tmp/organization-integration-security.png'});
        await page.setViewportSize({width: 390, height: 844});
        const mobileSave = await page.locator('[data-omo-security-save]').boundingBox();
        assert(mobileSave.x >= 0 && mobileSave.x + mobileSave.width <= 390, 'Policy save button fits mobile viewport');
        await page.screenshot({path: 'tmp/organization-integration-security-mobile.png'});
        await page.setViewportSize({width: 1100, height: 900});
        for (const blocked of [true, false]) for (const [a, m] of [['1', '0'], ['0', '1'], ['1', '1'], ['0', '0']]) {
            await api().selectOption(a); await mcp().selectOption(m);
            assert.equal(await apiInfo().isVisible(), a === '1', 'REST details follow current choice');
            assert.equal(await mcpInfo().isVisible(), m === '1', 'MCP details follow current choice');
            if (blocked && a === '1' && m === '1') {
                assert.equal(await apiInfo().locator('code').textContent(), config.origin + '/api/v1');
                assert.equal(await mcpInfo().locator('code').textContent(), config.mcpEndpoint, 'Canonical endpoint keeps its exact trailing slash');
                assert.equal(await apiInfo().locator('a').getAttribute('href'), config.origin + '/developer/#overview');
                assert.equal(await mcpInfo().locator('a').getAttribute('href'), config.origin + '/developer/#mcp');
                const docs = await context.request.get(config.origin + '/developer/');
                assert.equal(docs.status(), 200);
                assert((await docs.text()).includes('id="mcp"'), 'MCP documentation anchor exists');
                await page.screenshot({path: 'tmp/organization-connections-enabled.png'});
                await page.setViewportSize({width: 390, height: 844});
                const mcpHeading = page.locator('label[for="omoOrganizationMcpEnabled"]');
                const headingBox = await mcpHeading.boundingBox();
                const helpBox = await mcpHeading.locator('..').locator('summary').boundingBox();
                assert(helpBox.x > headingBox.x && helpBox.y < headingBox.y + headingBox.height, 'MCP help stays at the end of the label row on mobile');
                await page.screenshot({path: 'tmp/organization-connections-enabled-mobile.png'});
                await page.setViewportSize({width: 1100, height: 900});
            }
            await ai().setChecked(blocked);
            await page.locator('[name="email"]').fill('security@example.invalid');
            const resultReady = page.waitForResponse(response => response.url() === endpoint);
            await page.locator('[data-omo-security-save]').click();
            assert.equal((await (await resultReady).json()).status, 'ok');
            assert.equal(await page.evaluate(() => window.commonDrawerHasUnsavedChanges(document.querySelector('[data-omo-security]'))), false, 'Global save clears the whole drawer guard');
            await page.locator('#commonTopbarModal button[data-topbar-modal-close]').click();
            await open();
            assert.equal(await api().inputValue(), a); assert.equal(await mcp().inputValue(), m);
            assert.equal(await ai().isChecked(), blocked);
            assert.equal(await apiInfo().isVisible(), a === '1'); assert.equal(await mcpInfo().isVisible(), m === '1');
            assert.equal(await page.locator('[name="email"]').inputValue(), 'security@example.invalid', 'Backup preferences saved together with AI/API/MCP');
            if (blocked && a === '0' && m === '0') {
                for (const action of ['html/rewrite', 'html/summarize', 'html/transcribe', 'pv/summarize']) {
                    const denied = await context.request.post(config.origin + '/omo/api/documents/' + action + '.php', {form: {oid: config.oid, document_id: '1', full_text: 'Do not send', selected_text: 'Do not send'}});
                    assert.equal(denied.status(), 403, action + ' rejects already-open clients');
                    assert.equal((await denied.json()).code, 'organization_ai_blocked');
                }
            }
        }
        assert.equal(await page.locator('[data-omo-security-backup-now]').count(), 1, 'Backup settings still available independently');
        assert.deepEqual(errors, []);
        console.log('PASS one global security save without Patreon, AI/API/MCP and backup persistence, eight independent combinations, blocked AI HTTP endpoints, authorization, CSRF and validation');
    } finally { if (browser) await browser.close(); fixture.stdin.end('\n'); await closed; }
})().catch(error => { console.error(error); process.exitCode = 1; });
