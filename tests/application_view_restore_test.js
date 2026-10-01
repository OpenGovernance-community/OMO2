'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const source = fs.readFileSync(path.join(__dirname, '../omo/assets/js/application-view-preferences.js'), 'utf8');
const session = new Map();
const local = new Map();
const window = {
    location: {href: 'https://org1.localtest.me/omo/', origin: 'https://org1.localtest.me'},
    omoConfig: {oid: 1, rootHolonId: 674},
    sessionStorage: {getItem: key => session.get(key)},
    localStorage: {getItem: key => local.get(key)}
};
vm.runInNewContext(source, {window, document: {addEventListener() {}}, URL});
const prepare = window.omoApplicationViewPreferencesPrepareRequest;
const documentsUrl = '/omo/api/documents/index.php?oid=1&lang=fr';
const calendarUrl = '/omo/api/calendar/index.php?oid=1&lang=fr';
const decode = url => JSON.parse(new URL(url, window.location.href).searchParams.get('restore_view'));
assert.equal(prepare(documentsUrl), documentsUrl, 'No stored view: keep server defaults.');
session.set('omo.documents.session-views.v1', JSON.stringify({'1:674': {scope: 'descendants', sort: 'alpha', density: 'compact', secret: 'do-not-send'}}));
let restored = decode(prepare(documentsUrl));
assert.equal(restored.holonId, 674, 'Resolve the implicit root context before the first request.');
assert.deepEqual(restored.temporary, {scope: 'descendants', sort: 'alpha', density: 'compact'});
assert.equal(prepare('/omo/api/documents/index.php?oid=2'), '/omo/api/documents/index.php?oid=2');
assert.equal(prepare(documentsUrl + '&cid=675'), documentsUrl + '&cid=675');
for (const query of ['document_scope=contextual', 'open_document_id=42', 'pv_application_tab_id=3', 'restore_view=existing']) {
    assert.equal(prepare(documentsUrl + '&' + query), documentsUrl + '&' + query, 'Explicit route is unchanged.');
}
const external = 'https://example.org/omo/api/documents/index.php?oid=1';
assert.equal(prepare(external), external, 'Never transmit preferences to another origin.');
local.set('omo.documents.saved-views.v2', JSON.stringify({contexts: {'1:674': {scope: 'children'}}, defaultView: {scope: 'contextual'}}));
restored = decode(prepare(documentsUrl));
assert.equal(restored.saved.scope, 'children');
assert.equal(restored.default.scope, 'contextual');
local.delete('omo.documents.saved-views.v2');
local.set('omo.documents.saved-views.v1', JSON.stringify({'1:674': {scope: 'children'}}));
assert.equal(decode(prepare(documentsUrl)).saved.scope, 'children', 'Legacy storage is still supported.');

function position(url) {
    session.set('omo.calendar.session-position.v1', JSON.stringify({'1:674': {url}}));
}
position('/omo/api/calendar/index.php?oid=1&cid=674&date=2026-08-01&month=2026-08&view=week&scope=children&token=do-not-send');
assert.deepEqual(decode(prepare(calendarUrl)).position, {date: '2026-08-01', month: '2026-08', view: 'week', scope: 'children'});
for (const query of ['date=2026-09-23', 'month=2026-09', 'view=day', 'scope=contextual', 'open_event_id=42', 'pv_application_tab_id=3']) {
    assert.equal(prepare(calendarUrl + '&' + query), calendarUrl + '&' + query);
}
for (const url of ['https://example.org/omo/api/calendar/index.php?oid=1&cid=674', '/omo/api/calendar/index.php?oid=2&cid=674', '/omo/api/calendar/index.php?oid=1&cid=675', '/omo/api/documents/index.php?oid=1&cid=674']) {
    position(url);
    assert.equal(prepare(calendarUrl), calendarUrl, 'Reject positions from another endpoint or context.');
}
session.set('omo.documents.session-views.v1', '{broken');
local.clear();
assert.equal(prepare(documentsUrl), documentsUrl);
window.sessionStorage.getItem = () => { throw new Error('blocked storage'); };
assert.equal(prepare(documentsUrl), documentsUrl);
const app = fs.readFileSync(path.join(__dirname, '../omo/assets/js/app.js'), 'utf8');
const shell = fs.readFileSync(path.join(__dirname, '../omo/index.php'), 'utf8');
assert(app.includes('url: requestUrl,'), 'The initial AJAX request uses the restored preferences.');
assert(shell.indexOf('src="assets/js/application-view-preferences.js') < shell.indexOf('src="assets/js/app.js'), 'Restoration is available synchronously before routing.');
console.log('application_view_restore_test: OK');
