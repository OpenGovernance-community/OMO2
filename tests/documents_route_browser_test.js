'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require(process.argv[2] || 'playwright');
const root = path.resolve(__dirname, '..');
const app = fs.readFileSync(path.join(root, 'omo/assets/js/app.js'), 'utf8');

(async () => {
    const browser = await chromium.launch({headless: true, channel: 'msedge'});
    try {
        const page = await browser.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.route('https://documents.test/**', route => route.fulfill({body: '<!doctype html><body></body>'}));
        await page.goto('https://documents.test/');
        await page.evaluate(() => {
            const panel = name => `<section id="omo-documents-root" class="omo-documents" data-test="${name}" data-omo-document-oid="828">
                <div data-omo-documents-results></div>
                <script type="application/json" data-omo-documents-data>${JSON.stringify({documents: [], requestedDocument: {
                    id: 23551, title: 'Document de la reunion', contextUrl: '/detail/23551', documentType: 'pv'
                }})}</script>
                <div data-omo-document-detail-drawer hidden><h2 data-omo-document-detail-title></h2>
                    <p data-omo-document-detail-description></p><div data-omo-document-detail-body></div>
                </div></section>`;
            // A closed PV retains its selected application tab, including duplicate root IDs.
            document.body.innerHTML = `<div id="omoExternalPanelDrawer" class="omo-overlay-drawer" hidden><div data-omo-pv-application-panel>${panel('pv')}</div></div>
                <div id="drawer_documents" class="drawer open">${panel('main')}</div>`;
            window.commonRenderLoadingState = body => {body.innerHTML = '<div class="loading">Loading</div>';};
            window.commonExecuteFragmentScripts = () => Promise.resolve();
            window.fetch = async url => ({ok: true, text: async () => '<article data-loaded-document>' + url + '</article>'});
            window.omoCloseExternalPanelDrawer = () => {};
            window.omoPeekPersistentExternalPanelDrawer = () => false;
        });
        await page.addScriptTag({content: app.slice(app.indexOf('function omoFindApplicationRoot('), app.indexOf('function omoPreparePvApplicationSubdrawers('))});
        for (const file of ['common/drawer/subdrawer.js', 'omo/api/documents/progressive-list.js', 'omo/api/documents/list.js', 'omo/api/documents/drawers.js']) {
            await page.addScriptTag({path: path.join(root, file)});
        }
        await page.evaluate(() => {
            window.commonPageScripts['/omo/api/documents/list.js']({emptyStateMessage: 'Aucun document'});
            window.commonPageScripts['/omo/api/documents/drawers.js']({documentsErrorLoadDocument: 'Erreur'});
        });
        assert.deepEqual(errors, [], 'Document scripts initialize without errors.');
        assert.equal(await page.evaluate(() => window.omoDocumentsFindRoot()?.dataset.test), 'main',
            'A retained tab inside a closed PV must not receive actions for the main Documents panel.');
        await page.evaluate(() => window.omoHandleDocumentsRouteChange({documentId: 23551, mode: 'detail'}));
        await page.waitForFunction(() => document.querySelector('#drawer_documents [data-loaded-document]'));
        assert.equal(await page.locator('#drawer_documents [data-omo-document-detail-drawer]').isVisible(), true);
        assert.equal(await page.locator('#omoExternalPanelDrawer [data-loaded-document]').count(), 0);

        // Closing/reopening the cached detail must work both during and after its animation.
        for (const delay of [0, 250, 0]) {
            await page.evaluate(() => window.omoCloseDocumentDetailDrawer({force: true}));
            if (delay) await page.waitForTimeout(delay);
            await page.evaluate(() => window.omoHandleDocumentsRouteChange({documentId: 23551, mode: 'detail'}));
            await page.waitForTimeout(250);
            assert.equal(await page.locator('#drawer_documents [data-omo-document-detail-drawer]').isVisible(), true);
            assert.equal(await page.locator('#drawer_documents [data-loaded-document]').count(), 1);
        }

        // Global links target the main panel even while a PV is open, while local
        // document actions keep using its application tab.
        await page.evaluate(() => {
            const pv = document.getElementById('omoExternalPanelDrawer');
            pv.hidden = false;
            pv.classList.add('is-open');
        });
        assert.equal(await page.evaluate(() => window.omoDocumentsFindRoot()?.dataset.test), 'pv');
        await page.evaluate(() => {
            window.omoOpenDocumentDetailByPayload({id: 42, contextUrl: '/detail/42', documentType: 'uploaded_file'});
        });
        await page.waitForFunction(() => document.querySelector('#omoExternalPanelDrawer [data-loaded-document]'));
        assert.equal(await page.locator('#omoExternalPanelDrawer [data-loaded-document]').textContent(), '/detail/42');
        await page.evaluate(() => {
            // A route must neither clean up the embedded editor nor change its document.
            document.querySelector('#omoExternalPanelDrawer [data-omo-document-detail-drawer]').dataset.omoDocumentDrawerMode = 'edit';
            window.omoHandleDocumentsRouteChange({documentId: 23551, mode: 'detail'});
            window.dispatchEvent(new CustomEvent('omo-documents-route-change', {detail: {documentId: 23551, mode: 'detail'}}));
        });
        assert.equal(await page.locator('#omoExternalPanelDrawer [data-loaded-document]').textContent(), '/detail/42');
        assert.equal(await page.locator('#omoExternalPanelDrawer [data-omo-document-detail-drawer]').getAttribute('data-omo-document-drawer-mode'), 'edit');
        await page.evaluate(() => window.omoHandleDocumentsRouteChange({documentId: 23551, mode: 'edit'}));
        await page.waitForFunction(() => document.querySelector('#drawer_documents [data-loaded-document]')?.textContent.includes('create.php'));
        assert.equal(await page.locator('#omoExternalPanelDrawer [data-loaded-document]').textContent(), '/detail/42');
        await page.evaluate(() => window.dispatchEvent(new CustomEvent('omo-documents-route-change', {detail: {documentId: 0}})));
        await page.waitForTimeout(250);
        assert.equal(await page.locator('#drawer_documents [data-omo-document-detail-drawer]').isVisible(), false);
        assert.equal(await page.locator('#omoExternalPanelDrawer [data-loaded-document]').count(), 1);

        await page.evaluate(() => document.getElementById('omoExternalPanelDrawer').classList.add('is-peek'));
        assert.equal(await page.evaluate(() => window.omoDocumentsFindRoot()?.dataset.test), 'main');
        await page.evaluate(() => {
            const pv = document.getElementById('omoExternalPanelDrawer');
            pv.classList.remove('is-peek', 'is-open'); // Closing transition before hidden is set.
        });
        assert.equal(await page.evaluate(() => window.omoDocumentsFindRoot()?.dataset.test), 'main');
        // Fragment initialization remains explicitly scoped, even inside a hidden tab.
        await page.evaluate(() => {
            const script = document.createElement('script');
            script.__omoLoadTarget = document.querySelector('#omoExternalPanelDrawer [data-omo-pv-application-panel]');
            script.textContent = 'window.testScopedRoot = window.omoDocumentsFindRoot().dataset.test;';
            document.body.appendChild(script);
        });
        assert.equal(await page.evaluate(() => window.testScopedRoot), 'pv');
        // A calendar link can target a PAD absent from the cached list and its
        // initial requestedDocument. Refresh the panel before opening that PAD.
        await page.evaluate(() => {
            window.omoReplaceFetchedPanelRoot = async ({currentRoot, url}) => {
                window.testRefreshUrl = url;
                const id = Number(new URL(url, location.origin).searchParams.get('open_document_id'));
                const next = currentRoot.cloneNode(true);
                delete next.dataset.omoDocumentsReady;
                next.querySelector('[data-omo-documents-data]').textContent = JSON.stringify({documents: [], openDocumentId: id,
                    requestedDocument: {id, contextUrl: '/detail/' + id, documentType: 'etherpad', title: 'Deuxieme PAD'}});
                currentRoot.replaceWith(next);
                window.omoInitDocumentsPanels(next.parentElement);
                return next;
            };
            window.omoHandleDocumentsRouteChange({documentId: 23552, mode: 'detail', forcedScope: 'global'});
        });
        await page.waitForFunction(() => document.querySelector('#drawer_documents [data-loaded-document]')?.textContent === '/detail/23552');
        assert.equal(await page.locator('#drawer_documents [data-omo-document-detail-drawer]').isVisible(), true);
        assert.equal(await page.evaluate(() => new URL(window.testRefreshUrl, location.origin).searchParams.get('document_scope')), 'descendants');
        await page.evaluate(() => window.omoCloseDocumentDetailDrawer({force: true}));
        await page.evaluate(() => window.omoHandleDocumentsRouteChange({documentId: 23553, mode: 'detail'}));
        await page.waitForFunction(() => document.querySelector('#drawer_documents [data-loaded-document]')?.textContent === '/detail/23553');
        assert.equal(await page.locator('#drawer_documents [data-omo-document-detail-drawer]').isVisible(), true);
        await page.evaluate(() => document.getElementById('drawer_documents').remove());
        assert.equal(await page.evaluate(() => window.omoHandleDocumentsRouteChange({documentId: 23551})), false,
            'An absent main panel must never redirect a global route into a retained PV.');
        assert.deepEqual(errors, []);
        console.log('documents_route_browser_test: OK (closed/visible PV, cached reopen, global/local routes, editing, scoped bootstrap)');
    } finally {
        await browser.close();
    }
})().catch(error => {console.error(error); process.exitCode = 1;});
