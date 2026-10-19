'use strict';
const assert = require('node:assert/strict');
const {spawn} = require('node:child_process');
const {createInterface} = require('node:readline');
const {chromium} = require('playwright');

(async () => {
    const fixture = spawn('docker', ['exec', '-i', 'openmyorganization-app-1', 'php', 'tests/faq_access_denied_browser_fixture.php'], {stdio: ['pipe', 'pipe', 'inherit']});
    const closed = new Promise(resolve => fixture.on('close', resolve));
    const lines = createInterface({input: fixture.stdout});
    let browser;
    try {
        const first = await lines[Symbol.asyncIterator]().next();
        assert(!first.done, 'Local fixture must start.');
        const config = JSON.parse(first.value);
        browser = await chromium.launch({channel: 'msedge', headless: true});
        const makeContext = () => browser.newContext({ignoreHTTPSErrors: true, locale: 'fr-CH', extraHTTPHeaders: {Origin: config.origin}});
        const login = async (context, email) => {
            const response = await context.request.post(config.origin + '/common/login_password.php',
                {form: {email, password: config.password}, headers: {'X-Requested-With': 'XMLHttpRequest'}});
            assert.equal((await response.json()).status, 'ok');
        };
        const url = config.origin + '/omo/o/' + config.oid + '/#|faq-' + config.faqId;
        const outsider = await makeContext();
        await login(outsider, config.outsiderEmail);
        const deniedPage = await outsider.newPage();
        await deniedPage.route('https://code.jquery.com/jquery-3.7.1.min.js', route => route.fulfill({path: require.resolve('jquery/dist/jquery.js')}));
        assert.equal((await deniedPage.goto(url)).status(), 403);
        assert.equal(await deniedPage.locator('#commonTopbarModal').isVisible(), false, 'An ordinary outsider cannot open someone else’s request.');
        assert.equal((await outsider.request.get(config.origin + '/popup/faq.php?oid=' + config.oid + '&id=' + config.faqId)).status(), 403);
        await outsider.close();

        const context = await makeContext();
        await login(context, config.adminEmail);
        const page = await context.newPage();
        page.setDefaultTimeout(15000);
        const errors = [], requests = [], dialogs = [];
        page.on('pageerror', error => errors.push(error.message));
        page.on('request', request => requests.push(new URL(request.url()).pathname));
        page.on('dialog', dialog => { dialogs.push(dialog.message()); return dialog.dismiss(); });
        await page.route('https://code.jquery.com/jquery-3.7.1.min.js', route => route.fulfill({path: require.resolve('jquery/dist/jquery.js')}));
        assert.equal((await page.goto(url)).status(), 403, 'The organization workspace remains forbidden.');
        assert((await page.locator('.auth-state-card h1').textContent()).includes('Accès interdit'));
        await page.locator('[data-faq-request-only="1"] [data-faq-answer-save]').waitFor({state: 'visible'});
        assert.equal(await page.locator('#commonTopbarModal').isVisible(), true, 'The linked request opens over the denied-access screen.');
        assert.equal((await context.request.get(config.origin + '/omo/api/getStructureData.php?oid=' + config.oid)).status(), 403, 'The request does not grant structure access.');
        assert(!requests.includes('/omo/assets/js/app.js') && !requests.includes('/omo/api/getStructureData.php'), 'No organization workspace or structure is fetched in the background.');

        await page.locator('#commonTopbarModal button[data-topbar-modal-close]').click();
        assert.equal(await page.locator('#commonTopbarModal').isVisible(), false);
        assert.equal(new URL(page.url()).hash, '', 'Closing clears the request route.');
        await page.evaluate(id => { window.location.hash = '|faq-' + id; }, config.faqId);
        await page.locator('[data-faq-answer-save]').waitFor({state: 'visible'});
        await page.locator('[data-faq-answer-save]').click();
        await page.waitForFunction(() => document.getElementById('commonNotifications').textContent.includes('reponse courte'));
        assert.equal(await page.locator('#commonTopbarModal form').count(), 1, 'Invalid answers retain the form and use shared notifications.');
        await page.locator('[name="answer"]').fill('Synthetic reply without organization membership.');
        await page.locator('[data-html-editor-surface]').waitFor({state: 'visible'});
        await page.locator('[data-faq-answer-save]').click();
        const confirmation = page.locator('[data-faq-complete-message]');
        await confirmation.waitFor({state: 'visible'});
        assert((await confirmation.textContent()).includes('envoyee par e-mail'));
        assert.equal(await page.locator('#commonTopbarModal form').count(), 0, 'Completion replaces the form without loading the organization FAQ list.');
        assert.equal(requests.filter(path => path === '/popup/faq.php').length, 2, 'Opening and reopening fetch just the request, with no list refresh after sending.');
        assert.equal((await context.request.get(config.origin + '/omo/api/getStructureData.php?oid=' + config.oid)).status(), 403, 'Sending a reply still grants no organization access.');
        await page.locator('[data-faq-back]').click();
        assert.equal(await page.locator('#commonTopbarModal').isVisible(), false);
        assert.equal(new URL(page.url()).hash, '');
        assert.deepEqual(dialogs, [], 'A completed reply closes without an unsaved-data prompt.');
        assert.deepEqual(errors, []);
        console.log('PASS denied organization background, authorized FAQ deep link, role isolation, reply completion and clean closing');
    } finally {
        if (browser) await browser.close();
        fixture.stdin.end('\n');
        await closed;
        lines.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
