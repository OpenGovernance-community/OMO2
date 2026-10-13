'use strict';

// Run with playwright on NODE_PATH and a local Edge installation. No server or DB.
const assert = require('node:assert/strict');
const path = require('node:path');
const {chromium} = require('playwright');

(async () => {
    const browser = await chromium.launch({channel: 'msedge', headless: true});
    try {
        const page = await browser.newPage();
        for (const action of ['archive', 'restore']) {
            await page.setContent(`<div data-holon-archives>
                <form action="/omo/api/holons/archive.php" method="post">
                    <input type="hidden" name="_csrf" value="test-token">
                    <input type="hidden" name="hid" value="17">
                    <input type="hidden" name="action" value="${action}">
                    ${action === 'archive' ? '<select name="authority_choice" required><option value="">Choose</option><option value="reassign">Reassign</option></select>' : ''}
                    <button type="submit">Confirm</button>
                </form>
            </div>`);
            await page.evaluate(() => {
                window.notices = [];
                window.modalClosed = false;
                window.omoNotify = (...args) => window.notices.push(args);
                window.commonTopbarCloseModal = () => { window.modalClosed = true; };
                window.omoReloadStructureAndFocus = () => {};
                window.fetch = async (url, options) => {
                    window.sent = {url: String(url), method: options.method, body: Object.fromEntries(options.body)};
                    if (window.returnHtml) return new Response('<!DOCTYPE html><html></html>', {status: 500});
                    return new Response(JSON.stringify({status: true, message: 'Saved', targetId: 42}));
                };
            });
            await page.addScriptTag({path: path.join(__dirname, '../omo/api/holons/archive.js')});
            await page.evaluate(() => window.commonPageScripts['/omo/api/holons/archive.js']({
                organizationId: 12, rootId: 42, title: 'Archives', error: 'Archive failed'
            }));
            await page.locator('button').click();
            if (action === 'archive') {
                assert.equal(await page.evaluate(() => window.sent), undefined, 'An empty choice must block confirmation');
                await page.locator('select').selectOption('reassign');
                await page.locator('button').click();
            }
            await page.waitForFunction(() => window.notices.length === 1);
            assert.deepEqual(await page.evaluate(() => window.sent), {
                url: '/omo/api/holons/archive.php', method: 'POST',
                body: {_csrf: 'test-token', hid: '17', action, ...(action === 'archive' ? {authority_choice: 'reassign'} : {})}
            }, 'The action field must not shadow the POST destination');
            assert.deepEqual(await page.evaluate(() => window.notices), [['Saved', 'success']]);
            assert(await page.evaluate(() => window.modalClosed));

            await page.evaluate(() => {
                window.returnHtml = true;
                window.modalClosed = false;
                document.querySelector('button').disabled = false;
            });
            await page.locator('button').click();
            await page.waitForFunction(() => window.notices.length === 2);
            assert.deepEqual(await page.evaluate(() => window.notices[1]), ['Archive failed', 'error']);
            assert(await page.locator('button').isEnabled(), 'A non-JSON failure must allow retry');
            assert.equal(await page.evaluate(() => window.modalClosed), false, 'Keep the form open on failure');
            await page.evaluate(() => { window.returnHtml = false; });
        }
        console.log('holon_archive_dom_test: OK');
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
