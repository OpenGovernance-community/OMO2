'use strict';
const assert = require('node:assert/strict');
const path = require('node:path');
const {chromium} = require(process.argv[2] || 'playwright');

function panel(added) {
    return `<section data-lms-mission-dependency-manager data-parcours-id="7" data-mission-id="42">
        <button type="button" data-lms-open-mission-dependency-picker>Choose prerequisite</button>
        ${added ? '<article data-required-mission-id="41"><button type="button" data-lms-remove-mission-dependency data-required-mission-id="41">Remove prerequisite</button></article>' : '<p>No prerequisites</p>'}
        <div data-lms-mission-dependency-picker hidden><input type="search" data-lms-mission-dependency-picker-search>
        <button type="button" data-lms-close-mission-dependency-picker>Close</button>
        ${added ? '' : '<div data-lms-mission-dependency-picker-item data-search-text="Prerequisite"><button type="button" data-lms-add-mission-dependency-id="41">Add prerequisite</button></div>'}</div>
    </section>`;
}
(async () => {
    const browser = await chromium.launch({headless: true, channel: 'chrome'});
    try {
        const page = await browser.newPage();
        const requests = [];
        let fail = false;
        let delayed = null;
        await page.route('https://lms-fixture.invalid/**', route => route.fulfill({contentType: 'text/html', body: '<body></body>'}));
        await page.goto('https://lms-fixture.invalid/');
        await page.setContent(`<div id="drawer-content" style="height:240px;overflow:auto">
            <form id="formulaire-edit"><input name="title" value="Saved title"><textarea name="resume">Saved summary</textarea>
            <input name="video" value="https://vimeo.com/1"><div contenteditable="true" id="html-editor">Saved content</div></form>
            ${panel(false)}<section id="homework"><input value="Unsaved homework"></section><section id="questions"><input value="Unsaved answer"></section><div style="height:1000px"></div></div>`);
        await page.evaluate(() => {
            window.omoLmsCatalogConfig = {
                lmsIndexText: {addPrerequisiteError: 'Add failed', removePrerequisiteError: 'Remove failed'},
                lmsMissionDependencyAddPath: '/add', lmsMissionDependencyRemovePath: '/remove'
            };
            window.notifications = [];
            window.omoNotify = (message, type) => notifications.push({message, type});
            window.confirm = () => true;
            window.openDrawerFromUrl = () => { throw new Error('The whole editor must never be reloaded'); };
            window.savedNodes = ['formulaire-edit', 'html-editor', 'homework', 'questions'].map(id => document.getElementById(id));
        });
        await page.addScriptTag({path: path.join(__dirname, '../omo/api/lms/catalog.js')});
        await page.evaluate(() => initMissionDependencyManager());
        await page.locator('[name="title"]').fill('Unsaved title');
        await page.locator('[name="resume"]').fill('Unsaved summary');
        await page.locator('[name="video"]').fill('https://vimeo.com/999');
        await page.locator('#html-editor').fill('Unsaved formatted content');
        await page.route('https://lms-fixture.invalid/add', async route => {
            requests.push('add');
            if (delayed) await delayed;
            await route.fulfill({status: fail ? 403 : 200, contentType: 'application/json', body: JSON.stringify(fail ? {success: false, message: 'Permission denied'} : {success: true, dependenciesHtml: panel(true)})});
        });
        await page.route('https://lms-fixture.invalid/remove', route => {
            requests.push('remove');
            return route.fulfill({contentType: 'application/json', body: JSON.stringify({success: true, dependenciesHtml: panel(false)})});
        });
        const preserved = async () => {
            assert.equal(await page.locator('[name="title"]').inputValue(), 'Unsaved title');
            assert.equal(await page.locator('[name="resume"]').inputValue(), 'Unsaved summary');
            assert.equal(await page.locator('[name="video"]').inputValue(), 'https://vimeo.com/999');
            assert.equal(await page.locator('#html-editor').textContent(), 'Unsaved formatted content');
            assert(await page.evaluate(() => savedNodes.every(node => node.isConnected && document.getElementById(node.id) === node)), 'Form, HTML editor and neighboring blocks keep their DOM identity.');
        };
        const add = async () => {
            await page.locator('[data-lms-open-mission-dependency-picker]').click();
            await page.locator('[data-lms-add-mission-dependency-id]').click();
        };
        await add();
        await page.locator('[data-lms-remove-mission-dependency]').waitFor();
        await preserved();
        await page.locator('[data-lms-remove-mission-dependency]').click();
        await page.locator('[data-lms-add-mission-dependency-id]').waitFor({state: 'attached'});
        await preserved();
        // The replacement panel is interactive and failures preserve the draft and enable retry.
        fail = true;
        await add();
        await page.waitForFunction(() => notifications.length === 1);
        assert.equal(await page.locator('[data-lms-add-mission-dependency-id]').isEnabled(), true);
        await preserved();
        fail = false;
        await page.evaluate(() => { document.getElementById('drawer-content').scrollTop = 120; });
        const scroll = await page.locator('#drawer-content').evaluate(el => el.scrollTop);
        await page.evaluate(() => document.querySelector('[data-lms-add-mission-dependency-id]').click());
        await page.locator('[data-lms-remove-mission-dependency]').waitFor();
        assert.equal(await page.locator('#drawer-content').evaluate(el => el.scrollTop), scroll);
        await preserved();
        // Late responses must not overwrite another mission after navigation.
        await page.locator('[data-lms-remove-mission-dependency]').click();
        await page.locator('[data-lms-add-mission-dependency-id]').waitFor({state: 'attached'});
        let release;
        delayed = new Promise(resolve => { release = resolve; });
        await add();
        await page.waitForFunction(() => document.querySelector('[data-lms-add-mission-dependency-id]').disabled);
        await page.evaluate(() => { document.getElementById('drawer-content').innerHTML = '<p id="other-mission">Another mission</p>'; });
        release();
        await page.waitForResponse(response => response.url().endsWith('/add'));
        assert.equal(await page.locator('#other-mission').textContent(), 'Another mission');
        assert.deepEqual(requests, ['add', 'remove', 'add', 'add', 'remove', 'add']);
        console.log('PASS prerequisite add/remove, draft and editor identity, retry, scroll and stale responses');
    } finally { await browser.close(); }
})().catch(error => {console.error(error); process.exitCode = 1;});
