'use strict';

// Real Summernote notifications must represent content changes, not focus/blur swaps.
const assert = require('node:assert/strict');
const path = require('node:path');
const {chromium} = require('playwright');
const root = path.join(__dirname, '..');

(async () => {
    const browser = await chromium.launch({channel: 'msedge', headless: true});
    try {
        const page = await browser.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.setContent('<div id="host"></div><button id="outside">Outside</button><button id="save" disabled>Save</button>'
            + '<form id="admin"><textarea id="description" class="summernote"><p>Admin text</p></textarea></form>');
        await page.addStyleTag({path: path.join(root, 'common/assets/components.css')});
        await page.addScriptTag({url: 'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js'});
        await page.addScriptTag({path: path.join(root, 'omo/assets/js/simple-html-field.js')});
        await page.addScriptTag({path: path.join(root, 'common/assets/admin-edit.js')});
        const embed = '<span class="omo-document-embed" data-omo-embed-type="document" data-omo-document-id="42" contenteditable="false">Document</span>';
        for (const value of ['', '<p>Hello <strong>world</strong></p>', embed]) {
            await page.evaluate(value => {
                if (window.api) window.api.destroy();
                window.changes = [];
                document.querySelector('#save').disabled = true;
                window.api = window.omoSimpleHtmlField.mount(document.querySelector('#host'), {
                    value,
                    onChange: value => { window.changes.push(value); document.querySelector('#save').disabled = false; }
                });
            }, value);
            for (let i = 0; i < 2; i++) {
                await page.locator('#host [data-html-editor-surface]').click({position: {x: 20, y: 20}});
                await page.locator('#host .note-editable').waitFor();
                assert.deepEqual(await page.evaluate(() => window.changes), [], 'Activation must not report a change.');
                await page.locator('#outside').click();
                await page.locator('#host .note-editor').waitFor({state: 'detached'});
                assert.deepEqual(await page.evaluate(() => window.changes), [], 'Blur without editing must not report a change.');
                assert.equal(await page.locator('#save').isDisabled(), true, 'Save stays disabled through unchanged focus/blur cycles.');
            }
        }

        await page.evaluate(() => { window.api.setValue('<p>Hello world</p>'); window.changes = []; });
        await page.locator('#host [data-html-editor-surface]').focus();
        await page.locator('#host .note-editable').waitFor();
        await page.keyboard.press('End');
        await page.keyboard.type(' edited');
        await page.waitForFunction(() => window.changes.length > 0);
        assert((await page.evaluate(() => window.changes.length)) > 0, 'Typing must still report changes.');
        const afterTyping = await page.evaluate(() => window.changes.length);
        await page.locator('#outside').focus();
        await page.locator('#host .note-editor').waitFor({state: 'detached'});
        assert.equal(await page.evaluate(() => window.changes.length), afterTyping, 'Blur must not repeat the last edit notification.');
        await page.evaluate(() => { window.changes = []; window.api.setValue(window.api.getValue()); });
        assert.deepEqual(await page.evaluate(() => window.changes), [], 'Setting the same value is not a change.');

        await page.locator('#host [data-html-editor-surface]').focus();
        await page.locator('#host .note-editable').waitFor();
        await page.evaluate(() => {
            window.changes = [];
            window.api.setValue('<p>New value</p>');
        });
        assert.deepEqual(await page.evaluate(() => window.changes), ['<p>New value</p>'], 'A programmatic change is notified once.');
        await page.evaluate(() => {
            window.changes = [];
            const range = document.createRange();
            range.selectNodeContents(window.api.getEditableElement().querySelector('p'));
            const selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(range);
            window.api.saveRange();
        });
        await page.locator('#host .note-btn-bold').click();
        await page.waitForFunction(() => window.changes.length > 0);
        assert((await page.evaluate(() => window.changes.length)) > 0, 'Formatting without changing the text still counts as a change.');
        await page.locator('#outside').focus();
        await page.locator('#host .note-editor').waitFor({state: 'detached'});

        await page.evaluate(embed => {
            window.api.setValue('<p>' + embed + '</p>');
            window.changes = [];
            window.api.replaceNodeWithHtml(window.api.getEditableElement().querySelector('[data-omo-embed-type]'),
                embed.replace('Document</span>', 'Refreshed document</span>'), false);
        }, embed);
        await page.locator('#host .note-editable').waitFor();
        await page.locator('#outside').focus();
        await page.locator('#host .note-editor').waitFor({state: 'detached'});
        assert.deepEqual(await page.evaluate(() => window.changes), [], 'Silent resource refreshes stay silent after blur.');

        await page.locator('#host [data-html-editor-surface]').focus();
        await page.locator('#host .note-editable').waitFor();
        await page.evaluate(embed => {
            const paragraph = document.createElement('p');
            paragraph.textContent = 'Pending real edit';
            const editable = window.api.getEditableElement();
            editable.appendChild(paragraph);
            editable.dispatchEvent(new Event('input', {bubbles: true}));
            // Refresh before Summernote's debounced input callback has run.
            window.api.replaceNodeWithHtml(editable.querySelector('[data-omo-embed-type]'), embed, false);
        }, embed);
        assert((await page.evaluate(() => window.changes.length)) > 0, 'Silent refresh must preserve pending real edits.');
        const pendingCount = await page.evaluate(() => window.changes.length);
        await page.locator('#outside').focus();
        await page.locator('#host .note-editor').waitFor({state: 'detached'});
        assert.equal(await page.evaluate(() => window.changes.length), pendingCount);

        await page.evaluate(() => {
            window.adminEvents = [];
            const field = document.querySelector('#description');
            field.addEventListener('input', () => window.adminEvents.push('input'));
            window.jQuery(field).on('summernote.change', () => window.adminEvents.push('summernote.change'));
            return window.adminEditInitHtmlFields(document.querySelector('#admin'));
        });
        await page.locator('#description-html').click();
        await page.locator('#admin .note-editable').waitFor();
        await page.locator('#outside').click();
        await page.locator('#admin .note-editor').waitFor({state: 'detached'});
        assert.deepEqual(await page.evaluate(() => window.adminEvents), [], 'adminEdit must not mark an untouched form dirty.');
        await page.locator('#description-html').focus();
        await page.locator('#admin .note-editable').waitFor();
        await page.keyboard.press('End');
        await page.keyboard.type(' edited');
        await page.waitForFunction(() => document.querySelector('#description').value.includes('edited'));
        assert((await page.locator('#description').inputValue()).includes('edited'), 'adminEdit still synchronizes real edits.');
        const adminCount = await page.evaluate(() => window.adminEvents.length);
        assert(adminCount > 0);
        await page.locator('#outside').focus();
        await page.locator('#admin .note-editor').waitFor({state: 'detached'});
        assert.equal(await page.evaluate(() => window.adminEvents.length), adminCount, 'adminEdit blur does not repeat input/change events.');
        assert.deepEqual(errors, []);
        console.log('html_editor_change_test: OK (unchanged focus/blur, empty/formatted/embed fields, typing, programmatic changes, formatting, silent resource refresh and pending edits, adminEdit events)');
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
