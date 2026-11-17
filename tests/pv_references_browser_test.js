'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require(process.argv[2] || 'playwright');

(async function () {
    const browser = await chromium.launch({headless: true, channel: process.env.PV_BROWSER_CHANNEL || 'msedge'});
    try {
        const page = await browser.newPage({viewport: {width: 390, height: 850}, hasTouch: true});
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.route('https://pv-fixture.invalid/**', route => route.fulfill({contentType: 'application/json',
            body: JSON.stringify({position: 'Référence {current} sur {total}', previous: 'Référence précédente', next: 'Référence suivante'})}));
        await page.goto('https://pv-fixture.invalid/');
        await page.setContent(`<!doctype html><html><body>
            <div id="drawer" style="height:650px; overflow:auto">
                <main id="pv">
                    <aside>@David (navigation copy)</aside>
                    <h3 class="omo-document-pv__point-title">Point pour @david</h3>
                    <div class="omo-document-pv__point-content">
                        <p>@David <strong>Dräyer</strong> prépare le dossier.</p>
                        <p>@David Morel et @davidm ont répondu.</p>
                        <p>x@david.ch, @davido, @@david, @dd_extra, @dd.fr.</p>
                        <p hidden>@dd invisible</p><p style="display:none">@dd invisible</p>
                        <p style="visibility:hidden">@dd invisible</p><p style="opacity:0">@dd invisible</p>
                        <div style="height:1200px"></div><p id="last">Réponse de @DD.</p>
                    </div>
                    <textarea hidden>@dd</textarea><script id="mount-script" type="application/json">{}</script>
                </main>
            </div></body></html>`);
        for (const file of ['common/assets/theme.css', 'common/assets/components.css', 'common/document/pv-references.css']) {
            await page.addStyleTag({path: path.join(__dirname, '..', file)});
        }
        await page.addScriptTag({path: path.join(__dirname, '../common/document/pv-references.js')});
        await page.evaluate(function () {
            window.referenceConfig = {rootSelector: '#pv', stage: 'review', viewerKey: 'user:1', identities: [
                {key: 'user:1', firstname: 'David', fullName: 'David Dräyer', username: 'dd'},
                {key: 'user:2', firstname: 'David', fullName: 'David Morel', username: 'davidm'}
            ]};
            window.mountReferences = function () {
                window.commonPageScripts['/common/document/pv-references.js'](window.referenceConfig, document.querySelector('#mount-script'));
            };
            window.mountReferences();
        });
        const panel = page.locator('.common-pv-references');
        const count = panel.locator('.common-pv-references__count');
        await count.getByText('Référence 1 sur 3', {exact: true}).waitFor();
        assert.equal(await page.evaluate(() => document.querySelector('#pv').pvReferences.index), 0, 'The first reference is selected initially.');
        assert.equal(await panel.evaluate(element => getComputedStyle(element).position), 'fixed');
        assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true);
        const originalContent = await page.locator('.omo-document-pv__point-content').innerHTML();
        const down = panel.getByRole('button', {name: 'Référence suivante'});
        const up = panel.getByRole('button', {name: 'Référence précédente'});
        await down.click();
        await count.getByText('Référence 2 sur 3', {exact: true}).waitFor();
        assert.equal(await page.evaluate(() => document.querySelector('#pv').pvReferences.index), 1);
        assert.equal(await page.evaluate(() => document.querySelector('#pv').pvReferences.ranges[1].toString()), '@David Dräyer');
        await down.click();
        await count.getByText('Référence 3 sur 3', {exact: true}).waitFor();
        assert.equal(await page.evaluate(() => document.querySelector('#pv').pvReferences.index), 2);
        assert(await page.locator('#last').evaluate(element => {
            const rect = element.getBoundingClientRect();
            return rect.top >= 0 && rect.bottom < innerHeight;
        }), 'Navigation reaches the reference inside the nested scroller.');
        await down.click();
        await count.getByText('Référence 1 sur 3', {exact: true}).waitFor();
        assert.equal(await page.evaluate(() => document.querySelector('#pv').pvReferences.index), 0, 'Next wraps to the first reference.');
        await up.click();
        await count.getByText('Référence 3 sur 3', {exact: true}).waitFor();
        assert.equal(await page.evaluate(() => document.querySelector('#pv').pvReferences.index), 2, 'Previous wraps to the last reference.');
        assert.equal(await page.locator('.omo-document-pv__point-content').innerHTML(), originalContent, 'Highlighting preserves stored HTML.');
        const handle = panel.locator('.common-pv-references__handle');
        const initialPanel = await panel.boundingBox();
        const initialHandle = await handle.boundingBox();
        const downBounds = await down.boundingBox();
        assert(initialHandle.x > downBounds.x + downBounds.width, 'The movement handle sits on the right.');
        await page.mouse.move(initialHandle.x + initialHandle.width / 2, initialHandle.y + initialHandle.height / 2);
        await page.mouse.down();
        await page.mouse.move(initialHandle.x + initialHandle.width / 2 - 25, initialHandle.y + initialHandle.height / 2 - 120, {steps: 6});
        await page.mouse.up();
        assert((await panel.boundingBox()).y < initialPanel.y - 100, 'Dragging moves the fixed navigator.');
        assert.equal(await handle.getAttribute('aria-expanded'), 'true', 'Dragging does not collapse the navigator.');
        assert.equal(await page.locator('.generic-resize-shield--move').count(), 0, 'The drag shield is removed after releasing the pointer.');
        await count.getByText('Référence 3 sur 3', {exact: true}).waitFor();
        await handle.click();
        assert.equal(await handle.getAttribute('aria-expanded'), 'false');
        assert.equal(await count.isVisible(), false);
        assert.equal(await up.isVisible(), false);
        assert.equal(await down.isVisible(), false);
        assert((await panel.boundingBox()).width <= (await handle.boundingBox()).width + 3, 'Only the grip remains when minimized.');
        const collapsedHandle = await handle.boundingBox();
        await page.mouse.move(collapsedHandle.x + collapsedHandle.width / 2, collapsedHandle.y + collapsedHandle.height / 2);
        await page.mouse.down();
        await page.mouse.move(-100, -100, {steps: 6});
        await page.mouse.up();
        const minimizedPanel = await panel.boundingBox();
        assert(minimizedPanel.x >= 0 && minimizedPanel.y >= 0, 'A minimized grip stays within the viewport.');
        assert.equal(await handle.getAttribute('aria-expanded'), 'false', 'The minimized grip can be dragged.');
        await handle.click();
        assert.equal(await handle.getAttribute('aria-expanded'), 'true');
        const restoredPanel = await panel.boundingBox();
        assert(restoredPanel.x >= 0 && restoredPanel.x + restoredPanel.width <= 390, 'Expanding near the edge keeps the whole navigator visible.');
        await count.getByText('Référence 3 sur 3', {exact: true}).waitFor();
        await handle.press('ArrowRight');
        await handle.press('ArrowDown');
        assert((await panel.boundingBox()).x > restoredPanel.x, 'Keyboard arrows move the focused handle.');
        await page.setViewportSize({width: 320, height: 600});
        await page.waitForFunction(() => {
            const rect = document.querySelector('.common-pv-references').getBoundingClientRect();
            return rect.left >= 0 && rect.top >= 0 && rect.right <= innerWidth && rect.bottom <= innerHeight;
        });
        assert(await panel.evaluate(element => {
            const rect = element.getBoundingClientRect();
            return rect.left >= 0 && rect.top >= 0 && rect.right <= innerWidth && rect.bottom <= innerHeight;
        }), 'The navigator stays reachable after resizing the viewport.');
        await page.setViewportSize({width: 390, height: 850});
        await page.evaluate(() => new Promise(resolve => requestAnimationFrame(resolve)));
        const touchHandle = await handle.boundingBox();
        const touchX = touchHandle.x + touchHandle.width / 2;
        const touchY = touchHandle.y + touchHandle.height / 2;
        const touchSession = await page.context().newCDPSession(page);
        await touchSession.send('Input.dispatchTouchEvent', {type: 'touchStart', touchPoints: [{x: touchX, y: touchY}]});
        await touchSession.send('Input.dispatchTouchEvent', {type: 'touchMove', touchPoints: [{x: touchX + 15, y: touchY + 90}]});
        await touchSession.send('Input.dispatchTouchEvent', {type: 'touchEnd', touchPoints: []});
        assert((await panel.boundingBox()).y > touchHandle.y + 50, 'Touch dragging moves the navigator.');
        assert.equal(await handle.getAttribute('aria-expanded'), 'true', 'Touch dragging does not minimize the panel.');
        const tapHandle = await handle.boundingBox();
        await page.touchscreen.tap(tapHandle.x + tapHandle.width / 2, tapHandle.y + tapHandle.height / 2);
        await page.waitForFunction(() => document.querySelector('.common-pv-references__handle').getAttribute('aria-expanded') === 'false', null, {timeout: 1500});
        assert.equal(await handle.getAttribute('aria-expanded'), 'false', 'A touch tap minimizes the panel.');
        await handle.press('Enter');
        assert.equal(await handle.getAttribute('aria-expanded'), 'true', 'Keyboard activation restores the panel.');
        const cancelHandle = await handle.boundingBox();
        const cancelX = cancelHandle.x + cancelHandle.width / 2;
        const cancelY = cancelHandle.y + cancelHandle.height / 2;
        await touchSession.send('Input.dispatchTouchEvent', {type: 'touchStart', touchPoints: [{x: cancelX, y: cancelY}]});
        await touchSession.send('Input.dispatchTouchEvent', {type: 'touchMove', touchPoints: [{x: cancelX - 20, y: cancelY + 30}]});
        await touchSession.send('Input.dispatchTouchEvent', {type: 'touchCancel', touchPoints: []});
        assert.equal(await page.locator('.generic-resize-shield--move').count(), 0, 'Cancelling touch dragging removes its shield.');
        assert.equal(await handle.getAttribute('aria-expanded'), 'true', 'Cancelling a drag does not minimize the panel.');
        await touchSession.detach();
        await page.evaluate(() => {
            const paragraph = document.createElement('p');
            paragraph.textContent = '@David Dra\u0308yer et @david';
            document.querySelector('.omo-document-pv__point-content').append(paragraph);
        });
        await count.getByText('Référence 3 sur 5', {exact: true}).waitFor();
        await page.evaluate(() => document.querySelector('#pv').setAttribute('data-common-pv-reference-stage', 'meeting'));
        await panel.waitFor({state: 'hidden'});
        await page.evaluate(() => document.querySelector('#pv').setAttribute('data-common-pv-reference-stage', 'validated'));
        await count.getByText('Référence 1 sur 5', {exact: true}).waitFor();
        await page.evaluate(() => document.querySelector('#drawer').hidden = true);
        await panel.waitFor({state: 'hidden'});
        // Refresh while the drawer is hidden, then reopen it.
        await page.evaluate(() => document.querySelector('#last').append(' '));
        await page.waitForTimeout(180);
        await page.evaluate(() => document.querySelector('#drawer').hidden = false);
        await panel.waitFor({state: 'visible'});
        await count.getByText('Référence 1 sur 5', {exact: true}).waitFor();
        await page.evaluate(() => {
            window.referenceConfig.viewerKey = 'user:2';
            window.mountReferences();
        });
        await count.getByText('Référence 1 sur 4', {exact: true}).waitFor();
        assert.equal(await page.evaluate(() => document.querySelector('#pv').pvReferences.ranges.some(range => range.toString().includes('Dräyer'))), false,
            'A full name suppresses the shared firstname for the other David.');
        await page.evaluate(() => {
            window.referenceConfig.identities.push({key: 'user:3', firstname: 'Jean', fullName: 'Jean Dupont', username: 'david'});
            window.mountReferences();
        });
        await count.getByText('Référence 1 sur 2', {exact: true}).waitFor();
        await page.evaluate(() => {
            window.referenceConfig.identities.pop();
            window.referenceConfig.viewerKey = 'user:1';
            window.mountReferences();
            document.querySelector('.omo-document-pv__point-content').innerHTML = '<p>@DD</p>';
            document.querySelector('.omo-document-pv__point-title').textContent = 'Titre';
        });
        await count.getByText('Référence 1 sur 1', {exact: true}).waitFor();
        await page.evaluate(() => document.querySelector('#drawer').className = 'omo-overlay-drawer is-open');
        await panel.waitFor({state: 'visible'});
        await page.evaluate(() => document.querySelector('#drawer').classList.add('is-peek'));
        await panel.waitFor({state: 'hidden'});
        await page.evaluate(() => document.querySelector('#drawer').classList.remove('is-peek'));
        await panel.waitFor({state: 'visible'});
        await page.evaluate(() => document.querySelector('#drawer').classList.remove('is-open'));
        await panel.waitFor({state: 'hidden'});
        await page.evaluate(() => document.querySelector('#drawer').classList.add('is-open'));
        await panel.waitFor({state: 'visible'});
        await page.evaluate(() => document.querySelector('.omo-document-pv__point-content').innerHTML = '<p>@David\nMorel</p>');
        await panel.waitFor({state: 'hidden'});
        await page.evaluate(() => document.querySelector('.omo-document-pv__point-content').innerHTML = '<p>@David<br>Morel</p>');
        await count.getByText('Référence 1 sur 1', {exact: true}).waitFor();
        await page.evaluate(() => document.querySelector('.omo-document-pv__point-content').textContent = 'Aucune mention');
        await panel.waitFor({state: 'hidden'});
        await page.evaluate(() => document.querySelector('#pv').remove());
        await panel.waitFor({state: 'detached'});
        assert.deepEqual(errors, []);
        console.log('pv_references_browser_test: OK (references, navigation, mouse/touch dragging, minimize/restore, keyboard, viewport bounds, drawer lifecycle)');
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
