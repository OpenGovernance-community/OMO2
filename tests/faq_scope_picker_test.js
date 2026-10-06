'use strict';
// node tests/faq_scope_picker_test.js /path/to/playwright /path/to/chrome
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require(process.argv[2] || 'playwright');

(async () => {
    const browser = await chromium.launch({headless: true, ...(process.argv[3] ? {executablePath: process.argv[3]} : {})});
    try {
        const page = await browser.newPage();
        const errors = []; page.on('pageerror', error => errors.push(error.message));
        await page.setContent(`<div id="commonTopbarModal"><div class="common-topbar-modal__panel">
            <span id="commonTopbarModalTitle">FAQ editor</span><div id="commonTopbarModalBody">
            <div id="faqPopupRoot" data-faq-oid="1"><div data-faq-editor-view><div data-faq-form-shell>
            <form id="formulaire-edit"><div data-faq-scope-fields>
                <input type="hidden" name="IDorganization" value="1"><input type="hidden" name="IDholon" value="10">
                <input type="hidden" name="IDparcours" value="">
                <select data-faq-scope-kind><option value="organization">Organization</option><option value="generic">Generic</option></select>
                <div data-faq-scope-organization-shell><select data-faq-scope-organization><option value="1">One</option><option value="2">Two</option></select></div>
                <div data-faq-scope-holon-shell><span class="generic-form-control-group">
                    <input class="generic-form-control" type="text" readonly data-holon-target-label><input type="hidden" value="10" data-faq-scope-holon data-holon-target-id>
                    <button class="generic-action-button generic-action-button--secondary generic-action-button--icon-only" type="button" data-holon-target-selector>Structure</button>
                </span></div>
            </div></form></div></div></div></div></div></div>`);
        await page.evaluate(() => {
            const labels = {title: 'Choose space', hint: 'FAQ attachment', confirm: 'Confirm', cancel: 'Cancel', none: 'Organization'};
            const configs = {};
            for (const [organizationId, id] of [[1, 10], [2, 20]]) {
                configs[organizationId] = {organizationId, selectableHolonIds: [id], allowOrganization: true, organizationLabel: 'Organization', labels};
            }
            const shell = document.querySelector('[data-faq-scope-holon-shell]');
            shell.setAttribute('data-faq-holon-configs', JSON.stringify(configs));
            shell.setAttribute('data-faq-holon-names', JSON.stringify({10: 'Role A', 20: 'Role B'}));
            shell.querySelector('button').setAttribute('data-holon-target-selector', JSON.stringify(configs[1]));
        });
        await page.addStyleTag({content: ':root{--radius-md:6px;--generic-space-2:8px;--generic-space-4:16px}'});
        for (const stylesheet of ['common/assets/components.css', 'common/assets/admin-edit.css', 'omo/assets/css/faq.css']) {
            await page.addStyleTag({content: fs.readFileSync(path.join(__dirname, '..', stylesheet), 'utf8')});
        }
        for (const script of ['common/assets/topbar.js', 'common/holon_scope_picker.js', 'omo/assets/js/faq.js']) {
            await page.addScriptTag({content: fs.readFileSync(path.join(__dirname, '..', script), 'utf8')});
        }
        await page.evaluate(() => {
            document.querySelector('[data-faq-editor-view]').hidden = false;
            window.omoMountHolonScopePicker = config => {
                window.lastPickerConfig = config;
                let selected = config.initialHolonId;
                window.selectSpace = id => { selected = id; config.onChange(id); };
                queueMicrotask(() => config.onReady(selected));
                return {getSelectedHolonLabel: () => selected === 10 ? 'Role A' : 'Role B',
                    setSelectedHolonId: window.selectSpace, destroy() {}};
            };
        });
        assert.equal(await page.locator('[data-holon-target-label]').inputValue(), 'Role A');
        for (const width of [1000, 390]) {
            await page.setViewportSize({width, height: 900});
            const geometry = await page.locator('.generic-form-control-group').evaluate(group => {
                const input = group.querySelector('[data-holon-target-label]'), button = group.querySelector('button');
                const a = input.getBoundingClientRect(), b = button.getBoundingClientRect();
                return [b.left - a.right, getComputedStyle(input).borderTopRightRadius, getComputedStyle(button).borderTopLeftRadius, b.width];
            });
            assert.deepEqual(geometry, [-1, '0px', '0px', 34], 'The adminEdit field and icon button must remain joined.');
        }
        await page.locator('[data-faq-scope-organization]').selectOption('2');
        assert.equal(await page.locator('input[name="IDholon"]').inputValue(), '0', 'Changing organization clears the old assignment.');
        assert.equal(await page.locator('[data-holon-target-label]').inputValue(), 'Organization');
        await page.locator('[data-holon-target-selector]').click();
        assert.deepEqual(await page.evaluate(() => [window.lastPickerConfig.organizationId, window.lastPickerConfig.selectableHolonIds]), [2, [20]]);
        await page.evaluate(() => window.selectSpace(10));
        assert(await page.locator('[data-holon-target-apply]').isDisabled(), 'A space in another organization cannot be accepted.');
        await page.evaluate(() => window.selectSpace(20));
        await page.locator('[data-holon-target-apply]').click();
        assert.equal(await page.locator('[data-holon-target-label]').inputValue(), 'Role B');
        assert.equal(await page.locator('input[name="IDholon"]').inputValue(), '20', 'Confirmation updates the submitted FAQ attachment.');
        assert.equal(await page.locator('input[name="IDorganization"]').inputValue(), '2');
        await page.locator('[data-faq-scope-kind]').selectOption('generic');
        assert.equal(await page.locator('input[name="IDholon"]').inputValue(), '');
        assert.equal(await page.locator('input[name="IDorganization"]').inputValue(), '');
        assert(await page.locator('[data-holon-target-selector]').isDisabled());
        await page.locator('[data-faq-scope-kind]').selectOption('organization');
        assert.equal(await page.locator('input[name="IDholon"]').inputValue(), '20', 'Returning to organization scope restores the compatible choice.');
        await page.locator('[data-holon-target-selector]').click();
        await page.locator('[data-holon-target-none]').click();
        await page.locator('[data-holon-target-apply]').click();
        assert.equal(await page.locator('input[name="IDholon"]').inputValue(), '0', 'Organization-wide assignment remains available.');
        assert.deepEqual(errors, []);
        console.log('faq_scope_picker_test: OK');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
