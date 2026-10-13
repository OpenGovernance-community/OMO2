'use strict';
// NODE_PATH may point to test dependencies (jquery). Pass Playwright and Chrome paths as arguments.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require(process.argv[2] || 'playwright');
const read = file => fs.readFileSync(path.join(__dirname, '..', file), 'utf8');
const app = read('omo/assets/js/app.js');
function extract(start, end) {
    const first = app.indexOf(start), last = app.indexOf(end, first);
    assert(first >= 0 && last > first, 'Cannot locate ' + start);
    return app.slice(first, last);
}
const guards = extract('function omoConfirmDiscardChanges(', 'function loadContent(');
const navigation = extract('function navigate(', 'function parseUrl(');
const routing = extract('function handleRoute()', 'function activateMenu(');
const loading = extract('function loadContent(', 'function omoResetMainRightPanel(');
const drawers = extract('function omoDrawerNeedsReload(', 'function refreshDrawer(')
    + extract('function closeDrawer(', 'function getSidebarMenuItem(')
    + extract('function omoCanReuseStoredDrawerRoute(', 'function omoResolveDrawerContentRouteToken(')
    + extract('function omoClearClosedDrawers(', 'function omoRefreshUserProfileDependentViews(');

(async () => {
    const browser = await chromium.launch({headless: true, ...(process.argv[3] ? {executablePath: process.argv[3]} : {})});
    try {
        const page = await browser.newPage();
        page.setDefaultTimeout(5000);
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.route('http://navigation.test/**', route => route.fulfill({contentType: 'text/html', body: '<html lang="fr"><body><p id="reloaded">Reloaded page</p></body></html>'}));
        await page.goto('http://navigation.test/omo/c/10#projects');
        await page.setContent(`<style>.drawer:not(.open) {display:none}</style><button id="holon">Other holon</button><div id="panel-left"></div><div class="content">
            <div class="drawer open" id="drawer_projects"><div class="drawer-content">
                <div class="omo-overlay-drawer is-open"><form id="editor"><input name="title" value="">
                    <div data-rich-host><div contenteditable="true"></div></div></form></div>
            </div></div><div class="drawer" id="drawer_stats"><div class="drawer-content"><p>Reference information</p></div></div></div>`);
        await page.addScriptTag({path: require.resolve('jquery')});
        await page.evaluate(() => {
            window.confirmations = [];
            window.acceptDiscard = false;
            window.confirm = text => { window.confirmations.push(text); return window.acceptDiscard; };
            window.fetch = async () => ({ok: true, json: async () => ({discard: 'Discard unsaved data?'})});
            const host = document.querySelector('[data-rich-host]');
            host.__omoSimpleHtmlField = {getValue: () => host.querySelector('[contenteditable]').innerHTML};
        });
        await page.addScriptTag({content: read('common/drawer/subdrawer.js')});
        await page.addScriptTag({content: guards + loading + drawers + navigation + routing + String.raw`
            let currentState = {oid:7, cid:'10', hash:'projects', routeToken:'projects', popupToken:null};
            window.operations = [];
            window.requests = [];
            window.routeEvents = 0;
            window.parseUrl = () => ({oid:7, cid:location.pathname.match(/\/c\/(\d+)/)?.[1] || null, hash:location.hash.slice(1) || null});
            window.buildOmoUrl = (oid, cid, hash) => '/omo' + (cid ? '/c/' + cid : '') + (hash ? '#' + hash : '');
            window.omoParseHashState = hash => ({routeToken:hash || null, popupToken:null, popupKey:null, popupId:null});
            window.omoBuildHashFromState = (token, popup) => popup ? token + '|' + popup : token;
            window.omoGetMenuHashForRouteToken = token => token && token.split('-')[0];
            window.getSidebarMenuConfig = (token, oid, cid) => ({drawer:'drawer_' + token.split('-')[0], resolvedUrl:'/replacement?app=' + token.split('-')[0] + '&cid=' + cid});
            window.omoNormalizeDrawerId = id => id;
            window.omoNormalizeHashToken = token => token || null;
            window.omoResolveDrawer = id => ({id:id, drawer:$('#' + id)});
            window.omoResolveDrawerContentRouteToken = (id, token) => token || omoParseHashState(parseUrl().hash).routeToken;
            window.omoGetDrawerRouteContextKey = () => '7:' + Number(parseUrl().cid || 0);
            window.omoParseDocumentRouteToken = () => null;
            window.omoCanReuseCachedPanelDrawer = () => false;
            window.clearDrawerRemoval = () => {};
            window.omoIsDecisionIndexUrl = () => false;
            window.updateDrawerPosition = () => {};
            window.omoRestoreCachedDrawerRoute = () => false;
            window.removeDrawerAfterTransition = drawer => drawer.remove();
            window.omoNormalizeDrawerForcedScope = () => '';
            window.omoGetLeftPanelContentSelector = () => '#panel-left';
            window.omoIsShareMode = () => false;
            window.omoRefreshMainRightPanel = () => window.operations.push('right-panel');
            window.refreshDrawer = (id, url) => { window.operations.push('refresh'); loadContent('#' + id + ' .drawer-content', url); return true; };
            window.omoResolveAppUrl = url => url;
            window.omoShouldTraceDecisionLoad = () => false;
            window.getSkeleton = () => '<p>Loading</p>';
            window.omoExecuteFetchedScripts = () => Promise.resolve();
            ['omoEnsurePopupBootstrapState', 'omoResetRememberedDrawerRoutes', 'omoRememberDrawerRoute',
                'updateActiveMenu', 'omoEnsureMainRightPanelCurrent', 'omoClosePopupModalFromRoute',
                'omoClearPendingDrawerRouteOptions', 'omoPreparePvApplicationSubdrawers', 'omoInitMobileHeaderMenus',
                'omoReplayLoadedDocumentsRoute', 'omoDispatchSpecialDrawerRouteChange', 'omoStoreDrawerContentRoute']
                .forEach(name => { window[name] = () => {}; });
            $.ajax = settings => {
                window.requests.push(settings.url);
                queueMicrotask(() => { settings.success('<p data-loaded>New content</p>'); settings.complete(); });
                return {readyState:4};
            };
            document.getElementById('holon').onclick = () => navigate(7, 11, 'projects');
            window.addEventListener('omo-route-change', () => window.routeEvents++);
            window.addEventListener('popstate', () => handleRoute());
            window.getRouteState = () => currentState;
            ['projects', 'stats'].forEach(app => {
                $('#drawer_' + app).data('omo-drawer-url', '/replacement?app=' + app + '&cid=10')
                    .data('omo-drawer-route-token', app).data('omo-drawer-route-context', '7:10');
            });
        `});

        await page.locator('[name="title"]').fill('Holon 10 draft');
        await page.evaluate(() => { window.originalForm = document.getElementById('editor'); navigate(7, 10, 'stats'); });
        await page.waitForFunction(() => document.getElementById('drawer_stats').classList.contains('open'));
        assert.equal(await page.locator('[name="title"]').isVisible(), false);
        assert.equal(await page.evaluate(() => window.confirmations.length), 0, 'Switching to another cached app must not ask about the source.');
        assert.equal(await page.evaluate(() => document.getElementById('editor') === window.originalForm), true);
        assert.equal(await page.locator('[name="title"]').inputValue(), 'Holon 10 draft');
        assert.equal(await page.evaluate(() => commonDrawerHasUnsavedChanges(document)), true, 'The hidden app still owns an unsaved draft.');
        await page.evaluate(() => omoClearClosedDrawers());
        assert.equal(await page.evaluate(() => document.getElementById('editor') === window.originalForm), true, 'Cache maintenance must preserve hidden unsaved editors.');
        await page.evaluate(() => {
            document.querySelector('#drawer_stats .drawer-content').insertAdjacentHTML('beforeend', '<div class="omo-overlay-drawer is-open" id="stats-editor"><form><input name="stats_name"></form></div>');
        });
        await page.locator('[name="stats_name"]').fill('Statistics draft');
        await page.evaluate(() => navigate(7, 10, 'projects'));
        await page.waitForFunction(() => document.getElementById('drawer_projects').classList.contains('open'));
        assert.equal(await page.evaluate(() => window.confirmations.length), 0, 'Several apps can each keep a draft without blocking app switches.');
        await page.evaluate(() => navigate(7, 10, 'stats-i9'));
        await page.waitForFunction(() => document.getElementById('drawer_stats').classList.contains('open') && location.hash === '#stats');
        assert.equal(new URL(page.url()).hash, '#stats', 'Cancelling the replacement reveals the preserved target editor and its cached route.');
        assert.equal(await page.evaluate(() => window.confirmations.length), 1);
        assert.equal(await page.locator('[name="stats_name"]').inputValue(), 'Statistics draft');
        assert.equal(await page.locator('[name="title"]').inputValue(), 'Holon 10 draft');
        await page.evaluate(() => { window.confirmations = []; navigate(7, 10, 'stats'); });
        await page.waitForFunction(() => document.getElementById('drawer_stats').classList.contains('open'));
        assert.equal(await page.evaluate(() => window.confirmations.length), 0, 'Returning to the preserved target editor does not discard either draft.');
        await page.evaluate(() => {
            commonDrawerMarkSaved(document.querySelector('#stats-editor form'));
            document.getElementById('stats-editor').remove();
        });
        await page.evaluate(() => navigate(7, 10, 'stats-i1'));
        assert.equal(await page.evaluate(() => window.confirmations.length), 0, 'Consulting details in another app does not discard the hidden draft.');
        await page.locator('#holon').click();
        await page.waitForFunction(() => document.getElementById('drawer_projects').classList.contains('open') && location.hash === '#projects');
        assert.equal(new URL(page.url()).pathname, '/omo/c/10', 'A background draft also blocks a holon change.');
        assert.equal(new URL(page.url()).hash, '#projects');
        assert.equal(await page.locator('[name="title"]').isVisible(), true, 'The editor that blocked the holon change is brought back into view.');
        assert.equal(await page.evaluate(() => document.activeElement === document.getElementById('editor').closest('.omo-overlay-drawer')), true, 'The revealed subdrawer receives keyboard focus.');
        assert.equal(await page.locator('[name="title"]').inputValue(), 'Holon 10 draft');
        await page.evaluate(() => { window.confirmations = []; closeDrawer('drawer_projects', true); });
        assert.equal(await page.evaluate(() => window.confirmations.length), 1, 'Explicit removal checks the hidden drawer before deleting it.');
        assert.equal(await page.evaluate(() => document.getElementById('editor') === window.originalForm), true);
        assert.equal(await page.evaluate(() => {
            document.getElementById('drawer_projects').hidden = true;
            const event = new Event('beforeunload', {cancelable:true});
            window.dispatchEvent(event);
            document.getElementById('drawer_projects').hidden = false;
            return event.defaultPrevented;
        }), true, 'Even a main drawer hidden with an attribute remains protected.');
        await page.evaluate(() => { window.confirmations = []; navigate(7, 10, 'projects'); });
        await page.waitForFunction(() => document.getElementById('drawer_projects').classList.contains('open'));
        assert.equal(await page.evaluate(() => document.getElementById('editor') === window.originalForm), true, 'Returning must keep the same form, including its editor state.');
        await page.evaluate(() => navigate(7, 10, 'stats'));
        await page.waitForFunction(() => document.getElementById('drawer_stats').classList.contains('open'));
        await page.evaluate(() => navigate(7, 10, 'projects'));
        await page.waitForFunction(() => document.getElementById('drawer_projects').classList.contains('open'));
        await page.goBack();
        await page.waitForFunction(() => document.getElementById('drawer_stats').classList.contains('open'));
        await page.goForward();
        await page.waitForFunction(() => document.getElementById('drawer_projects').classList.contains('open'));
        assert.equal(await page.evaluate(() => window.confirmations.length), 0, 'Back/Forward between cached apps must not prompt.');
        await page.evaluate(() => { window.operations = []; window.requests = []; window.routeEvents = 0; });
        await page.locator('#holon').click();
        assert.equal(new URL(page.url()).pathname, '/omo/c/10', 'Declining must precede history changes.');
        assert.equal(await page.locator('[name="title"]').inputValue(), 'Holon 10 draft');
        assert.deepEqual(await page.evaluate(() => [window.operations, window.requests, window.routeEvents]), [[], [], 0]);
        assert.equal(await page.evaluate(() => window.getRouteState().cid), '10');

        assert.deepEqual(await page.evaluate(() => {
            const editor = document.createElement('section');
            editor.setAttribute('data-omo-unsaved-changes', '');
            let calls = 0;
            editor.omoConfirmDiscardChanges = () => { calls++; return false; };
            document.body.appendChild(editor);
            const count = window.confirmations.length;
            const result = omoConfirmDiscardChanges();
            editor.remove();
            return [result, calls, window.confirmations.length - count];
        }), [false, 1, 0], 'An existing editor guard can still veto navigation.');

        await page.evaluate(() => { window.confirmations = []; loadContent('#drawer_projects .drawer-content', '/replacement'); });
        assert.equal(await page.locator('[name="title"]').inputValue(), 'Holon 10 draft', 'A direct drawer reload must preserve the draft when declined.');
        assert.equal(await page.evaluate(() => window.requests.length), 0);
        assert.equal(await page.evaluate(() => window.confirmations.length), 1);

        await page.evaluate(() => {
            history.replaceState({}, '', '/omo/c/9#projects');
            history.pushState({}, '', '/omo/c/10#projects');
            window.confirmations = [];
        });
        await page.goBack();
        await page.waitForFunction(() => window.confirmations.length === 1);
        assert.equal(new URL(page.url()).pathname, '/omo/c/10', 'Declining browser Back restores the displayed route.');
        assert.equal(await page.locator('[name="title"]').inputValue(), 'Holon 10 draft');
        assert.deepEqual(await page.evaluate(() => window.operations), []);

        await page.evaluate(() => { window.confirmations = []; window.acceptDiscard = true; });
        await page.locator('#holon').click();
        await page.locator('#drawer_projects [data-loaded]').waitFor();
        assert.equal(new URL(page.url()).pathname, '/omo/c/11');
        assert.equal(await page.evaluate(() => window.confirmations.length), 1, 'navigate, handleRoute and loadContent share one approval.');
        assert.equal(await page.evaluate(() => window.routeEvents), 1);

        // Check that a navigation approval neither clears the draft nor disables native unload.
        await page.evaluate(() => {
            document.querySelector('#drawer_projects .drawer-content').innerHTML = '<div class="omo-overlay-drawer is-open"><form id="editor"><input name="title" value=""></form></div>';
        });
        await page.locator('[name="title"]').fill('New draft');
        assert.deepEqual(await page.evaluate(() => {
            window.confirmations = [];
            const first = omoConfirmDiscardChanges();
            const second = omoConfirmDiscardChanges(document.getElementById('drawer_projects'));
            const unload = new Event('beforeunload', {cancelable:true});
            window.dispatchEvent(unload);
            return [first, second, window.confirmations.length, unload.defaultPrevented, commonDrawerHasUnsavedChanges(document)];
        }), [true, true, 1, true, true]);
        await page.evaluate(() => { window.acceptDiscard = false; });
        assert.equal(await page.evaluate(() => omoConfirmDiscardChanges()), false, 'A later action must ask again about the same preserved draft.');

        await page.evaluate(() => { window.confirmations = []; navigate(7, 11, 'stats'); });
        await page.waitForFunction(() => document.getElementById('drawer_stats').classList.contains('open'));
        assert.equal(await page.locator('[name="title"]').isVisible(), false);
        assert.equal(await page.evaluate(() => window.confirmations.length), 0, 'Opening an uncached app must also preserve the source without a warning.');

        const nativeDialogs = [];
        page.once('dialog', async dialog => { nativeDialogs.push(dialog.type()); await dialog.dismiss(); });
        await page.reload({waitUntil:'domcontentloaded', timeout:5000}).catch(error => {
            assert.match(error.message, /ERR_ABORTED|net::|Timeout/, 'Reload cancellation should abort navigation.');
        });
        assert.deepEqual(nativeDialogs, ['beforeunload']);
        await page.locator('[name="title"]').waitFor({state: 'visible'});
        assert.equal(await page.locator('[name="title"]').inputValue(), 'New draft', 'Cancelling a real browser reload preserves the input.');
        await page.locator('[name="title"]').click();
        assert.equal(new URL(page.url()).hash, '#projects', 'Resuming after a cancelled native reload aligns the URL without replaying the editor.');
        page.once('dialog', async dialog => { nativeDialogs.push(dialog.type()); await dialog.accept(); });
        await page.reload({waitUntil:'domcontentloaded'});
        assert.deepEqual(nativeDialogs, ['beforeunload', 'beforeunload']);
        assert.equal(await page.locator('#reloaded').innerText(), 'Reloaded page');

        page.on('dialog', async dialog => { nativeDialogs.push(dialog.type()); await dialog.accept(); });
        for (const mode of ['untouched', 'saved']) {
            await page.setContent('<div class="omo-overlay-drawer is-open"><form><input name="title" value=""></form></div>');
            await page.evaluate(() => { window.fetch = async () => ({ok:true, json:async () => ({})}); });
            await page.addScriptTag({content: read('common/drawer/subdrawer.js')});
            if (mode === 'saved') {
                await page.locator('[name="title"]').fill('Saved title');
                await page.evaluate(() => commonDrawerMarkSaved(document.querySelector('form')));
            } else {
                await page.locator('[name="title"]').focus();
            }
            await page.reload({waitUntil:'domcontentloaded'});
            assert.equal(nativeDialogs.length, 2, mode + ' forms reload without a warning.');
        }

        assert.deepEqual(errors, []);
        console.log('subdrawer_navigation_unsaved_test: OK');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
