'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require(process.argv[2] || 'playwright');
const root = path.resolve(__dirname, '..');

(async () => {
    const browser = await chromium.launch({headless: true, channel: 'msedge'});
    try {
        const errors = [];
        for (const host of ['calendar', 'project']) {
            const html = fs.readFileSync(path.join(root, host === 'calendar' ? 'tmp/calendar-attachment-form.html' : 'tmp/calendar-external-link-form.html'), 'utf8')
                .replace(/<script\b[^>]*>[\s\S]*?<\/script>/g, '').replace(/<link\b[^>]*>/g, '');
            const parent = fs.readFileSync(path.join(root, `omo/api/${host === 'calendar' ? 'calendar' : 'projects'}/index.php`), 'utf8');
            assert(parent.includes("commonAssetUrl('/common/calendar/document-fields.js')"), 'Parent must load the helper before inserting the form');
            for (const width of [1280, 390]) {
                const page = await browser.newPage({viewport: {width, height: 1000}});
                page.on('pageerror', error => errors.push(error.message));
                await page.route('https://calendar.example.invalid/**', route => route.fulfill({contentType: 'text/html', body: '<html></html>'}));
                await page.goto('https://calendar.example.invalid/');
                const config = {labels: {}, weekdays: [], items: [], views: {contextual: {month: {title: 'October', days: []}}}};
                await page.setContent(host === 'project' ? '<main id="project-editor"></main>' :
                    '<main id="omo-calendar-root" data-omo-calendar-view="month" data-omo-calendar-can-create="1" data-omo-calendar-create-url="/omo/api/calendar/create.php?oid=1" data-omo-calendar-current-url="/omo/api/calendar/index.php?oid=1">'
                    + '<button data-omo-calendar-open-create>Ajouter</button><script type="application/json" data-omo-calendar-data>' + JSON.stringify(config) + '</script><div data-omo-calendar-views></div>'
                    + '<aside hidden data-omo-calendar-editor-drawer><h2 data-omo-calendar-editor-title></h2><p data-omo-calendar-editor-description></p><div data-omo-calendar-editor-actions></div><div data-omo-calendar-editor-body></div></aside></main>');
                await page.evaluate(html => {
                    window.commonNotify = () => {};
                    window.omoReplaceFetchedPanelRoot = () => Promise.resolve();
                    window.fetch = async (url, options = {}) => {
                        if (options.method === 'POST') {
                            const file = options.body.get('document_uploaded_file');
                            window.sentFile = file ? {name: file.name, text: await file.text()} : null;
                            return {ok: true, json: async () => ({status: true, message: 'Saved'})};
                        }
                        return {ok: true, text: async () => html};
                    };
                }, html);
                await page.addStyleTag({path: path.join(root, 'common/assets/components.css')});
                await page.addStyleTag({path: path.join(root, 'omo/api/calendar/calendar.css')});
                await page.addScriptTag({path: path.join(root, 'common/assets/components.js')});
                await page.addScriptTag({path: path.join(root, 'common/calendar/event-editor.js')});
                await page.addScriptTag({path: path.join(root, 'common/calendar/document-fields.js')});
                if (host === 'project') {
                    await page.evaluate(html => {
                        document.querySelector('#project-editor').innerHTML = html;
                        window.omoInitCalendarEventEditor(document);
                    }, html);
                } else {
                    for (const file of ['common/calendar/availability-model.js', 'common/calendar/availability-view.js', 'common/calendar/availability.js', 'omo/api/calendar/calendar.js']) {
                        await page.addScriptTag({path: path.join(root, file)});
                    }
                    await page.evaluate(() => window.omoInitCalendar(document.querySelector('#omo-calendar-root')));
                    await page.locator('[data-omo-calendar-open-create]').click();
                }
                const type = page.locator('[data-omo-calendar-document-type]');
                await type.waitFor();
                const input = page.locator('[name="document_external_url"]');
                assert.equal(await input.isVisible(), false);
                await type.selectOption('external_link');
                assert.equal(await input.isVisible(), true);
                assert.equal(await input.isEnabled(), true);
                assert.equal(await input.evaluate(node => node.required && !node.checkValidity()), true);
                await input.fill('https://example.invalid/reference?meeting=1&part=2');
                assert.equal(await input.evaluate(node => node.checkValidity()), true);
                assert.equal(await input.evaluate(node => new FormData(node.form).get(node.name)),
                    'https://example.invalid/reference?meeting=1&part=2');
                const bounds = await input.boundingBox();
                assert(bounds.width > 0 && bounds.x >= 0 && bounds.x + bounds.width <= width + 1, 'URL field fits the viewport');
                await input.fill('invalid-url');
                await type.selectOption('html');
                assert.equal(await input.isVisible(), false);
                assert.equal(await input.evaluate(node => !node.required && node.disabled && !new FormData(node.form).has(node.name)), true);
                await type.selectOption('external_link');
                assert.equal(await input.inputValue(), 'invalid-url', 'Type changes preserve the URL draft');
                await page.locator('[name="document_template_id"]').evaluate(select => {
                    const option = new Option('Link template', '987');
                    option.dataset.omoCalendarDocumentTemplateType = 'external_link';
                    option.dataset.omoCalendarDocumentTemplateScope = 'organization';
                    select.append(option);
                });
                await page.locator('[name="document_template_id"]').selectOption('987');
                assert.equal(await input.isVisible(), false);
                assert.equal(await input.isDisabled(), true, 'Template supplies its own URL');
                await page.locator('[name="document_template_id"]').selectOption('0');
                assert.equal(await input.isVisible(), true);
                await input.fill('https://example.invalid/reference');
                await page.screenshot({path: path.join(root, `tmp/calendar-external-link-${host}-${width}.png`), fullPage: true});
                const file = page.locator('[name="document_uploaded_file"]');
                await type.selectOption('uploaded_file');
                assert.equal(await input.isDisabled(), true);
                assert.equal(await file.isVisible(), true);
                assert.equal(await file.evaluate(node => node.required && !node.checkValidity()), true);
                await file.setInputFiles({name: 'meeting.txt', mimeType: 'text/plain', buffer: Buffer.from('Meeting attachment')});
                assert.equal(await file.evaluate(node => node.checkValidity()), true);
                await type.selectOption('html');
                assert.equal(await file.isVisible(), false);
                assert.equal(await file.evaluate(node => !node.required && node.disabled && !new FormData(node.form).has(node.name)), true);
                await type.selectOption('uploaded_file');
                assert.equal(await file.evaluate(node => new FormData(node.form).get(node.name).name), 'meeting.txt');
                await page.locator('[name="document_template_id"]').evaluate(select => {
                    const option = new Option('File template', '988');
                    option.dataset.omoCalendarDocumentTemplateType = 'uploaded_file';
                    option.dataset.omoCalendarDocumentTemplateScope = 'organization';
                    select.append(option);
                });
                await page.locator('[name="document_template_id"]').selectOption('988');
                assert.equal(await file.isVisible(), false);
                assert.equal(await file.isDisabled(), true);
                await page.locator('[name="document_template_id"]').selectOption('0');
                assert.equal(await file.isVisible(), true);
                await page.locator('[name="title"]').fill('Meeting with a file');
                await page.locator('[name="document_title"]').fill('Meeting notes');
                await page.screenshot({path: path.join(root, `tmp/calendar-file-${host}-${width}.png`), fullPage: true});
                await page.locator('[data-omo-calendar-create-form]').evaluate(form => form.requestSubmit());
                await page.waitForFunction(() => window.sentFile);
                assert.deepEqual(await page.evaluate(() => window.sentFile), {name: 'meeting.txt', text: 'Meeting attachment'});
                await page.close();
            }
        }
        assert.deepEqual(errors, []);
        console.log('calendar_external_link_browser_test: OK');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
