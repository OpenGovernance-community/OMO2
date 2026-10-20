'use strict';
// Run with Playwright on NODE_PATH. Synthetic documents only; no application writes.
const assert = require('node:assert/strict');
const path = require('node:path');
const {chromium} = require('playwright');
const root = path.resolve(__dirname, '..');

(async () => {
    const browser = await chromium.launch({headless: true, channel: 'msedge'});
    try {
        for (const density of ['compact', 'detail']) {
            const page = await browser.newPage({viewport: {width: 1440, height: 1000}});
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.route('https://documents.test/**', route => route.fulfill({body: '<!doctype html><body></body>'}));
            await page.goto('https://documents.test/');
            await page.evaluate(density => {
                const entry = (id, extra = {}) => ({id, title: 'Document ' + id, sortTitle: 'Document ' + id,
                    parentDocumentId: 0, documentType: 'html', canArchive: true, canMove: true,
                    groupKey: 'today', updatedGroupKey: 'today', createdGroupKey: 'today',
                    timestamp: 100 - id, updatedTimestamp: 100 - id, createdTimestamp: 100 - id,
                    updatedDateLabel: '20 oct.', contextUrl: '/detail/' + id, ...extra});
                const documents = [entry(1), entry(2, {canArchive: false, canMove: false}),
                    entry(3, {canArchive: false, canMove: false, canDelete: true}),
                    entry(4, {documentType: 'folder', isFolder: true, childrenLoaded: true}),
                    entry(5, {parentDocumentId: 4}), entry(6), entry(7, {isRemoteFile: true}), entry(8)];
                document.body.innerHTML = `<section id="omo-documents-root" class="omo-documents omo-panel-view" data-omo-document-oid="7">
                    <div class="omo-documents__bulk-actions" data-omo-documents-bulk-actions hidden><span data-omo-documents-bulk-count></span>
                    <button data-omo-documents-bulk-action="archive">Archive</button><button data-omo-documents-bulk-action="delete">Delete</button>
                    <button data-omo-documents-bulk-action="move">Move</button></div>
                    <input data-omo-documents-quick-search type="search">
                    <div class="omo-documents__results generic-file-list" data-omo-documents-results></div>
                    <script type="application/json" data-omo-documents-data>${JSON.stringify({documents, groups: [{key: 'today', label: 'Today'}]})}</script></section>`;
                window.omoApplicationViewPreferencesGetDefault = () => ({density, sort: 'updated'});
                window.openedDocuments = [];
                window.omoOpenDocumentDetailFromTrigger = trigger => {window.openedDocuments.push(Number(trigger.dataset.omoDocumentId)); return true;};
            }, density);
            for (const file of ['common/assets/components.css', 'omo/assets/css/styles.css', 'omo/api/documents/list.css']) {
                await page.addStyleTag({path: path.join(root, file)});
            }
            await page.addStyleTag({content: 'body{margin:0;overflow:auto;font:16px Arial} #omo-documents-root{width:1000px;margin:24px auto}'});
            for (const file of ['common/assets/components.js', 'omo/api/documents/progressive-list.js', 'omo/api/documents/list.js']) {
                await page.addScriptTag({path: path.join(root, file)});
            }
            await page.evaluate(() => window.commonPageScripts['/omo/api/documents/list.js']({emptyStateMessage: 'Empty', moreLabel: 'More',
                documentsSelectionToggle: 'Select document', documentsSelectionCount: '{count} selected'}));
            const box = id => page.locator('[data-omo-document-select][value="' + id + '"]');
            const card = id => box(id).locator('xpath=ancestor::*[contains(concat(" ",normalize-space(@class)," ")," omo-documents__item ")][1]');
            const title = id => card(id).locator('.omo-documents__compact-title, .omo-documents__title').first();
            const selected = () => page.locator('[data-omo-document-select]:checked').evaluateAll(nodes => nodes.map(node => Number(node.value)).sort((a, b) => a - b));
            const expectSelected = async ids => {
                assert.deepEqual(await selected(), ids, density + ' selected IDs');
                assert.equal(await page.locator('[data-omo-documents-bulk-count]').textContent(), ids.length + ' selected', 'Native checkbox state and selection store agree.');
            };
            const clear = async () => {
                for (const id of await selected()) await box(id).uncheck();
            };
            assert.equal(await page.locator('[data-omo-document-select]').count(), 8, 'Every entry has a checkbox, including remote files.');
            assert.equal(await box(2).isDisabled(), true);
            assert.equal(await box(7).isDisabled(), true);
            assert.equal(await box(3).isEnabled(), true, 'Delete-only entries can be selected.');
            const positions = await Promise.all([1, 2, 3, 6, 7, 8].map(id => box(id).boundingBox()));
            assert(positions.every(rect => Math.abs(rect.x - positions[0].x) < 1), 'Disabled boxes preserve alignment.');

            await title(8).click({modifiers: ['Shift']});
            await expectSelected([8]);
            await title(8).click({modifiers: ['Control']});
            await title(1).click({modifiers: ['Control']});
            await title(6).click({modifiers: ['Control']});
            await expectSelected([1, 6]);
            await title(1).click({modifiers: ['Control']});
            await title(8).click({modifiers: ['Meta']});
            await expectSelected([6, 8]);
            assert.deepEqual(await page.evaluate(() => window.openedDocuments), [], 'Modifier clicks never open documents.');

            await clear();
            await box(1).check();
            await title(8).click({modifiers: ['Shift']});
            await expectSelected([1, 3, 4, 6, 8]);
            assert.equal(await box(5).isVisible(), false, 'Collapsed folder children are excluded.');
            await title(3).click({modifiers: ['Shift']});
            await expectSelected([1, 3, 4, 6, 8]);
            assert.equal(await page.locator('[data-omo-document-folder]').getAttribute('class').then(value => value.includes('is-collapsed')), true, 'Range selection does not open a folder.');

            await clear();
            await box(1).click({modifiers: ['Control']});
            await expectSelected([1]);
            await box(8).click({modifiers: ['Shift']});
            await expectSelected([1, 3, 4, 6, 8]);
            await clear();
            await box(4).focus();
            await box(4).press('Space');
            await expectSelected([4]);
            assert.equal(await box(5).isVisible(), false, 'Space on the folder checkbox selects without expanding it.');
            await clear();
            await title(4).click();
            await page.waitForFunction(() => document.querySelector('[data-omo-document-folder-icon]').src.endsWith('/folder-open.png'));
            await box(1).check();
            await title(8).click({modifiers: ['Shift', 'Control']});
            await expectSelected([1, 3, 4, 5, 6, 8]);

            await clear();
            await box(3).check();
            await expectSelected([3]);
            assert.equal(await page.locator('[data-omo-documents-bulk-action="archive"]').isVisible(), false, 'Delete-only selection cannot be archived.');
            assert.equal(await page.locator('[data-omo-documents-bulk-action="delete"]').isVisible(), true);
            await clear();
            await title(6).click();
            assert.deepEqual(await page.evaluate(() => window.openedDocuments), [6], 'Plain clicks still open files.');
            await title(8).click({modifiers: ['Shift']});
            await expectSelected([6, 8]);
            await clear();
            await title(2).click({modifiers: ['Control']});
            await expectSelected([]);
            await page.locator('[data-omo-documents-quick-search]').fill('Document 8');
            await title(8).click({modifiers: ['Shift']});
            await expectSelected([8]);
            assert.deepEqual(errors, []);
            await page.close();
        }
        console.log('documents_selection_browser_test: OK (disabled alignment, Ctrl/Cmd, Shift, native checkboxes, folders, rights, filtering, plain opening)');
    } finally { await browser.close(); }
})().catch(error => {console.error(error); process.exitCode = 1;});
