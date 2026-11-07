'use strict';
// Run against local Docker only, with disposable CLI fixtures (see README).
const fs = require('node:fs');
const assert = require('node:assert/strict');
const puppeteer = require('puppeteer-core');
const crypto = require('node:crypto');
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
        async function frameFor(page, muted = false, guestName = 'Local browser guest') {
            await page.waitForSelector('#visio-frame iframe');
            const handle = await page.$('#visio-frame iframe');
            const frame = await handle.contentFrame();
            await frame.waitForFunction(() => !!window.APP?.store);
            if (!muted) await frame.waitForFunction(() => APP.store.getState()['features/base/tracks'].filter(t=>t.local).length >= 2);
            if (muted) await frame.waitForFunction(() => APP.store.getState()['features/base/media'].audio.muted && APP.store.getState()['features/base/media'].video.muted);
            if (await page.$eval('#visio-config',el=>!!JSON.parse(el.textContent).access.settings.camera_restricted)) {
                assert.equal(await frame.evaluate(()=>document.featurePolicy.allowsFeature('camera')),false,'Camera must already be blocked before joining');
                const cameraStarted = await frame.evaluate(async()=>{
                    try { const stream=await navigator.mediaDevices.getUserMedia({video:true}); stream.getTracks().forEach(track=>track.stop()); return true; }
                    catch (_) { return false; }
                });
                assert.equal(cameraStarted,false,'Prejoin/lobby cannot capture a blocked camera');
                assert.equal(await frame.evaluate(async()=>{
                    try { const stream=await navigator.mediaDevices.getUserMedia({audio:true}); stream.getTracks().forEach(track=>track.stop()); return true; }
                    catch (_) { return false; }
                }),true,'Blocking the camera must preserve microphone access');
            }
            if (!await page.$eval('#visio-config',el=>JSON.parse(el.textContent).access.name)) {
                const nameInput = await frame.$('input[placeholder="Saisissez votre nom"]') || await frame.$('input[type="text"]');
                assert.ok(nameInput, 'Jitsi asks the anonymous guest for a name');
                await nameInput.click({clickCount:3});
                await nameInput.type(guestName);
            }
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
        assert.equal(await guest.$('#guest-name'),null,'OMO does not ask for the guest name');
        await Promise.all([guest.waitForNavigation({waitUntil:'domcontentloaded'}), guest.locator('form button[type="submit"]').click()]);
        const guestFrame = await frameFor(guest);
        await hostFrame.waitForSelector('button::-p-text(Accepter)');
        assert.equal(await guestFrame.$('button::-p-text(Saisissez un mot de passe de réunion)'),null,'No native password button when no password is configured');
        assert.equal(await guestFrame.evaluate(() => !!APP.store.getState()['features/base/conference']?.conference?.isJoined()), false);
        for (let attempt=0; attempt<3; attempt++) {
            await hostFrame.locator('button::-p-text(Accepter)').click();
            try { await guestFrame.waitForFunction(()=>APP.store.getState()['features/base/conference']?.conference?.isJoined(), {timeout:8000}); break; }
            catch (error) { if (attempt===2 || !await hostFrame.$('button::-p-text(Accepter)')) throw error; }
        }
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

        await member.browserContext().close();
        await guest.browserContext().close();
        await owner.goto(invitation + '?edit=1', {waitUntil:'domcontentloaded'});
        assert.equal(await owner.$('#visio-frame iframe'), null, 'Editing must not join the meeting');
        await owner.locator('#title').fill('Salle configurée par groupe');
        await owner.select('#IDorganization', String(fixture.organization));
        await owner.evaluate(() => {
            for (const field of ['member_audio_muted','member_video_muted','guest_audio_muted','guest_video_muted','guest_camera_restricted','guest_screen_restricted']) document.querySelector('input[type="checkbox"][name="'+field+'"]').checked=true;
            document.querySelector('input[type="checkbox"][name="guest_lobby"]').checked=false;
        });
        await Promise.all([owner.waitForNavigation({waitUntil:'domcontentloaded'}),owner.locator('button[form="formulaire-edit"]').click()]);
        assert.equal(await owner.$eval('#title',input=>input.value),'Salle configurée par groupe');
        await pause(1500);
        await owner.goto(invitation, {waitUntil:'domcontentloaded'});
        const configuredHost = await frameFor(owner);
        await joined(configuredHost,'moderator');
        const configuredMember = await pageFor(fixture.users[1]);
        await configuredMember.goto(invitation,{waitUntil:'domcontentloaded'});
        const configuredMemberFrame = await frameFor(configuredMember,true);
        await joined(configuredMemberFrame,'participant');
        await configuredMemberFrame.waitForFunction(()=>APP.store.getState()['features/av-moderation'].videoUnmuteApproved && APP.store.getState()['features/av-moderation'].desktopUnmuteApproved);
        console.log('Organization member: starts muted, camera and screen remain permitted.');
        const outsider = await pageFor(fixture.users[2]);
        await outsider.goto(invitation,{waitUntil:'domcontentloaded'});
        assert.equal(await outsider.$eval('#visio-config',e=>JSON.parse(e.textContent).access.role),'guest');
        const outsiderFrame = await frameFor(outsider,true);
        await joined(outsiderFrame,'participant');
        await outsiderFrame.waitForFunction(()=>APP.store.getState()['features/av-moderation'].videoModerationEnabled && APP.store.getState()['features/av-moderation'].desktopModerationEnabled);
        assert.equal(await outsiderFrame.evaluate(()=>!!APP.store.getState()['features/av-moderation'].videoUnmuteApproved),false);
        assert.equal(await outsiderFrame.evaluate(()=>!!APP.store.getState()['features/av-moderation'].desktopUnmuteApproved),false);
        console.log('Other OMO account: guest policy, no lobby, camera blocked and screen approval required.');
        const anonymous = await pageFor();
        await anonymous.goto(invitation,{waitUntil:'domcontentloaded'});
        await Promise.all([anonymous.waitForNavigation({waitUntil:'domcontentloaded'}),anonymous.locator('form button[type="submit"]').click()]);
        const anonymousFrame = await frameFor(anonymous,true,'Guest without a lobby');
        await joined(anonymousFrame,'participant');
        assert.equal(await anonymousFrame.evaluate(()=>!!APP.store.getState()['features/base/media'].video.muted),true);
        console.log('Anonymous guest joins without a lobby; camera capture denied before entry and remains muted.');
        const outsiderId = await outsiderFrame.evaluate(()=>APP.store.getState()['features/base/participants'].local.id);
        await configuredHost.evaluate(id=>APP.store.getState()['features/base/conference'].conference.avModerationApprove('desktop',id),outsiderId);
        await outsiderFrame.waitForFunction(()=>APP.store.getState()['features/av-moderation'].desktopUnmuteApproved);
        assert.equal(await anonymousFrame.evaluate(()=>!!APP.store.getState()['features/av-moderation'].desktopUnmuteApproved),false,'Approval is individual');
        assert.equal(await outsiderFrame.evaluate(()=>!!APP.store.getState()['features/av-moderation'].videoUnmuteApproved),false,'Screen approval does not grant camera access');
        console.log('Moderator approves screen sharing for one guest; other guests and camera remain restricted.');
        await Promise.all([configuredMember,outsider,anonymous].map(page=>page.browserContext().close()));
        await owner.goto(invitation+'?edit=1',{waitUntil:'domcontentloaded'});
        const password = crypto.randomBytes(12).toString('hex');
        await owner.locator('#room-password').fill(password);
        await owner.evaluate(()=>{
            for(const name of ['member_lobby','guest_lobby']) document.querySelector('input[type="checkbox"][name="'+name+'"]').checked=true;
        });
        await Promise.all([owner.waitForNavigation({waitUntil:'domcontentloaded'}),owner.locator('button[form="formulaire-edit"]').click()]);
        assert.equal(await owner.$eval('#room-password',input=>input.value),'','Configured password is never redisplayed');
        await pause(1500);
        async function guestInLobby(name) {
            const page=await pageFor();
            await page.goto(invitation,{waitUntil:'domcontentloaded'});
            await Promise.all([page.waitForNavigation({waitUntil:'domcontentloaded'}),page.locator('form button[type="submit"]').click()]);
            const frame=await frameFor(page,true,name);
            await frame.waitForFunction(()=>/demander à rejoindre|vous allez rejoindre|waiting|attente/i.test(document.body.innerText));
            assert.equal(await frame.evaluate(()=>!!APP.store.getState()['features/base/conference']?.conference?.isJoined()),false);
            return {page,frame};
        }
        const waiting=await guestInLobby('Guest waiting without password');
        assert.equal(await waiting.page.$('#bypass-password,#guest-password'),null,'Password input stays inside Jitsi');
        async function nativePassword(page,frame,value) {
            if (!await frame.$('input[type="password"]')) await frame.locator('button::-p-text(Saisissez un mot de passe de réunion)').click();
            await frame.locator('input[type="password"]').fill(value);
            await frame.locator('button::-p-text(Rejoindre la réunion)').click();
        }
        await nativePassword(waiting.page,waiting.frame,'wrong');
        await pause(1500);
        assert.equal(await waiting.frame.evaluate(()=>!!APP.store.getState()['features/base/conference']?.conference?.isJoined()),false,'Wrong native password keeps the guest outside');
        await nativePassword(waiting.page,waiting.frame,password);
        const bypassFrame=waiting.frame;
        await joined(bypassFrame,'participant');
        assert.equal(await waiting.page.$eval('#visio-config',el=>JSON.parse(el.textContent).access.moderator),false);
        assert.equal(await bypassFrame.evaluate(()=>!!APP.store.getState()['features/av-moderation'].desktopUnmuteApproved),false,'Password does not grant screen sharing');
        await guestInLobby('Another guest still waiting');
        const memberWithPassword=await pageFor(fixture.users[1]);
        await memberWithPassword.goto(invitation,{waitUntil:'domcontentloaded'});
        const memberPasswordFrame=await frameFor(memberWithPassword,true);
        await memberPasswordFrame.waitForFunction(()=>/demander à rejoindre|vous allez rejoindre|waiting|attente/i.test(document.body.innerText));
        await nativePassword(memberWithPassword,memberPasswordFrame,password);
        await joined(memberPasswordFrame,'participant');
        console.log('Native Jitsi password: wrong password denied, guests and members enter without an organizer; other visitors keep waiting and role restrictions remain.');
    } catch (error) {
        for (const page of await browser.pages()) {
            for (const frame of page.frames()) console.error(await frame.evaluate(() => document.body.innerText.slice(-1600)).catch(()=>'Frame unavailable'));
        }
        throw error;
    } finally { await browser.close(); }
})().catch(error => { console.error(error.name + ': ' + error.message.replace(/jwt=[^\s"']+/g,'jwt=[redacted]')); process.exitCode=1; });
