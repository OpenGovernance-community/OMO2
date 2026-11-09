'use strict';
// Generate the HTML first with decision_evaluation_preview_test.php as www-data in Docker.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require(process.argv[2] || 'playwright');
const root = path.join(__dirname, '..');
const html = fs.readFileSync(path.join(root, 'tmp/decision-evaluation-preview.html'), 'utf8');
(async () => {
    const browser = await chromium.launch({headless: true, channel: 'msedge'});
    try {
        for (const width of [1280, 390]) {
            const page = await browser.newPage({viewport: {width, height: 1000}});
            const errors = []; const mutations = [];
            page.on('pageerror', error => errors.push(error.message));
            page.on('request', request => { if (request.method() !== 'GET') mutations.push(request.url()); });
            await page.addInitScript(() => {
                window.previewConnections = [];
                const originalFetch = window.fetch;
                window.fetch = function (...args) { window.previewConnections.push(String(args[0])); return originalFetch.apply(this, args); };
            });
            await page.route('**/*', route => {
                const url = new URL(route.request().url());
                if (url.hostname === 'preview.test' && url.pathname === '/preview') return route.fulfill({body: html,
                    contentType: 'text/html; charset=utf-8', headers: {'Content-Security-Policy': "form-action 'none'; connect-src 'none'; base-uri 'self'"}});
                const file = path.join(root, url.pathname);
                if (url.hostname === 'preview.test' && fs.existsSync(file) && fs.statSync(file).isFile() && !file.endsWith('.php')) return route.fulfill({path: file});
                if (route.request().resourceType() === 'image') return route.fulfill({contentType: 'image/svg+xml', body: '<svg xmlns="http://www.w3.org/2000/svg" width="1" height="1"/>'});
                return route.fulfill({body: '', contentType: route.request().resourceType() === 'script' ? 'application/javascript' : 'text/plain'});
            });
            await page.goto('https://preview.test/preview');
            await page.waitForFunction(() => typeof window.omoDecisionCompleteEvaluationPreview === 'function');
            await page.waitForFunction(() => Array.from(document.querySelectorAll('[data-omo-decision-one-at-a-time]')).every(root => root.dataset.omoDecisionOneAtATimeReady === '1'));
            assert.equal(await page.locator('[data-omo-decision-one-at-a-time]').count(), 3);
            assert.equal(await page.locator('[data-omo-decision-one-at-a-time-draft-url]').count(), 0);
            await page.evaluate(() => { window.previewConnections = []; });
            const forms = [
                ['[data-omo-decision-vote-response-form]', null],
                ['[data-omo-decision-mj-response-form]', '[data-omo-decision-mj-rating-trigger]'],
                ['[data-omo-decision-consent-response-form]', '[data-omo-decision-consent-choice-trigger]'],
            ];
            for (const [selector, trigger] of forms) {
                const form = page.locator(selector); const cards = form.locator('[data-omo-decision-one-at-a-time-item]');
                assert.equal(await form.locator('input[type=radio]:checked, input[name="proposal_ids[]"]:checked').count(), 0);
                if (!trigger) {
                    await form.locator('button[type=submit]').click();
                    assert.notEqual(await form.locator('[data-omo-decision-vote-response-feedback]').textContent(), '', 'Keep normal multiple-choice validation in preview');
                }
                if (trigger) await cards.nth(0).locator(trigger).first().click();
                else await cards.nth(0).click();
                await cards.nth(1).waitFor({state: 'visible'});
                if (trigger) await cards.nth(1).locator(trigger).first().click();
                else await cards.nth(1).click();
                await form.locator('[data-omo-decision-one-at-a-time-previous]').click();
                await cards.nth(0).waitFor({state: 'visible'});
                await form.locator('[data-omo-decision-one-at-a-time-next]').click();
                await cards.nth(1).waitFor({state: 'visible'});
                assert.equal(await form.locator('input[type=radio]:checked, input[name="proposal_ids[]"]:checked').count(), 2);
                await form.locator('button[type=submit]').click();
                await page.getByText('Simulation terminée. Aucune réponse n’a été enregistrée.', {exact: true}).first().waitFor({state: 'visible'});
                if (!trigger) assert.equal(await form.locator('[data-omo-decision-vote-response-feedback]').textContent(), '', 'Clear earlier validation after a valid simulated submit');
                assert.equal(await form.locator('input[type=radio]:checked, input[name="proposal_ids[]"]:checked').count(), 2);
            }
            await page.waitForTimeout(350);
            assert.deepEqual(await page.evaluate(() => window.previewConnections), [], 'Choices, navigation and final submissions never invoke fetch');
            assert.deepEqual(mutations, [], 'No POST or other mutation leaves the preview window');
            if (width === 390) {
                await page.locator('#omo-mobile-nav [data-view=left]').click();
                assert(await page.locator('body').evaluate(body => body.classList.contains('view-left')));
                await page.locator('#omo-mobile-nav [data-view=right]').click();
            }
            await page.evaluate(() => document.querySelectorAll('.common-notification').forEach(item => window.commonDismissNotification(item.id)));
            await page.waitForTimeout(250);
            await page.getByRole('heading', {name: 'Prévisualisation de l’évaluation', exact: true}).scrollIntoViewIfNeeded();
            await page.screenshot({path: path.join(root, `tmp/decision-evaluation-preview-${width}.png`)});
            assert.deepEqual(errors, []);
            await page.close();
        }
        console.log('decision_evaluation_preview_browser_test: OK (three methods, navigation, local submissions, desktop and mobile)');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
