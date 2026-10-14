'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require(process.argv[2] || 'playwright-core');
const root = path.resolve(__dirname, '..');
const fixture = JSON.parse(fs.readFileSync(path.join(root, 'tmp/scrum-browser-fixture.json'), 'utf8'));
const clean = html => html.replace(/<script\b[^>]*>[\s\S]*?<\/script>/g, '').replace(/<link\b[^>]*>/g, '');
(async () => {
    const browser = await chromium.launch({headless: true, channel: 'msedge'});
    try {
        const page = await browser.newPage({viewport: {width: 1400, height: 960}});
        await page.route('**/*', route => route.fulfill({contentType: 'application/json', body: '{}'}));
        await page.route('**/common/assets/icon-topbar-search.png', route => route.fulfill({path: path.join(root, 'common/assets/icon-topbar-search.png')}));
        await page.goto('http://scrum.test/');
        await page.setContent('<html lang="fr" data-theme="light" data-color-style="turquoise"><head></head><body><main style="height:100vh" id="host"></main></body></html>');
        for (const file of ['common/assets/theme.css', 'common/assets/theme-style-mono.css', 'common/assets/theme-style-turquoise.css', 'omo/assets/css/styles.css', 'common/assets/components.css', 'omo/api/projects/projects.css', 'omo/api/scrum/scrum.css', 'common/project-picker/project-picker.css']) {
            await page.addStyleTag({content: fs.readFileSync(path.join(root, file), 'utf8').replace(/^@import[^;]+;/gm, '')});
        }
        await page.addScriptTag({path: path.join(root, 'common/panel-view/actions.js')});
        const mount = async html => {
            await page.evaluate(html => {
                document.getElementById('host').innerHTML = html;
                window.commonPanelViewActions.mount(document.getElementById('host'), {
                    rootSelector: '.omo-panel-view', headerSelector: '.omo-panel-view__header',
                    headerMainSelector: '.omo-panel-view__header-main', actionsSelector: '[data-omo-header-actions]'
                });
                document.querySelectorAll('[data-omo-projects-preferences-pending]').forEach(el => el.removeAttribute('data-omo-projects-preferences-pending'));
            }, clean(html));
        };
        await mount(fixture.listing);
        assert.equal(await page.locator('#omo-scrum-root > header [data-common-panel-menu-toggle]').count(), 1);
        const row = await page.locator('#omo-scrum-root > header [data-omo-header-actions]').boundingBox();
        const menu = await page.locator('#omo-scrum-root > header [data-common-panel-menu-toggle]').boundingBox();
        assert.ok(menu.y >= row.y && menu.y + menu.height <= row.y + row.height + 1, 'Menu stays in the main action row');
        await page.locator('[data-common-panel-menu-toggle]').click();
        await page.getByRole('menuitem', {name: 'Archives', exact: true}).waitFor({state: 'visible'});
        await mount(fixture.running);
        assert.equal(await page.locator('.scrum-chart svg').count(), 1);
        assert.ok((await page.locator('.scrum-chart').textContent()).includes('100 %'));
        await page.evaluate(() => {
            const cards = document.querySelector('.omo-projects__column-cards');
            const card = document.querySelector('[data-omo-project-card]');
            for (let i = 0; i < 16; i++) cards.appendChild(card.cloneNode(true));
        });
        const header = page.locator('.omo-projects__column-header').first();
        const outer = page.locator('#omo-scrum-detail > .omo-panel-view__body');
        const dimensions = await page.locator('.omo-projects__column-cards').first().evaluate(node => ({height: node.clientHeight, scroll: node.scrollHeight}));
        assert.ok(dimensions.height > 960, 'Columns grow beyond the viewport with their projects');
        assert.equal(dimensions.height, dimensions.scroll, 'Columns have no internal vertical scroll');
        await outer.evaluate(node => { node.scrollTop = 750; });
        const before = await header.boundingBox();
        const outerBox = await outer.boundingBox();
        assert.ok(Math.abs(before.y - outerBox.y) <= 2, 'Column title sticks to the sprint scroll viewport');
        await outer.evaluate(node => { node.scrollTop += 220; });
        const after = await header.boundingBox();
        assert.equal(Math.round(after.y), Math.round(before.y), 'Column title stays visible as the sprint scrolls further');
        await page.screenshot({path: path.join(root, 'tmp/scrum-sticky-scroll.png')});
        await page.locator('#omo-scrum-detail > .omo-panel-view__body').evaluate(node => { node.scrollTop = 0; });
        await page.screenshot({path: path.join(root, 'tmp/scrum-desktop.png')});
        await page.evaluate(() => document.documentElement.dataset.theme = 'dark');
        await page.screenshot({path: path.join(root, 'tmp/scrum-dark.png')});
        await page.setViewportSize({width: 390, height: 844});
        await page.evaluate(() => document.documentElement.dataset.theme = 'light');
        await mount(fixture.detail);
        await page.evaluate(() => { window.resetGenericExpandedMenu = () => {}; });
        await page.addScriptTag({path: path.join(root, 'omo/api/projects/projects.js')});
        await page.locator('.omo-projects__column.is-mobile-active').waitFor({state: 'visible'});
        const overflow = await page.evaluate(() => document.documentElement.scrollWidth > innerWidth);
        assert.equal(overflow, false, 'Mobile page has no horizontal overflow');
        await page.screenshot({path: path.join(root, 'tmp/scrum-mobile.png')});
        await page.setViewportSize({width: 1100, height: 860});
        await mount(fixture.picker);
        await page.evaluate(() => {
            const picker = document.querySelector('[data-scrum-picker]');
            const nodes = JSON.parse(picker.dataset.nodes);
            const rootId = nodes[0].holonId;
            const roleId = Number(picker.dataset.cid);
            picker.dataset.cid = rootId;
            window.omoFetchStructureData = async () => ({ID: rootId, name: 'Cercle principal', type: '2', children: [{ID: roleId, name: 'Role de coordination', type: '1'}]});
            window.d3 = {layout: {pack: () => {
                const pack = {padding: () => pack, size: () => pack, value: () => pack, sort: () => pack,
                    nodes: root => { const child = root.children[0]; Object.assign(root, {x: 200, y: 200, r: 200, depth: 0}); Object.assign(child, {x: 200, y: 200, r: 90, depth: 1, parent: root}); return [root, child]; }};
                return pack;
            }}};
        });
        for (const file of ['common/holon_scope_picker.js', 'common/project-picker/project-picker.js', 'omo/api/scrum/scrum.js']) await page.addScriptTag({path: path.join(root, file)});
        await page.locator('[data-scrum-scope] canvas').waitFor({state: 'visible'});
        assert.deepEqual(await page.locator('[data-scrum-scope] .omo-scope-toggle__text').allTextContents(), ['Local', 'Enfants directs', 'Descendants']);
        await page.waitForFunction(() => document.querySelector('[data-scrum-select]').options.length > 0);
        const scopeBox = await page.locator('[data-scrum-scope]').boundingBox();
        const resultsBox = await page.locator('.common-project-picker__content').boundingBox();
        assert.ok(scopeBox.x + scopeBox.width <= resultsBox.x, 'Scope map stays beside results like the PV selector');
        await page.screenshot({path: path.join(root, 'tmp/scrum-project-picker.png')});
        await page.setViewportSize({width: 390, height: 844});
        await page.locator('[data-scrum-scope] canvas').waitFor({state: 'visible'});
        assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false, 'Shared selector fits mobile and keeps circle navigation available');
        await page.locator('[data-scrum-select]').selectOption(String(fixture.parent));
        await page.locator('[data-scrum-next]').click();
        await page.locator('[data-scrum-step="sizes"]').waitFor({state: 'visible'});
        assert.equal(await page.locator('[data-scrum-preview] select').count(), 2, 'Second step still adjusts parent and child');
        await mount(fixture.estimates);
        await page.addScriptTag({path: path.join(root, 'omo/api/scrum/scrum.js')});
        await page.locator('[data-scrum-step="sizes"]').waitFor({state: 'visible'});
        assert.equal(await page.locator('[data-scrum-step="selection"]').isVisible(), false);
        assert.equal(await page.locator('[data-scrum-remove-tree]').count(), 1);
        assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false, 'Estimate adjustment fits mobile');
        await page.screenshot({path: path.join(root, 'tmp/scrum-estimates-mobile.png')});
        await page.setViewportSize({width: 1100, height: 860});
        await page.screenshot({path: path.join(root, 'tmp/scrum-estimates-desktop.png')});
        await page.setViewportSize({width: 390, height: 844});
        await mount(fixture.running);
        const distantDestination = await page.evaluate(html => {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const destination = doc.querySelector('[data-omo-projects-cards="in_progress"]');
            const moved = destination.querySelector('[data-omo-project-card]');
            for (let i = 0; i < 8; i++) {
                const clone = moved.cloneNode(true);
                clone.dataset.projectId = String(900000 + i);
                destination.insertBefore(clone, moved);
            }
            return doc.documentElement.outerHTML;
        }, fixture.moving);
        await page.route('**/omo/api/projects/action.php*', route => route.fulfill({contentType: 'application/json', body: '{"success":true}'}));
        await page.route('**/omo/api/scrum/index.php*', route => route.fulfill({contentType: 'text/html', body: distantDestination}));
        await page.evaluate(() => {
            window.omoNotify = () => {};
            window.scrumPreservedRoot = document.querySelector('#omo-scrum-detail');
            window.scrumPreservedHeader = window.scrumPreservedRoot.querySelector('header');
        });
        await page.addScriptTag({path: path.join(root, 'omo/api/projects/projects.js')});
        await page.locator('[data-omo-project-card][data-project-id="' + fixture.child + '"] [data-omo-project-status-select]').selectOption('in_progress');
        await page.waitForFunction(id => {
            const column = document.querySelector('[data-omo-projects-column="in_progress"]');
            const card = column.querySelector('[data-omo-project-card][data-project-id="' + id + '"]');
            if (!card || !column.classList.contains('is-mobile-active') || column.querySelector('[aria-busy="true"]') || column.querySelectorAll('[data-omo-project-card]').length !== 9) return false;
            const rect = card.getBoundingClientRect();
            const header = column.querySelector('header').getBoundingClientRect();
            return rect.top >= header.bottom && rect.bottom <= innerHeight;
        }, fixture.child);
        assert.equal(await page.evaluate(() => document.querySelector('#omo-scrum-detail') === window.scrumPreservedRoot && window.scrumPreservedRoot.querySelector('header') === window.scrumPreservedHeader), true, 'Move preserves the sprint and its header');
        assert.ok(await page.locator('#omo-scrum-detail > .omo-panel-view__body').evaluate(node => node.scrollTop > 0), 'Offscreen destination scrolls into view');
        await page.screenshot({path: path.join(root, 'tmp/scrum-move-mobile.png')});
        // Project editing must cover the sprint viewport, not its growing kanban.
        await page.setViewportSize({width: 1400, height: 960});
        await mount(fixture.listing);
        await page.evaluate(html => {
            const drawer = document.querySelector('[data-scrum-drawer]');
            drawer.hidden = false; drawer.classList.add('is-open');
            drawer.querySelector('[data-scrum-drawer-body]').innerHTML = html;
            document.querySelectorAll('[data-omo-projects-preferences-pending]').forEach(node => node.removeAttribute('data-omo-projects-preferences-pending'));
            const cards = document.querySelector('[data-omo-projects-cards="ready"]');
            for (let i = 0; i < 16; i++) cards.appendChild(cards.querySelector('[data-omo-project-card]').cloneNode(true));
            document.querySelector('#omo-scrum-detail > .omo-panel-view__body').scrollTop = 750;
            window.commonRenderLoadingState = host => { host.textContent = 'Chargement...'; };
            window.commonExecuteFragmentScripts = () => Promise.resolve();
        }, clean(fixture.detail));
        await page.route('**/omo/api/projects/create.php*', route => route.fulfill({contentType: 'text/html', body: clean(fixture.projectEditor)}));
        await page.addScriptTag({path: path.join(root, 'common/drawer/subdrawer.js')});
        await page.addScriptTag({path: path.join(root, 'omo/api/projects/projects.js')});
        await page.evaluate(() => document.querySelector('[data-omo-project-action="edit"]').click());
        const projectDrawer = page.locator('[data-omo-projects-drawer]');
        await projectDrawer.locator('form').waitFor({state: 'visible'});
        await page.waitForFunction(() => document.querySelector('[data-omo-projects-drawer]').classList.contains('is-open'));
        await page.waitForFunction(() => getComputedStyle(document.querySelector('[data-omo-projects-drawer] > .omo-overlay-drawer__panel')).opacity === '1');
        for (const width of [1400, 390]) {
            await page.setViewportSize({width, height: 960});
            const sprintBounds = await page.locator('#omo-scrum-detail').boundingBox();
            const drawerBounds = await projectDrawer.boundingBox();
            assert.ok(Math.abs(drawerBounds.y - sprintBounds.y) < 2, 'Project drawer starts at the sprint top after scrolling');
            assert.ok(Math.abs(drawerBounds.height - sprintBounds.height) < 2, 'Project drawer height follows the sprint viewport');
            assert.ok(Math.abs(drawerBounds.width - sprintBounds.width) < 2, 'Project drawer covers the sprint width');
            const action = projectDrawer.locator('[data-omo-subdrawer-actions] [data-omo-project-form-submit]');
            await action.waitFor({state: 'visible'});
            const actionBounds = await action.boundingBox();
            assert.ok(actionBounds.y >= 0 && actionBounds.y + actionBounds.height < 960, 'Editor actions stay visible');
            await page.screenshot({path: path.join(root, 'tmp/scrum-project-editor-' + width + '.png')});
        }
        await projectDrawer.locator('[data-omo-projects-cancel-create]').click();
        await projectDrawer.waitFor({state: 'hidden'});
        assert.equal(await page.locator('[data-scrum-drawer]').isVisible(), true, 'Closing project editor preserves the sprint drawer');
        assert.ok(Math.abs(await page.locator('#omo-scrum-detail > .omo-panel-view__body').evaluate(node => node.scrollTop) - 750) <= 2, 'Closing preserves the sprint scroll position across resizing');
        await page.setViewportSize({width: 1100, height: 860});
        await mount(fixture.reevaluated);
        assert.ok((await page.locator('[data-scrum-summary]').textContent()).includes('Restant : 400 %'));
        assert.ok((await page.locator('.scrum-chart__axes').textContent()).includes('400 %'));
        const revisedPoint = await page.locator('.scrum-chart__endpoint').getAttribute('cy');
        const idealPath = await page.locator('.scrum-chart__target').getAttribute('d');
        assert.equal(Number(revisedPoint), 30, 'Peak above 100 percent fits inside the plot');
        assert.match(idealPath, /^M52 150 /, 'Ideal trajectory remains based on the original load');
        await page.screenshot({path: path.join(root, 'tmp/scrum-reevaluated.png')});
        console.log('Scrum layout: menu, percent chart, sticky column headers and mobile overflow passed.');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
