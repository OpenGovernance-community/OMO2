'use strict';
const assert = require('node:assert/strict');
const path = require('node:path');
const {chromium} = require(process.argv[2] || 'playwright');
const root = path.join(__dirname, '..');

(async () => {
    const browser = await chromium.launch({headless: true, channel: 'msedge'});
    try {
        for (const [module, prefix] of [['majority_judgment', 'mj'], ['vote', 'vote'], ['consent', 'consent']]) {
            for (const editable of [true, false]) {
                const page = await browser.newPage();
                const errors = [];
                page.on('pageerror', error => errors.push(error.message));
                const payload = {saveUrl: '/save', responsesEditable: editable,
                    texts: {save: 'Save', update: 'Update', saving: 'Saving', success: 'Saved', error: 'Failed'}};
                await page.setContent(`<section data-decision-public-group><form data-omo-decision-${prefix}-response-form>
                    <div data-omo-decision-one-at-a-time data-omo-decision-one-at-a-time-draft-url="/draft">
                        <div data-omo-decision-one-at-a-time-item><input type="radio" name="scores[1]" value="0" checked></div>
                        <div data-omo-decision-one-at-a-time-item><input type="radio" name="scores[2]" value="6" checked></div>
                        <button type="button" data-omo-decision-one-at-a-time-previous>Previous</button>
                        <div data-omo-decision-one-at-a-time-dots></div>
                        <button type="button" data-omo-decision-one-at-a-time-next>Next</button>
                    </div>
                    <input type="hidden" name="token" value="fixture">
                    <button type="submit" data-omo-decision-${prefix}-response-submit>Save</button>
                    <div data-omo-decision-${prefix}-response-feedback></div>
                    <script type="application/json" data-omo-decision-${prefix}-response-data>${JSON.stringify(payload)}</script>
                </form></section><section data-decision-public-group><input id="other-answer" value="Unsaved answer"></section>`);
                await page.evaluate(() => {
                    window.notices = []; window.requests = []; window.drawers = []; window.savedEvents = 0;
                    window.failSave = false;
                    window.commonNotify = (message, type) => window.notices.push({message, type});
                    window.omoDecisionOpenNestedDrawer = (...args) => window.drawers.push(args);
                    window.commonTopbarOpenDrawer = (...args) => window.drawers.push(args);
                    window.fetch = async (url, options) => {
                        window.requests.push({url, fields: Array.from(options.body.entries())});
                        return {json: async () => window.failSave ? {status: false, message: 'Rejected'}
                            : {status: true, message: 'Saved', redirectUrl: '/participate', drawerTitle: 'Decision'}};
                    };
                    window.addEventListener('omo-decision-response-saved', () => window.savedEvents++);
                    window.originalForm = document.querySelector('form');
                });
                for (const file of ['common/choice/decision-notifications.js', 'common/choice/one-proposal-at-a-time.js',
                    `omo/api/decision/modules/${module}/module.js`]) {
                    await page.addScriptTag({path: path.join(root, file)});
                }
                if (module === 'vote') await page.evaluate(() => window.commonPageScripts['/omo/api/decision/modules/vote/module.js']({}, null));
                else if (module === 'majority_judgment') await page.evaluate(() => window.omoDecisionMajorityJudgmentInit(document));
                else await page.evaluate(() => window.omoDecisionConsentInit(document));
                const submit = page.locator('button[type="submit"]');
                // Cancel a pending automatic draft when the final ballot is submitted.
                await page.evaluate(() => {
                    document.querySelector('input[type="radio"]').dispatchEvent(new Event('change', {bubbles: true}));
                    document.querySelector('form').requestSubmit();
                });
                await page.waitForFunction(() => window.notices.length === 1);
                await page.waitForTimeout(350);
                assert.deepEqual(await page.evaluate(() => window.drawers), [], 'Saving must never open a drawer on the questionnaire');
                assert.equal(await page.evaluate(() => window.originalForm === document.querySelector('form')), true, 'Keep the current questionnaire mounted');
                assert.equal(await page.locator('#other-answer').inputValue(), 'Unsaved answer');
                assert.equal(await submit.textContent(), 'Update');
                assert.equal(await submit.isDisabled(), !editable, 'Respect the response edit policy');
                assert.equal(await page.locator('input[type="radio"]').first().isDisabled(), !editable);
                assert.equal(await page.locator(`[data-omo-decision-${prefix}-response-feedback]`).textContent(), '');
                assert.deepEqual(await page.evaluate(() => window.notices), [{message: 'Saved', type: 'success'}]);
                assert.equal(await page.evaluate(() => window.savedEvents), 1);
                assert.deepEqual(await page.evaluate(() => window.requests.map(request => request.url)), ['/save'], 'Final submission cancels pending drafts');
                if (editable) {
                    await page.locator('input[type="radio"]').first().dispatchEvent('change');
                    await page.waitForTimeout(300);
                    assert.equal(await page.evaluate(() => window.requests.length), 1, 'Submitted ballots never resume draft autosave');
                    await page.evaluate(() => { window.failSave = true; });
                    await submit.click();
                    await page.waitForFunction(() => window.notices.length === 2);
                    assert.deepEqual(await page.evaluate(() => window.notices[1]), {message: 'Rejected', type: 'error'});
                    assert.equal(await submit.isDisabled(), false, 'A failed save can be retried');
                    assert.equal(await page.locator('input[type="radio"]:checked').count(), 2, 'Keep answers after a failure');
                    await page.evaluate(() => {
                        window.failSave = false;
                        document.querySelector('[data-decision-public-group]').removeAttribute('data-decision-public-group');
                    });
                    await submit.click();
                    await page.waitForFunction(() => window.drawers.length === 1);
                    assert.equal(await page.evaluate(() => window.notices.length), 2, 'Internal editors retain their existing drawer navigation');
                }
                assert.deepEqual(errors, []);
                await page.close();
            }
        }
        console.log('decision_response_save_browser_test: OK');
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
