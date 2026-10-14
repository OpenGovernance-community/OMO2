'use strict';
// Pass a Playwright module path when it is not installed locally.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require(process.argv[2] || 'playwright');
const read = file => fs.readFileSync(path.join(__dirname, '..', file), 'utf8');
const source = read('omo/assets/js/app.js');
function extract(start, end) {
    const first = source.indexOf(start);
    const last = source.indexOf(end, first);
    assert(first >= 0 && last > first, 'Cannot locate ' + start);
    return source.slice(first, last);
}
const router = extract('function handleRoute()', 'function activateMenu(')
    + extract('function omoDispatchSpecialDrawerRouteChange(', 'function omoReplayLoadedDocumentsRoute(')
    + extract('function omoHandlePolicyRuleLink(', 'window.omoSetPopupHashState =');
const card = id => `<article data-policy-rule-card data-policy-rule-id="${id}" data-generic-accordion class="is-collapsed">
    <button data-generic-accordion-toggle aria-expanded="false"><span data-policy-search-text>Rule ${id}</span></button>
    <div class="generic-accordion__content"><details class="omo-policy__rule-details"><summary>Details</summary>Content</details></div>
</article>`;

(async () => {
    const browser = await chromium.launch({headless: true, channel: 'msedge'});
    try {
        const page = await browser.newPage();
        page.setDefaultTimeout(5000);
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.route('**/policy-fixture', route => route.fulfill({contentType: 'text/html', body: `<!doctype html>
            <style>.is-filter-hidden {display:none} article {margin-top:700px} .is-collapsed .generic-accordion__content {display:none}</style>
            <a id="first" href="#policy-r1368">First</a><a id="second" href="#policy-r1369">Second</a>
            <a id="missing" href="#policy-r1370">Missing</a>
            <div id="drawer_policy" class="open"><div id="omo-policy-root" data-policy-oid="57" data-policy-cid="2512"
                data-policy-index-url="/omo/api/policy/index.php?oid=57&cid=2512" data-policy-scope="local">
                <input data-policy-quick-search><div data-policy-rule-group>${card(1368)}${card(1369)}</div>
                <div data-policy-search-empty class="is-filter-hidden">Empty</div>
                <div data-policy-drawer hidden><div data-policy-drawer-body></div></div>
            </div></div>
            <script>
                var currentState = {oid:57, cid:2512, hash:'policy', routeToken:'policy', popupToken:null};
                history.replaceState({}, '', '#policy');
                window.parentOperations = [];
                window.refreshUrls = [];
                function parseUrl() {return {oid:57, cid:2512, hash:location.hash.slice(1)};}
                function omoParseHashState(hash) {return {routeToken:hash, popupToken:null};}
                function omoNormalizeHashToken(token) {return token || null;}
                function omoGetMenuHashForRouteToken(token) {return token && token.split('-')[0];}
                function omoConfirmDiscardChanges() {return true;}
                function omoConfirmRouteChange() {return true;}
                function getSidebarMenuConfig() {return {drawer:'drawer_policy', resolvedUrl:'/omo/api/policy/index.php'};}
                function omoNormalizeDrawerId(id) {return id;}
                function omoNormalizeDrawerForcedScope() {return '';}
                function openDrawer(id, url, options) {
                    window.parentOperations.push('open');
                    omoRestoreCachedDrawerRoute(options.routeToken, currentState.routeToken);
                }
                function closeAllDrawers() {throw new Error('Panel must remain open');}
                function resetDrawers() {throw new Error('Panel must not be reset');}
                function omoStoreDrawerContentRoute(id, token) {window.storedRoute = token;}
                function omoEnsurePopupBootstrapState() {}
                function omoResetRememberedDrawerRoutes() {}
                function omoRememberDrawerRoute() {}
                function updateActiveMenu() {}
                function omoEnsureMainRightPanelCurrent() {}
                function omoClosePopupModalFromRoute() {}
                function omoClearPendingDrawerRouteOptions() {}
                function omoSetDrawerHashState(state) {
                    history.pushState({}, '', '#' + state.routeToken);
                    handleRoute();
                }
                window.omoReplaceFetchedPanelRoot = options => {
                    window.refreshUrls.push(options.url);
                    return Promise.resolve();
                };
                ${router}
                window.addEventListener('popstate', handleRoute);
            </script>
            <script>${read('common/assets/components.js')}</script>
            <script>${read('omo/api/policy/index.js')}</script>
        `}));
        await page.goto('https://localtest.me/policy-fixture');
        const first = page.locator('[data-policy-rule-card][data-policy-rule-id="1368"]');
        const second = page.locator('[data-policy-rule-card][data-policy-rule-id="1369"]');
        async function assertFocused(card) {
            assert.equal(await card.evaluate(el => el.classList.contains('is-collapsed')), false);
            assert.equal(await card.locator('details').evaluate(el => el.open), true);
            await page.waitForFunction(id => document.activeElement.closest('[data-policy-rule-card]')?.dataset.policyRuleId === id,
                await card.getAttribute('data-policy-rule-id'));
            assert.equal(await card.locator('button').evaluate(el => el === document.activeElement), true);
            assert.equal(await card.locator('button').getAttribute('aria-expanded'), 'true');
        }
        // Navigation inside an already open drawer must avoid toggling the parent.
        await page.locator('#first').click();
        await assertFocused(first);
        assert.deepEqual(await page.evaluate(() => parentOperations), []);
        assert.equal(await page.evaluate(() => storedRoute), 'policy-r1368');
        // Another rule, then Back, should both focus the requested card.
        await page.locator('#second').click();
        await assertFocused(second);
        await page.goBack();
        await assertFocused(first);
        // Repeat the identical link after collapse and a search hiding the target.
        await first.locator('button').click();
        await page.locator('[data-policy-quick-search]').fill('unmatched');
        await page.locator('#first').click();
        await assertFocused(first);
        assert.equal(await page.locator('[data-policy-quick-search]').inputValue(), '');
        assert.equal(await first.isVisible(), true);
        assert(await first.evaluate(el => {
            const rect = el.getBoundingClientRect();
            return rect.top >= 0 && rect.bottom <= innerHeight;
        }), 'Target must be scrolled into the viewport');
        // Cached panels replay the target even when an editor was left open.
        await page.evaluate(() => {
            const drawer = document.querySelector('[data-policy-drawer]');
            drawer.hidden = false;
            drawer.classList.add('is-open');
            omoRestoreCachedDrawerRoute('policy-r1369', 'policy-r1369');
        });
        await assertFocused(second);
        assert.equal(await page.locator('[data-policy-drawer]').evaluate(el => el.classList.contains('is-open')), false);
        // A rule excluded by the current scope is fetched globally with its ID.
        await page.locator('#missing').click();
        const urls = await page.evaluate(() => refreshUrls);
        assert.equal(urls.length, 1);
        const url = new URL(urls[0], 'https://localtest.me');
        assert.equal(url.searchParams.get('policy_scope'), 'global');
        assert.equal(url.searchParams.get('open_rule_id'), '1370');
        // Detached, replaced panels must no longer react to navigation.
        await page.evaluate(() => {
            document.getElementById('omo-policy-root').remove();
            omoDispatchSpecialDrawerRouteChange('policy-r1371');
        });
        assert.equal(await page.evaluate(() => refreshUrls.length), 1);
        assert.deepEqual(errors, []);
        console.log('Policy rule navigation tests passed.');
    } finally {
        await browser.close();
    }
})().catch(error => {console.error(error); process.exitCode = 1;});
