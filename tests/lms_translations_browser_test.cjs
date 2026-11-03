'use strict';
const assert = require('node:assert/strict');
const {spawn} = require('node:child_process');
const {createInterface} = require('node:readline');
const {chromium} = require(process.argv[2] || 'playwright');

(async () => {
    const fixture = spawn('docker', ['compose', 'exec', '-T', 'app', 'php', 'tests/tutorial_video_browser_fixture.php'], {stdio: ['pipe', 'pipe', 'inherit']});
    const closed = new Promise(resolve => fixture.on('close', resolve));
    const lines = createInterface({input: fixture.stdout});
    let browser;
    try {
        const c = JSON.parse((await lines[Symbol.asyncIterator]().next()).value);
        browser = await chromium.launch({headless: true, channel: 'chrome'});
        const context = await browser.newContext({ignoreHTTPSErrors: true, locale: 'fr-CH'});
        const login = await context.request.post(c.origin + '/common/login_password.php', {
            form: {email: c.email, password: c.password}, headers: {'X-Requested-With': 'XMLHttpRequest'},
        });
        assert.equal((await login.json()).status, 'ok');
        const requests = [], errors = [];
        let showQuiz = false;
        await context.addInitScript(() => { window.turnstile = {ready: cb => cb(), render: () => 'fixture', reset() {}, remove() {}}; });
        await context.route('https://player.vimeo.com/**', route => route.fulfill({contentType: 'text/html', body: ''}));
        await context.route('**/getmissions.php?*', route => route.fulfill({contentType: 'application/json', body: '{"html":"","progress":0}'}));
        await context.route('**/getMissionDetail.php?*', async route => {
            requests.push(route.request().url());
            const response = await route.fetch();
            let html = await response.text();
            if (showQuiz) {
                html = html.replace(/data-quiz-count='0'/, "data-quiz-count='2'");
            } else {
                html = html.replace(/("lmsMissionHomeworks":)\[[\s\S]*?\]/, (_, key) => key + JSON.stringify([
                    {id: 1, title: 'Texte du devoir conservé', detail: '', is_done: false},
                    {id: 2, title: 'Autre devoir', detail: '', is_done: false},
                ]));
            }
            await route.fulfill({response, body: html});
        });
        await context.route('**/getMissionQuestions.php?*', route => route.fulfill({contentType: 'application/json', body: JSON.stringify([
            {id: 1, question: 'Texte de la question conservé', multiple: true, choices: [{id: 1, label: 'Choix saisi'}]},
            {id: 2, question: 'Question suivante', choices: []},
        ])}));
        await context.route('**/checkAnswer.php*', route => route.fulfill({contentType: 'application/json', body: '{"correct":false}'}));
        const page = await context.newPage();
        page.on('pageerror', error => errors.push(error.message));
        for (const base of ['/lms', '/omo/api/lms']) {
            await page.goto(`${c.origin}${base}/?oid=${c.oid}&lang=fr`);
            assert.equal(await page.locator('html').getAttribute('lang'), 'fr');
            assert(await page.getByRole('heading', {name: 'Parcours de formation', exact: true}).isVisible(), base + ': ' + (await page.locator('body').innerText()).slice(0, 900));
            assert(!/\b(?:Login|Homeworks|Close|Done)\b/.test(await page.locator('body').innerText()));
            const url = `${c.origin}${base}/parcours.php?oid=${c.oid}&idp=${c.id}&lang=fr`;
            for (const quiz of [false, true]) {
                showQuiz = quiz;
                await page.goto(url);
                await page.waitForFunction(() => typeof viewMission === 'function');
                await page.evaluate(mid => viewMission(mid), c.vimeo);
                await page.locator('.lms-mission-detail').waitFor();
                await page.waitForFunction(() => !document.getElementById('doneBtn').textContent.includes('Terminé'));
                assert.equal(new URL(requests.at(-1)).searchParams.get('lang'), 'fr');
                assert.equal(await page.locator('#drawer-footer button').first().textContent(), 'Fermer');
                if (!quiz) {
                    assert.equal(await page.locator('.lms-homework-section h3').textContent(), 'Devoirs');
                    assert.match(await page.locator('#quiz-zone').innerText(), /Terminez encore 2 devoirs pour continuer\./);
                    await page.locator('[data-homework-expand]').first().click();
                    assert.match(await page.locator('.lms-homework-detail').first().innerText(), /Aucun détail supplémentaire\./);
                    await page.screenshot({path: `tmp/lms-fr-${base === '/lms' ? 'public' : 'omo'}.png`});
                } else {
                    assert.match(await page.locator('#quiz-zone').innerText(), /Cette mission sera validée par 2 questions\./);
                    await page.locator('#doneBtn').click();
                    await page.locator('input[name="qcm"]').waitFor();
                    assert.match(await page.locator('#quiz-zone').innerText(), /Question 1 sur 2/);
                    assert.match(await page.locator('#quiz-zone').innerText(), /Plusieurs réponses possibles/);
                    await page.locator('input[name="qcm"]').check();
                    await page.locator('#doneBtn').click();
                    await page.getByText('Mauvaise réponse.', {exact: true}).waitFor();
                }
                assert(!/\b(?:Login|Homeworks|homeworks|Close|Done)\b/.test(await page.locator('body').innerText()));
            }
        }
        assert.deepEqual(errors, []);
        console.log('PASS public and OMO catalogs, French tasks and quiz, accents, apostrophes, plurals and request language');
    } finally {
        if (browser) await browser.close();
        fixture.stdin.end('\n');
        assert.equal(await closed, 0, 'Fixture cleaned up');
        lines.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
