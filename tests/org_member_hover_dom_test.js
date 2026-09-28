'use strict';

// Run with playwright and jquery on NODE_PATH and a local Edge installation.
const assert = require('node:assert/strict');
const path = require('node:path');
const {chromium} = require('playwright');

const root = path.join(__dirname, '..');

async function renderMembers(page, panelWidth, adminCount, regularCount, addButton, fullLayout = false) {
    const admins = Array.from({length: adminCount}, (_, index) => `<span class="circle-member circle-member--admin" data-circle-member-item="1">A${index}</span>`).join('');
    const members = Array.from({length: regularCount}, (_, index) => `<span class="circle-member circle-member--regular" data-circle-member-item="1">M${index}</span>`).join('');
    const header = fullLayout ? '<div class="circle-header"><div><div class="breadcrumb"><span class="crumb">OrganisationAvecUnNomAssezLongPourOccuperToutLePanneauGauche</span></div><div class="circle-title-row"><div class="circle-title-copy"><h2 class="circle-title">OrganisationAvecUnNomAssezLongPourOccuperToutLePanneauGauche</h2></div></div></div><div class="circle-meta"><button class="circle-badge">#123</button></div></div>' : '';
    const memberPanel = `<div class="circle-panel"><div class="circle-top">${header}<div class="circle-members"><div class="circle-members__row"><div class="circle-members__list">${admins}${members}<button class="circle-member circle-member--more" data-circle-member-action="more" hidden>...</button>${addButton ? '<button class="circle-member circle-member--add" data-circle-member-action="add">+</button>' : ''}</div></div></div></div></div>`;
    const html = fullLayout
        ? `<div class="content" style="width:1200px;height:600px"><div id="panel-left" class="panel panel-left" style="width:${panelWidth}px;flex-basis:${panelWidth}px"><div class="omo-left-panel-shell"><div class="omo-left-panel-shell__context" id="panel-left-context">${memberPanel}</div></div></div><div class="resizer"></div><div class="panel panel-right"></div></div>`
        : `<div id="panel-left" style="width:${panelWidth}px">${memberPanel}</div>`;
    await page.setContent(html);
    await page.addStyleTag({content: '* { box-sizing: border-box; }'});
    if (fullLayout) {
        await page.addStyleTag({path: path.join(root, 'common/assets/components.css')});
        await page.addStyleTag({path: path.join(root, 'omo/assets/css/styles.css')});
    }
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

        await renderMembers(page, 440, 1, 2, true);
        const shortStack = page.locator('[data-circle-member-stack]');
        assert.equal(await page.locator('.circle-members__list > .circle-member--admin').count(), 1);
        assert.equal(await shortStack.getAttribute('data-member-compact'), '0', 'Members should stay separate when they fit on one row.');
        const shortBefore = await shortStack.locator('.circle-member--regular').evaluateAll(members => members.map(member => member.getBoundingClientRect().left));
        assert(shortBefore[1] - shortBefore[0] >= 42);
        await shortStack.hover();
        const shortAfter = await shortStack.locator('.circle-member--regular').evaluateAll(members => members.map(member => member.getBoundingClientRect().left));
        assert(shortAfter[1] - shortAfter[0] >= 42);
        await shortStack.click();
        assert.equal(await shortStack.getAttribute('aria-expanded'), 'true');
        await page.mouse.move(760, 560);
        const shortAfterLeave = await shortStack.locator('.circle-member--regular').evaluateAll(members => members.map(member => member.getBoundingClientRect().left));
        assert(shortAfterLeave[1] - shortAfterLeave[0] >= 42);
        await page.locator('#panel-left').evaluate(element => { element.style.width = '180px'; });
        await page.waitForFunction(() => document.querySelector('[data-circle-member-stack]').dataset.memberCompact === '1');
        const narrowBefore = await shortStack.locator('.circle-member--regular').evaluateAll(members => members.map(member => member.getBoundingClientRect().left));
        assert(narrowBefore[1] - narrowBefore[0] < 34);
        await shortStack.hover();
        const narrowAfter = await shortStack.locator('.circle-member--regular').evaluateAll(members => members.map(member => member.getBoundingClientRect().left));
        assert(narrowAfter[1] - narrowAfter[0] >= 42);
        await page.mouse.move(760, 560);
        await page.locator('#panel-left').evaluate(element => { element.style.width = '440px'; });
        await page.waitForFunction(() => document.querySelector('[data-circle-member-stack]').dataset.memberCompact === '0');
        const wideAgain = await shortStack.locator('.circle-member--regular').evaluateAll(members => members.map(member => member.getBoundingClientRect().left));
        assert(wideAgain[1] - wideAgain[0] >= 42);

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
        await page.mouse.move(760, 560);
        await renderMembers(page, 600, 1, 3, true, true);
        const resizedStack = page.locator('[data-circle-member-stack]');
        await page.locator('#panel-left').evaluate(element => {
            element.style.width = '250px';
            element.style.flexBasis = '250px';
        });
        await page.waitForFunction(() => document.querySelector('[data-circle-member-stack]').dataset.memberCapacity === '9', null, {timeout: 2000});
        assert.equal(await resizedStack.getAttribute('data-member-compact'), '1');
        assert.equal(await resizedStack.locator('.circle-member--regular:visible').count(), 3);
        const hybridContext = await browser.newContext({hasTouch: true});
        try {
            const hybridPage = await hybridContext.newPage();
            await renderMembers(hybridPage, 440, 1, 10, true);
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

            await hybridPage.mouse.move(760, 560);
            await renderMembers(hybridPage, 600, 1, 3, true, true);
            const hybridResizedStack = hybridPage.locator('[data-circle-member-stack]');
            assert.equal(await hybridResizedStack.getAttribute('data-member-compact'), '0');
            await hybridPage.locator('#panel-left').evaluate(element => {
                element.style.width = '250px';
                element.style.flexBasis = '250px';
            });
            await hybridPage.waitForFunction(() => document.querySelector('[data-circle-member-stack]').dataset.memberCompact === '1');
            assert.equal(await hybridResizedStack.getAttribute('aria-expanded'), 'false', 'Shrinking must collapse a previously separate touch layout.');
            const hybridNarrowPositions = await hybridResizedStack.locator('.circle-member--regular').evaluateAll(members => members.map(member => member.getBoundingClientRect().left));
            assert(hybridNarrowPositions[1] - hybridNarrowPositions[0] < 34);
            const hybridNarrowBounds = await hybridResizedStack.evaluate(stack => ({
                right: stack.getBoundingClientRect().right,
                actionRight: stack.querySelector('[data-circle-member-action="add"]').getBoundingClientRect().right
            }));
            assert(hybridNarrowBounds.actionRight <= hybridNarrowBounds.right + 1, 'The compact preview must stay inside the resized pane.');
        } finally {
            await hybridContext.close();
        }
        assert.deepEqual(errors, []);
        console.log('org_member_hover_dom_test: OK');
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
