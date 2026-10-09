'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require(process.argv[2] || 'playwright');
const root = path.join(__dirname, '..');

(async () => {
    const browser = await chromium.launch({headless: true, channel: 'chrome'});
    try {
        const page = await browser.newPage({viewport: {width: 1100, height: 900}});
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.route('http://project.test/', route => route.fulfill({body: '<html><body></body></html>'}));
        await page.goto('http://project.test/');
        await page.setContent(`<div id="omo-projects-root" data-omo-projects-oid="7">
            <div class="generic-menu generic-menu--split" data-omo-project-detail-document-menu>
                <button data-omo-project-detail-add-document data-omo-project-detail-add-document-url="/omo/api/documents/create.php?oid=7&cid=10&project_id=42&editor_host=project">Create document</button>
                <button data-omo-project-detail-document-menu-toggle aria-expanded="false">&#9662;</button>
                <div data-omo-project-detail-document-menu-panel hidden>
                    <button data-omo-project-import-document-url="/omo/api/projects/document_picker.php?oid=7&cid=10&id=42">Import document</button>
                </div>
            </div>
            <div data-omo-projects-document-drawer hidden><header>
                <h3 data-omo-subdrawer-title>Documents</h3><p data-omo-subdrawer-description></p>
                <div data-omo-subdrawer-actions></div><button data-omo-projects-document-drawer-close>Close</button>
            </header><div data-omo-projects-document-drawer-body></div></div>
            </div>
            <div id="commonTopbarModal" class="common-topbar-modal" hidden>
                <div class="common-topbar-modal__panel"><div class="common-topbar-modal__header">
                    <h3 id="commonTopbarModalTitle"></h3><button data-topbar-modal-close>Close</button>
                </div><div id="commonTopbarModalBody"></div></div>
            </div>`);
        await page.route('**/*.css*', route => route.fulfill({contentType: 'text/css', body: ''}));
        await page.addStyleTag({content: fs.readFileSync(path.join(root, 'common/assets/components.css'), 'utf8')});
        await page.addStyleTag({content: fs.readFileSync(path.join(root, 'common/assets/topbar.css'), 'utf8')});
        await page.addScriptTag({path: require.resolve('d3', {paths: [path.dirname(process.argv[2] || __filename)]})});
        await page.evaluate(() => {
            window.requests = []; window.posts = []; window.saved = []; window.notifications = [];
            window.failImport = true;
            const node = (ID, name, children = []) => ({ID, name, type: '2', mycolor: '#3388aa', children});
            const form = `<div class="omo-document-editor">
                <div hidden data-omo-subdrawer-header data-omo-subdrawer-title="Create document">
                    <button type="submit" form="document-form" data-omo-subdrawer-action>Create</button>
                </div>
                <form id="document-form" action="/omo/api/documents/save.php" data-omo-document-create-form data-omo-document-editor-host="project">
                    <input name="project_id" value="42" type="hidden"><input name="title" value="Project document">
                    <select name="document_type" data-omo-document-type><option value="external_link">Link</option></select>
                    <input name="external_url" data-omo-document-external-url value="https://example.com/document">
                    <div data-omo-document-editor-status hidden></div>
                </form></div>
                <script>window.commonPageScripts['/omo/api/documents/create.js']({documentFormId:'document-form',uiText:{},embeddableDocuments:[],editingDocumentId:0,aiToolsEnabled:false});</script>`;
            const catalog = {success: true, projectId: 42, organizationId: 7, projectHolonId: 10,
                documents: [{id: 1, title: 'Local memo', contextHolonId: 10},
                    {id: 2, title: 'Child memo', contextHolonId: 11},
                    {id: 3, title: 'Peer memo', contextHolonId: 20},
                    {id: 4, title: 'Organization memo', contextHolonId: 0}],
                scopeLabels: {local: 'Local', children: 'Children', descendants: 'Descendants'}};
            window.fetch = async (url, options = {}) => {
                window.requests.push(String(url));
                if (options.method === 'POST') {
                    const post = Object.fromEntries(options.body.entries()); window.posts.push(post);
                    const result = String(url).includes('/documents/save.php')
                        ? {status: true, id: 9}
                        : {success: !window.failImport, projectId: 42, documentId: Number(post.document_id), message: window.failImport ? 'Rejected' : 'Linked'};
                    return {ok: true, text: async () => JSON.stringify(result), json: async () => result};
                }
                return {ok: true, text: async () => form, json: async () => String(url).includes('getStructureData')
                    ? node(7, 'Organization', [node(10, 'Team', [node(11, 'Child')]), node(20, 'Peer')]) : catalog};
            };
            window.omoNotify = window.commonNotify = (message, type) => window.notifications.push({message, type});
            window.addEventListener('omo-project-document-saved', event => window.saved.push(event.detail));
        });
        for (const file of ['common/assets/components.js', 'common/assets/topbar.js', 'common/drawer/subdrawer.js',
            'common/holon_scope_picker.js', 'common/document/embed-picker.js', 'omo/api/documents/create.js', 'omo/api/projects/projects.js']) {
            await page.addScriptTag({path: path.join(root, file)});
        }
        await page.locator('[data-omo-project-detail-add-document]').click();
        await page.waitForFunction(() => document.getElementById('document-form')?.dataset.omoDocumentCreateReady === '1');
        assert.equal(await page.locator('[data-omo-projects-document-drawer]').isVisible(), true);
        assert.equal(await page.locator('#commonTopbarModal').isVisible(), false, 'Creation uses the drawer.');
        assert.equal(await page.locator('header [data-omo-subdrawer-title]').innerText(), 'Create document');
        await page.locator('header button[form="document-form"]').click();
        await page.waitForFunction(() => window.saved.length === 1);
        await page.locator('[data-omo-projects-document-drawer]').waitFor({state: 'hidden'});
        assert.deepEqual(await page.evaluate(() => window.saved[0]), {projectId: 42, documentId: 9});

        await page.locator('[data-omo-project-detail-document-menu-toggle]').click();
        assert.equal(await page.locator('[data-omo-project-detail-document-menu-panel]').isVisible(), true);
        await page.locator('[data-omo-project-import-document-url]').click();
        await page.locator('[data-document-picker-scope] canvas').waitFor();
        assert.equal(await page.locator('#commonTopbarModalTitle').innerText(), 'Importer un document');
        assert.equal(await page.locator('#commonTopbarModalBody .generic-tabs__tab').count(), 0, 'Import has no creation tab.');
        assert.equal(await page.locator('[data-omo-project-detail-document-menu-panel]').isVisible(), false);
        const ids = () => page.locator('[data-document-picker-select] option').evaluateAll(options => options.map(option => Number(option.value)));
        assert.deepEqual(await ids(), [1, 4], 'Organization documents remain available alongside the selected space.');
        await page.locator('[data-omo-holon-scope="children"]').click();
        assert.deepEqual(await ids(), [1, 2, 4]);
        await page.locator('[data-document-picker-search]').fill('Child');
        assert.deepEqual(await ids(), [2]);
        await page.locator('[data-document-picker-apply]').click();
        await page.waitForFunction(() => window.notifications.some(item => item.type === 'error'));
        assert.equal(await page.locator('#commonTopbarModal').isVisible(), true, 'A rejected import keeps the selector open.');
        await page.evaluate(() => { window.failImport = false; });
        await page.locator('[data-document-picker-apply]').click();
        await page.waitForFunction(() => window.saved.length === 2);
        assert.equal(await page.locator('#commonTopbarModal').isVisible(), false);
        assert.deepEqual(await page.evaluate(() => window.saved[1]), {projectId: 42, documentId: 2});
        assert.equal(await page.evaluate(() => window.posts[2].project_action), 'attach_document');
        assert.equal(await page.evaluate(() => window.requests.filter(url => url.includes('/documents/create.php')).length), 1, 'Import must not fetch a creation form.');
        assert.deepEqual(errors, []);
        console.log('project_document_workflow_test: OK');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
