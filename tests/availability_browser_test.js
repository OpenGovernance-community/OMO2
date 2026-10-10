'use strict';
// Run with Node and a test-only linkedom installation: node tests/availability_browser_test.js /path/to/linkedom
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const {parseHTML, DOMParser} = require(process.argv[2] || 'linkedom');
const root = path.resolve(__dirname, '..');
const settle = () => new Promise(resolve => setImmediate(resolve));
function setup(html, href, fetch) {
    const {document, Event} = parseHTML('<html><body>' + html + '</body></html>');
    const location = {href, reload() { throw new Error('Unexpected reload'); }};
    const window = {document, location, setTimeout, addEventListener() {}};
    const context = vm.createContext({window, document, location, URL, URLSearchParams, Event, DOMParser, fetch,
        history: {pushState(_state, _title, url) { location.href = String(url); }, replaceState(_state, _title, url) { location.href = String(url); }}, FormData, AbortController, console});
    function run(file) { vm.runInContext(fs.readFileSync(path.join(root, file), 'utf8'), context); }
    run('common/calendar/availability-model.js');
    run('common/calendar/availability-view.js');
    return {document, window, run, click(node) {
        assert.ok(node, 'Click target exists');
        const event = new Event('click', {bubbles: true, cancelable: true});
        event.button = 0;
        node.dispatchEvent(event);
    }};
}
const labels = {free: 'Libre', full: 'Occupé', partial: 'Partiel', closed: 'Fermé', busy: 'Occupé', available: 'Libre',
    day_availability: '{free} / {total} libres', select_day: 'Choisir', select_day_hint: 'Jour', no_hours: 'Fermé', pause: 'Pause'};
function profileMonth(month) {
    const dates = {[month + '-01']: 'Jour 1', [month + '-02']: 'Jour 2'};
    const data = {month, date: '', dates, labels, people: [{days: {
        [month + '-01']: '0'.repeat(18) + '1211' + '0'.repeat(26),
        [month + '-02']: '0'.repeat(18) + '1111' + '0'.repeat(26)
    }}]};
    return '<div class="calendar-freebusy-calendar">' + Object.keys(dates).map(date =>
        '<button class="calendar-freebusy-day" data-user-availability-url="/popup/user.php?section=availability&date=' + date + '">' + date + '</button>').join('') +
        '<button data-next data-user-availability-url="/popup/user.php?section=availability&month=2030-02">Next</button>' +
        '<button data-prev data-user-availability-url="/popup/user.php?section=availability&month=2030-01">Previous</button></div>' +
        '<aside class="calendar-freebusy-day-panel"></aside><script data-user-availability-data type="application/json">' + JSON.stringify(data) + '</script>';
}
async function testProfile() {
    const calls = [];
    const app = setup('<div class="omo-user-context"><button data-user-fragment-panel="availability">Open</button><div id="availability">' +
        '<div data-user-fragment-host="1" data-user-availability-host="1" data-user-fragment-url="/popup/user.php?section=availability&month=2030-01"></div></div></div>',
    'https://localtest.me/omo/', async url => {
        calls.push(url);
        return {ok: true, text: async () => profileMonth(new URL(url, 'https://localtest.me').searchParams.get('month'))};
    });
    app.run('common/team/user-popup.js');
    app.click(app.document.querySelector('[data-user-fragment-panel]'));
    await settle();
    const scrolls = [];
    let mobile = false;
    let reducedMotion = false;
    app.window.matchMedia = query => ({matches: query.includes('reduced-motion') ? reducedMotion : mobile});
    app.document.querySelector('.calendar-freebusy-day-panel').scrollIntoView = options => scrolls.push(options);
    app.click(app.document.querySelector('.calendar-freebusy-day'));
    assert.equal(scrolls.length, 0, 'Desktop day selection does not scroll.');
    assert.equal(calls.length, 1, 'Selecting a loaded day never fetches.');
    assert.equal(app.document.querySelectorAll('.calendar-freebusy-slot').length, 4);
    assert.equal(app.document.querySelectorAll('.calendar-freebusy-slot[data-state="busy"]').length, 1);
    mobile = true;
    app.click(app.document.querySelectorAll('.calendar-freebusy-day')[1]);
    assert.equal(scrolls.length, 1, 'Mobile day selection scrolls to the time panel.');
    assert.equal(scrolls[0].behavior, 'smooth');
    reducedMotion = true;
    app.click(app.document.querySelectorAll('.calendar-freebusy-day')[1]);
    assert.equal(scrolls[1].behavior, 'instant', 'Reduced motion avoids animated scrolling.');
    mobile = false;
    assert.equal(calls.length, 1);
    assert.equal(app.document.querySelectorAll('.calendar-freebusy-slot[data-state="busy"]').length, 0);
    app.click(app.document.querySelector('[data-next]'));
    await settle();
    assert.equal(calls.length, 2, 'A new month loads once.');
    app.click(app.document.querySelector('[data-prev]'));
    await settle();
    assert.equal(calls.length, 2, 'Returning to a loaded month uses the cache.');
    app.click(app.document.querySelector('.calendar-freebusy-day'));
    assert.equal(app.document.querySelectorAll('.calendar-freebusy-slot').length, 4);
}
async function testMeeting() {
    const date = '2030-01-07';
    const start = Date.parse(date + 'T09:00:00Z') / 1000;
    const slots = [true, true, true, false, true, false].map((free, index) => ({
        time: (9 + Math.floor(index / 2)).toString().padStart(2, '0') + (index % 2 ? ':30' : ':00'),
        end: (9 + Math.floor((index + 1) / 2)).toString().padStart(2, '0') + (index % 2 ? ':00' : ':30'),
        startEpoch: start + index * 1800, free, pause: false
    }));
    const data = {month: '2030-01', path: '/meeting/fixture', durationSlots: 3, maxDuration: 90, preparationMinutes: 30, labels: {...labels, continue: 'Continuer', range_unavailable: 'Erreur {duration}', duration_limited: 'Choisissez une durée plus courte'},
        dates: {[date]: date}, weekdays: {[date]: 'Lundi'}, days: {[date]: {state: 'partial', slots}}};
    let requests = 0;
    const app = setup('<select data-meeting-method><option value="address" selected>Address</option><option value="video">Video</option></select><select data-meeting-duration><option value="30">30</option><option value="60">60</option><option value="90" selected>90</option></select><div class="meeting-layout"><section><a data-date="' + date + '" href="https://localtest.me/meeting/fixture?date=' + date + '">7</a></section>' +
        '<aside id="meeting-times"></aside></div><script data-meeting-month-data type="application/json">' + JSON.stringify(data) + '</script>',
    'https://localtest.me/meeting/fixture?date=' + date, async () => { requests++; throw new Error('Unexpected request'); });
    const messages = [];
    app.window.commonNotify = (message, options) => messages.push({message, options});
    const scrolls = []; const focused = [];
    let mobile = false; let reducedMotion = false;
    app.window.matchMedia = query => ({matches: query.includes('reduced-motion') ? reducedMotion : mobile});
    app.document.querySelector('#meeting-times').scrollIntoView = options => scrolls.push(options);
    const makeElement = app.window.omoCalendarAvailabilityView.element;
    app.window.omoCalendarAvailabilityView.element = (...args) => {
        const node = makeElement(...args);
        node.focus = options => focused.push({id: node.id, options});
        return node;
    };
    const selector = app.document.querySelector('[data-meeting-duration]');
    // linkedom implements select.value as a getter only.
    Object.defineProperty(selector, 'value', {writable: true, value: '90'});
    const methodSelector = app.document.querySelector('[data-meeting-method]');
    Object.defineProperty(methodSelector, 'value', {writable: true, value: 'address'});
    app.run('meeting/meeting.js');
    assert.equal(scrolls.length, 0, 'Initial rendering never jumps to the time panel.');
    app.click(app.document.querySelector('[data-date]'));
    assert.equal(scrolls.length, 0, 'Desktop day selection keeps the two-column view in place.');
    mobile = true;
    app.click(app.document.querySelector('[data-date]'));
    assert.equal(scrolls[0].block, 'start');
    assert.equal(scrolls[0].behavior, 'smooth');
    assert.equal(focused[0].id, 'meeting-day', 'Mobile selection focuses the rebuilt time heading.');
    assert.equal(focused[0].options.preventScroll, true, 'Focus does not trigger a second uncontrolled scroll.');
    assert.equal(app.document.querySelector('#meeting-day').getAttribute('tabindex'), '-1');
    assert.equal(app.document.querySelectorAll('[data-slot-index]').length, 6, 'Booking renders half-hour cells.');
    assert.equal(app.document.querySelector('[data-slot-index="0"]').getAttribute('aria-disabled'), 'false', 'Preparation before opening does not gray the first slot.');
    app.click(app.document.querySelector('[data-slot-index="2"]'));
    assert.equal(app.document.querySelectorAll('[aria-pressed="true"]').length, 3, '90-minute booking selects three cells backwards when necessary.');
    const next = app.document.querySelector('[data-meeting-continue]');
    assert.ok(next.href.includes('time=09:00'), 'Continue uses the actual range start, not the clicked cell.');
    assert.ok(next.href.includes('duration=90'), 'Selected duration travels to the server review.');
    assert.ok(next.href.includes('method=address'));
    methodSelector.value = 'video';
    methodSelector.dispatchEvent(new app.document.defaultView.Event('change', {bubbles: true}));
    assert.ok(next.href.includes('method=video'), 'Changing contact updates the selection link without recalculating availability.');
    assert.equal(app.document.querySelectorAll('[aria-pressed="true"]').length, 3, 'Method change preserves the time selection.');
    assert.equal(app.document.querySelector('[data-slot-index="4"]').getAttribute('aria-disabled'), 'true', 'An isolated free half-hour cannot fit the longer appointment.');
    assert.ok(app.document.querySelector('[data-slot-index="4"]').classList.contains('generic-action-button--choice-limited'));
    assert.ok(!app.document.querySelector('[data-slot-index="4"]').classList.contains('generic-action-button--unavailable'), 'Free but too short is distinct from occupied.');
    assert.equal(app.document.querySelector('[data-slot-index="4"]').title, 'Choisissez une durée plus courte');
    assert.ok(app.document.querySelector('[data-slot-index="3"]').classList.contains('generic-action-button--unavailable'), 'Occupied cells remain gray.');
    app.click(app.document.querySelector('[data-slot-index="4"]'));
    assert.equal(app.document.querySelectorAll('[aria-pressed="true"]').length, 0, 'Failed range clears the old selection.');
    assert.ok(next.hidden);
    assert.equal(messages.at(-1).message, 'Erreur 90');
    assert.equal(messages.at(-1).options.duration, 5000);
    selector.value = '30';
    selector.dispatchEvent(new app.document.defaultView.Event('change', {bubbles: true}));
    assert.equal(scrolls.length, 1, 'Changing duration does not scroll again.');
    assert.equal(app.document.querySelector('[data-slot-index="4"]').getAttribute('aria-disabled'), 'false', 'Shorter duration immediately enables the isolated slot.');
    assert.ok(!app.document.querySelector('[data-slot-index="4"]').classList.contains('generic-action-button--choice-limited'));
    app.click(app.document.querySelector('[data-slot-index="4"]'));
    assert.equal(app.document.querySelectorAll('[aria-pressed="true"]').length, 1);
    reducedMotion = true;
    app.click(app.document.querySelector('[data-date]'));
    assert.equal(scrolls[1].behavior, 'instant', 'Reduced motion avoids animated scrolling on mobile.');
    mobile = false;
    app.click(app.document.querySelector('[data-date]'));
    assert.equal(scrolls.length, 2, 'Desktop remains still after mobile day selections.');
    assert.equal(requests, 0, 'Day navigation and selection never fetch.');
}
async function testEditor() {
    const date = '2030-01-07';
    const data = {month: '2030-01', dates: {[date]: date}, labels: {...labels, selected_count: '{selected}/{total}',
        busy_count: '{busy}/{total}', busy_names: 'Occupé: {names}', range_blocked: 'Erreur', range_selected: 'Mis à jour'},
    people: [{id: '1', name: 'Guest', days: {[date]: '0'.repeat(18) + '111212' + '0'.repeat(24)}}]};
    const html = '<div class="omo-calendar-create__preview-people"><strong data-omo-calendar-preview-people-count></strong></div>' +
        '<p data-omo-calendar-preview-cache-warning></p><button class="calendar-freebusy-day" data-omo-calendar-preview-target="month=2030-01&date=' + date + '"></button>' +
        '<aside class="calendar-freebusy-day-panel"></aside><script data-omo-calendar-preview-data type="application/json">' + JSON.stringify(data) + '</script>';
    let requests = 0;
    const messages = [];
    const app = setup('<form data-omo-calendar-create-form><input name="start_at" value="' + date + 'T09:00"><input name="end_at" value="' + date + 'T10:30">' +
        '<button type="button" data-omo-calendar-preview-tab>Open</button><div data-omo-calendar-preview-host></div></form>',
    'https://localtest.me/omo/', async () => { requests++; return {ok: true, text: async () => html}; });
    // linkedom does not implement HTMLFormElement.elements; production browsers do.
    app.document.querySelector('form').elements = {};
    app.window.commonNotify = (message, type) => messages.push({message, type});
    app.run('common/calendar/availability.js');
    app.click(app.document.querySelector('[data-omo-calendar-preview-tab]'));
    await settle();
    app.click(app.document.querySelector('[data-omo-calendar-preview-slot-start="' + date + 'T10:00"]'));
    assert.equal(app.document.querySelectorAll('[aria-pressed="true"]').length, 3);
    assert.equal(app.document.querySelector('[name="start_at"]').value, date + 'T09:00');
    assert.equal(app.document.querySelector('[name="end_at"]').value, date + 'T10:30');
    assert.equal(messages.at(-1).type, 'success');
    app.click(app.document.querySelector('[data-omo-calendar-preview-slot-start="' + date + 'T11:00"]'));
    assert.equal(app.document.querySelectorAll('[aria-pressed="true"]').length, 0);
    assert.equal(messages.at(-1).type, 'error');
    assert.equal(app.document.querySelector('[name="start_at"]').value, date + 'T09:00', 'A failed choice does not alter event fields.');
    assert.equal(requests, 1);
}
function testConfirmation() {
    const app = setup('<form data-omo-calendar-create-form><input name="availability_ack" value=""><input name="title">' +
        '<input type="checkbox" data-omo-calendar-buffers-toggle><div data-omo-calendar-buffers-fields hidden><select name="preparation_minutes" disabled><option value="15" selected>15 minutes</option></select><select name="closing_minutes" disabled><option value="20" selected>20 minutes</option></select></div>' +
        '<div data-calendar-availability-loading hidden></div><div data-calendar-availability data-acknowledgement="reviewed">' +
        '<button type="button" data-calendar-availability-confirm>Confirm</button></div></form>',
    'https://localtest.me/omo/', async () => { throw new Error('Confirmation must not fetch availability'); });
    const form = app.document.querySelector('form');
    form.elements = {availability_ack: form.querySelector('[name="availability_ack"]')};
    let submitted = '';
    form.requestSubmit = () => { submitted = form.elements.availability_ack.value; };
    app.run('common/calendar/availability.js');
    const toggle = form.querySelector('[data-omo-calendar-buffers-toggle]');
    const bufferFields = form.querySelector('[data-omo-calendar-buffers-fields]');
    toggle.checked = true;
    toggle.dispatchEvent(new app.document.defaultView.Event('change', {bubbles: true}));
    assert.equal(bufferFields.hidden, false, 'Any editor host can expand attached time fields.');
    assert.equal(bufferFields.querySelector('select').disabled, false);
    toggle.checked = false;
    toggle.dispatchEvent(new app.document.defaultView.Event('change', {bubbles: true}));
    assert.equal(bufferFields.hidden, true);
    assert.equal(bufferFields.querySelector('select').value, '15', 'Toggling preserves values until save.');
    assert.equal(bufferFields.querySelector('select').disabled, true, 'Inactive durations are omitted on submit.');
    app.window.omoCalendarSetAvailabilityPending(form, true);
    assert.equal(form.querySelector('[data-calendar-availability-loading]').hidden, false);
    app.window.omoCalendarSetAvailabilityPending(form, false);
    form.querySelector('[data-calendar-availability]').dataset.acknowledgement = 'reviewed';
    app.click(form.querySelector('[data-calendar-availability-confirm]'));
    assert.equal(submitted, 'reviewed', 'Confirmation submits the already reviewed warning.');
    app.window.omoCalendarSetAvailabilityPending(form, true);
    assert.equal(form.querySelector('[data-calendar-availability-loading]').hidden, true, 'Confirmation does not announce another synchronization.');
    assert.equal(form.elements.availability_ack.value, 'reviewed');
    form.querySelector('[name="title"]').dispatchEvent(new app.document.defaultView.Event('input', {bubbles: true}));
    assert.equal(form.elements.availability_ack.value, '', 'Editing invalidates the previous confirmation.');
}
function testStandaloneNotifications() {
    const app = setup('', 'https://localtest.me/meeting/fixture', async () => { throw new Error('Unexpected request'); });
    const timers = [];
    app.window.setTimeout = (callback, duration) => { timers.push({callback, duration}); return timers.length; };
    app.window.clearTimeout = () => {};
    app.window.requestAnimationFrame = callback => callback();
    app.run('common/notifications/notifications.js');
    app.window.commonNotify('Erreur <test>', {type: 'error', duration: 5000});
    assert.ok(app.document.querySelector('#commonNotifications'), 'Notification API creates its region without any topbar.');
    assert.equal(app.document.querySelector('.common-notification__message').textContent, 'Erreur <test>');
    assert.equal(timers[0].duration, 5000);
    timers[0].callback();
    timers[1].callback();
    assert.equal(app.document.querySelector('.common-notification'), null, 'Transient error disappears after its timeout and animation.');
}
(async () => { await testProfile(); await testMeeting(); await testEditor(); testConfirmation(); testStandaloneNotifications(); console.log('availability_browser_test: OK'); })().catch(error => { console.error(error); process.exitCode = 1; });
