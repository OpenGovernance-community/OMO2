'use strict';
const assert = require('node:assert/strict');
const {spawn} = require('node:child_process');
const {createInterface} = require('node:readline');
const {chromium} = require(process.argv[2] || 'playwright');

(async () => {
    const fixture = spawn('docker', ['compose', 'exec', '-T', 'app', 'php', 'tests/parcours_content_indicators_test.php', '--browser'], {stdio: ['pipe', 'pipe', 'inherit']});
    const closed = new Promise(resolve => fixture.on('close', resolve));
    let browser;
    try {
        const c = JSON.parse((await createInterface({input: fixture.stdout})[Symbol.asyncIterator]().next()).value);
        browser = await chromium.launch({headless: true, channel: 'chrome'});
        for (const member of [false, true]) {
            const context = await browser.newContext({ignoreHTTPSErrors: true, extraHTTPHeaders: {Origin: c.origin}});
            // Navigation does not depend on mission-list migrations in the local database.
            await context.route('**/getmissions.php?*', route => route.fulfill({contentType: 'application/json', body: '{"html":"","progress":0,"done":0,"total":1}'}));
            if (member) {
                const login = await context.request.post(c.origin + '/common/login_password.php', {
                    form: {email: c.email, password: c.password}, headers: {'X-Requested-With': 'XMLHttpRequest'},
                });
                assert.equal((await login.json()).status, 'ok');
            }
            const scope = member ? `oid=${c.oid}` : 'catalog=basic';
            const page = await context.newPage();
            for (const embedded of [false, true]) {
                await page.setViewportSize({width: embedded ? 390 : 1200, height: 900});
                const params = `${scope}${embedded ? '&embed=1' : ''}`;
                const packUrl = `${c.origin}/omo/api/lms/parcours.php?${params}&idp=${c.ids.pack}`;
                await page.goto(packUrl, {waitUntil: 'domcontentloaded'});
                assert.equal(await page.locator('[data-lms-catalog-back]').count(), 1);
                assert.equal(await page.locator('[data-lms-pack-back]').count(), 0);
                if (member) {
                    const child = page.locator('.lms-pack-children .card').filter({has: page.locator('h3', {hasText: 'Indicators video'})});
                    await child.locator('.open-btn').click();
                } else {
                    // The basic catalog has no organization scope for listing pack children.
                    await page.goto(`${c.origin}/omo/api/lms/parcours.php?${params}&idp=${c.ids.video}&pack_id=${c.ids.pack}`, {waitUntil: 'domcontentloaded'});
                }
                await page.waitForURL(url => url.searchParams.get('idp') === String(c.ids.video));
                assert.equal(new URL(page.url()).searchParams.get('pack_id'), String(c.ids.pack));
                const navigation = page.locator('.lms-catalog-navigation');
                assert.match(await navigation.innerText(), /Retour au catalogue\s*<\s*Retour au pack/);
                for (const target of ['catalog', 'pack']) {
                    const link = page.locator(`[data-lms-${target}-back]`);
                    const url = new URL(await link.getAttribute('href'), c.origin);
                    assert.equal(url.searchParams.get(member ? 'oid' : 'catalog'), member ? String(c.oid) : 'basic');
                    assert.equal(url.searchParams.get('embed'), embedded ? '1' : null);
                    assert.equal(url.searchParams.get('pack_id'), null);
                }
                assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
                await page.locator('[data-lms-pack-back]').click();
                await page.waitForURL(url => url.searchParams.get('idp') === String(c.ids.pack));
                assert.equal(await page.locator('[data-lms-pack-back]').count(), 0);
                await page.locator('[data-lms-catalog-back]').click();
                await page.waitForURL(url => url.pathname === '/omo/api/lms/');

                // Direct links and a forged or unrelated pack context must keep a single back link.
                for (const suffix of ['', `&pack_id=${c.ids.homework}`, `&pack_id=${c.ids.pack}`]) {
                    await page.goto(`${c.origin}/omo/api/lms/parcours.php?${params}&idp=${c.ids.empty}${suffix}`, {waitUntil: 'domcontentloaded'});
                    assert.equal(await page.locator('[data-lms-catalog-back]').count(), 1);
                    assert.equal(await page.locator('[data-lms-pack-back]').count(), 0);
                }
            }
            await context.close();
        }
        console.log('Pack navigation OK: public/member, standalone/iframe, round trips and invalid contexts.');
    } finally {
        await browser?.close();
        fixture.stdin.end('\n');
        assert.equal(await closed, 0);
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
