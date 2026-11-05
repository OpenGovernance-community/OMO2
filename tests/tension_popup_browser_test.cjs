'use strict';
const assert = require('node:assert/strict');
const {spawn} = require('node:child_process');
const {createInterface} = require('node:readline');
const {chromium} = require('playwright');

(async () => {
    const fixture = spawn('docker', ['compose', 'exec', '-T', 'app', 'php', 'tests/tension_popup_browser_fixture.php'], {stdio: ['pipe', 'pipe', 'inherit']});
    const closed = new Promise(resolve => fixture.on('close', resolve));
    fixture.stdin.on('error', error => { if (error.code !== 'EPIPE') { throw error; } });
    const lines = createInterface({input: fixture.stdout});
    let browser;
    let page;
    try {
        const first = await lines[Symbol.asyncIterator]().next();
        assert(!first.done, 'Local fixture must start.');
        const config = JSON.parse(first.value);
        browser = await chromium.launch({channel: 'msedge', headless: true});
        const context = await browser.newContext({ignoreHTTPSErrors: true, locale: 'fr-CH', extraHTTPHeaders: {Origin: config.origin}});
        const login = await context.request.post(config.origin + '/common/login_password.php', {
            form: {email: config.email, password: config.password}, headers: {'X-Requested-With': 'XMLHttpRequest'}
        });
        assert.equal((await login.json()).status, 'ok');
        page = await context.newPage();
        page.setDefaultTimeout(15000);
        const errors = [];
        page.on('pageerror', error => {
            // The external login widget is unrelated to this authenticated popup.
            if (error.message !== 'turnstile is not defined') { errors.push(error.message); }
        });
        page.on('response', async response => {
            if (response.url().includes('/tension_list.php') && response.status() >= 500) {
                console.error((await response.text()).slice(0, 2500));
            }
        });
        await page.route('https://code.jquery.com/jquery-3.7.1.min.js', route => route.fulfill({path: require.resolve('jquery/dist/jquery.js')}));
        await page.goto(config.origin + '/omo/o/' + config.oid + '/', {waitUntil: 'domcontentloaded', timeout: 30000});

        let releaseList;
        const listGate = new Promise(resolve => { releaseList = resolve; });
        let capturedList;
        const listCaptured = new Promise(resolve => { capturedList = resolve; });
        await page.route('**/omo/api/tension_list.php?*', async route => {
            const response = await route.fetch();
            capturedList();
            await listGate;
            await route.fulfill({response});
        }, {times: 1});
        await page.locator('[data-topbar-tension-report]').click();
        await page.locator('#omoTensionForm').waitFor({state: 'visible'});
        await listCaptured;
        assert.equal(await page.locator('#omoTensionPopup .generic-drawer-header').evaluate(node => node.getBoundingClientRect().height < 90), true, 'Header stays compact.');
        assert.equal(await page.locator('#omoTensionPopup [data-generic-tab]').count(), 2);
        await page.locator('#omoTensionTitle').fill('New browser tension');
        await page.locator('#omoTensionDescription').fill('Synthetic tension from the popup.');
        let releaseSave;
        const saveGate = new Promise(resolve => { releaseSave = resolve; });
        await page.route('**/omo/api/tension_save.php?*', async route => { await saveGate; await route.continue(); }, {times: 1});
        await page.locator('#omoTensionSubmit').click();
        assert.equal(await page.locator('#omoTensionSubmit').isDisabled(), true);
        assert.match(await page.locator('#omoTensionSubmit').textContent(), /Enregistrement/);
        assert.equal(await page.locator('.common-notification--error').count(), 0, 'Saving is not an error notification.');
        const saved = page.waitForResponse(response => response.url().includes('/tension_save.php'));
        releaseSave();
        assert.equal((await saved).status(), 200);
        releaseList();
        await page.waitForFunction(() => !document.getElementById('omoTensionSubmit').disabled);
        assert.equal(await page.locator('#commonTopbarModal').isVisible(), true, 'Successful saves keep the popup open.');
        assert.equal(await page.locator('#omoTensionTitle').inputValue(), '');
        await page.locator('[data-generic-tab-target="omoTensionListPanel"]').click();
        await page.locator('#omoTensionList h3', {hasText: 'New browser tension'}).waitFor();
        assert.equal(await page.locator('#omoTensionList > article').count(), 2, 'A queued refresh includes the new tension and only the current author s records.');
        assert.equal(await page.locator('[data-tension-id="' + config.otherId + '"]').count(), 0, 'Other authors tensions must not be displayed.');
        assert.equal(await page.locator('[data-generic-tab-target="omoTensionListPanel"]').textContent(), 'Mes tensions');
        const own = page.locator('[data-tension-id="' + config.ownId + '"]');
        assert.equal(await own.locator('script').count(), 0, 'Descriptions render as plain text.');
        await own.locator('[data-omo-tension-menu]').click();
        await page.keyboard.press('Escape');
        assert.equal(await page.locator('#commonTopbarModal').isVisible(), true, 'Escape closes the row menu first.');
        assert.equal(await own.locator('[role="menu"]').isVisible(), false);
        await own.locator('[data-omo-tension-menu]').click();
        page.once('dialog', dialog => dialog.dismiss());
        await own.locator('[data-omo-tension-delete]').click();
        assert.equal(await own.count(), 1, 'Cancelling removal preserves the tension.');
        page.once('dialog', dialog => dialog.accept());
        await own.locator('[data-omo-tension-delete]').click();
        await own.waitFor({state: 'detached'});

        const api = path => config.origin + '/omo/api/' + path;
        const post = data => context.request.post(api('tension_delete.php?oid=' + data.oid), {form: data});
        const personalListUrl = api('tension_list.php?oid=' + config.oid + '&IDuser=0&includeInactive=1');
        const personalList = await (await context.request.get(personalListUrl)).json();
        assert.equal(personalList.items.length, 1, 'The API must filter by the authenticated author and exclude removed tensions.');
        assert.equal(personalList.items[0].title, 'New browser tension');
        assert.equal((await post({oid: config.oid, id: config.otherId})).status(), 403);
        assert.equal((await context.request.get(api('tension_list.php?oid=' + config.otherOid))).status(), 403);
        assert.equal((await post({oid: config.otherOid, id: config.otherId})).status(), 403);
        assert.equal((await post({oid: config.oid, id: config.foreignId})).status(), 404, 'The author cannot remove a record by passing the wrong organization.');
        assert.equal((await context.request.get(api('tension_delete.php?oid=' + config.oid + '&id=' + config.otherId))).status(), 405);
        assert.equal((await context.request.post(api('tension_delete.php'), {form: {oid: config.oid, id: config.otherId}, headers: {Origin: 'https://foreign.example.invalid'}})).status(), 403);
        const admin = await context.request.post(config.origin + '/common/admin_mode.php', {
            form: {organization_id: config.oid, enabled: 1}, headers: {'X-Requested-With': 'XMLHttpRequest'}
        });
        assert.equal((await admin.json()).active, true);
        const adminList = await (await context.request.get(personalListUrl)).json();
        assert.deepEqual(adminList.items.map(item => item.id), personalList.items.map(item => item.id), 'Admin mode must keep the list personal.');
        assert.equal((await post({oid: config.oid, id: config.otherId})).status(), 200, 'Admin mode can remove another author s tension.');
        await page.locator('[data-omo-tension-refresh]').click();
        await page.locator('[data-tension-id="' + config.otherId + '"]').waitFor({state: 'detached'});
        await page.evaluate(() => document.querySelectorAll('.common-notification').forEach(node => window.commonDismissNotification(node.id)));
        await page.waitForFunction(() => !document.querySelector('.common-notification'));
        await page.setViewportSize({width: 1280, height: 900});
        await page.screenshot({path: 'tmp/tension-popup-desktop.png'});
        await page.setViewportSize({width: 390, height: 844});
        assert.equal(await page.locator('#omoTensionPopup').evaluate(node => node.scrollWidth <= node.clientWidth), true, 'Mobile popup has no horizontal overflow.');
        await page.screenshot({path: 'tmp/tension-popup-mobile.png'});
        await page.locator('#commonTopbarModal button[data-topbar-modal-close]').click();
        await page.locator('[data-topbar-tension-report]').click();
        await page.locator('#omoTensionForm').waitFor({state: 'visible'});
        await page.screenshot({path: 'tmp/tension-popup-form-mobile.png'});
        await page.setViewportSize({width: 1280, height: 900});
        await page.screenshot({path: 'tmp/tension-popup-form-desktop.png'});
        assert.deepEqual(errors, []);
        console.log('tension_popup_browser_test: OK (personal list and API including admin mode, tabs, save, removal, permissions, mobile, reopen)');
    } catch (error) {
        if (page && !page.isClosed()) {
            await page.screenshot({path: 'tmp/tension-popup-failure.png'});
            console.error(await page.locator('#omoTensionPopup').innerText().catch(() => 'Popup missing'));
        }
        throw error;
    } finally {
        if (browser) await browser.close();
        fixture.stdin.end('\n');
        await closed;
        lines.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
