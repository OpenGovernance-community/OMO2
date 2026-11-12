'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {execFileSync} = require('node:child_process');
const {chromium} = require(process.argv[2] || 'playwright');
const php = process.argv[3] || 'php';
const base = path.join(__dirname, '..');

// Render the actual PHP toolbar, without bootstrapping a database session.
const toolbar = execFileSync(php, ['-r', `
    $source = file_get_contents($argv[1]);
    $start = strpos($source, '<div class="omo-pv-editor__topbar"');
    $end = strpos($source, '<aside class="omo-pv-editor__sidebar">', $start);
    $showPvToolbar = $isParticipationPvEditor = $showPvApplicationTabs = $canManagePvStructure = true;
    $isPvValidated = false;
    $pvTimer = ['status' => 'stopped'];
    $pvApplicationTabsPayload = [];
    $uiText = ['applicationPickerTitle' => 'Applications', 'applicationEditorTab' => 'PV', 'applicationAdd' => '+'];
    $escape = 'htmlspecialchars';
    function omoDocumentsPvEditorT($key) { return $key; }
    eval('?>' . substr($source, $start, $end - $start));
`, path.join(base, 'omo/api/documents/pv/editor.php')], {encoding: 'utf8'});

(async function () {
    const browser = await chromium.launch({headless: true, channel: 'msedge'});
    try {
        const page = await browser.newPage({viewport: {width: 1200, height: 800}});
        await page.clock.install({time: new Date('2026-11-11T10:00:00Z')});
        const css = ['common/assets/components.css', 'omo/api/documents/pv/editor.css']
            .map(file => fs.readFileSync(path.join(base, file), 'utf8')).join('\n');
        await page.setContent(`<style>${css}</style><style>body{margin:0} .fixture{height:220px;margin-bottom:12px}</style>
            <div id="editor" class="omo-pv-editor omo-pv-editor--has-toolbar fixture">${toolbar}<aside>Ordre du jour</aside></div>
            <div id="viewer" class="omo-pv-editor fixture">${toolbar}<aside>Autre écran</aside></div>`);
        await page.addScriptTag({content: fs.readFileSync(path.join(base, 'omo/api/documents/pv/timer.js'), 'utf8')});
        await page.evaluate(() => {
            window.calls = [];
            window.responses = [];
            window.errors = [];
            window.beeps = 0;
            window.omoNotify = message => window.errors.push(message);
            window.AudioContext = class {
                constructor() { this.state = 'running'; this.currentTime = 0; }
                createOscillator() { return {frequency: {}, connect() {}, disconnect() {}, start() { window.beeps++; }, stop() {}}; }
                createGain() { return {gain: {setValueAtTime() {}, linearRampToValueAtTime() {}}, connect() {}, disconnect() {}}; }
                close() { return Promise.resolve(); }
            };
            window.stopped = {status: 'stopped', mode: 'stopwatch', milliseconds: 0, anchor: 0, revision: '', runId: ''};
            window.controllers = ['editor', 'viewer'].map((id, index) => {
                const controller = window.omoPvTimer.mount({
                    root: document.getElementById(id), state: {...window.stopped}, serverNow: Date.now(),
                    ui: {timerInvalid: 'Invalid time', timerError: 'Failed command'},
                    send: fields => {
                        window.calls.push(fields);
                        const response = window.responses.shift();
                        if (!response) return Promise.reject(new Error('Missing test response'));
                        return Promise.resolve(response);
                    },
                    onDocument() {}
                });
                controller.sync({canControlTimer: index === 0});
                return controller;
            });
            window.next = (state, rights = true) => {
                const timer = {...state, anchor: Date.now(), revision: String(window.calls.length + 1)};
                window.responses.push({timer, serverNow: Date.now(), document: {canControlTimer: rights}});
                return timer;
            };
            window.share = timer => window.controllers[1].sync({canControlTimer: false}, timer, Date.now());
        });
        const time = page.locator('#editor [data-omo-pv-timer-time]');
        const button = command => page.locator(`#editor [data-omo-pv-timer-command="${command}"]`);
        assert.equal(await time.inputValue(), '00:00:00');
        assert.equal(await page.locator('#viewer [data-omo-pv-timer]').isVisible(), false);
        assert.equal(await page.locator('#viewer [data-omo-pv-timer-controls]').isVisible(), false);
        const playing = await page.evaluate(() => window.next({...window.stopped, status: 'playing', runId: 'first'}));
        await button('play').click();
        assert.deepEqual(await page.evaluate(() => window.calls[0]), {command: 'play', revision: '', seconds: 0, mode: 'stopwatch'});
        await page.evaluate(state => window.share(state), playing);
        await page.clock.runFor(2300);
        assert.equal(await time.inputValue(), '00:00:02');
        assert.equal(await page.locator('#viewer [data-omo-pv-timer-time]').inputValue(), '00:00:02');
        const paused = await page.evaluate(() => window.next({...window.stopped, status: 'paused', milliseconds: 2300, runId: 'first'}));
        await button('pause').click();
        await page.evaluate(state => window.share(state), paused);
        await page.clock.runFor(5000);
        assert.equal(await time.inputValue(), '00:00:02', 'Pause freezes time.');
        assert.equal(await page.locator('#viewer [data-omo-pv-timer-time]').inputValue(), '00:00:02', 'A paused timer remains visible and frozen on other screens.');
        await page.evaluate(() => window.controllers[0].selectPoint(5));
        assert.equal(await time.inputValue(), '00:00:02', 'Point selection preserves a paused timer.');
        const stopped = await page.evaluate(() => window.next({...window.stopped}));
        await button('stop').click();
        await page.evaluate(state => window.share(state), stopped);
        assert.equal(await page.locator('#viewer [data-omo-pv-timer]').isVisible(), false, 'Stop hides the timer on other screens.');
        assert.equal(await time.inputValue(), '00:00:00');
        await page.evaluate(() => window.controllers[0].selectPoint(2));
        assert.equal(await time.inputValue(), '00:02:00', 'Selected point prepares its duration.');
        await time.fill('00:01:01');
        const countdown = await page.evaluate(() => window.next({...window.stopped, status: 'playing', mode: 'countdown', milliseconds: 61000, runId: 'countdown'}));
        await button('play').click();
        assert.equal(await page.evaluate(() => window.calls.at(-1).seconds), 61, 'A single Play click commits a typed duration.');
        assert.equal(await page.evaluate(() => window.calls.at(-1).mode), 'countdown');
        await page.evaluate(state => window.share(state), countdown);
        assert.equal(await page.locator('#editor [data-omo-pv-timer]').evaluate(node => node.classList.contains('is-urgent')), false);
        await page.clock.runFor(1100);
        assert.equal(await page.locator('#editor [data-omo-pv-timer]').evaluate(node => node.classList.contains('is-urgent')), true);
        await page.evaluate(() => window.controllers[0].selectPoint(5));
        assert.equal(await time.inputValue(), '00:01:00', 'Point selection preserves a running countdown.');
        await page.clock.runFor(60000);
        assert.equal(await time.inputValue(), '00:00:00');
        assert.equal(await page.evaluate(() => window.beeps), 6, 'Each screen rings three short beeps at zero.');
        await page.clock.runFor(3000);
        assert.equal(await page.evaluate(() => window.beeps), 6, 'The alarm only rings once per run.');
        await page.evaluate(() => {
            window.controllers[0].sync({canControlTimer: false});
            window.controllers[1].sync({canControlTimer: true});
        });
        assert.equal(await page.locator('#editor [data-omo-pv-timer-controls]').isVisible(), false);
        assert.equal(await time.getAttribute('readonly'), '');
        assert.equal(await page.locator('#viewer [data-omo-pv-timer-controls]').isVisible(), true, 'Handover transfers controls.');
        await page.evaluate(() => window.controllers.forEach(controller => controller.sync({canControlTimer: false}, {...window.stopped, anchor: Date.now(), revision: 'final'}, Date.now())));
        assert.equal(await page.locator('#editor [data-omo-pv-timer]').isVisible(), false);
        await page.evaluate(() => window.controllers[0].sync({canControlTimer: true}));
        await time.fill('00:99:00');
        await button('play').click();
        assert.equal(await time.evaluate(node => node.validity.valid), false, 'Invalid durations stay inline.');
        await time.press('Escape');
        assert.equal(await time.inputValue(), '00:00:00');
        for (const width of [1200, 390, 320]) {
            await page.setViewportSize({width, height: 800});
            const box = await page.locator('#editor [data-omo-pv-timer]').boundingBox();
            assert(box.x <= 5 && box.x + box.width <= width, 'The timer stays at the left and fits narrow screens.');
        }
        if (process.env.PV_TIMER_SCREENSHOT) {
            await page.setViewportSize({width: 390, height: 800});
            await page.screenshot({path: process.env.PV_TIMER_SCREENSHOT});
        }
        await page.evaluate(() => {
            window.pollCount = 0;
            window.pollRoot = document.getElementById('viewer').cloneNode(true);
            window.pollRoot.id = 'projection';
            document.body.appendChild(window.pollRoot);
            Object.defineProperty(document, 'hasFocus', {value: () => false});
            window.omoPvTimer.mount({
                root: window.pollRoot, state: {...window.stopped}, serverNow: Date.now(), ui: {},
                poll: () => {
                    window.pollCount++;
                    return Promise.resolve({
                        timer: {...window.stopped, status: 'paused', milliseconds: 42000, anchor: Date.now(), revision: 'projection'},
                        serverNow: Date.now(), timerPermissions: {canControlTimer: false}
                    });
                }
            });
        });
        await page.clock.runFor(2100);
        assert.equal(await page.evaluate(() => document.hasFocus()), false);
        assert.equal(await page.locator('#projection [data-omo-pv-timer-time]').inputValue(), '00:00:42', 'Unfocused projection windows receive timer updates.');
        assert.equal(await page.evaluate(() => window.pollCount), 1);
        await page.evaluate(() => { window.pollRoot.style.display = 'none'; });
        await page.clock.runFor(2100);
        assert.equal(await page.evaluate(() => window.pollCount), 1, 'Hidden drawers do not poll.');
        await page.evaluate(() => window.pollRoot.remove());
        await page.clock.runFor(2100);
        assert.equal(await page.evaluate(() => window.pollCount), 1, 'Detached editors release their polling interval.');
        assert.deepEqual(await page.evaluate(() => window.errors), []);
        console.log('pv_timer_browser_test: OK');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
