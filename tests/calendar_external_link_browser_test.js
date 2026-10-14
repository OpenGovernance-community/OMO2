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
        const html = fs.readFileSync(path.join(root, 'tmp/calendar-external-link-form.html'), 'utf8')
            .replace(/<script\b[^>]*>[\s\S]*?<\/script>/g, '').replace(/<link\b[^>]*>/g, '');
        for (const width of [1280, 390]) {
            const page = await browser.newPage({viewport: {width, height: 1000}});
            page.on('pageerror', error => errors.push(error.message));
            await page.setContent(html);
            await page.addStyleTag({path: path.join(root, 'common/assets/components.css')});
            await page.addStyleTag({path: path.join(root, 'omo/api/calendar/calendar.css')});
            await page.addScriptTag({path: path.join(root, 'common/assets/components.js')});
            await page.addScriptTag({path: path.join(root, 'common/calendar/event-editor.js')});
            await page.addScriptTag({path: path.join(root, 'common/calendar/document-fields.js')});
            await page.evaluate(() => window.omoInitCalendarEventEditor(document));
            const type = page.locator('[data-omo-calendar-document-type]');
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
            await page.screenshot({path: path.join(root, `tmp/calendar-external-link-${width}.png`), fullPage: true});
            await page.close();
        }
        assert.deepEqual(errors, []);
        console.log('calendar_external_link_browser_test: OK');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
