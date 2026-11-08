const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require(process.argv[2] || 'playwright');
const root = path.resolve(__dirname, '..');
const script = fs.readFileSync(path.join(root, 'omo/assets/js/pwa-navigation.js'), 'utf8');
const css = fs.readFileSync(path.join(root, 'common/assets/topbar.css'), 'utf8');
const catalog = 'https://omo.test/omo/';

(async () => {
    const browser = await chromium.launch({headless: true, channel: 'chrome'});
    try {
        async function session(mode = 'browser', storageUnavailable = false) {
            const context = await browser.newContext();
            await context.addInitScript(({mode, storageUnavailable}) => {
                const original = window.matchMedia.bind(window);
                window.matchMedia = query => query === '(display-mode: standalone)'
                    ? {matches: mode === 'standalone', addEventListener() {}} : original(query);
                if (mode === 'ios') Object.defineProperty(navigator, 'standalone', {value: true});
                if (storageUnavailable) Object.defineProperty(window, 'sessionStorage', {get() { throw new Error('Unavailable'); }});
            }, {mode, storageUnavailable});
            await context.route('https://**.test/**', async route => {
                const url = new URL(route.request().url());
                if (url.pathname === '/common/assets/icon-topbar-catalog.png') {
                    return route.fulfill({contentType: 'image/png', body: fs.readFileSync(path.join(root, 'common/assets/icon-topbar-catalog.png'))});
                }
                const hostOid = url.hostname === 'team.omo.test' ? 42 : 0;
                const oid = hostOid || Number(url.pathname.match(/\/o\/(\d+)/)?.[1] || 0);
                const home = url.origin + (hostOid || !oid ? '/omo/' : '/omo/o/' + oid);
                const config = {organizationId: oid, hostOrganizationId: hostOid, homeUrl: home, catalogUrl: catalog};
                await route.fulfill({contentType: 'text/html', body: `<!doctype html><meta charset="utf-8"><style>${css}</style>
                    <header class="common-topbar"><a class="common-topbar__brand-link common-topbar__brand-link--logo" href="${home}" data-common-topbar-brand-link>
                    <span class="common-topbar__brand"><img alt="Organization" width="48" height="48"><span class="common-topbar__catalog-badge" data-common-topbar-catalog-badge hidden aria-hidden="true"><img src="/common/assets/icon-topbar-catalog.png" width="48" height="48" alt=""></span></span></a>
                    <a class="common-topbar__brand-link common-topbar__brand-link--name" href="${home}">Team</a></header>
                    <a id="catalog" href="${catalog}">Catalog</a><a id="other" href="https://omo.test/omo/o/99">Other organization</a>
                    <article data-organization-id="42"><a id="team" href="https://team.omo.test/omo/">Organization</a></article>
                    <script>window.omoPwaNavigationConfig=${JSON.stringify(config)};
                    window.commonTopbarConfig={brandNavigation:{enabled:${oid > 0},homeLabel:'Organization home',catalogLabel:'Organization catalog'}};
                    window.omoConfig={oid:${oid}};
                    window.navigate=function(oid){history.pushState({},'',${JSON.stringify(home)});window.dispatchEvent(new Event('omo-structure-route-sync'));};</script><script>${script}</script>`});
            });
            return {context, page: await context.newPage()};
        }
        const logo = page => page.locator('[data-common-topbar-brand-link]');
        const badge = page => page.locator('[data-common-topbar-catalog-badge]');
        for (const url of ['https://omo.test/omo/o/42/c/8#documents', 'https://team.omo.test/omo/c/8#projects', 'https://omo.test/omo/o/42?pwa=42#documents']) {
            const {context, page} = await session();
            await page.goto(url);
            assert.equal(await badge(page).isVisible(), false);
            await logo(page).click();
            assert.equal(await badge(page).isVisible(), true, 'Returning home reveals the catalog action');
            assert.equal(await logo(page).getAttribute('aria-label'), 'Organization catalog');
            await page.goBack();
            assert.equal(await badge(page).isVisible(), false, 'Browser back restores the home action');
            await page.goForward();
            assert.equal(await badge(page).isVisible(), true);
            await logo(page).click();
            await page.waitForURL(catalog);
            await context.close();
        }
        for (const mode of ['standalone', 'ios']) {
            const {context, page} = await session(mode);
            await page.goto('https://omo.test/omo/o/42?pwa=42');
            assert.equal(await badge(page).isVisible(), false, 'Organization apps never offer the catalog');
            await logo(page).click();
            assert.equal(new URL(page.url()).pathname, '/omo/o/42');
            await page.locator('#catalog').click();
            await page.locator('#other').click();
            assert.equal(new URL(page.url()).pathname, '/omo/o/42', 'Links cannot leave the installed organization');
            await page.goto(catalog);
            await page.waitForURL('**/omo/o/42?pwa=42');
            await page.goto('https://omo.test/omo/o/99');
            await page.waitForURL('**/omo/o/42?pwa=42');
            assert.equal(await badge(page).isVisible(), false);
            // A separate installation on the same origin must have its own home.
            const second = await context.newPage();
            await second.goto(catalog + '?pwa=0');
            await second.locator('#team').click();
            await second.waitForURL('https://omo.test/omo/o/42?pwa=0');
            assert.equal(await badge(second).isVisible(), true, 'Catalog apps retain their catalog after entering an organization');
            await logo(second).click();
            await second.waitForURL(catalog + '?pwa=0');
            await page.reload();
            assert.equal(await badge(page).isVisible(), false, 'The other installation cannot change this window home');
            await context.close();
        }
        {
            const {context, page} = await session('standalone', true);
            await page.goto('https://team.omo.test/omo/o/42');
            assert.equal(await badge(page).isVisible(), false, 'Older apps and unavailable storage retain their organization');
            await logo(page).click();
            assert.equal(await badge(page).isVisible(), false);
            await context.close();
        }
        console.log('PASS brand navigation: home/catalog, back/forward, host/path routes, standalone/iOS, organization lock, catalog app, isolated installations and legacy fallback');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
