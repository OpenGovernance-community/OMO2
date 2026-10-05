'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require('playwright');
const root = path.join(__dirname, '..');
const items = [
    {id: 1, title: 'Local memo', description: 'Local summary', contextHolonId: 10, contextLabel: 'Team', documentType: 'html'},
    {id: 2, title: 'Child memo', contextHolonId: 11, contextLabel: 'Child', documentType: 'html'},
    {id: 3, title: 'Deep memo', contextHolonId: 12, contextLabel: 'Deep', documentType: 'html'},
    {id: 4, title: 'Peer memo', contextHolonId: 20, contextLabel: 'Peer', documentType: 'html'},
    {id: 5, title: 'Uploaded file', contextHolonId: 10, documentType: 'uploaded_file'},
    {id: 99, title: 'Self', contextHolonId: 10, documentType: 'html'}
];
const node = (ID, name, children = []) => ({ID, name, type: '2', mycolor: '#3388aa', children});
const structure = node(7, 'Organization', [node(10, 'Team', [node(11, 'Child', [node(12, 'Deep')])]), node(20, 'Peer')]);

(async () => {
    const browser = await chromium.launch({channel: 'msedge', headless: true});
    try {
        const page = await browser.newPage({viewport: {width: 1100, height: 900}});
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.setContent('<form id="memo"><div data-omo-document-editor-html></div></form><button id="outside">Outside</button>');
        await page.addStyleTag({path: path.join(root, 'common/assets/components.css')});
        await page.addScriptTag({url: 'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js'});
        for (const file of ['omo/assets/js/simple-html-field.js', 'common/holon_scope_picker.js', 'common/document/embed-picker.js', 'omo/api/documents/create.js']) {
            await page.addScriptTag({path: path.join(root, file)});
        }
        await page.evaluate(({items, structure}) => {
            window.requests = [];
            window.fetch = async (url, options) => {
                window.requests.push(String(url));
                return {ok: true, json: async () => String(url).includes('getStructureData') ? structure : {status: true}};
            };
            window.commonTopbarOpenModal = (title, html) => {
                const body = document.createElement('div');
                body.id = 'commonTopbarModalBody';
                body.style.cssText = 'position:fixed;inset:0;padding:20px;overflow:auto;background:white;z-index:100';
                body.innerHTML = html;
                document.body.appendChild(body);
            };
            window.commonTopbarCloseModal = () => {
                document.querySelector('#commonTopbarModalBody')?.remove();
                window.dispatchEvent(new Event('common-topbar-modal-close'));
            };
            const mountScope = window.omoMountHolonScopePicker;
            window.omoMountHolonScopePicker = options => {
                window.scope = mountScope(options);
                return window.scope;
            };
            window.commonPageScripts['/omo/api/documents/create.js']({
                documentFormId: 'memo', initialHtmlValue: '<p>StartEnd</p>', embeddableDocuments: items,
                organizationId: 7, contextHolonId: 10, editingDocumentId: 99, editLockHeartbeatIntervalMs: 600000,
                aiToolsEnabled: false, uiText: {embedAddLine: 'Add line', embedScope: {local: 'Local', children: 'Children', descendants: 'Descendants'},
                    embedModalTitle: 'Insert Memo', embedSearch: 'Search', embedSearchPlaceholder: 'Search Memos', embedVisible: 'Visible Memos',
                    embedNone: 'None', embedFallbackTitle: 'Memo #{id}', actionCancel: 'Cancel', embedRemove: 'Remove', embedInsert: 'Insert', embedUpdate: 'Update'}
            });
            window.api = document.querySelector('[data-omo-document-editor-html]').__omoSimpleHtmlField;
        }, {items, structure});
        const openMemoPicker = async () => {
            await page.locator('#memo [data-html-editor-surface]').focus();
            await page.locator('#memo .note-editable').waitFor();
            await page.locator('[data-omo-toolbar-button-name="omoDocumentEmbed"]').click();
            await page.locator('[data-document-picker-scope] canvas').waitFor();
        };
        const select = page.locator('[data-document-picker-select]');
        const ids = () => select.locator('option').evaluateAll(options => options.map(option => Number(option.value)));
        await openMemoPicker();
        assert.deepEqual(await ids(), [1], 'Local Memo filter excludes uploads, self and other holons.');
        await page.locator('[data-omo-holon-scope="children"]').click();
        assert.deepEqual(await ids(), [1, 2]);
        await page.locator('[data-omo-holon-scope="descendants"]').click();
        assert.deepEqual(await ids(), [1, 2, 3]);
        await page.locator('[data-document-picker-search]').fill('deep');
        assert.deepEqual(await ids(), [3]);
        await page.locator('[data-document-picker-search]').fill('no match');
        assert.equal(await page.locator('[data-document-picker-apply]').isDisabled(), true);
        await page.locator('[data-document-picker-search]').fill('');
        await page.evaluate(() => window.scope.setSelectedHolonId(20));
        assert.deepEqual(await ids(), [4], 'Selecting another circle changes the document context.');
        await page.locator('[data-document-picker-cancel]').click();
        assert.equal(await page.locator('[data-omo-cursor-marker]').count(), 0, 'Cancellation cleans the insertion marker.');
        await openMemoPicker();
        await page.evaluate(() => {
            window.dispatchEvent(new Event('common-topbar-modal-pop'));
            document.querySelector('#commonTopbarModalBody').remove();
        });
        assert.equal(await page.locator('[data-omo-cursor-marker]').count(), 0, 'Returning from a nested selector also cleans its marker.');

        await page.locator('#memo [data-html-editor-surface]').focus();
        await page.locator('#memo .note-editable').waitFor();
        await page.evaluate(() => {
            const range = document.createRange();
            const text = Array.from(window.api.getEditableElement().querySelector('p').childNodes).find(node => node.nodeType === Node.TEXT_NODE && node.length >= 5);
            range.setStart(text, 5);
            range.collapse(true);
            const selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(range);
            window.api.saveRange();
        });
        await openMemoPicker();
        await page.locator('[data-document-picker-apply]').click();
        await page.locator('#memo .note-editable').waitFor();
        assert.equal(await page.locator('#memo [data-omo-document-id="1"]').count(), 1);
        assert.deepEqual(await page.locator('#memo .note-editable > p').allTextContents(), ['Start', 'Document li\u00e9Local memoLocal summary', 'End']);
        await page.keyboard.type('Continue');
        assert.equal(await page.locator('#memo .note-editable > p').last().textContent(), 'ContinueEnd');
        await page.locator('#memo [data-omo-document-id="1"]').dblclick();
        await page.locator('[data-document-picker-scope] canvas').waitFor();
        await page.locator('[data-omo-holon-scope="children"]').click();
        await select.selectOption('2');
        await page.locator('[data-document-picker-apply]').click();
        assert.equal(await page.locator('#memo [data-omo-document-id="2"]').count(), 1, 'Updating replaces the existing reference.');
        assert.equal(await page.locator('#memo [data-omo-embed-type]').count(), 1);
        await page.locator('#memo [data-omo-document-id="2"]').dblclick();
        await page.locator('[data-document-picker-remove]').click();
        assert.equal(await page.locator('#memo [data-omo-embed-type]').count(), 0);

        // Run the actual PV adapter against the same shared UI; its catalogue keeps other document types.
        const pv = fs.readFileSync(path.join(root, 'omo/api/documents/pv/editor.js'), 'utf8');
        const start = pv.indexOf('    function openPvDocumentEmbedPicker(');
        const end = pv.indexOf('    function buildPvDecisionEmbedHtml(', start);
        await page.evaluate(({source, items}) => {
            window.canEmbedDocuments = true;
            window.embeddableDocuments = items.filter(item => item.id !== 99);
            window.resourcePickerOrganizationId = 7;
            window.resourcePickerInitialHolonId = 10;
            window.resourcePickerScopeUi = {local: 'Local', children: 'Children', descendants: 'Descendants'};
            window.documentEmbedUi = {modalTitle: 'PV documents', search: 'Search', quickSearchPlaceholder: 'Search', visibleDocuments: 'Documents', none: 'None', insert: 'Insert', cancel: 'Cancel'};
            window.loadPvResourceCatalog = (type, items, host, render) => { window.pvLoadedType = type; render(); };
            window.buildPvDocumentEmbedHtml = item => '<span class="omo-document-embed" data-omo-embed-type="document" data-omo-document-id="' + item.id + '" contenteditable="false">' + item.title + '</span>';
            window.insertPvEmbedIntoField = (field, target, marker, html) => !!field.replaceMarkerWithHtml(marker, html);
            (0, eval)(source);
            window.openPvDocumentEmbedPicker(window.api, null);
        }, {source: pv.slice(start, end), items});
        await page.locator('[data-document-picker-scope] canvas').waitFor();
        assert.deepEqual(await ids(), [1, 5], 'PV still offers all authorized embeddable document types.');
        assert.equal(await page.evaluate(() => window.pvLoadedType), 'documents');
        await select.selectOption('5');
        await page.locator('[data-document-picker-apply]').click();
        assert.equal(await page.locator('#memo [data-omo-document-id="5"]').count(), 1);
        assert((await page.evaluate(() => window.requests)).some(url => url.includes('getStructureData.php?oid=7')));
        assert.deepEqual(errors, []);
        console.log('document_embed_picker_test: OK (shared Memo/PV selector, real circle canvas, local/children/descendants, holon selection, search, Memo/self filter, insertion caret, cancel/update/remove, PV catalogue)');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
