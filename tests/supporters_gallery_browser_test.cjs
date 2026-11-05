const assert = require('node:assert/strict');
const fs = require('node:fs');
const {execFileSync} = require('node:child_process');
const {chromium} = require(process.argv[2] || 'playwright');

(async () => {
    execFileSync('docker', ['compose', 'exec', '-T', 'app', 'php', '-d', 'display_errors=1', 'tests/supporters_gallery_test.php', '--preview']);
    const preview = fs.readFileSync('tmp/supporters-gallery-preview.html', 'utf8');
    const browser = await chromium.launch({headless: true, channel: 'chrome'});
    try {
        const context = await browser.newContext({ignoreHTTPSErrors: true, locale: 'fr-CH', viewport: {width: 1280, height: 1000}});
        const page = await context.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.goto('https://localtest.me/index2.php?lang=fr');
        const link = page.locator('[data-omo2-supporters]');
        assert.equal(await link.count(), 1);
        assert.equal(await link.getAttribute('href'), '/popup/supporters.php');
        await link.click();
        const dialog = page.locator('dialog.common-iframe-popup');
        await dialog.waitFor({state: 'visible'});
        let frame = page.frameLocator('dialog iframe');
        await frame.locator('#supporters-organizations').waitFor();
        assert(await frame.locator('#supporters-people').count());
        await frame.locator('h1').click();
        await page.keyboard.press('Escape');
        await dialog.waitFor({state: 'hidden'});
        assert(await link.evaluate(element => element === document.activeElement), 'Focus returns to footer link');

        // Render real dbObject-backed fixtures without persisting demo supporters in the local DB.
        await page.route('**/popup/supporters.php*', route => route.fulfill({status: 200, contentType: 'text/html; charset=utf-8', body: preview}));
        await link.click();
        frame = page.frameLocator('dialog iframe');
        await frame.locator('.supporters-card').first().waitFor();
        assert.equal(await frame.locator('.supporters-card').count(), 11);
        assert.equal(await frame.locator('.supporters-person').count(), 11);
        assert.equal(await frame.locator('.supporters-tier-amount').count(), 11);
        assert.equal(await frame.locator('.supporters-image--round').first().evaluate(element => getComputedStyle(element).borderRadius), '50%');
        await frame.locator('img').evaluateAll(images => images.forEach(image => image.loading = 'eager'));
        const galleryFrame = page.frames().find(child => child.url().includes('/popup/supporters.php'));
        await galleryFrame.waitForFunction(() => [...document.querySelectorAll('img')].every(image => image.complete && image.naturalWidth > 0));
        assert.equal(await frame.locator('img').count(), 2, 'Photos load and missing images reveal initials');
        await dialog.screenshot({path: 'tmp/supporters-gallery-desktop.png'});
        await dialog.getByRole('button', {name: 'Fermer'}).click();
        await dialog.waitFor({state: 'hidden'});
        await page.setViewportSize({width: 390, height: 844});
        await link.click();
        await frame.locator('.supporters-card').first().waitFor();
        assert(await frame.locator('html').evaluate(element => element.scrollWidth <= element.clientWidth), 'Mobile gallery does not overflow horizontally');
        await dialog.screenshot({path: 'tmp/supporters-gallery-mobile.png'});
        await page.mouse.click(2, 2);
        await dialog.waitFor({state: 'hidden'});

        // The existing public map uses the same extracted popup component.
        await page.goto('https://localtest.me/index.php');
        await page.waitForFunction(() => typeof openHomepageMaturityChart === 'function');
        await page.evaluate(() => {
            const trigger = document.createElement('a');
            trigger.href = '/popup/supporters.php';
            openHomepageMaturityChart(trigger, {name: 'Map regression'});
        });
        await dialog.waitFor({state: 'visible'});
        assert((await dialog.locator('iframe').getAttribute('title')).includes('Map regression'));
        await dialog.getByRole('button', {name: 'Fermer'}).click();
        assert.deepEqual(errors, []);
        console.log('supporters_gallery_browser_test: OK (footer, popup, keyboard, focus, 11+11 cards/portraits, mobile and map popup)');
    } finally { await browser.close(); }
})().catch(error => {console.error(error); process.exitCode = 1;});
