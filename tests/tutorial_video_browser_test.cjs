'use strict';
const assert = require('node:assert/strict');
const {spawn} = require('node:child_process');
const {createInterface} = require('node:readline');
const {chromium} = require(process.argv[2] || 'playwright');
const vimeoStub = `window.Vimeo = {Player: class {
    constructor() { this.handlers = {}; window.videoTestPlayer = this; this.volume = 1; }
    getVideoWidth() { return Promise.resolve(640); } getVideoHeight() { return Promise.resolve(360); }
    getDuration() { return Promise.resolve(120); } getPaused() { return Promise.resolve(true); }
    getVolume() { return Promise.resolve(this.volume); } setVolume(v) { this.volume = v; return Promise.resolve(); }
    on(name, handler) { this.handlers[name] = handler; }
    play() { this.played = true; this.handlers.play?.(); return Promise.resolve(); }
    pause() { this.handlers.pause?.(); return Promise.resolve(); }
    unload() { return Promise.resolve(); } setCurrentTime(v) { this.time = v; return Promise.resolve(); }
}};`;
(async () => {
    const fixture = spawn('docker', ['compose', 'exec', '-T', 'app', 'php', '-d', 'display_errors=1', 'tests/tutorial_video_browser_fixture.php'], {stdio: ['pipe', 'pipe', 'inherit']});
    const closed = new Promise(resolve => fixture.on('close', resolve));
    const lines = createInterface({input: fixture.stdout});
    let browser;
    try {
        const first = await lines[Symbol.asyncIterator]().next();
        const c = JSON.parse(first.value);
        browser = await chromium.launch({headless: true, channel: 'chrome'});
        const admin = await browser.newContext({ignoreHTTPSErrors: true, viewport: {width: 1200, height: 900}, extraHTTPHeaders: {Origin: c.origin}});
        const guest = await browser.newContext({ignoreHTTPSErrors: true});
        for (const context of [admin, guest]) {
            await context.addInitScript(() => { window.turnstile = {ready: cb => cb(), render: () => 'fixture', reset() {}, remove() {}, execute() {}, getResponse: () => ''}; });
        }
        await guest.route('https://player.vimeo.com/api/player.js', route => route.fulfill({contentType: 'text/javascript', body: vimeoStub}));
        await guest.route('https://player.vimeo.com/video/**', route => route.fulfill({contentType: 'text/html', body: '<p>Video fixture</p>'}));
        await guest.route('https://www.youtube-nocookie.com/**', route => route.fulfill({contentType: 'text/html', body: '<p>YouTube fixture</p>'}));
        // Rendering does not depend on the old local list-position migration.
        await guest.route('**/getmissions.php?*', route => route.fulfill({contentType: 'application/json', body: '{"html":"","progress":0}'}));
        const view = await guest.newPage();
        const errors = [];
        view.on('pageerror', error => errors.push(error.message));
        const publicUrl = `${c.origin}/omo/api/lms/parcours.php?catalog=basic&idp=${c.id}&mid=${c.vimeo}`;
        await view.goto(publicUrl);
        await view.locator('#missionVideoPlayer').waitFor();
        assert.equal(await view.locator('#missionVideoPlayer').getAttribute('data-video-control-mode'), 'native');
        assert.equal(await view.locator('.branding-overlay, .custom-controls').count(), 0);
        const settingsUrl = c.origin + '/omo/api/parameters/tutorials/index.php?oid=' + c.oid;
        const endpoint = c.origin + '/omo/api/parameters/tutorials/save.php?oid=' + c.oid;
        assert.equal((await guest.request.post(endpoint, {form: {customVideoLayout: '1'}})).status(), 401);
        const login = await admin.request.post(c.origin + '/common/login_password.php', {form: {email: c.email, password: c.password}, headers: {'X-Requested-With': 'XMLHttpRequest'}});
        assert.equal((await login.json()).status, 'ok');
        assert.equal((await admin.request.get(settingsUrl)).status(), 403);
        await admin.request.post(c.origin + '/common/admin_mode.php', {form: {enabled: '1', organization_id: c.oid}, headers: {'X-Requested-With': 'XMLHttpRequest'}});
        const page = await admin.newPage();
        page.on('pageerror', error => errors.push(error.message));
        await page.goto(c.origin + '/omo/o/' + c.oid);
        assert.match(await (await admin.request.get(c.origin + '/omo/api/parameters/index.php')).text(), /parameters\/tutorials\/index.php/);
        const loadForm = async () => {
            const result = await page.evaluate(async url => {
            const host = document.createElement('div'); document.body.replaceChildren(host);
            const response = await fetch(url); host.innerHTML = await response.text();
            if (!response.ok) return {status: response.status, body: host.innerText};
            await window.commonExecuteFragmentScripts(host);
            return {status: response.status, initialized: host.querySelector('form')?.dataset.tutorialSettingsReady};
            }, settingsUrl);
            assert.equal(result.status, 200, JSON.stringify(result));
            assert.equal(result.initialized, '1', 'Settings form scripts initialize.');
        };
        await loadForm();
        const form = page.locator('[data-omo-tutorial-settings]');
        const checkbox = form.locator('[name="customVideoLayout"]');
        assert.equal(await checkbox.isChecked(), false);
        assert.equal(await form.locator('[data-tutorial-mask]').isVisible(), false);
        const csrf = await form.locator('[name="csrf"]').inputValue();
        const data = {csrf, organization_id: c.oid, customVideoLayout: '1', videoMask: '/img/omo2/flow-information.png'};
        const methodResponse = await admin.request.get(endpoint);
        assert.equal(methodResponse.status(), 405, (await methodResponse.text()).slice(0, 1000));
        for (const invalid of [{...data, csrf: 'bad'}, {...data, organization_id: c.otherOid}]) assert.equal((await admin.request.post(endpoint, {form: invalid})).status(), 403);
        for (const mask of ['javascript:alert(1)', '//example.invalid/mask.png', '/img/../mask.png']) assert.equal((await admin.request.post(endpoint, {form: {...data, videoMask: mask}})).status(), 422);
        await checkbox.check();
        assert(await form.locator('[data-tutorial-mask]').isVisible());
        await form.locator('[name="videoMask"]').fill(data.videoMask);
        const saved = page.waitForResponse(response => response.url() === endpoint && response.request().method() === 'POST');
        await form.locator('[type="submit"]').click();
        assert.equal((await (await saved).json()).status, 'ok');
        await page.waitForFunction(() => !document.querySelector('[data-omo-tutorial-settings] [type="submit"]').disabled);
        assert.equal(await form.locator('[data-tutorial-settings-feedback]').textContent(), '');
        await loadForm();
        assert(await page.locator('[name="customVideoLayout"]').isChecked(), 'Saved option survives reload.');
        for (const width of [1200, 390]) {
            await page.setViewportSize({width, height: 900});
            assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
        }
        await page.screenshot({path: 'tmp/tutorial-settings-mobile.png'});
        for (const width of [1200, 390]) {
            await view.setViewportSize({width, height: 900});
            await view.goto(publicUrl);
            await view.locator('#missionVideoPlayer[data-video-control-mode="custom"]').waitFor();
            await view.waitForFunction(() => !!window.videoTestPlayer);
            await view.waitForFunction(() => parseFloat(getComputedStyle(document.querySelector('#drawer')).right) === 0);
            assert.equal(await view.locator('.branding-overlay').getAttribute('src'), data.videoMask);
            assert.match(await view.locator('#missionVideoPlayer').getAttribute('src'), /controls=0/);
            assert.equal(await view.locator('#playBtn').evaluate(el => getComputedStyle(el).backgroundColor), 'rgb(255, 217, 102)');
            assert.equal(await view.locator('#playBtn').evaluate(el => getComputedStyle(el).color), 'rgb(17, 24, 39)');
            await view.locator('#playBtn').click();
            assert(await view.evaluate(() => window.videoTestPlayer.played));
            await view.locator('#volumeSlider').evaluate(el => { el.value = '35'; });
            await view.locator('#volumeSlider').dispatchEvent('input');
            await view.waitForFunction(() => window.videoTestPlayer.volume === 0.35);
            assert(await view.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
            await view.screenshot({path: `tmp/tutorial-custom-video-${width}.png`});
        }
        await view.goto(publicUrl.replace(`mid=${c.vimeo}`, `mid=${c.youtube}`));
        await view.locator('#missionVideoPlayer[data-video-provider="youtube"]').waitFor();
        assert.equal(await view.locator('.branding-overlay, .custom-controls').count(), 0, 'YouTube retains its native player even when customization is enabled.');
        await guest.route('https://player.vimeo.com/api/player.js', route => route.fulfill({contentType: 'text/javascript', body: ''}));
        await view.goto(publicUrl);
        await view.locator('#missionVideoPlayer[data-video-control-mode="native"]').waitFor();
        assert.equal(await view.locator('.branding-overlay').count(), 0, 'SDK failure falls back to native video.');
        assert(!((await view.locator('#missionVideoPlayer').getAttribute('src')).includes('controls=0')));
        assert.equal((await admin.request.post(endpoint, {form: {...data, customVideoLayout: '0'}})).status(), 200);
        await view.goto(publicUrl);
        await view.locator('#missionVideoPlayer').waitFor();
        assert.equal(await view.locator('.branding-overlay, .custom-controls').count(), 0, 'Disabling the option restores native markup.');
        await admin.request.post(c.origin + '/common/admin_mode.php', {form: {enabled: '0', organization_id: c.oid}, headers: {'X-Requested-With': 'XMLHttpRequest'}});
        assert.equal((await admin.request.post(endpoint, {form: data})).status(), 403);
        assert.deepEqual(errors, []);
        console.log('PASS tutorial settings, rights, CSRF, persistence, native/custom Vimeo, YouTube, fallback, controls and mobile layout');
    } finally {
        if (browser) await browser.close();
        fixture.stdin.end('\n');
        assert.equal(await closed, 0, 'Fixture cleaned up');
        lines.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
