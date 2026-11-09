'use strict';
// Run with jsdom on NODE_PATH. No email is sent by this test.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const {JSDOM} = require('jsdom');
const source = fs.readFileSync('common/assets/auth.js', 'utf8');
const flush = () => new Promise(resolve => setImmediate(resolve));

function fixture(storage = {}, initialNow = 1000000) {
    const dom = new JSDOM('<input id="authEmailInput" value="test@example.invalid"><button id="authLoginSubmit"></button><button id="authResendLink"></button><div id="authCodeBox"><p></p><input id="authCodeInput"><button id="authCodeSubmit"></button></div><div id="authStatus"></div>', {url:'https://example.invalid/login', runScripts:'outside-only'});
    const {window} = dom;
    let now = initialNow;
    let nextId = 0;
    const timers = new Map();
    window.Date.now = () => now;
    window.setTimeout = (callback, delay) => { timers.set(++nextId, {callback, at:now + delay}); return nextId; };
    window.clearTimeout = id => timers.delete(id);
    Object.entries(storage).forEach(([key, value]) => window.sessionStorage.setItem(key, value));
    const requests = [];
    window.fetch = (url, options) => {
        if (options.method === 'GET') return Promise.resolve({ok:true, json:async () => ({})});
        return new Promise(resolve => requests.push({resolve, options}));
    };
    window.eval(source);
    return {
        window, requests,
        button:window.document.getElementById('authResendLink'),
        advance(milliseconds) {
            now += milliseconds;
            // Simulates delayed callbacks too (e.g. a background tab).
            for (const [id, timer] of [...timers]) {
                if (timer.at <= now) { timers.delete(id); timer.callback(); }
            }
        },
        storage() { return Object.fromEntries(Object.keys(window.sessionStorage).map(key => [key, window.sessionStorage.getItem(key)])); },
        reply(data) { requests.at(-1).resolve({ok:true, json:async () => data}); }
    };
}

(async () => {
    const first = fixture();
    await flush();
    first.window.document.getElementById('authLoginSubmit').click();
    assert.equal(first.requests.length, 1);
    first.reply({status:'code_sent', request_token:'test-token'});
    await flush();
    assert.equal(first.button.textContent, 'Envoyer un nouveau code (60 s)');
    assert.equal(first.button.disabled, true);
    assert.equal(first.button.getAttribute('aria-disabled'), 'true');
    assert.equal(first.window.document.getElementById('authCodeSubmit').disabled, false, 'Code verification stays available');
    first.button.click();
    first.window.document.getElementById('authEmailInput').dispatchEvent(new first.window.KeyboardEvent('keydown', {key:'Enter', bubbles:true}));
    assert.equal(first.requests.length, 1, 'Neither click nor Enter resends during the cooldown');
    assert.match(first.window.document.getElementById('authStatus').textContent, /1 minute/, 'Blocked keyboard attempts explain the wait');
    first.advance(30000);
    assert.equal(first.button.textContent, 'Envoyer un nouveau code (30 s)');
    first.window.document.getElementById('authEmailInput').dispatchEvent(new first.window.KeyboardEvent('keydown', {key:'Enter', bubbles:true}));
    assert.match(first.window.document.getElementById('authStatus').textContent, /30 secondes/, 'Local feedback shows the remaining seconds');

    const reloaded = fixture(first.storage(), 1030000);
    await flush();
    assert.equal(reloaded.button.textContent, 'Envoyer un nouveau code (30 s)', 'Reload retains the remaining delay');
    reloaded.advance(29000);
    assert.equal(reloaded.button.textContent, 'Envoyer un nouveau code (1 s)');
    assert.equal(reloaded.button.disabled, true);
    reloaded.advance(1000);
    assert.equal(reloaded.button.textContent, 'Envoyer un nouveau code');
    assert.equal(reloaded.button.disabled, false);
    reloaded.button.click();
    assert.equal(reloaded.requests.length, 1);
    assert.equal(reloaded.button.disabled, true, 'Sending remains disabled while the request is pending');
    reloaded.reply({status:'code_pending', request_token:'replacement-token'});
    await flush();
    assert.equal(reloaded.button.textContent, 'Envoyer un nouveau code (60 s)', 'Uncertain delivery also starts the cooldown');
    assert.equal(reloaded.button.disabled, true);
    reloaded.advance(65000);
    assert.equal(reloaded.button.disabled, false, 'A delayed timer uses elapsed wall time');
    reloaded.button.click();
    reloaded.reply({error:'send_failed'});
    await flush();
    assert.equal(reloaded.button.disabled, false, 'An unsuccessful request does not start a new countdown');
    reloaded.button.click();
    reloaded.reply({error:'rate_limited', retry_after:2700, message:'Trop de tentatives. Veuillez patienter 45 minutes avant d’essayer à nouveau.'});
    await flush();
    assert.match(reloaded.window.document.getElementById('authStatus').textContent, /45 minutes/, 'Server cooldown message is displayed');
    assert.equal(reloaded.button.disabled, true, 'Server cooldown disables further delivery');
    const requestCount = reloaded.requests.length;
    reloaded.advance(10000);
    reloaded.window.document.getElementById('authEmailInput').dispatchEvent(new reloaded.window.KeyboardEvent('keydown', {key:'Enter', bubbles:true}));
    assert.equal(reloaded.requests.length, requestCount, 'Cooldown attempts send no request');
    const serverReload = fixture(reloaded.storage(), 1135000);
    await flush();
    assert.equal(serverReload.button.textContent, 'Envoyer un nouveau code (2690 s)', 'Reload preserves the server deadline without extending it');
    serverReload.advance(2690000);
    assert.equal(serverReload.button.disabled, false, 'Resending becomes available at the original deadline');
    first.window.close();
    reloaded.window.close();
    serverReload.window.close();
    console.log('auth_resend_countdown_test: OK (60s delay, reload, keyboard, resend, pending delivery, failure, server cooldown)');
})().catch(error => { console.error(error); process.exitCode = 1; });
