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
        const mockChats = new Map();
        let modelRequests = 0;
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
        const emptyState = await (await context.request.post(endpoint, {data: {...base, action: 'state'}})).json();
        assert.deepEqual(emptyState.messages, [], 'Authenticated state read does not invoke a model');
        assert.equal((await context.request.post(endpoint, {data: {...base, action: 'state', csrf: 'wrong'}})).status(), 403, 'History is CSRF protected');
        assert.equal((await context.request.post(endpoint, {data: {...base, action: 'reset', organizationId: config.otherOid}})).status(), 403, 'Reset respects organization access');
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
                const identity = {status: true, organizationId: config.oid, conversationId: body.conversationId};
                if (body.action === 'reset') {
                    mockChats.delete(body.conversationId);
                    return route.fulfill({json: identity});
                }
                if (body.action === 'state') return route.fulfill({json: {...identity, messages: [], busy: false,
                    pendingQuestion: '', failed: false, ...mockChats.get(body.conversationId)}});
                modelRequests++;
                const url = `/omo/o/${config.oid}/c/${config.cid}#calendar-e${config.eventId}`;
                const answer = {status: true, organizationId: config.oid, conversationId: body.conversationId,
                    answer: 'Voici les projets a examiner.\n### Quelques **points cles** : ###\n\n| Projet | Echeance | Statut |\n| --- | --- | --- |\n'
                        + '| [Reunion Suisse](' + url + ') | 10.10.2026 | **En cours** |\n'
                        + '| Documentation \\| FAQ | 12.10.2026 | A relire, puis "valider" |\n'
                        + '| <img src=x onerror=alert(1)> | [Interdit](javascript:alert(1)) | [Externe](https://example.invalid) |\n\n'
                        + '- Verifier les echeances.\n- Contacter les responsables.\n\n'
                        + 'Tableau incomplet conserve en texte :\n| A | B |\n| --- | invalide |',
                    links: [{url, label: 'Reunion Suisse'}, {url: 'https://example.invalid', label: 'External'}]};
                mockChats.set(body.conversationId, {messages: [
                    ...(mockChats.get(body.conversationId)?.messages || []),
                    {role: 'user', text: body.question}, {role: 'assistant', text: answer.answer, links: answer.links}]});
                await route.fulfill({json: answer});
            });
            await question.fill('Qui est en charge de ce role ?');
            await page.locator('[data-assistant-send]').click();
            await page.locator('[data-assistant-messages] article').nth(1).waitFor();
            assert.equal(await page.locator('[data-assistant-messages] img').count(), 0, 'Model HTML is text');
            assert.equal(await page.locator('[data-assistant-messages] a[href^="javascript:"]').count(), 0, 'No script URLs');
            assert.equal(await page.locator('[data-assistant-messages] a[href^="https://example.invalid"]').count(), 0, 'No untrusted URLs');
            assert.equal(await page.locator('[data-assistant-messages] table').count(), 1, 'Only valid Markdown tables are rendered');
            assert.equal(await page.locator('[data-assistant-messages] th[scope="col"]').count(), 3, 'Table headers are accessible');
            assert.equal(await page.locator('[data-assistant-messages] tbody tr').count(), 3);
            assert.equal(await page.locator('[data-assistant-messages] tbody tr').nth(1).locator('td').first().textContent(), 'Documentation | FAQ', 'Escaped pipes stay in their cell');
            assert.equal(await page.locator('[data-assistant-messages] ul li').count(), 2, 'Lists use semantic markup');
            assert.equal(await page.locator('[data-assistant-messages] table a').count(), 1, 'Only verified table links are clickable');
            assert.equal(await page.locator('.generic-message-bubble--outgoing').count(), 1, 'User question has its own bubble');
            const heading = page.locator('.organization-assistant__heading');
            assert.equal(await heading.textContent(), 'Quelques points cles :', 'Markdown headings render without hash markers, even immediately after a paragraph');
            assert.equal(await heading.getAttribute('role'), 'heading');
            assert.equal(await heading.locator('strong').textContent(), 'points cles', 'Heading keeps safe inline formatting');
            assert(await heading.evaluate(node => node.tagName === 'DIV'
                && getComputedStyle(node).fontSize === getComputedStyle(node.parentElement).fontSize), 'Chat headings stay compact and independent of site heading styles');
            const copy = page.locator('.generic-data-table-copy');
            assert.equal(await copy.evaluate(node => getComputedStyle(node).opacity), '0', 'Copy action stays discreet before hover');
            await page.locator('.generic-data-table-frame').hover();
            assert.equal(await copy.evaluate(node => getComputedStyle(node).opacity), '1', 'Hover reveals the copy action');
            await page.screenshot({path: 'tmp/assistant-copy-csv.png'});
            await question.hover();
            await copy.focus();
            assert.equal(await copy.evaluate(node => getComputedStyle(node).opacity), '1', 'Keyboard focus reveals the copy action');
            await context.grantPermissions(['clipboard-read', 'clipboard-write'], {origin: config.origin});
            await copy.click();
            const csv = await page.evaluate(() => navigator.clipboard.readText());
            assert.equal(csv, '"Projet","Echeance","Statut"\r\n'
                + '"Reunion Suisse","10.10.2026","En cours"\r\n'
                + '"Documentation | FAQ","12.10.2026","A relire, puis ""valider"""\r\n'
                + '"<img src=x onerror=alert(1)>","Interdit)","Externe"', 'CSV copies headers and rendered text with comma/quote escaping');
            assert((await page.locator('#commonNotifications').textContent()).includes(widgetConfig.text.copied_csv), 'Successful copy uses shared notifications');
            const cell = page.locator('[data-assistant-messages] tbody tr').nth(1).locator('td').first();
            await cell.evaluate(node => { node.textContent = 'Ligne 1\nLigne "2", suite'; });
            await copy.click();
            await page.waitForFunction(() => !document.querySelector('.generic-data-table-copy').disabled);
            const multilineCsv = await page.evaluate(() => navigator.clipboard.readText());
            assert(multilineCsv.replace(/\r\n/g, '\n').includes('"Ligne 1\nLigne ""2"", suite"'), 'CSV preserves embedded line breaks');
            await cell.evaluate(node => { node.textContent = 'Documentation | FAQ'; });
            await page.evaluate(() => Object.defineProperty(navigator, 'clipboard', {value: undefined, configurable: true}));
            await copy.click();
            assert((await page.locator('#commonNotifications').textContent()).includes(widgetConfig.text.copy_error), 'Unavailable clipboard reports an error');
            assert(await copy.isEnabled(), 'Copy can be retried after failure');
            await page.evaluate(() => { delete navigator.clipboard; });
            await question.focus();
        }
        await page.screenshot({path: 'tmp/assistant-desktop.png'});
        const panel = page.locator('#organization-assistant-panel');
        const grip = page.locator('[data-assistant-resize]');
        const initialWidth = (await panel.boundingBox()).width;
        const gripBox = await grip.boundingBox();
        await page.mouse.move(gripBox.x + gripBox.width / 2, gripBox.y + gripBox.height / 2);
        await page.mouse.down();
        await page.mouse.move(gripBox.x - 200, gripBox.y + gripBox.height / 2, {steps: 6});
        await page.mouse.up();
        const draggedWidth = (await panel.boundingBox()).width;
        assert(draggedWidth > initialWidth + 190, 'Left grip expands the window across embedded content');
        assert.equal(await page.locator('.generic-resize-shield').count(), 0, 'Resize shield is removed after dragging');
        await grip.press('ArrowLeft');
        assert((await panel.boundingBox()).width > draggedWidth + 35, 'Keyboard can expand the window');
        await grip.press('End');
        assert.equal((await panel.boundingBox()).width, 1100, 'Expansion is bounded');
        await grip.press('Home');
        assert.equal((await panel.boundingBox()).width, 360, 'Minimum width remains usable');
        for (let step = 0; step < 8; step++) await grip.press('ArrowLeft');
        const preferredWidth = (await panel.boundingBox()).width;
        await question.focus();
        await page.locator('.organization-assistant__conversation').evaluate(node => { node.scrollTop = 0; });
        await page.screenshot({path: 'tmp/assistant-expanded.png'});
        for (const width of [390, 320]) {
            await page.setViewportSize({width, height: 844});
            const box = await page.locator('#organization-assistant-panel').boundingBox();
            assert(box.x >= 0 && box.x + box.width <= width + 1 && box.y >= 0 && box.y + box.height <= 844, 'Panel fits mobile viewport');
            const inputBox = await question.boundingBox();
            const sendBox = await page.locator('[data-assistant-send]').boundingBox();
            assert(sendBox.x >= inputBox.x + inputBox.width && sendBox.y < inputBox.y + inputBox.height, 'Send icon stays beside the input on mobile');
            assert(await page.locator('.organization-assistant__conversation').evaluate(node => node.scrollWidth <= node.clientWidth + 1), 'Tables do not overflow the conversation');
            if (!process.argv.includes('--live') && width === 320) {
                assert(await page.locator('.generic-data-table-scroll').evaluate(node => {
                    node.scrollLeft = 100;
                    return node.scrollLeft > 0;
                }), 'Wide tables can scroll horizontally on mobile');
                const copyBox = await page.locator('.generic-data-table-copy').boundingBox();
                assert(copyBox.x >= box.x && copyBox.x + copyBox.width <= box.x + box.width, 'Copy action remains reachable after horizontal scrolling');
            }
            await help.locator('summary').click();
            await page.waitForFunction(() => document.querySelector('#organization-assistant-panel .generic-context-help__content').style.getPropertyValue('--generic-context-help-max-width'));
            const helpBox = await help.locator('.generic-context-help__content').boundingBox();
            assert(helpBox.x >= box.x && helpBox.x + helpBox.width <= box.x + box.width + 1, 'Help fits inside the chat on mobile');
            await page.screenshot({path: 'tmp/assistant-help-mobile.png'});
            await question.click();
        }
        await page.screenshot({path: 'tmp/assistant-mobile.png'});
        await page.setViewportSize({width: 1360, height: 900});
        assert.equal((await panel.boundingBox()).width, preferredWidth, 'Desktop width survives a smaller viewport');
        await page.evaluate(() => document.documentElement.setAttribute('data-theme', 'dark'));
        await page.screenshot({path: 'tmp/assistant-dark.png'});
        await page.evaluate(() => document.documentElement.setAttribute('data-theme', 'light'));
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
        const previousMessages = await page.locator('[data-assistant-messages]').textContent();
        const stateKey = 'common_assistant_chat_' + config.oid;
        const chatId = await page.evaluate(key => JSON.parse(sessionStorage.getItem(key)).conversationId, stateKey);
        await question.fill('Une question encore en brouillon');
        await page.reload();
        await page.waitForFunction(() => !document.querySelector('[data-assistant-send]').disabled);
        assert(await panel.isVisible(), 'Reload restores the open panel');
        assert.equal(await page.locator('[data-assistant-messages]').textContent(), previousMessages, 'Reload restores all messages, tables and links');
        assert.equal(await question.inputValue(), 'Une question encore en brouillon', 'Reload restores the draft');
        assert.equal(await page.evaluate(key => JSON.parse(sessionStorage.getItem(key)).conversationId, stateKey), chatId, 'Reload continues the same server conversation');
        await page.locator('[data-assistant-close]').click();
        await page.reload();
        await page.waitForFunction(() => !document.querySelector('[data-assistant-send]').disabled);
        assert(await panel.isHidden(), 'Closed panel stays closed after reload');
        await page.locator('[data-assistant-toggle]').click();
        if (!process.argv.includes('--live')) {
            const countBefore = modelRequests;
            const oldChat = mockChats.get(chatId);
            mockChats.set(chatId, {...oldChat, busy: true, pendingQuestion: 'Question en cours'});
            await page.reload();
            await page.waitForFunction(() => document.querySelector('[data-assistant-progress]').textContent.includes('consulte'));
            mockChats.set(chatId, {messages: [...oldChat.messages, {role: 'user', text: 'Question en cours'},
                {role: 'assistant', text: 'Reponse arrivee pendant le refresh', links: []}]});
            await page.getByText('Reponse arrivee pendant le refresh', {exact: true}).waitFor();
            assert.equal(modelRequests, countBefore, 'Reload polls the running turn without sending the question again');
        }
        await page.locator('[data-assistant-new]').click();
        await page.waitForFunction(() => document.querySelector('[data-assistant-messages]').childElementCount === 0);
        assert.equal(await page.locator('[data-assistant-messages] article').count(), 0, 'New discussion clears visible history');
        if (!process.argv.includes('--live')) assert(!mockChats.has(chatId), 'New discussion removes the server history');
        else {
            const removed = await (await context.request.post(endpoint, {data: {...base, action: 'state', conversationId: chatId}})).json();
            assert.deepEqual(removed.messages, [], 'Reset removes persisted server messages');
        }
        await page.reload();
        await page.waitForFunction(() => !document.querySelector('[data-assistant-send]').disabled);
        assert(await panel.isVisible(), 'New discussion retains the open state');
        assert.equal(await page.locator('[data-assistant-messages] article').count(), 0, 'Reset stays empty after reload');
        assert.equal((await panel.boundingBox()).width, preferredWidth, 'Chosen width survives reload');
        await page.addInitScript(key => { const saved = JSON.parse(sessionStorage.getItem(key)); saved.scope = 'another-session'; saved.draft = 'Private draft'; sessionStorage.setItem(key, JSON.stringify(saved)); }, stateKey);
        await page.reload();
        assert(await panel.isHidden(), 'A different account/session scope cannot restore panel state');
        assert.equal(await question.inputValue(), '', 'A different account/session scope cannot restore its draft');
        console.log('PASS assistant real session/CSRF/org isolation, chat controls, safe links and mobile layout');
    } finally {
        if (browser) await browser.close();
        fixture.stdin.write('\n'); fixture.stdin.end();
        await closed; lines.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
