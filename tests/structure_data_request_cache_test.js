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

Promise.all([
  context.window.omoFetchStructureData('/omo/api/getStructureData.php?oid=57'),
  context.window.omoFetchStructureData('api/getStructureData.php?oid=57')
]).then(function (responses) {
  assert(requestCount === 1, 'Concurrent consumers must share one HTTP request.');
  assert(responses[0] === responses[1], 'Concurrent consumers must receive the same cached payload.');

  return context.window.omoFetchStructureData('/omo/api/getStructureData.php?oid=57');
}).then(function () {
  assert(requestCount === 1, 'A resolved response must remain cached in memory.');

  return Promise.all([
    context.window.omoFetchStructureData('/omo/api/getStructureData.php?oid=57', { forceRefresh: true }),
    context.window.omoFetchStructureData('/omo/api/getStructureData.php?oid=57', { forceRefresh: true })
  ]);
}).then(function () {
  assert(requestCount === 2, 'Concurrent forced refreshes must also share one HTTP request.');
  process.stdout.write('structure_data_request_cache_test: OK\n');
}).catch(function (error) {
  process.stderr.write(String(error && error.stack ? error.stack : error) + '\n');
  process.exitCode = 1;
});
