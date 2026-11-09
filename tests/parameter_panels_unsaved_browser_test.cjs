'use strict';
// Real PHP panels and browser scripts; save responses are simulated, no settings are written.
// node tests/parameter_panels_unsaved_browser_test.cjs /path/to/playwright
const assert = require('node:assert/strict');
const {spawn} = require('node:child_process');
const {createInterface} = require('node:readline');
const {chromium} = require(process.argv[2] || 'playwright');

(async () => {
    const fixture = spawn('docker', ['compose', 'exec', '-T', 'app', 'php', 'tests/parameter_panels_browser_fixture.php'], {stdio: ['pipe', 'pipe', 'inherit']});
    const closed = new Promise(resolve => fixture.on('close', resolve));
    let browser;
    try {
        const first = await createInterface({input: fixture.stdout})[Symbol.asyncIterator]().next();
        assert(!first.done, 'Disposable local administrator starts');
        const config = JSON.parse(first.value);
        browser = await chromium.launch({headless: true, channel: 'chrome'});
        const context = await browser.newContext({ignoreHTTPSErrors: true, extraHTTPHeaders: {Origin: config.origin}});
        context.setDefaultTimeout(15000);
        await context.addInitScript(() => { window.turnstile = {ready: cb => cb(), render: () => 'fixture', reset() {}, remove() {}}; });
        const login = await context.request.post(config.origin + '/common/login_password.php', {
            form: {email: config.email, password: config.password}, headers: {'X-Requested-With': 'XMLHttpRequest'}
        });
        assert.equal((await login.json()).status, 'ok');
        await context.request.post(config.origin + '/common/site_admin_mode.php', {
            form: {enabled: '1'}, headers: {'X-Requested-With': 'XMLHttpRequest'}
        });
        const page = await context.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.goto(config.origin + '/omo/o/' + config.oid, {waitUntil: 'domcontentloaded'});
        await page.waitForFunction(() => typeof window.commonTopbarOpenDrawer === 'function');
        await page.evaluate(() => {
            window.testMessages = [];
            window.testConfirmations = [];
            window.testAcceptDiscard = false;
            window.confirm = message => { testConfirmations.push(message); return testAcceptDiscard; };
            window.commonNotify = (message, type) => testMessages.push({message, type,
                dirty: commonDrawerHasUnsavedChanges(document.getElementById('commonTopbarDrawer'))});
            // Keep reload effects pending so assertions can inspect the saved form itself.
            const setTimeout = window.setTimeout;
            window.setTimeout = function (callback, delay, ...args) {
                if (delay === 450) { window.testReloadPending = true; return 0; }
                return setTimeout(callback, delay, ...args);
            };
            const load = window.jQuery.fn.load;
            window.jQuery.fn.load = function (url, ...args) {
                if (String(url).includes('/parameters/tips/index.php')) { window.testReloadPending = true; return this; }
                return load.call(this, url, ...args);
            };
        });

        const cases = [
            {name: 'Notifications', path: 'parameters/notifications', form: '[data-omo-notification-preferences]', field: 'input[type="checkbox"]', status: true},
            {name: 'Calendar', path: 'parameters/calendar', form: '[data-omo-calendar-settings]', field: 'select', status: 'ok'},
            {name: 'Tutorials', path: 'parameters/tutorials', form: '[data-omo-tutorial-settings]', field: '[name="customVideoLayout"]', status: 'ok'},
            {name: 'Security', path: 'parameters/security', form: 'form', field: '[name="ai_blocked"]', button: '[data-omo-security-save]', status: 'ok'},
            {name: 'Lexicon', path: 'parameters/lexicon', form: '[data-omo-lexicon-form]', field: '[name="admin_label"]', status: 'ok'},
            {name: 'Structure display', path: 'parameters/structure-display', form: '[data-omo-structure-display-form]', field: '[name="labelMinFontSize"]', status: 'ok'},
            {name: 'Project blocking', path: 'projects/params', form: '[data-omo-projects-params-form]', index: 0, field: '[name="require_blocked_reason"]', status: true},
            {name: 'Project calculations', path: 'projects/params', form: '[data-omo-projects-params-form]', index: 1, field: '[name="parent_weight"]', status: true},
            {name: 'Decisions', path: 'decision/params', form: '[data-omo-decision-params-form]', field: '[name="methods[simple_vote]"]', status: true},
            {name: 'Documents', path: 'documents/params', form: '[data-omo-documents-params-form]', field: '[name="pv_review_days"]', status: true},
            {name: 'Tips', path: 'parameters/tips', form: 'form', field: '[name="text"]', button: '[data-tip-save]', status: true}
        ];
        for (const item of cases) {
            console.log('Checking ' + item.name);
            const url = '/omo/api/' + item.path + '/index.php?oid=' + config.oid;
            await page.evaluate(({name, url}) => window.commonTopbarOpenDrawer(name, url, 'fetch'), {name: item.name, url});
            const host = page.locator('#commonTopbarDrawer');
            const form = host.locator(item.form).nth(item.index || 0);
            await form.waitFor();
            const field = (item.name === 'Security' ? host : form).locator(item.field).first();
            const button = item.button ? host.locator(item.button) : form.locator('[type="submit"]');
            const dirty = () => host.evaluate(node => commonDrawerHasUnsavedChanges(node));
            const change = async () => {
                await field.focus();
                const type = await field.evaluate(node => node.type);
                if (type === 'checkbox') await field.setChecked(!(await field.isChecked()));
                else if (type === 'select-one') {
                    const value = await field.evaluate(node => Array.from(node.options).find(option => option.value !== node.value).value);
                    await field.selectOption(value);
                } else if (type === 'number') {
                    const value = await field.evaluate(node => String(Number(node.value) === Number(node.min || 0) ? Number(node.value) + Number(node.step || 1) : Number(node.min || 0)));
                    await field.fill(value);
                } else await field.fill((await field.inputValue()) + ' test');
            };
            let pending;
            let onRequest;
            const endpoint = '**/omo/api/' + item.path + '/'+ (item.path === 'parameters/notifications' ? 'preferences.php*' : 'save.php*');
            await page.route(endpoint, route => { pending = route; onRequest(); });
            const save = async (httpStatus, status, extra = {}) => {
                pending = null;
                let requestTimeout;
                const requested = new Promise(resolve => { onRequest = resolve; });
                const count = await page.evaluate(() => testMessages.length);
                await button.click();
                try {
                    await Promise.race([requested, new Promise((resolve, reject) => {
                        requestTimeout = setTimeout(() => reject(new Error(item.name + ': save request did not start')), 15000);
                    })]);
                } finally { clearTimeout(requestTimeout); }
                assert(pending, item.name + ': submit reaches the endpoint');
                assert.equal(await dirty(), true, item.name + ': pending requests retain protection');
                await pending.fulfill({status: httpStatus, json: {status, message: 'Fixture response', id: 0, ...extra}});
                await page.waitForFunction(count => testMessages.length > count, count);
            };
            assert.equal(await dirty(), false, item.name + ': initial form is clean');
            await change();
            assert.equal(await dirty(), true, item.name + ': edits are protected');
            await save(500, item.status);
            assert.equal(await dirty(), true, item.name + ': HTTP errors cannot clear protection');
            await save(200, false);
            assert.equal(await dirty(), true, item.name + ': application errors cannot clear protection');
            if (item.name === 'Documents') {
                await save(409, false, {confirmationRequired: true});
                assert.equal(await dirty(), true, 'Declining storage confirmation retains protection');
            }
            await save(200, item.status);
            assert.equal(await dirty(), false, item.name + ': successful save clears protection');
            if (['Lexicon', 'Tips'].includes(item.name)) {
                assert.equal(await page.evaluate(() => testMessages.at(-1).dirty), false, item.name + ': clean before reload');
            }
            const prompts = await page.evaluate(() => testConfirmations.length);
            await host.locator('button[data-topbar-drawer-close]').click();
            assert.equal(await page.evaluate(() => testConfirmations.length), prompts, item.name + ': closes without warning after saving');

            // Reopen the actual panel and verify that a new edit after saving is protected.
            await page.evaluate(({name, url}) => window.commonTopbarOpenDrawer(name, url, 'fetch'), {name: item.name, url});
            await form.waitFor();
            await change();
            await save(200, item.status);
            await change();
            assert.equal(await dirty(), true, item.name + ': subsequent edits are protected');
            await host.locator('button[data-topbar-drawer-close]').click();
            assert.equal(await host.isVisible(), true, item.name + ': declining discard keeps the panel open');
            await page.evaluate(() => { window.testAcceptDiscard = true; });
            await host.locator('button[data-topbar-drawer-close]').click();
            await page.evaluate(() => { window.testAcceptDiscard = false; });
            await page.unroute(endpoint);
            console.log('PASS ' + item.name);
        }
        assert.deepEqual(errors, [], 'No browser script errors');
        console.log('parameter_panels_unsaved_browser_test: OK');
    } finally {
        if (browser) await browser.close();
        fixture.stdin.end('\n');
        await closed;
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
