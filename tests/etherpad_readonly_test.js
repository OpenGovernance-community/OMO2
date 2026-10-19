'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync(require('node:path').join(__dirname, '../omo/api/documents/etherpad/readonly.js'), 'utf8');

function reader() {
    const timers = [];
    const responses = [];
    const requests = [];
    const content = {innerHTML: '<p>Initial content</p>', replaceChildren() { this.innerHTML = ''; }};
    const status = {hidden: true, textContent: '', dataset: {error: 'Refresh failed', denied: 'Access revoked'}};
    const document = {hidden: false, querySelector: () => ({querySelector: selector => selector.endsWith('content]') ? content : status})};
    vm.runInNewContext(source, {
        document, URL, AbortSignal,
        window: {location: {href: 'https://localtest.me/omo/api/documents/etherpad/open.php?id=12'},
            setTimeout(callback) { timers.push(callback); }},
        async fetch(url, options) {
            requests.push({url, options});
            const result = responses.shift();
            if (result instanceof Error) throw result;
            return {status: 200, ok: true, redirected: false, json: async () => ({status: true, html: '<p>Updated content</p>'}), ...result};
        },
    });
    return {timers, responses, requests, content, status, document, async tick() { await timers.shift()(); }};
}

(async () => {
    const page = reader();
    await page.tick();
    assert.equal(page.content.innerHTML, '<p>Updated content</p>');
    assert.equal(page.requests[0].url.searchParams.get('content'), '1');
    assert.equal(page.requests[0].options.cache, 'no-store');
    assert.equal(page.status.hidden, true);
    page.responses.push({ok: false, status: 503});
    await page.tick();
    assert.equal(page.content.innerHTML, '<p>Updated content</p>', 'Temporary errors preserve readable content');
    assert.equal(page.status.hidden, false);
    assert.equal(page.status.textContent, 'Refresh failed');
    await page.tick();
    assert.equal(page.status.hidden, true, 'A successful retry clears the error');
    page.document.hidden = true;
    await page.tick();
    assert.equal(page.requests.length, 3, 'Hidden frames pause network requests');
    for (const response of [{status: 401}, {status: 403}, {status: 404}, {redirected: true}]) {
        const revoked = reader();
        revoked.responses.push(response);
        await revoked.tick();
        assert.equal(revoked.content.innerHTML, '', 'Revoked access immediately clears old content');
        assert.equal(revoked.status.textContent, 'Access revoked');
        assert.equal(revoked.timers.length, 0, 'Revoked access stops refresh requests');
    }
    console.log('etherpad_readonly_test: OK');
})().catch(error => { console.error(error); process.exitCode = 1; });
