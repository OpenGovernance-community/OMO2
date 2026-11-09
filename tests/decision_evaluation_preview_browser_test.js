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
            const contents = page.locator('[data-decision-public-contents]');
            const markers = page.locator('[data-decision-public-contents-answered]');
            assert.equal(await contents.locator('[data-decision-public-contents-link]').count(), 3);
            assert.equal(await page.locator('[data-decision-public-contents-answered]:visible').count(), 0);
            if (width === 390) await page.locator('#omo-mobile-nav [data-view=left]').click();
            const heading = await contents.locator('h2').boundingBox();
            const list = await contents.locator('ol').boundingBox();
            assert(list.y - heading.y - heading.height >= 7, 'The contents list has space below its heading');
            assert.equal(await markers.locator('.generic-checkmark[aria-hidden="true"]').count(), 3, 'Completed blocks use the shared checkmark');
            assert.equal(await markers.locator('.generic-visually-hidden').first().textContent(), 'Réponses complètes', 'The icon retains an accessible label');
            await contents.locator('[data-decision-public-contents-link]').nth(2).click();
            assert(await page.locator('body').evaluate(body => body.classList.contains('view-right')));
            await page.waitForFunction(() => {
                const block = document.querySelectorAll('[data-decision-public-group]')[2].getBoundingClientRect();
                const panel = document.getElementById('panel-right').getBoundingClientRect();
                return block.top >= panel.top - 2 && block.top < panel.bottom;
            });
            assert(await markers.evaluateAll(items => items.every(item => item.hidden)), 'Navigating to a block never marks it as answered');
            await page.evaluate(() => { window.previewConnections = []; });
            const forms = [
                ['[data-omo-decision-vote-response-form]', null],
                ['[data-omo-decision-mj-response-form]', '[data-omo-decision-mj-rating-trigger]'],
                ['[data-omo-decision-consent-response-form]', '[data-omo-decision-consent-choice-trigger]'],
            ];
            for (const [index, [selector, trigger]] of forms.entries()) {
                const form = page.locator(selector); const cards = form.locator('[data-omo-decision-one-at-a-time-item]');
                assert.equal(await form.locator('input[type=radio]:checked, input[name="proposal_ids[]"]:checked').count(), 0);
                if (!trigger) {
                    await form.locator('button[type=submit]').click();
                    assert.notEqual(await form.locator('[data-omo-decision-vote-response-feedback]').textContent(), '', 'Keep normal multiple-choice validation in preview');
                }
                if (trigger) await cards.nth(0).locator(trigger).first().click();
                else await cards.nth(0).click();
                assert.equal(await markers.nth(index).evaluate(marker => marker.hidden), !!trigger, 'The marker only appears once the block has enough answers');
                await cards.nth(1).waitFor({state: 'visible'});
                if (trigger) await cards.nth(1).locator(trigger).first().click();
                else await cards.nth(1).click();
                assert.equal(await markers.nth(index).evaluate(marker => marker.hidden), false, 'A fully answered block is marked seen');
                await form.locator('[data-omo-decision-one-at-a-time-previous]').click();
                await cards.nth(0).waitFor({state: 'visible'});
                await form.locator('[data-omo-decision-one-at-a-time-next]').click();
                await cards.nth(1).waitFor({state: 'visible'});
                assert.equal(await form.locator('input[type=radio]:checked, input[name="proposal_ids[]"]:checked').count(), 2);
                await form.locator('button[type=submit]').click();
                await page.getByText('Simulation terminée. Aucune réponse n’a été enregistrée.', {exact: true}).first().waitFor({state: 'visible'});
                if (!trigger) assert.equal(await form.locator('[data-omo-decision-vote-response-feedback]').textContent(), '', 'Clear earlier validation after a valid simulated submit');
                assert.equal(await form.locator('input[type=radio]:checked, input[name="proposal_ids[]"]:checked').count(), 2);
                const selectedAnswers = await form.evaluate(form => Array.from(form.querySelectorAll('input[type=radio]:checked, input[name="proposal_ids[]"]:checked')).map(input => ({name: input.name, value: input.value})));
                await form.evaluate(form => form.reset());
                await page.waitForFunction(index => document.querySelectorAll('[data-decision-public-contents-answered]')[index].hidden, index);
                await form.evaluate((form, answers) => {
                    for (const answer of answers) {
                        const input = Array.from(form.querySelectorAll('input')).find(input => input.name === answer.name && input.value === answer.value);
                        input.checked = true; input.dispatchEvent(new Event('change', {bubbles: true}));
                    }
                }, selectedAnswers);
                assert.equal(await markers.nth(index).evaluate(marker => marker.hidden), false, 'The marker also follows cleared and restored answers');
            }
            await page.waitForTimeout(350);
            assert.deepEqual(await page.evaluate(() => window.previewConnections), [], 'Choices, navigation and final submissions never invoke fetch');
            assert.deepEqual(mutations, [], 'No POST or other mutation leaves the preview window');
            if (width === 390) await page.locator('#omo-mobile-nav [data-view=left]').click();
            await contents.screenshot({path: path.join(root, `tmp/decision-contents-complete-${width}.png`)});
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
