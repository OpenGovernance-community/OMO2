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
        await page.setContent(`<div hidden data-omo-stats-editor>Other application editor</div>
            <div id="omo-projects-root" data-omo-projects-oid="7" data-omo-projects-action-url="/omo/api/projects/action.php">
                <div class="generic-menu generic-menu--split" data-omo-project-detail-document-menu>
                    <button data-omo-project-indicator-editor-url="/omo/api/stats/edit.php?oid=7&cid=10&project_id=42">Create indicator</button>
                    <button data-omo-project-detail-document-menu-toggle aria-expanded="false">&#9662;</button>
                    <div data-omo-project-detail-document-menu-panel hidden><button data-omo-project-resource-add data-resource-url="/omo/api/projects/resources.php?type=indicator&id=42&picker=1">Import indicator</button></div>
                </div>
                <div data-omo-projects-document-drawer hidden><header>
                    <h3 data-omo-subdrawer-title>Documents</h3><p data-omo-subdrawer-description></p>
                    <div data-omo-subdrawer-actions></div><button data-omo-projects-document-drawer-close>Close</button>
                </header><div data-omo-projects-document-drawer-body></div></div>
            </div>
            <div id="commonTopbarModal" class="common-topbar-modal" hidden><div class="common-topbar-modal__panel">
                <div class="common-topbar-modal__header"><h3 id="commonTopbarModalTitle"></h3><button data-topbar-modal-close>Close</button></div>
                <div id="commonTopbarModalBody"></div>
            </div></div>`);
        await page.route('**/*.css*', route => route.fulfill({contentType: 'text/css', body: ''}));
        for (const file of ['common/assets/components.css', 'common/assets/topbar.css']) {
            await page.addStyleTag({content: fs.readFileSync(path.join(root, file), 'utf8')});
        }
        await page.addScriptTag({path: require.resolve('d3', {paths: [path.dirname(process.argv[2] || __filename)]})});
        await page.evaluate(() => {
            window.saved = []; window.posts = []; window.notifications = []; window.failImport = true;
            window.omoStatsAfterIndicatorSave = () => { throw new Error('Project save must not navigate the Stats application.'); };
            const node = (ID, name, children = []) => ({ID, name, type: '2', mycolor: '#3388aa', children});
            const form = `<div id="omoProjectIndicatorEditor" data-omo-stats-editor>
                <div hidden data-omo-subdrawer-header data-omo-subdrawer-title="Create indicator"></div>
                <form id="omoProjectIndicatorForm" action="/omo/api/stats/action.php">
                    <input name="id" value="0" type="hidden"><input name="project_id" value="42" type="hidden">
                    <input name="name" value="Project indicator"><input name="stats_action" value="save_indicator" type="hidden">
                    <select name="source_type" data-omo-stats-source-type><option value="manual">Manual</option></select>
                    <div data-omo-stats-editor-feedback></div>
                    <button type="button" data-omo-stats-cancel-editor>Cancel</button><button type="submit" data-omo-stats-save-editor>Save</button>
                </form></div>
                <script>window.commonPageScripts['/omo/api/stats/edit.js']({projectId:42,title:'Create indicator',description:'Full editor',editorSaved:'Saved',editorSaving:'Saving',saveError:'Failed',labels:{},measurementScheduleOptions:{}});</script>`;
            const data = {success: true, projectId: 42, organizationId: 7, projectHolonId: 10, type: 'indicator', canCreate: true,
                items: [{id: 1, title: 'Local measure', contextHolonId: 10}, {id: 2, title: 'Child measure', contextHolonId: 11},
                    {id: 3, title: 'Peer measure', contextHolonId: 20}, {id: 4, title: 'Organization measure', contextHolonId: 0}],
                labels: {title: 'Import indicator', search: 'Search', existing: 'Indicators', none: 'None', attach: 'Link', cancel: 'Cancel', error: 'Failed'},
                scopeLabels: {local: 'Local', children: 'Children', descendants: 'Descendants'}};
            window.fetch = async (url, options = {}) => {
                if (options.method === 'POST') {
                    const post = Object.fromEntries(options.body.entries()); window.posts.push(post);
                    const result = String(url).includes('/stats/action.php') ? {success: true, id: 9}
                        : {success: !window.failImport, message: window.failImport ? 'Rejected' : 'Linked'};
                    return {ok: true, json: async () => result, text: async () => JSON.stringify(result)};
                }
                return {ok: true, text: async () => form, json: async () => String(url).includes('getStructureData')
                    ? node(7, 'Organization', [node(10, 'Team', [node(11, 'Child')]), node(20, 'Peer')]) : data};
            };
            window.omoNotify = window.commonNotify = (message, type) => window.notifications.push({message, type});
            window.addEventListener('omo-project-resource-saved', event => window.saved.push(event.detail));
        });
        for (const file of ['common/assets/components.js', 'common/assets/topbar.js', 'common/drawer/subdrawer.js',
            'common/holon_scope_picker.js', 'common/document/embed-picker.js', 'omo/api/stats/edit.js', 'omo/api/projects/projects.js']) {
            await page.addScriptTag({path: path.join(root, file)});
        }
        const openEditor = async () => {
            await page.locator('[data-omo-project-indicator-editor-url]').click();
            await page.waitForFunction(() => document.getElementById('omoProjectIndicatorEditor')?.dataset.omoStatsEditorReady === '1');
        };
        await openEditor();
        assert.equal(await page.locator('header [data-omo-subdrawer-title]').innerText(), 'Create indicator');
        assert.equal(await page.locator('#commonTopbarModal').isVisible(), false);
        await page.locator('header [data-omo-stats-cancel-editor]').click();
        await page.locator('[data-omo-projects-document-drawer]').waitFor({state: 'hidden'});
        assert.equal(await page.evaluate(() => window.posts.length), 0, 'Cancel creates nothing.');
        await openEditor();
        await page.locator('header [data-omo-stats-save-editor]').click();
        await page.waitForFunction(() => window.saved.length === 1);
        await page.locator('[data-omo-projects-document-drawer]').waitFor({state: 'hidden'});
        assert.deepEqual(await page.evaluate(() => window.saved[0]), {projectId: 42, resourceType: 'indicator', resourceId: 9});
        assert.equal(await page.evaluate(() => window.posts[0].project_id), '42');
        await page.locator('[data-omo-project-detail-document-menu-toggle]').click();
        await page.locator('[data-omo-project-resource-add]').click();
        await page.locator('[data-document-picker-scope] canvas').waitFor();
        assert.equal(await page.locator('#commonTopbarModalTitle').innerText(), 'Import indicator');
        assert.equal(await page.locator('#commonTopbarModalBody .generic-tabs__tab, [data-resource-create]').count(), 0, 'Import only selects existing indicators.');
        const ids = () => page.locator('[data-document-picker-select] option').evaluateAll(options => options.map(option => Number(option.value)));
        assert.deepEqual(await ids(), [1, 4]);
        await page.locator('[data-omo-holon-scope="children"]').click();
        assert.deepEqual(await ids(), [1, 2, 4]);
        await page.locator('[data-document-picker-search]').fill('Child');
        assert.deepEqual(await ids(), [2]);
        await page.locator('[data-document-picker-apply]').click();
        await page.waitForFunction(() => window.notifications.some(item => item.type === 'error'));
        assert.equal(await page.locator('#commonTopbarModal').isVisible(), true);
        await page.evaluate(() => { window.failImport = false; });
        await page.locator('[data-document-picker-apply]').click();
        await page.waitForFunction(() => document.getElementById('commonTopbarModal').hidden);
        assert.equal(await page.evaluate(() => window.posts[2].resource_id), '2');
        assert.equal(await page.evaluate(() => window.posts[2].project_action), 'attach_resource');
        assert.deepEqual(errors, []);
        console.log('project_indicator_picker_test: OK');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
