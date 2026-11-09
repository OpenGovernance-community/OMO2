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
        for (const width of [1280, 390]) {
            const page = await browser.newPage({viewport: {width, height: 1000}});
            page.setDefaultTimeout(10000);
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.route('**/*', route => route.fulfill({body: ''}));
            await page.goto('https://settings.test/editor');
            await page.setContent('<main id="editor"></main><div id="commonTopbarModalBody" hidden></div>');
            await page.locator('#editor').evaluate((node, html) => {
                node.innerHTML = html;
                const editor = node.querySelector('[data-omo-decision-multi-editor]');
                Array.from(editor.querySelectorAll('[data-omo-decision-question-panel]')).forEach((source, index) => {
                    if (index === 2) {
                        const fragment = source.cloneNode(true);
                        fragment.removeAttribute('data-omo-decision-question-panel');
                        fragment.setAttribute('data-omo-decision-question-fragment', '');
                        fragment.hidden = false;
                        window.newConsentFragment = fragment.outerHTML;
                    }
                    const copy = source.cloneNode(true);
                    copy.dataset.questionKey = 'destination-' + index;
                    copy.hidden = true;
                    copy.querySelector('[name="gid"]').value = String(90000 + index);
                    copy.querySelector('[name="short_title"]').value = 'Destination ' + index;
                    copy.querySelector('[name="title"]').value = 'Question indépendante ' + index;
                    editor.querySelector('[data-omo-decision-question-panels]').appendChild(copy);
                    const link = document.createElement('a');
                    link.href = '#'; link.className = 'omo-decision-edit__question-tab';
                    link.dataset.omoDecisionQuestionLink = ''; link.dataset.questionKey = copy.dataset.questionKey;
                    link.textContent = 'Destination ' + index;
                    editor.querySelector('.omo-decision-edit__question-tabs').appendChild(link);
                    const set = (name, value) => { const field = source.querySelector('[name="' + name + '"]'); if (field) field.value = value; };
                    for (const name of ['allow_anonymous_votes', 'allow_consultation_proposals', 'allow_proposal_discussions', 'randomize_proposal_order', 'vote_weight_enabled']) set(name, '1');
                    set('is_anonymous', ''); set('one_proposal_at_a_time', '');
                    set('proposal_content_description', ''); set('proposal_content_url', '1'); set('proposal_content_date', '1');
                    set('vote_weight_question', 'Quel est votre poids ?');
                    set('vote_weight_options_json', JSON.stringify([{weight: 1, label: 'Normal'}, {weight: 2, label: 'Double'}]));
                    set('mention_customization_enabled', '1'); set('mention_labels[6]', 'Formidable'); set('mention_active[0]', '');
                    set('choice_mode', 'multiple'); set('max_choices', '2');
                });
            }, html);
            await page.addStyleTag({path: path.join(root, 'common/assets/components.css')});
            await page.addStyleTag({path: path.join(root, 'common/choice/decision_cards.css')});
            await page.addScriptTag({path: path.join(root, 'omo/api/decision/modules/vote-weight-editor.js')});
            await page.evaluate(() => {
                window.notifications = []; window.refreshes = [];
                window.commonNotify = (message, type) => window.notifications.push({message, type});
                window.omoProposalHtml = {refreshDecisionProposalCards: (list, content) => window.refreshes.push({...content})};
                window.commonTopbarOpenModal = (title, html) => {
                    const modal = document.getElementById('commonTopbarModalBody'); modal.innerHTML = html; modal.hidden = false;
                };
                window.commonTopbarCloseModal = () => { document.getElementById('commonTopbarModalBody').hidden = true; };
            });
            for (const file of ['omo/api/decision/modules/vote/module.js', 'omo/api/decision/modules/majority_judgment/module.js', 'omo/api/decision/modules/consent/module.js', 'common/choice/question-settings-copy.js', 'omo/api/decision/edit_shared.js']) {
                await page.addScriptTag({path: path.join(root, file)});
            }
            await page.evaluate(config => {
                window.commonPageScripts['/omo/api/decision/modules/vote/module.js']({isConsultationOnly: false});
                window.commonPageScripts['/omo/api/decision/edit_shared.js'](config);
            }, config);
            const editor = page.locator('[data-omo-decision-multi-editor]');
            assert.equal(await editor.evaluate(node => node.commonDrawerHasUnsavedChanges()), false);
            for (const [index, prefix] of ['vote', 'mj', 'consent'].entries()) {
                const target = editor.locator('[data-omo-decision-question-panel][data-question-key="destination-' + index + '"]');
                const preserved = await target.evaluate(panel => Array.from(new FormData(panel.querySelector('form'))).filter(([name]) => ['title', 'short_title', 'gid', 'proposals[]', 'proposal_ids[]', 'proposal_descriptions[]', 'proposal_info_urls[]'].includes(name)));
                const general = await editor.locator('[data-omo-decision-process-form]').evaluate(form => Array.from(new FormData(form)));
                await editor.locator('[data-omo-decision-question-link][data-question-key="destination-' + index + '"]').click();
                const toggle = target.locator('[data-decision-settings-copy-toggle]');
                await toggle.click();
                const items = target.getByRole('menuitem');
                assert.equal(await items.count(), 1, 'Only the other block of this voting method is offered');
                assert.equal(await toggle.getAttribute('aria-expanded'), 'true');
                if (index === 0) {
                    await items.first().press('Escape');
                    assert.equal(await toggle.getAttribute('aria-expanded'), 'false');
                    await toggle.press('ArrowDown');
                }
                await items.first().click();
                assert.equal(await toggle.getAttribute('aria-expanded'), 'false');
                const values = await target.locator('form').evaluate(form => Object.fromEntries(new FormData(form)));
                assert.equal(values.vote_weight_question, 'Quel est votre poids ?');
                assert.equal(values.vote_weight_enabled, '1');
                assert.deepEqual(JSON.parse(values.vote_weight_options_json), [{weight: 1, label: 'Normal'}, {weight: 2, label: 'Double'}]);
                assert.equal(values.randomize_proposal_order, '1');
                assert.equal(values.one_proposal_at_a_time, '');
                assert.equal(values.proposal_content_date, '1');
                assert.equal(values.proposal_content_description, '');
                if (prefix === 'mj') {
                    assert.equal(values['mention_labels[6]'], 'Formidable');
                    assert.equal(values['mention_active[0]'], '');
                    assert((await target.locator('[data-omo-decision-mj-scale-summary]').textContent()).includes('Formidable'));
                }
                if (prefix === 'vote') { assert.equal(values.choice_mode, 'multiple'); assert.equal(values.max_choices, '2'); }
                assert.deepEqual(await target.evaluate(panel => Array.from(new FormData(panel.querySelector('form'))).filter(([name]) => ['title', 'short_title', 'gid', 'proposals[]', 'proposal_ids[]', 'proposal_descriptions[]', 'proposal_info_urls[]'].includes(name))), preserved, 'Question contents and proposals are preserved');
                assert.deepEqual(await editor.locator('[data-omo-decision-process-form]').evaluate(form => Array.from(new FormData(form))), general, 'Decision-level settings are preserved');
                await target.locator('.omo-decision-settings-button').click();
                const modal = page.locator('#commonTopbarModalBody');
                assert(await modal.locator('[data-omo-decision-' + prefix + '-popup-random-order]').isChecked(), 'Configure opens the copied values');
                assert(await modal.locator('[data-omo-decision-proposal-content-popup-date]').isChecked());
                await modal.locator('[data-omo-decision-' + prefix + '-popup-cancel]').click();
            }
            assert.equal(await editor.evaluate(node => node.commonDrawerHasUnsavedChanges()), true, 'Copying marks the editor as unsaved');
            assert.equal(await page.evaluate(() => window.notifications.length), 3);
            assert.equal(await page.evaluate(() => window.refreshes.length), 3, 'Proposal presentation refreshes immediately');
            const target = editor.locator('[data-omo-decision-question-panel][data-question-key="destination-2"]');
            await target.locator('[data-decision-settings-copy-toggle]').click();
            const buttonBox = await target.locator('.generic-menu--split').boundingBox();
            const menuBox = await target.getByRole('menu').boundingBox();
            assert(menuBox.x >= 0 && menuBox.x + menuBox.width <= width, 'The copy menu fits on mobile');
            await page.screenshot({path: path.join(root, `tmp/decision-settings-copy-${width}.png`), clip: {
                x: menuBox.x, y: buttonBox.y, width: menuBox.width, height: menuBox.y + menuBox.height - buttonBox.y
            }});
            await page.locator('h3').first().click();
            assert.equal(await target.locator('[data-decision-settings-copy-toggle]').getAttribute('aria-expanded'), 'false', 'Clicking outside closes the menu');
            await editor.evaluate(node => {
                const source = Array.from(node.querySelectorAll('[data-omo-decision-question-panel]')).find(panel => panel.dataset.questionMethod === 'consent' && panel.dataset.questionKey !== 'destination-2');
                node.querySelector('[data-omo-decision-question-link][data-question-key="' + source.dataset.questionKey + '"]').remove();
                source.remove();
                window.commonExecuteFragmentScripts = async container => window.omoDecisionConsentInit(container);
                window.fetch = async () => ({ok: true, text: async () => window.newConsentFragment});
            });
            await page.evaluate(config => window.commonDecisionInitQuestionSettingsCopy(document.querySelector('[data-omo-decision-multi-editor]'), config.settingsCopy), config);
            assert(await target.locator('[data-decision-settings-copy-toggle]').isDisabled(), 'Without another compatible block the copy action is disabled');
            await editor.locator('[data-omo-decision-question-add-toggle]').click();
            await editor.locator('[data-omo-decision-question-add-method="consent"]').click();
            await page.waitForFunction(() => document.querySelector('[data-question-key="destination-2"] [data-decision-settings-copy-toggle]').disabled === false);
            const fresh = editor.locator('[data-omo-decision-question-panel][data-question-key^="new-"]');
            await fresh.locator('[data-decision-settings-copy-toggle]').click();
            assert.equal(await fresh.getByRole('menuitem').count(), 1, 'Unsaved added blocks can copy settings immediately');
            assert((await fresh.getByRole('menuitem').textContent()).includes('Destination 2'));
            await fresh.getByRole('menuitem').click();
            assert.equal(await fresh.locator('form').evaluate(form => new FormData(form).get('vote_weight_question')), 'Quel est votre poids ?');
            await editor.evaluate(node => {
                node.querySelectorAll('form').forEach(form => { if (form.commonDecisionQuestionSettings) form.commonDecisionQuestionSettings.editable = false; });
            });
            await page.evaluate(config => window.commonDecisionInitQuestionSettingsCopy(document.querySelector('[data-omo-decision-multi-editor]'), config.settingsCopy), config);
            assert.equal(await editor.locator('[data-decision-settings-copy-toggle]:enabled').count(), 0, 'Locked questions cannot copy settings');
            assert.deepEqual(errors, []);
            await page.close();
        }
        console.log('decision_settings_copy_browser_test: OK (three methods, mentions, weights, preserved content, popup, lock, desktop and mobile)');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
