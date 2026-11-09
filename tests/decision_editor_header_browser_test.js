'use strict';
// Generate the saved editor fixtures with decision_evaluation_preview_test.php first.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require(process.argv[2] || 'playwright');
const root = path.join(__dirname, '..');
const source = fs.readFileSync(path.join(root, 'omo/api/decision/index.js'), 'utf8');
const start = source.indexOf('            if (decisionDrawerController) {', source.indexOf('elements.editorBody.innerHTML = data;'));
const end = source.indexOf('            if (typeof window.omoDecisionVoteInit', start);
assert(start >= 0 && end > start);
(async () => {
    const browser = await chromium.launch({headless: true, channel: 'msedge'});
    try {
        for (const width of [1280, 390]) for (const mode of ['editor', 'single-editor']) {
            const page = await browser.newPage({viewport: {width, height: 850}});
            await page.context().route('https://toolbar.test/**', route => {
                const file = path.join(root, new URL(route.request().url()).pathname);
                return fs.existsSync(file) && fs.statSync(file).isFile() && !file.endsWith('.php')
                    ? route.fulfill({path: file}) : route.fulfill({body: '<!doctype html><body>Preview</body>'});
            });
            await page.goto('https://toolbar.test/editor');
            await page.setContent('<div id="drawer"><header class="generic-drawer-header"><div class="generic-drawer-header__copy"><h2 data-omo-subdrawer-title></h2></div><div class="generic-drawer-header__actions" data-omo-subdrawer-actions></div></header><div id="editor-body"></div></div>');
            const html = fs.readFileSync(path.join(root, `tmp/decision-preview-${mode}.html`), 'utf8');
            await page.locator('#editor-body').evaluate((body, html) => { body.innerHTML = html; }, html);
            await page.addStyleTag({path: path.join(root, 'common/assets/components.css')});
            await page.addStyleTag({content: '#drawer { max-width: 1000px; margin: auto; } #editor-body { height: 550px; overflow: auto; }'});
            await page.addScriptTag({path: path.join(root, 'common/drawer/subdrawer.js')});
            await page.addScriptTag({content: `
                const elements = {editorBody: document.getElementById('editor-body')};
                const resolvedTitle = 'Scrutin';
                const decisionDrawerController = window.omoCreateSubdrawerController({drawer: document.getElementById('drawer')});
                ${source.slice(start, end)}
                window.submitCount = 0;
                document.addEventListener('submit', function (event) { event.preventDefault(); window.submitCount++; });
            `});
            const actions = page.locator('[data-omo-subdrawer-actions]');
            const preview = actions.getByRole('link', {name: 'Prévisualiser', exact: true});
            const save = actions.getByRole('button', {name: 'Enregistrer', exact: true});
            assert(await preview.isVisible() && await save.isVisible(), `${mode}: both actions must be visible in the header`);
            assert((await preview.getAttribute('class')).includes('generic-action-button--secondary'));
            assert((await save.getAttribute('class')).includes('generic-action-button--main'));
            const p = await preview.boundingBox(), s = await save.boundingBox();
            assert(p.x + p.width <= s.x + 1 && Math.abs(p.y - s.y) < 4, 'Preview sits directly to the left of Save');
            await page.locator('#editor-body').evaluate(body => { body.scrollTop = body.scrollHeight; });
            assert(await preview.isVisible() && await save.isVisible(), 'Actions stay visible while the questionnaire scrolls');
            const [popup] = await Promise.all([page.waitForEvent('popup'), preview.click()]);
            await popup.waitForURL(/evaluation_preview=1/);
            assert(popup.url().includes('evaluation_preview=1'));
            assert.equal(await page.evaluate(() => window.submitCount), 0, 'Preview does not submit the editor');
            await popup.close();
            if (mode === 'single-editor') {
                await page.locator('[data-omo-decision-editor-header-form]').evaluate(form => {
                    form.querySelectorAll('input[required], textarea[required]').forEach(field => { if (!field.value) field.value = 'Test'; });
                });
                await save.click();
                assert.equal(await page.evaluate(() => window.submitCount), 1, 'Save still submits the linked form');
            } else {
                assert(await save.getAttribute('data-omo-decision-multi-save-for'), 'Multi-question save action retains its binding');
            }
            await page.screenshot({path: path.join(root, `tmp/decision-header-${mode}-${width}.png`)});
            await page.close();
        }
        console.log('decision_editor_header_browser_test: OK (both editor layouts, two header actions, desktop and mobile)');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
