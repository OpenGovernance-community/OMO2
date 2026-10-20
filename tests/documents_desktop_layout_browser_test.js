'use strict';
// Run with Playwright on NODE_PATH. Uses synthetic documents, without database writes.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require('playwright');
const root = path.resolve(__dirname, '..');

(async () => {
    const browser = await chromium.launch({headless: true, channel: 'msedge'});
    const folderMarkup = [];
    try {
        for (const density of ['compact', 'detail']) {
            const page = await browser.newPage({viewport: {width: 1440, height: 1000}});
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.route('https://documents.test/**', route => {
                const url = new URL(route.request().url());
                const asset = path.join(root, url.pathname);
                if (/^\/(omo|common)\/.+\.(png|svg|css)$/.test(url.pathname) && fs.existsSync(asset)) {
                    return route.fulfill({path: asset});
                }
                return route.fulfill({body: '<!doctype html><body></body>'});
            });
            await page.goto('https://documents.test/');
            await page.evaluate(density => {
                const entry = (id, title, extra = {}) => ({id, title, listTitle: title, sortTitle: title,
                    parentDocumentId: 0, documentType: 'pv', canMove: true, canArchive: true,
                    groupKey: 'month', groupLabel: 'Ce mois', updatedGroupKey: 'month', createdGroupKey: 'month',
                    updatedTimestamp: 1, createdTimestamp: 1, timestamp: 1, dateLabel: '20 oct.',
                    updatedDateLabel: '20 oct.', createdDateLabel: '20 oct.', fullDateLabel: '20 octobre 2026',
                    contextUrl: '/detail/' + id, description: 'Compte rendu et suivi des actions de la reunion.',
                    keywords: 'PV, Organisation, Suivi des actions',
                    visibilityBadge: 'Organisation', visibilityIconUrl: '/omo/assets/images/documents/visibility/organization.png',
                    editVisibilityBadge: 'Proprietaire', editVisibilityIconUrl: '/omo/assets/images/documents/visibility/me.png',
                    contextBreadcrumb: [{label: 'La Passerelle', organizationId: 7, holonId: 0},
                        {label: 'Ancrage', organizationId: 7, holonId: 10}, {label: 'La Clairiere', organizationId: 7, holonId: 20}], ...extra});
                const documents = [entry(1, 'PV Reunion OP du 12.10.2026 09:00 (Relecture)'),
                    entry(2, 'Un document avec un titre volontairement tres long pour verifier la limite de deux lignes et garder son contenu complet accessible'),
                    entry(3, 'Dossier des reunions et documents de suivi', {documentType: 'folder', isFolder: true, childrenLoaded: true}),
                    entry(4, 'Document dans le dossier', {parentDocumentId: 3}),
                    entry(5, 'Document prive sans action', {canMove: false, canArchive: false, keywords: '', description: ''})];
                document.body.innerHTML = `<main id="omo-documents-root" class="omo-documents omo-panel-view" data-omo-document-oid="7" data-omo-document-scope="descendants">
                    <h1>Documents</h1><div class="omo-documents__results generic-file-list" data-omo-documents-results></div>
                    <script type="application/json" data-omo-documents-data>${JSON.stringify({documents, groups: [{key: 'month', label: 'Ce mois'}]})}</script></main>`;
                window.omoApplicationViewPreferencesGetDefault = () => ({density, scope: 'descendants', sort: 'updated'});
            }, density);
            for (const file of ['common/assets/components.css', 'omo/assets/css/styles.css', 'omo/api/documents/list.css']) {
                await page.addStyleTag({path: path.join(root, file)});
            }
            await page.addStyleTag({content: 'body{margin:0;overflow:auto;font:16px Arial;background:#f3f5f7;--color-surface:#fff;--color-surface-alt:#f8fafc;--color-border:#d8e0e8;--color-text:#1f2937;--color-text-light:#64748b;--color-primary:#4169c5;--radius-md:6px;--radius-sm:4px} main{margin:24px auto;background:#fff;padding-bottom:16px} h1{padding:16px;margin:0;font-size:22px}'});
            await page.addScriptTag({path: path.join(root, 'common/assets/components.js')});
            await page.addScriptTag({path: path.join(root, 'omo/api/documents/progressive-list.js')});
            await page.addScriptTag({path: path.join(root, 'omo/api/documents/list.js')});
            await page.evaluate(() => window.commonPageScripts['/omo/api/documents/list.js']({emptyStateMessage: 'Vide', moreLabel: 'Suite',
                documentsSelectionToggle: 'Selectionner ce document', documentsDateColumnUpdated: 'Modifie le',
                documentsDateColumnCreated: 'Cree le', tooltipLabel: 'Voir', documentsScopeEdit: 'Editer'}));
            await page.locator('[data-omo-document-folder-toggle]').click();
            assert.equal(await page.locator('[data-omo-document-id="4"]').isVisible(), true, 'Folder contents remain available.');
            await page.waitForFunction(() => document.querySelector('[data-omo-document-folder-icon]').src.endsWith('/folder-open.png'));
            folderMarkup.push(await page.locator('.omo-documents__folder-card .omo-documents__item-frame').evaluate(node => node.outerHTML));
            for (const width of [420, 700, 1100]) {
                await page.locator('main').evaluate((node, width) => {node.style.width = width + 'px';}, width);
                const rows = await page.locator('.generic-file-list__row--dense-desktop').evaluateAll(nodes => nodes.map(node => ({
                    height: node.getBoundingClientRect().height, overflow: node.scrollWidth > node.clientWidth + 1,
                    title: node.querySelector('.generic-file-list__title')?.getAttribute('title')})));
                assert(rows.length > 0);
                for (const row of rows) {
                    assert(row.height <= 64, density + ' row height at ' + width + ': ' + row.height);
                    assert.equal(row.overflow, false, 'No horizontal overflow at ' + width);
                    assert(row.title, 'The complete title is retained.');
                }
                const summary = await page.locator('.omo-documents__compact-heading--with-excerpt').first().evaluate(node => {
                    const title = node.querySelector('strong'), excerpt = node.querySelector('.omo-documents__compact-excerpt');
                    const css = getComputedStyle(excerpt);
                    return {titleWeight: Number(getComputedStyle(title).fontWeight), excerptWeight: Number(css.fontWeight),
                        text: excerpt.textContent, overflow: css.textOverflow, wrap: css.whiteSpace,
                        height: node.getBoundingClientRect().height};
                });
                assert(summary.titleWeight > summary.excerptWeight, 'The title is bold and the excerpt is regular.');
                assert(summary.text.startsWith('\u2014 '), 'An em dash separates the excerpt.');
                assert.equal(summary.overflow, 'ellipsis');
                assert.equal(summary.wrap, 'nowrap');
                assert(summary.height < 25, 'Title and excerpt share one line.');
                if (density === 'compact') assert.equal(await page.locator('[data-omo-document-id="5"] .omo-documents__compact-excerpt').count(), 0, 'Empty descriptions add no separator.');
                if (density === 'detail') {
                    const order = await page.locator('[data-omo-document-id="1"]').evaluate(node => {
                        const y = selector => node.querySelector(selector).getBoundingClientRect().top;
                        return [y('.omo-documents__item-head'), y('.omo-documents__context'), y('.omo-documents__description'), y('.omo-documents__eyebrow')];
                    });
                    assert.deepEqual(order, [...order].sort((a, b) => a - b), 'Cards show title, context, description, then metadata.');
                }
            }
            await page.locator('main').evaluate(node => {node.style.width = '1000px';});
            if (process.env.OMO_LAYOUT_PREVIEW_DIR) await page.locator('main').screenshot({path: path.join(process.env.OMO_LAYOUT_PREVIEW_DIR, 'documents-desktop-' + density + '.png')});
            assert.deepEqual(errors, []);
            await page.close();
        }
        assert.equal(folderMarkup[0], folderMarkup[1], 'Both display modes use the same folder renderer.');
        console.log('documents_desktop_layout_browser_test: OK (two lines, narrow panels, titles, shared folder renderer, card hierarchy)');
    } finally { await browser.close(); }
})().catch(error => {console.error(error); process.exitCode = 1;});
