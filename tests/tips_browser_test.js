'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require(process.argv[2] || 'playwright');
const root = path.join(__dirname, '..');
const read = file => fs.readFileSync(path.join(root, file), 'utf8');

(async () => {
    const browser = await chromium.launch({headless: true, channel: 'msedge'});
    try {
        const page = await browser.newPage({ignoreHTTPSErrors: true, viewport: {width: 1200, height: 850}});
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        let requests = 0;
        let failNext = false;
        const tips = [
            {id: 11, text: 'Une petite astuce pour decouvrir le logiciel.', link: '#|faq', image: ''},
            {id: 12, text: '<img src=x onerror=alert(1)> reste du texte.', link: 'https://example.org/help', image: '/img/test-tip.png'}
        ];
        await page.route('**/omo/api/tips.php?*', route => {
            requests++;
            if (failNext) return route.fulfill({status: 500, body: '{}'});
            const cookieHeader = route.request().headers().cookie || '';
            const value = cookieHeader.split('; ').find(value => value.startsWith('omo_tip_2='));
            const cookie = value ? JSON.parse(decodeURIComponent(value.slice('omo_tip_2='.length))) : {};
            const next = route.request().url().includes('next=1');
            const tip = next ? tips.find(tip => tip.id !== cookie.id) : tips.find(tip => tip.id === cookie.id) || tips[0];
            return route.fulfill({contentType: 'application/json', body: JSON.stringify({tip, cookieName: 'omo_tip_2', day: '2026-10-12', expires: 'Tue, 13 Oct 2026 00:00:00 GMT'})});
        });
        await page.route('**/common/jstranslation/tips.php', route => route.fulfill({contentType: 'application/json', body: '{}'}));
        await page.route('**/img/test-tip.png', route => route.fulfill({contentType: 'image/png', body: fs.readFileSync(path.join(root, 'common/tips/ampoule.png'))}));
        const sidebar = `<div class="menu-primary"><div class="menu-item"><span class="icon">A</span></div></div><div class="menu-secondary">
            <div class="omo-tips-slot" data-omo-tips-slot><button class="omo-tips-button" aria-expanded="false"><span class="icon"><img class="icon-img black-icon" src="/img/test-tip.png" alt=""></span><span class="label">Trucs et astuces</span></button></div>
            <div class="menu-item" data-hash="parameters"><span class="icon">P</span><span class="label">Parametres</span></div></div>`;
        await page.route('**/omo/tips-fixture', route => route.fulfill({contentType: 'text/html', body: `<!doctype html>
            <meta name="viewport" content="width=device-width, initial-scale=1"><base href="/omo/">
            <style>${read('common/assets/components.css')}\n${read('omo/assets/css/styles.css')}\n${read('common/notifications/notifications.css')}\n${read('common/tips/tips.css')}</style>
            <body class="view-left"><div class="app"><aside class="sidebar" id="sidebar"><div class="sidebar-toggle">Menu</div><div class="menu" id="menu_sidebar">${sidebar}</div></aside><div class="main">Contexte courant</div></div>
            <script>window.omoConfig = {oid:7,currentUserId:2};</script>
            <script>${read('common/notifications/notifications.js')}</script><script>${read('common/tips/tips.js')}</script>`}));
        await page.goto('https://localtest.me/omo/tips-fixture');
        const popup = page.locator('#omo-tips-region [data-common-notification-state="visible"]');
        await popup.waitFor({state: 'visible'});
        await page.clock.install();
        assert.equal(requests, 1);
        const link = popup.getByRole('link');
        assert.equal(new URL(await link.getAttribute('href')).pathname, '/omo/tips-fixture', 'Hash links preserve the current route despite the base tag.');
        await popup.hover();
        await page.clock.runFor(11000);
        assert.equal(await popup.count(), 1, 'Hover suspends dismissal beyond ten seconds.');
        assert.equal(await popup.locator('.common-notification__progress').evaluate(node => getComputedStyle(node).animationPlayState), 'paused');
        await link.focus();
        await page.mouse.move(800, 100);
        await page.clock.runFor(11000);
        assert.equal(await popup.count(), 1, 'Keyboard focus also suspends the countdown.');
        await link.evaluate(node => node.blur());
        await page.clock.runFor(11000);
        assert.equal(await page.locator('#omo-tips-region .common-notification').count(), 0, 'Popup fades out after the remaining time.');
        assert.equal(await page.locator('[data-omo-tips-slot]').evaluate(node => node.classList.contains('is-revealed')), false, 'Bulb slides away after dismissal.');
        await page.locator('[data-omo-tips-slot]').hover();
        assert.equal(await page.locator('[data-omo-tips-slot]').evaluate(node => node.classList.contains('is-revealed')), true);
        await page.locator('.omo-tips-button').click();
        await popup.waitFor({state: 'visible'});
        await popup.getByRole('button', {name: 'Suivant'}).click();
        await page.waitForFunction(() => document.querySelector('.omo-tips-content p')?.textContent.includes('<img'));
        assert.equal(await popup.locator('p img').count(), 0, 'Tip content is always plain text.');
        assert.equal(await popup.locator('img').count(), 1, 'Optional image is rendered.');
        assert.equal(await popup.getByRole('link').getAttribute('target'), '_blank', 'External help opens in another tab.');
        await page.clock.runFor(300);
        await page.screenshot({path: path.join(root, 'tmp/tips-desktop.png')});
        failNext = true;
        await popup.getByRole('button', {name: 'Suivant'}).click();
        await page.getByText('Impossible de charger cette astuce.').waitFor();
        assert.equal(await popup.getByRole('button', {name: 'Suivant'}).isEnabled(), true, 'Failed requests keep Next usable.');
        failNext = false;
        await page.reload();
        await page.waitForFunction(() => document.querySelector('#omo-tips-region'));
        await page.clock.runFor(1500);
        assert.equal(await popup.count(), 0, 'Reload never shows an already viewed daily tip automatically.');
        await page.locator('[data-omo-tips-slot]').hover();
        await page.locator('.omo-tips-button').click();
        await popup.waitFor({state: 'visible'});
        assert.match(await popup.locator('p').textContent(), /<img/, 'The same tip returns after reload.');
        await page.evaluate(html => { document.querySelector('#menu_sidebar').innerHTML = html; }, sidebar);
        assert.equal(await page.locator('[data-omo-tips-slot]').evaluate(node => node.classList.contains('is-revealed')), true, 'Replacing the sidebar preserves the open tip.');
        await page.setViewportSize({width: 390, height: 850});
        await page.evaluate(() => { document.body.className = 'view-menu'; });
        await page.clock.runFor(500);
        await page.waitForTimeout(300);
        const bounds = await popup.boundingBox();
        assert(bounds.x >= 0 && bounds.x + bounds.width <= 390 && bounds.y >= 0 && bounds.y + bounds.height <= 850, 'Mobile popup stays inside the viewport.');
        await page.screenshot({path: path.join(root, 'tmp/tips-mobile.png')});
        await page.keyboard.press('Escape');
        await page.clock.runFor(600);
        assert.equal(await popup.count(), 0, 'Escape dismisses the tip.');
        // A new day/browser must not consume the tip while the mobile sidebar is offscreen.
        await page.evaluate(() => { document.body.className = 'view-left'; });
        await page.waitForTimeout(300);
        await page.context().clearCookies();
        await page.reload();
        await page.waitForFunction(() => document.querySelector('#omo-tips-region'));
        await page.clock.runFor(1500);
        assert.equal(await popup.count(), 0);
        assert.equal((await page.context().cookies()).some(cookie => cookie.name === 'omo_tip_2'), false,
            JSON.stringify(await page.evaluate(() => ({cookie: document.cookie, classes: document.body.className,
                bounds: document.querySelector('[data-omo-tips-slot]').getBoundingClientRect().toJSON(),
                transform: getComputedStyle(document.getElementById('sidebar')).transform}))));
        await page.evaluate(() => { document.body.className = 'view-menu'; });
        await page.waitForTimeout(300);
        await page.clock.runFor(1500);
        await popup.waitFor({state: 'visible'});
        // The catalog uses the same form in an asynchronously loaded settings drawer.
        let submitted = '';
        await page.route('**/omo/api/parameters/tips/save.php', route => {
            submitted = route.request().postData();
            return route.fulfill({contentType: 'application/json', body: JSON.stringify({status: true, message: 'Astuce enregistree.', id: 27})});
        });
        await page.evaluate(() => {
            window.adminReloads = [];
            window.$ = () => ({parent: () => ({load: url => window.adminReloads.push(url)})});
            const host = document.createElement('div');
            host.innerHTML = `<div data-tips-admin data-oid="7"><form action="/omo/api/parameters/tips/save.php" method="post">
                <input name="text" value="Nouvelle astuce"><input name="link" value="/omo/"><input name="image" value=""><input name="active" value="1">
                <button type="submit" data-tip-save>Enregistrer</button></form><button data-tip-edit="0">Nouveau</button><button data-tip-remove-image>Retirer</button></div>`;
            document.body.append(host);
            window.croppedImages = {image: new Blob(['stale image'])};
        });
        await page.addScriptTag({content: read('omo/api/parameters/tips/index.js')});
        await page.evaluate(() => window.commonPageScripts['/omo/api/parameters/tips/index.js']({error: 'Failed'}));
        assert.equal(await page.evaluate(() => window.croppedImages.image), undefined, 'A previous editor image cannot leak into another tip.');
        await page.locator('[data-tip-save]').evaluate(button => button.click());
        await page.waitForFunction(() => window.adminReloads.length === 1);
        assert(submitted.includes('Nouvelle astuce'), 'Catalog submits its adminEdit values.');
        assert.equal(await page.evaluate(() => window.adminReloads[0]), '/omo/api/parameters/tips/index.php?oid=7&id=27');
        await page.evaluate(() => {
            document.querySelector('[data-tips-admin] [name=image]').value = '/img/test-tip.png';
            window.croppedImages.image = new Blob(['stale image']);
            document.querySelector('[data-tip-remove-image]').click();
        });
        assert.equal(await page.locator('[data-tips-admin] [name=image]').inputValue(), '');
        assert.equal(await page.evaluate(() => window.croppedImages.image), undefined);
        assert.deepEqual(errors, [], 'No browser errors.');
        console.log('tips_browser_test: OK');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
