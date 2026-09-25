'use strict';

// Run with playwright and jquery on NODE_PATH and a local Edge installation.
const assert = require('node:assert/strict');
const path = require('node:path');
const {chromium} = require('playwright');

const root = path.join(__dirname, '..');

async function renderMembers(page, panelWidth, adminCount, regularCount, addButton) {
    const admins = Array.from({length: adminCount}, (_, index) => `<span class="circle-member circle-member--admin" data-circle-member-item="1">A${index}</span>`).join('');
    const members = Array.from({length: regularCount}, (_, index) => `<span class="circle-member circle-member--regular" data-circle-member-item="1">M${index}</span>`).join('');
    await page.setContent(`<div id="panel-left" style="width:${panelWidth}px"><div class="circle-panel"><div class="circle-top"><div class="circle-members"><div class="circle-members__row"><div class="circle-members__list">${admins}${members}<button class="circle-member circle-member--more" data-circle-member-action="more" hidden>...</button>${addButton ? '<button class="circle-member circle-member--add" data-circle-member-action="add">+</button>' : ''}</div></div></div></div></div></div>`);
    await page.addStyleTag({content: '* { box-sizing: border-box; }'});
    await page.addStyleTag({path: path.join(root, 'omo/api/getOrg.css')});
    await page.addScriptTag({path: require.resolve('jquery')});
    await page.addScriptTag({path: path.join(root, 'omo/api/getOrg.js')});
    await page.waitForFunction(() => document.querySelector('[data-circle-member-stack]')?.dataset.memberCapacity);
}

(async () => {
    const browser = await chromium.launch({channel: 'msedge', headless: true});
    try {
        const page = await browser.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));

        await renderMembers(page, 440, 1, 3, true);
        const shortStack = page.locator('[data-circle-member-stack]');
        assert.equal(await page.locator('.circle-members__list > .circle-member--admin').count(), 1);
        assert.equal(await shortStack.getAttribute('data-member-compact'), '1', 'Regular members should overlap even when one row has room.');
        const shortBefore = await shortStack.locator('.circle-member--regular').evaluateAll(members => members.map(member => member.getBoundingClientRect().left));
        assert(shortBefore[1] - shortBefore[0] < 34);
        await shortStack.hover();
        const shortAfter = await shortStack.locator('.circle-member--regular').evaluateAll(members => members.map(member => member.getBoundingClientRect().left));
        assert(shortAfter[1] - shortAfter[0] >= 42);
        await shortStack.click();
        assert.equal(await shortStack.getAttribute('aria-expanded'), 'false', 'Mouse click should not toggle the stack.');
        await page.mouse.move(760, 560);
        const shortAfterLeave = await shortStack.locator('.circle-member--regular').evaluateAll(members => members.map(member => member.getBoundingClientRect().left));
        assert(shortAfterLeave[1] - shortAfterLeave[0] < 34);

        for (const width of [220, 320, 440]) {
            await page.mouse.move(760, 560);
            await renderMembers(page, width, 1, 30, true);
            const stack = page.locator('[data-circle-member-stack]');
            assert.equal(await page.locator('.circle-members__list > .circle-member--admin').count(), 1);
            assert.equal(await stack.locator('.circle-member--admin').count(), 0);
            assert.equal(await stack.locator('.circle-member--regular').count(), 30);
            const before = await stack.evaluate(element => ({width: element.getBoundingClientRect().width, height: element.getBoundingClientRect().height, capacity: Number(element.dataset.memberCapacity)}));
            await stack.hover();
            const after = await stack.evaluate(element => ({width: element.getBoundingClientRect().width, height: element.getBoundingClientRect().height}));
            const visible = await stack.locator('.circle-member--regular:visible').count();
            const moreVisible = await stack.locator('[data-circle-member-action="more"]:visible').count();
            assert(after.height > before.height, 'Hover must expand the regular member stack.');
            assert(visible === before.capacity - 1);
            assert.equal(moreVisible, 1);
            assert(after.height <= 118);
            const lastRegular = await stack.locator('.circle-member--regular:visible').last().boundingBox();
            const more = await stack.locator('[data-circle-member-action="more"]').boundingBox();
            assert.equal(lastRegular.y, more.y, 'The ellipsis should end the third row.');
            assert(more.x > lastRegular.x);

            if (width === 440) {
                await page.locator('#panel-left').evaluate(element => { element.style.width = '220px'; });
                await page.waitForFunction(() => document.querySelector('[data-circle-member-stack]').dataset.memberCapacity === '9');
                assert.equal(await stack.locator('.circle-member--regular:visible').count(), 8);
            }
        }
        const hybridContext = await browser.newContext({hasTouch: true});
        try {
            const hybridPage = await hybridContext.newPage();
            await renderMembers(hybridPage, 440, 1, 3, true);
            const hybridStack = hybridPage.locator('[data-circle-member-stack]');
            await hybridStack.hover();
            const positions = await hybridStack.locator('.circle-member--regular').evaluateAll(members => members.map(member => member.getBoundingClientRect().left));
            assert(positions[1] - positions[0] >= 42, 'A mouse must expand the stack on a touch-capable computer.');
            assert.equal(await hybridStack.getAttribute('aria-expanded'), 'false');
            await hybridPage.mouse.move(760, 560);
            const collapsed = await hybridStack.locator('.circle-member--regular').evaluateAll(members => members.map(member => member.getBoundingClientRect().left));
            assert(collapsed[1] - collapsed[0] < 34, 'Mouse leave should collapse the stack.');
            await hybridStack.tap();
            assert.equal(await hybridStack.getAttribute('aria-expanded'), 'true', 'Touch should still expand on tap.');
            await hybridPage.mouse.move(760, 560);
            await hybridStack.hover();
            assert.equal(await hybridStack.getAttribute('aria-expanded'), 'false', 'Mouse hover should take priority after a touch tap.');
        } finally {
            await hybridContext.close();
        }
        assert.deepEqual(errors, []);
        console.log('org_member_hover_dom_test: OK');
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
