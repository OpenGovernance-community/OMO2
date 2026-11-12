'use strict';
// First render fixtures with tests/document_reader_test.php; run with Playwright on NODE_PATH.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require('playwright');
const root = path.resolve(__dirname, '..');

(async () => {
    const browser = await chromium.launch({headless: true, channel: 'msedge'});
    try {
        if (process.argv.includes('--live')) {
            const fixture = path.join(root, 'tmp/document-reader-live.json');
            try {
                const {url} = JSON.parse(fs.readFileSync(fixture, 'utf8'));
                const page = await browser.newPage({ignoreHTTPSErrors: true, viewport: {width: 1280, height: 1050}});
                const errors = [];
                page.on('pageerror', error => errors.push(error.message));
                const ready = page.waitForEvent('framenavigated', {predicate: frame => frame.url().includes('document.localtest.me/browser/'), timeout: 30000});
                await page.goto(url, {waitUntil: 'domcontentloaded'});
                const office = await ready;
                await office.waitForFunction(() => window.app?.file?.readOnly === true && window.app?.map?._permission === 'view', null, {timeout: 30000});
                await office.locator('.status-readonly-mode').waitFor();
                assert.equal(await page.locator('[contenteditable="true"]').count(), 0);
                for (const width of [1280, 390]) {
                    await page.setViewportSize({width, height: 1050});
                    assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), 'Collabora reader fits ' + width);
                    await page.screenshot({path: path.join(root, `tmp/document-reader-collabora-live-${width}.png`), fullPage: true});
                }
                assert.deepEqual(errors, []);
                console.log('document_reader_browser_test: OK (live Collabora, anonymous view mode, mobile/desktop)');
            } finally { if (fs.existsSync(fixture)) fs.unlinkSync(fixture); }
            return;
        }
        for (const width of [390, 1280]) {
            const page = await browser.newPage({viewport: {width, height: 950}});
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.route('https://reader.test/**', route => {
                const url = new URL(route.request().url());
                if (url.pathname === '/fixture') {
                    return route.fulfill({contentType: 'text/html', body: fs.readFileSync(path.join(root, 'tmp/document-reader-' + url.searchParams.get('view') + '.html'), 'utf8')});
                }
                const file = path.join(root, url.pathname);
                if (!fs.existsSync(file) || !fs.statSync(file).isFile()) return route.fulfill({status: 404, body: ''});
                const types = {'.css': 'text/css', '.js': 'text/javascript', '.png': 'image/png', '.svg': 'image/svg+xml'};
                return route.fulfill({contentType: types[path.extname(file)] || 'text/plain', body: fs.readFileSync(file)});
            });
            for (const view of ['public', 'login', 'denied']) {
                await page.goto('https://reader.test/fixture?view=' + view);
                assert(await page.locator('h1').isVisible(), 'Reader state has a title');
                assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), view + ' fits the viewport');
                assert.equal(await page.locator('[contenteditable="true"]').count(), 0);
                if (view === 'public') {
                    assert.equal(await page.getByRole('link', {name: 'Voir dans OMO'}).getAttribute('target'), '_blank');
                    assert(await page.getByText('READER_SAVED_CONTENT').isVisible());
                }
                await page.screenshot({path: path.join(root, `tmp/document-reader-${view}-${width}.png`), fullPage: true});
            }
            await page.goto('https://reader.test/fixture?view=public');
            await page.evaluate(() => {
                window.copied = [];
                window.notifications = [];
                Object.defineProperty(navigator, 'clipboard', {value: {writeText: async text => window.copied.push(text)}, configurable: true});
                window.commonNotify = (message, kind) => window.notifications.push({message, kind});
            });
            await page.setContent(fs.readFileSync(path.join(root, 'tmp/document-reader-popup.html'), 'utf8'));
            for (const file of ['common/assets/theme.css', 'common/assets/components.css', 'omo/assets/css/styles.css']) {
                await page.addStyleTag({path: path.join(root, file)});
            }
            await page.locator('#omoDocumentReaderLink').waitFor();
            const link = await page.locator('#omoDocumentReaderLink').inputValue();
            assert.match(new URL(link).searchParams.get('code'), /^[a-f0-9]{48}$/);
            assert.equal(new URL(link).searchParams.has('id'), false, 'Shared links hide the document id');
            await page.getByRole('button', {name: 'Copier le lien de consultation'}).click();
            assert.deepEqual(await page.evaluate(() => window.copied), [link]);
            assert.equal(await page.evaluate(() => window.notifications[0].kind), 'success');
            await page.evaluate(() => { navigator.clipboard.writeText = async () => {throw new Error('Denied');}; });
            await page.getByRole('button', {name: 'Copier le lien de consultation'}).click();
            assert.equal(await page.evaluate(() => window.notifications[1].kind), 'error');
            const bounds = await page.locator('#omoDocumentReaderLink').boundingBox();
            assert(bounds.x >= 0 && bounds.x + bounds.width <= width, 'Reader link fits the share popup');
            await page.evaluate(() => window.scrollTo({top: 0, behavior: 'instant'}));
            await page.evaluate(() => document.querySelectorAll('*').forEach(node => { if (node.scrollTop) node.scrollTop = 0; }));
            await page.screenshot({path: path.join(root, `tmp/document-reader-popup-${width}.png`), fullPage: true});
            assert.deepEqual(errors, []);

            // Detail actions also work when opened outside the Documents list.
            await page.setContent('<details open><summary>Actions</summary><button data-omo-detail-share-url="/omo/api/documents/share_popup.php?id=42">Partager</button></details>');
            await page.evaluate(() => { window.modals = []; window.commonTopbarOpenModal = (...args) => window.modals.push(args); });
            await page.addScriptTag({path: path.join(root, 'omo/api/documents/detail.js')});
            await page.addScriptTag({path: path.join(root, 'omo/api/documents/detail.js')});
            await page.getByRole('button', {name: 'Partager'}).click();
            assert.deepEqual(await page.evaluate(() => window.modals), [['Partager', '/omo/api/documents/share_popup.php?id=42', 'fetch']]);
            assert.equal(await page.locator('details').getAttribute('open'), null);
            await page.close();
        }
        console.log('document_reader_browser_test: OK (public, login, denied, copy success/error, detail share, mobile/desktop)');
    } finally { await browser.close(); }
})().catch(error => {console.error(error); process.exitCode = 1;});
