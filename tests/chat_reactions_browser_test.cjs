const assert = require('node:assert/strict');
const {spawn} = require('node:child_process');
const {createInterface} = require('node:readline');
const {chromium} = require(process.argv[2] || 'playwright');

(async () => {
    const fixture = spawn('docker', ['compose', 'exec', '-T', '--user', 'www-data', 'app', 'php',
        '-d', 'display_errors=1', 'tests/chat_reactions_test.php', '--browser'], {stdio: ['pipe', 'pipe', 'inherit']});
    const closed = new Promise(resolve => fixture.on('close', resolve));
    let browser;
    try {
        const lines = createInterface({input: fixture.stdout});
        const first = await lines[Symbol.asyncIterator]().next();
        assert(!first.done, 'Fixture started');
        const config = JSON.parse(first.value);
        browser = await chromium.launch({headless: true, channel: 'chrome'});
        const context = await browser.newContext({ignoreHTTPSErrors: true, viewport: {width: 1200, height: 900}});
        await context.addCookies([{...config.cookie, url: config.origin, secure: true}]);
        const page = await context.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.route('**/__chat_reactions_test', route => route.fulfill({contentType: 'text/html', body: `<!doctype html>
            <html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
            <link rel="stylesheet" href="/common/assets/theme-style-mono.css">
            <link rel="stylesheet" href="/common/assets/components.css">
            <link rel="stylesheet" href="/common/chat/thread.css">
            <link rel="stylesheet" href="/common/choice/proposal-discussion.css">
            <style>body{margin:0;font-family:Arial}#commonTopbarModal{max-width:760px;margin:auto}</style></head>
            <body><div id="triggers"></div><div id="commonTopbarModal"><div id="commonTopbarModalBody"></div></div>
            <script>window.testNotifications=[];window.commonNotify=(message)=>testNotifications.push(message);
            window.commonTopbarOpenModal=(title,html)=>{window.dispatchEvent(new Event('common-topbar-modal-close'));
                document.getElementById('commonTopbarModalBody').innerHTML=html;};</script>
            <script src="/common/chat/reactions.js"></script><script src="/common/chat/thread.js"></script>
            <script src="/common/choice/proposal-discussion.js"></script></body></html>`}));
        await page.goto(config.origin + '/__chat_reactions_test');
        await page.evaluate(contexts => {
            Object.entries(contexts).forEach(([name, config]) => {
                const button = document.createElement('button'); button.id = name; button.textContent = name;
                if (name === 'decision') {
                    button.setAttribute('data-omo-proposal-discussion-open', '');
                    button.setAttribute('data-proposal-context', JSON.stringify(config.context));
                    button.setAttribute('data-proposal-id', config.context.proposal_id);
                } else {
                    button.setAttribute('data-omo-chat-open', '');
                    button.setAttribute('data-omo-chat-endpoint', config.endpoint);
                    button.setAttribute('data-omo-chat-context', JSON.stringify(config.context));
                    button.setAttribute('data-omo-chat-labels', JSON.stringify({send: 'Envoyer', placeholder: 'Message'}));
                }
                document.getElementById('triggers').appendChild(button);
            });
        }, config.contexts);
        for (const name of ['project', 'pv', 'decision']) {
            await page.locator('#' + name).click();
            const summary = page.locator('.chat-reactions__summary').first();
            await summary.waitFor({state: 'visible'});
            const ownBubble = page.locator('.omo-chat-popup__message-row--own .omo-chat-popup__bubble, .omo-proposal-chat__message-row--own .omo-proposal-chat__bubble').first();
            assert(await ownBubble.locator('.chat-reactions__summary').isDisabled(), 'Own message reactions are read-only');
            assert.equal(await ownBubble.locator('.chat-reactions__summary img').count(), 3, 'Own message still displays reactions');
            await ownBubble.locator('p').click();
            assert.equal(await page.locator('body > .chat-reactions__picker').count(), 0, 'Clicking an own message cannot open the picker');
            const bubble = page.locator('.omo-chat-popup__bubble, .omo-proposal-chat__bubble').first();
            const picker = page.locator('body > .chat-reactions__picker');
            assert.equal(await summary.locator('.chat-reactions__item--empty').count(), 3, 'Three muted icons before voting');
            assert.equal(await summary.locator('img').count(), 3, 'Custom images replace the emojis');
            await summary.locator('img').evaluateAll(images => Promise.all(images.map(image => image.decode())));
            const thumbs = await summary.locator('[data-reaction-kind="up"] img, [data-reaction-kind="down"] img').evaluateAll(images =>
                images.map(image => ({src: image.src, transform: getComputedStyle(image).transform})));
            assert.equal(thumbs[0].src, thumbs[1].src, 'Both thumbs use the supplied image');
            assert.equal(thumbs[0].transform, 'matrix(1, 0, 0, 1, 0, -2.8)', 'Up thumb is raised to align the wrist');
            assert.equal(thumbs[1].transform, 'matrix(1, 0, 0, -1, 0, 2.8)', 'Down thumb is flipped and lowered to align the wrist');
            const closedGeometry = await bubble.boundingBox();
            await bubble.locator('p').click();
            assert(await picker.isVisible(), name + ': message click opens picker');
            assert.deepEqual(await bubble.boundingBox(), closedGeometry, 'Floating picker does not move or resize the message');
            assert.equal(await picker.evaluate(node => getComputedStyle(node).position), 'fixed');
            const save = page.waitForResponse(r => r.url().includes(config.contexts[name].endpoint) && r.request().method() === 'POST');
            await picker.locator('[data-reaction="up"]').click();
            assert.equal((await (await save).json()).reactions[0].own, 'up');
            await page.waitForFunction(() => document.querySelector('.chat-reactions__summary').textContent.includes('1'));
            assert(await summary.locator('strong').count(), 'Own reaction highlighted');
            assert.equal(await summary.locator('[data-reaction-kind="up"]').evaluate(node => getComputedStyle(node).opacity), '1');
            assert.equal(await summary.locator('.chat-reactions__item--empty').count(), 2);
            await summary.focus(); await page.keyboard.press('Enter');
            assert(await picker.isVisible(), 'Keyboard opens picker');
            assert.equal(await page.evaluate(() => document.activeElement.dataset.reaction), 'up');
            await page.keyboard.press('Escape');
            assert(!(await picker.isVisible()), 'Escape closes picker');
            assert(await summary.evaluate(node => node === document.activeElement), 'Focus restored');
            await summary.click();
            const change = page.waitForResponse(r => r.url().includes(config.contexts[name].endpoint) && r.request().method() === 'POST');
            await picker.locator('[data-reaction="clap"]').click();
            const changed = await (await change).json();
            assert.deepEqual(changed.reactions[0].counts, {up: 0, down: 0, clap: 1});
            await summary.click();
            const remove = page.waitForResponse(r => r.url().includes(config.contexts[name].endpoint) && r.request().method() === 'POST');
            await picker.locator('[data-reaction="clap"]').click();
            assert.equal((await (await remove).json()).reactions[0].own, '');
            await page.waitForFunction(() => document.querySelector('.chat-reactions__summary').querySelectorAll('.chat-reactions__item--empty').length === 3);

            // Another tab changes an existing message; the ordinary poll must refresh it.
            const cfg = config.contexts[name];
            const remote = await context.request.post(config.origin + cfg.endpoint, {
                form: {...cfg.context, action: 'reaction', message_id: cfg.messageId, reaction: 'down'}, headers: {Origin: config.origin}});
            assert.equal(remote.status(), 200);
            await page.waitForFunction(() => document.querySelector('[data-reaction-kind="down"]').textContent.includes('1'), null, {timeout: 15000});
            const rootSelector = name === 'decision' ? '[data-omo-proposal-chat]' : '[data-omo-chat-popup]';
            await page.evaluate(({rootSelector, id}) => {
                window.commonChatReactions.update(document.querySelector(rootSelector), {reactions: [{id, counts: {up: 99}, own: 'up', version: 0}]});
            }, {rootSelector, id: cfg.messageId});
            assert(!(await summary.textContent()).includes('99'), 'Stale poll cannot overwrite a newer vote');
            await page.setViewportSize({width: 390, height: 844});
            await summary.click();
            assert(await picker.isVisible(), 'Mobile picker visible');
            const geometry = await bubble.evaluate(node => {
                const footer = node.querySelector('.chat-reactions__footer');
                const summary = footer.firstElementChild.getBoundingClientRect();
                const time = footer.lastElementChild.getBoundingClientRect();
                return {scroll: document.documentElement.scrollWidth, width: innerWidth, sameLine: Math.abs(summary.top - time.top) < 15};
            });
            assert(geometry.scroll <= geometry.width, 'No horizontal overflow on mobile');
            assert(geometry.sameLine, 'Reactions share the date line');
            const floatingBox = await picker.boundingBox();
            assert(floatingBox.x >= 0 && floatingBox.y >= 0 && floatingBox.x + floatingBox.width <= 390,
                'Floating picker stays inside the mobile viewport');
            await page.screenshot({path: require('node:path').join(require('node:os').tmpdir(), 'chat-reactions-' + name + '.png')});
            for (const theme of ['light', 'dark']) {
                await page.evaluate(theme => document.documentElement.dataset.theme = theme, theme);
                await page.evaluate(() => Promise.all(document.getAnimations().map(animation => animation.finished.catch(() => {}))));
                const contrast = await page.evaluate(() => ({
                    summary: getComputedStyle(document.querySelector('.chat-reactions__summary img')).filter,
                    selected: getComputedStyle(document.querySelector('.chat-reactions__picker [aria-pressed="true"] img')).filter,
                    other: getComputedStyle(document.querySelector('.chat-reactions__picker [aria-pressed="false"] img')).filter
                }));
                assert.equal(contrast.summary, 'none', 'Light message bubbles retain black icons in ' + theme);
                assert.equal(contrast.selected, theme === 'dark' ? 'none' : 'invert(1)', 'Selected button icon contrast in ' + theme);
                assert.equal(contrast.other.includes('invert(1)'), theme === 'dark', 'Menu icons follow the theme');
                await page.screenshot({path: require('node:path').join(require('node:os').tmpdir(), 'chat-reactions-' + name + '-' + theme + '.png')});
            }
            await page.evaluate(() => document.documentElement.dataset.theme = 'light');
            await page.setViewportSize({width: 1200, height: 900});
            console.log('PASS ' + name + ': add/change/remove, keyboard, live polling, stale response, mobile');
        }
        assert.deepEqual(errors, []);
        assert.deepEqual(await page.evaluate(() => window.testNotifications), []);
    } finally {
        if (browser) await browser.close();
        fixture.stdin.end('\n');
        assert.equal(await closed, 0, 'Fixture cleaned up');
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
