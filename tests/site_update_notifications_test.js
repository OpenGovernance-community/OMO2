'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync(require('node:path').join(__dirname, '../common/notifications/inbox.js'), 'utf8');

async function fixture(search = '') {
    const documentEvents = {};
    const windowEvents = {};
    const calls = [];
    const popups = [];
    const host = {innerHTML: ''};
    const badge = {};
    const url = 'https://omo.test/omo/' + search;
    const window = {
        location: {href: url, origin: 'https://omo.test'},
        commonTopbarConfig: {notifications: {enabled: true, inboxUrl: '/inbox', markReadUrl: '/read', csrfToken: 'token'}},
        commonTopbarOpenModal: (...args) => popups.push(args),
        addEventListener: (name, handler) => { windowEvents[name] = handler; },
        history: {state: {}, replaceState: (...args) => { calls.push(['history', ...args]); }}
    };
    const document = {
        addEventListener: (name, handler) => { (documentEvents[name] ||= []).push(handler); },
        querySelector: selector => selector.includes('badge') ? badge : host
    };
    const fetch = async (url, options) => {
        calls.push([url, options]);
        return {ok: true, json: async () => ({status: true, unreadCount: 1, notifications: [
            {id: 8, title: '<New feature>', eventKey: 'site_update_installed', url: '/omo/?site-update-news=42'},
            {id: 9, title: 'Meeting', eventKey: 'event_created', url: '/meeting/'}
        ]})};
    };
    vm.runInNewContext(source, {window, document, fetch, URL, Promise, Date});
    windowEvents.DOMContentLoaded();
    await new Promise(resolve => setImmediate(resolve));
    return {window, documentEvents, calls, popups, host, badge};
}
(async () => {
    const f = await fixture();
    assert.match(f.host.innerHTML, /data-site-update-news/);
    assert.match(f.host.innerHTML, /&lt;New feature&gt;/);
    assert.equal((f.host.innerHTML.match(/data-site-update-news/g) || []).length, 1);
    let prevented = false;
    const item = {
        href: 'https://omo.test/omo/?site-update-news=42', classList: {remove() {}},
        hasAttribute: () => true, querySelector: () => ({textContent: 'New feature'}),
        getAttribute: key => key === 'data-notification-id' ? '8' : '/omo/?site-update-news=42'
    };
    const click = {target: {closest: selector => selector === '[data-omo-notification-item]' ? item : null}, preventDefault: () => { prevented = true; }};
    f.documentEvents.click.forEach(handler => handler(click));
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(prevented, true);
    assert.equal(f.popups.length, 1);
    assert.deepEqual(f.popups[0], ['New feature', '/omo/api/notifications/site_update_news.php?id=42', 'fetch']);
    assert.equal(JSON.parse(f.calls.find(call => call[0] === '/read')[1].body).notification_id, 8);
    f.documentEvents.click.forEach(handler => handler({...click, ctrlKey: true}));
    assert.equal(f.popups.length, 1, 'Preserve modified clicks');
    const deep = await fixture('?site-update-news=42');
    assert.equal(deep.popups[0][1], '/omo/api/notifications/site_update_news.php?id=42');
    assert.equal(JSON.parse(deep.calls.find(call => call[0] === '/read')[1].body).url, '/omo/?site-update-news=42');
    assert.equal(deep.calls.find(call => call[0] === 'history')[3], 'https://omo.test/omo/');
    console.log('site_update_notifications_test: OK');
})().catch(error => { console.error(error); process.exitCode = 1; });
