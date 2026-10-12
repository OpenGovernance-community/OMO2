'use strict';

// NODE_PATH must contain playwright; uses a local Edge installation, without a server or DB.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require('playwright');

(async () => {
    const browser = await chromium.launch({channel: 'msedge', headless: true});
    try {
        const page = await browser.newPage();
        const errors = [];
        const requests = [];
        let failList = true;
        page.on('pageerror', error => errors.push(error.message));
        const faqScript = fs.readFileSync(path.join(__dirname, '../omo/assets/js/faq.js'), 'utf8');
        const detailHtml = id => `<h4 data-faq-view-title>Question ${id}</h4><button data-faq-back>Retour</button>
            <button data-faq-edit data-faq-id="${id}">Editer</button>`;
        const shell = `<div id="commonTopbarModalTitle">FAQ</div><div id="commonTopbarModalBody">
            <div id="faqPopupRoot" data-faq-oid="7" data-faq-cid="8" data-faq-scope="children"
                data-faq-initial-id="42" data-faq-list-loaded="0"
                data-faq-reload-url="/popup/faq.php?oid=7&amp;cid=8&amp;faq_scope=children">
                <div data-faq-detail-view></div><template data-faq-detail-back><button data-faq-back>Retour</button></template>
            </div></div>`;
        await page.route('https://faq.test/**', async route => {
            const url = new URL(route.request().url());
            if (url.pathname === '/') return route.fulfill({body: shell, contentType: 'text/html'});
            requests.push(url);
            if (url.pathname === '/ajax/faq_update.php') {
                return route.fulfill({json: {status: true, focusId: 42, reloadUrl: '/popup/faq.php?oid=7&cid=8'}});
            }
            if (url.pathname === '/ajax/faq_detail.php') {
                const id = url.searchParams.get('id');
                assert.equal(url.searchParams.get('oid'), '7');
                assert.equal(url.searchParams.get('cid'), '8');
                assert.equal(url.searchParams.get('faq_scope'), 'children');
                if (id === '99') return route.fulfill({status: 500, body: 'Unavailable'});
                const body = url.searchParams.get('edit') === '1'
                    ? `<div data-faq-form-shell><form id="formulaire-edit" action="/ajax/faq_update.php">
                        <button type="button" data-faq-save>Enregistrer</button>
                        <button type="button" data-faq-cancel-edit data-faq-id="${id}">Annuler</button></form></div>`
                    : detailHtml(id);
                return route.fulfill({body, contentType: 'text/html'});
            }
            assert.equal(url.pathname, '/popup/faq.php');
            assert.equal(url.searchParams.has('id'), false, 'Returning to the list drops the question ID.');
            assert.equal(url.searchParams.get('faq_scope'), 'children');
            await new Promise(resolve => setTimeout(resolve, 50));
            if (failList) { failList = false; return route.fulfill({status: 503, body: 'Unavailable'}); }
            return route.fulfill({contentType: 'text/html', body: `<div id="faqPopupRoot" data-faq-oid="7" data-faq-cid="8"
                data-faq-scope="children"><div data-faq-search-view><input data-faq-search-input><div data-faq-list>FAQ list</div>
                <button data-faq-detail data-faq-id="43">Question 43</button></div><div data-faq-detail-view hidden></div></div>
                <script src="/omo/assets/js/faq.js"></script>`});
        });
        await page.goto('https://faq.test/#|faq-42');
        await page.evaluate(script => {
            window.notifications = [];
            window.omoNotify = (...args) => window.notifications.push(args);
            window.omoOpenPopupHashState = (key, id) => {
                history.replaceState(null, '', '#|' + key + (id ? '-' + id : ''));
                window.dispatchEvent(new Event('hashchange'));
            };
            window.commonExecuteFragmentScripts = () => { new Function(script)(); return Promise.resolve(); };
        }, faqScript);
        await page.addScriptTag({path: path.join(__dirname, '../common/search_text.js')});
        await page.addScriptTag({content: faqScript});
        await page.waitForFunction(() => document.querySelector('#commonTopbarModalTitle').textContent === 'Question 42');
        const listCalls = () => requests.filter(url => url.pathname === '/popup/faq.php').length;
        assert.equal(listCalls(), 0, 'Direct detail opening must not fetch the collection.');
        assert.equal(requests.length, 1, 'Initial route synchronization must fetch the detail only once.');

        await page.locator('[data-faq-edit]').click();
        await page.locator('[data-faq-cancel-edit]').click();
        await page.locator('[data-faq-edit]').click();
        await page.locator('[data-faq-save]').click();
        await page.locator('[data-faq-edit]').waitFor();
        assert.equal(listCalls(), 0, 'Editing, cancelling and saving a question must not fetch the collection.');

        await page.evaluate(() => window.omoOpenPopupHashState('faq', 99));
        await page.locator('[data-faq-detail-view] .faq-popup__no-result').waitFor();
        await page.locator('[data-faq-back]').evaluate(button => { button.click(); button.click(); });
        await page.waitForFunction(() => window.notifications.length === 1);
        assert.equal(listCalls(), 1, 'Repeated clicks must share a pending list request.');
        await page.locator('[data-faq-back]').click();
        await page.locator('[data-faq-list]').waitFor();
        assert.equal(listCalls(), 2, 'A failed list request can be retried.');
        assert.equal(await page.locator('#commonTopbarModalTitle').textContent(), 'FAQ');

        await page.locator('[data-faq-detail]').click();
        await page.locator('[data-faq-back]').click();
        assert(await page.locator('[data-faq-detail-view]').isHidden());
        assert.equal(listCalls(), 2, 'The loaded list is reused on subsequent returns.');

        await page.evaluate(() => history.replaceState(null, '', '/'));
        await page.setContent(shell);
        await page.addScriptTag({content: faqScript});
        await page.waitForFunction(() => document.querySelector('#commonTopbarModalTitle').textContent === 'Question 42');
        assert.equal(listCalls(), 2, 'A direct ID without a hash also opens only the question.');
        assert.deepEqual(errors, []);
        console.log('faq_direct_detail_test: OK');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
