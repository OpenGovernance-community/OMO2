'use strict';
// Run with jsdom on NODE_PATH. No server or database writes.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {JSDOM} = require('jsdom');
const source = fs.readFileSync(path.join(__dirname, '../common/panel-view/actions.js'), 'utf8');
const appSource = fs.readFileSync(path.join(__dirname, '../omo/assets/js/app.js'), 'utf8');
const headerSource = appSource.slice(appSource.indexOf('let omoMobileHeaderMenuSequence'), appSource.indexOf('function omoIsExecutableScriptTag'));
const componentsSource = fs.readFileSync(path.join(__dirname, '../common/assets/components.js'), 'utf8');
const expandedMenuSource = componentsSource.slice(componentsSource.indexOf('    var expandedMenuMedia ='), componentsSource.indexOf('    function initGenericComponents'));
const configurations = [
    '<div class="omo-panel-view__header-main"><h2>Budget</h2></div>',
    '<div class="omo-panel-view__header-main"><h2>Team</h2><div data-omo-header-actions><button id="create">Create</button></div></div>',
    '<h2>Parameters</h2>',
    '<div class="omo-panel-view__header-main"><h2>Indicators</h2><div data-omo-header-actions><div class="generic-menu"><button class="generic-menu-toggle">...</button><div class="generic-menu-panel" hidden><button id="import">Import</button></div></div></div></div>'
];

async function run() {
    for (const header of configurations) {
        const dom = new JSDOM(`<div class="drawer open"><div class="drawer-content"><div class="omo-panel-view" id="app-root"><header class="omo-panel-view__header">${header}</header><div id="details"></div></div></div></div>`, {
            url: 'https://omo.test/omo/', runScripts: 'outside-only', pretendToBeVisual: true
        });
        const {window} = dom;
        const {document} = window;
        const host = document.querySelector('.drawer');
        const root = document.getElementById('app-root');
        window.eval(source);
        window.eval(headerSource);
        try {
            window.omoInitMobileHeaderMenus(root);
            window.omoInitMobileHeaderMenus(root);
            assert.equal(root.querySelectorAll('[data-common-panel-fullscreen]').length, 1, 'One fullscreen action per application, including read-only views');
            assert.equal(root.querySelectorAll('.generic-menu').length, 1, 'Existing menus are reused');
            const group = root.querySelector('.omo-panel-view__mobile-actions-menu') || root.querySelector('[data-omo-header-actions]');
            assert.equal(group.lastElementChild.classList.contains('generic-menu'), true, 'Actions menu is last, after all buttons');
            assert.equal(root.querySelector('.generic-menu-toggle').textContent, '\u22ee');
            const mobileToggle = root.querySelector('.omo-panel-view__mobile-actions-toggle');
            if (mobileToggle) assert.equal(mobileToggle.textContent, '\u22ee', 'Mobile actions use vertical dots');
            else assert.equal(root.querySelectorAll('.generic-menu-toggle').length, 1, 'A lone menu stays directly accessible on mobile');
            const action = root.querySelector('[data-common-panel-fullscreen]');
            const ownToggle = root.querySelector('[data-common-panel-menu-toggle]');
            if (ownToggle) {
                if (mobileToggle) mobileToggle.click();
                ownToggle.click();
                if (mobileToggle) assert.equal(root.querySelector('[data-omo-header-actions]').classList.contains('is-mobile-menu-open'), true, 'Nested menu keeps the responsive actions open');
                assert.equal(root.querySelector('.generic-menu-panel').hidden, false, 'New menu opens');
                document.body.click();
                assert.equal(root.querySelector('.generic-menu-panel').hidden, true, 'Outside click closes new menu');
                ownToggle.click();
                document.dispatchEvent(new window.KeyboardEvent('keydown', {key: 'Escape'}));
                assert.equal(ownToggle.getAttribute('aria-expanded'), 'false', 'Escape closes menu in normal mode');
            }
            if (root.querySelector('#create')) assert.equal(root.querySelector('#create').parentElement.classList.contains('omo-panel-view__mobile-actions-menu'), true, 'Existing actions remain in the responsive header');
            if (root.querySelector('#import')) assert.equal(root.querySelector('#import').textContent, 'Import');
            action.click();
            assert(host.classList.contains('generic-panel-fullscreen-host'));
            assert.equal(document.getElementById('details').parentElement, root, 'Details stay inside the view');
            host.classList.remove('open');
            await new Promise(resolve => window.setTimeout(resolve, 0));
            assert(!host.classList.contains('generic-panel-fullscreen-host'), 'Closing the application exits fullscreen');
        } finally { window.close(); }
    }

    const mobileDom = new JSDOM('<div class="drawer open"><div class="omo-panel-view"><header class="omo-panel-view__header"><h2>Application</h2><div data-omo-header-actions><button>Create</button></div></header></div></div>', {
        url: 'https://omo.test/omo/', runScripts: 'outside-only', pretendToBeVisual: true
    });
    try {
        const {window} = mobileDom;
        const {document} = window;
        let mediaListener;
        const media = {matches: true, addEventListener: (type, handler) => {mediaListener = handler;}};
        window.matchMedia = () => media;
        window.eval(expandedMenuSource);
        window.eval(source);
        window.eval(headerSource);
        window.omoInitMobileHeaderMenus(document);
        const panel = document.querySelector('.generic-menu-panel');
        const action = document.querySelector('[data-common-panel-fullscreen]');
        assert.equal(panel.hidden, false, 'Fullscreen is visible in the mobile action group');
        assert.equal(action.getAttribute('role'), null);
        document.querySelector('.omo-panel-view__mobile-actions-toggle').click();
        assert(document.querySelector('[data-omo-header-actions]').classList.contains('is-mobile-menu-open'), 'Inline mobile actions do not close the parent menu');
        action.click();
        assert(document.querySelector('.drawer').classList.contains('generic-panel-fullscreen-host'));
        document.dispatchEvent(new window.KeyboardEvent('keydown', {key: 'Escape'}));
        assert.equal(panel.hidden, false, 'Closing fullscreen keeps responsive menu behavior');
        media.matches = false;
        mediaListener();
        assert.equal(panel.hidden, true, 'Returning to desktop restores the dropdown');
        assert.equal(action.getAttribute('role'), 'menuitem');
    } finally { mobileDom.window.close(); }

    const tasksDom = new JSDOM('<div class="drawer open"><div class="omo-panel-view"><header class="omo-panel-view__header"><div data-omo-header-actions><button>Create task</button><div class="generic-menu generic-menu--expanded-mobile generic-panel-actions" data-activity-action-menu><button class="generic-menu-toggle" data-activity-action-menu-toggle>Menu</button><div class="generic-menu-panel" data-activity-action-menu-panel hidden><button id="archives">Archives</button></div></div></div></header></div></div>', {
        url: 'https://omo.test/omo/', runScripts: 'outside-only', pretendToBeVisual: true
    });
    try {
        const {window} = tasksDom;
        const {document} = window;
        const mobileMedia = {matches: false, addEventListener: () => {}};
        let headerListener;
        const headerMedia = {matches: true, addEventListener: (type, handler) => {headerListener = handler;}};
        window.matchMedia = query => query.includes('768px') ? mobileMedia : headerMedia;
        window.eval(expandedMenuSource);
        window.eval(source);
        window.eval(headerSource);
        window.omoInitMobileHeaderMenus(document);
        window.omoInitMobileHeaderMenus(document);
        const panel = document.querySelector('[data-activity-action-menu-panel]');
        assert.equal(panel.hidden, false, 'Task actions are inline at the responsive header breakpoint, including tablet widths');
        assert.equal(panel.getAttribute('role'), 'group');
        assert.equal(document.querySelector('#archives').getAttribute('role'), null);
        const activitySource = fs.readFileSync(path.join(__dirname, '../omo/api/activities/activities.js'), 'utf8');
        const closeStart = activitySource.indexOf('    function closeActionMenu()');
        const closeEnd = activitySource.indexOf("    document.addEventListener('click'", closeStart);
        window.eval('var root = document.querySelector(".omo-panel-view");\n' + activitySource.slice(closeStart, closeEnd));
        window.closeActionMenu();
        assert.equal(panel.hidden, false, 'Closing task menus must retain their inline actions on mobile');
        headerMedia.matches = false;
        headerListener();
        assert.equal(panel.hidden, true, 'Desktop restores the task dropdown');
        assert.equal(document.querySelector('#archives').getAttribute('role'), 'menuitem');
        mobileMedia.matches = true;
        headerMedia.matches = true;
        headerListener();
        assert.equal(panel.hidden, false, 'Returning to phone widths restores the single menu');
    } finally { tasksDom.window.close(); }

    const dom = new JSDOM('<div class="drawer open"><div id="structure" data-common-panel-view><div class="structure-actions" data-common-panel-actions-menu><button data-common-panel-actions-toggle>Menu</button><div data-common-panel-actions-panel><button id="export">Export</button></div></div></div></div>', {
        url: 'https://omo.test/omo/', runScripts: 'outside-only', pretendToBeVisual: true
    });
    const {window} = dom;
    const {document} = window;
    try {
        window.eval(source);
        window.commonPanelViewActions.mount(document, {getHost: root => root.closest('.drawer')});
        const panel = document.querySelector('[data-common-panel-actions-panel]');
        document.querySelector('[data-common-panel-fullscreen]').click();
        assert.equal(panel.hidden, false, 'Structure menu keeps its existing visibility mechanism');
        assert.equal(document.getElementById('export').textContent, 'Export', 'Structure actions remain intact');
        assert(document.querySelector('.drawer').classList.contains('generic-panel-fullscreen-host'));
        document.dispatchEvent(new window.KeyboardEvent('keydown', {key: 'Escape'}));
        assert(!document.querySelector('.drawer').classList.contains('generic-panel-fullscreen-host'));
    } finally { window.close(); }
    console.log('panel_view_actions_test: OK');
}
run().catch(error => { console.error(error); process.exitCode = 1; });
