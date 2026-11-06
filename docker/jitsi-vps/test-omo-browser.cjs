'use strict';
// Isolated JWT profile only. Never run this against the temporary internal-auth profile.
const puppeteer = require('puppeteer-core');
const assert = require('node:assert/strict');
const crypto = require('node:crypto');
const fs = require('node:fs');

(async () => {
    const secret = process.env.JITSI_TEST_SECRET_FILE
        ? fs.readFileSync(process.env.JITSI_TEST_SECRET_FILE, 'utf8').trim() : process.env.JWT_APP_SECRET;
    assert.ok(secret?.length >= 32, 'A private JWT test secret is required');
    const origin = process.env.JITSI_TEST_URL || 'https://visio-jitsi.localtest.me:8443';
    const room = 'omo-' + crypto.randomBytes(16).toString('hex');
    function token(name, moderator, bypass, signedRoom = room) {
        const encode = data => Buffer.from(JSON.stringify(data)).toString('base64url');
        const now = Math.floor(Date.now() / 1000);
        const body = encode({alg:'HS256',typ:'JWT'}) + '.' + encode({iss:'omo2',aud:'omo2',sub:'meet.jitsi',
            room:signedRoom,iat:now,nbf:now-10,exp:now+600,
            context:{user:{id:crypto.randomBytes(16).toString('hex'),name,moderator,
                affiliation:moderator?'owner':'member',lobby_bypass:bypass}}});
        return body + '.' + crypto.createHmac('sha256', secret).update(body).digest('base64url');
    }
    const browser = await puppeteer.launch({executablePath:'/usr/bin/chromium',headless:true,
        acceptInsecureCerts:process.env.JITSI_TEST_INSECURE === '1',args:['--no-sandbox','--disable-dev-shm-usage',
            '--use-fake-ui-for-media-stream','--use-fake-device-for-media-stream','--autoplay-policy=no-user-gesture-required',
            '--disable-background-timer-throttling','--disable-renderer-backgrounding',
            ...(process.env.JITSI_TEST_INSECURE === '1' ? ['--ignore-certificate-errors'] : [])]});
    try {
        async function participant(name, jwt) {
            const context = await browser.createBrowserContext();
            await context.overridePermissions(origin, ['camera','microphone']);
            const page = await context.newPage();
            page.setDefaultTimeout(45000);
            await page.setViewport({width:1440,height:1000});
            await page.goto(origin + '/' + room + (jwt ? '?jwt=' + encodeURIComponent(jwt) : '')
                + '#config.p2p.enabled=false', {waitUntil:'domcontentloaded'});
            await page.waitForFunction(() => window.APP?.store);
            await page.waitForFunction(() => APP.store.getState()['features/base/tracks'].filter(t=>t.local).length >= 2);
            await page.locator('#premeeting-name-input').fill(name);
            await page.locator('[data-testid="prejoin.joinMeeting"]').click();
            return page;
        }
        async function joined(page, role) {
            await page.waitForFunction(() => APP.store.getState()['features/base/conference']?.conference?.isJoined());
            await page.waitForFunction(expected => APP.store.getState()['features/base/participants'].local.role === expected, {}, role);
        }
        const earlyGuest = await participant('Early guest', token('Early guest', false, false));
        await earlyGuest.waitForFunction(() => /demander à rejoindre|vous allez rejoindre|waiting|attente/i.test(document.body.innerText)
            || APP.store.getState()['features/base/conference']?.conference?.isJoined());
        assert.equal(await earlyGuest.evaluate(() => !!APP.store.getState()['features/base/conference']?.conference?.isJoined()), false);
        console.log('Guest arriving before the organizer remains outside the conference.');
        await earlyGuest.close();
        // A participant arriving first must not acquire moderator powers.
        const member = await participant('OMO member', token('OMO member', false, true));
        await joined(member, 'participant');
        console.log('Authenticated participant arriving first: participant, no auto-owner.');
        const host = await participant('OMO owner', token('OMO owner', true, true));
        await joined(host, 'moderator');
        console.log('OMO creator: moderator.');
        const guest = await participant('Guest', token('Guest', false, false));
        await host.waitForSelector('button::-p-text(Accepter)');
        assert.equal(await guest.evaluate(() => !!APP.store.getState()['features/base/conference']?.conference?.isJoined()), false);
        await host.locator('button::-p-text(Accepter)').click();
        await joined(guest, 'participant');
        console.log('External guest: lobby required, then participant after admission.');
        await joined(member, 'participant');
        for (const [name, jwt] of [['No token', null], ['Wrong room',token('Wrong room',true,true,'another-room')]]) {
            const rejected = await participant(name, jwt);
            await rejected.waitForFunction(() => /authentification|authentication|autorisé|not.allowed|token|jeton/i.test(document.body.innerText)
                || !!APP.store.getState()['features/base/conference'].error);
            assert.equal(await rejected.evaluate(() => !!APP.store.getState()['features/base/conference']?.conference?.isJoined()), false);
            await rejected.close();
            console.log(name + ': denied.');
        }
        const media = async page => page.evaluate(async () => {
            const conference = APP.store.getState()['features/base/conference'].conference;
            const stats = conference.getStats?.();
            return {count:conference.getParticipants().length, name:APP.store.getState()['features/base/participants'].local.name,
                remoteTracks:APP.store.getState()['features/base/tracks'].filter(t=>!t.local).length};
        });
        for (const page of [host,member,guest]) {
            await page.waitForFunction(() => APP.store.getState()['features/base/tracks'].filter(t=>!t.local).length >= 2);
            const result = await media(page);
            assert.equal(result.count,2);
            assert.ok(result.remoteTracks >= 2);
        }
        console.log('Three participants receive remote media tracks; ownership remains unchanged.');
    } catch (error) {
        for (const page of await browser.pages()) {
            console.error(await page.evaluate(() => ({text:document.body.innerText.slice(-1800),
                joined:!!window.APP?.store?.getState()['features/base/conference']?.conference?.isJoined(),
                role:window.APP?.store?.getState()['features/base/participants']?.local?.role})));
        }
        throw error;
    } finally { await browser.close(); }
})().catch(error => { console.error(error.name + ': ' + error.message.replace(/jwt=[^\s"']+/g,'jwt=[redacted]')); process.exitCode = 1; });
