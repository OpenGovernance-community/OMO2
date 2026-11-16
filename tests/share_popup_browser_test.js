'use strict';
// Fixtures are rendered by document_share_links_test.php.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require('playwright');
const root = path.resolve(__dirname, '..');

(async () => {
    const browser = await chromium.launch({channel: 'msedge', headless: true});
    try {
        for (const width of [390, 960]) {
            const page = await browser.newPage({viewport: {width, height: 900}});
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            const posts = [];
            await page.route('https://share.test/**', route => {
                const url = new URL(route.request().url());
                if (route.request().method() === 'POST') {
                    posts.push({url: url.pathname, body: route.request().postData()});
                    return route.fulfill({contentType: 'application/json', body: JSON.stringify({status: false, message: 'Fixture'})});
                }
                if (url.pathname === '/omo/') return route.fulfill({contentType: 'text/html', body: '<!doctype html><html><head><link rel="stylesheet" href="/common/assets/components.css"><link rel="stylesheet" href="/omo/assets/css/styles.css"><link rel="stylesheet" href="/common/assets/topbar.css"></head><body></body></html>'});
                const file = path.join(root, url.pathname);
                if (!fs.existsSync(file) || !fs.statSync(file).isFile()) return route.fulfill({status: 404, body: ''});
                const contentType = {'.css': 'text/css', '.js': 'text/javascript', '.png': 'image/png'}[path.extname(file)] || 'text/plain';
                return route.fulfill({contentType, body: fs.readFileSync(file)});
            });
            for (const type of ['document', 'structure']) {
                await page.goto('https://share.test/omo/');
                const html = fs.readFileSync(path.join(root, `tmp/${type}-share-popup.html`), 'utf8');
                await page.evaluate(() => {
                    window.copied = []; window.notices = [];
                    window.commonNotify = (message, kind) => window.notices.push({message, kind});
                    window.confirm = () => true;
                    Object.defineProperty(navigator, 'clipboard', {configurable: true, value: {writeText: async value => window.copied.push(value)}});
                });
                await page.setContent(`<!doctype html><html><head><link rel="stylesheet" href="/common/assets/components.css"><link rel="stylesheet" href="/omo/assets/css/styles.css"><link rel="stylesheet" href="/common/assets/topbar.css"></head><body><div role="dialog" aria-label="Partager" class="common-topbar-modal__panel" style="margin:20px auto;max-height:calc(100vh - 40px)"><header class="common-topbar-modal__header"><h3>Partager</h3><button aria-label="Fermer">×</button></header><div class="common-topbar-modal__body">${html}</div></div></body></html>`);
                const prefix = type === 'document' ? 'omoDocumentSharePopup' : 'omoSharePopup';
                const popup = page.locator('#' + prefix + 'Root');
                await popup.locator('[data-share-id]').first().waitFor();
                if (type === 'document') {
                    const icon = popup.locator('.omo-share-popup__resource-icon');
                    assert(await icon.evaluate(node => node.complete && node.naturalWidth > 0), 'Document type icon loads');
                    assert((await icon.getAttribute('src')).includes('/collaborative.png'));
                    const description = popup.locator('.omo-share-popup__resource-description');
                    assert(await description.isVisible(), 'Document description is visible');
                    assert(await description.evaluate(node => getComputedStyle(node).whiteSpace === 'nowrap' && getComputedStyle(node).textOverflow === 'ellipsis'), 'Description truncates on one line');
                    if (width === 390) assert(await description.evaluate(node => node.scrollWidth > node.clientWidth), 'Long description is truncated on mobile');
                }
                assert.equal(await popup.locator('h2').count(), 0, 'Modal owns the main title');
                assert.equal(await popup.locator('.generic-drawer-header').count(), 0, 'No nested title band');
                assert.equal(await popup.locator('[data-share-id]').count(), 3);
                for (const label of ['Sans mot de passe', 'Mot de passe', 'Sans expiration', 'Lecture seule']) {
                    assert(await popup.getByText(label, {exact: true}).first().isVisible(), type + ' displays ' + label);
                }
                assert(await popup.getByText(/^Expiré le /).isVisible());
                assert(await popup.getByText(/^Expire le /).isVisible());
                if (type === 'document') assert(await popup.getByText('Édition autorisée', {exact: true}).first().isVisible());
                for (const theme of ['light', 'dark']) {
                    await page.evaluate(theme => {document.documentElement.dataset.theme = theme;}, theme);
                    assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), 'Popup fits ' + width);
                    const height = await popup.boundingBox();
                    assert(height.height < 620, type + ' list remains compact');
                    await page.screenshot({path: path.join(root, `tmp/share-${type}-list-${width}-${theme}.png`)});
                }
                const card = popup.locator('[data-share-id][data-has-password="1"]');
                await card.getByRole('button', {name: 'Copier', exact: true}).click();
                assert.match((await page.evaluate(() => window.copied))[0], /^https:\/\//);
                await card.getByRole('button', {name: 'Modifier', exact: true}).click();
                assert.equal(await page.locator('#' + prefix + 'Label').inputValue(), 'Équipe projet');
                assert(await page.locator('#' + prefix + 'ClearPasswordWrap').isVisible());
                assert.notEqual(await page.locator('#' + prefix + 'Expiration').inputValue(), '');
                if (type === 'document') assert.equal(await popup.locator('.omo-share-popup__reader').isVisible(), false);
                if (type === 'structure') {
                    await page.locator('#' + prefix + 'AllowPeople').uncheck();
                    assert(await page.locator('#' + prefix + 'AllowPeopleDetail').isDisabled());
                }
                await page.screenshot({path: path.join(root, `tmp/share-${type}-edit-${width}.png`)});
                await page.locator('#' + prefix + 'Submit').click();
                await page.waitForFunction(() => window.notices.length > 0);
                assert(posts.at(-1).body.includes('share_id'), 'Edit submits the existing link');
                await page.locator('#' + prefix + 'CancelButton').click();
                assert(await page.locator('#' + prefix + 'ListSection').isVisible());
                if (type === 'document') assert(await popup.locator('.omo-share-popup__reader').isVisible());
                await page.locator('#' + prefix + 'NewButton').click();
                assert.equal(await page.locator('#' + prefix + 'ShareId').inputValue(), '');
                assert.equal(await page.locator('#' + prefix + 'Expiration').inputValue(), '');
                assert.equal(await page.locator('#' + prefix + 'ClearPasswordWrap').isVisible(), false);
                await page.locator('#' + prefix + 'CancelButton').click();
                await card.getByRole('button', {name: 'Supprimer', exact: true}).click();
                await page.waitForFunction(() => window.notices.length > 1);
                assert(posts.at(-1).url.endsWith('delete.php'));
                assert.deepEqual(errors, []);
            }
            await page.close();
        }
        console.log('share_popup_browser_test: OK (structure/documents, compact lists, status indicators, editing, copying, deletion, mobile/desktop, themes)');
    } finally { await browser.close(); }
})().catch(error => {console.error(error); process.exitCode = 1;});
