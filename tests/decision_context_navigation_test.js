'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '..', 'omo/assets/js/app.js'), 'utf8');
function extract(start, end) {
    const first = source.indexOf(start);
    const last = source.indexOf(end, first);
    assert(first >= 0 && last > first, 'Cannot locate ' + start);
    return source.slice(first, last);
}

let route = {oid: 7, cid: null, hash: 'decision'};
let structureSelection = 11;
const requests = [];
const noop = () => {};
const context = {
    window: {
        omoConfig: {rootHolonId: 11},
        omoGetCurrentStructureHolonId: () => structureSelection,
        dispatchEvent: noop
    },
    CustomEvent: function () {},
    currentState: {oid: null, cid: null, hash: null, routeToken: null, popupToken: null},
    parseUrl: () => route,
    omoParseHashState: hash => ({routeToken: hash, popupToken: null}),
    omoConfirmDiscardChanges: () => true,
    getSidebarMenuConfig: () => ({drawer: 'drawer_decisions', url: 'api/decision/index.php'}),
    omoNormalizeDrawerId: id => id,
    omoNormalizeDrawerForcedScope: scope => scope || '',
    omoResolveAppUrl: url => url,
    omoGetMenuHashForRouteToken: token => token && token.split('-')[0],
    refreshDrawer: (id, url) => { requests.push(url); return true; },
    openDrawer: () => { throw new Error('An existing decision drawer must refresh in place.'); },
    omoEnsurePopupBootstrapState: noop,
    omoResetRememberedDrawerRoutes: noop,
    omoRememberDrawerRoute: noop,
    updateActiveMenu: noop,
    loadContent: noop,
    omoGetLeftPanelContentSelector: noop,
    omoRefreshMainRightPanel: noop,
    omoIsShareMode: () => false,
    resetDrawers: noop,
    omoEnsureMainRightPanelCurrent: noop,
    omoClosePopupModalFromRoute: noop,
    omoClearPendingDrawerRouteOptions: noop
};
vm.createContext(context);
vm.runInContext(
    extract('function omoNormalizeRouteCid(', 'function buildOmoUrl(')
        + extract('function buildDrawerUrl(', 'function omoParseReusableDrawerUrl(')
        + extract('function handleRoute()', 'function activateMenu('),
    context
);

function currentRequestCid() {
    return new URL(requests.at(-1), 'https://localtest.me/omo/').searchParams.get('cid');
}

// The Structure view can remain mounted on the anchoring circle while navigation
// returns to the organization. Its selection must not affect the decision query.
vm.runInContext('handleRoute()', context);
assert.equal(currentRequestCid(), null, 'Initial organization context must omit cid.');
for (let i = 0; i < 2; i++) {
    structureSelection = 48;
    route = {oid: 7, cid: '48', hash: 'decision'};
    vm.runInContext('handleRoute()', context);
    assert.equal(currentRequestCid(), '48', 'Anchoring circle must request its own decisions.');

    route = {oid: 7, cid: null, hash: 'decision'};
    vm.runInContext('handleRoute()', context);
    assert.equal(currentRequestCid(), null, 'Returning to the organization must not reuse the anchoring circle.');
}
assert.equal(requests.length, 5, 'Every context change must refresh the decision drawer.');

for (const cid of ['null', '0', '11']) {
    const url = vm.runInContext(`buildDrawerUrl('api/decision/index.php', 7, ${cid}, {forcedScope:'contextual'})`, context);
    const query = new URL(url, 'https://localtest.me/omo/').searchParams;
    assert.equal(query.get('cid'), null, 'Organization context must stay at the organization.');
    assert.equal(query.get('decision_scope'), 'contextual', 'Local scope must be preserved.');
}
assert.equal(
    vm.runInContext("buildDrawerUrl('api/documents/index.php', 7, 48)", context),
    'api/documents/index.php?oid=7&cid=48',
    'Other application contexts must remain unchanged.'
);
console.log('decision_context_navigation_test: OK');
