const assert = require('node:assert/strict');
const fs = require('node:fs');
const {chromium} = require(process.argv[2] || 'playwright');

(async () => {
    const illustrations = JSON.parse(fs.readFileSync('img/technologies/sources.json', 'utf8'));
    const packages = JSON.parse(fs.readFileSync('composer.lock', 'utf8')).packages;
    const origin = 'https://localtest.me';
    const browser = await chromium.launch({headless: true, channel: 'chrome'});
    try {
        const context = await browser.newContext({ignoreHTTPSErrors: true, locale: 'fr-CH', viewport: {width: 1280, height: 1000}});
        const page = await context.newPage();
        const errors = [];
        const remoteGalleryRequests = [];
        page.on('pageerror', error => errors.push(error.message));
        page.on('request', request => {
            if (request.frame().url().includes('/popup/technologies.php') && new URL(request.url()).origin !== origin) remoteGalleryRequests.push(request.url());
        });
        await page.goto(origin + '/index2.php?lang=fr');
        const trigger = page.locator('footer a[href="/popup/technologies.php"]');
        await trigger.click();
        const dialog = page.locator('dialog.common-iframe-popup');
        await dialog.waitFor({state: 'visible'});
        const frame = page.frameLocator('dialog iframe');
        await frame.locator('#technology-summernote').waitFor();
        const ids = await frame.locator('.technology-card').evaluateAll(cards => cards.map(card => card.id.replace('technology-', '')).sort());
        assert.deepEqual(ids, Object.keys(illustrations).sort(), 'Every documented technology has a local illustration');
        for (const id of ['dompdf', 'tcpdf', 'phpword', 'phpspreadsheet', 'etherpad', 'ethercalc', 'collabora', 'spacedeck', 'jitsi', 'infomaniak', 'infomaniak-ai']) {
            assert.equal(await frame.locator('#technology-' + id).count(), 1, 'Required technology: ' + id);
        }
        assert(await frame.locator('.technology-card').evaluateAll(cards => cards.every(card => [...card.querySelectorAll('a')].some(link => /^https:\/\//.test(link.href)))), 'Each card links to its project');
        assert(await frame.locator('a[target="_blank"]').evaluateAll(links => links.every(link => link.rel.includes('noopener'))));
        assert(await frame.locator('.technology-card__image').evaluateAll(images => images.every(image => image.width === image.height)), 'Illustrations are square');
        await frame.locator('img').evaluateAll(images => images.forEach(image => image.loading = 'eager'));
        const gallery = page.frames().find(child => child.url().includes('/popup/technologies.php'));
        await gallery.waitForFunction(() => [...document.images].every(image => image.complete && image.naturalWidth > 0));
        assert.deepEqual(remoteGalleryRequests, [], 'The gallery loads no third-party resources');
        await dialog.screenshot({path: 'tmp/technologies-desktop.png'});
        await frame.getByRole('link', {name: 'Documents et bibliotheques PHP', exact: true}).click();
        await dialog.screenshot({path: 'tmp/technologies-libraries.png'});
        const detail = frame.locator('details');
        assert.equal(await detail.getAttribute('open'), null);
        await detail.locator('summary').click();
        assert.equal(await detail.locator('tbody tr').count(), packages.length, 'All Composer dependencies are documented');
        const word = detail.locator('tbody tr').filter({hasText: 'phpoffice/phpword'});
        assert((await word.textContent()).includes('LGPL-3.0-only'));
        await frame.locator('h1').scrollIntoViewIfNeeded();
        await frame.locator('h1').click();
        await page.keyboard.press('Escape');
        await dialog.waitFor({state: 'hidden'});
        assert(await trigger.evaluate(element => element === document.activeElement), 'Closing restores footer focus');

        await page.setViewportSize({width: 390, height: 844});
        await trigger.click();
        await frame.locator('#technology-etherpad').waitFor();
        assert(await frame.locator('html').evaluate(element => element.scrollWidth <= element.clientWidth), 'Mobile page has no horizontal overflow');
        await dialog.screenshot({path: 'tmp/technologies-mobile.png'});
        await dialog.getByRole('button', {name: 'Fermer'}).click();
        await page.locator('[data-omo2-supporters]').click();
        await page.frameLocator('dialog iframe').locator('h1').waitFor();
        assert((await dialog.locator('iframe').getAttribute('src')).includes('/popup/supporters.php'), 'The supporter popup still works');
        assert.deepEqual(errors, []);

        const noJs = await browser.newContext({ignoreHTTPSErrors: true, javaScriptEnabled: false});
        const direct = await noJs.newPage();
        assert.equal((await direct.goto(origin + '/popup/technologies.php?lang=fr')).status(), 200);
        assert.equal(await direct.locator('.technology-card').count(), ids.length, 'Standalone page works without JavaScript');
        await noJs.close();
        console.log('technologies_browser_test: OK (' + ids.length + ' technologies, images, ' + packages.length + ' PHP dependencies, footer popup, mobile, keyboard and standalone page)');
    } finally { await browser.close(); }
})().catch(error => {console.error(error); process.exitCode = 1;});
