'use strict';
// node tests/calendar_lazy_invitations_test.js /path/to/linkedom
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const {parseHTML} = require(process.argv[2] || 'linkedom');
const {document, Event, MutationObserver} = parseHTML('<html><body><form><div hidden data-calendar-invitations-lazy="/editor" data-loading-label="Loading" data-error-label="Retry">' +
    '<div hidden data-omo-calendar-invitations-editor data-omo-calendar-invitations-placeholder data-omo-calendar-uses-default-selection="1" data-omo-calendar-default-holon-id="12">' +
    '<input type="checkbox" name="invitation_holon_ids[]" value="12" checked><input type="checkbox" name="invitation_user_ids[]" value="34" checked><textarea name="invitation_emails">guest@example.invalid</textarea></div>' +
    '<div data-calendar-invitations-feedback></div></div></form></body></html>');
const html = '<div data-omo-calendar-invitations-editor><input type="checkbox" name="invitation_holon_ids[]" value="12">' +
    '<input type="checkbox" name="invitation_user_ids[]" value="34"><input type="checkbox" name="invitation_user_ids[]" value="99" checked><textarea name="invitation_emails"></textarea></div>';
let requests = [], initialize = 0, loadedEvents = 0;
const window = {requestAnimationFrame() {}, omoCalendarInitInvitationEditors() { initialize++; }};
const context = vm.createContext({window, document, Event, MutationObserver, AbortController,
    ResizeObserver: class { observe() {} disconnect() {} },
    fetch: (url, options) => new Promise((resolve, reject) => requests.push({url, options, resolve, reject}))});
const settle = () => new Promise(resolve => setImmediate(resolve));
const host = document.querySelector('[data-calendar-invitations-lazy]');
document.querySelector('form').addEventListener('omo-calendar-invitations-loaded', () => loadedEvents++);
(async () => {
    vm.runInContext(fs.readFileSync('common/calendar/invitations.js', 'utf8'), context);
    await settle(); assert.equal(requests.length, 0, 'Hidden tabs do not fetch.');
    host.hidden = false; await settle();
    assert.equal(requests.length, 1, 'Showing the tab, including keyboard/mobile navigation, fetches once.');
    host.hidden = true; host.hidden = false; await settle();
    assert.equal(requests.length, 1, 'In-flight requests are shared.');
    assert.ok(host.querySelector('[name="invitation_holon_ids[]"]'), 'Draft fields remain available while loading.');
    requests[0].resolve({ok: true, text: async () => html}); await settle(); await settle();
    assert.equal(host.dataset.loadState, 'loaded');
    assert.equal(host.querySelector('[value="12"]').checked, true);
    assert.equal(host.querySelector('[value="34"]').checked, true);
    assert.equal(host.querySelector('[value="99"]').checked, false, 'The server does not replace the form draft selections.');
    assert.equal(host.querySelector('textarea').value, 'guest@example.invalid');
    assert.equal(host.querySelector('[data-omo-calendar-invitations-editor]').dataset.omoCalendarUsesDefaultSelection, '1');
    assert.equal(initialize, 1); assert.equal(loadedEvents, 1);
    host.hidden = true; host.hidden = false; await settle();
    assert.equal(requests.length, 1, 'Reopening preserves edits without another request.');
    const details = document.createElement('details');
    details.innerHTML = '<summary>Invites</summary><div data-calendar-invitations-lazy="/summary" data-error-label="Retry"><div data-calendar-invitations-feedback></div></div>';
    document.body.append(details); await settle();
    assert.equal(requests.length, 1, 'Closed invitation summary does not fetch.');
    details.setAttribute('open', ''); await settle();
    assert.equal(requests.length, 2);
    requests[1].resolve({ok: false}); await settle(); await settle();
    const retry = details.querySelector('button'); assert.ok(retry, 'Failure presents a retry without erasing the form.');
    retry.dispatchEvent(new Event('click')); await settle();
    assert.equal(requests.length, 3);
    const signal = requests[2].options.signal;
    details.remove(); await settle();
    assert.equal(signal.aborted, true, 'Closing the drawer aborts pending work.');
    requests[2].resolve({ok: true, text: async () => '<p>Late summary</p>'}); await settle();
    assert.equal(document.body.textContent.includes('Late summary'), false, 'Late responses never populate another drawer.');
    console.log('calendar_lazy_invitations_test: OK');
})().catch(error => { console.error(error); process.exitCode = 1; });
