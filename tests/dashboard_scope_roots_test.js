'use strict';
// First render the fixture with dashboard_scope_roots_integration_test.php --html.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const {chromium} = require(process.argv[2] || 'playwright');
const root = path.join(__dirname, '..');
const source = fs.readFileSync(path.join(root, 'omo/assets/js/app.js'), 'utf8');
const extract = (start, end) => source.slice(source.indexOf(start), source.indexOf(end, source.indexOf(start)));

// Exercise the real dashboard click handler without starting the entire application.
let clickHandler;
let navigation;
const context = {
    document: {},
    window: {omoConfig: {rootHolonId: 1}, omoOpenDrawerHashState: (...args) => { navigation = ['open', ...args]; }},
    $: target => target === context.document ? {on: (event, selector, handler) => { clickHandler = handler; }} : {attr: name => target[name]},
    parseUrl: () => ({oid: 10, cid: 3}),
    omoSetPendingDrawerRouteOptions: (token, options) => { context.pending = {token, options}; },
    navigate: (...args) => { navigation = ['navigate', ...args]; },
    omoNormalizeRouteCid: value => Number(value) || null,
    omoNormalizeDrawerForcedScope: value => value || '',
    omoResolveAppUrl: value => value,
};
vm.createContext(context);
vm.runInContext(extract("$(document).on('click', '[data-omo-personal-space-route-token]'", "$(document).on('click', '[data-omo-personal-space-document-url]'"), context);
for (const scope of ['contextual', 'children', 'descendants']) {
    clickHandler.call({'data-omo-personal-space-route-token': 'documents', 'data-omo-personal-space-forced-scope': scope, 'data-omo-personal-space-context-holon-id': '2'}, {preventDefault() {}});
    assert.deepEqual(navigation, ['navigate', 10, 2, 'documents']);
    assert.equal(context.pending.options.forcedScope, scope);
    clickHandler.call({'data-omo-personal-space-route-token': 'documents', 'data-omo-personal-space-forced-scope': scope, 'data-omo-personal-space-context-holon-id': '3'}, {preventDefault() {}});
    assert.equal(navigation[0], 'open');
    assert.equal(navigation[2].forcedScope, scope);
}
vm.runInContext(extract('function buildDrawerUrl(', 'function omoParseReusableDrawerUrl('), context);
for (const [app, parameter] of [['checklist', 'checklist_scope'], ['processes', 'checklist_scope'], ['activities', 'activity_scope'], ['documents', 'document_scope']]) {
    assert.match(context.buildDrawerUrl('api/' + app + '/index.php', 10, 2, {forcedScope: 'children'}), new RegExp(parameter + '=children'));
}

(async () => {
    const browser = await chromium.launch({headless: true, channel: 'msedge'});
    try {
        for (const width of [1200, 390]) {
            const page = await browser.newPage({viewport: {width, height: 900}});
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.route('http://dashboard.test/**', async route => {
                const url = new URL(route.request().url());
                if (url.pathname === '/') {
                    let html = fs.readFileSync(path.join(root, 'tmp/dashboard-scope-roots.html'), 'utf8');
                    if (width === 390) {
                        const scopes = {holon: ['contextual'], parent: ['contextual', 'children', 'descendants'], organization: ['contextual', 'children', 'descendants']};
                        html = html.replace(/data-omo-dashboard-scopes-by-root="[^"]*"/, 'data-omo-dashboard-scopes-by-root="' + JSON.stringify(scopes).replaceAll('"', '&quot;') + '"');
                    }
                    await route.fulfill({contentType: 'text/html', body: '<!doctype html><meta charset="utf-8"><link rel="stylesheet" href="/common/assets/components.css"><link rel="stylesheet" href="/omo/assets/css/styles.css">' + html});
                } else if (url.pathname.startsWith('/common/assets/') || url.pathname.startsWith('/omo/assets/')) {
                    const file = path.join(root, url.pathname);
                    if (fs.existsSync(file)) await route.fulfill({path: file});
                    else await route.abort();
                } else await route.abort();
            });
            await page.goto('http://dashboard.test/');
            await page.evaluate(() => {
                window.omoRefreshMainRightPanel = () => {};
                window.omoNotify = message => { throw new Error(message); };
                window.fetch = async (url, options) => {
                    window.savedDashboard = JSON.parse(options.body);
                    return {ok: true, json: async () => ({status: true, layout: window.savedDashboard.layout})};
                };
            });
            await page.locator('[data-omo-dashboard-edit]').click();
            await page.locator('[data-configure-module="holon-contextual"]').click();
            assert.equal(await page.locator('[data-omo-dashboard-configure-root]').count(), 3);
            assert.equal(await page.locator('[data-omo-dashboard-configure-scope]').count(), 3);
            assert.equal(await page.locator('[data-omo-dashboard-configure-scope]:visible').count(), width === 390 ? 1 : 3);
            await page.locator('[data-omo-dashboard-configure-root="parent"]').click();
            assert.equal(await page.locator('[data-omo-dashboard-configure-scope="contextual"]').getAttribute('aria-pressed'), 'true');
            await page.locator('[data-omo-dashboard-configure-scope="children"]').click();
            assert.equal(await page.locator('[data-omo-dashboard-configure-root="parent"]').getAttribute('aria-pressed'), 'true');
            assert.equal(await page.locator('[data-omo-dashboard-configure-scope="children"]').getAttribute('aria-pressed'), 'true');
            if (width === 390) {
                await page.locator('[data-omo-dashboard-configure-root="holon"]').click();
                assert.equal(await page.locator('[data-omo-dashboard-configure-scope="contextual"]').getAttribute('aria-pressed'), 'true', 'A leaf root falls back to Local.');
                assert.equal(await page.locator('[data-omo-dashboard-configure-scope]:visible').count(), 1);
                await page.locator('[data-omo-dashboard-configure-root="parent"]').click();
                await page.locator('[data-omo-dashboard-configure-scope="children"]').click();
            }
            const panel = page.locator('[data-omo-dashboard-configurator] [role="dialog"]');
            const bounds = await panel.boundingBox();
            assert(bounds.x >= 0 && bounds.x + bounds.width <= width + 1, 'Configurator must fit the viewport.');
            assert(await panel.evaluate(element => element.scrollWidth <= element.clientWidth + 1), 'Root selector must not overflow.');
            await page.screenshot({path: path.join(root, 'tmp/dashboard-scope-roots-' + width + '.png'), animations: 'disabled'});
            await page.locator('[data-omo-dashboard-configurator-apply]').click();
            await page.locator('[data-configure-module="holon-contextual"]').click();
            assert.equal(await page.locator('[data-omo-dashboard-configure-root="parent"]').getAttribute('aria-pressed'), 'true');
            assert.equal(await page.locator('[data-omo-dashboard-configure-scope="children"]').getAttribute('aria-pressed'), 'true');
            await page.locator('[data-omo-dashboard-configure-root="organization"]').click();
            await page.locator('[data-omo-dashboard-configurator-close]').click();
            await page.locator('[data-omo-dashboard-editor-save]').click();
            const saved = await page.evaluate(() => window.savedDashboard.layout.find(module => module.id === 'holon-contextual').settings);
            assert.equal(saved.scopeRoot, 'parent', 'Cancelling the configurator must preserve the applied root.');
            assert.equal(saved.scope, 'children');
            assert.deepEqual(errors, []);
            await page.close();
        }
        console.log('dashboard_scope_roots_test: OK (desktop/mobile configuration, save, cancel, and root/scope navigation)');
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
