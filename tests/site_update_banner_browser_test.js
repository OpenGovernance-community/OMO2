'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require(process.argv[2] || 'playwright');
const root = path.resolve(__dirname, '..');
const read = file => fs.readFileSync(path.join(root, file), 'utf8');

(async () => {
    const browser = await chromium.launch({headless: true, channel: 'msedge'});
    try {
        for (const width of [1280, 390]) {
            const page = await browser.newPage({viewport: {width, height: 850}});
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.route('https://omo.test/**', route => route.fulfill({status: 200, contentType: 'application/json', body: JSON.stringify({
                status: true, supported: true, available: true, behindCount: 8,
                message: '8 updates available', changelogTitle: 'Modifications de la mise a jour',
                changelogHtml: '<section class="generic-section"><h2>Bug fixes</h2><ul><li>New features</li></ul></section>'
            })}));
            await page.goto('https://omo.test/');
            await page.setContent('<html lang="fr"><head></head><body><div id="commonTopbarModal" class="common-topbar-modal" hidden>'
                + '<div class="common-topbar-modal__backdrop" data-topbar-modal-close></div>'
                + '<div class="common-topbar-modal__panel"><div class="common-topbar-modal__header"><h3 id="commonTopbarModalTitle"></h3>'
                + '<button data-topbar-modal-close>Close</button></div><div id="commonTopbarModalBody" class="common-topbar-modal__body"></div></div></div></body></html>');
            await page.addStyleTag({content: read('common/assets/components.css') + read('common/assets/topbar.css')});
            await page.addScriptTag({content: read('common/assets/topbar.js')});
            await page.addScriptTag({content: read('omo/assets/js/site-update.js')});
            await page.evaluate(() => window.omoInitSiteUpdateCheck({enabled: true, statusUrl: '/status', runUrl: '/run'}));
            const button = page.locator('[data-omo-site-update-details]');
            await button.waitFor({state: 'visible'});
            assert.equal(await button.getAttribute('aria-label'), 'Modifications de la mise a jour');
            const box = await button.boundingBox();
            assert(box.width < 50 && box.height < 40, 'Compact help capsule');
            assert(box.x >= 0 && box.x + box.width <= width, 'Capsule fits the viewport');
            await button.click();
            await page.locator('#commonTopbarModal:not([hidden])').waitFor();
            assert.equal(await page.locator('#commonTopbarModalBody li').innerText(), 'New features');
            await page.screenshot({path: path.join(root, `tmp/site-update-popup-${width}.png`)});
            await page.locator('#commonTopbarModal [data-topbar-modal-close]').last().click();
            assert.equal(await page.locator('#commonTopbarModal').getAttribute('hidden'), '');
            assert.deepEqual(errors, []);
            await page.close();
        }
        console.log('site_update_banner_browser_test: OK');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
