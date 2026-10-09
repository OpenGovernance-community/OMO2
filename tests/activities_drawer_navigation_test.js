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
    + extract('function omoDispatchSpecialDrawerRouteChange(', 'function omoRestoreCachedDrawerRoute(')
    + extract('function omoParseActivityRouteToken(', 'function omoBuildActivityRouteToken(');

(async () => {
    const browser = await chromium.launch({headless: true, channel: 'msedge'});
    try {
        const page = await browser.newPage();
        page.setDefaultTimeout(5000);
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.route('**/omo/api/activities/detail.php?*', route => route.fulfill({
            contentType: 'text/html', body: '<p data-loaded-detail>Task detail</p>'
        }));
        await page.route('**/activities-fixture', route => route.fulfill({contentType: 'text/html', body: `
            <!doctype html><div id="drawer_activities" class="open">
                <div id="omo-activities-root" data-activity-oid="7" data-activity-cid="48" data-activity-open-id="63">
                    <button data-activity-open-url="/omo/api/activities/detail.php?oid=7&id=63">Open task</button>
                    <div data-activity-drawer hidden>
                        <button data-activity-close id="backdrop">Backdrop</button>
                        <h3 data-omo-subdrawer-title>Tasks</h3>
                        <p data-omo-subdrawer-description hidden></p>
                        <div data-omo-subdrawer-actions></div>
                        <button data-activity-close id="close">Close</button>
                        <div data-activity-drawer-body></div>
                    </div>
                </div>
            </div>
            <script>
                var currentState = {oid:7, cid:48, hash:'activities-d63', routeToken:'activities-d63', popupToken:null};
                history.replaceState({}, '', '#activities-d63');
                window.parentOperations = [];
                function parseUrl() { return {oid:7, cid:48, hash:location.hash.slice(1) || null}; }
                function omoParseHashState(hash) { return {routeToken:hash, popupToken:null}; }
                function omoNormalizeHashToken(token) { return token || null; }
                function omoGetMenuHashForRouteToken(token) { return token && token.split('-')[0]; }
                function omoConfirmDiscardChanges() { return true; }
                function getSidebarMenuConfig() { return {drawer:'drawer_activities', resolvedUrl:'/omo/api/activities/index.php'}; }
                function omoNormalizeDrawerId(id) { return id; }
                function omoNormalizeDrawerForcedScope() { return ''; }
                function openDrawer() { window.parentOperations.push('open'); }
                function closeAllDrawers() { window.parentOperations.push('close'); }
                function resetDrawers() { window.parentOperations.push('reset'); }
                function refreshDrawer() { window.parentOperations.push('refresh'); return true; }
                function omoStoreDrawerContentRoute(id, token) { window.storedRoute = token; }
                function omoEnsurePopupBootstrapState() {}
                function omoResetRememberedDrawerRoutes() {}
                function omoRememberDrawerRoute() {}
                function updateActiveMenu() {}
                function omoEnsureMainRightPanelCurrent() {}
                function omoClosePopupModalFromRoute() {}
                function omoClearPendingDrawerRouteOptions() {}
                window.commonRenderLoadingState = (body, message) => { body.textContent = message; };
                window.omoParsePopupHashState = () => omoParseHashState(parseUrl().hash);
                window.omoOpenDrawerHashState = token => {
                    history.pushState({}, '', '#' + token);
                    handleRoute();
                };
                ${router}
            </script>
            <script>${read('common/drawer/subdrawer.js')}</script>
            <script>${read('omo/api/activities/activities.js')}</script>
        `}));
        await page.goto('https://localtest.me/activities-fixture');
        const detail = page.locator('[data-activity-drawer]');
        const loaded = page.locator('[data-loaded-detail]');
        await loaded.waitFor();

        // Closing a task opened by the attention badge must only close the detail.
        await page.locator('#close').click();
        await detail.waitFor({state: 'hidden'});
        assert.equal(await page.evaluate(() => location.hash), '#activities');
        assert.deepEqual(await page.evaluate(() => window.parentOperations), []);
        assert.equal(await page.evaluate(() => window.storedRoute), 'activities');
        assert.equal(await page.locator('#drawer_activities.open').count(), 1);

        // Direct navigation to another task reuses the parent, including browser Back/Forward.
        await page.evaluate(() => window.omoOpenDrawerHashState('activities-d64'));
        await loaded.waitFor();
        await page.goBack();
        await page.evaluate(() => handleRoute());
        await detail.waitFor({state: 'hidden'});
        await page.goForward();
        await page.evaluate(() => handleRoute());
        await loaded.waitFor();
        await page.locator('#backdrop').click();
        await detail.waitFor({state: 'hidden'});
        assert.equal(await page.evaluate(() => location.hash), '#activities');
        assert.deepEqual(await page.evaluate(() => window.parentOperations), []);

        // The ordinary list opening and local close must still work.
        await page.locator('[data-activity-open-url]').click();
        await loaded.waitFor();
        await page.locator('#close').click();
        await detail.waitFor({state: 'hidden'});
        assert.deepEqual(await page.evaluate(() => window.parentOperations), []);
        assert.deepEqual(errors, []);
        console.log('activities_drawer_navigation_test: OK');
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
