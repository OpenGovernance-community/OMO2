'use strict';
// Generate fixtures with decision_finish_evaluation_test.php first.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require(process.argv[2] || 'playwright');
const root = path.join(__dirname, '..');
(async () => {
    const browser = await chromium.launch({headless: true, channel: 'msedge'});
    try {
        for (const width of [1280, 390]) for (const mode of ['ready', 'empty']) {
            const html = fs.readFileSync(path.join(root, `tmp/decision-finish-${mode}.html`), 'utf8');
            const config = JSON.parse(html.match(/window\.commonPageScripts\["\/omo\/api\/decision\/edit_shared\.js"\]\((\{[^\n]+\}), document\.currentScript/)[1]);
            const page = await browser.newPage({viewport: {width, height: 950}});
            page.setDefaultTimeout(10000);
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.route('**/*', route => route.fulfill({body: ''}));
            await page.goto('https://finish.test/editor');
            await page.setContent('<main id="editor"></main>');
            await page.locator('#editor').evaluate((node, html) => { node.innerHTML = html; }, html);
            await page.addStyleTag({path: path.join(root, 'common/assets/components.css')});
            await page.addStyleTag({path: path.join(root, 'common/choice/decision_cards.css')});
            await page.addScriptTag({path: path.join(root, 'omo/api/decision/edit_shared.js')});
            await page.evaluate(config => {
                window.confirmAllowed = false; window.confirmText = ''; window.notifications = []; window.requests = []; window.opened = [];
                window.confirm = text => { window.confirmText = text; return window.confirmAllowed; };
                window.commonNotify = (message, type) => window.notifications.push({message, type});
                window.omoDecisionOpenNestedDrawer = (title, url) => window.opened.push(url);
                window.fetch = async (url, options) => {
                    window.requests.push({url, data: Object.fromEntries(options.body)});
                    if (!window.requestSucceeds) return {ok: false, json: async () => ({status: false, message: 'Une réponse manque encore.'})};
                    return new Promise(resolve => { window.finishRequest = () => resolve({ok: true, json: async () => ({status: true, message: 'Scrutin clôturé.', redirectUrl: '/editor/results'})}); });
                };
                window.commonPageScripts['/omo/api/decision/edit_shared.js'](config);
            }, config);
            const action = page.locator(mode === 'ready' ? '[data-omo-decision-finish]' : '[data-omo-decision-interrupt]');
            const select = action.locator('..').locator('select[name="status"]');
            assert.equal(await action.count(), 1);
            assert.equal(await select.count(), 1, 'Lifecycle action is directly beside the stage selector');
            assert.equal(await action.getAttribute('type'), 'button', 'Immediate action never submits the editor');
            await action.scrollIntoViewIfNeeded();
            const a = await action.boundingBox(), s = await select.boundingBox();
            assert(a.x + a.width <= width && a.x >= s.x + s.width - 2 && Math.abs(a.y - s.y) < 2, 'The action and selector share a row, including on mobile');
            await action.locator('..').screenshot({path: path.join(root, `tmp/decision-stage-${mode}-${width}.png`)});
            if (mode === 'ready') {
                await action.click();
                assert.equal(await page.evaluate(() => window.requests.length), 0, 'Cancelling preserves the evaluation');
                assert((await page.evaluate(() => window.confirmText)).includes('fin de l’évaluation'));
                await page.evaluate(() => { window.confirmAllowed = true; });
                await action.click();
                await page.waitForFunction(() => window.notifications.length === 1);
                assert.equal(await action.isEnabled(), true, 'A rejected close can be retried');
                assert.deepEqual(await page.evaluate(() => window.notifications[0]), {message: 'Une réponse manque encore.', type: 'error'});
                assert.equal(await page.evaluate(() => window.opened.length), 0);
                await page.evaluate(() => { window.requestSucceeds = true; });
                await action.click();
                assert(await action.isDisabled());
                assert.equal(await action.textContent(), 'Clôture en cours…');
                assert.equal(await page.evaluate(() => window.requests[1].data.decision_action), 'finish_evaluation');
                await page.evaluate(() => window.finishRequest());
                await page.waitForFunction(() => window.opened.length === 1);
                assert.deepEqual(await page.evaluate(() => window.opened), ['/editor/results']);
                assert.deepEqual(await page.evaluate(() => window.notifications[1]), {message: 'Scrutin clôturé.', type: 'success'});
                assert.equal(await page.locator('[data-omo-decision-multi-editor]').evaluate(node => node.commonDrawerHasUnsavedChanges()), false);
            }
            assert.deepEqual(errors, []);
            await page.close();
        }
        console.log('decision_finish_evaluation_browser_test: OK (adjacent stage actions, cancel, rejection, close, desktop and mobile)');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
