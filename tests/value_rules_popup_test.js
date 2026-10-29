'use strict';
// Run the HTTP test first to generate the fixtures. Playwright and jquery must be on NODE_PATH.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require('playwright');
const read = file => fs.readFileSync(path.join(__dirname, '..', file), 'utf8');
const clean = html => html.replace(/<script\b[^>]*>[\s\S]*?<\/script>/gi, '').replace(/<link\b[^>]*>/gi, '');
const structure = clean(read('tmp/value-rules-structure-fixture.html'));
const popup = clean(read('tmp/value-rules-popup-fixture.html'));
const source = read('omo/api/getOrg.js');
const handler = source.slice(0, source.indexOf('function omoCloseHolonMenus()'));

(async () => {
    const browser = await chromium.launch({channel:'msedge', headless:true});
    try {
        for (const viewport of [{width:1280, height:900}, {width:390, height:844}]) {
            const page = await browser.newPage({viewport});
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.route('**/*', route => {
                if (!route.request().url().endsWith('/value-rules-fixture')) return route.fulfill({status:204, body:''});
                return route.fulfill({contentType:'text/html; charset=UTF-8', body:`<!doctype html><html><head><meta charset="utf-8">
                    <style>${read('common/assets/theme-style-mono.css')}\n${read('common/assets/components.css')}\n${read('common/assets/topbar.css')}\n${read('omo/assets/css/styles.css')}\n${read('omo/api/getOrg.css')}</style>
                    <style>body{margin:0;padding:16px} #panel-left{position:static;max-width:340px;width:100%}</style>
                    </head><body><div id="panel-left">${structure}</div><div id="fixture-modal" class="common-topbar-modal" hidden><div class="common-topbar-modal__backdrop"></div><div class="common-topbar-modal__panel" role="dialog" aria-modal="true" aria-labelledby="fixture-title"><div class="common-topbar-modal__header"><h3 id="fixture-title"></h3><button class="generic-action-button common-topbar-modal__close" aria-label="Fermer">&times;</button></div><div class="common-topbar-modal__body"></div></div></div>
                    <script>${fs.readFileSync(require.resolve('jquery'), 'utf8')}</script>
                    <script>${read('common/assets/components.js')}</script>
                    <script>window.modalCalls=[]; window.commonTopbarOpenModal=function(title,url,mode){window.modalCalls.push({title,url,mode});var modal=document.getElementById('fixture-modal');modal.querySelector('.common-topbar-modal__body').innerHTML=${JSON.stringify(popup)};modal.querySelector('h3').textContent=title;modal.hidden=false;window.initGenericComponents(modal);};</script>
                    <script>${handler}</script></body></html>`});
            });
            await page.goto('https://localtest.me/value-rules-fixture');
            const list = page.locator('#panel-left .section-list--values');
            assert.equal(await list.evaluate(node => getComputedStyle(node).listStyleType), 'none');
            assert.equal(await list.evaluate(node => getComputedStyle(node).paddingLeft), '0px');
            const count = list.locator('[data-value-rules-popup]');
            assert.equal(await count.isVisible(), false, 'Counter starts inside the collapsed principle');
            await list.locator('details > summary').first().click();
            assert.equal(await count.isVisible(), true);
            assert.equal(await count.evaluate(node => node.firstChild.textContent.trim()), '4 regles associees');
            assert.equal(await count.evaluate(node => getComputedStyle(node).textDecorationLine), 'none');
            await page.screenshot({path:path.join(__dirname, '../tmp/value-rules-counter-' + viewport.width + '.png'), fullPage:true});
            await count.click();
            const calls = await page.evaluate(() => window.modalCalls);
            assert.equal(calls.length, 1);
            assert.equal(calls[0].mode, 'fetch');
            assert.match(calls[0].url, /^\/omo\/api\/policy\/value_rules\.php\?id=\d+$/);
            const modal = page.locator('#fixture-modal');
            assert.equal(await modal.locator('[data-policy-rule-card]').count(), 4);
            const spacing = await modal.locator('[data-value-rules-list]').evaluate(node => {
                const container = node.getBoundingClientRect();
                const cards = node.querySelectorAll('[data-policy-rule-card]');
                const first = cards[0].getBoundingClientRect();
                const last = cards[cards.length - 1].getBoundingClientRect();
                return [first.top - container.top, first.left - container.left, container.right - first.right, container.bottom - last.bottom];
            });
            assert.ok(spacing.every(inset => inset >= 12 && inset <= 20), 'Popup cards must have balanced padding on all sides');
            const card = modal.locator('[data-policy-rule-card]').first();
            const statement = card.locator('.omo-policy__rule-statement');
            assert.equal(await statement.isVisible(), false, 'Rules start collapsed');
            await card.locator('[data-generic-accordion-toggle]').click();
            assert.equal(await statement.isVisible(), true, 'First disclosure shows the rule');
            const intention = card.locator('details.omo-policy__rule-details');
            assert.equal(await intention.evaluate(node => node.open), false, 'Intention remains independently collapsed');
            await intention.locator(':scope > summary').click();
            assert.equal(await intention.evaluate(node => node.open), true);
            assert.equal(await intention.getByText('Make decisions understandable.', {exact:true}).isVisible(), true);
            assert.equal(await modal.evaluate(node => node.scrollWidth <= node.clientWidth + 1), true, 'Popup must fit the viewport');
            assert.deepEqual(errors, []);
            await page.screenshot({path:path.join(__dirname, '../tmp/value-rules-popup-' + viewport.width + '.png'), fullPage:true});
            await page.close();
        }
        console.log('value_rules_popup_test: OK (no bullets, counter, popup, independent disclosures, desktop/mobile)');
    } finally { await browser.close(); }
})().catch(error => {console.error(error); process.exitCode = 1;});
