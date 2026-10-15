const assert = require('node:assert/strict');
const {spawn} = require('node:child_process');
const {createInterface} = require('node:readline');
const {chromium} = require(process.argv[2] || 'playwright');

(async () => {
    const fixture = spawn('docker', ['compose', 'exec', '-T', 'app', 'php', 'tests/assistant_browser_fixture.php',
        ...process.argv.filter(arg => ['--live', '--authorized'].includes(arg))],
        {stdio: ['pipe', 'pipe', 'inherit']});
    const closed = new Promise(resolve => fixture.on('close', resolve));
    const lines = createInterface({input: fixture.stdout});
    let browser;
    try {
        const first = await lines[Symbol.asyncIterator]().next();
        assert(!first.done, 'Local fixture started');
        const config = JSON.parse(first.value);
        if (process.argv.includes('--live') || process.argv.includes('--authorized')) assert(config.aiReady, 'Authorized test requires configured AI credentials and access');
        browser = await chromium.launch({headless: true, channel: 'chrome'});
        const context = await browser.newContext({ignoreHTTPSErrors: true, locale: 'fr-CH',
            extraHTTPHeaders: {'Origin': config.origin}, viewport: {width: 1360, height: 900}});
        const login = await context.request.post(config.origin + '/common/login_password.php', {
            form: {email: config.email, password: config.password, return_to: `/omo/o/${config.oid}`},
            headers: {'X-Requested-With': 'XMLHttpRequest'}});
        assert.equal((await login.json()).status, 'ok');
        if (config.liveAdmin) {
            const mode = await context.request.post(config.origin + '/common/site_admin_mode.php', {
                form: {enabled: '1'}, headers: {'X-Requested-With': 'XMLHttpRequest'}});
            assert((await mode.json()).active, 'Disposable live-test account activates its own site-admin mode');
        }
        const page = await context.newPage();
        await page.goto(`${config.origin}/omo/o/${config.oid}/c/${config.cid}`);
        if (!config.aiReady) {
            assert.equal(await page.locator('[data-organization-assistant]').count(), 0, 'No assistant button or panel without AI access');
            assert.equal(await page.locator('script[src*="/common/assistant/chat.js"]').count(), 0, 'Assistant script is not loaded without AI access');
            const denied = await context.request.post(config.origin + '/common/assistant/endpoint.php', {
                data: {organizationId: config.oid, csrf: 'unauthorized', conversationId: '1'.repeat(32), question: 'Bonjour'}});
            assert.equal(denied.status(), 403, 'Direct requests remain protected');
            console.log('PASS assistant hidden without AI access, no chat assets and direct endpoint protected');
            return;
        }
        await page.locator('[data-assistant-toggle]').click();
        await page.locator('#organization-assistant-panel').waitFor({state: 'visible'});
        const widgetConfig = JSON.parse(await page.locator('[data-organization-assistant]').getAttribute('data-config'));
        const endpoint = config.origin + widgetConfig.endpoint;
        const base = {csrf: widgetConfig.csrf, organizationId: config.oid, conversationId: '1'.repeat(32), question: 'Bonjour'};
        assert.equal((await context.request.post(endpoint, {data: {...base, csrf: 'wrong'}})).status(), 403, 'CSRF enforced');
        assert.equal((await context.request.post(endpoint, {data: {...base, organizationId: config.otherOid}})).status(), 403, 'Organization cannot be switched by JSON');
        assert.equal((await context.request.post(endpoint, {data: {...base, question: 'x'.repeat(4001)}})).status(), 400, 'Question length bounded');
        assert.equal((await context.request.get(endpoint)).status(), 405, 'GET cannot invoke a model');
        const anonymous = await browser.newContext({ignoreHTTPSErrors: true, extraHTTPHeaders: {'Origin': config.origin}});
        assert.equal((await anonymous.request.post(endpoint, {data: base})).status(), 403, 'A copied CSRF token cannot authorize another session');
        await anonymous.close();
        const question = page.locator('#organization-assistant-question');
        const help = page.locator('#organization-assistant-panel .generic-context-help');
        await help.locator('summary').click();
        await page.screenshot({path: 'tmp/assistant-help-desktop.png'});
        assert(await help.locator('.generic-context-help__content').isVisible(), 'Help remains available in its capsule');
        await question.click();
        await page.screenshot({path: 'tmp/assistant-welcome.png'});
        if (process.argv.includes('--live')) {
            for (const text of ['Qui est en charge de ce role ?', 'Quand a lieu la prochaine reunion Suisse ?']) {
                await question.fill(text);
                const answer = page.waitForResponse(response => response.url() === endpoint && response.request().method() === 'POST', {timeout: 130000});
                await page.locator('[data-assistant-send]').click();
                const result = await (await answer).json();
                assert(result.status, result.message);
                console.log('LIVE ' + config.provider + ' / ' + config.model + ': ' + result.answer);
                if (text.includes('role')) assert(result.answer.includes('Camille'), 'Actual model must identify the role holder');
                else assert(result.links.some(link => link.url.endsWith('#calendar-e' + config.eventId)), 'Meeting answer links to its actionable event page');
                await page.waitForFunction(() => !document.querySelector('[data-assistant-send]').disabled);
            }
        } else {
            await page.route('**/common/assistant/endpoint.php', async route => {
                const body = route.request().postDataJSON();
                const url = `/omo/o/${config.oid}/c/${config.cid}#calendar-e${config.eventId}`;
                await route.fulfill({json: {status: true, organizationId: config.oid, conversationId: body.conversationId,
                    answer: 'Camille est en charge de ce role. <img src=x onerror=alert(1)>\n[Ouvrir la reunion](' + url + ')\n[Interdit](javascript:alert(1))',
                    links: [{url, label: 'Reunion Suisse'}, {url: 'https://example.invalid', label: 'External'}]}});
            });
            await question.fill('Qui est en charge de ce role ?');
            await page.locator('[data-assistant-send]').click();
            await page.locator('[data-assistant-messages] article').nth(1).waitFor();
            assert.equal(await page.locator('[data-assistant-messages] img').count(), 0, 'Model HTML is text');
            assert.equal(await page.locator('[data-assistant-messages] a[href^="javascript:"]').count(), 0, 'No script URLs');
            assert.equal(await page.locator('[data-assistant-messages] a[href^="https://example.invalid"]').count(), 0, 'No untrusted URLs');
        }
        await page.screenshot({path: 'tmp/assistant-desktop.png'});
        for (const width of [390, 320]) {
            await page.setViewportSize({width, height: 844});
            const box = await page.locator('#organization-assistant-panel').boundingBox();
            assert(box.x >= 0 && box.x + box.width <= width + 1 && box.y >= 0 && box.y + box.height <= 844, 'Panel fits mobile viewport');
            const inputBox = await question.boundingBox();
            const sendBox = await page.locator('[data-assistant-send]').boundingBox();
            assert(sendBox.x >= inputBox.x + inputBox.width && sendBox.y < inputBox.y + inputBox.height, 'Send icon stays beside the input on mobile');
            await help.locator('summary').click();
            await page.waitForFunction(() => document.querySelector('#organization-assistant-panel .generic-context-help__content').style.getPropertyValue('--generic-context-help-max-width'));
            const helpBox = await help.locator('.generic-context-help__content').boundingBox();
            assert(helpBox.x >= box.x && helpBox.x + helpBox.width <= box.x + box.width + 1, 'Help fits inside the chat on mobile');
            await page.screenshot({path: 'tmp/assistant-help-mobile.png'});
            await question.click();
        }
        await page.screenshot({path: 'tmp/assistant-mobile.png'});
        if (process.argv.includes('--live')) {
            const lastAnswer = page.locator('[data-assistant-messages] article').last();
            if (!await lastAnswer.locator('a[href$="#calendar-e' + config.eventId + '"]').first().isVisible()) {
                await lastAnswer.locator('summary').click();
            }
            await lastAnswer.locator('a[href$="#calendar-e' + config.eventId + '"]').first().click();
            await page.waitForURL('**#calendar-e' + config.eventId);
            assert(await page.locator('#organization-assistant-panel').isHidden(), 'Event source opens the existing action page');
            await page.locator('[data-assistant-toggle]').click();
        }
        await page.locator('[data-assistant-close]').click();
        assert.equal(await page.locator('[data-assistant-toggle]').getAttribute('aria-expanded'), 'false');
        await page.locator('[data-assistant-toggle]').click();
        assert(await page.locator('[data-assistant-messages] article').count() >= 2, 'Closing preserves the conversation');
        await page.locator('[data-assistant-new]').click();
        assert.equal(await page.locator('[data-assistant-messages] article').count(), 0, 'New discussion clears visible history');
        console.log('PASS assistant real session/CSRF/org isolation, chat controls, safe links and mobile layout');
    } finally {
        if (browser) await browser.close();
        fixture.stdin.write('\n'); fixture.stdin.end();
        await closed; lines.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
