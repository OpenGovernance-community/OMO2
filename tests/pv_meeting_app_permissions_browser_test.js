'use strict';
// Generate the isolated server fixture with pv_meeting_app_permissions_test.php first.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {JSDOM} = require('jsdom');
const fixture = JSON.parse(fs.readFileSync(path.join(__dirname, '../tmp/pv-meeting-app-permissions.json'), 'utf8'));
const read = file => fs.readFileSync(path.join(__dirname, '..', file), 'utf8');
const tick = () => new Promise(resolve => setTimeout(resolve, 60));

function assertContext(value) {
    const params = typeof value === 'string' ? new URL(value, 'https://omo.test').searchParams : value;
    assert.equal(params.get('pv_meeting_document_id'), String(fixture.context.pv_meeting_document_id));
    assert.equal(params.get('pv_meeting_editor_token'), fixture.context.pv_meeting_editor_token);
}

function setup(module) {
    const dom = new JSDOM(fixture.html[module], {url: 'https://omo.test/omo/', runScripts: 'outside-only', pretendToBeVisual: true});
    const {window} = dom;
    const {document} = window;
    const requests = [];
    const refreshes = [];
    const errors = [];
    window.omoIsPvApplicationTabContext = () => true;
    window.commonRenderLoadingState = () => {};
    window.commonExecuteFragmentScripts = () => Promise.resolve();
    window.commonNotify = window.omoNotify = message => errors.push(message);
    window.resetGenericExpandedMenu = () => {};
    window.omoReplaceFetchedPanelRoot = options => {
        refreshes.push(options.url);
        return Promise.resolve(null);
    };
    window.fetch = async (url, options = {}) => {
        requests.push({url, options});
        return {ok: true, text: async () => options.method === 'POST'
            ? JSON.stringify({status: true, success: true}) : fixture.html[module + '_detail'],
        json: async () => ({status: true, success: true})};
    };
    window.eval(read('omo/assets/js/application-view-preferences.js'));
    window.omoApplicationViewPreferencesSave = () => Promise.resolve();
    const start = () => {
        window.eval(read('omo/api/' + module + '/' + (module === 'activities' ? 'activities' : module) + '.js'));
        if (module === 'stats') window.commonPageScripts['/omo/api/stats/stats.js']({texts: {}, displayItemCount: 1});
    };
    return {dom, window, document, requests, refreshes, errors, start};
}

async function projects(grouped = false) {
    const test = setup('projects');
    const {window, document, requests, refreshes} = test;
    try {
        const card = document.querySelector('[data-omo-project-card]');
        assert.equal(card.draggable, true, 'The server authorizes dragging using collective rights.');
        const target = document.querySelector('[data-omo-projects-cards="done"]');
        if (grouped) {
            // Exercise the second kanban path: a status change across holon rows.
            for (const [container, holonId] of [[card.parentElement, fixture.context.cid], [target, fixture.context.cid + 1]]) {
                const row = document.createElement('div');
                row.setAttribute('data-omo-projects-kanban-row', '');
                row.setAttribute('data-omo-projects-kanban-group-kind', 'holon');
                row.setAttribute('data-omo-projects-kanban-group-value', String(holonId));
                container.parentElement.insertBefore(row, container);
                row.appendChild(container);
            }
        }
        test.start();
        const transfer = {effectAllowed: '', dropEffect: '', value: '', setData(type, value) { this.value = value; }, getData() { return this.value; }};
        const dispatch = (element, type) => {
            const event = new window.Event(type, {bubbles: true, cancelable: true});
            Object.defineProperty(event, 'dataTransfer', {value: transfer});
            element.dispatchEvent(event);
            return event;
        };
        dispatch(card, 'dragstart');
        assert.equal(dispatch(target, 'dragover').defaultPrevented, true, 'The embedded kanban accepts the drop.');
        dispatch(target, 'drop');
        await tick();
        const mutation = requests.find(request => request.options.method === 'POST');
        assert.ok(mutation, 'Drop submits the real kanban mutation.');
        assert.equal(mutation.options.body.get('project_action'), grouped ? 'update_kanban_position' : 'update_status');
        if (grouped) {
            assert.equal(mutation.options.body.get('group_kind'), 'holon');
            assert.equal(mutation.options.body.get('target_holon_id'), String(fixture.context.cid + 1));
        }
        assert.equal(mutation.options.body.get('status'), 'done');
        assertContext(mutation.options.body);
        assertContext(refreshes.at(-1));
        document.querySelector('[data-omo-projects-filter-toggle]').click();
        document.querySelector('[data-omo-projects-view="list"]').click();
        document.querySelector('[data-omo-projects-filter-save]').click();
        await tick();
        assert.equal(new URL(refreshes.at(-1), window.location.origin).searchParams.get('project_view'), 'list');
        assertContext(refreshes.at(-1));
        // Open the real PHP-rendered detail and follow its edit action inside the PV.
        window.omoOpenProjectRoute('projects-d' + card.getAttribute('data-project-id'));
        await tick();
        document.querySelector('[data-omo-projects-open-edit-url]').click();
        await tick();
        assert.ok(requests.at(-1).url.includes('/projects/create.php?'));
        assertContext(requests.at(-1).url);
        assert.deepEqual(test.errors, []);
    } finally { test.dom.window.close(); }
}

async function stats() {
    const test = setup('stats');
    const {window, document, requests, refreshes} = test;
    try {
        test.start();
        // Filters trigger a server refresh, whose URL must keep the same collective.
        document.querySelector('[data-omo-stats-filter-toggle]').click();
        document.querySelector('[data-omo-stats-sort="alpha"]').click();
        document.querySelector('[data-omo-stats-filter-save]').click();
        await tick();
        assertContext(refreshes.at(-1));
        const detail = document.createElement('div');
        detail.innerHTML = fixture.html.stats_detail;
        const id = detail.querySelector('[data-indicator-id]').getAttribute('data-indicator-id');
        window.dispatchEvent(new window.CustomEvent('omo-stats-route-change', {detail: {indicatorId: Number(id)}}));
        await tick();
        assertContext(requests.at(-1).url);
        const edit = document.querySelector('[data-omo-stats-drawer-body] [data-omo-stats-open-editor-url]');
        assert.ok(edit, 'The collective can open indicator editing.');
        edit.click();
        await tick();
        assert.ok(requests.at(-1).url.includes('/stats/edit.php?'));
        assertContext(requests.at(-1).url);
        assert.deepEqual(test.errors, []);
    } finally { test.dom.window.close(); }
}

async function activities() {
    const test = setup('activities');
    const {window, document, requests} = test;
    try {
        test.start();
        const detail = document.createElement('div');
        detail.innerHTML = fixture.html.activities_detail;
        const edit = Array.from(detail.querySelectorAll('[data-activity-open-url]')).find(node => node.getAttribute('data-activity-open-url').includes('/edit.php?'));
        const id = new URL(edit.getAttribute('data-activity-open-url'), window.location.origin).searchParams.get('id');
        window.omoOpenActivityRoute('activities-d' + id);
        await tick();
        assertContext(requests.at(-1).url);
        const editButton = Array.from(document.querySelectorAll('[data-activity-open-url]')).find(node => node.getAttribute('data-activity-open-url').includes('/edit.php?'));
        editButton.click();
        await tick();
        assert.ok(requests.at(-1).url.includes('/activities/edit.php?'));
        assertContext(requests.at(-1).url);
        assert.deepEqual(test.errors, []);
    } finally { test.dom.window.close(); }
}

(async () => {
    await projects();
    await projects(true);
    await stats();
    await activities();
    console.log('pv_meeting_app_permissions_browser_test: OK');
})().catch(error => { console.error(error); process.exitCode = 1; });
