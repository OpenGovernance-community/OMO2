'use strict';

// Optional live test: synthetic media only, isolated browser identities and room.
const puppeteer = require('puppeteer-core');
const assert = require('node:assert/strict');
const crypto = require('node:crypto');
const pause = ms => new Promise(resolve => setTimeout(resolve, ms));

(async () => {
    assert.ok(process.env.JITSI_USERNAME && process.env.JITSI_PASSWORD, 'Organizer credentials are required');
    const origin = process.env.JITSI_TEST_URL || 'https://meet.opengov.tools';
    const room = 'omoverification' + crypto.randomBytes(10).toString('hex');
    const browser = await puppeteer.launch({executablePath: process.env.PUPPETEER_EXECUTABLE_PATH || '/usr/bin/chromium',
        headless: true, args: ['--no-sandbox', '--disable-dev-shm-usage', '--use-fake-ui-for-media-stream',
            '--use-fake-device-for-media-stream', '--autoplay-policy=no-user-gesture-required',
            '--disable-background-timer-throttling', '--disable-renderer-backgrounding',
            ...(process.env.JITSI_TEST_IP ? ['--host-resolver-rules=MAP ' + new URL(origin).hostname + ' ' + process.env.JITSI_TEST_IP] : [])]});
    try {
        async function participant(name) {
            const context = await browser.createBrowserContext();
            await context.overridePermissions(origin, ['camera', 'microphone']);
            const page = await context.newPage();
            await page.setViewport({width: 1440, height: 1000});
            await page.evaluateOnNewDocument(() => {
                window.__testPeerConnections = [];
                window.__testMediaRequests = [];
                window.__testErrors = [];
                window.addEventListener('error', e => window.__testErrors.push(e.message));
                window.addEventListener('unhandledrejection', e => window.__testErrors.push(String(e.reason?.message || e.reason)));
                const gum = navigator.mediaDevices.getUserMedia.bind(navigator.mediaDevices);
                navigator.mediaDevices.getUserMedia = async constraints => {
                    const item = {audio:!!constraints.audio, video:!!constraints.video, state:'pending'};
                    window.__testMediaRequests.push(item);
                    try {
                        const stream = await gum(constraints);
                        item.state = 'ready';
                        return stream;
                    } catch (error) {
                        item.state = error.name;
                        throw error;
                    }
                };
                window.RTCPeerConnection = new Proxy(window.RTCPeerConnection, {
                    construct(Target, args, NewTarget) {
                        const connection = Reflect.construct(Target, args, NewTarget);
                        window.__testPeerConnections.push(connection);
                        return connection;
                    }
                });
            });
            await page.goto(origin + '/' + room + '#config.p2p.enabled=false'
                + '&userInfo.displayName=' + encodeURIComponent(JSON.stringify(name)), {waitUntil: 'domcontentloaded'});
            await page.waitForFunction(() => window.APP && APP.store);
            await page.waitForFunction(() => APP.store.getState()['features/base/tracks'].filter(t=>t.local).length >= 2);
            await page.locator('#premeeting-name-input').fill(name);
            await page.locator('[data-testid="prejoin.joinMeeting"]').click();
            return page;
        }
        const anonymous = await participant('OMO2 test visitor');
        await anonymous.waitForFunction(() => APP.store.getState()['features/base/conference']?.authRequired);
        assert.equal(await anonymous.evaluate(() => !!APP.store.getState()['features/base/conference']?.conference), false);
        console.log('Guest cannot start a meeting alone: OK');
        await anonymous.browserContext().close();

        const host = await participant('OMO2 test organizer');
        await host.waitForSelector('button::-p-text(Je suis l\'hôte)');
        await host.locator('button::-p-text(Je suis l\'hôte)').click();
        await host.waitForSelector('#login-dialog-username');
        await host.type('#login-dialog-username', process.env.JITSI_USERNAME);
        await host.type('#login-dialog-password', process.env.JITSI_PASSWORD);
        await host.locator('button::-p-text(Connexion)').click();
        await host.waitForFunction(() => APP.store.getState()['features/base/conference']?.conference?.isJoined());
        const nameInput = await host.$('#dialog-displayName');
        if (nameInput) {
            await nameInput.type('OMO2 test organizer');
            await host.click('#modal-dialog-ok-button');
        }
        await host.waitForFunction(() => APP.store.getState()['features/base/participants'].local.role === 'moderator');
        console.log('Organizer authentication and moderator role: OK');
        await host.evaluate(() => APP.store.getState()['features/base/conference'].conference.enableLobby());
        console.log('Lobby activated by the moderator.');
        const guests = [];
        for (const name of ['OMO2 test guest one', 'OMO2 test guest two']) {
            const guest = await participant(name);
            await guest.waitForFunction(() => document.body.innerText.includes('Demander à rejoindre')
                || document.body.innerText.includes('Vous allez rejoindre une réunion dès que')
                || APP.store.getState()['features/base/conference']?.conference?.isJoined());
            if (await guest.$('#lobby-name-field')) await guest.locator('#lobby-name-field').fill(name);
            if (await guest.$('button::-p-text(Demander à rejoindre)')) {
                await guest.locator('button::-p-text(Demander à rejoindre)').click();
            }
            await host.waitForSelector('button::-p-text(Accepter)');
            assert.equal(await guest.evaluate(() => !!APP.store.getState()['features/base/conference']?.conference?.isJoined()), false);
            await host.locator('button::-p-text(Accepter)').click();
            await guest.waitForFunction(() => APP.store.getState()['features/base/conference']?.conference?.isJoined());
            await guest.waitForFunction(() => APP.store.getState()['features/base/participants'].local.role === 'participant');
            guests.push(guest);
            console.log('Guest admitted from the lobby with participant role: OK');
        }
        const pages = [host, ...guests];
        for (const page of pages) {
            await page.waitForFunction(() => APP.store.getState()['features/base/conference'].conference.getParticipants().length === 2);
        }
        async function media(page) {
            return page.evaluate(async () => {
                const result = {videoFrames:0, audioPackets:0, sentBytes:0, bridgeUdp:false};
                for (const connection of window.__testPeerConnections) {
                    if (connection.connectionState !== 'connected') continue;
                    const stats = await connection.getStats();
                    for (const item of stats.values()) {
                        if (item.type === 'inbound-rtp' && item.kind === 'video') result.videoFrames += item.framesDecoded || 0;
                        if (item.type === 'inbound-rtp' && item.kind === 'audio') result.audioPackets += item.packetsReceived || 0;
                        if (item.type === 'outbound-rtp') result.sentBytes += item.bytesSent || 0;
                        if (item.type === 'transport' && item.selectedCandidatePairId) {
                            const pair = stats.get(item.selectedCandidatePairId);
                            const remote = pair && stats.get(pair.remoteCandidateId);
                            if (remote && remote.protocol === 'udp' && remote.port === 10000) result.bridgeUdp = true;
                        }
                    }
                }
                return result;
            });
        }
        let samples;
        for (let attempt=0; attempt<25; attempt++) {
            samples = await Promise.all(pages.map(media));
            if (samples.every(s => s.videoFrames > 0 && s.audioPackets > 0 && s.sentBytes > 0 && s.bridgeUdp)) break;
            await pause(1000);
        }
        console.log('Media samples: ' + JSON.stringify(samples));
        if (!samples.every(s=>s.videoFrames && s.audioPackets && s.sentBytes)) {
            for (const page of pages) console.log('Media diagnostic: '+await page.evaluate(async()=>JSON.stringify({
                text:document.body.innerText.slice(-2200),
                media:APP.store.getState()['features/base/media'],
                mediaRequests:window.__testMediaRequests,
                errors:window.__testErrors,
                tracks:APP.store.getState()['features/base/tracks'].map(t=>({local:t.local,mediaType:t.mediaType,muted:t.muted,type:t.jitsiTrack?.getType()})),
                devices:(await navigator.mediaDevices.enumerateDevices()).map(d=>({kind:d.kind,label:d.label}))
            })));
        }
        assert.ok(samples.every(s => s.videoFrames > 0 && s.audioPackets > 0 && s.sentBytes > 0 && s.bridgeUdp),
            'Every participant must send media and decode audio/video through UDP 10000');
        await pause(2500);
        const later = await Promise.all(pages.map(media));
        assert.ok(later.every((s,i) => s.videoFrames > samples[i].videoFrames && s.audioPackets > samples[i].audioPackets),
            'Media must keep flowing for all three participants');
        console.log('Three-party audio/video through the public videobridge, with lobby admission: OK');
    } finally {
        await browser.close();
    }
})().catch(error => {
    console.error(String(error.stack || error.message).replaceAll(process.env.JITSI_PASSWORD || 'unused-private-password', '[redacted]'));
    process.exitCode = 1;
});
