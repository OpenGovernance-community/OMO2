'use strict';
// Run with jsdom on NODE_PATH. No server or database writes.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {JSDOM} = require('jsdom');
const source = fs.readFileSync(path.join(__dirname, '../omo/api/projects/projects.js'), 'utf8');
const preferencesSource = fs.readFileSync(path.join(__dirname, '../omo/assets/js/application-view-preferences.js'), 'utf8');
const savedView = {scope: 'contextual', assignment: 'mine', view: 'list', sort: 'priority'};
const choices = {
    scope: ['contextual', 'children', 'descendants'],
    assignment: ['mine', 'spaces', 'followed', 'all'],
    view: ['kanban', 'list', 'gantt'],
    sort: ['planned', 'priority', 'importance', 'holon']
};

for (const changes of [
    {assignment: 'all'},
    {view: 'kanban'},
    {sort: 'importance'},
    {assignment: 'all', view: 'kanban', sort: 'importance'},
    {scope: 'descendants', assignment: 'followed', view: 'gantt', sort: 'holon'}
]) {
    for (const restore of [false, true]) {
        const target = {...savedView, ...changes};
        const dom = new JSDOM('', {url: 'https://omo.test/omo/', runScripts: 'outside-only', pretendToBeVisual: true});
        const {window} = dom;
        const {document} = window;
        const requests = [];
        const pending = [];
        window.resetGenericExpandedMenu = () => {};
        window.eval(preferencesSource);
        window.omoReplaceFetchedPanelRoot = options => {
            requests.push(options.url);
            pending.push(options);
            return Promise.resolve();
        };
        const render = url => {
            const params = new URL(url, window.location.origin).searchParams;
            // The PHP resolver uses the saved view whenever a URL field is absent.
            const view = Object.fromEntries(Object.entries(savedView).map(([key, fallback]) => [key, params.get('project_' + key) ?? fallback]));
            document.body.innerHTML = `<div id="omo-projects-root" data-omo-projects-oid="109" data-omo-projects-cid="5156"
                data-omo-projects-current-url="${url}" data-omo-projects-preferences-pending="1" aria-busy="true"
                data-omo-projects-default-sort="importance" data-omo-projects-list-sort="${view.sort}"
                ${Object.entries(view).map(([key, value]) => `data-omo-projects-${key}="${value}"`).join(' ')}>
                <div data-omo-projects-filter-control><button data-omo-projects-filter-toggle>Filters</button>
                    <div data-omo-projects-filter-panel hidden>
                        ${Object.entries(choices).map(([key, values]) => values.map(value => `<button data-omo-projects-${key}="${value}">${value}</button>`).join('')).join('')}
                        <button data-omo-projects-filter-apply>Apply</button>
                    </div>
                </div>
            </div>`;
            document.getElementById('omo-projects-root').setAttribute('data-omo-app-view-preferences', JSON.stringify({personalView: savedView}));
            window.eval(source);
        };
        try {
            if (restore) {
                window.sessionStorage.setItem('omo.projects.session-views.v1', JSON.stringify({'109:5156': target}));
            }
            render('/omo/api/projects/index.php?oid=109&cid=5156&lang=fr');
            if (!restore) {
                assert.equal(requests.length, 0, 'The saved server view is already applied');
                document.querySelector('[data-omo-projects-filter-toggle]').click();
                for (const [key, value] of Object.entries(target)) {
                    document.querySelector(`button[data-omo-projects-${key}="${value}"]`).click();
                }
                document.querySelector('[data-omo-projects-filter-apply]').click();
            }
            for (let count = 0; pending.length && count < 3; count++) {
                const request = pending.shift();
                request.beforeReplace();
                render(request.url);
            }
            assert.equal(requests.length, 1, `View must settle after one refresh: ${JSON.stringify({target, restore})}`);
            const root = document.getElementById('omo-projects-root');
            for (const [key, value] of Object.entries(target)) {
                const attribute = key === 'sort' ? 'list-sort' : key;
                assert.equal(root.getAttribute('data-omo-projects-' + attribute), value, 'Rendered filters match the selection');
            }
            assert.equal(root.hasAttribute('data-omo-projects-preferences-pending'), false, 'The projects are visible after restoration');
            assert.equal(root.hasAttribute('aria-busy'), false, 'Restoration has finished');
        } finally {
            window.close();
        }
    }
}
console.log('projects_view_filters_test: OK');
