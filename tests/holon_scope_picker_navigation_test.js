'use strict';
// node tests/holon_scope_picker_navigation_test.js /path/to/playwright /path/to/chrome
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require(process.argv[2] || 'playwright');

(async () => {
    const browser = await chromium.launch({headless: true, ...(process.argv[3] ? {executablePath: process.argv[3]} : {})});
    try {
        const page = await browser.newPage({viewport: {width: 1000, height: 800}});
        const errors = []; page.on('pageerror', error => errors.push(error.message));
        await page.setContent('<div id="host"></div>');
        await page.addStyleTag({content: fs.readFileSync(path.join(__dirname, '../common/assets/components.css'), 'utf8')});
        await page.evaluate(() => {
            const data = {ID: 1, name: 'Organization', type: '2', children: [
                {ID: 2, name: 'Parent', type: '2', children: [
                    {ID: 10, name: 'Allowed role', type: '1'},
                    {ID: 20, name: 'Other allowed role', type: '1'},
                    {ID: 99, name: 'Forbidden role', type: '1'}]}]};
            window.omoFetchStructureData = () => Promise.resolve(data);
            // Fixed packing positions isolate navigation from the D3 layout algorithm.
            window.d3 = {layout: {pack: () => {
                const pack = {padding: () => pack, size: () => pack, value: () => pack, sort: () => pack,
                    nodes: root => {
                        const parent = root.children[0], roles = parent.children;
                        Object.assign(root, {x: 200, y: 200, r: 200, depth: 0});
                        Object.assign(parent, {x: 200, y: 200, r: 100, depth: 1, parent: root});
                        roles.forEach((role, index) => Object.assign(role, {x: [200, 260, 140][index], y: 200, r: 25, depth: 2, parent}));
                        return [root, parent, ...roles];
                    }};
                return pack;
            }}};
            const arc = CanvasRenderingContext2D.prototype.arc;
            const clear = CanvasRenderingContext2D.prototype.clearRect;
            CanvasRenderingContext2D.prototype.clearRect = function (...args) { window.drawnArcs = []; return clear.apply(this, args); };
            CanvasRenderingContext2D.prototype.arc = function (x, y, radius, ...args) {
                window.drawnArcs.push({x, y, radius}); return arc.call(this, x, y, radius, ...args);
            };
        });
        await page.addScriptTag({content: fs.readFileSync(path.join(__dirname, '../common/holon_scope_picker.js'), 'utf8')});
        await page.evaluate(() => {
            window.selectionChanges = [];
            window.picker = window.omoMountHolonScopePicker({host: document.getElementById('host'), organizationId: 1,
                initialHolonId: 10, selectableHolonIds: [10, 20], showModes: false, labelMode: 'context',
                onChange: id => window.selectionChanges.push(id), onReady: () => window.ready = true});
        });
        await page.waitForFunction(() => window.ready && window.drawnArcs.length > 0);
        const canvas = page.locator('canvas');
        const initialRadius = await page.evaluate(() => window.drawnArcs[0].radius);
        async function clickContainer(offset) {
            const bounds = await canvas.boundingBox();
            const diameter = Math.min(bounds.width, bounds.height) * 0.9;
            await canvas.click({position: {x: bounds.width / 2, y: bounds.height / 2 + diameter * offset}});
        }
        await clickContainer(0.35); // Parent is navigable without creation rights.
        const parentRadius = await page.evaluate(() => window.drawnArcs[0].radius);
        assert(parentRadius < initialRadius, 'Clicking the forbidden parent must zoom out.');
        assert.equal(await page.evaluate(() => window.picker.getSelectedHolonId()), 10);
        await clickContainer(0.55); // Organization is also navigable without being selectable.
        const rootRadius = await page.evaluate(() => window.drawnArcs[0].radius);
        assert(rootRadius < parentRadius, 'Clicking the forbidden organization must zoom out again.');
        assert.deepEqual(await page.evaluate(() => window.selectionChanges), [10], 'Navigation never selects a forbidden destination.');
        const bounds = await canvas.boundingBox();
        const rootScale = Math.min(bounds.width, bounds.height) * 0.9 / (200 * 2.05);
        await canvas.click({position: {x: bounds.width / 2 - 60 * rootScale, y: bounds.height / 2}});
        assert.equal(await page.evaluate(() => window.picker.getSelectedHolonId()), 10, 'A forbidden leaf remains unselectable.');
        await canvas.click({position: {x: bounds.width / 2 + 60 * rootScale, y: bounds.height / 2}});
        assert.equal(await page.evaluate(() => window.picker.getSelectedHolonId()), 20, 'An allowed destination is still selectable.');
        assert(await page.evaluate(() => window.drawnArcs[0].radius) > rootRadius, 'Selecting an allowed destination zooms into it.');
        assert.deepEqual(errors, []);
        await page.evaluate(() => window.picker.destroy());
        console.log('holon_scope_picker_navigation_test: OK');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
