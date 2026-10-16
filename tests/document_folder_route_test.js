'use strict';
const assert = require('node:assert/strict');
const path = require('node:path');
const {chromium} = require('playwright');
const root = path.resolve(__dirname, '..');

(async () => {
    const browser = await chromium.launch({headless: true, channel: 'msedge'});
    try {
        const page = await browser.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.route('https://documents.test/**', route => route.fulfill({body: '<!doctype html><body></body>'}));
        await page.goto('https://documents.test/');
        await page.addStyleTag({path: path.join(root, 'common/assets/components.css')});
        await page.evaluate(() => {
            const entry = (id, title, parentDocumentId = 0, isFolder = false) => ({
                id, title, parentDocumentId, isFolder, childrenLoaded: false, documentType: isFolder ? 'folder' : 'ethercalc',
                sortTitle: title.toLowerCase(), groupKey: 'today', updatedGroupKey: 'today', createdGroupKey: 'today',
                contextUrl: '/detail/' + id, timestamp: 1, updatedTimestamp: 1, createdTimestamp: 1
            });
            const documents = Array.from({length: 40}, (_, i) => entry(i + 1, 'File ' + i));
            documents.push(entry(100, 'ZZ Folder', 0, true));
            const target = {...entry(102, 'Target folder', 101, true), folderPath: [100, 101, 102]};
            document.body.innerHTML = `<div id="drawer_documents" class="drawer open"><section id="omo-documents-root" class="omo-documents" data-omo-document-oid="7">
                <div data-omo-documents-results></div>
                <script type="application/json" data-omo-documents-data>${JSON.stringify({documents, requestedDocument: target, groups: [{key: 'today', label: 'Today'}]})}</script>
                <div data-omo-document-detail-drawer hidden><h2 data-omo-document-detail-title></h2>
                    <p data-omo-document-detail-description></p><div data-omo-document-detail-body></div>
                </div></section></div>`;
            window.folderRequests = [];
            window.commonExecuteFragmentScripts = () => Promise.resolve();
            window.fetch = async url => {
                const parsed = new URL(url, location.href);
                const id = Number(parsed.searchParams.get('id'));
                if (parsed.pathname.endsWith('/children.php')) window.folderRequests.push(id);
                const entries = id === 100 ? [entry(101, 'Nested folder', 100, true)]
                    : id === 101 ? [entry(102, 'Target folder', 101, true)]
                    : id === 102 ? [entry(103, 'Nested EtherCalc', 102)] : [];
                return {ok: true, json: async () => ({status: true, entries})};
            };
        });
        for (const file of ['common/drawer/subdrawer.js', 'omo/api/documents/progressive-list.js', 'omo/api/documents/list.js', 'omo/api/documents/drawers.js']) {
            await page.addScriptTag({path: path.join(root, file)});
        }
        await page.evaluate(() => {
            window.commonPageScripts['/omo/api/documents/list.js']({emptyStateMessage: 'Empty', moreLabel: 'More'});
            window.commonPageScripts['/omo/api/documents/drawers.js']({documentsErrorLoadDocument: 'Error'});
            window.omoHandleDocumentsRouteChange({documentId: 102, mode: 'detail'});
        });
        assert.deepEqual(errors, [], 'Folder navigation initializes without errors.');
        await page.locator('[data-omo-document-folder="102"] [data-omo-document-id="103"]').first().waitFor();
        for (const id of [100, 101, 102]) {
            assert.equal(await page.locator('[data-omo-document-folder="' + id + '"]').evaluate(node => node.classList.contains('is-collapsed')), false);
        }
        await page.waitForFunction(() => document.activeElement?.closest('[data-omo-document-folder]')?.getAttribute('data-omo-document-folder') === '102');
        assert.deepEqual(await page.evaluate(() => window.folderRequests), [100, 101, 102], 'Ancestors and target load exactly once.');
        assert.equal(await page.locator('[data-omo-document-detail-drawer]').isVisible(), false, 'Folder route opens the explorer, not an empty detail drawer.');
        await page.evaluate(() => window.omoHandleDocumentsRouteChange({documentId: 100, mode: 'detail'}));
        await page.waitForFunction(() => document.activeElement?.closest('[data-omo-document-folder]')?.getAttribute('data-omo-document-folder') === '100');
        assert.deepEqual(errors, []);
        console.log('document_folder_route_test: OK (nested folder links, progressive list, lazy children, focus, existing folder route)');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
