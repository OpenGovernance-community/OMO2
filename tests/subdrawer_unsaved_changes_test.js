'use strict';
// node tests/subdrawer_unsaved_changes_test.js /path/to/playwright /path/to/chrome
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require(process.argv[2] || 'playwright');

(async () => {
    const browser = await chromium.launch({headless: true, ...(process.argv[3] ? {executablePath: process.argv[3]} : {})});
    try {
        const page = await browser.newPage();
        await page.route('http://drawer.test/**', route => route.fulfill({contentType: 'text/html', body: '<html lang="fr"><body></body></html>'}));
        await page.goto('http://drawer.test/');
        await page.evaluate(() => {
            window.translationLoads = 0;
            window.fetch = async () => { window.translationLoads++; return {ok: true, json: async () => ({discard: 'Confirm discard: unsaved data will be lost.'})}; };
            window.confirmations = [];
            window.confirmAnswer = false;
            window.confirm = message => { window.confirmations.push(message); return window.confirmAnswer; };
            window.mountDrawer = (cancelAttribute = 'data-omo-subdrawer-cancel') => {
                document.body.innerHTML = `<div class="omo-overlay-drawer is-open" id="drawer">
                    <div class="omo-overlay-drawer__backdrop" data-dismiss="backdrop">Outside</div>
                    <header><button type="button" class="omo-overlay-drawer__close" data-dismiss="close"><span>Close</span></button>
                        <button type="button" ${cancelAttribute} data-dismiss="cancel" form="editor">Cancel</button></header>
                    <form id="editor"><input name="title" value=""><textarea name="description"></textarea>
                        <select name="kind"><option value="a">A</option><option value="b">B</option></select>
                        <input type="checkbox" name="enabled"><input type="file" name="attachment">
                        <div data-rich-host><div contenteditable="true"></div></div>
                        <button type="submit">Save</button></form></div>`;
                window.closeCalls = 0;
                window.confirmations = [];
                document.querySelectorAll('[data-dismiss]').forEach(button => button.addEventListener('click', () => {
                    window.closeCalls++;
                    document.getElementById('drawer').hidden = true;
                }));
                document.querySelector('form').addEventListener('submit', event => event.preventDefault());
                const rich = document.querySelector('[data-rich-host]');
                rich.__omoSimpleHtmlField = {getValue: () => rich.querySelector('[contenteditable]').innerHTML};
            };
            window.mountDrawer();
        });
        const script = fs.readFileSync(path.join(__dirname, '../common/drawer/subdrawer.js'), 'utf8');
        await page.addScriptTag({content: script});
        await page.addScriptTag({content: script});
        assert.equal(await page.evaluate(() => window.translationLoads), 1, 'Fragment reloads must not duplicate guards or translation loads.');

        for (const dismissal of ['close', 'backdrop', 'cancel']) {
            await page.evaluate(() => window.mountDrawer());
            await page.locator(`[data-dismiss="${dismissal}"]`).click();
            assert.equal(await page.evaluate(() => window.confirmations.length), 0, 'Untouched forms close freely.');
            assert.equal(await page.evaluate(() => window.closeCalls), 1);

            await page.evaluate(() => window.mountDrawer());
            await page.locator('[name="title"]').fill('Draft title');
            await page.locator(`[data-dismiss="${dismissal}"]`).click();
            assert.equal(await page.evaluate(() => window.closeCalls), 0, 'Declining must stop cleanup and navigation handlers.');
            assert.equal(await page.locator('[name="title"]').inputValue(), 'Draft title');
            assert.deepEqual(await page.evaluate(() => window.confirmations), ['Confirm discard: unsaved data will be lost.']);
            await page.evaluate(() => { window.confirmAnswer = true; });
            await page.locator(`[data-dismiss="${dismissal}"]`).click();
            assert.equal(await page.evaluate(() => window.closeCalls), 1, 'Accepting must run the existing close handler once.');
            await page.evaluate(() => { window.confirmAnswer = false; });
        }

        for (const attribute of ['data-omo-document-editor-cancel', 'data-omo-projects-cancel-create',
            'data-omo-stats-cancel-editor', 'data-activity-editor-cancel', 'data-checklist-back-detail', 'data-cancel']) {
            await page.evaluate(attribute => window.mountDrawer(attribute), attribute);
            await page.locator('[name="title"]').fill('Draft');
            await page.locator('[data-dismiss="cancel"]').click();
            assert.equal(await page.evaluate(() => window.closeCalls), 0, attribute + ' must preserve the form.');
        }

        for (const edit of ['select', 'checkbox', 'file', 'rich']) {
            await page.evaluate(() => window.mountDrawer());
            if (edit === 'select') {
                await page.locator('select').focus();
                await page.locator('select').selectOption('b');
            }
            if (edit === 'checkbox') await page.locator('[type="checkbox"]').check();
            if (edit === 'file') {
                await page.locator('[type="file"]').focus();
                await page.locator('[type="file"]').setInputFiles({name: 'draft.txt', mimeType: 'text/plain', buffer: Buffer.from('Draft')});
            }
            if (edit === 'rich') await page.locator('[contenteditable]').fill('Rich draft');
            await page.locator('[data-dismiss="backdrop"]').click();
            assert.equal(await page.evaluate(() => window.closeCalls), 0, edit + ' changes must be protected.');
        }

        await page.evaluate(() => window.mountDrawer());
        await page.locator('[name="title"]').fill('Draft');
        await page.locator('[name="title"]').fill('');
        await page.locator('[data-dismiss="close"]').click();
        assert.equal(await page.evaluate(() => window.confirmations.length), 0, 'Reverted values do not need confirmation.');

        await page.evaluate(() => window.mountDrawer());
        await page.locator('[name="title"]').fill('Draft');
        await page.locator('[type="submit"]').click();
        assert.equal(await page.evaluate(() => window.confirmations.length), 0, 'Submitting is not a dismissal.');
        await page.locator('[data-dismiss="close"]').click();
        assert.equal(await page.evaluate(() => window.closeCalls), 0, 'A failed or incomplete save must keep the protection.');
        await page.evaluate(() => window.commonDrawerMarkSaved(document.querySelector('form')));
        await page.locator('[data-dismiss="close"]').click();
        assert.equal(await page.evaluate(() => window.closeCalls), 1, 'Successfully saved forms close freely.');

        await page.evaluate(() => {
            window.mountDrawer();
            const form = document.querySelector('form');
            form.elements.kind.value = 'b';
            form.elements.kind.dispatchEvent(new Event('change', {bubbles: true}));
        });
        await page.locator('[name="title"]').focus();
        await page.locator('[data-dismiss="close"]').click();
        assert.equal(await page.evaluate(() => window.confirmations.length), 0, 'Initialization and focus alone do not dirty the form.');

        await page.evaluate(() => {
            window.mountDrawer();
            const child = document.createElement('div');
            child.className = 'omo-overlay-drawer is-open';
            child.id = 'child';
            child.innerHTML = '<button class="omo-overlay-drawer__close" id="child-close">Close child</button><form><input name="child_title"></form>';
            document.getElementById('drawer').appendChild(child);
            document.getElementById('child-close').onclick = () => { child.hidden = true; };
        });
        await page.locator('[name="title"]').fill('Parent draft');
        await page.locator('#child-close').click();
        assert.equal(await page.evaluate(() => window.confirmations.length), 0, 'Closing an untouched child does not ask about its parent.');
        await page.locator('[data-dismiss="close"]').click();
        assert.equal(await page.evaluate(() => window.closeCalls), 0, 'The parent draft remains protected.');
        await page.evaluate(() => {
            window.commonDrawerMarkSaved(document.getElementById('editor'));
            document.getElementById('child').hidden = false;
        });
        await page.locator('[name="child_title"]').fill('Child draft');
        await page.locator('[data-dismiss="close"]').click();
        assert.equal(await page.evaluate(() => window.closeCalls), 0, 'Closing the parent also checks its open children.');
        await page.evaluate(() => { document.getElementById('child').hidden = true; window.confirmations = []; });
        await page.locator('[data-dismiss="close"]').click();
        assert.equal(await page.evaluate(() => window.confirmations.length), 0, 'Hidden closed children must not leave stale warnings.');

        await page.evaluate(() => {
            window.mountDrawer();
            const editor = document.createElement('section');
            editor.setAttribute('data-common-drawer-editor', '');
            editor.commonDrawerHasUnsavedChanges = () => true;
            document.getElementById('drawer').appendChild(editor);
        });
        await page.locator('[data-dismiss="close"]').click();
        assert.equal(await page.evaluate(() => window.closeCalls), 0, 'Custom multi-question signatures are included.');
        console.log('subdrawer_unsaved_changes_test: OK');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
