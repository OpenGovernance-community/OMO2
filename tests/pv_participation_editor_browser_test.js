'use strict';
// Real PHP page and action payloads from isolated, already cleaned fixtures.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const {chromium} = require(process.argv[3] || 'playwright');
(async () => {
    const fixture = JSON.parse(fs.readFileSync(process.argv[2], 'utf8'));
    const browser = await chromium.launch({headless: true, channel: 'msedge'});
    try {
        const page = await browser.newPage({ignoreHTTPSErrors: true, viewport: {width: 1200, height: 900}});
        const errors = [], actions = [];
        let replaced = false;
        page.on('pageerror', error => errors.push(error.message));
        await page.route('**/omo/pv_participation.php?*', route => route.fulfill({contentType: 'text/html', body: fixture.html}));
        await page.route('**/omo/api/documents/pv/action.php*', route => {
            const post = new Map(Array.from((route.request().postData() || '').matchAll(/name="([^"]+)"\r\n\r\n([^\r]*)/g), match => [match[1], match[2]]));
            const action = post.get('action');
            actions.push({action, title: post.get('title')});
            let payload = fixture.responses[action] || {status: true};
            if (action === 'poll_updates' && replaced) payload = fixture.responses.poll_after_replacement;
            if (action === 'pass_pv_editor') replaced = true;
            if (action === 'claim_pv_editor') replaced = false;
            return route.fulfill({contentType: 'application/json', body: JSON.stringify(payload)});
        });
        await page.goto('https://localtest.me/omo/pv_participation.php?token=fixture');
        await page.waitForFunction(() => document.querySelector('[data-omo-pv-editor-root]')?.dataset.omoPvEditorReady === '1');
        assert.equal(await page.locator('[data-omo-pv-editor-root]').getAttribute('data-omo-pv-editor-user-id'), '0');
        const groups = page.locator('[data-omo-pv-editor-add-group]');
        const sort = page.locator('[data-omo-pv-sort-menu]');
        const secretary = page.locator('[data-omo-pv-claim-secretary]');
        assert.equal(await groups.isVisible(), true);
        assert.equal(await sort.isVisible(), true);
        assert.equal(await secretary.getAttribute('data-omo-pv-secretary-action'), 'pass_pv_editor');
        await groups.click();
        const title = page.locator('[data-omo-pv-group-title]').first();
        await title.waitFor();
        await title.fill('Renamed group');
        await page.locator('[data-omo-pv-group-title-save]').first().click();
        await page.waitForFunction(() => !document.querySelector('[data-omo-pv-group-title-save]').disabled);
        assert(actions.some(entry => entry.action === 'update_group' && entry.title === 'Renamed group'));
        await sort.locator('summary').click();
        await page.locator('[name="omo_pv_sort_mode"][value="priority"]').check();
        await page.locator('[data-omo-pv-sort-submit]').click();
        await page.waitForFunction(() => document.querySelectorAll('[data-omo-pv-point-drag-handle]').length >= 2);
        assert.equal(await sort.isVisible(), true, 'Sorting keeps the token permissions.');
        await secretary.click();
        await page.waitForFunction(() => document.querySelector('[data-omo-pv-claim-secretary]').dataset.omoPvSecretaryAction === 'claim_pv_editor');
        assert.equal(await secretary.isVisible(), true, 'Polling offers reclaim when another editor takes the hand.');
        await secretary.click();
        await page.waitForFunction(() => document.querySelector('[data-omo-pv-claim-secretary]').dataset.omoPvSecretaryAction === 'pass_pv_editor');
        assert.equal(await groups.isVisible(), true);
        assert.equal(await sort.isVisible(), true);
        assert(actions.some(entry => entry.action === 'pass_pv_editor') && actions.some(entry => entry.action === 'claim_pv_editor'));
        assert.deepEqual(errors, []);
        console.log('pv_participation_editor_browser_test: OK (anonymous editor, create/rename groups, sorting, drag handles, handover, reclaim, polling)');
    } finally { await browser.close(); }
})().catch(error => {console.error(error); process.exitCode = 1;});
