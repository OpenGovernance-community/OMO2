'use strict';
// node tests/subdrawer_cancel_action_test.js /path/to/playwright /path/to/chrome
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require(process.argv[2] || 'playwright');

(async () => {
    const browser = await chromium.launch({headless: true, ...(process.argv[3] ? {executablePath: process.argv[3]} : {})});
    try {
        const page = await browser.newPage();
        await page.setContent(`<div id="drawer"><header class="generic-drawer-header">
            <span data-omo-subdrawer-title>Project</span><span data-omo-subdrawer-description></span>
            <div class="generic-drawer-header__actions"><div data-omo-subdrawer-actions></div>
                <button class="generic-action-button" data-dismiss>Fermer</button></div></header><div id="body"></div></div>`);
        await page.addStyleTag({content: fs.readFileSync(path.join(__dirname, '../common/assets/components.css'), 'utf8')});
        await page.addScriptTag({content: fs.readFileSync(path.join(__dirname, '../common/drawer/subdrawer.js'), 'utf8')});
        await page.evaluate(() => {
            window.cancelClicks = 0;
            window.controller = window.omoCreateSubdrawerController({drawer: document.getElementById('drawer'), dismissAction: '[data-dismiss]'});
            window.loadForm = (title, submitLabel) => {
                const body = document.getElementById('body');
                body.innerHTML = '<div hidden data-omo-subdrawer-header data-omo-subdrawer-title="' + title + '">'
                    + '<button type="button" class="generic-action-button" data-omo-subdrawer-action data-omo-subdrawer-cancel>Annuler</button>'
                    + '<button type="submit" form="editor" class="generic-action-button" data-omo-subdrawer-action>' + submitLabel + '</button></div>'
                    + '<form id="editor"><input name="title"></form>';
                body.querySelector('[data-omo-subdrawer-cancel]').addEventListener('click', () => window.cancelClicks++);
                window.controller.applyContentHeader(body);
            };
        });
        async function labels() {
            return page.locator('header button:visible').allTextContents();
        }
        await page.evaluate(() => window.loadForm('New document', 'Creer le document'));
        assert.deepEqual(await labels(), ['Creer le document', 'Annuler']);
        await page.locator('header [data-omo-subdrawer-cancel]').click();
        assert.equal(await page.evaluate(() => window.cancelClicks), 1, 'The real cancel handler is preserved.');
        await page.evaluate(() => window.loadForm('New project', 'Creer le projet'));
        assert.deepEqual(await labels(), ['Creer le projet', 'Annuler']);
        assert.equal(await page.locator('header [data-omo-subdrawer-cancel]').count(), 1, 'Replacing forms must remove the previous cancel action.');
        await page.evaluate(() => window.controller.resetHeader());
        assert.deepEqual(await labels(), ['Fermer']);
        await page.evaluate(() => {
            document.getElementById('body').innerHTML = '<div hidden data-omo-subdrawer-header data-omo-subdrawer-title="Detail">'
                + '<button class="generic-action-button" data-omo-subdrawer-action>Modifier</button></div>';
            window.controller.applyContentHeader(document.getElementById('body'));
        });
        assert.deepEqual(await labels(), ['Modifier', 'Fermer'], 'Detail screens keep the normal close action.');

        // Exercise the Documents module's own controller setup, not just the shared controller.
        const documentsPage = await browser.newPage();
        documentsPage.setDefaultTimeout(5000);
        await documentsPage.route('**/*.css*', route => route.fulfill({contentType: 'text/css', body: ''}));
        await documentsPage.route('http://documents.test/', route => route.fulfill({contentType: 'text/html', body: '<html><body></body></html>'}));
        await documentsPage.goto('http://documents.test/');
        await documentsPage.setContent(`<div id="omo-documents-root" class="omo-documents" data-omo-document-oid="1">
            <button data-omo-documents-new data-omo-documents-new-url="/omo/api/documents/create.php?oid=1">New</button>
            <div data-omo-document-detail-drawer hidden><header class="generic-drawer-header">
                <span data-omo-subdrawer-title data-omo-document-detail-title>Document</span>
                <span data-omo-subdrawer-description data-omo-document-detail-description></span>
                <div class="generic-drawer-header__actions"><div data-omo-subdrawer-actions></div>
                    <button class="generic-action-button" data-omo-document-detail-close>Fermer</button></div>
                </header><div data-omo-document-detail-body></div></div></div>`);
        await documentsPage.addStyleTag({content: fs.readFileSync(path.join(__dirname, '../common/assets/components.css'), 'utf8')});
        await documentsPage.addScriptTag({content: fs.readFileSync(path.join(__dirname, '../common/drawer/subdrawer.js'), 'utf8')});
        await documentsPage.addScriptTag({content: fs.readFileSync(path.join(__dirname, '../omo/api/documents/list.js'), 'utf8')});
        await documentsPage.evaluate(() => window.commonPageScripts['/omo/api/documents/list.js']({}));
        assert.equal(await documentsPage.evaluate(() => !!document.querySelector('[data-omo-document-detail-drawer]').__omoSubdrawerController), true,
            'The list initializes the shared drawer before the document editor.');
        await documentsPage.evaluate(() => {
            window.omoDocumentsFindRoot = () => document.getElementById('omo-documents-root');
            window.commonExecuteFragmentScripts = () => Promise.resolve();
            window.fetch = async (url) => ({ok: true, text: async () => String(url).includes('create.php')
                ? '<div hidden data-omo-subdrawer-header data-omo-subdrawer-title="New document">'
                    + '<button form="document-editor" data-omo-subdrawer-action data-omo-subdrawer-cancel data-omo-document-editor-cancel>Annuler</button>'
                    + '<button type="submit" form="document-editor" data-omo-subdrawer-action>Creer le document</button></div>'
                    + '<form id="document-editor"><input name="title"></form>'
                : '<div hidden data-omo-subdrawer-header data-omo-subdrawer-title="Document detail">'
                    + '<button data-omo-subdrawer-action>Modifier</button></div><div data-document-detail>Content</div>'});
        });
        await documentsPage.addScriptTag({content: fs.readFileSync(path.join(__dirname, '../omo/api/documents/drawers.js'), 'utf8')});
        await documentsPage.evaluate(() => window.commonPageScripts['/omo/api/documents/drawers.js']({
            documentsDrawerEditorTitle: 'New document', documentsDrawerEditorDescription: 'Create',
            documentsDrawerDetailDescription: 'Detail', documentsActionLoading: 'Loading'
        }));
        await documentsPage.locator('[data-omo-documents-new]').click();
        await documentsPage.locator('header [data-omo-subdrawer-cancel]').waitFor({state: 'visible'});
        assert.deepEqual(await documentsPage.locator('header button:visible').allTextContents(), ['Creer le document', 'Annuler']);
        await documentsPage.locator('header [data-omo-subdrawer-cancel]').click();
        await documentsPage.locator('[data-omo-document-detail-drawer]').waitFor({state: 'hidden'});
        await documentsPage.evaluate(() => window.omoOpenDocumentDetailByPayload({id: 12, contextUrl: '/detail', title: 'Document'}));
        await documentsPage.locator('[data-document-detail]').waitFor({state: 'visible'});
        assert.deepEqual(await documentsPage.locator('header button:visible').allTextContents(), ['Modifier', 'Fermer']);
        await documentsPage.evaluate(() => window.omoOpenDocumentEditorDrawer('/omo/api/documents/create.php?oid=1&id=12'));
        await documentsPage.locator('header [data-omo-subdrawer-cancel]').waitFor({state: 'visible'});
        assert.deepEqual(await documentsPage.locator('header button:visible').allTextContents(), ['Creer le document', 'Annuler']);
        await documentsPage.locator('header [data-omo-subdrawer-cancel]').click();
        await documentsPage.locator('[data-document-detail]').waitFor({state: 'visible'});
        assert.deepEqual(await documentsPage.locator('header button:visible').allTextContents(), ['Modifier', 'Fermer'], 'Cancel edition returns to detail with its close action.');
        console.log('subdrawer_cancel_action_test: OK');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
