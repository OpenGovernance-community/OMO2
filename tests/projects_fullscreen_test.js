'use strict';
// Run with jsdom on NODE_PATH. No server or database writes.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {JSDOM} = require('jsdom');
const source = fs.readFileSync(path.join(__dirname, '../omo/api/projects/projects.js'), 'utf8');
const actionsSource = fs.readFileSync(path.join(__dirname, '../common/panel-view/actions.js'), 'utf8');

async function run() {
    for (const native of ['supported', 'unavailable', 'rejected']) {
        const dom = new JSDOM('<div class="drawer open"><div class="drawer-content"></div></div><div id="shared-modal"></div>', {
            url: 'https://omo.test/omo/', runScripts: 'outside-only', pretendToBeVisual: true
        });
        const {window} = dom;
        const {document} = window;
        window.resetGenericExpandedMenu = () => {};
        const host = document.querySelector('.drawer');
        const content = document.querySelector('.drawer-content');
        window.eval(actionsSource);
        let fullscreenElement = null;
        let requested = 0;
        let exited = 0;
        Object.defineProperty(document, 'fullscreenElement', {get: () => fullscreenElement});
        const tick = () => new Promise(resolve => window.setTimeout(resolve, 0));
        if (native !== 'unavailable') {
            document.documentElement.requestFullscreen = () => {
                requested++;
                if (native === 'rejected') return Promise.reject(new Error('unsupported'));
                fullscreenElement = document.documentElement;
                document.dispatchEvent(new window.Event('fullscreenchange'));
                return Promise.resolve();
            };
            document.exitFullscreen = () => {
                exited++;
                fullscreenElement = null;
                document.dispatchEvent(new window.Event('fullscreenchange'));
                return Promise.resolve();
            };
        }
        const render = (url = '/omo/api/projects/index.php?oid=828') => {
            const params = new URL(url, window.location.origin).searchParams;
            const values = {view: 'kanban', scope: 'contextual', assignment: 'all', sort: 'planned'};
            content.innerHTML = `<div id="omo-projects-root" class="omo-panel-view" data-omo-projects-current-url="${url}"
                ${Object.entries(values).map(([key, fallback]) => `data-omo-projects-${key === 'sort' ? 'list-sort' : key}="${params.get('project_' + key) || fallback}"`).join(' ')}>
                <header class="omo-panel-view__header"><div data-omo-header-actions>
                    <div class="generic-menu" data-omo-projects-header-menu>
                        <button class="generic-menu-toggle" data-omo-projects-header-menu-toggle>Menu</button>
                        <div class="generic-menu-panel" data-omo-projects-header-menu-panel hidden><button>Archives</button></div>
                    </div>
                </div></header>
                <div data-omo-projects-filter-control><button data-omo-projects-filter-toggle>Filters</button>
                    <div data-omo-projects-filter-panel hidden><button data-omo-projects-view="gantt">Gantt</button>
                    <button data-omo-projects-filter-apply>Apply</button></div>
                </div>
            </div>`;
            window.eval(source);
            window.commonPanelViewActions.mount(document.getElementById('omo-projects-root'), {
                rootSelector: '.omo-panel-view', headerSelector: '.omo-panel-view__header', actionsSelector: '[data-omo-header-actions]',
                getHost: root => root.closest('.drawer')
            });
        };
        window.omoReplaceFetchedPanelRoot = options => {
            options.beforeReplace();
            render(options.url);
            return Promise.resolve(document.getElementById('omo-projects-root'));
        };
        const click = selector => document.querySelector(selector).click();
        try {
            render();
            click('[data-omo-projects-header-menu-toggle]');
            click('[data-common-panel-fullscreen]');
            await tick();
            assert(host.classList.contains('generic-panel-fullscreen-host'));
            assert.equal(document.querySelector('[data-omo-projects-header-menu-panel]').hidden, true);
            assert.equal(document.querySelector('[data-common-panel-fullscreen]').textContent, 'Quitter le plein \u00e9cran');
            assert.equal(document.getElementById('shared-modal').parentElement, document.body, 'Shared dialogs stay in the fullscreen document');
            click('[data-omo-projects-filter-toggle]');
            click('[data-omo-projects-view="gantt"]');
            click('[data-omo-projects-filter-apply]');
            await tick();
            assert(host.classList.contains('generic-panel-fullscreen-host'), 'Filter reload keeps fullscreen');
            assert.equal(requested, native === 'unavailable' ? 0 : 1, 'Reload does not request fullscreen again');
            document.dispatchEvent(new window.KeyboardEvent('keydown', {key: 'Escape', bubbles: true}));
            assert(!host.classList.contains('generic-panel-fullscreen-host'), 'Escape restores normal layout after reload');
            assert.equal(document.querySelector('[data-common-panel-fullscreen]').textContent, 'Plein \u00e9cran');
            assert.equal(exited, native === 'supported' ? 1 : 0);
            click('[data-common-panel-fullscreen]');
            await tick();
            click('[data-common-panel-fullscreen]');
            assert(!host.classList.contains('generic-panel-fullscreen-host'), 'Menu can exit fullscreen');
            click('[data-common-panel-fullscreen]');
            await tick();
            if (native === 'supported') {
                fullscreenElement = null;
                document.dispatchEvent(new window.Event('fullscreenchange'));
                assert(!host.classList.contains('generic-panel-fullscreen-host'), 'Browser fullscreen exit restores layout');
                click('[data-common-panel-fullscreen]');
                await tick();
            }
            host.remove();
            await tick();
            assert(!host.classList.contains('generic-panel-fullscreen-host'), 'Leaving the application cleans up fullscreen');
            assert.equal(fullscreenElement, null);
        } finally {
            window.close();
        }
    }
    console.log('projects_fullscreen_test: OK');
}
run().catch(error => {console.error(error); process.exitCode = 1;});
