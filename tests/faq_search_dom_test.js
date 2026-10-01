'use strict';

// Run with playwright on NODE_PATH and a local Edge installation; no server or DB.
const assert = require('node:assert/strict');
const path = require('node:path');
const {chromium} = require('playwright');

(async () => {
    const browser = await chromium.launch({channel: 'msedge', headless: true});
    try {
        const page = await browser.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.setContent(`<div id="faqPopupRoot" data-faq-default-visible="2">
            <input data-faq-search-input><div data-faq-no-result hidden>Aucune FAQ</div>
            <div data-faq-list></div><div data-faq-load-more-shell><button data-faq-load-more></button></div>
        </div>`);
        await page.evaluate(() => {
            const entries = [
                {title: 'Digestion', answer: 'Autre sujet.'},
                {title: 'Gestions', answer: 'Reponse.'},
                {title: 'Gestion', answer: 'Reponse.'},
                {title: 'Archives', answer: 'Introduction. '.repeat(60), detail: '<p>Validation</p><p>des factures r\u00e9currentes</p><script>motinterdit</script>'},
                {title: 'Contrat <img src=x onerror=alert(1)>', answer: 'Texte &amp; suite.'},
                {title: 'Gestion', answer: 'Reponse.'}
            ];
            entries.forEach((entry, i) => {
                const item = document.createElement('div');
                item.setAttribute('data-faq-item', '');
                item.setAttribute('data-faq-id', String(i));
                item.setAttribute('data-faq-default-order', String(i));
                item.setAttribute('data-faq-search', JSON.stringify(entry));
                item.innerHTML = '<div class="faq-popup__question"></div><div class="faq-popup__meta">Editable</div><div data-faq-answer><div data-faq-answer-text></div><button>Ouvrir</button></div>';
                item.querySelector('.faq-popup__question').textContent = entry.title;
                item.querySelector('[data-faq-answer-text]').textContent = entry.answer.slice(0, 220);
                document.querySelector('[data-faq-list]').appendChild(item);
            });
        });
        await page.addScriptTag({path: path.join(__dirname, '../common/search_text.js')});
        await page.addScriptTag({path: path.join(__dirname, '../omo/assets/js/faq.js')});
        const visibleIds = () => page.locator('[data-faq-item]:not([hidden])').evaluateAll(items => items.map(item => Number(item.getAttribute('data-faq-id'))));
        const query = async value => { await page.locator('[data-faq-search-input]').fill(value); };
        assert.deepEqual(await visibleIds(), [0, 1]);
        await query('gestion');
        assert.deepEqual(await visibleIds(), [2, 5, 1, 0]);
        assert.equal(await page.locator('[data-faq-id="1"] .faq-popup__highlight').textContent(), 'Gestions');
        await query('validation des factures recurrentes');
        assert.deepEqual(await visibleIds(), [3]);
        assert((await page.locator('[data-faq-id="3"] [data-faq-answer-text]').textContent()).includes('Validation des factures'));
        assert.equal(await page.locator('[data-faq-id="3"] .faq-popup__highlight').count(), 3);
        for (const noise of ['ouvrir', 'editable', 'motinterdit']) {
            await query(noise);
            assert.deepEqual(await visibleIds(), []);
            assert(await page.locator('[data-faq-no-result]').isVisible());
        }
        await query('img');
        assert.equal(await page.locator('[data-faq-id="4"] img').count(), 0, 'Highlight must not turn escaped text into HTML');
        await query('gestion');
        assert.deepEqual(await visibleIds(), [2, 5, 1, 0], 'Ties must not depend on previous searches');
        await query('');
        assert.deepEqual(await visibleIds(), [0, 1]);
        assert.equal(await page.locator('.faq-popup__highlight').count(), 0);
        assert.equal(await page.locator('[data-faq-id="3"] [data-faq-answer-text]').textContent(), 'Introduction. '.repeat(60).slice(0, 220));
        await page.locator('[data-faq-load-more]').click();
        assert.deepEqual(await visibleIds(), [0, 1, 2, 3]);
        await query('le la de');
        assert.deepEqual(await visibleIds(), [0, 1, 2, 3]);
        assert.deepEqual(errors, []);
        console.log('faq_search_dom_test: OK');
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
