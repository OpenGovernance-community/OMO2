'use strict';
const assert = require('node:assert/strict');
const {spawn} = require('node:child_process');
const {createInterface} = require('node:readline');
const {chromium} = require(process.argv[2] || 'playwright');
(async () => {
    const fixture = spawn('docker', ['compose', 'exec', '-T', 'app', 'php', '-d', 'display_errors=1', 'tests/mission_read_browser_fixture.php'], {stdio: ['pipe', 'pipe', 'inherit']});
    const closed = new Promise(resolve => fixture.on('close', resolve));
    const lines = createInterface({input: fixture.stdout});
    let browser;
    try {
        const c = JSON.parse((await lines[Symbol.asyncIterator]().next()).value);
        browser = await chromium.launch({headless: true, channel: 'chrome'});
        const context = await browser.newContext({ignoreHTTPSErrors: true});
        await context.addInitScript(() => { window.turnstile = {ready: cb => cb(), render: () => 'fixture', reset() {}, remove() {}}; });
        await context.route('https://player.vimeo.com/api/player.js', route => route.fulfill({contentType: 'text/javascript', body: ''}));
        await context.route('**/getmissions.php?*', route => route.fulfill({contentType: 'application/json', body: '{"html":"","progress":0}'}));
        // Exercise required-task UI independently of the old local homework schema.
        await context.route('**/getMissionDetail.php?*', async route => {
            const response = await route.fetch();
            let html = await response.text();
            if (Number(new URL(route.request().url()).searchParams.get('mission_id')) === c.homework) {
                const tasks = [{id: c.task, title: 'Required reading task', detail: 'Complete this task as well.', is_done: false}];
                html = html.replace(/("lmsMissionHomeworks":)\[[\s\S]*?\]/, (_, key) => key + JSON.stringify(tasks));
            }
            await route.fulfill({response, body: html});
        });
        const page = await context.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        const open = async mid => {
            await page.goto(`${c.origin}/omo/api/lms/parcours.php?catalog=basic&idp=${c.pid}&mid=${mid}`);
            await page.locator('#doneBtn').filter({hasText: 'Marquer comme lu'}).waitFor();
            await page.waitForFunction(() => parseFloat(getComputedStyle(document.getElementById('drawer')).right) === 0);
        };
        const bottom = async () => {
            await page.locator('#drawer-content').evaluate(el => { el.scrollTop = el.scrollHeight; });
        };
        const enabled = async value => page.waitForFunction(value => document.getElementById('doneBtn').disabled === !value, value);
        for (const width of [1200, 390]) {
            await page.setViewportSize({width, height: 800});
            await open(c.long);
            await page.evaluate(pid => localStorage.removeItem(`lms_progress_0_${pid}`), c.pid);
            assert(await page.locator('#doneBtn').isDisabled());
            assert(await page.locator('#quiz-zone').isVisible());
            await page.locator('#drawer-content').evaluate(el => { el.scrollTop = 100; });
            assert(await page.locator('#doneBtn').isDisabled());
            await bottom();
            await enabled(true);
            await page.locator('#drawer-content').evaluate(el => { el.scrollTop = 0; });
            assert(await page.locator('#doneBtn').isEnabled(), 'Read confirmation stays unlocked after scrolling back up.');
            await page.screenshot({path: `tmp/mission-read-${width}.png`});
            // A new mission starts with fresh read progress; old observers cannot unlock it.
            await page.evaluate(async mid => { await viewMission(mid); }, c.homework);
            await page.locator('[data-homework-check]').waitFor({state: 'attached'});
            await page.waitForFunction(() => typeof document.querySelector('[data-homework-check]')?.onclick === 'function');
            await page.waitForFunction(() => document.getElementById('drawer-content').scrollTop === 0);
            assert(await page.locator('#doneBtn').isDisabled());
            await bottom();
            assert(await page.locator('#doneBtn').isDisabled(), 'Required tasks still block completion at the bottom.');
            await page.locator('[data-homework-check]').click();
            await enabled(true);
            await open(c.short);
            await enabled(true);
            assert.equal(await page.locator('#drawer-content').evaluate(el => el.scrollTop), 0, 'Entirely visible missions need no artificial scrolling.');
            // Simulate an image expanding after the initial short content was laid out.
            await page.locator('.lms-mission-detail').evaluate(el => {
                const image = document.createElement('img');
                image.style.width = '100%';
                image.alt = 'Late image';
                image.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="100" height="600"%3E%3Crect width="100" height="600" fill="lightblue"/%3E%3C/svg%3E';
                el.append(image);
            });
            await enabled(false);
            await bottom();
            await enabled(true);
            await page.locator('#doneBtn').click();
            await page.waitForFunction(({pid, mid}) => !!JSON.parse(localStorage.getItem(`lms_progress_0_${pid}`) || '{}').missions?.[mid], {pid: c.pid, mid: c.short});
        }
        assert.deepEqual(errors, []);
        console.log('PASS mission scroll gate, short content, late image growth, required homework, reopening and desktop/mobile completion');
    } finally {
        if (browser) await browser.close();
        fixture.stdin.end('\n');
        assert.equal(await closed, 0);
        lines.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
