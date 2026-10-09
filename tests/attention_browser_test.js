'use strict';
// Run with a Playwright module path as the first argument when it is not installed locally.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require(process.argv[2] || 'playwright');
const root = path.join(__dirname, '..');
const read = file => fs.readFileSync(path.join(root, file), 'utf8');
async function waitFor(check) {
    const deadline = Date.now() + 5000;
    while (!check()) {
        if (Date.now() > deadline) throw new Error('Expected browser request did not arrive.');
        await new Promise(resolve => setTimeout(resolve, 5));
    }
}

(async () => {
    const browser = await chromium.launch({headless: true, channel: 'msedge'});
    try {
        const page = await browser.newPage({ignoreHTTPSErrors: true, viewport: {width: 1200, height: 850}});
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        let loadGate;
        let requestCount = 0;
        let pendingRequest;
        const decisionSignal = {type: 'decision', id: 31, oid: 7, cid: 42, routeToken: 'decision-p31', count: 3,
            message: 'Une decision sur laquelle se prononcer dans le cercle Support. Et 2 autres ailleurs.'};
        const activitySignal = {type: 'activities', id: 63, oid: 7, cid: 48, routeToken: 'activities-d63', count: 4, spaceCount: 2,
            message: 'Une tache recurrente en retard dans le role Support : Verifier les sauvegardes. Et 3 autres dans 2 espaces.'};
        const payload = {signals: [decisionSignal, activitySignal]};
        const decisionMenu = `<div class="menu-primary">
            <div class="menu-item" data-hash="structure"><span class="icon">S</span><span class="label">Structure</span></div>
            <div class="menu-item" data-hash="dashboard"><span class="icon">T</span><span class="label">Tableau de bord</span></div>
            <div class="menu-item omo-attention-target" data-hash="decision"><span class="icon">D</span><span class="label">Decision</span>
                <a class="omo-attention-badge" data-omo-attention-badge="decision" hidden></a>
            </div>
            <div class="menu-item omo-attention-target" data-hash="activities"><span class="icon">R</span><span class="label">Taches recurrentes</span>
                <a class="omo-attention-badge" data-omo-attention-badge="activities" hidden></a>
            </div></div>`;
        await page.route('**/attention-load-gate', route => { loadGate = route; });
        await page.route('**/omo/api/attention.php?*', route => {
            requestCount++;
            pendingRequest = route;
        });
        const bootstrap = `
            window.omoConfig = {oid:7, currentUserId:2};
            window.omoResolveAppUrl = url => url + '&lang=fr';
            window.buildOmoUrl = (oid,cid,hash) => '/omo/o/'+oid+'/c/'+cid+'#'+hash;
            window.navigations = [];
            window.omoNavigate = (...args) => { window.navigations.push(args); history.pushState({},'',window.buildOmoUrl(...args)); };
            window.menuClicks = 0;
            document.querySelector('#sidebar-toggle').addEventListener('click', () => { window.menuClicks++; });
            document.querySelector('[data-view=menu]').addEventListener('click', () => { window.menuClicks++; });
            document.querySelector('#menu_sidebar').addEventListener('click', () => { window.menuClicks++; });
        `;
        await page.route('**/attention-fixture', route => route.fulfill({contentType: 'text/html', body: `
            <!doctype html><meta name="viewport" content="width=device-width, initial-scale=1">
            <style>${read('common/assets/theme.css')}\n${read('common/assets/components.css')}\n${read('omo/assets/css/styles.css')}\n${read('omo/assets/css/attention.css')}</style>
            <body class="view-left"><div class="app">
                <aside class="sidebar" id="sidebar"><div class="sidebar-toggle" id="sidebar-toggle">
                    <span class="icon">&#9776;</span><span class="label">Applications</span>
                </div><div class="menu" id="menu_sidebar"></div></aside><div class="main">Current space</div>
                <div class="mobile-nav" id="omo-mobile-nav">
                    <button data-view="menu" class="nav-btn">Menu</button>
                    <button class="nav-btn">Contexte</button><button class="nav-btn">Applications</button>
                </div></div><img hidden src="/attention-load-gate">
                <script>${bootstrap}</script><script>${read('omo/assets/js/attention.js')}</script>
        `}));
        await page.goto('https://localtest.me/attention-fixture', {waitUntil: 'domcontentloaded'});
        assert.equal(requestCount, 0, 'No calculation starts before the page has loaded.');
        assert.equal(await page.locator('[data-omo-attention-badge]:visible').count(), 0);
        await waitFor(() => loadGate);
        await loadGate.fulfill({status: 204});
        await page.waitForLoadState('load');
        await page.waitForFunction(() => document.readyState === 'complete');
        await waitFor(() => pendingRequest);
        assert(pendingRequest.request().url().includes('oid=7&lang=fr'), 'The request preserves organization and locale.');
        await pendingRequest.fulfill({contentType: 'application/json', body: JSON.stringify(payload)});
        pendingRequest = null;
        // Give the fulfilled background response a turn before inserting the asynchronously loaded menu.
        await page.evaluate(() => new Promise(resolve => setTimeout(resolve, 20)));
        assert.equal(await page.locator('[data-omo-attention-badge]').count(), 0, 'The calculation may finish before the sidebar loads.');
        await page.evaluate(html => { document.querySelector('#menu_sidebar').innerHTML = html; }, decisionMenu);
        const desktop = page.locator('#menu_sidebar [data-hash="decision"] [data-omo-attention-badge]');
        await desktop.waitFor({state: 'visible'});
        assert.equal(await page.locator('#sidebar-toggle [data-omo-attention-badge], .mobile-nav [data-omo-attention-badge]').count(), 0, 'Only the Decision application carries the signal.');
        assert.equal(await desktop.getAttribute('href'), '/omo/o/7/c/42#decision-p31');
        await desktop.hover();
        const tooltip = page.locator('#omo-attention-tooltip');
        await tooltip.waitFor({state: 'visible'});
        assert.equal(await tooltip.textContent(), decisionSignal.message);
        await page.screenshot({path: path.join(root, 'tmp/attention-desktop.png')});
        await desktop.focus();
        assert.equal(await tooltip.isVisible(), true, 'Keyboard focus also reveals the explanation.');
        await page.keyboard.press('Escape');
        assert.equal(await tooltip.isVisible(), false);
        await desktop.click();
        assert.deepEqual(await page.evaluate(() => window.navigations), [[7,42,'decision-p31']]);
        assert.equal(await page.evaluate(() => window.menuClicks), 0, 'Clicking the badge does not toggle Applications.');
        assert.equal(await desktop.isVisible(), true, 'Navigation does not dismiss an unanswered invitation.');
        const activityBadge = page.locator('#menu_sidebar [data-hash="activities"] [data-omo-attention-badge]');
        await activityBadge.waitFor({state: 'visible'});
        assert.equal(await activityBadge.getAttribute('href'), '/omo/o/7/c/48#activities-d63');
        await activityBadge.hover();
        assert.equal(await tooltip.textContent(), activitySignal.message, 'Each application has its own message, total and space count.');
        await activityBadge.click();
        assert.deepEqual((await page.evaluate(() => window.navigations)).at(-1), [7,48,'activities-d63']);
        await page.evaluate(html => { document.querySelector('#menu_sidebar').innerHTML = html; }, decisionMenu);
        await desktop.waitFor({state: 'visible'});
        assert.equal(await desktop.getAttribute('href'), '/omo/o/7/c/42#decision-p31', 'Replacing the sidebar preserves the current signal and its target.');
        await page.setViewportSize({width: 390, height: 850});
        await page.evaluate(() => { document.body.className = 'view-menu'; });
        const mobile = desktop;
        await mobile.waitFor({state: 'visible'});
        await mobile.focus();
        await tooltip.waitFor({state: 'visible'});
        const bounds = await tooltip.boundingBox();
        assert(bounds.x >= 0 && bounds.x + bounds.width <= 390 && bounds.y + bounds.height <= 850, 'The mobile tooltip remains inside the viewport.');
        await page.screenshot({path: path.join(root, 'tmp/attention-mobile.png')});
        await activityBadge.focus();
        assert.equal(await tooltip.textContent(), activitySignal.message);
        await page.screenshot({path: path.join(root, 'tmp/attention-recurring-mobile.png')});
        await mobile.click();
        assert.equal(await page.evaluate(() => window.menuClicks), 0, 'The mobile badge navigates without triggering the application button.');
        await page.evaluate(() => window.dispatchEvent(new CustomEvent('omo-decision-response-saved')));
        await waitFor(() => pendingRequest);
        const requestAfterVote = requestCount;
        await page.evaluate(() => window.dispatchEvent(new CustomEvent('omo-decision-response-saved')));
        assert.equal(requestCount, requestAfterVote, 'Only one calculation may run at a time.');
        await pendingRequest.fulfill({contentType: 'application/json', body: JSON.stringify({signals: [activitySignal]})});
        pendingRequest = null;
        await mobile.waitFor({state: 'hidden'});
        assert.equal(await activityBadge.isVisible(), true, 'Submitting a decision must not clear overdue recurring tasks.');
        await waitFor(() => pendingRequest);
        await pendingRequest.fulfill({contentType: 'application/json', body: JSON.stringify(payload)});
        pendingRequest = null;
        await mobile.waitFor({state: 'visible'});
        await page.evaluate(() => window.dispatchEvent(new CustomEvent('omo-activities-changed')));
        await waitFor(() => pendingRequest);
        await pendingRequest.fulfill({contentType: 'application/json', body: JSON.stringify({signals: [decisionSignal]})});
        pendingRequest = null;
        await activityBadge.waitFor({state: 'hidden'});
        assert.equal(await mobile.isVisible(), true, 'Completing recurring tasks must not clear unanswered decisions.');
        await page.evaluate(() => window.dispatchEvent(new CustomEvent('omo-decision-response-saved')));
        await waitFor(() => pendingRequest);
        await pendingRequest.fulfill({status: 500, contentType: 'application/json', body: '{}'});
        pendingRequest = null;
        assert.equal(await mobile.isVisible(), true, 'A temporary server failure must preserve the last known signal.');
        await page.evaluate(() => window.dispatchEvent(new CustomEvent('omo-decision-response-saved')));
        await waitFor(() => pendingRequest);
        await pendingRequest.fulfill({status: 403});
        await mobile.waitFor({state: 'hidden'});
        assert.deepEqual(errors, []);
        console.log('attention_browser_test: OK');
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
