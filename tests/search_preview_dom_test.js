'use strict';

// Run with playwright on NODE_PATH and a local Edge installation. No server or DB.
const assert = require('node:assert/strict');
const path = require('node:path');
const {chromium} = require('playwright');

(async () => {
    const browser = await chromium.launch({channel: 'msedge', headless: true});
    try {
        const page = await browser.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.setContent(`<main class="common-topbar-modal__panel">
          <div data-omo-search-popup-root="1" data-omo-search-popup-oid="1" data-omo-search-popup-cid="2"
               data-omo-search-preview-open-label="Ouvrir" data-omo-search-preview-loading="Chargement">
            <form data-omo-search-popup-form><input data-omo-search-popup-input value="nouvelle saisie non lancee"></form>
            <div data-omo-search-result-query="validation factures"><article><h4>Validation des factures</h4>
              <button data-omo-search-preview="faq" data-omo-search-preview-id="12" data-omo-search-open-faq="12">Apercu</button>
            </article></div>
            <div data-omo-search-preview-drawer hidden><section role="dialog" tabindex="-1">
              <h3 data-omo-subdrawer-title></h3><button data-omo-search-preview-close>Fermer</button>
              <div data-omo-search-preview-body></div><button data-omo-search-preview-retry hidden>Reessayer</button>
              <div data-omo-search-preview-action></div>
            </section></div>
          </div></main>`);
        await page.evaluate(() => {
            window.fetch = async url => {
                window.lastPreviewRequest = url;
                return {ok: true, json: async () => ({
                    title: 'Validation des factures',
                    titleHtml: '<mark class="generic-search-highlight">Validation</mark> des <mark class="generic-search-highlight">factures</mark>',
                    html: '<div class="generic-drawer-content"><p>La <mark>validation</mark> est requise.</p></div>'
                })};
            };
            window.omoOpenFaqHashState = id => { window.openedFaq = id; };
        });
        await page.addScriptTag({path: path.join(__dirname, '../common/drawer/subdrawer.js')});
        await page.addScriptTag({path: path.join(__dirname, '../omo/api/search_popup.js')});
        await page.locator('[data-omo-search-preview]').click();
        await page.waitForSelector('[data-omo-search-preview-action] button');
        const request = new URL(await page.evaluate(() => window.lastPreviewRequest), 'http://localhost');
        assert.equal(request.searchParams.get('q'), 'validation factures', 'Use the query that produced these results, not unsent edits');
        assert.equal(request.searchParams.get('id'), '12');
        assert.equal(await page.locator('[data-omo-subdrawer-title] mark').count(), 2);
        await page.keyboard.press('Escape');
        assert(await page.locator('[data-omo-search-preview-drawer]').isHidden());
        assert(await page.locator('[data-omo-search-preview]').evaluate(button => button === document.activeElement));
        await page.locator('[data-omo-search-preview]').click();
        await page.waitForSelector('[data-omo-search-preview-action] button');
        await page.locator('[data-omo-search-preview-action] button').click();
        assert.equal(await page.evaluate(() => window.openedFaq), 12, 'Footer must preserve the original object action');
        assert(await page.locator('[data-omo-search-preview-drawer]').isHidden());
        await page.evaluate(() => {
            const root = document.querySelector('[data-omo-search-popup-root]');
            for (const [module, count] of [['documents', 36], ['team', 3], ['projects', 3]]) {
                const button = document.createElement('button');
                button.setAttribute('data-omo-search-popup-stat-filter', module);
                button.textContent = count + ' ' + module;
                root.appendChild(button);
                for (let i = 0; i < count; i++) {
                    const article = document.createElement('article');
                    article.setAttribute('data-omo-search-popup-result-module', module);
                    article.textContent = module + ' ' + i;
                    root.appendChild(article);
                }
            }
            const empty = document.createElement('div');
            empty.setAttribute('data-omo-search-popup-filter-empty', '');
            empty.textContent = 'Aucun resultat';
            empty.hidden = true;
            root.appendChild(empty);
        });
        for (const module of ['team', 'projects']) {
            await page.locator('[data-omo-search-popup-stat-filter="' + module + '"]').click();
            assert.equal(await page.locator('[data-omo-search-popup-result-module]:not([hidden])').count(), 3);
            assert(await page.locator('[data-omo-search-popup-filter-empty]').isHidden());
        }
        await page.locator('[data-omo-search-popup-stat-filter="projects"]').click();
        assert.equal(await page.locator('[data-omo-search-popup-result-module]:not([hidden])').count(), 42);
        assert.deepEqual(errors, []);
        console.log('search_preview_dom_test: OK');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
