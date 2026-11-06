'use strict';
// Run against local Docker only, with disposable CLI fixtures (see README).
const fs = require('node:fs');
const assert = require('node:assert/strict');
const puppeteer = require('puppeteer-core');
const pause = ms => new Promise(resolve => setTimeout(resolve, ms));

(async () => {
    const fixture = JSON.parse(fs.readFileSync(process.env.VISIO_FIXTURE_FILE, 'utf8'));
    assert.equal(new URL(fixture.origin).hostname, 'org1.localtest.me');
    const browser = await puppeteer.launch({executablePath:process.env.PUPPETEER_EXECUTABLE_PATH,
        headless:true, acceptInsecureCerts:true, args:['--no-sandbox','--ignore-certificate-errors','--disable-dev-shm-usage',
            '--use-fake-ui-for-media-stream','--use-fake-device-for-media-stream','--autoplay-policy=no-user-gesture-required',
            '--disable-background-timer-throttling','--disable-renderer-backgrounding',
            ...(process.env.VISIO_TEST_FORCE_IPV4 === '1' ? ['--host-resolver-rules=MAP *.localtest.me 127.0.0.1'] : [])]});
    try {
        async function pageFor(identity) {
            const context = await browser.createBrowserContext();
            for (const origin of [fixture.origin, 'https://meet.localtest.me']) await context.overridePermissions(origin, ['camera','microphone']);
            if (identity) await context.setCookie({name:fixture.cookie, value:identity.session, domain:'org1.localtest.me', path:'/', secure:true, httpOnly:true});
            const page = await context.newPage();
            page.setDefaultTimeout(45000);
            await page.setViewport({width:1440,height:1100});
            await page.evaluateOnNewDocument(() => {
                window.__videoConnections = [];
                window.RTCPeerConnection = new Proxy(window.RTCPeerConnection, {construct(Target,args,NewTarget) {
                    const connection = Reflect.construct(Target,args,NewTarget);
                    window.__videoConnections.push(connection);
                    return connection;
                }});
            });
            return page;
        }
        async function frameFor(page) {
            await page.waitForSelector('#visio-frame iframe');
            const handle = await page.$('#visio-frame iframe');
            const frame = await handle.contentFrame();
            await frame.waitForFunction(() => !!window.APP?.store);
            await frame.waitForFunction(() => APP.store.getState()['features/base/tracks'].filter(t=>t.local).length >= 2);
            await frame.locator('[data-testid="prejoin.joinMeeting"]').click();
            return frame;
        }
        async function joined(frame, role) {
            await frame.waitForFunction(() => APP.store.getState()['features/base/conference']?.conference?.isJoined());
            await frame.waitForFunction(expected => APP.store.getState()['features/base/participants'].local.role === expected, {}, role);
        }
        const owner = await pageFor(fixture.users[0]);
        await owner.goto(fixture.origin + '/visio/', {waitUntil:'domcontentloaded'});
        await owner.locator('#room-title').fill('Réunion vidéo locale de vérification');
        await Promise.all([owner.waitForNavigation({waitUntil:'domcontentloaded'}), owner.locator('form button[type="submit"]').click()]);
        const invitation = await owner.$eval('#visio-share', input=>input.value);
        assert.match(invitation, /^https:\/\/org1\.localtest\.me\/visio\/[a-f0-9]{32}$/);
        console.log('OMO room created; loading the Jitsi iframe.');
        const hostFrame = await frameFor(owner);
        await joined(hostFrame, 'moderator');
        assert.equal(await hostFrame.evaluate(() => APP.store.getState()['features/base/participants'].local.name), 'Owner Browser Fixture');
        console.log('OMO creation and iframe: creator identity recognized, moderator role.');

        const member = await pageFor(fixture.users[1]);
        await member.goto(invitation, {waitUntil:'domcontentloaded'});
        const memberFrame = await frameFor(member);
        await joined(memberFrame, 'participant');
        assert.equal(await memberFrame.evaluate(() => APP.store.getState()['features/base/participants'].local.name), 'Member Browser Fixture');
        console.log('Second OMO account: direct access as participant.');

        const guest = await pageFor();
        await guest.goto(invitation, {waitUntil:'domcontentloaded'});
        await guest.locator('#guest-name').fill('Local browser guest');
        await Promise.all([guest.waitForNavigation({waitUntil:'domcontentloaded'}), guest.locator('form button[type="submit"]').click()]);
        const guestFrame = await frameFor(guest);
        await hostFrame.waitForSelector('button::-p-text(Accepter)');
        assert.equal(await guestFrame.evaluate(() => !!APP.store.getState()['features/base/conference']?.conference?.isJoined()), false);
        await hostFrame.locator('button::-p-text(Accepter)').click();
        await joined(guestFrame, 'participant');
        console.log('Anonymous invitation: lobby required, then participant after admission.');

        const frames = [hostFrame, memberFrame, guestFrame];
        async function media(frame) {
            return frame.evaluate(async () => {
                const result = {videoFrames:0,audioPackets:0,sentBytes:0,bridgeUdp:false};
                for (const connection of window.__videoConnections) {
                    if (connection.connectionState !== 'connected') continue;
                    const stats = await connection.getStats();
                    for (const item of stats.values()) {
                        if (item.type === 'inbound-rtp' && item.kind === 'video') result.videoFrames += item.framesDecoded || 0;
                        if (item.type === 'inbound-rtp' && item.kind === 'audio') result.audioPackets += item.packetsReceived || 0;
                        if (item.type === 'outbound-rtp') result.sentBytes += item.bytesSent || 0;
                        if (item.type === 'transport' && item.selectedCandidatePairId) {
                            const pair = stats.get(item.selectedCandidatePairId);
                            const remote = pair && stats.get(pair.remoteCandidateId);
                            if (remote?.protocol === 'udp' && remote.port === 10000) result.bridgeUdp = true;
                        }
                    }
                }
                return result;
            });
        }
        let samples;
        for (let attempt=0; attempt<30; attempt++) {
            samples = await Promise.all(frames.map(media));
            if (samples.every(s=>s.videoFrames && s.audioPackets && s.sentBytes && s.bridgeUdp)) break;
            await pause(1000);
        }
        console.log('Media samples: ' + JSON.stringify(samples));
        assert.ok(samples.every(s=>s.videoFrames && s.audioPackets && s.sentBytes && s.bridgeUdp), 'All three browsers receive real media through local UDP 10000');
        await pause(2500);
        const later = await Promise.all(frames.map(media));
        assert.ok(later.every((s,i)=>s.videoFrames>samples[i].videoFrames && s.audioPackets>samples[i].audioPackets), 'Audio/video keeps flowing');
        if (process.env.VISIO_SCREENSHOT_FILE) await owner.screenshot({path:process.env.VISIO_SCREENSHOT_FILE, fullPage:true});
        console.log('Local OMO + Jitsi end-to-end: identity, roles, lobby, WebSockets and three-way audio/video OK.');
    } catch (error) {
        for (const page of await browser.pages()) {
            for (const frame of page.frames()) console.error(await frame.evaluate(() => document.body.innerText.slice(-1600)).catch(()=>'Frame unavailable'));
        }
        throw error;
    } finally { await browser.close(); }
})().catch(error => { console.error(error.name + ': ' + error.message.replace(/jwt=[^\s"']+/g,'jwt=[redacted]')); process.exitCode=1; });
