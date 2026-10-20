'use strict';
// Run with Playwright on NODE_PATH. No server or database writes.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require('playwright');
const root = path.resolve(__dirname, '..');
const app = fs.readFileSync(path.join(root, 'omo/assets/js/app.js'), 'utf8');
const headerSource = app.slice(app.indexOf('let omoMobileHeaderMenuSequence'), app.indexOf('function omoIsExecutableScriptTag'));
const components = fs.readFileSync(path.join(root, 'common/assets/components.js'), 'utf8');
const expandedSource = components.slice(components.indexOf('    var expandedMenuMedia ='), components.indexOf('    function initGenericComponents'));
const index = fs.readFileSync(path.join(root, 'omo/api/documents/index.php'), 'utf8');
// Exercise the actual creation controls, including their PHP-rendered classes.
const creation = index.slice(index.indexOf('<div class="omo-documents__new-actions">'), index.indexOf('\n                <?php endif; ?>', index.indexOf('<div class="omo-documents__new-actions">')))
    .replace(/<\?php[\s\S]*?\?>/g, '').replace(/<\?=[\s\S]*?\?>/g, 'Nouveau');

(async () => {
    const browser = await chromium.launch({headless: true, channel: 'msedge'});
    try {
        for (const hasTemplates of [false, true]) {
            const page = await browser.newPage();
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.route('https://documents.test/**', route => route.fulfill({body: '<!doctype html><body></body>'}));
            await page.goto('https://documents.test/');
            await page.setContent(`<div class="drawer open"><section class="omo-panel-view omo-documents" id="omo-documents-root">
                <header class="omo-panel-view__header"><div class="omo-panel-view__header-main">
                    <h2>Documents</h2><div class="omo-documents__header-main-actions" data-omo-header-actions>
                    <div class="omo-documents__bulk-actions" data-omo-documents-bulk-actions hidden>Selection</div>${creation}
                    </div></div></header></section></div>`);
            for (const file of ['common/assets/components.css', 'omo/assets/css/styles.css', 'omo/api/documents/list.css']) {
                await page.addStyleTag({path: path.join(root, file)});
            }
            await page.addStyleTag({content: 'body {margin:0;font:16px Arial} .drawer {display:block;position:static;width:100%;height:auto}'});
            await page.evaluate(hasTemplates => {
                if (!hasTemplates) document.querySelector('[data-omo-document-template-picker]').remove();
                window.omoDocumentsFindRoot = () => document.getElementById('omo-documents-root');
                window.omoConfig = {};
            }, hasTemplates);
            await page.addScriptTag({content: expandedSource});
            await page.addScriptTag({path: path.join(root, 'common/panel-view/actions.js')});
            await page.addScriptTag({content: headerSource});
            await page.addScriptTag({path: path.join(root, 'omo/api/documents/drawers.js')});
            await page.evaluate(() => {
                window.omoInitMobileHeaderMenus(document);
                window.omoInitMobileHeaderMenus(document);
                window.commonPageScripts['/omo/api/documents/drawers.js']({});
            });
            assert.equal(await page.locator('[data-common-panel-fullscreen]').count(), 1, 'Repeated initialization keeps one fullscreen action.');
            if (hasTemplates) {
                assert.equal(await page.locator('.omo-documents__new-actions [data-omo-document-template-picker]').count(), 1, 'Template picker stays beside creation.');
                assert.equal(await page.locator('[data-omo-document-template-picker] [data-common-panel-fullscreen]').count(), 0, 'Fullscreen never enters the template picker.');
            }
            for (const width of [320, 390, 768, 900, 1024, 1280]) {
                await page.setViewportSize({width, height: 900});
                if (width <= 1024) await page.locator('.omo-panel-view__mobile-actions-toggle').click();
                const button = page.locator('[data-omo-documents-new]');
                assert.equal(await button.isVisible(), true, 'Creation remains available at ' + width);
                const geometry = await button.evaluate(node => {
                    const r = node.getBoundingClientRect(), css = getComputedStyle(node);
                    const group = node.parentElement.getBoundingClientRect();
                    return {width: r.width, position: css.position, left: r.left, right: r.right, groupWidth: group.width,
                        hasPlus: getComputedStyle(node, '::before').content};
                });
                assert(geometry.width > 100 && geometry.left >= 0 && geometry.right <= width, 'Creation is a full visible button at ' + width);
                assert.notEqual(geometry.position, 'absolute', 'Creation stays in menu flow.');
                assert(['none', 'normal'].includes(geometry.hasPlus), 'No legacy corner plus.');
                if (width <= 1024) {
                    assert(geometry.groupWidth - geometry.width < (hasTemplates ? 60 : 2), 'Creation uses the action group width.');
                    if (hasTemplates) {
                        await page.locator('[data-omo-document-template-picker-toggle]').click();
                        assert.equal(await page.locator('[data-omo-document-template-picker-panel]').isVisible(), true);
                        assert.equal(await button.isVisible(), true, 'Template toggle keeps the parent menu open.');
                    }
                    await button.press('Escape');
                    assert.equal(await page.locator('.omo-panel-view__mobile-actions-toggle').getAttribute('aria-expanded'), 'false');
                    if (hasTemplates) assert.equal(await page.locator('[data-omo-document-template-picker-panel]').isVisible(), false);
                }
            }
            assert.deepEqual(errors, []);
            await page.close();
        }
        console.log('documents_header_menu_browser_test: OK (320-1280px, creation, templates, fullscreen, repeat init, Escape)');
    } finally { await browser.close(); }
})().catch(error => {console.error(error); process.exitCode = 1;});
