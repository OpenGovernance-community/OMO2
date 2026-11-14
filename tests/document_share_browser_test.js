'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require('playwright');
const root = path.resolve(__dirname, '..');
const file = path.join(root, 'tmp/document-share-live.json');
(async () => {
    for (let attempt = 0; attempt < 120 && !fs.existsSync(file); attempt++) await new Promise(resolve => setTimeout(resolve, 250));
    const fixture = JSON.parse(fs.readFileSync(file, 'utf8'));
    const browser = await chromium.launch({channel: 'msedge', headless: true,
        args: ['--host-resolver-rules=MAP localtest.me 127.0.0.1, MAP *.localtest.me 127.0.0.1']});
    try {
        const context = await browser.newContext({ignoreHTTPSErrors: true});
        const page = await context.newPage();
        await page.goto(fixture.url, {waitUntil: 'domcontentloaded'});
        assert.equal(await page.locator('#omoDocumentReaderPreview iframe').count(), 1, await page.locator('body').innerText());
        const frameElement = page.locator('#omoDocumentReaderPreview iframe');
        await frameElement.waitFor();
        const frame = await (await frameElement.elementHandle()).contentFrame();
        await frame.waitForFunction(() => typeof window.SocialCalc !== 'undefined', null, {timeout: 30000});
        for (const width of [1280, 390]) {
            await page.setViewportSize({width, height: 950});
            assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
            await page.screenshot({path: path.join(root, `tmp/document-share-sheet-${width}.png`)});
        }
        const native = new URL(fixture.sheetUrl);
        const wsBase = native.origin.replace(/^http/, 'ws') + '/_ws/' + fixture.room;
        await page.evaluate(({wsBase, auth, room}) => new Promise((resolve, reject) => {
            const ws = window.shareSocket = new WebSocket(wsBase + '?' + new URLSearchParams({auth}));
            window.shareMessages = [];
            let poll;
            ws.onopen = () => {
                ws.send(JSON.stringify({type: 'execute', room, auth, cmdstr: 'set A1 text t SHARE_BROWSER_EDIT'}));
                poll = setInterval(() => ws.send(JSON.stringify({type: 'ask.log', room, auth})), 300);
            };
            ws.onmessage = event => {
                const message = JSON.parse(event.data); window.shareMessages.push(message);
                if (message.type === 'log' && event.data.includes('SHARE_BROWSER_EDIT')) {clearInterval(poll); resolve();}
            };
            ws.onerror = () => reject(new Error('Shared sheet socket failed'));
            ws.onclose = event => {clearInterval(poll); reject(new Error('Shared sheet socket closed: ' + event.code));};
            setTimeout(() => reject(new Error('Shared sheet socket timed out')), 15000);
        }), {wsBase, auth: fixture.auth, room: fixture.room});
        assert((await page.evaluate(() => JSON.stringify(window.shareMessages))).includes('SHARE_BROWSER_EDIT'));
        // A regular editor and an external editor share the same room without exchanging credentials.
        await page.evaluate(({wsBase, auth, room}) => new Promise((resolve, reject) => {
            const ws = new WebSocket(wsBase + '?' + new URLSearchParams({auth}));
            ws.onopen = () => {
                ws.send(JSON.stringify({type: 'execute', room, auth, cmdstr: 'set A2 text t OWNER_BROWSER_EDIT'}));
                ws.send(JSON.stringify({type: 'ask.log', room, auth}));
            };
            ws.onmessage = event => { if (JSON.parse(event.data).type === 'log') {ws.close(); resolve();} };
            ws.onerror = () => reject(new Error('Owner sheet socket failed'));
            setTimeout(() => reject(new Error('Owner sheet socket timed out')), 15000);
        }), {wsBase, auth: fixture.ownerAuth, room: fixture.room});
        await page.waitForFunction(() => JSON.stringify(window.shareMessages).includes('OWNER_BROWSER_EDIT'));
        assert(!(await page.evaluate(() => JSON.stringify(window.shareMessages))).includes(fixture.ownerAuth), 'Peer broadcasts never disclose the owner credential');
        const request = context.request;
        assert.equal((await request.get(native.origin + '/_/' + fixture.room)).status(), 403);
        assert.equal((await request.get(native.origin + '/' + fixture.room + '/edit')).status(), 403);
        assert.equal((await request.delete(native.origin + '/_/' + fixture.room + '?auth=' + encodeURIComponent(fixture.auth))).status(), 403);
        const owner = await browser.newContext({ignoreHTTPSErrors: true});
        await owner.addCookies([fixture.ownerCookie]);
        const revoked = await owner.request.post(fixture.revokeUrl, {form: {id: fixture.documentId, share_id: fixture.shareId, _csrf: fixture.csrf}});
        assert.equal(revoked.status(), 200);
        await page.evaluate(({auth, room}) => window.shareSocket.send(JSON.stringify({type: 'execute', room, auth, cmdstr: 'set A1 text t REVOKED_WRITE'})), fixture);
        await page.waitForFunction(() => window.shareSocket.readyState === WebSocket.CLOSED);
        assert.equal((await context.request.get(fixture.url)).status(), 404);
        await owner.close();
        console.log('document_share_browser_test: OK (real EtherCalc edits, owner/shared collaboration, no credential disclosure, direct access blocked, revocation, mobile/desktop)');
    } finally {
        await browser.close();
        if (fs.existsSync(file)) fs.unlinkSync(file);
    }
})().catch(error => {console.error(error); process.exitCode = 1;});
