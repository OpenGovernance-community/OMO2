'use strict';
// Real import script and shared unsaved-change guard; HTTP responses are isolated fixtures.
const assert = require('node:assert/strict');
const path = require('node:path');
const {chromium} = require(process.argv[2] || 'playwright');
const root = path.join(__dirname, '..');

(async () => {
    const browser = await chromium.launch({channel: 'msedge', headless: true});
    try {
        for (const scenario of ['warnings', 'clean', 'no-redirect', 'retry']) {
            const page = await browser.newPage();
            page.setDefaultTimeout(10000);
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            let posts = 0, releaseResponse, requestReady;
            await page.route('http://import.test/**', async route => {
                const pathname = new URL(route.request().url()).pathname;
                if (pathname.endsWith('/import_preview.php')) return route.fulfill({json: {status: true, payload: {holons: [], modules: {}, organization: {name: 'Imported'}}}});
                if (pathname.endsWith('/create_import.php')) {
                    posts++;
                    const reply = await new Promise(resolve => { releaseResponse = resolve; requestReady(); });
                    return route.fulfill({status: reply.status ? 200 : 422, json: reply});
                }
                if (pathname.endsWith('/drawer.php')) return route.fulfill({json: {discard: 'Unsaved changes will be lost.'}});
                return route.fulfill({contentType: 'text/html', body: '<html><body>Organization home</body></html>'});
            });
            await page.goto('http://import.test/');
            await page.setContent(`<div id="commonTopbarModal"><button type="button" data-topbar-modal-close>Close window</button><div data-omo-create-import="1">
                <form data-omo-create-import-form="1"><input type="file" name="omo1_export_file"><input name="organization_name">
                    <select name="member_invitation_email_choice" data-omo-create-import-member-invitation-email-choice="1"><option value="">Choose</option><option value="skip">Skip</option></select>
                    <button type="button" data-omo-create-import-cancel="1">Cancel</button><button type="submit" data-omo-create-import-submit="1" disabled>Import</button></form>
                <section data-omo-create-import-waiting="1" hidden aria-busy="true">Wait</section>
                <section data-omo-create-import-completed="1" hidden><h3>Import complete</h3><div data-omo-create-import-warnings="1" hidden><ul data-omo-create-import-warning-list="1"></ul></div>
                    <a data-omo-create-import-open="1" hidden>Open organization</a><button type="button" data-omo-create-import-close="1">Close</button></section>
                <div data-omo-create-import-feedback="1" hidden></div></div></div>`);
            await page.addStyleTag({path: path.join(root, 'common/assets/components.css')});
            await page.evaluate(() => {
                window.notices = []; window.confirmations = [];
                window.commonNotify = (message, type) => window.notices.push({message, type});
                window.confirm = message => { window.confirmations.push(message); return false; };
                window.commonTopbarCloseModal = () => {
                    const host = document.getElementById('commonTopbarModal');
                    if (window.commonDrawerConfirmDiscard(host)) host.hidden = true;
                };
                document.querySelector('[data-topbar-modal-close]').onclick = window.commonTopbarCloseModal;
            });
            await page.addScriptTag({path: path.join(root, 'common/drawer/subdrawer.js')});
            await page.addScriptTag({path: path.join(root, 'omo/api/organizations/create_import_popup.js')});
            await page.evaluate(() => window.commonPageScripts['/omo/api/organizations/create_import_popup.js']({templateCatalog: [], ui: {genericError: 'Import failed'}}));
            await page.locator('input[type="file"]').setInputFiles({name: 'fixture.json', mimeType: 'application/json', buffer: Buffer.from('{}')});
            await page.waitForFunction(() => !document.querySelector('[data-omo-create-import-submit]').disabled);
            await page.locator('select').selectOption('skip');
            await page.locator('[name="organization_name"]').fill('Chosen organization');
            const dirty = () => page.evaluate(() => window.commonDrawerHasUnsavedChanges(document.getElementById('commonTopbarModal')));
            assert.equal(await dirty(), true, 'An unsubmitted import must retain discard protection');
            async function startImport() {
                const received = new Promise(resolve => { requestReady = resolve; });
                await page.locator('[data-omo-create-import-submit]').click();
                await received;
                assert.equal(await page.locator('[data-omo-create-import-waiting]').isVisible(), true);
                await page.locator('form').dispatchEvent('submit');
            }
            await startImport();
            if (scenario === 'retry') {
                releaseResponse({status: false, message: 'Rejected'});
                await page.waitForFunction(() => window.notices.length === 1);
                assert.equal(await page.locator('[data-omo-create-import-waiting]').isVisible(), false);
                assert.equal(await page.locator('form').isVisible(), true, 'Failed imports must restore the form for retry');
                assert.equal(await dirty(), true, 'Failed imports must not mark the form as saved');
                await page.locator('[data-topbar-modal-close]').click();
                assert.equal(await page.evaluate(() => window.confirmations.length), 1);
                assert.equal(await page.locator('#commonTopbarModal').isVisible(), true, 'Declining discard must keep the failed import editable');
                await page.evaluate(() => { window.confirmations = []; window.notices = []; });
                await startImport();
            }
            const warnings = scenario === 'warnings' || scenario === 'no-redirect' ? ['17 documents skipped', '<img src=x onerror=alert(1)>'] : [];
            releaseResponse({status: true, message: 'Imported', warnings, ...(scenario === 'no-redirect' ? {} : {redirect: 'http://import.test/organization'})});
            await page.locator('[data-omo-create-import-completed]').waitFor({state: 'visible'});
            assert.equal(await page.locator('[data-omo-create-import-waiting]').isVisible(), false);
            assert.equal(await page.locator('[data-omo-create-import-waiting]').getAttribute('aria-busy'), 'false');
            assert.equal(await page.locator('form').isVisible(), false, 'A successful import must not offer a second submission');
            assert.equal(await dirty(), false, 'Success with or without warnings must clear discard protection');
            assert.deepEqual(await page.locator('[data-omo-create-import-warning-list] li').allTextContents(), warnings);
            assert.equal(await page.locator('[data-omo-create-import-warning-list] img').count(), 0, 'Warnings must render as plain text');
            assert.deepEqual(await page.evaluate(() => window.notices), [{message: 'Imported', type: 'success'}], 'Detailed warnings must remain inline without duplicate notifications');
            await page.locator('form').dispatchEvent('submit');
            assert.equal(posts, scenario === 'retry' ? 2 : 1, 'Pending and completed imports must reject duplicate submissions');
            assert.equal(page.url(), 'http://import.test/', 'Completion must let the user read warnings before navigating');
            if (scenario === 'no-redirect') assert.equal(await page.locator('[data-omo-create-import-open]').isVisible(), false);
            else assert.equal(await page.locator('[data-omo-create-import-open]').getAttribute('href'), 'http://import.test/organization');
            if (scenario === 'clean') {
                await page.locator('[data-omo-create-import-open]').click();
                await page.waitForURL('http://import.test/organization');
            } else {
                await page.locator(scenario === 'retry' ? '[data-topbar-modal-close]' : '[data-omo-create-import-close]').click();
                assert.equal(await page.locator('#commonTopbarModal').isVisible(), false);
                assert.deepEqual(await page.evaluate(() => window.confirmations), [], 'Closing a completed import must never warn about data loss');
            }
            assert.deepEqual(errors, []);
            await page.close();
        }
        console.log('organization_import_completion_test: OK');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
