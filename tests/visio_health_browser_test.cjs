const assert = require('node:assert/strict');
const {chromium} = require(process.argv[2] || 'playwright');

(async () => {
    const browser = await chromium.launch({headless: true, channel: 'chrome'});
    try {
        const context = await browser.newContext({ignoreHTTPSErrors: true, locale: 'fr-CH'});
        const page = await context.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        const origin = 'https://localtest.me';
        await page.goto(origin + '/visio/status.php?lang=fr');
        await page.waitForFunction(() => document.querySelector('[data-health-results]').getAttribute('aria-busy') === 'false');
        assert.equal(await page.locator('[data-health-summary]').textContent(), 'Controles techniques reussis');
        assert.equal(await page.locator('[data-health-check]').count(), 6);
        assert.deepEqual(await page.locator('[data-health-check]').allTextContents(), Array(6).fill('OK'));
        assert.equal(await page.locator('[data-health-url] code').textContent(), origin + '/visio/health.php');
        assert(await page.locator('[data-health-time]').isVisible());
        for (const path of ['/omo-health/debug', '/omo-health/jicofo/about/version', '/omo-health/videobridge/shutdown']) {
            const response = await context.request.get('https://meet.localtest.me' + path);
            assert.equal(response.status(), 404, 'Only exact health paths are proxied: ' + path);
        }
        await page.screenshot({path: 'tmp/visio-health-desktop.png', fullPage: true});

        await page.route('**/visio/health.php', route => route.fulfill({status: 503, contentType: 'application/json', body: JSON.stringify({status: 'error', checks: {jicofo: 'unavailable'}})}));
        await page.locator('[data-health-refresh]').click();
        await page.waitForFunction(() => document.querySelector('[data-health-results]').getAttribute('aria-busy') === 'false');
        assert.equal(await page.locator('[data-health-summary]').textContent(), 'Service indisponible ou verification incomplete');
        assert.equal(await page.locator('[data-health-check="jicofo"]').textContent(), 'Indisponible');
        assert.equal(await page.locator('[data-health-check="database"]').textContent(), 'Non verifie');

        await page.route('**/visio/health.php', route => route.fulfill({status: 200, contentType: 'text/html', body: '<html>Login</html>'}));
        await page.locator('[data-health-refresh]').click();
        await page.waitForFunction(() => document.querySelector('[data-health-results]').getAttribute('aria-busy') === 'false');
        assert.equal(await page.locator('[data-health-summary]').textContent(), 'Service indisponible ou verification incomplete');
        assert.deepEqual(await page.locator('[data-health-check]').allTextContents(), Array(6).fill('Non verifie'), 'A failed request clears previous success');
        assert(await page.locator('[data-health-refresh]').isEnabled(), 'Checks can be retried after a malformed response');

        await page.setViewportSize({width: 390, height: 844});
        assert(await page.locator('html').evaluate(element => element.scrollWidth <= element.clientWidth), 'No mobile horizontal overflow');
        await page.screenshot({path: 'tmp/visio-health-mobile.png', fullPage: true});
        assert.deepEqual(errors, []);
        const noJs = await browser.newContext({ignoreHTTPSErrors: true, javaScriptEnabled: false});
        const direct = await noJs.newPage();
        await direct.goto(origin + '/visio/status.php?lang=fr');
        assert(await direct.locator('noscript').isVisible());
        assert.equal(await direct.locator('[data-health-url]').getAttribute('href'), '/visio/health.php');
        await noJs.close();
        console.log('visio_health_browser_test: OK (live Jitsi, degraded/malformed responses, retry, desktop/mobile and no-JS fallback)');
    } finally { await browser.close(); }
})().catch(error => {console.error(error); process.exitCode = 1;});
