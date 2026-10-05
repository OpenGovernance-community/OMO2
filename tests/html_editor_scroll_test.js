'use strict';

// Reproduce lazy editor swaps inside a scrolled page and a nested PV-like panel.
const assert = require('node:assert/strict');
const path = require('node:path');
const {chromium} = require('playwright');
const root = path.join(__dirname, '..');

(async () => {
    const browser = await chromium.launch({channel: 'msedge', headless: true});
    try {
        const page = await browser.newPage({viewport: {width: 1100, height: 900}});
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.setContent('<style>body{margin:0}.spacer{height:1200px}#panel{height:600px;overflow:auto}</style>'
            + '<div class="spacer"></div><div id="panel"><div class="spacer"></div><div id="host"></div><div class="spacer"></div></div>'
            + '<div class="spacer"></div><div id="picker" style="position:fixed;inset:0;display:none"><button>Insert</button></div>');
        await page.addStyleTag({path: path.join(root, 'common/assets/components.css')});
        await page.addScriptTag({url: 'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js'});
        await page.addScriptTag({path: path.join(root, 'omo/assets/js/simple-html-field.js')});
        await page.evaluate(() => {
            window.api = window.omoSimpleHtmlField.mount(document.querySelector('#host'), {
                value: Array.from({length: 60}, (_, i) => '<p>Line ' + i + '</p>').join('')
            });
            window.scrollTo(0, 1000);
            document.querySelector('#panel').scrollTop = 1080;
            window.api.getEditableElement().scrollTop = 1400;
        });
        const snapshot = () => page.evaluate(() => ({
            page: window.scrollY,
            panel: document.querySelector('#panel').scrollTop,
            content: window.api.getEditableElement().scrollTop,
            host: document.querySelector('#host').getBoundingClientRect().top
        }));
        const assertScroll = async (expected, message) => {
            const actual = await snapshot();
            for (const key of ['page', 'panel', 'content', 'host']) {
                assert(Math.abs(actual[key] - expected[key]) <= 1, message + ': ' + key + ' ' + actual[key] + ' instead of ' + expected[key]);
            }
        };
        const beforeClick = await snapshot();
        const box = await page.locator('[data-html-editor-surface]').boundingBox();
        const point = {x: box.x + 40, y: box.y + 120};
        await page.evaluate(point => {
            const range = document.caretRangeFromPoint(point.x, point.y);
            window.clickedPoint = {node: range.startContainer, offset: range.startOffset};
        }, point);
        // A real click, not programmatic focus, in an already scrolled contenteditable div.
        await page.mouse.click(point.x, point.y);
        await page.locator('.note-editable').waitFor();
        await page.waitForTimeout(100);
        await assertScroll(beforeClick, 'Activating Summernote keeps page, panel and content scroll');
        assert.equal(await page.evaluate(() => {
            const selection = window.getSelection();
            return selection.anchorNode === window.clickedPoint.node && selection.anchorOffset === window.clickedPoint.offset;
        }), true, 'First-load activation keeps the clicked caret in scrolled content');
        await page.evaluate(() => {
            const editable = window.api.getEditableElement();
            const paragraph = editable.children[40];
            editable.scrollTop += paragraph.getBoundingClientRect().top - editable.getBoundingClientRect().top - 120;
            const range = document.createRange();
            range.selectNodeContents(paragraph);
            range.collapse(false);
            const selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(range);
            window.api.saveRange();
            window.marker = window.api.createTemporaryCursorMarker();
        });
        const beforePicker = await snapshot();
        await page.evaluate(() => {
            document.querySelector('#picker').style.display = 'block';
            document.querySelector('#picker button').focus({preventScroll: true});
        });
        await page.locator('.note-editor').waitFor({state: 'detached'});
        await assertScroll(beforePicker, 'Deactivating Summernote keeps all scroll positions');
        await page.evaluate(() => {
            window.api.replaceMarkerWithHtml(window.marker,
                '<span class="omo-document-embed" data-omo-embed-type="document" data-omo-document-id="42" contenteditable="false">Document</span>');
            document.querySelector('#picker').remove();
        });
        await page.locator('.note-editable').waitFor();
        await page.waitForTimeout(100);
        await assertScroll(beforePicker, 'Inserting a resource and restoring focus keeps all scroll positions');
        assert.equal(await page.evaluate(() => {
            const embed = document.querySelector('[data-omo-embed-type]').getBoundingClientRect();
            const panel = document.querySelector('#panel').getBoundingClientRect();
            return embed.top >= Math.max(panel.top, 0) && embed.bottom <= Math.min(panel.bottom, innerHeight)
                && document.activeElement === window.api.getEditableElement();
        }), true, 'Inserted resource remains visible and the editor has focus');
        await page.keyboard.type('Continue');
        assert.equal(await page.locator('[data-omo-embed-type]').evaluate(el => el.closest('p').nextElementSibling.textContent), 'ContinueLine 41');
        await assertScroll(beforePicker, 'Typing after insertion keeps the page and panel in place');
        assert.deepEqual(errors, []);
        console.log('html_editor_scroll_test: OK (real activation click, page/panel/content scroll, blur, resource insertion, visible block, caret and continued typing)');
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
