'use strict';

const fs = require('fs');
const path = require('path');
const vm = require('vm');

function assert(condition, message) {
  if (!condition) {
    throw new Error(message);
  }
}

const sourcePath = path.join(__dirname, '..', 'omo', 'assets', 'js', 'app.js');
const source = fs.readFileSync(sourcePath, 'utf8');
const cacheStart = source.indexOf('function omoCanReuseStoredDrawerRoute');
const cacheEnd = source.indexOf('function omoResolveDrawerContentRouteToken', cacheStart);
assert(cacheStart >= 0 && cacheEnd > cacheStart, 'Unable to locate the drawer route cache implementation.');
const restoreStart = source.indexOf('function omoRestoreCachedDrawerRoute');
const restoreEnd = source.indexOf('function omoGetMenuHashForRouteToken', restoreStart);
assert(restoreStart >= 0 && restoreEnd > restoreStart, 'Unable to locate the cached drawer route restoration implementation.');
const reopenStart = source.indexOf('function omoReopenCurrentDrawerRoute');
const reopenEnd = source.indexOf('function omoOpenSearchDocumentResult', reopenStart);
assert(reopenStart >= 0 && reopenEnd > reopenStart, 'Unable to locate the current drawer route reopening implementation.');
const start = source.indexOf('let omoRememberedDrawerRoutes');
const end = source.indexOf('let omoPendingDrawerRouteOptions', start);
assert(start >= 0 && end > start, 'Unable to locate the drawer route memory implementation.');

const context = {
  omoNormalizeHashToken: function (value) {
    const normalized = String(value || '').trim().toLowerCase();
    return normalized || null;
  },
  omoGetMenuHashForRouteToken: function (routeToken) {
    const normalized = String(routeToken || '').trim().toLowerCase();
    if (/^projects-(?:d|e)\d+$/.test(normalized) || normalized === 'projects-new') {
      return 'projects';
    }
    if (/^documents-d(?:e)?\d+$/.test(normalized)) {
      return 'documents';
    }
    return normalized || null;
  }
};

vm.createContext(context);
vm.runInContext(source.slice(cacheStart, cacheEnd), context);
vm.runInContext(source.slice(restoreStart, restoreEnd), context);
vm.runInContext(source.slice(reopenStart, reopenEnd), context);
vm.runInContext(source.slice(start, end), context);

assert(
  vm.runInContext("omoCanReuseStoredDrawerRoute(true, 'projects-d1286', '19:516', 'projects-d1286', '19:516')", context) === true,
  'An already loaded drawer route in the same holon must be reused.'
);
assert(
  vm.runInContext("omoCanReuseStoredDrawerRoute(true, 'projects-d17', '1:678', 'projects-d24', '1:678')", context) === true,
  'Two project details in the same holon must reuse the Projects drawer.'
);
assert(
  vm.runInContext("omoCanReuseStoredDrawerRoute(true, 'projects-d17', '1:678', 'documents-d24', '1:678')", context) === false,
  'Routes from different applications must not share their drawer cache.'
);
assert(
  vm.runInContext("omoCanReuseStoredDrawerRoute(true, 'projects-d1286', '19:516', 'projects-d1286', '19:517')", context) === false,
  'A drawer route must not be reused in another holon.'
);
assert(
  vm.runInContext("omoCanReuseStoredDrawerRoute(true, 'projects-d1286', '19:516', 'projects-d1286', '19:516', true)", context) === false,
  'An explicit refresh must bypass the drawer route cache.'
);

let restoredRoute = null;
context.omoDispatchSpecialDrawerRouteChange = function (routeToken, previousRouteToken) {
  restoredRoute = {routeToken, previousRouteToken};
  return true;
};
assert(
  vm.runInContext("omoRestoreCachedDrawerRoute('documents-d7662', 'documents-d7662')", context) === true
    && restoredRoute
    && restoredRoute.routeToken === 'documents-d7662'
    && restoredRoute.previousRouteToken === 'documents-d7662',
  'A cached Documents drawer must replay its detail route even when the same subdrawer was already visible.'
);
assert(
  source.includes('function omoReplayLoadedDocumentsRoute(')
    && source.includes('omoReplayLoadedDocumentsRoute(requestedRouteToken, currentRouteToken);'),
  'A Documents drawer loaded for the first time must replay its detailed route after its scripts are ready.'
);

let reopenedRoute = null;
context.omoParseHashState = function (hash) {
  return {routeToken: String(hash || '').trim() || null};
};
context.window = {
  omoOpenDrawerHashState: function (routeToken) {
    reopenedRoute = routeToken;
  }
};
assert(
  vm.runInContext("omoReopenCurrentDrawerRoute('calendar-e42', 57, 2500, {oid: 57, cid: 2500, hash: 'calendar-e42'})", context) === true
    && reopenedRoute === 'calendar-e42',
  'Reopening the current calendar event route must restore its cached drawer directly.'
);
reopenedRoute = null;
assert(
  vm.runInContext("omoReopenCurrentDrawerRoute('calendar-e43', 57, 2500, {oid: 57, cid: 2500, hash: 'calendar-e42'})", context) === false
    && reopenedRoute === null,
  'Opening another calendar event must continue through normal navigation.'
);

vm.runInContext("omoRememberDrawerRoute('projects-d1286')", context);
assert(
  vm.runInContext("omoGetRememberedDrawerRoute('projects')", context) === 'projects-d1286',
  'The detailed project route must be restored for the Projects application.'
);

vm.runInContext("omoRememberDrawerRoute('documents-de42')", context);
assert(
  vm.runInContext("omoGetRememberedDrawerRoute('documents')", context) === 'documents-de42',
  'Each application must keep its own detailed route.'
);

vm.runInContext('omoRememberDrawerRoute(null)', context);
assert(
  vm.runInContext("omoGetRememberedDrawerRoute('projects')", context) === 'projects-d1286',
  'Showing the dashboard must not discard the hidden application route.'
);

vm.runInContext("omoRememberDrawerRoute('projects')", context);
assert(
  vm.runInContext("omoGetRememberedDrawerRoute('projects')", context) === 'projects',
  'Returning to an application list must replace its previous detail route.'
);

vm.runInContext('omoResetRememberedDrawerRoutes()', context);
assert(
  vm.runInContext("omoGetRememberedDrawerRoute('projects')", context) === null
    && vm.runInContext("omoGetRememberedDrawerRoute('documents')", context) === null,
  'Changing context must clear all remembered application routes.'
);

process.stdout.write('sidebar_drawer_route_memory_test: OK\n');
