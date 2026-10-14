'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require(process.argv[2] || 'playwright-core');
const root = path.resolve(__dirname, '..');
const read = file => fs.readFileSync(path.join(root, file), 'utf8');

(async () => {
    const browser = await chromium.launch({headless: true, channel: 'msedge'});
    try {
        const page = await browser.newPage({viewport: {width: 1200, height: 700}});
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        // Use the actual server template for controls; only replace PHP-rendered labels.
        const template = read('omo/api/getSidebar.php');
        const controls = template.slice(template.indexOf('    <button type="button" class="menu-item sidebar-overflow-toggle"'), template.indexOf('<div class="menu-secondary">'))
            .replace(/<\?=[\s\S]*?\?>/g, 'Autres applications');
        const item = index => `<div class="menu-item" data-hash="app${index}" data-navigation-mode="drawer"><span class="icon">A</span><span class="label">Application ${index}</span></div>`;
        const markup = count => '<div class="menu-primary">' + Array.from({length: count}, (_, i) => item(i)).join('')
            + '<div class="menu-item menu-item--add" data-omo-open-app-picker="1"><span class="icon">+</span><span class="label">Gerer</span></div>'
            + controls + '<div class="menu-secondary"><div style="height:48px">Tips</div><div class="menu-item" data-hash="parameters">P</div></div>';
        await page.setContent('<html data-theme="light"><body class="view-menu"><div class="app"><aside id="sidebar" class="sidebar"><div class="sidebar-toggle">Apps</div><div id="menu_sidebar" class="menu"></div></aside><main class="main"></main></div></body></html>');
        for (const file of ['common/assets/theme.css', 'common/assets/theme-style-turquoise.css', 'omo/assets/css/styles.css', 'common/assets/components.css']) {
            await page.addStyleTag({content: read(file).replace(/^@import[^;]+;/gm, '')});
        }
        await page.addScriptTag({path: path.join(root, 'omo/assets/js/sidebar-overflow.js')});
        await page.evaluate(html => { document.getElementById('menu_sidebar').innerHTML = html; }, markup(20));
        const toggle = page.locator('.sidebar-overflow-toggle');
        const panel = page.locator('#sidebar-overflow-menu');
        const settled = () => page.evaluate(() => new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve))));
        await settled();
        const checkFit = async () => {
            const result = await page.evaluate(() => {
                const primary = document.querySelector('.menu-primary');
                const toggle = document.querySelector('.sidebar-overflow-toggle');
                const secondary = document.querySelector('.menu-secondary');
                return {scroll: primary.scrollHeight, height: primary.clientHeight, bottom: toggle.getBoundingClientRect().bottom, limit: secondary.getBoundingClientRect().top};
            });
            assert.ok(result.scroll <= result.height + 1, 'No clipped rows or scrollbar in the sidebar');
            assert.ok(result.bottom <= result.limit + 1, 'More button stays above fixed bottom entries');
        };
        await checkFit();
        const count = await page.locator('.menu-primary [data-hash]').count();
        assert.ok(count > 0 && count < 20);
        await toggle.click();
        assert.equal(await panel.evaluate(el => el.matches(':popover-open')), true);
        assert.equal(await panel.locator('.menu-item').count(), 21 - count);
        await page.keyboard.press('End');
        assert.equal(await page.evaluate(() => document.activeElement.hasAttribute('data-omo-open-app-picker')), true);
        await page.keyboard.press('Escape');
        assert.equal(await panel.isVisible(), false);
        assert.equal(await toggle.evaluate(el => el === document.activeElement), true);
        await page.evaluate(() => {
            window.clicked = null;
            window.original = document.querySelector('#sidebar-overflow-menu [data-hash]');
            document.addEventListener('click', event => {
                const entry = event.target.closest('[data-hash]');
                if (entry) window.clicked = {same: entry === window.original, sidebar: !!entry.closest('#menu_sidebar'), hash: entry.dataset.hash};
            });
        });
        await page.keyboard.press('ArrowDown');
        await page.keyboard.press('Enter');
        assert.deepEqual(await page.evaluate(() => ({same: window.clicked.same, sidebar: window.clicked.sidebar})), {same: true, sidebar: true});
        assert.equal(await panel.isVisible(), false);
        await page.setViewportSize({width: 1200, height: 1200});
        await settled();
        assert.equal(await toggle.isVisible(), false, 'All apps return when the window is tall enough');
        assert.equal(await page.locator('.menu-primary [data-hash]').count(), 20);
        assert.deepEqual(await page.locator('.menu-primary [data-hash]').evaluateAll(nodes => nodes.map(node => node.dataset.hash)), Array.from({length: 20}, (_, i) => 'app' + i));
        await page.setViewportSize({width: 1200, height: 500});
        await settled();
        await checkFit();
        await page.locator('#sidebar').evaluate(el => el.classList.add('expanded'));
        await page.waitForTimeout(300);
        await toggle.click();
        const desktop = await panel.boundingBox();
        assert.ok(desktop.x >= 200 && desktop.y + desktop.height <= 500, 'Expanded menu fits the viewport');
        await page.screenshot({path: path.join(root, 'tmp/sidebar-overflow-desktop.png')});
        await page.locator('main').click({position: {x: 600, y: 30}});
        assert.equal(await panel.isVisible(), false);
        await page.setViewportSize({width: 390, height: 650});
        await settled();
        await checkFit();
        await toggle.click();
        const mobile = await panel.boundingBox();
        assert.ok(mobile.x >= 0 && mobile.x + mobile.width <= 390 && mobile.y + mobile.height <= 650);
        const entryBox = await panel.locator('.menu-item').first().boundingBox();
        assert.ok(entryBox.width > mobile.width - 20, 'Popup rows use full width on mobile');
        await page.screenshot({path: path.join(root, 'tmp/sidebar-overflow-mobile.png')});
        await page.evaluate(() => document.documentElement.dataset.theme = 'dark');
        await page.screenshot({path: path.join(root, 'tmp/sidebar-overflow-dark.png')});
        await page.evaluate(() => document.body.className = 'view-left');
        await settled();
        assert.equal(await panel.isVisible(), false, 'Changing mobile view closes the popup');
        await page.evaluate(html => { document.getElementById('menu_sidebar').innerHTML = html; document.body.className = 'view-menu'; }, markup(2));
        await settled();
        assert.equal(await toggle.isVisible(), false, 'Async sidebar replacement recalculates overflow');
        assert.equal(await page.locator('#menu_sidebar [data-hash]').count(), 3, 'No stale or duplicated apps after refresh');
        assert.deepEqual(errors, []);
        console.log('sidebar_overflow_browser_test: OK (resize, order, navigation, keyboard, mobile, themes, refresh)');
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
