'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const source = fs.readFileSync(path.join(__dirname, '../omo/assets/js/app.js'), 'utf8');
function extract(start, end) {
    const first = source.indexOf(start);
    const last = source.indexOf(end, first);
    assert(first >= 0 && last > first, 'Missing source segment: ' + start);
    return source.slice(first, last);
}
const apps = {
    documents: ['documents', 'document_scope'],
    decision: ['decision', 'decision_scope'],
    calendar: ['calendar', 'scope'],
    stats: ['stats', 'stats_scope'],
    projects: ['projects', 'project_scope'],
    team: ['team', 'team_scope'],
    processus: ['processes', 'checklist_scope'],
    activities: ['activities', 'activity_scope'],
    policy: ['policy', 'policy_range'],
};
let route;
let handler;
let opened;
let specialEvents;
const noop = () => {};
const context = {
    document: {},
    window: {omoConfig: {rootHolonId: 1}, dispatchEvent: noop},
    CustomEvent: class { constructor(type, options) { this.type = type; this.detail = options.detail; } },
    parseUrl: () => route,
    omoNormalizeHashToken: value => value || null,
    omoParseHashState: hash => ({routeToken: hash || null, popupToken: null, popupKey: null, popupId: null}),
    omoGetMenuHashForRouteToken: token => token ? token.split('-')[0] : null,
    omoNormalizeDrawerId: value => value,
    omoNormalizeRouteCid: value => Number(value) || null,
    omoResolveAppUrl: value => value,
    omoResolveSpecialDrawerRoute: () => null,
    getSidebarMenuItem: token => ({length: 1, attr: name => ({'data-drawer': 'drawer_' + token, 'data-url': 'api/' + apps[token][0] + '/index.php'})[name]}),
    $: target => target === context.document ? {on: (event, selector, callback) => { handler = callback; }} : {attr: name => target[name]},
    navigate: (oid, cid, hash) => { route = {oid, cid, hash}; context.handleRoute(); },
    omoSetDrawerHashState: state => { route = {...route, hash: state.routeToken}; context.handleRoute(); },
    omoConfirmRouteChange: () => true,
    omoEnsurePopupBootstrapState: noop,
    omoResetRememberedDrawerRoutes: noop,
    omoRememberDrawerRoute: noop,
    updateActiveMenu: noop,
    loadContent: noop,
    omoGetLeftPanelContentSelector: () => '#left',
    omoRefreshMainRightPanel: noop,
    omoIsShareMode: () => false,
    omoEnsureMainRightPanelCurrent: noop,
    omoClosePopupModalFromRoute: noop,
    omoStoreDrawerContentRoute: noop,
    omoFocusStructureNode: noop,
    omoDispatchSpecialDrawerRouteChange: () => { specialEvents++; },
    resetDrawers: noop,
    closeAllDrawers: noop,
    refreshDrawer: () => false,
    openDrawer: (id, url, options) => { opened = {id, url, options}; },
};
vm.createContext(context);
vm.runInContext(
    extract('let omoPendingDrawerRouteOptions', 'function omoResolveSpecialDrawerRoute(')
    + extract('function getSidebarMenuConfig(', 'function omoDetectStructureAvailabilityFromSidebar(')
    + extract('function buildDrawerUrl(', 'function omoParseReusableDrawerUrl(')
    + extract('function handleRoute()', 'function activateMenu(')
    + extract('window.omoOpenDrawerHashState = function', 'window.omoSetPopupHashState =')
    + extract("$(document).on('click', '[data-omo-personal-space-route-token]'", "$(document).on('click', '[data-omo-personal-space-document-url]'"), context);

for (const [app, [directory, parameter]] of Object.entries(apps)) {
    for (const scope of ['contextual', 'children', 'descendants']) {
        for (const audience of ['all', 'mine', 'roles']) {
        for (const scenario of ['parent', 'organization', 'same-app', 'other-app-cached', 'detail-cached']) {
            const targetId = scenario === 'organization' ? 1 : (scenario === 'parent' ? 2 : 3);
            const hash = scenario === 'same-app' ? app : (scenario === 'detail-cached' ? app + '-d42' : (app === 'team' ? 'documents' : 'team'));
            route = {oid: 10, cid: 3, hash};
            context.currentState = {...route, routeToken: hash, popupToken: null};
            opened = null;
            specialEvents = 0;
            handler.call({'data-omo-personal-space-route-token': app,
                'data-omo-personal-space-audience': audience,
                'data-omo-personal-space-context-holon-id': String(targetId),
                'data-omo-personal-space-forced-scope': scope}, {preventDefault: noop});
            assert(opened, app + '/' + scenario + ' must open the selected application');
            const url = new URL(opened.url, 'http://omo.test/');
            assert.equal(url.pathname, '/api/' + directory + '/index.php');
            assert.equal(url.searchParams.get('cid'), String(targetId));
            assert.equal(url.searchParams.get(parameter), scope, app + '/' + scenario + ' must retain the requested scope');
            assert.equal(url.searchParams.get('dashboard_context'), '1');
            const assignments = {stats: ['stats_assignment', audience], projects: ['project_assignment', audience === 'roles' ? 'spaces' : audience], activities: ['activity_assignment', audience === 'roles' ? 'spaces' : audience], processus: ['checklist_assignment', audience]};
            if (assignments[app]) assert.equal(url.searchParams.get(assignments[app][0]), assignments[app][1]);
            if (app === 'policy') assert.equal(url.searchParams.get('policy_scope'), 'contextual');
            assert.equal(specialEvents, 0, 'A forced dashboard scope must not merely close the previous detail');
            if (targetId === 3) assert.equal(opened.options.forceReload, true, 'A cached drawer must apply the dashboard filter');
        }
        }
    }
}
console.log('dashboard_module_navigation_test: OK (9 applications, 3 scopes, audiences, parent/organization/current roots and cached drawers)');
