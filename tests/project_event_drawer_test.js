'use strict';
// node tests/project_event_drawer_test.js /path/to/playwright /path/to/chrome
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require(process.argv[2] || 'playwright');
const source = file => fs.readFileSync(path.join(__dirname, '..', file), 'utf8');

function eventForm(contextField) {
    return `<div class="omo-calendar-create">
        <div hidden data-omo-subdrawer-header data-omo-subdrawer-title="New event">
            <button type="submit" form="event-form" data-omo-subdrawer-action data-omo-calendar-create-submit>Create event</button>
        </div>
        <form id="event-form" action="/omo/api/calendar/create.php?project_id=7&editor_host=project"
            data-omo-calendar-create-form data-omo-calendar-editor-host="project">
            ${contextField}
            <input name="project_id" value="7" type="hidden">
            <input name="title" value="Project event">
            <input name="start_at" type="datetime-local" value="2026-10-08T10:00">
            <input name="end_at" type="datetime-local" value="2026-10-08T11:00">
            <label><input name="is_all_day" type="checkbox" value="1">All day</label>
            <select name="document_type" data-omo-calendar-document-type><option value="pv">PV</option><option value="note">Note</option></select>
            <div data-omo-calendar-document-fields>
                <select name="document_template_id">
                    <option value="0">No template</option>
                    <option value="1" data-omo-calendar-document-template-type="pv" data-omo-calendar-document-template-scope="organization">Organization</option>
                    <option value="2" data-omo-calendar-document-template-type="pv" data-omo-calendar-document-template-scope="circle" data-omo-calendar-document-template-target="10">Parent circle</option>
                    <option value="3" data-omo-calendar-document-template-type="pv" data-omo-calendar-document-template-scope="circle" data-omo-calendar-document-template-target="99">Other circle</option>
                    <option value="4" data-omo-calendar-document-template-type="pv" data-omo-calendar-document-template-scope="role" data-omo-calendar-document-template-target="42">Current role</option>
                    <option value="5" data-omo-calendar-document-template-type="note" data-omo-calendar-document-template-scope="organization">Other type</option>
                </select>
            </div>
            <div data-omo-calendar-create-feedback></div>
        </form>
    </div>`;
}

(async () => {
    const browser = await chromium.launch({headless: true, ...(process.argv[3] ? {executablePath: process.argv[3]} : {})});
    try {
        for (const contextField of [
            '<input type="hidden" name="IDholon" value="42" data-omo-calendar-context-holon data-holon-context-path="10,42">',
            '<select name="IDholon" data-omo-calendar-context-holon><option value="42" data-omo-calendar-context-path="10,42">Context</option></select>'
        ]) {
            const page = await browser.newPage();
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.route('http://project.test/', route => route.fulfill({contentType: 'text/html', body: '<html><body></body></html>'}));
            await page.route('**/*.css*', route => route.fulfill({contentType: 'text/css', body: ''}));
            await page.goto('http://project.test/');
            await page.setContent(`<div id="omo-projects-root">
                <button data-omo-project-detail-add-event data-omo-project-detail-add-event-url="/omo/api/calendar/create.php?project_id=7&editor_host=project&cid=42">Add event</button>
                <div data-omo-projects-document-drawer hidden><header>
                    <h3 data-omo-subdrawer-title>Documents</h3><p data-omo-subdrawer-description>Documents</p>
                    <div><div data-omo-subdrawer-actions></div><button data-omo-projects-document-drawer-close>Close</button></div>
                </header><div data-omo-projects-document-drawer-body></div></div>
            </div>`);
            await page.addStyleTag({content: source('common/assets/components.css')});
            await page.evaluate(html => {
                window.requests = [];
                window.eventsSaved = [];
                window.fetch = async (url, options) => {
                    window.requests.push({url, method: options.method || 'GET'});
                    if (options.method !== 'POST') {
                        await new Promise(resolve => {window.finishLoading = resolve;});
                    }
                    return {ok: true, text: async () => html, json: async () => ({status: true, projectId: 7, eventId: 12})};
                };
                window.addEventListener('omo-project-event-saved', event => window.eventsSaved.push(event.detail));
            }, eventForm(contextField));
            for (const file of ['common/assets/components.js', 'common/drawer/subdrawer.js', 'common/calendar/availability-model.js', 'common/calendar/availability-view.js', 'common/calendar/availability.js', 'common/calendar/event-editor.js', 'omo/api/projects/projects.js']) {
                await page.addScriptTag({content: source(file)});
            }
            await page.locator('[data-omo-project-detail-add-event]').click();
            assert.equal(await page.locator('[data-omo-projects-document-drawer-body]').innerText(), 'Chargement...');
            const loadingState = page.locator('[data-common-loading-state]');
            assert.equal(await loadingState.getAttribute('role'), 'status');
            assert.equal(await loadingState.locator('.generic-loading-indicator__spinner').count(), 1);
            assert.equal(await loadingState.evaluate(el => getComputedStyle(el).borderTopWidth), '0px');
            assert.equal(await loadingState.evaluate(el => getComputedStyle(el).borderRadius), '0px');
            assert.equal(await loadingState.evaluate(el => getComputedStyle(el).justifyItems), 'center');
            assert.equal(await loadingState.locator('.generic-loading-indicator__spinner').evaluate(el => getComputedStyle(el).animationName), 'generic-loading-indicator-spin');
            await page.evaluate(() => window.finishLoading());
            await page.waitForFunction(() => document.querySelector('[data-omo-calendar-create-form]')?.dataset.omoCalendarStandaloneReady === '1');
            assert.equal(await page.locator('header [data-omo-subdrawer-title]').innerText(), 'New event', 'The event header must replace the document header.');
            assert.equal(await page.locator('[data-omo-projects-document-drawer]').evaluate(el => el.classList.contains('is-calendar-event-editor')), true);
            assert.equal(await page.locator('[data-omo-projects-document-drawer-body]').innerText().then(text => text.includes('Impossible')), false);
            const available = () => page.locator('select[name="document_template_id"] option').evaluateAll(options => options.filter(option => !option.disabled && !option.hidden).map(option => option.value));
            assert.deepEqual(await available(), ['0', '1', '2', '4'], 'Template visibility must retain the context path, role and document type.');
            await page.locator('select[name="document_type"]').selectOption('note');
            assert.deepEqual(await available(), ['0', '5']);
            assert.equal((await page.evaluate(() => window.requests))[0].url, '/omo/api/calendar/create.php?project_id=7&editor_host=project&cid=42');
            const scheduleFields = page.locator('input[name="start_at"], input[name="end_at"]');
            await page.getByLabel('All day').check();
            assert.deepEqual(await scheduleFields.evaluateAll(fields => fields.map(field => ({readonly: field.readOnly, inactive: field.getAttribute('aria-disabled')}))), [
                {readonly: true, inactive: 'true'}, {readonly: true, inactive: 'true'}
            ]);
            assert.deepEqual(await page.locator('form').evaluate(form => {
                const data = new FormData(form);
                return [data.get('start_at'), data.get('end_at'), data.get('is_all_day')];
            }), ['2026-10-08T10:00', '2026-10-08T11:00', '1'], 'All-day fields must retain their values in the submitted data.');
            await page.getByLabel('All day').uncheck();
            assert.deepEqual(await scheduleFields.evaluateAll(fields => fields.map(field => field.readOnly)), [false, false]);
            await page.getByLabel('All day').check();
            await page.locator('header [data-omo-calendar-create-submit]').click();
            await page.waitForFunction(() => window.eventsSaved.length === 1);
            assert.deepEqual(await page.evaluate(() => window.eventsSaved), [{projectId: 7, eventId: 12}]);
            await page.locator('[data-omo-projects-document-drawer]').waitFor({state: 'hidden'});
            assert.deepEqual(errors, []);
            await page.close();
        }
        console.log('project_event_drawer_test: OK');
    } finally {await browser.close();}
})().catch(error => {console.error(error); process.exitCode = 1;});
