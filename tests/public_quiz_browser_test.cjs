'use strict';
const assert = require('node:assert/strict');
const {spawn} = require('node:child_process');
const {createInterface} = require('node:readline');
const {chromium} = require(process.argv[2] || 'playwright');
(async () => {
    const fixture = spawn('docker', ['compose', 'exec', '-T', 'app', 'php', '-d', 'display_errors=1', 'tests/public_quiz_browser_fixture.php'], {stdio: ['pipe', 'pipe', 'inherit']});
    const closed = new Promise(resolve => fixture.on('close', resolve));
    const lines = createInterface({input: fixture.stdout});
    let browser;
    try {
        const c = JSON.parse((await lines[Symbol.asyncIterator]().next()).value);
        browser = await chromium.launch({headless: true, channel: 'chrome'});
        const context = await browser.newContext({ignoreHTTPSErrors: true, viewport: {width: 1000, height: 850}, extraHTTPHeaders: {Origin: c.origin}});
        await context.addInitScript(() => { window.turnstile = {ready: cb => cb(), render: () => 'fixture', reset() {}, remove() {}}; });
        await context.route('https://player.vimeo.com/api/player.js', route => route.fulfill({contentType: 'text/javascript', body: ''}));
        // Isolate quiz completion from the unrelated old local mission-list schema.
        await context.route('**/getmissions.php?*', route => {
            const done = new URL(route.request().url()).searchParams.get('done_ids');
            return route.fulfill({contentType: 'application/json', body: JSON.stringify({html: '', progress: done ? 100 : 0})});
        });
        const endpoint = c.origin + '/omo/api/lms/checkAnswer.php?catalog=basic';
        const base = {parcours_id: c.pid, mission_id: c.mid, question_id: c.questions.single.id, choices: c.questions.single.correct};
        const check = async (data, status, correct) => {
            const response = await context.request.post(endpoint, {data});
            assert.equal(response.status(), status, await response.text());
            assert.equal((await response.json()).correct, correct);
        };
        await check(base, 200, true);
        await check({...base, choices: c.questions.single.wrong}, 200, false);
        await check({...base, choices: c.questions.private.correct}, 200, false);
        await check({...base, question_id: c.questions.multiple.id, choices: c.questions.multiple.correct}, 200, true);
        await check({...base, question_id: c.questions.multiple.id, choices: c.questions.multiple.correct.slice(0, 1)}, 200, false);
        await check({...base, question_id: c.questions.private.id, choices: c.questions.private.correct}, 404, false);
        await check({...base, mission_id: c.privateMid}, 404, false);
        await check({...base, parcours_id: c.privatePid, mission_id: c.privateMid, question_id: c.questions.private.id, choices: c.questions.private.correct}, 401, false);
        await check({...base, choices: [[]]}, 400, false);
        await check({...base, question_id: null}, 400, false);
        assert.equal((await context.request.get(endpoint)).status(), 405);
        assert.equal((await context.request.post(c.origin + '/omo/api/lms/checkAnswer.php', {data: base})).status(), 401);
        const legacy = await context.request.post(`${c.origin}/lms/checkAnswer.php?oid=${c.oid}`, {data: base});
        assert.equal(legacy.status(), 200);
        assert.equal((await legacy.json()).correct, true);
        const questions = await (await context.request.get(`${c.origin}/omo/api/lms/getMissionQuestions.php?catalog=basic&parcours_id=${c.pid}&mission_id=${c.mid}`)).json();
        assert.equal(questions.length, 2);
        assert(!JSON.stringify(questions).includes('is_correct'));
        const page = await context.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        const publicUrl = `${c.origin}/omo/api/lms/parcours.php?catalog=basic&idp=${c.pid}&mid=${c.mid}`;
        await page.goto(publicUrl);
        await page.locator('#doneBtn').filter({hasText: 'Commencer'}).waitFor();
        assert.equal(await page.evaluate(() => typeof window.commonNotify), 'function');
        await page.locator('#doneBtn').click();
        await page.locator(`input[name="qcm"][value="${c.questions.single.wrong[0]}"]`).check();
        await page.locator('#doneBtn').click();
        await page.locator('.common-notification').filter({hasText: 'Mauvaise'}).waitFor();
        const storageKey = `lms_progress_0_${c.pid}`;
        assert.equal(await page.evaluate(key => localStorage.getItem(key), storageKey), null);
        // Network failures preserve the chosen answer and permit a retry.
        await page.locator(`input[name="qcm"][value="${c.questions.single.correct[0]}"]`).check();
        await page.route('**/checkAnswer.php?catalog=basic', route => route.fulfill({status: 503, contentType: 'application/json', body: '{"correct":false}'}), {times: 1});
        await page.locator('#doneBtn').click();
        await page.locator('.common-notification').filter({hasText: 'Reessayez'}).waitFor();
        assert(await page.locator(`input[value="${c.questions.single.correct[0]}"]`).isChecked());
        assert(await page.locator('#doneBtn').isEnabled());
        const sent = page.waitForRequest(request => request.url().includes('/checkAnswer.php') && request.method() === 'POST');
        await page.locator('#doneBtn').click();
        const request = await sent;
        assert.equal(new URL(request.url()).searchParams.get('catalog'), 'basic');
        assert.equal(request.postDataJSON().question_id, c.questions.single.id);
        for (const choice of c.questions.multiple.correct) await page.locator(`input[name="qcm"][value="${choice}"]`).check();
        await page.locator('#doneBtn').click();
        await page.waitForFunction(({key, mid}) => !!JSON.parse(localStorage.getItem(key) || '{}').missions?.[mid], {key: storageKey, mid: c.mid});
        await page.waitForFunction(() => document.getElementById('progressPercent').textContent === '100 %');
        assert.equal(await page.locator('#progressPercent').textContent(), '100 %');
        await page.reload();
        await page.waitForFunction(() => document.getElementById('progressPercent').textContent === '100 %');
        assert(await page.evaluate(({key, mid}) => !!JSON.parse(localStorage.getItem(key)).missions[mid], {key: storageKey, mid: c.mid}));
        assert.deepEqual(errors, []);
        console.log('PASS public quiz, single/multiple answers, scoped access, retry and local completion after reload');
    } finally {
        if (browser) await browser.close();
        fixture.stdin.end('\n');
        assert.equal(await closed, 0, 'Quiz fixtures removed and anonymous server progress unchanged');
        lines.close();
    }
})().catch(error => {console.error(error); process.exitCode = 1;});
