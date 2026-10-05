'use strict';

// Run with playwright on NODE_PATH and local Edge. Uses the production CDN assets.
const assert = require('node:assert/strict');
const path = require('node:path');
const {chromium} = require('playwright');
const root = path.join(__dirname, '..');

(async () => {
    const browser = await chromium.launch({channel: 'msedge', headless: true});
    try {
        const page = await browser.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.stack));
        await page.setContent('<form id="proposals"></form><button id="outside">Outside</button>');
        await page.addStyleTag({path: path.join(root, 'common/assets/components.css')});
        await page.addScriptTag({url: 'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js'});
        let releaseAssets;
        const assetsGate = new Promise(resolve => { releaseAssets = resolve; });
        let requestedAssets = 0;
        await page.route('**/summernote/**', async route => {
            requestedAssets++;
            await assetsGate;
            await route.continue();
        });
        await page.evaluate(() => {
            for (let i = 0; i < 60; i++) {
                const card = document.createElement('div');
                card.className = 'omo-decision-proposal-card';
                card.innerHTML = '<div data-omo-proposal-html-field><div data-omo-proposal-html-editor></div>'
                    + '<textarea hidden name="proposal_descriptions[]" data-omo-proposal-html-value></textarea></div>';
                card.querySelector('textarea').value = '<p>Proposal <strong>' + i + '</strong></p>';
                if (i === 59) card.querySelector('[data-omo-proposal-html-editor]').setAttribute('data-omo-proposal-html-disabled', '1');
                document.querySelector('form').appendChild(card);
            }
        });
        await page.addScriptTag({path: path.join(root, 'omo/assets/js/simple-html-field.js')});
        await page.addScriptTag({path: path.join(root, 'common/choice/highlight-palette.js')});
        await page.addScriptTag({path: path.join(root, 'common/choice/proposal-html.js')});
        const hosts = page.locator('[data-omo-proposal-html-editor]');
        const previews = page.locator('[contenteditable="true"]');
        assert.equal(await previews.count(), 59);
        assert.equal(await page.locator('.note-editor').count(), 0);
        assert.equal(requestedAssets, 0, 'Listing 60 proposals must not request Summernote.');
        assert.equal(await hosts.nth(59).locator('[aria-readonly="true"]').count(), 1);
        const initial = await page.evaluate(() => new FormData(document.querySelector('form')).getAll('proposal_descriptions[]'));
        assert.equal(initial.length, 60);
        assert.equal(initial[45], '<p>Proposal <strong>45</strong></p>');
        await hosts.nth(1).evaluate(host => window.omoProposalHtml.setValue(host, '<p>Updated <em>without focus</em></p>'));
        assert.equal(await hosts.nth(1).locator('em').textContent(), 'without focus');
        assert.equal(await page.locator('.note-editor').count(), 0);

        await page.evaluate(() => {
            const removed = document.createElement('div');
            document.body.appendChild(removed);
            window.omoSimpleHtmlField.mount(removed, {lazy: true, value: '<p>Removed</p>', onReady: () => { window.removedReady = true; }}).focus();
            removed.remove();
        });
        // Typing and saving still work while the network has not delivered Summernote.
        await previews.first().focus();
        await page.keyboard.press('End');
        await page.keyboard.type(' pending');
        assert((await page.evaluate(() => new FormData(document.querySelector('form')).getAll('proposal_descriptions[]')[0])).includes('pending'));
        releaseAssets();
        await hosts.first().locator('.note-editable').waitFor();
        assert.equal(await page.locator('.note-editor').count(), 1);
        assert.equal(await hosts.first().locator('.note-editable').evaluate(el => document.activeElement === el), true);
        await page.keyboard.type(' continued');
        assert((await hosts.first().evaluate(host => window.omoProposalHtml.getValue(host))).includes('pending continued'));
        await hosts.first().locator('.note-editable').blur();
        await hosts.first().locator('.note-editor').waitFor({state: 'detached'});
        assert((await hosts.first().locator('[contenteditable]').textContent()).includes('pending continued'));
        assert.equal(await page.locator('.note-modal').count(), 0, 'Destroy removes Summernote dialogs from the body.');
        await hosts.first().locator('[contenteditable]').focus();
        await hosts.first().locator('.note-editable').waitFor();
        assert.equal(await page.locator('.note-editor').count(), 1, 'Refocusing recreates exactly one editor.');

        // Toolbar and body-mounted popups belong to the active editor.
        await hosts.first().locator('.note-btn-bold').click();
        assert.equal(await hosts.first().locator('.note-editor').count(), 1);
        await hosts.first().locator('[data-omo-toolbar-button-name="omoProposalHighlight"]').click();
        await page.locator('.omo-highlight-palette').waitFor();
        assert.equal(await hosts.first().locator('.note-editor').count(), 1);
        await page.locator('.omo-highlight-palette__color').first().click();
        await page.locator('.omo-highlight-palette').waitFor({state: 'detached'});
        await hosts.first().locator('[contenteditable]').focus();
        await hosts.first().locator('.note-editable').waitFor();
        await hosts.first().locator('.note-toolbar .note-btn').filter({has: page.locator('.note-icon-link')}).click();
        await page.locator('.note-link-url:visible').fill('https://example.com');
        await page.locator('.note-link-text:visible').fill('Example');
        assert.equal(await hosts.first().locator('.note-editor').count(), 1, 'Link dialog keeps the editor alive.');
        await page.locator('.note-link-btn:visible').click();
        await hosts.first().locator('.note-editable a[href="https://example.com"]').waitFor();

        await hosts.nth(1).locator('[contenteditable]').focus();
        await hosts.nth(1).locator('.note-editable').waitFor();
        await hosts.first().locator('.note-editor').waitFor({state: 'detached'});
        assert.equal(await page.locator('.note-editor').count(), 1);
        const saved = await page.evaluate(() => new FormData(document.querySelector('form')).getAll('proposal_descriptions[]'));
        assert(saved[0].includes('pending continued'));
        assert.equal(saved[1], '<p>Updated <em>without focus</em></p>');
        assert.equal(saved[45], initial[45]);
        assert.equal(saved[59], initial[59]);
        assert.equal(await page.locator('.note-modal-backdrop').count(), 0, 'Closing a lazy editor removes its dialog backdrops.');
        assert.equal(await page.evaluate(() => !!window.removedReady), false, 'Removed fields must not initialize after loading.');

        await hosts.nth(2).evaluate(host => {
            host.querySelector('[contenteditable]').focus();
            document.querySelector('#outside').focus();
        });
        await hosts.nth(1).locator('.note-editor').waitFor({state: 'detached'});
        assert.equal(await hosts.nth(2).locator('.note-editor').count(), 0, 'Leaving during loading cancels initialization.');
        assert.equal(await page.locator('#outside').evaluate(el => document.activeElement === el), true, 'Late initialization must not steal focus.');

        // Added cards take the same lazy path through the shared observer.
        await page.evaluate(() => {
            const card = document.querySelector('.omo-decision-proposal-card').cloneNode(false);
            card.innerHTML = '<div data-omo-proposal-html-field><div data-omo-proposal-html-editor></div>'
                + '<textarea hidden data-omo-proposal-html-value>&lt;p&gt;New&lt;/p&gt;</textarea></div>';
            document.querySelector('form').appendChild(card);
        });
        await hosts.nth(60).locator('[contenteditable="true"]').waitFor();
        assert.equal(await page.locator('.note-editor').count(), 0);
        // Keyboard departure and repeated open/close cycles retain values without leaks.
        for (let i = 0; i < 3; i++) {
            await hosts.first().locator('[contenteditable]').focus();
            await hosts.first().locator('.note-editable').waitFor();
            await page.keyboard.press('Tab');
            await hosts.first().locator('.note-editor').waitFor({state: 'detached'});
            assert((await hosts.first().evaluate(host => window.omoProposalHtml.getValue(host))).includes('pending continued'));
            assert.equal(await page.locator('.note-modal').count(), 0);
        }
        assert.deepEqual(errors, []);

        await page.evaluate(() => {
            const host = document.createElement('div');
            host.id = 'eager';
            document.body.appendChild(host);
            window.omoSimpleHtmlField.mount(host, {lazy: false, value: '<p>Standard editor</p>'});
        });
        await page.locator('#eager .note-editable').waitFor();
        await page.locator('#eager .note-editable').focus();
        await page.locator('#outside').focus();
        await page.waitForTimeout(100);
        assert.equal(await page.locator('#eager .note-editor').count(), 1, 'Standard editors keep their existing lifecycle.');

        // A loading failure must leave a usable editor and preserve subsequent input.
        const failed = await browser.newPage();
        await failed.setContent('<div id="host"></div>');
        await failed.addScriptTag({path: path.join(root, 'omo/assets/js/simple-html-field.js')});
        await failed.evaluate(() => {
            window.api = window.omoSimpleHtmlField.mount(document.querySelector('#host'), {lazy: true, value: '<p>Kept</p>'});
        });
        await failed.locator('[contenteditable]').focus();
        await failed.keyboard.press('End');
        await failed.keyboard.type(' after failure');
        assert((await failed.evaluate(() => window.api.getValue())).includes('Kept after failure'));
        await failed.locator('[contenteditable]').evaluate(el => {
            const clipboardData = new DataTransfer();
            clipboardData.setData('text/html', '<b>Safe paste</b><img src="x" onerror="window.unsafePaste=true"><script>window.unsafePaste=true</script>');
            el.dispatchEvent(new ClipboardEvent('paste', {clipboardData, bubbles: true, cancelable: true}));
        });
        assert.equal(await failed.evaluate(() => !!window.unsafePaste), false);
        assert(!(await failed.evaluate(() => window.api.getValue())).includes('onerror'));
        console.log('decision_proposal_lazy_editor_test: OK (60 proposals, focus/blur cycles, toolbar, highlight, link dialog, typing during load, caret, save, readonly, dynamic cards, failure fallback)');
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
