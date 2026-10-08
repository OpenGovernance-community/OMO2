'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require(process.argv[2] || 'playwright');
(async () => {
    const browser = await chromium.launch({headless: true, channel: process.env.PV_BROWSER_CHANNEL || 'msedge'});
    try {
        const page = await browser.newPage({viewport: {width: 390, height: 850}});
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        const html = `<!doctype html><html><body>
            <main><a id="document" href="#documents-d42">Attachment</a>
                <a id="external" href="/omo/pv_participation.php#documents-d42" target="_blank">External</a>
                <a id="denied" href="#documents-d99">Unavailable</a><div id="editable-point"></div></main>
            <dialog class="meeting-document-drawer" data-meeting-document-drawer data-pv-document-url="/omo/pv_document.php?pv_token=fixture&amp;pv_document_id=7">
                <div class="generic-drawer-header"><h2 data-meeting-document-title>Document</h2>
                    <a data-meeting-document-external target="_blank">Open window</a><button data-meeting-document-close>Close</button></div>
                <iframe class="meeting-document-drawer__frame"></iframe>
            </dialog>
            <script src="/omo/assets/js/simple-html-field.js"></script>
            <script src="/common/meeting/document-drawer.js"></script><script src="/omo/pv_participation.js"></script>
            <script>document.querySelector('main').addEventListener('click', function (event) { if (!event.target.closest('a[target="_blank"]')) throw new Error('App router should not receive document clicks'); }, true);</script>
            </body></html>`;
        await page.context().route('https://pv-fixture.invalid/**', async route => {
            const url = new URL(route.request().url());
            if (url.pathname.endsWith('.js')) return route.fulfill({contentType: 'text/javascript', body: fs.readFileSync(path.join(__dirname, '..', url.pathname), 'utf8')});
            if (url.pathname === '/omo/pv_document.php') {
                assert.equal(url.searchParams.get('pv_token'), 'fixture');
                assert.equal(url.searchParams.get('pv_document_id'), '7');
                const denied = url.searchParams.get('id') === '99';
                return route.fulfill({status: denied ? 404 : 200, contentType: 'text/html', body: denied ? '<p>Document inaccessible</p>' : '<h1>Attachment</h1><p>Read only content</p>'});
            }
            return route.fulfill({contentType: 'text/html', body: html});
        });
        await page.goto('https://pv-fixture.invalid/omo/pv_participation.php?token=fixture#documents-d42');
        for (const file of ['common/assets/theme.css', 'common/assets/components.css', 'common/meeting/document-drawer.css']) {
            await page.addStyleTag({path: path.join(__dirname, '..', file)});
        }
        const drawer = page.locator('dialog');
        const frame = page.frameLocator('iframe');
        await frame.locator('h1').waitFor();
        assert.equal(await drawer.isVisible(), true, 'Initial document hash opens the drawer.');
        assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true, 'Drawer fits mobile viewport.');
        await page.locator('[data-meeting-document-close]').click();
        await page.waitForFunction(() => !location.hash);
        assert.equal(await drawer.isVisible(), false);
        await page.evaluate(() => {
            window.pointFocusCount = 0;
            window.omoSimpleHtmlField.mount(document.querySelector('#editable-point'), {
                value: '<p><span data-omo-embed-type="document" data-omo-document-id="42" contenteditable="false"><a id="editable-document" href="#documents-d42">Editable point attachment</a></span></p>',
            });
            document.querySelector('#editable-point').addEventListener('focusin', () => { window.pointFocusCount++; });
        });
        await page.locator('#editable-point [data-omo-document-id="42"] a').click();
        await frame.locator('h1').waitFor();
        assert.equal(await page.evaluate(() => window.pointFocusCount), 0, 'Author document click does not focus or lock the point.');
        assert.equal(await page.locator('#editable-point .note-editor').count(), 0, 'Author document click keeps the lightweight preview.');
        await page.locator('[data-meeting-document-close]').click();
        await page.locator('#document').click();
        await frame.locator('h1').waitFor();
        assert.equal(new URL(page.url()).hash, '#documents-d42');
        await page.keyboard.press('Escape');
        await page.waitForFunction(() => !location.hash);
        await page.locator('#document').click();
        await frame.locator('h1').waitFor();
        await page.evaluate(() => history.back());
        await page.waitForFunction(() => !document.querySelector('dialog').open);
        await page.evaluate(() => history.forward());
        await frame.locator('h1').waitFor();
        await page.locator('[data-meeting-document-close]').click();
        await page.locator('#denied').click();
        await frame.getByText('Document inaccessible').waitFor();
        await page.locator('[data-meeting-document-close]').click();
        const popupPromise = page.waitForEvent('popup');
        await page.locator('#external').click();
        const popup = await popupPromise;
        await popup.waitForURL('**/omo/pv_participation.php?token=fixture#documents-d42');
        assert.equal(new URL(popup.url()).searchParams.get('token'), 'fixture', 'External document links keep the participant token.');
        assert.deepEqual(errors, []);
        console.log('pv_public_document_browser_test: OK (direct hash, author preview click, reopen, Escape, history, access message, external link, mobile)');
    } finally { await browser.close(); }
})().catch(error => {console.error(error); process.exitCode = 1;});
