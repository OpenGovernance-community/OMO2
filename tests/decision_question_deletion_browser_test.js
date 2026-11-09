'use strict';
// Generate the real editor HTML with decision_evaluation_preview_test.php first.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require(process.argv[2] || 'playwright');
const root = path.join(__dirname, '..');
const html = fs.readFileSync(path.join(root, 'tmp/decision-preview-editor.html'), 'utf8');
const config = JSON.parse(html.match(/window\.commonPageScripts\["\/omo\/api\/decision\/edit_shared\.js"\]\((\{[^\n]+\}), document\.currentScript/)[1]);
(async () => {
    const browser = await chromium.launch({headless: true, channel: 'msedge'});
    try {
        for (const width of [1280, 390]) for (const duplicate of [false, true]) {
            const page = await browser.newPage({viewport: {width, height: 900}});
            page.setDefaultTimeout(10000);
            const errors = []; let accept = false; let message = '';
            page.on('pageerror', error => errors.push(error.message));
            page.on('dialog', async dialog => { message = dialog.message(); if (accept) await dialog.accept(); else await dialog.dismiss(); });
            await page.route('**/*', route => route.fulfill({body: ''}));
            await page.goto('https://delete.test/editor');
            await page.setContent('<main id="editor"></main>');
            await page.locator('#editor').evaluate((node, html) => { node.innerHTML = html; }, html);
            await page.locator('[data-omo-subdrawer-header]').evaluate(node => { node.hidden = false; });
            if (duplicate) await page.locator('[data-omo-decision-process-form] [name="id"]').evaluate(field => { field.value = '0'; });
            await page.addStyleTag({path: path.join(root, 'common/assets/components.css')});
            await page.addStyleTag({path: path.join(root, 'common/choice/decision_cards.css')});
            await page.addScriptTag({path: path.join(root, 'omo/api/decision/edit_shared.js')});
            await page.evaluate(config => {
                window.commonNotify = () => {};
                window.commonPageScripts['/omo/api/decision/edit_shared.js'](config);
            }, config);
            const editor = page.locator('[data-omo-decision-multi-editor]');
            const panels = editor.locator('[data-omo-decision-question-panel]');
            const tabs = editor.locator('[data-omo-decision-question-link]');
            const actions = editor.locator('[data-omo-decision-question-actions]:visible');
            const ids = await panels.locator('[name="gid"]').evaluateAll(fields => fields.map(field => field.value));
            await actions.click();
            const menu = page.getByRole('menu', {name: 'Actions de la question', exact: true});
            assert.equal(await menu.getByRole('menuitem').count(), 2);
            const bounds = await menu.boundingBox();
            assert(bounds.x >= 0 && bounds.x + bounds.width <= width, 'Floating menu fits the viewport and escapes the scrolling tabs');
            await menu.screenshot({path: path.join(root, `tmp/decision-question-actions-${width}.png`)});
            await menu.getByRole('menuitem', {name: 'Supprimer', exact: true}).click();
            assert(message.includes('Restaurant') && message.includes('toutes ses propositions') && message.includes('enregistrement'));
            assert.equal(await panels.count(), 3, 'Cancelling the confirmation preserves the whole questionnaire');
            assert.equal(await editor.evaluate(node => node.commonDrawerHasUnsavedChanges()), false);
            accept = true;
            await menu.getByRole('menuitem', {name: 'Supprimer', exact: true}).click();
            assert.equal(await panels.count(), 2);
            assert.equal(await tabs.count(), 2);
            assert.equal(await editor.locator('[data-omo-decision-question-panel]:not([hidden])').count(), 1);
            assert.equal(await editor.evaluate(node => node.commonDrawerHasUnsavedChanges()), true);
            await actions.click();
            await menu.getByRole('menuitem', {name: 'Supprimer', exact: true}).click();
            assert.equal(await panels.count(), 1);
            assert.equal(await tabs.first().innerText(), 'Bloc 1', 'Unnamed remaining tabs are renumbered');
            await actions.click();
            assert(await menu.getByRole('menuitem', {name: 'Supprimer', exact: true}).isDisabled(), 'The last question cannot be deleted');
            await menu.getByRole('menuitem', {name: 'Éditer', exact: true}).press('Escape');
            await page.evaluate(() => {
                const source = document.querySelector('[data-omo-decision-question-panel]').cloneNode(true);
                source.removeAttribute('data-omo-decision-question-panel');
                source.setAttribute('data-omo-decision-question-fragment', '');
                source.querySelector('[name="gid"]').value = '0';
                window.commonExecuteFragmentScripts = async () => {};
                window.fetch = async () => ({ok: true, text: async () => source.outerHTML});
            });
            await editor.locator('[data-omo-decision-question-add-toggle]').click();
            await editor.locator('[data-omo-decision-question-add-method="consent"]').click();
            await page.waitForFunction(() => document.querySelectorAll('[data-omo-decision-question-panel]').length === 2);
            await actions.click();
            await menu.getByRole('menuitem', {name: 'Supprimer', exact: true}).click();
            assert.equal(await panels.count(), 1, 'An unsaved question can also be deleted');
            assert.deepEqual(await editor.evaluate(node => Array.from(node.deletedQuestionIds || [])), duplicate ? [] : ids.slice(0, 2), 'Unsaved and duplicate questions never delete source database rows');
            await page.evaluate(() => {
                window.saved = null;
                window.fetch = async (url, options) => {
                    window.saved = {deleted: options.body.getAll('deleted_groups[]'), groups: options.body.getAll('groups[]')};
                    return {ok: true, json: async () => ({status: true})};
                };
            });
            await page.locator('[data-omo-decision-multi-save-for]').click();
            await page.waitForFunction(() => window.saved !== null);
            assert.deepEqual(await page.evaluate(() => window.saved.deleted), duplicate ? [] : ids.slice(0, 2));
            assert.equal(await page.evaluate(() => window.saved.groups.length), 1);
            assert.equal(await editor.evaluate(node => node.commonDrawerHasUnsavedChanges()), false);
            assert.deepEqual(errors, []);
            await page.close();
        }
        console.log('decision_question_deletion_browser_test: OK (confirmation, cancel, last question, unsaved questions, duplication, save, desktop and mobile)');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
