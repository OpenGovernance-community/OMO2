'use strict';

// Shared HTML surfaces and adminEdit forms, with real Summernote in local Edge.
const assert = require('node:assert/strict');
const path = require('node:path');
const {chromium} = require('playwright');
const root = path.join(__dirname, '..');

(async () => {
    const browser = await chromium.launch({channel: 'msedge', headless: true});
    try {
        const page = await browser.newPage({viewport: {width: 1100, height: 800}});
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.setContent('<label class="generic-form-field"><span>Description</span><div id="html"></div></label><button id="outside">Outside</button><form id="admin"></form>');
        await page.addStyleTag({path: path.join(root, 'common/assets/components.css')});
        await page.addScriptTag({url: 'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js'});
        await page.addScriptTag({path: path.join(root, 'omo/assets/js/simple-html-field.js')});
        await page.addScriptTag({path: path.join(root, 'common/choice/highlight-palette.js')});
        await page.addScriptTag({path: path.join(root, 'common/assets/admin-edit.js')});
        await page.evaluate(() => {
            window.api = window.omoSimpleHtmlField.mount(document.querySelector('#html'), {value: '<p>Hello World</p>'});
            for (let i = 0; i < 40; i++) {
                const label = document.createElement('label');
                label.htmlFor = 'field' + i;
                label.textContent = 'Field ' + i;
                const field = document.createElement('textarea');
                field.id = 'field' + i;
                field.name = 'description[]';
                field.className = 'summernote';
                field.value = '<p>Admin <strong>' + i + '</strong></p>';
                if (i === 39) field.disabled = true;
                document.querySelector('#admin').append(label, field);
            }
            return window.adminEditInitHtmlFields(document.querySelector('#admin'));
        });
        assert.equal(await page.locator('.note-editor').count(), 0, 'All shared HTML fields start as lightweight divs.');
        assert.equal(await page.locator('#admin [aria-readonly="true"]').count(), 1);
        const surface = page.locator('#html [data-html-editor-surface]');
        // Real pointer clicks must keep an empty Summernote active, even over its placeholder.
        for (const value of ['', '<p><br></p>', ' ']) {
            await page.evaluate(value => window.api.setValue(value), value);
            await surface.click();
            await page.locator('#html .note-editable').waitFor();
            const placeholderTarget = await page.locator('#html .note-placeholder').evaluate(el => {
                const box = el.getBoundingClientRect();
                return document.elementFromPoint(box.x + 20, box.y + 10).closest('.note-editable') !== null;
            });
            assert.equal(placeholderTarget, true, 'Pointer clicks over a placeholder must reach the editable content.');
            await page.locator('#html .note-toolbar button').first().hover();
            for (const offset of [{x: 20, y: 20}, {x: 50, y: 65}]) {
                const box = await page.locator('#html .note-editable').boundingBox();
                await page.mouse.click(box.x + offset.x, box.y + offset.y);
                await page.waitForTimeout(100);
                assert.equal(await page.locator('#html .note-editor').count(), 1,
                    'Clicking inside an empty editor must keep Summernote active. Focus: '
                    + await page.evaluate(() => document.activeElement.tagName));
            }
            await page.keyboard.type('Empty field works');
            assert((await page.evaluate(() => window.api.getValue())).includes('Empty field works'));
            const toolbarBox = await page.locator('#html .note-toolbar').boundingBox();
            await page.mouse.click(toolbarBox.x + 2, toolbarBox.y + 2);
            await page.waitForTimeout(100);
            assert.equal(await page.locator('#html .note-editor').count(), 1, 'Toolbar padding keeps the editor active.');
            await page.keyboard.type(' after toolbar');
            assert((await page.evaluate(() => window.api.getValue())).includes('Empty field works after toolbar'),
                'Toolbar whitespace preserves the caret for continued typing.');
            await page.keyboard.press('Control+A');
            await page.keyboard.press('Backspace');
            const emptyBox = await page.locator('#html .note-editable').boundingBox();
            await page.mouse.click(emptyBox.x + 20, emptyBox.y + 20);
            await page.waitForTimeout(100);
            assert.equal(await page.locator('#html .note-editor').count(), 1, 'Clearing an editor must not break subsequent clicks.');
            await page.locator('#outside').click();
            await page.locator('#html .note-editor').waitFor({state: 'detached'});
        }
        // Once Summernote is cached, the swap must still wait for the click's native caret.
        for (const offset of [3, 12]) {
            const point = await page.evaluate(offset => {
                window.api.setValue('<p>First paragraph</p><p>Text with <strong>bold content here</strong> after.</p>');
                window.clickedText = window.api.getEditableElement().querySelector('strong').firstChild;
                const range = document.createRange();
                range.setStart(window.clickedText, offset);
                range.setEnd(window.clickedText, offset + 1);
                const rect = range.getBoundingClientRect();
                return {x: rect.left + 0.5, y: rect.top + rect.height / 2};
            }, offset);
            await page.mouse.click(point.x, point.y);
            await page.locator('#html .note-editable').waitFor();
            assert.equal(await page.evaluate(offset => {
                const selection = window.getSelection();
                return selection.anchorNode === window.clickedText && selection.anchorOffset === offset && selection.isCollapsed;
            }, offset), true, 'The caret stays at the clicked character in formatted content after cached activation.');
            await page.keyboard.type('X');
            assert.equal(await page.locator('#html strong').textContent(), 'bold content here'.slice(0, offset) + 'X' + 'bold content here'.slice(offset));
            await page.locator('#outside').focus();
            await page.locator('#html .note-editor').waitFor({state: 'detached'});
        }
        await page.evaluate(() => window.api.setValue('<p>Hello World</p>'));
        const shortHeight = (await surface.boundingBox()).height;
        assert(shortHeight >= 85 && shortHeight <= 115, 'Shared minimum is about three lines, including padding.');
        await surface.focus();
        await page.locator('#html .note-editable').waitFor();
        assert(Math.abs((await surface.boundingBox()).height - shortHeight) < 3, 'Inactive and active content surfaces have the same minimum.');
        await page.evaluate(() => window.api.setValue(Array.from({length: 60}, (_, i) => '<p>Line ' + i + '</p>').join('')));
        const activeFrame = await page.locator('#html .note-editor').boundingBox();
        assert(activeFrame.height <= 561, 'Toolbar plus content is capped at 70% of the viewport.');
        assert(await surface.evaluate(el => el.scrollHeight > el.clientHeight), 'Long active content scrolls.');
        await page.locator('#outside').focus();
        await page.locator('#html .note-editor').waitFor({state: 'detached'});
        assert((await surface.boundingBox()).height <= 561);
        assert(await surface.evaluate(el => el.scrollHeight > el.clientHeight), 'Long inactive content scrolls.');
        await page.setViewportSize({width: 600, height: 500});
        assert((await surface.boundingBox()).height <= 351, 'The cap follows viewport changes.');
        await page.evaluate(() => window.api.setValue('<p>Hello World</p>'));
        assert(Math.abs((await surface.boundingBox()).height - shortHeight) < 3, 'Shortened content shrinks back to the shared minimum.');

        // Async PV/document pickers keep their exact insertion point across blur.
        await surface.focus();
        await page.locator('#html .note-editable').waitFor();
        await page.evaluate(() => {
            const text = window.api.getEditableElement().querySelector('p').firstChild;
            const range = document.createRange();
            range.setStart(text, 5);
            range.collapse(true);
            const selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(range);
            window.api.saveRange();
            window.marker = window.api.createTemporaryCursorMarker();
        });
        await page.locator('#outside').focus();
        await page.locator('#html .note-editor').waitFor({state: 'detached'});
        assert.equal(await page.evaluate(() => window.marker.isConnected), true);
        await page.evaluate(() => window.api.replaceMarkerWithHtml(window.marker, '<strong> INSERT</strong>'));
        assert.equal(await page.evaluate(() => window.api.getPlainText()), 'Hello INSERT World');
        assert((await page.locator('#html .note-editor').count()) <= 1, 'Async insertion restores at most one editor.');
        await page.locator('#outside').focus();
        await page.locator('#html .note-editor').waitFor({state: 'detached'});

        await page.evaluate(() => window.api.setValue('<p>Hello World</p>'));
        await surface.focus();
        await page.locator('#html .note-editable').waitFor();
        await page.evaluate(() => {
            const range = document.createRange();
            const text = window.api.getEditableElement().querySelector('p').firstChild;
            range.setStart(text, 0);
            range.setEnd(text, 5);
            const selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(range);
            window.api.saveRange();
        });
        await page.locator('#outside').focus();
        await page.locator('#html .note-editor').waitFor({state: 'detached'});
        await page.evaluate(() => window.api.replaceSelectionWithText('Bonjour'));
        assert.equal(await page.evaluate(() => window.api.getPlainText()), 'Bonjour World', 'Async text replacement keeps the saved selection after blur.');
        await page.locator('#outside').focus();
        await page.locator('#html .note-editor').waitFor({state: 'detached'});

        // Resource pickers close after insertion, then typing resumes immediately after the block.
        const embed = '<span class="omo-document-embed" data-omo-embed-type="document" data-omo-document-id="42" contenteditable="false">Document</span>';
        for (const scenario of [
            {value: '', offset: null, method: 'marker', expected: 'DocumentContinue'},
            {value: '<p>Hello World</p>', offset: 11, method: 'marker', expected: 'Hello WorldDocumentContinue'},
            {value: '<p>HelloWorld</p>', offset: 5, method: 'marker', expected: 'HelloDocumentContinueWorld'},
            {value: '<p>Hello World</p>', offset: 11, method: 'direct', expected: 'Hello WorldDocumentContinue'},
            {value: '<p>' + embed + '</p><p>Following</p>', method: 'replace', expected: 'DocumentContinueFollowing'}
        ]) {
            await page.evaluate(scenario => window.api.setValue(scenario.value), scenario);
            await surface.focus();
            await page.locator('#html .note-editable').waitFor();
            await page.evaluate(scenario => {
                const editable = window.api.getEditableElement();
                const range = document.createRange();
                if (scenario.offset !== undefined && scenario.offset !== null) {
                    range.setStart(editable.querySelector('p').firstChild, scenario.offset);
                } else {
                    range.selectNodeContents(editable);
                }
                range.collapse(false);
                const selection = window.getSelection();
                selection.removeAllRanges();
                selection.addRange(range);
                window.api.saveRange();
                window.marker = scenario.method === 'marker' ? window.api.createTemporaryCursorMarker() : null;
            }, scenario);
            await page.locator('#outside').focus();
            await page.locator('#html .note-editor').waitFor({state: 'detached'});
            await page.evaluate(({scenario, embed}) => {
                const picker = document.createElement('div');
                const button = document.createElement('button');
                picker.appendChild(button);
                document.body.appendChild(picker);
                button.focus();
                if (scenario.method === 'replace') {
                    window.api.replaceNodeWithHtml(window.api.getEditableElement().querySelector('[data-omo-embed-type]'), embed);
                } else if (scenario.method === 'direct') {
                    window.api.insertHtmlAtCursor(embed);
                } else {
                    window.api.replaceMarkerWithHtml(window.marker, embed);
                }
                // Simulate the picker closing/resetting focus after its insertion callback.
                document.querySelector('#outside').focus();
                picker.remove();
            }, {scenario, embed});
            await page.locator('#html .note-editable').waitFor();
            await page.waitForTimeout(100);
            assert.equal(await page.evaluate(() => document.activeElement === window.api.getEditableElement()), true,
                'Resource insertion restores editable focus after the picker closes.');
            assert.equal(await page.evaluate(() => {
                const selection = window.getSelection();
                const paragraph = selection.anchorNode.nodeType === Node.ELEMENT_NODE
                    ? selection.anchorNode : selection.anchorNode.parentElement;
                return paragraph.closest('p').previousElementSibling.querySelector('[data-omo-embed-type]') !== null;
            }), true, 'Caret is in the editable paragraph immediately after the resource.');
            await page.keyboard.type('Continue');
            assert.equal(await page.evaluate(() => window.api.getEditableElement().textContent), scenario.expected);
            assert.equal(await page.locator('#html [data-omo-embed-type]').count(), 1);
            if (scenario.method === 'replace') {
                assert.equal(await page.locator('#html .note-editable > p').count(), 2, 'Replacement reuses the following editable paragraph.');
            }
            await page.locator('#outside').focus();
            await page.locator('#html .note-editor').waitFor({state: 'detached'});
        }

        await page.locator('label[for="field0-html"]').click();
        await page.locator('#field0 + div .note-editable').waitFor();
        await page.keyboard.press('End');
        await page.keyboard.type(' edited');
        await page.waitForFunction(() => document.querySelector('#field0').value.includes('edited'));
        assert((await page.locator('#field0').inputValue()).includes('edited'), 'The named source textarea stays in sync while editing.');
        await page.locator('#outside').focus();
        await page.locator('#field0 + div .note-editor').waitFor({state: 'detached'});
        await page.evaluate(() => window.adminEditSetHtmlFieldValue(document.querySelector('#field0'), '<p>Programmatic</p>'));
        assert.equal(await page.locator('#field0-html').textContent(), 'Programmatic');
        await page.locator('label[for="field0-html"]').click();
        await page.locator('#field0 + div .note-editable').waitFor();
        assert.equal(await page.locator('#field0 + div .note-icon-table').count() > 0, true, 'adminEdit keeps its full toolbar.');
        await page.locator('#field0 + div .note-toolbar button').filter({has: page.locator('.note-icon-code')}).click();
        await page.locator('#field0 + div .note-codable').fill('<p>Source <em>edited</em></p>');
        await page.locator('#outside').focus();
        await page.locator('#field0 + div .note-editor').waitFor({state: 'detached'});
        assert.equal(await page.locator('#field0-html').textContent(), 'Source edited', 'Code view edits survive blur.');
        const saved = await page.evaluate(() => {
            window.adminEditSyncHtmlFields(document.querySelector('#admin'));
            return new FormData(document.querySelector('#admin')).getAll('description[]');
        });
        assert.equal(saved[0], '<p>Source <em>edited</em></p>');
        assert.equal(saved[30], '<p>Admin <strong>30</strong></p>');
        await page.evaluate(() => window.adminEditDestroyHtmlFields(document.querySelector('#admin')));
        assert.equal(await page.locator('#admin .admin-edit__html-field').count(), 0);
        assert.equal(await page.locator('#field0').inputValue(), saved[0]);
        assert.deepEqual(errors, []);
        console.log('html_editor_dom_test: OK (empty-field clicks, cached activation click/caret, toolbar whitespace/caret, placeholder/tooltip hit targets, shared lazy default, 40 admin fields, minimum parity, content sizing, 70vh, viewport resize, async insertion, resource focus/continuation after picker close, code view, form save, cleanup)');
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
