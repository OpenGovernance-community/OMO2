'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync(require('node:path').join(__dirname, '../omo/assets/js/app.js'), 'utf8');
const context = vm.createContext({
    Date, Number, String, Array, Object, encodeURIComponent, window: {},
    omoNormalizeHashToken: value => String(value || ''),
    omoResolveAppUrl: url => url,
    omoBuildPopupToken: () => 'search',
});
function include(name) {
    const start = source.indexOf('function ' + name + '(');
    const end = source.indexOf('\nfunction ', start + 1);
    assert(start >= 0 && end > start, name);
    vm.runInContext(source.slice(start, end), context);
}
for (const name of ['omoNormalizeRouteCid', 'omoNormalizeSearchPopupContext', 'omoNormalizeSearchPopupScopes', 'omoNormalizeSearchPopupDate', 'omoNormalizeSearchPopupEntry', 'omoResolveSearchPopupRoute']) include(name);
for (const archived of [false, true]) {
    const entry = context.omoNormalizeSearchPopupEntry({query: 'Needle', scopes: ['documents'], oid: 5, cid: 9, archived});
    assert.equal(entry.archived, archived, 'Stored search must retain its exclusive partition');
    context.omoGetSearchPopupPendingState = () => entry;
    context.omoGetSearchPopupJobState = () => null;
    const route = context.omoResolveSearchPopupRoute(null, {oid: 5, cid: 9});
    const url = new URL(route.url, 'http://localhost');
    assert.equal(url.searchParams.get('archive'), archived ? '1' : '0');
    assert.equal(url.searchParams.get('q'), 'Needle');
    assert.deepEqual(url.searchParams.getAll('scopes[]'), ['documents']);
    context.omoGetSearchPopupJobState = () => ({...entry, jobId: 42, jobToken: 'saved-token'});
    const restored = new URL(context.omoResolveSearchPopupRoute(42, {oid: 5, cid: 9}).url, 'http://localhost');
    assert.equal(restored.searchParams.get('archive'), archived ? '1' : '0');
    assert.equal(restored.searchParams.get('restore_job_id'), '42');
}
assert.equal(context.omoNormalizeSearchPopupEntry({query: 'Old search', oid: 5}).archived, false);
console.log('search_archive_state_test: OK');
