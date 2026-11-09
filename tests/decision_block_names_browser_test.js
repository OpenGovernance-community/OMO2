'use strict';
// Generate the saved HTML with decision_evaluation_preview_test.php first.
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
        for (const width of [1280, 390]) {
            const page = await browser.newPage({viewport: {width, height: 900}});
            page.setDefaultTimeout(10000);
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.route('**/*', route => route.fulfill({body: ''}));
            await page.goto('https://rename.test/editor');
            await page.setContent('<main id="editor"></main>');
            await page.locator('#editor').evaluate((node, value) => { node.innerHTML = value; }, html);
            await page.locator('[data-omo-subdrawer-header]').evaluate(node => { node.hidden = false; });
            await page.addStyleTag({path: path.join(root, 'common/assets/components.css')});
            await page.addStyleTag({path: path.join(root, 'common/choice/decision_cards.css')});
            await page.addScriptTag({path: path.join(root, 'omo/api/decision/edit_shared.js')});
            await page.evaluate(config => {
                window.commonNotify = () => {};
                window.commonPageScripts['/omo/api/decision/edit_shared.js'](config);
            }, config);
            const editor = page.locator('[data-omo-decision-multi-editor]');
            const tabs = editor.locator('[data-omo-decision-question-link]');
            const pencils = editor.locator('[data-omo-decision-question-actions]:visible');
            const editSelected = async () => {
                await pencils.click();
                await page.getByRole('menuitem', {name: 'Éditer', exact: true}).click();
            };
            const input = editor.locator('.omo-decision-edit__question-name:visible');
            const panels = editor.locator('[data-omo-decision-question-panel]');
            assert.equal(await pencils.count(), 1, 'Only the active block has a pencil');
            const original = await panels.nth(0).locator('[name="short_title"]').inputValue();
            const questions = await panels.locator('[name="title"]').evaluateAll(nodes => nodes.map(node => node.value));
            await editSelected();
            await input.fill('Restaurant');
            const shortWidth = (await input.boundingBox()).width;
            assert(shortWidth < 160, 'Short tab names have a compact input');
            await editor.locator('[data-omo-decision-question-nav]').screenshot({path: path.join(root, `tmp/decision-block-name-input-${width}.png`)});
            await input.fill('Restaurants pour notre prochaine rencontre');
            const longWidth = (await input.boundingBox()).width;
            assert(longWidth > shortWidth && longWidth <= width * 0.6 + 1, 'The input grows with its content within the mobile limit');
            await input.fill('Oui');
            assert((await input.boundingBox()).width < shortWidth, 'The input shrinks as text is removed');
            assert.equal(await editor.locator('.omo-decision-edit__question-tabs').evaluate(node => getComputedStyle(node).overflowY), 'hidden', 'Editable tabs never show a vertical scrollbar');
            await input.fill('Nom provisoire');
            await input.press('Escape');
            assert.equal(await panels.nth(0).locator('[name="short_title"]').inputValue(), original);
            assert.equal(await editor.evaluate(node => node.commonDrawerHasUnsavedChanges()), false, 'Cancel restores the clean state');
            const titles = ['Choix de l’équipe', 'Évaluation des propositions', 'Décision finale'];
            for (let index = 0; index < titles.length; index++) {
                await tabs.nth(index).dblclick();
                await input.fill(titles[index]);
                await input.press('Enter');
                assert.equal(await tabs.nth(index).innerText(), titles[index]);
                assert.equal(await panels.nth(index).locator('[name="short_title"]').inputValue(), titles[index]);
            }
            assert.equal(await editor.evaluate(node => node.commonDrawerHasUnsavedChanges()), true, 'Renaming participates in unsaved-change protection');
            await editSelected();
            await input.fill('   ');
            await input.press('Enter');
            assert.equal(await tabs.nth(2).innerText(), 'Bloc 3', 'An empty short name restores the numbered label');
            await panels.nth(2).locator('[name="title"]').fill('Quelle proposition souhaitez-vous retenir pour notre prochaine rencontre ?');
            assert.equal(await tabs.nth(2).innerText(), 'Bloc 3', 'The long question text is independent of the tab');
            await editSelected();
            await input.fill(titles[2]);
            await tabs.nth(0).click();
            assert.equal(await input.count(), 0, 'Switching blocks commits the name');
            assert.equal(await pencils.count(), 1);
            await page.evaluate(() => {
                window.savedGroups = null;
                window.fetch = async (url, options) => {
                    window.savedGroups = options.body.getAll('groups[]').map(value => Object.fromEntries(new URLSearchParams(value)));
                    return {ok: true, json: async () => ({status: true})};
                };
            });
            await page.locator('[data-omo-decision-multi-save-for]').click();
            await page.waitForFunction(() => window.savedGroups !== null);
            assert.deepEqual(await page.evaluate(() => window.savedGroups.map(group => group.short_title)), titles, 'All three methods send short names through the usual save');
            assert.deepEqual(await page.evaluate(() => window.savedGroups.map(group => group.title)), [...questions.slice(0, 2), 'Quelle proposition souhaitez-vous retenir pour notre prochaine rencontre ?'], 'Renaming never overwrites question text');
            assert.equal(await editor.evaluate(node => node.commonDrawerHasUnsavedChanges()), false);
            await page.screenshot({path: path.join(root, `tmp/decision-block-names-${width}.png`)});
            await editor.evaluate(node => { node.dataset.canRenameQuestions = '0'; });
            await tabs.nth(1).click();
            await tabs.nth(1).dblclick();
            assert.equal(await input.count(), 0, 'Evaluation-locked editors cannot rename by double click');
            assert.equal(await pencils.count(), 0, 'Evaluation-locked editors have no pencil');
            assert.deepEqual(errors, []);
            await page.close();
        }
        console.log('decision_block_names_browser_test: OK (three methods, keyboard, dirty state, save payload, lock, desktop and mobile)');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
