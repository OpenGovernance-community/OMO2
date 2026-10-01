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
const start = source.indexOf('const omoStructureDataRequestCache');
const end = source.indexOf('function omoFocusStructureNode', start);
assert(start >= 0 && end > start, 'Unable to locate the structure request cache implementation.');

let requestCount = 0;
const context = {
  URL,
  Promise,
  Map,
  Error,
  window: {
    location: {
      href: 'https://example.test/omo/'
    }
  },
  fetch: function () {
    requestCount += 1;
    return Promise.resolve({
      ok: true,
      json: function () {
        return Promise.resolve({ ID: '1', children: [] });
      }
    });
  }
};

vm.runInNewContext(source.slice(start, end), context);

async function run() {
  const responses = await Promise.all([
    context.window.omoFetchStructureData('/omo/api/getStructureData.php?oid=57&cid=23'),
    context.window.omoFetchStructureData('api/getStructureData.php?oid=57')
  ]);
  assert(requestCount === 1, 'Concurrent consumers must share one HTTP request.');
  assert(responses[0] === responses[1], 'Concurrent consumers must receive the same cached payload.');

  await context.window.omoFetchStructureData('/omo/api/getStructureData.php?cid=42&oid=57');
  assert(requestCount === 1, 'A resolved response must remain cached in memory.');

  await Promise.all([
    context.window.omoFetchStructureData('/omo/api/getStructureData.php?oid=57&cid=42', { forceRefresh: true }),
    context.window.omoFetchStructureData('/omo/api/getStructureData.php?oid=57', { forceRefresh: true })
  ]);
  assert(requestCount === 2, 'Concurrent forced refreshes must also share one HTTP request.');

  await context.window.omoFetchStructureData('/omo/api/getStructureData.php?oid=58');
  assert(requestCount === 3, 'Organizations must not share cached structure data.');
  await context.window.omoFetchStructureData('/omo/api/getStructureData.php?oid=57&token=share-a');
  await context.window.omoFetchStructureData('/omo/api/getStructureData.php?token=share-a&cid=12&oid=57');
  assert(requestCount === 4, 'Parameter order must not duplicate the same share cache.');
  await context.window.omoFetchStructureData('/omo/api/getStructureData.php?oid=57&token=share-b');
  assert(requestCount === 5, 'Distinct share tokens must retain separate caches.');

  context.window.omoInvalidateStructureDataCache('/omo/api/getStructureData.php?cid=42&oid=57');
  await context.window.omoFetchStructureData('/omo/api/getStructureData.php?oid=57');
  assert(requestCount === 6, 'Invalidating with a focus ID must clear the shared organization entry.');

  // Reproduce a settings save while an older structure request is still in flight.
  const pending = [];
  context.fetch = function (url) {
    return new Promise(function (resolve) {
      pending.push({ url, resolve });
    });
  };
  function completeRequest(index, radius) {
    pending[index].resolve({
      ok: true,
      json: () => Promise.resolve({ ID: '1', displaySettings: { labelAutoMinRadius: radius } })
    });
  }

  context.window.omoInvalidateStructureDataCache();
  const staleRequest = context.window.omoFetchStructureData('/omo/api/getStructureData.php?oid=57');
  context.window.omoInvalidateStructureDataCache();
  const freshRequest = context.window.omoFetchStructureData('/omo/api/getStructureData.php?oid=57', { forceRefresh: true });
  assert(pending[1].url.includes('structure_refresh=1'), 'Forced refresh must bypass the server representation cache.');
  completeRequest(1, 3);
  await freshRequest;
  completeRequest(0, 18);
  await staleRequest;
  const cached = await context.window.omoFetchStructureData('/omo/api/getStructureData.php?oid=57&cid=12');
  assert(pending.length === 2 && cached.displaySettings.labelAutoMinRadius === 3,
    'An old response must not replace the updated display settings in the cache.');

  context.window.omoInvalidateStructureDataCache();
  const superseded = context.window.omoFetchStructureData('/omo/api/getStructureData.php?oid=57');
  const refreshed = context.window.omoFetchStructureData('/omo/api/getStructureData.php?oid=57', { forceRefresh: true });
  completeRequest(3, 5);
  await refreshed;
  completeRequest(2, 18);
  await superseded;
  const latest = await context.window.omoFetchStructureData('/omo/api/getStructureData.php?oid=57');
  assert(latest.displaySettings.labelAutoMinRadius === 5, 'Forced refresh must win over an older normal request.');

  context.window.omoInvalidateStructureDataCache();
  const abandoned = context.window.omoFetchStructureData('/omo/api/getStructureData.php?oid=57');
  context.window.omoInvalidateStructureDataCache();
  completeRequest(4, 18);
  await abandoned;
  const afterInvalidation = context.window.omoFetchStructureData('/omo/api/getStructureData.php?oid=57');
  assert(pending.length === 6, 'An invalidated pending request must not repopulate the cache.');
  completeRequest(5, 3);
  await afterInvalidation;

  process.stdout.write('structure_data_request_cache_test: OK\n');
}

run().catch(function (error) {
  process.stderr.write(String(error && error.stack ? error.stack : error) + '\n');
  process.exitCode = 1;
});
