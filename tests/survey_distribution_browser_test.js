'use strict';
// Generate the real report with survey_distribution_test.php first.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require(process.argv[2] || 'playwright');
const root = path.join(__dirname, '..');
const html = fs.readFileSync(path.join(root, 'tmp/survey-distribution-report.html'), 'utf8');
(async () => {
    const browser = await chromium.launch({headless: true, channel: 'msedge'});
    try {
        for (const width of [1280, 390]) {
            const page = await browser.newPage({viewport: {width, height: 900}});
            const errors = []; const writes = [];
            page.on('pageerror', error => errors.push(error.message));
            page.on('request', request => { if (request.method() !== 'GET') writes.push(request.url()); });
            await page.route('**/*', route => {
                const url = new URL(route.request().url());
                if (url.pathname === '/report') return route.fulfill({body: html, contentType: 'text/html; charset=utf-8'});
                const file = path.join(root, url.pathname);
                return fs.existsSync(file) && fs.statSync(file).isFile() && !file.endsWith('.php')
                    ? route.fulfill({path: file}) : route.fulfill({body: ''});
            });
            await page.goto('https://survey.test/report');
            const cards = page.locator('#surveyPublicList .survey-result-card');
            assert.equal(await cards.count(), 10);
            const triggers = cards.first().locator('[data-survey-distribution]');
            assert.equal(await triggers.count(), 3, 'The three colored convergence capsules are buttons');
            assert.equal(await cards.first().locator('button.survey-result-card__agreement-signal').count(), 2);
            assert.equal(await cards.first().locator('button.survey-result-card__affinity-signal').count(), 1);
            assert.equal(await cards.first().locator('.survey-result-card__affinity-label[data-survey-distribution], .survey-result-card__score[data-survey-distribution]').count(), 0, 'Labels and numeric scores are plain text');
            assert((await triggers.first().getAttribute('title')).includes('Échantillon'), 'Capsules preserve their existing statistical tooltips');
            const dialog = page.getByRole('dialog');
            let firstContent;
            for (let index = 0; index < 3; index++) {
                if (index === 1) { await triggers.nth(index).focus(); await page.keyboard.press('Enter'); }
                else await triggers.nth(index).click();
                await dialog.waitFor({state: 'visible'});
                assert.equal(await page.locator('dialog').count(), 1, 'All three entry points use the same popup');
                const content = await dialog.locator('[data-survey-distribution-panels]').innerHTML();
                if (index === 0) firstContent = content;
                else assert.equal(content, firstContent, 'Every entry point shows all three distributions together');
                assert.equal(await dialog.locator('[data-distribution-dimension]').count(), 3);
                assert.equal(await dialog.locator('.survey-distribution__level').count(), 15);
                assert.equal(await dialog.locator('[data-survey-distribution-sample]').textContent(), '8 évaluations complètes');
                for (const [dimension, status, widths] of [
                    ['today', 'Alignement', ['0%', '0%', '100%', '0%', '0%']],
                    ['tomorrow', 'Dispersion', ['12.5%', '12.5%', '50%', '12.5%', '12.5%']],
                    ['affinity', 'Polarisation', ['50%', '0%', '0%', '0%', '50%']]
                ]) {
                    const panel = dialog.locator('[data-distribution-dimension="' + dimension + '"]');
                    assert.equal(await panel.locator('.survey-distribution__status strong').textContent(), status);
                    assert.deepEqual(await panel.locator('.survey-distribution__bar').evaluateAll(bars => bars.map(bar => bar.style.getPropertyValue('--param-distribution-share'))), widths);
                    assert.equal(await panel.locator('.survey-distribution__meaning').count(), 5);
                    const tracks = await panel.locator('.survey-distribution__track').evaluateAll(nodes => nodes.map(node => node.getBoundingClientRect().width));
                    assert(tracks.every(value => Math.abs(value - tracks[0]) < 1), 'Each rating level uses the same physical scale');
                }
                const bounds = await dialog.boundingBox();
                assert(bounds.x >= 0 && bounds.x + bounds.width <= width, 'The popup fits the screen');
                assert(await dialog.evaluate(node => node.scrollWidth <= node.clientWidth + 1), 'No horizontal overflow');
                if (index === 0) await page.screenshot({path: path.join(root, `tmp/survey-distribution-${width}.png`)});
                if (index === 1) await page.keyboard.press('Escape');
                else await dialog.getByRole('button', {name: 'Fermer', exact: true}).click();
                await dialog.waitFor({state: 'hidden'});
                assert(await triggers.nth(index).evaluate(node => document.activeElement === node), 'Closing restores keyboard focus');
            }
            await cards.nth(1).locator('[data-survey-distribution]').first().click();
            assert((await dialog.locator('h2').textContent()).startsWith('2. '), 'Switching principle updates the shared popup');
            assert.equal(await dialog.locator('[data-distribution-dimension="today"] .survey-distribution__count').nth(1).textContent(), '8 · 100 %');
            await page.mouse.click(2, 2);
            await dialog.waitFor({state: 'hidden'});
            await page.evaluate(() => {
                const question = window.SURVEY_PUBLIC_RESULT[0];
                question.responseCount = 2;
                for (const dimension of ['today', 'tomorrow', 'affinity']) {
                    question[dimension + 'Agreement'] = 'insufficient';
                    question.distributions[dimension] = [1, 0, 0, 0, 1];
                }
            });
            await triggers.first().click();
            assert.equal(await dialog.locator('.survey-distribution__status strong').first().textContent(), 'Données insuffisantes');
            assert.equal(await dialog.locator('[data-survey-distribution-sample]').textContent(), '2 évaluations complètes');
            await dialog.getByRole('button', {name: 'Fermer', exact: true}).click();
            assert.deepEqual(errors, []);
            assert.deepEqual(writes, [], 'Opening charts sends no data or votes');
            // The shared renderer still leaves individual survey reports non-interactive.
            await page.evaluate(() => { window.SURVEY_PUBLIC_OPTIONS = {}; });
            await page.addScriptTag({path: path.join(root, 'survey/public.js')});
            assert.equal(await page.locator('[data-survey-distribution]').count(), 0);
            assert.equal(await cards.count(), 10);
            await page.close();
        }
        console.log('survey_distribution_browser_test: OK (three entry points, same popup, actual counts, statuses, scales, keyboard, mobile, individual report)');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
