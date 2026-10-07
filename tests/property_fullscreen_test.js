'use strict';
// Run with Playwright on NODE_PATH and Microsoft Edge installed. No database writes.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require('playwright');
const read = file => fs.readFileSync(path.join(__dirname, '..', file), 'utf8');

async function run() {
    const browser = await chromium.launch({channel: 'msedge', headless: true});
    try {
        for (const viewport of [{width: 1920, height: 1080}, {width: 1280, height: 800}, {width: 390, height: 844}]) {
            for (const native of ['supported', 'unavailable', 'rejected']) {
                const page = await browser.newPage({viewport});
                const errors = [];
                page.on('pageerror', error => errors.push(error.message));
                await page.setContent(`<html><body class="view-left"><div class="app"><div class="main"><div class="content">
                    <div id="panel-left" class="panel panel-left"><div class="omo-left-panel-shell"><div class="omo-left-panel-shell__context"><div id="context" class="circle-panel generic-panel-fullscreen-content--contained generic-panel-fullscreen-content--scrollable" data-common-panel-view data-common-panel-fullscreen-isolate="#panel-left">
                        <div class="circle-top" data-common-panel-header><div class="circle-header"><h2>Context</h2><div class="circle-meta">
                            <div class="generic-menu generic-panel-actions"><button class="generic-menu-toggle" data-common-panel-menu-toggle>...</button><div class="generic-menu-panel generic-menu-panel--wide generic-menu-panel--anchored" hidden><button id="context-expand" class="generic-menu-item" data-common-panel-fullscreen>Plein ecran</button></div></div>
                            <button id="context-close" class="generic-action-button generic-action-button--icon-only generic-action-button--close" data-common-panel-fullscreen data-common-panel-fullscreen-icon data-common-panel-fullscreen-close></button>
                        </div></div></div>
                        <div class="circle-members">Members</div>
                        <div id="property" class="circle-section generic-section generic-accordion generic-accordion--row generic-accordion--collapsible generic-panel-fullscreen-content--contained" data-common-panel-view data-common-panel-fullscreen-isolate="#panel-left">
                            <div class="generic-accordion__header"><span class="generic-accordion__title">Long property</span><span class="generic-accordion__toggle">v</span>
                                <button class="generic-action-button generic-action-button--icon-only generic-action-button--close" data-common-panel-fullscreen data-common-panel-fullscreen-icon data-common-panel-fullscreen-close></button></div>
                            <div class="generic-accordion__content"><details><summary>Details</summary><p>Content</p></details>${'<p>Property content</p>'.repeat(90)}
                                <div class="section-update-meta"><span>Mis a jour le 06.10.2026 15:32 par admin@example.org</span><button id="expand" class="generic-action-button generic-action-button--icon-only" data-common-panel-fullscreen data-common-panel-fullscreen-icon><svg viewBox="0 0 24 24"><path d="M8 3H3v5M16 3h5v5M21 16v5h-5M8 21H3v-5"/></svg></button></div>
                            </div>
                        </div><div id="sibling">Another property</div>
                    </div></div><div class="omo-left-panel-shell__structure">Structure</div></div></div>
                    <div id="panel-right" class="panel panel-right"></div></div></div></div></body></html>`);
                for (const file of ['common/assets/theme.css', 'common/assets/components.css', 'omo/assets/css/styles.css', 'omo/api/getOrg.css']) {
                    await page.addStyleTag({content: read(file).replace(/@import[^;]+;/g, '')});
                }
                await page.evaluate(mode => {
                    window.fetch = undefined;
                    let element = null;
                    Object.defineProperty(document, 'fullscreenElement', {get: () => element});
                    document.documentElement.requestFullscreen = mode === 'unavailable' ? undefined : () => {
                        if (mode === 'rejected') return Promise.reject(new Error('Unavailable'));
                        element = document.documentElement;
                        document.dispatchEvent(new Event('fullscreenchange'));
                        return Promise.resolve();
                    };
                    document.exitFullscreen = () => {
                        element = null;
                        document.dispatchEvent(new Event('fullscreenchange'));
                        return Promise.resolve();
                    };
                }, native);
                await page.addScriptTag({content: read('common/panel-view/actions.js')});
                await page.evaluate(() => window.commonPanelViewActions.mount(document.body, {getHost: root => root.closest('#panel-right') || root}));
                const close = page.locator('#property [data-common-panel-fullscreen-close]');
                assert.equal(await close.isVisible(), false, 'Close button is hidden in the normal view');
                await page.locator('#expand').scrollIntoViewIfNeeded();
                const originalScroll = await page.locator('.omo-left-panel-shell__context').evaluate(element => element.scrollTop);
                await page.locator('#expand').click();
                assert.equal(await close.isVisible(), true);
                assert.equal(await page.locator('#sibling').isVisible(), false);
                assert.equal(await page.locator('#expand svg').count(), 1, 'Icon survives label updates');
                const box = await page.locator('#property').boundingBox();
                const width = Math.min(viewport.width, 1200);
                assert.deepEqual(box, {x: (viewport.width - width) / 2, y: 0, width, height: viewport.height}, 'Property is centered at up to 1200px and fills narrow screens');
                await page.locator('summary').click();
                assert.equal(await page.locator('details').getAttribute('open'), '', 'Original details remain interactive');
                await page.locator('#property > .generic-accordion__content').evaluate(element => element.scrollTop = element.scrollHeight);
                const closeBox = await close.boundingBox();
                assert(closeBox.y >= 0 && closeBox.y + closeBox.height <= viewport.height, 'Close stays visible while content scrolls');
                await page.keyboard.press('Escape');
                assert.equal(await close.isVisible(), false);
                assert.equal(await page.locator('#sibling').evaluate(element => element.inert), false);
                assert.equal(await page.locator('#expand').evaluate(element => element === document.activeElement), true, 'Focus returns to the trigger');
                assert.equal(await page.locator('.omo-left-panel-shell__context').evaluate(element => element.scrollTop), originalScroll, 'Original panel scroll is restored');
                assert.equal(await page.locator('details').getAttribute('open'), '', 'Expanded details are preserved');
                await page.locator('#expand').click();
                await close.click();
                assert.equal(await close.isVisible(), false, 'Touch close restores normal view');
                const menuToggle = page.locator('[data-common-panel-menu-toggle]');
                await menuToggle.click();
                const toggleBox = await menuToggle.boundingBox();
                const menuBox = await page.locator('.generic-menu-panel').boundingBox();
                assert(Math.abs(menuBox.x + menuBox.width - toggleBox.x - toggleBox.width) < 1, 'Menu is anchored to its toggle');
                await page.locator('#context-expand').click();
                assert.deepEqual(await page.locator('#context').boundingBox(), {x: (viewport.width - width) / 2, y: 0, width, height: viewport.height}, 'Whole context uses the same centered reading layout');
                assert.equal(await page.locator('.omo-left-panel-shell__structure').isVisible(), false, 'Miniature navigator is excluded');
                assert.equal(await page.locator('.circle-members').isVisible(), true, 'Members are included');
                assert.equal(await page.locator('#sibling').isVisible(), true, 'All properties are included');
                assert.equal(await page.locator('#expand').getAttribute('aria-label'), 'Plein \u00e9cran', 'Nested property actions still expand their own property');
                assert.equal(await close.isVisible(), false, 'Nested property close stays hidden');
                await page.locator('#context').evaluate(element => element.scrollTop = element.scrollHeight);
                const contextCloseBox = await page.locator('#context-close').boundingBox();
                assert(contextCloseBox.y >= 0 && contextCloseBox.y + contextCloseBox.height <= viewport.height, 'Whole context keeps its sticky close button');
                await page.locator('#context-close').click();
                assert.equal(await menuToggle.evaluate(element => document.activeElement === element), true, 'Menu action restores focus to the visible menu toggle');
                await menuToggle.click();
                await page.locator('#context-expand').click();
                await page.locator('#expand').click();
                assert.equal(await page.locator('#context').evaluate(element => element.classList.contains('generic-panel-fullscreen-content')), false, 'A property can be isolated from the whole context');
                assert.equal(await close.isVisible(), true);
                await page.keyboard.press('Escape');
                await menuToggle.click();
                await page.locator('#context-expand').click();
                await menuToggle.click();
                assert.equal(await page.locator('#context-expand').textContent(), 'Quitter le plein \u00e9cran');
                await page.locator('#context-expand').click();
                assert.equal(await page.locator('#context-close').isVisible(), false, 'Menu also exits whole-context fullscreen');
                await menuToggle.click();
                await page.locator('#context-expand').click();
                await page.keyboard.press('Escape');
                assert.equal(await page.locator('#context-close').isVisible(), false, 'Escape exits whole-context fullscreen');
                await page.locator('#expand').click();
                if (native === 'supported') {
                    await page.evaluate(() => document.exitFullscreen());
                    assert.equal(await close.isVisible(), false, 'Browser fullscreen exit restores the property');
                    await page.locator('#expand').click();
                }
                await page.locator('#property').evaluate(element => element.remove());
                await page.waitForFunction(() => !document.querySelector('.generic-panel-fullscreen-host'));
                assert.equal(await page.locator('#sibling').evaluate(element => element.inert), false, 'Navigation cleans up hidden siblings');
                await menuToggle.click();
                await page.locator('#context-expand').click();
                await page.locator('#context').evaluate(element => element.remove());
                await page.waitForFunction(() => !document.querySelector('.generic-panel-fullscreen-host'));
                assert.equal(await page.locator('.omo-left-panel-shell__structure').evaluate(element => element.inert || element.classList.contains('generic-panel-fullscreen-hidden')), false, 'Context reload restores the miniature navigator');
                assert.deepEqual(errors, []);
                await page.close();
            }
        }
        console.log('property_fullscreen_test: OK (desktop/mobile, native/unavailable/rejected, Escape/close/navigation)');
    } finally {
        await browser.close();
    }
}
run().catch(error => {console.error(error); process.exitCode = 1;});
