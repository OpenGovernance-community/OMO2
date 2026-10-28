'use strict';

// Pass a Playwright module path as the first argument if it is not installed locally.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require(process.argv[2] || 'playwright');
const root = path.join(__dirname, '..');
const source = fs.readFileSync(path.join(root, 'omo/api/decision/index.js'), 'utf8');
function extract(start, end) {
    const first = source.indexOf(start);
    const last = source.indexOf(end, first);
    assert(first >= 0 && last > first, 'Cannot locate ' + start);
    return source.slice(first, last);
}

(async () => {
    const browser = await chromium.launch({headless: true, channel: 'msedge'});
    try {
        const page = await browser.newPage({viewport: {width: 1200, height: 850}});
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.setContent('<main class="omo-decisions"><div id="cards" class="omo-decisions__card-list"></div><div id="compact" class="omo-decisions__list generic-file-list"></div></main>');
        for (const file of ['common/assets/components.css', 'common/choice/decision_cards.css', 'omo/api/decision/index.css']) {
            await page.addStyleTag({path: path.join(root, file)});
        }
        await page.addScriptTag({path: path.join(root, 'common/choice/decision_cards.js')});
        await page.addScriptTag({content: `
            const payload = {text: {}};
            const escapeHtml = window.commonChoiceDecisionCards.escapeHtml;
            function resolveDecisionAutoOpenAction() { return null; }
            function buildCompactActionMenu() { return null; }
            function buildCompactAvatar() { return ''; }
            function buildCompactMetaLine() { return ''; }
            function buildDecisionVisibilityIconHtml() { return ''; }
            function appendDetailedActionMenuButton() {}
            function buildMetaItem() { return ''; }
            ${extract('function buildParticipationIndicatorHtml(', 'function renderDetailedList(')}
            ${extract('function renderCard(', 'function buildStateCardHtml(')}
            window.renderFixtures = function () {
                ['', 'pending', 'elaboration', 'submitted'].forEach(function (state, id) {
                    const item = {id, title: state || 'Without invitation', statusLabel: state === 'elaboration' ? 'En elaboration' : 'En evaluation',
                        participationIndicator: state, participationIndicatorLabel: 'Participation "' + state + '" <test>'};
                    document.querySelector('#cards').appendChild(renderCard(item));
                    document.querySelector('#compact').appendChild(renderCompactRow(item));
                });
            };
            window.renderFixtures();
        `});
        for (const container of ['#cards', '#compact']) {
            assert.equal(await page.locator(container + ' .choice-participation-indicator').count(), 3);
            for (const [state, color] of [['pending', 'rgb(239, 36, 36)'], ['elaboration', 'rgb(234, 179, 8)'], ['submitted', 'rgb(34, 164, 71)']]) {
                const dot = page.locator(container + ' .choice-participation-indicator--' + state);
                const metrics = await dot.evaluate(element => {
                    const style = getComputedStyle(element);
                    const bounds = element.getBoundingClientRect();
                    const status = element.parentElement.getBoundingClientRect();
                    return {color: style.backgroundColor, animation: style.animationName, width: bounds.width,
                        inside: bounds.left >= status.left && bounds.right <= status.right,
                        parent: element.parentElement.className};
                });
                assert.equal(metrics.color, color);
                assert.equal(metrics.animation, state === 'submitted' ? 'none' : 'choice-participation-pulse');
                assert(metrics.width > 0 && metrics.width <= 14 && metrics.inside, 'Dot must fit inside the status capsule.');
                assert(metrics.parent.endsWith('status'), 'Dot must appear alongside the status.');
                assert.equal(await dot.getAttribute('title'), 'Participation "' + state + '" <test>');
                assert.equal(await dot.getAttribute('aria-label'), await dot.getAttribute('title'));
            }
        }
        await page.emulateMedia({reducedMotion: 'reduce'});
        assert(await page.locator('.choice-participation-indicator').evaluateAll(dots => dots.every(dot => getComputedStyle(dot).animationName === 'none')));
        await page.emulateMedia({reducedMotion: 'no-preference'});
        await page.setViewportSize({width: 390, height: 844});
        assert(await page.locator('.choice-participation-indicator').evaluateAll(dots => dots.every(dot => dot.getBoundingClientRect().width > 0)));
        await page.evaluate(() => {
            window.commonChoiceDecisionCards = null;
            const card = renderCard({id: 10, title: 'Fallback', statusLabel: 'En evaluation', participationIndicator: 'submitted', participationIndicatorLabel: 'Envoye'});
            document.querySelector('#cards').appendChild(card);
        });
        assert.equal(await page.locator('#cards .choice-participation-indicator--submitted').count(), 2);
        assert.deepEqual(errors, []);
        console.log('decision_participation_indicator_browser_test: OK');
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
