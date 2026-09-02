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
