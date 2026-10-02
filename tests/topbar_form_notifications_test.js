const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const rootPath = path.resolve(__dirname, '..');
const source = (file) => fs.readFileSync(path.join(rootPath, file), 'utf8');

function checkNotificationService() {
    const timers = [];
    class Element {
        constructor(tag) {
            this.tagName = tag;
            this.children = [];
            this.dataset = {};
            this.attributes = {};
            this.style = { setProperty() {} };
            this.className = '';
            this.classList = {
                add: (name) => { this.className += ' ' + name; },
                remove: (name) => { this.className = this.className.split(' ').filter((item) => item !== name).join(' '); },
                contains: (name) => this.className.split(' ').includes(name)
            };
        }
        setAttribute(name, value) { this.attributes[name] = value; }
        addEventListener() {}
        appendChild(child) { child.parent = this; this.children.push(child); }
        remove() { this.parent.children = this.parent.children.filter((child) => child !== this); }
    }
    const body = new Element('body');
    function find(node, id) {
        if (node.id === id) return node;
        for (const child of node.children) {
            const match = find(child, id);
            if (match) return match;
        }
        return null;
    }
    const context = {
        window: {
            setTimeout(callback, delay) { timers.push({ callback, delay }); return timers.length; },
            clearTimeout() {},
            requestAnimationFrame(callback) { callback(); }
        },
        document: { body, createElement: (tag) => new Element(tag), getElementById: (id) => find(body, id) }
    };
    const script = source('common/notifications/notifications.js');
    vm.runInNewContext(script, context);
    const api = context.window.commonNotify;
    const id = api('<strong>Saved</strong>', 'success');
    const notification = find(body, id);
    assert.equal(notification.attributes.role, 'status');
    assert.equal(notification.children[0].textContent, '<strong>Saved</strong>', 'Messages must remain plain text');
    assert.equal(timers[0].delay, 5000, 'Notifications must dismiss automatically');
    vm.runInNewContext(script, context);
    assert.equal(context.window.commonNotify, api, 'Loading legacy and topbar assets together must preserve the same API');
    const secondId = api('Failed', 'error');
    assert.notEqual(secondId, id, 'A second initialization must not reset notification IDs');
    assert.equal(find(body, secondId).attributes.role, 'alert');
    timers[0].callback();
    assert.equal(notification.dataset.commonNotificationState, 'leaving');
    timers[timers.length - 1].callback();
    assert.equal(find(body, id), null, 'Dismissed notifications must be removed');
}

async function checkSettings(file, selectors, pageConfig) {
    const handlers = {};
    const notices = [];
    const feedback = { textContent: '', hidden: false, className: '' };
    const button = { disabled: false, addEventListener() {} };
    const reset = { addEventListener() {} };
    const form = { elements: {}, addEventListener(name, handler) { handlers[name] = handler; } };
    const nodes = { [selectors.form]: form, [selectors.feedback]: feedback, [selectors.save]: button, [selectors.reset]: reset };
    const root = { dataset: {}, querySelector: (selector) => nodes[selector] };
    let reply = { ok: false, payload: { status: 'error', message: 'Rejected' } };
    const context = {
        window: {
            commonNotify: (message, type) => notices.push({ message, type }),
            setTimeout() {}, location: { reload() {} }
        },
        document: { querySelector: () => root },
        FormData: class {},
        fetch: () => Promise.resolve({ ok: reply.ok, json: () => Promise.resolve(reply.payload) })
    };
    vm.runInNewContext(source(file), context);
    context.window.commonPageScripts['/' + file](pageConfig);
    handlers.submit({ preventDefault() {} });
    assert.equal(button.disabled, true);
    await new Promise(setImmediate);
    assert.deepEqual(notices, [{ message: 'Rejected', type: 'error' }]);
    assert.equal(feedback.textContent, '', 'Global notifications must not duplicate inline messages');
    assert.equal(button.disabled, false, 'Errors must leave the form usable');
    reply = { ok: true, payload: { status: 'ok', message: 'Saved' } };
    handlers.submit({ preventDefault() {} });
    await new Promise(setImmediate);
    assert.deepEqual(notices[1], { message: 'Saved', type: 'success' });
    assert.equal(feedback.textContent, '');
    delete context.window.commonNotify;
    reply = { ok: false, payload: { status: 'error', message: 'Rejected again' } };
    handlers.submit({ preventDefault() {} });
    await new Promise(setImmediate);
    assert.equal(feedback.textContent, 'Rejected again');
    assert.equal(feedback.hidden, false, 'The fallback must remain visible if the topbar API is unavailable');
}

async function checkPushSettingsState() {
    const handlers = {};
    const notices = [];
    const feedback = { textContent: '' };
    const toggle = { checked: false, addEventListener: (name, callback) => { handlers[name] = callback; } };
    const root = {
        dataset: {},
        querySelector: (selector) => ({
            '[data-omo-notification-toggle]': toggle,
            '[data-omo-notification-feedback]': feedback
        })[selector] || null
    };
    const Notification = { permission: 'granted' };
    const context = {
        window: { isSecureContext: true, Notification, PushManager: class {},
            commonNotify: (message, type) => notices.push({ message, type }) },
        Notification,
        document: { querySelector: () => root },
        navigator: { serviceWorker: { getRegistration: async () => ({
            active: true, pushManager: { getSubscription: async () => null }
        }) } }
    };
    const file = 'omo/api/parameters/notifications/index.js';
    vm.runInNewContext(source(file), context);
    context.window.commonPageScripts['/' + file]({ configuration: {
        vapidPublicKey: 'fixture', texts: { disabled: 'Push disabled', loading: 'Loading' }
    } });
    await new Promise(setImmediate);
    assert.equal(feedback.textContent, 'Push disabled');
    assert.equal(notices.length, 0, 'Reading the current push state must not announce a completed action');
    await handlers.change();
    assert.deepEqual(notices, [{ message: 'Push disabled', type: 'success' }], 'A user action must show a transient confirmation');
    assert.equal(feedback.textContent, '');
    assert.equal(toggle.disabled, false);
}

async function checkPopupActions(file, ids, config) {
    const notices = [];
    const nodes = Object.fromEntries(ids.map((id) => [id, {
        value: 'fixture', handlers: {}, disabled: false, hidden: true,
        classList: { remove() {}, add() {} },
        addEventListener(name, callback) { this.handlers[name] = callback; },
        setAttribute() {}, getAttribute() { return '/save'; }
    }]));
    let reply = { ok: false, payload: { status: false, message: 'Rejected' } };
    const window = {
        omoNotify: (message, type) => notices.push({ message, type }),
        setTimeout() {}, dispatchEvent() {}
    };
    const context = {
        window, document: { getElementById: (id) => nodes[id], addEventListener() {} },
        FormData: class {}, CustomEvent: class {},
        fetch: () => Promise.resolve({ ok: reply.ok, json: () => Promise.resolve(reply.payload) })
    };
    vm.runInNewContext(source(file), context);
    window.commonPageScripts['/' + file](config);
    const [form, feedback, submit] = ids.map((id) => nodes[id]);
    form.handlers.submit({ preventDefault() {} });
    await new Promise(setImmediate);
    assert.deepEqual(notices, [{ message: 'Rejected', type: 'error' }]);
    assert.equal(submit.disabled, false);
    assert.equal(feedback.textContent, '');
    reply = { ok: true, payload: { status: true, message: 'Completed' } };
    form.handlers.submit({ preventDefault() {} });
    await new Promise(setImmediate);
    assert.deepEqual(notices[1], { message: 'Completed', type: 'success' });
    assert.equal(feedback.textContent, '', 'Confirmations must remain visible through the global API when the popup closes');
}

(async () => {
    checkNotificationService();
    await checkSettings('omo/api/parameters/lexicon/index.js', {
        form: '[data-omo-lexicon-form]', feedback: '[data-omo-lexicon-feedback]',
        save: '[data-omo-lexicon-save]', reset: '[data-omo-lexicon-reset]'
    }, { defaults: {}, message: 'Failure', parametersLexiconStatusSaved: 'Saved' });
    await checkSettings('omo/api/parameters/structure-display/index.js', {
        form: '[data-omo-structure-display-form]', feedback: '[data-omo-structure-display-feedback]',
        save: '[data-omo-structure-display-save]', reset: '[data-omo-structure-display-reset]'
    }, { defaults: {}, message: 'Failure', parametersStructureDisplayStatusSaved: 'Saved' });
    await checkPushSettingsState();
    await checkPopupActions('omo/api/holons/member_popup.js', [
        'omoHolonMemberPopupForm', 'omoHolonMemberPopupFeedback', 'omoHolonMemberPopupSubmit',
        'omoHolonMemberExistingUser', 'omoHolonMemberEmail'
    ], { holonId: 2, rootHolonId: 1, organizationId: 1 });
    await checkPopupActions('omo/api/decision/send_invitations_popup.js', [
        'omoDecisionSendInvitationsPopupForm', 'omoDecisionSendInvitationsPopupFeedback', 'omoDecisionSendInvitationsPopupSubmit',
        'omoDecisionSendInvitationsPopupToggle', 'omoDecisionSendInvitationsPopupMenu', 'omoDecisionSendInvitationsScope'
    ], { canSendPending: true, canSendAll: true });
    console.log('topbar_form_notifications_test: OK');
})().catch((error) => { console.error(error); process.exitCode = 1; });
