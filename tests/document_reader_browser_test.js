'use strict';
// First render fixtures with tests/document_reader_test.php; run with Playwright on NODE_PATH.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require('playwright');
const root = path.resolve(__dirname, '..');

async function verifyReaderLayout(page, width, media = false) {
    const header = page.locator('header.common-topbar');
    const view = page.locator('#omoDocumentReaderPreview');
    const headerBounds = await header.boundingBox();
    const bounds = await view.boundingBox();
    assert.equal(bounds.x, 0);
    assert.equal(bounds.width, width);
    assert.equal(bounds.y, headerBounds.height, 'Document starts immediately below the title bar');
    assert.equal(bounds.height + headerBounds.height, page.viewportSize().height, 'Document fills the remaining window');
    assert.equal(await page.locator('[data-common-panel-fullscreen]').count(), 0, 'Reader opens directly in its final layout');
    assert(await header.locator('.common-topbar__brand-logo').isVisible());
    assert(await header.locator('.common-topbar__brand-name').isVisible(), 'Document title remains visible on mobile');
    if (media) {
        const previewBounds = await view.locator('iframe, img').boundingBox();
        assert.deepEqual(previewBounds, bounds, 'Visual preview uses the whole document area');
    } else {
        await view.locator('.prose').evaluate(node => { node.innerHTML += '<p>Lecture du document.</p>'.repeat(50); });
        await view.evaluate(node => { node.scrollTop = 600; });
        assert.equal((await header.boundingBox()).y, 0, 'Title bar stays visible while reading');
    }
    const menu = page.locator('[data-topbar-menu="context"]');
    assert.equal(await menu.isVisible(), false);
    await page.getByRole('button', {name: 'Info', exact: true}).click();
    assert(await menu.isVisible(), 'Info menu opens');
    assert(await menu.getByText('Contexte', {exact: true}).isVisible());
    assert.equal(await menu.getByRole('link', {name: 'Voir dans OMO'}).getAttribute('target'), '_blank');
    const menuBounds = await menu.boundingBox();
    assert(menuBounds.x >= 0 && menuBounds.x + menuBounds.width <= width + 1, 'Info menu fits the screen');
    await page.screenshot({path: path.join(root, `tmp/document-reader-info-${media ? 'media' : 'text'}-${width}.png`)});
    await page.keyboard.press('Escape');
    assert.equal(await menu.isVisible(), false, 'Escape closes the Info menu');
    assert.equal(await page.evaluate(() => document.fullscreenElement), null, 'Browser tabs remain visible');
    await page.screenshot({path: path.join(root, `tmp/document-reader-fullscreen-${media ? 'media' : 'text'}-${width}.png`)});
}

(async () => {
    const browser = await chromium.launch({headless: true, channel: 'msedge',
        args: ['--host-resolver-rules=MAP localtest.me 127.0.0.1, MAP *.localtest.me 127.0.0.1']});
    try {
        if (process.argv.includes('--live')) {
            const fixture = path.join(root, 'tmp/document-reader-live.json');
            try {
                const {url, ownerCookie, editorUrl} = JSON.parse(fs.readFileSync(fixture, 'utf8'));
                const page = await browser.newPage({ignoreHTTPSErrors: true, viewport: {width: 1280, height: 1050}});
                const errors = [];
                page.on('pageerror', error => errors.push(error.message));
                await page.route('https://code.jquery.com/**', route => route.fulfill({contentType: 'text/javascript', body: ''}));
                await page.goto(url, {waitUntil: 'domcontentloaded'});
                await page.locator('#omoDocumentReaderPreview iframe').waitFor();
                const office = await (await page.locator('#omoDocumentReaderPreview iframe').elementHandle()).contentFrame();
                await office.waitForFunction(() => window.app?.file?.readOnly === true && window.app?.map?._permission === 'readonly', null, {timeout: 30000});
                await office.locator('.status-readonly-mode').waitFor();
                assert.equal(await office.locator('#viewModeDropdown').isVisible(), false, 'Strict reader offers no editing mode switch');
                assert.equal(await page.locator('[contenteditable="true"]').count(), 0);
                for (const width of [1280, 390]) {
                    await page.setViewportSize({width, height: 1050});
                    assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), 'Collabora reader fits ' + width);
                    await verifyReaderLayout(page, width, true);
                    await page.screenshot({path: path.join(root, `tmp/document-reader-collabora-live-${width}.png`), fullPage: true});
                }
                const ownerContext = await browser.newContext({ignoreHTTPSErrors: true});
                await ownerContext.route('https://code.jquery.com/**', route => route.fulfill({contentType: 'text/javascript', body: ''}));
                await ownerContext.addCookies([ownerCookie]);
                const editor = await ownerContext.newPage();
                await editor.goto(editorUrl, {waitUntil: 'domcontentloaded'});
                await editor.waitForFunction(() => window.app?.map?.wopi?.UserCanWrite === true, null, {timeout: 30000});
                const ownerPage = await ownerContext.newPage();
                await ownerPage.goto(url, {waitUntil: 'domcontentloaded'});
                await ownerPage.locator('#omoDocumentReaderPreview iframe').waitFor();
                const ownerOffice = await (await ownerPage.locator('#omoDocumentReaderPreview iframe').elementHandle()).contentFrame();
                await ownerOffice.waitForFunction(() => window.app?.file?.readOnly === true && window.app?.map?._permission === 'readonly', null, {timeout: 30000});
                assert.equal(await ownerOffice.evaluate(() => app.map.wopi.UserCanWrite), false, 'An existing editable session does not grant writing to the reader');
                assert.equal(await ownerOffice.locator('#viewModeDropdown').isVisible(), false, 'Author also has no editing mode switch in the reader');
                await ownerPage.screenshot({path: path.join(root, 'tmp/document-reader-owner-strict-readonly.png')});
                await ownerContext.close();
                assert.deepEqual(errors, []);
                console.log('document_reader_browser_test: OK (live Collabora strict read-only, anonymous/author, existing editor session, mobile/desktop)');
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
                    if (url.searchParams.get('view') === 'image') {
                        const svg = Buffer.from('<svg xmlns="http://www.w3.org/2000/svg" width="900" height="1200"><rect width="900" height="1200" fill="#005b7a"/></svg>').toString('base64');
                        const html = fs.readFileSync(path.join(root, 'tmp/document-reader-pdf.html'), 'utf8').replace(/<iframe\b[^>]*><\/iframe>/, `<img class="omo-document-reader__image" src="data:image/svg+xml;base64,${svg}" alt="Image preview">`);
                        return route.fulfill({contentType: 'text/html', body: html});
                    }
                    return route.fulfill({contentType: 'text/html', body: fs.readFileSync(path.join(root, 'tmp/document-reader-' + url.searchParams.get('view') + '.html'), 'utf8')});
                }
                if (url.pathname === '/omo/api/documents/upload/download.php') {
                    return route.fulfill({contentType: 'text/html', body: '<!doctype html><input data-fixture-state value="Preview">'});
                }
                if (url.pathname === '/common/jstranslation/panel_view_actions.php') {
                    return route.fulfill({contentType: 'application/json', body: JSON.stringify({more: 'Autres options', fullscreen: 'Plein écran', exitFullscreen: 'Quitter le plein écran'})});
                }
                const file = path.join(root, url.pathname);
                if (!fs.existsSync(file) || !fs.statSync(file).isFile()) return route.fulfill({status: 404, body: ''});
                const types = {'.css': 'text/css', '.js': 'text/javascript', '.png': 'image/png', '.svg': 'image/svg+xml'};
                return route.fulfill({contentType: types[path.extname(file)] || 'text/plain', body: fs.readFileSync(file)});
            });
            for (const view of ['public', 'login', 'denied']) {
                await page.goto('https://reader.test/fixture?view=' + view);
                assert(await page.locator('h1, .common-topbar__brand-name').first().isVisible(), 'Reader state has a title');
                assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), view + ' fits the viewport');
                assert.equal(await page.locator('[contenteditable="true"]').count(), 0);
                if (view === 'public') {
                    assert(await page.getByText('READER_SAVED_CONTENT').isVisible());
                }
                await page.screenshot({path: path.join(root, `tmp/document-reader-${view}-${width}.png`), fullPage: true});
                if (view === 'public') await verifyReaderLayout(page, width);
            }
            for (const media of ['pdf', 'image']) {
                await page.goto('https://reader.test/fixture?view=' + media);
                await verifyReaderLayout(page, width, true);
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
            assert.match(new URL(link).pathname, /^\/doc\/\d+\/[a-z0-9]{8}$/);
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
