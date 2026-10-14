'use strict';
// NODE_PATH may point to jquery. Pass Playwright and Chrome paths as arguments.
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

(async () => {
    const browser = await chromium.launch({headless: true, ...(process.argv[3] ? {executablePath: process.argv[3]} : {})});
    try {
        const failures = [];
        for (const width of [1400, 390]) {
            const page = await browser.newPage({viewport: {width, height: 900}});
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.setContent(`<!doctype html><div class="app"><main class="main"><div class="topbar"></div><div class="content">
                <div class="panel panel-left" id="panel-left">Structure</div><div id="resizer" style="width:5px"></div><div class="panel panel-right">Dashboard</div>
                <div class="drawer open" id="drawer_projects"><div class="drawer-content">
                    <div class="omo-overlay-drawer is-open" id="subdrawer"><div class="omo-overlay-drawer__backdrop"></div>
                        <div class="omo-overlay-drawer__panel"><header class="generic-drawer-header">Project editor</header>
                            <div class="omo-overlay-drawer__body"><div style="height:1000px">Earlier fields</div><form id="editor"><input name="title"></form></div>
                        </div></div>
                </div></div><div class="drawer" id="drawer_stats"><div class="drawer-content">Reference information</div></div>
            </div></main></div>`);
            await page.addStyleTag({content: read('common/assets/components.css')});
            await page.addStyleTag({content: read('omo/assets/css/styles.css')});
            await page.addScriptTag({path: require.resolve('jquery')});
            await page.evaluate(() => {
                window.fetch = async () => ({ok: true, json: async () => ({})});
                window.omoNormalizeDrawerId = id => id;
                window.omoNormalizeHashToken = token => token || null;
                window.clearDrawerRemoval = () => {};
            });
            await page.addScriptTag({content: 'let omoLastNonMenuView = "right";'
                + extract('function omoSetAppView(', 'function omoGetCurrentAppView(')
                + extract('function omoIsMobileLayout(', 'function clearDrawerRemoval(')
                + extract('function omoRevealUnsavedEditor(', 'function omoHasUnsavedChanges(')});
            await page.addScriptTag({content: read('common/drawer/subdrawer.js')});
            await page.evaluate(() => {
                omoSetAppView('menu');
                updateDrawerPosition($('#drawer_projects'));
                updateDrawerPosition($('#drawer_stats'));
            });
            await page.locator('[name="title"]').fill('Preserved draft');
            await page.evaluate(() => {
                window.originalForm = document.getElementById('editor');
                $('#drawer_projects').removeClass('open');
                $('#drawer_stats').addClass('open');
                omoSetAppView('right');
            });
            await page.waitForTimeout(300);
            await page.evaluate(() => commonDrawerRevealEditor(document.getElementById('editor'), {syncRoute:false}));
            await page.waitForTimeout(400);
            const geometry = await page.evaluate(() => {
                const rect = element => { const box = element.getBoundingClientRect(); return {left:box.left, right:box.right, width:box.width}; };
                return {content:rect(document.querySelector('.content')), left:rect(document.getElementById('panel-left')),
                    drawer:rect(document.getElementById('drawer_projects')), panel:rect(document.querySelector('#subdrawer > .omo-overlay-drawer__panel')),
                    scrollLeft:document.querySelector('.content').scrollLeft, rootScrollLeft:document.scrollingElement.scrollLeft,
                    bodyClass:document.body.className, sameForm:document.getElementById('editor') === window.originalForm};
            });
            if (geometry.scrollLeft !== 0 || geometry.rootScrollLeft !== 0) failures.push(width + ': revealing must not scroll the application shell horizontally');
            const expectedLeft = width > 768 ? geometry.left.right + 5 : geometry.content.left + 36;
            if (Math.abs(geometry.drawer.left - expectedLeft) > 2) failures.push(width + ': the application drawer must retain its normal column');
            if (geometry.panel.left < geometry.drawer.left - 2 || geometry.panel.right > width + 2) failures.push(width + ': the subdrawer must remain inside the visible application drawer');
            if (width <= 768 && !geometry.bodyClass.includes('view-menu')) failures.push('Mobile reveal must select the application view');
            assert.equal(geometry.sameForm, true);
            assert.equal(await page.locator('[name="title"]').inputValue(), 'Preserved draft');
            assert.deepEqual(errors, []);
            await page.close();
        }
        assert.deepEqual(failures, []);
        console.log('subdrawer_reveal_layout_test: OK');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
