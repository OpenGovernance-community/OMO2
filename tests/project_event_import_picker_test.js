'use strict';
const assert = require('node:assert/strict');
const path = require('node:path');
const fs = require('node:fs');
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
            <div class="generic-menu generic-menu--split" data-omo-project-detail-event-menu>
                <button class="generic-action-button">Create event</button>
                <button class="generic-menu-toggle" data-omo-project-detail-event-menu-toggle aria-expanded="false">&#9662;</button>
                <div class="generic-menu-panel" data-omo-project-detail-event-menu-panel hidden>
                    <button data-omo-project-import-event-url="/omo/api/projects/event_import.php?id=42">Import event</button>
                </div>
            </div></div>
            <div id="commonTopbarModal" class="common-topbar-modal" hidden>
                <div class="common-topbar-modal__panel"><div class="common-topbar-modal__header">
                    <div class="generic-heading-with-help"><h3 id="commonTopbarModalTitle"></h3>
                        <div id="commonTopbarModalHelp" hidden><details class="generic-context-help generic-context-help--compact generic-context-help--align-start">
                            <summary aria-label="Help">i</summary><div class="generic-context-help__content"></div>
                        </details></div>
                    </div><button data-topbar-modal-close>Close</button>
                </div><div id="commonTopbarModalBody"></div></div>
            </div>`);
        await page.route('**/*.css*', route => route.fulfill({contentType: 'text/css', body: ''}));
        await page.addStyleTag({content: fs.readFileSync(path.join(root, 'common/assets/components.css'), 'utf8')});
        await page.addStyleTag({content: fs.readFileSync(path.join(root, 'common/assets/topbar.css'), 'utf8')});
        await page.addScriptTag({path: require.resolve('d3', {paths: [path.dirname(process.argv[2] || __filename)]})});
        await page.evaluate(() => {
            window.posts = []; window.notifications = []; window.saved = []; window.fail = false;
            const node = (ID, name, children = []) => ({ID, name, type: '2', mycolor: '#3388aa', children});
            const data = {success: true, projectId: 42, organizationId: 7, projectHolonId: 10, csrf: 'fixture',
                items: [{id: 1, eventId: 1, source: 'internal', title: 'Local event', contextHolonId: 10},
                    {id: 2, eventId: 2, source: 'internal', title: 'Child event', contextHolonId: 11},
                    {id: 3, eventId: 3, source: 'internal', title: 'Peer event', contextHolonId: 20},
                    {id: -1, eventId: 1, source: 'external', title: 'Google appointment', contextHolonId: 0}],
                tabs: [{value: 'internal', label: 'OMO'}, {value: 'external', label: 'External', scope: false}],
                scopeLabels: {local: 'Local', children: 'Children', descendants: 'Descendants'},
                labels: {modalTitle: 'Import event', search: 'Search', insert: 'Link', cancel: 'Cancel', none: 'None', hint: 'Shared with the project'}};
            window.fetch = async (url, options = {}) => {
                if (options.method === 'POST') {
                    window.posts.push(Object.fromEntries(options.body.entries()));
                    await new Promise(resolve => { window.finishPost = resolve; });
                    return {ok: !window.fail, json: async () => ({success: !window.fail, projectId: 42, message: window.fail ? 'Rejected' : 'Linked'})};
                }
                return {ok: true, json: async () => String(url).includes('getStructureData')
                    ? node(7, 'Organization', [node(10, 'Team', [node(11, 'Child')]), node(20, 'Peer')]) : data};
            };
            window.omoNotify = window.commonNotify = (message, type) => window.notifications.push({message, type});
            window.addEventListener('omo-project-event-saved', e => window.saved.push(e.detail));
        });
        for (const file of ['common/assets/components.js', 'common/assets/topbar.js', 'common/holon_scope_picker.js', 'common/document/embed-picker.js', 'omo/api/projects/projects.js']) {
            await page.addScriptTag({path: path.join(root, file)});
        }
        await page.locator('[data-omo-project-detail-event-menu-toggle]').click();
        assert.equal(await page.locator('[data-omo-project-detail-event-menu-panel]').isVisible(), true, JSON.stringify({errors, state: await page.locator('#omo-projects-root').innerHTML()}));
        await page.locator('[data-omo-project-import-event-url]').click();
        await page.locator('[data-document-picker-scope] canvas').waitFor();
        assert.equal(await page.locator('.generic-tabs > .generic-tabs__list > [data-document-picker-tab]').count(), 2, 'Both source tabs retain the shared tabs container.');
        assert.deepEqual(await page.locator('[data-document-picker-tab="internal"]').evaluate(el => {
            const style = getComputedStyle(el);
            return [style.paddingTop, style.paddingLeft];
        }), ['11px', '16px'], 'Tab spacing comes from the generic tabs defaults.');
        assert.equal(await page.locator('#commonTopbarModalBody').getByText('Shared with the project').count(), 0, 'Help must not take space in the selector body.');
        const help = page.locator('#commonTopbarModalHelp details');
        assert.equal(await help.isVisible(), true);
        await help.locator('summary').click();
        assert.equal(await help.getAttribute('open'), '');
        assert.equal(await help.locator('.generic-context-help__content').innerText(), 'Shared with the project');
        assert.equal(await help.evaluate(el => {
            const content = el.querySelector('.generic-context-help__content').getBoundingClientRect();
            const panel = el.closest('.common-topbar-modal__panel').getBoundingClientRect();
            return content.left >= panel.left && content.right <= panel.right;
        }), true, 'Help must remain within the popup panel.');
        assert.equal(await page.locator('.common-topbar-modal__panel').evaluate(el => el.classList.contains('is-dragging')), false);
        await page.evaluate(() => window.commonTopbarPushModal('Nested', '<p>Nested content</p>', 'html'));
        assert.equal(await page.locator('#commonTopbarModalHelp').isVisible(), false, 'Nested popups must not inherit the help.');
        await page.evaluate(() => window.commonTopbarPopModal());
        assert.equal(await help.isVisible(), true, 'Returning to the picker restores its help.');
        const select = page.locator('[data-document-picker-select]');
        const ids = () => select.locator('option').evaluateAll(options => options.map(option => Number(option.value)));
        assert.deepEqual(await ids(), [1]);
        await page.locator('[data-omo-holon-scope="children"]').click();
        assert.deepEqual(await ids(), [1, 2], 'Structure navigation includes child-space events.');
        await page.locator('[data-document-picker-tab="external"]').click();
        assert.deepEqual(await ids(), [-1], 'Personal external events bypass the organization scope.');
        assert.equal(await page.locator('[data-document-picker-scope]').isVisible(), false);
        await page.locator('[data-document-picker-search]').fill('absent');
        assert.equal(await page.locator('[data-document-picker-apply]').isDisabled(), true);
        await page.locator('[data-document-picker-search]').fill('Google');
        await page.evaluate(() => { window.fail = true; });
        await page.locator('[data-document-picker-apply]').click();
        assert.equal(await page.locator('[data-document-picker-apply]').isDisabled(), true);
        await page.evaluate(() => window.finishPost());
        await page.waitForFunction(() => window.notifications.length === 1);
        assert.equal(await page.locator('#commonTopbarModalBody').count(), 1, 'Rejected import leaves the selector open.');
        await page.evaluate(() => { window.fail = false; });
        await page.locator('[data-document-picker-apply]').click();
        await page.evaluate(() => window.finishPost());
        await page.waitForFunction(() => window.saved.length === 1);
        await page.waitForFunction(() => document.getElementById('commonTopbarModal').hidden);
        assert.equal(await page.locator('#commonTopbarModalHelp').isVisible(), false);
        await page.evaluate(() => window.commonTopbarOpenModal('Other popup', '<p>No help</p>', 'html'));
        assert.equal(await page.locator('#commonTopbarModalHelp').isVisible(), false, 'Help must not leak into the next popup.');
        const posts = await page.evaluate(() => window.posts);
        assert.deepEqual(posts[1], {id: '42', _csrf: 'fixture', action: 'attach', source: 'external', event_id: '1'});
        assert.deepEqual(await page.evaluate(() => window.notifications.map(item => item.type)), ['error', 'success']);
        assert.deepEqual(errors, []);
        console.log('project_event_import_picker_test: OK');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
