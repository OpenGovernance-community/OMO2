'use strict';
// First render fixtures with tests/document_reader_test.php; run with Playwright on NODE_PATH.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require('playwright');
const root = path.resolve(__dirname, '..');

async function verifyWindowPreview(page, width, media = false) {
    const openButton = page.locator('[data-common-panel-fullscreen-target]');
    const button = page.locator('[data-common-panel-view] [data-common-panel-fullscreen]');
    const view = page.locator('[data-common-panel-view]');
    assert.equal(await view.locator('[data-common-panel-media-toolbar]').isVisible(), false, 'Normal preview has no extra toolbar row');
    assert.equal(await openButton.locator('..').getByRole('link', {name: 'Voir dans OMO'}).count(), 1, 'Fullscreen is in the document header');
    await page.evaluate(() => {
        window.nativeCalls = 0;
        Element.prototype.requestFullscreen = () => { window.nativeCalls++; throw new Error('Native fullscreen must not be requested'); };
        if (document.querySelector('.omo-document-share__content')) {
            document.querySelector('.omo-document-share__content').innerHTML += '<p>Lecture du document.</p>'.repeat(50);
        } else {
            const spacer = document.createElement('div');
            spacer.style.height = '500px';
            document.querySelector('main').appendChild(spacer);
        }
        window.scrollTo({top: 60, behavior: 'instant'});
    });
    const scrollTop = await page.evaluate(() => window.scrollY);
    assert(scrollTop > 0, 'Standalone reading pages can scroll');
    const framedInput = page.frameLocator('[data-common-panel-media-content] iframe').locator('[data-fixture-state]');
    const hasFramedInput = await page.locator('[data-common-panel-media-content] iframe').count() > 0 && await framedInput.count() > 0;
    if (hasFramedInput) await framedInput.fill('Preserved preview state');
    await page.evaluate(() => window.addEventListener('click', event => {
        const button = event.target.closest('[data-common-panel-fullscreen]');
        const target = button.getAttribute('data-common-panel-fullscreen-target');
        const rect = (target ? document.getElementById(target) : button.closest('[data-common-panel-view]')).getBoundingClientRect();
        window.previewEntry = {scrollTop: window.scrollY, rect: {x: rect.x, y: rect.y, width: rect.width, height: rect.height}};
    }, {capture: true, once: true}));
    let frameNavigations = 0;
    const onNavigation = frame => { if (frame !== page.mainFrame()) frameNavigations++; };
    page.on('framenavigated', onNavigation);
    await openButton.click();
    const entry = await page.evaluate(() => window.previewEntry);
    assert.equal(await button.getAttribute('aria-pressed'), 'true');
    const bounds = await view.boundingBox();
    assert.deepEqual(bounds, {x: 0, y: 0, width, height: page.viewportSize().height}, 'Preview fills the browser window');
    assert(await button.isVisible(), 'Return control stays visible');
    assert(await button.evaluate(node => {
        const rect = node.getBoundingClientRect();
        return rect.top >= 0 && rect.right <= innerWidth && node.contains(document.elementFromPoint(rect.x + rect.width / 2, rect.y + rect.height / 2));
    }), 'Return control remains inside the viewport and above the preview');
    if (media) {
        const content = page.locator('[data-common-panel-media-content]');
        const rect = await content.boundingBox();
        assert(rect.height > page.viewportSize().height - 100, 'Media fills the space below the toolbar');
        const mediaBounds = await content.locator('iframe, img').boundingBox();
        assert(mediaBounds.y >= rect.y && mediaBounds.y + mediaBounds.height <= page.viewportSize().height + 1, 'Visual preview stays within the window');
    } else {
        await view.evaluate(node => { node.scrollTop = 600; });
        const rect = await button.boundingBox();
        assert(rect.y >= 0 && rect.y < 80, 'Text reading keeps the exit button at the top');
    }
    await page.screenshot({path: path.join(root, `tmp/document-reader-fullscreen-${media ? 'media' : 'text'}-${width}.png`)});
    await button.click();
    assert.equal(await openButton.getAttribute('aria-pressed'), 'false');
    assert.equal(await button.getAttribute('aria-pressed'), 'false');
    assert.equal(await page.evaluate(() => window.scrollY), entry.scrollTop, 'Leaving fullscreen restores page scroll at ' + width);
    const restored = await view.boundingBox();
    for (const axis of ['x', 'y', 'width', 'height']) assert(Math.abs(restored[axis] - entry.rect[axis]) < 1, 'Original geometry returns: ' + axis);
    await openButton.click();
    await button.focus();
    await page.keyboard.press('Escape');
    assert.equal(await button.getAttribute('aria-pressed'), 'false', 'Escape exits window mode');
    assert.equal(frameNavigations, 0, 'Changing preview size never reloads its iframe');
    if (hasFramedInput) assert.equal(await framedInput.inputValue(), 'Preserved preview state', 'Iframe state survives resizing');
    assert.equal(await page.evaluate(() => window.nativeCalls), 0, 'Browser tabs remain visible');
    page.off('framenavigated', onNavigation);
}

(async () => {
    const browser = await chromium.launch({headless: true, channel: 'msedge'});
    try {
        if (process.argv.includes('--live')) {
            const fixture = path.join(root, 'tmp/document-reader-live.json');
            try {
                const {url, ownerCookie, editorUrl} = JSON.parse(fs.readFileSync(fixture, 'utf8'));
                const page = await browser.newPage({ignoreHTTPSErrors: true, viewport: {width: 1280, height: 1050}});
                const errors = [];
                page.on('pageerror', error => errors.push(error.message));
                const ready = page.waitForEvent('framenavigated', {predicate: frame => frame.url().includes('document.localtest.me/browser/'), timeout: 30000});
                await page.goto(url, {waitUntil: 'domcontentloaded'});
                const office = await ready;
                await office.waitForFunction(() => window.app?.file?.readOnly === true && window.app?.map?._permission === 'readonly', null, {timeout: 30000});
                await office.locator('.status-readonly-mode').waitFor();
                assert.equal(await office.locator('#viewModeDropdown').isVisible(), false, 'Strict reader offers no editing mode switch');
                assert.equal(await page.locator('[contenteditable="true"]').count(), 0);
                for (const width of [1280, 390]) {
                    await page.setViewportSize({width, height: 1050});
                    assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), 'Collabora reader fits ' + width);
                    await verifyWindowPreview(page, width, true);
                    await page.screenshot({path: path.join(root, `tmp/document-reader-collabora-live-${width}.png`), fullPage: true});
                }
                const ownerContext = await browser.newContext({ignoreHTTPSErrors: true});
                await ownerContext.addCookies([ownerCookie]);
                const editor = await ownerContext.newPage();
                await editor.goto(editorUrl, {waitUntil: 'domcontentloaded'});
                await editor.waitForFunction(() => window.app?.map?.wopi?.UserCanWrite === true, null, {timeout: 30000});
                const ownerPage = await ownerContext.newPage();
                const ownerReady = ownerPage.waitForEvent('framenavigated', {predicate: frame => frame.url().includes('document.localtest.me/browser/'), timeout: 30000});
                await ownerPage.goto(url, {waitUntil: 'domcontentloaded'});
                const ownerOffice = await ownerReady;
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
                assert(await page.locator('h1').isVisible(), 'Reader state has a title');
                assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), view + ' fits the viewport');
                assert.equal(await page.locator('[contenteditable="true"]').count(), 0);
                if (view === 'public') {
                    assert.equal(await page.getByRole('link', {name: 'Voir dans OMO'}).getAttribute('target'), '_blank');
                    assert(await page.getByText('READER_SAVED_CONTENT').isVisible());
                }
                await page.screenshot({path: path.join(root, `tmp/document-reader-${view}-${width}.png`), fullPage: true});
                if (view === 'public') await verifyWindowPreview(page, width);
            }
            for (const media of ['pdf', 'image']) {
                await page.goto('https://reader.test/fixture?view=' + media);
                await verifyWindowPreview(page, width, true);
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
