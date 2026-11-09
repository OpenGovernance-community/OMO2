'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {execFileSync} = require('node:child_process');
const {chromium} = require(process.argv[2] || 'playwright');
const root = path.join(__dirname, '..');
const source = fs.readFileSync(path.join(root, 'omo/api/decision/index.js'), 'utf8');
const documents = fs.readFileSync(path.join(root, 'omo/api/documents/list.js'), 'utf8');
const fixtures = JSON.parse(execFileSync('docker', ['compose', 'exec', '-T', 'app', 'php', 'tests/object_visibility_capsule_test.php', '--fixtures'], {cwd: root, encoding: 'utf8'}));
function extract(text, start, end) {
    const first = text.indexOf(start), last = text.indexOf(end, first);
    assert(first >= 0 && last > first, 'Find renderer ' + start);
    return text.slice(first, last);
}
(async () => {
    const browser = await chromium.launch({headless: true, channel: 'msedge'});
    try {
        const page = await browser.newPage({viewport: {width: 1280, height: 900}});
        const errors = []; page.on('pageerror', error => errors.push(error.message));
        await page.route('https://capsules.test/**', route => {
            const file = path.join(root, new URL(route.request().url()).pathname);
            return fs.existsSync(file) && fs.statSync(file).isFile()
                ? route.fulfill({path: file}) : route.fulfill({body: '<!doctype html><body></body>'});
        });
        await page.goto('https://capsules.test/');
        await page.setContent('<main class="omo-decisions"><div id="cards" class="omo-decisions__card-list"></div><div id="compact" class="omo-decisions__list generic-file-list"></div></main><div id="documents"></div>');
        for (const file of ['common/assets/components.css', 'common/choice/decision_cards.css', 'omo/api/decision/index.css', 'omo/api/documents/list.css']) {
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
            function appendDetailedActionMenuButton() {}
            function buildMetaItem() { return ''; }
            ${extract(source, 'function buildDecisionVisibilityIconHtml(', 'function buildCompactMetaLine(')}
            ${extract(source, 'function buildParticipationIndicatorHtml(', 'function renderDetailedList(')}
            ${extract(source, 'function renderCard(', 'function buildStateCardHtml(')}
            const fixtures = ${JSON.stringify(fixtures)};
            const item = {id: 1, title: 'Scrutin avec lecture et édition', statusLabel: 'En évaluation', visibilityCapsuleHtml: fixtures.decision};
            document.querySelector('#cards').appendChild(renderCard(item));
            document.querySelector('#compact').appendChild(renderCompactRow(item));
            const state = {density: 'detail'};
            ${extract(documents, 'const createVisibilityCapsule = function', 'const childrenByParentId')}
            document.querySelector('#documents').appendChild(createVisibilityCapsule({visibilityCapsuleHtml: fixtures.document}));
            state.density = 'compact';
            document.querySelector('#documents').appendChild(createVisibilityCapsule({visibilityCapsuleHtml: fixtures.document}));
        `});
        await page.addStyleTag({content: '.omo-decisions { min-height: 0; height: auto; } #documents { display: flex; align-items: center; gap: 12px; margin-top: 16px; }'});
        await page.locator('img').evaluateAll(images => images.forEach(image => { image.loading = 'eager'; }));
        await page.waitForFunction(() => Array.from(document.images).every(image => image.complete && image.naturalWidth > 0));
        for (const width of [1280, 390]) {
            await page.setViewportSize({width, height: 900});
            const capsules = page.locator('.generic-visibility-capsule'); assert.equal(await capsules.count(), 4);
            for (const capsule of await capsules.all()) {
                assert.equal(await capsule.locator('img').count(), 2);
                assert((await capsule.locator('img').nth(0).getAttribute('src')).endsWith('/organization.png'));
                assert((await capsule.locator('img').nth(1).getAttribute('src')).endsWith('/me.png'));
                assert.equal(await capsule.getAttribute('title'), 'Voir: Organisation <test> | Éditer: Propriétaire "test"');
                assert.equal(await capsule.getAttribute('aria-label'), await capsule.getAttribute('title'));
                const metrics = await capsule.evaluate(el => { const b = el.getBoundingClientRect(); return {width: b.width, height: b.height}; });
                assert(metrics.width > 40 && metrics.height >= 22 && metrics.width < width);
            }
            const metrics = await capsules.evaluateAll(els => els.map(el => { const s = getComputedStyle(el); return [s.backgroundColor, s.boxShadow, s.borderRadius, el.getBoundingClientRect().height]; }));
            assert.deepEqual(metrics[0], metrics[2], 'Detailed decisions and documents use exactly the same capsule');
            assert.deepEqual(metrics[1], metrics[3], 'Compact decisions and documents use exactly the same capsule');
            await page.screenshot({path: path.join(root, `tmp/decision-capsules-${width}.png`)});
        }
        assert.deepEqual(errors, []);
        console.log('decision_visibility_capsule_browser_test: OK');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
