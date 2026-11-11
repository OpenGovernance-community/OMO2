'use strict';
// Run with Playwright on NODE_PATH. Exercises the PHP preview markup without storage access.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require('playwright');
const root = path.resolve(__dirname, '..');
const read = file => fs.readFileSync(path.join(root, file), 'utf8');

function previewMarkup(remote, pdf) {
    const source = read(remote ? 'omo/api/documents/nextcloud/detail.php' : 'omo/api/documents/detail.php');
    const start = remote ? source.indexOf('    <article class=') : source.indexOf('        <section\n');
    const end = source.indexOf(remote ? '</article>' : '</section>', start);
    const frame = `<iframe class="${pdf ? 'omo-document-file__pdf-frame' : 'omo-document-collabora__frame'}" srcdoc="<input value='Saved'>"></iframe>`;
    let html = source.slice(start, end + 10);
    if (remote) {
        const contentStart = html.indexOf('        <?php if ($isPdf): ?>');
        const contentEnd = html.indexOf('        </div>', contentStart);
        html = html.slice(0, contentStart) + frame + '\n' + html.slice(contentEnd);
    } else {
        html = html.replace('<?= $renderedContent ?>', `<div class="${pdf ? 'omo-document-file__media--pdf' : 'omo-document-collabora'}">${frame}</div>`);
    }
    return html
        .replace(/<\?= \$escape\((?:omoDocumentsDetailT|\$t)\('([^']+)'\)\) \?>/g, (_, key) => key.endsWith('.download') ? 'Télécharger' : key.endsWith('.exit_fullscreen') ? 'Quitter le plein écran' : 'Plein écran')
        .replace(/<\?= \$escape\(\$(?:previewDownloadUrl|downloadUrl)\) \?>/g, '/download')
        .replace(/<\?= \$escape\(\$(?:previewDownloadName|filename)\) \?>/g, 'Document.docx')
        .replace(/<\?[\s\S]*?\?>/g, '');
}

(async () => {
    const browser = await chromium.launch({headless: true, channel: 'msedge'});
    try {
        for (const remote of [false, true]) for (const pdf of [false, true]) for (const width of [320, 1440]) {
            const page = await browser.newPage({viewport: {width, height: 900}});
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.route('https://documents.test/**', route => route.fulfill({
                body: route.request().url().endsWith('/download') ? 'Original file' : '<!doctype html><body></body>',
                headers: route.request().url().endsWith('/download') ? {'Content-Disposition': 'attachment; filename="Document.docx"'} : {},
            }));
            await page.goto('https://documents.test/');
            await page.setContent(`<div class="panel panel-right"><div class="drawer open"><div class="omo-panel-view">
                <header id="app-header">Documents</header><div data-omo-document-detail-drawer class="omo-overlay-drawer is-open">
                <div class="omo-overlay-drawer__backdrop"></div><div class="omo-overlay-drawer__panel">
                <header>Document details</header><div class="omo-overlay-drawer__body"><div class="omo-document-detail">
                <article class="omo-document-detail__article generic-stack"><header>Description</header>${previewMarkup(remote, pdf)}<footer>Metadata</footer></article>
                </div></div></div></div></div></div></div>`);
            for (const file of ['common/assets/theme.css', 'common/assets/components.css', 'omo/assets/css/styles.css', 'omo/api/documents/detail.css']) {
                await page.addStyleTag({content: read(file).replace(/@import[^;]+;/g, '')});
            }
            await page.addStyleTag({content: '.panel-right {display:block!important;position:fixed;inset:40px 0 0 30px;width:calc(100% - 30px);height:calc(100% - 40px);transform:translateX(0)} .drawer {display:block;position:absolute;inset:0;transform:translateX(0)}'});
            await page.evaluate(() => {
                window.fetch = undefined;
                window.nativeCalls = 0;
                Element.prototype.requestFullscreen = () => { window.nativeCalls++; return Promise.reject(new Error('Native fullscreen must not be requested')); };
            });
            await page.addScriptTag({content: read('common/panel-view/actions.js')});
            const settleAnimations = () => page.evaluate(() => Promise.all(document.getAnimations().map(animation => animation.finished.catch(() => {}))));
            await settleAnimations();
            const frame = page.frameLocator('iframe');
            await frame.locator('input').fill('Working draft');
            const button = page.locator('[data-common-panel-fullscreen]');
            const original = await page.locator('iframe').boundingBox();
            await button.click();
            assert.equal(await button.getAttribute('aria-pressed'), 'true');
            assert.equal(await button.textContent(), 'Quitter le plein écran');
            assert.equal(await page.locator('#app-header').isVisible(), false);
            const view = await page.locator('.generic-panel-fullscreen-content').boundingBox();
            assert.deepEqual(view, {x: 0, y: 0, width, height: 900}, 'Preview fills the browser viewport');
            const bounds = await page.locator('iframe').boundingBox();
            const toolbar = await page.locator('[data-common-panel-media-toolbar]').boundingBox();
            assert(bounds.y >= toolbar.y + toolbar.height - 1 && bounds.height > 700, 'Frame fills the area below the visible toolbar');
            assert(bounds.x >= 0 && bounds.x + bounds.width <= width + 1 && bounds.y + bounds.height <= 901, 'Frame fits without viewport overflow');
            const download = page.locator('a[download]');
            const linkBounds = await download.boundingBox();
            const buttonBounds = await button.boundingBox();
            assert(linkBounds.x >= 0 && buttonBounds.x + buttonBounds.width <= width + 1, 'Download and exit controls remain inside narrow screens');
            const downloadEvent = page.waitForEvent('download');
            await download.click();
            assert.equal((await downloadEvent).suggestedFilename(), 'Document.docx');
            await button.click();
            assert.equal(await button.getAttribute('aria-pressed'), 'false');
            assert.equal(await frame.locator('input').inputValue(), 'Working draft', 'Toggling preserves the iframe and unsaved state');
            await settleAnimations();
            const restored = await page.locator('iframe').boundingBox();
            for (const axis of ['x', 'y', 'width', 'height']) assert(Math.abs(restored[axis] - original[axis]) < 1, 'Normal layout is restored: ' + axis);
            await button.click();
            await page.keyboard.press('Escape');
            assert.equal(await page.locator('.generic-panel-fullscreen-host').count(), 0, 'Escape exits the window preview');
            await button.click();
            await page.locator('[data-omo-document-detail-drawer]').evaluate(node => node.classList.remove('is-open'));
            await page.waitForFunction(() => !document.querySelector('.generic-panel-fullscreen-host'));
            assert.equal(await page.locator('.generic-panel-fullscreen-bridge, .generic-panel-fullscreen-hidden').count(), 0, 'Closing the detail drawer restores its ancestors');
            assert.equal(await page.evaluate(() => window.nativeCalls), 0);
            assert.deepEqual(errors, []);
            await page.close();
        }
        console.log('document_preview_fullscreen_browser_test: OK');
    } finally { await browser.close(); }
})().catch(error => {console.error(error); process.exitCode = 1;});
