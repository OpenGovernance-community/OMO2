'use strict';
// First render fixtures with DOCUMENT_HISTORY_RENDER_DIR via document_history_integration_test.php.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require('playwright');
const root = path.resolve(__dirname, '..');
const read = file => fs.readFileSync(path.join(root, file), 'utf8');

(async () => {
    const browser = await chromium.launch({headless: true, channel: 'msedge'});
    try {
        for (const width of [390, 1280]) {
            const page = await browser.newPage({viewport: {width, height: 950}});
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.route('https://documents.test/**', route => route.fulfill({body: '<!doctype html><body></body>'}));
            await page.goto('https://documents.test/');
            const html = read('tmp/document-history-detail.html').replace(/<script\b[^>]*>[\s\S]*?<\/script>/gi, '').replace(/<link\b[^>]*>/gi, '');
            await page.setContent(html);
            for (const file of ['common/assets/theme.css', 'common/assets/components.css', 'omo/api/documents/detail.css']) {
                await page.addStyleTag({content: read(file).replace(/@import[^;]+;/g, '')});
            }
            await page.addStyleTag({content: 'body{margin:16px;font:16px Arial} .omo-document-detail{max-width:900px;margin:auto}'});
            await page.evaluate(() => {
                window.notifications = [];
                window.commonNotify = (message, kind) => window.notifications.push({message, kind});
            });
            await page.addScriptTag({path: path.join(root, 'omo/api/documents/detail.js')});
            await page.addScriptTag({path: path.join(root, 'omo/api/documents/detail.js')});
            const history = page.locator('[data-omo-document-history]');
            const button = history.locator('[data-omo-document-archive-id]');
            const list = history.locator('[data-omo-document-history-list]');
            assert.equal(await list.locator('li').count(), 3);
            const item = await list.locator('li').first().evaluate(node => node.outerHTML);
            let calls = 0;
            let fail = false;
            await page.route('**/history_action.php', async route => {
                calls++;
                assert.equal(route.request().method(), 'POST');
                assert(Number(JSON.parse(route.request().postData()).id) > 0);
                await new Promise(resolve => setTimeout(resolve, 80));
                await route.fulfill({status: fail ? 422 : 200, contentType: 'application/json', body: JSON.stringify(fail
                    ? {status: false, message: 'Stockage indisponible.'}
                    : {status: true, message: 'La version actuelle a été archivée.', html: item})});
            });
            await button.evaluate(node => {node.click(); node.click();});
            await page.waitForFunction(() => window.notifications.length === 1);
            assert.equal(calls, 1, 'Repeated initialization and rapid clicks make one request');
            assert.equal(await list.locator('li').count(), 4, 'New archive appears immediately');
            assert(await button.isEnabled(), 'Button becomes available after completion');
            assert.deepEqual(await page.evaluate(() => window.notifications.map(item => item.kind)), ['success']);
            fail = true;
            await button.click();
            await page.waitForFunction(() => window.notifications.length === 2);
            assert.equal(await list.locator('li').count(), 4, 'Failure leaves the archive list intact');
            assert.deepEqual(await page.evaluate(() => window.notifications[1]), {message: 'Stockage indisponible.', kind: 'error'});
            assert(await button.isEnabled() && await button.getAttribute('aria-busy') === null, 'Failure clears progress');
            const bounds = await history.boundingBox();
            assert(bounds.x >= 0 && bounds.x + bounds.width <= width + 1, 'History fits the viewport');
            assert.deepEqual(errors, []);
            await history.screenshot({path: path.join(root, `tmp/document-history-${width}.png`)});
            await page.close();
        }
        console.log('document_history_browser_test: OK');
    } finally { await browser.close(); }
})().catch(error => {console.error(error); process.exitCode = 1;});
