'use strict';

// Exercise the real Memo controller and the shared resource gap helper in Edge.
const assert = require('node:assert/strict');
const path = require('node:path');
const {chromium} = require('playwright');
const root = path.join(__dirname, '..');
const embed = id => '<span class="omo-document-embed" data-omo-embed-type="document" data-omo-document-id="' + id + '" contenteditable="false"><strong>Document ' + id + '</strong></span>';

(async () => {
    const browser = await chromium.launch({channel: 'msedge', headless: true});
    try {
        const page = await browser.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.setContent('<form id="memo"><div data-omo-document-editor-html></div></form><button id="outside">Outside</button>');
        await page.addStyleTag({path: path.join(root, 'common/assets/components.css')});
        await page.addScriptTag({url: 'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js'});
        await page.addScriptTag({path: path.join(root, 'omo/assets/js/simple-html-field.js')});
        await page.addScriptTag({path: path.join(root, 'omo/api/documents/create.js')});
        await page.evaluate(value => {
            document.querySelector('#memo').addEventListener('submit', event => { event.preventDefault(); window.unexpectedSubmit = true; });
            window.commonPageScripts['/omo/api/documents/create.js']({
                documentFormId: 'memo', initialHtmlValue: value, embeddableDocuments: [],
                aiToolsEnabled: false, editingDocumentId: 0, uiText: {embedAddLine: 'Add a line'}
            });
            window.api = document.querySelector('[data-omo-document-editor-html]').__omoSimpleHtmlField;
        }, '<p>' + embed(1) + '</p>');
        const helper = page.locator('.omo-html-resource-gap-helper');
        const surface = page.locator('#memo [data-html-editor-surface]');
        for (const scenario of [
            {html: '<p>' + embed(1) + '</p>', id: 1, side: 'before', expected: ['Typed', 'Document 1']},
            {html: '<p>' + embed(1) + '</p>', id: 1, side: 'after', expected: ['Document 1', 'Typed']},
            {html: '<p>' + embed(1) + '</p><p>' + embed(2) + '</p>', id: 1, side: 'after', expected: ['Document 1', 'Typed', 'Document 2']},
            {html: '<p>' + embed(1) + '</p><p>' + embed(2) + '</p>', id: 2, side: 'before', expected: ['Document 1', 'Typed', 'Document 2']},
            {html: embed(1) + embed(2), id: 1, side: 'before', expected: ['Typed', 'Document 1', 'Document 2']},
            {html: '<p>' + embed(1) + '</p><p>' + embed(2) + '</p>', id: 2, side: 'after', expected: ['Document 1', 'Document 2', 'Typed']}
        ]) {
            await page.evaluate(html => window.api.setValue(html), scenario.html);
            await surface.focus();
            await page.locator('#memo .note-editable').waitFor();
            assert.equal(await helper.getAttribute('aria-label'), 'Add a line', 'Memo passes the translated label to the shared helper.');
            const target = page.locator('#memo [data-omo-document-id="' + scenario.id + '"]');
            const box = await target.boundingBox();
            await page.mouse.move(box.x + box.width / 2, box.y + box.height * (scenario.side === 'before' ? 0.2 : 0.8));
            await helper.waitFor({state: 'visible'});
            const boundary = await target.evaluate(el => {
                const rect = el.closest('p').getBoundingClientRect();
                return {top: rect.top, bottom: rect.bottom};
            });
            const buttonBox = await helper.boundingBox();
            const buttonCenter = buttonBox.y + buttonBox.height / 2;
            assert(scenario.side === 'before' ? buttonCenter <= boundary.top + 2 : buttonCenter >= boundary.bottom - 2,
                'The plus appears at the requested boundary.');
            await helper.click();
            assert.equal(await page.evaluate(() => {
                const selection = window.getSelection();
                const element = selection.anchorNode.nodeType === Node.ELEMENT_NODE ? selection.anchorNode : selection.anchorNode.parentElement;
                const paragraph = element.closest('p');
                return document.activeElement === window.api.getEditableElement() && paragraph && !paragraph.querySelector('[data-omo-embed-type]');
            }), true, 'The new line is editable and receives the caret.');
            await page.keyboard.type('Typed');
            assert.deepEqual(await page.locator('#memo .note-editable > p').allTextContents(), scenario.expected);
            assert.equal(await page.locator('#memo [data-omo-embed-type]').count(), scenario.expected.length - 1, 'All document references are preserved.');
            await page.locator('#outside').focus();
            await page.locator('#memo .note-editor').waitFor({state: 'detached'});
            assert((await page.evaluate(() => window.api.getValue())).includes('Typed'), 'Text entered in the new line survives blur and saving.');
        }
        await page.evaluate(html => window.api.setValue(html), '<p>Before</p><p>' + embed(1) + '</p><p>After</p>');
        await surface.focus();
        await page.locator('#memo .note-editable').waitFor();
        await page.locator('#memo [data-omo-document-id="1"]').hover();
        await page.waitForTimeout(200);
        assert.equal(await helper.isVisible(), false, 'No helper is needed when editable lines already surround the block.');
        assert.equal(await page.evaluate(() => !!window.unexpectedSubmit), false, 'Plus buttons never submit the Memo form.');
        assert.deepEqual(errors, []);
        console.log('memo_embed_gap_test: OK (real Memo controller, translated helper, first/last blocks, both sides of adjacent blocks, legacy raw embeds, caret, typing and blur)');
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
